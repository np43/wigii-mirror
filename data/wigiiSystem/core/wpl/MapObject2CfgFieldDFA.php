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
 * A data flow activity which maps PHP stdClass instances to cfgField stdClass instances
 * Used in the process of generating Wigii Configuration files from various sources (like CSV)
 * Created by Medair.org (CWE) on 11.08.2017
 */
class MapObject2CfgFieldDFA implements DataFlowActivity
{	
	private $_debugLogger;
	private $supportedActivities;
	private $supportedLanguagesByLabel;
	
	// Dependency injection
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("MapObject2CfgFieldDFA");
		}
		return $this->_debugLogger;
	}	
	
	private $translationService;
	/**
	 * Injects a TranslationService to be used by this library
	 * @param TranslationService $translationService
	 */
	public function setTranslationService($translationService){
	    $this->translationService = $translationService;
	}
	/**
	 * Gets the injected TranslationService
	 * @return TranslationService
	 */
	protected function getTranslationService(){
	    //autowired
	    if(!isset($this->translationService)){
	        $this->translationService = ServiceProvider::getTranslationService();
	    }
	    return $this->translationService;
	}
			
	// Object lifecycle
		
	public function reset() {
		$this->freeMemory();
		$this->supportedActivities = array('listView'=>true,'blogView'=>true,'sortBy'=>true,'groupBy'=>true,'selectSearchBar'=>true);
	}	
	public function freeMemory() {
	}

	// configuration

	/**
	 * Declares an activity to added to the XML configuration file.
	 * By default, already supports listView, blogView, sortBy, groupBy and selectSearchBar
	 */
	public function addSupportedActivity($activityName) {
        $this->supportedActivities[$activityName] = true;	    
	}
	
	// stream data event handling
	
	public function startOfStream($dataFlowContext) {
		// Loads supported languages by label
		$this->supportedLanguagesByLabel = array();
		foreach($this->getTranslationService()->getInstalledLanguage() as $language) {
		    $this->supportedLanguagesByLabel['label_'.$language] = $language;
		}
	}
	public function processDataChunk($data, $dataFlowContext) {
	    $returnValue = cfgField($data->name);
	    $label = null;
	    $attributes = array();
	    // aggregates field attributes
	    foreach($data as $attr=>$val) {
	       // skip name because already mapped 
	       if($attr == 'name') continue;
	       // adds field to supported activity
	       elseif($this->supportedActivities[$attr]) {
	           if($val) $returnValue->{$attr} = true;
	       }
	       // records label
	       elseif($this->supportedLanguagesByLabel[$attr]) {
	           if($val) {
	               if(!is_array($label)) $label = array();
	               $label[$this->supportedLanguagesByLabel[$attr]] = $val;
	           }
	       }
	       elseif($attr == 'label') {
	           if(!is_array($label) && $val) $label = $val;
	       }
	       // records attribute
	       elseif($val || $val===0 || $val === '0') $attributes[$attr] = $val;
	    }
	    if(!empty($attributes)) $returnValue->attributes = $attributes;
	    if(isset($label)) $returnValue->label = $label;
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