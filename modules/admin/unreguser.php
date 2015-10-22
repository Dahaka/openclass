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



/**===========================================================================
	unreguser.php
	@last update: 27-06-2006 by Karatzidis Stratos
	@authors list: Karatzidis Stratos <kstratos@uom.gr>
		       Vagelis Pitsioygas <vagpits@uom.gr>
==============================================================================
        @Description: Delete user from platform and from courses (eclass version)

 	This script allows the admin to :
 	- delete a user from participating into a course

==============================================================================
*/

$require_usermanage_user = TRUE;
include '../../include/baseTheme.php';
$nameTools = $langUnregUser;
$navigation[]= array ("url"=>"index.php", "name"=> $langAdmin);

// get the incoming values and initialize them
$u = isset($_GET['u'])? intval($_GET['u']): false;
$c = isset($_GET['c'])? intval($_GET['c']): false;
$doit = isset($_GET['doit']);

$u_account = $u? q(uid_to_username($u)): '';
$u_realname = $u? q(uid_to_name($u)): '';
$u_statut = get_uid_statut($u);
$t = 0;

if (!$doit) {
    
    if ($u_account && $c) {
        $tool_content .= "<p class='title1'>$langConfirmDelete</p>
            <div class='alert1'>$langConfirmDeleteQuestion1 <em>$u_realname ($u_account)</em>
            $langConfirmDeleteQuestion2 <em>".q(course_id_to_title($c))."</em>
            </div>
            <p class='eclass_button'><a href='$_SERVER[SCRIPT_NAME]?u=$u&amp;c=$c&amp;doit=yes'>$langDelete</a></p>";
    } else {
        $tool_content .= "<p>$langErrorUnreguser</p>";
    }
    
    $tool_content .= "<div class='right'><a href='edituser.php?u=$u'>$langBack</a></div><br/>";
    
} else {
    if ($c and $u) {
        $q = db_query("DELETE from cours_user WHERE user_id = $u AND cours_id = $c");
        if (mysql_affected_rows() > 0) {
            db_query("DELETE FROM group_members
                            WHERE user_id = $u AND
                            group_id IN (SELECT id FROM `group` WHERE course_id = $c)");
            $tool_content .= "<p>$langUserWithId $u $langWasCourseDeleted <em>".q(course_id_to_title($c))."</em></p>\n";
            $m = 1;
        }
    } else {
        $tool_content .= "$langErrorDelete";
    }
    $tool_content .= "<br />&nbsp;";
    if((isset($m)) && (!empty($m))) {
        $tool_content .= "<br /><a href='edituser.php?u=$u'>$langEditUser $u_account</a>&nbsp;&nbsp;&nbsp;";
    }
    $tool_content .= "<a href='index.php'>$langBackAdmin</a>.<br />\n";
}

function get_uid_statut($u)
{
	global $mysqlMainDb;

	if ($r = mysql_fetch_row(db_query("SELECT statut FROM user WHERE user_id = '".mysql_real_escape_string($u)."'",	$mysqlMainDb)))
	{
		return $r[0];
	}
	else
	{
		return FALSE;
	}
}

draw($tool_content,3);
