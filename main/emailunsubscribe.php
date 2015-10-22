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

$path2add = 2;
$require_login = true;

include '../include/baseTheme.php';
load_js('jquery');
load_js('tools.js');

$nameTools = $langEmailUnsubscribe;
$navigation[]= array("url"=>"profile.php", "name"=> $langModifyProfile);

// get user registered courses
$q = db_query("SELECT statut, cours_id FROM cours_user                 
                    WHERE user_id = $uid ORDER BY statut");
while ($r = mysql_fetch_array($q)) {
    $code = course_id_to_code($r['cours_id']);
    $status = $r['statut'];
    $_SESSION['course_status'][$code] = $status;
}

if (isset($_POST['submit'])) {     
        if (isset($_POST['unsub'])) {
                db_query("UPDATE user SET receive_mail = 1");
        }
        if (isset($_POST['cid'])) {  // change email subscription for one course                
                $cid = intval($_POST['cid']);
                if (isset($_POST['c_unsub'])) {
                        db_query("UPDATE cours_user SET receive_mail = 1
                                WHERE user_id = $uid AND cours_id = $cid");        
                } else {
                        db_query("UPDATE cours_user SET receive_mail = 0
                                WHERE user_id = $uid AND cours_id = $cid");        
                }                
                $course_title = course_id_to_title($cid);        
                $tool_content .= "<div class='success'>".q(sprintf($course_title, $langEmailUnsubSuccess))."</div>";
        } else { // change email subscription for all courses
                foreach ($_SESSION['course_status'] as $course_code => $c_value) {
                        if (@array_key_exists($course_code, $_POST['c_unsub'])) {                        
                                db_query("UPDATE cours_user SET receive_mail = 1
                                WHERE user_id = $uid AND cours_id = ". course_code_to_id($course_code));
                        } else {                        
                                 db_query("UPDATE cours_user SET receive_mail = 0
                                WHERE user_id = $uid AND cours_id = ". course_code_to_id($course_code));
                        }
                }
                $tool_content .= "<div class='success'>$langWikiEditionSucceed. <br />
                                <a href='profile.php'>$langBack</a></div>";
        }        
        
} else {
        $tool_content .= "<form action='$_SERVER[SCRIPT_NAME]' method='post'>";
        if (get_config('email_verification_required') && get_config('dont_mail_unverified_mails')) {
                $user_email_status = get_mail_ver_status($uid);
                if ($user_email_status == EMAIL_VERIFICATION_REQUIRED or
                    $user_email_status == EMAIL_UNVERIFIED) {
                        $link = "<a href = '../modules/auth/mail_verify_change.php?from_profile=TRUE'>$langHere</a>.";
                        $tool_content .= "<div class='alert1'>$langMailNotVerified $link</div>";
                }
        }
        if (!get_user_email_notification_from_courses($uid)) {
                $head_content .= '<script type="text/javascript">$(control_deactivate);</script>';
                $tool_content .= "<div class='info'>$langEmailUnsubscribeWarning</div>
                                  <input type='checkbox' id='unsub' name='unsub' value='1'>&nbsp;$langEmailFromCourses";
        }
        $tool_content .= "<div class='info'>$langInfoUnsubscribe</div>
                          <div id='unsubscontrols'>";
        if (isset($_POST['cid'])) { // one course only                
                $cid = intval($_POST['cid']);
                $course_title = course_id_to_title($cid);        
                $selected = get_user_email_notification($uid, $cid) ? 'checked': '';        
                $tool_content .= "<input type='checkbox' name='c_unsub' value='1' $selected>&nbsp;". q($course_title) ."<br />";
                $tool_content .= "<input type='hidden' name='cid' value='$cid'>";
        } else { // displays all courses
                foreach ($_SESSION['course_status'] as $course_code => $status) {
                        $course_title = course_code_to_title($course_code);
                        $cid = course_code_to_id($course_code);        
                        $selected = get_user_email_notification($uid, $cid) ? 'checked': '';        
                        $tool_content .= "<input type='checkbox' name='c_unsub[$course_code]' value='1' $selected>&nbsp;". q($course_title) ."<br />";
                }       
        }
        $tool_content .= "</div><br /><input type='submit' name='submit' value='".q($langSubmit)."'>";
        $tool_content .= "</form>";
}

draw($tool_content, 1, null, $head_content);
