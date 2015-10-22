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



$questionName = $objQuestion->selectTitle();
$answerType = $objQuestion->selectType();
$questionId = $objQuestion->selectId();
$okPicture = file_exists($picturePath.'/quiz-'.$questionId)?true:false;
if (isset($_POST['submitAnswers'])) {
	$submitAnswers = $_POST['submitAnswers'];
}
if (isset($_POST['buttonBack'])) {
	$buttonBack = $_POST['buttonBack'];
}
// if we come from the warning box "this question is used in several exercises"
if (isset($usedInSeveralExercises) or isset($_POST['modifyIn'])) {
	// if the user has chosed to modify the question only in the current exercise
	if($_POST['modifyIn'] == 'thisExercise')
	{
		// duplicates the question
		$questionId=$objQuestion->duplicate();
		// deletes the old question
		$objQuestion->delete($exerciseId);
		// removes the old question ID from the question list of the Exercise object
		$objExercise->removeFromList($_GET['modifyAnswers']);
		// adds the new question ID into the question list of the Exercise object
		$objExercise->addToList($questionId);
		// construction of the duplicated Question
		$objQuestion=new Question();
		$objQuestion->read($questionId);
		// adds the exercise ID into the exercise list of the Question object
		$objQuestion->addToList($exerciseId);
		// copies answers from $modifyAnswers to $questionId
		$objAnswer->duplicate($questionId);
		// construction of the duplicated Answers
		$objAnswer=new Answer($questionId);
	}
	
	if($answerType == UNIQUE_ANSWER || $answerType == MULTIPLE_ANSWER)
	{
		$correct=unserialize($correct);
		$reponse=unserialize($reponse);
		$comment=unserialize($comment);
		$weighting=unserialize($weighting);
	}
	elseif($answerType == MATCHING)
	{
		$option=unserialize($option);
		$match=unserialize($match);
		$sel=unserialize($sel);
		$weighting=unserialize($weighting);
	}
	else
	{
		$reponse=unserialize($reponse);
		$comment=unserialize($comment);
		$blanks=unserialize($blanks);
		$weighting=unserialize($weighting);
	}
	unset($buttonBack);
}

// the answer form has been submitted
if(isset($submitAnswers) || isset($buttonBack)) {
	if($answerType == UNIQUE_ANSWER || $answerType == MULTIPLE_ANSWER) {
		$questionWeighting=$nbrGoodAnswers=0;

		for($i=1;$i <= $nbrAnswers;$i++) {
			$reponse[$i]=trim($reponse[$i]);
			$comment[$i]=trim($comment[$i]);
			$weighting[$i]=$weighting[$i];

			if($answerType == UNIQUE_ANSWER) {
				$goodAnswer=@($correct == $i)?1:0;
			} else {
                                $goodAnswer=@($correct[$i])?1:0;
			}
			if($goodAnswer) {
				$nbrGoodAnswers++;
				// a good answer can't have a negative weighting
				$weighting[$i]=abs($weighting[$i]);
				// calculates the sum of answer weighting 
				if($weighting[$i]) {
					$questionWeighting+=$weighting[$i];
				}
			} else {
				// a bad answer can't have a positive weighting
				$weighting[$i]=0-abs($weighting[$i]);
			}

			// checks if field is empty
			//if(empty($reponse[$i])) {
			// '0' might be a valid answer
			if(!isset($reponse[$i]) || ($reponse[$i] === null)) {
				$msgErr=$langGiveAnswers;
				// clears answers already recorded into the Answer object
				$objAnswer->cancel();
				break;
			} else {
				// adds the answer into the object
				$objAnswer->createAnswer($reponse[$i],$goodAnswer,$comment[$i],$weighting[$i],$i);
			}
		}  // end for()

		if(empty($msgErr)) {
			if(!$nbrGoodAnswers) {
				$msgErr=($answerType == UNIQUE_ANSWER)?$langChooseGoodAnswer:$langChooseGoodAnswers;
				// clears answers already recorded into the Answer object
				$objAnswer->cancel();
			}
			// checks if the question is used in several exercises
			elseif($exerciseId && !isset($_POST['modifyIn']) && $objQuestion->selectNbrExercises() > 1)
			{
				$usedInSeveralExercises=1;
			} else {
				// saves the answers into the data base
				$objAnswer->save();
				// sets the total weighting of the question
				$objQuestion->updateWeighting($questionWeighting);
				$objQuestion->save($exerciseId);
				$editQuestion=$questionId;
				unset($_GET['modifyAnswers']);
			}
		}
	}
	elseif($answerType == FILL_IN_BLANKS) {
		$reponse=trim($reponse);
		if(!isset($buttonBack)) {
			if($setWeighting) {
				@$blanks=unserialize($blanks);
				// checks if the question is used in several exercises
				if($exerciseId && !isset($_POST['modifyIn']) && $objQuestion->selectNbrExercises() > 1)
				{
					$usedInSeveralExercises=1;
				} else {
					// separates text and weightings by '::'
					$reponse.='::';
					$questionWeighting=0;
					foreach($weighting as $val) {
						// a blank can't have a negative weighting
						$val=abs($val);
						$questionWeighting+=$val;
						// adds blank weighting at the end of the text
						$reponse.=$val.',';
					}
					$reponse=substr($reponse,0,-1);
					$objAnswer->createAnswer($reponse, 0, '', 0, 0);
					$objAnswer->save();

					// sets the total weighting of the question
					$objQuestion->updateWeighting($questionWeighting);
					$objQuestion->save($exerciseId);

					$editQuestion=$questionId;
					unset($_GET['modifyAnswers']);
				}
			}
			// if no text has been typed or the text contains no blank
			elseif(empty($reponse))
			{
				$msgErr=$langGiveText;
			}
			elseif(!preg_match('/\[.+\]/',$reponse))
			{
				$msgErr=$langDefineBlanks;
			}
			else
			{
				// now we're going to give a weighting to each blank
				$setWeighting=1;
				unset($submitAnswers);
				// removes character '::' possibly inserted by the user in the text
				$reponse=str_replace('::','',$reponse);
				// we save the answer because it will be modified
				$temp=$reponse;
				// blanks will be put into an array
				$blanks=Array();
				$i=1;
				// the loop will stop at the end of the text
				while(1) {
					if(($pos = strpos($temp,'[')) === false) {
						break;
					}
					// removes characters till '['
					$temp=substr($temp,$pos+1);
					// quits the loop if there are no more blanks
					if(($pos = strpos($temp,']')) === false) {
						break;
					}
					// stores the found blank into the array
					$blanks[$i++]=substr($temp,0,$pos);
					// removes the character ']'
					$temp=substr($temp,$pos+1);
				}
			} 
		}
		else
		{
			unset($setWeighting);
		}
	}
	elseif($answerType == MATCHING)
	{
		for($i=1;$i <= $nbrOptions;$i++) {
			$option[$i]=trim($option[$i]);
			// checks if field is empty
			if(empty($option[$i])){
				$msgErr=$langFillLists;
				// clears options already recorded into the Answer object
				$objAnswer->cancel();
				break;
			} else {
				// adds the option into the object
				$objAnswer->createAnswer($option[$i],0,'',0,$i);
			}
		}
		$questionWeighting=0;
		if(empty($msgErr)) {
			for($j=1;$j <= $nbrMatches;$i++,$j++) {
				$match[$i]=trim($match[$i]);
				$weighting[$i]=abs($weighting[$i]);
				$questionWeighting+=$weighting[$i];
				// checks if field is empty
				if(empty($match[$i])) {
					$msgErr=$langFillLists;
					// clears matches already recorded into the Answer object
					$objAnswer->cancel();
					break;
				}
				// check if correct number
				else
				{
					// adds the answer into the object
					$objAnswer->createAnswer($match[$i],$sel[$i],'',$weighting[$i],$i);
				}
			}
		}
		if(empty($msgErr)) {
			// checks if the question is used in several exercises
			if($exerciseId && !isset($_POST['modifyIn']) && $objQuestion->selectNbrExercises() > 1) {
				$usedInSeveralExercises=1;
			} else {
				// all answers have been recorded, so we save them into the data base
				$objAnswer->save();
				// sets the total weighting of the question
				$objQuestion->updateWeighting($questionWeighting);
				$objQuestion->save($exerciseId);
				$editQuestion=$questionId;
				unset($_GET['modifyAnswers']);
			}
		}
	}
	elseif($answerType == TRUE_FALSE) {
		$questionWeighting = $nbrGoodAnswers = 0;
		for($i=1;$i <= $nbrAnswers;$i++) {
			$comment[$i] = trim($comment[$i]);
			$goodAnswer=($correct == $i)?1:0;
			
			if($goodAnswer) {
				$nbrGoodAnswers++;
				// a good answer can't have a negative weighting
				$weighting[$i]=abs($weighting[$i]);
				// calculates the sum of answer weighting 
				if($weighting[$i]) {
					$questionWeighting+=$weighting[$i];
				}
			} else {
				// a bad answer can't have a positive weighting
				$weighting[$i]=0-abs($weighting[$i]);
			}
			// checks if field is empty
			//if(empty($reponse[$i])) {
			// '0' might be a valid answer
			if(!isset($reponse[$i]) || ($reponse[$i] === null)) {
				$msgErr=$langGiveAnswers;
				// clears answers already recorded into the Answer object
				$objAnswer->cancel();
				break;
			} else {
				// adds the answer into the object
				$objAnswer->createAnswer($reponse[$i],$goodAnswer,$comment[$i],$weighting[$i],$i);
			}
		}  // end for()
		if(empty($msgErr)) {
			if(!$nbrGoodAnswers) {
				$msgErr=($answerType == TRUE_FALSE)?$langChooseGoodAnswer:$langChooseGoodAnswers;
				// clears answers already recorded into the Answer object
				$objAnswer->cancel();
			}
			// checks if the question is used in several exercises
			elseif($exerciseId && !isset($_POST['modifyIn']) && $objQuestion->selectNbrExercises() > 1)
			{
				$usedInSeveralExercises=1;
			} else {
				// saves the answers into the data base
				$objAnswer->save();
				// sets the total weighting of the question
				$objQuestion->updateWeighting($questionWeighting);
				$objQuestion->save($exerciseId);
				$editQuestion = $questionId;
				unset($_GET['modifyAnswers']);
			}
		}
	}
}
if(isset($_GET['modifyAnswers'])) {
	// construction of the Answer object
	$objAnswer=new Answer($questionId);
	$_SESSION['objAnswer'] = $objAnswer;
	if($answerType == UNIQUE_ANSWER || $answerType == MULTIPLE_ANSWER) {
		if(!isset($nbrAnswers))
		{
			$nbrAnswers=$objAnswer->selectNbrAnswers();
			$reponse=Array();
			$comment=Array();
			$weighting=Array();

			// initializing
			if($answerType == MULTIPLE_ANSWER)
			{
				$correct=Array();
			}
			else
			{
				$correct=0;
			}
			for($i=1;$i <= $nbrAnswers;$i++)
			{
				$reponse[$i]=$objAnswer->selectAnswer($i);
				$comment[$i]=$objAnswer->selectComment($i);
				$weighting[$i]=$objAnswer->selectWeighting($i);
				
				if($answerType == MULTIPLE_ANSWER)
				{
					$correct[$i]=$objAnswer->isCorrect($i);
				}
				elseif($objAnswer->isCorrect($i))
				{
					$correct=$i;
				}
			}
		}
		if(isset($lessAnswers))
		{
			$nbrAnswers--;
		}
		if(isset($moreAnswers))
		{
			$nbrAnswers++;
		}
		// minimum 2 answers
		if($nbrAnswers < 2)
		{
			$nbrAnswers=2;
		}
	}
	elseif($answerType == FILL_IN_BLANKS) {
		if(!isset($submitAnswers) && !isset($buttonBack)) {
			if(!isset($setWeighting)) {
				$reponse=$objAnswer->selectAnswer(1);
				list($reponse,$weighting)=explode('::',$reponse);
				$weighting=explode(',',$weighting);
				// keys of the array go from 1 to N and not from 0 to N-1
				array_unshift($weighting, 0);
			}
			elseif(!isset($_POST['modifyIn']))
			{
				$weighting = explode(',', $_POST['str_weighting']);
			}
		}
	}
	elseif($answerType == MATCHING) {
		if(!isset($nbrOptions) || !isset($nbrMatches))
		{
			$option=Array();
			$match=Array();
			$sel=Array();
			$nbrOptions = $nbrMatches = 0;
			// fills arrays with data from data base
			for($i=1;$i <= $objAnswer->selectNbrAnswers();$i++)
			{
				// it is a match
				if($objAnswer->isCorrect($i))
				{
					$match[$i]=$objAnswer->selectAnswer($i);
					$sel[$i]=$objAnswer->isCorrect($i);
					$weighting[$i]=$objAnswer->selectWeighting($i);
					$nbrMatches++;
				}
				// it is an option
				else
				{
					$option[$i]=$objAnswer->selectAnswer($i);
					$nbrOptions++;
				}
			}
		}

		if(isset($lessOptions))
		{
			// keeps the correct sequence of array keys when removing an option from the list
			for($i=$nbrOptions+1,$j=1;$nbrOptions > 2 && $j <= $nbrMatches;$i++,$j++)
			{
				$match[$i-1]=$match[$i];
				$sel[$i-1]=$sel[$i];
				$weighting[$i-1]=$weighting[$i];
			}

			unset($match[$i-1]);
			unset($sel[$i-1]);

			$nbrOptions--;
		}

		if(isset($moreOptions))
		{
			// keeps the correct sequence of array keys when adding an option into the list
			for($i=$nbrMatches+$nbrOptions;$i > $nbrOptions;$i--)
			{
				$match[$i+1]=$match[$i];
				$sel[$i+1]=$sel[$i];
				$weighting[$i+1]=$weighting[$i];
			}

			unset($match[$i+1]);
			unset($sel[$i+1]);

			$nbrOptions++;
		}

		if(isset($lessMatches))
		{
			$nbrMatches--;
		}

		if(isset($moreMatches))
		{
			$nbrMatches++;
		}

		// minimum 2 options
		if($nbrOptions < 2)
		{
			$nbrOptions=2;
		}

		// minimum 2 matches
		if($nbrMatches < 2)
		{
			$nbrMatches=2;
		}

	} elseif ($answerType == TRUE_FALSE) {
		if(!isset($nbrAnswers)) {
			$nbrAnswers=$objAnswer->selectNbrAnswers();
			//$nbrAnswers = 2;
			$reponse = Array();
			$comment = Array();
			$weighting = Array();
			$correct = 0;
			for($i=1;$i <= $nbrAnswers; $i++) {
				$reponse[$i] = $objAnswer->selectAnswer($i);
				$comment[$i] = $objAnswer->selectComment($i);
				$weighting[$i] = $objAnswer->selectWeighting($i);
				if($objAnswer->isCorrect($i)) {
					$correct=$i;
				}
			}
		}
		// minimum 2 answers
		if($nbrAnswers < 2) {
			$nbrAnswers=2;
		}
	}

	if(!isset($usedInSeveralExercises)) {
		if($answerType == UNIQUE_ANSWER || $answerType == MULTIPLE_ANSWER) {
			$tool_content .= "
			<form method='post' action='$_SERVER[SCRIPT_NAME]?course=$code_cours&amp;modifyAnswers=$_GET[modifyAnswers]'>
			<input type='hidden' name='formSent' value='1' />
			<input type='hidden' name='nbrAnswers' value='$nbrAnswers' />
		     
			<fieldset>
			<legend>$langQuestion</legend>
			  <b>".nl2br(q($questionName))."</b>
			</fieldset>
		     
			<fieldset>
			<legend>$langQuestionAnswers</legend>
			<table width=\"99%\" class=\"tbl\">
			<tr>
			  <td colspan=\"5\" >";
			
			if($answerType == UNIQUE_ANSWER) {
				$tool_content .= "$langUniqueSelect";
			}
			if($answerType == MULTIPLE_ANSWER) {
				$tool_content .= "$langMultipleSelect";
			}
			$tool_content .= "</td></tr>";

			// if there is a picture, display this
			if($okPicture) {
				$tool_content .= "
				<tr>
				  <td colspan='5' align=\"center\">"."<img src=\"".$picturePath."/quiz-".$questionId."\" alt=''></td>
				</tr>";
			}

			// if there is an error message
			if(!empty($msgErr)) {
				$tool_content .= "
				<tr>
				  <td colspan='5'><div class='caution'>$msgErr</div></td>
				</tr>";
			}
			$tool_content .= "
			<tr>
			  <th class='right' width='3'>$langID</th>
			  <th class='center' width='20'>$langTrue</th>
			  <th class='center'>$langAnswer</th>
			  <th class='center'>$langComment</th>
			  <th class='center' width='20'>$langQuestionWeighting</th>
			</tr>";
			for($i=1;$i <= $nbrAnswers;$i++) {
				$tool_content .="
				<tr>
				  <td class=\"right\" valign='top'>$i.</td>";
					if($answerType == UNIQUE_ANSWER) {
						$tool_content .= "
						<td class=\"center\" valign=\"top\"><input type=\"radio\" value=\"".$i."\" name=\"correct\" ";
						if(isset($correct) and $correct == $i) {
							$tool_content .= "checked=\"checked\" /></td>";
						} else {
							$tool_content .= "></td>";
						}
					} else {
						$tool_content .= "
						<td class=\"center\" valign=\"top\"><input type=\"checkbox\" value=\"1\" name=\"correct[".$i."]\" ";
						if ((isset($correct[$i]))&&($correct[$i])) {
							$tool_content .= "checked=\"checked\"></td>";
						} else {
							$tool_content .= " /></td>";
						}
					}
				
				$tool_content .= "
				<td>". text_area("reponse[$i]", 7, 40, @$reponse[$i], "class=''") ."</td>
				<td class='center'>". text_area("comment[$i]", 7, 25, @$comment[$i], "class=''") ."</td>
				<td valign='top' class='center'><input type='text' name=\"weighting[".$i."]\" size=\"5\" value=\"";
				if (isset($weighting[$i])) {
					$tool_content .= $weighting[$i];
				} else {	
					$tool_content .= 0;
				}
				$tool_content .= "\" /></td></tr>";
			}
			$tool_content .= "
			<tr>
			  <td class='left' colspan='2'>&nbsp;</td>
			  <td><b>$langSurveyAddAnswer :</b>&nbsp;
			    <input type='submit' name='lessAnswers' value='".q($langLessAnswers)."' />&nbsp;
			    <input type='submit' name='moreAnswers' value='".q($langMoreAnswers)."' />
			  </td>
			  <td colspan='3'>&nbsp;</td>
			</tr>
			<tr>
			  <td class='left' colspan='5'>&nbsp;</td>
			</tr>
			<tr>
			  <td class='right' colspan='5'>
			    <input type='submit' name='submitAnswers' value='".q($langCreate)."' />&nbsp;&nbsp;
			    <input type='submit' name='cancelAnswers' value='".q($langCancel)."' />
			  </td>
			</tr>
			</table>
			</fieldset>
			</form>";
		}
		elseif($answerType == FILL_IN_BLANKS) {
			$tool_content .= "
			<form name='formulaire' method='post' action='$_SERVER[SCRIPT_NAME]?course=$code_cours&amp;modifyAnswers=$_GET[modifyAnswers]'>";
			if(!isset($setWeighting)) {
				$tempSW = "";
			} else {
				$tempSW = $setWeighting;	
			}
			
			$tool_content .= "
			<input type='hidden' name='formSent' value='1' />\n
			<input type='hidden' name='setWeighting' value='$tempSW' />\n";
			if(!isset($setWeighting)) {
				$str_weighting = implode(',', $weighting);
				$tool_content .= "
				<input type='hidden' name='str_weighting' value='$str_weighting' />\n";
				$tool_content .= "
				<fieldset>
				<legend>$langQuestion</legend>
				 <b>". q($questionName) ."</b>
				 <br />";
				if($okPicture) {
					$tool_content .= "<div align=\"center\"><img src=\"".$picturePath."/quiz-".$questionId."\" alt=''></div>";
				}
				$tool_content .= "</fieldset>";
				$tool_content .= "
				<fieldset>
				<legend>$langQuestionAnswers</legend>
				<table class='tbl' width='99%'>
				<tr>
				  <td>$langTypeTextBelow, $langAnd $langUseTagForBlank :<br/><br/>
				  <textarea name='reponse' cols='70' rows='6'>";
				if(!isset($submitAnswers) && empty($reponse)) {
				      $tool_content .= $langDefaultTextInBlanks; 
				}
				else {
				      $tool_content .= htmlspecialchars($reponse);
				}
				$tool_content .= "</textarea></td></tr>";
			// if there is an error message
				if(!empty($msgErr)) {
					$tool_content .= "
					<tr>
					<td>
					  <table border='0' cellpadding='3' align='center' width='400' bgcolor='#FFCC00'>
					    <tr><td>$msgErr</td></tr>
					  </table>
					</td>
					</tr>";
				}
				$tool_content .= "<tr><td>
				  <input type='submit' name='submitAnswers' value='".q($langNext)." &gt;' />
				  &nbsp;&nbsp;<input type='submit' name='cancelAnswers' value='".q($langCancel)."' />
				</td>
				</tr>
				</table>
				</fieldset>
				</form>";
			} else {
				$tool_content .= "
				<input type=\"hidden\" name=\"blanks\" value=\"".htmlspecialchars(serialize($blanks))."\" />";
				$tool_content .= "
				<input type=\"hidden\" name=\"reponse\" value=\"".htmlspecialchars($reponse)."\" />";
				// if there is an error message
				if(!empty($msgErr)) {
					$tool_content .= "
					<table border='0' cellpadding='3' align='center' width='400'>
					<tr><td class='caution'>$msgErr</td></tr>
					</table>";
				} else {
					$tool_content .= "
					<fieldset>
					<legend>$langWeightingForEachBlank</legend>
					<table class='tbl' width='99%'>";
					foreach($blanks as $i=>$blank) {
						$tool_content .= "
						<tr>
						  <td class='right'><b>[".q($blank)."] :</b></td>"."
						  <td><input type='text' name='weighting[".($i-1)."]' size='5' value='".intval($weighting[$i-1])."' /></td>
						</tr>";
					}
					$tool_content .= "
					<tr>
					  <td>&nbsp;</td>
					  <td>
					    <input type='submit' name='buttonBack' value='&lt; ".q($langBack)."' />&nbsp;&nbsp;
					    <input type='submit' name='submitAnswers' value='".q($langCreate)."' />&nbsp;&nbsp;
					    <input type='submit' name='cancelAnswers' value='".q($langCancel)."' />
					  </td>
					</tr>
					</table>";
				}
				$tool_content .= "</fieldset></form>";
			}
	} //END FILL_IN_BLANKS !!!
	elseif($answerType == MATCHING) {
		$tool_content .= "
		    <form method='post' action='$_SERVER[SCRIPT_NAME]?course=$code_cours&amp;modifyAnswers=$_GET[modifyAnswers]'>
		    <input type='hidden' name='formSent' value='1' />
		    <input type='hidden' name='nbrOptions' value='$nbrOptions' />
		    <input type='hidden' name='nbrMatches' value='$nbrMatches' />
			
		    <fieldset>
		    <legend>$langQuestion</legend>
		    ". q($questionName) ."
		    </fieldset>	
		    
		    <fieldset>
		    <legend>$langAnswer</legend>
		    <table width='99%' class='tbl'>";

		if($okPicture) {
			$tool_content .= "
			<tr>
			  <td colspan='4' class='center'><img src='${picturePath}/quiz-${questionId}' alt=''></td>
			</tr>";
		}

	// if there is an error message
	if(!empty($msgErr)) {
		$tool_content .= "<tr>
		  <td colspan='4'>
		    <table border='0' cellpadding='3' align='center' width='400'>
		    <tr>
		      <td>$msgErr</td>
		    </tr>
		    </table>
		  </td>
		</tr>";
	}
	$listeOptions=Array();
	// creates an array with the option letters
	for($i=1,$j='A';$i <= $nbrOptions;$i++,$j++) {
		$listeOptions[$i]=$j;
	}

$tool_content .= "<tr><td colspan='2'><b>$langDefineOptions</b></td>
      <td class='center' colspan='2'><b>$langMakeCorrespond</b></td>
	</tr>
	<tr>
      <td>&nbsp;</td>
      <td><b>$langColumnA</b>: $langMoreLessChoices: <input type='submit' name='moreMatches' value='+' />&nbsp;
      <input type='submit' name='lessMatches' value='-' /></td>
      <td><div align='right'>$langColumnB</div></td>
      <td>$langQuestionWeighting</td>
    </tr>";
    
	for($j=1;$j <= $nbrMatches;$i++,$j++) {
		$tool_content .= "
		<tr>
                <td class='right'><b>".$j."</b></td>
                <td><input type='text' name='match[".$i."]' size='58' value=\"";
		if(!isset($formSent) && !isset($match[$i])) {			
                        $tool_content .= "";
                } else {
			@$tool_content .= str_replace('{','&#123;',htmlspecialchars($match[$i]));
                }
	
		$tool_content .= "\" /></td>
		<td><div align='right'><select name=\"sel[".$i."]\">";
		foreach($listeOptions as $key=>$val) {
			$tool_content .= "<option value=\"".$key."\" ";
			if((!isset($submitAnswers) && !isset($sel[$i]) 
				&& $j == 2 && $val == 'B') || @$sel[$i] == $key) 
				$tool_content .= "selected=\"selected\"";
				$tool_content .= ">".$val."</option>";
		} // end foreach()
	
	$tool_content .= "</select></div></td>
	  <td><input type=\"text\" size=\"3\" ".
		"name=\"weighting[".$i."]\" value=\"";
		if(!isset($submitAnswers) && !isset($weighting[$i])) {
			$tool_content .= '5'; 
		}
		else {
			$tool_content .= $weighting[$i];
		}
		$tool_content .= "\" /></td>
		</tr>";
	} // end for()

	$tool_content .= "
	    <tr>
	      <td class='right'>&nbsp;</td>
	      <td colspan='3'>&nbsp;</td>
	    </tr>
	    <tr>
	      <td>&nbsp;</td>
	      <td colspan='1'><b>$langColumnB</b>: $langMoreLessChoices: <input type='submit' name='moreOptions' value='+' />
	      &nbsp;<input type='submit' name='lessOptions' value='-' />
	      </td>
	      <td>&nbsp;</td>
	    </tr>";

		foreach($listeOptions as $key=>$val) {
			$tool_content .= "
			<tr>
			  <td class='right'><b>".$val."</b></td>
			  <td><input type='text' ".
				"name='option[".$key."]' size='58' value='";
			if(!isset($formSent) && !isset($option[$key])) {				
                                $tool_content .= "";
                        } else {
				@$tool_content .= str_replace('{','&#123;',htmlspecialchars($option[$key]));
                        }
			$tool_content .= "' /></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		      </tr>";
		} // end foreach()
			$tool_content .= "
			<tr>
			  <td>&nbsp;</td>
			  <td colspan='3'><input type='submit' name='submitAnswers' value='".q($langCreate)."' />
					    &nbsp;&nbsp;<input type='submit' name='cancelAnswers' value='".q($langCancel)."' />
			  </td>
			</tr>
			</table>	
			</fieldset>
			</form>";
	} // end of MATCHING
		
		elseif ($answerType == TRUE_FALSE) {
			$tool_content .= "
			<form method='post' action='$_SERVER[SCRIPT_NAME]?course=$code_cours&amp;modifyAnswers=$_GET[modifyAnswers]'>
			<input type='hidden' name='formSent' value='1' />
			<input type='hidden' name='nbrAnswers' value='$nbrAnswers' />
			<fieldset>
			 <legend>$langQuestion</legend>
			   <b>".nl2br(q($questionName))."</b>";
			// if there is a picture, display this
			if($okPicture) {
				$tool_content .= "
				<div align=\"center\">"."<img src=\"".$picturePath."/quiz-".$questionId."\" alt=''></div>";
			}
			$tool_content .="
			</fieldset> 
			<fieldset>
			 <legend>$langQuestionAnswers</legend>";
			// if there is an error message
			if(!empty($msgErr)) {
				$tool_content .= "<div class='caution'>$msgErr</div>";
			}
			$tool_content .= "
			<table class='tbl'>
			<tr>
			  <td colspan='2'><b>$langAnswer</b></td>
			  <td class='center'><b>$langComment</b></td>
			  <td class='center'><b>$langQuestionWeighting</b></td>
			</tr>";		
			$tool_content .="
			<tr>
			  <td valign='top' width='30'>$langCorrect</td>
			  <td valign='top' width='1'><input type='radio' value='1' name='correct' ";
			if(isset($correct) and $correct == 1) {
				$tool_content .= "checked='checked' /></td>";
			} else {
				$tool_content .= "></td>";
			}
			
			$tool_content .= "
      <input type='hidden' name='reponse[1]' value='".q($langCorrect)."' />
      <td>".
			text_area("comment[1]", 4, 40, @$comment[1], "class=''")
			."</td>
      <td valign='top'><input type='text' name='weighting[1]' size='5' value=\"";
			if (isset($weighting[1])) {
				$tool_content .= $weighting[1];
			} else {	
				$tool_content .= 0;
			}
			$tool_content .= "\" /></td></tr>";		
			$tool_content .="<tr>";
			$tool_content .= "
			<td valign='top'>$langFalse</td>
			<td valign='top'><input type='radio' value='2' name='correct' ";
			if(isset($correct) and $correct == 2) {
				$tool_content .= "checked='checked' /></td>";
			} else {
				$tool_content .= "></td>";
			}
			$tool_content .= "
			<input type='hidden' name='reponse[2]' value='".q($langFalse)."'>
		      <td>".
			text_area("comment[2]", 4, 40, @$comment[2], "class=''")
			."</td>
			<td valign='top'><input type='text' name='weighting[2]' size='5' value=\"";
			if (isset($weighting[2])) {
				$tool_content .= $weighting[2];
			} else {	
				$tool_content .= 0;
			}
			$tool_content .= "\" /></td></tr>";
			$tool_content .= "
		<tr>
		  <td colspan='2'>&nbsp;</td>
		  <td align='center'>
		     <input type='submit' name='submitAnswers' value='".q($langCreate)."' />&nbsp;&nbsp;
		     <input type='submit' name='cancelAnswers' value='".q($langCancel)."' />
		  </td>
		  <td>&nbsp;</td>
		</tr>
		</table>
	       </fieldset>
	       </form>";
		}
	}
}
?>
