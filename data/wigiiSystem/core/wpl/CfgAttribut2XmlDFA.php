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
 * Serializes cfgAttribut StdClasses to XML following the Wigii configuration file syntax. 
 * Created by CWE on 02 Mars 2014
 */
class CfgAttribut2XmlDFA implements DataFlowActivity
{	
	private $_debugLogger;
	private $rootNode;
	
	// Dependency injection
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("CfgAttribut2XmlDFA");
		}
		return $this->_debugLogger;
	}	
	
	private $wplToolBox;
	public function setWplToolbox($wplToolbox) {
		$this->wplToolBox = $wplToolbox;
	}
	protected function getWplToolbox() {
		// autowired
		if(!isset($this->wplToolBox)) {
			$this->wplToolBox = TechnicalServiceProvider::getWplToolbox();			
		}
		return $this->wplToolBox;
	} 
	
	// Object lifecycle
		
	public function reset() {
		$this->freeMemory();
		$this->outputAsString = false;
		$this->groupInput= true;
		$this->rootNodeName= 'attributes';	
	}	
	public function freeMemory() {
		unset($this->rootNode);
	}

	// configuation

	private $outputAsString;
	/**
	 * If true, then the output is xml as string else the output is SimpleXMLElement instances.
	 * Default is SimpleXMLElement instances.
	 * @param boolean $bool
	 */
	public function setOutputAsString($bool) {
		$this->outputAsString = $bool;
	}
	
	private $groupInput;
	/**
	 * If true, then all the incoming nodes are grouped as child node of one single parent node,
	 * and only the grouping parent node is pushed as output.
	 * Else, each incoming node are pushed as output.
	 * The name of the parent grouping node is given by the configuration property 'setRootNodeName'.
	 * By default, grouping is active.
	 * @param boolean $bool
	 */
	public function setGroupInput($bool) {
		$this->groupInput = $bool;
	}
	
	private $rootNodeName;
	/**
	 * Sets the name of the root node which holds the incoming nodes as children.
	 * Defaults to 'attributes'
	 * @param String $rootNodeName
	 */
	public function setRootNodeName($rootNodeName) {
		$this->rootNodeName = $rootNodeName; 
	}
	
	// stream data event handling
	
	public function startOfStream($dataFlowContext) {
		if($this->groupInput) {
			$this->rootNode = new SimpleXMLElement('<'.$this->rootNodeName.'/>');
		}
	}
	public function processDataChunk($data, $dataFlowContext) {
		if(is_string($data)) {
			if($this->groupInput) {
				$data = simplexml_load_string($data);
				simplexml_appendChild($this->rootNode, $data);
			}
			else {
				if($this->outputAsString) $dataFlowContext->writeResultToOutput($data, $this);
				else $dataFlowContext->writeResultToOutput(simplexml_load_string($data), $this);
			}
		}
		elseif($data instanceof stdClass) {
			$data = $this->getWplToolbox()->cfgAttribut2Xml($data);
			if($this->groupInput) {				
				simplexml_appendChild($this->rootNode, $data);
			}
			else {
				if($this->outputAsString) $dataFlowContext->writeResultToOutput($data->asXML(), $this);
				else $dataFlowContext->writeResultToOutput($data, $this);
			}
		}
		elseif($data instanceof SimpleXMLElement) {
			if($this->groupInput) {				
				simplexml_appendChild($this->rootNode, $data);
			}
			else {
				if($this->outputAsString) $dataFlowContext->writeResultToOutput($data->asXMl(), $this);
				else $dataFlowContext->writeResultToOutput($data, $this);
			}
		}
		else throw new DataFlowServiceException('data chunk cannot be converted to Wigii XML configuration node. '.(is_null($data) ? '(data is null)' : (is_object($data) ? '(data is of class '.get_class($data).')' : "($data)")), DataFlowServiceException::DATA_FORMAT_ERROR);
	}
	public function endOfStream($dataFlowContext) {
		if($this->groupInput) {
			if($this->outputAsString) $dataFlowContext->writeResultToOutput($this->rootNode->asXml(), $this);
			else $dataFlowContext->writeResultToOutput($this->rootNode, $this);
		}
	}
	
	
	// single data event handling
	
	public function processWholeData($data, $dataFlowContext) {
		// if only one node of type SimpleXmlElement and output is not string, then
		// assumes that entry is already a configuration node and passes it further
		if($data instanceof SimpleXMLElement && !$this->outputAsString) {
			$dataFlowContext->writeResultToOutput($data, $this);
		}
		else {
			$this->startOfStream($dataFlowContext);
			$this->processDataChunk($data, $dataFlowContext);
			$this->endOfStream($dataFlowContext);
		}
	}	
}