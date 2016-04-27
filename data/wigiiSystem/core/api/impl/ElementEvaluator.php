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
 * Gives the infrastructure to evaluate an Element against funcExp attached to a field.
 * Base class, subclass can extend the FuncExp language.
 * Created by CWE on 17 avr. 10
 */
class ElementEvaluator extends RecordEvaluator
{	
	const ELEMENT_FLOW_ADD = 'element-add';
	const ELEMENT_FLOW_EDIT = 'element-edit';
	const ELEMENT_FLOW_DELETE = 'element-delete';
	const ELEMENT_FLOW_COPY = 'element-copy';
	const ELEMENT_FLOW_DATAFLOW = 'element-dataflow';
	const ELEMENT_FLOW_MULTIPLE_ADD = 'multiple-add';
	const ELEMENT_FLOW_MULTIPLE_EDIT = 'multiple-edit';
	const ELEMENT_FLOW_MULTIPLE_DELETE = 'multiple-delete';
	const ELEMENT_FLOW_MULTIPLE_COPY = 'multiple-copy';
	const ELEMENT_FLOW_UNSPECIFIED = 'unspecified';
	
	private $_debugLogger;
	
	// object lifecycle

	public static function createInstance()
	{
		$returnValue = new self();
		$returnValue->reset();
		return $returnValue;
	}

	public function freeMemory($keepContext=false)
	{
		// contextual information that can be kept between two evaluations
		if(!$keepContext) {
			unset($this->elementPListEvalContext);
			unset($this->dataFlowContext);
		}
		parent::freeMemory($keepContext);
	}
	public function reset()
	{
		parent::reset();
	}
	
	protected function copyContextFromRecordEvaluator($recordEvaluator) {
		parent::copyContextFromRecordEvaluator($recordEvaluator);
		$this->elementPListEvalContext = $recordEvaluator->elementPListEvalContext;
		$this->dataFlowContext = $recordEvaluator->dataFlowContext;
	}
	
	// dependency injection
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("ElementEvaluator");
		}
		return $this->_debugLogger;
	}
	
	// implementation

	/**
	 * Evaluates all calculated fields in element in principal security context
	 * @param Field $field If field is not null, then evaluates only this field.
	 * @param boolean $keepContext if true, then evaluator remains locked and contextual information is not unsetted. Call freeMemory to unlock et unset context.
	 */
	public function evaluateElement($principal, $element, $field=null, $keepContext=false) {$this->evaluateRecord($principal, $element, $field, $keepContext);}

	// current context accessible to FuncExp language implementation

	/**
	 * @return Element Returns the element currently evaluated
	 */
	protected function getElement() {return $this->getRecord();}

	/**
	 * Returns the value of a field stored in underlying wigiiBag given a fieldSelector
	 */
	protected function getFieldValue($fieldSelector)
	{
		if(is_null($fieldSelector)) throw new RecordException("fieldSelector cannot be null", RecordException::INVALID_ARGUMENT);
		if($fieldSelector->isElementAttributeSelector())
		{
			$element = $this->getElement();
			if(is_null($element)) throw new RecordException("no Element has been attached to ElementEvaluator", RecordException::INVALID_STATE);
			return $element->getAttribute($fieldSelector);
		}
		else return parent::getFieldValue($fieldSelector);
	}
	
	
	private $elementPListEvalContext;
	/**
	 * Injects an ElementPListEvalContext instance that gives information about 
	 * the currently evaluated elementPList.
	 * The ElementPMapper calls this function
	 * @param ElementPListEvalContext $elementPListEvalContext
	 */
	public function setElementPListEvalContext($elementPListEvalContext) {
		$this->elementPListEvalContext = $elementPListEvalContext;
	}
	
	/**
	 * Returns the injected ElementPListEvalContext that can be used by FuncExp implementors.
	 * @return ElementPListEvalContext instance or null if not currently evaluating an elementPList.
	 */
	protected function getElementPListEvalContext() {
		return $this->elementPListEvalContext;	
	}	
	
	
	private $dataFlowContext;
	/**
	 * @param DataFlowContext $dataFlowContext injects the current DataFlowContext that can be used by FuncExp implementors to interact with the current running DataFlow.
	 * This method is called in data flow activities which subclass ElementDFAWithFuncExpVM
	 */
	public function setDataFlowContext($dataFlowContext) {
		$this->dataFlowContext = $dataFlowContext;
	}
	/**
	 * @return DataFlowContext instance or null if not currently evaluating a data flow of elements.
	 */
	protected function getDataFlowContext() {
		return $this->dataFlowContext;
	}
	
	/**
	 * @return String returns the name of the current flow in which this element is evaluated.
	 * One of ELEMENT_FLOW_ADD, ELEMENT_FLOW_EDIT, ELEMENT_FLOW_DELETE, ELEMENT_FLOW_COPY, ELEMENT_FLOW_DATAFLOW, ELEMENT_FLOW_MULTIPLE_ADD, ELEMENT_FLOW_MULTIPLE_EDIT, ELEMENT_FLOW_MULTIPLE_DELETE, ELEMENT_FLOW_MULTIPLE_COPY, ELEMENT_FLOW_UNSPECIFIED
	 */
	protected function getCurrentFlowName() {
		$element = $this->getElement();
		if($element instanceof Element) { 
			// checks element dynamic attribute 'ctlCurrentFlow' and returns its value if exists
			$ctlCurrentFlow = $element->getDynamicAttribute('ctlCurrentFlow');
			if(isset($ctlCurrentFlow)) {
				$returnValue = $ctlCurrentFlow->getValue();
			}
			// else calculates current flow name and stores it into the dynamic attribute 'ctlCurrentFlow'
			else {
				$formExec = $this->getFormExecutor();
				if(isset($formExec)) {
					if(is_a($formExec, 'EditMultipleElementFormExecutor')) $returnValue = ElementEvaluator::ELEMENT_FLOW_MULTIPLE_EDIT;
					elseif(is_a($formExec, 'DeleteMultipleElementFormExecutor')) $returnValue = ElementEvaluator::ELEMENT_FLOW_MULTIPLE_DELETE;
					elseif(is_a($formExec, 'DeleteElementFormExecutor')) $returnValue = ElementEvaluator::ELEMENT_FLOW_DELETE;
					elseif(is_a($formExec, 'CopyElementFormExecutor')) $returnValue = ElementEvaluator::ELEMENT_FLOW_COPY;
					elseif(is_a($formExec, 'AddElementFormExecutor')) $returnValue = ElementEvaluator::ELEMENT_FLOW_ADD;
					elseif(is_a($formExec, 'EditElementFormExecutor')) $returnValue = ElementEvaluator::ELEMENT_FLOW_EDIT;
					else $returnValue = ElementEvaluator::ELEMENT_FLOW_UNSPECIFIED;
				}
				elseif(!is_null($this->getDataFlowContext())) $returnValue = ElementEvaluator::ELEMENT_FLOW_DATAFLOW;
				else $returnValue = ElementEvaluator::ELEMENT_FLOW_UNSPECIFIED;
	
				$ctlCurrentFlow = ElementDynAttrFixedValueImpl::createInstance($returnValue);
				$element->setDynamicAttribute('ctlCurrentFlow', $ctlCurrentFlow, false);
			}
		}
		else $returnValue = ElementEvaluator::ELEMENT_FLOW_UNSPECIFIED;
		return $returnValue;
	}
	
	
	// root FuncExp language (can be extended in subclasses)
	
	
	/**
	 * Serializes and deserializes an element inner fields from xml. 
	 * See examples of usage in word document 'Wigii Inner Fields concept.doc' stored in the '/Readme/Technical documentation' folder
	 * funcExp signature: manageInnerFields(xmlConfig, optional execGuard, optional const “separateWigiiBag”)
	 * where arguments are:
	 * - Arg(0) xmlConfig: evaluates to an XML string or to a SimpleXmlElement instance which contain 
	 * the configuration of the inner fields.
	 * - Arg(1) execGuard: optional argument. If defined, then should evaluate to a boolean. 
	 * If true, then manageInnerFields executes its job, else does nothing. This is used for conditional 
	 * serialization/deserialization of inner fields. If not defined, then always assumes true.
	 * - Arg(2) const “separateWigiiBag”: optional argument. If defined should evaluate to a constant string
	 * “separateWigiiBag”. If yes, then the inner fields values are not stored in the wigii bag of the element, 
	 * but are stored in a separate wigii bag. This separate wigii bag can be accessed using 
	 * the getInnerFieldsWigiiBag method on the element using the current evaluate field name as a key. 
	 * (if the innerFields wigii bag is not created on the element, then a default WigiiBagBaseImpl is created. 
	 * A specific wigii bag could be set by dependency injection on the element using the 
	 * setInnerFieldsWigiiBag(key, wigiiBag) method).
	 */
	public function manageInnerFields($args) {
		// validates arguments
		$n = $this->getNumberOfArgs($args);
		if($n < 1) throw new ElementServiceException("xmlConfig cannot be null", ElementServiceException::INVALID_ARGUMENT);
		if($n >= 2) $execute = $this->evaluateArg($args[1]);
		else $execute = true;
		// executes if guard is true
		// checks if we have an xml configuration, else does nothing
		if($execute) {
			$xmlConfig = $this->evaluateArg($args[0]);
			$execute = isset($xmlConfig) && $xmlConfig != '';
		}		
		if($execute) {
			$p = $this->getPrincipal();
			
			$separateWigiiBag = (n >= 3) && ($this->evaluateArg($args[2]) == "separateWigiiBag");			
			
			$element = $this->getElement();
			$fieldName = $this->getCurrentField()->getFieldName();
			
			// Gets existing inner field list or creates a new one
			$innerFieldList = $element->getInnerFieldsFieldList($fieldName);
			if(!isset($innerFieldList)) {
				$innerFieldList = FieldListArrayImpl::createInstance();
				$element->setInnerFieldsFieldList($fieldName, $innerFieldList);
			}
			
			// Fills inner field list from config
			if($this->isCalculatingOnFetch() || $innerFieldList->isEmpty()) {
				ServiceProvider::getConfigService()->addFieldsToFieldListFromXmlConfig($p, $innerFieldList, $xmlConfig, true);				
			}
			
			// deserialization
			if($this->isCalculatingOnFetch()) {
				if($separateWigiiBag) {
					$wigiiBag = $element->getInnerFieldsWigiiBag($fieldName);
					if(!isset($wigiiBag)) {
						$wigiiBag = WigiiBagBaseImpl::createInstance();
						$element->setInnerFieldsWigiiBag($fieldName, $wigiiBag);
					}
					
					$elementCopy = clone $element;
					$elementCopy->setWigiiBag($wigiiBag);
				}
				else {
					$wigiiBag = $element->getWigiiBag();
					$elementCopy = $element;
					
					// copies inner fields to element field list
					$fieldList = $element->getFieldList();
					foreach($innerFieldList->getListIterator() as $innerField) {
						try {$fieldList->addField($innerField);} 
						catch(ListException $le){
							if($le->getCode() != ListException::ALREADY_EXISTS) throw $le;
							else {
								$existingField = $fieldList->getField($innerField->getFieldName());
								$existingField->setDataType($innerField->getDataType());
								$existingField->setFuncExp($innerField->getFuncExp());
								$existingField->setXml($innerField->getXml());
								$existingField->disableCalculation($innerField->isCalculationDisabled());
							}						
						}
					}
				}
				
				// Ignores syntax errors.
				// A syntax error appears if the config has changed, but the serialized xml
				// still reflects the old config.
				try {
					TechnicalServiceProvider::getWplToolbox()->xml2record($p, $this->getCurrentFieldValue(), 
						$innerFieldList, $wigiiBag, false, null, $element->getId());				
				}
				catch(DataFlowServiceException $dfse) {
					if($dfse->getCode() != DataFlowServiceException::SYNTAX_ERROR) throw $dfse;
				}

				// evaluates all calculated fields that should be calculated on fetch
				$elementEvaluator = ServiceProvider::getElementEvaluator($p, get_class($this));
				$elementEvaluator->setCalculatingOnFetch(true);
				
				foreach($innerFieldList->getListIterator() as $innerField) {
					if($innerField->shouldCalculateOnFetch()) {
						$elementEvaluator->evaluateElement($p, $elementCopy, $innerField);
					}
				}
				$returnValue = null;
			}
			// serialization
			else {
				if($separateWigiiBag) {
					$wigiiBag = $element->getInnerFieldsWigiiBag($fieldName);
					$elementCopy = clone $element;
					$elementCopy->setWigiiBag($wigiiBag);
				}
				else {
					$wigiiBag = $element->getWigiiBag();
					$elementCopy = $element;
				}
				
				// evaluates all calculated fields and builds a field selector list
				$elementEvaluator = ServiceProvider::getElementEvaluator($p, get_class($this));
				$fsl = FieldSelectorListArrayImpl::createInstance();
				
				foreach($innerFieldList->getListIterator() as $innerField) {
					$elementEvaluator->evaluateElement($p, $elementCopy, $innerField);
					// disables further calculations until next deserialization
					$innerField->disableCalculation(true);
					
					$fXml = $innerField->getXml();
					if($fXml['doNotPersist'] != '1') $fsl->addFieldSelector($innerField->getFieldName());
				}
								
				$returnValue = TechnicalServiceProvider::getWplToolbox()->record2xml($p, $innerFieldList, 
					$wigiiBag, false, $fsl, $element->getId());				
			}
		}
		else $returnValue = $this->getCurrentFieldValue();
		return $returnValue;
	}
	
	/**
	 * Returns the current ElementPListEvalContext or null if none.	
	 */
	public function elementPListEvalGetContext() {
		return $this->elementPListEvalContext;
	}
	/**
	 * Returns the current evaluated element ordinal (index).
	 * First element of list has an ordinal equal to 1.
	 * @return int the ordinal of the current evaluated element or null if evaluation has not started.
	 */
	public function elementPListEvalOrdinal() {
		if(isset($this->elementPListEvalContext)) return $this->elementPListEvalContext->getElementOrdinal();
		else return null;
	}
	
	/**
	 * Returns the number of elements contained in the currently evaluated elementPList
	 * @return int the number of elements contained in the list.
	 * If the select in the database was done using a list filter with pagination, 
	 * then the number of elements in the list will always be smaller or equal to the page size.
	 */
	public function elementPListEvalListSize() {
		if(isset($this->elementPListEvalContext)) return $this->elementPListEvalContext->getElementPListSize();
		else return null;
	}
	
	/**
	 * Returns the total number of elements that would have been selected if no pagination was used.
	 * See method 'getTotalNumberOfObjects' on the Listfilter class.
	 * If no pagination or number of elements smaller than the page size, 
	 * then the method elementPListEvalListSize and elementPListEvalTotalNumberOfElements return the same value.
	 * @return int or 0 if list is empty.
	 */
	public function elementPListEvalTotalNumberOfElements() {
		if(isset($this->elementPListEvalContext)) return $this->elementPListEvalContext->getTotalNumberOfElements();
		else return null;
	}
	
	/**
	 * Cumulative function on a sequence of values
	 * FuncExp signature : <code>elementPListEvalCumul(val, key=null)</code><br/>
	 * Where arguments are :
	 * - Arg(0) val: Evaluates to a number. This number will be added to the last calculated cumul.
	 * - Arg(1) key: Optional scalar. The key under which is stored the history of the cumul. 
	 * If not specified, then the key will be (fieldName(cumul)) where fieldName is equal to currently evaluated field.	 
	 * @return Numeric returns val + cumul(getOrdinal()-1)
	 */
	public function elementPListEvalCumul($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new ElementServiceException("elementPListEvalCumul takes at least one argument which is the value to be added.", ElementServiceException::INVALID_ARGUMENT);
		$val = $this->evaluateArg($args[0]);
		if(empty($val)) $val = 0;
		if(!is_numeric($val)) throw new ElementServiceException("val should evaluated to a number.", ElementServiceException::INVALID_ARGUMENT);		
		if($nArgs > 1) $key = $this->evaluateArg($args[1]);
		else $key = null;
		if(is_null($key)) $key = "(".$this->getCurrentField()->getFieldName()."(cumul))";
		$eltEvalCtx = $this->getElementPListEvalContext();
		$ordinal = $eltEvalCtx->getElementOrdinal();
		if($ordinal > 0) $oldVal = $eltEvalCtx->getHistorizedValue($key, $ordinal-1);
		else $oldVal = 0;
		if(empty($oldVal)) $oldVal = 0;
		$val = $oldVal + $val;
		$eltEvalCtx->setHistorizedValue($key, $val);
		return $val;  
	}
	
	/**
	 * Delta function on a sequence of values
	 * FuncExp signature : <code>elementPListEvalDelta(val, key=null)</code><br/>
	 * Where arguments are :
	 * - Arg(0) val: Evaluates to a number. The last evaluated value will be substracted to this new value to get the delta.
	 * - Arg(1) key: Optional scalar. The key under which is stored the history of the delta. 
	 * If not specified, then the key will be (fieldName(delta)) where fieldName is equal to currently evaluated field.	 
	 * @return Numeric returns val - delta(getOrdinal()-1)
	 */
	public function elementPListEvalDelta($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new ElementServiceException("elementPListEvalDelta takes at least one argument which is the value for which to calculate a delta.", ElementServiceException::INVALID_ARGUMENT);
		$val = $this->evaluateArg($args[0]);
		if(empty($val)) $val = 0;
		if(!is_numeric($val)) throw new ElementServiceException("val should evaluated to a number.", ElementServiceException::INVALID_ARGUMENT);		
		if($nArgs > 1) $key = $this->evaluateArg($args[1]);
		else $key = null;
		if(is_null($key)) $key = "(".$this->getCurrentField()->getFieldName()."(delta))";
		$eltEvalCtx = $this->getElementPListEvalContext();
		$ordinal = $eltEvalCtx->getElementOrdinal();
		if($ordinal > 1) $oldVal = $eltEvalCtx->getHistorizedValue($key, $ordinal-1);
		else $oldVal = 0;
		if(empty($oldVal)) $oldVal = 0;		
		$eltEvalCtx->setHistorizedValue($key, $val);
		return $val-$oldVal;  
	}
	
	/**
	 * Square Delta function on a sequence of values
	 * FuncExp signature : <code>elementPListEvalSquareDelta(val, key=null)</code><br/>
	 * Where arguments are :
	 * - Arg(0) val: Evaluates to a number. The last evaluated value will be substracted to this new value to get the delta and squared up.
	 * - Arg(1) key: Optional scalar. The key under which is stored the history of the delta.
	 * If not specified, then the key will be (fieldName(delta)) where fieldName is equal to currently evaluated field.
	 * @return Numeric returns (val - delta(getOrdinal()-1))^2
	 */
	public function elementPListEvalSquareDelta($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new ElementServiceException("elementPListEvalSquareDelta takes at least one argument which is the value for which to calculate a square delta.", ElementServiceException::INVALID_ARGUMENT);
		$val = $this->evaluateArg($args[0]);
		if(empty($val)) $val = 0;
		if(!is_numeric($val)) throw new ElementServiceException("val should evaluated to a number.", ElementServiceException::INVALID_ARGUMENT);
		if($nArgs > 1) $key = $this->evaluateArg($args[1]);
		else $key = null;
		if(is_null($key)) $key = "(".$this->getCurrentField()->getFieldName()."(squaredelta))";
		$eltEvalCtx = $this->getElementPListEvalContext();
		$ordinal = $eltEvalCtx->getElementOrdinal();
		if($ordinal > 1) $oldVal = $eltEvalCtx->getHistorizedValue($key, $ordinal-1);
		else $oldVal = 0;
		if(empty($oldVal)) $oldVal = 0;
		$eltEvalCtx->setHistorizedValue($key, $val);
		return ($val-$oldVal)*($val-$oldVal);
	}
	
	/**
	 * Returns a linked element through a soft link.
	 * FuncExp signature : <code>getLinkedElement(fieldName)</code><br/>
	 * Where arguments are :
	 * - Arg(0) fieldName: String|FieldSelector. Evaluates to a String defining the field name for which to get the linked element id,
	 * or can be directly a FieldSelector that can be used to select the id value in the element.
	 * @return Element the linked element or null if not defined.
	 */
	public function getLinkedElement($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException('The getLinkedElement function takes at least one argument which is a fieldName or fieldSelector', FuncExpEvalException::INVALID_ARGUMENT);
		if($args[1] instanceof FieldSelector) $fieldName = $args[0];
		else $fieldName = $this->evaluateArg($args[0]);
		return $this->getElement()->getLinkedElement($fieldName);
	}
	
	/**
	 * Returns the old element as currently into the database.
	 * FuncExp signature : <code>getOldElement()</code><br/>
	 * @return Element the old element or null if not current flow is not ELEMENT_FLOW_EDIT
	 */
	public function getOldElement($args) {
		$flowContext = $this->getCurrentFlowName();
		if($flowContext == ElementEvaluator::ELEMENT_FLOW_EDIT) {
			return $this->getFormExecutor()->fetchOldRecord($this->getPrincipal(), ServiceProvider::getExecutionService(), $this->getElement()->getId());
		}
		else return null;
	}
	
	/**
	 * Sets the matrix as an Element dynamic attribute for later use (for instance in other func exp calls).
	 * FuncExp signature: <code>setMatrix(elementAttributeName, matrix)</code><br/>
	 * Where arguments are :
	 * - Arg(0) elementAttributeName: String|FieldSelector. The name of the dynamic element attribute under which to store the matrix or a FieldSelector of type element attribute selector (fs_e)
	 * - Arg(1) matrix: stdClass. The matrix to be set as a dynamic element attribute.
	 * @return stdClass returns the matrix for further use
	 */
	public function setMatrix($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 2) throw new ElementServiceException('setMatrix func exp takes at least two arguments which are elementAttributeName and matrix', ElementServiceException::INVALID_ARGUMENT);
		$elementAttributeName = $args[0];
		if(!($elementAttributeName instanceof FieldSelector)) {
			$elementAttributeName = $this->evaluateArg($args[0]);
			if(!($elementAttributeName instanceof FieldSelector)) {
				$elementAttributeName = fs_e($elementAttributeName);
			}		
		}
		if(!$elementAttributeName->isElementAttributeSelector()) throw new ElementServiceException('elementAttributeName should be either a string or a Element attribute field selector', ElementServiceException::INVALID_ARGUMENT);
		$matrix = $this->evaluateArg($args[1]);
		if(!(is_object($matrix))) throw new ElementServiceException('matrix should be a StdClass instance', ElementServiceException::INVALID_ARGUMENT);
		
		$element = $this->getElement();
		$da = $element->getDynamicAttribute($elementAttributeName->getSubFieldName());
		if(!isset($da)) {
			$da = ElementDynAttrMutableValueImpl::createInstance();
			$element->setDynamicAttribute($elementAttributeName->getSubFieldName(), $da);
		}
		$da->setValue($matrix);
		return $matrix;
	}
	
	/**
	 * Returns the current selected sub element master id
	 * or null if no sub element configuration has been selected
	 * @return Scalar
	 */
	public function cfgSubElementCurrentMasterId($args) {
		$form = $this->getFormExecutor();
		if(isset($form)) {
			return $form->getWigiiExecutor()->getConfigurationContext()->getCurrentMasterElementId();
		} else {
			return null;
		}
	}
	
	/**
	 * Returns the current selected group name, ID or object
	 * FuncExp signature : <code>cfgCurrentGroup(returnAttribute=groupname|id|group, silent=false)</code><br/>
	 * Where arguments are :
	 * - Arg(0) returnAttribute: String. The name of the group attribute to return. Defaults to groupname. If 'group' then returns Group object.
	 * - Arg(1) silent: Boolean. If true, then if current group cannot be retrieved, then no Exception is thrown, but null is returned instead, else Exception is thrown as usual. Defaults to no silent (false). 
	 * @return String|Int|Group
	 * @throws ServiceException INVALID_STATE if Wigii is not capable to return a current selected group name in the calling context.
	 */
	public function cfgCurrentGroup($args) {
		$this->debugLogger()->logBeginOperation('cfgCurrentGroup');
		$nArgs = $this->getNumberOfArgs($args);
		$returnAttribute = 'groupname';
		if($nArgs > 0) $returnAttribute = $this->evaluateArg($args[0]);
		$silent = false;
		if($nArgs > 1) $silent = ($this->evaluateArg($args[1]) == true);
		
		// checks element dynamic attribute 'cfgCurrentGroup' and returns its value if exists
		$cfgCurrentGroup = $this->getElement()->getDynamicAttribute('cfgCurrentGroup');
		if(isset($cfgCurrentGroup)) {
			$returnValue = $cfgCurrentGroup->getValue();
		}
		// else calculates current group and stores it into the dynamic attribute 'cfgCurrentGroup'
		else {
			$flowContext = $this->getCurrentFlowName();
			if($flowContext == ElementEvaluator::ELEMENT_FLOW_ADD ||
				$flowContext == ElementEvaluator::ELEMENT_FLOW_COPY) {
				$exec = ServiceProvider::getExecutionService();
				$p = $this->getPrincipal();
					
				$returnValue = $this->getFormExecutor()->getGroupInWhichToAdd($p, $exec);
				if(!$returnValue->isEmpty()) $returnValue = reset($returnValue->getListIterator());
				else $returnValue = null;
			}
			elseif($flowContext == ElementEvaluator::ELEMENT_FLOW_EDIT ||
					$flowContext == ElementEvaluator::ELEMENT_FLOW_DELETE ||
					$flowContext == ElementEvaluator::ELEMENT_FLOW_MULTIPLE_EDIT ||
					$flowContext == ElementEvaluator::ELEMENT_FLOW_MULTIPLE_DELETE) {
					
				$exec = ServiceProvider::getExecutionService();
				$p = $this->getPrincipal();
				$gAS = ServiceProvider::getGroupAdminService();
				
				// gets all groups containing element
				$groupPList = GroupPListArrayImpl::createInstance();
				ServiceProvider::getElementService()->getAllGroupsContainingElement($p, $this->getElement(), $groupPList, lf($gAS->getFieldSelectorListForGroupWithoutDetail()));
				// if more than one group, intersects with ListContext
				if($groupPList->count() > 1) {
					$returnValue = null;
					$lc = $this->getFormExecutor()->getWigiiExecutor()->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
					if(isset($lc)) {
						$selectedGroups = $lc->getGroupPList();						
						if(isset($selectedGroups) && !$selectedGroups->isEmpty()) {
							$selectedGroups = $selectedGroups->getListIterator();
							if(is_array($selectedGroups)) {
								// retrieves hierarchy of groups below selected groups and intersects tree with groups containing element
								$groupList = GroupListArrayImpl::createInstance();
								$gAS->getSelectedGroupsWithChildrenWithoutDetail($p, lxIn(fs('id'),array_keys($selectedGroups)), $groupList, null,
									lxIn(fs('id'), array_keys($groupPList->getListIterator()))
								);								
								if($groupList->count() == 1) {
									$returnValue = reset($groupList->getListIterator());
								}
							}
						}
					}
				}
				elseif(!$groupPList->isEmpty()) {
					$returnValue = reset($groupPList->getListIterator());
				}
				else $returnValue = null;
			}
			elseif($flowContext == ElementEvaluator::ELEMENT_FLOW_DATAFLOW) {
				$exec = ServiceProvider::getExecutionService();
				$p = $this->getPrincipal();
					
				// gets all groups containing element
				$groupPList = GroupPListArrayImpl::createInstance();
				ServiceProvider::getElementService()->getAllGroupsContainingElement($p, $this->getElement(), $groupPList, lf(ServiceProvider::getGroupAdminService()->getFieldSelectorListForGroupWithoutDetail()));
				// if more than one group, intersects with GroupBasedWigiiApiClient::getGroupList
				if($groupPList->count() > 1) {
					$returnValue = null;
					$apiClient = $this->getDataFlowContext()->getAttribute('GroupBasedWigiiApiClient');
					if(isset($apiClient)) {
						$selectedGroups = $apiClient->getGroupList();
						if(isset($selectedGroups) && !$selectedGroups->isEmpty()) {
							$selectedGroups = $selectedGroups->getListIterator();
							if(is_array($selectedGroups)) {
								// retrieves hierarchy of groups below selected groups and intersects tree with groups containing element
								$groupList = GroupListArrayImpl::createInstance();
								$gAS->getSelectedGroupsWithChildrenWithoutDetail($p, lxIn(fs('id'),array_keys($selectedGroups)), $groupList, null,
										lxIn(fs('id'), array_keys($groupPList->getListIterator()))
								);
								if($groupList->count() == 1) {
									$returnValue = reset($groupList->getListIterator());
								}
							}
						}
					}
				}
				elseif(!$groupPList->isEmpty()) {
					$returnValue = reset($groupPList->getListIterator());
				}
				else $returnValue = null;
			}
			else $returnValue = null;
			
			if(!is_null($returnValue)) $returnValue = $returnValue->getDbEntity();
			
			$cfgCurrentGroup = ElementDynAttrFixedValueImpl::createInstance($returnValue);
			$this->getElement()->setDynamicAttribute('cfgCurrentGroup', $cfgCurrentGroup, false);
		}
		
		$this->debugLogger()->logEndOperation('cfgCurrentGroup');
		if(is_null($returnValue)) {
			if($silent) return null;
			else {
				if(!isset($flowContext)) $flowContext = $this->getCurrentFlowName();
				throw new ServiceException("Not able to evaluate current group in flow '".$flowContext."'", ServiceException::INVALID_STATE);
			}
		}
		else {
			if($returnAttribute == 'group') return $returnValue;
			else return $returnValue->getAttribute($returnAttribute);
		}
	}
	
	/**
	 * Returns the first matching parent group name, ID or object given a name, array of names or drop-down xml configuration 
	 * FuncExp signature : <code>cfgParentGroup(name, returnAttribute=groupname|id|group, silent=false)</code><br/>
	 * Where arguments are :
	 * - Arg(0) name: String|Array|SimpleXmlElement the name or possible names of the group to search for in the parent hierarchy. If name is not defined, returns the direct parent group.
	 * - Arg(1) returnAttribute: String. The name of the group attribute to return. Defaults to groupname. If 'group' then returns Group object.
	 * - Arg(2) silent: Boolean. If true, then if parent group cannot be retrieved, then no Exception is thrown, but null is returned instead, else Exception is thrown as usual. Defaults to silent (true).	 
	 * @return String|Int|Group
	 * @throws ServiceException INVALID_STATE if Wigii is not capable to return a current selected group name in the calling context.
	 */
	public function cfgParentGroup($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs>0) $name=$this->evaluateArg($args[0]);
		else $name=null;
		if($nArgs>1) $returnAttribute=$this->evaluateArg($args[1]);
		else $returnAttribute='groupname';
		if($nArgs>2) $silent=$this->evaluateArg($args[2]);
		else $silent=true;
		
		// gets starting point		
		$group = $this->evaluateFuncExp(fx('cfgCurrentGroup', 'group', true),$this);
		// if no group returns null
		if(!isset($group)) {
			if($silent) return null;
			else {
				if(!isset($flowContext)) $flowContext = $this->getCurrentFlowName();
				throw new ServiceException("Not able to evaluate current group in flow '".$flowContext."'", ServiceException::INVALID_STATE);
			}
		}

		// creates matching array
		$matches=array();
		if(is_array($name)) {
			$matches = array_combine($name, $name);
		}
		elseif($name instanceof SimpleXMLElement) {
			foreach($name->attribute as $attribute_key => $attribute){
				$val = (string)$attribute;
				$matches[$val]=$val;
			}
		}
		elseif(!empty($name)) {
			$matches=array($name,$name);
		}		
		
		$gAS = ServiceProvider::getGroupAdminService();
		$p = $this->getPrincipal();
		$parentId = $group->getGroupParentId();
		$returnValue=null;
		// if no matching, only returns first parent
		if(empty($matches)) {
			if($parentId) $returnValue = $gAS->getGroupWithoutDetail($p, $parentId);
		}
		// else goes up the hierarchy
		else {
			while($parentId) {
				$group = $gAS->getGroupWithoutDetail($p, $parentId);
				if(isset($group)) {				
					// checks for group name matching
					if($matches[$group->getGroupName()]) {
						$returnValue = $group;
						break;
					}
					$parentId = $group->getGroupParentId();
				}
				elseif(!$silent) {
					if(!isset($flowContext)) $flowContext = $this->getCurrentFlowName();
					throw new ServiceException("Not able to evaluate parent group in flow '".$flowContext."'", ServiceException::INVALID_STATE);
				}
				else $parentId=null;
			}		
		}
		if(isset($returnValue)) {	
			if($returnAttribute == 'group') return $returnValue;
			else return $returnValue->getAttribute($returnAttribute);
		}
	}
	
	/**
	 * Checks if a field has changed in the WigiiBag.
	 * FuncExp signature : <code>ctlFieldHasChanged(fieldName)</code><br/>
	 * Where arguments are :
	 * - Arg(0) fieldName: String|FieldSelector. The name of the field or a FieldSelector for which to check if the field has changed in the WigiiBag
	 * @return boolean true if field has changed, else false. If current flow is not 'element-edit' always return false.
	 */
	public function ctlFieldHasChanged($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new RecordException('ctlFieldHasChanged takes one argument which is the field name or a field selector', RecordException::INVALID_ARGUMENT);
		$fieldName = $args[0];
		if($fieldName instanceof FieldSelector) $fieldName = $fieldName->getFieldName();
		else $fieldName = $this->evaluateArg($fieldName);
		if(empty($fieldName)) throw new RecordException('fieldName cannot be null', RecordException::INVALID_ARGUMENT);
		if($this->getCurrentFlowName() == ElementEvaluator::ELEMENT_FLOW_EDIT) {
			return $this->getElement()->getWigiiBag()->isChanged($fieldName);
		}
		else return false;
	}
	
	/**
	 * Refreshes the group panel around a given group. If no group is given, then takes the current group.
	 * FuncExp signature : <code>ctlRefreshGroupPanel(group=null)</code><br/>
	 * Where arguments are :
	 * - Arg(0) group: Group|int. The Group or group ID around which to refresh the group panel.
	 */
	public function ctlRefreshGroupPanel($args) {
		$this->debugLogger()->logBeginOperation('ctlRefreshGroupPanel');
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) $group = $this->evaluateFuncExp(fx('cfgCurrentGroup', 'group'), $this, true);
		else $group = $this->evaluateArg($args[0]);
		
		$p = $this->getPrincipal();		
		if($group != null && !($group instanceof Group)) {
			$group = ServiceProvider::getGroupAdminService()->getGroupWithoutDetail($p, $group);
		}
		if($group != null) {			
			$this->debugLogger()->write('invalids group panel cache');
			$exec = ServiceProvider::getExecutionService();
			$exec->addRequests("groupPanel/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleName()."/display/groupPanel");
			$exec->addJsCode("invalidCache('moduleView');");
			$exec->addJsCode("setTimeout(function(){update('moduleView/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleName()."/display/moduleView');}, 100);");
		}
		$this->debugLogger()->logEndOperation('ctlRefreshGroupPanel');
	}
	
	/**
	 * Refreshes the module view.
	 * FuncExp signature : <code>ctlRefreshModuleView()</code><br/>
	 */
	public function ctlRefreshModuleView($args) {
		$exec = ServiceProvider::getExecutionService();
		$exec->addJsCode("setTimeout(function(){update('moduleView/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleName()."/display/moduleView');}, 100);");
	}
	
	/**
	 * Copies the current element in the specified folder (not sharing but real copy)
	 * See method 'elementCopyTo' in WigiiBPL class.
	 * FuncExp signature : <code>copyElementTo(groupId)</code><br/>
	 * Where arguments are :
	 * - Arg(0) groupId: int. The ID of the group in which to insert the element. If the group ID is null, then takes the current folder.
	 * If the group has another Module than the Element, then a matching on FieldName and DataType is done.
	 */
	public function copyElementTo($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) $groupId = $this->evaluateFuncExp(fx('group', '.'), $this);
		else $groupId = $this->evaluateArg($args[0]);
		
		$parameter = TechnicalServiceProvider::createWigiiBPLParameterInstance();
		$parameter->setValue('element', $this->getElement());
		$parameter->setValue('groupId', $groupId);
		ServiceProvider::getWigiiBPL()->elementCopyTo($this->getPrincipal(), $this, $parameter);
	}
	
	/**
	 * Builds a copy of a current Element which can be dumped into a DataFlow.
	 * FuncExp signature : <code>copyElement(configSelector=null)</code><br/>
	 * Where arguments are :
	 * - Arg(0) configSelector: ConfigSelector. An optional ConfigSelector used to choose the right configuration of the copied Element.
	 * If ConfigSelector points to a different module than the source element, then a matching is done of the fieldName and DataType.
	 * @return DataFlowDumpable
	 */
	public function copyElement($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs > 0) $cs = $this->evaluateArg($args[0]);
		else $cs = null;
		return ServiceProvider::getWigiiBPL()->buildCopyElementDataFlowConnector($this->getElement(), $cs);
	}
	
	/**
	 * Builds a DataFlowDumpable object which dumps the current element instance into a DataFlow.
	 * FuncExp signature : <code>thisElement()</code><br/>
	 * @return DataFlowDumpable
	 */
	public function thisElement($args) {
		return array2df($this->getElement());
	}
}