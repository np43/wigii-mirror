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

/**
 * Created on 3 déc. 09 by LWR
 * Modified on 25.02.2016 by CWE to always display MultipleAttribut codes not present in drop-down
 */
class MultipleAttributs extends DataTypeInstance {

	/**
	* overrides parent class
	* cette méthode contrôle les données du type de donnée. Ce contrôle ne se fait pas
	* automatiquement, si le type de donnée évolue, il faut aussi modifier cette méthode
	*/
	public function checkValues($p, $elementId, $wigiiBag, $field){
		//il faut s'assurer que la valeur de retour soit bien une valeur
		//prévue dans la liste d'option...

		$transS = ServiceProvider::getTranslationService();

		//construction des options dans le cas d'un select ou multipleSelect
		//nécessaire pour assurer que la valeur retournée est bel et bien une option
		//définie
		$options = array();
		$fieldXml = $field->getXml();
		foreach($field->getXml()->attribute as $attribute_key => $attribute){
			$options[(string)$attribute] = $transS->t($p, (string)$attribute, $attribute);
		}

		$value = $field->getValue($elementId, $wigiiBag);
		$ok = true;
		//la définition est dans params[options]
		//contrôle que le résultat fourni corrsepond à une clé du tableau d'options
		//ou a vide. En effet un multiple-select peut très bien n'avoir rien de sélectionné
		//contrairement à un select ou il y a forcément une option sélectionnée
		if($value != null && $fieldXml["allowNewValues"]!="1"){ //il peut être null dans le cas ou rien n'est sélectionné...
			foreach($value as $tempKey){
				 if ($tempKey != null && !array_key_exists(stripslashes($tempKey), $options)) $ok=false;
			}
		}
		if (!$ok){
			throw new ServiceException("notValidOptionForMultippleAttributField", ServiceException::INVALID_ARGUMENT);
		}

	}

	//affiche pour l'utilisateur la liste des valeurs sélectionnées
	public static function formatDisplay($value, $field, $returnArray=false){

		$transS = ServiceProvider::getTranslationService();
		$p = ServiceProvider::getAuthenticationService()->getMainPrincipal();
		if($value!=null){
			//$value = formatMultipleValues($value);
			$translated = array();
			$fieldXml = $field->getXml();
			$existingKeys = array();
			$isDisplayDbValueSet = ($fieldXml["displayDBValue"]=="1");
			foreach($fieldXml->attribute as $attr){
//				eput($attr);
				$color = (string)$attr["color"];			
				$sAttr = (string)$attr;
				if(array_search($sAttr, $value) === false) continue; //$value[(string)$attr] == null) continue;
				if(!$isDisplayDbValueSet) {
					if($color && !$returnArray){
						$translated[] = '<span style="padding:2px 10px 2px 10px;line-height:21px;background-color:#'.$color.';color:#'.getBlackOrWhiteFromBackgroundColor($color).'">'.$transS->t($p, $sAttr, $attr).'</span>';
					} else {
						$translated[] = $transS->t($p, $sAttr, $attr);
					}
				} else {
					$translated[] = $sAttr;
				}
				$existingKeys[$sAttr] = $sAttr;
			}
			// adds non existing values in array if allowNewValues
			// CWE 25.02.2016: always display code if value is not present in drop-down
			/*
			if($fieldXml["allowNewValues"]=="1"){
				foreach($value as $v) {
					if($existingKeys[$v] == null) $translated[] = $transS->t($p, $v);
				}
			}
			*/
			foreach($value as $v) {
				if($existingKeys[$v] == null) $translated[] = $transS->t($p, $v);
			}
			if($returnArray) return $translated;
			if(!$fieldXml["useMultipleColumn"] || $fieldXml["useMultipleColumn"]=="1"){
				return implode("<br />", $translated);
			} else {
				return implode(", ", $translated);
			}
		}
		return null;
	}
}



