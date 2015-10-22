<?php

/* ========================================================================
 * Open eClass 2.8
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

require_once 'mediaresource.class.php';

class MediaResourceFactory {

    public static function initFromDocument($queryRow) {
        global $urlServer, $code_cours;
        return new MediaResource(
                        $queryRow['id'],
                        $queryRow['course_id'],
                        empty($queryRow['title']) ? $queryRow['filename'] : $queryRow['title'], // Override title member
                        $queryRow['path'],
                        null,
                        $urlServer . 'modules/document/mediafile.php?course=' . $code_cours . '&amp;id=' . intval($queryRow['id']),
                        $urlServer . 'modules/document/play.php?course=' . $code_cours . '&amp;id=' . intval($queryRow['id']) );
    }

    public static function initFromVideo($queryRow) {
        global $urlServer, $code_cours;
        return new MediaResource(
                        $queryRow['id'],
                        $queryRow['course_id'],
                        $queryRow['titre'],
                        $queryRow['path'],
                        $queryRow['url'],
                        $urlServer . 'modules/video/file.php?course=' . $code_cours . '&amp;id=' . intval($queryRow['id']),
                        $urlServer . 'modules/video/play.php?course=' . $code_cours . '&amp;id=' . intval($queryRow['id']) );
    }

    public static function initFromVideoLink($queryRow) {
        global $urlServer, $code_cours;
        // validate url
        $url = $queryRow['url'];
        if ($url == 'http://' || empty($url) || !filter_var($url, FILTER_VALIDATE_URL) || preg_match('/^javascript/i', preg_replace('/\s+/', '', $url)) ) {
            $url = '#';
        }
        return new MediaResource(
                        $queryRow['id'],
                        $queryRow['course_id'],
                        $queryRow['titre'],
                        $url, // Override because path is url in db for videolinks
                        $url,
                        $url,
                        $urlServer . 'modules/video/playlink.php?course=' . $code_cours . '&amp;id=' . intval($queryRow['id']) );
    }

}
