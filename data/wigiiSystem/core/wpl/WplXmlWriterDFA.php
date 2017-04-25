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
 * Wigii Persistence Language Xml Writer data flow activity
 * Use cases are :
 * 1. Serialize to xml entity RECORD given as a stream of FieldsWithSelectedSubfields (+ a wigii bag as a parameter)
 * 2. Serialize to xml entity RECORD given as a single object Record
 * 3. Serialize to xml entity ELEMENT given as a single object Element
 * 4. Serialize to xml entity ELEMENT_LIST given as a stream of Elements
 * 5. Serialize to xml entity ELEMENT_LIST given as a single object ElementList
 * Created by CWE on 28 mai 2013
 * Updated by Medair in 2017 for maintenance purpose (see SVN log for details)
 */
class WplXmlWriterDFA implements DataFlowActivity
{

	// Object lifecycle

	public function reset() {
		$this->freeMemory();
		$this->entityType = self::ENTITY_TYPE_RECORD;
		$this->writeConfig = false;		
		$this->mustRecycleWigiiXmlWriterDFAOF = false;
		$this->hasFieldSelectorList = false;		
	}
	public function freeMemory() {
		unset($this->wigiiXmlWriterDFAOF);
		unset($this->wigiiBag);
		unset($this->recordId);
		unset($this->fieldList);
		unset($this->fieldSelectorList);
		unset($this->selectedLanguages);
	}

	// dependency injection

	private $wigiiXmlWriterDFAOF;
	private $mustRecycleWigiiXmlWriterDFAOF;

	/**
	 * Sets the WigiiXmlWriter data flow actitivity output filter
	 * that will be used to write xml in the pipe
	 * @param $wigiiXmlWriterDFAOF a WigiiXmlWriterDFAOF instance
	 */
	public function setWigiiXmlWriterDFAOF($wigiiXmlWriterDFAOF) {
		$this->wigiiXmlWriterDFAOF = $wigiiXmlWriterDFAOF;
	}
	/**
	 * Defaults to WigiiXmlWriterDFAOF
	 */
	protected function getWigiiXmlWriterDFAOF($dataFlowContext) {
		if(!isset($this->wigiiXmlWriterDFAOF)) {
			$this->wigiiXmlWriterDFAOF = $dataFlowContext->getDataFlowService()->getWigiiXmlWriterDFAOF($dataFlowContext, $this);
			$this->mustRecycleWigiiXmlWriterDFAOF = true;
		}
		return $this->wigiiXmlWriterDFAOF;
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
	
	
	// configuration

	const ENTITY_TYPE_RECORD = 0;
	const ENTITY_TYPE_ELEMENT = 1;
	const ENTITY_TYPE_ELEMENT_LIST = 2;
	const ENTITY_TYPE_STDCLASS = 3;
	private $entityType = self::ENTITY_TYPE_RECORD;

	public function setEntityType($entityType) {
		switch($entityType) {
			case self::ENTITY_TYPE_RECORD:
			case self::ENTITY_TYPE_ELEMENT:
			case self::ENTITY_TYPE_ELEMENT_LIST:
			case self::ENTITY_TYPE_STDCLASS:
				$this->entityType = $entityType;
				break;
			default: throw new DataFlowServiceException("invalid entity type. Should be one of WplXmlWriterDFA::ENTITY_TYPE_RECORD, WplXmlWriterDFA::ENTITY_TYPE_ELEMENT, WplXmlWriterDFA::ENTITY_TYPE_ELEMENT_LIST or WplXmlWriterDFA::ENTITY_TYPE_STDCLASS", DataFlowServiceException::INVALID_ARGUMENT);
		}
	}

	private $wigiiBag = null;
	/**
	 * Sets the wigii bag to be used to retrieve data
	 */
	public function setWigiiBag($wigiiBag) {
		$this->wigiiBag = $wigiiBag;
	}

	private $recordId;
	/**
	 * Sets the ID of the record to be read in the Wigii Bag
	 */
	public function setRecordId($recordId) {
		$this->recordId = $recordId;
	}

	private $fieldList = null;
	/**
	 * Sets the FieldList to be used for the config
	 */
	public function setFieldList($fieldList) {
		$this->fieldList = $fieldList;
	}
			
	private $fieldSelectorList = null;	
	private $hasFieldSelectorList;	
	private $selectedLanguages = null;
	/**
	 * Sets the FieldSelectorList used to restrict the fields and subfields which are effectively written
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
	
	private $writeConfig = false;
	/**
	 * If true, then writes the config in the xml stream
	 */
	public function setWriteConfig($writeConfig) {
		$this->writeConfig = $writeConfig;
	}
		
	private $rootElementName;
	/**
	 * Sets the name of the xml root element in case of serializing a stdClass or array.
	 * @param String $rootElementName
	 */
	public function setRootElementName($rootElementName) {
		$this->rootElementName = $rootElementName;
	}

	// stream data event handling

	public function startOfStream($dataFlowContext) {
		switch($this->entityType) {
			case self::ENTITY_TYPE_RECORD:
				$wigiiXmlWriter = $this->getWigiiXmlWriterDFAOF($dataFlowContext);					
				$this->writeStartOfRecord($wigiiXmlWriter);
				if($this->writeConfig) {
					$this->writeFieldsConfig($wigiiXmlWriter, $this->fieldList);
				}
				$this->writeStartOfFields($wigiiXmlWriter);
				break;
			case self::ENTITY_TYPE_STDCLASS: break;
			default: 
				ServiceException::throwNotImplemented();
		}
	}
	public function processDataChunk($data, $dataFlowContext) {
		switch($this->entityType) {
			case self::ENTITY_TYPE_RECORD:
				$this->writeField($this->getWigiiXmlWriterDFAOF($dataFlowContext),
					$data, $this->wigiiBag, $this->recordId);
				break;
			case self::ENTITY_TYPE_STDCLASS:
				$wigiiXmlWriter = $this->getWigiiXmlWriterDFAOF($dataFlowContext);
				$this->writeStdClass($wigiiXmlWriter, ($this->rootElementName?$this->rootElementName:'stdClass'), $data);
				break;
			default: 
				ServiceException::throwNotImplemented();
		}
	}
	public function endOfStream($dataFlowContext) {
		switch($this->entityType) {
			case self::ENTITY_TYPE_RECORD:
				$wigiiXmlWriter = $this->getWigiiXmlWriterDFAOF($dataFlowContext);
				$this->writeEndOfFields($wigiiXmlWriter);
				$this->writeEndOfRecord($wigiiXmlWriter);
				break;
			case self::ENTITY_TYPE_STDCLASS: break;
			default: 
				ServiceException::throwNotImplemented();
		}
		
		if($this->mustRecycleWigiiXmlWriterDFAOF) {
			$wigiiXmlWriter = $this->getWigiiXmlWriterDFAOF($dataFlowContext);
			$dataFlowContext->getDataFlowService()->recycleWigiiXmlWriterDFAOF($wigiiXmlWriter, $dataFlowContext);
		}
	}


	// single data event handling

	public function processWholeData($data, $dataFlowContext) {
		switch($this->entityType) {
			case self::ENTITY_TYPE_RECORD:
				$wigiiXmlWriter = $this->getWigiiXmlWriterDFAOF($dataFlowContext);
				if($data instanceof DbEntity) $data = $data->getDbEntity();	
				$this->writeRecord($wigiiXmlWriter, $data, $this->writeConfig, $this->fieldSelectorList);
				break;
			case self::ENTITY_TYPE_STDCLASS:
				$wigiiXmlWriter = $this->getWigiiXmlWriterDFAOF($dataFlowContext);
				$this->writeStdClass($wigiiXmlWriter, ($this->rootElementName?$this->rootElementName:'stdClass'), $data);
				break;
			default: 
				ServiceException::throwNotImplemented();
		}
		
		if($this->mustRecycleWigiiXmlWriterDFAOF) {
			$wigiiXmlWriter = $this->getWigiiXmlWriterDFAOF($dataFlowContext);
			$dataFlowContext->getDataFlowService()->recycleWigiiXmlWriterDFAOF($wigiiXmlWriter, $dataFlowContext);
		}
	}

	// functional implementation
	
	protected function writeRecord($wigiiXmlWriter, $record, $writeConfig, $fieldSelectorList=null) {
		if(isset($record)) {
			$fieldList = $record->getFieldList();
			$this->writeStartOfRecord($wigiiXmlWriter);
			if($writeConfig) $this->writeFieldsConfig($wigiiXmlWriter, $fieldList);			
			$this->writeFields($wigiiXmlWriter, 
				FieldWithSelectedSubfieldsListArrayImpl::createInstance($fieldList, $fieldSelectorList), 
				$record->getWigiiBag(), $record->getId());
			$this->writeEndOfRecord($wigiiXmlWriter);
		}
	}
	
	protected function writeStartOfRecord($wigiiXmlWriter) {
		$wigiiXmlWriter->writeStartElement('record');
	}
	
	protected function writeFieldsConfig($wigiiXmlWriter, $fieldList) {
		ServiceException::throwNotImplemented();
	}
	
	protected function writeFields($wigiiXmlWriter, $fieldWithSelectedSubfieldsList, $wigiiBag, $recordId) {
		if(is_null($fieldWithSelectedSubfieldsList)) throw new DataFlowServiceException("fieldWithSelectedSubfieldsList cannot be null", DataFlowServiceException::INVALID_ARGUMENT);
		$this->writeStartOfFields($wigiiXmlWriter);
		foreach($fieldWithSelectedSubfieldsList->getListIterator() as $field) {
			$this->writeField($wigiiXmlWriter, $field, $wigiiBag, $recordId);
		}
		$this->writeEndOfFields($wigiiXmlWriter);
	}
	
	protected function writeStartOfFields($wigiiXmlWriter) {
		$wigiiXmlWriter->writeStartElement('fields');
	}		
	
	/**
	 * Writes a field to XML
	 * @param $wigiiXmlWriter the open WigiiXmlWriterDFAOF to use
	 * @param $fieldWithSelectedSubfields an instance of model FieldWithSelectedSubfields wrapping the field to write
	 * @param $wigiiBag the wigii bag where is stored the data
	 * @param $recordId the record ID to fetch the data in the wigii bag
	 */
	protected function writeField($wigiiXmlWriter, $fieldWithSelectedSubfields, $wigiiBag, $recordId) {
		if(is_null($fieldWithSelectedSubfields)) throw new DataFlowServiceException("fieldWithSelectedSubfields cannot be null", DataFlowServiceException::INVALID_ARGUMENT);
		if(is_null($wigiiBag)) throw new DataFlowServiceException("wigii bag cannot be null", DataFlowServiceException::INVALID_ARGUMENT);
		$field = $fieldWithSelectedSubfields->getField();
		$dt = $field->getDataType();
		if(!$dt) return;		
		$x = $dt->getXml();
		
		$wigiiXmlWriter->writeStartElement('field', 'name', $field->getFieldName());
		foreach($fieldWithSelectedSubfields->getSelectedSubfieldsIterator() as $subFieldName) {
			$dbFieldParams = $x->xpath($subFieldName);
			if(!$dbFieldParams) throw new DataFlowServiceException("invalid subfield name '$subFieldName' for field ".$field->getFieldName(), DataFlowServiceException::INVALID_ARGUMENT);
			$dbFieldParams = $dbFieldParams[0];
			$subFieldType = (string)$dbFieldParams['type'];
			$sqlType = (string)$dbFieldParams['sqlType'];
			$multilanguage = (((string)$dbFieldParams['multiLanguage']) == '1');
			
			$value = $field->getValue($recordId, $wigiiBag, $subFieldName);
			
			if($multilanguage) {
				if(!isset($this->selectedLanguages)) $this->selectedLanguages = $this->getTranslationService()->getVisibleLanguage();
				$arrVal = is_array($value);
				foreach($this->selectedLanguages as $lang => $language) {
					if($arrVal) $val = $value[$lang];
					else $val = $value;
					$this->writeSubField($wigiiXmlWriter, $field, $subFieldName, $val, $subFieldType, $sqlType, $lang);
				}
			}
			else {
				$this->writeSubField($wigiiXmlWriter, $field, $subFieldName, $value, $subFieldType, $sqlType);
			}
		}
		$wigiiXmlWriter->writeEndElement();		
	}
	
	/**
	 * Writes a subfield to XML
	 * @param $wigiiXmlWriter the open WigiiXmlWriterDFAOF to use
	 * @param $field the Field containing this subfield
	 * @param $subFieldName the subfield name
	 * @param $value the subfield value to be written in XML
	 * @param $subFieldType the subfield type (see DataType->xml->xpath(subfieldname))
	 * @param $sqlType the subfield sql type (see DataType->xml->xpath(subfieldname))
	 * @param $lang if set, the language attribute value
	 */
	protected function writeSubField($wigiiXmlWriter, $field, $subFieldName, $value, $subFieldType, $sqlType, $lang=null) {
		$subFieldType = strtolower($subFieldType);
		// if multiple-select, implodes array		
		if($subFieldType === "multiple-select") {
			if(is_array($value)) $value = implode('XAGU___XAGU', $value);	
		}
		// writes xml
		$wigiiXmlWriter->writeStartElement($subFieldName);
		if(isset($lang)) {
			$wigiiXmlWriter->writeAttribute('lang', $lang);
		}
		if(isset($value)) {
			switch($sqlType) {				
				case "blob":
				case "longblob":
					// writes cdata
					$wigiiXmlWriter->writeCData($value);
					break;
				default: 
					// writes text	
					$wigiiXmlWriter->writeText($value);
			}
		}
		$wigiiXmlWriter->writeEndElement();
	}	
	
	protected function writeEndOfFields($wigiiXmlWriter) {
		$wigiiXmlWriter->writeEndElement();
	}
	
	protected function writeEndOfRecord($wigiiXmlWriter) {
		$wigiiXmlWriter->writeEndElement();
	}
	/**
	 * Writes a StdClass instance or an Array to XML. 
	 * @param WigiiXmlWriterDFAOF $wigiiXmlWriter the open WigiiXmlWriterDFAOF to use
	 * @param String $rootNodeName the name of the root xml element containing the data of the stdClass or array
	 * @param StdClass|Array $stdClass the stdClass instance or array to serialize to XML
	 */
	protected function writeStdClass($wigiiXmlWriter,$rootNodeName,$stdClass) {
		$wigiiXmlWriter->writeStartElement($rootNodeName);
		if(!empty($stdClass)) {
			foreach($stdClass as $k=>$v) {
				if(is_array($v) || $v instanceof StdClass) $this->writeStdClass($wigiiXmlWriter, $k, $v);
				else $wigiiXmlWriter->writeTextElement($k,$v);
			}
		}
		$wigiiXmlWriter->writeEndElement();
	}
}