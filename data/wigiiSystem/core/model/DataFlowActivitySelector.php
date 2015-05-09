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
 * A data flow activity selector
 * Created by CWE on 28 mai 2013
 */
class DataFlowActivitySelector
{
	private $className;
	private $parameters = array();
	
	/**
	 * Creates a instance of a DataFlowActivitySelector
	 * @param $className the class name of the DataFlowActivity that should be executed
	 */
	public static function createInstance($className) {
		$returnValue = new self();
		if(is_null($className)) throw new DataFlowServiceException("className cannot be null", DataFlowServiceException::INVALID_ARGUMENT);
		$returnValue->setClassName($className);
		return $returnValue;
	}
	
	/**
	 * Returns the class name of the DataFlowActivity that should be executed
	 */
	public function getClassName() {
		return $this->className;
	}
	protected function setClassName($className) {
		$this->className = $className;
	}
	
	/**
	 * Defines a configuration parameter for the DataFlowActivity that will be executed
	 * @param $parameterName the parameter name. It should match a method name on the DataFlowActivity class
	 * for instance: 'setWigiiBag'. The DataFlowService will call $className->setWigiiBag
	 * @param $value the value of the parameter.
	 */
	public function setDataFlowActivityParameter($parameterName, $value) {
		if(is_null($parameterName)) throw new DataFlowServiceException("parameterName cannot be null", DataFlowServiceException::INVALID_ARGUMENT);
		$this->parameters[$parameterName] = $value;
	}
	
	/**
	 * Returns the list of parameters (parameterName, value)
	 */
	public function getDataFlowActivityParametersIterator() {
		return $this->parameters;
	}
}