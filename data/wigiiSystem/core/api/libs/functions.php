<?php
/**
 *  This file is part of Wigii.
 *
 *  Wigii is free software: you can redistribute it and\/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Wigii is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Wigii.  If not, see <http:\//www.gnu.org/licenses/>.
 *
 *  @copyright  Copyright (c) 2012 Wigii 		 http://code.google.com/p/wigii/    http://www.wigii.ch
 *  @license    http://www.gnu.org/licenses/     GNU General Public License
 */

/*
 * Created on 21 juil. 09 by LWR
 */

//fonction avec tous les paramètres pour le put
$private_put_objectDone = null;
function private_put($array, $maxLevel = NULL, $recursif = false, $niveau = 0, $noHTML = false) {
	global $private_put_objectDone;
	if($niveau == 0 && !$recursif) $private_put_objectDone = array();

	$nbSp = 2;
	if ($noHTML)
		$nbspText = " ";
	else
		$nbspText = " &nbsp; ";
	if ($maxLevel !== NULL && $niveau > $maxLevel)
		return;
	$result = "";
	if(is_object($array)){
		if(is_object($array) && method_exists($array, "displayDebug")){
			return $array->displayDebug($niveau, $noHTML);
		}
		$idObj = spl_object_hash($array);
		//if the object is already displayed, then do nothing to not make recursion
		if($private_put_objectDone[$idObj] != null){
			return str_repeat("|".str_repeat($nbspText, $nbSp), $niveau)."CLASS: ".get_class($array)." (already displayed)\n";
		}
		$private_put_objectDone[$idObj] = $idObj;
	}
	//Object Part
	if (is_object($array) && !is_a($array, "Iterator")) {
		//on ajoute l'id de l'objet lorsqu'on l'affiche
		$name = get_class($array);
		$vars = get_object_vars($array);
		$result .= str_repeat("|".str_repeat($nbspText, $nbSp), $niveau)."CLASS: $name\n";
		foreach ($vars as $var_name => $var) {
			$result .= str_repeat("|".str_repeat($nbspText, $nbSp), $niveau +1).private_put($var_name, $maxLevel, true, 0, $noHTML);
			$temp_niveau = $niveau +1;
			$retour_ligne = "";
			if ((is_array($var) || is_object($var)) && $var != null) {
				$temp_niveau += 1;
				$result .= " :\n";
			} else {
				$result .= " = ";
				$retour_ligne = "\n";
			}
			if(is_object($var) && method_exists($var, "displayDebug")){
				$result .= $var->displayDebug($temp_niveau, $noHTML).$retour_ligne;
			} else {
				$result .= private_put($var, $maxLevel, true, $temp_niveau, $noHTML).$retour_ligne;
			}
//			$result .= private_put($var, $maxLevel, true, $temp_niveau, $noHTML).$retour_ligne;
		}
		if ($recursif)
			return $result;
		$result .= "\n";
		return $result;
	}
	//Array Part
	if (is_array($array) || is_a($array, "Iterator")) {
		//reset($array);
		if ($array == NULL)
			return "EmptyArray";
		foreach ($array as $key => $value) {
			if ((!is_array($value) && !is_object($value)) || (is_array($value) && $value == null)) {
				$result .= str_repeat("|".str_repeat($nbspText, $nbSp), $niveau).$key." = ".private_put($value, $maxLevel, true, 0, $noHTML)."\n";
			} else {
				$result .= str_repeat("|".str_repeat($nbspText, $nbSp), $niveau).$key." :\n";

				if(is_object($value) && method_exists($value, "displayDebug")){
					$result .= $value->displayDebug($niveau+1, $noHTML);
				} else {
					$result .= private_put($value, $maxLevel, true, $niveau +1, $noHTML);
				}
//				$result .= private_put($value, $maxLevel, true, $niveau +1, $noHTML);
			}
		}

		return $result;
	}
	//Normal Part
	if ($array === NULL)
		$array = "NULL";
	if ($array === "")
		$array = "empty";
	if ($array === false)
		$array = "FALSE";
	if ($array === true)
		$array = "TRUE";

	return $array;
}

/**
* public
* construit un string représentant un élément php bien formaté
* @return string déjà avec les <br>
* @param $element
*/
function alert($element) {
	return str_replace("&nbsp; "," ", private_put($element, null, false, 0, true));
}
function put($element, $maxLevel = NULL) {
	return str_replace("\n","<br>", private_put($element, $maxLevel));
}
function eput($element, $maxLevel = NULL) {
	echo put($element, $maxLevel);
}

$___MYSPecialPUtFile = null;
/**
 * cette fonction écrit dans un fichier log à la racine du serveur le contenu de $st
 * @param dontStamp = false bool, si vrai n'ajoute pas un stamp de la date du username etc avant d'écrire dans le fichier $st
 */
function fput($st, $dontStamp = false) {
	global $___MYSPecialPUtFile;
	$p = ServiceProvider::getAuthenticationService()->getMainPrincipal();
	$fileName = dirname($_SERVER["SCRIPT_FILENAME"])."/log.txt";
	$result = "";
	if (!$dontStamp)
		$result = "*** date:".date("d.m.Y H:i:s")." username: ".$p->getUsername()." wigiiNamespace: ".$p->getWigiiNamespace()->getWigiiNamespaceName()." ***\n";
	$result .= private_put($st, NULL, FALSE, 0, true);
	$result .= "\n\n";
	if ($___MYSPecialPUtFile === null)
		$___MYSPecialPUtFile = fopen(dirname($_SERVER["SCRIPT_FILENAME"])."/log.txt", "a");
	else
		$___MYSPecialPUtFile = fopen(dirname($_SERVER["SCRIPT_FILENAME"])."/log.txt", "a");
	fwrite($___MYSPecialPUtFile, $result);
	fclose($___MYSPecialPUtFile);
}

/**
 * Creates a new child element in the given parent element
 * @param SimpleXMLElement $parent parent node
 * @param String $name name of the child element
 * @param String $value text value of the child element
 * @return SimpleXMLElement returns a pointer on the created child element
 */
function simplexml_addChild($parent, $name, $value = '') {
	$new_child = new SimpleXMLElement("<$name>$value</$name>");
	$node1 = dom_import_simplexml($parent);
	$dom_sxe = dom_import_simplexml($new_child);
	$node2 = $node1->ownerDocument->importNode($dom_sxe, true);
	$node1->appendChild($node2);
	return simplexml_import_dom($node2);
}
/**
 * Appends an existing element (with its whole subtree) as a new child element of the given parent
 * @param SimpleXMLElement $parent the parent node to which to add the child node
 * @param SimpleXMLElement $child the node to be added as a childe node
 * @param SimpleXMLElement $beforenode if set, then the node will be inserted before this node.
 * @return SimpleXMLElement returns a pointer on the inserted child node.
 */
function simplexml_appendChild($parent, $child, $beforenode=null){
	$node1 = dom_import_simplexml($parent);
	$dom_sxe = dom_import_simplexml($child);
	$node2 = $node1->ownerDocument->importNode($dom_sxe, true);

	if($beforenode===null) $node1->appendChild($node2);
	else $node1->insertBefore($node2, dom_import_simplexml($beforenode));
	//fput($parent->asXML());
	return simplexml_import_dom($node2);
}
/**
 * Appends all the children of an existing parent, as children of another given parent.
 * @param SimpleXMLElement $parent the parent node to which to add the children
 * @param SimpleXMLElement $childrenParent the parent node from which to copy the children
 * @param SimpleXMLElement $beforenode if set, then the children will be inserted before this node.
 * @return SimpleXMLElement returns a pointer on the parent containing the new children.
 */
function simplexml_appendChildren($parent, $childrenParent, $beforenode=null) {
	$parent = dom_import_simplexml($parent);
	if($beforeNode !== null) $beforeNode = dom_import_simplexml($beforeNode);
	foreach($childrenParent->children() as $child) {
		$child = dom_import_simplexml($child);
		$child = $parent->ownerDocument->importNode($child, true);
		if($beforenode === null) $parent->appendChild($child);
		else $parent->insertBefore($child, $beforeNode);
	}
	return simplexml_import_dom($parent);
}
/**
 * Replaces an existing node1 in an xml tree by another node2 at the same place.
 * If node1 is the root element, then returns a clone of node2.
 * @param SimpleXMLElement $node1 the existing node to replace
 * @param SimpleXMLElement $node2 the replacement node
 * @return SimpleXMLElement a pointer on the replaced node.
 */
function simplexml_replaceNode($node1, $node2) {
	$node1 = dom_import_simplexml($node1);
	if($node1 instanceof DOMDocument || $node1->parentNode === null) return simplexml_load_string($node2->asXML());
	$nextSibling = $node1->nextSibling;
	$parent = $node1->parentNode;
	$node2 = dom_import_simplexml($node2);
	$node2 = $parent->ownerDocument->importNode($node2, true);
	if($nextSibling === null) $node2 = $parent->appendChild($node2);
	else $node2 = $parent->insertBefore($node2, $nextSibling);
	$parent->removeChild($node1);
	return simplexml_import_dom($node2);
}
/**
 * Replaces an existing node1 in an xml tree by the children of another node2 at the same place.
 * If node1 is the root element, then throws ServiceException::INVALID_ARGUMENT.
 * @param SimpleXMLElement $node1 the existing node to replace
 * @param SimpleXMLElement $node2ChildrenParent the replacement node from which to take children
 * @return SimpleXMLElement returns a pointer on the parent containing the new children.
 */
function simplexml_replaceNodeWithChildren($node1, $node2ChildrenParent) {
	$node1 = dom_import_simplexml($node1);
	if($node1 instanceof DOMDocument || $node1->parentNode === null) throw new ServiceException('cannot replace a root element with several children', ServiceException::INVALID_ARGUMENT);
	$nextSibling = $node1->nextSibling;
	$parent = $node1->parentNode;
	foreach($node2ChildrenParent->children() as $child) {
		$child = dom_import_simplexml($child);
		$child = $parent->ownerDocument->importNode($child, true);
		if($nextSibling === null) $parent->appendChild($child);
		else $parent->insertBefore($child, $nextSibling);
	}
	$parent->removeChild($node1);
	return simplexml_import_dom($parent);
}
function simplexml_addAttribute($parent, $name, $value = '') {
	$node1 = dom_import_simplexml($parent);
	$node1->setAttribute($name, $value);
	return simplexml_import_dom($node1);
}
function simplexml_removeNode($node) {
	$child = dom_import_simplexml($node);
	$parent = $child->parentNode;
	return $parent->removeChild($child);
}

/**
 * Check qu'un string ne contient pas de caractère spéciaux et qu'il a une longueur correcte
 * accepte les espaces les - et les _ et les . @
 * par rapport à des valeurs Min et Max.
 * il accepte aussi les caractères accentués (par défaut false)
 * @param string $string chaine de caractère à checker
 * @param int $minChars nombre minimal de caractères pour la chaine
 * @param int $maxChars nombre maximal de caractères pour la chaine
 * @return bool true si la chaine est correcte
 */
function checkNoSpecialCharsString($string, $minChars, $maxChars, $acceptAccent = false) {
	if($acceptAccent){
		return preg_match('/^['.ACCENT_list.'A-Za-z0-9\@\._-\s]{'.$minChars.','.$maxChars.'}$/', $string);
	} else {
		return preg_match('/^[A-Za-z0-9\@\._-\s]{'.$minChars.','.$maxChars.'}$/', $string);
	}
}
function stripAccents($string){
	return strtr($string, array("'"=>"-","é"=>"e", "è"=>"e", "ë"=>"e", "ê"=>"e", "à"=>"a", "á"=>"a", "â"=>"a", "ä"=>"a", "ó"=>"o", "ò"=>"o", "ô"=>"o", "ö"=>"o", "ú"=>"u", "ù"=>"u", "û"=>"u", "ü"=>"u", "í"=>"i", "ì"=>"i", "ï"=>"i", "î"=>"i", "É"=>"E", "È"=>"E", "Ë"=>"E", "Ê"=>"E", "Á"=>"A", "À"=>"A", "Ä"=>"A", "Â"=>"A", "Ó"=>"O", "Ò"=>"O", "Ö"=>"O", "Ô"=>"O", "Ú"=>"U", "Ù"=>"U", "Ü"=>"U", "Û"=>"U", "Í"=>"I", "Ì"=>"I", "Î"=>"I", "Ï"=>"I", "ñ"=>"n", "Ñ"=>"N", "õ"=>"o", "Õ"=>"O"));
}
/**
 * convert a string like 134.5.67.78
 * in 134005067078
 */
function ipToStr($ip) {
	$ip = explode(".", $ip);
	$ipSt = "";
	foreach ($ip as $num) {
		$num = (int) $num;
		if ($num < 10)
			$num = "00$num";
		else
			if ($num < 100)
				$num = "0$num";
			else
				$num = "".$num;
		$ipSt .= $num;
	}
	return $ipSt;
}

//return a string representing the int number with 0 in front if not enough digits
//if nb of int digit > length return false
function intToString($int, $length) {
	$r = "".$int;
	$l = strlen($r);
	if($l < $length){
		$r = str_repeat("0", ($length-$l)).$r;
	} else if($l > $length){
		return false;
	}
	return $r;
}

//deprecated, this is no more used
//function replaceWordChar($value){
//	//to know the code for new special char
//	//just echo the value and find the succession of character
//	//which define the one and do the replace
//	$value = preg_replace('/â\x80\x99/', "'", $value);
//	$value = preg_replace('/â\x80\x93/', "-", $value);
//	$value = preg_replace('/Å\x93/', "oe", $value);
//	$value = preg_replace('/â\x80¦/', "...", $value);
//	return $value;
//}


// splits a string into an array of tokens, delimited by delimiter char
// tokens in input string containing the delimiter character or the literal escape character are surrounded by a pair of escape characteres
// a literal escape character is produced by the escape character appearing twice in sequence
// default delimiter character and escape character are suitable for Excel-exported CSV formatted lines
if(!function_exists("str_getcsv")){
function str_getcsv($str, $delimiterChar = ',', $escapeChar = '"') {
	$len = strlen($str);
	$tokens = array();
	$i = 0;
	$inEscapeSeq = false;
	$currToken = '';
	while ($i < $len) {
		$c = substr($str, $i, 1);
		if ($inEscapeSeq) {
			if ($c == $escapeChar) {
				// lookahead to see if next character is also an escape char
				if ($i == ($len - 1)) {
					// c is last char, so must be end of escape sequence
					$inEscapeSeq = false;
				} else if (substr($str, $i + 1, 1) == $escapeChar) {
					// append literal escape char
					$currToken .= $escapeChar;
					$i++;
				} else {
					// end of escape sequence
					$inEscapeSeq = false;
				}
			} else {
				$currToken .= $c;
			}
		} else {
			if ($c == $delimiterChar) {
				// end of token, flush it
				array_push($tokens, trim($currToken));
				$currToken = '';
			} else if ($c == $escapeChar) {
				// begin escape sequence
				$inEscapeSeq = true;
			} else {
				$currToken .= $c;
			}
		}
		$i++;
	}
	// flush the last token
	array_push($tokens, trim($currToken));
	return $tokens;
}
}

//rempli le tableau $tab avec tous les numéros contenu dans $string
//qui ont plus de 7 caractères et peuvent être séparé par des
//caractère blancs. $string est modifié. chaque numéro trouvé sont extrait
//de la chaîne.
function str_numExtract(& $string, & $tab) {
	$matches = array ();
	if ($tab == null)
		$tab = array ();
	$search = array (" ", "\t", "\n", "\r", "\0", "\x0B");
	preg_match_all('/(^|\s)([\d|\s]{7,})(\s|$)/', $string, $matches);
	foreach ($matches[0] as $key => $value) {
		$string = str_replace($value, " ", $string);
		$tempTab = str_replace($search, "", $value);
		$tempTab = str_split($tempTab);
		$tab[] = implode("%", $tempTab);
	}
}

//supprime les éléments entre "" et les mets dans un tableau
function str_quoteExtract(& $string, & $tab) {
	//eput($string);
	$matches = array ();
	if ($tab == null)
		$tab = array ();
	preg_match_all('/"([^\"]+)"/', $string, $matches);
	foreach ($matches[1] as $key => $value) {
		//mb_regex_encoding("UTF-8");
		//$string = mb_ereg_replace($matches[0][$key], " ", $string);
		if ($value != null) {
			$string = str_replace($matches[0][$key], " ##### ", $string); //on remet à la place du string entre "" un repère indiquant que l'on doit aller recherché l'élément au début du tableau
			$tab[] = addslashes($value);
		}
	}
	//on ne traite pas l'appostrophe simple car il peut être utiliser
	//comme apostrophe justement dans un texte...
	//	preg_match_all("/'([^']+)'/", $string, $matches);
	//	foreach($matches[1] as $key=>$value){
	//		//mb_regex_encoding("UTF-8");
	//		//$string = mb_ereg_replace($matches[0][$key], " ", $string);
	//		$string = str_replace($matches[0][$key], " ", $string);
	//		if ($value != null) $tab[]=addslashes($value);
	//	}
	//$exec->debug(put($tab));
	//$exec->debug(alert($matches));
}
//ajoute chaque ( ou ) en tant que token
function str_parenthesisExtract(& $string, & $tab) {
//	eput($string);
	$string = str_replace("(", " ( ", $string);
	$string = str_replace(")", " ) ", $string);
}

//rempli le tableau avec les éléments séparé par des espaces
function str_spaceSplit($string, & $tab) {
	$matches = array ();
	if ($tab == null)
		$tab = array ();
	preg_match_all('/([^\s]+)/', $string, $matches);
//	echo alert($matches);
	foreach ($matches[1] as $key => $value) {
		if ($value == "#####") {
			//on pop le premier élément du tableau et on le met à cet endroit
			//ainsi on le remet à sa vrai place...
			$value = array_shift($tab);
		}
		if ($value != null)
			$tab[] = addslashes($value);
	}
}

//cette fonction traite un champ de recherche.
//Elle renvoie un tableau composé des éléments à rechercher
//les numéros de plus de 7 caractère sont extrait et les espaces entre sont enlevé. Des
// % sont placé entre chaque numéro. Les autres éléments sont séparé par les espaces
// sauf s'il sont entre "".
function str_searchFilter($string) {
	$tab = array ();
	str_quoteExtract($string, $tab);
	str_parenthesisExtract($string, $tab);
	str_numExtract($string, $tab);
	str_spaceSplit($string, $tab);
	return $tab;
}

function deleteDirectory($dir, $ignoreWarning=false) {
 if (!file_exists($dir)) return true;
 if (!is_dir($dir)) {
  if($ignoreWarning) return @unlink($dir);
  else return unlink($dir);
 }
 foreach (scandir($dir) as $item) {
  if ($item == '.' || $item == '..') continue;
  if (!deleteDirectory($dir.DIRECTORY_SEPARATOR.$item, $ignoreWarning)) return false;
 }
 if($ignoreWarning) return @rmdir($dir);
 else return rmdir($dir);
}

// String update operations

/**
 * SET: replaces existing value with new one
 */
define('SUPDOP_SET', 1);
/**
 * CAT: concatenates new value to existing list of values using separator.
 */
define('SUPDOP_CAT', 2);
/**
 * ADD: concatenates new value to existing list of values using separator,
 * only if new value does not exist in the list
 */
define('SUPDOP_ADD', 3);
/**
 * DEL: removes new value from existing list of values
 * if list becomes empty, then stores NULL
 */
define('SUPDOP_DEL', 4);
/**
 * SET_IF_NULL: set existing value only if existing value is NULL
 */
define('SUPDOP_SET_IF_NULL', 5);

/**
 * Updates the value of a string in a case insensitive way
 * string: existing string to update
 * newValue: the new value to be updated, can be a string or an array.
 * updateOp: update operation, see constants above. One of SET,ADD,CAT,DEL
 * readSep: separator regExp used in preg_split when reading existing values or newValue string
 * writeSep: separator to be used when adding new value to existing string
 * returns the updated string
 */
function str_update($string, $newValue, $updateOp, $readSep='/[\\s,;\\/:\\\\]+/', $writeSep=',')
{
	$values = preg_split($readSep, $string, null, PREG_SPLIT_NO_EMPTY);
	if(!is_array($newValue)) $newValue = preg_split($readSep, $newValue, null, PREG_SPLIT_NO_EMPTY);
	switch($updateOp)
	{
		case SUPDOP_SET: $values = $newValue; break;
		case SUPDOP_CAT: $values = array_merge($values, $newValue); break;
		case SUPDOP_ADD:
			$valCount = count($values);
			$newValCount = count($newValue);
			if($valCount <= 1 && $values[0] == '')
			{
				if($newValCount <= 1 && $newValue[0] == '') $values = array();
				else {
					$values = array_combine(array_map('strtolower', $newValue), $newValue);
				}
			}
			else
			{
				if($newValCount <= 1 && $newValue[0] == ''){
					$values = array_combine(array_map('strtolower', $values), $values);
				} else {
					$values = array_combine(array_map('strtolower', $values), $values) + array_combine(array_map('strtolower', $newValue), $newValue);
				}
			}
			break;
		case SUPDOP_DEL:
			//the diff needs to be done in a insensitive case way, because the DB is case insensitive
			$values = array_udiff($values, $newValue, 'strcasecmp'); //array_diff($values, $newValue);
			break;
		default: throw new ServiceException("$updateOp is an invalid string update operator, should be one of SET=1, CAT=2, ADD=3, DEL=4", ServiceException::INVALID_ARGUMENT);
	}
	return implode($writeSep, $values);
}

/**
 * replace the standad addslahes and adds the management of array (recursive)
 */
function addslashesextended(&$arr_r) {
	if(!isset($arr_r)) return;
	if(is_array($arr_r)) {
		foreach ($arr_r as &$val)
			is_array($val) ? addslashesextended($val):$val=addslashes($val);
		unset($val);
	} else {
		$arr_r=addslashes($arr_r);
	}
}
/**
 * replace the standad addslahes and adds the management of array (recursive)
 */
function stripslashesextended(&$arr_r) {
	if(!isset($arr_r)) return;
	if(is_array($arr_r)) {
		foreach ($arr_r as &$val)
			is_array($val) ? stripslashesextended($val):$val=stripslashes($val);
		unset($val);
	} else {
		$arr_r=stripslashes($arr_r);
	}
}

//function array2str($array,$level=1) {
//  $str = array();
//  foreach($array as $key=>$value) {
//	$nkey = base64_encode($key);
//	$nvalue = is_array($value)?'$'.base64_encode(array2str($value)) : (string)base64_encode($value);
//	$str[] = $nkey.'&'.$nvalue;
//	//printf("Encoded %s,%s to %s,%s\n",$key,$value,$nkey,$nvalue);
//  }
//  return implode('|',$str);
//}
//function str2array($str) {
//  $rest = array();
//  if(strpos($str,'|')>0) {
//	$array = explode('|',$str);
//  } else $array=array($str);
//
//  foreach($array as $token) {
//	list($key,$value) = explode('&',$token);
//	$nkey=base64_decode($key);
//	$nvalue = (    substr($value,0,1) != '$' ? base64_decode($value) : str2array(base64_decode(substr($value,1))) );
//	$rest[$nkey] = $nvalue;
//	//printf("Decoded %s,%s to %s,%s\n",$key,$value,$nkey,$nvalue);
//  }
//  return $rest;
//}

function array2str($array, $level=1) {
  $str = array();
  $maxLevel = $level;
  if($array==null) return "";
  if(is_string($array)) $array=array($array);
  foreach($array as $key=>$value) {
  	if(is_array($value)){
  		//list($value, $maxLevel) = array2str($value, $level+1);
  		$nvalue = '##_$'.$level.'_##'.array2str($value, $level+1);
  	} else {
  		$nvalue = $value;
  	}
	$str[] = $key.'##_&'.$level.'_##'.$nvalue;
  }
  return implode('##_|'.$level.'_##',$str);
}
function str2array($str, $level=1) {
  $rest = array();
  if($str==null) return $rest;

  if(strpos($str,'##_|'.$level.'_##')>0) {
	$array = explode('##_|'.$level.'_##',$str);
  } else $array=array($str);

  foreach($array as $token) {
  	//don't understand why this line makes memory limite overflow for files more than 4Mb.
	list($key,$value) = explode('##_&'.$level.'_##',$token);
	$l = strlen('##_$'.$level.'_##');
	if(substr($value,0,$l) != '##_$'.$level.'_##'){
		$rest[$key] = $value;
  	} else {
  		$rest[$key] = str2array(substr($value,$l), $level+1);
  	}
  }
  return $rest;
}
/**
 * Converts a FieldSelector FuncExp to a String
 * @param FuncExp $fx
 */
function fx2str($fx) {
	return TechnicalServiceProvider::getFieldSelectorFuncExpParser()->funcExpToString($fx);
}
/**
 * @param info: result of pathinfo
 * @param indexFilename: string
 * @param indexExt: array of possible extensions
 */
function entryIsIndexFile($info, $indexFilename, $indexExt){
	return (($info["dirname"] == null || $info["dirname"] === ".") && $info["filename"]==$indexFilename && false!==array_search(strtolower($info["extension"]), $indexExt, true));
}
/**
 * check if the zip file meet those requirement:
 * - size is less than limit
 * - an index is found
 */
function doesZipHaveAnIndexFile($path, $indexFilename="index", $indexExt=array("html", "htm"), $sizeLimit=52428800){
	if(!file_exists($path)) {
//		eput($path);
		return false;
	}
	//control sizeLimit:
	if(filesize($path)>$sizeLimit){
//		eput(filesize($path));
		return false;
	}
	$zip = new ZipArchive();
	if ($zip->open($path) === TRUE) {
		for ( $i=0; $i < $zip->numFiles; $i++ )
		{
			$entry = $zip->getNameIndex($i);
			$info = pathinfo($entry);
			if(entryIsIndexFile($info, $indexFilename, $indexExt)){
				$zip->close();
				return $info["filename"].".".$info["extension"];
			}
		}
		$zip->close();
	}
//	eput("no index file");
	return false;
}

/**
 * extract the zip
 * - if the size is less than limit
 * - if an index is found
 * - only the files withe the allow extension
 */
function extractSafeZipFile($path, $toPath, $allow = array("txt", "html", "htm", "css", "js", "swf", "au", "aac", "aif", "mp3", "rm", "wav", "wma", "flv", "swf", "wmv", "avi", "mpg", "mpeg", "3g2", "ram", "mov", "pdf", "html", "jpg", "jpeg", "gif", "png", "bmp"), $sizeLimit=52428800, $indexFilename="index", $indexExt=array("html", "htm")){
	if(!file_exists($path)) return false;
	//control sizeLimit:
	if(filesize($path)>$sizeLimit) return false;
	$zip = new ZipArchive();
	if ($zip->open($path) === TRUE) {
		$entries = array();
		$hasIndex = false;
		for ( $i=0; $i < $zip->numFiles; $i++ )
		{
			$entry = $zip->getNameIndex($i);
			$info = pathinfo($entry);
			//filter on allow intries
			if(false!==array_search(strtolower($info["extension"]), $allow, true)){
				$entries[] = $entry;
			}
			//verify if is index
			if(entryIsIndexFile($info, $indexFilename, $indexExt)){
				$hasIndex = $info["filename"].".".$info["extension"];
			}
		}
		if(!$hasIndex) return false;
		if($entries != null){
			$zip->extractTo($toPath, $entries);
		}
		$zip->close();
		return $hasIndex;
	} else {
		return false;
	}
}

/**
 * character rotation
 */
function strRot($st, $rot) {
	$st = str_split($st);
	foreach ($st as $i => $letter) {
		$ascii = ord($letter);
		//pour des caractères a-z
		if ($ascii >= 97 && $ascii <= 122) {
			$ascii -= 97;
			$rot = $rot % 26; //pour assurer d'avoir le décalage minimum utile
			$ascii += $rot;
			$ascii += 26; // pour assurer qu'il soit positig
			$ascii = $ascii % 26;
			$ascii += 97;
			//pour des caractères 0-9
		} else
			if ($ascii >= 48 && $ascii <= 57) {
				$ascii -= 48;
				$rot = $rot % 10; //pour assurer d'avoir le décalage minimum utile
				$ascii += $rot;
				$ascii += 10; // pour assurer qu'il soit positig
				$ascii = $ascii % 10;
				$ascii += 48;
			}

		$st[$i] = chr($ascii);
	}
	return implode("", $st);
}

//use Html2Text class instead
//function html2text($html)
//{
//	//replace links with real link content instead of link name:
//	$html = preg_replace('~<a[^(href)]+href="([^"]+)"[^>]*>[^<]+</a>~si','$1',$html);
//
//	// remove script/style tags content
//	$html = preg_replace('~<script[^>]*>[^(</)]*</script>~si','',$html);
//	$html = preg_replace('~<style[^>]*>[^(</)]*</style>~si','',$html);
//	// adding \n for tags which usualy make new lines
//	$tags = array (
//	0 => '~<h[123][^>]*>~si',
//	1 => '~<h[456][^>]*>~si',
//	2 => '~<table[^>]*>~si',
//	3 => '~<tr[^>]*>~si',
//	4 => '~<li[^>]*>~si',
//	5 => '~<br[^>]*>~si',
//	6 => '~<p[^>]*>~si',
//	7 => '~<div[^>]*>~si',
//	);
//	$html = preg_replace($tags,"\n",$html);
//	// add - for tables
//	$html = preg_replace('~</t(d|h)>\s*<t(d|h)[^>]*>~si',' - ',$html);
//	// remove any tags
//	$html = preg_replace('~<[^>]*>~s','',$html);
//	// reducing spaces
//	$html = preg_replace('~&nbsp;~si',' ',$html);
//	$html = preg_replace('~ +~s',' ',$html);
//	$html = preg_replace('~^\s+~m','',$html);
//	$html = preg_replace('~\s+$~m','',$html);
//	// reducing newlines
//	$html = preg_replace('~\n+~s',"\n",$html);
//	return $html;
//}

//$number 1->26 returning A->Z
function num2letter($number, $fromRec = false){
	if(!$fromRec) $number--; //to be able to begin at 1

	if ($number < 26) {
		return chr(65 + $number);
	}
   	return num2letter((int)($number / 26) -1, true).chr(65 + $number%26) ;
}

//count none empty values
function reduce_count($t, $v){
	if($v) $t++;
	return $t;
}

function getBlackOrWhiteFromBackgroundColor($hexColor){
	//eput(getBrigthness($hexColor));
	if(getBrigthness($hexColor)>140){
		return "000000";
	} else {
		return "FFFFFF";
	}
}
function getBrigthness($hexColor){
	$r = HexToR($hexColor);
	$g = HexToG($hexColor);
	$b = HexToB($hexColor);
	//eput($r." ".$g." ".$b);
	return (($r*299) + ($g*587) + ($b*114)) / 1000;
}
function HexToR($h) {
	return (int)base_convert(substr(cutHex($h),0,2),16,10);
}
function HexToG($h) {
	return (int)base_convert(substr(cutHex($h),2,2),16, 10);
}
function HexToB($h) {
	return (int)base_convert(substr(cutHex($h),4,2),16, 10);
}
function cutHex($h) {
	return ($h[0]=="#") ? substr($h, 1,7) : $h;
}

//used in homePage and in navigationBar
function reorderTabBasedOnKeyPriority($array, $keyPriority, $removeTitlesAndLinks=false){
	if(!$keyPriority) return $array;
	$keys = explode(",",$keyPriority);
	if(!$keys) return $array;
	$returnValue = array();
	foreach($keys as $key){
		$data = $array[$key];
		if(!$data && strpos($key,"[title]")===0){
			if(!$removeTitlesAndLinks) $returnValue[$key] = $key;
			continue;
		} else if(!$data && strpos($key,"[link]")===0){
			if(!$removeTitlesAndLinks) $returnValue[$key] = $key;
			continue;
		} else if(!$data) continue;
		$returnValue[$key] = $data;
	}
	foreach($array as $key=>$data){
		if($returnValue[$key]) continue;
		$returnValue[$key] = $data;
	}
	return $returnValue;
}

/**
 * Formats a timestamp with ms precision
 * example echo udate('Y-m-d H:i:s:u'); // 2010-11-15 21:21:00:987
 */
function udate($format, $utimestamp = null) {
  if (is_null($utimestamp))
    $utimestamp = microtime(true);

  $timestamp = floor($utimestamp);
  $milliseconds = round(($utimestamp - $timestamp) * 1000);

  return date(preg_replace('`(?<!\\\\)u`', $milliseconds, $format), $timestamp);
}

/**
 * Returns the value to store in the Cookie that an external system can use to authenticate
 * the content of this cookie is:
 * username|custom|expiration|roles
 *
 * The | will be replaced with the defined separator.
 * The whole string will be encoded if the Encrypt checkbox is ticked.
 * username: username of the current user
 * custom: the content of the custom field
 * expiration: the date calculated by adding expiration value to the time when the user display the folder (yyyy-mm-dd hh:mm:ss)
 * roles: the list of the roles that the user has in this tab
 *
 * @param string $separator: the separator to use
 * @param string $username: the username to integrate in the cookie
 * @param string $custom: the custom value to integrate in the cookie
 * @param int $expiration: the number of hours to add to the current time
 * @param string $roles: the roles that the user can play
 * @param boolean $encrypt: if true the returnValue will be encrypted with str_rot(base64(base64()),20)
 */
function getExternalCookieConnectionValue($separator, $username, $custom, $expiration, $roles=null, $encrypt=false, $rotationKey=13){
	$result = array();
	$result[] = $username;
	$result[] = $custom;
	$result[] = date("Y-m-d H:i:s", (time()+3600*$expiration));
	$result[] = $roles;

	$result = implode($separator, $result);

	if($encrypt){
		$result = strRot(base64_encode(base64_encode($result)), $rotationKey);
	}
	return $result;
}

function base64url_encode($data) {
	return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode($data) {
	return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
}

// FuncExp builder shortcuts

/**
 * Constructs a FuncExp given its name and an array of arguments.
 * This function supports variable number of arguments, that means that
 * instead of providing an array with the arguments, you can pass the arguments
 * in a comma separated list as a normal function call.
 * example: fx('myFunction', 'valarg1', 'valarg2', 'valarg3') is equivalent
 * to fx('myFunction', array('valarg1', 'valarg2', 'valarg3'))
 * @param String $funcName the name of the function
 * @param Array $funcArgs an array of arguments
 * @return FuncExp a FuncExp instance.
 */
function fx($funcName, $funcArgs=null) {
	$nArgs = func_num_args();
	$_funcArgs = null;
	if($nArgs > 2) {
		$_funcArgs = array();
		for($i = 1; $i < $nArgs; $i++) {
			$_funcArgs[] = func_get_arg($i);
		}
	}
	else if(isset($funcArgs)) $_funcArgs = $funcArgs;
	return TechnicalServiceProvider::getFuncExpBuilder()->fx($funcName, $_funcArgs);
}

/**
 * Creates a FuncExp which callbacks a PHP method or executes a closure.
 * @param String|Closure $method the method name or function name or closure that should be called.
 * The callback signature is callback(principal, valarg1, valarg2, ...),
 * where arguments are :
 * - principal: Principal, the principal executing the function
 * - valarg1, ..., valargn: Any, the function arguments.
 * The return value of the callback is returned as the return value of the wrapping func exp.
 * @param Object $obj if set, then should be the object instance which holds the method that should be called.
 * @param Array $funcArgs an array of arguments
 * This function supports variable number of arguments, that means that
 * instead of providing an array with the arguments, you can pass the arguments
 * in a comma separated list as a normal function call.
 * example: $funcExpBuilder->fx_callback('myMethod', $obj, 'valarg1', 'valarg2', 'valarg3') is equivalent
 * to $funcExpBuilder->fx_callback('myMethod', $obj, array('valarg1', 'valarg2', 'valarg3'))
 * @return FuncExp a FuncExp instance wrapping the callback
 */
function fx_callback($method, $obj, $funcArgs=null) {
	$nArgs = func_num_args();
	$_funcArgs = null;
	if($nArgs > 3) {
		$_funcArgs = array();
		for($i = 2; $i < $nArgs; $i++) {
			$_funcArgs[] = func_get_arg($i);
		}
	}
	else if(isset($funcArgs)) $_funcArgs = $funcArgs;
	return TechnicalServiceProvider::getFuncExpBuilder()->fx_callback($method, $obj, $_funcArgs);
}

/**
 * Creates a FuncExp which callbacks a PHP method or executes a closure in a "func exp style",
 * i.e. that arguments are not evaluated before calling the function.
 * @param String|Closure $method the method name or function name or closure that should be called.
 * @param Object $obj if set, then should be the object instance which holds the method that should be called.
 * The callback signature is callback(callbackFL, args),
 * where arguments are :
 * - callbackFL: CallbackFL, a reference to the func exp executing environment that can be used to evaluate arguments, get the principal or other information.
 * - args: Array, an array containing the function arguments. Each argument should be evaluated using the callbackFL->evaluateArg() method.
 * The return value of the callback is returned as the return value of the wrapping func exp.
 * @param Array $funcArgs an array of arguments
 * This function supports variable number of arguments, that means that
 * instead of providing an array with the arguments, you can pass the arguments
 * in a comma separated list as a normal function call.
 * example: $funcExpBuilder->fx_fxcallback('myMethod', $obj, 'valarg1', 'valarg2', 'valarg3') is equivalent
 * to $funcExpBuilder->fx_fxcallback('myMethod', $obj, array('valarg1', 'valarg2', 'valarg3'))
 * @return FuncExp a FuncExp instance wrapping the callback
 */
function fx_fxcallback($method, $obj, $funcArgs=null) {
	$nArgs = func_num_args();
	$_funcArgs = null;
	if($nArgs > 3) {
		$_funcArgs = array();
		for($i = 2; $i < $nArgs; $i++) {
			$_funcArgs[] = func_get_arg($i);
		}
	}
	else if(isset($funcArgs)) $_funcArgs = $funcArgs;
	return TechnicalServiceProvider::getFuncExpBuilder()->fx_fxcallback($method, $obj, $_funcArgs);
}

/**
 * Evaluates a given FuncExp using the wigii FuncExpVM and the provided FuncExp modules.
 * @param Principal $principal the principal evaluating the FuncExp
 * @param FuncExp|String $funcExp the func exp to evaluate.
 * If funcExp is a string, then first parses it.
 * @param Array|String $modules an array of class names or one string
 * This function supports variable number of arguments, that means that
 * instead of providing an array of modules, you can pass the arguments
 * in a comma separated list as a normal function call.
 * example: evalfx($principal, $myFuncExp, 'module1', 'module2', 'module3') is equivalent
 * to evalfx($principal, $myFuncExp, array('module1', 'module2', 'module2'))
 * @return Any returns the value of the evaluated func exp.
 */
function evalfx($principal, $funcExp, $modules=null) {
	$nArgs = func_num_args();
	$_modules = null;
	if($nArgs > 3) {
		$_modules = array();
		for($i = 2; $i < $nArgs; $i++) {
			$_modules[] = func_get_arg($i);
		}
	}
	else if(isset($modules)) $_modules = $modules;
	return TechnicalServiceProvider::getFuncExpBuilder()->evalfx($principal, $funcExp, $_modules);
}

/**
 * Converts a string to a FuncExp
 */
function str2fx($str) {return TechnicalServiceProvider::getFuncExpBuilder()->str2fx($str);}

/**
 * Converts a LogExp given into its string form to a semantically equivalent FuncExp
 * @param String $str a log exp in its string form
 * @return FuncExp
 */
function strlx2fx($str) {return TechnicalServiceProvider::getFuncExpBuilder()->strlx2fx($str);}

/**
 * Constructs a FieldSelector given the field name and optional subfield name
 * @param String $fieldName the field name
 * @param String $subFieldName optional subfield name
 * @return FieldSelector a instance of a FieldSelector
 */
function fs($fieldName, $subFieldName=null) {return TechnicalServiceProvider::getFuncExpBuilder()->fs($fieldName, $subFieldName);}

/**
 * Constructs a FieldSelector given the field name and optional subfield name
 * which value will be formatted and translated on evaluation time.
 * An optional language code can be provided, else uses the current language of
 * the TranslationService
 * @param String $fieldName the field name
 * @param String $subFieldName optional subfield name
 * @param String $lang language code. Should be one of the valid installed language,
 * see TranslationService->getInstalledLanguage()
 * @return FuncExp returns a FuncExp instance wrapping the FieldSelector that will ask the translation.
 */
function fs_t($fieldName, $subFieldName=null, $lang=null) {return TechnicalServiceProvider::getFuncExpBuilder()->fs_t($fieldName, $subFieldName, $lang);}

/**
 * Constructs a FieldSelector representing an element attribute
 * @param String $attributeName the element attribute name as defined in Element->fillFromArray
 * @return FieldSelector a instance of a FieldSelector
 */
function fs_e($attributeName) {return TechnicalServiceProvider::getFuncExpBuilder()->fs_e($attributeName);}

/**
 * Creates a FieldSelectorList based on an array of FieldSelectors
 * This function supports variable number of arguments, that means that
 * instead of providing an array with the arguments, you can pass the arguments
 * in a comma separated list as a normal function call.
 * example: $funcExpBuilder->fsl(fs1, fs2, fs3) is equivalent
 * to $funcExpBuilder->fsl(array(fs1, fs2, fs3))
 * @param Array $fsArr an array of FieldSelector or one FieldSelector
 */
function fsl($fsArr) {
	$nArgs = func_num_args();
	$_fsArr = null;
	if($nArgs > 1) {
		$_fsArr = array();
		for($i = 0; $i < $nArgs; $i++) {
			$_fsArr[] = func_get_arg($i);
		}
	}
	else if(isset($fsArr)) $_fsArr = $fsArr;
	return TechnicalServiceProvider::getFuncExpBuilder()->fsl($_fsArr);
}

/**
 * Builds a FieldSelectorList based on a comma separated string
 * Syntax is : 'field1.subfield1, field2.subfield2, field3'
 */
function str2fsl($str) {return TechnicalServiceProvider::getFuncExpBuilder()->str2fsl($str);}

/**
 * Creates a LinkSelector given the owner element id and the field name
 * @param ConfigSelector|LogExp|String $rootConfigSelector optional parameter. If set, then defines the configuration of the root element.
 * If LogExp then should be the group selection log exp used to define the configuration,
 * if String then should be the wigiiNamespace name used to define the configuration.
 * @return LinkSelector
 */
function ls($ownerElementId, $fieldName, $rootConfigSelector=null) {return TechnicalServiceProvider::getFuncExpBuilder()->ls($ownerElementId, $fieldName, $rootConfigSelector);}

/**
 * Creates a ConfigSelector
 * @param String|LogExp $wigiiNamespaceNameOrGroupExp Or a String representing a WigiiNamespace name 
 * or a LogExp representing a Group selection log exp
 * @param String $moduleName if WigiiNamespace name is defined, then a String representing a Module, else ignored.
 * @return ConfigSelector
 */
function cs($wigiiNamespaceNameOrGroupExp, $moduleName=null) {return TechnicalServiceProvider::getFuncExpBuilder()->cs($wigiiNamespaceNameOrGroupExp, $moduleName);}

/**
 * Creates a CalculatedFieldSelector
 * @param String|FieldSelector $fieldName the fieldName or directly a FieldSelector instance.
 * @param FuncExp $funcExp the associated FuncExp
 * @param String $subFieldName optional string to be used as a sub field.
 */
function cfs($fieldName, $funcExp, $subFieldName=null) {return TechnicalServiceProvider::getFuncExpBuilder()->cfs($fieldName, $funcExp, $subFieldName);}

/**
 * Builds a CalculatedFieldSelectorMap based on an array of CalculatedFieldSelectors
 * This function supports variable number of arguments, that means that
 * instead of providing an array with the arguments, you can pass the arguments
 * in a comma separated list as a normal function call.
 * example: $funcExpBuilder->cfsMap(cfs1, cfs2, cfs3) is equivalent
 * to $funcExpBuilder->cfsMap(array(cfs1, cfs2, cfs3))
 * @param Array $cfsArr an array of CalculatedFieldSelectors or one CalculatedFieldSelector
 */
function cfsMap($cfsArr) {
	$nArgs = func_num_args();	
	$_cfsArr = null;
	if($nArgs > 1) {
		$_cfsArr = array();
		for($i = 0; $i < $nArgs; $i++) {
			$_cfsArr[] = func_get_arg($i);
		}
	}
	else if(isset($cfsArr)) $_cfsArr = $cfsArr;
	return TechnicalServiceProvider::getFuncExpBuilder()->cfsMap($_cfsArr); 
}

/**
 * Creates a FieldSortingKey given the fieldName, optional subFieldName and direction (default to ascending)
 */
function fsk($fieldName, $subFieldName = null, $ascending=true) {return TechnicalServiceProvider::getFuncExpBuilder()->fsk($fieldName, $subFieldName, $ascending);}

/**
 * Builds a FieldSortingKeyList based on an array of FieldSortingKeys
 * This function supports variable number of arguments, that means that
 * instead of providing an array with the arguments, you can pass the arguments
 * in a comma separated list as a normal function call.
 * example: $funcExpBuilder->fskl(fsk1, fsk2, fsk3) is equivalent
 * to $funcExpBuilder->fskl(array(fsk1, fsk2, fsk3))
 * @param Array $fskArr an array of FieldSortingKey or one FieldSortingKey
 */
function fskl($fskArr) {
	$nArgs = func_num_args();
	$_fskArr = null;
	if($nArgs > 1) {
		$_fskArr = array();
		for($i = 0; $i < $nArgs; $i++) {
			$_fskArr[] = func_get_arg($i);
		}
	}
	else if(isset($fskArr)) $_fskArr = $fskArr;
	return TechnicalServiceProvider::getFuncExpBuilder()->fskl($_fskArr);
}

/**
 * Builds a FieldSortingKeyList based on a comma separated string
 * Syntax is : 'field1.subfield1 ASC, field2.subfield2 DESC, field3 ASC'
 */
function str2fskl($str) {return TechnicalServiceProvider::getFuncExpBuilder()->str2fskl($str);}

/**
 * Constructs a logical AND expression on the arguments
 * This function supports variable number of arguments, that means that
 * instead of providing an array with the arguments, you can pass the arguments
 * in a comma separated list as a normal function call.
 * example: $funcExpBuilder->lxAnd(arg1, arg2, arg3) is equivalent
 * to $funcExpBuilder->lxAnd(array(arg1, arg2, arg3))
 * @return LogExpAnd a LogExpAnd instance
 */
function lxAnd($args) {
	$nArgs = func_num_args();
	$_args = null;
	if($nArgs > 1) {
		$_args = array();
		for($i = 0; $i < $nArgs; $i++) {
			$_args[] = func_get_arg($i);
		}
	}
	else if(isset($args)) $_args = $args;
	return TechnicalServiceProvider::getFuncExpBuilder()->lxAnd($_args);
}

/**
 * Constructs a logical OR expression on the arguments
 * This function supports variable number of arguments, that means that
 * instead of providing an array with the arguments, you can pass the arguments
 * in a comma separated list as a normal function call.
 * example: $funcExpBuilder->lxOr(arg1, arg2, arg3) is equivalent
 * to $funcExpBuilder->lxOr(array(arg1, arg2, arg3))
 * @return LogExpOr a LogExpOr instance
 */
function lxOr($args) {
	$nArgs = func_num_args();
	$_args = null;
	if($nArgs > 1) {
		$_args = array();
		for($i = 0; $i < $nArgs; $i++) {
			$_args[] = func_get_arg($i);
		}
	}
	else if(isset($args)) $_args = $args;
	return TechnicalServiceProvider::getFuncExpBuilder()->lxOr($_args);
}

/**
 * Constructs a logical NOT expression on the argument
 * @return LogExpNot a LogExpNot instance
 */
function lxNot($arg) {return TechnicalServiceProvider::getFuncExpBuilder()->lxNot($arg);}

/**
 * Constructs a logical EQUAL expression on the two arguments
 * @return LogExpBin a LogExpBin instance with operator "="
 */
function lxEq($arg1, $arg2) {return TechnicalServiceProvider::getFuncExpBuilder()->lxEq($arg1, $arg2);}

/**
 * Constructs a logical NOTEQUAL expression on the two arguments
 * @return LogExpBin a LogExpBin instance with operator "!="
 */
function lxNotEq($arg1, $arg2) {return TechnicalServiceProvider::getFuncExpBuilder()->lxNotEq($arg1, $arg2);}

/**
 * Constructs a logical SMALLER expression on the two arguments
 * @return LogExpBin a LogExpBin instance with operator "<"
 */
function lxSm($arg1, $arg2) {return TechnicalServiceProvider::getFuncExpBuilder()->lxSm($arg1, $arg2);}

/**
 * Constructs a logical SMALLER or EQUAL expression on the two arguments
 * @return LogExpBin a LogExpBin instance with operator "<="
 */
function lxSmEq($arg1, $arg2) {return TechnicalServiceProvider::getFuncExpBuilder()->lxSmEq($arg1, $arg2);}

/**
 * Constructs a logical GREATER expression on the two arguments
 * @return LogExpBin a LogExpBin instance with operator ">"
 */
function lxGr($arg1, $arg2) {return TechnicalServiceProvider::getFuncExpBuilder()->lxGr($arg1, $arg2);}

/**
 * Constructs a logical GREATER or EQUAL expression on the two arguments
 * @return LogExpBin a LogExpBin instance with operator ">="
 */
function lxGrEq($arg1, $arg2) {return TechnicalServiceProvider::getFuncExpBuilder()->lxGrEq($arg1, $arg2);}

/**
 * Constructs a logical LIKE expression on the two arguments
 * @return LogExpBin a LogExpBin instance with operator "LIKE"
 */
function lxLike($arg1, $arg2) {return TechnicalServiceProvider::getFuncExpBuilder()->lxLike($arg1, $arg2);}

/**
 * Constructs a logical NOT LIKE expression on the two arguments
 * @return LogExpBin a LogExpBin instance with operator "NOTLIKE"
 */
function lxNotLike($arg1, $arg2) {return TechnicalServiceProvider::getFuncExpBuilder()->lxNotLike($arg1, $arg2);}

/**
 * Constructs a logical IN expression on the two arguments
 * @param Array $arg2 should be an array
 * @return LogExpBin a LogExpBin instance with operator "IN"
 */
function lxIn($arg1, $arg2) {return TechnicalServiceProvider::getFuncExpBuilder()->lxIn($arg1, $arg2);}

/**
 * Constructs a logical NOT IN expression on the two arguments
 * @param Array $arg2 should be an array
 * @return LogExpBin a LogExpBin instance with operator "NOTIN"
 */
function lxNotIn($arg1, $arg2) {return TechnicalServiceProvider::getFuncExpBuilder()->lxNotIn($arg1, $arg2);}

/**
 * Constructs a logical in group expression given a group selection expression
 * @param LogExp $lx a LogExp instance to select groups
 * @return LogExpInGroup a LogExpInGroup instance without children
 */
function lxInG($lx) {return TechnicalServiceProvider::getFuncExpBuilder()->lxInG($lx);}

/**
 * Constructs a logical in group expression given a group selection expression
 * Children groups are also selected.
 * @param LogExp $lx a LogExp instance to select groups
 * @return LogExpInGroup a LogExpInGroup instance with children
 */
function lxInGR($lx) {return TechnicalServiceProvider::getFuncExpBuilder()->lxInGR($lx);}

/**
 * Constructs a logical not in group expression given a group selection expression
 * @param LogExp $lx a LogExp instance to select groups
 * @return LogExpInGroup a LogExpNotInGroup instance ignoring children
 */
function lxNotInG($lx) {return TechnicalServiceProvider::getFuncExpBuilder()->lxNotInG($lx);}

/**
 * Constructs a logical not in group expression given a group selection expression
 * Children groups are also excluded.
 * @param LogExp $lx a LogExp instance to select groups
 * @return LogExpInGroup a LogExpNotInGroup instance excluding children
 */
function lxNotInGR($lx) {return TechnicalServiceProvider::getFuncExpBuilder()->lxNotInGR($lx);}

/**
 * Parses a String in a LogExp
 */
function str2lx($str) {return TechnicalServiceProvider::getFuncExpBuilder()->str2lx($str);}

/**
 * Converts a String representation of a LogExp into a semantically equivalent string representation of a FuncExp
 * This function can be used to store LogExp into FuncExp language format.
 * Example: call strlx2strfx('module = "Sequences" && wigiiNamespace = "Geometry" && id_group_parent = NULL');
 * will return 'lxAnd(lxEq(fs("module"), "Sequences"), lxEq(fs("wigiiNamespace"), "Geometry"), lxEq(fs("id_group_parent"), NULL))'
 * @param String $str
 * @return String
 */
function strlx2strfx($str) {return TechnicalServiceProvider::getFuncExpBuilder()->strlx2strfx($str);}

/**
 * Constructs a DataFlowActivitySelector given its class name and some configuration parameters
 * This function supports variable number of arguments, that means that
 * instead of providing an array with the parameters, you can pass the parameters
 * in a comma separated list as a normal function call.
 * example: $funcExpBuilder->dfas(className, param1, param1value, param2, param2value) is equivalent
 * to $funcExpBuilder->dfas(className, array(param1 => param1value, param2 => param2value))
 * @param String $className the class name of the DataFlowActivity that should be executed
 * @param Array $params an array of configuration parameters (paramName => paramValue, ...)
 * @return DataFlowActivitySelector a DataFlowActivitySelector instance
 */
function dfas($className, $params=null) {
	$nArgs = func_num_args();
	$_params = null;
	if($nArgs > 2) {
		$_params=array();
		$i = 1;
		while($i < $nArgs) {
			// get parameter name
			$paramName = func_get_arg($i);
			$i++;
			// get parameter value
			if($i < $nArgs) $paramValue = func_get_arg($i);
			else $paramValue = null;
			$_params[$paramName] = $paramValue;
			$i++;
		}
	}
	else if(isset($params)) $_params = $params;
	return TechnicalServiceProvider::getFuncExpBuilder()->dfas($className, $_params);
}

/**
 * Constructs a DataFlowActivitySelectorList based on an array of DataFlowActivitySelectors
 * This function supports variable number of arguments, that means that
 * instead of providing an array with the data flow activities selectors, you can pass them
 * in a comma separated list as a normal function call.
 * example: $funcExpBuilder->dfasl(dfas1, dfas2, dfas3) is equivalent
 * to $funcExpBuilder->dfasl(array(dfas1, dfas2, dfas3))
 * @param Array $dfasArr an array of DataFlowActivitySelectors or one DataFlowActivitySelector
 */
function dfasl($dfasArr) {
	$nArgs = func_num_args();
	$_dfasArr = null;
	if($nArgs > 1) {
		$_dfasArr = array();
		for($i = 0; $i < $nArgs; $i++) {
			$_dfasArr[] = func_get_arg($i);
		}
	}
	else if(isset($dfasArr)) $_dfasArr = $dfasArr;
	return TechnicalServiceProvider::getFuncExpBuilder()->dfasl($_dfasArr);
}

/**
 * Creates a DataFlowSelector instance
 * @param DataFlowDumpable|InputDataFlow|ObjectList $src the data flow source
 * @param DataFlowActivitySelectorList $dfasl the DataFlowActivitySelectorList describing the data flow
 */
function dfs($src, $dfasl) {
	return TechnicalServiceProvider::getFuncExpBuilder()->dfs($src, $dfasl);
}

/**
 * Constructs a DataFlowSelectorList based on an array of DataFlowSelectors
 * This function supports variable number of arguments, that means that
 * instead of providing an array with the data flow selectors, you can pass them
 * in a comma separated list as a normal function call.
 * example: $funcExpBuilder->dfsList(dfs1, dfs2, dfs3) is equivalent
 * to $funcExpBuilder->dfsList(array(dfs1, dfs2, dfs3))
 * @param Array $dfsArr an array of DataFlowSelectors or one DataFlowSelector
 */
function dfsList($dfsArr) {
	$nArgs = func_num_args();
	$_dfsArr = null;
	if($nArgs > 1) {
		$_dfsArr = array();
		for($i = 0; $i < $nArgs; $i++) {
			$_dfsArr[] = func_get_arg($i);
		}
	}
	else if(isset($dfsArr)) $_dfsArr = $dfsArr;
	return TechnicalServiceProvider::getFuncExpBuilder()->dfsList($_dfsArr);
}

/**
 * Constructs a DataFlowSelectorList based on an array of DataFlowSelectors indexed by keys.
 * This function supports variable number of arguments, that means that
 * instead of providing an array with the data flow selectors, you can pass them
 * in a comma separated list as a normal function call.
 * example: $funcExpBuilder->dfsMap(key1, dfs1, key2, dfs2, key3, dfs3) is equivalent
 * to $funcExpBuilder->dfsList(array(key1 => dfs1, key2 => dfs2, key3 => dfs3))
 * @param Array $dfsArr an array of DataFlowSelectors
 */
function dfsMap($dfsArr) {
	$nArgs = func_num_args();
	$_dfsArr = null;
	if($nArgs > 1) {
		$_dfsArr = array();
		$i = 0;
		while($i < $nArgs) {
			$key = func_get_arg($i);
			$i++;
			if($i < $nArgs) $_dfsArr[$key] = func_get_arg($i);
			$i++;
		}
	}
	else if(isset($dfsArr)) $_dfsArr = $dfsArr;
	return TechnicalServiceProvider::getFuncExpBuilder()->dfsMap($_dfsArr);
}

/**
 * Connects an array as a dataflow source.
 * @param Array $arr the array to use as the dataflow source.
 * This function also supports variable number of arguments, that means that instead of passing one array,
 * you can pass several arguments to the function, in a comma separated list.
 * example: array2df(val1, val2, val3, ..., valn) is equivalent to array2df(array(val1, val2, val3, ..., valn))
 * @return ArrayDataFlowConnector returns an ArrayDataFlowConnector instance that can be used as a DataFlow source.
 */
function array2df($arr) {
	$nArgs = func_num_args();
	if($nArgs > 1) $_arr = func_get_args();
	else $_arr = $arr;
	return TechnicalServiceProvider::getFuncExpBuilder()->array2df($_arr);
}

/**
 * Matches an input array with a pattern and binds any given ValueObject on both sides.
 * @param Array $input input array
 * @param Array $pattern pattern
 * This function supports variable number of arguments, that means that
 * instead of providing an array with the arguments, you can pass the arguments
 * in a comma separated list as a normal function call.
 * example: $funcExpBuilder->arrayMatch(array("item","12345","edit"), "item", $x, "edit") is equivalent
 * to $funcExpBuilder->arrayMatch(array("item","12345","edit"), array("item", $x, "edit"))
 * @example $x = ValueObject::createInstance(); $y = ValueObject::createInstance();
 * $funcExpBuilder->arrayMatch(array("item","12345",$y), "item", $x, "edit") will return true 
 * and $x->getValue() will return "12345" and $y->getValue() will return "edit".
 * - If one array is longer than the other and that the smallest array has a ValueObject as last item, 
 * then it will has its value bound to the tail of the longest array.
 *  $funcExpBuilder->arrayMatch(array("item","12345","edit"), "item", $x) will return true and $x->getValue() will returny array("12345","edit");
 *  - If two variables are on both sides, then the non null value will be bound to the variable with a null value, if both are non null, then matching is true if values are equal. 
 * @return Boolean true if input array matched pattern
 */
function arrayMatch($input,$pattern) {
	$nArgs = func_num_args();
	if($nArgs > 2) $_pattern = array_slice(func_get_args(),1);
	else $_pattern = $pattern;
	return TechnicalServiceProvider::getFuncExpBuilder()->arrayMatch($input,$_pattern);
}

/**
 * Constructs a ListFilter
 * @param FieldSelectorList $fieldSelectorList optional field selector list
 * @param LogExp $fieldSelectorLogExp an optional log exp to filter the list
 * @param FieldSortingKeyList $fieldSortingKeyList an optional field sorting key list
 * @param int $pageNumber an optional page number to be retrieved
 * @param int $pageSize the optional page limit
 */
function lf($fieldSelectorList=null, $fieldSelectorLogExp=null, $fieldSortingKeyList=null, $pageNumber=null, $pageSize=null) {
	return TechnicalServiceProvider::getFuncExpBuilder()->lf($fieldSelectorList, $fieldSelectorLogExp, $fieldSortingKeyList, $pageNumber, $pageSize);
}

/**
 * Creates an ElementPList based on selected elements using the ElementService
 * and which can be dumped into a data flow
 * @param LogExp $inGroupLogExp the in group log exp used to select the elements
 * @param ListFilter $listFilter an optional list filter to filter the selected elements
 * @return ElementPListDataFlowConnector an instance of an ElementPListDataFlowConnector which
 * is a fusion between an ElementPList and a DataFlowDumpable object.
 */
function elementPList($inGroupLogExp, $listFilter=null) {
	return TechnicalServiceProvider::getFuncExpBuilder()->elementPList($inGroupLogExp, $listFilter);
}

/**
 * Fetches an existing element or sub element in the database and dumps it into a Data flow
 * @param int $elementId the element id used to fetch the element
 * @param FieldSelectorList $fieldSelectorList an optional FieldSelectorList to filter the fields that are fetched.
 * @param ConfigSelector|LogExp|String $configSelector optional parameter. If set, then defines the configuration of the element or the root element in case of subitems.
 * If LogExp then should be the group selection log exp used to define the configuration,
 * if String then should be the wigiiNamespace name used to define the configuration.
 * @return ElementPDataFlowConnector
 */
function elementP($elementId, $fieldSelectorList=null, $configSelector=null) {
	return TechnicalServiceProvider::getFuncExpBuilder()->elementP($elementId, $fieldSelectorList, $configSelector);
}

/**
 * Creates a new Element which can be dumped into a DataFlow
 * @param int $groupId the group id in which the Element should be inserted
 * @param LinkSelector $linkSelector an optional LinkSelector. If set, then the created element is linked to
 * the owner element described in the LinkSelector.
 * @return ElementDataFlowConnector
 */
function newElement($groupId, $linkSelector=null) {
	return TechnicalServiceProvider::getFuncExpBuilder()->newElement($groupId, $linkSelector);
}

/**
 * Builds a copy of a given Element which can be dumped into a DataFlow.
 * @param Element $element element on which to do an in memory copy.
 * @param ConfigSelector $configSelector an optional ConfigSelector used to choose the right configuration of the copied Element.
 * If ConfigSelector points to a different module than the source element, then a matching is done of the fieldName and DataType.
 * @return DataFlowDumpable
 */
function copyElement($element, $configSelector=null) {
	return ServiceProvider::getWigiiBPL()->buildCopyElementDataFlowConnector($element, $configSelector);
}

/**
 * Creates a new sub Element which can be dumped into a DataFlow
 * @param int $masterElementId the master element id to which this sub element belongs
 * @param String $fieldName the field name defining the link with this sub element
 * @param ConfigSelector|LogExp|String $rootConfigSelector the configuration selector of the master element defined as a 
 * ConfigSelector object or a GroupLogExp or a String which is the WigiiNamespace.
 * @return ElementDataFlowConnector
 */
function newSubElement($masterElementId, $fieldName, $rootConfigSelector=null) {
	return TechnicalServiceProvider::getFuncExpBuilder()->newSubElement($masterElementId, $fieldName, $rootConfigSelector);
}

/**
 * Creates an GroupList based on selected groups (without detail) using the GroupAdminService
 * and which can be dumped into a data flow
 * @param LogExp $groupLogExp the log exp used to select the groups.
 * @param bool $asStdClass if true, then StdClass instances with the attributes {id, groupname, wigiiNamespace, module, id_group_parent}
 * are pushed in the dataflow, else Group instances (without detail) are pushed.
 * @return GroupListDataFlowConnector an instance of a GroupListDataFlowConnector which
 * is a fusion between an GroupList and a DataFlowDumpable object.
 */
function groupList($groupLogExp, $asStdClass=false) {
	return TechnicalServiceProvider::getFuncExpBuilder()->groupList($groupLogExp, $asStdClass);
}

/**
 * Creates an StdClass instance which maps a Field in the Wigii XML configuration file.
 * @param String $name the name of the field
 * @param Array $attributes an array [key => value] which defines some xml attributes.
 * The type of the field should defined in this array if needed. For example array('type' => 'Attributs').
 * @param String|Array $label the label of the field, or an array with the label translated in different languages.
 * The array is indexed with the Wigii installed languages (see TranslationService). For instance [l01 => label in English, l02 => label in French]
 * @param StdClass|Array $cfgAttributs an optional array of cfgAttribut StdClasses defining the list of attributes or one StdClass cfgAttribut
 * @return StdClass returns an StdClass instance with the properties {name, attributes, label, cfgAttributs}
 */
function cfgField($name, $attributes=null, $label=null, $cfgAttributs=null) {
	return TechnicalServiceProvider::getFuncExpBuilder()->cfgField($name, $attributes, $label, $cfgAttributs);
}

/**
 * Creates an StdClass instance which maps an 'attribute' node in the Wigii XML configuration file.
 * @param Scalar $value the value of the attribute
 * @param Array $attributes an array [key => value] which defines some xml attributes
 * @param String|Array $label the label of the attribute, or an array with the label translated in different languages.
 * The array is indexed with the Wigii installed languages (see TranslationService). For instance [l01 => label in English, l02 => label in French]
 * @return StdClass returns an StdClass instance with the properties {value, attributes, label}
 */
function cfgAttribut($value, $attributes=null, $label=null) {
	return TechnicalServiceProvider::getFuncExpBuilder()->cfgAttribut($value, $attributes, $label);
}

/**
 * Creates an StdClass instance which maps an 'attributeExp' node in the Wigii XML configuration file.
 * @param String $funcExp the attribute func exp as a string.
 * @return StdClass returns an StdClass instance with the property {funcExp}
 */
function cfgAttributeExp($funcExp) {
	return TechnicalServiceProvider::getFuncExpBuilder()->cfgAttributeExp($funcExp);
}

/**
 * Creates a WigiiBPLParameter based on a list of pairs (key, value) or other WigiiBPLParameter instances.
 * @param $args a list of arguments of the form wigiiBPLParam(k1,v1,k2,v2,p1,k3,v3,p2,p3,...) where
 * - ki,vi: pairs of (key, value) where key ki evaluates to a string and value to any value used as a parameter,
 * - pi: if pi evaluates to a WigiiBPLParameter instance, then adds its content
 * @return WigiiBPLParameter
 */
function wigiiBPLParam($args=null) {
	$nArgs = func_num_args();
	if($nArgs>1) $_args = func_get_args();			
	else $_args=$args;	
	return TechnicalServiceProvider::getFuncExpBuilder()->wigiiBPLParam($_args);
}

/**
 * Selects some objects and dumps them into a data flow
 * @param Principal $principal the principal executing the pocess
 * @param DataFlowDumpable|InputDataFlow|ObjectList $dataFlowDumpable the objects to be selected and pushed into the data flow
 * @param DataFlowActivitySelectorList $dataFlowActivitySelectorList the data flow description
 * @param FuncExpParameterMapping $fxpMapping an optional FuncExpParameterMapping instance used to instanciate any parameters
 * that would be present in the arguments with a given value.
 * @return Any optionally returns some data if the last stage of the data flows writes some output.
 */
function sel($principal, $dataFlowDumpable, $dataFlowActivitySelectorList, $fxpMapping=null) {
	return TechnicalServiceProvider::getFuncExpBuilder()->sel($principal, $dataFlowDumpable, $dataFlowActivitySelectorList, $fxpMapping);
}

/**
 * Executes a sel dataflow several times, using a list of FuncExp parameters
 * @param Principal $principal the principal executing the pocess
 * @param DataFlowDumpable|InputDataFlow|ObjectList $dataFlowDumpable the objects to be selected and pushed into the data flow
 * @param DataFlowActivitySelectorList $dataFlowActivitySelectorList the data flow description
 * @param FuncExpParameterMappingList $fxpMappingList the list of FuncExpParameterMapping against which the data flow will be executed.
 * @param DataFlowResultList $dataFlowResultList an optional list to store the data flows result.
 * @return DataFlowResultList optionally fills a provided DataFlowResultList with the result of the executed data flows.
 */
function multiSel($principal, $dataFlowDumpable, $dataFlowActivitySelectorList, $fxpMappingList, $dataFlowResultList=null) {
	return TechnicalServiceProvider::getFuncExpBuilder()->multiSel($principal, $dataFlowDumpable, $dataFlowActivitySelectorList, $fxpMappingList, $dataFlowResultList);
}