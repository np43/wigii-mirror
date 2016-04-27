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
 */
class MapObject2ObjectDFA extends ObjectDFAWithFuncExpVM
{			
	// Object lifecycle
		
	public function reset() {
		parent::reset();		
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

	// object event handling
				
	protected function processObject($obj, $dataFlowContext) {
		// evaluates each object attribute
		$returnValue = array();
		if(isset($this->obj2ObjMap)) {
			foreach($this->obj2ObjMap as $attr => $funcExp) {
				$returnValue[$attr] = $this->evaluateFuncExp($funcExp);
			}
		}
		
		// converts the return value to an instance of class stdClass
		$returnValue = (object)$returnValue;
		// writes the output
		$dataFlowContext->writeResultToOutput($returnValue, $this);
	}
}