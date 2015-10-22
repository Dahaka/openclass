<?php
/* ========================================================================
 * Open eClass 2.8
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
 * ======================================================================== */

/*
 * Perso Component
 *
 * @author Evelthon Prodromou <eprodromou@upnet.gr>
 * @version $Id$
 *
 * @abstract This component is the central controller of eclass personalised.
 * It controls personalisation and initialises several variables used by it.
 *
 * It is based on the diploma thesis of Evelthon Prodromou
 *
 */

if (!isset($_SESSION['uid'])) {
    die("Unauthorized Access!");
    exit;
}

$subsystem = MAIN;

include "$webDir/include/lib/textLib.inc.php";
include "$webDir/include/lib/fileDisplayLib.inc.php";
//include personalised component files (announcemets.php etc.) from /modules/perso
include "$webDir/main/perso/lessons.php";
include "$webDir/main/perso/assignments.php";
include "$webDir/main/perso/announcements.php";
include "$webDir/main/perso/documents.php";
include "$webDir/main/perso/agenda.php";
include "$webDir/main/perso/forumPosts.php";
include "$webDir/include/lib/mediaresource.factory.php";

$_user['persoLastLogin'] = last_login($uid);
$_user['lastLogin'] = str_replace('-', ' ', $_user['persoLastLogin']);

//	BEGIN Get user's lesson info]=====================================================
$user_lesson_info = getUserLessonInfo($uid, "html");
//	END Get user's lesson info]=====================================================

//if user is registered to at least one lesson
if ($user_lesson_info[0][0] > 0) {
	// BEGIN - Get user assignments
	$param = array(	'uid'	=> $uid,
	'max_repeat_val' 	=> $user_lesson_info[0][0], //max repeat val (num of lessons)
	'lesson_titles'	=> $user_lesson_info[0][1],
	'lesson_code'	=> $user_lesson_info[0][2],
	'lesson_professor'	=> $user_lesson_info[0][3],
	'lesson_statut'		=> $user_lesson_info[0][4],
	'lesson_id'             => $user_lesson_info[0][8]
	);
	$user_assignments = getUserAssignments($param, "html");
	//END - Get user assignments

	// BEGIN - Get user announcements
	$param = array(	'uid'	=> $uid,
	'max_repeat_val' 	=> $user_lesson_info[0][0], //max repeat val (num of lessons)
	'lesson_titles'	=> $user_lesson_info[0][1],
	'lesson_code'	=> $user_lesson_info[0][2],
	'lesson_professor'	=> $user_lesson_info[0][3],
	'lesson_statut'		=> $user_lesson_info[0][4],
	'usr_lst_login'		=> $_user["lastLogin"],
	'usr_memory'		=> $user_lesson_info[0][5],
        'lesson_id'             => $user_lesson_info[0][8]
	);

	$user_announcements = getUserAnnouncements($param, 'html');
	// END - Get user announcements

	// BEGIN - Get user documents

	$param = array(	'uid'	=> $uid,
	'max_repeat_val' 	=> $user_lesson_info[0][0], //max repeat val (num of lessons)
	'lesson_titles'	=> $user_lesson_info[0][1],
	'lesson_code'	=> $user_lesson_info[0][2],
	'lesson_professor'	=> $user_lesson_info[0][3],
	'lesson_statut'		=> $user_lesson_info[0][4],
	'usr_lst_login'		=> $_user["lastLogin"],
	'usr_memory'		=> $user_lesson_info[0][6]
	);

	$user_documents = getUserDocuments($param, "html");

	// END - Get user documents

	//BEGIN - Get user agenda
	$param = array(	'uid'	=> $uid,
	'max_repeat_val' 	=> $user_lesson_info[0][0], //max repeat val (num of lessons)
	'lesson_titles'	=> $user_lesson_info[0][1],
	'lesson_code'	=> $user_lesson_info[0][2],
	'lesson_professor'	=> $user_lesson_info[0][3],
	'lesson_statut'		=> $user_lesson_info[0][4],
	'usr_lst_login'		=> $_user["lastLogin"]
	);
	$user_agenda = getUserAgenda($param, "html");

	//END - Get user agenda

	//BEGIN - Get user forum posts
	$param = array(	'uid'	=> $uid,
	'max_repeat_val' 	=> $user_lesson_info[0][0], //max repeat val (num of lessons)
	'lesson_titles'	=> $user_lesson_info[0][1],
	'lesson_code'	=> $user_lesson_info[0][2],
	'lesson_professor'	=> $user_lesson_info[0][3],
	'lesson_statut'		=> $user_lesson_info[0][4],
	'usr_lst_login'		=> $_user["lastLogin"],
	'usr_memory'		=> $user_lesson_info[0][7]//forum memory
	);
	$user_forumPosts = getUserForumPosts($param, "html");
	//END - Get user forum posts

} else {
	//show a "-" in all blocks if the user is not enrolled to any lessons
	// (except of the lessons block which is handled before)
	$user_assignments = "<p>-</p>";
	$user_announcements = "<p>-</p>";
	$user_documents = "<p>-</p>";
	$user_agenda = "<p>-</p>";
	$user_forumPosts = "<p>-</p>";
}

// ==  BEGIN create array with personalised content
$perso_tool_content = array(
'lessons_content' 	=> $user_lesson_info[1],
'assigns_content' 	=> $user_assignments,
'announce_content' 	=> $user_announcements,
'docs_content'		=> $user_documents,
'agenda_content' 	=> $user_agenda,
'forum_content' 	=> $user_forumPosts
);

// == END create array with personalised content