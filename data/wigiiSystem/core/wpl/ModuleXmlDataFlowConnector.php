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
 * A connector which dumps the fields of a Wigii xml configuration file into a flow of cfgField stdClass instances
 * This DataFlow connector cannot be called from public space (i.e. caller is located outside of the Wigii instance)
 * Created by Medair (CWE) on 04.04.2018
 */
class ModuleXmlDataFlowConnector implements DataFlowDumpable
{
	private $_debugLogger;
	private $lockedForUse = true;
	
	// Object lifecycle
	
	public function reset() {
		$this->freeMemory();	
		$this->lockedForUse = true;
		$this->labelsAsText = false;
	}	
	public function freeMemory() {		
		unset($this->listFilter);
		if(isset($this->xmlFile) && method_exists($this->xmlFile, 'freeMemory')) $this->xmlFile->freeMemory();
		unset($this->xmlFile);
		$this->lockedForUse = false;	
	}
		
	public function isLockedForUse() {
		return $this->lockedForUse;
	}
	
	public static function createInstance($xmlFile=null, $listFilter=null) {
		$returnValue = new self();
		$returnValue->reset();
		if(isset($xmlFile)) $returnValue->setXmlFile($xmlFile);
		if(isset($listFilter)) $returnValue->setListFilter($listFilter);
		return $returnValue;
	}
	
	// Dependency injection
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("ModuleXmlDataFlowConnector");
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
	
	private $configS;
	public function setConfigService($configService)
	{
	    $this->configS = $configService;
	}
	protected function getConfigService()
	{
	    // autowired
	    if(!isset($this->configS))
	    {
	        $this->configS = ServiceProvider::getConfigService();
	    }
	    return $this->configS;
	}
	
	// Configuration
	
	private $xmlFile;
	/**
	 * @param String|ElementFileDataFlowConnector xmlFile the name of an existing Wigii configuration file to load or an already open connector to an xml file attached to current element
	 */
	public function setXmlFile($xmlFile) {
	    $this->xmlFile = $xmlFile;
	}
	
	private $listFilter;
	/**
	 * Sets the list filter used to filter the module fields
	 * @param ListFilter $listFilter a ListFilter
	 */
	public function setListFilter($listFilter) {
		$this->listFilter = $listFilter;
	}
	/**
	 * Returns the list filter used to filter the module fields
	 * @return ListFilter
	 */
	public function getListFilter() {
		return $this->listFilter;
	}	
	
	private $labelsAsText;
	/**
	 * If true, then field labels are converted to text. Any html tags are removed.
	 */
	public function setLabelsAsText($bool) {
	    $this->labelsAsText = $bool;
	}
	
	// DataFlowDumpable implementation
	
	public function dumpIntoDataFlow($dataFlowService, $dataFlowContext) {
	    $dataFlowContext->assertOriginIsNotPublic();
		if(!isset($this->xmlFile)) throw new DataFlowServiceException('No Wigii xml file has been set from which to extracts the fields', DataFlowServiceException::INVALID_ARGUMENT);
		$principal = $dataFlowContext->getPrincipal();
		// Loads Wigii xml configuration
		if($this->xmlFile instanceof ElementFileDataFlowConnector) {
		    $moduleXml = $this->readXmlConfigFile($principal,$this->xmlFile);
		}
		else $moduleXml = $this->readWigiiConfigFile($principal,$this->xmlFile);
		// Dumps module fields into data flow
		if(isset($moduleXml) && $moduleXml->fields) {		    
		    $fsl = null; $cfgFieldEval=null;
		    if(isset($this->listFilter)) {
		        // Prepares selectable attributes based on ListFilter given FieldSelectorList
		        if(!is_null($this->listFilter->getFieldSelectorList()) && !$this->listFilter->getFieldSelectorList()->isEmpty()) {    		        
    		        $fsl = array();
    		        foreach($this->listFilter->getFieldSelectorList()->getListIterator() as $fs) {
    		            $fsl[$fs->getFieldName()] = true;
    		        }
    		        // stores FieldSelectorList in DataFlowContext for future use
    		        $dataFlowContext->setAttribute('FieldSelectorList', $this->listFilter->getFieldSelectorList());
		        }
		        // Prepares a cfgField LogExp evaluator based on ListFilter given FieldSelectorLogExp
		        if(!is_null($this->listFilter->getFieldSelectorLogExp())) {
		            $cfgFieldEval = TechnicalServiceProvider::getCfgFieldLogExpEvaluator();
		        }
		    }
		    
		    $wplToolbox = $this->getWplToolbox();
		    foreach($moduleXml->fields->children() as $fieldXml) {
		        $cfgField = $wplToolbox->xml2cfgField($fieldXml);
		        // filters cfgField against LogExp if defined
		        if(is_null($cfgFieldEval) || isset($cfgFieldEval) && $cfgFieldEval->evaluate($cfgField, $this->listFilter->getFieldSelectorLogExp())) {
    		        // reduces the attributes to the given FieldSelectorList
    		        if(isset($fsl) && isset($cfgField->attributes)) {    		            
    		            $cfgField->attributes = array_intersect_key($cfgField->attributes, $fsl);    		            
    		        }
    		        // if specified, converts each label to text
    		        if($this->labelsAsText) {
    		            if(is_array($cfgField->label)) {
    		                $cleanLabels = array();
    		                foreach($cfgField->label as $lang=>$label) {
    		                    if(!empty($label)) $cleanLabels[$lang] = strip_tags($label);
    		                }
    		                $cfgField->label = $cleanLabels;
    		            }
    		            elseif(!empty($cfgField->label)) $cfgField->label = strip_tags($cfgField->label);
    		        }
    		        $dataFlowService->processDataChunk($cfgField, $dataFlowContext);
		        }
		    }
		    if(isset($cfgFieldEval)) $cfgFieldEval->freeMemory();
		}
	}
	
	/**
	 * Reads a Wigii configuration file given the file name and returns a loaded SimpleXmlElement
	 * @param String $fileName
	 * @return SimpleXMLElement the loaded Wigii configuration file as XML
	 */
	protected function readWigiiConfigFile($principal,$fileName) {
	    $returnValue = $this->getConfigService()->getClientConfigFolderPath($principal).$fileName;	    
	    if(!file_exists($returnValue)) throw new DataFlowServiceException("Module configuration file $fileName does not exist.");
	    // Parses the XML file
	    $returnValue = simplexml_load_file($returnValue);
	    if($returnValue) return $returnValue;
	    else throw new DataFlowServiceException('Error while loading xml configuration file '.$fileName);
	}
	
	/**
	 * Reads the content of a Wigii XML configuration file attached to current element and dumps it as a SimpleXmlElement
	 * @param ElementFileDataFlowConnector $elementFileDfc open element file data flow connector
	 * @return SimpleXMLElement the loaded configuration file as XML.
	 */
	protected function readXmlConfigFile($principal,$elementFileDfc) {
	    // Dumps XML file into a string
	    $returnValue = sel($principal,$elementFileDfc,dfasl(dfas("StringBufferDFA")));
	    // Parses the XML file
	    $returnValue = simplexml_load_string($returnValue);
	    if($returnValue) return $returnValue;
	    else throw new DataFlowServiceException('Error while loading xml configuration file '.$fileName);
	}
}