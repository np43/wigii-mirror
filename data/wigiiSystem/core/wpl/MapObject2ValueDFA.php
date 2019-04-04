<?php
/**
 *  This file is part of Wigii (R) software.
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
 * A data flow activity which maps PHP StdClasses to a value
 * Created by CWE on 2 mars 2014
 */
class MapObject2ValueDFA extends ObjectDFAWithFuncExpVM
{			
	// Object lifecycle
		
	public function reset() {
		parent::reset();		
	}	
	public function freeMemory() {
		unset($this->obj2ValueFuncExp);
		parent::freeMemory();
	}
	
	// dependency injection
	
	private $_debugLogger;
	private function debugLogger() {
		if (!isset ($this->_debugLogger)) {
			$this->_debugLogger = DebugLogger :: getInstance("MapObject2ValueDFA");
		}
		return $this->_debugLogger;
	}	
	
	// configuration
	
	private $obj2ValueFuncExp;
	/**
	 * Sets the func exp used to calculate a value based on the object.
	 * @param FuncExp $funcExp a FuncExp or a FieldSelector instance used to calculate a value
	 * example: fx('concat', fs('first_name'), ' ', fs('last_name'))	 
	 */
	public function setObject2ValueFuncExp($funcExp) {
		$this->obj2ValueFuncExp = $funcExp;
	}	
	
	// object event handling
				
	protected function processObject($obj, $dataFlowContext) {
		// evaluates the func exp
		$returnValue = $this->evaluateFuncExp($this->obj2ValueFuncExp);
		// writes the output
		if(isset($returnValue)) $dataFlowContext->writeResultToOutput($returnValue, $this);
	}
}