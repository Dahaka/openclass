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

session_start();

if (!isset($_GET['language'])) {
	$language = 'greek';
} else {
	$language = preg_replace('/[^a-z-]/', '', $_GET['language']);
}

if (isset($_SESSION['theme'])) {
        $theme = $_SESSION['theme'];
} else {
        $theme = 'classic';
}
$themeimg = '../../template/' . $theme . '/img';

if (file_exists("../lang/$language/help.inc.php")) {
        $siteName = '';
	include("../lang/$language/common.inc.php");
	include("../lang/$language/help.inc.php");
} else {
	die('No such help topic');
}

// Default topic
if (!isset($_GET['topic']) or !isset($GLOBALS["lang$_GET[topic]Content"])) {
	$_GET['topic'] = 'Default';
}

header('Content-Type: text/html; charset=UTF-8');

$title = $GLOBALS['langH' . str_replace('_student', '', $_GET['topic'])];

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
    <head>
        <title><?php echo $GLOBALS["langH$_GET[topic]"]; ?></title>
            <link href="../../template/<?php echo $theme ?>/help.css" rel="stylesheet" type="text/css" />
        </head>
        <body>
            <h3><?php echo $title; ?></h3>
            <?php echo $GLOBALS["lang$_GET[topic]Content"]; ?>
            <div align="right"><a href='javascript:window.close();'><?php echo $langWindowClose; ?></a>&nbsp;&nbsp;</div>
            <br />
        </body>
</html>
