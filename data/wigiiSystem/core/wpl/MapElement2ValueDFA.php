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
 * A data flow activity which maps elements to a scalar value
 * Created by CWE on 5 décembre 2013
 */
class MapElement2ValueDFA extends ElementDFAWithFuncExpVM
{			
	// Object lifecycle
		
	public function reset() {
		parent::reset();		
	}	
	public function freeMemory() {
		unset($this->element2ValueFuncExp);
		parent::freeMemory();
	}
	
	// dependency injection
	
	private $_debugLogger;
	private function debugLogger() {
		if (!isset ($this->_debugLogger)) {
			$this->_debugLogger = DebugLogger :: getInstance("MapElement2ValueDFA");
		}
		return $this->_debugLogger;
	}	
	
	// configuration
	
	private $element2ValueFuncExp;
	/**
	 * Sets the func exp used to calculate a value based on the element.
	 * @param FuncExp $funcExp a FuncExp or a FieldSelector instance used to calculate a value
	 * example: fx('concat', fs('first_name'), ' ', fs('last_name'))	 
	 */
	public function setElement2ValueFuncExp($funcExp) {
		$this->element2ValueFuncExp = $funcExp;
	}	
	
	// element event handling
				
	protected function processElement($elementP, $dataFlowContext) {
		// evaluates the func exp
		$returnValue = $this->evaluateFuncExp($this->element2ValueFuncExp);
		// writes the output
		if(isset($returnValue)) $dataFlowContext->writeResultToOutput($returnValue, $this);
	}
}