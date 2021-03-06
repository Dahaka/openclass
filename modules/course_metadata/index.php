<?php

/* ========================================================================
 * Open eClass 2.8
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2013  Greek Universities Network - GUnet
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
$require_course_admin = true;
define('STATIC_MODULE', 1);
require_once '../../include/baseTheme.php';
$nameTools = $langCourseMetadata;
require_once 'CourseXML.php';

// exit if feature disabled
if (!get_config('course_metadata')) {
    header("Location: {$urlServer}courses/$code_cours/index.php");
    exit();
}

if (isset($_POST['submit'])) {
    $tool_content .= submitForm();
}

// display Form
list($displayHtml, $xml) = displayForm();
$tool_content .= $displayHtml;

$naturalKeys = array('othernatural', 'maths', 'cis', 'phys', 'chem', 'environ', 'biology');
$naturalJSON = generateJSON($naturalKeys);
$agriKeys = array('otheragri', 'agrifor', 'animal', 'veterin', 'agribio');
$agriJSON = generateJSON($agriKeys);
$engKeys = array('othereng', 'civil', 'eeeeie', 'mechan', 'chemic', 'mateng', 'medeng', 'enveng', 'envbio', 'indbio', 'nanotech');
$engJSON = generateJSON($engKeys);
$socKeys = array('othersoc', 'psych', 'ecobi', 'edusoc', 'sociology', 'law', 'political', 'ecogeosoc', 'mediacomm');
$socJSON = generateJSON($socKeys);
$medKeys = array('othermed', 'basicmed', 'clinicalmed', 'healthsci', 'medbio');
$medJSON = generateJSON($medKeys);
$humKeys = array('otherhum', 'hisarch', 'langlit', 'philosophy', 'arts', 'pedagogy');
$humJSON = generateJSON($humKeys);

$instrFirst = $langCMeta['course_instructor_firstName'];
$instrLast = $langCMeta['course_instructor_lastName'];
$greek = $langCMeta['el'];
$english = $langCMeta['en'];
$instrPhoto = $langCMeta['course_instructor_photo'];

$head_content .= "<link href='../../js/jquery-ui.css' rel='stylesheet' type='text/css'>";
load_js('jquery');
load_js('jquery-ui-new');
load_js('jquery-multiselect');
$head_content .= <<<EOF
<script type='text/javascript'>
/* <![CDATA[ */
        
    var subThematics = {
        "othersubj" : [{"val" : "othersubsubj", "name" : "{$langCMeta['othersubsubj']}"}],
        "natural" : {$naturalJSON},
        "agricultural" : {$agriJSON},
        "engineering" : {$engJSON},
        "social" : {$socJSON},
        "medical" : {$medJSON},
        "humanities" : {$humJSON},
    };
        
    var populateSubThematic = function(key) {
        var subthem = $( "#course_subthematic" );
        subthem.empty();
        $.each(subThematics[key], function() {
            subthem.append( $( "<option />" ).val(this.val).text(this.name) );
        });
    };
        
    var photoDelete = function(id) {
        $( id + "_image" ).remove();
        $( id + "_hidden" ).remove();
        $( id + "_hidden_mime" ).remove();
        $( id + "_delete" ).remove();
    };

    $(document).ready(function(){
        $( ".cmetaaccordion" ).accordion({
            collapsible: true,
            active: false
        });
        
        $( document ).tooltip({
            track: true
        });
        
        $( "#tabs" ).tabs();
        
        $( "#multiselect" ).multiselect();
        
        $( "#course_coursePhoto_delete" ).on('click', function() {
            $( "#course_coursePhoto_image" ).remove();
            $( "#course_coursePhoto_hidden" ).remove();
            $( "#course_coursePhoto_hidden_mime" ).remove();
        });
        
        $( ".course_instructor_photo_delete" ).on('click', function() {
            $(this).parent().children( ".course_instructor_photo_image" ).remove();
            $(this).parent().children( ".course_instructor_photo_hidden" ).val('');
            $(this).parent().children( ".course_instructor_photo_hidden_mime" ).val('');
            $(this).parent().children( ".course_instructor_photo_delete" ).remove();
        });
        
        $( ".instructor_add" ).on('click', function() {
            $(this).parent().parent().children( ".instructor_container" ).append(
                '<div class="cmetarow">' +
                    '<span class="cmetalabel">{$instrFirst} ({$greek}):</span>' +
                    '<span class="cmetafield"><input size="55" name="course_instructor_firstName_el[]" type="text"></span>' +
                    '<span class="cmetamandatory">*</span>' +
                '</div>' +
                '<div class="cmetarow">' +
                    '<span class="cmetalabel">{$instrLast} ({$greek}):</span>' +
                    '<span class="cmetafield"><input size="55" name="course_instructor_lastName_el[]" type="text"></span>' +
                    '<span class="cmetamandatory">*</span>' +
                '</div>' +
                '<div class="cmetarow">' +
                    '<span class="cmetalabel">{$instrFirst} ({$english}):</span>' +
                    '<span class="cmetafield"><input size="55" name="course_instructor_firstName_en[]" type="text"></span>' +
                    '<span class="cmetamandatory">*</span>' +
                '</div>' +
                '<div class="cmetarow">' +
                    '<span class="cmetalabel">{$instrLast} ({$english}):</span>' +
                    '<span class="cmetafield"><input size="55" name="course_instructor_lastName_en[]" type="text"></span>' +
                    '<span class="cmetamandatory">*</span>' +
                '</div>' +
                '<div class="cmetarow">' +
                    '<span class="cmetalabel">{$instrPhoto}:</span>' +
                    '<span class="cmetafield">' +
                        '<input class="course_instructor_photo_hidden" type="hidden" name="course_instructor_photo[]">' +
                        '<input class="course_instructor_photo_hidden_mime" type="hidden" name="course_instructor_photo_mime[]">' +
                        '<input size="30" name="course_instructor_photo[]" type="file">' +
                    '</span>' +
                '</div>'
                    );
        });
        
        $( "#course_thematic" ).on('change', function() {
            populateSubThematic( $( "#course_thematic" ).val() );
        });
        
        populateSubThematic( $( "#course_thematic" ).val() );
        $( "#course_subthematic" ).val('{$xml->subthematic}');
    });

/* ]]> */
</script>
<style type="text/css">
.ui-widget {
    font-family: "Trebuchet MS",Tahoma,Arial,Helvetica,sans-serif;
    font-size: 13px;
}

.ui-widget-content {
    color: rgb(119, 119, 119);
}
</style>
EOF;
draw($tool_content, 2, null, $head_content);

//--- HELPER FUNCTIONS ---//

function displayForm() {
    global $cours_id, $code_cours;
    $xml = CourseXMLElement::init($cours_id, $code_cours);
    return array($xml->asForm(), $xml);
}

function submitForm() {
    global $cours_id, $code_cours, $webDir, $langModifDone, $mysqlMainDb;

    // handle uploaded files
    $fileData = array();
    foreach (CourseXMLConfig::$binaryFields as $bkey) {
        if (in_array($bkey, CourseXMLConfig::$multipleFields) || in_array($bkey, CourseXMLConfig::$arrayFields)) {
            if (isset($_FILES[$bkey]) && isset($_FILES[$bkey]['tmp_name']) && isset($_FILES[$bkey]['type'])
                    && is_array($_FILES[$bkey]['tmp_name'])) {
                for ($i = 0; $i < count($_FILES[$bkey]['tmp_name']); $i++) {
                    if (is_uploaded_file($_FILES[$bkey]['tmp_name'][$i])
                            && isValidImage($_FILES[$bkey]['type'][$i])) {
                        // convert to resized jpg if possible
                        $uploaded = $_FILES[$bkey]['tmp_name'][$i];
                        $copied = $_FILES[$bkey]['tmp_name'][$i] . '.new';
                        $type = $_FILES[$bkey]['type'][$i];

                        if (copy_resized_image($uploaded, $type, IMAGESIZE_LARGE, IMAGESIZE_LARGE, $copied)) {
                            $fileData[$bkey][$i] = base64_encode(file_get_contents($copied));
                            $fileData[$bkey . '_mime'][$i] = 'image/jpeg'; // copy_resized_image always outputs jpg
                        } else { // erase possible previous image or failed conversion
                            $fileData[$bkey][$i] = '';
                            $fileData[$bkey . '_mime'][$i] = '';
                        }
                    } else {
                        // add to array as empty, in order to keep correspondence
                        $fileData[$bkey][$i] = '';
                        $fileData[$bkey . '_mime'][$i] = '';
                    }
                }
            }
        } else {
            if (isset($_FILES[$bkey])
                    && is_uploaded_file($_FILES[$bkey]['tmp_name'])
                    && isValidImage($_FILES[$bkey]['type'])) {
                // convert to resized jpg if possible
                $uploaded = $_FILES[$bkey]['tmp_name'];
                $copied = $_FILES[$bkey]['tmp_name'] . '.new';
                $type = $_FILES[$bkey]['type'];

                if (copy_resized_image($uploaded, $type, IMAGESIZE_LARGE, IMAGESIZE_LARGE, $copied)) {
                    $fileData[$bkey] = base64_encode(file_get_contents($copied));
                    $fileData[$bkey . '_mime'] = 'image/jpeg'; // copy_resized_image always outputs jpg
                    // unset old photo because array_merge_recursive below will keep the old one
                    unset($_POST[$bkey]);
                    unset($_POST[$bkey . '_mime']);
                } else { // erase possible previous image or failed conversion
                    $fileData[$bkey] = '';
                    $fileData[$bkey . '_mime'] = '';
                }
            }
        }
    }

    $skeleton = $webDir . '/modules/course_metadata/skeleton.xml';
    $extraData = CourseXMLElement::getAutogenData($cours_id);
    // manually merge instructor photo, to achieve multiplicity sync
    foreach ($fileData['course_instructor_photo'] as $key => $value) {
        if (!empty($value)) {
            $_POST['course_instructor_photo'][$key] = $value;
        }
    }
    unset($fileData['course_instructor_photo']);
    foreach ($fileData['course_instructor_photo_mime'] as $key => $value) {
        if (!empty($value)) {
            $_POST['course_instructor_photo_mime'][$key] = $value;
        }
    }
    unset($fileData['course_instructor_photo_mime']);
    $data = array_merge_recursive($_POST, $extraData, $fileData);
    // course-based adaptation
    list($dnum) = mysql_fetch_row(db_query("select count(id) from document where course_id = " . $cours_id, $mysqlMainDb));
    list($vnum) = mysql_fetch_row(db_query("select count(id) from video", $code_cours));
    list($vlnum) = mysql_fetch_row(db_query("select count(id) from videolinks", $code_cours));
    if ($dnum + $vnum + $vlnum < 1) {
        $data['course_confirmVideolectures'] = 'false';
    }

    $xml = simplexml_load_file($skeleton, 'CourseXMLElement');
    $xml->adapt($data);
    $xml->populate($data);

    CourseXMLElement::save($cours_id, $code_cours, $xml);

    return "<p class='success'>$langModifDone</p>";
}

function isValidImage($type) {
    $ret = false;
    if ($type == 'image/jpeg') {
        $ret = true;
    } elseif ($type == 'image/png') {
        $ret = true;
    } elseif ($type == 'image/gif') {
        $ret = true;
    } elseif ($type == 'image/bmp') {
        $ret = true;
    }

    return $ret;
}

function generateJSON($keys) {
    $json = "[";
    foreach($keys as $key) {
        $json .= "{\"val\" : \"" . $key . "\", \"name\" : \"" . $GLOBALS['langCMeta'][$key] . "\"}, ";
    }
    $json .= "]";
    return $json;
}
