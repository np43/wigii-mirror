<?php
/**
 *  This file is part of Wigii (R) software.
 *  Wigii is developed to inspire humanity. To Humankind we offer Gracefulness, Righteousness and Goodness.
 *  
 *  Wigii is free software: you can redistribute it and/or modify it 
 *  under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, 
 *  or (at your option) any later version.
 *  
 *  Wigii is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; 
 *  without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
 *  See the GNU General Public License for more details.
 *
 *  A copy of the GNU General Public License is available in the Readme folder of the source code.  
 *  If not, see <http://www.gnu.org/licenses/>.
 *
 *  @copyright  Copyright (c) 2016  Wigii.org
 *  @author     <http://www.wigii.org/system>      Wigii.org 
 *  @link       <http://www.wigii-system.net>      <https://github.com/wigii/wigii>   Source Code
 *  @license    <http://www.gnu.org/licenses/>     GNU General Public License
 */

/**
 * Base class for all Wigii Models
 * Created by LWR on 7 july 2009
 */
abstract class Model
{

	/**
	 * this method return a string representing all the values
	 * of a model.
	 * Warning this method needs to be used carefully because we could
	 * display information we don't want to see...
	 */
	public function displayDebug($nb = 0, $noHTML=false){
		global $private_put_objectDone;
		if($nb == 0) $private_put_objectDone = array();
		
		$idObj = spl_object_hash($this);
		$name = "CLASS: ".get_class($this); //since php 5.2 we cannot find the object id... ." ID:".str_replace("Object id", "", "".$this);
		//$vars = get_object_vars($this);
		
		if($noHTML) $nbspText = " ";
		else $nbspText = " &nbsp; ";
		
		$nbSp = 2;

		$returnValue = "";
		$returnValue .= str_repeat("|".str_repeat($nbspText, $nbSp),$nb).$name;

		//if the object is already displayed, then do nothing to not make recursion
		if($private_put_objectDone[$idObj] != null){
			return $returnValue." (already displayed)\n";
		}
		$returnValue .= "\n";
		$private_put_objectDone[$idObj] = $idObj;
		
		$vars = (array)$this; //like this we really take everything including the heritage
		foreach ($vars as $var_name => $var) {
			//the var_name contains a very strange character just after the class definition
			//so we use that to cut off the name
			$returnValue .= str_repeat("|".str_repeat($nbspText, $nbSp),$nb).preg_replace('/^.*[^a-zA-Z0-9_-]/',"", $var_name);
			$retour_ligne = "";
			if ((is_array($var) || is_object($var)) && $var != null) {
				$returnValue .= " :\n";
				//$retour_ligne = "\n";
			} else {
				$returnValue .= " = ";
				$retour_ligne = "\n";
			}
//			eput($var);
//			eput(is_object($var));
//			eput(method_exists($var, "displayDebug"));
			if(is_object($var) && method_exists($var, "displayDebug")){
				$returnValue .= $var->displayDebug($nb+1, $noHTML).$retour_ligne;
			} else {
				$returnValue .= private_put($var, null, false, $nb+1, $noHTML).$retour_ligne;
			}
		}
		return $returnValue;
	}

	// Formatting functions

	/**
	 * Returns a real bool based on the value of $var
	 */
	protected function formatBoolean($var){ return formatBoolean($var);}

	/**
	 * Returns a real null if $var reduces to null, else returns $var
	 */
	protected function formatValue($var){return formatValue($var);}
	//protected function formatMultipleValues($var){return formatMultipleValues($var);}
	protected function formatToString($var){return formatToString($var);}
	protected function formatToEmailLabel($var){return formatToEmailLabel($var);}
//	protected function formatToEmailArray($var){return formatToEmailArray($var);}

	/**
     * alias of t (translate) method of the Translation service
     */
	public function t($text, $node=null) {
	    return ServiceProvider::getTranslationService()->t(ServiceProvider::getAuthenticationService()->getMainPrincipal(), $text, $node);
	}
}


/**
 * Formatting functions
 * we need those functions also outside of Model, this is why there are global functions
 */
/**
 * Returns a real bool based on the value of $var
 */
function formatBoolean($var)
{
	if($var === "0" || $var === "" ||
		$var === null ||
		$var === "FALSE" ||
		$var === "false" ||
		$var === "NULL" || ///warning, if you don't put === then with a true you will always equal to a string
		$var === false)
		return false;
	else return true;
}

/**
 * Returns a real null if $var reduces to null, else returns $var
 */
function formatValue($var)
{
	if($var === "NULL") return null; //warning, if you don't put === then a bool true will equal the string "NULL"
	else return $var;
}

//function formatMultipleValues($var){
//	if(is_string($var) && strpos($var, "XAGU___XAGU")!==false){
//		$var = explode("XAGU___XAGU", $var);
//		$var = array_combine($var, $var);
//		unset($var[null]);
//		return $var;
//	}
//	if(is_array($var)){
//		$var = array_combine($var, $var);
//		unset($var[null]);		
//		return $var;
//	}
//	return $var;
//}

function formatToString($var){
	if(is_string($var)){
		if(strpos($var, "XAGU___XAGU")!==false){
			$temp = explode("XAGU___XAGU", $var);
			$temp = array_combine($temp, $temp);
			unset($temp[null]);
			unset($temp["none"]);
			$var = implode(", ", $temp);
		}
	} else if (is_array($var)){
		$temp = array();
		foreach($var as $val) if($val!=null) $temp[]=$val;
		$var = implode(", ", $temp);
	} else if ($var === NULL) {
		$var = "";
	} else if ($var === false) {
		$var = "FALSE";
	} else if ($var === true) {
		$var = "TRUE";
	}
	return $var;
}

function formatToEmailLabel($var){
	return str_replace(array("\n", "\r", ";", ",", "[", "{", "}", "]", "@", ":"), array(" ", "", " -", " -", "(", "(", ")", ")", "-", "-"), $var);
}

function validateEmail($email){
	$allow = "[_A-Za-z0-9-]";
	$regex_email = '/^'.$allow.'+(\.'.$allow.'+)*@'.$allow.'+(\.'.$allow.'+)*(\.'.$allow.'{2,})+$/i';
	
	if(!empty($email) && preg_match($regex_email, $email) == 0) return false;
	return true;
}

function validateEmails($emails){
	$allow = "[_A-Za-z0-9-]";
	/*
	$allowSep = "[ \/,;\:\n\r\t]*";
	$regex_email = '/^('.$allow.'+(\.'.$allow.'+)*@'.$allow.'+(\.'.$allow.'+)*(\.'.$allow.'{2,})+'.$allowSep.')*$/i';
	
	if(!empty($emails) && preg_match($regex_email, $emails) == 0) return false;
	return true;
	*/
	$allowSep = "[ \/,;\:\n\r\t]+";
	if(!empty($emails)) {
		$emailsArr = preg_split('/'.$allowSep.'/i', $emails);
		$regex_email = '/^'.$allow.'+(\.'.$allow.'+)*@'.$allow.'+(\.'.$allow.'+)*(\.'.$allow.'{2,})+$/i';
		if(!empty($emailsArr)) {
			$invalidEmails = preg_grep($regex_email, $emailsArr, 1);
			if(!empty($invalidEmails)) {
				return false;
			}
		}		
	}
	return true;
}

//function formatToEmailArray($emails){
//	$emails = preg_replace("/^[ \/;,\n\r\t]+/", "", $emails);
//	$emails = preg_replace("/[ \/;,\n\r\t]+$/", "", $emails);		
//	//on split en faisant attention s'il y a des séparateurs multiple vide
//	$emails = preg_split("/[ \/,;\n\r\t]+/", $emails);
//	//on supprime les doublons d'email:
//	$emails= array_combine($emails, $emails);
//	unset($emails[null]);
//	unset($emails[""]);
//	return $emails;
//}