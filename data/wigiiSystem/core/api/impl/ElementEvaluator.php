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
 * Modified by CWE on 12.12.2016 to add Manual Ordering of Elements Functions
 */
class ElementEvaluator extends RecordEvaluator
{	
	const ELEMENT_FLOW_READ = 'element-read';
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
	 * Authorize specific method and args to be called from public.
	 * WARNING Public principal will be used to execute the method.
	 * @param FuncExp $fx FuncExp to call
	 */
	public function isCallAuthorizedFromPublic($fx){
		$fxName = $fx->getName();
		$args = $fx->getArguments();
// 		switch($fxName){
// 			case "...":
// 				if($args[...]==="..."){
// 					return true;
// 				}
// 				break;
// 		}
		return false;
	}
	
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
	 * One of ELEMENT_FLOW_READ, ELEMENT_FLOW_ADD, ELEMENT_FLOW_EDIT, ELEMENT_FLOW_DELETE, ELEMENT_FLOW_COPY, ELEMENT_FLOW_DATAFLOW, ELEMENT_FLOW_MULTIPLE_ADD, ELEMENT_FLOW_MULTIPLE_EDIT, ELEMENT_FLOW_MULTIPLE_DELETE, ELEMENT_FLOW_MULTIPLE_COPY, ELEMENT_FLOW_UNSPECIFIED
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
					elseif(is_a($formExec, 'DetailElementFormExecutor')) $returnValue = ElementEvaluator::ELEMENT_FLOW_READ;
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
	 * Returns the value of an attribute of current element. Returns null if attribute is not a valid element attribute.
	 * Supports dynamic element attributes.
	 * funcExp signature: eltAttr(attrName)
	 * where arguments are:
	 * - Arg(0) attrName: String|FieldSelector. The element attribute name for which to retrieve the value
	 * @return Any the element attribute value or null if not defined
	 */
	public function eltAttr($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new RecordException("eltAttr takes at least one argument which is the element attribute name for which to get a value", RecordException::INVALID_ARGUMENT);
		$fse = $args[0];
		if(!($fse instanceof FieldSelector)) $fse = fs_e($this->evaluateArg($fse));
		try{return $this->getElement()->getAttribute($fse);}
		catch(Exception $e){return null;}
	}
	
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
	 * Returns the group IDs in which the element should be shared according to the current Attributs and MultipleAttributs values
	 * FuncExp signature : <code>getLinkedGroupIds()</code><br/>
	 * @return Array an array containing the linked group IDs in which to share the element
	 */
	public function getLinkedGroupIds($args) {
		return $this->getElement()->getLinkedIdGroupInRecord($this->getPrincipal(), null)->getListIterator();
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
	    if(!($this->getElement() instanceof Element)) return parent::cfgCurrentGroup($args);
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
								elseif(count($selectedGroups) == 1) {
									$returnValue = reset($selectedGroups);
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
	 * @return int group ID around which group panel has been refreshed.
	 */
	public function ctlRefreshGroupPanel($args) {
		$this->debugLogger()->logBeginOperation('ctlRefreshGroupPanel');
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) $group = $this->evaluateFuncExp(fx('cfgCurrentGroup', 'group', true), $this);
		else $group = $this->evaluateArg($args[0]);
		
		$p = $this->getPrincipal();		
		if($group != null && !($group instanceof Group)) {
			$group = ServiceProvider::getGroupAdminService()->getGroupWithoutDetail($p, $group);
		}
		//refresh group panel and moduleView
		$this->debugLogger()->write('invalids group panel cache');
		$exec = ServiceProvider::getExecutionService();
		$exec->addRequests("groupPanel/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleName()."/display/groupPanel");
		$exec->addJsCode("invalidCache('moduleView');");
		$exec->addJsCode("setTimeout(function(){update('moduleView/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleName()."/display/moduleView');}, 100);");
		$this->debugLogger()->logEndOperation('ctlRefreshGroupPanel');
		if($group) return $group->getId();
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
	 * 
	 * This function cannot be called from public space (i.e. caller is located outside of the Wigii instance)
	 */
	public function copyElementTo($args) {
		$this->assertFxOriginIsNotPublic();
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
	 * Builds an Element and links it to current Element in a field of type Urls.
	 * FuncExp signature : <code>linkElement(groupId, urlFieldname, urlLabel, fieldValuesMap)</code><br/>
	 * Where arguments are :
 	 * - Arg(0) groupId: int. The group in which to insert the linked element
	 * - Arg(1) urlFieldname: string|FieldSelector. The field of type Urls which will contain the link to the new element.
	 * - Arg(2) urlLabel: String. The value of the label for the url field.
	 * - Arg(3) fieldValuesMap: Array. The linked element field values as a Map. Key = fieldname, value = field value (if value is a map then subfields are updated).
	 *
	 * This function cannot be called from public space (i.e. caller is located outside of the Wigii instance)
	 * @return Element
	 */
	public function linkElement($args) {
		$this->assertFxOriginIsNotPublic();
		$p = $this->getPrincipal();
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 4) throw new FuncExpEvalException('The linkElement function takes at least four argument which are groupId, urlFieldname, urlLabel, fieldValuesMap', FuncExpEvalException::INVALID_ARGUMENT);
		$groupId = $this->evaluateArg($args[0]);		
		if($args[1] instanceof FieldSelector){
			$urlField = $args[1]->getFieldName();
		} else {
			$urlField = $this->evaluateArg($args[1]);
		}
		$urlLabel = $this->evaluateArg($args[2]);
		$fieldMap = $this->evaluateArg($args[3]); //here the values are evaluated for each fields
		
		$currentUrl = $this->getFieldValue(fs($urlField,"url"));
		$elementId = explode("/",$currentUrl);
		if(is_array($elementId)){
			$elementId = end($elementId);
		} else {
			$elementId = null;
		}
		
		$elementP = $this->evaluateFuncExp(fx("createUpdateElement",$groupId,$elementId,$fieldMap));
		$element = $elementP->getDbEntity();
		$wigiiNamespace = ServiceProvider::getGroupAdminService()->getGroupWithoutDetail($p, $groupId)->getWigiiNamespace();
		
		$this->setFieldValue(fs($urlField,"target"), "_blank");
		$this->setFieldValue(fs($urlField,"name"), $urlLabel);
		$this->setFieldValue(fs($urlField,"url"), SITE_ROOT."#".$wigiiNamespace->getWigiiNamespaceUrl()."/".$element->getModule()->getModuleUrl()."/item/".$element->getId());
		
		return $element;
	}
	
	/**
	 * Builds a DataFlowDumpable object which dumps the current element instance into a DataFlow.
	 * FuncExp signature : <code>thisElement()</code><br/>
	 * @return DataFlowDumpable
	 */
	public function thisElement($args) {
		return array2df($this->getElement());
	}
	
	/**
	 * Updates a value in the database, given one or several field selectors
	 * Last argument is the value to be persisted, all previous arguments should evaluate to field selectors.
	 * Example: persistVal(f1, f2.name, f3.city, NULL)
	 * will persist field f1, subfields f2.name, f3.city with value NULL
	 *
	 * This function cannot be called from public space (i.e. caller is located outside of the Wigii instance)
	 * @return Scalar the persisted value
	 */
	public function persistVal($args) {
		$this->assertFxOriginIsNotPublic();
		$n = $this->getNumberOfArgs($args);
		if($n < 2) throw new RecordException("For persistVal, the number of arguments should be at least 2", RecordException::INVALID_ARGUMENT);
		
		$element = $this->getElement();
		if(!$element->isNew()) {
			// evaluates value
			$val = $this->evaluateArg($args[$n-1]);
			// prepares cfsMap
			$cfsMap = CalculatedFieldSelectorMapArrayImpl::createInstance();
			for($i = 0; $i < $n-1; $i++) {
				$fs = $args[$i];
				if(!($fs instanceof FieldSelector)) {
					$fs = $this->evaluateArg($fs);
					if(!($fs instanceof FieldSelector)) throw new RecordException("argument $i does not evaluate to a FieldSelector", RecordException::INVALID_ARGUMENT);
				}
				$cfsMap->setCalculatedFieldSelectorByFieldName($fs->getFieldName(), $val, $fs->getSubFieldName());				
			}
			// sets values in element and persists
			sel($this->getPrincipal(),array2df($element),dfasl(
				dfas("ElementSetterDFA","setCalculatedFieldSelectorMap",$cfsMap),
				dfas("ElementDFA","setMode","1")
			));			
			return $val;
		}
	}
	
	/**
	 * Fills current element with a list of pairs (key,value) given on current url.
	 * If key maps to a field name, then field value is updated with given value on url,
	 * Else an element dynamic attribute is created (or updated) with the value given on the url.
	 * FuncExp signature: <code>fillElementFromUrl(startIndex,length) or fillElementFromUrl(fsl)</code><br/>
	 * Where arguments are :
	 * - Arg(0) startIndex: int. Starts parsing the url arguments from this index. Default to 0.
	 * - Arg(1) length: int. Specifies how many arguments should be parsed. If ommitted, then goes until the end.
	 * Or 
	 * - Arg(0) fsl: FieldSelectorList. If given, then filters the incoming URL arguments to take only the (key,value) pairs matching the given field selectors.
	 * This allows to control which fields we allow to update from the URL. 
	 */
	public function fillElementFromUrl($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs>0) $startIndex = $this->evaluateArg($args[0]);
		else $startIndex = 0;
		// checks if we have a FieldSelectorList
		if($startIndex instanceof FieldSelectorList) {
			$fsl = $startIndex;
			$startIndex=0;
		}
		else {
			$fsl = null;
			if($nArgs>1) $length = $this->evaluateArg($args[1]);
			else $length=null;
		}		
		
		$element = $this->getElement();
		$fieldList = $element->getFieldList();
		$params = ServiceProvider::getExecutionService()->getCrtParameters();
		$params = array_slice($params, $startIndex,$length);		
		foreach($params as $fieldDefault){
			list($fieldname, $value) = explode("=", $fieldDefault);
			list($fieldname, $subfieldname) = explode(".", $fieldname);
			// if FieldSelectorList is defined, then takes from URL only declared fields
			if(isset($fsl) && !$fsl->containsFieldSelector($fieldname,$subfieldname)) continue;			
			// updates element field	
			if($fieldList->doesFieldExist($fieldname)) $element->setFieldValue($value, $fieldname, $subfieldname);
			// or creates/updates element attribute
			else {
				if($fieldname=='__element') $fse = fs_e($subfieldname);
				else $fse = fs_e($fieldname);
				
				try { $element->setAttribute($value,$fse); }
				catch(Exception $e) {
					// if no dynamic attribute is defined, then creates one
					if(is_null($element->getDynamicAttribute($fse->getSubFieldName()))) {
						$element->setDynamicAttribute($fse->getSubFieldName(), ElementDynAttrMutableValueImpl::createInstance($value));
					}
					else throw $e;
				}
			}
		}
	}
	
	// Manual ordering of elements
	
	/**
	 * Returns some JS code which fills the choosePosition drop-down used when manually ordering elements in the list
	 * FuncExp signature : <code>ctlManualElementOrderingJS(positionFs,nextIdFs,choosePositionFs,groupLogExp,labelFx,listFilter=null)</code><br/>
	 * Where arguments are :
	 * - Arg(0) positionFs: FieldSelector|String. The field in the Element holding the position number. Field should by of type Numerics.
	 * - Arg(1) nextIdFs: FieldSelector|String. The field in the Element holding the next Element ID. Field should be of type Strings.
	 * - Arg(2) choosePositionFs: FieldSelector|String. The current choosePosition drop-down field. Should be of type Attributs.
	 * - Arg(3) groupLogExp: LogExp. In group log exp specifying the search space in which manual ordering of elements take place.
	 * - Arg(4) labelFx: FieldSelector|FuncExp. An Element FieldSelector or a FuncExp calculating the label which should be put into the choosePosition drop-down
	 * - Arg(5) listFilter: ListFilter. An optional ListFilter used to filter the search space on a specific subset of elements.
	 *@see https://resource.wigii.org/#Public/WigiiCfgPattern/item/1911
	 *@return String the JS code used to initialize the ChoosePosition drop-down when manually ordering elements
	 */
	public function ctlManualElementOrderingJS($args) {
		// restricts flow on Add, Edit or Copy.
		switch($this->getCurrentFlowName()) {
			case ElementEvaluator::ELEMENT_FLOW_ADD:
			case ElementEvaluator::ELEMENT_FLOW_EDIT:
			case ElementEvaluator::ELEMENT_FLOW_COPY:
				break;
			default: return;
		}
		// checks arguments
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs<5) throw new FuncExpEvalException('ctlManualElementOrderingJS takes at least 5 arguments positionFs,nextIdFs,choosePositionFs,groupLogExp,labelFx', FuncExpEvalException::INVALID_ARGUMENT);
		$positionFs = $args[0];
		if(!($positionFs instanceof FieldSelector)) {
			$positionFs = $this->evaluateArg($args[0]);
			if(empty($positionFs)) throw new FuncExpEvalException('positionFs should be a non null FieldSelector', FuncExpEvalException::INVALID_ARGUMENT);
			if(!($positionFs instanceof FieldSelector)) $positionFs = fs($positionFs);
		}
		$nextIdFs = $args[1];
		if(!($nextIdFs instanceof FieldSelector)) {
			$nextIdFs = $this->evaluateArg($args[1]);
			if(empty($nextIdFs)) throw new FuncExpEvalException('nextIdFs should be a non null FieldSelector', FuncExpEvalException::INVALID_ARGUMENT);
			if(!($nextIdFs instanceof FieldSelector)) $nextIdFs = fs($nextIdFs);
		}
		$choosePositionFs = $args[2];
		if(!($choosePositionFs instanceof FieldSelector)) {
			$choosePositionFs = $this->evaluateArg($args[2]);
			if(empty($choosePositionFs)) throw new FuncExpEvalException('choosePositionFs should be a non null FieldSelector', FuncExpEvalException::INVALID_ARGUMENT);
			if(!($choosePositionFs instanceof FieldSelector)) $choosePositionFs = fs($choosePositionFs);
		}
		$groupLogExp = $this->evaluateArg($args[3]);
		$labelFx = $args[4];
		if($nArgs>5) {
			$lf = $this->evaluateArg($args[5]);
			if(!($lf instanceof ListFilter)) throw new FuncExpEvalException('listFilter should be a non null instance of ListFilter', FuncExpEvalException::INVALID_ARGUMENT);
		}
		else $lf = ListFilter::createInstance();
	
		// Prepares FieldSelectorList
		$fsl = $lf->getFieldSelectorList();
		if(!isset($fsl)) {
			$fsl = FieldSelectorListArrayImpl::createInstance();
			$lf->setFieldSelectorList($fsl);
		}
		if(!$fsl->containsFieldSelector($positionFs->getFieldName())) $fsl->addFieldSelectorInstance($positionFs);
		if(!$fsl->containsFieldSelector($nextIdFs->getFieldName())) $fsl->addFieldSelectorInstance($nextIdFs);
		if($labelFx instanceof FieldSelector && !$fsl->containsFieldSelector($labelFx->getFieldName())) $fsl->addFieldSelectorInstance($labelFx);
		elseif($labelFx instanceof FuncExp) $labelFx->getDependencies($fsl);
	
		// Prepares sorting by position
		$lf->setFieldSortingKeyList(fskl(fsk($positionFs->getFieldName(),'value')));
	
		// gets the ChoosePosition drop-down as html
		$choosePosition = sel($this->getPrincipal(), elementPList($groupLogExp,$lf),
				dfasl(
						/* add missing links to next element */
						dfas('CallbackDFA','initializeContext',array('nextIdFs'=>$nextIdFs),
								'setProcessDataChunkCallback',function($data,$callbackDFA){
									$currentElement = $data->getDbEntity();
									$previousElement = $callbackDFA->getValueInContext('previousElement');
									$nextIdFs = $callbackDFA->getValueInContext('nextIdFs');
									// stores first element
									if(!isset($previousElement)) {
										$callbackDFA->setValueInContext('previousElement',$currentElement);
									}
									else {
										$previousElement->setDynamicAttribute('nextIdChanged',ElementDynAttrMutableValueImpl::createInstance(false));
										// else if previous element does not link to current element, then corrects nextId field
										if($previousElement->getFieldValue($nextIdFs->getFieldName()) != $currentElement->getId()) {
											$previousElement->setFieldValue($currentElement->getId(), $nextIdFs->getFieldName());
											// marks element as changed to make it persisted.
											$previousElement->setDynamicAttributeValue('nextIdChanged',true);
											// if previousElement equals current element in Form, then updates nextId in Form to allow drop-down sync
											if($previousElement->getId() == $this->getElement()->getId()) {
												$this->getElement()->setFieldValue($previousElement->getFieldValue($nextIdFs->getFieldName()),$nextIdFs->getFieldName());
											}
										}
										// stores currentElement as new previousElement
										$callbackDFA->setValueInContext('previousElement',$currentElement);
										// pushes previousElement down in stream
										$callbackDFA->writeResultToOutput($previousElement);
									}
								},
								'setEndOfStreamCallback',function($callbackDFA){
									// updates last element if needed
									$previousElement = $callbackDFA->getValueInContext('previousElement');
									$nextIdFs = $callbackDFA->getValueInContext('nextIdFs');
									if(isset($previousElement)) {
										$previousElement->setDynamicAttribute('nextIdChanged',ElementDynAttrMutableValueImpl::createInstance(false));
										if($previousElement->getFieldValue($nextIdFs->getFieldName()) != 'last') {
											$previousElement->setFieldValue('last', $nextIdFs->getFieldName());
											// marks element as changed to make it persisted.
											$previousElement->setDynamicAttributeValue('nextIdChanged',true);
											// if previousElement equals current element in Form, then updates nextId in Form to allow drop-down sync
											if($previousElement->getId() == $this->getElement()->getId()) {
												$this->getElement()->setFieldValue($previousElement->getFieldValue($nextIdFs->getFieldName()),$nextIdFs->getFieldName());
											}
										}
										// pushes previousElement down in stream
										$callbackDFA->writeResultToOutput($previousElement);
									}
								}),
								/* persists elements which have missing link added */
								dfas('ElementDFA','setFieldSelectorList',fsl($nextIdFs),'setMode',3,'setDecisionMethod',function($element,$dataFlowContext){
									if($element->getDynamicAttributeValue('nextIdChanged')) return 1;
									else return 5;
								},'setIgnoreLockedElements',true),
								/* creates drop-down (filters current element) */
								dfas('MapElement2ValueDFA','setElement2ValueFuncExp',fx('ctlIf',fx('neq',fs_e('id'),$this->getElement()->getId()),fx('concat',fx('htmlStartTag','option','value',fs_e('id')),fx('str_replace',"'","\\'",$labelFx),fx('htmlEndTag','option')))),
								dfas('StringBufferDFA')
		)
		);
		// Builds next article drop-down and syncs with current next ID
		if(isset($choosePosition)) {
			$nextId = $this->getElement()->getFieldValue($nextIdFs->getFieldName());
			return '(function(){$("#$$idForm$$_'.$choosePositionFs->getFieldName().'_value_select").append('."'".$choosePosition."'".')'.($nextId?".val('$nextId')":'').';})();';
		}
	}
	
	/**
	 * Calculates the position of the element based on the chosen position in the drop-down.
	 * FuncExp signature : <code>ctlManualElementOrdering(positionFS,nextIdFS,groupLogExp,listFilter=null)</code><br/>
	 * Where arguments are :
	 * - Arg(0) positionFs: FieldSelector|String. The field in the Element holding the position number. Field should by of type Numerics.
	 * - Arg(1) nextIdFs: FieldSelector|String. The field in the Element holding the next Element ID. Field should be of type Strings.
	 * - Arg(2) groupLogExp: LogExp. In group log exp specifying the search space in which manual ordering of elements take place.
	 * - Arg(3) listFilter: ListFilter. An optional ListFilter used to filter the search space on a specific subset of elements.
	 *@see https://resource.wigii.org/#Public/WigiiCfgPattern/item/1911
	 *@return String the choosePosition drop-down value
	 */
	public function ctlManualElementOrdering($args) {
		// checks arguments
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs<3) throw new FuncExpEvalException('ctlManualElementOrdering takes at least 3 arguments positionFs,nextIdFs,groupLogExp', FuncExpEvalException::INVALID_ARGUMENT);
		$positionFs = $args[0];
		if(!($positionFs instanceof FieldSelector)) {
			$positionFs = $this->evaluateArg($args[0]);
			if(empty($positionFs)) throw new FuncExpEvalException('positionFs should be a non null FieldSelector', FuncExpEvalException::INVALID_ARGUMENT);
			if(!($positionFs instanceof FieldSelector)) $positionFs = fs($positionFs);
		}
		$nextIdFs = $args[1];
		if(!($nextIdFs instanceof FieldSelector)) {
			$nextIdFs = $this->evaluateArg($args[1]);
			if(empty($nextIdFs)) throw new FuncExpEvalException('nextIdFs should be a non null FieldSelector', FuncExpEvalException::INVALID_ARGUMENT);
			if(!($nextIdFs instanceof FieldSelector)) $nextIdFs = fs($nextIdFs);
		}
		$groupLogExp = $this->evaluateArg($args[2]);
	
		$currentElement = $this->getElement();
		if($currentElement->isNew()) $currentId = 'new';
		else $currentId = $currentElement->getId();
	
		// gets next element position
		$nextId = $this->getCurrentFieldValue();
		if($nextId!='last') {
			$nextPos = sel($this->getPrincipal(),elementP($nextId,fsl($positionFs)),
					dfasl(dfas('MapElement2ValueDFA','setElement2ValueFuncExp',$positionFs))
			);
		}
		else $nextPos = 10000;
	
		// Prepares ListFilter
		if($nArgs>3) {
			$lf = $this->evaluateArg($args[3]);
			if(!($lf instanceof ListFilter)) throw new FuncExpEvalException('listFilter should be a non null instance of ListFilter', FuncExpEvalException::INVALID_ARGUMENT);
		}
		else $lf = ListFilter::createInstance();
		$lf->setPageSize(1);
		$lf->setDesiredPageNumber(1);
	
		// Prepares FieldSelectorList
		$fsl = $lf->getFieldSelectorList();
		if(!isset($fsl)) {
			$fsl = FieldSelectorListArrayImpl::createInstance();
			$lf->setFieldSelectorList($fsl);
		}
		if(!$fsl->containsFieldSelector($positionFs->getFieldName())) $fsl->addFieldSelectorInstance($positionFs);
		if(!$fsl->containsFieldSelector($nextIdFs->getFieldName())) $fsl->addFieldSelectorInstance($nextIdFs);
	
		// Prepares sorting by position (DESC)
		$lf->setFieldSortingKeyList(fskl(fsk($positionFs->getFieldName(),'value',false)));
	
		// Prepares filter by position max
		$lx = $lf->getFieldSelectorLogExp();
		$lxPos = lxSm($positionFs,$nextPos);
		if(isset($lx)) $lx = lxAnd($lx,$lxPos);
		else $lx = $lxPos;
		$lf->setFieldSelectorLogExp($lx);
	
		// calculates the position if not already done or if next ID changed
		if($currentElement->getFieldValue($positionFs->getFieldName()) == null || $currentElement->getFieldValue($nextIdFs->getFieldName()) != $nextId) {
			// retrieves previous element
			$prevPos = sel($this->getPrincipal(), elementPList($groupLogExp,$lf), dfasl(
					/* updates contentNextId link to current element */
					dfas('ElementSetterDFA','setCalculatedFieldSelectorMap',cfsMap(cfs($nextIdFs,$currentId))),
					dfas('ElementDFA','setFieldSelectorList',fsl($nextIdFs),'setMode','1','setIgnoreLockedElements',true),
					/* and returns position */
					dfas('MapElement2ValueDFA','setElement2ValueFuncExp',$positionFs)
			)
			);
				
			// calculates element position (compacts at the end to let more space at the beginning)
			$currentElement->setFieldValue(0.25*$prevPos+0.75*$nextPos, $positionFs->getFieldName());
			// stores next ID
			$currentElement->setFieldValue($nextId, $nextIdFs->getFieldName());
		}
		return 'last';
	}
}