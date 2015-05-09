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
			$gids = array();
			if($dt == "Attributs" || $dt == "MultipleAttributs"){
				$gids= $fxml->xpath("attribute[@idGroup and @idGroup!=\"\"]/attribute::idGroup");
				if($gids)  $gids = implode(";",$gids);
			} else if($dt == "Booleans"){
				if($fxml["idGroup"] != null){
					$gids = (string)$fxml["idGroup"];
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