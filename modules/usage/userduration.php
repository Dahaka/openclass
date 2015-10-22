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


/*
===========================================================================
    usage/userlogins.php
 * @version $Id$
    @last update: 2006-12-27 by Evelthon Prodromou <eprodromou@upnet.gr>
    @authors list: Vangelis Haniotakis haniotak@ucnet.uoc.gr,
                    Ophelia Neofytou ophelia@ucnet.uoc.gr
==============================================================================
    @Description: Shows logins made by a user or all users of a course, during a specific period.
    Takes data from table 'logins' (and also from table 'stat_accueil' if still exists).

==============================================================================
*/

$require_current_course = TRUE;
$require_course_admin = TRUE;
$require_help = true;
$helpTopic = 'Usage';
$require_login = true;

include '../../include/baseTheme.php';
include 'duration_query.php';
include '../group/group_functions.php';

if (isset($_GET['format']) and $_GET['format'] == 'csv') {
        $format = 'csv';

        if (isset($_GET['enc']) and $_GET['enc'] == '1253') {
                $charset = 'Windows-1253';
        } else {
                $charset = 'UTF-8';
        }
        $crlf="\r\n";

        header("Content-Type: text/csv; charset=$charset");
        header("Content-Disposition: attachment; filename=usersduration.csv");
        
        echo join(';', array_map("csv_escape",
                                 array($langSurnameName, $langAm, $langGroup, $langDuration))),
             $crlf, $crlf;

} else {
        $format = 'html';

        $nameTools = $langUserDuration;
        $navigation[] = array('url' => 'usage.php?course='.$code_cours, 'name' => $langUsage);
        
        $tool_content .= "
        <div id='operations_container'>
          <ul id='opslist'>
            <li><a href='favourite.php?course=$code_cours&amp;first='>$langFavourite</a></li>
            <li><a href='userlogins.php?course=$code_cours&amp;first='>$langUserLogins</a></li>
            <li><a href='userduration.php?course=$code_cours'>$langUserDuration</a></li>
            <li><a href='../learnPath/detailsAll.php?course=$code_cours&amp;from_stats=1'>$langLearningPaths</a></li>
            <li><a href='group.php?course=$code_cours'>$langGroupUsage</a></li>
          </ul>
        </div>\n";
        
        // display number of users
        $tool_content .= "
        <div class='info'>
           <b>$langDumpUserDurationToFile: </b>1. <a href='userduration.php?course=$code_cours&amp;format=csv'>$langcsvenc2</a>
                2. <a href='userduration.php?course=$code_cours&amp;format=csv&amp;enc=1253'>$langcsvenc1</a>          
          </div>";

        $local_style = '
            .month { font-weight : bold; color: #FFFFFF; background-color: #000066;
             padding-left: 15px; padding-right : 15px; }
            .content {position: relative; left: 25px; }';

        $tool_content .= "
        <table class='tbl_alt' width='99%'>
        <tr>
          <th class='left'>&nbsp;&nbsp;&nbsp;$langSurname $langName</th>
          <th>$langAm</th>
          <th>$langGroup</th>
          <th>$langDuration</th>
        </tr>\n";
}

$result = user_duration_query($currentCourseID, $cours_id);
if ($result) {
        $i = 0;
        while ($row = mysql_fetch_assoc($result)) {
                $i++;
                $grp_name = user_groups($cours_id, $row['user_id'], $format);
                if ($format == 'html') {
                        if ($i%2 == 0) {
                                $tool_content .= "\n<tr class='even'>";
                        } else {
                                $tool_content .= "\n<tr class='odd'>";
                        }
                        $tool_content .= "<td class='bullet'>" . display_user($row['user_id']) . "</td>
                                <td class='center'>$row[am]</td>
                                <td class='center'>$grp_name</td>
                                <td class='center'>" . format_time_duration(0 + $row['duration']) . "</td>
                                </tr>\n";
                } else {
                        echo csv_escape($row['nom'] . ' ' . $row['prenom']), ';',
                             csv_escape($row['am']), ';',
                             csv_escape($grp_name), ';',
                             csv_escape(format_time_duration(0 + $row['duration'])), $crlf;
                }
        }
        if ($format == 'html') {
                $tool_content .= "</table>";
        }
}

user_duration_query_end();

if ($format == 'html') {
        draw($tool_content, 2);
}
