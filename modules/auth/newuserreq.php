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

$require_admin = TRUE;
include '../../include/baseTheme.php';
include '../../include/sendMail.inc.php';
require_once '../../include/phpass/PasswordHash.php';
require_once '../../include/lib/pwgen.inc.php';

$nameTools = $langNewUser;
$navigation[] = array ('url' => '../admin/', 'name' => $langAdmin);

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

// Initialise $tool_content
$tool_content = "";
$submit = isset($_POST['submit'])?$_POST['submit']:'';
// ----------------------------
// register user
// ----------------------------

if($submit) {
   // register user
  $nom_form = isset($_POST['nom_form'])?$_POST['nom_form']:'';
  $prenom_form = isset($_POST['prenom_form'])?$_POST['prenom_form']:'';
  $uname = isset($_POST['uname'])?canonicalize_whitespace($_POST['uname']):'';
  $password = isset($_POST['password'])?$_POST['password']:'';
  $email_form = isset($_POST['email_form'])?mb_strtolower(trim($_POST['email_form'])):'';
  $department = isset($_POST['department'])?$_POST['department']:'';
  $localize = isset($_POST['localize'])?$_POST['localize']:'';
  $lang = langname_to_code($localize);	

      // check if user name exists
  $username_check = db_query("SELECT username FROM `$mysqlMainDb`.user WHERE username=". quote($uname));
  while ($myusername = mysql_fetch_array($username_check)) {
    $user_exist=$myusername[0];
  }

// check if there are empty fields
  if (empty($nom_form) or empty($prenom_form) or empty($password)
        or empty($uname) or empty($email_form)) {
      $tool_content .= error_screen($langFieldsMissing);
      $tool_content .= end_tables();
  }
  elseif(isset($user_exist) and $uname==$user_exist) {
      $tool_content .= error_screen($langUserFree);
      $tool_content .= end_tables();
 }

// check if email syntax is valid
 elseif(!email_seems_valid($email_form)) {
        $tool_content .= error_screen($langEmailWrong);
        $tool_content .= end_tables();
 }


// registration accepted

  else {
    $emailsubject = "$langYourReg $siteName"; // $langAsUser

      $emailbody = "
$langDestination $prenom_form $nom_form

$langYouAreReg $siteName $langSettings $uname
$langPass: $password
$siteName URL: $urlServer

$langProblem

$administratorName $administratorSurname
$siteName
$langTel: $telephone
$langEmail: $emailhelpdesk
";

send_mail('', '', '', $email_form, $emailsubject, $emailbody, $charset);


// register user
    $registered_at = time();
    $expires_at = time() + $durationAccount;

    $hasher = new PasswordHash(8, false);
    $password_encrypted = $hasher->HashPassword($password);
    $s = db_query("SELECT id FROM faculte WHERE name= ". quote($department) );
    $dep = mysql_fetch_array($s);
    
    if ($dep !== false)
    {
        $inscr_user = db_query("INSERT INTO `$mysqlMainDb`.user
          (user_id, nom, prenom, username, password, email, statut, department, registered_at, expires_at, lang)
          VALUES ('NULL', ". quote($nom_form) .", ". quote($prenom_form) .", ". quote($uname) .", ". 
                        quote($password_encrypted) .", ". quote($email_form) .", 5, ". quote($dep['id']) .", ". 
                        quote($registered_at) .", ". quote($expires_at) .", ". quote($lang) );
    
        // close request
        $rid = intval($_POST['rid']);
        db_query("UPDATE user_request set status = 2, date_closed = NOW() WHERE id = $rid");
    
        $tool_content .= "<tr><td valign='top' align='center' class='alert1'>$usersuccess
        <br><br><a href='../admin/listreq.php?type=user' class='mainpage'>$langBack</a>";
    }
  }

} else {

//---------------------------
// 	display form
// ---------------------------

if (isset($_GET['lang'])) {
	$lang = $_GET['lang'];
	$lang = langname_to_code($language);
}

$tool_content .= "<table width=\"99%\"><tbody>
   <tr>
    <td>
    <form action='$_SERVER[SCRIPT_NAME]' method='post'>
    <table border=0 cellpadding='1' cellspacing='2' border='0' width='100%' align=center>
	<thead>
    <tr>
    <th class='left' width=20%>$langSurname</th>
	 <td><input type='text' class=auth_input_admin name='nom_form' value='".@q($ps)."' >
	<small>&nbsp;(*)</small></td>
	  </tr>
	  <tr>
	  <th class='left'>$langName</th>
	  <td><input type='text' class=auth_input_admin name='prenom_form' value='".@q($pn)."' >
	<small>&nbsp;(*)</small></td>
	  </tr>
	  <tr>
	  <th class='left'>$langUsername</th>
	  <td><input type='text' class=auth_input_admin name='uname' value='".@q($pu)."' autocomplete='off'>
		<small>&nbsp;(*)</small></td>
	  </tr>
	  <tr>
	  <th class='left'>$langPass&nbsp;:</th>
	  <td><input type='text' class=auth_input_admin name='password' value=".genPass()." id='password' autocomplete='off'/>&nbsp;<span id='result'></span></td>
	  </tr>
	  <tr>
    	<th class='left'>$langEmail</th>
	  <td><input type='text' class=auth_input_admin name='email_form' value='".@q($pe)."'>
		<small>&nbsp;(*)</small></td>
	  </tr>
	  <tr>
	  <th class='left'>$langFaculty &nbsp;
		</span></th><td>";

	$dep = array();
        $deps=db_query("SELECT name FROM faculte order by id");
			while ($n = mysql_fetch_array($deps))
				$dep[$n[0]] = $n['name'];  

		if (isset($pt))
			$tool_content .= selection ($dep, 'department', $pt);
		else 
			$tool_content .= selection ($dep, 'department');
 
	$tool_content .= "<tr><th class='left'>$langLanguage</th><td>";
	$tool_content .= lang_select_options('localize');
	$tool_content .= "</td></tr>";

	$tool_content .= "</td></tr><tr><td colspan='2'>".$langRequiredFields."</td></tr>
		<tr><td>&nbsp;</td>
		<td><input type=\"submit\" name='submit' value='".q($langSubmit)."' ></td>
		</tr></thead></table>
		<input type='hidden' name='rid' value='".@q($id)."'>
		</tbody></table></form>";
    $tool_content .= "<center><p><a href=\"../admin/index.php\">$langBack</p></center>";

} // end of if 

draw($tool_content,3, 'auth', $head_content);

// -----------------
// functions
// -----------------
function error_screen($message) {

	global $langTryAgain;

	return "<tr height='80'><td colspan='3' valign='top' align='center' class=alert1>$message</td></tr><br><br>
      <tr height='30' valign='top' align='center'><td align=center>
      <a href='../admin/listreq.php?type=user' class=mainpage>$langTryAgain</a><br><br></td></tr>";
}

function end_tables() {
	global $langBack;
	
	$retstring = "</td></tr><tr><td align=right valign=bottom height='180'>";
	$retstring .= "<a href='../admin/index.php' class=mainpage>$langBack&nbsp;</a>";
	$retstring .= "</td></tr></table>";
	
	return $retstring;
}

?>
