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
 * Modified on 25.02.2016 by CWE to always display Attribut code if not present in drop-down
 */
class Attributs extends DataTypeInstance {

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
		foreach($fieldXml->attribute as $attribute_key => $attribute){
			$options[(string)$attribute] = $transS->t($p, (string)$attribute, $attribute);
		}

		$value = $field->getValue($elementId, $wigiiBag);

		//if value === null then this means this field was probably disabled, maybe by the
		//GUI. in that case we set the value to the first value. if this is not a good way,
		//then the check or the reset to the orginial value needs to be done elsewhere
		if($value === null){
			$value = reset(array_keys($options));
			$field->setValue($value, $elementId, $wigiiBag);
		}

		if($fieldXml["allowNewValues"]!="1" && $value && !array_key_exists(stripslashes($value), $options)){
			throw new ServiceException("notValidOptionForAttributField", ServiceException::INVALID_ARGUMENT);
		}
	}

	//affiche pour l'utilisateur la valeur sélectionnée
	public static function formatDisplay($value, $field){

		$transS = ServiceProvider::getTranslationService();
		$p = ServiceProvider::getAuthenticationService()->getMainPrincipal();
		$fieldXml = $field->getXml();
		//on n'affiche rien dans le cas ou $value est null ou none.
		if($value == null || $value =="none") return "";

		foreach($field->getXml()->attribute as $attr){
			if($value != (string)$attr) continue;
			return $transS->t($p, $value, $attr);
		}
		//arrive here only if attribute is not found
		// CWE 25.02.2016: always display code if value is not present in drop-down
		/*
		if($fieldXml["allowNewValues"]=="1"){
			return $transS->t($p, $value);
		}
		*/
		return $transS->t($p, $value);
	}
}



