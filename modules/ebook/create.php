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



$require_current_course = true;

require_once '../../include/baseTheme.php';
require_once '../../include/pclzip/pclzip.lib.php';
include '../../include/lib/fileUploadLib.inc.php';

$nameTools = $langEBookCreate;
$navigation[] = array('url' => 'index.php?course='.$code_cours, 'name' => $langEBook);
define('EBOOK_DOCUMENTS', true);

mysql_select_db($mysqlMainDb);

if (!$is_editor) {
        redirect_to_home_page();
} else {
        $title = trim(@$_POST['title']);
        if (empty($title) or !isset($_FILES['file'])) {
                $tool_content .= "<p class='caution'>$langFieldsMissing</p>";
        }
        if (!preg_match('/\.zip$/i', $_FILES['file']['name'])) {
                $tool_content .= "<p class='caution'>$langUnwantedFiletype: " .
                                 q($_FILES['file']['name']) . "</p>";
        }
        
        validateUploadedFile($_FILES['file']['name'], 2);
        
        if (!empty($tool_content)) {
                draw($tool_content, 2);
                exit;
        }
        
        $zipFile = new pclZip($_FILES['file']['tmp_name']);
        validateUploadedZipFile($zipFile->listContent(), 2);

        list($order) = mysql_fetch_row(db_query("SELECT MAX(`order`) FROM ebook WHERE course_id = $cours_id"));
        if (!$order) {
                $order = 1;
        } else {
                $order++;
        }
        db_query("INSERT INTO ebook SET `order` = $order, `course_id` = $cours_id, `title` = " .
                         autoquote($title));
        $ebook_id = mysql_insert_id();

        // Initialize document subsystem global variables
        include '../document/doc_init.php';

        if (!mkdir($basedir, 0775, true)) {
                db_query("DELETE FROM ebook WHERE course_id = $cours_id AND id = $ebook_id");
                $tool_content .= "<p class='caution'>$langImpossible</p>";
                draw($tool_content, 2);
                exit;
        }

        chdir($basedir);
        
        $realFileSize = 0;
        $zipFile->extract(PCLZIP_CB_PRE_EXTRACT, 'process_extracted_file');
        header("Location: $urlAppend/modules/ebook/edit.php?course=$code_cours&id=$ebook_id");
}
