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
	module.php
	@authors list: Thanos Kyritsis <atkyritsis@upnet.gr>

	based on Claroline version 1.7 licensed under GPL
	      copyright (c) 2001, 2006 Universite catholique de Louvain (UCL)

	      original file: module.php Revision: 1.26

	Claroline authors: Piraux Sebastien <pir@cerdecam.be>
                      Lederer Guillaume <led@cerdecam.be>
==============================================================================
    @Description: This script provides information on the progress of a
                  learning path module and then launches navigation for it.
                  It also displays some extra option for the teacher.

    @Comments:
==============================================================================
*/

require_once("../../include/lib/learnPathLib.inc.php");
require_once("../../include/lib/fileDisplayLib.inc.php");
require_once("../../include/lib/fileManageLib.inc.php");
require_once("../../include/lib/fileUploadLib.inc.php");

$require_current_course = TRUE;
$require_login = TRUE;

$TABLELEARNPATH         = "lp_learnPath";
$TABLEMODULE            = "lp_module";
$TABLELEARNPATHMODULE   = "lp_rel_learnPath_module";
$TABLEASSET             = "lp_asset";
$TABLEUSERMODULEPROGRESS= "lp_user_module_progress";

$TABLEQUIZTEST          = "exercices";
$dbTable                = $TABLEASSET; // for old functions of document tool

require_once("../../include/baseTheme.php");
require_once '../../include/lib/modalboxhelper.class.php';
require_once '../../include/lib/multimediahelper.class.php';
ModalBoxHelper::loadModalBox();

$body_action = '';

$nameTools = $langLearningObject;
if (!add_units_navigation()) {
	$navigation[] = array('url' => "learningPathList.php?course=$code_cours", 'name'=> $langLearningPaths);
	if ($is_editor) {
                $navigation[] = array('url' => "learningPathAdmin.php?course=$code_cours&amp;path_id=".(int)$_SESSION['path_id'],
                                      'name' => $langAdm);
	}
}


if ( isset($_GET['path_id']) && $_GET['path_id'] != '' )
{
    $_SESSION['path_id'] = intval($_GET['path_id']);
}
// module_id
if ( isset($_GET['module_id']) && $_GET['module_id'] != '')
{
    $_SESSION['lp_module_id'] = intval($_GET['module_id']);
}

mysql_select_db($currentCourseID);

$q = db_query("SELECT name, visibility FROM $TABLELEARNPATH WHERE learnPath_id = '".(int)$_SESSION['path_id']."'");
$lp = mysql_fetch_array($q);
if (!add_units_navigation() && !$is_editor) {
	$navigation[] = array("url" => "learningPath.php?course=$code_cours&amp;path_id=".(int)$_SESSION['path_id'], "name" => $lp['name']);
}

if ( !$is_editor && $lp['visibility'] == "HIDE" ) {
	// if the learning path is invisible, don't allow users in it
	header("Location: ./learningPathList.php?course=$code_cours");
	exit();
}

check_LPM_validity($is_editor, $code_cours);

// main page
// FIRST WE SEE IF USER MUST SKIP THE PRESENTATION PAGE OR NOT
// triggers are : if there is no introdution text or no user module progression statistics yet and user is not admin,
// then there is nothing to show and we must enter in the module without displaying this page.

/*
 *  GET INFOS ABOUT MODULE and LEARNPATH_MODULE
 */

// check in the DB if there is a comment set for this module in general

$sql = "SELECT `comment`, `startAsset_id`, `contentType`
        FROM `".$TABLEMODULE."`
        WHERE `module_id` = ". (int)$_SESSION['lp_module_id'];

$module = db_query_get_single_row($sql);

if( empty($module['comment']) || $module['comment'] == $langDefaultModuleComment )
{
  	$noModuleComment = true;
}
else
{
   $noModuleComment = false;
}


if( $module['startAsset_id'] == 0 )
{
    $noStartAsset = true;
}
else
{
    $noStartAsset = false;
}


// check if there is a specific comment for this module in this path
$sql = "SELECT `specificComment`
        FROM `".$TABLELEARNPATHMODULE."`
        WHERE `module_id` = ". (int)$_SESSION['lp_module_id'];

$learnpath_module = db_query_get_single_row($sql);

if( empty($learnpath_module['specificComment']) || $learnpath_module['specificComment'] == $langDefaultModuleAddedComment )
{
	$noModuleSpecificComment = true;
}
else
{
    $noModuleSpecificComment = false;
}

// check in DB if user has already browsed this module

$sql = "SELECT `contentType`,
	`total_time`,
	`session_time`,
	`scoreMax`,
	`raw`,
	`lesson_status`
        FROM `".$TABLEUSERMODULEPROGRESS."` AS UMP,
             `".$TABLELEARNPATHMODULE."` AS LPM,
             `".$TABLEMODULE."` AS M
        WHERE UMP.`user_id` = '$uid'
          AND UMP.`learnPath_module_id` = LPM.`learnPath_module_id`
          AND LPM.`learnPath_id` = ".(int)$_SESSION['path_id']."
          AND LPM.`module_id` = ". (int)$_SESSION['lp_module_id']."
          AND LPM.`module_id` = M.`module_id`
             ";
$resultBrowsed = db_query_get_single_row($sql);

        if ($module['contentType']== CTSCORM_ || $module['contentType']== CTSCORMASSET_) { $nameTools = $langSCORMTypeDesc; }
        if ($module['contentType']== CTEXERCISE_ ) { $nameTools = $langExerciseAsModuleLabel; }
        if ($module['contentType']== CTDOCUMENT_ ) { $nameTools = $langDocumentAsModuleLabel; }
        if ($module['contentType']== CTLINK_ ) { $nameTools = $langLinkAsModuleLabel; }
        if ($module['contentType']== CTCOURSE_DESCRIPTION_ ) { $nameTools = $langCourseDescriptionAsModuleLabel; }
        if ($module['contentType']== CTLABEL_ ) { $nameTools = $langModuleOfMyCourseLabel_onom; }
        if ($module['contentType']== CTMEDIA_ || $module['contentType']== CTMEDIALINK_) { $nameTools = $langMediaAsModuleLabel; }
        if ($is_editor)
            $nameTools = $langModify ." ". $nameTools;
        else
            $nameTools = $langTracking ." ". $nameTools;



// redirect user to the path browser if needed
if( !$is_editor
	&& ( !is_array($resultBrowsed) || !$resultBrowsed || count($resultBrowsed) <= 0 )
	&& $noModuleComment
	&& $noModuleSpecificComment
	&& !$noStartAsset
	)
{
    header("Location:./viewer.php?course=$code_cours");
    exit();
}

$tool_content .="
<fieldset>
    <legend>$langLearningObjectData</legend>
    <table width=\"100%\" class=\"tbl_alt\">";
//################################## MODULE NAME BOX #################################\\
$tool_content .="
    <tr>
      <th width=\"250\" class=\"left\">$langTitle:</th>
      <td>";
      $cmd = ( isset($_REQUEST['cmd']) && is_string($_REQUEST['cmd']) )? (string)$_REQUEST['cmd'] : '';

if ($cmd == "updateName")
{
    $tool_content .= "".disp_message_box1(nameBox(MODULE_, UPDATE_, $langModify))."";
}
else
{
    $tool_content .= "".nameBox(MODULE_, DISPLAY_)."";
}

$tool_content .= "
      </td>
    </tr>";
$tool_content .="
    <tr>
      <th class=\"left\">$langComments:</th>
      <td class=\"left\">";
if($module['contentType'] != CTLABEL_ )
{

    //############################### MODULE COMMENT BOX #################################\\
    //#### COMMENT #### courseAdmin cannot modify this if this is a imported module ####\\
    // this the comment of the module in ALL learning paths
    if ( $cmd == "updatecomment" )
    {
        $tool_content .= "".commentBox(MODULE_, UPDATE_)."";
        $head_content .= disp_html_area_head("insertCommentBox");
		$body_action = "onload=\"initEditor()\"";
    }
    elseif ($cmd == "delcomment" )
    {
        $tool_content .= "".commentBox(MODULE_, DELETE_)."";
    }
    else
    {
        $tool_content .= "".commentBox(MODULE_, DISPLAY_)."";
    }
$tool_content .="
      </td>
    </tr>";
$tool_content .="
    <tr>
      <th class=\"left\">$langComments - $langInstructions:<br /><small>($langModuleComment_inCurrentLP)</small></th>
      <td class=\"left\">";
    //#### ADDED COMMENT #### courseAdmin can always modify this ####\\
    // this is a comment for THIS module in THIS learning path
    if ( $cmd == "updatespecificComment" )
    {
        $tool_content .= "".commentBox(LEARNINGPATHMODULE_, UPDATE_)."";
        $head_content .= disp_html_area_head("insertCommentBox");
		$body_action = "onload=\"initEditor()\"";
    }
    elseif ($cmd == "delspecificComment" )
    {
        $tool_content .= "".commentBox(LEARNINGPATHMODULE_, DELETE_)."";
    }
    else
    {
        $tool_content .= "".commentBox(LEARNINGPATHMODULE_, DISPLAY_)."";
    }
} //  if($module['contentType'] != CTLABEL_ )

$tool_content .= "
      </td>
    </tr>";
    $tool_content .="
    <tr>
      <th class=\"left\">$langProgInModuleTitle:</th>
      <td>";
//############################ PROGRESS  AND  START LINK #############################\\

/* Display PROGRESS */

if($module['contentType'] != CTLABEL_) //
{

    if( $resultBrowsed && count($resultBrowsed) > 0 && $module['contentType'] != CTLABEL_)
    {
        $contentType_img = selectImage($resultBrowsed['contentType']);
        $contentType_alt = selectAlt($resultBrowsed['contentType']);

        if ($resultBrowsed['contentType']== CTSCORM_ || $resultBrowsed['contentType']== CTSCORMASSET_ ) { $contentDescType = $langSCORMTypeDesc;    }
        if ($resultBrowsed['contentType']== CTEXERCISE_ ) { $contentDescType = $langEXERCISETypeDesc; }
        if ($resultBrowsed['contentType']== CTDOCUMENT_ ) { $contentDescType = $langDOCUMENTTypeDesc; }
        if ($resultBrowsed['contentType']== CTLINK_ ) { $contentDescType = $langLINKTypeDesc; }
        if ($resultBrowsed['contentType']== CTCOURSE_DESCRIPTION_ ) { $contentDescType = $langDescriptionCours; }
        if ($resultBrowsed['contentType']== CTMEDIA_ || $resultBrowsed['contentType']== CTMEDIALINK_) { $contentDescType = $langMediaTypeDesc; }


		$tool_content .= ''."\n\n"
			.'        <table class="tbl_alt">'."\n"
			.'        <tr>'."\n"
			.'          <th>'.$langInfoProgNameTitle.'</th>'."\n"
			.'          '."\n"
			.'          <th>'.$langPersoValue.'</th>'."\n"
			.'        </tr>'."\n";

        //display type of the module
		$tool_content .= '        <tr class="even">'."\n"
                        .'          <td>'.$langTypeOfModule.'</td>'."\n"
                        .'          '."\n"
			.'          <td align="right"><img src="'.$themeimg.'/'.$contentType_img.'" alt="'.$contentType_alt.'" title="'.$contentType_alt.'" border="0" />&nbsp;&nbsp;'.$contentDescType.'</td>'."\n"
			.'        </tr>'."\n\n";

        //display total time already spent in the module
		$tool_content .= '        <tr class="even">'."\n"
			.'          <td>'.$langTotalTimeSpent.'</td>'."\n"
                        .'          '."\n"
			.'          <td align="right">'.$resultBrowsed['total_time'].'</td>'."\n"
			.'        </tr>'."\n\n";

        //display time passed in last session
		$tool_content .= '        <tr class="even">'."\n"
			.'          <td>'.$langLastSessionTimeSpent.'</td>'."\n"
                        .'          '."\n"
			.'          <td align="right">'.$resultBrowsed['session_time'].'</td>'."\n"
			.'        </tr>'."\n\n";

        //display user best score
        if ($resultBrowsed['scoreMax'] > 0)
        {
		$raw = round($resultBrowsed['raw']/$resultBrowsed['scoreMax']*100);
        }
        else
        {
		$raw = 0;
        }

        $raw = max($raw, 0);

        if (($resultBrowsed['contentType'] == CTSCORM_ ) && ($resultBrowsed['scoreMax'] <= 0)
            &&  ((($resultBrowsed['lesson_status'] == "COMPLETED") || ($resultBrowsed['lesson_status'] == "PASSED") ) || ($resultBrowsed['raw'] != -1) ) )
        {
		$raw = 100;
        }

        // no sens to display a score in case of a document module
        if ( ($resultBrowsed['contentType'] != CTDOCUMENT_) &&
             ($resultBrowsed['contentType'] != CTLINK_) &&
             ($resultBrowsed['contentType'] != CTCOURSE_DESCRIPTION_) &&
             ($resultBrowsed['contentType'] != CTMEDIA_) &&
             ($resultBrowsed['contentType'] != CTMEDIALINK_)
           )
        {
		$tool_content .= '<tr>'."\n"
			.'          <td>'.$langYourBestScore.'</td>'."\n"
            .'          '."\n"
			.'          <td>'.disp_progress_bar($raw, 1).' '.$raw.'%</td>'."\n"
			.'        </tr>'."\n\n";
        }

        //display lesson status

        // display a human readable string ...

		if ($resultBrowsed['lesson_status']=="NOT ATTEMPTED") {
			$statusToDisplay = $langNotAttempted;
		}
		else if ($resultBrowsed['lesson_status']=="PASSED") {
			$statusToDisplay = $langPassed;
		}
		else if ($resultBrowsed['lesson_status']=="FAILED") {
			$statusToDisplay = $langFailed;
		}
		else if ($resultBrowsed['lesson_status']=="COMPLETED") {
			$statusToDisplay = $langAlreadyBrowsed;
		}
		else if ($resultBrowsed['lesson_status']=="BROWSED") {
			$statusToDisplay = $langAlreadyBrowsed;
		}
		else if ($resultBrowsed['lesson_status']=="INCOMPLETE") {
			$statusToDisplay = $langNeverBrowsed;
		}
        else {
            $statusToDisplay = $resultBrowsed['lesson_status'];
        }

		$tool_content .= '        <tr class="even">'."\n"
			.'          <td>'.$langLessonStatus.'</td>'."\n"
            .'          '."\n"
			.'          <td align="right">'.$statusToDisplay.'</td>'."\n"
			.'        </tr>'."\n\n"
			.'        </table>'."\n\n";

    } //end display stats

    /* START */
    // check if module.startAssed_id is set and if an asset has the corresponding asset_id
    // asset_id exists ?  for the good module  ?
    $sql = "SELECT `asset_id`
              FROM `".$TABLEASSET."`
             WHERE `asset_id` = ". (int)$module['startAsset_id']."
               AND `module_id` = ". (int)$_SESSION['lp_module_id'];

	$asset = db_query_get_single_row($sql);
$tool_content .= "
      </td>
    </tr>";
    $tool_content .="
    <tr>
      <th class=\"left\">$langPreview:</th>
      <td>";
    if( $module['startAsset_id'] != "" && $asset['asset_id'] == $module['startAsset_id'] )
    {
	$tool_content .= ''."\n"
		.'        <form action="./viewer.php?course='.$code_cours.'" method="post">'."\n"
		.'        <input type="submit" value="'.q($langStartModule).'" />'."\n"
		.'        </form>'."\n";
    }
    else
    {
        $tool_content .= '        <p><center>'.$langNoStartAsset.'</center></p>'."\n";
    }
}// end if($module['contentType'] != CTLABEL_)
// if module is a label, only allow to change its name.
$tool_content .= "
      </td>
    </tr>";
//####################################################################################\\
//################################# ADMIN DISPLAY ####################################\\
//####################################################################################\\
/*
if( $is_editor ) // for teacher only
{
    switch ($module['contentType'])
    {
        case CTDOCUMENT_ :
            require_once("./include/document.inc.php");
            break;
        case CTEXERCISE_ :
            require_once("./include/exercise.inc.php");
            break;
        case CTSCORM_ :
            require_once("./include/scorm.inc.php");
            break;
        case CTCLARODOC_ :
        case CTLABEL_ :
        case CTCOURSE_DESCRIPTION_ :
        case CTLINK_:
       	break;
    }
} // if ($is_editor)
*/

    $tool_content .= "
    </table>
    </fieldset>"
  ;

//back button
if ($is_editor) {
	$pathBack = "./learningPathAdmin.php";
} else {
	$pathBack = "./learningPath.php";
}
$tool_content .= "
    <p align=\"right\"><a href=\"".$pathBack."?course=$code_cours\">".$langBackToLPAdmin."</a></p>";

draw($tool_content, 2, null, $head_content, $body_action);
