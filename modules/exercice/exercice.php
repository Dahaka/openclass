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


// answer types
define('UNIQUE_ANSWER', 1);
define('MULTIPLE_ANSWER', 2);
define('FILL_IN_BLANKS', 3);
define('MATCHING', 4);
define('TRUE_FALSE', 5);

require_once 'exercise.class.php';
require_once 'question.class.php';
require_once 'answer.class.php';
$require_current_course = TRUE;

$require_help = TRUE;
$helpTopic = 'Exercise';
$guest_allowed = true;

require_once '../../include/baseTheme.php';
require_once '../../include/lib/modalboxhelper.class.php';
require_once '../../include/lib/multimediahelper.class.php';
ModalBoxHelper::loadModalBox();

/**** The following is added for statistics purposes ***/
require_once '../../include/action.php';
$action = new action();
$action->record('MODULE_ID_EXERCISE');

$nameTools = $langExercices;

/*******************************/
/* Clears the exercise session */
/*******************************/
if (isset($_SESSION['objExercise']))  { unset($_SESSION['objExercise']); }
if (isset($_SESSION['objQuestion']))  { unset($_SESSION['objQuestion']); }
if (isset($_SESSION['objAnswer']))  { unset($_SESSION['objAnswer']); }
if (isset($_SESSION['questionList']))  { unset($_SESSION['questionList']); }
if (isset($_SESSION['exerciseResult']))  { unset($_SESSION['exerciseResult']); }

$TBL_EXERCICE_QUESTION='exercice_question';
$TBL_EXERCICES='exercices';
$TBL_QUESTIONS='questions';
$TBL_RECORDS='exercise_user_record';

// maximum number of exercises on a same page
$limitExPage = 15;
if (isset($_GET['page'])) {
	$page = intval($_GET['page']);
} else {
	$page = 0;
}
// selects $limitExPage exercises at the same time
$from = $page * $limitExPage;

// only for administrator
if($is_editor) {	
        $head_content .= <<< END
        <script type="text/javascript">	
	function confirmation (name)
	{
                if (name == "purge") {
                        if (confirm("$langConfirmPurgeExercises")) {
                                return true;
                        } else {
                                return false;
                        }
                } else if (name == "delete") {
                        if (confirm("$langConfirmDelete")) {
                                return true;
                        } else {
                            return false;
                        }
                }
	}
	</script>
END;

	if (isset($_GET['exerciseId'])) {
		$exerciseId = $_GET['exerciseId'];
	}
	if(!empty($_GET['choice'])) {
		// construction of Exercise
		$objExerciseTmp=new Exercise();
		if($objExerciseTmp->read($exerciseId))
		{
			switch($_GET['choice'])
			{
				case 'delete':	// deletes an exercise
					$objExerciseTmp->delete();
					break;
                                case 'purge':	// purge exercise results
					$objExerciseTmp->purge();
					break;
				case 'enable':  // enables an exercise
					$objExerciseTmp->enable();
					$objExerciseTmp->save();
					break;
				case 'disable': // disables an exercise
					$objExerciseTmp->disable();
					$objExerciseTmp->save();
					break;
                                case 'public':  // make exercise public
					$objExerciseTmp->makepublic();
					$objExerciseTmp->save();                                        
					break;
                                case 'limited':  // make exercise limited
					$objExerciseTmp->makelimited();
					$objExerciseTmp->save();                                        
					break;
			}
		}
		// destruction of Exercise
		unset($objExerciseTmp);
	}
	$sql="SELECT id, titre, description, type, active, public FROM `$TBL_EXERCICES` ORDER BY id LIMIT $from, $limitExPage";
	$result = db_query($sql, $currentCourseID);
	$qnum = db_query("SELECT COUNT(*) FROM `$TBL_EXERCICES`");
} else {
        // only for students
	$sql = "SELECT id, titre, description, type, active, public, StartDate, EndDate, TimeConstrain, AttemptsAllowed, score ".
		"FROM `$TBL_EXERCICES` WHERE active='1' ORDER BY id LIMIT $from, $limitExPage";
	$result = db_query($sql);
	$qnum = db_query("SELECT COUNT(*) FROM `$TBL_EXERCICES` WHERE active = 1");
}

list($num_of_ex) = mysql_fetch_array($qnum);
$nbrExercises = mysql_num_rows($result);

if($is_editor) {
	$tool_content .= "
    <div id='operations_container'>
      <ul id='opslist'>
        <li><a href='admin.php?course=$code_cours&amp;NewExercise=Yes'>$langNewEx</a></li>
        <li><a href='question_pool.php?course=$code_cours'>$langQuestionPool</a></li>
      </ul>
    </div>";
} else  {
	$tool_content .= "";
}

if(!$nbrExercises) {
    $tool_content .= "<p class='alert1'>$langNoEx</p>";
} else {
	$maxpage = 1 + intval($num_of_ex / $limitExPage);
	if ($maxpage > 0) {
		$prevpage = $page - 1;
		$nextpage = $page + 1;
		if ($prevpage >= 0) {
			$tool_content .= "<a href='$_SERVER[SCRIPT_NAME]?course=$code_cours&amp;page=$prevpage'>&lt;&lt; $langPreviousPage</a>&nbsp;";
		}
		if ($nextpage < $maxpage) { 
			$tool_content .= "<a href='$_SERVER[SCRIPT_NAME]?course=$code_cours&amp;page=$nextpage'>$langNextPage &gt;&gt;</a>";
		}
	}

	$tool_content .= "
	    <table width='100%' class='tbl_alt'>
	    <tr>";
	
	// shows the title bar only for the administrator
	if($is_editor) {
		$tool_content .= "
	      <th width='500' colspan='2'><div class='left'>$langExerciseName</div></th>
	      <th class='center'>$langResults</th>
	      <th class='center'>$langCommands&nbsp;</th>
	    </tr>";
	} else { // student view
		$tool_content .= "
	      <th colspan='2'>$langExerciseName</th>
	      <th width='110' class='center'>$langExerciseStart / $langExerciseEnd</th>
	      <th width='70' class='center'>$langExerciseConstrain</th>
	      <th width='70' class='center'>$langExerciseAttemptsAllowed</th>
              <th width='70' class='center'>$langResults</th>
	    </tr>";
	}
	$tool_content .= "<tbody>";
	// while list exercises
	$k = 0;
	while($row = mysql_fetch_array($result)) {
		if($is_editor) {
			if(!$row['active']) {
				$tool_content .= "<tr class='invisible'>";
			} else {
				if ($k%2 == 0) {
					$tool_content .= "<tr class='even'>";
				} else {
					$tool_content .= "<tr class='odd'>";
				}
			}
		} else {
			if ($k%2 == 0) {
				$tool_content .= "<tr class='even'>";
			} else {
				$tool_content .= "<tr class='odd'>";
			}
		}
		
		$row['description'] = standard_text_escape($row['description']);
	
		// prof only
		if($is_editor) {
			if (!empty($row['description'])) {
				$descr = "<br/>$row[description]";
			} else {
				$descr = '';
			}
			$tool_content .= "<td width='16'>
				<img src='$themeimg/arrow.png' alt='' /></td>
				<td><a href=\"exercice_submit.php?course=$code_cours&amp;exerciseId=${row['id']}\">". q($row['titre']) ."</a>$descr</td>";
			$eid = $row['id'];
			$NumOfResults = mysql_fetch_array(db_query("SELECT COUNT(*) FROM `$TBL_RECORDS` 
				WHERE eid='$eid'", $currentCourseID));
	
			if ($NumOfResults[0]) {
				$tool_content .= "<td align='center'><a href='results.php?course=$code_cours&amp;exerciseId=".$row['id']."'>".
				$langExerciseScores1."</a> | 
				<a href='csv.php?course=$code_cours&amp;exerciseId=".$row['id']."' target=_blank>".$langExerciseScores3."</a></td>";
			} else {
				$tool_content .= "<td align='center'>	-&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp;- </td>";
			}
			$langModify_temp = htmlspecialchars($langModify);
			$langConfirmYourChoice_temp = addslashes(htmlspecialchars($langConfirmYourChoice));
			$langDelete_temp = htmlspecialchars($langDelete);
			$tool_content .= "<td align = 'right'>
                                <a href='admin.php?course=$code_cours&amp;exerciseId=$row[id]'>
                                  <img src='$themeimg/edit.png' alt='".q($langModify_temp)."' title='".q($langModify_temp)."'></a>
				<a href='$_SERVER[SCRIPT_NAME]?course=$code_cours&amp;choice=delete&amp;exerciseId=$row[id]' onClick=\"return confirmation('delete');\">
                                        <img src='$themeimg/delete.png' alt='".q($langDelete_temp)."' title='".q($langDelete_temp)."'></a>&nbsp;
                                <a href='$_SERVER[SCRIPT_NAME]?course=$code_cours&amp;choice=purge&amp;exerciseId=$row[id]' onClick=\"return confirmation('purge');\">
                                        <img src='$themeimg/clear.png' alt='".q($langPurgeExercises)."' title='".q($langPurgeExercises)."'></a>&nbsp;";
		
			// if active
			if($row['active']) {
				if (isset($page)) {
					$tool_content .= "<a href=\"$_SERVER[SCRIPT_NAME]?course=$code_cours&amp;choice=disable&amp;page=${page}&amp;exerciseId=".$row['id']."\"><img src='$themeimg/visible.png' alt='".q($langVisible)."' title='".q($langVisible)."' /></a>&nbsp;";
				} else {
					$tool_content .= "<a href='$_SERVER[SCRIPT_NAME]?course=$code_cours&amp;choice=disable&amp;exerciseId=".$row['id']."'><img src='$themeimg/visible.png' alt='".q($langVisible)."' title='".q($langVisible)."'></a>&nbsp;";
				}
			} else { // else if not active
				if (isset($page)) {
					$tool_content .= "<a href='$_SERVER[SCRIPT_NAME]?course=$code_cours&amp;choice=enable&amp;page=${page}&amp;exerciseId=".$row['id']."'>
					<img src='$themeimg/invisible.png' alt='".q($langVisible)."' title='".q($langVisible)."' /></a>&nbsp;";
				} else {
					$tool_content .= "<a href='$_SERVER[SCRIPT_NAME]?course=$code_cours&amp;choice=enable&amp;exerciseId=".$row['id']."'>
					<img src='$themeimg/invisible.png' alt='".q($langVisible)."' title='".q($langVisible)."' /></a>&nbsp;";
				}
			}
            if (course_status($cours_id) == COURSE_OPEN) {
            	if ($row['public']) {
                	$tool_content .= "<a href='$_SERVER[SCRIPT_NAME]?course=$code_cours&amp;choice=limited&amp;exerciseId=".$row['id']."'>" .
                    					"<img src='$themeimg/access_public.png' " .
                                        "title='".q($langResourceAccess)."' alt='".q($langResourceAccess)."' /></a>";
                } else {
                	$tool_content .= "<a href='$_SERVER[SCRIPT_NAME]?course=$code_cours&amp;choice=public&amp;exerciseId=".$row['id']."'>" .
                    					"<img src='$themeimg/access_limited.png' " .
                                        "title='".q($langResourceAccess)."' alt='".q($langResourceAccess)."' /></a>";
				}
				$tool_content .= "&nbsp;";
			}
			$tool_content .= "</td></tr>";
		}
		// student only
		else {
        	if (!resource_access($row['active'], $row['public'])) {
            	continue;
			}
			$CurrentDate = date("Y-m-d H:i");
            $temp_StartDate = mktime(substr($row['StartDate'], 11, 2), substr($row['StartDate'], 14, 2), 0, substr($row['StartDate'], 5, 2), substr($row['StartDate'], 8, 2), substr($row['StartDate'], 0, 4));
            $temp_EndDate = mktime(substr($row['EndDate'], 11, 2), substr($row['EndDate'], 14, 2), 0, substr($row['EndDate'], 5, 2), substr($row['EndDate'], 8, 2), substr($row['EndDate'], 0, 4));
            $CurrentDate = mktime(substr($CurrentDate, 11, 2), substr($CurrentDate, 14, 2), 0, substr($CurrentDate, 5, 2), substr($CurrentDate, 8, 2), substr($CurrentDate, 0, 4));
            if (($CurrentDate >= $temp_StartDate) && ($CurrentDate <= $temp_EndDate)) { // exercise is ok
                $tool_content .= "<td width='16'><img src='$themeimg/arrow.png' alt='' /></td>
                                  <td><a href=\"exercice_submit.php?course=$code_cours&amp;exerciseId=".$row['id']."\">". q($row['titre']) ."</a>";
            } elseif ($CurrentDate <= $temp_StartDate) { // exercise has not yet started
                $tool_content .= "<td width='16'><img src='$themeimg/arrow.png' alt='' /></td>
                                  <td class='invisible'>". q($row['titre']) ."&nbsp;&nbsp;";
            } else { // exercise has expired
                $tool_content .= "<td width='16'>
                                <img src='$themeimg/arrow.png' alt='' />
                                </td><td>". q($row['titre']) ."&nbsp;&nbsp;(<font color='red'>$m[expired]</font>)";
            }
            $tool_content .= "<br />$row[description]</td><td class='smaller' align='center'>
                             ".nice_format(date("Y-m-d H:i", strtotime($row['StartDate'])), true)." / 
                             ".nice_format(date("Y-m-d H:i", strtotime($row['EndDate'])), true)."</td>";
			// how many attempts we have.
            $CurrentAttempt = mysql_fetch_array(db_query("SELECT COUNT(*) FROM `$TBL_RECORDS`
            												WHERE eid='$row[id]' AND uid='$uid'", $currentCourseID));
            if ($row['TimeConstrain'] > 0) {
            	$tool_content .= "<td align='center'>";

                // if there is an active attempt
            	$sql = "SELECT COUNT(*), RecordStartDate FROM `$TBL_RECORDS` WHERE eid='{$row['id']}' AND uid='$uid' AND (RecordEndDate is NULL OR RecordEndDate = 0) ";
            	$tmp = mysql_fetch_row(db_query($sql, $currentCourseID));
            	if ($tmp[0] > 0) {
            		$RecordStartDate = strtotime($tmp[1]);
            		$temp_CurrentDate = time();
            		// if exerciseTimeConstrain has not passed yet calculate the remaining time
            		if ($RecordStartDate + ($row['TimeConstrain']*60) >= $temp_CurrentDate) {
            			$_SESSION['exercise_begin_time'][$row['id']] = $RecordStartDate;
            			$timeleft = ($row['TimeConstrain']*60) - ($temp_CurrentDate - $RecordStartDate);
            			$passed = false;
            		} else {
            			$timeleft = "{$row['TimeConstrain']} $langExerciseConstrainUnit";
            			$passed = true;
            		}
            		$tool_content .= "<span id=\"progresstime\">$timeleft</span></td>";
            		$exerciseId = $row['id'];
            		if($passed){
            		    unset($timeleft);
            		}
            	} else {
                	$tool_content .= "{$row['TimeConstrain']} $langExerciseConstrainUnit</td>";
            	}
            } else {
                $tool_content .= "<td align='center'> - </td>";
            }
            if ($row['AttemptsAllowed'] > 0) {
                $tool_content .= "<td align='center'>$CurrentAttempt[0]/$row[AttemptsAllowed]</td>";
            } else {
                $tool_content .= "<td align='center'> - </td>";
            }
            // user last exercise score
            if ($row['score']) {
                $r = mysql_fetch_array(db_query("SELECT TotalScore, TotalWeighting 
                                    FROM `$TBL_RECORDS` WHERE uid=$uid 
                                    AND eid=$row[id] 
                                    ORDER BY eurid DESC LIMIT 1", $currentCourseID));
                if (empty($r)) {
                    $tool_content .= "<td align='center'>&dash;</td>";
                } else {
                    $tool_content .= "<td align='center'>$r[TotalScore]/$r[TotalWeighting]</td>";
                }
            } else {
                $tool_content .= "<td align='center'>$langNotAvailable</td>";
            }
            $tool_content .= "</tr>";
		}
		// skips the last exercise, that is only used to know if we have or not to create a link "Next page"
		if ($k+1 == $limitExPage) {
			break;
		}
		$k++;
	}	// end while()
	$tool_content .= "</table>";
}
add_units_navigation(TRUE);
//if there is an active attempt, countdown leftime
if(isset($timeleft) and $timeleft > 0) {    
	load_js('tools.js');
	$head_content .= "<script type='text/javascript'>";
	// If not editor, enable countdown mechanism
	if (!$is_editor) {
		$head_content .= "
			$(document).ready(function(){
				timer = $('#progresstime');
				timer.time = timer.text();
				timer.text(secondsToHms(timer.time--));
			    countdown(timer, function() {
			        alert('$langExerciseEndTime');
			        url = \"exercise_redirect.php?course=$code_cours&exerciseId=$exerciseId&error=langExerciseExpiredTime\";
			        $(location).attr('href',url);
			    });
			});";
	}
	
	$head_content .="$(exercise_enter_handler);</script>";
}
draw($tool_content, 2, null, $head_content);

