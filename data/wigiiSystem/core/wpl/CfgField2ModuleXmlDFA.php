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
 * Aggregates a flow of cfgField StdClasses into a Module XML configuration node, using the config_moduleTemplate.xml
 * This DataFlowActivity cannot be called from public space (i.e. caller is located outside of the Wigii instance) 
 * Created by Medair.org (CWE) on 10.08.2017
 * Modified by Medair (CWE) on 10.04.2018 to enable updating xml attributes or labels into an existing Wigii configuration file
 */
class CfgField2ModuleXmlDFA implements DataFlowActivity
{	
	private $_debugLogger;
	private $moduleXml;
	private $moduleActivities;
	private $supportedActivities;
	
	// Dependency injection
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("CfgField2ModuleXmlDFA");
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
	
	// Object lifecycle
		
	public function reset() {
		$this->freeMemory();
		$this->outputAsString = false;
		$this->moduleConfigTemplateFilename = 'config_moduleTemplate.xml';
		$this->supportedActivities = array('module'=>true, 'listView'=>true,'blogView'=>true,'sortBy'=>true,'groupBy'=>true,'selectSearchBar'=>true);
	}	
	public function freeMemory() {
	    unset($this->moduleXml);
	    unset($this->moduleActivities);
	    unset($this->fieldSelectorList);
	}

	// configuration

	private $outputAsString;
	/**
	 * If true, then the output is xml as string else the output is SimpleXMLElement instances.
	 * Default is SimpleXMLElement instances.
	 * @param boolean $bool
	 */
	public function setOutputAsString($bool) {
		$this->outputAsString = $bool;
	}
	
	private $moduleConfigTemplateFilename;
	/**
	 * Sets the name of the module configuration template to use as a base.
	 * Defaults to config_moduleTemplate.xml
	 * @param String|ElementFileDataFlowConnector $fileName the name of a Wigii configuration file or an already open connector to an xml file attached to current element 
	 * 
	 */
	public function setModuleConfigTemplateFilename($fileName) {
	    if(!empty($fileName)) $this->moduleConfigTemplateFilename = $fileName;
	}
	
	private $moduleName;
	/**
	 * Sets the name of the Module to be used when creating new module configuration based on template
	 */
	public function setModuleName($moduleName) {
        $this->moduleName = $moduleName;	    
	}
	
	/**
	 * Declares an activity to added to the XML configuration file.
	 * By default, already supports listView, blogView, sortBy, groupBy and selectSearchBar
	 */
	public function addSupportedActivity($activityName) {
        $this->supportedActivities[$activityName] = true;	    
	}
	
	private $keepExistingFields;
	/**
	 * If true then existing fields in configuration file are kept and attributes are updated with the ones coming in the flow. 
	 * Else, existing fields are deleted and replaced by incoming fields, without any merge. By default merge is NOT active.	 
	 */
	public function setKeepExistingFields($bool) {
	    $this->keepExistingFields = $bool;
	}
	
	private $fieldSelectorList;
	/**
	 * @param FieldSelectorList $fieldSelectorList optional FieldSelectorList specifying which Wigii config xml attributes should be updated into the XML file
	 * If not specified, takes the FieldSelectorList present in DataFlowContext,
	 * if none, takes all attributes present in cfgField StdClass instance.
	 * CAUTION: The FieldSelectorList is taken into account only on update, ie if setKeepExistingFields=true
	 */
	public function setFieldSelectorList($fieldSelectorList) {
	    $this->fieldSelectorList = $fieldSelectorList;
	}	
	
	// stream data event handling
	
	public function startOfStream($dataFlowContext) {
	    $dataFlowContext->assertOriginIsNotPublic();
		// loads xml configuration template
		$this->moduleXml = $this->loadModuleConfigXmlTemplate($dataFlowContext->getPrincipal(), $this->moduleConfigTemplateFilename);
		// on update, initializes FieldSelectorList to update subset of xml attributes if defined
		if($this->keepExistingFields) {
		    if(!isset($this->fieldSelectorList)) $this->fieldSelectorList = $dataFlowContext->getAttribute('FieldSelectorList');
		    if(!$this->moduleXml->fields) throw new DataFlowServiceException('Cannot update XML configuration file, fields section is missing',DataFlowServiceException::CONFIGURATION_ERROR);
		}
		// on creation, opens the module activities channels
		else {
		    $this->moduleActivities = array();
		}
	}
	public function processDataChunk($data, $dataFlowContext) {
	    // updates existing XMl file with incoming field
	    if($this->keepExistingFields) {	       
	       $fieldXml = $this->moduleXml->fields->{$data->name};	       
	       // creates field if doesn't exist
	       if(!$fieldXml) {
	           $fieldXml = $this->moduleXml->fields->addChild($data->name);
	           $newField = true;
	       }
	       else {
	           $newField = false;
	           $firstAttribute = $fieldXml->attribute;
	           if($firstAttribute && $firstAttribute->count()>1) $firstAttribute = $firstAttribute[0]; 
	       }
	       // creates or updates xml attributes according to FieldSelectorList if defined
	       // if new field, then always puts all defined attributes
	       if(!isset($this->fieldSelectorList)) $newField = true;
	       if(!empty($data->attributes)) {
	           foreach($data->attributes as $k=>$v) {
	               if($newField || $this->fieldSelectorList->containsFieldSelector($k)) {
	                   $fieldXml[$k] = $v;
	               }
	           }
	       }
	       // creates or updates translated labels
	       if(is_array($data->label)) {
	           foreach($data->label as $lang => $label) {
	               if($newField || $this->fieldSelectorList->containsFieldSelector('label',$lang)) {
	                   $labelXml = $fieldXml->{'label_'.$lang};
	                   $newLabelXml = new SimpleXMLElement('<label_'.$lang.'>'.$label.'</label_'.$lang.'>');
	                   if(!$labelXml) simplexml_appendChild($fieldXml, $newLabelXml,$firstAttribute);
	                   else simplexml_replaceNode($labelXml, $newLabelXml);
	               }
	           }
	       }
	       // creates or updates general label
	       elseif(!empty($data->label) && 
	           ($newField || $this->fieldSelectorList->containsFieldSelector('label') 
	            || $this->fieldSelectorList->containsFieldSelector('label','value'))) {
	           $labelXml = $fieldXml->label;
	           $newLabelXml = new SimpleXMLElement('<label>'.$data->label.'</label>');
	           if(!$labelXml) simplexml_appendChild($fieldXml, $newLabelXml,$firstAttribute);
	           else simplexml_replaceNode($labelXml, $newLabelXml);
	       }
	    }
	    // else dispatches the incoming cfgField Stdclass instance into the matching activities
	    else {
    	    $dfs = $dataFlowContext->getDataFlowService();	    
    		foreach($this->supportedActivities as $activityName => $bool) {
    		    // if the activity concerns the incoming field, then gets the associated channel
    		    if($data->{$activityName} || $activityName == 'module') {
        		    $channel = $this->moduleActivities[$activityName];
        		    // opens the channel
        		    if(!isset($channel)) {
        		        $channel = $dfs->startStream($dataFlowContext->getPrincipal(), $this->getDfaslForBuildingActivityXmlNode($activityName));
        		        $this->moduleActivities[$activityName] = $channel;
        		    }
        		    // pushes the cfgField inside the activity channel
        		    $dfs->processDataChunk($data,$channel);
    		    }		    
    		}
	    }
	}
	public function endOfStream($dataFlowContext) {
	    // Merges the generated activities in the module xml configuration
	    if(!$this->keepExistingFields) {
    	    $dfs = $dataFlowContext->getDataFlowService();
    	    foreach($this->moduleActivities as $activityName => $activityNode) {
    	        // Closes the stream and gets the XML back
    	        $activityNode = $dfs->endStream($activityNode);
    	        // If activity has some fields, replaces the activity fields with these ones.
    	        if($activityNode && $activityNode->count() > 0) {
    	            // updates or creates activity node
    	            if($activityName && $activityName != 'module') {
    	                $node = $this->moduleXml->{$activityName};
    	                // if activity does not exists, creates it.
    	                if(!$node) simplexml_appendChild($this->moduleXml, simplexml_appendChild(
    	                    new SimpleXMLElement('<'.$activityName.'/>'), 
    	                    $activityNode
    	                ));
    	                // else updates activity
    	                else simplexml_replaceNode($node->fields, $activityNode);
    	            }
    	            // else updates module fields
    	            else simplexml_replaceNode($this->moduleXml->fields, $activityNode);
    	        }
    	    }
	    }	    
	    // Outputs the XML configuration
	    if($this->outputAsString) {
	        $fileContent = $this->moduleXml->asXml();
	        // pretty prints result
	        $fileContent = $this->prettyPrintXmlString($fileContent);
	        // Puts all FuncExp into single quotes, then call html_entity_decode.
	        // A FuncExp is a string having parenthesis and no quotes inside.
	        $fileContent = html_entity_decode(preg_replace('/="([^"\']+[(][^"\']*[)][^"\']*)"/', '=\'$1\'', $fileContent),ENT_QUOTES);
	        $dataFlowContext->writeResultToOutput($fileContent, $this);
	    }
	    else $dataFlowContext->writeResultToOutput($this->moduleXml, $this);
	}
	
	
	// single data event handling
	
	public function processWholeData($data, $dataFlowContext) {
		$this->startOfStream($dataFlowContext);
		$this->processDataChunk($data, $dataFlowContext);
		$this->endOfStream($dataFlowContext);
	}
	
	// Utilities
	
	/**
	 * Loads a module configuration xml file from the client folder or from an attached file to current element.
	 * @param Principal $principal current principal executing the data flow 
	 * @param String|ElementFileDataFlowConnector $moduleConfigTemplateFilename module configuration file name to be used as a template
	 * @return SimpleXMLElement the module XML base
	 */
	protected function loadModuleConfigXmlTemplate($principal, $moduleConfigTemplateFilename) {
	    if(!isset($moduleConfigTemplateFilename)) throw new DataFlowServiceException('Module configuration template cannot be null');
	    // if attached file to element, then reads it
	    if($moduleConfigTemplateFilename instanceof ElementFileDataFlowConnector) {
	        $xmlString = sel($principal,$moduleConfigTemplateFilename,dfasl(
	            dfas("StringBufferDFA")
            ));
	    }
	    // Checks for presence of module configuration template in client folder
	    else {
    	    $configS = $this->getConfigService();
    	    $file = $configS->getClientConfigFolderPath($principal).$moduleConfigTemplateFilename;
    	    if(!file_exists($file)){
    	        $file = $configS->getConfigFolderPath($principal).$moduleConfigTemplateFilename;
    	    }
    	    if(!file_exists($file)) throw new DataFlowServiceException("Module configuration template $moduleConfigTemplateFilename does not exist.");
    	    
    	    $isStandardTemplate = ($moduleConfigTemplateFilename == 'config_moduleTemplate.xml');
    	    
    	    // Loads xml file
    	    $xmlString = file_get_contents($file);
    	    // Sets module name if template is the standard one.
    	    if($isStandardTemplate) {
    	        if(!isset($this->moduleName)) throw new DataFlowServiceException('A module name should be provided when generating a configuration file based on the standard template');
        	    $xmlString = str_replace('<moduleName>','<'.$this->moduleName.'>', $xmlString);
        	    $xmlString = str_replace('</moduleName>','</'.$this->moduleName.'>', $xmlString);
    	    }
	    }
	    
	    // Parses the XML file
	    $returnValue = simplexml_load_string($xmlString);
	    if($returnValue) return $returnValue;
	    else throw new DataFlowServiceException('Error while loading xml configuration template '.$moduleConfigTemplateFilename);
	}
	
	/**	
	 * @return DataFlowActivitySelectorList returns a DataFlow which builds the xml node of a given activity in the module configuration based on a flow of cfgField StdClass instances.
	 */
	protected function getDfaslForBuildingActivityXmlNode($activityName) {
	    // for activities, the incoming cfgField StdClass is re-mapped to keep only matching field and generate a sequence of fields c1, c2, ...
	    if($activityName && $activityName != 'module') return dfasl(
	        dfas('CallbackDFA','setProcessDataChunkCallback',function($cfgField,$callbackDFA) use($activityName){
	            $i = $callbackDFA->getValueInContext('fieldCount')+1;
	            $callbackDFA->setValueInContext('fieldCount',$i);
	            $attributes = array('field'=>$cfgField->name);
	            // adds specific attributes for List View
	            if($activityName == 'listView') {
	                $attributes['width'] = 'null';
	            }
	            $callbackDFA->writeResultToOutput(cfgField('c'.$i,$attributes));
	        }),
	        dfas('CfgField2XmlDFA')	        
	    );
	    // if activity is not specified, then concerns the fields of the module itself
	    else return dfasl(dfas('CfgField2XmlDFA'));
	}
	
	/**
	 * Pretty prints xml string
	 * @return String xml tree well formatted
	 */
	protected function prettyPrintXmlString($xmlString) {
	    $dom = new DOMDocument("1.0");
	    $dom->preserveWhiteSpace = false;
	    $dom->formatOutput = true;
	    $dom->loadXML($xmlString);
	    return $dom->saveXML();
	}
}