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
 * ========================================================================

============================================================================
@Description: Main script for the work tool
============================================================================
*/


$require_current_course = true;
$require_login = true;
$require_help = true;
$helpTopic = 'Work';

include '../../include/baseTheme.php';
include '../../include/lib/forcedownload.php';

$head_content = "
<script type='text/javascript'>
function confirmation (name)
{
    if (name == 'purge'){
        if (confirm(\"$langWarnForSubmissions. $langDelSure\"))
            {return true;}
        else
            {return false;}
    } else {
        if (confirm(\"$langDelWarn1 \"+ name + \". $langWarnForSubmissions. $langDelSure \"))
            {return true;}
        else
            {return false;}    
    }
}

function confirm_delete_assignment ()
{
    if (confirm(\"$langDelWarnUserAssignment\"))
        {return true;}
    else
        {return false;}
}
</script>
";

$head_content .= <<<hContent
<script type="text/javascript">
function checkrequired(which, entry) {
	var pass=true;
	if (document.images) {
		for (i=0;i<which.length;i++) {
			var tempobj=which.elements[i];
			if (tempobj.name == entry) {
				if (tempobj.type=="text"&&tempobj.value=='') {
					pass=false;
					break;
		  		}
	  		}
		}
	}
	if (!pass) {
		alert("$langEmptyAsTitle");
		return false;
	} else {
		return true;
	}
}

</script>
hContent;

// For using with the pop-up calendar
include 'jscalendar.inc.php';
require_once '../../include/lib/modalboxhelper.class.php';
require_once '../../include/lib/multimediahelper.class.php';
ModalBoxHelper::loadModalBox();

/**** The following is added for statistics purposes ***/
include '../../include/action.php';
$action = new action();
$action->record('MODULE_ID_ASSIGN');
/**************************************/

include 'work_functions.php';
include '../group/group_functions.php';

$workPath = $webDir."courses/".$currentCourseID."/work";

if (isset($_GET['get'])) {
        if (!send_file(intval($_GET['get']))) {
                $tool_content .= "<p class='caution'>$langFileNotFound</p>";
        }
}

// Only course admins can download all assignments in a zip file
if ($is_editor) {
  if (isset($_GET['download'])) {
	include "../../include/pclzip/pclzip.lib.php";
        // Allow unlimited time for creating the archive
        @set_time_limit(0);
	download_assignments(intval($_GET['download']));
  }
}

$nameTools = $langWorks;
mysql_select_db($currentCourseID);

include '../../include/lib/fileUploadLib.inc.php';
include '../../include/lib/fileManageLib.inc.php';
include '../../include/sendMail.inc.php';

include '../../include/libchart/libchart.php';

//-------------------------------------------
// main program
//-------------------------------------------

$works_url = array('url' => "$_SERVER[SCRIPT_NAME]?course=$code_cours", 'name' => $langWorks);

if ($is_editor) {
        $email_notify = isset($_POST['email']) and $_POST['email'];
	if (isset($_POST['grade_comments'])) {
		$work_title = db_query_get_single_value("SELECT title FROM assignments WHERE id = " .
                                                        intval($_POST['assignment']), $currentCourseID);
		$nameTools = $work_title;
		$nameTools = $langWorks;
		$navigation[] = $works_url;
                submit_grade_comments(intval($_POST['assignment']), intval($_POST['submission']),
                                      $_POST['grade'], $_POST['comments'], $email_notify);
	} elseif (isset($_GET['add'])) {
		$nameTools = $langNewAssign;
		$navigation[] = $works_url;
		new_assignment();
	} elseif (isset($_POST['sid'])) {
		show_submission(intval($_POST['sid']));
	} elseif (isset($_POST['new_assign'])) {
                if (!add_assignment($_POST['title'], $_POST['desc'], $_POST['WorkEnd'], $_POST['group_submissions'])) {
                        $tool_content = "<p class='caution'>$langError</p>\n";
                }
		show_assignments();
        } elseif (isset($_GET['as_id'])) {
                $as_id = intval($_GET['as_id']);
                delete_user_assignment($as_id);
        } elseif (isset($_POST['grades'])) {
		$nameTools = $langWorks;
		$navigation[] = $works_url;
                submit_grades(intval($_POST['grades_id']), $_POST['grades'], $email_notify);
        } elseif (isset($_REQUEST['id'])) {
                $id = intval($_REQUEST['id']);
                $work_title = db_query_get_single_value("SELECT title FROM assignments
                                                                WHERE id = $id");
                $work_id_url = array('url' => "$_SERVER[SCRIPT_NAME]?course=$code_cours&amp;id=$id",
                                     'name' => $work_title);
                if (isset($_POST['on_behalf_of'])) {
                        if (isset($_POST['user_id'])) {
                                $user_id = intval($_POST['user_id']);
                        } else {
                                $user_id = $uid;
                        }
                        $nameTools = $langAddGrade;
                        $navigation[] = $works_url;
                        $navigation[] = $work_id_url;
                        submit_work($id, $user_id);
                } elseif (isset($_REQUEST['choice'])) {
                        $choice = $_REQUEST['choice'];
                        if ($choice == 'disable') {
                                db_query("UPDATE assignments SET active = 0 WHERE id = $id");
                                show_assignments($langAssignmentDeactivated);
                        } elseif ($choice == 'enable') {
                                db_query("UPDATE assignments SET active = 1 WHERE id = $id");
                                show_assignments($langAssignmentActivated);
                        } elseif ($choice == 'delete') {
                                die("invalid option");
                        } elseif ($choice == 'do_delete') {
                                $nameTools = $m['WorkDelete'];
                                $navigation[] = $works_url;
                                delete_assignment($id);
                        } elseif ($choice == 'do_purge') {
                                $nameTools = $m['WorkSubsDelete'];
                                $navigation[] = $works_url;
                                purge_assignment_subs($id);                                
                        } elseif ($choice == 'edit') {
                                $nameTools = $m['WorkEdit'];
                                $navigation[] = $works_url;
                                $navigation[] = $work_id_url;
                                show_edit_assignment($id);
                        } elseif ($choice == 'do_edit') {
                                $nameTools = $langWorks;
                                $navigation[] = $works_url;
                                $navigation[] = $work_id_url;
                                edit_assignment($id);
                        } elseif ($choice == 'add') {
                                $nameTools = $langAddGrade;
                                $navigation[] = $works_url;
                                $navigation[] = $work_id_url;
                                show_submission_form($id, groups_with_no_submissions($id), true);
                        } elseif ($choice == 'plain') {
                                show_plain_view($id);
                        }
                } else {
                        $nameTools = $work_title;
                        if (isset($_GET['disp_results'])) {
                                show_assignment($id, false, true);
                        } else {
                                show_assignment($id);
                        }
                }
	} else {
		$nameTools = $langWorks;
		show_assignments();
	}
} else {
        if (isset($_REQUEST['id'])) {
                $id = intval($_REQUEST['id']);
                if (isset($_POST['work_submit'])) {
                        $nameTools = $m['SubmissionStatusWorkInfo'];
                        $navigation[] = $works_url;
                        $navigation[] = array('url' => "$_SERVER[SCRIPT_NAME]?course=$code_cours&amp;id=$id", 'name' => $langWorks);
                        submit_work($id);
                } else {
                        $work_title = db_query_get_single_value("SELECT title FROM assignments WHERE id = $id", $currentCourseID);
                        $nameTools = $work_title;                        
                        show_student_assignment($id);
                }
        } else {
                show_student_assignments();
        }
}

add_units_navigation(TRUE);
draw($tool_content, 2, null, $head_content.$local_head);

//-------------------------------------
// end of main program
//-------------------------------------

// Show details of a student's submission to professor
function show_submission($sid)
{
        global $tool_content, $works_url, $langSubmissionDescr, $langNotice3, $code_cours,
               $nameTools, $navigation;

        $nameTools = $langWorks;
        $navigation[] = $works_url;
        
        if ($sub = mysql_fetch_array(db_query("SELECT * FROM assignment_submit WHERE id = $sid"))) {
                $tool_content .= "<p>$langSubmissionDescr".
                                 q(uid_to_name($sub['uid'])).
                                 $sub['submission_date'].
                                 "<a href='$GLOBALS[urlServer]$GLOBALS[currentCourseID]".
                                 "/work/$sub[file_path]'>".q($sub['file_name'])."</a>";
                if (!empty($sub['comments'])) {
                        $tool_content .=  " $langNotice3: ".q($sub[comments])."";
                }
                $tool_content .=  "</p>";
        } else {
                $tool_content .= "<p class='caution'>error - no such submission with id $sid</p>\n";
        }
}


// insert the assignment into the database
function add_assignment($title, $desc, $deadline, $group_submissions)
{
        global $tool_content, $workPath;

        $secret = uniqid('');
        if (@mkdir("$workPath/$secret",0777)) {
                db_query("INSERT INTO assignments
                        (title, description, comments, deadline, submission_date, secret_directory,
                        group_submissions) VALUES
                        (".autoquote($title).", ".autoquote(purify($desc)).", '', ".autoquote($deadline).", NOW(), '$secret',
                                ".autoquote($group_submissions).")");
                return true;
        } else {
                return false;
        }
}

function submit_work($id, $on_behalf_of=null)
{
        global $tool_content, $workPath, $uid, $cours_id, $works_url,
               $langUploadSuccess, $langBack, $langUploadError, $currentCourseID,
               $langExerciseNotPermit, $langUnwantedFiletype, $code_cours,
               $langOnBehalfOfUserComment, $langOnBehalfOfGroupComment,
               $navigation, $langNoAssign;

        if (isset($on_behalf_of)) {
                $user_id = $on_behalf_of;
        } else {
                $user_id = $uid;
        }
        $submit_ok = FALSE; // Default do not allow submission
        if (isset($uid) && $uid) { // check if logged-in
                if ($GLOBALS['statut'] == 10) { // user is guest
                        $submit_ok = FALSE;
                } else { // user NOT guest
                        if (isset($_SESSION['status']) && isset($_SESSION['status'][$_SESSION['dbname']])) {
                                // user is registered to this lesson
                                $res = db_query("SELECT CAST(UNIX_TIMESTAMP(deadline)-UNIX_TIMESTAMP(NOW()) AS SIGNED) AS time
                                        FROM assignments WHERE id = ". intval($id));
                                if ($res and mysql_num_rows($res) == 1) {
                                        $row = mysql_fetch_array($res);
                                        if ($row['time'] < 0 and !$on_behalf_of) {
                                                $submit_ok = false; // after assignment deadline
                                        } else {
                                                $submit_ok = true; // before deadline
                                        }
                                } else {
                                        $tool_content .= "<p class='caution'>$langNoAssign</p>";
                                }
                        } else {
                                //user NOT registered to this lesson
                                $submit_ok = FALSE;
                        }
                }
        } //checks for submission validity end here

        $res = db_query("SELECT title, group_submissions FROM assignments WHERE id = ". intval($id));
        $row = mysql_fetch_array($res);
        $group_sub = $row['group_submissions'];
        $navigation[] = $works_url;
        $navigation[] = array('url' => "$_SERVER[SCRIPT_NAME]?id=$id", 'name' => $row['title']);

        if ($submit_ok) {
                if ($group_sub) {
                        $group_id = isset($_POST['group_id'])? intval($_POST['group_id']): -1;
                        $gids = user_group_info($on_behalf_of? null: $user_id,
                                                $cours_id);
                        $local_name = isset($gids[$group_id])? greek_to_latin($gids[$group_id]): '';
                } else {
                        $group_id = 0;
                        $local_name = uid_to_name($user_id);
                        $am = mysql_fetch_array(db_query("SELECT am FROM user WHERE user_id = $user_id"));
                        if (!empty($am[0])) {
                                $local_name .= $am[0];
                        }
                        $local_name = greek_to_latin($local_name);
                }
                $local_name = replace_dangerous_char($local_name);
                if (isset($on_behalf_of) and
                    (!isset($_FILES) or !$_FILES['userfile']['size'])) {
                        $_FILES['userfile']['name'] = '';
                        $_FILES['userfile']['tmp_name'] = '';
                        $no_files = true;
                } else {
                        $no_files = false;
                }

                validateUploadedFile($_FILES['userfile']['name'], 2);
                
                if (preg_match('/\.(ade|adp|bas|bat|chm|cmd|com|cpl|crt|exe|hlp|hta|' .'inf|ins|isp|jse|lnk|mdb|mde|msc|msi|msp|mst|pcd|pif|reg|scr|sct|shs|' .'shb|url|vbe|vbs|wsc|wsf|wsh)$/', $_FILES['userfile']['name'])) {
                        $tool_content .= "<div class='caution'>$langUnwantedFiletype: ".q($_FILES['userfile']['name']).
                                "</p><p><a href='$_SERVER[SCRIPT_NAME]?course=$code_cours&amp;id=$id'>$langBack</a></p></div>";
                        return;
                }
                $secret = work_secret($id);
                $ext = get_file_extension($_FILES['userfile']['name']);
                $filename = "$secret/$local_name" . (empty($ext)? '': '.' . $ext);
                if (!isset($on_behalf_of)) {
                        $msg1 = delete_submissions_by_uid($user_id, -1, $id);
                        if ($group_sub) {
                                if (array_key_exists($group_id, $gids)) {
                                        $msg1 = delete_submissions_by_uid(-1, $group_id, $id);
                                }
                        }
                } else {
                        $msg1 = '';
                }
                if ($no_files or @move_uploaded_file($_FILES['userfile']['tmp_name'], "$workPath/$filename")) {
                        if ($no_files) {
                                $filename = '';
                        } else {
                                @chmod("$workPath/$filename", 0644);
                        }
                        $msg2 = $langUploadSuccess;
                        $submit_ip = quote($_SERVER['REMOTE_ADDR']);
                        if (isset($on_behalf_of)) {
                                if ($group_sub) {
                                        $auto_comments = sprintf($langOnBehalfOfGroupComment,
                                                                 uid_to_name($uid),
                                                                 $gids[$group_id]);
                                } else {
                                        $auto_comments = sprintf($langOnBehalfOfUserComment,
                                                                 uid_to_name($uid),
                                                                 uid_to_name($user_id));
                                }
                                $stud_comments = quote($auto_comments);
                                $grade_comments = autoquote($_POST['stud_comments']);
                                $grade = autoquote($_POST['grade']);
                                $grade_ip = $submit_ip;
                        } else {
                                $stud_comments = autoquote($_POST['stud_comments']);
                                $grade_comments = $grade = $grade_ip = "''";
                        }
                        if (!$group_sub or array_key_exists($group_id, $gids)) {
                                db_query("INSERT INTO assignment_submit
                                                 (uid, assignment_id, submission_date, submission_ip, file_path,
                                                  file_name, comments, grade, grade_comments, grade_submission_ip,
                                                  grade_submission_date, group_id)
                                          VALUES ($user_id, $id, NOW(), $submit_ip, " .  quote($filename) . ', ' .
                                                  autoquote($_FILES['userfile']['name']) .
                                                  ", $stud_comments, $grade, $grade_comments, $grade_ip, NOW(), $group_id)",
                                         $currentCourseID);
                                $sid = mysql_insert_id();
                                if ($on_behalf_of and isset($_POST['email'])) {
                                        $email_grade = autounquote($_POST['grade']);
                                        $email_comments = "\n$auto_comments\n\n" .
                                                          autounquote($_POST['stud_comments']);
                                        grade_email_notify($id, $sid, $email_grade, $email_comments);
                                }
                        }
                        $tool_content .= "<div class='success'><p>$msg2</p><p>$msg1</p><p><a href='$_SERVER[SCRIPT_NAME]?course=$code_cours&amp;id=$id'>$langBack</a></p></div>";
                } else {
                        $tool_content .= "<div class='caution'><p>$langUploadError</p><p><a href='$_SERVER[SCRIPT_NAME]?course=$code_cours'>$langBack</a></p></div>";
                }
        } else { // not submit_ok
                $tool_content .="<div class='caution'><p>$langExerciseNotPermit</p><p><a href='$_SERVER[SCRIPT_NAME]?course=$code_cours'>$langBack</a></p></div>";
        }
}


//  assignment - prof view only
function new_assignment()
{
	global $tool_content, $m, $langAdd, $code_cours;
	global $urlAppend;
	global $desc;
	global $end_cal_Work;
	global $langBack;

	$day	= date("d");
	$month	= date("m");
	$year	= date("Y");

	$tool_content .= "
        <form action='$_SERVER[SCRIPT_NAME]?course=$code_cours' method='post' onsubmit='return checkrequired(this, \"title\");'>
        <fieldset>
        <legend>$m[WorkInfo]</legend>
        <table class='tbl' width='100%'>
        <tr>
          <th>$m[title]:</th>
          <td><input type='text' name='title' size='55' /></td>
        </tr>
        <tr>
          <th>$m[description]:</th>
          <td>" . rich_text_editor('desc', 4, 20, $desc) . " </td>
        </tr>
        <tr>
          <th>$m[deadline]:</th>
          <td>$end_cal_Work</td>
        </tr>
        <tr>
          <th>$m[group_or_user]:</th>
          <td><input type='radio' id='user_button' name='group_submissions' value='0' checked='1' /><label for='user_button'>$m[user_work]</label>
          <br /><input type='radio' id='group_button' name='group_submissions' value='1' /><label for='group_button'>$m[group_work]</label></td>
        </tr>
        <tr>
          <th>&nbsp;</th>
          <td class='right'><input type='submit' name='new_assign' value='".q($langAdd)."' /></td>
        </tr>
        </table>
        </fieldset>
      </form>
      <br />";
  	$tool_content .= "<p align='right'><a href='$_SERVER[SCRIPT_NAME]?course=$code_cours'>$langBack</a></p>";
}


//form for editing
function show_edit_assignment($id)
{
	global $tool_content, $m, $langEdit, $langBack, $code_cours,
	       $urlAppend, $works_url, $end_cal_Work_db;

	$res = db_query("SELECT * FROM assignments WHERE id = ". intval($id));
	$row = mysql_fetch_array($res);

	$deadline = $row['deadline'];

        $textarea = rich_text_editor('desc', 4, 20, $row['description']);
	$tool_content .= "
        <form action='$_SERVER[SCRIPT_NAME]?course=$code_cours' method='post' onsubmit=\"return checkrequired(this, 'title');\">
        <input type='hidden' name='id' value='$id'>
        <input type='hidden' name='choice' value='do_edit'>
        <fieldset>
        <legend>$m[WorkInfo]</legend>
        <table class='tbl'>
        <tr>
          <th>$m[title]:</th>
          <td><input type='text' name='title' size='45' value='".q($row['title'])."'></td>
        </tr>
        <tr>
          <th valign='top'>$m[description]:</th>
          <td>$textarea</td>
        </tr>";
            $comments = trim($row['comments']);
            if (!empty($comments)) {
                    $tool_content .= "
        <tr>
          <th>$m[comments]:</th>
          <td>" . text_area('comments', 5, 65, $comments) .  "</td>
        </tr>";
        }

	if ($row['group_submissions'] == '0') {
                $group_checked_0 = ' checked';
                $group_checked_1 = '';
        } else {
                $group_checked_0 = '';
                $group_checked_1 = ' checked="1"';
        }
        $tool_content .= "
    <tr>
      <th valign='top'>$m[deadline]:</th>
      <td>" .  getJsDeadline($deadline) .  "</td>
    </tr>
    <tr>
      <th valign='top'>$m[group_or_user]:</th>
      <td><input type='radio' id='user_button' name='group_submissions' value='0'$group_checked_0 />
          <label for='user_button'>$m[user_work]</label><br />
          <input type='radio' id='group_button' name='group_submissions' value='1'$group_checked_1 />
          <label for='group_button'>$m[group_work]</label></td>
    </tr>
    <tr>
      <th>&nbsp;</th>
      <td><input type='submit' name='do_edit' value='".q($langEdit)."' /></td>
    </tr>
    </table>
    </fieldset>
    </form>";

    $tool_content .= "\n   <br /><div align='right'><a href='$_SERVER[SCRIPT_NAME]?course=$code_cours'>$langBack</a></div>";
}

// edit assignment
function edit_assignment($id)
{

        global $tool_content, $langBackAssignment, $langEditSuccess, $langEditError, $langEdit,
               $code_cours, $works_url, $navigation;

	$navigation[] = $works_url;
	$navigation[] = array('url' => "$_SERVER[SCRIPT_NAME]?id=$id", 'name' => $_POST['title']);

        if (!isset($_POST['comments'])) {
                $comments = "''";
        } else {
                $comments = autoquote(trim($_POST['comments']));
        }
	if (db_query("UPDATE assignments SET title=".autoquote(trim($_POST['title'])).",
		description=".autoquote(trim($_POST['desc'])).", group_submissions=".autoquote($_POST['group_submissions']).",
		comments=$comments, deadline=".autoquote($_POST['WorkEnd'])." WHERE id='$id'")) {

        $title = autounquote($_POST['title']);
	$tool_content .="\n  <p class='success'>$langEditSuccess<br /><a href='$_SERVER[SCRIPT_NAME]?course=$code_cours&amp;id=$id'>$langBackAssignment '".q($title)."'</a></p><br />";
	} else {
	$tool_content .="\n  <p class='caution'>$langEditError<br /><a href='$_SERVER[SCRIPT_NAME]?course=$code_cours&amp;id=$id'>$langBackAssignment '".q($title)."'</a></p><br />";
	}
}


/**
 * @brief delete assignment
 * @global string $tool_content
 * @global string $workPath
 * @global type $currentCourseID
 * @global type $webDir
 * @global type $langBack
 * @global type $langDeleted
 * @global type $code_cours
 * @param type $id
 */
function delete_assignment($id) {

	global $tool_content, $workPath, $currentCourseID, $webDir, $langBack, $langDeleted, $code_cours;

	$secret = work_secret($id);
	db_query("DELETE FROM assignments WHERE id='$id'");
	db_query("DELETE FROM assignment_submit WHERE assignment_id='$id'");
	move_dir("$workPath/$secret",
	"{$webDir}courses/garbage/${currentCourseID}_work_${id}_$secret");

	$tool_content .="<p class='success'>$langDeleted<br /><a href='$_SERVER[SCRIPT_NAME]?course=$code_cours'>".$langBack."</a></p>";
}

function purge_assignment_subs($id) {

	global $tool_content, $workPath, $currentCourseID, $webDir, $langBack, $langDeleted, $langAssignmentSubsDeleted, $code_cours;

	$secret = work_secret($id);
	db_query("DELETE FROM assignment_submit WHERE assignment_id='$id'");
	move_dir("$workPath/$secret",
	"{$webDir}courses/garbage/${currentCourseID}_work_${id}_$secret");

	$tool_content .="<p class='success'>$langAssignmentSubsDeleted<br /><a href='$_SERVER[SCRIPT_NAME]?course=$code_cours'>".$langBack."</a></p>";
}

function delete_user_assignment($id) 
{
        global $currentCourseID, $webDir, $langBack, $langDeleted, $tool_content;
        
        $filename = db_query_get_single_value("SELECT file_path FROM assignment_submit WHERE id = $id");        
        $file = $webDir."courses/".$currentCourseID."/work/".$filename;        
        if (my_delete($file)) {
                db_query("DELETE FROM assignment_submit WHERE id = $id");
                $tool_content .= "<p class='success'>$langDeleted<br />
                        <a href='$_SERVER[SCRIPT_NAME]?course=$currentCourseID'>".$langBack."</a></p>";
        }                
}


// show assignment - student
function show_student_assignment($id)
{
	global $tool_content, $m, $uid, $langSubmitted, $langSubmittedAndGraded, $langNotice3,
               $works_url, $langUserOnly, $langBack, $langWorkGrade, $langGradeComments,
               $currentCourseID, $cours_id, $code_cours, $navigation, $langNoAssign;

        $navigation[] = $works_url;
        $user_group_info = user_group_info($uid, $cours_id);
        $res = db_query("SELECT *, CAST(UNIX_TIMESTAMP(deadline)-UNIX_TIMESTAMP(NOW()) AS SIGNED) AS time
                                 FROM `$currentCourseID`.assignments
                                 WHERE id = ". intval($id));
        if ($res and mysql_num_rows($res) == 1) {
                $row = mysql_fetch_array($res);

                assignment_details($id, $row);

                $submit_ok = ($row['time'] > 0);

                if (!$uid) {
                        $tool_content .= "<p>$langUserOnly</p>";
                        $submit_ok = FALSE;
                } elseif ($GLOBALS['statut'] == 10) {
                        $tool_content .= "\n  <p class='alert1'>$m[noguest]</p>";
                        $submit_ok = FALSE;
                } else {
                        foreach (find_submissions($row['group_submissions'],
                                                  $uid, $id, $user_group_info) as $sub) {
                                  if ($sub['grade'] != '') {
                                          $submit_ok = false;
                                  }
                                  show_submission_details($sub['id']);
                        }
                }
                if ($submit_ok) {
                        show_submission_form($id, $user_group_info);
                }
                $tool_content .= "<br/>
                        <p align='right'><a href='$_SERVER[SCRIPT_NAME]?course=$code_cours'>$langBack</a></p>";
        } else {
                $tool_content .= "<p class='caution'>$langNoAssign</p>";
        }
}


function show_submission_form($id, $user_group_info, $on_behalf_of=false)
{
        global $tool_content, $m, $langWorkFile, $langSendFile, $langSubmit, $uid, $langNotice3, $gid, $is_member,
               $urlAppend, $langGroupSpaceLink, $langOnBehalfOf, $code_cours;

        $group_select_hidden_input = $group_select_form = '';
        $is_group_assignment = is_group_assignment($id);
        if ($is_group_assignment) {
                if (!$on_behalf_of) {
                        if (count($user_group_info) == 1) {
                                $gids = array_keys($user_group_info);
                                $group_link = $urlAppend . '/modules/group/document.php?gid=' . $gids[0];
                                $group_select_hidden_input = "<input type='hidden' name='group_id' value='$gids[0]' />";
                        } elseif ($user_group_info) {
                                $group_select_form = "<tr><th class='left'>$langGroupSpaceLink:</th><td>" .
                                                     selection($user_group_info, 'group_id') . "</td></tr>";
                        } else {
                                $group_link = $urlAppend . '/modules/group/group.php';
                                $tool_content .= "<p class='alert1'>$m[this_is_group_assignment] <br />" .
                                        sprintf(count($user_group_info)?
                                                $m['group_assignment_publish']:
                                                $m['group_assignment_no_groups'], $group_link) .
                                        "</p>\n";
                        }
                } else {
                        $group_select_form = "<tr><th class='left'>$langGroupSpaceLink:</th><td>" .
                                             selection(groups_with_no_submissions($id), 'group_id') . "</td></tr>";
                }
        } elseif ($on_behalf_of) {
                $group_select_form = "<tr><th class='left'>$langOnBehalfOf:</th><td>" .
                                     selection(users_with_no_submissions($id), 'user_id') . "</td></tr>";
        }
        $notice = $on_behalf_of? '': "<br />$langNotice3";
        $extra = $on_behalf_of? "<tr><th class='left'>$m[grade]</th>
                                     <td><input type='text' name='grade' maxlength='3' size='3'>
                                         <input type='hidden' name='on_behalf_of' value='1'></td></tr>
                                 <tr><th><label for='email_button'>$m[email_users]:</label></th>
                                     <td><input type='checkbox' value='1' id='email_button' name='email'></td></tr>": '';
        if (!$is_group_assignment or count($user_group_info) or $on_behalf_of) {
                $tool_content .= "
                     <form enctype='multipart/form-data' action='$_SERVER[SCRIPT_NAME]?course=$code_cours' method='post'>
                        <input type='hidden' name='id' value='$id' />$group_select_hidden_input
                        <fieldset>
                        <legend>$langSubmit</legend>
                        <table width='100%' class='tbl'>
                        $group_select_form
                        <tr>
                          <th class='left' width='150'>$langWorkFile:</th>
                          <td><input type='file' name='userfile' /></td>
                        </tr>
                        <tr>
                          <th class='left'>$m[comments]:</th>
                          <td><textarea name='stud_comments' rows='5' cols='55'></textarea></td>
                        </tr>
                        $extra
                        <tr>
                          <th>&nbsp;</th>
                          <td align='right'><input type='submit' value='".q($langSubmit)."' name='work_submit' />$notice</td>
                        </tr>
                        </table>
                        </fieldset>
                     </form>
                     <p align='right'><small>$GLOBALS[langMaxFileSize] " .
                                ini_get('upload_max_filesize') . "</small></p>";
        }
}


// Print a box with the details of an assignment
function assignment_details($id, $row, $message = null)
{
        global $tool_content, $is_editor, $code_cours, $themeimg, $m, $langDaysLeft,
               $langDays, $langWEndDeadline, $langNEndDeadLine, $langNEndDeadline,
               $langEndDeadline, $langDelAssign, $langAddGrade, $langZipDownload,
               $langSaved, $langGraphResults;

	if ($is_editor) {
            $tool_content .= "
            <div id='operations_container'>
              <ul id='opslist'>
              <li><a href='$_SERVER[SCRIPT_NAME]?course=$code_cours&amp;id=$id&amp;choice=do_delete' onClick='return confirmation(\"" .
                js_escape($row['title']) . "\");'>$langDelAssign</a></li>
                <li><a href='$_SERVER[SCRIPT_NAME]?course=$code_cours&amp;download=$id'>$langZipDownload</a></li>
		<li><a href='$_SERVER[SCRIPT_NAME]?course=$code_cours&amp;id=$id&amp;disp_results=true'>$langGraphResults</a></li>
		<li><a href='$_SERVER[SCRIPT_NAME]?course=$code_cours&amp;id=$id&amp;choice=add'>$langAddGrade</a></li>
              </ul>
            </div>";
	}

	if (isset($message)) {
		$tool_content .="
                <p class=\"success\">$langSaved</p>";
        }
	$tool_content .= "
        <fieldset>
        <legend>".$m['WorkInfo'];
        if ($is_editor) {
                $tool_content .= "&nbsp;
                 <a href='$_SERVER[SCRIPT_NAME]?course=$code_cours&amp;id=$id&amp;choice=edit'>
                 <img src='$themeimg/edit.png' alt='$m[edit]' />
                 </a>";
        }
        $tool_content .= "</legend>
        <table class='tbl'>
        <tr>
          <th width='150'>$m[title]:</th>
          <td>".q($row['title'])."</td>
        </tr>";
        $tool_content .= "
        <tr>
          <th valign='top'>$m[description]:</th>
          <td>$row[description]</td>
        </tr>";
	if (!empty($row['comments'])) {
		$tool_content .= "
                <tr>
                  <th class='left'>$m[comments]:</th>
                  <td>".nl2br(q($row['comments']))."</td>
                </tr>";
	}
	$tool_content .= "
        <tr>
          <th>$m[start_date]:</th>
          <td>".nice_format($row['submission_date'], true)."</td>
        </tr>
        <tr>
          <th valign='top'>$m[deadline]:</th>
          <td>".nice_format($row['deadline'], true)." <br />";

	if ($row['time'] > 0) {
		$tool_content .= "<span>($langDaysLeft ".format_time_duration($row['time']).")</span></td>
                </tr>";
	} else {
		$tool_content .= "<span class='expired'>$langEndDeadline</span></td>
                </tr>";
	}
	$tool_content .= "
        <tr>
          <th>$m[group_or_user]:</th>
          <td>";
	if ($row['group_submissions'] == '0') {
		$tool_content .= "$m[user_work]</td>
        </tr>";
	} else {
		$tool_content .= "$m[group_work]</td>
        </tr>";
	}
	$tool_content .= "
        </table>
        </fieldset>";
}

// Show a table header which is a link with the appropriate sorting
// parameters - $attrib should contain any extra attributes requered in
// the <th> tags
function sort_link($title, $opt, $attrib = '')
{
	global $tool_content, $code_cours;
	$i = '';
	if (isset($_REQUEST['id'])) {
		$i = "&amp;id=$_REQUEST[id]";
	}
	if (@($_REQUEST['sort'] == $opt)) {
		if (@($_REQUEST['rev'] == 1)) {
			$r = 0;
		} else {
			$r = 1;
		}
		$tool_content .= "
                  <th $attrib><a href='$_SERVER[SCRIPT_NAME]?course=$code_cours&amp;sort=$opt&amp;rev=$r$i'>" ."$title</a></th>";
	} else {
		$tool_content .= "
                  <th $attrib><a href='$_SERVER[SCRIPT_NAME]?course=$code_cours&amp;sort=$opt$i'>$title</a></th>";
	}
}


// show assignment - prof view only
// the optional message appears instead of assignment details
function show_assignment($id, $message = false, $display_graph_results = false)
{
        global $tool_content, $m, $langBack, $langNoSubmissions, $langSubmissions,
               $mysqlMainDb, $langEndDeadline, $langWEndDeadline, $langNEndDeadline,
               $langDays, $langDaysLeft, $langGradeOk, $currentCourseID, $webDir, $urlServer,
               $nameTools, $langGraphResults, $m, $code_cours, $themeimg, $works_url,
               $navigation;
        
        $res = db_query("SELECT *, CAST(UNIX_TIMESTAMP(deadline)-UNIX_TIMESTAMP(NOW()) AS SIGNED) AS time
                                 FROM assignments
                                 WHERE id = $id");
	$row = mysql_fetch_array($res);

	$navigation[] = $works_url;
	if ($message) {
		assignment_details($id, $row, $message);
	} else {
		assignment_details($id, $row);
	}

	$rev = (@($_REQUEST['rev'] == 1))? ' DESC': '';
	if (isset($_REQUEST['sort'])) {
		if ($_REQUEST['sort'] == 'am') {
			$order = 'am';
		} elseif ($_REQUEST['sort'] == 'date') {
			$order = 'submission_date';
		} elseif ($_REQUEST['sort'] == 'grade') {
			$order = 'grade';
		} elseif ($_REQUEST['sort'] == 'filename') {
			$order = 'file_name';
		} else {
			$order = 'nom';
		}
	} else {
		$order = 'nom';
	}

	$result = db_query("SELECT * FROM `$GLOBALS[code_cours]`.assignment_submit AS assign,
                                          `$mysqlMainDb`.user AS user
                                     WHERE assign.assignment_id = $id AND user.user_id = assign.uid
                                     ORDER BY $order $rev");

	// Used to display grades distribution chart
        list($graded_submissions_count) = mysql_fetch_row(
                                db_query("SELECT COUNT(*)
                                                 FROM `$GLOBALS[code_cours]`.assignment_submit AS assign,
                                                      `$mysqlMainDb`.user AS user
                                                 WHERE assign.assignment_id = $id AND
                                                       user.user_id = assign.uid AND
                                                       assign.grade <> ''"));

	$num_results = mysql_num_rows($result);
        if ($num_results > 0) {
                if ($num_results == 1) {
                        $num_of_submissions = $m['one_submission'];
                } else {
                        $num_of_submissions = sprintf("$m[more_submissions]", $num_results);
                }

                $gradeOccurances = array(); // Named array to hold grade occurances/stats
                $gradesExists = 0;
                while ($row = mysql_fetch_array($result)) {
                        $theGrade = $row['grade'];
                        if ($theGrade) {
                                $gradesExists = 1;
                                if (!isset($gradeOccurances[$theGrade])) {
                                        $gradeOccurances[$theGrade] = 1;
                                } else {
                                        if ($gradesExists) {
                                                ++$gradeOccurances[$theGrade];
                                        }
                                }
                        }
                }
                if (!$display_graph_results) {
                        $result = db_query("SELECT * FROM `$GLOBALS[code_cours]`.assignment_submit AS assign,
                                                          `$mysqlMainDb`.user AS user
                                                     WHERE assign.assignment_id='$id' AND user.user_id = assign.uid
                                                     ORDER BY $order $rev");

                        $tool_content .= "
                  <form action='$_SERVER[SCRIPT_NAME]?course=$code_cours' method='post'>
                    <input type='hidden' name='grades_id' value='$id' />
                    <div class='sub_title1'>$langSubmissions:</div>
                    <p>$num_of_submissions</p>
                    <table width='100%' class='sortable'>
                    <tr>
                      <th width='3'>&nbsp;</th>";
                        sort_link($m['username'], 'nom');
                        sort_link($m['am'], 'am');
                        sort_link($m['filename'], 'filename');
                        sort_link($m['sub_date'], 'date');
                        sort_link($m['grade'], 'grade');
                        $tool_content .= "</tr>";

                        $i = 1;
                        while ($row = mysql_fetch_array($result)) {
                                //is it a group assignment?
                                if (!empty($row['group_id'])) {
                                        $subContentGroup = "<div>$m[groupsubmit] ".
                                          "<a href='../group/group_space.php?course=$code_cours&amp;group_id=$row[group_id]'>".
                                          "$m[ofgroup] ".gid_to_name($row['group_id'])."</a></div>";
                                } else {
                                        $subContentGroup = '';
                                }

                                $uid_2_name = display_user($row['uid']);
                                $stud_am = mysql_fetch_array(db_query("SELECT am from `$mysqlMainDb`.user WHERE user_id = '$row[uid]'"));
                                if ($i%2 == 1) {
                                        $row_color = "class='even'";
                                } else {
                                        $row_color = "class='odd'";
                                }
                                $filelink = empty($row['file_name'])? '&nbsp;':
                                        ("<a href='$_SERVER[SCRIPT_NAME]?course=$code_cours&amp;get=$row[id]'>" .
                                         q($row['file_name']) . "</a>");
                                $tool_content .= "
                                <tr $row_color>
                                  <td align='right' width='4' rowspan='2' valign='top'>$i.</td>
                                  <td>${uid_2_name}</td>
                                  <td width='85'>" . q($stud_am[0]) . "</td>
                                  <td width='180'>$filelink&nbsp;&nbsp;
                                   <a href='$_SERVER[SCRIPT_NAME]?course=$code_cours&amp;as_id=$row[id]' onClick='return confirm_delete_assignment();'>
                                                  <img src='$themeimg/delete.png' title='$m[WorkDelete]' />
                                          </a>
                                  </td>
                                  <td width='100'>".nice_format($row['submission_date'], TRUE)."</td>
                                  <td width='5'>
                                     <div align='center'><input type='text' value='{$row['grade']}' maxlength='3' size='3' name='grades[{$row['id']}]'></div>
                                  </td>
                                </tr>
                                <tr $row_color>
                                <td colspan='5'>$subContentGroup";
                                if (trim($row['comments'] != '')) {
                                        $tool_content .= "<div style='margin-top: .5em;'><b>$m[comments]:</b> " .
                                                q($row['comments']) . '</div>';
                                }
                                //professor comments
                                $gradelink = "grade_edit.php?course=$code_cours&amp;assignment=$id&amp;submission=$row[id]";
                                if (trim($row['grade_comments'])) {
                                        $label = $m['gradecomments'] . ':';
                                        $icon = 'edit.png';
                                        $comments = "<div class='smaller'>".q($row['grade_comments'])."</div>";
                                } else {
                                        $label = $m['addgradecomments'];
                                        $icon = 'add.png';
                                        $comments = '';
                                }
                                if ($row['grade_comments'] or $row['grade'] != '') {
                                        $comments .= "<div class='smaller'><i>($m[grade_comment_date]: " .
                                                     nice_format($row['grade_submission_date']) . ")</i></div>";
                                }
                                $tool_content .= "<div style='padding-top: .5em;'><a href='$gradelink'><b>$label</b></a>
				  <a href='$gradelink'><img src='$themeimg/$icon' alt=''></a></div>
				  $comments
		    </td>
		  </tr>";
                                $i++;
                        } //END of While

                        $tool_content .= "</table>
                  <p class='smaller right'><img src='$themeimg/email.png' alt='' >
                        $m[email_users]: <input type='checkbox' value='1' name='email'></p>

		  <p><input type='submit' name='submit_grades' value='".q($langGradeOk)."'></p>
		  </form>";
                }

                if ($display_graph_results) { // display pie chart with grades results
                        if ($gradesExists) {
                                $chart = new PieChart(300, 200);
                                $dataSet = new XYDataSet();
                                $chart->setTitle("$langGraphResults");
                                foreach ($gradeOccurances as $gradeValue => $gradeOccurance) {
                                        $percentage = 100.0 * $gradeOccurance / $graded_submissions_count;
                                        $dataSet->addPoint(new Point("$gradeValue ($percentage)", $percentage));
                                }
                                $chart_path = 'courses/'.$currentCourseID.'/temp/chart_'.md5(serialize($chart)).'.png';
                                $chart->setDataSet($dataSet);
                                $chart->render($webDir.$chart_path);
                                $tool_content .= "
		  <table width='100%' class='tbl'>
		  <tr>
		    <td><img src='$urlServer$chart_path' /></td>
		  </tr>
		  </table>";
                        }
                }
        } else {
                $tool_content .= "
                      <p class='sub_title1'>$langSubmissions:</p>
                      <p class='alert1'>$langNoSubmissions</p>";
        }
	$tool_content .= "<br/>
                <p align='right'><a href='$_SERVER[SCRIPT_NAME]?course=$code_cours'>$langBack</a></p>";
}


// show all the assignments - student view only
function show_student_assignments()
{
        global $tool_content, $m, $uid, $cours_id, $currentCourseID,
               $langDaysLeft, $langDays, $langNoAssign, $urlServer,
               $code_cours, $themeimg;

        $gids = user_group_info($uid, $cours_id);

        $result = db_query("SELECT *, CAST(UNIX_TIMESTAMP(deadline)-UNIX_TIMESTAMP(NOW()) AS SIGNED) AS time
                                   FROM `$currentCourseID`.assignments
                                           WHERE active = '1'
                                           ORDER BY deadline");

        if (mysql_num_rows($result)) {
                $tool_content .= "<table class='tbl_alt' width='100%'>
                                  <tr>
                                      <th colspan='2'>$m[title]</th>
                                      <th class='center'>$m[deadline]</th>
                                      <th class='center'>$m[submitted]</th>
                                      <th>$m[grade]</th>
                                  </tr>";
                $k = 0;
                while ($row = mysql_fetch_array($result)) {
                        $title_temp = q($row['title']);
                        $class = $k%2? 'odd': 'even';
                        $tool_content .= "
                                <tr class='$class'>
                                    <td width='16'><img src='$themeimg/arrow.png' alt='' /></td>
                                    <td><a href='$_SERVER[SCRIPT_NAME]?course=$code_cours&amp;id=$row[id]'>$title_temp</a></td>
                                    <td width='150' align='center'>".nice_format($row['deadline'], TRUE);
                        if ($row['time'] > 0) {
                                $tool_content .= " (<span>$langDaysLeft".format_time_duration($row['time']).")</span>";
                        } else {
                                $tool_content .= " (<span class='expired'>$m[expired]</span>)";
                        }
                        $tool_content .= "</td><td width='170' align='center'>";

                        if ($submission = find_submissions(is_group_assignment($row['id']), $uid, $row['id'], $gids)) {
                            foreach ($submission as $sub) {
                                if (isset($sub['group_id'])) { // if is a group assignment
                                    $tool_content .= "<div style='padding-bottom: 5px;padding-top:5px;font-size:9px;'>($m[groupsubmit] ".
                                        "<a href='../group/group_space.php?course=$code_cours&amp;group_id=$sub[group_id]'>".
                                        "$m[ofgroup] ".gid_to_name($sub['group_id'])."</a>)</div>";
                                }
                                $tool_content .= "<img src='$themeimg/checkbox_on.png' alt='$m[yes]' /><br />";
                            }
                        } else {
                                $tool_content .= "<img src='$themeimg/checkbox_off.png' alt='$m[no]' />";
                        }
                        $tool_content .= "</td>
                                    <td width='30' align='center'>";
                        foreach ($submission as $sub) {
                            $grade = submission_grade($sub['id']);
                                if (!$grade) {
                                    $grade = "<div style='padding-bottom: 5px;padding-top:5px;'> - </div>";
                                }
                            $tool_content .= "<div style='padding-bottom: 5px;padding-top:5px;'>$grade</div>";
                        }
                        $tool_content .= "</td>
                                  </tr>";
                        $k++;
                }
                $tool_content .= '
                                  </table>';
        } else {
                $tool_content .= "<p class='alert1'>$langNoAssign</p>";
        }
}


// show all the assignments
function show_assignments($message = null)
{
        global $tool_content, $m, $langNoAssign, $langNewAssign, $langCommands,
               $code_cours, $themeimg, $m;

	$result = db_query("SELECT * FROM assignments ORDER BY deadline");

	if (isset($message)) {
		$tool_content .="<p class='success'>$message</p><br />";
	}

	$tool_content .="
            <div id='operations_container'>
              <ul id='opslist'>
                <li><a href='$_SERVER[SCRIPT_NAME]?course=$code_cours&amp;add=1'>$langNewAssign</a></li>
              </ul>
            </div>";

	if (mysql_num_rows($result)) {
		$tool_content .= "
                    <table width='100%' class='tbl_alt'>
                    <tr>
                      <th>$m[title]</th>
                      <th width='60'>$m[subm]</th>
                      <th width='60'>$m[nogr]</th>
                      <th width='130'>$m[deadline]</th>
                      <th width='80'>$langCommands</th>
                    </tr>";
                $index = 0;
		while ($row = mysql_fetch_array($result)) {
			// Check if assignement contains submissions
                        $AssignementId = $row['id'];

                        $num_submitted = db_query_get_single_value("SELECT COUNT(*) FROM assignment_submit
                                                WHERE assignment_id = $AssignementId");
                        if (!$num_submitted) {
                                $num_submitted = '&nbsp;';
                        }

                        $num_ungraded = db_query_get_single_value("SELECT COUNT(*) FROM assignment_submit
                                                WHERE assignment_id = $AssignementId AND grade=''");
                        if (!$num_ungraded) {
                                $num_ungraded = '&nbsp;';
                        }
			if(!$row['active']) {
				 $tool_content .= "\n<tr class = 'invisible'>";
			} else {
			  if ($index%2==0) {
				 $tool_content .= "\n<tr class='even'>";
			      } else {
				 $tool_content .= "\n<tr class='odd'>";
			  }
			}

			$tool_content .= "
			  <td><img src='$themeimg/arrow.png' alt=''>
			      <a href='$_SERVER[SCRIPT_NAME]?course=$code_cours&amp;id=${row['id']}' ";
			$tool_content .= ">";
			$tool_content .= $row_title = q($row['title']);
                        $tool_content .= "</a></td>
			  <td class='center'>$num_submitted</td>
			  <td class='center'>$num_ungraded</td>
			  <td class='center'>".nice_format($row['deadline'], true)."</td>
			  <td class='right'>
                            <a href='$_SERVER[SCRIPT_NAME]?course=$code_cours&amp;id=$row[id]&amp;choice=edit'>
                                <img src='$themeimg/edit.png' alt='$m[edit]' />
                            </a>";
                        if (is_numeric($num_submitted) && $num_submitted>0) {    
                            $tool_content .= "<a href='$_SERVER[SCRIPT_NAME]?course=$code_cours&amp;id=$row[id]&amp;choice=do_purge' onClick=\"return confirmation('purge');\">
                                <img src='$themeimg/clear.png' alt='".q($m['WorkSubsDelete'])."' title='".q($m['WorkSubsDelete'])."'>
                            </a>";
                        }
                        $tool_content .= "<a href='$_SERVER[SCRIPT_NAME]?course=$code_cours&amp;id=$row[id]&amp;choice=do_delete' onClick='return confirmation(\"".addslashes($row_title)."\");'>
                                <img src='$themeimg/delete.png' alt='$m[delete]' />
                            </a>";
			if ($row['active']) {
				$deactivate_temp = htmlspecialchars($m['deactivate']);
				$activate_temp = htmlspecialchars($m['activate']);
				$tool_content .= "<a href='$_SERVER[SCRIPT_NAME]?course=$code_cours&amp;choice=disable&amp;id=$row[id]'><img src='$themeimg/visible.png' title='$deactivate_temp' alt='$deactivate_temp'></a>";
			} else {
				$activate_temp = htmlspecialchars($m['activate']);
				$tool_content .= "<a href='$_SERVER[SCRIPT_NAME]?course=$code_cours&amp;choice=enable&amp;id=$row[id]'><img src='$themeimg/invisible.png' title='$activate_temp' alt='$activate_temp'></a>";
			}
			$tool_content .= "&nbsp;</td></tr>";
                        $index++;
                }
                $tool_content .= '</table>';
        } else {
                $tool_content .= "\n<p class='alert1'>$langNoAssign</p>";
        }
}


// submit grade and comment for a student submission
function submit_grade_comments($id, $sid, $grade, $comment, $email)
{
	global $tool_content, $langGrades, $langWorkWrongInput;

	$stupid_user = 0;

	/*  If check expression is changed by nikos, in order to give to teacher the ability to
	 * assign comments to a work without assigning grade. */
	if (!is_numeric($grade) && '' != $grade ) {
		$tool_content .= $langWorkWrongInput;
		$stupid_user = 1;
	} else {
                db_query("UPDATE assignment_submit
                                 SET grade = " . autoquote($grade) . ",
                                     grade_comments = " . autoquote($comment) . ",
                                     grade_submission_date = NOW(),
                                     grade_submission_ip = '$_SERVER[REMOTE_ADDR]'
                                 WHERE id = $sid");
                if ($email) {
                       grade_email_notify($id, $sid, $grade, $comment);
                }
	}
	if (!$stupid_user) {
		show_assignment($id, $langGrades);
	}
}


// submit grades to students
function submit_grades($grades_id, $grades, $email = false)
{
	global $tool_content, $langGrades, $langWorkWrongInput;

	$stupid_user = 0;

	foreach ($grades as $sid => $grade) {
                $sid = intval($sid);
		$val = mysql_fetch_row(db_query("SELECT grade from assignment_submit WHERE id = $sid"));
		if ($val[0] != $grade) {
			/*  If check expression is changed by nikos, in order to give to teacher
			 * the ability to assign comments to a work without assigning grade. */
			if (!is_numeric($grade) && '' != $grade) {
        			$stupid_user = 1;
                        }
		}
	}

	if (!$stupid_user) {
                foreach ($grades as $sid => $grade) {
                        $sid = intval($sid);
			$val = mysql_fetch_row(db_query("SELECT grade from assignment_submit WHERE id = $sid"));
			if ($val[0] != $grade) {
                                db_query("UPDATE assignment_submit
                                                 SET grade = " . autoquote($grade) . ",
                                                     grade_submission_date = NOW(),
                                                     grade_submission_ip = '$_SERVER[REMOTE_ADDR]'
                                                 WHERE id = $sid");
                                if ($email) {
                                        grade_email_notify($grades_id, $sid, $grade, '');
                                }
			}
		}
		show_assignment($grades_id, $langGrades);
	} else {
		$tool_content .= "<div class='alert1'>$langWorkWrongInput</div>";
	}
}

// functions for downloading
function send_file($id)
{
        global $tool_content, $currentCourseID, $uid, $is_editor;
        mysql_select_db($currentCourseID);
        $q = db_query("SELECT * FROM assignment_submit WHERE id = $id");
        if (!$q or !mysql_num_rows($q)) {
                return false;
        }
        $info = mysql_fetch_array($q);
        if ($info['group_id']) {
                initialize_group_info($info['group_id']);
        }
        if (!($is_editor or $info['uid'] == $uid or $GLOBALS['is_member'])) {
                return false;
        }
        send_file_to_client("$GLOBALS[workPath]/$info[file_path]", $info['file_name'], null, true);
        exit;
}


// Zip submissions to assignment $id and send it to user
function download_assignments($id)
{
	global $tool_content, $workPath;

	$secret = work_secret($id);
	$filename = "$GLOBALS[currentCourseID]_work_$id.zip";
	chdir($workPath);
	create_zip_index("$secret/index.html", $id);
	$zip = new PclZip($filename);
	$flag = $zip->create($secret, "work_$id", $secret);
	header("Content-Type: application/x-zip");
	header("Content-Disposition: attachment; filename=$filename");
        stop_output_buffering();
	@readfile($filename);
	@unlink($filename);
	exit;
}


// Create an index.html file for assignment $id listing user submissions
// Set $online to TRUE to get an online view (on the web) - else the
// index.html works for the zip file
function create_zip_index($path, $id, $online = FALSE)
{
	global $tool_content, $charset, $m;

	$fp = fopen($path, "w");
	if (!$fp) {
		die("Unable to create assignment index file - aborting");
	}
	fputs($fp, '
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset='.$charset.'">
                <style type="text/css">
                .sep td, th { border: 1px solid; }
                td { border: none; }
                table { border-collapse: collapse; border: 2px solid; }
                .sep { border-top: 2px solid black; }
                </style>
	</head>
	<body>
		<table width="95%" class="tbl">
			<tr>
				<th>'.$m['username'].'</th>
				<th>'.$m['am'].'</th>
				<th>'.$m['filename'].'</th>
				<th>'.$m['sub_date'].'</th>
				<th>'.$m['grade'].'</th>
			</tr>');

	$result = db_query("SELECT * FROM assignment_submit
		WHERE assignment_id='$id' ORDER BY id");


	while ($row = mysql_fetch_array($result)) {
		$filename = basename($row['file_path']);
                $filelink = empty($filename)? '&nbsp;':
                        ("<a href='$filename'>".htmlspecialchars($filename).'</a>');
		fputs($fp, '
			<tr class="sep">
				<td>'.q(uid_to_name($row['uid'])).'</td>
				<td>'.q(uid_to_am($row['uid'])).'</td>
				<td align="center">'.$filelink.'</td>
				<td align="center">'.$row['submission_date'].'</td>
				<td align="center">'.$row['grade'].'</td>
			</tr>');
		if (trim($row['comments'] != '')) {
			fputs($fp, "
			<tr><td colspan='6'><b>$m[comments]: ".
			"</b>".q($row['comments'])."</td></tr>");
		}
		if (trim($row['grade_comments'] != '')) {
			fputs($fp, "
			<tr><td colspan='6'><b>$m[gradecomments]: ".
			"</b>".q($row['grade_comments'])."</td></tr>");
		}
		if (!empty($row['group_id'])) {
			fputs($fp, "<tr><td colspan='6'>$m[groupsubmit] ".
                                   "$m[ofgroup] $row[group_id]</td></tr>\n");
		}
	}
	fputs($fp, ' </table></body></html>');
	fclose($fp);
}


// Show a simple html page with grades and submissions
function show_plain_view($id)
{
	global $tool_content, $workPath, $charset;
	$secret = work_secret($id);
	create_zip_index("$secret/index.html", $id, TRUE);
	header("Content-Type: text/html; charset=$charset");
	readfile("$workPath/$secret/index.html");
	exit;
}

// Notify students by email about grade/comment submission
// Send to single user for individual submissions or group members for group
// submissions
function grade_email_notify($assignment_id, $submission_id, $grade, $comments)
{
        global $m, $currentCourseName, $urlServer, $currentCourseID;
        static $title, $group;

        if (!isset($title)) {
                $res = db_query("SELECT title, group_submissions FROM assignments WHERE id = $assignment_id");
                list($title, $group) = mysql_fetch_row($res);
        }
        $res = db_query("SELECT uid, group_id, file_name, grade, grade_comments
                                FROM assignment_submit
                                WHERE id = $submission_id");
        $info = mysql_fetch_assoc($res);
        $subject = sprintf($m['work_email_subject'], $title);
        $body = sprintf($m['work_email_message'], $title, $currentCourseName) . "\n\n";
        if ($grade != '') {
                $body .= "$m[grade]: $grade\n";
        }
        if ($comments) {
                $body .= "$m[gradecomments]: $comments\n";
        }
        $body .= "\n$m[link_follows]\n{$urlServer}modules/work/work.php?course=$currentCourseID&amp;id=$assignment_id\n";
        if (!$group or !$info['group_id']) {
                send_mail_to_user_id($info['uid'], $subject, $body);
        } else {
                send_mail_to_group_id($info['group_id'], $subject, $body);
        }
}

function send_mail_to_group_id($gid, $subject, $body)
{
        global $mysqlMainDb, $charset;
        $res = db_query("SELECT nom, prenom, email
                                FROM `$mysqlMainDb`.user AS user,
                                     `$mysqlMainDb`.group_members AS members
                                WHERE members.group_id = $gid AND
                                      user.user_id = members.user_id");
        while ($info = mysql_fetch_assoc($res)) {
                send_mail('', '', "$info[prenom] $info[nom]", $info['email'],
                          $subject, $body, $charset);
        }
}

function send_mail_to_user_id($uid, $subject, $body)
{
        global $mysqlMainDb, $charset;
        list($nom, $prenom, $email) = mysql_fetch_row(db_query("SELECT nom, prenom, email
                FROM `$mysqlMainDb`.user WHERE user_id = $uid"));
        send_mail('', '', "$prenom $nom", $email, $subject, $body, $charset);
}

// Return a list of users with no submissions for assignment $id
function users_with_no_submissions($id)
{
        global $mysqlMainDb, $cours_id;

        $q = db_query("SELECT user.user_id, nom, prenom
                              FROM `$mysqlMainDb`.user,
                                   `$mysqlMainDb`.cours_user
                              WHERE user.user_id = cours_user.user_id AND
                                    cours_user.cours_id = $cours_id AND
                                    cours_user.statut = 5 AND
                                    user.user_id NOT IN (SELECT uid
                                                                FROM assignment_submit
                                                                WHERE assignment_id = $id)");
        $users = array();
        while ($row = mysql_fetch_row($q)) {
                $users[$row[0]] = "$row[1] $row[2]";
        }
        return $users;
}

// Return a list of groups with no submissions for assignment $id
function groups_with_no_submissions($id)
{
        global $currentCourseID, $cours_id;

        $groups = user_group_info(null, $cours_id);
        $q = db_query("SELECT group_id FROM `$currentCourseID`.assignment_submit
                              WHERE assignment_id = $id");
        while ($row = mysql_fetch_row($q)) {
                unset($groups[$row[0]]);
        }
        return $groups;
}
