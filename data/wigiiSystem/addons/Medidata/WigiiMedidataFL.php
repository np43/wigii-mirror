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

	private $trm;
	public function setTrm($trm) {
		$this->trm = $trm;
	}
	protected function getTrm() {
		if(!isset($this->trm)) {
			$this->trm = TemplateRecordManager::createInstance();
		}
		return $this->trm;
	}
	
	/**
	 * Gets a table with the medical cases attached to a customer<br/>
	 * FuncExp signature : <code>getCustomerMediCaseTable(customerNumber)</code><br/>
	 * Where arguments are :
	 * - Arg(0) customerNumber: String|LogExp. Contact number of type Customer for which to get the medical cases
	 * @return Array of StdClass instances of the form {contactNumber,caseNumber,caseLaw,caseDate,caseTariff}
	 */
	public function getCustomerMediCaseTable($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException('getCustomerMediCaseTable takes at least one parameter which is the customer number', FuncExpEvalException::INVALID_ARGUMENT);
		$customerNumber = $this->evaluateArg($args[0]);
		if(!($customerNumber instanceof LogExp)) $customerNumber = lxEq(fs('contactNumber'),$customerNumber);
		// loads the customers and extracts the medical cases
		return sel($this->getPrincipal(),elementPList(lxInGR($this->evaluateFuncExp(fx('companyDataConfig','customerGroupLx'))),lf(null,$customerNumber)),dfasl(
			dfas("ElementMatrixDFA",
				"setColumns",array('caseNumber_','caseLaw_','caseDate_','caseTariff_'),
				"setFromRow","1",
				"setCalculatedColumns",array('contactNumber'=>fs('contactNumber'))
			),
			dfas("CallbackDFA","setProcessDataChunkCallback",function($data,$callbackDFA){
				// keeps only rows with a case number
				if(oVal($data->{'caseNumber_'})) {
					$callbackDFA->writeResultToOutput((object)array(
						'contactNumber'=>$data->contactNumber,
						'caseNumber'=>oVal($data->{'caseNumber_'}),
						'caseLaw'=>oVal($data->{'caseLaw_'}),
						'caseDate'=>oVal($data->{'caseDate_'}),
						'caseTariff'=>oVal($data->{'caseTariff_'}),
					));
				}
			}),
			dfas("ArrayBufferDFA")
		));
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
		$this->assertXmlIsValid($returnValue, $options->getValue('xmlSchema'));
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
		// loads attached legal entity if not defined
		if(is_null($options->getValue('legalEntity'))) $options->setValue('legalEntity', $this->evaluateFuncExp(fx('getLegalEntityAttr',$customerOrder->getFieldValue('legalEntity'),fs_e('this'))));
		// loads attached customer if not defined
		if(is_null($options->getValue('customer'))) $options->setValue('customer', $this->evaluateFuncExp(fx('getContactAttr',$customerOrder->getFieldValue('customer'),fs_e('this'))));
		// loads attached invoice to if not defined
		if(is_null($options->getValue('invoiceTo'))) {
			if($customerOrder->getFieldValue('customer') == $customerOrder->getFieldValue('invoiceTo')) $options->setValue('invoiceTo',$options->getValue('customer'));
			else $options->setValue('invoiceTo', $this->evaluateFuncExp(fx('getContactAttr',$customerOrder->getFieldValue('invoiceTo'),fs_e('this'))));
		}		
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
		/* CWE 2019.02.19: not yet authorized to be put into production */$returnValue->setAttribute('modus','test');
		//$returnValue->setAttribute('modus','production');
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
		$legalEntity = $options->getValue('legalEntity');
		$insurance = $options->getValue('invoiceTo');
		$returnValue = $this->createXmlElement($invoiceRequest, 'processing', $options);
		// transport
		$xml = $this->createXmlElement($returnValue, 'transport', $options);
		$xml->setAttribute('from', $this->assertNoSepNotNull($legalEntity,'noGLN'));
		$xml->setAttribute('to', $this->assertNoSepNotNull($insurance,'noGLN'));
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
		$xml->setAttribute('copyright', 'Wigii.org 2019');
		$xml->setAttribute('version', '450');
		// remark
		$remark = substr(trim($this->evaluateFuncExp(fx('html2text',$customerOrder->getFieldValue('annexInfo')))),0,350);
		if(!empty($remark)) $xml = $this->createXmlElement($returnValue, 'remark', $options, $remark);
		// tiers payant
		$this->createInvoice45TiersPayant($returnValue, $customerOrder, $options);
		// esr QR
		$this->createInvoice45esrQR($returnValue, $customerOrder, $options);
		// case management
		switch($customerOrder->getFieldValue('caseLaw')){
			case 'LAI':
				// ivg
				$this->createInvoice45ivg($returnValue, $customerOrder, $options);
				break;
			case 'LAA':
				// uvg
				$this->createInvoice45uvg($returnValue, $customerOrder, $options);
				break;
			// default node is not created.
		}
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
		$legalEntity = $options->getValue('legalEntity');
		$insurance = $options->getValue('invoiceTo');
		$patient = $options->getValue('customer');
		$returnValue = $this->createXmlElement($invoiceBody, 'tiers_payant', $options);
		$returnValue->setAttribute('payment_period', 'P60D');
		// biller
		$xml = $this->createXmlElement($returnValue, 'biller', $options);
		$xml->setAttribute('ean_party', $this->assertNoSepNotNull($legalEntity,'noGLN'));
		$xml->setAttribute('zsr', $this->assertNoSepNotNull($legalEntity,'noRCC'));
		$xml->setAttribute('specialty', 'Orthopédie');
		$xml->setAttribute('uid_number', $this->assertNoSepNotNull($legalEntity, 'IDE'));
		$xml = $this->createXmlElement($xml, 'company', $options);
		$this->createXmlElement($xml, 'companyname', $options, $this->assertNotNull($legalEntity, 'entityName'));
		$xml = $this->createXmlElement($xml, 'postal', $options);
		$this->createXmlElement($xml, 'street', $options, $this->assertNotNull($legalEntity, 'entityAddress','street'));
		$this->createXmlElement($xml, 'zip', $options, $this->assertNotNull($legalEntity, 'entityAddress','zip_code'));
		$this->createXmlElement($xml, 'city', $options, $this->assertNotNull($legalEntity, 'entityAddress','city'));
		// debitor
		$xml = $this->createXmlElement($returnValue, 'debitor', $options);
		$xml->setAttribute('ean_party', $this->assertNoSepNotNull($insurance,'noGLN'));
		$xml = $this->createXmlElement($xml, 'company', $options);
		$this->createXmlElement($xml, 'companyname', $options, $this->assertNotNull($insurance, 'company'));
		$xml = $this->createXmlElement($xml, 'postal', $options);
		$this->createXmlElement($xml, 'street', $options, $this->assertNotNull($insurance, 'address','street'));
		$this->createXmlElement($xml, 'zip', $options, $this->assertNotNull($insurance, 'address','zip_code'));
		$this->createXmlElement($xml, 'city', $options, $this->assertNotNull($insurance, 'address','city'));
		// provider
		$xml = $this->createXmlElement($returnValue, 'provider', $options);
		$xml->setAttribute('ean_party', $this->assertNoSepNotNull($legalEntity,'noGLN'));
		$xml->setAttribute('zsr', $this->assertNoSepNotNull($legalEntity,'noRCC'));
		$xml->setAttribute('specialty', 'Orthopédie');
		$xml = $this->createXmlElement($xml, 'company', $options);
		$this->createXmlElement($xml, 'companyname', $options, $this->assertNotNull($legalEntity, 'entityName'));
		$xml = $this->createXmlElement($xml, 'postal', $options);
		$this->createXmlElement($xml, 'street', $options, $this->assertNotNull($legalEntity, 'entityAddress','street'));
		$this->createXmlElement($xml, 'zip', $options, $this->assertNotNull($legalEntity, 'entityAddress','zip_code'));
		$this->createXmlElement($xml, 'city', $options, $this->assertNotNull($legalEntity, 'entityAddress','city'));
		// insurance
		$xml = $this->createXmlElement($returnValue, 'insurance', $options);
		$xml->setAttribute('ean_party', $this->assertNoSepNotNull($insurance,'noGLN'));
		$xml = $this->createXmlElement($xml, 'company', $options);
		$this->createXmlElement($xml, 'companyname', $options, $this->assertNotNull($insurance, 'company'));
		$xml = $this->createXmlElement($xml, 'postal', $options);
		$this->createXmlElement($xml, 'street', $options, $this->assertNotNull($insurance, 'address','street'));
		$this->createXmlElement($xml, 'zip', $options, $this->assertNotNull($insurance, 'address','zip_code'));
		$this->createXmlElement($xml, 'city', $options, $this->assertNotNull($insurance, 'address','city'));
		// patient
		$xml = $this->createXmlElement($returnValue, 'patient', $options);
		$xml->setAttribute('gender', ($this->assertNotNull($patient, 'title')=='mr'?'male':'female'));
		$xml->setAttribute('birthdate', $this->assertDateNotNull($patient, 'dateOfBirth'));
		$xml->setAttribute('ssn', $this->assertNoSepNotNull($patient, 'noAVS'));
		$xml = $this->createXmlElement($xml, 'person', $options);
		$xml->setAttribute('salutation', $this->formatValue($patient, 'title'));
		$this->createXmlElement($xml, 'familyname', $options, $this->assertNotNull($patient, 'last_name'));
		$this->createXmlElement($xml, 'givenname', $options, $this->assertNotNull($patient, 'first_name'));
		$xml = $this->createXmlElement($xml, 'postal', $options);
		$this->createXmlElement($xml, 'street', $options, $this->assertNotNull($patient, 'address','street'));
		$this->createXmlElement($xml, 'zip', $options, $this->assertNotNull($patient, 'address','zip_code'));
		$this->createXmlElement($xml, 'city', $options, $this->assertNotNull($patient, 'address','city'));
		// guarantor
		$xml = $this->createXmlElement($returnValue, 'guarantor', $options);
		$xml = $this->createXmlElement($xml, 'person', $options);
		// takes tutor as guarantor if defined
		if(!empty($patient->getFieldValue('tutor_last_name'))) {
			$this->createXmlElement($xml, 'familyname', $options, $this->assertNotNull($patient, 'tutor_last_name'));
			$this->createXmlElement($xml, 'givenname', $options, $this->assertNotNull($patient, 'tutor_first_name'));
			$xml = $this->createXmlElement($xml, 'postal', $options);
			$this->createXmlElement($xml, 'street', $options, $this->assertNotNull($patient, 'tutor_address','street'));
			$this->createXmlElement($xml, 'zip', $options, $this->assertNotNull($patient, 'tutor_address','zip_code'));
			$this->createXmlElement($xml, 'city', $options, $this->assertNotNull($patient, 'tutor_address','city'));
		}
		// else patient is guarantor
		else {
			$xml->setAttribute('salutation', $this->formatValue($patient, 'title'));
			$this->createXmlElement($xml, 'familyname', $options, $this->assertNotNull($patient, 'last_name'));
			$this->createXmlElement($xml, 'givenname', $options, $this->assertNotNull($patient, 'first_name'));
			$xml = $this->createXmlElement($xml, 'postal', $options);
			$this->createXmlElement($xml, 'street', $options, $this->assertNotNull($patient, 'address','street'));
			$this->createXmlElement($xml, 'zip', $options, $this->assertNotNull($patient, 'address','zip_code'));
			$this->createXmlElement($xml, 'city', $options, $this->assertNotNull($patient, 'address','city'));
		}
		// balance
		$this->createInvoice45Balance($returnValue, $customerOrder, $options);
		return $returnValue;
	}
	
	/**
	 * Creates an invoice request Balance node
	 * @param DOMElement $invoiceTiersType current invoice Tiers payant or Tiers garant node
	 * @param Element $customerOrder element of type CustomerOrders sourcing the invoice creation
	 * @param WigiiBPLParameter $options optional bag of options to configure the generation process
	 * @return DOMElement the created invoice balance node
	 */
	protected function createInvoice45Balance($invoiceTiersType,$customerOrder,$options) {
		$legalEntity = $options->getValue('legalEntity');
		$returnValue = $this->createXmlElement($invoiceTiersType, 'balance', $options);		
		// vat
		$vat = $this->createXmlElement($returnValue, 'vat', $options);
		$vat->setAttribute('vat_number', $this->assertNoSepNotNull($legalEntity, 'IDE'));
		$vat->setAttribute('vat', $this->assertNumericNotNull($customerOrder, 'vatAmount'));
		// vat summary
		$vatSummary = $this->assertNotNull($customerOrder, 'vatSummary');
		$vatSummary = json_decode(stripslashes($vatSummary));
		if(!$vatSummary) throw new WigiiMedidataException('vatSummary is not valid json. '.json_last_error_msg(),WigiiMedidataException::XML_VALIDATION_ERROR);
		// creates vat entries per rate
		$ttcAmount = 0.0;
		foreach($vatSummary as $vatValue=>$vatObj) {
			$xml = $this->appendXmlElement($vat, 'vat_rate', $options);
			$xml->setAttribute('vat_rate', $vatValue);
			$orderTTCAmount = $this->assertNumericNotNull($vatObj, 'orderTTCAmount');
			$ttcAmount += $orderTTCAmount;
			$xml->setAttribute('amount', $orderTTCAmount);
			$xml->setAttribute('vat', $this->assertNumericNotNull($vatObj, 'vatAmount'));
		}		
		// sets balance attributes
		$returnValue->setAttribute('currency', 'CHF');
		$returnValue->setAttribute('amount', number_format($ttcAmount, 2, '.', ''));
		$returnValue->setAttribute('amount_due', $this->assertNumericNotNull($customerOrder, 'dueAmount'));
		$returnValue->setAttribute('amount_obligations', number_format($ttcAmount, 2, '.', ''));
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
		$legalEntity = $options->getValue('legalEntity');
		$returnValue = $this->createXmlElement($invoiceBody, 'esrQR', $options);
		$returnValue->setAttribute('type', 'esrQR');
		$returnValue->setAttribute('iban', $this->assertNoSepNotNull($legalEntity, 'IBAN'));
		$returnValue->setAttribute('reference_number', $this->evaluateFuncExp(fx('txtFormatSwissBvr',$this->assertNotNull($customerOrder,'customerOrderNumber'),false)));
		// bank
		$xml = $this->createXmlElement($returnValue, 'bank', $options);
		$xml = $this->createXmlElement($xml, 'company', $options);
		$this->createXmlElement($xml, 'companyname', $options, 'Postfinance SA');
		$xml = $this->createXmlElement($xml, 'postal', $options);
		$this->createXmlElement($xml, 'zip', $options, '3030');
		$this->createXmlElement($xml, 'city', $options, 'Bern');
		// creditor
		$xml = $this->createXmlElement($returnValue, 'creditor', $options);
		$xml = $this->createXmlElement($xml, 'company', $options);
		$this->createXmlElement($xml, 'companyname', $options, $this->assertNotNull($legalEntity, 'entityName'));
		$xml = $this->createXmlElement($xml, 'postal', $options);
		$this->createXmlElement($xml, 'street', $options, $this->assertNotNull($legalEntity, 'entityAddress','street'));
		$this->createXmlElement($xml, 'zip', $options, $this->assertNotNull($legalEntity, 'entityAddress','zip_code'));
		$this->createXmlElement($xml, 'city', $options, $this->assertNotNull($legalEntity, 'entityAddress','city'));
		return $returnValue;
	}
			
	/**
	 * Creates an invoice request ivg (LAI) node
	 * @param DOMElement $invoiceBody current invoice body node
	 * @param Element $customerOrder element of type CustomerOrders sourcing the invoice creation
	 * @param WigiiBPLParameter $options optional bag of options to configure the generation process
	 * @return DOMElement the created invoice ivg node
	 */
	protected function createInvoice45ivg($invoiceBody,$customerOrder,$options) {
		$legalEntity = $options->getValue('legalEntity');
		$patient = $options->getValue('customer');
		$returnValue = $this->createXmlElement($invoiceBody, 'ivg', $options);
		$returnValue->setAttribute('case_id', $this->assertNotNull($customerOrder, 'caseNumber'));
		$returnValue->setAttribute('case_date', $this->assertDateNotNull($customerOrder, 'caseDate'));
		$returnValue->setAttribute('ssn', $this->assertNoSepNotNull($patient, 'noAVS'));
		$returnValue->setAttribute('nif', $this->assertNoSepNotNull($legalEntity,'noNIF'));
		return $returnValue;
	}
	
	/**
	 * Creates an invoice request uvg (LAA) node
	 * @param DOMElement $invoiceBody current invoice body node
	 * @param Element $customerOrder element of type CustomerOrders sourcing the invoice creation
	 * @param WigiiBPLParameter $options optional bag of options to configure the generation process
	 * @return DOMElement the created invoice ivg node
	 */
	protected function createInvoice45uvg($invoiceBody,$customerOrder,$options) {
		$legalEntity = $options->getValue('legalEntity');
		$patient = $options->getValue('customer');
		$returnValue = $this->createXmlElement($invoiceBody, 'uvg', $options);
		$returnValue->setAttribute('insured_id', $this->assertNotNull($customerOrder, 'caseNumber'));
		$returnValue->setAttribute('case_id', $this->assertNotNull($customerOrder, 'caseNumber'));
		$returnValue->setAttribute('case_date', $this->assertDateNotNull($customerOrder, 'caseDate'));
		$returnValue->setAttribute('ssn', $this->assertNoSepNotNull($patient, 'noAVS'));
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
		$patient = $options->getValue('customer');
		$returnValue = $this->createXmlElement($invoiceBody, 'treatment', $options);
		$returnValue->setAttribute('date_begin', $this->assertDateNotNull($customerOrder, 'orderDate'));
		$returnValue->setAttribute('date_end', $this->assertDateNotNull($customerOrder, 'orderDate'));
		$returnValue->setAttribute('canton', $this->assertCanton($patient, 'address'));
		$returnValue->setAttribute('reason', 'unknown');
		return $returnValue;
	}
	
	protected $tariffTypeMapping = array('OSM'=>'326','ASTO'=>'327');
	/**
	 * Creates an invoice request services node
	 * @param DOMElement $invoiceBody current invoice body node
	 * @param Element $customerOrder element of type CustomerOrders sourcing the invoice creation
	 * @param WigiiBPLParameter $options optional bag of options to configure the generation process
	 * @return DOMElement the created invoice services node
	 */
	protected function createInvoice45Services($invoiceBody,$customerOrder,$options) {
		$services = $this->createXmlElement($invoiceBody, 'services', $options);
		// iterates through the CatalogOrders linked to this CustomerOrder and create service nodes.
		sel($this->getPrincipal(),$this->evaluateFuncExp(fx('getCustomerOrderDetail',$customerOrder->getFieldValue('customerOrderNumber'))),dfasl(
			dfas('CallbackDFA','setProcessDataChunkCallback',function($data,$callbackDFA) use($services,$customerOrder, $options){
				$legalEntity = $options->getValue('legalEntity');
				$servicesCount = $callbackDFA->getValueInContext('servicesCount');
				$servicesCount++;
				$callbackDFA->setValueInContext('servicesCount',$servicesCount);
				
				$catalogOrder = $data->getDbEntity();				
				$service = $this->appendXmlElement($services, 'service', $options);
				$service->setAttribute('record_id',$servicesCount);
				
				$articleNumber = $this->assertNotNull($catalogOrder, 'articleNumber');
				$tariffNumber = $catalogOrder->getFieldValue('tariffNumber');
				if(empty($tariffNumber)) $tariffNumber = $articleNumber;
				list($designation,$tariffType) = explode($tariffNumber,$this->assertNotNull($catalogOrder, 'designation'));
				$tariffType = trim($tariffType);
				$remark = str_replace(array('OSM','ASTO'), "", $tariffType);
				if(!empty($remark) && $remark==$tariffType) $remark=null;
				if(!empty($remark)) $tariffType = str_replace($remark,"",$tariffType);
				$coTariffType = $catalogOrder->getFieldValue('tariffType');
				if(!empty($coTariffType) && $coTariffType!='none') $tariffType = $coTariffType; 
				$tariffType = $this->tariffTypeMapping[$tariffType];
				if(empty($tariffType)) throw new WigiiMedidataException('tariff type is empty for article '.$articleNumber,WigiiMedidataException::XML_VALIDATION_ERROR);
				
				$service->setAttribute('tariff_type',$tariffType);
				$service->setAttribute('code',$tariffNumber);				
				$service->setAttribute('name',trim($designation));
				$service->setAttribute('session','1');
				$quantity = $this->assertNumericNotNull($catalogOrder, 'quantity');
				$service->setAttribute('quantity',$quantity);
				$service->setAttribute('date_begin',$this->assertDateNotNull($catalogOrder, 'orderDate'));
				$service->setAttribute('provider_id',$this->assertNoSepNotNull($legalEntity,'noGLN'));
				$glnPrincipal = $this->assertNoSep($legalEntity,'noGLNResponsible');
				if(empty($glnPrincipal)) $glnPrincipal = $this->assertNoSepNotNull($legalEntity,'noGLN');
				$service->setAttribute('responsible_id',$glnPrincipal);
				$amount = $this->assertNumericNotNull($catalogOrder, 'orderTTCTotal');
				$service->setAttribute('unit',($quantity!=0?$amount/$quantity:$amount));
				$service->setAttribute('unit_factor','1');
				$service->setAttribute('amount',$amount);
				$service->setAttribute('vat_rate',$this->assertNumeric($catalogOrder, 'vatValue'));
				$service->setAttribute('obligation','1');				
				if(!empty($remark)) $service->setAttribute('remark',$remark);
				$service->setAttribute('service_attributes','0');
			})
		));
		return $services;
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
	 * @param String $nodeValue optional node value to set upon creation
	 * @return DOMElement created or found xml element
	 */
	protected function createXmlElement($parentNode,$name,$options,$nodeValue=null) {
		if(!isset($options)) $options = wigiiBPLParam();
		$returnValue = $this->createXmlElementByName($parentNode, $options->getValue('namespaceURI'), $options->getValue('namespacePrefix'), $name);
		if(isset($nodeValue)) $returnValue->nodeValue = $nodeValue;
		return $returnValue;
	}
	
	/**
	 * Creates and appends an XML Element givent its parent node, and name.
	 * @param DOMNode $parentNode existing XML node in which to create the element
	 * @param String $name element tag name
	 * @param WigiiBPLParameter $options a bag of options containing at least the namespaceURI and namespacePrefix
	 * @return DOMElement created xml child element
	 */
	protected function appendXmlElement($parentNode,$name,$options) {
		if(!isset($options)) $options = wigiiBPLParam();
		return $this->appendXmlElementByName($parentNode, $options->getValue('namespaceURI'), $options->getValue('namespacePrefix'), $name);
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
	
	/**
	 * Creates and appends an XML Element givent its parent node, local name and namespace.
	 * @param DOMDocument|DOMElement $parentNode existing XML node in which to create the element
	 * @param String $namespaceURI xml namespace of the element
	 * @param String $namespacePrefix actual namespace used prefi
	 * @param String $localName element tag name
	 * @return DOMElement created xml element
	 */
	protected function appendXmlElementByName($parentNode,$namespaceURI,$namespacePrefix,$localName) {
		if(!isset($parentNode)) throw new FuncExpEvalException('parentNode cannot be null',FuncExpEvalException::INVALID_ARGUMENT);
		// creates a new child node
		if($namespaceURI) $returnValue = $this->getXmlDoc($parentNode)->createElementNS($namespaceURI,($namespacePrefix?$namespacePrefix.':':'').$localName);
		else $returnValue = $this->getXmlDoc($parentNode)->createElement($localName);
		// attaches child node to parent
		$parentNode->appendChild($returnValue);
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
	 * @param String $subfieldName optional subfield name
	 * @return Scalar element field value
	 * @throws WigiiMedidataException if assertion fails
	 */
	protected function assertNotNull($element,$fieldName,$subfieldName=null) {
		$returnValue = $element->getFieldValue($fieldName,$subfieldName);
		if(!is_numeric($returnValue) && empty($returnValue)) throw new WigiiMedidataException("Field '$fieldName' cannot be empty",WigiiMedidataException::XML_VALIDATION_ERROR);
		return $returnValue;
	}
	/**
	 * Asserts that a field value doesn't contain any natural separator and is not null, then returns it
	 * @param Element $element element from which to get the field value
	 * @param String $fieldName the field name
	 * @param String $subfieldName optional subfield name
	 * @return Scalar element field value
	 * @throws WigiiMedidataException if assertion fails
	 */
	protected function assertNoSepNotNull($element,$fieldName,$subfieldName=null) {
		$returnValue = $element->getFieldValue($fieldName,$subfieldName);
		if(!empty($returnValue)) $returnValue = str_replace(array('.','-'), "", preg_replace("/".ValueListArrayMapper::Natural_Separators."/", "", $returnValue));
		if(!is_numeric($returnValue) && empty($returnValue)) throw new WigiiMedidataException("Field '$fieldName' cannot be empty",WigiiMedidataException::XML_VALIDATION_ERROR);
		return $returnValue;
	}
	/**
	 * Asserts that a field value doesn't contain any natural separator and returns it
	 * @param Element $element element from which to get the field value
	 * @param String $fieldName the field name
	 * @param String $subfieldName optional subfield name
	 * @return Scalar element field value
	 * @throws WigiiMedidataException if assertion fails
	 */
	protected function assertNoSep($element,$fieldName,$subfieldName=null) {
		$returnValue = $element->getFieldValue($fieldName,$subfieldName);
		if(!empty($returnValue)) $returnValue = str_replace(array('.','-'), "", preg_replace("/".ValueListArrayMapper::Natural_Separators."/", "", $returnValue));
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
	/**
	 * Asserts that a field value is a number and returns it
	 * @param Element|Scalar $element element from which to get the field value or scalar value to be tested directly
	 * @param String $fieldName the field name
	 * @param Boolean $allowNull optional flag allowing null numbers or not. Default to true.
	 * @return Scalar element field value
	 * @throws WigiiMedidataException if assertion fails
	 */
	protected function assertNumeric($element,$fieldName,$allowNull=true) {
		if($element instanceof Record) $returnValue = $element->getFieldValue($fieldName);
		elseif(is_object($element)) $returnValue = $element->{$fieldName}; 
		else $returnValue = $element;
		if(!is_numeric($returnValue)) {
			if(!(empty($returnValue) && $allowNull)) throw new WigiiMedidataException("Field '$fieldName' is not a valid number",WigiiMedidataException::XML_VALIDATION_ERROR);
		}		
		return $returnValue;
	}	
	/**
	 * Asserts that a field value is a non null number and returns it
	 * @param Element $element element from which to get the field value
	 * @param String $fieldName the field name
	 * @return Scalar element field value
	 * @throws WigiiMedidataException if assertion fails
	 */
	protected function assertNumericNotNull($element,$fieldName) {return $this->assertNumeric($element, $fieldName,false);}
	private $cantonCodeMapping = array(
			'vaud'=>'VD',
			'valais'=>'VS',
			'genève'=>'GE','geneve'=>'GE',
			'neuchatel'=>'NE','neuchâtel'=>'NE',
			'fribourg'=>'FR',
			'berne'=>'BE','bern'=>'BE',
			'tessin'=>'TI',
			'allemagne'=>'D','france'=>'F','italie'=>'I','autriche'=>'A'		
	);
	/**
	 * Asserts that a field value is a canton code and returns it
	 * @param Element $element element from which to get the field value
	 * @param String $fieldName the field name
	 * @param Boolean $allowNull optional flag allowing null values or not. Default to true.
	 * @return Scalar element field value
	 * @throws WigiiMedidataException if assertion fails
	 */
	protected function assertCanton($element,$fieldName,$allowNull=true) {
		// checks for canton
		$returnValue = $element->getFieldValue($fieldName,'state');
		if(!empty($returnValue)) {
			$s = $this->cantonCodeMapping[strtolower($returnValue)];
			if(!empty($s)) $returnValue = $s;
		}
		// checks for country
		if(empty($returnValue)) {
			$returnValue = $element->getFieldValue($fieldName,'country');
			if(!empty($returnValue)) {
				$s = $this->cantonCodeMapping[strtolower($returnValue)];
				if(!empty($s)) $returnValue = $s;
			}
		}
		if(empty($returnValue) && !$allowNull) throw new WigiiMedidataException("Field '$fieldName' is not a valid canton",WigiiMedidataException::XML_VALIDATION_ERROR);		
		return $returnValue;
	}
	/**
	 * Asserts that a field value is a non null canton code and returns it
	 * @param Element $element element from which to get the field value
	 * @param String $fieldName the field name
	 * @return Scalar element field value
	 * @throws WigiiMedidataException if assertion fails
	 */
	protected function assertCantonNotNull($element,$fieldName) {return $this->assertCanton($element, $fieldName,false);}	
	/**
	 * Formats and translates a field value using the Wigii TRM
	 * @param Element $element element from which to get the field value
	 * @param String $fieldName the field name
	 */
	protected function formatValue($element, $fieldName) {
		return html_entity_decode($this->getTrm()->formatValueFromFS(fs($fieldName), $element), ENT_COMPAT, "UTF-8");
	}	
}