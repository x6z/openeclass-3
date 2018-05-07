<?php

/* ========================================================================
 * Open eClass
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2014  Greek Universities Network - GUnet
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
 * ========================================================================
 */

/**
 * Function lessonToolsMenu
 *
 * Creates a multi-dimensional array of the user's tools
 * in regard to the user's user level
 * (student | professor | platform administrator)
 *
 * @param bool $rich Whether to include rich text notifications in title
 *
 * @return array
 */
function lessonToolsMenu_offline($rich=true, $urlAppend) {
    global $langExternalLinks, $offline_course_modules;

    $sideMenuGroup = array();
    $sideMenuSubGroup = array();
    $sideMenuText = array();
    $sideMenuLink = array();
    $sideMenuImg = array();
    $sideMenuID = array();

    $arrMenuType = array();
    $arrMenuType['type'] = 'none';

    $tools_sections =
        array(array('type' => 'Public',
                    'title' => $GLOBALS['langCourseOptions'],
                    'class' => 'active'));

    foreach ($tools_sections as $section) {

        $sideMenuSubGroup = array();
        $sideMenuText = array();
        $sideMenuLink = array();
        $sideMenuImg = array();
        $sideMenuID = array();
        $arrMenuType = array('type' => 'text',
                            'text' => $section['title'],
                            'class' => $section['class']);
        array_push($sideMenuSubGroup, $arrMenuType);

        // sort array according to title (respect locale)
        setlocale(LC_COLLATE, $GLOBALS['langLocale']);
        usort($offline_course_modules, function ($a, $b) {
            return strcoll($a['title'], $b['title']);
        });
        foreach ($offline_course_modules as $mid) {
            array_push($sideMenuText, q($mid['title']));
            array_push($sideMenuLink, q($urlAppend . 'modules/' . $mid['link'] . '.html'));
            array_push($sideMenuImg, $mid['image']);
            array_push($sideMenuID, $mid);
        }
        array_push($sideMenuSubGroup, $sideMenuText);
        array_push($sideMenuSubGroup, $sideMenuLink);
        array_push($sideMenuSubGroup, $sideMenuImg);
        array_push($sideMenuSubGroup, $sideMenuID);
        array_push($sideMenuGroup, $sideMenuSubGroup);
    }
    $result2 = getExternalLinks();
    if ($result2) { // display external link (if any)
        $sideMenuSubGroup = array();
        $sideMenuText = array();
        $sideMenuLink = array();
        $sideMenuImg = array();
        $arrMenuType = array('type' => 'text',
            'text' => $langExternalLinks,
            'class' => 'external');
        array_push($sideMenuSubGroup, $arrMenuType);

        foreach ($result2 as $ex_link) {
            array_push($sideMenuText, q($ex_link->title));
            array_push($sideMenuLink, q($ex_link->url));
            array_push($sideMenuImg, 'fa-external-link');
        }

        array_push($sideMenuSubGroup, $sideMenuText);
        array_push($sideMenuSubGroup, $sideMenuLink);
        array_push($sideMenuSubGroup, $sideMenuImg);
        array_push($sideMenuGroup, $sideMenuSubGroup);
    }
    return $sideMenuGroup;
}

/**
 * Used in documents path navigation bar
 * @global type $langRoot
 * @global type $base_url
 * @global type $group_sql
 * @param type $path
 * @return type
 */
function make_clickable_path($path) {
    global $langRoot, $group_sql;

    $out = '';
    $depth = count(explode('/', $path));
    $i = 1;
    foreach (explode('/', $path) as $component) {
        $dotsprefix = "";
        for ($j = 1; $j <= $depth-$i; $j++) {
            $dotsprefix .= "../";
        }

        if (empty($component)) {
            $out = "<a href='" . $dotsprefix . "document.html'>$langRoot</a>";
        } else {
            $row = Database::get()->querySingle("SELECT filename FROM document WHERE path LIKE '%/$component' AND $group_sql");
            $dirname = $row->filename;
            $out .= " &raquo; <a href='" . $dotsprefix . $dirname . ".html'>" . q($dirname) . "</a>";
        }
        $i++;
    }
    return $out;
}

function getLinksOfCategory($cat_id, $is_editor, $filterv, $order, $course_id, $filterl, $is_in_tinymce, $compatiblePlugin) {
    $uncatresults = array();

    $vis_q = ($is_editor) ? '' : "AND visible = 1";
    if ($cat_id > 0) {
        $results['video'] = Database::get()->queryArray("SELECT * FROM video $filterv AND course_id = ?d AND category = ?d $vis_q $order", $course_id, $cat_id);
        $results['videolink'] = Database::get()->queryArray("SELECT * FROM videolink $filterl AND course_id = ?d AND category = ?d $vis_q $order", $course_id, $cat_id);
    } else {
        $results['video'] = Database::get()->queryArray("SELECT * FROM video $filterv AND course_id = ?d AND (category IS NULL OR category = 0) $vis_q $order", $course_id);
        $results['videolink'] = Database::get()->queryArray("SELECT * FROM videolink $filterl AND course_id = ?d AND (category IS NULL OR category = 0) $vis_q $order", $course_id);
    }

    foreach ($results as $table => $result) {
        foreach ($result as $myrow) {
            $myrow->course_id = $course_id;
            $resultObj = new stdClass();
            $resultObj->myrow = $myrow;
            $resultObj->table = $table;

            if (resource_access($myrow->visible, $myrow->public) || $is_editor) {
                switch ($table) {
                    case 'video':
                        $vObj = MediaResourceFactory::initFromVideo($myrow);
                        if ($is_in_tinymce && !$compatiblePlugin) { // use Access/DL URL for non-modable tinymce plugins
                            $vObj->setPlayURL($vObj->getAccessURL());
                        }
                        $resultObj->vObj = $vObj;
                        $resultObj->link_href = "<a href='video/" . $vObj->getUrl() . "'>" . $vObj->getTitle() . "</a>";
                        break;
                    case "videolink":
                        $resultObj->vObj = $vObj = MediaResourceFactory::initFromVideoLink($myrow);
                        $resultObj->link_href = "<a href='" . $vObj->getUrl() . "'>" . $vObj->getTitle() . "</a>";
                        break;
                    default:
                        exit;
                }

                $resultObj->row_class = (!$myrow->visible) ? 'not_visible' : 'visible' ;
                $resultObj->extradescription = '';

                if (!$is_in_tinymce and ( !empty($myrow->creator) or ! empty($myrow->publisher))) {
                    $resultObj->extradescription .= '<br><small>';
                    if ($myrow->creator == $myrow->publisher) {
                        $resultObj->extradescription .= $GLOBALS['langcreator'] . ": " . q($myrow->creator);
                    } else {
                        $emit = false;
                        if (!empty($myrow->creator)) {
                            $resultObj->extradescription .= $GLOBALS['langcreator'] . ": " . q($myrow->creator);
                            $emit = true;
                        }
                        if (!empty($myrow->publisher)) {
                            $resultObj->extradescription .= ($emit ? ', ' : '') . $GLOBALS['langpublisher'] . ": " . q($myrow->publisher);
                        }
                    }
                    $resultObj->extradescription .= "</small>";
                }
                $uncatresults[] = $resultObj;
            }
        }
    }

    return ($uncatresults);
}