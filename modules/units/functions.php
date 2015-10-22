<?php

require_once '../../include/lib/mediaresource.factory.php';
require_once '../../include/lib/multimediahelper.class.php';

//
// Units utility functions	
//

// Process resource actions
function process_actions()
{
        global $tool_content, $id, $mysqlMainDb, $langResourceCourseUnitDeleted, $langResourceUnitModified,
               $cours_id, $code_cours;
        
        // refresh course metadata
        require_once '../course_metadata/CourseXML.php';
	
        if (isset($_REQUEST['edit'])) {
                $res_id = intval($_GET['edit']);
                if ($id = check_admin_unit_resource($res_id)) {
                        return edit_res($res_id);
                }
        } elseif (isset($_REQUEST['edit_res_submit'])) { // edit resource
                $res_id = intval($_REQUEST['resource_id']);	
                if ($id = check_admin_unit_resource($res_id)) {
                        @$restitle = autoquote(trim($_REQUEST['restitle']));
                        $rescomments = autoquote(trim($_REQUEST['rescomments']));
                        $result = db_query("UPDATE unit_resources SET
                                        title = $restitle,
                                        comments = $rescomments
                                        WHERE unit_id = $id AND id = $res_id");
                         CourseXMLElement::refreshCourse($cours_id, $code_cours);
                }
                $tool_content .= "<p class='success'>$langResourceUnitModified</p>";
        } elseif (isset($_REQUEST['del'])) { // delete resource from course unit
                $res_id = intval($_GET['del']);
                if ($id = check_admin_unit_resource($res_id)) {
                        db_query("DELETE FROM unit_resources WHERE id = $res_id", $mysqlMainDb);
                        CourseXMLElement::refreshCourse($cours_id, $code_cours);
                        $tool_content .= "<p class='success'>$langResourceCourseUnitDeleted</p>";
                }
        } elseif (isset($_REQUEST['vis'])) { // modify visibility in text resources only 
                $res_id = intval($_REQUEST['vis']);
                if ($id = check_admin_unit_resource($res_id)) {
                        $sql = db_query("SELECT `visibility` FROM unit_resources WHERE id=$res_id");
                        list($vis) = mysql_fetch_row($sql);
                        $newvis = ($vis == 'v')? 'i': 'v';
                        db_query("UPDATE unit_resources SET visibility = '$newvis' WHERE id = $res_id");
                        CourseXMLElement::refreshCourse($cours_id, $code_cours);
                }
        } elseif (isset($_REQUEST['down'])) { // change order down
                $res_id = intval($_REQUEST['down']);
                if ($id = check_admin_unit_resource($res_id)) {
                        move_order('unit_resources', 'id', $res_id, 'order', 'down',
                                   "unit_id=$id");
                }
        } elseif (isset($_REQUEST['up'])) { // change order up
                $res_id = intval($_REQUEST['up']);
                if ($id = check_admin_unit_resource($res_id)) {
                        move_order('unit_resources', 'id', $res_id, 'order', 'up',
                                   "unit_id=$id");
                }
        }
	return '';
}


// Check that a specified resource id belongs to a resource in the
// current course, and that the user is an admin in this course.
// Return the id of the unit or false if user is not an admin 
function check_admin_unit_resource($resource_id)
{
	global $cours_id, $is_editor;
	
	if ($is_editor) {
		$q = db_query("SELECT course_units.id FROM course_units,unit_resources WHERE
			course_units.course_id = $cours_id AND course_units.id = unit_resources.unit_id
			AND unit_resources.id = $resource_id");
		if (mysql_num_rows($q) > 0) {
			list($unit_id) = mysql_fetch_row($q);
			return $unit_id;
		}
	}
	return false;
}


// Display resources for unit with id=$id
function show_resources($unit_id)
{
	global $tool_content, $max_resource_id;
	$req = db_query("SELECT * FROM unit_resources WHERE unit_id = $unit_id AND `order` >= 0 ORDER BY `order`");
	if (mysql_num_rows($req) > 0) {
		list($max_resource_id) = mysql_fetch_row(db_query("SELECT id FROM unit_resources
                                WHERE unit_id = $unit_id ORDER BY `order` DESC LIMIT 1"));
		$tool_content .= "
        <table class='tbl_alt_bordless' width='99%'>";
		while ($info = mysql_fetch_array($req)) {
			$info['comments'] = standard_text_escape($info['comments']);
			show_resource($info);
		}	
		$tool_content .= "
        </table>\n";
	}
}


function show_resource($info)
{
        global $tool_content, $langUnknownResType, $is_editor;
	
        if ($info['visibility'] == 'i' and !$is_editor) {
                return;
        }
        switch ($info['type']) {
                case 'doc':
                        $tool_content .= show_doc($info['title'], $info['comments'], $info['id'], $info['res_id']);
                        break;
                case 'text':
                        $tool_content .= show_text($info['comments'], $info['id'], $info['visibility']);
                        break;
                case 'description':
                        $tool_content .= show_description($info['title'], $info['comments'], $info['id'], $info['res_id'], $info['visibility']);
                        break;
		case 'lp':
                        $tool_content .= show_lp($info['title'], $info['comments'], $info['id'], $info['res_id']);
                        break;
		case 'video':
		case 'videolinks':                    
                        $tool_content .= show_video($info['type'], $info['title'], $info['comments'], $info['id'], $info['res_id'], $info['visibility']);
                        break;
                case 'videolinkcategory':                    
                        $tool_content .= show_videocat($info['type'], $info['title'], $info['comments'], $info['id'], $info['res_id'], $info['visibility']);
                        break;
		case 'exercise':
                        $tool_content .= show_exercise($info['title'], $info['comments'], $info['id'], $info['res_id'], $info['visibility']);
                        break;
		case 'work':
                        $tool_content .= show_work($info['title'], $info['comments'], $info['id'], $info['res_id'], $info['visibility']);
                        break;
		case 'topic':
		case 'forum':
                        $tool_content .= show_forum($info['type'], $info['title'], $info['comments'], $info['id'], $info['res_id'], $info['visibility']);
                        break;
		case 'wiki':
                        $tool_content .= show_wiki($info['title'], $info['comments'], $info['id'], $info['res_id'], $info['visibility']);
                        break;
		case 'link':
                        $tool_content .= show_link($info['title'], $info['comments'], $info['id'], $info['res_id'], $info['visibility']);
                        break;
		case 'linkcategory':
                        $tool_content .= show_linkcat($info['title'], $info['comments'], $info['id'], $info['res_id'], $info['visibility']);
                        break;
		case 'ebook':
                        $tool_content .= show_ebook($info['title'], $info['comments'], $info['id'], $info['res_id'], $info['visibility']);
                        break;
		case 'section':
                        $tool_content .= show_ebook_section($info['title'], $info['comments'], $info['id'], $info['res_id'], $info['visibility']);
                        break;
		case 'subsection':
                        $tool_content .= show_ebook_subsection($info['title'], $info['comments'], $info['id'], $info['res_id'], $info['visibility']);
                        break;
                default:
                        $tool_content .= $langUnknownResType;
        }
}


// display resource documents
function show_doc($title, $comments, $resource_id, $file_id)
{
        global $mysqlMainDb, $is_editor, $cours_id,
               $langWasDeleted, $urlServer, $id,
               $code_cours, $themeimg;

        $title = htmlspecialchars($title);
        $r = db_query("SELECT * FROM `$mysqlMainDb`.document
	               WHERE course_id = $cours_id AND id =" . intval($file_id));
        if (mysql_num_rows($r) == 0) {
                if (!$is_editor) {
                        return '';
                }
                $status = 'del';
                $image = $themeimg.'/delete.png';
                $link = "<span class='invisible'>".q($title)." ($langWasDeleted)</span>";
        } else {
                $file = mysql_fetch_array($r, MYSQL_ASSOC);
                $status = $file['visibility'];                
                if (!$is_editor and (!resource_access($status, $file['public']))) {
                    return '';
                }
                if ($file['format'] == '.dir') {
                        $image = $themeimg.'/folder.png';
                        $link = "<a href='{$urlServer}modules/document/document.php?course=$code_cours&amp;openDir=$file[path]&amp;unit=$id'>";
                } else {
                        $image = '../document/img/' .
                                choose_image('.' . $file['format']);
                        $link = "<a href='" . file_url($file['path'], $file['filename']) . "' target='_blank'>";
                }
        }
	$class_vis = ($status == 'i' or $status == 'del')? ' class="invisible"': ' class="even"';
        if (!empty($comments)) {
                $comment = '<br />' . $comments;
        } else {
                $comment = '';
        }
        return "
        <tr$class_vis>
          <td width='1'>$link<img src='$image' alt=''></a></td>
          <td align='left'>$link$title</a>$comment</td>" .
                actions('doc', $resource_id, $status) .
                '</tr>';
}


// display resource text
function show_text($comments, $resource_id, $visibility)
{
        global $tool_content;

        $class_vis = ($visibility == 'i')? ' class="invisible"': ' class="even"';
	$comments = mathfilter($comments, 12, "../../courses/mathimg/");
        $tool_content .= "
        <tr$class_vis>
          <td colspan='2'>$comments</td>" .
		actions('text', $resource_id, $visibility) .
                "
        </tr>";
}


// display course description resource
function show_description($title, $comments, $id, $res_id, $visibility)
{
        global $tool_content;

	$comments = mathfilter($comments, 12, "../../courses/mathimg/");
        $tool_content .= "
        <tr>
          <td colspan='2'>
            <div class='title'>" . q($title) .  "</div>
            <div class='content'>$comments</div>
          </td>" .  actions('description', $id, $visibility, $res_id) .  "
        </tr>";
}

// display resource learning path
function show_lp($title, $comments, $resource_id, $lp_id)
{
	global $id, $tool_content, $mysqlMainDb, $urlServer, $is_editor,
               $langWasDeleted, $currentCourseID, $code_cours, $themeimg, $langInactiveModule;

        $module_visible = visible_module(23); // checks module visibility
        if (!$module_visible and !$is_editor) {
                return '';
        }        
        $comment_box = $class_vis = $imagelink = $link = '';
        $class_vis = (!$module_visible)?
                     ' class="invisible"': ' class="even"';                
	
        $title = htmlspecialchars($title);
	$r = db_query("SELECT * FROM lp_learnPath WHERE learnPath_id = $lp_id",
                      $currentCourseID);
	if (mysql_num_rows($r) == 0) { // check if lp was deleted
		if (!$is_editor) {
			return '';
		} else {
			$status = 'del';
			$imagelink = "<img src='$themeimg/delete.png' />";
			$link = "<span class='invisible'>$title ($langWasDeleted)</span>";
		}
	} else {
                $lp = mysql_fetch_array($r, MYSQL_ASSOC);
		$status = ($lp['visibility'] == 'SHOW')? 'v': 'i';
		$link = "<a href='${urlServer}modules/learnPath/learningPath.php?course=$code_cours&amp;path_id=$lp_id&amp;unit=$id'>";
                if (!$module_visible) {
			$link .= " <i>($langInactiveModule)</i>";
                }
		$imagelink = "<img src='$themeimg/lp_" .
			($status == 'i'? 'off': 'on') . ".png' />";
	}
        if ($status != 'v' and !$is_editor) {
			return '';
        }

        if (!empty($comments)) {
                $comment_box = "<br />$comments";
        } else {
                $comment_box = '';
        }        
	return "
        <tr$class_vis>
          <td width='1'>$imagelink</a></td>
          <td>$link$title</a>$comment_box</td>" .
		actions('lp', $resource_id, $status) .  '
        </tr>';
}


// display resource video
function show_video($table, $title, $comments, $resource_id, $video_id, $visibility)
{
        global $is_editor, $currentCourseID, $tool_content, $themeimg, $langInactiveModule;
        
        $module_visible = visible_module(4); // checks module visibility
        if (!$module_visible and !$is_editor) {
                return '';
        }        
        $comment_box = $class_vis = $imagelink = $link = '';
        
        $result = db_query("SELECT * FROM $table WHERE id=$video_id",
                           $currentCourseID);
        if ($result and mysql_num_rows($result) > 0) {
                $row = mysql_fetch_array($result, MYSQL_ASSOC);
                if (!$is_editor and (!resource_access(1, $row['public']))) {
                    return '';
                }
                $status = $row['public'];
                $row['course_id'] = $GLOBALS['cours_id'];
                
                if ($table == 'video') {
                    $vObj = MediaResourceFactory::initFromVideo($row);
                    $videolink = MultimediaHelper::chooseMediaAhref($vObj);
                } else {
                    $vObj = MediaResourceFactory::initFromVideoLink($row);
                    $videolink = MultimediaHelper::chooseMedialinkAhref($vObj);
                }
                if (!$module_visible) {
			$videolink .= " <i>($langInactiveModule)</i>";
                }
                $imagelink = "<img src='$themeimg/videos_" .
                             ($visibility == 'i'? 'off': 'on') . ".png' />";
        } else {
                if (!$is_editor) {
                        return;
                }
                $videolink = $title;
                $imagelink = "<img src='$themeimg/delete.png' />";
                $visibility = 'del';
        }        


        if (!empty($comments)) {
                $comment_box = "<br />$comments";
        } else {
                $comment_box = "";
        }
        $class_vis = ($visibility == 'i' or !$module_visible or $status == 'del')? ' class="invisible"': ' class="even"';
        $tool_content .= "
        <tr$class_vis>
          <td width='1'>$imagelink</td>
          <td>$videolink $comment_box</td>" .  actions('video', $resource_id, $visibility) . "
        </tr>";
}


// display resource video category
function show_videocat($table, $title, $comments, $resource_id, $videolinkcat_id, $visibility)
{   
    	global $is_editor, $langWasDeleted, $cours_id, $currentCourseID, $themeimg, $langInactiveModule;
	
	$content = $linkcontent = '';
        $module_visible = visible_module(2); // checks module visibility
        
        if (!$module_visible and !$is_editor) {
                       return '';
        }        
	$comment_box = $class_vis = $imagelink = $link = '';
        $class_vis = ($visibility == 'i' or !$module_visible)?
                     ' class="invisible"': ' class="even"';	
        $title = htmlspecialchars($title);
	$sql = db_query("SELECT * FROM `$currentCourseID`.video_category WHERE id = $videolinkcat_id");
		while ($vlcat = mysql_fetch_array($sql)) {
                    $content .= "
                    <tr$class_vis>
                      <td width='1'><img src='$themeimg/folder_open.png' /></td>
                      <td>" . q($vlcat['name']);
                    if (!empty($vlcat['description'])) {
                            $comment_box = "<br />$vlcat[description]";
                    } else {
                            $comment_box = '';
                    }
                    foreach (array('video', 'videolinks') as $table) {    
                        $sql2 = db_query("SELECT * FROM `$currentCourseID`.$table WHERE category = $vlcat[id]");
                        while ($row = mysql_fetch_array($sql2, MYSQL_ASSOC)) {                            
                            if (!$is_editor and (!resource_access(1, $row['public']))) {
                                return '';
                            }
                            $status = $row['public'];
                            $row['course_id'] = $GLOBALS['cours_id'];

                            if ($table == 'video') {
                                $vObj = MediaResourceFactory::initFromVideo($row);
                                $videolink = MultimediaHelper::chooseMediaAhref($vObj);
                            } else {
                                $vObj = MediaResourceFactory::initFromVideoLink($row);
                                $videolink = MultimediaHelper::chooseMedialinkAhref($vObj);
                            }
                            if (!$module_visible) {
                                    $videolink .= " <i>($langInactiveModule)</i>";
                            }
                            $imagelink = "<img src='$themeimg/videos_" .
                                         ($visibility == 'i'? 'off': 'on') . ".png' />";
                            if (!empty($comments)) {
                                    $comment_box = "<br />$comments";
                            } else {
                                    $comment_box = "";
                            }
                            $class_vis = ($visibility == 'i' or !$module_visible or $status == 'del')? ' class="invisible"': ' class="even"';                            
                            $ltitle = q(($row['titre'] == '')? $row['url']: $row['titre']);
                            $linkcontent .= "<br />$imagelink&nbsp;&nbsp;$videolink</a>";
                            if (!$module_visible) {
                                    $linkcontent .= " <i>($langInactiveModule)</i>";
                            }
                        }
                    }
		}
	
	return $content . $comment_box . $linkcontent .'
           </td>'. actions('videolinkcategory', $resource_id, $visibility) .
		'</tr>';
}

// display resource work (assignment)
function show_work($title, $comments, $resource_id, $work_id, $visibility)
{
	global $id, $tool_content, $mysqlMainDb, $urlServer, $is_editor,
               $langWasDeleted, $currentCourseID, $code_cours, $themeimg, $langInactiveModule;

        $module_visible = visible_module(5); // checks module visibility
        if (!$module_visible and !$is_editor) {
                return '';
        }        
        $comment_box = $class_vis = $imagelink = $link = '';
        $class_vis = ($visibility == 'i' or !$module_visible)?
                     ' class="invisible"': ' class="even"';        
	
        $title = htmlspecialchars($title);
	$r = db_query("SELECT * FROM assignments WHERE id = $work_id",
                      $currentCourseID);
	if (mysql_num_rows($r) == 0) { // check if it was deleted
		if (!$is_editor) {
			return '';
		} else {
			$status = 'del';
			$imagelink = "<img src='$themeimg/delete.png' />";
			$exlink = "<span class='invisible'>$title ($langWasDeleted)</span>";
		}
	} else {
                $work = mysql_fetch_array($r, MYSQL_ASSOC);
		$link = "<a href='${urlServer}modules/work/work.php?course=$code_cours&amp;id=$work_id&amp;unit=$id'>";
                $exlink = $link . "$title</a>";
                if (!$module_visible) {
			$exlink .= " <i>($langInactiveModule)</i>";
                }
		$imagelink = $link .
                        "<img src='$themeimg/assignments_" .
			($visibility == 'i'? 'off': 'on') . ".png' /></a>";
	}	

        if (!empty($comments)) {
                $comment_box = "<br />$comments";
	} else {
                $comment_box = '';
        }
	return "
        <tr$class_vis>
          <td width='1'>$imagelink</td>
          <td>$exlink $comment_box</td>" .
		actions('lp', $resource_id, $visibility) .  '
        </tr>';
}


// display resource exercise
function show_exercise($title, $comments, $resource_id, $exercise_id, $visibility)
{
	global $id, $tool_content, $mysqlMainDb, $urlServer, $is_editor,
               $langWasDeleted, $currentCourseID, $code_cours, $themeimg, $langInactiveModule;

        $module_visible = visible_module(10); // checks module visibility
        if (!$module_visible and !$is_editor) {
                return '';
        }        
        $comment_box = $class_vis = $imagelink = $link = '';        
	
        $title = htmlspecialchars($title);
	$r = db_query("SELECT * FROM exercices WHERE id = $exercise_id",
                      $currentCourseID);
	if (mysql_num_rows($r) == 0) { // check if it was deleted
		if (!$is_editor) {
			return '';
		} else {
			$status = 'del';
			$imagelink = "<img src='$themeimg/delete.png' />";
			$exlink = "<span class='invisible'>$title ($langWasDeleted)</span>";
		}
	} else {
                $exercise = mysql_fetch_array($r, MYSQL_ASSOC);
                $status = $exercise['active'];
                if (!$is_editor and (!resource_access($status, $exercise['public']))) {
                    return '';
                }                
		$link = "<a href='${urlServer}modules/exercice/exercice_submit.php?course=$code_cours&amp;exerciseId=$exercise_id&amp;unit=$id'>";
                $exlink = $link . "$title</a>";
                if (!$module_visible) {
			$exlink .= " <i>($langInactiveModule)</i>";
                }
		$imagelink = $link .
                        "<img src='$themeimg/exercise_" .
			($visibility == 'i'? 'off': 'on') . ".png' /></a>";
	}
	$class_vis = ($status == '0' or $visibility == 'i' or !$module_visible)? ' class="invisible"': ' class="even"';

        if (!empty($comments)) {
                $comment_box = "<br />$comments";
	} else {
                $comment_box = "";
        }        
	return "
        <tr$class_vis>
          <td width='3'>$imagelink</td>
          <td>$exlink $comment_box</td>" .  actions('lp', $resource_id, $visibility) . "
        </tr>";
}


// display resource forum
function show_forum($type, $title, $comments, $resource_id, $ft_id, $visibility)
{
	global $id, $tool_content, $mysqlMainDb, $urlServer, $is_editor,
               $currentCourseID, $code_cours, $themeimg, $langInactiveModule;

        $module_visible = visible_module(9); // checks module visibility
        if (!$module_visible and !$is_editor) {
                return '';
        }
        
	$comment_box = '';
	$class_vis = ($visibility == 'i' or !$module_visible)?
                     ' class="invisible"': ' class="even"';
        $title = htmlspecialchars($title);
	if ($type == 'forum') {
		$link = "<a href='${urlServer}modules/phpbb/viewforum.php?course=$code_cours&amp;forum=$ft_id&amp;unit=$id'>";
                $forumlink = $link . "$title</a>";
	} else {
		$r = db_query("SELECT forum_id FROM topics WHERE topic_id = $ft_id", $currentCourseID);
		list($forum_id) = mysql_fetch_array($r);
		$link = "<a href='${urlServer}modules/phpbb/viewtopic.php?course=$code_cours&amp;topic=$ft_id&amp;forum=$forum_id&amp;unit=$id'>";
                $forumlink = $link . "$title</a>";
                if (!$module_visible) {
                        $forumlink .= "<i>($langInactiveModule)</i>";
                }
	}

	$imagelink = $link . "<img src='$themeimg/forum_" .
			($visibility == 'i'? 'off': 'on') . ".png' /></a>";


        if (!empty($comments)) {
                $comment_box = "<br />$comments";
	} else {
                $comment_box = '';
        }

	return "
        <tr$class_vis>
          <td width='1'>$imagelink</td>
          <td>$forumlink $comment_box</td>" .
		actions('forum', $resource_id, $visibility) .  '
        </tr>';
}


// display resource wiki
function show_wiki($title, $comments, $resource_id, $wiki_id, $visibility)
{
	global $id, $tool_content, $mysqlMainDb, $urlServer, $is_editor,
               $langWasDeleted, $langInactiveModule, $currentCourseID, $code_cours, $themeimg;
      
        $module_visible = visible_module(26); // checks module visibility
        
        if (!$module_visible and !$is_editor) {
                       return '';
        }        
        
	$comment_box = $imagelink = $link = $class_vis = '';
	$class_vis = ($visibility == 'i' or !$module_visible)?
                     ' class="invisible"': ' class="even"';
        $title = htmlspecialchars($title);
	$r = db_query("SELECT * FROM wiki_properties WHERE id = $wiki_id",
                      $currentCourseID);
	if (mysql_num_rows($r) == 0) { // check if it was deleted
		if (!$is_editor) {                
			return '';
		} else {
			$status = 'del';
			$imagelink = "<img src='$themeimg/delete.png' />";
			$wikilink = "<span class='invisible'>$title ($langWasDeleted)</span>";
		}
	} else {
                $wiki = mysql_fetch_array($r, MYSQL_ASSOC);
		$link = "<a href='${urlServer}modules/wiki/page.php?course=$code_cours&amp;wikiId=$wiki_id&amp;action=show&amp;unit=$id'>";
                $wikilink = $link . "$title</a>";
                if (!$module_visible) {
			$wikilink .= " <i>($langInactiveModule)</i>";
                }
		$imagelink = $link .
                        "<img src='$themeimg/wiki_" .
			($visibility == 'i'? 'off': 'on') . ".png' /></a>";
	}

        if (!empty($comments)) {
                $comment_box = "<br />$comments";
	} else {
                $comment_box = '';
        }
	return "
        <tr$class_vis>
          <td width='1'>$imagelink</td>
          <td>$wikilink $comment_box</td>" .
		actions('wiki', $resource_id, $visibility) .  '
        </tr>';
}


// display resource link
function show_link($title, $comments, $resource_id, $link_id, $visibility)
{
	global $tool_content, $mysqlMainDb, $urlServer, $is_editor,
               $langWasDeleted, $cours_id, $currentCourseID, $themeimg, $langInactiveModule;
        
        $module_visible = visible_module(2); // checks module visibility
        
        if (!$module_visible and !$is_editor) {
                       return '';
        }        
	$comment_box = $class_vis = $imagelink = $link = '';
        $class_vis = ($visibility == 'i' or !$module_visible)?
                     ' class="invisible"': ' class="even"';
        $title = htmlspecialchars($title);
	$r = db_query("SELECT * FROM `$mysqlMainDb`.link WHERE course_id = $cours_id AND id = $link_id");
	if (mysql_num_rows($r) == 0) { // check if it was deleted
		if (!$is_editor) {
			return '';
		} else {
			$status = 'del';
			$imagelink = "<img src='$themeimg/delete.png' />";
			$exlink = "<span class='invisible'>" . q($title) . " ($langWasDeleted)</span>";
		}
	} else {
                $l = mysql_fetch_array($r, MYSQL_ASSOC);
                $eurl = urlencode($l['url']);
		$link = "<a href='${urlServer}modules/link/go.php?c=$currentCourseID&amp;id=$link_id&amp;url=$eurl' target='_blank'>";
                if ($title == '') {
                        $title = q($l['url']);
                }
                $exlink = $link . "$title</a>";
                if (!$module_visible) {
			$exlink .= " <i>($langInactiveModule)</i>";
                }
		$imagelink = $link .
                        "<img src='$themeimg/links_" .
			($visibility == 'i'? 'off': 'on') . ".png' /></a>";
	}	

        if (!empty($comments)) {
                $comment_box = '<br />' . standard_text_escape($comments);
	} else {
                $comment_box = '';
        }

	return "
        <tr$class_vis>
          <td>$imagelink</td>
          <td>$exlink $comment_box</td>" .  actions('link', $resource_id, $visibility) . "
        </tr>";
}

// display resource link category
function show_linkcat($title, $comments, $resource_id, $linkcat_id, $visibility)
{
	global $id, $tool_content, $mysqlMainDb, $urlServer, $is_editor,
               $langWasDeleted, $cours_id, $currentCourseID, $themeimg, $langInactiveModule;
	
	$content = $linkcontent = '';
        $module_visible = visible_module(2); // checks module visibility
        
        if (!$module_visible and !$is_editor) {
                       return '';
        }        
	$comment_box = $class_vis = $imagelink = $link = '';
        $class_vis = ($visibility == 'i' or !$module_visible)?
                     ' class="invisible"': ' class="even"';	
        $title = htmlspecialchars($title);
	$sql = db_query("SELECT * FROM `$mysqlMainDb`.link_category WHERE course_id = $cours_id AND id = $linkcat_id");
	if (mysql_num_rows($sql) == 0) { // check if it was deleted
		if (!$is_editor) {
			return '';
		} else {
			$status = 'del';
			$imagelink = "<img src='$themeimg/delete.png' />";
			$exlink = "<span class='invisible'>" . q($title) . " ($langWasDeleted)</span>";
		}
	} else {                
		while ($lcat = mysql_fetch_array($sql)) {
			$content .= "
                        <tr$class_vis>
                          <td width='1'><img src='$themeimg/folder_open.png' /></td>
                          <td>" . q($lcat['name']);
			if (!empty($lcat['description'])) {
				$comment_box = "<br />$lcat[description]";
			} else {
                                $comment_box = '';
                        }

			$sql2 = db_query("SELECT * FROM `$mysqlMainDb`.link WHERE course_id = $cours_id AND category = $lcat[id]");
			while ($l = mysql_fetch_array($sql2, MYSQL_ASSOC)) {
				$imagelink = "<img src='$themeimg/links_" .
                                             ($visibility == 'i'? 'off': 'on') . ".png' />";
                                $ltitle = q(($l['title'] == '')? $l['url']: $l['title']);
				$linkcontent .= "<br />$imagelink&nbsp;&nbsp;<a href='${urlServer}modules/link/go.php?c=$currentCourseID&amp;id=$l[id]&amp;url=$l[url]' target='_blank'>$ltitle</a>";
                                if (!$module_visible) {
                                        $linkcontent .= " <i>($langInactiveModule)</i>";
                                }
			}
		}
	}
	return $content . $comment_box . $linkcontent .'
           </td>'. actions('linkcategory', $resource_id, $visibility) .
		'</tr>';
}


// display resource ebook
function show_ebook($title, $comments, $resource_id, $ebook_id, $visibility)
{
	global $id, $tool_content, $mysqlMainDb, $urlServer, $is_editor,
               $langWasDeleted, $currentCourseID, $themeimg, $langInactiveModule;
	
        $module_visible = visible_module(18); // checks module visibility
        
        if (!$module_visible and !$is_editor) {
                       return '';
        }        
	$comment_box = $class_vis = $imagelink = $link = '';
        $class_vis = ($visibility == 'i' or !$module_visible)?
                     ' class="invisible"': ' class="even"';	
        $title = htmlspecialchars($title);
	$r = db_query("SELECT * FROM ebook WHERE id = $ebook_id", $mysqlMainDb);
	if (mysql_num_rows($r) == 0) { // check if it was deleted
		if (!$is_editor) {
			return '';
		} else {
			$status = 'del';
			$imagelink = "<img src='$themeimg/delete.png' />";
			$exlink = "<span class='invisible'>$title ($langWasDeleted)</span>";
		}
	} else {
		$link = "<a href='${urlServer}modules/ebook/show.php/$currentCourseID/$ebook_id/unit=$id'>";
                $exlink = $link . "$title</a>";
                if (!$module_visible) {
                        $exlink .= " <i>($langInactiveModule)</i>";
                }
		$imagelink = $link .
                        "<img src='$themeimg/ebook_" .
			($visibility == 'i'? 'off': 'on') . ".png' /></a>";
	}

        if (!empty($comments)) {
                $comment_box = "<br />$comments";
	} else {
                $comment_box = "";
        }

	return "
        <tr$class_vis>
          <td width='3'>$imagelink</td>
          <td>$exlink $comment_box</td>" .  actions('ebook', $resource_id, $visibility) . "
        </tr>";
}

function show_ebook_section($title, $comments, $resource_id, $section_id, $visibility)
{
	global $id, $cours_id, $mysqlMainDb;
                
	$r = db_query("SELECT ebook.id AS ebook_id, ebook_subsection.id AS ssid
				FROM ebook, ebook_section, ebook_subsection
				WHERE ebook.course_id = $cours_id AND
				    ebook_section.ebook_id = ebook.id AND
				    ebook_section.id = ebook_subsection.section_id AND
				    ebook_section.id = $section_id 
				ORDER BY CONVERT(ebook_subsection.public_id, UNSIGNED), ebook_subsection.public_id
				LIMIT 1", $mysqlMainDb);
	if (mysql_num_rows($r) == 0) { // check if it was deleted
		$deleted = true;
		$display_id = $ebook_id = false;
	} else {
		$deleted = false;
		$data = mysql_fetch_array($r, MYSQL_ASSOC);
		$ebook_id = $data['ebook_id'];
		$display_id = $section_id . ',' . $data['ssid'];
	}
	return show_ebook_resource($title, $comments, $resource_id, $ebook_id,
		                   $display_id, $visibility, $deleted);
}

function show_ebook_subsection($title, $comments, $resource_id, $subsection_id, $visibility)
{
	global $cours_id, $mysqlMainDb;
	$r = db_query("SELECT ebook.id AS ebook_id, ebook_section.id AS sid
				FROM ebook, ebook_section, ebook_subsection
				WHERE ebook.course_id = $cours_id AND
				    ebook_section.ebook_id = ebook.id AND
				    ebook_section.id = ebook_subsection.section_id AND
				    ebook_subsection.id = $subsection_id
				LIMIT 1", $mysqlMainDb);
	if (mysql_num_rows($r) == 0) { // check if it was deleted
		$deleted = true;
		$display_id = $ebook_id = false;
	} else {
		$deleted = false;
		$data = mysql_fetch_array($r, MYSQL_ASSOC);
		$ebook_id = $data['ebook_id'];
		$display_id = $data['sid'] . ',' . $subsection_id;
	}
	return show_ebook_resource($title, $comments, $resource_id, $ebook_id,
		                   $display_id, $visibility, $deleted);	
}

// display resource ebook subsection
function show_ebook_resource($title, $comments, $resource_id, $ebook_id,
		             $display_id, $visibility, $deleted)
{
	global $id, $tool_content, $mysqlMainDb, $urlServer, $is_editor,
               $langWasDeleted, $currentCourseID, $cours_id, $themeimg, $langInactiveModule;

        $module_visible = visible_module(18); // checks module visibility
        
        if (!$module_visible and !$is_editor) {
                       return '';
        }
        $comment_box = $class_vis = $imagelink = $link = '';
        $class_vis = ($visibility == 'i' or !$module_visible)?
                     ' class="invisible"': ' class="even"';	
	if ($deleted) {
		if (!$is_editor) {
			return '';
		} else {
			$status = 'del';
			$imagelink = "<img src='$themeimg/delete.png' />";
			$exlink = "<span class='invisible'>$title ($langWasDeleted)</span>";
		}
	} else {
		$link = "<a href='${urlServer}modules/ebook/show.php/$currentCourseID/$ebook_id/$display_id/unit=$id'>";
                $exlink = $link . q($title) . '</a>';
                if (!$module_visible) {
                        $exlink .= " <i>($langInactiveModule)</i>";
                }
		$imagelink = $link .
                        "<img src='$themeimg/ebook_" .
			($visibility == 'i'? 'off': 'on') . ".png' /></a>";
	}
	

        if (!empty($comments)) {
                $comment_box = "<br />$comments";
	} else {
                $comment_box = "";
        }

	return "
        <tr$class_vis>
          <td width='3'>$imagelink</td>
          <td>$exlink $comment_box</td>" .  actions('section', $resource_id, $visibility) . "
        </tr>";
}

// resource actions
function actions($res_type, $resource_id, $status, $res_id = false)
{
        global $is_editor, $langEdit, $langDelete, $langVisibility,
               $langAddToCourseHome, $langDown, $langUp, $mysqlMainDb,
               $langConfirmDelete, $code_cours, $themeimg;

        static $first = true;

	if (!$is_editor) {
		return '';
	}

        if ($res_type == 'description') {
                $icon_vis = ($status == 'v')? 'publish.png': 'unpublish.png';
                $edit_link = "edit.php?course=$code_cours&amp;numBloc=$res_id";
        } else {
                $icon_vis = ($status == 'v')? 'visible.png': 'invisible.png';
                $edit_link = "$_SERVER[SCRIPT_NAME]?course=$code_cours&amp;edit=$resource_id";
        }

        if ($status != 'del') {
                $content = "<td width='3'><a href='$edit_link'>" .
                           "<img src='$themeimg/edit.png' title='".q($langEdit)."' alt='".q($langEdit)."' /></a></td>";
        } else {
                $content = "<td width='3'>&nbsp;</td>";
        }
        $content .= "<td width='3'><a href='$_SERVER[SCRIPT_NAME]?course=$code_cours&amp;del=$resource_id'" .
                    " onClick=\"return confirmation('" . js_escape($langConfirmDelete) . "')\">" .
                    "<img src='$themeimg/delete.png' " .
                    "title='".q($langDelete)."' alt='".q($langDelete)."'></a></td>";
	 
	if ($status != 'del') {
		if (in_array($res_type, array('text', 'video', 'forum', 'topic'))) { 
			$content .= "<td width='3'><a href='$_SERVER[SCRIPT_NAME]?course=$code_cours&amp;vis=$resource_id'>" .
                                    "<img src='$themeimg/$icon_vis' " .
                                    "title='".q($langVisibility)."'></a></td>";
		} elseif (in_array($res_type, array('description'))) { 
			$content .= "<td width='3'><a href='$_SERVER[SCRIPT_NAME]?course=$code_cours&amp;vis=$resource_id'>" .
                                    "<img src='$themeimg/$icon_vis' " .
                                    "title='".q($langAddToCourseHome)."' alt='".q($langAddToCourseHome)."'></a></td>";
		} else {
			
			$content .= "\n          <td width='3'>&nbsp;</td>";
		}
        } else {
                $content .= "\n          <td width='3'>&nbsp;</td>";
        }
        if ($resource_id != $GLOBALS['max_resource_id']) {
                $content .= "\n          <td width='12'><div align='right'><a href='$_SERVER[SCRIPT_NAME]?course=$code_cours&amp;down=$resource_id'>" .
                            "<img src='$themeimg/down.png' title='".q($langDown)."' alt='".q($langDown)."'></a></div></td>";
	} else {
		$content .= "\n          <td width='12'>&nbsp;</td>";
	}
        if (!$first) {
                $content .= "<td width='12'><div align='left'><a href='$_SERVER[SCRIPT_NAME]?course=$code_cours&amp;up=$resource_id'>" .
                            "<img src='$themeimg/up.png' title='".q($langUp)."' alt='".q($langUp)."'></a></div></td>";
        } else {
                $content .= "\n          <td width='12'>&nbsp;</td>";
        }
        $first = false;
        return $content;
}


// edit resource
function edit_res($resource_id) 
{
	global $id, $urlServer, $langTitle, $langDescr, $langEditForum, $langContents, $langModify, $code_cours;
	 
        $sql = db_query("SELECT id, title, comments, type FROM unit_resources WHERE id='$resource_id'");
        $ru = mysql_fetch_array($sql);
        $restitle = " value='" . htmlspecialchars($ru['title'], ENT_QUOTES) . "'";
        $rescomments = $ru['comments'];
        $resource_id = $ru['id'];
        $resource_type = $ru['type'];

	$tool_content = "\n  <form method='post' action='${urlServer}modules/units/?course=$code_cours'>" .
                        "\n  <fieldset>".
                        "\n  <legend>$langEditForum</legend>".
	                "\n    <input type='hidden' name='id' value='$id'>" .
                        "\n    <input type='hidden' name='resource_id' value='$resource_id'>";
	if ($resource_type != 'text') {
		$tool_content .= "\n    <table class='tbl'>" .
                                 "\n    <tr>" .
                                 "\n      <th>$langTitle:</th>" .
                                 "\n      <td><input type='text' name='restitle' size='50' maxlength='255' $restitle></td>" .
                                 "\n    </tr>";
		$message = $langDescr;
	} else {
		$message = $langContents;
	}
        $tool_content .= "\n    <tr>" .
                         "\n      <th>$message:</th>" .
                         "\n      <td>" .  rich_text_editor('rescomments', 4, 20, $rescomments) . "      </td>" .
                         "\n    </tr>" .
                         "\n    <tr>" .
                         "\n      <th>&nbsp;</th>" .
                         "\n      <td><input type='submit' name='edit_res_submit' value='".q($langModify)."'></td>" .
                         "\n    </tr> " .
                         "\n    </table>" .
                         "\n  </fieldset>" .
                         "\n  </form>";

	return $tool_content;
}
