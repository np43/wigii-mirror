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
 * A sub element in memory list
 * Created by CWE on 9.12.2014
 */
class SubElementInMemoryList extends Model implements ObjectList
{
	/**
	 * The sub element list.
	 * An array of Elements, index is numeric ranging from 0 to count.
	 * Not indexed by Element ID because should support new elements not yet inserted in the database.
	 * @var Array
	 */
	protected $eltArray;
	/**
	 * dynamically calculated FieldSelectorList used to persist elements
	 * @var FieldSelectorList
	 */
	protected $fieldSelectorListForPersist; 

	/**
	 * The Wigii API client used in data flows.
	 * @var GroupBasedWigiiApiClient
	 */
	protected $apiClient;
	
	/**
	 * The module of the sub elements stored in the list
	 * @var Module
	 */
	protected $eltModule;
	
	/**
	 * The module of the root element
	 * @var Module
	 */
	protected $rootEltModule;
	
	/**
	 * The field list of the sub elements stored in the list
	 * @var FieldList
	 */
	protected $eltFieldList;
	
	/**
	 * @var LinkSelector
	 */
	private $linkSelector;
	
	
	// Object lifecycle
	
	/**
	 * 
	 * @param Principal $principal
	 * @param Element $parentElement
	 * @param String $fieldName
	 * @param ConfigSelector $rootConfigSelector
	 * @return SubElementInMemoryList
	 */
	public static function createInstance($principal, $parentElement, $linkFieldName, $rootConfigSelector=null) {
		$returnValue = TechnicalServiceProvider::createSubElementInMemoryListInstance();
		$returnValue->setPrincipal($principal);
		$returnValue->setSubElementsConfig($parentElement, $linkFieldName, $rootConfigSelector);
		$returnValue->reset();
		return $returnValue;
	}
	
	public function reset() {
		$this->freeMemory();
		$this->eltArray = array();
		$this->fieldSelectorListForUpdate = FieldSelectorListArrayImpl::createInstance();
		$this->resetSubElementsConfig();
	}
	
	public function freeMemory() {
		unset($this->eltArray);
		unset($this->fieldSelectorListForPersist);
		if(isset($this->apiClient)) {
			$this->apiClient->freeMemory();
			unset($this->apiClient);
		}
		unset($this->linkSelector);
		unset($this->eltModule);
		unset($this->rootEltModule);
		unset($this->eltFieldList);
	}
	
	/**
	 * Resets sub elements configuration and instantiates API client and other contextual info.
	 * Precondition: setSubElementsConfig should have been called.
	 */
	protected function resetSubElementsConfig() {
		$principal = $this->getPrincipal();
		if(!isset($principal)) throw new ListException('principal has not been set. Please set one using the setPrincipal method.', ListException::CONFIGURATION_ERROR);
		$parentElement = $this->getParentElement();
		if(!isset($parentElement)) throw new ListException('parent element has not been set. Please set one using the setSubElementsConfig method', ListException::CONFIGURATION_ERROR);
		$linkFieldName = $this->getLinkFieldName();
		if(!isset($linkFieldName)) throw new ListException('link field name has not been set. Please set one using the setSubElementsConfig method', ListException::CONFIGURATION_ERROR);
		
		// creates API client centered on root config if defined
		$apiClient = null;
		$rootConfigSelector = $this->getRootConfigSelector();
		if(isset($rootConfigSelector)) {
			// a wigiiNamespace has been specified --> adapts the Principal if needed
			$confWigiiNamespace = $rootConfigSelector->getWigiiNamespaceName();
			if(isset($confWigiiNamespace)) $confWigiiNamespace = $this->getWigiiNamespaceAdminService()->getWigiiNamespace($principal, $confWigiiNamespace);
			if(isset($confWigiiNamespace) && $principal->hasAdaptiveWigiiNamespace()) {
				$principal->bindToWigiiNamespace($confWigiiNamespace);
			}
			// a groupLogExp has been specified --> creates a GroupBasedWigiiApiClient centered on theses groups
			$groupExp = $rootConfigSelector->getGroupLogExp();
			if(isset($groupExp)) {
				$apiClient = ServiceProvider::getGroupBasedWigiiApiClient($principal, $groupExp);
				$groupList = $apiClient->getGroupList();
				// gets wigiiNamespace
				$initialized = false; $firstWigiiNamespace = null;
				$oneWigiiNamespace = true;
				foreach($groupList->getListIterator() as $group)
				{
					$wigiiNamespace = $group->getWigiiNamespace();
					if($initialized)
					{
						// checks wigiiNamespace unicity
						if($wigiiNamespace !== $firstWigiiNamespace) $oneWigiiNamespace = false;
					}
					else
					{
						$firstWigiiNamespace = $wigiiNamespace;
						$initialized = true;
					}
				}
				// adapts wigii namespace if needed
				if(is_null($confWigiiNamespace) && $oneWigiiNamespace && $principal->hasAdaptiveWigiiNamespace()) {
					$principal->bindToWigiiNamespace($firstWigiiNamespace);
				}
			}
		}
		// if API client is still null, then instantiates one to support sub elements.
		if(!isset($apiClient)) $apiClient = ServiceProvider::getGroupBasedWigiiApiClient($principal, null);
		
		// centers configuration on subelements
		$cs = $apiClient->getConfigService();
		$cs->selectSubElementsConfig($principal, $parentElement, $linkFieldName);
		
		$this->eltModule = $cs->getCurrentModule();
		$this->rootEltModule = $this->getModuleAdminService()->getModule($principal, $cs->getCurrentSubElementPathFromRoot()->getFirstLinkSelector()->getModuleName());
		$this->linkSelector = $cs->getCurrentLinkSelector();
		$this->apiClient = $apiClient;
	}
	
	public function load($listFilter=null) {
		ListException::throwNotImplemented();
	}
	
	// Dependency injection
	
	private $dflowS;
	/**
	 * Injects a DataFlowService to be used by this class
	 * @param DataFlowService $dataFlowService
	 */
	public function setDataFlowService($dataFlowService)
	{
		$this->dflowS = $dataFlowService;
	}
	/**
	 * Gets the injected DataFlowService
	 * @return DataFlowService
	 */
	protected function getDataFlowService()
	{
		// autowired
		if(!isset($this->dflowS))
		{
			$this->dflowS = ServiceProvider::getDataFlowService();
		}
		return $this->dflowS;
	}
	
	private $moduleAS;
	public function setModuleAdminService($moduleAdminService)
	{
		$this->moduleAS = $moduleAdminService;
	}
	protected function getModuleAdminService()
	{
		// autowired
		if(!isset($this->moduleAS))
		{
			$this->moduleAS = ServiceProvider::getModuleAdminService();
		}
		return $this->moduleAS;
	}
	
	private $wigiiNamespaceAS;
	public function setWigiiNamespaceAdminService($wigiiNamespaceAdminService)
	{
		$this->wigiiNamespaceAS = $wigiiNamespaceAdminService;
	}
	protected function getWigiiNamespaceAdminService()
	{
		// autowired
		if(!isset($this->wigiiNamespaceAS))
		{
			$this->wigiiNamespaceAS = ServiceProvider::getWigiiNamespaceAdminService();
		}
		return $this->wigiiNamespaceAS;
	}
	
	/**
	 * Gets an ElementEvaluator given its class name
	 * @param String $className
	 * @return ElementEvaluator
	 */
	protected function getElementEvaluator($className) {
		return ServiceProvider::getElementEvaluator($this->getPrincipal(), $className);
	}
	
	/**
	 * Returns a configured FuncExpEvaluator ready to be used to execute func exp on the given element
	 * @param Element element on which the FuncExpEvaluator should be used.
	 * @param String $className
	 * @return FuncExpEvaluator
	 */
	protected function getFuncExpEvaluator($element, $className) {
		$principal = $this->getPrincipal();
		// gets element evaluator
		$parentEvaluator = $this->getElementEvaluator($className);
		// injects the context
		$parentEvaluator->setContext($principal, $element);
		// gets vm
		$returnValue = ServiceProvider::getFuncExpVM($principal, $parentEvaluator);
		$returnValue->setFreeParentEvaluatorOnFreeMemory(true);
		return $returnValue;
	}
	
	// Configuration
	
	private $principal;
	protected function setPrincipal($principal) {
		if(!isset($principal)) throw new ListException('principal cannot be null', ListException::INVALID_ARGUMENT);
		$this->principal = $principal;
	}
	/**
	 * @return Principal
	 */
	public function getPrincipal() {
		return $this->principal;
	}
	
	private $parentElement;
	private $linkFieldName;
	private $rootConfigSelector;
	/**
	 * 
	 * @param unknown $parentElement
	 * @param unknown $linkFieldName
	 * @param string $rootConfigSelector
	 * @throws ListException
	 */
	protected function setSubElementsConfig($parentElement, $linkFieldName, $rootConfigSelector=null) {
		if(!isset($parentElement)) throw new ListException('parent element cannot be null', ListException::INVALID_ARGUMENT);
		$this->parentElement = $parentElement;
		if(empty($linkFieldName)) throw new ListException('link field name cannot be empty', ListException::INVALID_ARGUMENT);
		$this->linkFieldName = $linkFieldName;
		$this->rootConfigSelector = $rootConfigSelector;
	}

	/**
	 * Returns the parent element to which the sub elements are linked
	 * @return Element
	 */
	public function getParentElement() {
		return $this->parentElement;
	}
	
	/**
	 * Returns the field name to which the sub elements are linked
	 * @return String
	 */
	public function getLinkFieldName() {
		return $this->linkFieldName;
	}
	
	/**
	 * Returns the root element config selector if set
	 * @return ConfigSelector
	 */
	public function getRootConfigSelector() {
		return $this->rootConfigSelector;
	}
	
	// Operations
	
	/**
	 * @param LogExp $logExp logExp filtering the list
	 * @return SubElementInMemoryList
	 */
	public function filter($logExp) {
		ListException::throwNotImplemented();
	}
	
	/**
	 * @return SubElementInMemoryList
	 */
	public function resetFilter() {
		ListException::throwNotImplemented();
	}
	
	/**
	 * @param CalculatedFieldSelectorMap $cfsMap calculated field selector map used to set the element field values
	 * @return SubElementInMemoryList
	 */
	public function update($cfsMap) {
		ListException::throwNotImplemented();
	}
	
	/**
	 * @param CalculatedFieldSelectorMap $cfsMap calculated field selector map used to set the element field values
	 * @return SubElementInMemoryList
	 */
	public function add($cfsMap) {
		ListException::throwNotImplemented();
	}
	
	/**
	 * @param CalculatedFieldSelectorMap $cfsMap calculated field selector map used to set the element field values
	 * @param LogExp $selectorLogExp log exp selecting existing elements to update. If no element are selected, then one is added.
	 * @return SubElementInMemoryList
	 */
	public function updateOrAdd($cfsMap, $selectorLogExp) {
		ListException::throwNotImplemented();
	}
	
	/**
	 * @return SubElementInMemoryList
	 */
	public function delete() {
		ListException::throwNotImplemented();
	}
	
	/**
	 * Processes the sub element list through the given set of DataFlowActivities
	 * @return Any|SubElementInMemoryList returns the return value of the data flow or 
	 * the list itself if the data flow did not return anything. 
	 */
	public function process($dataFlowActivitySelectorList) {
		if(!$this->isEmpty()) {
			$dfS = $this->getDataFlowService();
			$principal = $this->getPrincipal();
			$dataFlowContext = $dfS->startStream($principal, $dataFlowActivitySelectorList);
			// initializes the context
			if(isset($this->apiClient)) $dataFlowContext->setAttribute('GroupBasedWigiiApiClient', $this->apiClient);
			if(isset($this->fieldSelectorListForPersist) && !$this->fieldSelectorListForPersist->isEmpty()) $dataFlowContext->setAttribute('FieldSelectorList', $this->fieldSelectorListForPersist);
			if(isset($this->linkSelector) && !$this->getParentElement()->isNew()) {
				$this->linkSelector->setOwnerElementId($this->getParentElement()->getId());
				$dataFlowContext->setAttribute('linkSelector', $this->linkSelector);
				// centers config on sub elements if needed
				if(isset($this->apiClient)) {
					$cs = $this->apiClient->getConfigService();
					if($cs->getCurrentMasterElementId() != $this->linkSelector->getOwnerElementId() ||
						$cs->getCurrentFieldName() != $this->linkSelector->getFieldName() ||
						$cs->getCurrentModule() !== $this->eltModule) {
						
						$cs->selectSubElementsConfig($principal, 
							$this->linkSelector->getOwnerElementId(), $this->linkSelector->getFieldName());
						// sets the sub element module
						$this->eltModule = $cs->getCurrentModule();
						// sets the root element module
						$this->rootEltModule = $this->getModuleAdminService()->getModule($principal, $cs->getCurrentSubElementPathFromRoot()->getFirstLinkSelector()->getModuleName());
					}
				}
			}
			// runs the data flow
			foreach($this->eltArray as $element) {
				$dfS->processDataChunk($element, $dataFlowContext);
			}
			$returnValue = $dfS->endStream($dataFlowContext);
			
			if(isset($returnValue)) return $returnValue;
			else return $this;
		}
		else return $this;
	}
	
	private $dfaslForPersist;
	/**
	 * Persists the element list into the database.
	 * New elements are inserted, modified elements are updated according to calculated FieldSelectorList and
	 * elements marked to be deleted are deleted (or put in trashbin if defined)
	 * @return SubElementInMemoryList the current list (new elements are refreshed from the db to get ID).
	 */
	public function persist() {
		if(!$this->isEmpty()) {
			if(!isset($this->dfaslForPersist)) {
				$this->dfaslForPersist = dfasl(
					/* evaluates calculated fields before save */
					dfas('CallbackDFA',
						'setStartOfStreamCallback', CallableObject::createInstance('storeElementEvaluatorInContext', $this),
						'setProcessDataChunkCallback', CallableObject::createInstance('evaluateCalculatedFieldsBeforeSave', $this)
					),
					/* persists sub element in db */
					dfas('ElementDFA',
						'setMode', ElementDFA::MODE_MIXED,
						'setDecisionMethod', CallableObject::createInstance('choosePersistAction', $this),
						'setPutInTrashBin', true
					),
					dfas('ArrayBuffer')
				);
			}
			// persists the sub elements and replaces the current buffer with updated elements.
			$this->eltArray = $this->process($this->dfaslForPersist);
		}
	}
	/**
	 * Callback method for ElementDFA::setDecisionMethod
	 * @param Element $element
	 * @param DataFlowContext $dataFlowContext
	 */
	public function choosePersistAction($element, $dataFlowContext) {
		$isNew = $element->isNew();
		$isDeleted = $element->getDynamicAttributeValue('seml_isDeleted');
		// if element is new and deleted then filter
		if($isNew && $isDeleted) return ElementDFA::MODE_FILTER;
		// if element is new then insert
		elseif($isNew) return ElementDFA::MODE_PERSIST;
		// if element is deleted then delete
		elseif($isDeleted) return ElementDFA::MODE_DELETE;
		// else update
		else return ElementDFA::MODE_PERSIST;
	}
	
	/**
	 * Stores Element_Evaluator name in data flow context.
	 * Callback method for CallbackDFA::setStartOfStreamCallback 
	 * @param CallbackDFA $callbackDFA
	 */
	public function storeElementEvaluatorInContext($callbackDFA) {
		$callbackDFA->setValueInContext('Element_evaluator', $this->getElementEvaluatorClassName());
	}
	protected function getElementEvaluatorClassName() {
		$cs = $this->apiClient->getConfigService();
		$principal = $this->getPrincipal();
		// gets sub element evaluator class name
		$evaluatorClassName = (string)$cs->getParameter($principal, $this->eltModule, 'Element_evaluator');
		// if not found, then gets root element evaluator class name
		if(empty($evaluatorClassName)) $evaluatorClassName = (string)$cs->getParameter($principal, $this->rootEltModule, 'Element_evaluator');
		return $evaluatorClassName;
	}
	/**
	 * Evaluates sub element calculated fields before saving to the database.
	 * Callback method for CallbackDFA::setProcessDataChunkCallback 
	 * in the context of the persist dfasl.
	 * @param Element $element
	 * @param CallbackDFA $callbackDFA
	 */
	public function evaluateCalculatedFieldsBeforeSave($element, $callbackDFA) {
		// gets evaluator 
		$evaluator = $this->getElementEvaluator($callbackDFA->getValueInContext('Element_evaluator'));
		// evaluates all calculated fields
		$evaluator->evaluateElement($this->getPrincipal(), $element);
		// pushes element forward in the flow
		$callbackDFA->writeResultToOutput($element);
	}
	
	/**
	 * Evaluates sub elements default values when creating a new element
	 * Callback method for CallbackDFA::setProcessDataChunkCallback 
	 * in the context of the add dfasl.
	 * @param Element $element
	 * @param CallbackDFA $callbackDFA
	 */
	public function evaluateDefaultValues($element, $callbackDFA) {
		$p = $this->getPrincipal();
		$fxEval = $this->getFuncExpEvaluator($element, $callbackDFA->getValueInContext('Element_evaluator'));
		
		foreach($element->getFieldList()->getListIterator() as $field) {
			$dataType = $field->getDataType();
			$fieldXml = $field->getXml();
			$fieldName = $field->getFieldName();
			
			if($dataType  && !$element->getFieldValue($fieldName)){
				/***************
				 * datatype default values
				 ***************/
				//principal email
				if($dataType->getDataTypeName()=="Emails" && $fieldXml["autoFillWithPrincipalEmail"]){
					$element->setFieldValue($p->getValueInGeneralContext("email"), $fieldName);
				}
				//Attributs or MultipleAttributs with checked="1"
				if($dataType->getDataTypeName()=="Attributs"){
					//prefill the value if checked
					$checked = $fieldXml->xpath("*[@checked='1']");
					if($checked){
						$element->setFieldValue((string)$checked[0], $fieldName);
					} else if((string)$fieldXml->attribute[0]!="none"){
						//if no checked, then take the first value if not none
						$element->setFieldValue((string)$fieldXml->attribute[0], $fieldName);
					}
				}
				if($dataType->getDataTypeName()=="MultipleAttributs"){
					$checked = $fieldXml->xpath("*[@checked='1']");
					$res = array();
					foreach($checked as $val){
						$res[(string)$val] = (string)$val;
					}
					if($res){
						$element->setFieldValue($res, $fieldName);
					}
				}
		
				if($dataType->getDataTypeName()=="Booleans"){
					if((string)$fieldXml["checked"]=="1"){
						$element->setFieldValue(true, $fieldName);
					}
				}
		
				/***************
				 * defaultExp
				 ***************/
				$defaultExp = $this->getDefaultExpFromField($field);
				// if we have a default expression
				if(!empty($defaultExp)) {
					// parses it into a funcExp
					try {
						$defaultExp = str2fx($defaultExp);
					}
					catch(StringTokenizerException $ste) {
						// if syntax error, then keeps the defaultExp as is
						// or we have a real syntax error, that then will be corrected by the user
						// or it is not a funcExp but a constant, in that case keeps the value.
						if($ste->getCode() != StringTokenizerException::SYNTAX_ERROR) throw $ste;
					}
					// executes the func exp
					$defaultVal = $fxEval->evaluateFuncExp($defaultExp, $this);
					// sets the default value if not empty
					if(!empty($defaultVal)) $this->processFieldDefaultValue($element, $field, $defaultVal);
				}
			}
		}
		if(method_exists($fxEval, 'freeMemory')) $fxEval->freeMemory();
	}
	
	/**
	 * Returns a default expression string for this field or null if not defined.
	 * @param Field $field
	 */
	protected function getDefaultExpFromField($field) {
		$xml = $field->getXml();
		return (string)$xml["defaultExp"];
	}
	/**
	 * Processes current Field defaultExp evaluation result
	 * If result is not an array, then stores result as field value
	 * else, assumes that the array are matching pairs of (subfieldname => value)
	 * and stores result[subfieldname] as subfield value
	 * @param Element $element
	 * @param Field $field
	 */
	protected function processFieldDefaultValue($element, $field, $defaultVal)
	{
		if(is_array($defaultVal)){
			foreach($defaultVal as $subFieldName=>$value){
				$element->setFieldValue($value, $field->getFieldName(), $subFieldName);
			}
		} else {
			$element->setFieldValue($defaultVal, $field->getFieldName());
		}
	}
	
	// ObjectList implementation
	
	public function getListIterator() {
		return $this->eltArray;
	}
	
	public function isEmpty() {
		return empty($this->eltArray);
	}
	
	public function count() {
		if(isset($this->eltArray)) return count($this->eltArray);
		else return 0;
	}
}

