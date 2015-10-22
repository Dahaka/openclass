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

$require_current_course = TRUE;
$require_course_admin = TRUE;

include '../../include/init.php';

if (isset($_GET['enc']) and $_GET['enc'] == '1253') {
        $charset = 'Windows-1253';
} else {
        $charset = 'UTF-8';
}
$crlf="\r\n";

header("Content-Type: text/csv; charset=$charset");
header("Content-Disposition: attachment; filename=listusers.csv");

echo join(';', array_map("csv_escape", array($langSurname, $langName, $langEmail, $langAm, $langUsername, $langGroups))),
     $crlf;
$sql = db_query("SELECT user.user_id, user.nom, user.prenom, user.email, user.am, user.username
                FROM cours_user, user
                        WHERE `user`.`user_id` = `cours_user`.`user_id`
                        AND `cours_user`.`cours_id` = $cours_id ORDER BY user.nom,user.prenom", $mysqlMainDb);
$r=0;
while ($r < mysql_num_rows($sql)) {
        $a = mysql_fetch_array($sql);
        echo "$crlf";
        $f = 1;
        while ($f < mysql_num_fields($sql)) {
                if ($f > 1) {
                        echo ';';
                }
                echo csv_escape($a[$f]);
                $f++;
        }
        echo ';';
        echo csv_escape(user_groups($cours_id, $a['user_id'], 'txt'));
        $r++;
}
echo "$crlf";
