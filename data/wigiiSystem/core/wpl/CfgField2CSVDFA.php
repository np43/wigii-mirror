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
 * Serializes cfgField StdClasses as CSV. 
 * Created by Medair (CWE) on 09.04.2018
 */
class CfgField2CSVDFA implements DataFlowActivity
{	
	private $_debugLogger;
	private $cfgFieldCount;
	
	// Dependency injection
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("CfgField2CSVDFA");
		}
		return $this->_debugLogger;
	}	
	
	private $transS;
	public function setTranslationService($translationService)
	{
	    $this->transS = $translationService;
	}
	protected function getTranslationService()
	{
	    // autowired
	    if(!isset($this->transS))
	    {
	        $this->transS = ServiceProvider::getTranslationService();
	    }
	    return $this->transS;
	}
	
	// Object lifecycle
		
	public function reset() {
		$this->freeMemory();
		$this->cfgFieldCount = 0;
		$this->labelsAsText = false;
	}	
	public function freeMemory() {
		unset($this->fieldSelectorList);
	}

	// configuation

	private $fieldSelectorList;
	/**
	 * @param FieldSelectorList $fieldSelectorList optional FieldSelectorList specifying which Wigii config xml attributes should be dumped into the CSV
	 * If not specified, takes the FieldSelectorList present in DataFlowContext, 
	 * if none, takes the attributes of the first cfgField StdClass instance as a model.
	 */
	public function setFieldSelectorList($fieldSelectorList) {
	    $this->fieldSelectorList = $fieldSelectorList;
	}	
	
	private $labelsAsText;
	/**
	 * If true, then field labels are converted to text. Any html tags are removed.	 
	 */
	public function setLabelsAsText($bool) {
	    $this->labelsAsText = $bool;
	}
	
	// stream data event handling
	
	public function startOfStream($dataFlowContext) {
		$this->cfgFieldCount = 0;
		if(!isset($this->fieldSelectorList)) $this->fieldSelectorList = $dataFlowContext->getAttribute('FieldSelectorList');
	}
	public function processDataChunk($data, $dataFlowContext) {	    
	    // on first field, dumps the CSV header
	    if($this->cfgFieldCount == 0) {
    	   // if no FieldSelectorList then creates one based on first cfgField pattern
    	   if(!isset($this->fieldSelectorList)) {
    	       $this->fieldSelectorList = FieldSelectorListArrayImpl::createInstance(true,false);
    	       if(isset($data->attributes)) {
    	           foreach($data->attributes as $k=>$v) {
    	               $this->fieldSelectorList->addFieldSelector($k);
    	           }
    	       }
    	   }
    	   // dumps CSV header
    	   $this->dumpCSVHeader($dataFlowContext);
	    }
	    // dumps the CSV row
	    $this->dumpCSVRow($data, $dataFlowContext);
	    $this->cfgFieldCount++;
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
	
	// implementation
	
	/**
	 * @param DataFlowContext $dataFlowContext current open data flow
	 */
	protected function dumpCSVHeader($dataFlowContext) {
	    $header = '';
	    // name
	    $header .= 'name';
	    // label
	    $header .= ',label';
	    foreach($this->getTranslationService()->getVisibleLanguage() as $lang=>$language) {
	        $header .= ',label '.$lang;
	    }
	    // attributes
	    if(isset($this->fieldSelectorList) && !$this->fieldSelectorList->isEmpty()) {
	        foreach($this->fieldSelectorList->getListIterator() as $fs) {
	            $header .= ','.$fs->getFieldName();
	        }
	    }
	    $dataFlowContext->writeResultToOutput($header, $this);
	}
	/**
	 * @param DataFlowContext $dataFlowContext current open data flow
	 */
	protected function dumpCSVRow($cfgField,$dataFlowContext) {
	    $row = '';
	    // name
	    $row .= $this->formatValueForCSV($cfgField->name);
	    // label
	    if(is_array($cfgField->label)) {
	        $row .= ','; // general label is empty
	        foreach($this->getTranslationService()->getVisibleLanguage() as $lang=>$language) {
	            $row .= ','.$this->formatLabelForCSV($cfgField->label[$lang]);
	        }
	    }
	    else {
	        $row .= ','.$this->formatLabelForCSV($cfgField->label); // general label
	        // language labels are empty
	        foreach($this->getTranslationService()->getVisibleLanguage() as $lang=>$language) {
	            $row .= ',';
	        }
	    }
	    
	    // attributes
	    if(isset($this->fieldSelectorList) && !$this->fieldSelectorList->isEmpty()) {
	        foreach($this->fieldSelectorList->getListIterator() as $fs) {
	            if(isset($cfgField->attributes)) $row .= ','.$this->formatValueForCSV($cfgField->attributes[$fs->getFieldName()]);
	            else $row .= ',';
	        }
	    }
	    $dataFlowContext->writeResultToOutput("\n".$row, $this);
	}
	/**
	 * @param String $label Wigii field label to be formated to be compatible with CSV. 
	 * If labelsAsText is true, then gets rid of any HTML formatting tags, else keeps inner html.
	 */
	protected function formatLabelForCSV($label) {
	    if($this->labelsAsText) $label = strip_tags($label);
	    return $this->formatValueForCSV($label);
	}
	/**	 
	 * @param String $value the value to be formated to be compatible with Wigii CSVs
	 */
	protected function formatValueForCSV($value) {
	    $value = formatToString($value);
	    $value = str_replace('"', '""', str_replace('&nbsp;', ' ', str_replace("\n", '\\\\n', str_replace("\r", "", $value))));
	    $value = '"'.$value.'"';
	    return $value;
	}
}