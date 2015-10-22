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

/*
 * User mail verification
 * 
 * @author Kapetanakis Giannis <bilias@edu.physics.uoc.gr>
 * 
 * @abstract This component sends email verification code and can change user's email address
 *
 */

$require_login = true;
$require_valid_uid = true;
$mail_ver_excluded = true;
include '../../include/baseTheme.php';
include('../../include/sendMail.inc.php');
$nameTools = $langMailVerify;

$uid = (isset($_SESSION['uid']) && !empty($_SESSION['uid']))? $_SESSION['uid']: NULL;

if (empty($uid)) {
	$tool_content .= "<div class='caution'>$langMailVerificationError2</div> ";
	draw($tool_content,0);
	exit;
}

// user might already verified mail account or verification is no more needed
if (!get_config('email_verification_required') or 
     get_mail_ver_status($uid) == EMAIL_VERIFIED) {        
	if (isset($_SESSION['mail_verification_required'])) {
		unset($_SESSION['mail_verification_required']);
	}
	header("Location:" . $urlServer);
        exit;
}

if (!empty($_POST['submit'])) {
	if (!empty($_POST['email']) && email_seems_valid($_POST['email'])) {
		$email = $_POST['email'];
		// user put a new email address update db and session
		if ($email != $_SESSION['email']) {
			$_SESSION['email'] = $email;
			$qry = "UPDATE `user` set email=".autoquote($email). " WHERE user_id=$uid";
			db_query($qry);
		}
		//send new code
		$code_key = get_config('code_key');
		$hmac = hash_hmac('sha256', $_SESSION['uname'].$email.$uid, base64_decode($code_key));
		
		$subject = $langMailChangeVerificationSubject;
		$MailMessage = sprintf($mailbody1.$langMailVerificationChangeBody, $urlServer.'modules/auth/mail_verify.php?ver='.$hmac.'&id='.$uid);
		if (!send_mail($siteName, $emailAdministrator, '', $email, $subject, $MailMessage, $charset, "Reply-To: $emailhelpdesk")) {
			$mail_ver_error = sprintf("<p class='alert1'>".$langMailVerificationError,$email,$urlServer."auth/registration.php",
				"<a href='mailto:$emailhelpdesk' class='mainpage'>$emailhelpdesk</a>.</p>");
			$tool_content .= $mail_ver_error;
		}
		else {
			$tool_content .= "<div class='success'>$langMailVerificationSuccess4</div> ";
		}
	}
	// email wrong or empty
	else {
		$tool_content .= "<div class='caution'>$langMailVerificationWrong</div> ";
	}
}
elseif (!empty($_SESSION['mail_verification_required']) && ($_SESSION['mail_verification_required']===1) ) {
	$tool_content .= "<div class='info'>$langMailVerificationReq</div> ";
}
	
$tool_content .= "<br /><br /><form method='post' action='$_SERVER[SCRIPT_NAME]'>
        <fieldset>
                <legend>$langUserData</legend>
                <table class='tbl' with='100%'>
                <br />
                <tr>
                        <th class='left'>$lang_email:</th>
                        <td><input type='text' name='email' size='30' maxlength='40' value='". q($_SESSION['email']) ."' /></td>
                        <td><small>($langMailVerificationAddrChange)</small></td>
                </tr>
                <tr>
                        <th class='left'>&nbsp;</th>
                        <td colspan='2'><input type='submit' name='submit' value='".q($langMailVerificationNewCode)."' /></td>
                </tr>
                </table>
                <br />
        </fieldset>
</form>";

if (isset($_GET['from_profile'])) {
        draw($tool_content, 1);
} else {
draw($tool_content, 0);        
}

exit;
