<?php
/* ========================================================================
 * Open eClass 2.10
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
 * ======================================================================== */


/*===========================================================================
	insertMyDescription.php
	@last update: 30-06-2006 by Thanos Kyritsis
	@authors list: Thanos Kyritsis <atkyritsis@upnet.gr>
==============================================================================
    @Description: This script lets the course
                  admin to add the course description to a learning path

    @Comments:

    @todo:
==============================================================================
*/

require_once("../../include/lib/learnPathLib.inc.php");

$require_current_course = TRUE;
$require_editor = TRUE;

$TABLELEARNPATH         = "lp_learnPath";
$TABLEMODULE            = "lp_module";
$TABLELEARNPATHMODULE   = "lp_rel_learnPath_module";
$TABLEASSET             = "lp_asset";
$TABLEUSERMODULEPROGRESS= "lp_user_module_progress";

require_once("../../include/baseTheme.php");

$navigation[] = array("url"=>"learningPathList.php?course=$code_cours", "name"=> $langLearningPaths);
$navigation[] = array("url"=>"learningPathAdmin.php?course=$code_cours&amp;path_id=".(int)$_SESSION['path_id'], "name"=> $langAdm);
$nameTools = $langInsertMyDescToolName;

mysql_select_db($currentCourseID);

/*======================================*/

// TODO: check if course description is already in the pool of modules
// and if it is, use that instead of adding it as new

// SQL Checks
// check if a module of this course already used the same document
$sql = "SELECT * FROM `".$TABLEMODULE."` AS M, `".$TABLEASSET."` AS A
	WHERE A.`module_id` = M.`module_id` AND M.`contentType` = \"".CTCOURSE_DESCRIPTION_."\"";
$query = db_query($sql);
$num = mysql_num_rows($query);

if ($num == 0)
{

        // create new module
	// TODO: name goes from langWhatever
	$sql = "INSERT INTO `".$TABLEMODULE."`
		(`name`, `contentType`, `comment`, `launch_data`)
		VALUES ('".$langCourseDescription."', '".CTCOURSE_DESCRIPTION_."', '', '')";        
	$query = db_query($sql);

	$insertedModule_id = mysql_insert_id();

	// create new asset
	$sql = "INSERT INTO `".$TABLEASSET."`
		(`path` , `module_id`, `comment` )
		VALUES ('', " . (int)$insertedModule_id . ", '' )";
	$query = db_query($sql);

	$insertedAsset_id = mysql_insert_id();

	$sql = "UPDATE `".$TABLEMODULE."`
	SET `startAsset_id` = " . (int)$insertedAsset_id . "
	WHERE `module_id` = " . (int)$insertedModule_id . "";
	$query = db_query($sql);

	// determine the default order of this Learning path
	$sql = "SELECT MAX(`rank`)
		FROM `".$TABLELEARNPATHMODULE."`";
	$result = db_query($sql);

	list($orderMax) = mysql_fetch_row($result);
	$order = $orderMax + 1;

	// finally : insert in learning path
	$sql = "INSERT INTO `".$TABLELEARNPATHMODULE."`
		(`learnPath_id`, `module_id`, `rank`, `lock`, `specificComment`)
		VALUES ('". (int)$_SESSION['path_id']."', '". (int)$insertedModule_id."',
		" . (int)$order . ", 'OPEN', '')";
	$query = db_query($sql);
}
else
{   
	// check if this is this LP that used this course description as a module
	$sql = "SELECT * FROM `".$TABLELEARNPATHMODULE."` AS LPM,
		`".$TABLEMODULE."` AS M,
		`".$TABLEASSET."` AS A
		WHERE M.`module_id` =  LPM.`module_id`
		AND M.`startAsset_id` = A.`asset_id`
		AND LPM.`learnPath_id` = ". (int)$_SESSION['path_id'] ."
		AND M.`contentType` = \"".CTCOURSE_DESCRIPTION_."\"";
	$query2 = db_query($sql);
	$num = mysql_num_rows($query2);

	if ($num == 0) { // used in another LP but not in this one, so reuse the module id reference instead of creating a new one
		$thisDocumentModule = mysql_fetch_array($query);
		// determine the default order of this Learning path
		$sql = "SELECT MAX(`rank`)
			FROM `".$TABLELEARNPATHMODULE."`";
		$result = db_query($sql);

		list($orderMax) = mysql_fetch_row($result);
		$order = $orderMax + 1;

		// finally : insert in learning path
		$sql = "INSERT INTO `".$TABLELEARNPATHMODULE."`
			(`learnPath_id`, `module_id`, `rank`, `lock`, `specificComment`)
			VALUES ('". (int)$_SESSION['path_id']."', '".(int)$thisDocumentModule['module_id']."',
			" . (int)$order . ", 'OPEN', '')";
		$query = db_query($sql);

    }
}
$tool_content = "<table width=\"99%\" class=\"tbl\"><tr><td class=\"success\">";

$tool_content .= disp_tool_title($langLinkInsertedAsModule);
$tool_content .= "</td></tr></table>";
	$tool_content .= "
    <br />
    <p align=\"right\"><a href=\"learningPathAdmin.php?course=$code_cours&amp;path_id=".(int)$_SESSION['path_id']."\">$langBackToLPAdmin</a></p>";
draw($tool_content, 2);
?>
