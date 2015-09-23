<?php
/**
 *  This file is part of Wigii.
 *
 *  Wigii is free software: you can redistribute it and\/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Wigii is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Wigii.  If not, see <http:\//www.gnu.org/licenses/>.
 *
 *  @copyright  Copyright (c) 2012 Wigii 		 http://code.google.com/p/wigii/    http://www.wigii.ch
 *  @license    http://www.gnu.org/licenses/     GNU General Public License
 */

/**
 * Wigii Persistence Language toolbox
 * Created by CWE on 28 mai 2013
 */
class WplToolbox
{
	private $_debugLogger;

	// Dependency injection

	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("WplToolbox");
		}
		return $this->_debugLogger;
	}

	private $dflowS;
	public function setDataFlowService($dataFlowService)
	{
		$this->dflowS = $dataFlowService;
	}
	protected function getDataFlowService()
	{
		// autowired
		if(!isset($this->dflowS))
		{
			$this->dflowS = ServiceProvider::getDataFlowService();
		}
		return $this->dflowS;
	}

	private $fsFuncExpParser;
	public function setFieldSelectorFuncExpParser($fsFuncExpParser) {
		$this->fsFuncExpParser = $fsFuncExpParser;
	}
	protected function getFieldSelectorFuncExpParser() {
		// autowired
		if(!isset($this->fsFuncExpParser)) {
			$this->fsFuncExpParser = TechnicalServiceProvider::getFieldSelectorFuncExpParser();
		}
		return $this->fsFuncExpParser;
	}

	// Implementation

	/**
	 * Serializes a record into an xml string
	 * @param $principal authenticated user performing the operation
	 * @param $fieldList the FieldList of the Record
	 * @param $wigiiBag the Wigii Bag containing the data to be serialized
	 * @param $saveConfig if true, then saves into the xml string, the xml config of the fields
	 * @param $fieldSelectorList a FieldSelectorList to filter the fields to be persisted. Can also filter subfields.
	 * @param $recordId an optional id to be used to fetch the values in the wigii bag
	 */
	public function record2xml($principal, $fieldList, $wigiiBag, $saveConfig=false, $fieldSelectorList=null, $recordId=null) {
		$dfasl = $this->getRecord2xmlDFASL();
		// set parameters
		$dfas = $dfasl->getDataFlowActivitySelector(0);
		$dfas->setDataFlowActivityParameter('setEntityType', WplXmlWriterDFA::ENTITY_TYPE_RECORD);
		$dfas->setDataFlowActivityParameter('setWigiiBag', $wigiiBag);
		$dfas->setDataFlowActivityParameter('setWriteConfig', $saveConfig);
		$dfas->setDataFlowActivityParameter('setRecordId', $recordId);

		// runs the data flow process and returns the xml
		return $this->getDataFlowService()->processFieldList($principal, $fieldList, $dfasl, $fieldSelectorList);
	}
	private $record2xmlDFASL = null;
	protected function getRecord2xmlDFASL() {
		if(is_null($this->record2xmlDFASL)) {
			$this->record2xmlDFASL = DataFlowActivitySelectorListArrayImpl::createInstance();
			$this->record2xmlDFASL->addDataFlowActivitySelector('WplXmlWriterDFA');
			$this->record2xmlDFASL->addDataFlowActivitySelector('StringBufferDFA');
		}
		return $this->record2xmlDFASL;
	}


	/**
	 * Reads a serialized record from xml
	 * @param $principal authenticated user performing the operation
	 * @param $xml the xml string containing the record
	 * @param $fieldList the FieldList of the record.
	 * If the FieldList is empty and readConfig is true, then it is filled with the Fields config read from the xml
	 * If the FieldList is not empty and readConfig is true, then Fields definition are replaced by the ones read from the xml
	 * @param $wigiiBag the Wigii bag to be filled
	 * @param $readConfig if true, then reads the fields config from the xml, raises an error if not found
	 * @param $fieldSelectorList an optional FieldSelectorList to read only a selection of fields/subfields
	 * @param $recordId an optional id to be used to store the values in the wigii bag
	 */
	public function xml2record($principal, $xml, $fieldList, $wigiiBag, $readConfig=false, $fieldSelectorList=null, $recordId=null) {
		$dfasl = $this->getXml2RecordDFASL();
		// set parameters
		$dfas = $dfasl->getDataFlowActivitySelector(0);
		$dfas->setDataFlowActivityParameter('setEntityType', WplXmlReaderDFA::ENTITY_TYPE_RECORD);
		$dfas->setDataFlowActivityParameter('setWigiiBag', $wigiiBag);
		$dfas->setDataFlowActivityParameter('setFieldList', $fieldList);
		$dfas->setDataFlowActivityParameter('setFieldSelectorList', $fieldSelectorList);
		$dfas->setDataFlowActivityParameter('setReadConfig', $readConfig);
		$dfas->setDataFlowActivityParameter('setRecordId', $recordId);

		// runs the data flow process
		$this->getDataFlowService()->processString($principal, $xml, $dfasl);
	}
	private $xml2recordDFASL = null;
	protected function getXml2RecordDFASL() {
		if(is_null($this->xml2recordDFASL)) {
			$this->xml2recordDFASL = DataFlowActivitySelectorListArrayImpl::createInstance();
			$this->xml2recordDFASL->addDataFlowActivitySelector('WplXmlReaderDFA');
		}
		return $this->xml2recordDFASL;
	}

	/**
	 * Converts a cfgField StdClass instance to a SimpleXmlElement class
	 * See function 'cfgField' in FuncExpBuilder class.
	 * @param StdClass $cfgField an StdClass instance with properties {name, attributes, label, cfgAttributs}
	 * @return SimpleXMLElement
	 */
	public function cfgField2Xml($cfgField) {
		if(isset($cfgField)) $this->debugLogger()->write(json_encode($cfgField));
		if(is_string($cfgField->label)) $label = '<label>'.$cfgField->label.'</label>';
		else $label = '';
		$returnValue = new SimpleXMLElement('<'.$cfgField->name.'>'.$label.'</'.$cfgField->name.'>');
		if(is_array($cfgField->attributes)) {
			foreach($cfgField->attributes as $k => $v) {
				$returnValue->addAttribute($k, $v);
			}
		}
		if(is_array($cfgField->label)) {
			foreach($cfgField->label as $lang => $label) {
				$returnValue->{'label_'.$lang} = $label;
			}
		}
		if(isset($cfgField->cfgAttributs)) {
			if(is_array($cfgField->cfgAttributs)) {
				foreach($cfgField->cfgAttributs as $cfgAttribut) {
					simplexml_appendChild($returnValue, $this->cfgAttribut2Xml($cfgAttribut));
				}
			}
			else simplexml_appendChild($returnValue, $this->cfgAttribut2Xml($cfgField->cfgAttributs));
		}
		return $returnValue;
	}

	/**
	 * Converts a cfgAttribut StdClass instance to a SimpleXmlElement class
	 * See function 'cfgAttribut' in FuncExpBuilder class.
	 * @param StdClass $cfgAttribut an StdClass instance with properties {value, attributes, label} or {funcExp}
	 * @return SimpleXMLElement
	 */
	public function cfgAttribut2Xml($cfgAttribut) {
		// if attribute exp
		if(isset($cfgAttribut->funcExp)) {
			$returnValue = new SimpleXMLElement('<attributeExp/>');
			if(is_string($cfgAttribut->funcExp)) $funcExp = $cfgAttribut->funcExp;
			else $funcExp = $this->getFieldSelectorFuncExpParser()->funcExpToString($cfgAttribut->funcExp);
			$returnValue->addAttribute('funcExp', $funcExp);
			$this->debugLogger()->write("cfgAttribut->funcExp ='$funcExp'");
		}
		// else
		else {
			if(is_array($cfgAttribut->label)) {
				$label = '';
				foreach($cfgAttribut->label as $lang => $loc_label) {
					$label .= "<label_$lang>".$loc_label."</label_$lang>";
				}
			}
			elseif(is_string($cfgAttribut->label)){
				$label = '<label>'.$cfgAttribut->label.'</label>';
			} else $label = '';
			// attribute label can contain html and sometimes some non-escaped characters like & < or >
			// assumes xml/html is well formated, if not, fallback in a label construction which disables the html.
			$libxmlErrSettings = libxml_use_internal_errors(true);
			try {
				$returnValue = new SimpleXMLElement('<attribute>'.$cfgAttribut->value.$label.'</attribute>');
				$xmlErrors = libxml_get_errors();
				if(!empty($xmlErrors)) throw new Exception();
				libxml_use_internal_errors($libxmlErrSettings);
			}
			catch(Exception $xmlE) {
				libxml_clear_errors();
				libxml_use_internal_errors($libxmlErrSettings);
				if(is_string($cfgAttribut->label)){
					$label = '<label>'.$cfgAttribut->label.'</label>';
				} else $label = '';
				$returnValue = new SimpleXMLElement('<attribute>'.$cfgAttribut->value.$label.'</attribute>');
				if(is_array($cfgAttribut->label)) {
					foreach($cfgAttribut->label as $lang => $label) {
						$returnValue->{'label_'.$lang} = $label;
					}
				}
			}
			if(is_array($cfgAttribut->attributes)) {
				foreach($cfgAttribut->attributes as $k => $v) {
					$returnValue->addAttribute($k, $v);
				}
			}					
		}
		return $returnValue;
	}
}