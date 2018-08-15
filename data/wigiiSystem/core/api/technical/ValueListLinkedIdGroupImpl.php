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
 * A value list which add the values in a Array (the value is added only if != null)
 * Created by LWR on 13 June 2013
 */
class ValueListLinkedIdGroupImpl extends ValueListArrayMapper implements FieldList
{

	// Object lifecycle
	/**
	 * if one of the following parameter is defined, the values added in the ValueListArrayMapper must be
	 * strings only.
	 * @param $distinct : bool = false, define if we want unique values in the final array
	 * @param $possibleSeparator : string = null, define a possible separator in the value added.
	 * a preg_split is done with the possibleSeparator regExp, then values are added as separated items
	 * @param $trim : bool = false, define if a trim function is done on the value
	 */
	public static function createInstance($distinct=false, $possibleSeparator = null, $trim =false)
	{
		$returnValue = new self();
		$returnValue->reset($distinct, $possibleSeparator, $trim);
		return $returnValue;
	}

	public function addField($field)
	{
		if(!isset($field)) throw new ListException("field cannot be null", ListException::INVALID_ARGUMENT);
		if($field->getDataType() != null){
			$fxml = $field->getXml();
			$dt = $field->getDataType()->getDataTypeName();
			$wpl = ServiceProvider::getWigiiBPL();
			$p = ServiceProvider::getAuthenticationService()->getMainPrincipal();
			$gids = array();
			if($dt == "Attributs" || $dt == "MultipleAttributs"){
				// CWE 15.08.2018: evaluates dynamic attribute idGroup if defined
				$dynGids= $fxml->xpath("attribute[@idGroup and @idGroup!=\"\" and @enableDynamicAttributes=\"1\" and not(@movePriority)]/attribute::idGroup");
				if($dynGids) {					
					foreach($dynGids as $dynGid) {
						$gids[] = $wpl->evaluateConfigParameter($p, $dynGid);
					}
					$dynGids = implode(";",$gids);
				}
				// Static id groups
				$gids= $fxml->xpath("attribute[@idGroup and @idGroup!=\"\" and not(@enableDynamicAttributes) and not(@movePriority)]/attribute::idGroup");
				if($gids)  $gids = implode(";",$gids);
				else $gids = '';
				if($dynGids) $gids = $dynGids.';'.$gids;
			} else if($dt == "Booleans"){
				if($fxml["idGroup"] != null && !$fxml["movePriority"]){
					$gids = (string)$fxml["idGroup"];
					// CWE 15.08.2018: evaluates dynamic attribute idGroup if defined
					if($fxml["enableDynamicAttributes"]=="1") $gids = $wpl->evaluateConfigParameter($p, $gids);
				}
			}
			if($gids) $this->addValue($gids);
		}
	}

	public function getField($fieldName){
		throw new ListException("", ListException::UNSUPPORTED_OPERATION);
	}
	public function doesFieldExist($fieldName){
		throw new ListException("", ListException::UNSUPPORTED_OPERATION);
	}
}