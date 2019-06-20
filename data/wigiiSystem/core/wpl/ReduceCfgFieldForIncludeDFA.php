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
 * A data flow activity which reduces cfgField stdClasses to config include selectors expressions.
 * This dataflow activity is useful to re-factor a complete configuration file into a section which can be included into another configuratin file.
 * Created by CWE on 06.05.2019
 */
class ReduceCfgFieldForIncludeDFA implements DataFlowActivity
{	
	private $_debugLogger;
	private $configIncludeSelector;
	
	// Dependency injection
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("ReduceCfgFieldForIncludeDFA");
		}
		return $this->_debugLogger;
	}	
	
	// Object lifecycle
		
	public function reset() {
		$this->freeMemory();
	}	
	public function freeMemory() {
	    unset($this->configIncludeSelector);
	}

	// configuration

	/**
	 * Sets the include config file path to use
	 * @param String|ConfigIncludeSelector $configFilePath
	 */
	public function setIncludeConfigFilePath($configFilePath) {
        if(!isset($configFilePath)) throw new DataFlowServiceException('configFilePath cannot be null', DataFlowServiceException::INVALID_ARGUMENT);
        if($configFilePath instanceof ConfigIncludeSelector) $this->configIncludeSelector = $configFilePath;
        else $this->configIncludeSelector = ConfigIncludeSelector::createInstance($configFilePath);
        // includes whole node
        $this->configIncludeSelector->setXmlAttr(null);
	}
	
	// stream data event handling
	
	public function startOfStream($dataFlowContext) {
		// checks for presence of configIncludeSelector
	    if(!isset($this->configIncludeSelector)) {
	        $this->configIncludeSelector = $dataFlowContext->getAttribute('ConfigIncludeSelector');
	        if(!isset($this->configIncludeSelector)) throw new DataFlowServiceException('No include config file path has been given.', DataFlowServiceException::CONFIGURATION_ERROR);
	    }
	}
	public function processDataChunk($data, $dataFlowContext) {	    
	    // reduces group start to include expression
	    if($data->attributes && $data->attributes['groupStart']=='1') {
	        $this->configIncludeSelector->setNodePath('fields/'.$data->name.'[1]');
	        $this->configIncludeSelector->setXmlAttr('*');
	        $includExp = $this->configIncludeSelector->toFx();
	        $includeExp = fx2str($includExp);
	        $returnValue = cfgField($data->name,array('groupStart'=>'1','include'=>$includeExp));
	    }
	    // reduces group end to include expression
	    elseif($data->attributes && $data->attributes['groupEnd']=='1') {
	        $this->configIncludeSelector->setNodePath('fields/'.$data->name.'[2]');
	        $this->configIncludeSelector->setXmlAttr('*');
	        $includExp = $this->configIncludeSelector->toFx();
	        $includeExp = fx2str($includExp);
	        $returnValue = cfgField($data->name,array('groupEnd'=>'1','include'=>$includeExp));
	    }
	    // ignores freetext
	    elseif($data->attributes && !isset($data->attributes['type'])) $returnValue = $data;
	    // reduces field to include expression
	    else {
	        $this->configIncludeSelector->setNodePath('fields/'.$data->name);
	        $this->configIncludeSelector->setXmlAttr(null);
	        $includExp = $this->configIncludeSelector->toFx();
	        $includeExp = fx2str($includExp);
	        $returnValue = cfgField($data->name,array('include'=>$includeExp));
	    }
	    $dataFlowContext->writeResultToOutput($returnValue, $this);
	}
	public function endOfStream($dataFlowContext) {
	    /* nothing to do */
	}
	
	
	// single data event handling
	
	public function processWholeData($data, $dataFlowContext) {
		$this->startOfStream($dataFlowContext);
		$this->processDataChunk($data, $dataFlowContext);
		$this->endOfStream($dataFlowContext);
	}	
}