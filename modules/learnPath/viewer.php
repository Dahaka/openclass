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


/*===========================================================================
	viewer.php
	@authors list: Thanos Kyritsis <atkyritsis@upnet.gr>

	based on Claroline version 1.7 licensed under GPL
	      copyright (c) 2001, 2006 Universite catholique de Louvain (UCL)

	      original file: navigation/viewer.php Revision: 1.15

	Claroline authors: Piraux Sebastien <pir@cerdecam.be>
                      Lederer Guillaume <led@cerdecam.be>
==============================================================================
    @Description: This is the main navigation script for browsing a
                  learning path. It handles the frames.

    @Comments:
==============================================================================
*/

$require_current_course = TRUE;
$require_login = TRUE;

require_once("../../include/baseTheme.php");
require_once("../../include/lib/learnPathLib.inc.php");

// the following constant defines the default display of the learning path browser
// 0 : display eclass header and footer and table of content, and content
// 1 : display only table of content and content
define ('FULL_SCREEN' , 1);

// override session vars if get args are present
if (isset($_GET['path_id']) && !empty($_GET['path_id']))
    $_SESSION['path_id'] = intval($_GET['path_id']);
if (isset($_GET['module_id']) && !empty($_GET['module_id']))
	$_SESSION['lp_module_id'] = intval($_GET['module_id']);

check_LPM_validity($is_editor, $code_cours, true);

$nameTools = $langPreview;
if (!add_units_navigation()) {
	$navigation[] = array("url"=>"learningPathList.php?course=$code_cours", "name"=> $langLearningPaths);
	$navigation[] = array("url"=>"learningPath.php?course=$code_cours", "name"=> $langAdm);
}

if (!isset($titlePage)) $titlePage = '';
if(!empty($nameTools))
{
    $titlePage .= $nameTools.' - ';
}

if(!empty($intitule))
{
    $titlePage .= $intitule . ' - ';
}
$titlePage .= $siteName;

if (isset($_GET['fullscreen']) && is_numeric($_GET['fullscreen']))
{
    $displayFull = (int) $_GET['fullscreen'];
}
else
{
    // choose default display
    // default display is without fullscreen
    $displayFull = FULL_SCREEN;
}

if ($displayFull == 0)
{
	$tool_content .= "<iframe src=\"navigation/startModule.php?course=$code_cours\" name=\"mainFrame\" "
		."width=\"100%\" height=\"550\" scrolling=\"no\" frameborder=\"0\">"
		.$langBrowserCannotSeeFrames
		."<br />"."\n"
		."      <a href=\"module.php?course=$code_cours\">".$langBack."</a>"."\n"
		."      </iframe>"."\n";
	draw($tool_content, 2, null, $head_content);
}
else
{
	echo
	 "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Frameset//EN\""
	."   \"http://www.w3.org/TR/html4/frameset.dtd\">"."\n"
	."<html>"."\n"
	."<head>"."\n"
	.'<meta http-equiv="Content-Type" content="text/html; charset='.$charset.'">'."\n"
	."<title>".q($titlePage)."</title>"."\n"
	."</head>"."\n"
	."<frameset cols=\"*\" border=\"0\">"."\n"
	."<frame src=\"navigation/startModule.php?course=$code_cours\" name=\"mainFrame\" />"."\n"
	."</frameset>"."\n"
	."<noframes>"."\n"
	."<body>"."\n"
	.$langBrowserCannotSeeFrames
	."<br />"."\n"
	."<a href=\"module.php?course=$code_cours\">".$langBack."</a>"."\n"
	."</body>"."\n"
	."</noframes>"."\n"
	."</html>"."\n";
}

?>
