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
 * Gives the infrastructure to evaluate an Element against funcExp attached to a field.
 * Base class, subclass can extend the FuncExp language.
 * Created by CWE on 17 avr. 10
 */
class ElementEvaluator extends RecordEvaluator
{	
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
	 * Returns the element currently evaluated
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
			return $this->getElement()->getAttribute($fieldSelector);
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
}