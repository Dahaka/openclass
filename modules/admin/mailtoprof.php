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
	mailtoprof.php
	@last update: 31-05-2006 by Pitsiougas Vagelis
	@authors list: Karatzidis Stratos <kstratos@uom.gr>
		       Pitsiougas Vagelis <vagpits@uom.gr>
==============================================================================
        @Description: Send mail to the users of the platform

 	This script allows the administrator to send a message by email to all
 	users or just the professors of the platform

 	The user can : - Send a message by email to all users or just the pofessors
                 - Return to main administrator page

 	@Comments: The script is organised in three sections.

  1) Write message and select where to send it
  2) Try to send the message by email
  3) Display all on an HTML page

==============================================================================*/

// Check if user is administrator and if yes continue
// Othewise exit with appropriate message
$require_usermanage_user = TRUE;
// Include baseTheme
include '../../include/baseTheme.php';
// Include functions needed to send email
include('../../include/sendMail.inc.php');
// Define $nameTools
$nameTools=$langSendInfoMail;
$navigation[] = array("url" => "index.php", "name" => $langAdmin);

/*****************************************************************************
		MAIN BODY
******************************************************************************/
// Send email after form post
if (isset($_POST['submit']) && ($_POST['body_mail'] != "") && ($_POST['submit'] == $langSend)) {
	// Where to send the email
	if ($_POST['sendTo'] == "0") {
		// All users
		$sql = db_query("SELECT email, user_id FROM user");
	} elseif ($_POST['sendTo'] == "1") {
		// Only professors
		$sql = db_query("SELECT email, user_id FROM user where statut='1'");
	}  elseif ($_POST['sendTo'] == "2") {
		// Only students
		$sql = db_query("SELECT email, user_id FROM user where statut='5'");
	} else { die(); } // invalid sendTo var
        
        $recipients = array();
        $emailsubject = $langInfoAboutEclass;
		$emailbody = "".$_POST['body_mail']."

$langManager $siteName
$administratorName $administratorSurname
$langTel $telephone
$langEmail : $emailhelpdesk
";
	// Send email to all addresses
	while ($m = mysql_fetch_array($sql)) {
		$emailTo = $m["email"];
                $user_id = $m["user_id"];
                if (get_user_email_notification($user_id)) {
                        // checks if user is notified by email
                        array_push($recipients, $emailTo);
                }
                $linkhere = "&nbsp;<a href='${urlServer}modules/profile/profile.php'>$langHere</a>.";
                $unsubscribe = "<br /><br />".sprintf($langLinkUnsubscribeFromPlatform, $siteName);            
                $emailcontent = $emailbody.$unsubscribe.$linkhere;            
                if (count($recipients) >= 50) {                
                      send_mail_multipart('', '', '', $recipients, $emailsubject, $emailbody, $emailcontent, $charset);
                      $recipients = array();
                }
        } 
        if (count($recipients) > 0)  {                
                send_mail_multipart('', '', '', $recipients, $emailsubject, $emailbody, $emailcontent, $charset); 
        }
	// Display result and close table correctly
	$tool_content .= "<p class='success'>$emailsuccess</p>";
} else {
        // Display form to administrator
        $tool_content .= "<form action='$_SERVER[SCRIPT_NAME]' method='post'>
      <fieldset>
        <legend>$langMessage</legend>
	<table class='tbl' width='100%'>
	<tr>
	  <td>$typeyourmessage<br />
	      <textarea name='body_mail' rows='10' cols='60'></textarea></td>
	</tr>
	<tr>
	  <td>$langSendMessageTo
	    <select name='sendTo'>
	      <option value='1'>$langProfOnly</option>
		<option value='2'>$langStudentsOnly</option>
	      <option value='0'>$langToAllUsers</option>
	      </select>	    </td>
	  </tr>
	<tr>
	  <td class='right'><input type='submit' name='submit' value='".q($langSend)."' /></td>
	  </tr>
	</table>
        </fieldset>
	</form>";

}
// Display link back to index.php
$tool_content .= "<p align='right'><a href='index.php'>".$langBack."</a></p>";
draw($tool_content,3);
