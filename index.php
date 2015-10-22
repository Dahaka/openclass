<?php
session_start();
/* ========================================================================
 * Open eClass 2.8
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
 * Index
 *
 * @version $Id$
 *
 * @abstract This file serves as the home page of eclass when the user
 * is not logged in.
 *
 */

/***************************************************************
*               HOME PAGE OF ECLASS		               *
****************************************************************
*/
define('HIDE_TOOL_TITLE', 1);
$guest_allowed = true;
$path2add = 0;
include "include/baseTheme.php";
include "include/CAS/CAS.php";
include "modules/auth/auth.inc.php";
require_once 'include/lib/modalboxhelper.class.php';
require_once 'include/lib/multimediahelper.class.php';
require_once 'include/phpass/PasswordHash.php';
require_once 'include/lib/textLib.inc.php';

ModalBoxHelper::loadModalBox();
//$homePage is used by baseTheme.php to parse correctly the breadcrumb
$homePage = true;
$tool_content = "";
// first check
// check if we can connect to database. If not then eclass is most likely not installed
if (isset($mysqlServer) and isset($mysqlUser) and isset($mysqlPassword)) {
	$db = mysql_connect($mysqlServer, $mysqlUser, $mysqlPassword);
	if (mysql_version()) db_query("SET NAMES utf8");
}
if (!$db) {
	include "include/not_installed.php";
}

// unset system that records visitor only once by course for statistics
include('include/action.php');
if (isset($dbname)) {
        mysql_select_db($dbname);
        $action = new action();
        $action->record('MODULE_ID_UNITS', 'exit');
}
unset($dbname);

// second check
// can we select a database? if not then there is some sort of a problem
if (isset($mysqlMainDb)) $selectResult = mysql_select_db($mysqlMainDb,$db);
if (!isset($selectResult)) {
	include "include/not_installed.php";
}

// if we try to login... then authenticate user.
$warning = '';
if (isset($_SESSION['shib_uname'])) {
	// authenticate via shibboleth
	shib_cas_login('shibboleth');
} elseif (isset($_SESSION['cas_uname']) && !isset($_GET['logout'])) {
	// authenticate via cas
	shib_cas_login('cas');
} else {
	// normal authentication
	process_login();
} 

if (isset($_SESSION['uid'])) { 
	$uid = $_SESSION['uid'];
} else { 
	$uid = 0;
}

if (isset($_GET['logout']) and $uid) {
        db_query("INSERT INTO loginout (loginout.id_user,
                loginout.ip, loginout.when, loginout.action)
                VALUES ($uid, '$_SERVER[REMOTE_ADDR]', NOW(), 'LOGOUT')");
	if (isset($_SESSION['cas_uname'])) { // if we are CAS user
		define('CAS', true);
	}
	foreach(array_keys($_SESSION) as $key) {
		unset($_SESSION[$key]);
	}
	session_destroy();
	$uid = 0;
	if (defined('CAS')) {
		$cas = get_auth_settings(7);
		if (isset($cas['cas_ssout']) and intval($cas['cas_ssout']) === 1) {
			phpCAS::client(SAML_VERSION_1_1, $cas['cas_host'], intval($cas['cas_port']), $cas['cas_context'], FALSE);
			phpCAS::logoutWithRedirectService($urlServer);
		}
	}
}

// if the user logged in include the correct language files
// in case he has a different language set in his/her profile
if (isset($language)) {
        // include_messages
        include("${webDir}modules/lang/$language/common.inc.php");
        $extra_messages = "${webDir}/config/$language.inc.php";
        if (file_exists($extra_messages)) {
                include $extra_messages;
        } else {
                $extra_messages = false;
        }
        include("${webDir}modules/lang/$language/messages.inc.php");
        if ($extra_messages) {
                include $extra_messages;
        }

}

//----------------------------------------------------------------
// if login succesful display courses lists
// --------------------------------------------------------------
if ($uid AND !isset($_GET['logout'])) {
        if (check_guest()) {
                // if the user is a guest send him straight to the corresponding lesson
                $guestSQL = db_query("SELECT code FROM cours_user, cours
                                      WHERE cours.cours_id = cours_user.cours_id AND
                                            user_id = $uid", $mysqlMainDb);
                if (mysql_num_rows($guestSQL) > 0) {
                        $sql_row = mysql_fetch_row($guestSQL);
                        $dbname = $sql_row[0];
                        $_SESSION['dbname'] = $dbname;
                        header("Location: {$urlServer}courses/$dbname/index.php");
                        exit;
                }
        }        
        // if user is not guest redirect him to portfolio
        header("Location: {$urlServer}main/portfolio.php");
        
} else {	
        $rss_link = "<link rel='alternate' type='application/rss+xml' title='RSS-Feed' href='" .
                    $urlServer . "rss.php'>";
	if (isset($_SESSION['langswitch'])) {
            $language = $_SESSION['langswitch'];
        }

        $tool_content .= "<p align='justify'>$langInfoAbout</p>";        

        $qlang = ($language == "greek")? 'el': 'en';
        $sql = "SELECT `id`, `date`, `title`, `body`, `ordre` FROM `admin_announcements`
                WHERE `visible` = 'V'
                        AND lang='$qlang'
                        AND (`begin` <= NOW() or `begin` IS null)
                        AND (NOW() <= `end` or `end` IS null)
                ORDER BY `ordre` DESC";
        $result = db_query($sql, $mysqlMainDb);
        if (mysql_num_rows($result) > 0) {
                $announceArr = array();
                while ($eclassAnnounce = mysql_fetch_array($result)) {
                        array_push($announceArr, $eclassAnnounce);
                }
                $tool_content .= "
                <br />
                <table width='100%' class='tbl_alt'>
                <tr>
                  <th colspan='2'>$langAnnouncements <a href='${urlServer}rss.php'>
                    <img src='$themeimg/feed.png' alt='RSS Feed' title='RSS Feed' />
                    </a>
                  </th>
                </tr>";

                $numOfAnnouncements = count($announceArr);
                for($i=0; $i < $numOfAnnouncements; $i++) {
                        $aid = $announceArr[$i]['id'];
                        $tool_content .= "
                        <tr>
                          <td width='1'><img style='border:0px;' src='$themeimg/arrow.png' alt='' /></td>
                          <td>
                            <b><a href='modules/announcements/main_ann.php?aid=$aid'>".q($announceArr[$i]['title'])."</a></b>
                    &nbsp;<span class='smaller'>(".claro_format_locale_date($dateFormatLong, strtotime($announceArr[$i]['date'])).")</span>
                                ".standard_text_escape(ellipsize($announceArr[$i]['body'], 500, "<strong>&nbsp;<a href='modules/announcements/main_ann.php?aid=$aid'>... <span class='smaller'>[$langMore]</span></a></strong>"))."
                          </td>
                        </tr>";
                }
                $tool_content .= "</table>";
        }
        // check for shibboleth
        $shibactive = mysql_fetch_array(db_query("SELECT auth_default FROM auth WHERE auth_name='shibboleth'"));
        if ($shibactive['auth_default'] == 1) {
                $shibboleth_link = "<a href='{$urlSecure}secure/index.php'>$langShibboleth</a><br />";
        } else {
                $shibboleth_link = "";
        }

        // check for CAS
        $casactive = mysql_fetch_array(db_query("SELECT auth_default FROM auth WHERE auth_name='cas'"));
        if ($casactive['auth_default'] == 1) {
                $cas_link = "<a href='{$urlServer}secure/cas.php'>$langViaCAS</a><br />";
        } else {
                $cas_link = "";
        }

        if (!get_config('dont_display_login_form')) {
                $tool_content .= "</div><div id='rightbar'>
                <form action='$urlSecure' method='post'>
                 <table width='100%' class='tbl'>
                 <tr>
                   <th class='LoginHead'><b>$langUserLogin </b></th>
                 </tr>
                 <tr>
                   <td class='LoginData'>
                   $langUsername <br />
                   <input class='Login' name='uname' size='17' autocomplete='off' /><br />
                   $langPass <br />
                   <input class='Login' name='pass' type = 'password' size = '17' autocomplete='off' /><br /><br />
                   <input class='Login' name='submit' type = 'submit' size = '17' value = '".q($langEnter)."' /><br />
                   $warning</td></tr>
                   <tr><td><p class='smaller'><a href='modules/auth/lostpass.php'>$lang_forgot_pass</a></p>
                   </td>
                 </tr>";
                 if (!empty($shibboleth_link) or !empty($cas_link)) {
                        $tool_content .= "<tr><th class='LoginHead'><b>$langAlternateLogin </b></th></tr>";
                 }
                $tool_content .= "<tr><td class='LoginData'>
                   $shibboleth_link
                   $cas_link</td></tr>";
                $online_users = getOnlineUsers();
                if ($online_users > 0) {
                       $tool_content .= "<th class='LoginHead'><br />$langOnlineUsers: $online_users</th>";
                }          
                $tool_content .= "</table></form>";
        }
        $tool_content .= "<div id='extra'>{%ECLASS_HOME_EXTRAS_RIGHT%}</div>";
        
	draw($tool_content, 0, null, $rss_link);
}
