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

/*
 * Password reset component
 * 
 * @author Evelthon Prodromou <eprodromou@upnet.gr>
 * @version $Id$
 * 
 * @abstract This component resets the user's password after verifying 
 * his/hers  information through a challenge/response system.
 *
 */


include '../../include/baseTheme.php';
include 'auth.inc.php';
include('../../include/sendMail.inc.php');
require_once '../../include/phpass/PasswordHash.php';
$nameTools = $lang_remind_pass;

// javascript
load_js('jquery');
load_js('pwstrength.js');
$head_content .= <<<hContent
<script type="text/javascript">
/* <![CDATA[ */

    var lang = {
hContent;
$head_content .= "pwStrengthTooShort: '". js_escape($langPwStrengthTooShort) ."', ";
$head_content .= "pwStrengthWeak: '". js_escape($langPwStrengthWeak) ."', ";
$head_content .= "pwStrengthGood: '". js_escape($langPwStrengthGood) ."', ";
$head_content .= "pwStrengthStrong: '". js_escape($langPwStrengthStrong) ."'";
$head_content .= <<<hContent
    };

    $(document).ready(function() {
        $('#password').keyup(function() {
            $('#result').html(checkStrength($('#password').val()))
        });
    });

/* ]]> */
</script>
hContent;

// Password reset link is valid for 1 hour = 3600 sec
define('TOKEN_VALID_TIME', 3600);

$homelink = "<br><p><a href='$urlAppend'>$langHome</a></p>\n";

function check_password_editable($password)
{
	$authmethods = array("pop3","imap","ldap","db","shibboleth","cas");
	if(in_array($password,$authmethods))
	{
		return false; // it is not editable, because it belongs in external auth method
	}
	else
	{
		return true; // is editable
	}
}

if (isset($_REQUEST['u']) and
    isset($_REQUEST['h'])) {
        $change_ok = false;
	$userUID = intval($_REQUEST['u']);
        $valid = token_validate('password'.$userUID, $_REQUEST['h'], TOKEN_VALID_TIME);
	$res = db_query("SELECT user_id FROM user
                                WHERE user_id = $userUID AND
                                      password NOT IN ('" .
                                      implode("', '", $auth_ids) . "')");
	if ($valid and mysql_num_rows($res) == 1) {
                if (isset($_POST['newpass']) and isset($_POST['newpass1']) and
                    $_POST['newpass'] != '' and
                    $_POST['newpass'] == $_POST['newpass1']) {
                            $hasher = new PasswordHash(8, false);
                            if (db_query("UPDATE user SET `password` = ". quote($hasher->HashPassword($_POST['newpass'])) ."
                                                      WHERE user_id = $userUID")) {
                                      $tool_content = "<div class='success'><p>$langAccountResetSuccess1</p></div>
                                                       $homelink";
                                      $change_ok = true;
                        }
                } elseif (isset($_POST['newpass'])) {
                        $tool_content .= "<p class='alert1'>$langPassTwo</p>";
                }
		if (!$change_ok) {
                        $tool_content .= "
        <form method='post' action='$_SERVER[SCRIPT_NAME]'>
        <input type='hidden' name='u' value='$userUID'>
        <input type='hidden' name='h' value='".q($_REQUEST['h'])."'>
        <fieldset>
        <legend>$langPassword</legend>
        <table class='tbl'>
        <tr>
           <th>$langNewPass1</th>
           <td><input type='password' size='40' name='newpass' value='' id='password' autocomplete='off'/>&nbsp;<span id='result'></span></td>
        </tr>
        <tr>
           <th>$langNewPass2</th>
           <td><input type='password' size='40' name='newpass1' value='' autocomplete='off'></td>
        </tr>
        <tr>
           <th>&nbsp;</th>
           <td><input type='submit' name='submit' value='".q($langModify)."'></td>
        </tr>
        </table>
        </fieldset>
        </form>";
		}
	} else {
		$tool_content = "<div class='caution'>$langAccountResetInvalidLink</div>
                                 $homelink";
	}
} elseif (isset($_POST['send_link'])) {

	$email = isset($_POST['email'])? mb_strtolower(trim($_POST['email'])): '';
	$userName = isset($_POST['userName'])? canonicalize_whitespace($_POST['userName']): '';
	/***** If valid e-mail address was entered, find user and send email *****/
	$res = db_query("SELECT u.user_id, u.nom, u.prenom, u.username, u.password, u.statut FROM user u
	                LEFT JOIN admin a ON (a.idUser = u.user_id)
	                WHERE u.email = " . quote($email) . " AND
	                BINARY u.username = " . quote($userName) ." AND 
	                a.idUser IS NULL AND  
	                (u.last_passreminder IS NULL OR DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 1 HOUR) >= u.last_passreminder)"); //exclude admins and currently pending requests

        $found_editable_password = false;
	if (mysql_num_rows($res) == 1) {
		$text = $langPassResetIntro. $emailhelpdesk;
		$text .= $langHowToResetTitle;
		while ($s = mysql_fetch_assoc($res)) {
			if (check_password_editable($s['password'])) {
                                $found_editable_password = true;
				//prepare instruction for password reset
				$text .= $langPassResetGoHere;
                                $text .= $urlServer . "modules/auth/lostpass.php?u=$s[user_id]&h=" .
                                         token_generate('password'.$s['user_id'], true);
                // store the timestamp of this action (password reminding and token generation)
                db_query("UPDATE user SET last_passreminder = CURRENT_TIMESTAMP WHERE user_id = ". $s['user_id']);

			} else { //other type of auth...
                                $auth = array_search($s['password'], $auth_ids) or 1;
				$tool_content = "<div class='caution'>
				    <p><strong>$langPassCannotChange1</strong></p>
                                    <p>$langPassCannotChange2 ".get_auth_info($auth).
                                    ". $langPassCannotChange3 <a href='mailto:$emailhelpdesk'>$emailhelpdesk</a> $langPassCannotChange4</p>
                                    $homelink</div>";
			}
		}

                /***** Account details found, now send e-mail *****/
                if ($found_editable_password) {
                        $emailsubject = $lang_remind_pass;
                        if (!send_mail('', '', '', $email, $emailsubject, $text, $charset)) {
                                $tool_content = "<div class='caution'>
                                <p><strong>$langAccountEmailError1</strong></p>
                                <p>$langAccountEmailError2 $email.</p>
                                <p>$langAccountEmailError3 <a href='mailto:$emailhelpdesk'>$emailhelpdesk</a>.</p></div>
                                $homelink";
                        } elseif (!isset($auth)) {
                                $tool_content .= "<div class='success'>$lang_pass_email_ok <strong>".
                                        q($email)."</strong></div>$homelink";
                        }
                }
        } else {
		$tool_content .= "<div class='caution'>
		    <p><strong>$langAccountNotFound1 (".q("$userName / $email").")</strong></p>
		    <p>$langAccountNotFound2 <a href='mailto:$emailhelpdesk'>$emailhelpdesk</a>, $langAccountNotFound3</p></div>
		    $homelink";
        }
} else {
	/***** Email address entry form *****/
	$tool_content .= "<div class='info'>$lang_pass_intro</div><br>";
	$tool_content .= "<form method='post' action='$_SERVER[SCRIPT_NAME]'>
        <fieldset>
          <legend>$langUserData</legend>
	  <table class='tbl' width='100%'>
	  <tr>
            <th width='100'>$lang_username:</th>
	    <td><input type='text' name='userName' size='40' autocomplete='off'></td>
          </tr>
	  <tr>
	    <th>$lang_email: </th>
	    <td><input type='text' name='email' size='40' autocomplete='off'></td>
          </tr>
          <tr>
            <td>&nbsp;</td>
            <td class='right'><input type='submit' name='send_link' value='".q($lang_pass_submit)."'></td>
          </tr>
	  </table>
        </fieldset>
	</form>";
}

draw($tool_content, 0, null, $head_content);
