<?php
/**
 *  This file is part of Wigii.
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

/*
 * Created on 3 déc. 09
 * by LWR
 */
class Times extends DataTypeInstance {

	/**
	* overrides parent class
	* cette méthode contrôle les données du type de donnée. Ce contrôle ne se fait pas
	* automatiquement, si le type de donnée évolue, il faut aussi modifier cette méthode
	* @param &get, contient les données du formulaires. Ce paramètre est passé par référence pour permettre
	* le reformatage de la date
	*/
	public function checkValues($p, $elementId, $wigiiBag, $field){
		//nothing special to do, checks perfomr in isNewValueEqual is enough
	}

	/**
	 * crée une heure depuis un string
	 * si le string ne contient pas une heure valide, alors la fonction renvoie false.
	 * sinon elle renvoie true
	 * les paramètres $hours, $minutes, $seconds sont modifié si la date est valide
	*/
	public static function fromString($st, &$hours, &$minutes, &$seconds){
		//dans le cas ou le string est vide, il n'y a pas d'erreur d'heure
		//ce sera le compulsory field de FormExecutor::checkValues qui se chargera de l'erreur
		if ($st==null) { $hours = $minutes = $seconds = null; return true;}
		//on test plusieurs possibilité pour permettre de n'avoir que l'heure
		//ou que l'heure + minutes
		//ou le tout: heure + minute + seconde
		if (preg_match ("#^([0-9]{2})[hH/\ \,\;\:\.\-]{0,1}([0-9]{2})[/\ \,\;\:\.\-]{0,1}([0-9]{2})$#", $st, $regs) ||
			preg_match ("#^([0-9]{1,2})[hH/\ \,\;\:\.\-]{1}([0-9]{1,2})[/\ \,\;\:\.\-]{1}([0-9]{1,2})$#", $st, $regs) ||
			preg_match ("#^([0-9]{2})[hH/\ \,\;\:\.\-]{0,}([0-9]{2})$#", $st, $regs) ||
			preg_match ("#^([0-9]{1,2})[hH/\ \,\;\:\.\-]{1,}([0-9]{1,2})$#", $st, $regs) ||
			preg_match ("#^([0-9]{1,2})$#", $st, $regs)
			){
			$h= null; $m= null; $s=null;

			$h = (integer)$regs[1];
			$m = (integer)$regs[2];
			$s = (integer)$regs[3];

			if ($h<24 && $m<60 && $s<60 && $h>=0 && $m>=0 && $s>=0){
				$hours = $h;
				$minutes = $m;
				$seconds = $s;
				return true;
			}
			return false;
		}
		return false;
	}

	/**
	* deprecated
	*/
	public static function check(&$st, $reformat=true, $format=null){
		if($st == null) return true;
		if (Times::fromString($st, $h, $m, $s)){
			if ($reformat){
				if ($format == null) $st = Times::toString($h, $m, $s);
				else $st = Times::toString($h, $m, $s, $format);
			}
			return true;
		}
		return false;
	}

	/**
	* public
	* retourne un string dans le format choisi
	* le caractère d'échappement \ est possible
	* pour permettre d'afficher quand même un caractère formatant
	*/
	public static function toString($h, $m, $s, $format="hh:mm"){

		if ($h===null && $m===null && $s===null) return "";

		$zeroHour = "";
		$zeroMinute = "";
		$zeroSecond ="";
		$amPm = "";
		$texte = "";

		if ($s < 10){
			$zeroSecond = "0";
		}
		if ($m < 10){
			$zeroMinute = "0";
		}
		if ($h < 10) {
			$zeroHour = "0";
		}

		$nb_h = 0;
		$nb_m = 0;
		$nb_s = 0;
		for ($i = 0; $i<strlen($format); $i++){
			switch ($format[$i]) {
				case 'h':
				$nb_h++;
				if ($format[$i+1]!='h'){
					switch ($nb_h){
						case 0:
						case 1:
						$texte .= $h;
						break;
						default:
						$texte .= $zeroHour . $h;
						break;
					}
					$nb_h = 0;
				}
				break;
				case 'm':
				$nb_m ++;
				if ($format[$i+1]!='m'){
					switch ($nb_m){
						case 0:
						case 1:
						$texte .= $m;
						break;
						default:
						$texte .= $zeroMinute . $m;
						break;
					}
					$nb_m = 0;
				}
				break;
				case 's':
				$nb_s ++;
				if ($format[$i+1]!='s'){
					switch ($nb_s){
						case 0:
						case 1:
						$texte .= $s;
						break;
						default:
						$texte .= $zeroSecond . $s;
						break;
					}
					$nb_s = 0;
				}
				break;
				case '\\':
				$i++;
				$texte .= $format[$i];
				break;
				default:
				$texte .= $format[$i];
				break;
			}
		}
		return $texte . $amPm;
	}

	/**
	 * formatDisplay
	 * @param string $value
	 * @return string
	 */
	public static function formatDisplay($value, $format = "hh:mm"){
		if($value == null) //dans le cas ou il n'y a aucune donnée dans le champ du formulaire
			$value = null;
		else {
			$d = $m = $y = null;
			if (Times::fromString($value, $h, $m, $s))
			$value = Times::toString($h, $m, $s, $format);
			//dans le cas ou la valeur ne produit pas une heure correcte, alors formatDisplay
			//ne modifie pas la valeur....
		}
		return $value;
	}
}


