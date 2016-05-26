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
 * A data flow activity which maps PHP stdClass instances to other instance of PHP stdClass
 * Created by CWE on 02 mars 2014
 * Modified by CWE on 13.05.2016 to add the ability of keeping and updating or cloning incoming data
 */
class MapObject2ObjectDFA extends ObjectDFAWithFuncExpVM
{			
	// Object lifecycle
		
	public function reset() {
		parent::reset();	
		$this->creationOption = self::CREATION_OPTION_NEW;	
	}	
	public function freeMemory() {
		unset($this->obj2ObjMap);
		parent::freeMemory();
	}
	
	// dependency injection
	
	private $_debugLogger;
	private function debugLogger() {
		if (!isset ($this->_debugLogger)) {
			$this->_debugLogger = DebugLogger :: getInstance("MapObject2ObjectDFA");
		}
		return $this->_debugLogger;
	}	
	
	// configuration
	
	private $obj2ObjMap;
	/**
	 * Sets the map used to create an stdClass instance using functional expressions on the incoming stdClass instance.
	 * @param array $map an array which keys are the object attributes names 
	 * and the values are FuncExp or FieldSelector instances.
	 * example: array('name' => fx('concat', fs('first_name'), ' ', fs('last_name')),
	 * 				  'city' => fx('concat', fx('getAttr', fs('addr1'), 'zip_code'), ' ', fx('getAttr', fs('addr1'), 'city')),
	 * 				  'age' => fs('age'))	 
	 */
	public function setObject2ObjectMap($map) {
		if(!isset($map)) unset($this->ob2ObjMap);
		if(!is_array($map)) throw new DataFlowServiceException("object2objectMap should be an array", DataFlowServiceException::INVALID_ARGUMENT);
		$this->obj2ObjMap = $map;
	}		

	const CREATION_OPTION_NEW = 0;
	const CREATION_OPTION_CLONE = 1;
	const CREATION_OPTION_UPDATE = 2;	
	private $creationOption;
	
	/**
	 * Sets the creation option: one of new, clone or update.
	 * If new, then a new empty object is created which is initialized using the specified map,
	 * If clone, then incoming object is first cloned then updated using the specified map,
	 * If update, then incoming object is updated using the specified map.
	 * Defaults to new object.
	 * @param int $opt creation option, one of CREATION_OPTION_NEW, CREATION_OPTION_CLONE or CREATION_OPTION_UPDATE
	 */
	public function setCreationOption($opt) {
		switch($opt) {
			case self::CREATION_OPTION_NEW:
			case self::CREATION_OPTION_CLONE:
			case self::CREATION_OPTION_UPDATE:
				$this->creationOption = $opt;
				break;
			default: throw new DataFlowServiceException('creation option should be one of CREATION_OPTION_NEW, CREATION_OPTION_CLONE or CREATION_OPTION_UPDATE', DataFlowServiceException::INVALID_ARGUMENT);	
		}
	}
	
	// object event handling
				
	protected function processObject($obj, $dataFlowContext) {
		if($this->creationOption == self::CREATION_OPTION_CLONE) {
			if($obj instanceof ElementP || $obj instanceof Element) $returnValue = (object)array(); /* do not clone the element but always creates new object */
			else $returnValue = clone $obj;
		}
		elseif($this->creationOption == self::CREATION_OPTION_UPDATE) {
			if($obj instanceof ElementP || $obj instanceof Element) $returnValue = (object)array(); /* do not keep the element but always creates new object */
			else $returnValue = $obj;
		}
		else $returnValue = (object)array();
		
		// evaluates each object attribute
		if(isset($this->obj2ObjMap)) {
			foreach($this->obj2ObjMap as $attr => $funcExp) {
				$returnValue->{$attr} = $this->evaluateFuncExp($funcExp);
			}
		}
				
		// writes the output
		$dataFlowContext->writeResultToOutput($returnValue, $this);
	}
}