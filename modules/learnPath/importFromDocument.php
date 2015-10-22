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


/*===========================================================================
	importFromDocument.php
	@last update: 25-03-2010 by Thanos Kyritsis
	@authors list: Thanos Kyritsis <atkyritsis@upnet.gr>
==============================================================================
    @Description: This script handles importing of SCORM packages
                  from Open eClass document files.
==============================================================================
*/


$require_current_course = TRUE;
$require_editor = TRUE;

require_once("../../include/baseTheme.php");

$navigation[]= array ("url"=>"learningPathList.php?course=$code_cours", "name"=> $langLearningPaths);
$nameTools = $langimportLearningPath;

mysql_select_db($currentCourseID);

if (isset($_POST) && isset($_POST['selectedDocument'])) {
	require_once("./importLearningPathLib.php");
	
	$filename = basename($_POST['selectedDocument']);
	$srcFile = "../../courses/".$currentCourseID."/document/".$_POST['selectedDocument'];
	$destFile = "../../courses/".$currentCourseID."/temp/".$filename;
	
	copy($srcFile, $destFile);
	
	list($messages, $lpid) = doImport($currentCourseID, $mysqlMainDb, $webDir, filesize($destFile), $filename, true);
	$tool_content .= $messages;
	$tool_content .= "\n<br /><a href=\"importLearningPath.php?course=$code_cours\">$langBack</a></p>";
	
	unlink($destFile);
}
else {
	$tool_content .= "Error, please try again!";
}

draw($tool_content, 2);