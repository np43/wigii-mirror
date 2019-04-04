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
 * Wigii Persistence Language Xml Reader data flow activity
 * Created by CWE on 28 mai 2013
 */
class WplXmlReaderDFA extends DataFlowActivityEventHandler implements WigiiXmlSaxEvents
{	
	// Object lifecycle
		
	public function reset() {
		parent::reset();
		$this->entityType = self::ENTITY_TYPE_RECORD;
		$this->readConfig = false;
		$this->outputEncoding = 'UTF-8';
		$this->smStack = array();
		$this->smStackPos = -1;
		$this->mustRecycleEventDispatcherOnDataflowService = false;
	}	
	public function freeMemory() {
		unset($this->wigiiBag);
		unset($this->fieldList);	
		unset($this->fieldSelectorList);		
		unset($this->smStack);	
		parent::freeMemory();
	}
	
	// dependency injection
	
	private $mustRecycleEventDispatcherOnDataflowService = false;
	
	/**
	 * Defaults to WigiiXmlSaxParserDFA	 
	 */
	protected function getEventDispatcher($dataFlowContext) {
		$evd = parent::getEventDispatcher($dataFlowContext);
		// if not set, then gets a WigiiXmlSaxParserDFA instance
		if(!isset($evd)) {
			$evd = $dataFlowContext->getDataFlowService()->getWigiiXmlSaxParserDFA($dataFlowContext);
			// configures the parser
			$evd->setCaseFolding(false);
			$evd->setOutputEncoding($this->outputEncoding);
			// sets the parser into the parent.
			parent::setEventDispatcher($evd);
			$this->mustRecycleEventDispatcherOnDataflowService = true;
		}
		return $evd;
	}
			
	// configuration
	
	const ENTITY_TYPE_RECORD = 0;
	const ENTITY_TYPE_ELEMENT = 1;
	const ENTITY_TYPE_ELEMENT_LIST = 2;
	private $entityType = self::ENTITY_TYPE_RECORD;
	
	public function setEntityType($entityType) {
		switch($entityType) {
			case self::ENTITY_TYPE_RECORD:
			case self::ENTITY_TYPE_ELEMENT:
			case self::ENTITY_TYPE_ELEMENT_LIST:
				$this->entityType = $entityType;
				break;
			default: throw new DataFlowServiceException("invalid entity type. Should be one of WplXmlReaderDFA::ENTITY_TYPE_RECORD, WplXmlReaderDFA::ENTITY_TYPE_ELEMENT or WplXmlReaderDFA::ENTITY_TYPE_ELEMENT_LIST", DataFlowServiceException::INVALID_ARGUMENT);
		}
	}	
	
	private $wigiiBag = null;
	/**
	 * Sets the wigii bag to be filled
	 */
	public function setWigiiBag($wigiiBag) {
		$this->wigiiBag = $wigiiBag;
	}
	
	private $recordId;
	/**
	 * Sets the ID of the record to be filled in the Wigii Bag
	 */
	public function setRecordId($recordId) {
		$this->recordId = $recordId;
	}
	
	private $fieldList = null;
	/**
	 * Sets the FieldList to be used for the config or to be filled if readConfig is true
	 */
	public function setFieldList($fieldList) {
		$this->fieldList = $fieldList;
	}
	
	private $readConfig = false;
	/**
	 * If true, then reads the config in the xml stream
	 */
	public function setReadConfig($readConfig) {
		$this->readConfig = $readConfig;
	}
	
	private $fieldSelectorList = null;
	
	/**
	 * Sets the FieldSelectorList used to restrict the fields and subfields which are effectively read	 
	 */
	public function setFieldSelectorList($fieldSelectorList) {		
		$this->fieldSelectorList = $fieldSelectorList;
	}
	
	private $outputEncoding = 'UTF-8';
	/**
	 * Sets the character encoding of the output strings
	 * @param String $encoding the encoding name, one of UTF-8, ISO-8859-1 or US-ASCII
	 */
	public function setOutputEncoding($encoding) {
		$this->outputEncoding = $encoding;		
	}	
	
	// stream data event handling
			
	public function startOfStream($dataFlowContext) {
		$this->initializeStateMachine();
		parent::startOfStream($dataFlowContext);
	}
	
	public function endOfStream($dataFlowContext) {
		if($this->mustRecycleEventDispatcherOnDataflowService) {
			$evd = $this->getEventDispatcher($dataFlowContext);
			parent::endOfStream($dataFlowContext);
			$dataFlowContext->getDataFlowService()->recycleWigiiXmlSaxParserDFA($evd, $dataFlowContext);
		}
		else parent::endOfStream($dataFlowContext);
	}
	
	
	// single data event handling
		
	public function processWholeData($data, $dataFlowContext) {
		$this->initializeStateMachine();
		parent::processWholeData($data, $dataFlowContext);
		if($this->mustRecycleEventDispatcherOnDataflowService) {
			$evd = $this->getEventDispatcher($dataFlowContext);			
			$dataFlowContext->getDataFlowService()->recycleWigiiXmlSaxParserDFA($evd, $dataFlowContext);
		}
	}	
	
	
	// SAX parser event handling	
		
	public function actOnStartElement($parser, $name, $attribs) {
		$this->getActiveSM()->actOnStartElement($parser, $name, $attribs);
	}	
	public function actOnEndElement($parser, $name) {
		$this->getActiveSM()->actOnEndElement($parser, $name);
	}	
	public function actOnCharData($parser, $data) {
		$this->getActiveSM()->actOnCharData($parser, $data);
	}
	
	// Implementation	
		
	private function initializeStateMachine() {
		switch($this->entityType) {
			case self::ENTITY_TYPE_RECORD:
				$sm = $this->switchToRecordSM();
				$sm->setFieldList($this->fieldList);
				$sm->setWigiiBag($this->wigiiBag);
				$sm->setFieldSelectorList($this->fieldSelectorList);
				$sm->setRecordId($this->recordId);
				$sm->setReadConfig($this->readConfig);
				break;
			default: 
				ServiceException::throwNotImplemented();
		}
	}
	
	private $smStack;
	private $smStackPos = -1;
	/**
	 * Pushes a state machine on the stack
	 */
	protected function pushSMOnStack($sm) {		
		$this->smStackPos++;
		$this->smStack[$this->smStackPos] = $sm;
		
	}
	/**
	 * Pops the last state machine from the stack
	 * Returns the pop state machine or null if stack is empty.
	 */
	protected function popSMFromStack() {
		if($this->smStackPos >= 0) {
			$returnValue = $this->smStack[$this->smStackPos];
			$this->smStackPos--;
		}
		else $returnValue = null;
		return $returnValue;
	}
	
	/**
	 * Returns the currently active state machine or null if not defined.
	 */
	protected function getActiveSM() {
		if($this->smStackPos >= 0) return $this->smStack[$this->smStackPos];
		else return null;
	}
	
	/**
	 * Switches to Record state machine	and returns it
	 */
	public function switchToRecordSM() {
		$sm = $this->getRecordSM();
		$this->pushSMOnStack($sm);
		return $sm;
	}	
	private $recordSM;	
	protected function getRecordSM() {
		if(isset($this->recordSM)) {		
			$returnValue = $this->recordSM;
			$returnValue->reset();
			unset($this->recordSM);
		}
		else $returnValue = $this->createRecordSM();		
		return $returnValue;		
	}
	protected function createRecordSM() {
		$returnValue = new WplXmlReaderDFARecordSM();
		$returnValue->setWplXmlReaderDFA($this);
		return $returnValue;
	}
	public function recycleRecordSM($recordSM) {
		if(isset($recordSM)) {
			$recordSM->freeMemory();
			$this->recordSM = $recordSM;			
		}
	}
	
	/**
	 * Switches to Fields state machine	and returns it
	 */
	public function switchToFieldsSM() {
		$sm = $this->getFieldsSM();
		$this->pushSMOnStack($sm);
		return $sm;
	}	
	private $fieldsSM;	
	protected function getFieldsSM() {
		if(isset($this->fieldsSM)) {		
			$returnValue = $this->fieldsSM;
			$returnValue->reset();
			unset($this->fieldsSM);
		}
		else $returnValue = $this->createFieldsSM();		
		return $returnValue;		
	}
	protected function createFieldsSM() {
		$returnValue = new WplXmlReaderDFAFieldsSM();
		$returnValue->setWplXmlReaderDFA($this);
		return $returnValue;
	}
	public function recycleFieldsSM($fieldsSM) {
		if(isset($fieldsSM)) {
			$fieldsSM->freeMemory();
			$this->fieldsSM = $fieldsSM;			
		}
	}
	
	/**
	 * Switches back to previous state machine and continues execution
	 */
	public function switchBackToPreviousSM() {
		$this->popSMFromStack();
	}	
}

/**
 * Wigii Persistence Language Xml Reader
 * Abstract State Machine base class
 * Created by CWE on 10 juin 2013
 *
 */
abstract class WplXmlReaderDFASM implements WigiiXmlSaxEvents {
	// Object lifecycle
	public abstract function reset();
	public abstract function freeMemory();
	/**
	 * Subclasses should call this method when entering the final state
	 */
	protected function reachFinalState() {
		// informs the WplXmlReaderDFA that it can switch back to previous SM		
		$this->getWplXmlReaderDFA()->switchBackToPreviousSM();
	}
	
	// Dependency injection
	private $wplXmlReaderDFA;
	public function setWplXmlReaderDFA($obj) {
		$this->wplXmlReaderDFA = $obj;
	}
	protected function getWplXmlReaderDFA() {		
		return $this->wplXmlReaderDFA;
	}
	
	// Exception helpers
	protected function throwInvalidTokenSyntaxError($invalidToken) {
		$this->throwSyntaxError("Not expecting $invalidToken here.");
	}
	protected function throwSyntaxError($msg) {
		throw new DataFlowServiceException("Syntax error. $msg", DataFlowServiceException::SYNTAX_ERROR);
	}
}

/**
 * Wigii Persistence Language Xml Reader Data Flow Activity
 * Record State Machine
 * Created by CWE on 6 juin 2013
 *
 */
class WplXmlReaderDFARecordSM extends WplXmlReaderDFASM {
	private $state = self::RECORD_SM_INIT;
	const RECORD_SM_INIT = 0;
	const RECORD_SM_RECORD = 1;
	const RECORD_SM_FIELDSCONFIG = 2;
	const RECORD_SM_FIELDS = 3;
	const RECORD_SM_FINAL = 4; 
			
	// Object lifecycle
	public function reset() {
		$this->freeMemory();
		$this->state = self::RECORD_SM_INIT;
		$this->readConfig = false;
	}
	public function freeMemory() {
		unset($this->wigiiBag);
		unset($this->recordId);
		unset($this->fieldList);
		unset($this->fieldSelectorList);
	}
	protected function reachFinalState() {
		parent::reachFinalState();
		$this->getWplXmlReaderDFA()->recycleRecordSM($this);
	}
	
	// SM configuration
	
	private $wigiiBag;
	/**
	 * Sets the wigii bag in which the read fields values should be stored
	 */
	public function setWigiiBag($wigiiBag) {
		if(is_null($wigiiBag)) throw new DataFlowServiceException("wigii bag cannot be null", DataFlowServiceException::INVALID_ARGUMENT);
		$this->wigiiBag = $wigiiBag;
	}
	
	private $recordId;
	/**
	 * Sets the ID of the record to be filled in the Wigii Bag
	 */
	public function setRecordId($recordId) {
		$this->recordId = $recordId;
	}
	
	private $fieldList;
	/**
	 * Sets the FieldList to be used for the config or to be filled if readConfig is true
	 */
	public function setFieldList($fieldList) {
		if(is_null($fieldList)) throw new DataFlowServiceException("field list cannot be null", DataFlowServiceException::INVALID_ARGUMENT);
		$this->fieldList = $fieldList;
	}
	
	private $fieldSelectorList;
	/**
	 * Sets a FieldSelectorList to be used to filter the fields and subfields effectively read
	 */
	public function setFieldSelectorList($fieldSelectorList) {
		$this->fieldSelectorList = $fieldSelectorList;		
	}
	
	private $readConfig = false;
	/**
	 * If true, then reads the config in the xml stream
	 */
	public function setReadConfig($readConfig) {
		$this->readConfig = $readConfig;
	}
	
	// SAX parser event handling	
		
	public function actOnStartElement($parser, $name, $attribs) {		
		$invalidToken = "start element '$name'";
		switch($name) {
			case "record":
				switch($this->state) {
					case self::RECORD_SM_INIT:
						$this->state = self::RECORD_SM_RECORD;
						break;
					default: $this->throwSyntaxError($invalidToken);
				}
				break;
			case "fields":
				switch($this->state) {
					case self::RECORD_SM_RECORD:
					case self::RECORD_SM_FIELDSCONFIG:
						$this->state = self::RECORD_SM_FIELDS;
						// switches to Fields SM						
						$smFields = $this->getWplXmlReaderDFA()->switchToFieldsSM();
						$smFields->setWigiiBag($this->wigiiBag);
						$smFields->setFieldList($this->fieldList);
						$smFields->setFieldSelectorList($this->fieldSelectorList);
						$smFields->setRecordId($this->recordId);
						$smFields->actOnStartElement($parser, $name, $attribs);
						break;
					default: $this->throwSyntaxError($invalidToken);
				}
				break;
			case "fieldsConfig":
				switch($this->state) {
					case self::RECORD_SM_RECORD:					
						$this->state = self::RECORD_SM_FIELDSCONFIG;
						throw new DataFlowServiceException("not implemented yet", DataFlowServiceException::UNSUPPORTED_OPERATION);
						break;
					default: $this->throwSyntaxError($invalidToken);
				}
				break;
			default: $this->throwSyntaxError($invalidToken);
		}
	}	
	public function actOnEndElement($parser, $name) {
		$invalidToken = "end element '$name'";
		switch($name) {
			case "record":
				switch($this->state) {
					case self::RECORD_SM_RECORD:
					case self::RECORD_SM_FIELDS:
					case self::RECORD_SM_FIELDSCONFIG:
						$this->state = self::RECORD_SM_FINAL;
						$this->reachFinalState();
						break;
					default: $this->throwSyntaxError($invalidToken);
				}
				break;
			default: $this->throwSyntaxError($invalidToken);
		}
	}	
	public function actOnCharData($parser, $data) {
		$invalidToken = "char data";
		$this->throwSyntaxError($invalidToken);
	}	
}
/**
 * Wigii Persistence Language Xml Reader Data Flow Activity
 * Fields State Machine
 * Created by CWE on 6 juin 2013
 *
 */
class WplXmlReaderDFAFieldsSM extends WplXmlReaderDFASM {
	private $state = self::FIELDS_SM_INIT;
	const FIELDS_SM_INIT = 0;
	const FIELDS_SM_FIELDS = 1;
	const FIELDS_SM_FIELD = 2;
	const FIELDS_SM_SUBFIELD = 3;
	const FIELDS_SM_SUBFIELDCONTENT = 4;
	const FIELDS_SM_FINAL = 5;
	
	private $currentField = null;
	private $currentSubfield = null;
	private $skipSubfieldContent = false;
	private $subFieldContent = null;
	private $subFieldHasContent = false;
	private $subFieldType = null;
	private $subFieldLang = null;
	
	// Object lifecycle
	public function reset() {
		$this->freeMemory();
		$this->state = self::FIELDS_SM_INIT;
		$this->hasFieldSelectorList = false;
		$this->allSubfields = false;
		$this->skipSubfieldContent = false;
		$this->subFieldHasContent = false;
	}
	public function freeMemory() {
		unset($this->wigiiBag);
		unset($this->recordId);
		unset($this->fieldList);
		unset($this->fieldSelectorList);
		unset($this->selectedLanguages);	
		unset($this->currentField);
		unset($this->currentSubfield);
		unset($this->subFieldContent);
		unset($this->subFieldType);
		unset($this->subFieldLang);
	}	
	protected function reachFinalState() {
		parent::reachFinalState();
		$this->getWplXmlReaderDFA()->recycleFieldsSM($this);
	}
		
	// Dependency injection
	
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
	
	// SM configuration
	
	private $wigiiBag;
	/**
	 * Sets the wigii bag in which the read fields values should be stored
	 */
	public function setWigiiBag($wigiiBag) {
		if(is_null($wigiiBag)) throw new DataFlowServiceException("wigii bag cannot be null", DataFlowServiceException::INVALID_ARGUMENT);
		$this->wigiiBag = $wigiiBag;
	}
	
	private $recordId;
	/**
	 * Sets the ID of the record to be filled in the Wigii Bag
	 */
	public function setRecordId($recordId) {
		$this->recordId = $recordId;
	}
	
	private $fieldList;
	/**
	 * Sets the FieldList describing the Fields stored in the Wigii Bag
	 */
	public function setFieldList($fieldList) {
		if(is_null($fieldList)) throw new DataFlowServiceException("field list cannot be null", DataFlowServiceException::INVALID_ARGUMENT);
		$this->fieldList = $fieldList;
	}
	
	private $fieldSelectorList;
	private $hasFieldSelectorList;
	private $allSubfields;
	private $selectedLanguages = null;
	/**
	 * Sets a FieldSelectorList to be used to filter the fields and subfields effectively read
	 * Checks also the selected languages validity
	 */
	public function setFieldSelectorList($fieldSelectorList) {		
		unset($this->selectedLanguages);
		if(isset($fieldSelectorList)) {
			// sets selected languages
			$this->selectedLanguages = $fieldSelectorList->getSelectedLanguages();
			if(isset($this->selectedLanguages)) {
				ArgValidator::assertArrayInclusion('selected languages are not valid installed languages',
					$this->selectedLanguages,
					$this->getTranslationService()->getVisibleLanguage());
			}			
			$this->hasFieldSelectorList = true;
		} else $this->hasFieldSelectorList = false;		
		$this->fieldSelectorList = $fieldSelectorList;
	}
	
	// SAX parser event handling	
		
	public function actOnStartElement($parser, $name, $attribs) {
		$invalidToken = "start element '$name'";
		switch($name) {
			case "fields":
				switch($this->state) {					
					case self::FIELDS_SM_INIT:
						$this->state = self::FIELDS_SM_FIELDS;
						break;
					default: $this->throwInvalidTokenSyntaxError($invalidToken);
				}
				break;
			case "field":
				switch($this->state) {	
					case self::FIELDS_SM_FIELDS:
						// gets field name
						if(is_null($attribs)) $this->throwSyntaxError("fieldname is missing");
						$fieldName = $attribs['name'];
						if(is_null($fieldName)) $this->throwSyntaxError("fieldname is missing");
						// gets the field in the field list
						$this->currentField = $this->fieldList->doesFieldExist($fieldName);
						if(is_null($this->currentField)) $this->throwSyntaxError("Field $fieldName is not defined in config");
						// checks if field is defined in the FieldSelectorList
						$this->allSubfields = $this->hasFieldSelectorList && $this->fieldSelectorList->containsFieldSelector($fieldName);
						// changes state						
						$this->state = self::FIELDS_SM_FIELD;
						break;				
					default: $this->throwInvalidTokenSyntaxError($invalidToken);
				}
				break;
			default:
				switch($this->state) {					
					case self::FIELDS_SM_FIELD:
						// validates subfield name in config
						$dataType = $this->currentField->getDataType();						
						$dbFieldParams = $dataType->getXml()->xpath($name);
						if(!$dbFieldParams) $this->throwSyntaxError("Subfield $name is not defined in config of datatype ".$dataType->getDataTypeName());						
						// skips subfield content if subfield is not selected
						$this->skipSubfieldContent = $this->hasFieldSelectorList && !$this->allSubfields && 
							!$this->fieldSelectorList->containsFieldSelector($this->currentField->getFieldName(), $name);
						// if subfield content should be read, then :
						// - extracts subfield type
						// - checks language for multiple language subfields
						if(!$this->skipSubfieldContent) {
							// extracts subfield type from config
							$dbFieldParams = $dbFieldParams[0];
							$this->subFieldType = (string)$dbFieldParams['type'];
							// if subfield is multilanguage, then we should have a lang attribute
							if(((string)$dbFieldParams['multiLanguage']) == '1') {
								// gets lang
								if(is_null($attribs)) $this->throwSyntaxError("lang is missing");
								$lang = $attribs['lang'];
								if(is_null($lang)) $this->throwSyntaxError("lang is missing");
								// if some selected language exists, then skips the content if lang is not selected
								if(isset($this->selectedLanguages)) {
									$this->subFieldLang = $this->selectedLanguages[$lang];
									$this->skipSubfieldContent = is_null($this->subFieldLang);
								}
								// else lang should be a valid language
								else {
									$selectedLang = $this->getTranslationService()->getVisibleLanguage($lang);
									if(is_null($selectedLang)) $this->throwSyntaxError("language '$lang' is not a valid installed language");
								}								
							}
						}	
						$this->currentSubfield = $name;
						$this->subFieldContent = '';
						$this->subFieldHasContent = false;
						// changes state
						$this->state = self::FIELDS_SM_SUBFIELD;
						break;
					default: $this->throwInvalidTokenSyntaxError($invalidToken);
				}				
		}
	}	
	public function actOnEndElement($parser, $name) {
		$invalidToken = "end element '$name'";
		switch($name) {
			case "fields":
				switch($this->state) {
					case self::FIELDS_SM_FIELDS:
						$this->state = self::FIELDS_SM_FINAL;
						$this->reachFinalState();
						break;					
					default: $this->throwInvalidTokenSyntaxError($invalidToken);
				}
				break;
			case "field":
				switch($this->state) {
					case self::FIELDS_SM_FIELD:
						$this->currentField = null;
						$this->state = self::FIELDS_SM_FIELDS;
						break;					
					default: $this->throwInvalidTokenSyntaxError($invalidToken);
				}
				break;
			default:
				switch($this->state) {		
					case self::FIELDS_SM_SUBFIELD:
					case self::FIELDS_SM_SUBFIELDCONTENT:
						// validates closing tag
						if($name != $this->currentSubfield) $this->throwSyntaxError("Found end element $name instead expected $this->currentSubfield");
						// stores subfield content
						if($this->subFieldHasContent) {							
							$this->fillWigiiBag($this->wigiiBag, $this->recordId, $this->currentField, 
								$this->currentSubfield, $this->subFieldContent, $this->subFieldType, $this->subFieldLang);
							$this->subFieldContent = null;
							$this->subFieldHasContent = false;
						}
						$this->state = self::FIELDS_SM_FIELD;
						break;			
					default: $this->throwInvalidTokenSyntaxError($invalidToken);
				}				
		}
	}	
	public function actOnCharData($parser, $data) {
		$invalidToken = "char data";
		switch($this->state) {		
			case self::FIELDS_SM_SUBFIELD:
			case self::FIELDS_SM_SUBFIELDCONTENT:
				if(!$this->skipSubfieldContent) {
					if(isset($data)) {
						$this->subFieldContent .= $data;
						$this->subFieldHasContent = true;
					}
				}
				$this->state = self::FIELDS_SM_SUBFIELDCONTENT;	
				break;		
			default: $this->throwInvalidTokenSyntaxError($invalidToken);
		}	
	}	
	
	
	// Implementation
	
	/**
	 * (extension point)
	 * Formats value and fills wigii bag
	 */
	protected function fillWigiiBag($wigiiBag, $recordId, $field, $subFieldName, $value, $subFieldType, $lang)
	{
		$fieldName = $field->getFieldName();
		$dataTypeName = $field->getDataType()->getDataTypeName();
		// if multiple-select, creates array
		if(strtolower($subFieldType) === "multiple-select" && isset($value))
		{
			$tempValues = explode('XAGU___XAGU', $value);
			$value = array_combine($tempValues, $tempValues);		
		}
		// if multilanguage, updates array of multilanguage values
		if(isset($lang))
		{
			$multiLangVal = $wigiiBag->getValue($recordId, $dataTypeName, $fieldName, $subFieldName);
			if(!isset($multiLangVal)) $multiLangVal = array();
			$multiLangVal[$lang] = $value;
			$value = $multiLangVal;
		}
		// stores value		
		$field->setValue($value, $recordId, $wigiiBag, $subFieldName);
	}
}