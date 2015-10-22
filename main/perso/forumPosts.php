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



/**
 * Personalised ForumPosts Component, eClass Personalised
 *
 * @author Evelthon Prodromou <eprodromou@upnet.gr>
 * @version $Id$
 * @package eClass Personalised
 *
 * @abstract This component populates the Forum Posts block on the user's personalised
 * interface. It is based on the diploma thesis of Evelthon Prodromou.
 *
 */

/**
 * Function getUserForumPosts
 *
 * Populates an array with data regarding the user's personalised forum posts
 *
 * @param array $param
 * @param string $type (data, html)
 * @return array
 */
function getUserForumPosts($param, $type)
{
	global $uid;

	$uid				= $param['uid'];
	$lesson_code		= $param['lesson_code'];
	$max_repeat_val		= $param['max_repeat_val'];
	$lesson_title		= $param['lesson_titles'];
	$lesson_code		= $param['lesson_code'];
	$lesson_professor	= $param['lesson_professor'];

	$last_month = strftime('%Y %m %d', strtotime('now -1 month'));
	$forum_query_new = createForumQueries($last_month);

	$forumPosts = array();
	
	for ($i=0; $i < $max_repeat_val; $i++) {
		$mysql_query_result = db_query($forum_query_new, $lesson_code[$i]);
		if ($num_rows = mysql_num_rows($mysql_query_result) > 0) {
			$forumData = array();
			$forumSubData = array();
			$forumContent = array();
			array_push($forumData, $lesson_title[$i]);
			array_push($forumData, $lesson_code[$i]);
		}

		while ($myForumPosts = mysql_fetch_row($mysql_query_result)) {
			if ($myForumPosts){
				array_push($forumContent, $myForumPosts);
			}
		}
		if ($num_rows > 0) {
			array_push($forumSubData, $forumContent);
			array_push($forumData, $forumSubData);
			array_push($forumPosts, $forumData);
		}
	}

	if($type == "html") {
		return forumHtmlInterface($forumPosts);
	} elseif ($type == "data") {
		return $forumPosts;
	}
}


/**
 * Function forumHtmlInterface
 *
 * Generates html content for the Forum Posts block of eClass personalised.
 *
 * @param array $data
 * @return string HTML content for the documents block
 * @see function getUserForumPosts()
 */
function forumHtmlInterface($data)
{
	global $langNoPosts, $langMore, $urlServer;

	$content = "";
	if($numOfLessons = count($data) > 0) {
		$content .= "<table width='100%'>";
		$numOfLessons = count($data);
		for ($i=0; $i <$numOfLessons; $i++) {
			$content .= "<tr><td class='sub_title1'>".q($data[$i][0])."</td></tr>";
			$iterator =  count($data[$i][2][0]);
			for ($j=0; $j < $iterator; $j++){
				$url = $urlServer."modules/phpbb/viewtopic.php?course=".$data[$i][1]."&amp;topic=".$data[$i][2][0][$j][2]."&amp;forum=".$data[$i][2][0][$j][0]."&amp;s=".$data[$i][2][0][$j][4];
				$content .= "<tr><td><ul class='custom_list'><li><a href='$url'>
				<b>".q($data[$i][2][0][$j][3])." (".nice_format(date("Y-m-d", strtotime($data[$i][2][0][$j][5]))).")</b>
                                </a><div class='smaller grey'><b>".q($data[$i][2][0][$j][6]." ".$data[$i][2][0][$j][7]).
                                "</b></div><div class='smaller'>" .
                                ellipsize(q(strip_tags($data[$i][2][0][$j][8])), 150,
                                                     "<b>&nbsp;...<a href='$url'>[$langMore]</a></b>") .
                                "</div></li></ul></td></tr>";
			}
		}
		$content .= "</table>";
	} else {
		$content .= "<p class='alert1'>$langNoPosts</p>";
	}
	return $content;
}


/**
 * Function createForumQueries
 *
 * Creates needed queries used by getUserForumPosts()
 *
 * @param string $dateVar
 * @return string SQL query
 */
function createForumQueries($dateVar){

        $forum_query = 'SELECT forums.forum_id,
                               forums.forum_name,
                               topics.topic_id,
                               topics.topic_title,
                               topics.topic_replies,
                               posts.post_time,
                               posts.nom,
                               posts.prenom,
                               posts_text.post_text
                        FROM   forums,
                               topics,
                               posts,
                               posts_text,
                               accueil
                        WHERE  CONCAT(topics.topic_title, posts_text.post_text) != \'\'
                               AND forums.forum_id = topics.forum_id
                               AND posts.forum_id = forums.forum_id
                               AND posts.post_id = posts_text.post_id
                               AND posts.topic_id = topics.topic_id
                               AND DATE_FORMAT(posts.post_time, \'%Y %m %d\') >= "'.$dateVar.'"
                               AND accueil.visible = 1
                               AND accueil.id = 9
                        ORDER BY posts.post_time LIMIT 15';

	return $forum_query;
}
