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
 * A data flow activity which maps elements to instance of PHP stdClass
 * Created by CWE on 14 novembre 2013
 */
class MapElement2ObjectDFA extends ElementDFAWithFuncExpVM
{			
	// Object lifecycle
		
	public function reset() {
		parent::reset();		
	}	
	public function freeMemory() {
		unset($this->element2ObjMap);
		parent::freeMemory();
	}
	
	// dependency injection
	
	private $_debugLogger;
	private function debugLogger() {
		if (!isset ($this->_debugLogger)) {
			$this->_debugLogger = DebugLogger :: getInstance("MapElement2ObjectDFA");
		}
		return $this->_debugLogger;
	}	
	
	// configuration
	
	private $element2ObjMap;
	/**
	 * Sets the map used to create a stdClass instance using functional expressions on the element.
	 * @param array $map an array which keys are the object attributes names 
	 * and the values are FuncExp or FieldSelector instances.
	 * example: array('name' => fx('concat', fs('first_name'), ' ', fs('last_name')),
	 * 				  'city' => fx('concat', fs('addr1', 'zip_code'), ' ', fs('addr1', 'city')),
	 * 				  'age' => fs('age'))	 
	 */
	public function setElement2ObjectMap($map) {
		if(!isset($map)) unset($this->element2ObjMap);
		if(!is_array($map)) throw new DataFlowServiceException("element2objectMap should be an array", DataFlowServiceException::INVALID_ARGUMENT);
		$this->element2ObjMap = $map;
	}		

	// element event handling
				
	protected function processElement($elementP, $dataFlowContext) {
		// evaluates each object attribute
		$returnValue = array();
		if(isset($this->element2ObjMap)) {
			foreach($this->element2ObjMap as $attr => $funcExp) {
				$returnValue[$attr] = $this->evaluateFuncExp($funcExp);
			}
		}
		
		// converts the return value to an instance of class stdClass
		$returnValue = (object)$returnValue;
		// writes the output
		$dataFlowContext->writeResultToOutput($returnValue, $this);
	}
}