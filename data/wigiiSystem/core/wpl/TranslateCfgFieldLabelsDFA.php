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
 * Translates the labels of a flow of cfgField StdClasses given a dictionary
 * Created by Medair (CWE) on 09.04.2018
 */
class TranslateCfgFieldLabelsDFA implements DataFlowActivity
{	
	private $_debugLogger;
	
	// Dependency injection
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("TranslateCfgFieldLabelsDFA");
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
	}	
	public function freeMemory() {
	    if(isset($this->fieldDictionary) && method_exists($this->fieldDictionary, 'freeMemory')) $this->fieldDictionary->freeMemory();
	    unset($this->fieldDictionary);
	}

	// configuation

	private $fieldDictionary;
	/**
	 * Sets the field dictionary which maps a field name to its translated labels.	 
	 * @param Array|ElementFileDataFlowConnector $fieldDictionary can be either an array mapping a field name to its array of translations. Or a CSV file with first column as the field name and then the translated labels.
	 */
	public function setFieldDictionary($fieldDictionary) {
	    $this->fieldDictionary = $fieldDictionary;
	}
	
	// stream data event handling
	
	public function startOfStream($dataFlowContext) {
		if(!isset($this->fieldDictionary)) throw new DataFlowServiceException('field dictionary has not been set', DataFlowServiceException::CONFIGURATION_ERROR);
		// if field dictionary is an instance of a ElementFileDataFlowConnector, then assumes its a CSV file and reads it.
		if($this->fieldDictionary instanceof ElementFileDataFlowConnector) {
		    $this->fieldDictionary = sel($dataFlowContext->getPrincipal(),$this->fieldDictionary,dfasl(
		        dfas("LineReaderDFA"),
		        dfas("CSV2ObjectDFA"),
		        dfas("ArrayBufferDFA","setKeyField","name")
		    ));
		}
	}
	public function processDataChunk($data, $dataFlowContext) {	    
	    $labels = $this->fieldDictionary[$data->name];
	    // converts dictionary entry to object if needed
	    if(isset($labels) && is_array($labels)) {
	        $labels = (object)$labels;
	        $this->fieldDictionary[$data->name] = $labels;
	    }
	    if(isset($labels)) {
	        // translates each language if label is defined in dico
	        if(is_array($labels->label)) {
	            foreach($this->getTranslationService()->getVisibleLanguage() as $lang=>$language) {
	                if(!empty($labels->label[$lang])) {
	                    if(!is_array($data->label)) $data->label = array();
	                    $data->label[$lang] = $labels->label[$lang];
	                }
	            }
	        }	        
	        // else sets general label if defined
	        elseif(!empty($labels->label)) $data->label = $labels->label;
	        // else assumes labels are given as separated entries: label l01, label l02, etc
	        else {
	            foreach($this->getTranslationService()->getVisibleLanguage() as $lang=>$language) {
	                if(!empty($labels->{'label '.$lang})) {
	                    if(!is_array($data->label)) $data->label = array();
	                    $data->label[$lang] = $labels->{'label '.$lang};
	                }
	            }
	        }
	    }
	    // pushes cfgField further down in DataFlow
	    $dataFlowContext->writeResultToOutput($data, $this);
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