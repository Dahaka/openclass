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

$require_usermanage_user = TRUE;
include '../../include/baseTheme.php';
require_once 'admin.inc.php';
require_once '../create_course/functions.php';

$nameTools = $langMultiCourse;
$navigation[]= array ('url' => 'index.php', 'name' => $langAdmin);

if (isset($_POST['submit'])) {
        $line = strtok($_POST['courses'], "\n");
        $fac = intval($_POST['faculte']);
        $vis = intval($_POST['formvisible']);
        $lang = langcode_to_name($_POST['lang']);
        while ($line !== false) {
                $line = canonicalize_whitespace($line);
                if (!empty($line)) {
                        $info = explode('|', $line);
                        $title = $info[0];
                        $prof_uid = null;
                        $prof_not_found = false;
                        if (isset($info[1])) {
                                $prof_info = trim($info[1]);
                                $prof_uid = find_prof(trim($info[1]));
                                if ($prof_info and !$prof_uid) {
                                        $prof_not_found = true;
                                }
                        }
                        if ($prof_uid) {
                                $prof_name = uid_to_name($prof_uid);
                        } else {
                                $prof_name = '';
                        }
                        $cid = create_course('', $lang, $title, $fac, $vis, $prof_name,
                                             $_POST['type'], $_POST['password']);
                        if ($cid) {
                                activate_subsystems($lang, $_POST['subsystems']);
                                if ($prof_uid) {
                                        db_query("INSERT INTO `$mysqlMainDb`.cours_user
                                                         SET cours_id = $cid,
                                                             user_id = $prof_uid,
                                                             statut = 1,
                                                             tutor = 1,
                                                             reg_date = NOW()");
                                }
                                db_query("INSERT INTO `$mysqlMainDb`.group_properties SET
                                            course_id = $cid,
                                            self_registration = 1,
                                            multiple_registration = 0,
                                            forum = 1,
                                            private_forum = 0,
                                            documents = 1,
                                            wiki = 0,
                                            agenda = 0");
                        }
                        $class = $prof_not_found? 'alert1': 'success';
                        $tool_content .= "<p class='$class'><b>" . q($title) . '</b>: '. q($langBetaCMSLessonCreatedOK);
                        if ($prof_uid) {
                                $tool_content .= '<br>' . q($langTeacher) . ': <b>' . q($prof_name) . '</b>';
                        } elseif ($prof_not_found) {
                                $tool_content .= '<br>' . q($langTeacher) . ': <b>' .
                                        q($prof_info) . '</b>: ' . q($langNoUsersFound2);
                        }
                        $tool_content .= '</p>';
                }
                $line = strtok("\n");
        }

} else {
    
    $tool_content .= "<div class='noteit'>". $langMultiCourseInfo ."</div>
        <form method='post' action='". $_SERVER['SCRIPT_NAME'] ."'>
        <fieldset>
        <legend>". $langMultiCourseData ."</legend>
        <table class='tbl' width='100%'>
        <tr>
            <th>$langMultiCourseTitles:</th>
            <td>".text_area('courses', 20, 80, '')."</td>
        </tr>
	<tr>
	  <th>$langFaculty:</th>
	  <td>";
	$facs = db_query("SELECT id, name FROM faculte order by id");
	while ($n = mysql_fetch_array($facs)) {
		$fac[$n['id']] = $n['name'];
	}
	$tool_content .= selection($fac, 'faculte');
	$tool_content .= "</td>
          <td>&nbsp;</td>
        </tr>
	<tr>
	  <th class='left'>$langType:</th>
	  <td>" .  selection(array('pre' => $langpre, 'post' => $langpost, 'other' => $langother), 'type') . "</td>
	  <td>&nbsp;</td>
        </tr>
        <tr>
          <th>$langAvailableTypes:</th>
	<td>
	  <table class='tbl' width='100%'>
	  <tr class='smaller'>
	    <th width='130'><img src='$themeimg/lock_open.png' title='".$m['legopen']."' alt='".$m['legopen']."'width='16' height='16' /> ".$m['legopen']."</th>
	    <td><input name='formvisible' type='radio' value='2' checked='checked' /></td>
	    <td>$langPublic</td>
	  </tr>
	  <tr class='smaller'>
	    <th valign='top'><img src='$themeimg/lock_registration.png' title='".$m['legrestricted']."' alt='".$m['legrestricted']."' width='16' height='16' /> ".$m['legrestricted']."</th>
	    <td valign='top'><input name='formvisible' type='radio' value='1' /></td>
	    <td>
              $langPrivOpen<br />
              <div class='smaller' style='padding: 3px;'><em>$langOptPassword</em> <input type='text' name='password' class='FormData_InputText' id='password' autocomplete='off' />&nbsp;<span id='result'></span></div>
            </td>
          </tr>
	  <tr class='smaller'>
	    <th valign='top'><img src='$themeimg/lock_closed.png' title='".$m['legclosed']."' alt='".$m['legclosed']."' width=\"16\" height=\"16\" /> ".$m['legclosed']."</th>
	    <td valign='top'><input name='formvisible' type='radio' value='0' /></td>
	    <td>$langPrivate</td>
	  </tr>
          <tr class='smaller'>
	    <th valign='top'><img src='$themeimg/lock_inactive.png' title='".$m['linactive']."' alt='".$m['linactive']."' width='16' height='16' /> ".$m['linactive']."</th>
	    <td valign='top'><input name='formvisible' type='radio' value='3' /></td>
	    <td>$langCourseInactive</td>
	  </tr>
	  </table>      
	  <br />
	</td>
      </tr>
      <tr>
	<th colspan='2'>$langSubsystems</td>
      </tr>
      <tr>
	<td colspan='2'>
 	  <table class='tbl smaller' width='100%'>
	  <tr>
	    <td width='10' ><img src='$themeimg/calendar_on.png' alt='' height='16' width='16' /></td>
	    <td width='150'>$langAgenda</td>
	    <td width='30' ><input name='subsystems[]' type='checkbox' value='1' checked='checked' /></td>
	    <th width='2' >&nbsp;</th>
	    <td width='10' >&nbsp;<img src='$themeimg/dropbox_on.png' alt='' height='16' width='16' /></td>
	    <td width='150'>$langDropBox</td>
 	    <td width='30' ><input type='checkbox' name='subsystems[]' value='16' /></td>
	  </tr>
	  <tr  class='even'>
	    <td><img src='$themeimg/links_on.png' alt='' height='16' width='16' /></td>
	    <td>$langLinks</td>
	    <td><input name='subsystems[]' type='checkbox' value='2' checked='checked' /></td>
	    <th>&nbsp;</th>
	    <td>&nbsp;<img src='$themeimg/groups_on.png' alt='' height='16' width='16' /></td>
	    <td>$langGroups</td>
	    <td><input type='checkbox' name='subsystems[]' value='15' /></td>
	  </tr>
	  <tr>
	    <td><img src='$themeimg/docs_on.png' alt='' height='16' width='16' /></td>
	    <td>$langDoc</td>
	    <td><input name='subsystems[]' type='checkbox' value='3' checked='checked' /></td>
	    <th>&nbsp;</th>
	    <td>&nbsp;<img src='$themeimg/conference_on.png' alt='' height='16' width='16' /></td>
	    <td>$langConference</td>
	    <td><input type='checkbox' name='subsystems[]' value='19' /></td>
	  </tr>
	  <tr class='even'>
	    <td><img src='$themeimg/videos_on.png' alt='' height='16' width='16' /></td>
	    <td>$langVideo</td>
	    <td><input name='subsystems[]' type='checkbox' value='4'  /></td>
	    <th>&nbsp;</th>
	    <td>&nbsp;<img src='$themeimg/description_on.png' alt='' height='16' width='16' /></td>
	    <td>$langCourseDescription</td>
	    <td><input type='checkbox' name='subsystems[]' value='20' checked='checked' /></td>
	  </tr>
	  <tr>
	    <td><img src='$themeimg/assignments_on.png' alt='' height='16' width='16' /></td>
	    <td>$langWorks</td>
	    <td><input type='checkbox' name='subsystems[]' value='5' /></td>
	    <th>&nbsp;</th>
	    <td>&nbsp;<img src='$themeimg/questionnaire_on.png' alt='' height='16' width='16' /></td>
	    <td>$langQuestionnaire</td>
	    <td><input type='checkbox' name='subsystems[]' value='21' /></td>
	  </tr>
	  <tr  class='even'>
	    <td><img src='$themeimg/announcements_on.png' alt='' height='16' width='16' /></td>
	    <td>$langAnnouncements</td>
	    <td><input type='checkbox' name='subsystems[]' value='7' checked='checked'/></td>
	    <th>&nbsp;</th>
	    <td>&nbsp;<img src='$themeimg/lp_on.png' alt='' height='16' width='16' /></td>
	    <td>$langLearnPath</td>
	    <td><input type='checkbox' name='subsystems[]'  value='23' /></td>
	  </tr>
	  <tr>
	    <td><img src='$themeimg/forum_on.png' alt='' height='16' width='16' /></td>
	    <td>$langForums</td>
	    <td><input type='checkbox' name='subsystems[]' value='9' /></td>
	    <th>&nbsp;</th>
	    <td>&nbsp;<img src='$themeimg/wiki_on.png' alt='' height='16' width='16' /></td>
	    <td>$langWiki</td>
	    <td><input type='checkbox' name='subsystems[]' value='26' /></td>
	  </tr>
	  <tr class='even'>
	    <td><img src='$themeimg/exercise_on.png' alt='' height='16' width='16' /></td>
	    <td>$langExercices</td>
	    <td><input type='checkbox' name='subsystems[]' value='10' /></td>
	    <th>&nbsp;</th>
            <td>&nbsp;<img src='$themeimg/glossary_on.png' alt='' height='16' width='16' /></td>
	    <td>$langGlossary</td>
	    <td><input type='checkbox' name='subsystems[]' value='17' checked='checked' /></td>
	  </tr>
	  <tr>
	    <td><img src='$themeimg/ebook_on.png' alt='' height='16' width='16' /></td>
	    <td>$langEBook</td>
	    <td><input type='checkbox' name='subsystems[]' value='18' /></td>
	    <th>&nbsp;</th>
            <td>&nbsp;</td>
	    <td>&nbsp;</td>
	    <td>&nbsp;</td>
	  </tr>
	  </table>
        <br />
	</td>
      </tr>
	<tr>
	  <th class='left'>$langLanguage:</th>
	  <td>" . lang_select_options('lang') . "</td>
          <td>&nbsp;</td>
        </tr>
        <tr>
            <th>&nbsp;</th>
            <td class='right'>
            <input type='submit' name='submit' value='". q($langSubmit) ."'></td>
        </tr>
        </table>
        </fieldset>
        </form>";
}

$tool_content .= "<div class='right'><a href='index.php'>$langBackAdmin</a></div><br/>\n";
draw($tool_content, 3, null, $head_content);


// Helper function
function prof_query($sql)
{
        global $mysqlMainDb;
        return db_query_get_single_value("SELECT user_id FROM `$mysqlMainDb`.user
                                                 WHERE statut = 1 AND $sql");
}

// Find a professor by name ("Name surname") or username
function find_prof($uname)
{
        if ($uid = prof_query('username = ' . quote($uname))) {
		return $uid;
        } else {
                $names = explode(' ', $uname);
                if (count($names) == 2 and
                    $uid = prof_query('(nom = ' . quote($names[0]) .
                                      ' AND prenom = ' . quote($names[1]) .
                                      ') OR (prenom = ' . quote($names[0]) .
                                      ' AND nom = ' . quote($names[1]) . ')')) {
                        return $uid;
                }
	}
        return false;
}
