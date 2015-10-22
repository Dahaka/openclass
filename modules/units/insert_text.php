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



function display_text_form()
{
	global $tool_content, $id, $langContent, $langAdd, $code_cours;

	$tool_content .= "
        <form action='insert.php?course=$code_cours' method='post'><input type='hidden' name='id' value='$id'>";
	$tool_content .= "
        <fieldset>
        <legend>$langContent:</legend>".  rich_text_editor('comments', 4, 20, '') ."
	<br />
        <input type='submit' name='submit_text' value='".q($langAdd)."'>
	</fieldset>
	</form>";
}
