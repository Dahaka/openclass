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


$topics_per_page = 10;
$posts_per_page = 10;
$hot_threshold = 30;

$url_images = "images";

$folder_image = "$url_images/topic_read.gif";
$icon_topic_latest = "$url_images/icon_topic_latest.gif";
$hot_folder_image = $newposts_image = $folder_image;
$hot_newposts_image = "$url_images/topic_read_hot.gif";
$posticon = "$url_images/posticon.png";
$posticon_more = "$url_images/icon_pages.gif";
$locked_image = "$url_images/lock.png";

define('PAGINATION_CONTEXT', 3);
