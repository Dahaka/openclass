<?php
/* ========================================================================
 * Open eClass 2.6
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2012 Greek Universities Network - GUnet
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
 * Groups Component
 * 
 * @author Evelthon Prodromou <eprodromou@upnet.gr>
 * @version $Id$
 * 
 * @abstract This module is responsible for the user groups of each lesson
 *
 */
$require_current_course = TRUE;
$require_help = TRUE;
$helpTopic = 'Group';
$require_editor = true;

include '../../include/baseTheme.php';

$nameTools = $langNewGroupCreate;
$navigation[]= array ("url"=>"group.php?course=$code_cours", "name"=> $langGroups);

$tool_content .= "<form method='post' action='group.php?course=$code_cours'>
    <fieldset>
    <legend>$langNewGroupCreateData</legend>
    <table width='99%' class='tbl'>
    <tr> 
      <th width='160' class='left'>$langNewGroups:</th>
      <td><input type='text' name='group_quantity' size='3' value='1'></td>
    </tr>
    <tr> 
      <th class='left'>$langNewGroupMembers:</th>
      <td><input type='text' name='group_max' size='3' value='8'>&nbsp;$langMax $langPlaces</td>
    </tr>
    <tr>
      <th>&nbsp;</th>
      <td><input type='submit' value='".q($langCreate)."' name='creation'></td>
    </tr>
    </table>
    </fieldset>
    </form>";

draw($tool_content, 2);
