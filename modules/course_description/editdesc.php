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

/*
 * Edit, Course Description
 *
 */

$require_current_course = TRUE;
$require_login = true;
$require_editor = true;

include '../../include/baseTheme.php';
include '../units/functions.php';

$tool_content = $head_content = "";
$nameTools = $langEditCourseProgram ;
$navigation[] = array ('url' => 'index.php?course='.$code_cours, 'name' => $langCourseProgram);

mysql_select_db($mysqlMainDb);

$description = '';
$unit_id = description_unit_id($cours_id);
$q = db_query("SELECT id, res_id, comments FROM unit_resources WHERE unit_id = $unit_id
                      AND (res_id < 0 OR `order` < 0)");
if ($q and mysql_num_rows($q) > 0) {
        while ($row = mysql_fetch_array($q)) {
                if ($row['res_id'] == -1) {
                        $description = $row['comments'];
                } else {
                        $new_order = add_unit_resource_max_order($unit_id);
                        $new_res_id = new_description_res_id($unit_id);
                        db_query("UPDATE unit_resources SET
                                         res_id = $new_res_id, visibility = 'v', `order` = $new_order
                                  WHERE id = $row[id]");
                }
        }
}

$tool_content = "
 <form method='post' action='index.php?course=$code_cours'>
 <input type='hidden' name='edIdBloc' value='-1' />
 <input type='hidden' name='edTitleBloc' value='".q($langDescription)."' />
   <fieldset>
   <legend>$langDescription</legend>
         " . rich_text_editor('edContentBloc', 4, 20, $description) . "
   <br />
   <div class='right'><input type='submit' name='submit' value='".q($langSubmit)."' /></div>
   </fieldset>
 </form>\n";

draw($tool_content, 2, null, $head_content);
