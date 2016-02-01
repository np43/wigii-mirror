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
 * WPL ObjectList implementation
 * Created by CWE on 19.01.2016
 */
class WplObjectList extends ObjectListArrayImpl
{
	private $wplObjectName = 'wplObject';
	
	// Object lifecycle
	
	public static function createInstance($wplObjectName=null)
	{
		$returnValue = new self();
		$returnValue->reset();
		if(isset($wplObjectName)) $returnValue->wplObjectName = $wplObjectName;
		return $returnValue;
	}
	
	// Properties
	
	/**
	 * @return String the name or class of the objects stored in the list. Used to name the xml node of each object when serializing to xml.
	 * For example: if wplObjectName is 'item', will be serialized in xml as <item>...</item><item>...</item>...
	 * Defaults to 'wplObject'
	 */
	public function getWplObjectName() {
		return $this->wplObjectName;
	}	
	
	// Implementation
	
	/**
	 * Adds an object to the list. The object should be serializable through XML using WplToolbox class.
	 * @param Record|Element|ElementP|StdClass|WplObjectList $obj the object to add to the list. Only supports: Record|Element|ElementP,StdClass and WplObjectList
	 * @throws ListException in case of error
	 */
	public function addWplObject($obj) {
		if(is_null($obj)) return;
		if($obj instanceof Element ||
			$obj instanceof ElementP ||
			$obj instanceof Record ||
			$obj instanceof StdClass ||
			$obj instanceof WplObjectList) {

			$this->objArray[] = $obj;
		}
		else throw new ListException("obj of class '".get_class($obj)."' cannot be stored into a WplObjectList",ListException::INVALID_ARGUMENT);
	}
}