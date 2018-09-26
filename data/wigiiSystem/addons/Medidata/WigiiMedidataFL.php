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
 * Wigii interface with Medidata (https://www.medidata.ch/)
 * Created by CWE on 19 sept 2018
 */
class WigiiMedidataFL extends FuncExpVMAbstractFL
{
	// Dependency injection
	
	private $_debugLogger;
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("WigiiMedidataFL");
		}
		return $this->_debugLogger;
	}
	
	
	// Wigii Medidata General Invoice Request 4.5
	
	/**
	 * Generates a Medidata General Invoice Request 4.5 using a Wigii Company CustomerOrder element.<br/>
	 * FuncExp signature : <code>genMedidataInvoiceRequest45(customerOrder,options)</code><br/>
	 * Where arguments are :
	 * - Arg(0) customerOrder: Element of module CustomerOrders|ElementPDataFlowConnector. Given customer order for which to generate the Medidata General Invoice Request as XML.
	 * - Arg(1) options: WigiiBPLParameter. An optional bag of options to configure the generation process.
	 * @return SimpleXMLElement a SimpleXMLElement compatible with XML schema http://www.forum-datenaustausch.ch/invoice generalInvoiceRequest_450.xsd
	 */
	public function genMedidataInvoiceRequest45($args) {		
		$this->debugLogger()->logBeginOperation('genMedidataInvoiceRequest45');
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException('genMedidataInvoiceRequest45 takes at least one parameter which should evaluate to an Element of type Wigii Company CustomerOrders', FuncExpEvalException::INVALID_ARGUMENT);
		$customerOrder = $this->evaluateArg($args[0]);		
		// fetches element if needed
		if($customerOrder instanceof ElementPDataFlowConnector) $customerOrder = sel($this->getPrincipal(),$customerOrder,dfasl(dfas("NullDFA")));			
		if($customerOrder instanceof ElementP) $customerOrder = $customerOrder->getElement();
		if(!($customerOrder instanceof Element)) throw new FuncExpEvalException('genMedidataInvoiceRequest45 takes at least one parameter which should evaluate to an Element of type Wigii Company CustomerOrders', FuncExpEvalException::INVALID_ARGUMENT);
		// reads bag of options 
		if($nArgs>1) {
			$options = $this->evaluateArg($args[1]);
			if(!isset($options)) $options = wigiiBPLParam();
			elseif(!($options instanceof WigiiBPLParameter)) throw new FuncExpEvalException('options can only be an instance of WigiiBPLParameter',FuncExpEvalException::INVALID_ARGUMENT);
		}
		else $options = wigiiBPLParam();
		$this->initializeInvoice45Options($customerOrder, $options);
		
		// generates invoice request
		$returnValue = $this->getXmlDoc($options);		
		$this->createInvoice45Request($returnValue, $customerOrder, $options);
		// validates generated xml
		//$this->assertXmlIsValid($returnValue, $options->getValue('xmlSchema'));
		// returns as SimpleXmlElement
		$returnValue = simplexml_import_dom($returnValue);
		$this->debugLogger()->logEndOperation('genMedidataInvoiceRequest45');
		return $returnValue;
	}
	
	/**
	 * Initializes the bag of options to generate invoice request 4.5
	 * @param Element $customerOrder element of type CustomerOrders sourcing the invoice creation
	 * @param WigiiBPLParameter $options the ag of options configuring the generation process
	 * @return WigiiBPLParameter the bag of options initialized
	 */
	protected function initializeInvoice45Options($customerOrder,$options) {
		$options->setValue('xmlSchema','generalInvoiceRequest_450.xsd');
		$options->setValue('namespaceURI','http://www.forum-datenaustausch.ch/invoice');
		$options->setValue('namespacePrefix','invoice');
		return $options;
	}
	
	/**
	 * Creates an invoice request
	 * @param DOMDocument $xmlDoc XML document in which the invoice request will be created. 
	 * @param Element $customerOrder element of type CustomerOrders sourcing the invoice creation
	 * @param WigiiBPLParameter $options optional bag of options to configure the generation process
	 * @return DOMElement the created invoice request node
	 */
	protected function createInvoice45Request($xmlDoc, $customerOrder, $options) {
		$returnValue = $this->createXmlElement($xmlDoc, 'request', $options);
		$this->initializeXmlRootElement($returnValue, $options);
		$this->declareXmlNamespace($returnValue, 'xenc', 'http://www.w3.org/2001/04/xmlenc#');
		$this->declareXmlNamespace($returnValue, 'ds', 'http://www.w3.org/2000/09/xmldsig#');
		// invoice request attributes
		$returnValue->setAttribute('language','fr');
		$returnValue->setAttribute('modus','production');
		$returnValue->setAttribute('validation_status','0');
		// invoice processing
		$this->createInvoice45Processing($returnValue, $customerOrder, $options);
		// invoice payload
		$this->createInvoice45Payload($returnValue, $customerOrder, $options);
		return $returnValue;
	}
	
	/**
	 * Creates an invoice request processing node
	 * @param DOMElement $invoiceRequest current invoice request node
	 * @param Element $customerOrder element of type CustomerOrders sourcing the invoice creation
	 * @param WigiiBPLParameter $options optional bag of options to configure the generation process
	 * @return DOMElement the created invoice processing node
	 */
	protected function createInvoice45Processing($invoiceRequest,$customerOrder,$options) {
		$returnValue = $this->createXmlElement($invoiceRequest, 'processing', $options);
		// transport
		$xml = $this->createXmlElement($returnValue, 'transport', $options);
		$xml->setAttribute('from', 'customerOrder.GLN_LegalEntity');
		$xml->setAttribute('to', 'customerOrder.GLN_Insurance');
		// via Medidata
		$xml = $this->createXmlElement($xml, 'via', $options);
		$xml->setAttribute('via', '7601001304307');
		$xml->setAttribute('sequence_id', '1');
		return $returnValue;
	}
	
	/**
	 * Creates an invoice request payload node
	 * @param DOMElement $invoiceRequest current invoice request node
	 * @param Element $customerOrder element of type CustomerOrders sourcing the invoice creation
	 * @param WigiiBPLParameter $options optional bag of options to configure the generation process
	 * @return DOMElement the created invoice payload node
	 */
	protected function createInvoice45Payload($invoiceRequest,$customerOrder,$options) {
		$returnValue = $this->createXmlElement($invoiceRequest, 'payload', $options);
		$returnValue->setAttribute('type', 'invoice');
		$returnValue->setAttribute('copy', 0);
		$returnValue->setAttribute('storno', 0);
		// invoice
		$xml = $this->createXmlElement($returnValue, 'invoice', $options);		
		$xml->setAttribute('request_timestamp', strtotime($this->assertNotNull($customerOrder, 'orderDate')));
		$xml->setAttribute('request_date', $this->assertDateNotNull($customerOrder, 'orderDate'));
		$xml->setAttribute('request_id', $this->assertNotNull($customerOrder,'customerOrderNumber'));
		// body
		$this->createInvoice45Body($returnValue, $customerOrder, $options);
		return $returnValue;
	}
	
	/**
	 * Creates an invoice request body node
	 * @param DOMElement $invoicePayload current invoice payload node
	 * @param Element $customerOrder element of type CustomerOrders sourcing the invoice creation
	 * @param WigiiBPLParameter $options optional bag of options to configure the generation process
	 * @return DOMElement the created invoice body node
	 */
	protected function createInvoice45Body($invoicePayload,$customerOrder,$options) {
		$returnValue = $this->createXmlElement($invoicePayload, 'body', $options);
		$returnValue->setAttribute('role_title', 'Orthopédie');
		$returnValue->setAttribute('role', 'other');
		$returnValue->setAttribute('place', 'company');
		// prolog / generator
		$xml = $this->createXmlElement($returnValue, 'prolog', $options);
		$xml = $this->createXmlElement($xml, 'generator', $options);
		$xml->setAttribute('name', 'WigiiMedidataFL');
		$xml->setAttribute('copyright', 'Wigii.org 2018');
		$xml->setAttribute('version', '450');
		// remark
		$remark = substr(trim($this->evaluateFuncExp(fx('html2text',$customerOrder->getFieldValue('annexInfo')))),0,350);
		if(!empty($remark)) {
			$xml = $this->createXmlElement($returnValue, 'remark', $options);
			$xml->nodeValue = $remark;
		}
		// tiers payant
		$this->createInvoice45TiersPayant($returnValue, $customerOrder, $options);
		// esr QR
		$this->createInvoice45esrQR($returnValue, $customerOrder, $options);
		// ivg
		$this->createInvoice45ivg($returnValue, $customerOrder, $options);
		// treatment
		$this->createInvoice45Treatment($returnValue, $customerOrder, $options);
		// services
		$this->createInvoice45Services($returnValue, $customerOrder, $options);
		return $returnValue;
	}
	
	/**
	 * Creates an invoice request Tiers payant node
	 * @param DOMElement $invoiceBody current invoice body node
	 * @param Element $customerOrder element of type CustomerOrders sourcing the invoice creation
	 * @param WigiiBPLParameter $options optional bag of options to configure the generation process
	 * @return DOMElement the created invoice tiers payant node
	 */
	protected function createInvoice45TiersPayant($invoiceBody,$customerOrder,$options) {
		$returnValue = $this->createXmlElement($invoiceBody, 'tiers_payant', $options);
		$returnValue->setAttribute('payment_period', 'P60D');
		return $returnValue;
	}
	
	/**
	 * Creates an invoice request esrQR node
	 * @param DOMElement $invoiceBody current invoice body node
	 * @param Element $customerOrder element of type CustomerOrders sourcing the invoice creation
	 * @param WigiiBPLParameter $options optional bag of options to configure the generation process
	 * @return DOMElement the created invoice esr QR node
	 */
	protected function createInvoice45esrQR($invoiceBody,$customerOrder,$options) {
		$returnValue = $this->createXmlElement($invoiceBody, 'esrQR', $options);
		$returnValue->setAttribute('type', 'esrQR');
		return $returnValue;
	}
	
	/**
	 * Creates an invoice request ivg node
	 * @param DOMElement $invoiceBody current invoice body node
	 * @param Element $customerOrder element of type CustomerOrders sourcing the invoice creation
	 * @param WigiiBPLParameter $options optional bag of options to configure the generation process
	 * @return DOMElement the created invoice ivg node
	 */
	protected function createInvoice45ivg($invoiceBody,$customerOrder,$options) {
		$returnValue = $this->createXmlElement($invoiceBody, 'ivg', $options);
		$returnValue->setAttribute('case_id', 'customerOrder.case_id');
		$returnValue->setAttribute('case_date', 'customerOrder.case_date');
		$returnValue->setAttribute('ssn', 'customerOrder.ssn');
		$returnValue->setAttribute('nif', 'customerOrder.NIF_LegalEntity');
		return $returnValue;
	}
	
	/**
	 * Creates an invoice request treatement node
	 * @param DOMElement $invoiceBody current invoice body node
	 * @param Element $customerOrder element of type CustomerOrders sourcing the invoice creation
	 * @param WigiiBPLParameter $options optional bag of options to configure the generation process
	 * @return DOMElement the created invoice treatment node
	 */
	protected function createInvoice45Treatment($invoiceBody,$customerOrder,$options) {
		$returnValue = $this->createXmlElement($invoiceBody, 'treatment', $options);
		$returnValue->setAttribute('date_begin', $this->assertDateNotNull($customerOrder, 'orderDate'));
		$returnValue->setAttribute('date_end', $this->assertDateNotNull($customerOrder, 'orderDate'));
		$returnValue->setAttribute('canton', 'VD');
		$returnValue->setAttribute('reason', 'unknown');
		return $returnValue;
	}
	
	/**
	 * Creates an invoice request services node
	 * @param DOMElement $invoiceBody current invoice body node
	 * @param Element $customerOrder element of type CustomerOrders sourcing the invoice creation
	 * @param WigiiBPLParameter $options optional bag of options to configure the generation process
	 * @return DOMElement the created invoice services node
	 */
	protected function createInvoice45Services($invoiceBody,$customerOrder,$options) {
		$returnValue = $this->createXmlElement($invoiceBody, 'services', $options);
		return $returnValue;
	}
	
	// Tools
		
	/**
	 * Gets the XML document containing the given XML node or creates a new empty XML document
	 * @param DOMNode $xmlNode existing xml node for which to get the containing document. If null, then a new empty DOMDocument is created.
	 * If a bag of otions is given, then tries to set the document URI based on the namespaceURI option.
	 * @return DOMDocument  
	 */
	protected function getXmlDoc($xmlNode=null) {
		if(!isset($xmlNode) || ($xmlNode instanceof WigiiBPLParameter)) {
			$returnValue = new DOMDocument('1.0','UTF-8');
			$returnValue->xmlStandalone=false;
			if(isset($xmlNode)) {
				$returnValue->documentURI = $xmlNode->getValue('namespaceURI');
			}
		}
		else $returnValue = $xmlNode->ownerDocument;
		if(!isset($returnValue)) {
			if($xmlNode instanceof DOMDocument) $returnValue = $xmlNode;
			else throw new WigiiMedidataException("given xml node doesn't belong to any xml document", WigiiMedidataException::INVALID_ARGUMENT);
		}		
		return $returnValue;
	}
	
	/**
	 * Initializes XML root element with standard namespace declarations and other attributes based on given options
	 * @param DOMElement $xmlRootNode XML root element
	 * @param WigiiBPLParameter $options bag of options for the xml generation process
	 * @return DOMElement returns root element
	 */
	protected function initializeXmlRootElement($xmlRootNode,$options) {
		$xmlRootNode->setAttribute('xmlns', $options->getValue('namespaceURI'));
		$xmlRootNode->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance','xsi:schemaLocation', $options->getValue('namespaceURI').' '.$options->getValue('xmlSchema'));		
	}
	
	/**
	 * Declares a new XML namespace into XML document
	 * @param DOMNode $xmlNode one node in current XML document
	 * @param String $namespacePrefix XML namespace prefix that will be used in document
	 * @param String $namespaceURI namespace URI	 
	 */
	protected function declareXmlNamespace($xmlNode,$namespacePrefix,$namespaceURI) {
		if(!isset($xmlNode)) throw new WigiiMedidataException('xml node cannot be null',WigiiMedidataException::INVALID_ARGUMENT);
		$rootNode = $this->getXmlDoc($xmlNode)->documentElement;
		$rootNode->setAttributeNS('http://www.w3.org/2000/xmlns/','xmlns:'.$namespacePrefix, $namespaceURI);
	}
	
	/**
	 * Creates or gets an XML Element givent its parent node, and name.
	 * @param DOMNode $parentNode existing XML node in which to create or find given element
	 * @param String $name element tag name
	 * @param WigiiBPLParameter $options a bag of options containing at least the namespaceURI and namespacePrefix
	 * @return DOMElement created or found xml element
	 */
	protected function createXmlElement($parentNode,$name,$options) {
		if(!isset($options)) $options = wigiiBPLParam();
		return $this->createXmlElementByName($parentNode, $options->getValue('namespaceURI'), $options->getValue('namespacePrefix'), $name);
	}
	
	/**
	 * Creates or gets an XML Element givent its parent node, local name and namespace.
	 * @param DOMDocument|DOMElement $parentNode existing XML node in which to create or find given element
	 * @param String $namespaceURI xml namespace of the element
	 * @param String $namespacePrefix actual namespace used prefi 
	 * @param String $localName element tag name
	 * @return DOMElement created or found xml element
	 */
	protected function createXmlElementByName($parentNode,$namespaceURI,$namespacePrefix,$localName) {
		if(!isset($parentNode)) throw new FuncExpEvalException('parentNode cannot be null',FuncExpEvalException::INVALID_ARGUMENT);		
		// first looks for any existing node having this name
		$returnValue = $parentNode->getElementsByTagNameNS($namespaceURI, $localName);
		if($returnValue->length>0) $returnValue = $returnValue->item(0);
		// if not found, creates a new child node
		else {
			if($namespaceURI) $returnValue = $this->getXmlDoc($parentNode)->createElementNS($namespaceURI,($namespacePrefix?$namespacePrefix.':':'').$localName);
			else $returnValue = $this->getXmlDoc($parentNode)->createElement($localName);
			// attaches child node to parent
			$parentNode->appendChild($returnValue);
		}
		if($returnValue===false) throw new WigiiMedidataException("error creating xml element '$localName' attached to parent node ".$parentNode->getNodePath(), WigiiMedidataException::XML_VALIDATION_ERROR);
		return $returnValue;
	}
	
	// Validation
	
	/**
	 * Asserts given XML Document is valid against reference schema
	 * @param DOMDocument $xmlDoc prepared XML document
	 * @param String $schema XSD file name to be loaded fom Medidata addon repository
	 * @throws WigiiMedidataException in case of error
	 */
	protected function assertXmlIsValid($xmlDoc,$schema) {
		if(!isset($xmlDoc)) throw new WigiiMedidataException('xmlDoc cannot be null',WigiiMedidataException::INVALID_ARGUMENT);
		$schemaPath = ADDONS_PATH.'Medidata/'.$schema;
		if(!file_exists($schemaPath)) throw new WigiiMedidataException('xml schema '.$schema.' does not exist in Wigii Medidata addon folder', WigiiMedidataException::INVALID_ARGUMENT);
		libxml_use_internal_errors(true);
		$exception = null;
		// validates xml document against given schema
		if(!$xmlDoc->schemaValidate($schemaPath)) {
			$xmlErrors = libxml_get_errors();
			$exception = 'xml document is not valid against schema '.$schema;
			$fatalError=false;
			foreach($xmlErrors as $xmlError) {
				if($xmlError->code == 3083) continue; /* skips Warning XML_SCHEMAP_WARN_SKIP_SCHEMA */
				$exception .= "\n".'XML error '.$xmlError->code.' on line '.$xmlError->line.': '.$xmlError->message;
				$fatalError = true;
			}
			if($fatalError) $exception = new WigiiMedidataException($exception,WigiiMedidataException::XML_VALIDATION_ERROR);
			else $exception=null;
		}
		libxml_use_internal_errors(false);
		if(isset($exception)) throw $exception;
		else return true;
	}	
	
	/**
	 * Asserts that a field value is not null and returns it
	 * @param Element $element element from which to get the field value
	 * @param String $fieldName the field name
	 * @return Scalar element field value
	 * @throws WigiiMedidataException if assertion fails
	 */
	protected function assertNotNull($element,$fieldName) {
		$returnValue = $element->getFieldValue($fieldName);
		if(!is_numeric($returnValue) && empty($returnValue)) throw new WigiiMedidataException("Field '$fieldName' cannot be empty",WigiiMedidataException::XML_VALIDATION_ERROR);
		return $returnValue;
	}
	/**
	 * Asserts that a field value is a date and returns it
	 * @param Element $element element from which to get the field value
	 * @param String $fieldName the field name
	 * @param Boolean $allowNull optional flag allowing null dates or not. Default to true.
	 * @return Scalar element field value as a formatted date 
	 * @throws WigiiMedidataException if assertion fails
	 */
	protected function assertDate($element,$fieldName,$allowNull=true) {
		$returnValue = $element->getFieldValue($fieldName);		
		if(!empty($returnValue)) {
			$d = $m = $y = $h = $min = $s = null;
			if(Dates::fromString($returnValue, $d, $m, $y, $h, $min, $s)) {
				$returnValue = Dates::toString($d, $m, $y,'yyyy-mm-dd').'T'.Times::toString($h, $min, $s,'hh:mm:ss');
			}
			else throw new WigiiMedidataException("Field '$fieldName' is not a valid date",WigiiMedidataException::XML_VALIDATION_ERROR);
		}
		elseif(!$allowNull) throw new WigiiMedidataException("Field '$fieldName' cannot be empty",WigiiMedidataException::XML_VALIDATION_ERROR);
		return $returnValue;
	}
	/**
	 * Asserts that a field value is a non null date and returns it
	 * @param Element $element element from which to get the field value
	 * @param String $fieldName the field name
	 * @return Scalar element field value as a formatted date
	 * @throws WigiiMedidataException if assertion fails
	 */
	protected function assertDateNotNull($element,$fieldName) {return $this->assertDate($element, $fieldName,false);}
}