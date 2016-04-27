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
class TimeRanges extends DataTypeInstance {
	const mainSubField = "begDate"; // pour le focus (entre autre)
	/**
	* overrides parent class
	* cette méthode contrôle les données du type de donnée. Ce contrôle ne se fait pas
	* automatiquement, si le type de donnée évolue, il faut aussi modifier cette méthode
	* @param &get, contient les données du formulaires. Ce paramètre est passé par référence pour permettre
	* le reformatage de la Dates
	*/
	public function checkValues($p, $elementId, $wigiiBag, $field){
		$fieldParams = $field->getXml();
		if($fieldParams["onlyDate"]=="1"){
			$field->setValue(true, $elementId, $wigiiBag,"isAllDay");
		}
		$isAllDay = $field->getValue($elementId, $wigiiBag,"isAllDay");
		if($isAllDay){
			$field->setValue("0", $elementId, $wigiiBag,"begTime");
			$field->setValue(null, $elementId, $wigiiBag,"endTime");
		}
		
		//exept:
		//si aucune date de début n'est définie, il faut mettre une erreur
		//s'il y a qqch d'autre dans les champs de rempli
		if($field->getValue($elementId, $wigiiBag,"begDate") == null && !$field->getValue($elementId, $wigiiBag,"isAllDay") && ($field->getValue($elementId, $wigiiBag,"begTime") != null || $field->getValue($elementId, $wigiiBag,"endTime") != null || $field->getValue($elementId, $wigiiBag,"endDate") != null)){
			throw new ServiceException("noBegDateEntered", ServiceException::INVALID_ARGUMENT);
		}

		//si pas d'erreur, alors on peut checker si la date suivante est bien après la date de début.
		//eput($mysqlBegDate.$mysqlBegTime." ".$mysqlEndDate.$mysqlEndTime);
		$mysqlBegDate = $field->getValue($elementId, $wigiiBag,"begDate");
		$mysqlBegTime = $field->getValue($elementId, $wigiiBag,"begTime");
		$mysqlEndDate = $field->getValue($elementId, $wigiiBag,"endDate");
		if($mysqlEndDate == null) $mysqlEndDate = $mysqlBegDate;
		$mysqlEndTime = $field->getValue($elementId, $wigiiBag,"endTime");
		if($mysqlEndTime == null && $mysqlEndDate==$mysqlBegDate) $mysqlEndTime = $mysqlBegTime;
		if ($wigiiBag->getError($field->getFieldName())== null && $mysqlBegDate.$mysqlBegTime > $mysqlEndDate.$mysqlEndTime){
			throw new ServiceException("begDateAfterEndDate", ServiceException::INVALID_ARGUMENT);
		}
		
	}
}


