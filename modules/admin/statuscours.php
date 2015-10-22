<?php
/* ========================================================================
 * Open eClass 2.9
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
 * @file statuscours.php
 * @brief Edit status of a course
 */

$require_power_user = true;
include '../../include/baseTheme.php';

if(!isset($_GET['c'])) { die(); }

// Define $nameTools
$nameTools = $langCourseStatus;
$navigation[] = array("url" => "index.php", "name" => $langAdmin);
$navigation[] = array('url' => 'searchcours.php', 'name' => $langSearchCourse);
$navigation[] = array("url" => "editcours.php?c=".htmlspecialchars($_GET['c']), "name" => $langCourseEdit);

// Update course status
if (isset($_POST['submit']))  {
  // Update query
	$sql = db_query("UPDATE cours SET visible=". intval($_POST['formvisible']) ."
			WHERE code='".mysql_real_escape_string($_GET['c'])."'");
	// Some changes occured
	if (mysql_affected_rows() > 0) {
		$tool_content .= "<p>".$langCourseStatusChangedSuccess."</p>";
	}
	// Nothing updated
	else {
		$tool_content .= "<p>".$langNoChangeHappened."</p>";
	}

}
// Display edit form for course status
else {
	// Get course information
	$row = mysql_fetch_array(db_query("SELECT * FROM cours
		WHERE code='".mysql_real_escape_string($_GET['c'])."'"));
	$visible = $row['visible'];
	$visibleChecked[$visible]="checked";
	
	$tool_content .= "<form action=".$_SERVER['SCRIPT_NAME']."?c=".htmlspecialchars($_GET['c'])." method=\"post\">
<fieldset>
	<legend>".$langCourseStatusChange."</legend>
	<table class='tbl' width='100%'>";
	$tool_content .= "<tr><th class='left' rowspan='4'>$langConfTip</th>
	<td width='1'><input type='radio' name='formvisible' value='2'".@$visibleChecked[2]."></td>
	<td>".$langPublic."</td>
	</tr>
	<tr>
	<td><input type='radio' name='formvisible' value='1'".@$visibleChecked[1]."></td>
	<td>".$langPrivOpen."</td>
	</tr>
	<tr>
	<td><input type='radio' name='formvisible' value='0'".@$visibleChecked[0]."></td>
	<td>".$langPrivate."</td>
	</tr>
        <tr>
	<td><input type='radio' name='formvisible' value='3'".@$visibleChecked[3]."></td>
	<td>".$langCourseInactive."</td>
	</tr>
	<tr>
	<th>&nbsp;</th>
	<td colspan='2' class='right'><input type='submit' name='submit' value='".q($langModify)."'></td>
	</tr>
	</table></fieldset>
	</form>";
}
// If course selected go back to editcours.php
if (isset($_GET['c'])) {
	$tool_content .= "<p align=\"right\"><a href='editcours.php?c=".htmlspecialchars($_GET['c'])."'>".$langBack."</a></p>";
}
// Else go back to index.php directly
else {
	$tool_content .= "<p align=\"right\"><a href=\"index.php\">".$langBackAdmin."</a></p>";
}
draw($tool_content, 3);

