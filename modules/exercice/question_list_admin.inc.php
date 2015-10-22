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


 // $Id$
/**
 * This script allows to manage the question list
 * It is included from the script admin.php
 */

// moves a question up in the list
if(isset($_GET['moveUp'])) {
	$objExercise->moveUp($_GET['moveUp']);
	$objExercise->save();
}

// moves a question down in the list
if(isset($_GET['moveDown'])) {
	$objExercise->moveDown($_GET['moveDown']);
	$objExercise->save();
}

// deletes a question from the exercise (not from the data base)
if(isset($_GET['deleteQuestion'])) {
	$deleteQuestion = $_GET['deleteQuestion'];
	// construction of the Question object
	$objQuestionTmp = new Question();
	// if the question exists
	if($objQuestionTmp->read($deleteQuestion)) {
		$objQuestionTmp->delete($exerciseId);
		// if the question has been removed from the exercise
		if($objExercise->removeFromList($deleteQuestion))
		{
			$nbrQuestions--;
		}
	}
	// destruction of the Question object
	unset($objQuestionTmp);
}


$tool_content .= "
    <div align='left' id='operations_container'>
      <ul id='opslist'>
        <li><a href='$_SERVER[SCRIPT_NAME]?course=$code_cours&amp;newQuestion=yes'>$langNewQu</a>
	&nbsp;|&nbsp;
	<a href='question_pool.php?course=$code_cours&amp;fromExercise=$exerciseId'>$langGetExistingQuestion</a></li>
      </ul>
    </div>";


if($nbrQuestions) {
	$questionList = $objExercise->selectQuestionList();
	$i = 1;
	$tool_content .= "
	    <table width='100%' class='tbl_alt'>
	    <tr>
	      <th colspan='2' class='left'>$langQuestionList</th>
	      <th colspan='4' class='right'>$langActions</th>
	    </tr>";
	    
	foreach($questionList as $id) {
		$objQuestionTmp=new Question();
		$objQuestionTmp->read($id);
                    if ($i%2 == 0) {
                       $tool_content .= "\n    <tr class='odd'>";
                    } else {
                       $tool_content .= "\n    <tr class='even'>";
                    }

                $tool_content .= "<td align='right' width='1'>$i</td><td> " .
                        q($objQuestionTmp->selectTitle()) . "<br />" .
                        $aType[$objQuestionTmp->selectType()-1] .
                        "</td><td class='right' width='50'>" .
                        icon('edit', $langModify,
                             $_SERVER['SCRIPT_NAME']."?course=$code_cours&amp;editQuestion=$id") . "&nbsp;" .
                        icon('delete', $langDelete,
                             $_SERVER['SCRIPT_NAME']."?course=$code_cours&amp;deleteQuestion=$id",
                             "onclick=\"if(!confirm('".js_escape($langConfirmYourChoice)."')) return false;\"") .
			"</td><td width='20'>";
		if($i != 1) {
                        $tool_content .= icon('up', $langUp,
                                              $_SERVER['SCRIPT_NAME']."?course=$code_cours&amp;moveUp=$id");
		}
		$tool_content .= "</td><td width='20'>";
		if($i != $nbrQuestions)	{
                        $tool_content .= icon('down', $langDown,
                                              $_SERVER['SCRIPT_NAME']."?course=$code_cours&amp;moveDown=$id");
		}
		$tool_content .= "</td></tr>";
		$i++;
		unset($objQuestionTmp);
	}
	$tool_content .= "</table>";
}
if(!isset($i)) {
	$tool_content .= "<p class='alert1'>$langNoQuestion</p>";
}
