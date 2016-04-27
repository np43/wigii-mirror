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
class Dates extends DataTypeInstance {

	/**
	* overrides parent class
	* cette méthode contrôle les données du type de donnée. Ce contrôle ne se fait pas
	* automatiquement, si le type de donnée évolue, il faut aussi modifier cette méthode
	* @param &get, contient les données du formulaires. Ce paramètre est passé par référence pour permettre
	* le reformatage de la Dates
	*/
	public function checkValues($p, $elementId, $wigiiBag, $field){
		//nothing special to do, checks perfomr in isNewValueEqual is enough
	}

	/**
	 * crée une date depuis un string
	 * si le string ne contient pas une date valide, alors la fonction renvoie false.
	 * sinon elle renvoie true
	 * les paramètres $day, $month, $year sont modifié si la date est valide
	 */
	public static function fromString($st, &$day, &$month, &$year, &$hour=null, &$minute=null, &$second=null){
		//dans le cas ou le string est vide, il n'y a pas d'erreur d'heure
		//ce sera le compulsory field de FormExecutor::checkValues qui se chargera de l'erreur
		if ($st==null) return true;
		//permet de relever la date et l'heure d'un string venant de la fonction MySql NOW()
		if(preg_match ("#([0-9]{4})\-([0-9]{2})\-([0-9]{2})[\ \,\;]+([0-9]{2})\:([0-9]{2})\:([0-9]{2})#", $st, $regs)){
			if (count($regs)!=7) return false;
			$y = (int) $regs[1]; //year
			$m = (int) $regs[2]; //month
			$d = (int) $regs[3]; //day
			$h = (int) $regs[4]; //hour
			$i = (int) $regs[5]; //minute
			$s = (int) $regs[6]; //second
			if (checkdate($m, $d, $y) && $h>=0 && $h<24 && $i>=0 && $i<60 && $s>=0 && $s<60){
				$day = $d; $month=$m; $year=$y;
				$hour = $h; $minute=$i; $second = $s;
				return true;
			}
			return false;
		}
		//permet de relever la date et l'heure d'un string venant d'un formulaire avec le format:
		// dd.mm.yyy, hh:min:ss
		if(preg_match ("#([0-9]{2})[/\ \,\;\:\.\-]([0-9]{2})[/\ \,\;\:\.\-]([0-9]{4})[/\ \,\;\:\.\-]+([0-9]{1,2})[/\ \,\;\:\.\-\h\H]([0-9]{1,2})[/\ \,\;\:\.\-]?([0-9]{0,2})#", $st, $regs)){
			if (count($regs)!=7) return false;
			$d = (int) $regs[1]; //year
			$m = (int) $regs[2]; //month
			$y = (int) $regs[3]; //day
			$h = (int) $regs[4]; //hour
			$i = (int) $regs[5]; //minute
			$s = (int) $regs[6]; //second
			if (checkdate($m, $d, $y) && $h>=0 && $h<24 && $i>=0 && $i<60 && $s>=0 && $s<60){
				$day = $d; $month=$m; $year=$y;
				$hour = $h; $minute=$i; $second = $s;
				return true;
			}
			return false;
		}
		//dans un cas plus général (ne traite que les dates sans les heures
		if (preg_match ("#([0-9]{1,4})[/\ \,\;\:\.\-]{1}([0-9]{1,2})[/\ \,\;\:\.\-]{1}([0-9]{1,4})#", $st, $regs)){
			if (count($regs)!=4) return false;
			$d= null; $m= null; $y=null;
			if (strlen($regs[1])<=2) $d = (integer)$regs[1];
			if (strlen($regs[1])==4) $y = (integer)$regs[1];
			$m = (integer)$regs[2];
			if ($d === null && strlen($regs[3])<=2) $d = (integer)$regs[3];
			elseif (strlen($regs[3])==2 || strlen($regs[3])==4) $y = (integer)$regs[3];
			else return false; //dans le cas d'une année à 3 chiffres

			//si la date à deux chiffres est plus petites que l'année à deux
			//chiffre en cours+5 on ajouter 2000
			//sinon on ajoute 1900
			if($y<=((int)Date("y")+5)){
				$y += 2000;
			} else if ($y < 100) {
				$y+=1900;
			}

			if (checkdate((int)$m, (int)$d, (int)$y)){
				$day = (int) $d;
				$month = (int) $m;
				$year = (int)$y;
				return true;
			}
			return false;
		}
		return false;
	}

	/**
	* public
	* le format prévu pour MYSQL est yyyy-mm-dd
	* le caractère d'échappement \ est possible
	* pour permettre d'afficher quand même un caractère formatant
	*/
	public static function toString($d, $m, $y, $format="dd.mm.yyyy", $h=null, $min=null, $s=null, $formatTime=null){


		$transS = ServiceProvider::getTranslationService();
		$p = ServiceProvider::getAuthenticationService()->getMainPrincipal();

		if ($d===null && $m===null && $y===null) return "";

		$date = mktime(0,0,0,$m,$d,$y);
		if ($date !== false && $date != -1) {
			$date = getdate($date);
			$dow=$date['wday'];
			if ($dow==0) $dow=7; //just for sunday=7 not 0
		} else $dow = null;

		$texte = "";
		$zeroDay = "";
		$zeroMonth = "";
		if ($d < 10) $zeroDay = "0";
		if ($m < 10)$zeroMonth = "0";
		$nb_y = 0;
		$nb_m = 0;
		$nb_d = 0;
		for ($i = 0; $i<strlen($format); $i++){
			switch ($format[$i]) {
				case 'y':
				$nb_y++;
				if ($format[$i+1]!='y'){
					switch ($nb_y){
						case 0:
						case 1:
						case 2:
							$texte .= substr($y."", 2,2);
							break;
						case 3:
						case 4:
						default:
							$texte .= $y;
							break;
					}
					$nb_y = 0;
				}
				break;
				case 'm':
				$nb_m ++;
				if ($format[$i+1]!='m'){
					switch ($nb_m){
						case 0:
						case 1:
						case 2:
							$texte .= $zeroMonth . $m;
							break;
						case 3:
							$texte .= $transS->t($p, "month_".$m);
							break;
						case 4:
						default:
							$texte .= $transS->t($p, "month_long_".$m);
							break;
					}
					$nb_m = 0;
				}
				break;
				case 'd':
					$nb_d ++;
					if ($format[$i+1]!='d'){
						switch ($nb_d){
							case 0:
							case 1:
								$texte .= $d;
								break;
							case 2:
								$texte .= $zeroDay . $d;
								break;
							case 3:
								if ($dow === null) break; //dans le cas ou l'on dépasse les dates possibles
								$texte .= $transS->t($p, "dayOfWeek_".$dow);
								break;
							case 4:
							default:
								if ($dow === null) break; //dans le cas ou l'on dépasse les dates possibles
								$texte .= $transS->t($p, "dayOfWeek_long_".$dow);
								break;
						}
						$nb_d = 0;
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

		//ajout de l'heure si défini
		if ($formatTime){
			$texte .= " ";
			if($h==null) $h = 0;
			if($min==null) $min = 0;
			if($s==null) $s = 0;
			$texte .= Times::toString($h, $min, $s, $formatTime);
		}

		return $texte;
	}

	/**
	 * formatDisplay
	 * @param string $value
	 * @return string
	 */
	public static function formatDisplay($value, $format = "dd mmm yyyy", $formatTime="hh:mm:ss"){
		if($value == 0) //dans le cas ou il n'y a aucune donnée dans le champ du formulaire
			$value = null;
		else {
			$d = $m = $y = $h = $min = $s = null;
			if (Dates::fromString($value, $d, $m, $y, $h, $min, $s)){
				$value = Dates::toString($d, $m, $y, $format, $h, $min, $s, $formatTime);
			}
			//dans le cas ou la valeur ne produit pas une date correcte, alors formatDisplay
			//ne modifie pas la valeur....
		}
		return $value;
	}

	/**
	 * formatDisplay
	 * @param string $value
	 * @return string
	 */
	public static function DateTimesFormatDisplay($value, $dateFormat = "dd mmm yy", $hourFormat = "hh:mm"){
		$date = substr($value, 0, 10);
		$time = substr($value, 11);
		$date = Dates::formatDisplay($date, $dateFormat);
		$time = Times::formatDisplay($time, $hourFormat);
		return $date . " - " . $time;
	}
}