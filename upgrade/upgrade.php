<?php
/* ========================================================================
 * Open eClass 3.6
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2017  Greek Universities Network - GUnet
 * A full copyright notice can be read in "/info/copyright.txt".
 * For a full list of contributors, see "credits.txt".
 *
 * Open eClass is an open platform distributed in the hope that it will
 * be useful (without any warranty), under the terms of the GNU (General
 * Public License) as published by the Free Software Foundation.
 * The full license can be read in "/info/license/license_gpl.txt".
 *
 * Contact address: GUnet Asynchronous eLearning Group,
 *                  Network Operations Center, University of Athens,
 *                  Panepistimiopolis Ilissia, 15784, Athens, Greece
 *                  e-mail: info@openeclass.org
 * ======================================================================== */

define('UPGRADE', true);

require_once '../include/baseTheme.php';
require_once 'include/lib/fileUploadLib.inc.php';
require_once 'include/lib/forcedownload.php';
require_once 'include/course_settings.php';
require_once 'include/mailconfig.php';
require_once 'modules/db/recycle.php';
require_once 'modules/db/foreignkeys.php';
require_once 'modules/auth/auth.inc.php';
require_once 'modules/h5p/classes/H5PHubUpdater.php';
require_once 'upgradeHelper.php';

stop_output_buffering();

require_once 'upgrade/functions.php';

set_time_limit(0);

if (php_sapi_name() == 'cli' and ! isset($_SERVER['REMOTE_ADDR'])) {
    $command_line = true;
} else {
    $command_line = false;
}

if ($command_line and isset($argv[1])) {
    $logfile_path = $argv[1];
} else {
    $logfile_path = "$webDir/courses";
}

load_global_messages();

if ($urlAppend[strlen($urlAppend) - 1] != '/') {
    $urlAppend .= '/';
}

// include_messages
require "lang/$language/common.inc.php";
$extra_messages = "config/{$language_codes[$language]}.inc.php";
if (file_exists($extra_messages)) {
    include $extra_messages;
} else {
    $extra_messages = false;
}
require "lang/$language/messages.inc.php";
if ($extra_messages) {
    include $extra_messages;
}

$pageName = $langUpgrade;

$auth_methods = array('imap', 'pop3', 'ldap', 'db');
$OK = "[<font color='green'> $langSuccessOk </font>]";
$BAD = "[<font color='red'> $langSuccessBad </font>]";

$tbl_options = 'DEFAULT CHARACTER SET=utf8 ENGINE=InnoDB';

// Coming from the admin tool or stand-alone upgrade?
$fromadmin = !isset($_POST['submit_upgrade']);

if (isset($_POST['login']) and isset($_POST['password'])) {
    if (!is_admin($_POST['login'], $_POST['password'])) {
        Session::Messages($langUpgAdminError, 'alert-warning');
        redirect_to_home_page('upgrade/');
    }
}

if (!$command_line and !(isset($_SESSION['is_admin']) and $_SESSION['is_admin'])) {
    redirect_to_home_page('upgrade/');
}

if (!DBHelper::tableExists('config')) {
    $tool_content .= "<div class='alert alert-warning'>$langUpgTooOld</div>";
    draw($tool_content, 0);
    exit;
}

if (!check_engine()) {
    $tool_content .= "<div class='alert alert-warning'>$langInnoDBMissing</div>";
    draw($tool_content, 0);
    exit;
}

// Make sure 'video' subdirectory exists and is writable
$videoDir = $webDir . '/video';
if (!file_exists($videoDir)) {
    if (!make_dir($videoDir)) {
        die($langUpgNoVideoDir);
    }
} elseif (!is_dir($videoDir)) {
    die($langUpgNoVideoDir2);
}

mkdir_or_error('courses/temp');
touch_or_error('courses/temp/index.php');
mkdir_or_error('courses/temp/pdf');
mkdir_or_error('courses/userimg');
touch_or_error('courses/userimg/index.php');
touch_or_error($webDir . '/video/index.php');
mkdir_or_error('courses/user_progress_data');
mkdir_or_error('courses/user_progress_data/cert_templates');
touch_or_error('courses/user_progress_data/cert_templates/index.php');
mkdir_or_error('courses/user_progress_data/badge_templates');
touch_or_error('courses/user_progress_data/badge_templates/index.php');
mkdir_or_error('courses/eportfolio');
touch_or_error('courses/eportfolio/index.php');
mkdir_or_error('courses/eportfolio/userbios');
touch_or_error('courses/eportfolio/userbios/index.php');
mkdir_or_error('courses/eportfolio/work_submissions');
touch_or_error('courses/eportfolio/work_submissions/index.php');
mkdir_or_error('courses/eportfolio/mydocs');
touch_or_error('courses/eportfolio/mydocs/index.php');

// ********************************************
// upgrade config.php
// *******************************************

$default_student_upload_whitelist = 'pdf, ps, eps, tex, latex, dvi, texinfo, texi, zip, rar, tar, bz2, gz, 7z, xz, lha, lzh, z, Z, doc, docx, odt, ods, ott, sxw, stw, fodt, txt, rtf, dot, mcw, wps, xls, xlsx, xlt, ods, ots, sxc, stc, fods, uos, csv, ppt, pps, pot, pptx, ppsx, odp, otp, sxi, sti, fodp, uop, potm, odg, otg, sxd, std, fodg, odb, mdb, ttf, otf, jpg, jpeg, png, gif, bmp, tif, tiff, psd, dia, svg, ppm, xbm, xpm, ico, avi, asf, asx, wm, wmv, wma, dv, mov, moov, movie, mp4, mpg, mpeg, 3gp, 3g2, m2v, aac, m4a, flv, f4v, m4v, mp3, swf, webm, ogv, ogg, mid, midi, aif, rm, rpm, ram, wav, mp2, m3u, qt, vsd, vss, vst, pptx, ppsx, zipx';
$default_teacher_upload_whitelist = 'html, js, css, xml, xsl, cpp, c, java, m, h, tcl, py, sgml, sgm, ini, ds_store, dtd, xsd, woff2, ppsm, aia, hex, jqz, jm, data, jar, glo, epub, djvu';

if (!isset($_POST['submit2']) and isset($_SESSION['is_admin']) and $_SESSION['is_admin'] and !$command_line) {
    if (version_compare(PHP_VERSION, '7.0') < 0) {
        $tool_content .= "<div class='alert alert-danger'>$langWarnAboutPHP</div>";
    }
    if (!in_array(get_config('email_transport'), array('smtp', 'sendmail')) and
            !get_config('email_announce')) {
        $tool_content .= "<div class='alert alert-info'>$langEmailSendWarn</div>";
    }

    $tool_content .= "<h5>$langRequiredPHP</h5>";
    $tool_content .= "<ul class='list-unstyled'>";
    warnIfExtNotLoaded('session');
    warnIfExtNotLoaded('pdo');
    warnIfExtNotLoaded('pdo_mysql');
    warnIfExtNotLoaded('gd');
    warnIfExtNotLoaded('mbstring');
    warnIfExtNotLoaded('xml');
    warnIfExtNotLoaded('dom');
    warnIfExtNotLoaded('zlib');
    warnIfExtNotLoaded('pcre');
    warnIfExtNotLoaded('curl');
    warnIfExtNotLoaded('zip');
    $tool_content .= "</ul><h5>$langOptionalPHP</h5>";
    $tool_content .= "<ul class='list-unstyled'>";
    warnIfExtNotLoaded('soap');
    warnIfExtNotLoaded('ldap');
    $tool_content .= "</ul>";

    $tool_content .= "
      <div class='form-wrapper'>
        <form class='form-horizontal' role='form' action='$_SERVER[SCRIPT_NAME]' method='post'>";

    if (get_config('email_transport', 'mail') == 'mail' and
            !get_config('email_announce')) {
        $head_content .= '<script>$(function () {' . $mail_form_js . '});</script>';
        mail_settings_form();
    }

    setGlobalContactInfo();
    $tool_content .= "
        <div class='panel panel-default'>
          <div class='panel-heading'>
            <h2 class='panel-title'>$langUpgContact</h2>
          </div>
          <div class='panel-body'>
            <fieldset>
          <div class='form-group'>
                <label class='col-sm-2 control-label' for='id_Institution'>$langInstituteShortName:</label>
                <div class='col-sm-10'>
              <input class='form-control' type='text' name='Institution' id='id_Institution' value='" . q($Institution) . "'>
            </div>
          </div>
          <div class='form-group'>
                <label class='col-sm-2 control-label' for='id_postaddress'>$langUpgAddress</label>
                <div class='col-sm-10'>
              <textarea class='form-control' rows='3' name='postaddress' id='id_postaddress'>" . q($postaddress) . "</textarea>
            </div>
          </div>
          <div class='form-group'>
                <label class='col-sm-2 control-label' for='id_telephone'>$langUpgTel</label>
                <div class='col-sm-10'>
              <input class='form-control' type='text' name='telephone' id='id_telephone' value='" . q($telephone) . "'>
            </div>
          </div>
          <div class='form-group'>
                <label class='col-sm-2 control-label' for='id_fax'>Fax:</label>
                <div class='col-sm-10'>
              <input class='form-control' type='text' name='fax' id='id_fax' value='" . q($fax) . "'>
            </div>
          </div>
          <div class='form-group'>
            <div class='col-md-12'>
              <input class='pull-right btn btn-primary' name='submit2' value='$langContinue &raquo;' type='submit'>
                </div>
              </div>
            </fieldset>
            </div>
          </div>
        </form>
      </div>";
    draw($tool_content, 0, null, $head_content);
} else {
    // Main part of upgrade starts here
    if ($command_line) {
        setGlobalContactInfo();
        $_POST['Institution'] = $Institution;
        $_POST['postaddress'] = $postaddress;
        $_POST['telephone'] = $telephone;
        $_POST['fax'] = $fax;
        if (!isset($_SERVER['SERVER_NAME'])) {
            $_SERVER['SERVER_NAME'] = parse_url($urlServer, PHP_URL_HOST);
        }
    }

    if (isset($_POST['email_transport'])) {
        store_mail_config();
    }
    $logdate = date("Y-m-d_G.i.s");
    $logfile = "log-$logdate.html";
    if (!($logfile_handle = @fopen("$logfile_path/$logfile", 'w'))) {
        $error = error_get_last();
        if ($command_line) {
            die("$langLogFileWriteError\n$error[message].\nTry: $argv[0] <logfile path>\n");
        } else {
            Session::Messages($langLogFileWriteError .
                '<br><i>' . q($error['message']) . '</i>');
            draw($tool_content, 0);
            exit;
        }
    }

    fwrite($logfile_handle, "<!DOCTYPE html><html><head><meta charset='UTF-8'>
      <title>Open eClass upgrade log of $logdate</title></head><body>\n");

    set_config('upgrade_begin', time());

    if (!$command_line) {
        $tool_content .= getInfoAreas();
        define('TEMPLATE_REMOVE_CLOSING_TAGS', true);
        draw($tool_content, 0);
    }

    Debug::setOutput(function ($message, $level) use ($logfile_handle, &$debug_error) {
        fwrite($logfile_handle, $message);
        if ($level > Debug::WARNING) {
            $debug_error = true;
        }
    });
    Debug::setLevel(Debug::WARNING);

    set_config('institution', $_POST['Institution']);
    set_config('postaddress', $_POST['postaddress']);
    set_config('phone', $_POST['telephone']);
    set_config('fax', $_POST['fax']);

    if (isset($emailhelpdesk)) {
        // Upgrade to 3.x-style config
        if (!copy('config/config.php', 'config/config_backup.php')) {
            die($langConfigError1);
        }

        if (!isset($durationAccount)) {
            $durationAccount = 4 * 30 * 24 * 60 * 60; // 4 years
        }

        set_config('site_name', $siteName);
        set_config('account_duration', $durationAccount);
        set_config('institution_url', $InstitutionUrl);
        set_config('email_sender', $emailAdministrator);
        set_config('admin_name', $administratorName . ' ' . $administratorSurname);
        set_config('email_helpdesk', $emailhelpdesk);
        if (isset($emailAnnounce) and $emailAnnounce) {
            set_config('email_announce', $emailAnnounce);
        }
        set_config('base_url', $urlServer);
        set_config('default_language', $language);
        if (isset($active_ui_languages)) {
            set_config('active_ui_languages', implode(' ', $active_ui_languages));
        } else {
            set_config('active_ui_languages', 'el en');
        }
        set_config('phpMyAdminURL', $phpMyAdminURL);
        set_config('phpSysInfoURL', $phpSysInfoURL);

        $new_conf = '<?php
/* ========================================================
 * Open eClass 3.x configuration file
 * Created by upgrade on ' . date('Y-m-d H:i') . '
 * ======================================================== */

$mysqlServer = ' . quote($mysqlServer) . ';
$mysqlUser = ' . quote($mysqlUser) . ';
$mysqlPassword = ' . quote($mysqlPassword) . ';
$mysqlMainDb = ' . quote($mysqlMainDb) . ';
';
        $fp = @fopen('config/config.php', 'w');
        if (!$fp) {
            updateInfo(0.01, $langConfigError3);
            exit;
        }
        fwrite($fp, $new_conf);
        fclose($fp);
    }
    // ****************************************************
    //      upgrade database
    // ****************************************************


    // Create or upgrade config table
    if (DBHelper::fieldExists('config', 'id')) {
        Database::get()->query("RENAME TABLE config TO old_config");
        Database::get()->query("CREATE TABLE `config` (
                         `key` VARCHAR(32) NOT NULL,
                         `value` VARCHAR(255) NOT NULL,
                         PRIMARY KEY (`key`)) $tbl_options");
        Database::get()->query("INSERT INTO config
                         SELECT `key`, `value` FROM old_config
                         GROUP BY `key`");
        Database::get()->query("DROP TABLE old_config");
    }
    $oldversion = get_config('version');
    Database::get()->query("INSERT IGNORE INTO `config` (`key`, `value`) VALUES
                    ('dont_display_login_form', '0'),
                    ('email_required', '0'),
                    ('email_from', '1'),
                    ('am_required', '0'),
                    ('dropbox_allow_student_to_student', '0'),
                    ('dropbox_allow_personal_messages', '0'),
                    ('enable_social_sharing_links', '0'),
                    ('block_username_change', '0'),
                    ('enable_mobileapi', '0'),
                    ('display_captcha', '0'),
                    ('insert_xml_metadata', '0'),
                    ('doc_quota', '200'),
                    ('dropbox_quota', '100'),
                    ('video_quota', '100'),
                    ('group_quota', '100'),
                    ('course_multidep', '0'),
                    ('user_multidep', '0'),
                    ('restrict_owndep', '0'),
                    ('restrict_teacher_owndep', '0'),
                    ('allow_teacher_clone_course', '0')");

    // upgrade from versions < 3.0 is not possible
    if (version_compare($oldversion, '3.0', '<') or (!isset($oldversion))) {
        updateInfo(-2, $langUpgTooOld, 1);
        set_config('upgrade_begin', '');
        exit;
    }

    updateInfo(-1, $langUpgradeBase . " " . $mysqlMainDb);
    // // ----------------------------------
    // creation of indices
    // ----------------------------------
    updateInfo(-1, $langIndexCreation);

    // -----------------------------------
    // upgrade queries for 3.1
    // -----------------------------------
    if (version_compare($oldversion, '3.1', '<')) {
        updateInfo(-1, sprintf($langUpgForVersion, '3.1'));
        if (!DBHelper::fieldExists('course_user', 'document_timestamp')) {
            Database::get()->query("ALTER TABLE `course_user` ADD document_timestamp DATETIME NOT NULL,
                CHANGE `reg_date` `reg_date` DATETIME NOT NULL");
            Database::get()->query("UPDATE `course_user` SET document_timestamp = NOW()");
        }

        if (get_config('course_guest') == '') {
            set_config('course_guest', 'link');
        }

        // fix agenda entries without duration
        Database::get()->query("UPDATE agenda SET duration = '0:00' WHERE duration = ''");
        // Fix wiki last_version id's
        Database::get()->query("UPDATE wiki_pages SET last_version = (SELECT MAX(id) FROM wiki_pages_content WHERE pid = wiki_pages.id)");

        Database::get()->query("CREATE TABLE IF NOT EXISTS module_disable (module_id int(11) NOT NULL PRIMARY KEY)");
        DBHelper::fieldExists('assignment', 'submission_type') or
            Database::get()->query("ALTER TABLE `assignment` ADD `submission_type` TINYINT NOT NULL DEFAULT '0' AFTER `comments`");
        DBHelper::fieldExists('assignment_submit', 'submission_text') or
            Database::get()->query("ALTER TABLE `assignment_submit` ADD `submission_text` MEDIUMTEXT NULL DEFAULT NULL AFTER `file_name`");
        Database::get()->query("UPDATE `assignment` SET `max_grade` = 10 WHERE `max_grade` IS NULL");
        Database::get()->query("ALTER TABLE `assignment` CHANGE `max_grade` `max_grade` FLOAT NOT NULL DEFAULT '10'");
        // default assignment end date value should be null instead of 0000-00-00 00:00:00
        Database::get()->query("ALTER TABLE `assignment` CHANGE `deadline` `deadline` DATETIME NULL DEFAULT NULL");
        Database::get()->query("UPDATE `assignment` SET `deadline` = NULL WHERE `deadline` = '0000-00-00 00:00:00'");
        // improve primary key for table exercise_answer
        Database::get()->query("CREATE TABLE IF NOT EXISTS `tag_element_module` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    `course_id` int(11) NOT NULL,
                    `module_id` int(11) NOT NULL,
                    `element_id` int(11) NOT NULL,
                    `user_id` int(11) NOT NULL,
                    `date` DATETIME DEFAULT NULL,
                    `tag_id` int(11) NOT NULL)");
        DBHelper::indexExists('tag_element_module', 'tag_element_index') or
            Database::get()->query("CREATE INDEX `tag_element_index` ON `tag_element_module` (course_id, module_id, element_id)");
        // Tag tables upgrade
        if (DBHelper::fieldExists('tags', 'tag')) {
            $tags = Database::get()->queryArray("SELECT * FROM tags");
            $module_ids = array(
                'work'          =>  MODULE_ID_ASSIGN,
                'announcement'  =>  MODULE_ID_ANNOUNCE,
                'exe'           =>  MODULE_ID_EXERCISE
            );
            foreach ($tags as $tag) {
                $first_tag_id = Database::get()->querySingle("SELECT `id` FROM `tags` WHERE `tag` = ?s ORDER BY `id` ASC", $tag->tag)->id;
                Database::get()->query("INSERT INTO `tag_element_module` (`module_id`,`element_id`, `tag_id`)
                                        VALUES (?d, ?d, ?d)", $module_ids[$tag->element_type], $tag->element_id, $first_tag_id);
            }
            // keep one instance of each tag (the one with the lowest id)
            Database::get()->query("DELETE t1 FROM tags t1, tags t2 WHERE t1.id > t2.id AND t1.tag = t2.tag");
            Database::get()->query("ALTER TABLE tags DROP COLUMN `element_type`, "
                    . "DROP COLUMN `element_id`, DROP COLUMN `user_id`, DROP COLUMN `date`, DROP COLUMN `course_id`");
            Database::get()->query("ALTER TABLE tags CHANGE `tag` `name` varchar (255)");
            Database::get()->query("ALTER TABLE tags ADD UNIQUE KEY (name)");
            Database::get()->query("RENAME TABLE `tags` TO `tag`");
        }
        Database::get()->query("CREATE TABLE IF NOT EXISTS tag (
            `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(255) NOT NULL,
            UNIQUE KEY (name)) $tbl_options");

        if (!DBHelper::fieldExists('blog_post', 'commenting')) {
            Database::get()->query("ALTER TABLE `blog_post` ADD `commenting` TINYINT NOT NULL DEFAULT '1' AFTER `views`");
        }
        Database::get()->query("UPDATE unit_resources SET type = 'videolink' WHERE type = 'videolinks'");

        // unlink files that were used with the old theme import mechanism
        @unlink("$webDir/template/default/img/bcgr_lines_petrol_les saturation.png");
        @unlink("$webDir/template/default/img/eclass-new-logo_atoms.png");
        @unlink("$webDir/template/default/img/OpenCourses_banner_Color_theme1-1.png");
        @unlink("$webDir/template/default/img/banner_Sketch_empty-1-2.png");
        @unlink("$webDir/template/default/img/eclass-new-logo_sketchy.png");
        @unlink("$webDir/template/default/img/Light_sketch_bcgr2-1.png");
        @unlink("$webDir/template/default/img/Open-eClass-4-1-1.jpg");
        @unlink("$webDir/template/default/img/eclass_ice.png");
        @unlink("$webDir/template/default/img/eclass-new-logo_ice.png");
        @unlink("$webDir/template/default/img/ice.png");
        @unlink("$webDir/template/default/img/eclass_classic2-1-1.png");
        @unlink("$webDir/template/default/img/eclass-new-logo_classic.png");
    }

    // -----------------------------------
    // upgrade queries for 3.2
    // -----------------------------------
    if (version_compare($oldversion, '3.2', '<')) {
        updateInfo(-1, sprintf($langUpgForVersion, '3.2'));
        set_config('ext_bigbluebutton_enabled',
            Database::get()->querySingle("SELECT COUNT(*) AS count FROM bbb_servers WHERE enabled='true'")->count > 0? '1': '0');

        Database::get()->query("CREATE TABLE IF NOT EXISTS `custom_profile_fields` (
                                `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                `shortname` VARCHAR(255) NOT NULL,
                                `name` MEDIUMTEXT NOT NULL,
                                `description` MEDIUMTEXT NULL DEFAULT NULL,
                                `datatype` VARCHAR(255) NOT NULL,
                                `categoryid` INT(11) NOT NULL DEFAULT 0,
                                `sortorder`  INT(11) NOT NULL DEFAULT 0,
                                `required` TINYINT NOT NULL DEFAULT 0,
                                `visibility` TINYINT NOT NULL DEFAULT 0,
                                `user_type` TINYINT NOT NULL,
                                `registration` TINYINT NOT NULL DEFAULT 0,
                                `data` TEXT NULL DEFAULT NULL) $tbl_options");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `custom_profile_fields_data` (
                                `user_id` MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT 0,
                                `field_id` INT(11) NOT NULL,
                                `data` TEXT NOT NULL,
                                PRIMARY KEY (`user_id`, `field_id`)) $tbl_options");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `custom_profile_fields_data_pending` (
                                `user_request_id` INT(11) NOT NULL DEFAULT 0,
                                `field_id` INT(11) NOT NULL,
                                `data` TEXT NOT NULL,
                                PRIMARY KEY (`user_request_id`, `field_id`)) $tbl_options");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `custom_profile_fields_category` (
                                `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                `name` MEDIUMTEXT NOT NULL,
                                `sortorder`  INT(11) NOT NULL DEFAULT 0) $tbl_options");


        // Autojudge fields
        if (!DBHelper::fieldExists('assignment', 'auto_judge')) {
            Database::get()->query("ALTER TABLE `assignment`
                ADD `auto_judge` TINYINT(1) NOT NULL DEFAULT 0,
                ADD `auto_judge_scenarios` TEXT,
                ADD `lang` VARCHAR(10) NOT NULL DEFAULT ''");
            Database::get()->query("ALTER TABLE `assignment_submit`
                ADD `auto_judge_scenarios_output` TEXT");
        }

        if (!DBHelper::fieldExists('link', 'user_id')) {
            Database::get()->query("ALTER TABLE `link` ADD `user_id` INT(11) DEFAULT 0 NOT NULL");
        }
        if (!DBHelper::fieldExists('exercise', 'ip_lock')) {
            Database::get()->query("ALTER TABLE `exercise` ADD `ip_lock` TEXT NULL DEFAULT NULL");
        }
        if (!DBHelper::fieldExists('exercise', 'password_lock')) {
            Database::get()->query("ALTER TABLE `exercise` ADD `password_lock` VARCHAR(255) NULL DEFAULT NULL");
        }
        // Recycle object table
        Database::get()->query("CREATE TABLE IF NOT EXISTS `recyclebin` (
            `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `tablename` varchar(100) NOT NULL,
            `entryid` int(11) NOT NULL,
            `entrydata` varchar(4000) NOT NULL,
            KEY `entryid` (`entryid`), KEY `tablename` (`tablename`)) $tbl_options");

        // Auto-enroll rules tables
        Database::get()->query("CREATE TABLE IF NOT EXISTS `autoenroll_rule` (
            `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `status` TINYINT(4) NOT NULL DEFAULT 0)");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `autoenroll_rule_department` (
            `rule` INT(11) NOT NULL,
            `department` INT(11) NOT NULL,
            PRIMARY KEY (rule, department),
            FOREIGN KEY (rule) REFERENCES autoenroll_rule(id) ON DELETE CASCADE,
            FOREIGN KEY (department) REFERENCES hierarchy(id) ON DELETE CASCADE)");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `autoenroll_course` (
            `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `rule` INT(11) NOT NULL DEFAULT 0,
            `course_id` INT(11) NOT NULL,
            FOREIGN KEY (rule) REFERENCES autoenroll_rule(id) ON DELETE CASCADE,
            FOREIGN KEY (course_id) REFERENCES course(id) ON DELETE CASCADE)");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `autoenroll_department` (
            `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `rule` INT(11) NOT NULL DEFAULT 0,
            `department_id` INT(11) NOT NULL,
            FOREIGN KEY (rule) REFERENCES autoenroll_rule(id) ON DELETE CASCADE,
            FOREIGN KEY (department_id) REFERENCES hierarchy(id) ON DELETE CASCADE)");

        // Abuse report table
        Database::get()->query("CREATE TABLE IF NOT EXISTS `abuse_report` (
            `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `rid` INT(11) NOT NULL,
            `rtype` VARCHAR(50) NOT NULL,
            `course_id` INT(11) NOT NULL,
            `reason` VARCHAR(50) NOT NULL DEFAULT '',
            `message` TEXT NOT NULL,
            `timestamp` INT(11) NOT NULL DEFAULT 0,
            `user_id` MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT 0,
            `status` TINYINT(1) NOT NULL DEFAULT 1,
            INDEX `abuse_report_index_1` (`rid`, `rtype`, `user_id`, `status`),
            INDEX `abuse_report_index_2` (`course_id`, `status`)) $tbl_options");

        // Delete old key 'language' (it has been replaced by 'default_language')
        Database::get()->query("DELETE FROM config WHERE `key` = 'language'");

        // Add grading scales table
        Database::get()->query("CREATE TABLE IF NOT EXISTS `grading_scale` (
            `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `title` varchar(255) NOT NULL,
            `scales` text NOT NULL,
            `course_id` int(11) NOT NULL,
            KEY `course_id` (`course_id`)) $tbl_options");

        // Add grading_scale_id field to assignments
        if (!DBHelper::fieldExists('assignment', 'grading_scale_id')) {
            Database::get()->query("ALTER TABLE `assignment` ADD `grading_scale_id` INT(11) NOT NULL DEFAULT '0' AFTER `max_grade`");
        }

        // Add show results to participants field
        if (!DBHelper::fieldExists('poll', 'show_results')) {
            Database::get()->query("ALTER TABLE `poll` ADD `show_results` TINYINT NOT NULL DEFAULT '0'");
        }

        Database::get()->query("CREATE TABLE IF NOT EXISTS `poll_to_specific` (
            `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `user_id` int(11) NULL,
            `group_id` int(11) NULL,
            `poll_id` int(11) NOT NULL ) $tbl_options");

        if (!DBHelper::fieldExists('poll', 'assign_to_specific')) {
            Database::get()->query("ALTER TABLE `poll` ADD `assign_to_specific` TINYINT NOT NULL DEFAULT '0'");
        }
        Database::get()->query("CREATE TABLE IF NOT EXISTS `exercise_to_specific` (
                    `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    `user_id` int(11) NULL,
                    `group_id` int(11) NULL,
                    `exercise_id` int(11) NOT NULL ) $tbl_options");
        if (!DBHelper::fieldExists('exercise', 'assign_to_specific')) {
            Database::get()->query("ALTER TABLE `exercise` ADD `assign_to_specific` TINYINT NOT NULL DEFAULT '0'");
        }
        // This is needed for ALTER IGNORE TABLE
        Database::get()->query('SET SESSION old_alter_table = 1');

        // Unique and foreign keys for user_department table
        if (DBHelper::indexExists('user_department', 'udep_id')) {
            Database::get()->query('DROP INDEX `udep_id` ON user_department');
        }

        if (!DBHelper::indexExists('user_department', 'udep_unique')) {
            Database::get()->queryFunc('SELECT user_department.id FROM user
                        RIGHT JOIN user_department ON user.id = user_department.user
                    WHERE user.id IS NULL', function ($item) {
                Recycle::deleteObject('user_department', $item->id, 'id');
            });
            Database::get()->queryFunc('SELECT user_department.id FROM hierarchy
                        RIGHT JOIN user_department ON hierarchy.id = user_department.department
                    WHERE hierarchy.id IS NULL', function ($item) {
                Recycle::deleteObject('user_department', $item->id, 'id');
            });
            Database::get()->query('ALTER TABLE user_department CHANGE `user` `user` INT(11) NOT NULL');
            Database::get()->query('ALTER IGNORE TABLE `user_department`
                ADD UNIQUE KEY `udep_unique` (`user`,`department`),
                ADD FOREIGN KEY (user) REFERENCES user(id) ON DELETE CASCADE,
                ADD FOREIGN KEY (department) REFERENCES hierarchy(id) ON DELETE CASCADE');
        }

        // Unique and foreign keys for course_department table
        if (DBHelper::indexExists('course_department', 'cdep_index')) {
            Database::get()->query('DROP INDEX `cdep_index` ON course_department');
        }
        if (!DBHelper::indexExists('course_department', 'cdep_unique')) {
            Database::get()->queryFunc('SELECT course_department.id FROM course
                        RIGHT JOIN course_department ON course.id = course_department.course
                    WHERE course.id IS NULL', function ($item) {
                Recycle::deleteObject('course_department', $item->id, 'id');
            });
            Database::get()->queryFunc('SELECT course_department.id FROM hierarchy
                        RIGHT JOIN course_department ON hierarchy.id = course_department.department
                    WHERE hierarchy.id IS NULL', function ($item) {
                Recycle::deleteObject('course_department', $item->id, 'id');
            });
            Database::get()->query('ALTER IGNORE TABLE `course_department`
                ADD UNIQUE KEY `cdep_unique` (`course`,`department`),
                ADD FOREIGN KEY (course) REFERENCES course(id) ON DELETE CASCADE,
                ADD FOREIGN KEY (department) REFERENCES hierarchy(id) ON DELETE CASCADE');
        }

        // External authentication via Hybridauth
        Database::get()->query("INSERT IGNORE INTO `auth`
            (auth_id, auth_name, auth_title, auth_settings, auth_instructions, auth_default)
            VALUES
            (8, 'facebook', '', '', '', 0),
            (9, 'twitter', '', '', '', 0),
            (10, 'google', '', '', '', 0),
            (11, 'live', 'Microsoft Live Account', '', 'does not work locally', 0),
            (12, 'yahoo', '', '', 'does not work locally', 0),
            (13, 'linkedin', '', '', '', 0)");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `user_ext_uid` (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id INT(11) NOT NULL,
            auth_id INT(2) NOT NULL,
            uid VARCHAR(64) NOT NULL,
            UNIQUE KEY (user_id, auth_id),
            KEY (uid),
            FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE)
            $tbl_options");
        Database::get()->query("CREATE TABLE IF NOT EXISTS `user_request_ext_uid` (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_request_id INT(11) NOT NULL,
            auth_id INT(2) NOT NULL,
            uid VARCHAR(64) NOT NULL,
            UNIQUE KEY (user_request_id, auth_id),
            FOREIGN KEY (`user_request_id`) REFERENCES `user_request` (`id`) ON DELETE CASCADE)
            $tbl_options");

        if (!DBHelper::fieldExists('gradebook', 'start_date')) {
            Database::get()->query("ALTER TABLE `gradebook` ADD `start_date` DATETIME NOT NULL");
            Database::get()->query("UPDATE `gradebook` SET `start_date` = " . DBHelper::timeAfter(-6*30*24*60*60) . ""); // 6 months before
            $q = Database::get()->queryArray("SELECT gradebook_book.id, grade,`range` FROM gradebook, gradebook_activities, gradebook_book
                                                    WHERE gradebook_book.gradebook_activity_id = gradebook_activities.id
                                                    AND gradebook_activities.gradebook_id = gradebook.id");
            foreach ($q as $data) {
                Database::get()->query("UPDATE gradebook_book SET grade = $data->grade/$data->range WHERE id = $data->id");
            }
        }
        if (!DBHelper::fieldExists('gradebook', 'end_date')) {
            Database::get()->query("ALTER TABLE `gradebook` ADD `end_date` DATETIME NOT NULL");
            Database::get()->query("UPDATE `gradebook` SET `end_date` = " . DBHelper::timeAfter(6*30*24*60*60) . ""); // 6 months after
        }

        if (!DBHelper::fieldExists('attendance', 'start_date')) {
            Database::get()->query("ALTER TABLE `attendance` ADD `start_date` DATETIME NOT NULL");
            Database::get()->query("UPDATE `attendance` SET `start_date` = " . DBHelper::timeAfter(-6*30*24*60*60) . ""); // 6 months before
        }
        if (!DBHelper::fieldExists('attendance', 'end_date')) {
            Database::get()->query("ALTER TABLE `attendance` ADD `end_date` DATETIME NOT NULL");
            Database::get()->query("UPDATE `attendance` SET `end_date` = " . DBHelper::timeAfter(6*30*24*60*60) . ""); // 6 months after
        }
        // Cancelled exercises total weighting fix
        $exercises = Database::get()->queryArray("SELECT exercise.id AS id, exercise.course_id AS course_id, exercise_user_record.eurid AS eurid "
                . "FROM exercise_user_record, exercise "
                . "WHERE exercise_user_record.eid = exercise.id "
                . "AND exercise_user_record.total_weighting = 0 "
                . "AND exercise_user_record.attempt_status = 4");
        foreach ($exercises as $exercise) {
            $totalweight = Database::get()->querySingle("SELECT SUM(exercise_question.weight) AS totalweight
                                            FROM exercise_question, exercise_with_questions
                                            WHERE exercise_question.course_id = ?d
                                            AND exercise_question.id = exercise_with_questions.question_id
                                            AND exercise_with_questions.exercise_id = ?d", $exercise->course_id, $exercise->id)->totalweight;
            Database::get()->query("UPDATE exercise_user_record SET total_weighting = ?f WHERE eurid = ?d", $totalweight, $exercise->eurid);
        }

        if (DBHelper::fieldExists('link', 'hits')) { // not needed
           delete_field('link', 'hits');
        }

        Database::get()->query("CREATE TABLE IF NOT EXISTS `group_category` (
                                `id` INT(6) NOT NULL AUTO_INCREMENT,
                                `course_id` INT(11) NOT NULL,
                                `name` VARCHAR(255) NOT NULL,
                                `description` TEXT,
                                PRIMARY KEY (`id`, `course_id`)) $tbl_options");

        if (!DBHelper::fieldExists('group', 'category_id')) {
            Database::get()->query("ALTER TABLE `group` ADD `category_id` INT(11) NULL");
        }
        //Group Mapping due to group_id addition in group_properties table
        if (!DBHelper::fieldExists('group_properties', 'group_id')) {
            Database::get()->query("ALTER TABLE `group_properties` ADD `group_id` INT(11) NOT NULL DEFAULT 0");
            Database::get()->query("ALTER TABLE `group_properties` DROP PRIMARY KEY");

            $group_info = Database::get()->queryArray("SELECT * FROM group_properties");
            foreach ($group_info as $group) {
                $cid = $group->course_id;
                $self_reg = $group->self_registration;
                $multi_reg = $group->multiple_registration;
                $unreg = $group->allow_unregister;
                $forum = $group->forum;
                $priv_forum = $group->private_forum;
                $documents = $group->documents;
                $wiki = $group->wiki;
                $agenda = $group->agenda;

                Database::get()->query("DELETE FROM group_properties WHERE course_id = ?d", $cid);

                $num = Database::get()->queryArray("SELECT id FROM `group` WHERE course_id = ?d", $cid);

                foreach ($num as $group_num) {
                    $group_id = $group_num->id;
                    Database::get()->query("INSERT INTO `group_properties` (course_id, group_id, self_registration, allow_unregister, forum, private_forum, documents, wiki, agenda)
                                                    VALUES  (?d, ?d, ?d, ?d, ?d, ?d, ?d, ?d, ?d)", $cid, $group_id, $self_reg, $unreg, $forum, $priv_forum, $documents, $wiki, $agenda);
                }
                setting_set(SETTING_GROUP_MULTIPLE_REGISTRATION, $multi_reg, $cid);
            }
            Database::get()->query("ALTER TABLE `group_properties` ADD PRIMARY KEY (group_id)");
            delete_field('group_properties', 'multiple_registration');
        }

        Database::get()->query("CREATE TABLE IF NOT EXISTS `course_user_request` (
                        `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                        `uid` int(11) NOT NULL,
                        `course_id` int(11) NOT NULL,
                        `comments` text,
                        `status` int(11) NOT NULL,
                        `ts` datetime NOT NULL DEFAULT '1970-01-01 00:00:01',
                        PRIMARY KEY (`id`)) $tbl_options");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `poll_user_record` (
            `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `pid` INT(11) UNSIGNED NOT NULL DEFAULT 0,
            `uid` MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT 0,
            `email` VARCHAR(255) DEFAULT NULL,
            `email_verification` TINYINT(1) DEFAULT NULL,
            `verification_code` VARCHAR(255) DEFAULT NULL) $tbl_options");
        if (!DBHelper::fieldExists('poll_answer_record', 'poll_user_record_id')) {
            Database::get()->query("ALTER TABLE `poll_answer_record` "
                    . "ADD `poll_user_record_id` INT(11) NOT NULL AFTER `arid`");

            if ($user_records = Database::get()->queryArray("SELECT DISTINCT `pid`, `user_id` FROM poll_answer_record")) {
                foreach ($user_records as $user_record) {
                    $poll_user_record_id = Database::get()->query("INSERT INTO poll_user_record (pid, uid) VALUES (?d, ?d)", $user_record->pid, $user_record->user_id)->lastInsertID;
                    Database::get()->query("UPDATE poll_answer_record SET poll_user_record_id = ?d WHERE pid = ?d AND user_id = ?d", $poll_user_record_id, $user_record->pid, $user_record->user_id);
                }
            }
            Database::get()->query("ALTER TABLE `poll_answer_record` ADD FOREIGN KEY (`poll_user_record_id`) REFERENCES `poll_user_record` (`id`) ON DELETE CASCADE");
            delete_field('poll_answer_record', 'pid');
            delete_field('poll_answer_record', 'user_id');
        }
        DBHelper::indexExists('poll_user_record', 'poll_user_rec_id') or
            Database::get()->query("CREATE INDEX `poll_user_rec_id` ON poll_user_record(pid, uid)");
        //Removing Course Home Layout 2
        Database::get()->query("UPDATE course SET home_layout = 1 WHERE home_layout = 2");
    }

    // -----------------------------------
    // upgrade queries for 3.3
    // -----------------------------------
    if (version_compare($oldversion, '3.3', '<')) {
        updateInfo(-1, sprintf($langUpgForVersion, '3.3'));

        // Remove '0000-00-00' default dates and fix exercise weight fields
        Database::get()->query('ALTER TABLE `announcement`
            MODIFY `date` DATETIME NOT NULL,
            MODIFY `start_display` DATETIME DEFAULT NULL,
            MODIFY `stop_display` DATETIME DEFAULT NULL');
        Database::get()->query("UPDATE IGNORE announcement SET start_display=null
            WHERE start_display='0000-00-00 00:00:00'");
        Database::get()->query("UPDATE IGNORE announcement SET stop_display=null
            WHERE stop_display='0000-00-00 00:00:00'");
        Database::get()->query('ALTER TABLE `agenda`
            CHANGE `start` `start` DATETIME NOT NULL');
        Database::get()->query('ALTER TABLE `course`
            MODIFY `created` DATETIME DEFAULT NULL,
            MODIFY `start_date` DATE DEFAULT NULL,
            MODIFY `finish_date` DATE DEFAULT NULL');
        Database::get()->query("UPDATE IGNORE course SET start_date=null
                            WHERE start_date='0000-00-00 00:00:00'");
        Database::get()->query("UPDATE IGNORE course SET finish_date=null
                            WHERE finish_date='0000-00-00 00:00:00'");
        Database::get()->query('ALTER TABLE `course_weekly_view`
            MODIFY `start_week` DATE DEFAULT NULL,
            MODIFY `finish_week` DATE DEFAULT NULL');
        Database::get()->query('ALTER TABLE `course_weekly_view_activities`
            CHANGE `date` `date` DATETIME NOT NULL');
        Database::get()->query('ALTER TABLE `course_user_request`
            CHANGE `ts` `ts` DATETIME NOT NULL');
        Database::get()->query('ALTER TABLE `user`
            CHANGE `registered_at` `registered_at` DATETIME NOT NULL,
            CHANGE `expires_at` `expires_at` DATETIME NOT NULL');
        Database::get()->query('ALTER TABLE `loginout`
            CHANGE `when` `when` DATETIME NOT NULL');
        Database::get()->query('ALTER TABLE `personal_calendar`
            CHANGE `start` `start` datetime NOT NULL');
        Database::get()->query('ALTER TABLE `admin_calendar`
            CHANGE `start` `start` DATETIME NOT NULL');
        Database::get()->query('ALTER TABLE `loginout_summary`
            CHANGE `start_date` `start_date` DATETIME NOT NULL,
            CHANGE `end_date` `end_date` DATETIME NOT NULL');
        Database::get()->query('ALTER TABLE `document`
            CHANGE `date` `date` DATETIME NOT NULL,
            CHANGE `date_modified` `date_modified` DATETIME NOT NULL');
        Database::get()->query('ALTER TABLE `wiki_pages`
            CHANGE `ctime` `ctime` DATETIME NOT NULL,
            CHANGE `last_mtime` `last_mtime` DATETIME NOT NULL');
        Database::get()->query('ALTER TABLE `wiki_pages_content`
            CHANGE `mtime` `mtime` DATETIME NOT NULL');
        Database::get()->query('ALTER TABLE `wiki_locks`
            MODIFY `ltime_created` DATETIME DEFAULT NULL,
            MODIFY `ltime_alive` DATETIME DEFAULT NULL;');
        Database::get()->query('ALTER TABLE `poll`
            CHANGE `creation_date` `creation_date` DATETIME NOT NULL,
            CHANGE `start_date` `start_date` DATETIME DEFAULT NULL,
            CHANGE `end_date` `end_date` DATETIME DEFAULT NULL');
        Database::get()->query('ALTER TABLE `poll_answer_record`
            CHANGE `submit_date` `submit_date` DATETIME NOT NULL');
        Database::get()->query('ALTER TABLE `assignment`
            CHANGE `submission_date` `submission_date` DATETIME NOT NULL');
        Database::get()->query('ALTER TABLE `assignment_submit`
            CHANGE `submission_date` `submission_date` DATETIME NOT NULL');
        Database::get()->query('ALTER TABLE `exercise_user_record`
            CHANGE `record_start_date` `record_start_date` DATETIME NOT NULL,
            CHANGE `total_score` `total_score` FLOAT(11,2) NOT NULL DEFAULT 0,
            CHANGE `total_weighting` `total_weighting` FLOAT(11,2) DEFAULT 0');
        Database::get()->query('ALTER TABLE `exercise_answer_record`
            CHANGE `weight` `weight` FLOAT(11,2) DEFAULT NULL');
        Database::get()->query('ALTER TABLE `unit_resources`
            CHANGE `date` `date` DATETIME NOT NULL');
        Database::get()->query('ALTER TABLE `actions_summary`
            CHANGE `start_date` `start_date` DATETIME NOT NULL,
            CHANGE `end_date` `end_date` DATETIME NOT NULL');
        Database::get()->query('ALTER TABLE `logins`
            CHANGE `date_time` `date_time` DATETIME NOT NULL');

        // Fix incorrectly-graded fill-in-blanks questions
        Database::get()->queryFunc('SELECT question_id, answer, type
            FROM exercise_question, exercise_answer
            WHERE question_id = exercise_question.id AND
                  type in (?d, ?d) AND answer LIKE ?s',
            function ($item) {
                if (preg_match('#\[/?m[^]]#', $item->answer)) {
                    Database::get()->queryFunc('SELECT answer_record_id, answer, answer_id, weight
                        FROM exercise_user_record, exercise_answer_record
                        WHERE exercise_user_record.eurid = exercise_answer_record.eurid AND
                              exercise_answer_record.question_id = ?d AND
                              attempt_status IN (?d, ?d)',
                        function ($a) use ($item) {
                            static $answers, $weights;
                            $qid = $item->question_id;
                            if (!isset($answers[$qid])) {
                                // code from modules/exercise/exercise.class.php lines 865-878
                                list($answer, $answerWeighting) = explode('::', $item->answer);
                                $weights[$qid] = explode(',', $answerWeighting);
                                preg_match_all('#(?<=\[)(?!/?m])[^\]]+#', $answer, $match);
                                if ($item->type == FILL_IN_BLANKS_TOLERANT) {
                                    $expected = array_map(function ($m) {
                                           return strtr(mb_strtoupper($m, 'UTF-8'), 'ΆΈΉΊΌΎΏ', 'ΑΕΗΙΟΥΩ');
                                        }, $match[0]);
                                } else {
                                    $expected = $match[0];
                                }
                                $answers[$qid] = array_map(function ($str) {
                                        return preg_split('/\s*\|\s*/', $str);
                                    }, $expected);
                            }
                            if ($item->type == FILL_IN_BLANKS_TOLERANT) {
                                $choice = strtr(mb_strtoupper($a->answer, 'UTF-8'), 'ΆΈΉΊΌΎΏ', 'ΑΕΗΙΟΥΩ');
                            } else {
                                $choice = $a->answer;
                            }
                            $aid = $a->answer_id - 1;
                            $weight = in_array($choice, $answers[$qid][$aid]) ? $weights[$qid][$aid] : 0;
                            if ($weight != $a->weight) {
                                Database::get()->query('UPDATE exercise_answer_record
                                    SET weight = ?f WHERE answer_record_id = ?d',
                                    $weight, $a->answer_record_id);
                            }
                        }, $item->question_id, ATTEMPT_COMPLETED, ATTEMPT_PAUSED);
                }
            }, FILL_IN_BLANKS, FILL_IN_BLANKS_TOLERANT, '%[m%');

        // Fix duplicate exercise answer records
        Database::get()->queryFunc('SELECT COUNT(*) AS cnt,
                    MIN(answer_record_id) AS min_answer_record_id
                FROM exercise_answer_record
                GROUP BY eurid, question_id, answer, answer_id
                HAVING cnt > 1',
            function ($item) {
                $details = Database::get()->querySingle('SELECT * FROM exercise_answer_record
                    WHERE answer_record_id = ?d', $item->min_answer_record_id);
                if (is_null($details->answer)) {
                    Database::get()->query('DELETE FROM exercise_answer_record
                        WHERE eurid = ?d AND question_id = ?d AND answer IS NULL AND
                              answer_id = ?d AND answer_record_id > ?d',
                        $details->eurid, $details->question_id, $details->answer_id,
                        $item->min_answer_record_id);
                } else {
                    Database::get()->query('DELETE FROM exercise_answer_record
                        WHERE eurid = ?d AND question_id = ?d AND answer = ?s AND
                              answer_id = ?d AND answer_record_id > ?d',
                        $details->eurid, $details->question_id, $details->answer,
                        $details->answer_id, $item->min_answer_record_id);
                }
            });

        // Fix incorrect exercise answer grade totals
        Database::get()->query('CREATE TEMPORARY TABLE exercise_answer_record_total AS
            SELECT SUM(weight) AS TOTAL, exercise_answer_record.eurid AS eurid
                FROM exercise_user_record, exercise_answer_record
                WHERE exercise_user_record.eurid = exercise_answer_record.eurid AND
                      attempt_status = ?d
                GROUP BY eurid',
                ATTEMPT_COMPLETED);
        Database::get()->query('UPDATE exercise_user_record, exercise_answer_record_total
            SET exercise_user_record.total_score = exercise_answer_record_total.total
            WHERE exercise_user_record.eurid = exercise_answer_record_total.eurid AND
                  exercise_user_record.total_score <> exercise_answer_record_total.total');
        Database::get()->query('DROP TEMPORARY TABLE exercise_answer_record_total');

        // Fix duplicate link orders
        Database::get()->queryFunc('SELECT DISTINCT course_id, category FROM link
            GROUP BY course_id, category, `order` HAVING COUNT(*) > 1',
            function ($item) {
                $order = 0;
                foreach (Database::get()->queryArray('SELECT id FROM link
                    WHERE course_id = ?d AND category = ?d
                    ORDER BY `order`',
                    $item->course_id, $item->category) as $link) {
                        Database::get()->query('UPDATE link SET `order` = ?d
                            WHERE id = ?d', $order++, $link->id);
                }
            });

        Database::get()->query("UPDATE link SET `url` = '' WHERE `url` IS NULL");
        Database::get()->query("UPDATE link SET `title` = '' WHERE `title` IS NULL");
        Database::get()->query('ALTER TABLE link
            CHANGE `url` `url` TEXT NOT NULL,
            CHANGE `title` `title` TEXT NOT NULL');

        // Fix duplicate poll_question orders
        Database::get()->queryFunc('SELECT `pid`
                FROM `poll_question`
                GROUP BY `pid`, `q_position` HAVING COUNT(`pqid`) > 1',
                function ($item) {
                    $poll_questions = Database::get()->queryArray("SELECT * FROM `poll_question` WHERE pid = ?d", $item->pid);
                    $order = 1;
                    foreach ($poll_questions as $poll_question) {
                        Database::get()->query('UPDATE `poll_question` SET `q_position` = ?d
                                                    WHERE pqid = ?d', $order++, $poll_question->pqid);
                    }
                });
        if (!DBHelper::fieldExists('poll', 'public')) {
            Database::get()->query("ALTER TABLE `poll` ADD `public` TINYINT(1) NOT NULL DEFAULT 1 AFTER `active`");
            Database::get()->query("UPDATE `poll` SET `public` = 0");
        }

        // If Shibboleth auth is enabled, try reading current settings and
        // regenerate secure index if successful
        if (Database::get()->querySingle('SELECT auth_default FROM auth
                WHERE auth_name = ?s', 'shibboleth')->auth_default) {
            $secureIndexPath = $webDir . '/secure/index.php';
            $shib_vars = get_shibboleth_vars($secureIndexPath);
            if (count($shib_vars) and isset($shib_vars['uname'])) {
                $shib_config = array();
                foreach ($shib_vars as $shib_var => $shib_value) {
                    $shib_config['shib_' . $shib_var] = $shib_value;
                }
                update_shibboleth_endpoint($shib_config);
            }
        }
    }

    // -----------------------------------
    // upgrade queries for 3.4
    // -----------------------------------
    if (version_compare($oldversion, '3.4', '<')) {
        updateInfo(-1, sprintf($langUpgForVersion, '3.4'));

        // Conference table
        Database::get()->query("CREATE TABLE IF NOT EXISTS `conference` (
                        `conf_id` int(11) NOT NULL AUTO_INCREMENT,
                        `course_id` int(11) NOT NULL,
                        `conf_title` text NOT NULL,
                        `conf_description` text DEFAULT NULL,
                        `status` enum('active','inactive') DEFAULT 'active',
                        `start` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        `user_id` varchar(255) default '0',
                        `group_id` varchar(255) default '0',
                        PRIMARY KEY (`conf_id`,`course_id`)) $tbl_options");

        // create db entries about old chats
        $query = Database::get()->queryArray("SELECT id, code FROM course");
        foreach ($query as $codes) {
            $c = $codes->code;
            $chatfile = "$webDir/courses/$c/chat.txt";
            if (file_exists($chatfile) and filesize($chatfile) > 0) {
                $q_ins = Database::get()->query("INSERT INTO conference SET
                                                course_id = $codes->id,
                                                conf_title = '$langUntitledChat',
                                                status = 'active'");
                $last_conf_id = $q_ins->lastInsertID;
                $newchatfile = "$webDir/courses/$c/" . $last_conf_id . "_chat.txt";
                rename($chatfile, $newchatfile);
            }
        }

        // upgrade poll table (COLLES and ATTLS support)
        if (!DBHelper::fieldExists('poll', 'type')) {
            Database::get()->query("ALTER TABLE `poll` ADD `type` TINYINT(1) NOT NULL DEFAULT 0");
        }

        // upgrade bbb_session table
        if (DBHelper::tableExists('bbb_session')) {
            if (!DBHelper::fieldExists('bbb_session', 'end_date')) {
                Database::get()->query("ALTER TABLE `bbb_session` ADD `end_date` datetime DEFAULT NULL AFTER `start_date`");
            }
            Database::get()->query("RENAME TABLE bbb_session TO tc_session");
        }

        // upgrade bbb_servers table
        if (DBHelper::tableExists('bbb_servers')) {
            if (!DBHelper::fieldExists('bbb_servers', 'all_courses')) {
                Database::get()->query("ALTER TABLE bbb_servers ADD `type` varchar(255) NOT NULL DEFAULT 'bbb' AFTER id");
                Database::get()->query("ALTER TABLE bbb_servers ADD port varchar(255) DEFAULT NULL AFTER ip");
                Database::get()->query("ALTER TABLE bbb_servers ADD username varchar(255) DEFAULT NULL AFTER server_key");
                Database::get()->query("ALTER TABLE bbb_servers ADD password varchar(255) DEFAULT NULL AFTER username");
                Database::get()->query("ALTER TABLE bbb_servers ADD webapp varchar(255) DEFAULT NULL AFTER api_url");
                Database::get()->query("ALTER TABLE bbb_servers ADD screenshare varchar(255) DEFAULT NULL AFTER weight");
                Database::get()->query("ALTER TABLE bbb_servers ADD all_courses TINYINT(1) NOT NULL DEFAULT 1");
            }
            // rename `bbb_servers` to `tc_servers`
            if (DBHelper::tableExists('bbb_servers')) {
                Database::get()->query("RENAME TABLE bbb_servers TO tc_servers");
            }
        }

        // course external server table
        Database::get()->query("CREATE TABLE IF NOT EXISTS `course_external_server` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `course_id` int(11) NOT NULL,
            `external_server` int(11) NOT NULL,
            PRIMARY KEY (`id`),
            KEY (`external_server`, `course_id`)) $tbl_options");

        // drop trigger
        Database::get()->query("DROP TRIGGER IF EXISTS personal_calendar_settings_init");
        // update announcements
        Database::get()->query("UPDATE announcement SET `order` = 0");
        updateAnnouncementAdminSticky("admin_announcement");

        //Create FAQ table
        Database::get()->query("CREATE TABLE IF NOT EXISTS `faq` (
                            `id` int(11) NOT NULL AUTO_INCREMENT,
                            `title` text NOT NULL,
                            `body` text NOT NULL,
                            `order` int(11) NOT NULL,
                            PRIMARY KEY (`id`)) $tbl_options");

        //wall tables
        Database::get()->query("CREATE TABLE IF NOT EXISTS `wall_post` (
                            `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `course_id` INT(11) NOT NULL,
                            `user_id` MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT 0,
                            `content` TEXT DEFAULT NULL,
                            `extvideo` VARCHAR(250) DEFAULT '',
                            `timestamp` INT(11) NOT NULL DEFAULT 0,
                            `pinned` TINYINT(1) NOT NULL DEFAULT 0,
                            INDEX `wall_post_index` (`course_id`)) $tbl_options");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `wall_post_resources` (
                            `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `post_id` INT(11) NOT NULL,
                            `title` VARCHAR(255) NOT NULL DEFAULT '',
                            `res_id` INT(11) NOT NULL,
                            `type` VARCHAR(255) NOT NULL DEFAULT '',
                            INDEX `wall_post_resources_index` (`post_id`)) $tbl_options");
    }

    if (version_compare($oldversion, '3.5', '<')) {
        updateInfo(-1, sprintf($langUpgForVersion, '3.5'));

        // Fix multiple equal orders for the same unit if needed
        Database::get()->queryFunc('SELECT course_id FROM course_units
            GROUP BY course_id, `order` HAVING COUNT(`order`) > 1',
            function ($course) {
                $i = 0;
                Database::get()->queryFunc('SELECT id
                    FROM course_units WHERE course_id = ?d
                    ORDER BY `order`',
                    function ($unit) use (&$i) {
                        $i++;
                        Database::get()->query('UPDATE course_units SET `order` = ?d
                            WHERE id = ?d', $i, $unit->id);
                    }, $course->course_id);
            });
        if (!DBHelper::indexExists('course_units', 'course_units_order')) {
            Database::get()->query('ALTER TABLE course_units
                ADD UNIQUE KEY `course_units_order` (`course_id`,`order`)');
        }
        Database::get()->queryFunc('SELECT unit_id FROM unit_resources
            GROUP BY unit_id, `order` HAVING COUNT(`order`) > 1',
            function ($unit) {
                $i = 0;
                Database::get()->queryFunc('SELECT id
                    FROM unit_resources WHERE unit_id = ?d
                    ORDER BY `order`',
                    function ($resource) use (&$i) {
                        $i++;
                        Database::get()->query('UPDATE unit_resources SET `order` = ?d
                            WHERE id = ?d', $i, $resource->id);
                    }, $unit->unit_id);
            });
        if (!DBHelper::indexExists('unit_resources', 'unit_resources_order')) {
            Database::get()->query('ALTER TABLE unit_resources
                ADD UNIQUE KEY `unit_resources_order` (`unit_id`,`order`)');
        }

        // fix wrong entries in statistics
        Database::get()->query("UPDATE actions_daily SET module_id = " .MODULE_ID_VIDEO . " WHERE module_id = 0");

        // hierarchy extra fields
        if (!DBHelper::fieldExists('hierarchy', 'description')) {
            Database::get()->query("ALTER TABLE hierarchy ADD `description` TEXT AFTER name");
            Database::get()->query("ALTER TABLE hierarchy ADD `visible` tinyint(4) not null default 2 AFTER order_priority");
        }

        // fix invalid agenda durations
        Database::get()->queryFunc("SELECT DISTINCT duration FROM agenda WHERE duration NOT LIKE '%:%'",
            function ($item) {
                $d = $item->duration;
                if (preg_match('/(\d*)[.,:](\d+)/', $d, $matches)) {
                    $fixed = sprintf('%02d:%02d', intval($matches[0]), intval($matches[1]));
                } else {
                    $val = intval($d);
                    if ($val <= 10) {
                        $fixed = sprintf('%02d:00', $val);
                    } else {
                        $h = floor($val / 60);
                        $m = $val % 60;
                        $fixed = sprintf('%02d:%02d', $h, $m);
                    }
                }
                Database::get()->query('UPDATE agenda
                    SET duration = ?s WHERE duration = ?s', $fixed, $d);
            });
    }

    if (version_compare($oldversion, '3.5.1', '<')) {
        // FAQ, E-book and learning path unique indexes
        if (!DBHelper::indexExists('faq', 'faq_order')) {
            Database::get()->query('ALTER TABLE faq
                ADD UNIQUE KEY `faq_order` (`order`)');
        }
        if (!DBHelper::indexExists('ebook', 'ebook_order')) {
            Database::get()->query('ALTER TABLE ebook
                ADD UNIQUE KEY `ebook_order` (`course_id`, `order`)');
        }
        if (!DBHelper::indexExists('lp_learnPath', 'learnPath_order')) {
            Database::get()->query('ALTER TABLE lp_learnPath
                ADD UNIQUE KEY `learnPath_order` (`course_id`, `rank`)');
        }
    }

    if (version_compare($oldversion, '3.6', '<')) {
        updateInfo(-1, sprintf($langUpgForVersion, '3.6'));

        Database::get()->query("CREATE TABLE IF NOT EXISTS `activity_heading` (
            `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `order` INT(11) NOT NULL DEFAULT 0,
            `heading` TEXT NOT NULL,
            `required` BOOL NOT NULL DEFAULT 0) $tbl_options");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `activity_content` (
            `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `course_id` INT(11) NOT NULL,
            `heading_id` INT(11) NOT NULL DEFAULT 0,
            `content` TEXT NOT NULL,
            FOREIGN KEY (course_id) REFERENCES course(id) ON DELETE CASCADE,
            FOREIGN KEY (heading_id) REFERENCES activity_heading(id) ON DELETE CASCADE,
            UNIQUE KEY `heading_course` (`course_id`,`heading_id`)) $tbl_options");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `eportfolio_fields_data` (
            `user_id` MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT 0,
            `field_id` INT(11) NOT NULL,
            `data` TEXT NOT NULL,
            PRIMARY KEY (`user_id`, `field_id`)) $tbl_options");

        if (!DBHelper::tableExists('eportfolio_fields_category')) {
            Database::get()->query("CREATE TABLE `eportfolio_fields_category` (
            `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `name` MEDIUMTEXT NOT NULL,
            `sortorder`  INT(11) NOT NULL DEFAULT 0) $tbl_options");

            Database::get()->query("INSERT INTO `eportfolio_fields_category` (`id`, `name`, `sortorder`) VALUES
                (1, '$langPersInfo', 0),
                (2, '$langEduEmpl', -1),
                (3, '$langAchievements', -2),
                (4, '$langGoalsSkills', -3),
                (5, '$langContactInfo', -4)");
        }

        if (!DBHelper::tableExists('eportfolio_fields')) {
            Database::get()->query("CREATE TABLE `eportfolio_fields` (
                `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `shortname` VARCHAR(255) NOT NULL,
                `name` MEDIUMTEXT NOT NULL,
                `description` MEDIUMTEXT NULL DEFAULT NULL,
                `datatype` VARCHAR(255) NOT NULL,
                `categoryid` INT(11) NOT NULL DEFAULT 0,
                `sortorder`  INT(11) NOT NULL DEFAULT 0,
                `required` TINYINT NOT NULL DEFAULT 0,
                `data` TEXT NULL DEFAULT NULL) $tbl_options");

            Database::get()->query("INSERT INTO `eportfolio_fields` (`id`, `shortname`, `name`, `description`, `datatype`, `categoryid`, `sortorder`, `required`, `data`) VALUES
                (1, 'birth_date', '$langBirthDate', '', '3', 1, 0, 0, ''),
                (2, 'birth_place', '$langBirthPlace', '', '1', 1, -1, 0, ''),
                (3, 'gender', '$langGender', '', '4', 1, -2, 0, 'a:2:{i:0;s:".strlen($langMale).":\"$langMale\";i:1;s:".strlen($langFemale).":\"$langFemale\";}'),
                (4, 'about_me', '$langAboutMe', '$langAboutMeDescr', '2', 1, -3, 0, ''),
                (5, 'personal_website', '$langPersWebsite', '', '5', 1, -4, 0, ''),
                (6, 'education', '$langEducation', '$langEducationDescr', '2', 2, 0, 0, ''),
                (7, 'employment', '$langEmployment', '', '2', 2, -1, 0, ''),
                (8, 'certificates_awards', '$langCertAwards', '', '2', 3, 0, 0, ''),
                (9, 'publications', '$langPublications', '', '2', 3, -1, 0, ''),
                (10, 'personal_goals', '$langPersGoals', '', '2', 4, 0, 0, ''),
                (11, 'academic_goals', '$langAcademicGoals', '', '2', 4, -1, 0, ''),
                (12, 'career_goals', '$langCareerGoals', '', '2', 4, -2, 0, ''),
                (13, 'personal_skills', '$langPersSkills', '', '2', 4, -3, 0, ''),
                (14, 'academic_skills', '$langAcademicSkills', '', '2', 4, -4, 0, ''),
                (15, 'career_skills', '$langCareerSkills', '', '2', 4, -5, 0, ''),
                (16, 'email', '$langEmail', '', '1', 5, 0, 0, ''),
                (17, 'phone_number', '$langPhone', '', '1', 5, -1, 0, ''),
                (18, 'Address', '$langAddress', '', '1', 5, -2, 0, ''),
                (19, 'fb', '$langFBProfile', '', '5', 5, -3, 0, ''),
                (20, 'twitter', '$langTwitterAccount', '', '5', 5, -4, 0, ''),
                (21, 'linkedin', '$langLinkedInProfile', '', '5', 5, -5, 0, '')");
        }

        Database::get()->query("CREATE TABLE IF NOT EXISTS `eportfolio_resource` (
            `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `user_id` MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT 0,
            `resource_id` INT(11) NOT NULL,
            `resource_type` VARCHAR(50) NOT NULL,
            `course_id` INT(11) NOT NULL,
            `course_title` VARCHAR(250) NOT NULL DEFAULT '',
            `time_added` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `data` TEXT NOT NULL,
            INDEX `eportfolio_res_index` (`user_id`,`resource_type`)) $tbl_options");

        Database::get()->query("INSERT IGNORE INTO `config` (`key`, `value`) VALUES ('bio_quota', '4')");

        if (!DBHelper::fieldExists('user', 'eportfolio_enable')) {
            Database::get()->query("ALTER TABLE `user` ADD eportfolio_enable TINYINT(1) NOT NULL DEFAULT 0");
        }
        // upgrade table `assignment_submit`
        if (!DBHelper::fieldExists('assignment_submit', 'grade_comments_filepath')) {
            Database::get()->query("ALTER TABLE assignment_submit ADD grade_comments_filepath VARCHAR(200) NOT NULL DEFAULT ''
                                AFTER grade_comments");
        }
        if (!DBHelper::fieldExists('assignment_submit', 'grade_comments_filename')) {
            Database::get()->query("ALTER TABLE assignment_submit ADD grade_comments_filename VARCHAR(200) NOT NULL DEFAULT ''
                                AFTER grade_comments");
        }
        if (!DBHelper::fieldExists('assignment_submit', 'grade_rubric')) {
            Database::get()->query("ALTER TABLE assignment_submit ADD `grade_rubric` TEXT AFTER grade");
        }
        // upgrade table `assignment`
        if (!DBHelper::fieldExists('assignment', 'notification')) {
            Database::get()->query("ALTER TABLE assignment ADD notification tinyint(4) DEFAULT 0");
        }
        if (!DBHelper::fieldExists('assignment', 'grading_type')) {
            Database::get()->query("ALTER TABLE assignment ADD `grading_type` TINYINT NOT NULL DEFAULT '0' AFTER group_submissions");
        }
        if (!DBHelper::fieldExists('assignment', 'password_lock')) {
            Database::get()->query("ALTER TABLE `assignment` ADD `password_lock` VARCHAR(255) NOT NULL DEFAULT ''");
        }
        if (!DBHelper::fieldExists('assignment', 'ip_lock')) {
            Database::get()->query("ALTER TABLE `assignment` ADD `ip_lock` TEXT");
        }

        // plagiarism tool table
        if (!DBHelper::tableExists('ext_plag_connection')) {
            Database::get()->query("CREATE TABLE `ext_plag_connection` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `type` int(1) unsigned NOT NULL DEFAULT '1',
              `file_id` int(11) NOT NULL,
              `remote_file_id` int(11) DEFAULT NULL,
              `submission_id` int(11) DEFAULT NULL,
              PRIMARY KEY (`id`)) $tbl_options");
        }

        // Course Category tables
        Database::get()->query("CREATE TABLE IF NOT EXISTS `category` (
            `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `name` TEXT NOT NULL,
            `ordering` INT(11),
            `multiple` BOOLEAN NOT NULL DEFAULT TRUE,
            `searchable` BOOLEAN NOT NULL DEFAULT TRUE,
            `active` BOOLEAN NOT NULL DEFAULT TRUE
            ) $tbl_options");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `category_value` (
            `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `category_id` INT(11) NOT NULL REFERENCES category(id),
            `name` TEXT NOT NULL,
            `ordering` INT(11),
            `active` BOOLEAN NOT NULL DEFAULT TRUE
            ) $tbl_options");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `course_category` (
            `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `course_id` INT(11) NOT NULL REFERENCES course(id),
            `category_value_id` INT(11) NOT NULL REFERENCES category_value(id)
            ) $tbl_options");

        // Rubric tables
        Database::get()->query("CREATE TABLE IF NOT EXISTS `rubric` (
            `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(200) NOT NULL,
            `scales` text NOT NULL,
            `description` text,
            `preview_rubric` tinyint(1) NOT NULL DEFAULT '0',
            `points_to_graded` tinyint(1) NOT NULL DEFAULT '0',
            `course_id` int(11) NOT NULL,
            KEY `course_id` (`course_id`)
            ) $tbl_options");

        // Gamification Tables (aka certificate + badge)
        Database::get()->query("CREATE TABLE IF NOT EXISTS `certificate_template` (
            `id` mediumint(8) not null auto_increment,
            `name` varchar(255) not null,
            `description` text,
            `filename` varchar(255),
            `orientation` varchar(10),
            PRIMARY KEY(`id`)
        ) $tbl_options");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `badge_icon` (
                `id` mediumint(8) not null auto_increment primary key,
                `name` varchar(255) not null,
                `description` text,
                `filename` varchar(255)
        ) $tbl_options");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `certificate` (
            `id` int(11) not null auto_increment primary key,
            `course_id` int(11) not null,
            `issuer` varchar(255) not null default '',
            `template` mediumint(8),
            `title` varchar(255) not null,
            `description` text,
            `message` text,
            `autoassign` tinyint(1) not null default 1,
            `active` tinyint(1) not null default 1,
            `created` datetime,
            `expires` datetime,
            `bundle` int(11) not null default 0,
            index `certificate_course` (`course_id`),
            foreign key (`course_id`) references `course` (`id`),
            foreign key (`template`) references `certificate_template`(`id`)
          ) $tbl_options");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `badge` (
            `id` int(11) not null auto_increment primary key,
            `course_id` int(11) not null,
            `issuer` varchar(255) not null default '',
            `icon` mediumint(8),
            `title` varchar(255) not null,
            `description` text,
            `message` text,
            `autoassign` tinyint(1) not null default 1,
            `active` tinyint(1) not null default 1,
            `created` datetime,
            `expires` datetime,
            `bundle` int(11) not null default 0,
            index `badge_course` (`course_id`),
            foreign key (`course_id`) references `course` (`id`)
          ) $tbl_options");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `user_certificate` (
          `id` int(11) not null auto_increment primary key,
          `user` int(11) not null,
          `certificate` int(11) not null,
          `completed` boolean default false,
          `completed_criteria` int(11),
          `total_criteria` int(11),
          `updated` datetime,
          `assigned` datetime,
          unique key `user_certificate` (`user`, `certificate`),
          foreign key (`user`) references `user`(`id`),
          foreign key (`certificate`) references `certificate` (`id`)
        ) $tbl_options");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `user_badge` (
          `id` int(11) not null auto_increment primary key,
          `user` int(11) not null,
          `badge` int(11) not null,
          `completed` boolean default false,
          `completed_criteria` int(11),
          `total_criteria` int(11),
          `updated` datetime,
          `assigned` datetime,
          unique key `user_badge` (`user`, `badge`),
          foreign key (`user`) references `user`(`id`),
          foreign key (`badge`) references `badge` (`id`)
        ) $tbl_options");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `certificate_criterion` (
          `id` int(11) not null auto_increment primary key,
          `certificate` int(11) not null,
          `activity_type` varchar(255),
          `module` int(11),
          `resource` int(11),
          `threshold` decimal(7,2),
          `operator` varchar(20),
          foreign key (`certificate`) references `certificate`(`id`)
        ) $tbl_options");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `badge_criterion` (
          `id` int(11) not null auto_increment primary key,
          `badge` int(11) not null,
          `activity_type` varchar(255),
          `module` int(11),
          `resource` int(11),
          `threshold` decimal(7,2),
          `operator` varchar(20),
          foreign key (`badge`) references `badge`(`id`)
        ) $tbl_options");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `user_certificate_criterion` (
          `id` int(11) not null auto_increment primary key,
          `user` int(11) not null,
          `certificate_criterion` int(11) not null,
          `created` datetime,
          unique key `user_certificate_criterion` (`user`, `certificate_criterion`),
          foreign key (`user`) references `user`(`id`),
          foreign key (`certificate_criterion`) references `certificate_criterion`(`id`)
        ) $tbl_options");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `user_badge_criterion` (
          `id` int(11) not null auto_increment primary key,
          `user` int(11) not null,
          `badge_criterion` int(11) not null,
          `created` datetime,
          unique key `user_badge_criterion` (`user`, `badge_criterion`),
          foreign key (`user`) references `user`(`id`),
          foreign key (`badge_criterion`) references `badge_criterion`(`id`)
        ) $tbl_options");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `certified_users` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `course_title` varchar(255) NOT NULL DEFAULT '',
            `cert_title` varchar(255) NOT NULL DEFAULT '',
            `cert_message` TEXT,
            `cert_id` int(11) NOT NULL,
            `cert_issuer` varchar(256) DEFAULT NULL,
            `user_fullname` varchar(255) NOT NULL DEFAULT '',
            `assigned` datetime NOT NULL,
            `identifier` varchar(255) NOT NULL DEFAULT '',
            `expires` datetime DEFAULT NULL,
            `template_id` INT(11),
            PRIMARY KEY (`id`)
        ) $tbl_options");

        // install predefined cert templates
        installCertTemplates($webDir);
        // install badge icons
        installBadgeIcons($webDir);

        // tc attendance tables
        Database::get()->query("CREATE TABLE IF NOT EXISTS `tc_attendance` (
            `id` int(11) NOT NULL DEFAULT '0',
            `meetingid` varchar(20) NOT NULL,
            `bbbuserid` varchar(20) DEFAULT NULL,
            `totaltime` int(11) NOT NULL DEFAULT '0',
            `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`,`meetingid`),
            KEY `id` (`id`),
            KEY `meetingid` (`meetingid`)
        ) $tbl_options");

        Database::get()->query("CREATE TABLE IF NOT EXISTS `tc_log` (
                `id` int(11) NOT NULL,
                `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `meetingid` varchar(20) NOT NULL,
                `bbbuserid` varchar(20) DEFAULT NULL,
                `fullName` varchar(200) DEFAULT NULL,
                `type` varchar(255) default 'bbb',
                PRIMARY KEY (`id`),
                KEY `userid` (`bbbuserid`),
                KEY `fullName` (`fullName`)
            ) $tbl_options");

        Database::get()->query('ALTER TABLE poll_question
                CHANGE question_text question_text TEXT NOT NULL');
        Database::get()->query('ALTER TABLE document
                CHANGE filename filename VARCHAR(255) NOT NULL COLLATE utf8_bin');

        // restore admin user white list
        Database::get()->query("UPDATE user SET whitelist=NULL where username='admin'");

    }

    // upgrade queries for version 3.6.3
    if (version_compare($oldversion, '3.6.3', '<')) {
        updateInfo(-1, sprintf($langUpgForVersion, '3.6.3'));

        Database::get()->query('ALTER TABLE tc_session
            CHANGE external_users external_users TEXT DEFAULT NULL');
    }

    // upgrade queries for version 3.7
    if (version_compare($oldversion, '3.7', '<')) {
        updateInfo(-1, sprintf($langUpgForVersion, '3.7'));

        if (!DBHelper::fieldExists('wiki_properties', 'visible')) {
            Database::get()->query("ALTER TABLE `wiki_properties`
                ADD `visible` TINYINT(4) UNSIGNED NOT NULL DEFAULT '1'");
        }

        // course units upgrade
        if (!DBHelper::fieldExists('course_units', 'finish_week')) {
            Database::get()->query("ALTER TABLE course_units ADD finish_week DATE after comments");
        }
        if (!DBHelper::fieldExists('course_units', 'start_week')) {
            Database::get()->query("ALTER TABLE course_units ADD start_week DATE after comments");
        }

        // -------------------------------------------------------------------------------
        // Upgrade course units. Merge course weekly view type with course unit view type
        // -------------------------------------------------------------------------------

        // For all courses with view type = 'weekly'
        $q = Database::get()->queryArray("SELECT id FROM course WHERE view_type = 'weekly'");
        foreach ($q as $courseid) {
            // Clean up: Check if course has any (simple) course units.
            // If yes then delete them since they are not appeared anywhere and we don't want to have stale db records.
            $s = Database::get()->queryArray("SELECT id FROM course_units WHERE course_id = ?d", $courseid);
            foreach ($s as $oldcu) {
                Database::get()->query("DELETE FROM unit_resources WHERE unit_id = ?d", $oldcu);
            }
            Database::get()->query("DELETE FROM course_units WHERE course_id = ?d", $courseid);

            // Now we can continue
            // Move weekly_course_units to course_units
            $result = Database::get()->query("INSERT INTO course_units
                        (title, comments, start_week, finish_week, visible, public, `order`, course_id)
                            SELECT CASE WHEN (title = '' OR title IS NULL)
                                THEN
                                  TRIM(CONCAT_WS(' ','$langWeek', DATE_FORMAT(start_week, '%d-%m-%Y')))
                                ELSE
                                  title
                                END
                              AS title,
                              comments, start_week, finish_week, visible, public, `order`, ?d
                                FROM course_weekly_view
                                WHERE course_id = ?d ORDER BY id", $courseid, $courseid);
            $unit_map = [];
            $current_id = Database::get()->querySingle("SELECT MAX(id) AS max_id FROM course_units")->max_id;
            Database::get()->queryFunc("SELECT id FROM course_weekly_view
                                WHERE course_id = ?d ORDER BY id DESC LIMIT ?d",
                function ($item) use (&$unit_map, &$current_id) {
                    $unit_map[$current_id] = $item->id;
                    $current_id--;
                },
                $courseid, $result->affectedRows);

            // move weekly_course_unit_resources to course_unit_resources
            foreach ($unit_map as $unit_id => $weekly_id) {
                Database::get()->query("INSERT INTO unit_resources
                                (unit_id, title, comments, res_id, `type`, visible, `order`, `date`)
                            SELECT ?d, title, comments, res_id, `type`, visible, `order`, `date`
                                FROM course_weekly_view_activities
                                WHERE course_weekly_view_id = ?d", $unit_id, $weekly_id);
            }
            // update course with new view type (=units)
            Database::get()->query("UPDATE course SET view_type = 'units' WHERE id = ?d", $courseid);
        }
        // drop tables
        if (DBHelper::tableExists('course_weekly_view')) {
            Database::get()->query("DROP TABLE course_weekly_view");
        }
        if (DBHelper::tableExists('course_weekly_view_activities')) {
            Database::get()->query("DROP TABLE course_weekly_view_activities");
        }
        // end of upgrading course units

        // course prerequisites
        Database::get()->query("CREATE TABLE IF NOT EXISTS `course_prerequisite` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `course_id` int(11) not null,
                `prerequisite_course` int(11) not null,
                PRIMARY KEY (`id`)
            ) $tbl_options");

        // lti apps
        Database::get()->query("CREATE TABLE IF NOT EXISTS lti_apps (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `course_id` INT(11) DEFAULT NULL,
                `title` VARCHAR(255) DEFAULT NULL,
                `description` TEXT,
                `lti_provider_url` VARCHAR(255) DEFAULT NULL,
                `lti_provider_key` VARCHAR(255) DEFAULT NULL,
                `lti_provider_secret` VARCHAR(255) DEFAULT NULL,
                `launchcontainer` TINYINT(4) NOT NULL DEFAULT 1,
                `is_template` TINYINT(4) NOT NULL DEFAULT 0,
                `enabled` TINYINT(4) NOT NULL DEFAULT 1,
                PRIMARY KEY (`id`)
            ) $tbl_options");

        if (!DBHelper::fieldExists('assignment', 'assignment_type')) {
            Database::get()->query("ALTER TABLE assignment ADD assignment_type TINYINT NOT NULL DEFAULT '0' AFTER password_lock");
        }
        if (!DBHelper::fieldExists('assignment', 'lti_template')) {
            Database::get()->query("ALTER TABLE assignment ADD lti_template INT(11) DEFAULT NULL AFTER assignment_type");
        }
        if (!DBHelper::fieldExists('assignment', 'launchcontainer')) {
            Database::get()->query("ALTER TABLE assignment ADD launchcontainer TINYINT DEFAULT NULL AFTER lti_template");
        }
        if (!DBHelper::fieldExists('assignment', 'tii_feedbackreleasedate')) {
            Database::get()->query("ALTER TABLE assignment ADD tii_feedbackreleasedate DATETIME NULL DEFAULT NULL AFTER launchcontainer");
        }
        if (!DBHelper::fieldExists('assignment', 'tii_internetcheck')) {
            Database::get()->query("ALTER TABLE assignment ADD tii_internetcheck TINYINT NOT NULL DEFAULT '1' AFTER tii_feedbackreleasedate");
        }
        if (!DBHelper::fieldExists('assignment', 'tii_institutioncheck')) {
            Database::get()->query("ALTER TABLE assignment ADD tii_institutioncheck TINYINT NOT NULL DEFAULT '1' AFTER tii_internetcheck");
        }
        if (!DBHelper::fieldExists('assignment', 'tii_journalcheck')) {
            Database::get()->query("ALTER TABLE assignment ADD tii_journalcheck TINYINT NOT NULL DEFAULT '1' AFTER tii_institutioncheck");
        }
        if (!DBHelper::fieldExists('assignment', 'tii_report_gen_speed')) {
            Database::get()->query("ALTER TABLE assignment ADD tii_report_gen_speed TINYINT NOT NULL DEFAULT '0' AFTER tii_journalcheck");
        }
        if (!DBHelper::fieldExists('assignment', 'tii_s_view_reports')) {
            Database::get()->query("ALTER TABLE assignment ADD tii_s_view_reports TINYINT NOT NULL DEFAULT '0' AFTER tii_report_gen_speed");
        }
        if (!DBHelper::fieldExists('assignment', 'tii_studentpapercheck')) {
            Database::get()->query("ALTER TABLE assignment ADD tii_studentpapercheck TINYINT NOT NULL DEFAULT '1' AFTER tii_s_view_reports");
        }
        if (!DBHelper::fieldExists('assignment', 'tii_submit_papers_to')) {
            Database::get()->query("ALTER TABLE assignment ADD tii_submit_papers_to TINYINT NOT NULL DEFAULT '1' AFTER tii_studentpapercheck");
        }
        if (!DBHelper::fieldExists('assignment', 'tii_use_biblio_exclusion')) {
            Database::get()->query("ALTER TABLE assignment ADD tii_use_biblio_exclusion TINYINT NOT NULL DEFAULT '0' AFTER tii_submit_papers_to");
        }
        if (!DBHelper::fieldExists('assignment', 'tii_use_quoted_exclusion')) {
            Database::get()->query("ALTER TABLE assignment ADD tii_use_quoted_exclusion TINYINT NOT NULL DEFAULT '0' AFTER tii_use_biblio_exclusion");
        }
        if (!DBHelper::fieldExists('assignment', 'tii_exclude_type')) {
            Database::get()->query("ALTER TABLE assignment ADD tii_exclude_type VARCHAR(20) NOT NULL DEFAULT 'none' AFTER tii_use_quoted_exclusion");
        }
        if (!DBHelper::fieldExists('assignment', 'tii_exclude_value')) {
            Database::get()->query("ALTER TABLE assignment ADD tii_exclude_value INT(11) NOT NULL DEFAULT '0' AFTER tii_exclude_type");
        }

        // move question position to exercise and exercise_answer
        // and make sure position is unique in each exercise / attempt
        if (DBHelper::fieldExists('exercise_question', 'q_position')) {
            Database::get()->query('ALTER TABLE exercise_with_questions ADD q_position INT(11) NOT NULL DEFAULT 1');
            Database::get()->query('ALTER TABLE exercise_answer_record ADD q_position INT(11) NOT NULL DEFAULT 1');
            Database::get()->query('UPDATE exercise_with_questions
                JOIN exercise_question ON exercise_question.id = question_id
                SET exercise_with_questions.q_position = exercise_question.q_position');
            Database::get()->query('UPDATE exercise_answer_record
                JOIN exercise_question ON exercise_question.id = question_id
                SET exercise_answer_record.q_position = exercise_question.q_position');
            $exercises = Database::get()->queryArray('SELECT exercise_id AS id
                FROM exercise_with_questions GROUP by exercise_id, q_position HAVING COUNT(*) > 1');
            foreach ($exercises as $exercise) {
                $questions = Database::get()->queryArray('SELECT question_id AS id FROM exercise_with_questions
                    WHERE exercise_id = ?d ORDER BY q_position', $exercise->id);
                $i = 1;
                foreach ($questions as $question) {
                    Database::get()->query('UPDATE exercise_with_questions
                        SET q_position = ?d WHERE exercise_id = ?d AND question_id = ?d',
                        $i, $exercise->id, $question->id);
                    Database::get()->query('UPDATE exercise_answer_record
                        JOIN exercise_user_record USING (eurid)
                        SET q_position = ?d WHERE eid = ?d AND question_id = ?d',
                        $i, $exercise->id, $question->id);
                    $i++;
                }
            }
            Database::get()->query('ALTER TABLE exercise_question DROP q_position');
        }

        if (!DBHelper::fieldExists('exercise_user_record', 'assigned_to')) {
            Database::get()->query("ALTER TABLE `exercise_user_record`
                    ADD `assigned_to` INT(11) DEFAULT NULL");
        }

        // user consent
        Database::get()->query("CREATE TABLE IF NOT EXISTS `user_consent` (
            id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id INT(11) NOT NULL,
            has_accepted BOOL NOT NULL DEFAULT 0,
            ts DATETIME,
            PRIMARY KEY (id),
            UNIQUE KEY (user_id),
            FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
          ) $tbl_options");
    }


    // upgrade queries for version 3.8
    if (version_compare($oldversion, '3.8', '<')) {
        updateInfo(-1, sprintf($langUpgForVersion, '3.8'));

        if (DBHelper::fieldExists('announcement', 'preview')) {
            Database::get()->query("ALTER TABLE announcement DROP COLUMN preview");
        }
        // conference chat activity and agent
        if (!DBHelper::fieldExists('conference', 'chat_activity')) {
            Database::get()->query('ALTER TABLE conference ADD chat_activity boolean not null default false');
        }
        if (!DBHelper::fieldExists('conference', 'agent_created')) {
            Database::get()->query('ALTER TABLE conference ADD agent_created boolean not null default false');
        }

        // user settings table
        if (!DBHelper::tableExists('user_settings')) {
            Database::get()->query("CREATE TABLE `user_settings` (
                `setting_id` int(11) NOT NULL,
                `user_id` int(11) NOT NULL,
                `course_id` int(11) DEFAULT NULL,
                `value` int(11) NOT NULL DEFAULT '0',
                PRIMARY KEY (`setting_id`,`user_id`),
                KEY `user_id` (`user_id`),
                KEY `course_id` (`course_id`),
                  CONSTRAINT `user_settings_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE,
                  CONSTRAINT `user_settings_ibfk_4` FOREIGN KEY (`course_id`) REFERENCES `course` (`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE
            ) $tbl_options");
        }

        // forum attachments
        if (!DBHelper::fieldExists('forum_post', 'topic_filepath')) {
            Database::get()->query("ALTER TABLE forum_post ADD `topic_filepath` varchar(200) DEFAULT NULL");
        }

        if (!DBHelper::fieldExists('forum_post', 'topic_filename')) {
            Database::get()->query("ALTER TABLE forum_post ADD `topic_filename` varchar(200) DEFAULT NULL");
        }
        // chat agent
        if (!DBHelper::fieldExists('conference', 'chat_activity_id')) {
            Database::get()->query('ALTER TABLE conference ADD chat_activity_id int(11)');
        }

        if (!DBHelper::fieldExists('conference', 'agent_id')) {
            Database::get()->query('ALTER TABLE conference ADD agent_id int(11)');
        }

        if (!DBHelper::tableExists('colmooc_user')) {
            Database::get()->query("CREATE TABLE IF NOT EXISTS `colmooc_user` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `user_id` INT(11) NOT NULL,
                `colmooc_id` INT(11) NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY (user_id),
                FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE) $tbl_options");
        }

        if (!DBHelper::tableExists('colmooc_user_session')) {
            Database::get()->query("CREATE TABLE IF NOT EXISTS `colmooc_user_session` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `user_id` INT(11) NOT NULL,
                `activity_id` INT(11) NOT NULL,
                `session_id` TEXT NOT NULL,
                `session_token` TEXT NOT NULL,
                `session_status` TINYINT(4) NOT NULL DEFAULT 0,
                `session_status_updated` datetime DEFAULT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY `user_activity` (`user_id`, `activity_id`),
                FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE) $tbl_options");
        }

        if (!DBHelper::tableExists('colmooc_pair_log')) {
            Database::get()->query("CREATE TABLE IF NOT EXISTS `colmooc_pair_log` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `activity_id` INT(11) NOT NULL,
                `moderator_id` INT(11) NOT NULL,
                `partner_id` INT(11) NOT NULL,
                `session_status` TINYINT(4) NOT NULL DEFAULT 0,
                `created` datetime DEFAULT NULL,
                PRIMARY KEY (id),
                FOREIGN KEY (moderator_id) REFERENCES user(id) ON DELETE CASCADE,
                FOREIGN KEY (partner_id) REFERENCES user(id) ON DELETE CASCADE) $tbl_options");
        }

        //learning analytics
        if (!DBHelper::tableExists('analytics')) {
            Database::get()->query("CREATE TABLE `analytics` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `courseID` int(11) NOT NULL,
              `title` varchar(255) NOT NULL,
              `description` text,
              `active` tinyint(1) NOT NULL DEFAULT '0',
              `start_date` date DEFAULT NULL,
              `end_date` date DEFAULT NULL,
              `created` datetime DEFAULT NULL,
              `periodType` int(11) NOT NULL,
              PRIMARY KEY (id)) $tbl_options");
        }

        if (!DBHelper::tableExists('analytics_element')) {
            Database::get()->query("CREATE TABLE `analytics_element` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `analytics_id` int(11) NOT NULL,
              `module_id` int(11) NOT NULL,
              `resource` int(11) DEFAULT NULL,
              `upper_threshold` float DEFAULT NULL,
              `lower_threshold` float DEFAULT NULL,
              `weight` int(11) NOT NULL DEFAULT '1',
              `min_value` float NOT NULL,
              `max_value` float NOT NULL,
              PRIMARY KEY (`id`)) $tbl_options");
        }

        if (!DBHelper::tableExists('user_analytics')) {
            Database::get()->query("CREATE TABLE `user_analytics` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `user_id` int(11) NOT NULL,
              `analytics_element_id` int(11) NOT NULL,
              `value` float NOT NULL DEFAULT '0',
              `updated` datetime NOT NULL,
              PRIMARY KEY (`id`)) $tbl_options");
        }

        // lti apps
        if (!DBHelper::fieldExists('lti_apps', 'all_courses')) {
            Database::get()->query("ALTER TABLE lti_apps ADD all_courses TINYINT(1) NOT NULL DEFAULT 1");
        }
        if (!DBHelper::fieldExists('lti_apps', 'type')) {
            Database::get()->query("ALTER TABLE lti_apps ADD `type` VARCHAR(255) NOT NULL DEFAULT 'turnitin'");
        }

        if (!DBHelper::tableExists('course_lti_app')) {
            Database::get()->query("CREATE TABLE `course_lti_app` (
              `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
              `course_id` int(11) NOT NULL,
              `lti_app` int(11) NOT NULL,
              FOREIGN KEY (`course_id`) REFERENCES `course` (`id`),
              FOREIGN KEY (`lti_app`) REFERENCES `lti_apps` (`id`)) $tbl_options");
        }

        // fix wrong entries in exercises answers regarding negative weight (if any)
        Database::get()->query("UPDATE exercise_answer SET weight=-ABS(weight) WHERE correct=0 AND weight>0");

        // in gradebook change `weight` type from integer to decimal
        Database::get()->query("ALTER TABLE `gradebook_activities` CHANGE `weight` `weight` DECIMAL(5,2) NOT NULL DEFAULT '0'");

        // peer review
        if (!DBHelper::fieldExists('assignment', 'reviews_per_assignment')) {
            Database::get()->query("ALTER TABLE assignment ADD `reviews_per_assignment` INT(4) DEFAULT NULL");
        }
        if (!DBHelper::fieldExists('assignment', 'start_date_review')) {
            Database::get()->query("ALTER TABLE assignment ADD `start_date_review` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP");
        }
        if (!DBHelper::fieldExists('assignment', 'due_date_review')) {
            Database::get()->query("ALTER TABLE assignment ADD `due_date_review` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP");
        }

        if (!DBHelper::tableExists('assignment_grading_review')) {
            Database::get()->query("CREATE TABLE `assignment_grading_review` (
            `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `assignment_id` INT(11) NOT NULL,
            `user_submit_id` INT(11) NOT NULL,
            `user_id` INT(11) NOT NULL,
            `file_path` VARCHAR(200) NOT NULL,
            `file_name` VARCHAR(200) NOT NULL,
            `submission_text` MEDIUMTEXT,
            `submission_date` DATETIME NOT NULL,
            `gid` INT(11) NOT NULL,
            `users_id` INT(11) NOT NULL,
            `grade` FLOAT DEFAULT NULL,
            `comments` TEXT,
            `date_submit` DATETIME DEFAULT NULL,
            `rubric_scales` TEXT) $tbl_options");
        }
        Database::get()->query("ALTER TABLE `ebook_subsection` CHANGE `section_id` `section_id` int(11) NOT NULL");
    }

    // upgrade queries for version 3.9
    if (version_compare($oldversion, '3.9', '<')) {
        updateInfo(-1, sprintf($langUpgForVersion, '3.9'));

        if (!DBHelper::fieldExists('exercise', 'continue_time_limit')) {
            Database::get()->query("ALTER TABLE `exercise` ADD `continue_time_limit` INT(11) NOT NULL DEFAULT 0");
        }
        if (!DBHelper::fieldExists('assignment', 'max_submissions')) {
            Database::get()->query("ALTER TABLE `assignment` ADD `max_submissions` TINYINT(3) UNSIGNED NOT NULL DEFAULT 1");
        }
        if (!DBHelper::fieldExists('group', 'visible')) {
            Database::get()->query("ALTER TABLE `group` ADD `visible` TINYINT(4) NOT NULL DEFAULT 1");
        }
    }

    // upgrade queries for version 3.10
    if (version_compare($oldversion, '3.10', '<')) {
        updateInfo(-1, sprintf($langUpgForVersion, '3.10'));

        if (!DBHelper::fieldExists('exercise_with_questions', 'random_criteria')) {
            Database::get()->query("ALTER TABLE exercise_with_questions ADD `random_criteria` TEXT");
            Database::get()->query("ALTER TABLE exercise_with_questions DROP PRIMARY KEY");
            Database::get()->query("ALTER TABLE exercise_with_questions ADD id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT FIRST");
            Database::get()->query("ALTER TABLE exercise_with_questions CHANGE question_id question_id INT NULL DEFAULT 0");
        }

        if (!DBHelper::fieldExists('exercise', 'shuffle')) {
            Database::get()->query("ALTER TABLE `exercise` ADD `shuffle` SMALLINT NOT NULL DEFAULT '0' AFTER `random`");
            // update old records
            Database::get()->query("UPDATE exercise SET shuffle=1, random=0 WHERE random=32767");
            Database::get()->query("UPDATE exercise SET shuffle=1 WHERE random>0");
        }
        if (!DBHelper::fieldExists('exercise', 'range')) {
            Database::get()->query("ALTER TABLE `exercise` ADD `range` TINYINT DEFAULT 0 AFTER `type`");
        }
        if (!DBHelper::fieldExists('tc_session', 'options')) {
            Database::get()->query("ALTER TABLE `tc_session` ADD `options` TEXT DEFAULT NULL");
        }
    }

    // upgrade queries for version 3.11
    if (version_compare($oldversion, '3.11', '<')) {
        updateInfo(-1, sprintf($langUpgForVersion, '3.11'));
        if (!DBHelper::fieldExists('poll', 'lti_template')) {
            Database::get()->query("ALTER TABLE poll ADD lti_template INT(11) DEFAULT NULL AFTER assign_to_specific");
        }
        if (!DBHelper::fieldExists('poll', 'launchcontainer')) {
            Database::get()->query("ALTER TABLE poll ADD launchcontainer TINYINT DEFAULT NULL AFTER lti_template");
        }
        if (!DBHelper::fieldExists('poll', 'multiple_submissions')) {
            Database::get()->query("ALTER TABLE poll ADD multiple_submissions TINYINT NOT NULL DEFAULT '0'");
        }
        if (!DBHelper::fieldExists('poll', 'default_answer')) {
            Database::get()->query("ALTER TABLE poll ADD default_answer TINYINT NOT NULL DEFAULT '0'");
            Database::get()->query("UPDATE poll SET default_answer = 1"); // set value for previous polls
        }
        if (!DBHelper::fieldExists('exercise', 'calc_grade_method')) {
            Database::get()->query("ALTER TABLE exercise ADD calc_grade_method TINYINT DEFAULT '1'");
            Database::get()->query("UPDATE exercise SET calc_grade_method = 0");
        }
        if (!DBHelper::fieldExists('certified_users', 'user_id')) {
            Database::get()->query("ALTER TABLE certified_users
                ADD user_id INT DEFAULT NULL,
                ADD FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE SET NULL");
            Database::get()->query("UPDATE certified_users JOIN user
                ON certified_users.user_fullname = CONCAT(user.surname, ' ', user.givenname)
                SET certified_users.user_id = user.id");
        }
    }

    // upgrade queries for version 3.12
    if (version_compare($oldversion, '3.12', '<')) {
        updateInfo(-1, sprintf($langUpgForVersion, '3.12'));
        Database::get()->query("ALTER TABLE user MODIFY `password` VARCHAR(255) NOT NULL DEFAULT 'empty'");

        if (DBHelper::fieldExists('admin', 'department_id')) {
            Database::get()->query('DELETE FROM admin
                WHERE user_id IN (SELECT user_id FROM admin
                    LEFT JOIN user ON user_id = user.id
                    WHERE user.id IS NULL)');
            if (DBHelper::indexExists('admin', 'idUser')) {
                Database::get()->query('ALTER TABLE admin DROP index idUser');
            }
            Database::get()->query('ALTER TABLE admin
                DROP PRIMARY KEY,
                ADD COLUMN id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST,
                ADD department_id INT(11) DEFAULT NULL,
                ADD FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE,
                ADD FOREIGN KEY (department_id) REFERENCES hierarchy (id) ON DELETE CASCADE,
                ADD UNIQUE KEY (user_id, department_id)');
            Database::get()->query('INSERT INTO admin (user_id, privilege, department_id)
                SELECT user_id, ?d, department FROM admin, user_department
                    WHERE user_id = user AND privilege = ?d',
                    DEPARTMENTMANAGE_USER, DEPARTMENTMANAGE_USER);
            Database::get()->query('DELETE FROM admin
                WHERE department_id IS NULL AND privilege = ?d',
                DEPARTMENTMANAGE_USER);

        }

        // h5p
        if (!DBHelper::tableExists('h5p_library')) {
            Database::get()->query("CREATE TABLE h5p_library (
                id INT(10) NOT NULL AUTO_INCREMENT,
                machine_name VARCHAR(255) NOT NULL,
                title VARCHAR(255) NOT NULL,
                major_version INT(4) NOT NULL,
                minor_version INT(4) NOT NULL,
                patch_version INT(4) NOT NULL,
                runnable INT(1) NOT NULL DEFAULT '0',
                fullscreen INT(1) NOT NULL DEFAULT '0',
                embed_types VARCHAR(255),
                preloaded_js LONGTEXT,
                preloaded_css LONGTEXT,
                droplibrary_css LONGTEXT,
                semantics LONGTEXT,
                add_to LONGTEXT,
                core_major INT(4),
                core_minor INT(4),
                metadata_settings LONGTEXT,
                tutorial LONGTEXT,
                example LONGTEXT,
              PRIMARY KEY(id)) $tbl_options");
        } elseif (!DBHelper::fieldExists('h5p_library', 'example')) {
            Database::get()->query("ALTER TABLE h5p_library
                MODIFY preloaded_js LONGTEXT,
                MODIFY preloaded_css LONGTEXT,
                ADD droplibrary_css LONGTEXT,
                ADD semantics LONGTEXT,
                ADD add_to LONGTEXT,
                ADD core_major INT(4),
                ADD core_minor INT(4),
                ADD metadata_settings LONGTEXT,
                ADD tutorial LONGTEXT,
                ADD example LONGTEXT");
        }

        if (!DBHelper::tableExists('h5p_library_dependency')) {
            Database::get()->query("CREATE TABLE h5p_library_dependency (
                id INT(10) NOT NULL AUTO_INCREMENT,
                library_id INT(10) NOT NULL,
                required_library_id INT(10) NOT NULL,
                dependency_type VARCHAR(255) NOT NULL,
              PRIMARY KEY(id)) $tbl_options");
        }

        if (!DBHelper::tableExists('h5p_library_translation')) {
            Database::get()->query("CREATE TABLE h5p_library_translation (
                id INT(10) NOT NULL,
                library_id INT(10) NOT NULL,
                language_code VARCHAR(255) NOT NULL,
                language_json TEXT NOT NULL,
              PRIMARY KEY(id)) $tbl_options");
        }

        if (!DBHelper::tableExists('h5p_content')) {
            Database::get()->query("CREATE TABLE h5p_content (
                id INT(10) NOT NULL AUTO_INCREMENT,
                title varchar(255),
                main_library_id INT(10) NOT NULL,
                params LONGTEXT,
                course_id INT(11) NOT NULL,
              PRIMARY KEY(id)) $tbl_options");
        } else {
            Database::get()->query("ALTER TABLE h5p_content
                MODIFY params LONGTEXT");
        }

        if (!DBHelper::tableExists('h5p_content_dependency')) {
            Database::get()->query("CREATE TABLE h5p_content_dependency (
                id INT(10) NOT NULL AUTO_INCREMENT,
                content_id INT(10) NOT NULL,
                library_id INT(10) NOT NULL,
                dependency_type VARCHAR(10) NOT NULL,
          PRIMARY KEY(id)) $tbl_options");
        }
        // install h5p content
        $hubUpdater = new H5PHubUpdater();
        $hubUpdater->fetchLatestContentTypes();
        set_config('h5p_update_content_ts', date('Y-m-d H:i', time()));
    }


    // Ensure that all stored procedures about hierarchy are up and running!
    refreshHierarchyProcedures();
    // create appropriate indices
    create_indexes();

    // Import new themes
    importThemes();
    if (!get_config('theme_options_id')) {
        set_config('theme_options_id', Database::get()->querySingle('SELECT id FROM theme_options WHERE name = ?s', 'Open eClass 2020 - Default')->id);
    }

    // add new modules to courses by reinserting all modules
    Database::get()->queryFunc("SELECT id, code FROM course", function ($course) {
        global $modules;
        $modules_count = count($modules);
        $placeholders = implode(', ', array_fill(0, $modules_count, '(?d, ?d, ?d)'));
        $values = array();
        foreach($modules as $mid => $minfo) {
            $values[] = array($mid, 0, $course->id);
        }
        Database::get()->query("INSERT IGNORE INTO course_module (module_id, visible, course_id) VALUES " .
            $placeholders, $values);
    });

    // delete deprecated course modules
    Database::get()->query("DELETE FROM course_module WHERE module_id = " . MODULE_ID_DESCRIPTION);
    Database::get()->query("DELETE FROM course_module WHERE module_id = " . MODULE_ID_LTI_CONSUMER);

    // update eclass version and unlock upgrade
    set_config('version', ECLASS_VERSION);
    set_config('upgrade_begin', '');

    // create directory indexes to hinder directory traversal in misconfigured servers
    updateInfo(-1, sprintf($langAddingDirectoryIndex, '3.11'));
    addDirectoryIndexFiles();

    updateInfo(1, $langUpgradeSuccess);

    $output_result = "<br/><div class='alert alert-success'>$langUpgradeSuccess<br/><b>$langUpgReady</b><br/><a href=\"../courses/$logfile\" target=\"_blank\">$langLogOutput</a></div><p/>";
    if ($command_line) {
        if ($debug_error) {
            echo " * $langUpgSucNotice\n";
        }
        echo $langUpgradeSuccess, "\n", $langLogOutput, ": $logfile_path/$logfile\n";
    } else {
        if ($debug_error) {
            $output_result .= "<div class='alert alert-danger'>" . $langUpgSucNotice . "</div>";
        }
        updateInfo(1, $output_result, false);
        // Close HTML body
        echo "</body></html>\n";
    }

    fwrite($logfile_handle, "\n</body>\n</html>\n");
    fclose($logfile_handle);

} // end of if not submit
