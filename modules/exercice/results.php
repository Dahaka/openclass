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


$TBL_EXERCICE_QUESTION='exercice_question';
$TBL_EXERCICES='exercices';
$TBL_QUESTIONS='questions';
$TBL_REPONSES='reponses'; 
$TBL_RECORDS='exercise_user_record';

$require_current_course = TRUE;
$require_help = TRUE;
$helpTopic = 'Exercise';

include '../../include/baseTheme.php';
include('exercise.class.php');
include('../../include/lib/textLib.inc.php');
require_once '../../include/lib/modalboxhelper.class.php';
require_once '../../include/lib/multimediahelper.class.php';
ModalBoxHelper::loadModalBox();

if (!$is_editor) {
    redirect_to_home_page("modules/exercice/exercice.php?course=$code_cours");
}

$nameTools = $langResults;
$navigation[]=array("url" => "exercice.php?course=$code_cours","name" => $langExercices);

if (isset($_GET['exerciseId'])) {
	$exerciseId = intval($_GET['exerciseId']);
}

// if the object is not in the session
if(!isset($_SESSION['objExercise'][$exerciseId])) {
	// construction of Exercise
	$objExercise = new Exercise();
	// if the specified exercise doesn't exist or is disabled
	if(!$objExercise->read($exerciseId) && (!$is_editor)) {
		$tool_content .= "<p>$langExerciseNotFound</p>";	
		draw($tool_content, 2);
		exit();
	}
}

if (isset($_SESSION['objExercise'][$exerciseId])) {
	$objExercise = $_SESSION['objExercise'][$exerciseId];
}

$exerciseTitle=$objExercise->selectTitle();
$exerciseDescription=$objExercise->selectDescription();
$exerciseDescription_temp = nl2br(make_clickable($exerciseDescription));
	
$tool_content .= "
    <table class=\"tbl_border\" width=\"100%\">
    <tr>
    <th>". q($exerciseTitle) ."</th>
    </tr>
    <tr>
    <td>". standard_text_escape($exerciseDescription_temp) ."</td>
    </tr>
    </table>
    <br/>";

mysql_select_db($currentCourseID);
$sql="SELECT DISTINCT uid FROM `$TBL_RECORDS`";
$result = db_query($sql);
while($row=mysql_fetch_array($result)) {
	$sid = $row['uid'];
	$StudentName = db_query("SELECT nom,prenom,am FROM user WHERE user_id='$sid'", $mysqlMainDb);
	$theStudent = mysql_fetch_array($StudentName);
	
	mysql_select_db($currentCourseID);
	$sql2="SELECT DATE_FORMAT(RecordStartDate, '%Y-%m-%d / %H:%i') AS RecordStartDate, RecordEndDate,
		TIME_TO_SEC(TIMEDIFF(RecordEndDate,RecordStartDate))
		AS TimeDuration, TotalScore, TotalWeighting 
		FROM `$TBL_RECORDS` WHERE uid='$sid' AND eid='$exerciseId'";
	$result2 = db_query($sql2);
	if (mysql_num_rows($result2) > 0) { // if users found
		$tool_content .= "
    <table class='tbl_alt' width='100%'>";
		$tool_content .= "
    <tr>
      <td colspan='3'>";
		if (!$sid) {
			$tool_content .= "$langNoGroupStudents";
		} else {
			if ($theStudent['am'] == '') $studentam = '-';
			else $studentam = $theStudent['am'];
			$tool_content .= "<b>$langUser:</b> $theStudent[nom] $theStudent[prenom]  <div class='smaller'>($langAm: $studentam)</div>";
		}
		$tool_content .= "</td>
    </tr>
    <tr>
      <th width='150' class='center'>".$langExerciseStart."</td>
      <th width='150' class='center'>".$langExerciseDuration."</td>
      <th width='150' class='center'>".$langYourTotalScore2."</td>
    </tr>";
 	
                $k=0;
		while($row2=mysql_fetch_array($result2)) {
        if ($k%2 == 0) {
                $tool_content .= "    <tr class='even'>\n";
        } else {
                $tool_content .= "    <tr class='odd'>\n";
        }

			$tool_content .= "
      <td class='center'>$row2[RecordStartDate]</td>";
			if ($row2['TimeDuration'] == '00:00:00' or empty($row2['TimeDuration'])) { // for compatibility 
				$tool_content .= "
      <td class='center'>$langNotRecorded</td>";
			} else {
				$tool_content .= "
      <td class='center'>".format_time_duration($row2['TimeDuration'])."</td>";
			}
			$tool_content .= "
      <td class='center'>".$row2['TotalScore']. "/".$row2['TotalWeighting']."</td>
    </tr>";
    $k++;
		}
	$tool_content .= "
    </table>
    <br/>";
	}
}
draw($tool_content, 2, null, $head_content);
?>	
