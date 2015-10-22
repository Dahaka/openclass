<?php
/* ========================================================================
 * Open eClass 2.6
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2012  Greek Universities Network - GUnet
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
	viewer_toc.php
	@authors list: Thanos Kyritsis <atkyritsis@upnet.gr>

	based on Claroline version 1.7 licensed under GPL
	      copyright (c) 2001, 2006 Universite catholique de Louvain (UCL)

	      original file: navigation/tableOfContent.php Revision: 1.30

	Claroline authors: Piraux Sebastien <pir@cerdecam.be>
                      Lederer Guillaume <led@cerdecam.be>
==============================================================================
    @Description: Script for displaying a navigation bar to the users when
                  they are browsing a learning path

    @Comments:
==============================================================================
*/

$require_current_course = TRUE;
$require_login = TRUE;

require_once("../../config/config.php");
require_once("../../include/init.php");

/*
 * DB tables definition
 */
$TABLELEARNPATH         = "lp_learnPath";
$TABLEMODULE            = "lp_module";
$TABLELEARNPATHMODULE   = "lp_rel_learnPath_module";
$TABLEASSET             = "lp_asset";
$TABLEUSERMODULEPROGRESS= "lp_user_module_progress";

/**** The following is added for statistics purposes ***/
include('../../include/action.php');
$action = new action();
$action->record('MODULE_ID_LP');
/**************************************/

// lib of this tool
require_once("../../include/lib/learnPathLib.inc.php");

//lib of document tool
require_once("../../include/lib/fileDisplayLib.inc.php");

mysql_select_db($currentCourseID);

//  set redirection link
$returl = "navigation/viewModule.php?course=$currentCourseID&amp;go=" . 
          ($is_editor ? 'learningPathAdmin': 'learningPath');

if ($uid) {
	$uidCheckString = "AND UMP.`user_id` = $uid";
} else { // anonymous
        $uidCheckString = "AND UMP.`user_id` IS NULL ";
}

// get the list of available modules
$sql = "SELECT LPM.`learnPath_module_id` ,
	LPM.`parent`,
	LPM.`lock`,
            M.`module_id`,
            M.`contentType`,
            M.`name`,
            UMP.`lesson_status`, UMP.`raw`,
            UMP.`scoreMax`, UMP.`credit`,
            A.`path`
         FROM (`".$TABLELEARNPATHMODULE."` AS LPM,
              `".$TABLEMODULE."` AS M)
   LEFT JOIN `".$TABLEUSERMODULEPROGRESS."` AS UMP
           ON UMP.`learnPath_module_id` = LPM.`learnPath_module_id`
           ".$uidCheckString."
   LEFT JOIN `".$TABLEASSET."` AS A
          ON M.`startAsset_id` = A.`asset_id`
        WHERE LPM.`module_id` = M.`module_id`
          AND LPM.`learnPath_id` = '" . (int)$_SESSION['path_id'] ."'
          AND LPM.`visibility` = 'SHOW'
          AND LPM.`module_id` = M.`module_id`
     GROUP BY LPM.`module_id`
     ORDER BY LPM.`rank`";

$extendedList = db_query_fetch_all($sql);

// build the array of modules
// build_element_list return a multi-level array, where children is an array with all nested modules
// build_display_element_list return an 1-level array where children is the deep of the module
$flatElementList = build_display_element_list(build_element_list($extendedList, 'parent', 'learnPath_module_id'));

$is_blocked = false;
$moduleNb = 0;

// get the name of the learning path
$sql = "SELECT `name`
      FROM `".$TABLELEARNPATH."`
      WHERE `learnPath_id` = '". (int)$_SESSION['path_id']."'";

$lpName = db_query_get_single_value($sql);

$previous = ""; // temp id of previous module, used as a buffer in foreach
$previousModule = ""; // module id that will be used in the previous link
$nextModule = ""; // module id that will be used in the next link

foreach ($flatElementList as $module)
{	
	// spacing col
	if (!$is_blocked or $is_editor)
	{
		if($module['contentType'] != CTLABEL_) // chapter head
		{
			// bold the title of the current displayed module
			if( $_SESSION['lp_module_id'] == $module['module_id'] )
			{
				$previousModule = $previous;
			}
			// store next value if user has the right to access it
			if( $previous == $_SESSION['lp_module_id'] )
			{
				$nextModule = $module['module_id'];
			}
		}
                // a module ALLOW access to the following modules if
                // document module : credit == CREDIT || lesson_status == 'completed'
                // exercise module : credit == CREDIT || lesson_status == 'passed'
                // scorm module : credit == CREDIT || lesson_status == 'passed'|'completed'
                                
		if (($module['lock'] == 'CLOSE') 
                    and ($module['credit'] != 'CREDIT' 
                    or ($module['lesson_status'] != 'COMPLETED' and $module['lesson_status'] != 'PASSED'))) {
                            $is_blocked = true; // following modules will be unlinked
                }
	}

	if($module['contentType'] != CTLABEL_ )
		$moduleNb++; // increment number of modules used to compute global progression except if the module is a title

	// used in the foreach the remember the id of the previous module_id
	// don't remember if label...
	if ($module['contentType'] != CTLABEL_ )
		$previous = $module['module_id'];

} // end of foreach ($flatElementList as $module)

$prevNextString = "";
// display previous and next links only if there is more than one module
if ( $moduleNb > 1 )
{
	$imgPrevious = '<img src="'.$themeimg.'/lp/back.png" alt="'.q($langPrevious).'" title="'.q($langPrevious).'">';
	$imgNext = '<img src="'.$themeimg.'/lp/next.png" alt="'.q($langNext).'" title="'.q($langNext).'">';

	if( $previousModule != '' )
		$prevNextString .= '<a href="navigation/viewModule.php?course='.$code_cours.'&amp;viewModule_id='.$previousModule.'" target="scoFrame">'.$imgPrevious.'</a>';
	else
		$prevNextString .=  $imgPrevious;
	$prevNextString .=  '&nbsp;';

	if( $nextModule != '' )
		$prevNextString .=  '<a href="navigation/viewModule.php?course='.$code_cours.'&amp;viewModule_id='.$nextModule.'" target="scoFrame">'.$imgNext.'</a>';
	else
		$prevNextString .=  $imgNext;
}

echo "<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN' 'http://www.w3.org/TR/html4/loose.dtd'>
<html>
<head><title>-</title>
    <meta http-equiv='Content-Type' content='text/html; charset=$charset'>
    <link href='$urlAppend/template/$theme/lp.css' rel='stylesheet' type='text/css' />
</head>
<body>
<div class='header'>
    <div class='tools'>
    <div class='lp_right'>$prevNextString&nbsp;<a href='$returl' target='_top'>
        <img src='$themeimg/lp/nofullscreen.png' alt='".q($langQuitViewer)."' title='".q($langQuitViewer)."' /></a></div>
    <div class='lp_left'>
        <a href='$urlAppend/courses/$currentCourseID' target='_top' title='" .
                q($currentCourseName) . "'>" . q(ellipsize($currentCourseName, 35)) . "</a> &#187;
        <a href='$urlAppend/modules/learnPath/learningPathList.php?course=$currentCourseID' target='_top'>
                $langLearningPaths</a> &#187;
        <a href='$returl' title='" . q($lpName) . "' target='_top'>" . q(ellipsize($lpName, 40)) . "</a></div>
    <div class='clear'></div>
    <div class='logo'><img src='$themeimg/lp/logo_openeclass.png' alt='' title='' /></div>
    <div class='lp_right_grey'>";
if($uid) {
	$lpProgress = get_learnPath_progress((int)$_SESSION['path_id'],$uid);
	echo $langProgress . ': ' . disp_progress_bar($lpProgress, 1) ."&nbsp;". $lpProgress ."%";
}
echo "</div></div></div></body></html>";
