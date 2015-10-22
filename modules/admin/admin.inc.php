<?php
/* ========================================================================
 * Open eClass 2.7
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2013  Greek Universities Network - GUnet
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
	admin.inc.php
	@last update: 31-05-2006 by Stratos Karatzidis
	              11-07-2006 by Vagelis Pitsiougas
	@authors list: Karatzidis Stratos <kstratos@uom.gr>
		       Vagelis Pitsioygas <vagpits@uom.gr>
==============================================================================
        @Description: Functions Library for admin purposes

 	This library includes all the functions that admin is using
	and their settings.

==============================================================================
*/


/**************************************************************
Purpose: covert the difference ($seconds) between 2 unix timestamps
and produce a string ($r), explaining the time
(e.g. 2 years 2 months 1 day)

$seconds : integer
return $r
***************************************************************/
function convert_time($seconds)
{
    $f_minutes = $seconds / 60;
    $i_minutes = floor($f_minutes);
    $r_seconds = intval(($f_minutes - $i_minutes) * 60);

    $f_hours = $i_minutes / 60;
    $i_hours = floor($f_hours);
    $r_minutes = intval(($f_hours  - $i_hours) * 60);

    $f_days = $i_hours / 24;
    $i_days = floor($f_days);
    $r_hours = intval(($f_days - $i_days) * 24);
    $r = "";
    if ($i_days > 0)
    {
	if($i_days >= 365)
	{
	    $i_years = floor($i_days / 365);
	    $i_days = $i_days % 365;
	    $r = $i_years;
	    if($i_years>1)
	    {
		$r .= " years ";
	    }
	    else
	    {
		$r .= " year ";
	    }
	    if($i_days!=0)
	    {
		$r .= $i_days . " days ";
	    }
	}
	else
	{
	    $r .= "$i_days days ";
	}
    }
    if ($r_hours > 0) $r .= "$r_hours hours ";
    if ($r_minutes > 0) $r .= "$r_minutes min";
    return $r;
}

/**************************************************************
Purpose: display paging navigation
Parameters: limit - the current limit
            listsize - the size of the list
            fulllistsize - the size of the full list
            page - the page to send links from pages
            extra_page - extra arguments to page link
            displayAll - display 'all' button (defaults to FALSE)

return String (the constructed table)
***************************************************************/
function show_paging($limit, $listsize, $fulllistsize, $page, $extra_page = '', $displayAll = FALSE) {
	global $langNextPage, $langBeforePage, $langAllUsers;

	$retString = $link_all = "";
	if ($displayAll == TRUE) {
                if (isset($GLOBALS['code_cours'])) {
                        $link_all = "<a href='$_SERVER[SCRIPT_NAME]?all=TRUE&amp;course=$GLOBALS[code_cours]'>$langAllUsers</a>";
                } else {
                        $link_all = "<a href='$_SERVER[SCRIPT_NAME]?all=TRUE'>$langAllUsers</a>";
                }
	} 
	
	// Page numbers of navigation
	$pn = 15;
	$retString .= "
        <table width=\"99%\" class='tbl'>
        <tr>
          <td>&nbsp;</td>
          <td align='center'>$link_all &nbsp;&nbsp;&nbsp;";
	// Deal with previous page
	if ($limit!=0) {
		$newlimit = $limit - $listsize;
		$retString .= "<a href='$page?limit=$newlimit$extra_page'><b>$langBeforePage</b></a>&nbsp;|&nbsp;";
	} else {
		$retString .= "<b>$langBeforePage</b>&nbsp;|&nbsp;";
	}
	// Deal with pages
	if (ceil($fulllistsize / $listsize) <= $pn/3) {
		// Show all
		$counter = 0;
		while ($counter * $listsize < $fulllistsize) {
			$aa = $counter + 1;
			if ($counter * $listsize == $limit) {
				$retString .= "<b>$aa</b>&nbsp;";
			} else {
				$newlimit = $counter * $listsize;
				$retString .= "<b><a href='$page?limit=$newlimit$extra_page'>$aa</a></b>&nbsp;";
			}
			$counter++;
		}
	} elseif ($limit / $listsize < ($pn/3)+3) {
		// Show first 10
		$counter = 0;
		while ($counter * $listsize < $fulllistsize && $counter < $pn/3*2) {
			$aa = $counter + 1;
			if ($counter * $listsize == $limit) {
				$retString .= "<b>$aa</b>&nbsp;";
			} else {
				$newlimit = $counter * $listsize;
				$retString .= "<b><a href='$page?limit=$newlimit$extra_page'>$aa</a></b>&nbsp;";
			}
			$counter++;
		}
		$retString .= "<b>...</b>&nbsp;";
		// Show last 5
		$counter = ceil($fulllistsize / $listsize) - ($pn/3);
		while ($counter * $listsize < $fulllistsize) {
			$aa = $counter + 1;
			if ($counter * $listsize == $limit) {
				$retString .= "<b>".$aa."</b>&nbsp;";
			} else {
				$newlimit = $counter * $listsize;
				$retString .= "<b><a href='$page?limit=$newlimit$extra_page'>$aa</a></b>&nbsp;";
			}
			$counter++;
		}
	} elseif ($limit / $listsize >= ceil($fulllistsize / $listsize) - ($pn/3)-3) {
		// Show first 5
		$counter = 0;
		while ($counter * $listsize < $fulllistsize && $counter < ($pn/3)) {
			$aa = $counter + 1;
			if ($counter * $listsize == $limit) {
				$retString .= "<b>".$aa."</b>&nbsp;";
			} else {
				$newlimit = $counter * $listsize;
				$retString .= "<b><a href=\"".$page."?limit=".$newlimit."".$extra_page."\">".$aa."</a></b>&nbsp;";
			}
			$counter++;
		}
		$retString .= "<b>...</b>&nbsp;";
		// Show last 10
		$counter = ceil($fulllistsize / $listsize) - ($pn/3*2);
		while ($counter * $listsize < $fulllistsize) {
			$aa = $counter + 1;
			if ($counter * $listsize == $limit) {
				$retString .= "<b>".$aa."</b>&nbsp;";
			} else {
				$newlimit = $counter * $listsize;
				$retString .= "<b><a href='$page?limit=$newlimit$extra_page'>$aa</a></b>&nbsp;";
			}
			$counter++;
		}
	} else {
		// Show first 5
		$counter = 0;
		while ($counter * $listsize < $fulllistsize && $counter < ($pn/3)) {
			$aa = $counter + 1;
			if ($counter * $listsize == $limit) {
				$retString .= "<b>$aa</b>&nbsp;";
			} else {
				$newlimit = $counter * $listsize;
				$retString .= "<b><a href='$page?limit=$newlimit$extra_page'>$aa</a></b>&nbsp;";
			}
			$counter++;
		}
		$retString .= "<b>...</b>&nbsp;";
		// Show middle 5
		$counter = ($limit / $listsize) - 2;
		$top = $counter + 5;
		while ($counter * $listsize < $fulllistsize && $counter < $top) {
			$aa = $counter + 1;
			if ($counter * $listsize == $limit) {
				$retString .= "<b>".$aa."</b>&nbsp;";
			} else {
				$newlimit = $counter * $listsize;
				$retString .= "<b><a href='$page?limit=$newlimit$extra_page'>$aa</a></b>&nbsp;";
			}
			$counter++;
		}
		$retString .= "<b>...</b>&nbsp;";
		// Show last 5
		$counter = ceil($fulllistsize / $listsize) - ($pn/3);
		while ($counter * $listsize < $fulllistsize) {
			$aa = $counter + 1;
			if ($counter * $listsize == $limit) {
				$retString .= "<b>$aa</b>&nbsp;";
			} else {
				$newlimit = $counter * $listsize;
				$retString .= "<b><a href='$page?limit=$newlimit$extra_page'>$aa</a></b>&nbsp;";
			}
			$counter++;
		}
	}
	// Deal with next page
	if ($limit + $listsize >= $fulllistsize) {
		$retString .= "|&nbsp;<b>$langNextPage</b>";
	} else {
		$newlimit = $limit + $listsize;
		$retString .= "|&nbsp;<a href='$page?limit=$newlimit$extra_page'><b>$langNextPage</b></a>";
	}
	$retString .= "
          </td>
        </tr>
        </table>";

	return $retString;
}



/**
 * Delete a user and all his dependencies.
 * 
 * @param  integer $id - the id of the user.
 * @return boolean     - returns true if deletion was successful, false otherwise.
 */
function deleteUser($id) {
    global $mysqlMainDb;
    
    $u = intval($id);
    
    if ($u == 1) {
    	return false;
    } else {
    	// validate if this is an existing user
    	$q = db_query("SELECT * FROM user WHERE user_id = ". $u);
    
    	if (mysql_num_rows($q)) {
    		// delete everything
    		$courses = array();
    		$q_course = db_query("SELECT code FROM cours_user, cours
    				WHERE cours.cours_id = cours_user.cours_id AND
    				user_id = ". $u);
    		while (list($code) = mysql_fetch_row($q_course)) {
    			$courses[] = $code;
    		}
    
    
    		foreach ($courses as $code) {
    
    			mysql_select_db($code);
    
    			db_query("DELETE FROM actions WHERE user_id = ". $u);
    			db_query("DELETE FROM assignment_submit WHERE uid = ". $u);
    			db_query("DELETE FROM dropbox_file WHERE uploaderId = ". $u);
    			db_query("DELETE FROM dropbox_person WHERE personId = ". $u);
    			db_query("DELETE FROM dropbox_post WHERE recipientId = ". $u);
    			db_query("DELETE FROM exercise_user_record WHERE uid = ". $u);
    			db_query("DELETE FROM logins WHERE user_id = ". $u);
    			db_query("DELETE FROM lp_user_module_progress WHERE user_id = ". $u);
    			db_query("DELETE FROM poll WHERE creator_id = ". $u);
    			db_query("DELETE FROM poll_answer_record WHERE user_id = ". $u);
    			db_query("DELETE FROM posts WHERE poster_id = ". $u);
    			db_query("DELETE FROM topics WHERE topic_poster = ". $u);
    			db_query("DELETE FROM wiki_pages WHERE owner_id = ". $u);
    			db_query("DELETE FROM wiki_pages_content WHERE editor_id = ". $u);
    		}
    
    		mysql_select_db($mysqlMainDb);
    
    		db_query("DELETE FROM admin WHERE idUser = ". $u);
    		db_query("DELETE FROM forum_notify WHERE user_id = ". $u);
    		db_query("DELETE FROM group_members WHERE user_id = ". $u);
    		db_query("DELETE FROM loginout WHERE id_user = ". $u);
    		db_query("DELETE FROM cours_user WHERE user_id = ". $u);
    		db_query("DELETE FROM user WHERE user_id = ". $u);
    
    
    		return true;
    
    	} else {
    		return false;
    	}
    }
}