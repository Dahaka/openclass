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


/* *===========================================================================
edituser.php
@last update: 27-06-2006 by Karatzidis Stratos
@authors list: Karatzidis Stratos <kstratos@uom.gr>
Vagelis Pitsioygas <vagpits@uom.gr>
==============================================================================
@Description: Edit user info (eclass version)

This script allows the admin to :
- edit the user information
- activate / deactivate a user account

==============================================================================
*/

$require_usermanage_user = TRUE;
include '../../include/baseTheme.php';
include 'admin.inc.php';
include '../auth/auth.inc.php';
include '../../include/jscalendar/calendar.php';

if (isset($_REQUEST['u'])) {
	$u = intval($_REQUEST['u']);        
        // security check
        if (check_admin($u) and (!(isset($_SESSION['is_admin'])))) {            
                header('Location: ' . $urlServer);
        }
	$_SESSION['u_tmp'] = $u;
}

if (!isset($_REQUEST['u'])) {
	$u = $_SESSION['u_tmp'];
}



$verified_mail = isset($_REQUEST['verified_mail'])?intval($_REQUEST['verified_mail']):2;

$lang_editor = $lang_jscalendar = langname_to_code($language);

$jscalendar = new DHTML_Calendar($urlServer.'include/jscalendar/', $lang_jscalendar, 'calendar-blue2', false);
$head_content .= $jscalendar->get_load_files_code();

// Initialise $tool_content
$navigation[] = array('url' => 'index.php', 'name' => $langAdmin);
$navigation[] = array('url' => 'search_user.php', 'name' => $langSearchUser);
$nameTools = $langEditUser;

if (isset($_POST['u_submitted'])) {
        $u_submitted = $_POST['u_submitted'];
} else {
        $u_submitted = '';
}

if ($u)	{
        $extra = '';
        // Display secondary email field for students        
        $q = db_query("SELECT nom, prenom, username, password, email, parent_email, phone, department,
                        registered_at, expires_at, statut, am, verified_mail, whitelist 
                        FROM user WHERE user_id = $u");
        $info = mysql_fetch_assoc($q);
        if (isset($_POST['submit_editauth'])) {
                $auth = intval($_POST['auth']);
                $oldauth = array_search($info['password'], $auth_ids);
                $tool_content .= "<p class='success'>$langQuotaSuccess.";
                if ($auth == 1 and $oldauth != 1) {
                        $tool_content .= " <a href='password.php?userid=$u'>$langEditAuthSetPass</a>";
                        $newpass = '.';
                } else {
                        $newpass = $auth_ids[$auth];
                }
                $tool_content .= "</p>";
                db_query("UPDATE user SET password = '$newpass' WHERE user_id = $u");
                $info['password'] = $newpass;
        }
        if (isset($_GET['edit']) and $_GET['edit'] = 'auth') {
                $navigation[] = array('url' => 'edituser.php?u=' . $u, 'name' => $langEditUser);
                $nameTools = $langEditAuth;
                $current_auth = 1;
                $auth_names[1] = get_auth_info(1);
                foreach (get_auth_active_methods() as $auth) {
                        $auth_names[$auth] = get_auth_info($auth); 
                        if ($info['password'] == $auth_ids[$auth]) {
                                $current_auth = $auth;
                        }
                }
                $tool_content .= "<form method='post' action='$_SERVER[SCRIPT_NAME]'>
                        <fieldset>
                        <legend>$langEditAuth: ".q($info['username'])."</legend>
                        <table class='tbl' width='100%'>
                        <tr>
                          <th width='170' class='left'>$langEditAuthMethod</th>
                          <td>".selection($auth_names, 'auth', intval($current_auth))."</td>
                        </tr>
                        <tr>
                          <th>&nbsp;</th>
                          <td class='right'>
                            <input type='hidden' name='u' value='$u'>
                            <input type='submit' name='submit_editauth' value='".q($langModify)."'>
                          </td>
                        </tr>
                        </table>
                        </fieldset>
                        </form>";
                draw($tool_content, 3, null, $head_content);
                exit;
        }
        if (!$u_submitted) { // if the form was not submitted
		$tool_content .= "
                    <div id='operations_container'>
                     <ul id='opslist'>";
                if ($u != 1 and get_admin_rights($u) < 0) {
                        $tool_content .= "<li><a href='mergeuser.php?u=$u'>$langUserMerge</a></li>\n";
                }
                if (!in_array($info['password'], $auth_ids)) {
                        $tool_content .= "
                        <li><a href='password.php?userid=$u'>".$langChangePass."</a></li>\n";
                }
                $tool_content .= "
              <li><a href='edituser.php?u=$u&amp;edit=auth'>$langEditAuth</a></li>
              <li><a href='deluser.php?u=$u'>$langDelUser</a></li>
              <li><a href='listusers.php'>$langBack</a></li>";
                $tool_content .= "</ul></div>";
                   $tool_content .= "
                    <form name='edituser' method='post' action='$_SERVER[SCRIPT_NAME]'>
                    <fieldset>
                    <legend>$langEditUser: ".q($info['username'])."</legend>
                    <table class='tbl' width='100%'>
                    <tr>
                      <th width='170' class='left'>$langSurname:</th>
                      <td><input type='text' name='lname' size='50' value='".q($info['nom'])."' /></td>
                    </tr>
                    <tr>
                      <th class='left'>$langName:</th>
                      <td><input type='text' name='fname' size='50' value='".q($info['prenom'])."' /></td>
                   </tr>";

        if(!in_array($info['password'], $auth_ids)) {
                $tool_content .= "
                   <tr>
                     <th class='left'>$langUsername:</th>
                     <td><input type='text' name='username' size='50' value='".q($info['username'])."' /></td>
                   </tr>";
        }
        else    // means that it is external auth method, so the user cannot change this password
        {
                switch($info['password'])
                {
                        case "pop3": $auth=2;break;
                        case "imap": $auth=3;break;
                        case "ldap": $auth=4;break;
                        case "db": $auth=5;break;
                        case "shibboleth": $auth=6;break;
                        case "cas": $auth=7;break;
                        default: $auth=1;break;
                }		  
                $auth_text = get_auth_info($auth);
                $tool_content .= "
                <tr>
                <th class='left'>".$langUsername. "</th>
                <td><b>".autoquote($info['username'])."</b> [".$auth_text."] <input type='hidden' name='username' value=".autoquote($info['username'])." /> </td>
                </tr>";
        }

        $tool_content .= "
           <tr>
             <th class='left'>e-mail: </th>
             <td><input type='text' name='email' size='50' value='".q(mb_strtolower(trim($info['email'])))."' /></td>
           </tr>";

        $tool_content .= "<tr>
       <th>$langEmailVerified: </th>
       <td>";
        $verified_mail_data = array();
        $verified_mail_data[0] = $m['pending'];
        $verified_mail_data[1] = $m['yes'];
        $verified_mail_data[2] = $m['no'];

        $tool_content .= selection($verified_mail_data,"verified_mail",intval($info['verified_mail']));
        $tool_content .= "
                  </td>
             </tr>";
        
        // Display secondary email field for students
        if (get_config('enable_secondary_email')) {
                        if ($info['statut'] == 5) {
                        $tool_content .= "
                        <tr>
                          <th class='left'>$langParentEmail: </th>
                          <td><input type='text' name='parent_email' size='50' value='".q(mb_strtolower(trim($info['parent_email'])))."' /></td>
                        </tr>";
                }
        }

        $tool_content .= "
           <tr>
             <th class='left'>$langAm: </th>
             <td><input type='text' name='am' size='50' value='".q($info['am'])."' /></td>
           </tr>
           <tr>
             <th class='left'>$langTel: </th>
             <td><input type='text' name='phone' size='50' value='".q($info['phone'])."' /></td>
           </tr>
           <tr>
             <th class='left'>$langFaculty:</th>
           <td>";
	if(!empty($info['department'])) {
		$department_select_box = list_departments(intval($info['department']));
	} else {
		$department_select_box = "";
	}

	$tool_content .= $department_select_box."</td></tr>";
	$tool_content .= "
        <tr>
          <th class='left'>$langProperty:</th>
          <td>";       
	if ($info['statut'] == '10') { // if we are guest user do not display selection
		$tool_content .= selection(array(10 => $langGuest), 'newstatut', intval($info['statut']));
	} else {
		$tool_content .= selection(array(1 => $langTeacher, 5 => $langStudent), 'newstatut', intval($info['statut']));
	}
	$tool_content .= "</td>";

	$tool_content .= "
     <tr>
       <th class='left'>$langRegistrationDate:</th>
       <td>".date("j/n/Y H:i",$info['registered_at'])."</td>
     </tr>
     <tr>
      <th class='left'>$langExpirationDate: </th>
      <td>";
        $dateregistration = date("j-n-Y", $info['expires_at']);
        $hour = date("H", $info['expires_at']);
        $minute = date("i", $info['expires_at']);

        // -- jscalendar ------
        $start_cal = $jscalendar->make_input_field(
                array('showOthers' => true,
                      'align' => 'Tl',
                      'ifFormat' => '%d-%m-%Y'),
                array('name' => 'date',
                      'value' => $dateregistration));

	$tool_content .= $start_cal."&nbsp;&nbsp;&nbsp;";
	$tool_content .= "<select name='hour'>
	        <option value='$hour'>$hour</option>
        	<option value='--'>--</option>";
        for ($h=0; $h<=24; $h++)
                $tool_content .= "<option value='$h'>$h</option>";
        $tool_content .= "</select>&nbsp;&nbsp;&nbsp;";
        $tool_content .= "<select name='minute'>
                          <option value='$minute'>$minute</option>
                          <option value='--'>--</option>";
        for ($m = 0; $m <= 55; $m = $m + 5)
                $tool_content .= "<option value='$m'>$m</option>";
        $tool_content .= "</select></td>";

	$tool_content .= "</tr>
     <tr>
       <th>$langUserID: </th>
       <td>$u</td>
     </tr>
     <tr>
       <th>$langUserWhitelist</th>
       <td><textarea rows='6' cols='60' name='user_upload_whitelist'>".q($info['whitelist'])."</textarea></td>
     </tr>
     <tr>
       <th>&nbsp;</th>
       <td class='right'>
	    <input type='hidden' name='u' value='$u' />
	    <input type='hidden' name='u_submitted' value='1' />
	    <input type='hidden' name='registered_at' value='".$info['registered_at']."' />
	    <input type='submit' name='submit_edituser' value='".q($langModify)."' />
       </td>
     </tr>
     </table>
     </fieldset>
     </form>";

	$sql = db_query("SELECT a.code, a.intitule, a.cours_id, a.visible, b.reg_date, b.statut
                                FROM cours AS a JOIN faculte ON a.faculteid = faculte.id
                                     LEFT JOIN cours_user AS b ON a.cours_id = b.cours_id
                                WHERE b.user_id = $u ORDER BY b.statut, faculte.name");

		// user is registered to courses
		if (mysql_num_rows($sql) > 0) {
			$tool_content .= "
                        <p class='title1'>$langStudentParticipation</p>
			<table class='tbl_alt' width='100%'>
			<tr>                        
			<th colspan='2'><div align='left'>$langCode</div></th>
			<th><div align='left'>$langLessonName</div></th>
			<th>$langCourseRegistrationDate</th><th>$langProperty</th><th>$langActions</th>
			</tr>";
                        $k=0;
			for ($j = 0; $j < mysql_num_rows($sql); $j++) {
				$logs = mysql_fetch_array($sql);
                                if ($logs['visible'] == COURSE_INACTIVE) {
                                        $tool_content .= "<tr class='invisible'>";
                                } else {
                                        if ($k%2 == 0) {
                                                $tool_content .= "<tr class='even'>";
                                        } else {
                                                $tool_content .= "<tr class='odd'>";
                                        }
                                }
				$tool_content .= "
                                        <td width='1'><img src='$themeimg/arrow.png' alt=''></td>
					<td><a href='{$urlServer}courses/$logs[code]/'>".q($logs['code'])."</a></td>
					<td>".q($logs['intitule'])."</td><td align='center'>";
				if ($logs['reg_date'] == '0000-00-00') {
					 $tool_content .= $langUnknownDate;
                                } else {
					$tool_content .= " ".nice_format($logs['reg_date'])." ";
                                }
				$tool_content .= "</td><td align='center'>";
				switch ($logs['statut'])
				{
					case 1:
						$tool_content .= $langTeacher;
						$tool_content .= "</td><td align='center'>---</td></tr>\n";
						break;
					case 5:
						$tool_content .= $langStudent;
						$tool_content .= "</td><td align='center'>
						<a href='unreguser.php?u=$u&amp;c=$logs[cours_id]'>
						<img src='$themeimg/cunregister.png' title='".q($langUnregCourse)."' alt='".q($langUnregCourse)."' /></a></td>
  						</tr>\n";
						break;
					default:
						$tool_content .= $langVisitor;
						$tool_content .= "</td><td align='center'>
						<a href='unreguser.php?u=$u&amp;c=$logs[cours_id]'>
						<img src='$themeimg/cunregister.png' title='".q($langUnregCourse)."' alt='".q($langUnregCourse)."' /></a></td>
  						</tr>\n";
						break;
				}
                                $k++;
			}                          
			$tool_content .= "</table>";
		} else {
			$tool_content .= "<p class='caution'>$langNoStudentParticipation</p>";			
		}
	} else { // if the form was submitted then update user

	// get the variables from the form and initialize them
	$fname = isset($_POST['fname'])?$_POST['fname']:'';
	$lname = isset($_POST['lname'])?$_POST['lname']:'';
	// trim white spaces in the end and in the beginning of the word
	$username = isset($_POST['username'])?$_POST['username']:'';
	$email = isset($_POST['email'])?mb_strtolower(trim($_POST['email'])):'';
        $parent_email = (get_config('enable_secondary_email') and isset($_POST['parent_email']))?mb_strtolower(trim($_POST['parent_email'])):'';        
	$phone = isset($_POST['phone'])?$_POST['phone']:'';
	$am = isset($_POST['am'])?$_POST['am']:'';
	$department = isset($_POST['department'])?$_POST['department']:'NULL';
	$newstatut = isset($_POST['newstatut'])?$_POST['newstatut']:'NULL';
	$registered_at = isset($_POST['registered_at'])?$_POST['registered_at']:'';
	$date = isset($_POST['date'])?$_POST['date']:'';
	$hour = isset($_POST['hour'])?$_POST['hour']:'';
	$minute = isset($_POST['minute'])?$_POST['minute']:'';
	$date = explode("-",  $date);
	$day=$date[0];
	$year=$date[2];
	$month=$date[1];
	$expires_at = mktime($hour, $minute, 0, $month, $day, $year);
	$user_upload_whitelist = isset($_POST['user_upload_whitelist']) ? $_POST['user_upload_whitelist'] : '';
	$user_exist= FALSE;
	// check if username is free
	$username_check = db_query("SELECT username FROM user WHERE
		user_id <> $u AND username = ".autoquote($username));
	if (mysql_num_rows($username_check) > 0) {
		$user_exist = true;
	}

  // check if there are empty fields
	if (empty($fname) OR empty($lname) OR empty($username)) {
		$tool_content .= "<table width='99%'><tbody><tr>
		<td class='caution' height='60'><p>$langFieldsMissing</p>
		<p><a href='$_SERVER[SCRIPT_NAME]'>$langAgain</a></p></td></tr></tbody></table><br /><br />";
		draw($tool_content, 3, ' ', $head_content);
		  exit();
	      }
	elseif(isset($user_exist) AND $user_exist == TRUE) {
	       $tool_content .= "<table width='100%'><tbody><tr>
	       <td class='caution' height='60'><p>$langUserFree</p>
	       <p><a href='$_SERVER[SCRIPT_NAME]'>$langAgain</a></p></td></tr></tbody></table><br /><br />";
	       draw($tool_content, 3, null, $head_content);
	   exit();
	}
		if($registered_at>$expires_at) {
			$tool_content .= "<center><br /><b>$langExpireBeforeRegister<br /><br /><a href='edituser.php?u=$u'>$langAgain</a></b><br />";
		} else {
			if ($u == 1) $department = 'NULL';
			// email cannot be verified if there is no mail saved
			if (empty($email) && $verified_mail==1) {
				$verified_mail=2;
			}
                        $extra = '';                                         
                
			$sql = "UPDATE user SET nom = ".autoquote($lname).", prenom = ".autoquote($fname).",
                                       username = ".autoquote($username).", email = ".autoquote($email).",
                                       parent_email = ".autoquote($parent_email).",
                                       statut = ".intval($newstatut).", phone=".autoquote($phone).",
                                       department = ".intval($department).", expires_at=".$expires_at.",
                                       am = ".autoquote($am)." , verified_mail = ".intval($verified_mail) .",
                                       whitelist = ". quote($user_upload_whitelist) ." 
                                       WHERE user_id = ".intval($u);
			$qry = db_query($sql);
                        if (!$qry) {
                                $tool_content .= "$langNoUpdate: $u!";
                        } else {
                                $num_update = mysql_affected_rows();
                                if ($num_update == 1) {
                                        $tool_content .= "<center><br /><b>$langSuccessfulUpdate</b><br /><br />";
                                } else {
                                        $tool_content .= "<center><br /><b>$langUpdateNoChange</b><br /><br />";
                                }
                        }
                        $tool_content .= "<a href='listusers.php'>$langBack</a></center>";
                }
	}
}
else
{
	$tool_content .= "<h1>$langError</h1>\n<p><a href='listcours.php'>$back</p>\n";
}

draw($tool_content, 3, null, $head_content);
