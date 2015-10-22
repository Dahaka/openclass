<?php
/* ========================================================================
 * Open eClass 2.6
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2011  Greek Universities Network - GUnet
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


$can_upload = $is_editor || $is_admin;

if (defined('GROUP_DOCUMENTS')) {
    include '../group/group_functions.php';
    $subsystem = GROUP;
    initialize_group_id();
    initialize_group_info($group_id);        
    $subsystem_id = $group_id;
    $groupset = "group_id=$group_id&amp;";
    $base_url = $_SERVER['SCRIPT_NAME'] . '?course=' .$code_cours .'&amp;' . $groupset;
    $group_sql = "course_id = $cours_id AND subsystem = $subsystem AND subsystem_id = $subsystem_id";
    $group_hidden_input = "<input type='hidden' name='group_id' value='$group_id' />";
    $basedir = $webDir . 'courses/' . $currentCourseID . '/group/' . $secret_directory;
    $can_upload = $can_upload || $is_member;
    $nameTools = $langGroupDocumentsLink;
    $navigation[] = array('url' => $urlAppend . '/modules/group/group.php?course='.$code_cours, 'name' => $langGroups);
    $navigation[] = array('url' => $urlAppend . '/modules/group/group_space.php?course='.$code_cours.'&amp;group_id=' . $group_id, 'name' => q($group_name));
} elseif (defined('EBOOK_DOCUMENTS')) {
    if (isset($_REQUEST['ebook_id'])) {    
        $ebook_id = intval($_REQUEST['ebook_id']);
    }
    $subsystem = EBOOK;
    $subsystem_id = $ebook_id;
    $groupset = "ebook_id=$ebook_id&amp;";
    $base_url = $_SERVER['SCRIPT_NAME'] . '?course=' .$code_cours .'&amp;' . $groupset;
    $group_sql = "course_id = $cours_id AND subsystem = $subsystem AND subsystem_id = $subsystem_id";
    $group_hidden_input = "<input type='hidden' name='ebook_id' value='$ebook_id' />";
    $basedir = $webDir . '/courses/' . $currentCourseID . '/ebook/' . $ebook_id;
	$nameTools = $langFileAdmin;
	$navigation[] = array('url' => 'index.php?course='.$code_cours, 'name' => $langEBook);
    $navigation[] = array('url' => 'edit.php?course='.$code_cours.'&amp;id=' . $ebook_id, 'name' => $langEBookEdit);
} elseif (defined('COMMON_DOCUMENTS')) {
    $subsystem = COMMON;
    $base_url = $_SERVER['SCRIPT_NAME'] . '?';
    $subsystem_id = 'NULL';
    $groupset = '';
    $group_sql = "course_id = -1 AND subsystem = $subsystem";
    $group_hidden_input = '';
    $basedir = $webDir . 'courses/commondocs';
    if (!is_dir($basedir)) {
        mkdir($basedir, 0775);
    }        
    $nameTools = $langCommonDocs;        
    $navigation[] = array('url' => $urlAppend . '/modules/admin/index.php', 'name' => $langAdmin);
    // Saved course code so that file picker menu doesn't lose
    // the current course if we're in a course        
    if (isset($_GET['course']) and $_GET['course']) {
        define('SAVED_COURSE_CODE', $_GET['course']);
        define('SAVED_COURSE_ID', course_code_to_id(SAVED_COURSE_CODE));
        $base_url = $_SERVER['SCRIPT_NAME'] . '?course=' . SAVED_COURSE_CODE . '&amp;';
    }        
    $cours_id = -1;
    $code_cours = '';
} else {
    $subsystem = MAIN;
    $base_url = $_SERVER['SCRIPT_NAME'] . '?course=' .$code_cours .'&amp;';
    $subsystem_id = 'NULL';
    $groupset = '';
    $group_sql = "course_id = $cours_id AND subsystem = $subsystem";
    $group_hidden_input = '';
    $basedir = $webDir . 'courses/' . $currentCourseID . '/document';
    $nameTools = $langDoc;
}       
mysql_select_db($mysqlMainDb);
