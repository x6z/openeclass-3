<?php
/*
 * ========================================================================
 * Open eClass 3.11 - E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2021  Greek Universities Network - GUnet
 * A full copyright notice can be read in "/info/copyright.txt".
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
 *
 * For a full list of contributors, see "credits.txt".
 */

$require_login = true;
$require_current_course = true;
$require_editor = true;

require_once '../../include/baseTheme.php';

$content = Database::get()->querySingle("SELECT * FROM h5p_content WHERE course_id = ?d AND id = ?d", $course_id, $_GET['id']);

if ($content) {
    deleteContent($content->id);
    Session::Messages($langH5pDeleteSuccess, 'alert-success');
    redirect($urlAppend . 'modules/h5p/?course=' . $course_code);
} else {
    redirect($urlAppend . 'modules/h5p/?course=' . $course_code);
}

/**
 * @brief delete h5p content
 * @param $contentId
 * @return bool
 */
function deleteContent($contentId): bool {
    global $course_id, $course_code, $webDir;

    $editorTmpDir = $webDir . "/courses/h5p/editor/";
    $contentDir = $webDir . "/courses/" . $course_code . "/h5p/content/" . $contentId;
    $filesDir = $contentDir . "/workspace/content";
    $contentDirMod = $webDir . "/courses/h5p/content/" . $contentId;

    foreach (scandir($filesDir) as $didx => $dir) {
        $dirPath = $filesDir . '/' . $dir;
        if (!in_array($dir, ['.', '..']) && is_dir($dirPath)) {
            foreach (scandir($dirPath) as $fidx => $file) {
                $filePath = $dirPath . '/' . $file;
                if (!in_array($file, ['.', '..']) && is_file($filePath)) {
                    $editorTmpPath = $editorTmpDir . $dir . '/' . $file;
                    if (file_exists($editorTmpPath)) {
                        unlink($editorTmpPath);
                    }
                }
            }
        }
    }

    H5PCore::deleteFileTree($contentDir);
    Database::get()->query("DELETE FROM h5p_content WHERE course_id = ?d AND id = ?d ", $course_id, $contentId);
    Database::get()->query("DELETE FROM h5p_content_dependency WHERE content_id = ?d ", $contentId);
    return H5PCore::deleteFileTree($contentDirMod);
}
