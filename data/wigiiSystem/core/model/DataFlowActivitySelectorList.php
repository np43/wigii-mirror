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
 * A list of DataFlowActivity selectors
 * Created by CWE on 28 mai 2013
 */
interface DataFlowActivitySelectorList extends ObjectList
{
	/**
	 * Adds a data flow activity selector instance at the end of the list
	 * We can put two times the same data flow activity selector --> it results it two steps in the pipe.
	 */
	public function addDataFlowActivitySelectorInstance($dataFlowActivitySelector);
	
	/**
	 * Adds a data flow activity selector instance at the beginning of the list
	 * We can put two times the same data flow activity selector --> it results it two steps in the pipe.
	 */
	public function prependDataFlowActivitySelectorInstance($dataFlowActivitySelector);
	
	/**
	 * Adds a data flow activity selector at the end of the list given its class name
	 * Creates a DataFlowActivitySelector instance and adds it to the list.
	 * We can put two times the same data flow activity selector --> it results it two steps in the pipe.
	 */
	public function addDataFlowActivitySelector($className);
	
	/**
	 * Adds a data flow activity selector at the beginning of the list given its class name
	 * Creates a DataFlowActivitySelector instance and adds it to the list.
	 * We can put two times the same data flow activity selector --> it results it two steps in the pipe.
	 */
	public function prependDataFlowActivitySelector($className);
	
	/**
	 * Returns the DataFlowActivitySelector instance stored at given index
	 * Index starts at 0 and goes up to (count - 1)
	 * @param $index the index from which to retrieve the DataFlowActivitySelector index
	 * @throws ListException OUT_OF_BOUND if index is invalid
	 */
	public function getDataFlowActivitySelector($index);
	
	/**
	 * Returns one or several DataFlowActivitySelector that are of the given class
	 * @param $className the class name to select the DataFlowActivitySelectors
	 * @return one instance of a DataFlowActivitySelector or an array of DataFlowActivitySelectors 
	 * or null if no one is of the given class
	 */
	public function getDataFlowActivitySelectorByClassName($className);
	
	/**
	 * Sets a configuration parameter for all the DataFlowActivitySeletors which match the given class
	 * @param $className the class name to select the DataFlowActivitySelectors
	 * @param $parameterName the parameter name (see DataFlowActivitySelector, method setDataFlowActivityParameter)
	 * @param $value the parameter value
	 */
	public function setDataFlowActivityParameterForClass($className, $parameterName, $value);
	
	/**
	 * Configures one or several DataFlowActivitySelector that are of the given class
	 * @param $className the class name to select the DataFlowActivitySelectors
	 * @param $configurator an ObjectConfigurator instance that will be used to set the configuration
	 * parameters in each DataFlowActivitySelector
	 */
	public function configureDataFlowActivitySelectorByClassName($className, $configurator);
}