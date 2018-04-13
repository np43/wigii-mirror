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

	private $fxBuilder;
	/**
	 * Injects the FuncExpBuilder to be used by this library
	 * @param FuncExpBuilder $funcExpBuilder
	 */
	public function setFuncExpBuilder($funcExpBuilder)
	{
	    $this->fxBuilder = $funcExpBuilder;
	}
	/**
	 * Gets the injected FuncExpBuilder
	 * @return FuncExpBuilder
	 */
	protected function getFuncExpBuilder()
	{
	    // autowired
	    if(!isset($this->fxBuilder))
	    {
	        $this->fxBuilder = TechnicalServiceProvider::getFuncExpBuilder();
	    }
	    return $this->fxBuilder;
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
	 * Converts a Wigii Field from its XML representation to a cfgField StdClass instance
	 * See function 'cfgField' in FuncExpBuilder class. 
	 * @param SimpleXMLElement $fieldXml
	 * @return StdClass returns an StdClass instance with the properties {name, attributes, label, cfgAttributs}
	 */
	public function xml2cfgField($fieldXml) {
	    if(!isset($fieldXml)) throw new DataFlowServiceException('fieldXml cannot be null',DataFlowServiceException::INVALID_ARGUMENT);
	    // dumps attributes
	    $attributes = array();
	    foreach($fieldXml->attributes() as $k=>$v) {
	        $attributes[(string)$k] = (string)$v;
	    }
	    // dumps label
	    if($fieldXml->label) {
	        // keeps any inner html tags (but gets rid of surrounding label tag)
	        $label = substr($fieldXml->label->asXML(), strlen("label")+2, -(strlen("label")+3));	        
	    }
	    else {
	        $label = array();
	        foreach($this->getTranslationService()->getVisibleLanguage() as $lang=>$language) {
	            if($fieldXml->{'label_'.$lang}) {
	                $label[$lang] = substr($fieldXml->{'label_'.$lang}->asXML(), strlen("label_".$lang)+2, -(strlen("label_".$lang)+3));
	            }
	        }
	    }
	    return $this->getFuncExpBuilder()->cfgField($fieldXml->getName(),$attributes,$label);
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
			$returnValue = simplexml_load_string('<attribute>'.$cfgAttribut->value.'</attribute>');			
			if(is_array($cfgAttribut->label)) {
				foreach($cfgAttribut->label as $lang => $loc_label) {
					$loc_label = tryStr2Xml($loc_label);
					if($loc_label instanceof SimpleXMLElement) {
						$returnValue->{'label_'.$lang} = '';
						simplexml_appendChild($returnValue->{'label_'.$lang}, $loc_label);
					}
					else $returnValue->{'label_'.$lang} = $loc_label;
				}
			}
			elseif(is_string($cfgAttribut->label)){
				$returnValue->{'label'} = $cfgAttribut->label;
			}		
			if(is_array($cfgAttribut->attributes)) {
				foreach($cfgAttribut->attributes as $k => $v) {
					$returnValue->addAttribute($k, $v);
				}
			}					
		}
		return $returnValue;
	}
	
	/**
	 * Serializes a given stdClass or array into an xml string
	 * @param Principal current principal executing the process
	 * @param String $rootNodeName the root node name of the resulting xml
	 * @param StdClass|Array $stdClass the stdClass instance or array to serialize
	 * @return String
	 */
	public function stdClass2Xml($principal,$rootNodeName,$stdClass) {
		if(is_null($stdClass)) $stdClass = array();
		if(is_array($stdClass)) $stdClass = (object)$stdClass;
		return $this->getDataFlowService()->processDumpableObject($principal, array2df($stdClass), dfasl(
			dfas('WplXmlWriterDFA', 'setRootElementName', $rootNodeName, 'setEntityType', WplXmlWriterDFA::ENTITY_TYPE_STDCLASS),
			dfas('StringBufferDFA')
		));
	}
	
	/**
	 * Serializes a given WplObjectList into an xml string
	 * @param Principal $principal current principal executing the process
	 * @param String $rootNodeName the root node name of the resulting xml
	 * @param WplObjectList $wplObjectList the WplObjectList instance to serialize
	 * @return String
	 */
	public function wplObjectList2Xml($principal,$rootNodeName,$wplObjectList) {
		if(empty($rootNodeName)) throw new DataFlowServiceException('rootNodeName cannot be null',DataFlowServiceException::INVALID_ARGUMENT);
		$returnValue = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n";
		$returnValue .= '<'.$rootNodeName.'>'."\n";
		if(isset($wplObjectList) && !$wplObjectList->isEmpty()) {
			$nodeName = $wplObjectList->getWplObjectName();
			foreach($wplObjectList->getListIterator() as $obj) {
				// serializes object to xml
				if($obj instanceof stdClass || is_array($obj)) {
					$nodeXml = $this->stdClass2Xml($principal, $nodeName, $obj);
				}
				elseif($obj instanceof WplObjectList) {
					$nodeXml = $this->wplObjectList2Xml($principal, $nodeName, $obj);
				}
				elseif($obj instanceof Element || $obj instanceof ElementP || $obj instanceof Record) {
					$obj = $obj->getDbEntity();
					$nodeXml = $this->record2xml($principal, $obj->getFieldList(), $obj->getWigiiBag(), false, null, $obj->getId());
				}
				elseif(isset($obj)) throw new DataFlowServiceException((is_object($obj)?"objects of class '".get_class($obj)."' cannot be serialized to xml using wplObjectList2Xml method":'scalar data cannot be serialized to xml using wplObjectList2Xml method'), DataFlowServiceException::INVALID_ARGUMENT);
				else $nodeXml = '';
				// removes xml header if present
				if(!empty($nodeXml)) {
					$nodeXml = preg_replace('/\<\?xml.*?\?\>\s/', '',$nodeXml);
				}
				$returnValue .= $nodeXml;
			}
		}
		$returnValue .= '</'.$rootNodeName.'>';
		return $returnValue;
	}
	
	/**
	 * Converts some simple xml to a stdClass or a list of simple stdClasses. 
	 * Supports trees of simple objects.
	 * @param SimpleXMLElement $xml
	 * @return stdClass | WplObjectList | String  (can return a String in the case xml is a single leaf node)
	 * @example Consider a SimpleXMLElement parsed from the xml string
	 * <projectList>
	 * 		<project>
	 * 			<title>Project 1</title>
	 *	 		<startDate>2016.04.22</startDate>
	 * 			<endDate>2016.06.01</endDate>
	 * 		</project>
	 * 		<project>
	 * 			<title>Project 2</title>
	 *	 		<startDate>2016.06.02</startDate>
	 * 			<endDate></endDate>
	 * 		</project>
	 * </projectList>
	 * 
	 * Will return a WplObjectList instance equivalent to the expression :
	 * wplObjectList("project", 
	 * 		newObject("title","Project 1","startDate","2016.04.22","endDate","2016.06.01"),
	 * 		newObject("title","Project 2","startDate","2016.06.02","endDate",NULL),
	 * )
	 */
	public function xml2object($xml) {
		$returnValue = null;
		if(isset($xml)) {
			$nChildren = $xml->count();
			// goes down the tree
			if($nChildren > 0) {
				$isList=false;
				// if 2 and more children
				// 		if the first two has same name, then it is a list
				// 		else it is an object
				$i=1; $name1 = null; $child1 = null;
				foreach($xml->children() as $name => $child) {
					if($i==1) {
						$name1 = $name;
						$child1 = $this->xml2object($child);
					}
					elseif($i==2) {
						if($name == $name1) {
							$returnValue = $this->createWplObjectList($name);
							$isList=true;
							// adds first object
							$returnValue->addWplObject($child1);
							// adds second object
							$returnValue->addWplObject($this->xml2object($child));
						}
						else {
							$returnValue = array();
							$isList = false;
							// adds first attribute
							$returnValue[$name1] = $child1;
							// adds second attribute
							$returnValue[$name] = $this->xml2object($child);
						}
					}
					else {
						if($isList) $returnValue->addWplObject($this->xml2object($child));
						else {
							if(isset($returnValue[$name])) throw new DataFlowServiceException("cannot define two times attribute '$name' in object.", DataFlowServiceException::INVALID_ARGUMENT);
							$returnValue[$name] = $this->xml2object($child);
						}
					}
					$i++;
				}
				// if 1 child then
				// 		if child is object then it is a list
				// 		if child is leaf then it is an object
				if($nChildren < 2) {
					if(is_object($child1)) {
						$returnValue = $this->createWplObjectList($name1);
						$returnValue->addWplObject($child1);
					}
					else {
						$returnValue = array($name1 => $child1);
					}
				}
			}
			// if leaf, then returns string content
			else $returnValue = (string)$xml;
			
			// converts array to object
			if(is_array($returnValue)) $returnValue = (object)$returnValue;
		}		
		return $returnValue;
	}
	
	/**
	 * Creates a new empty instance of a WplObjectList
	 * @param String $wplObjectName optional naming of the object stored into the list
	 * @return WplObjectList
	 */
	public function createWplObjectList($wplObjectName=null) {
		//$this->debugLogger()->write("creating WplObjectList instance for ".$wplObjectName);
		return WplObjectList::createInstance($wplObjectName);
	}
}