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
 * Gives the infrastructure to evaluate a record against funcExp attached to a field.
 * Base class, subclass can extend the FuncExp language.
 * Created by CWE on 17 avr. 10
 * Modified by CWE on 07 mars 2014 to activate by default the FuncExpVM when evaluating a record.
 * Modified by Medair (CWE) on 28.11.2016 to protect against Cross Site Scripting
 * Modified by Medair (CWE) on 07.04.2017 to allow dynamic change of principal only by rootPrincipal
 * Modified by Medair (CWE) on 07.07.2017 to add field update helpers
 */
class RecordEvaluator implements FuncExpEvaluator
{
	private $_debugLogger;
	private $record;
	private $currentField;
	private $principal;
	private $lockedForEvaluation;
	private $calculatingOnFetch;
	private $ignoreReturnVal;
	private $useFuncExpVM = true; // default value defined at creation time, not reseted at each evaluation.
	private $funcExpVM;
	private $callingFuncExpEvaluator;
	private $currentFuncExp;
	private $trm;
	private $translateAllValues;

	// object lifecycle

	public static function createInstance()
	{
		$returnValue = new self();
		$returnValue->reset();
		return $returnValue;
	}
	/**
	 * @param boolean $keepContext if true, then evaluator remains locked and contextual information is not unsetted.
	 */
	public function freeMemory($keepContext=false)
	{
		unset($this->record);
		unset($this->currentField);
		unset($this->principal);
		unset($this->funcExpVM);
		unset($this->callingFuncExpEvaluator);		
		unset($this->currentFuncExp);
		// contextual information that can be kept between two evaluations
		if(!$keepContext) {
			//$this->debugLogger()->write('frees context');
			unset($this->trm);
			unset($this->formExecutor);
			$this->lockedForEvaluation = false;
		}		
	}
	public function reset()
	{
		$this->freeMemory();
		$this->calculatingOnFetch = false;
		$this->ignoreReturnVal = false;
		$this->translateAllValues = false;
	}
	public function isLockedForEvaluation(){
		return $this->lockedForEvaluation;
	}
	/**
	 * Copies the context of a given record evaluator into this evaluator
	 * This method can be used to delegate funcExp calls to other evaluators
	 * Be careful to always call the method freeMemory at the end of the delegation in order to release the lock
	 * @param RecordEvaluator $recordEvaluator a record evaluator instance
	 */
	protected function copyContextFromRecordEvaluator($recordEvaluator) {
		if(is_null($recordEvaluator) || !($recordEvaluator instanceof RecordEvaluator)) throw new RecordException('the evaluator should be an instance of RecordEvaluator', RecordException::INVALID_ARGUMENT);
		$this->record = $recordEvaluator->record;
		$this->currentField = $recordEvaluator->currentField;
		$this->principal = $recordEvaluator->principal;
		$this->lockedForEvaluation = $recordEvaluator->lockedForEvaluation;
		$this->calculatingOnFetch = $recordEvaluator->calculatingOnFetch;
		$this->ignoreReturnVal = $recordEvaluator->ignoreReturnVal;
		$this->useFuncExpVM = $recordEvaluator->useFuncExpVM;
		$this->funcExpVM = $recordEvaluator->funcExpVM;
		$this->formExecutor = $recordEvaluator->formExecutor;
		$this->trm = $recordEvaluator->trm;
		$this->translateAllValues = $recordEvaluator->translateAllValues;
	}

	/**
	 * Initializes the context of this RecordEvaluator
	 * This method is used when we want to evaluate external func exp in the context of a record,
	 * but without touching calculated fields.
	 * Postcondition: after calling this method, the evaluator is locked,
	 * do not forget to call freeMemory when finished using it.
	 * @param Principal $principal the principal for which the evaluation is done
	 * @param Record $record a record instance defining the context of evaluation. Can be null, if we are only interested in FuncExp.
	 */
	public function setContext($principal, $record) {
		//if(is_null($record)) throw new RecordException("record cannot be null", RecordException::INVALID_ARGUMENT);
		if(is_null($principal)) throw new RecordException("principal cannot be null", RecordException::INVALID_ARGUMENT);
		if($this->lockedForEvaluation) throw new RecordException('the record evaluator is already in use', RecordException::INVALID_STATE);
		$this->lockedForEvaluation = true;
		$this->principal = $principal;
		$this->record = $record;
	}

	/**
	 * Changes the current principal to a new one.
	 * This can only be done by the Root principal
	 * @param Principal rootPrincipal instance of root principal to check for authorization
	 * @param Principal $newPrincipal the new principal instance that should replace the current principal
	 */
	public function changePrincipal($rootPrincipal, $newPrincipal) {
	    if($newPrincipal !== $this->principal) {
    	    $this->debugLogger()->logBeginOperation('changePrincipal');
    	    if(!isset($newPrincipal)) throw new RecordException('newPrincipal cannot be null', RecordException::INVALID_ARGUMENT);	    
    	    // checks for authorization
    	    ServiceProvider::getAuthorizationService()->assertPrincipalAuthorized($rootPrincipal, 'RecordEvaluator', 'changePrincipal');
    	    // checks that Principal is valid
    	    ServiceProvider::getAuthenticationService()->assertPrincipalValid($newPrincipal);
    	    $this->principal = $newPrincipal;
    	    $this->debugLogger()->logEndOperation('changePrincipal');
	    }
	}
	
	// dependency injection

	private $funcExpVMClassName;
	/**
	 * Sets the FuncExp VM class to use if property setUseFuncExpVM is true.
	 */
	public function setFuncExpVMClassName($className) {
		$this->funcExpVMClassName = $className;
	}
	/**
	 * Defaults to FuncExpVM
	 */
	protected function getFuncExpVMClassName() {
		if(!isset($this->funcExpVMClassName) || $this->funcExpVMClassName == '') {
			$this->funcExpVMClassName = 'FuncExpVM';
		}
		return $this->funcExpVMClassName;
	}

	/**
	 * Injects a FuncExp VM to use.
	 * @param FuncExpVM $funcExpVM
	 */
	public function setFuncExpVM($funcExpVM) {
		$this->funcExpVM=$funcExpVM;
	}
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("RecordEvaluator");
		}
		return $this->_debugLogger;
	}

	private $formExecutor;
	/**
	 * Injects the current executing FormExecutor that can be used by funcExp implementators
	 * to interact with the GUI context.
	 * This method is called only in the context of saving an element from the GUI.
	 * @param FormExecutor $formExecutor
	 */
	public function setFormExecutor($formExecutor) {
		$this->formExecutor = $formExecutor;
	}
	/**
	 * Returns the current FormExecutor if set, else null.
	 * @return FormExecutor
	 */
	protected function getFormExecutor() {
		return $this->formExecutor;
	}
	
	private $matrixLogExpEvaluator;
	/**
	 * @return RecordMatrixLogExpEvaluator
	 */
	protected function getMatrixLogExpEvaluator() {
		if(!$this->matrixLogExpEvaluator) {
			$this->matrixLogExpEvaluator = RecordMatrixLogExpEvaluator::createInstance();
		}
		return $this->matrixLogExpEvaluator;
	}
	/**
	 * @param RecordMatrixLogExpEvaluator $matrixLogExpEvaluator injects the log exp evaluator to use to evaluate log exp against a record matrix.
	 */
	public function setMatrixLogExpEvaluator($matrixLogExpEvaluator) {
		$this->matrixLogExpEvaluator = $matrixLogExpEvaluator;
	}

	// configuration

	/**
	 * If true, then the caller indicates that this evaluator
	 * is used in the context of fetching a record from the persistence layer.
	 */
	public function setCalculatingOnFetch($bool) {
		$this->calculatingOnFetch = $bool;
	}

	/**
	 * If true, then funcExp returned value will be ignored and
	 * current field value will not be updated
	 * This option can be set inside a FuncExp implementation, before ending the code,
	 * so that the calculation result is ignored.
	 */
	protected function setIgnoreReturnValue($bool) {
		$this->ignoreReturnVal = $bool;
	}

	/**
	 * If true, then delegates the FuncExp evaluation to the FuncExp VM infrastructure,
	 * else evaluates it directly based on the language contained in the evaluator only.
	 * Default is false. A custom subclass of this RecordEvaluator class can be interested
	 * in disabling the usage of the FuncExp VM to force the evaluation to be contained only
	 * in the language described in the custom class.
	 */
	public function setUseFuncExpVM($bool) {
		$this->useFuncExpVM = $bool;
	}

	/**
	 * If true, then all field selectors values are translated using the TemplateRecordManager
	 * @param Boolean $bool
	 */
	public function setTranslateAllValues($bool) {
		$this->translateAllValues = $bool;
	}

	// implementation

	/**
	 * Evaluates all calculated fields in record in principal security context
	 * @param Field $field If field is not null, then evaluates only this field.
	 * @param boolean $keepContext if true, then evaluator remains locked and contextual information is not unsetted. Call freeMemory to unlock et unset context.
	 */
	public function evaluateRecord($principal, $record, $field=null, $keepContext=false)
	{
		if(is_null($record)) throw new RecordException("record cannot be null", RecordException::INVALID_ARGUMENT);
		if(is_null($principal)) throw new RecordException("principal cannot be null", RecordException::INVALID_ARGUMENT);
		$fieldList = $record->getFieldList();
		if(is_null($fieldList)) throw new RecordException("record->fieldList cannot be null", RecordException::INVALID_ARGUMENT);
		$this->lockedForEvaluation = true;
		$this->record = $record;
		$this->principal = $principal;
		// initializes FuncExp VM if activated
		if($this->useFuncExpVM) $this->funcExpVM = $this->getFuncExpVM($principal, $this->getFuncExpVMClassName());
		// evaluates each calculated field
		try {
			if(is_null($field))
			{
				foreach($fieldList->getListIterator() as $f)
				{
					if($f->isCalculated() && !$f->isCalculationDisabled())
					{
						$this->currentField = $f;
						$this->ignoreReturnVal = false;
						$funcExpResult = $this->evaluateFuncExp($f->getFuncExp());
						if(!$this->ignoreReturnVal) $this->processFieldCalculatedResult($f, $funcExpResult);
					}
				}
			}
			// only evaluates one field
			else
			{
				if($field->isCalculated() && !$field->isCalculationDisabled())
				{
					$this->currentField = $field;
					$this->ignoreReturnVal = false;
					$funcExpResult = $this->evaluateFuncExp($field->getFuncExp());
					if(!$this->ignoreReturnVal) $this->processFieldCalculatedResult($field, $funcExpResult);
				}
			}
			if(!$keepContext) $this->lockedForEvaluation = false;
		} catch (Exception $e){
			if(!$keepContext) $this->lockedForEvaluation = false;
			// frees FuncExp VM
			if(isset($this->funcExpVM)) $this->funcExpVM->freeMemory();
			throw $e;
		}
		// frees FuncExp VM
		if(isset($this->funcExpVM)) $this->funcExpVM->freeMemory();
		$this->freeMemory($keepContext);
	}

	/**
	 * Returns an instance of the FuncExpVM given its class name
	 */
	protected function getFuncExpVM($principal, $className) {
		return ServiceProvider::getFuncExpVM($principal, $this, $className);
	}

	/**
	 * Returns the FuncExpVMServiceProvider to use
	 */
	protected function getFuncExpVMServiceProvider() {
		if($this->callingFuncExpEvaluator instanceof FuncExpVM) return $this->callingFuncExpEvaluator->getFuncExpVMServiceProvider();
		else throw new FuncExpEvalException('cannot return the FuncExpVMServiceProvider in this context', FuncExpEvalException::INVALID_STATE);
	}
	
	/**
	 * Processes current Field funcExp evaluation result
	 * If result is not an array, then stores result as field value
	 * else, assumes that the array are matching pairs of (subfieldname => value)
	 * and stores result[subfieldname] as subfield value
	 */
	protected function processFieldCalculatedResult($field, $result)
	{
		if(is_array($result)){
			foreach($result as $subFieldName=>$value){
				$this->record->setFieldValue($value, $field->getFieldName(), $subFieldName);
			}
		} else {
			$this->record->setFieldValue($result, $field->getFieldName());
		}
	}

	public function evaluateFuncExp($funcExp, $caller=null) {
		if(is_null($funcExp)) return null;
		// if isset FuncExp VM and caller is not himself,
		// then delegates evaluation to FuncExp VM
		if(isset($this->funcExpVM) && $this->funcExpVM !== $caller) return $this->funcExpVM->evaluateFuncExp($funcExp);
		elseif(is_null($caller) && isset($this->callingFuncExpEvaluator) && $this->callingFuncExpEvaluator!==$this) return $this->callingFuncExpEvaluator->evaluateFuncExp($funcExp);
		else {
			if($caller instanceof FuncExpEvaluator) $this->callingFuncExpEvaluator = $caller;
			if($funcExp instanceof FuncExp) {											
				$fName = $funcExp->getName();
				if(method_exists($this, $fName)) {
					$this->currentFuncExp = $funcExp;
					$returnVal = $this->$fName($funcExp->getArguments());
					if($this->debugLogger()->isEnabled()) $this->debugLogger()->write($fName." returns ".(is_object($returnVal) ? get_class($returnVal) : $returnVal));
					return $returnVal;
				}
				else throw new RecordException("FuncExp '$fName' does not map to any public method defined in class ".get_class($this), RecordException::INVALID_ARGUMENT);
			}
			if($funcExp instanceof FieldSelector) {
				$returnValue = $this->getFieldValue($funcExp);
				if(!is_object($returnValue) && $this->translateAllValues) return $this->doFormatValueFromFS($funcExp);
				else return $returnValue;
			}
			return $funcExp;
		}
	}

	/**
	 * Formats and translates a FieldSelector value using the TRM
	 * @param FieldSelector $fieldSelector
	 */
	protected function doFormatValueFromFS($fieldSelector) {
		return html_entity_decode($this->getTrm()->formatValueFromFS($fieldSelector, $this->getRecord()), ENT_COMPAT, "UTF-8");
	}

	// current context accessible to FuncExp language implementation

	/**
	 * Returns the record currently evaluated
	 */
	protected function getRecord()
	{
		return $this->record;
	}

	/**
	 * Returns the principal currently evaluating this record
	 */
	protected function getPrincipal()
	{
		return $this->principal;
	}
	/**
	 * @return Boolean returns true if Principal is set in RecordEvaluator context.
	 */
    public function hasPrincipal() {
        return isset($this->principal);
    }
    
	/**
	 * Returns the field currently evaluated
	 */
	protected function getCurrentField()
	{
		return $this->currentField;
	}

	/**
	 * Returns the current value of the field currently beeing evaluated.
	 * subFieldName: the dataType subfield name from which to retrieve the value. If null takes the predefined "value" subfield.
	 */
	protected function getCurrentFieldValue($subFieldName = null)
	{
		if(is_null($this->record)) throw new RecordException("no Record has been attached to RecordEvaluator", RecordException::INVALID_STATE);
		return $this->record->getFieldValue($this->currentField->getFieldName(), $subFieldName);
	}

	/**
	 * Updates current field subfield value in wigii bag with new value
	 */
	protected function updateCurrentFieldSubFieldValue($subFieldName, $newValue)
	{
		if(is_null($this->record)) throw new RecordException("no Record has been attached to RecordEvaluator", RecordException::INVALID_STATE);
		$this->record->setFieldValue($newValue, $this->currentField->getFieldName(), $subFieldName);
	}

	/**
	 * Returns the value of a field stored in underlying wigiiBag given a fieldSelector
	 */
	protected function getFieldValue($fieldSelector)
	{
		if(is_null($fieldSelector)) throw new RecordException("fieldSelector cannot be null", RecordException::INVALID_ARGUMENT);
		if(is_null($this->record)) throw new RecordException("no Record has been attached to RecordEvaluator", RecordException::INVALID_STATE);
		return $this->record->getFieldValue($fieldSelector->getFieldName(), $fieldSelector->getSubFieldName());
	}

	/**
	 * Sets the value of a field stored in underlying wigiiBag given a fieldSelector
	 */
	protected function setFieldValue($fieldSelector, $value) {
		if(is_null($fieldSelector)) throw new RecordException("fieldSelector cannot be null", RecordException::INVALID_ARGUMENT);
		if(is_null($this->record)) throw new RecordException("no Record has been attached to RecordEvaluator", RecordException::INVALID_STATE);
		$this->record->setFieldValue($value, $fieldSelector->getFieldName(), $fieldSelector->getSubFieldName());
	}

	/**
	 * Returns true if the context of evaluation is done during fetching a record
	 * from the persistence layer
	 */
	public function isCalculatingOnFetch() {
		return $this->calculatingOnFetch;
	}

	private function getTrm() {
		if(!isset($this->trm)) {
			$this->trm = TemplateRecordManager::createInstance();
		}
		return $this->trm;
	}

	// Evaluation


	/**
	 * Returns the number of arguments stored in the args array
	 * @param $args the args array, can be null
	 */
	protected function getNumberOfArgs($args) {
		if(is_null($args)) return 0;
		else if(is_array($args)) {
			return count($args);
		}
		else throw new RecordException("funcExp args should be an array", RecordException::INVALID_ARGUMENT);
	}

	/**
	 * Evaluates function arg and returns its value
	 */
	protected function evaluateArg($arg) {return $this->evaluateFuncExp($arg);}

	/**
	 * This function proposes a default value if the field has no current value,
	 * or if the funcExp has no argument as a calculated default value from config.
	 * @param $funExpArgs the funcExp arg array
	 * @param $defaultValue the proposed default value as ultimate choice
	 */
	protected function proposeDefaultValueIfNotSet($funExpArgs, $defaultValue) {
		// 1. current field has already a value ?
		$val = $this->getCurrentFieldValue();
		// 2. FuncExp has a default value as argument ?
		if(is_null($val) && $this->getNumberOfArgs($funExpArgs) > 0) {
			$val = $this->evaluateArg($funExpArgs[0]);
		}
		// 3. Propose default value
		if(is_null($val)) {
			$val = $defaultValue;
		}
		return $val;
	}

	/**
	 * This function keeps the current field value if set,
	 * else evaluates the first funcExp argument if exists, else returns null.
	 * @param $funExpArgs the funcExp arg array
	 */
	protected function getCurrentValueOrEvaluateFirstArg($funcExpArgs) {
		return $this->proposeDefaultValueIfNotSet($funExpArgs, null);
	}
	
	/**
	 * Asserts that current FuncExp beeing evaluated does not originate from public space.
	 * @throws FuncExpEvalException with code FORBIDDEN (403) if current FuncExp is marked as public.
	 */
	protected function assertFxOriginIsNotPublic() {
		if(isset($this->currentFuncExp) && $this->currentFuncExp->isOriginPublic()) throw new FuncExpEvalException("FuncExp '".$this->currentFuncExp->getName()."' originates from public space and is not authorized to be executed.", FuncExpEvalException::FORBIDDEN);
	}
	
	/**
	 * @return Boolean returns true if current FuncExp beeing evaluated originates from public space, null if unknown, false if not.
	 */
	public function isFxOriginPublic() {
		if($this->currentFuncExp instanceof FuncExp) return $this->currentFuncExp->isOriginPublic();
	}





	// root FuncExp language (can be extended in subclasses)


	/**
	 * implodes values
	 * first argument in array is the implode separator,
	 * then other arguments in array are the imploded values
	 */
	public function implode($args)
	{
		if($this->getNumberOfArgs($args) < 1) throw new RecordException("args should have at least one value which is the separator", RecordException::INVALID_ARGUMENT);
		$returnValue = ''; $i = 0; $s = ", ";
		foreach($args as $v)
		{
			if($i == 0)
			{
				$s = $this->evaluateArg($v);
				$i++;
			}
			else
			{
				$tv = $this->evaluateArg($v);
				if($tv!=null){
					if(is_array($tv)) $tv = implode($s, $tv);
					if($returnValue==null){
						$returnValue = $tv;
					} else {
						$returnValue .= $s.$tv;
					}
					$i++;
				}
			}
		}
		return $returnValue;
	}

	/**
	 * Concatenates the arguments, returns null if no arg
	 */
	public function concat($args){
		$result = null;
		if($this->getNumberOfArgs($args) > 0) {
			foreach($args as $arg){
				$r = $this->evaluateArg($arg);
				if($r != null) $result .= $r;
			}
		}
		return $result;
	}

	/**
	 * Merges some lists of values defined as strings separated by a given separator into one string
	 * FuncExp signature : <code>merge(separator, list1, list2, ..., listN)</code><br/>
	 * Where arguments are :
	 * - Arg(0) separator : the separator used to split the input lists and to implode again the resulting list
	 * - Arg(1..n) listI: strings containing a list of values separated by the given separator
	 * @return String the resulting string defined by merge of list1 to listN. (concatenation + remove duplicates).
	 */
	public function merge($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new RecordException("args should have at least one value which is the separator", RecordException::INVALID_ARGUMENT);
		$sep = $this->evaluateArg($args[0]);
		$returnValue = array();
		for($i = 1; $i < $nArgs; $i++) {
			$a = $this->evaluateArg($args[$i]);
			if(!empty($a)) $a = explode($sep, $a);
			if(!empty($a)) $returnValue = array_merge($returnValue, array_combine($a, $a));			
		}
		if(!empty($returnValue)) return implode($sep, $returnValue);
	}
	
	/**
	 * Returns a string with a line separator repeated n times
	 * FuncExp signature : <code>txtLineSep(multiplier)</code><br/>
	 * Where arguments are :
	 * - Arg(0) multiplier : optional argument. Evaluates to a positive integer which indicates the number of time
	 * the line separator must be repeated. Default is one time.
	 */
	public function txtLineSep($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) $multiplier = 1;
		else $multiplier = $this->evaluateArg($args[0]);
		if($multiplier <= 0) throw new FuncExpEvalException("The multiplier must be a postive integer", FuncExpEvalException::INVALID_ARGUMENT);
		return str_repeat("\n", $multiplier);
	}

	/**
	 * Returns a string with a tab repeated n times
	 * FuncExp signature : <code>txtTab(multiplier)</code><br/>
	 * Where arguments are :
	 * - Arg(0) multiplier : optional argument. Evaluates to a positive integer which indicates the number of time
	 * the tab must be repeated. Default is one time.
	 */
	public function txtTab($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) $multiplier = 1;
		else $multiplier = $this->evaluateArg($args[0]);
		if($multiplier <= 0) throw new FuncExpEvalException("The multiplier must be a postive integer", FuncExpEvalException::INVALID_ARGUMENT);
		return str_repeat("\t", $multiplier);
	}

	/**
	 * Returns a double quote character
	 * @return string
	 */
	public function txtQuot($args) {
		return '"';
	}
	
	/**
	 * Returns a string representing a date in a Wigii compatible format (Y-m-d H:i:s).
	 * For general formatting use the func exp date (defined in PhpStdFL).
	 * FuncExp signature : <code>txtDate(timestamp)</code><br/>
	 * Where arguments are :
	 * - Arg(0) timestamp : optional argument. Evaluates to a positive integer which indicates a timestamp. If null, then gets the php current time.
	 */
	public function txtDate($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) $timestamp = time();
		else $timestamp = $this->evaluateArg($args[0]);
		return date('Y-m-d H:i:s', $timestamp);
	}

	/**
	 * Formats a value given its field selector.
	 * This functions uses the TemplateRecordManager.
	 * First argument should be a FieldSelector
	 * Second argument is optional and can evaluate to a valid language code
	 * used for translation
	 */
	public function formatValueFromFS($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new RecordException("formatValueFromFS takes at least one parameter which is a non null FieldSelector", RecordException::INVALID_ARGUMENT);
		// gets field selector
		$fs = $args[0];
		if(!($fs instanceof FieldSelector)) {
			$fs = $this->evaluateArg($fs);
			if(!($fs instanceof FieldSelector)) throw new RecordException("formatValueFromFS takes at least one parameter which is a non null FieldSelector", RecordException::INVALID_ARGUMENT);
		}
		// gets language if set
		if($nArgs > 1) $lang = $this->evaluateArg($args[1]);
		else $lang = null;

		// gets the field selector value and translates it.
		if(isset($lang)) {
			$ts = ServiceProvider::getTranslationService();
			$previousLang = $ts->getLanguage();
			if($lang != $previousLang) $ts->setLanguage($lang);
		}
		$returnValue = $this->doFormatValueFromFS($fs);
		if(isset($lang) && isset($previousLang) && $lang != $previousLang) {
			$ts->setLanguage($previousLang);
		}
		return $returnValue;
	}

	/**
	 * Returns the translation of the label of a field as defined in the configuration file
	 * FuncExp signature : <code>txtLabel(fs)</code><br/>
	 * Where arguments are :
	 * - Arg(0) fs : FieldSelector. A given field in the Record.
	 * @return String the translated label of the field
	 */
	public function txtLabel($args) {
	    $nArgs = $this->getNumberOfArgs($args);
	    if($nArgs<1 || !($args[0] instanceof FieldSelector)) throw new RecordException('txtLabel takes one argument which a FieldSelector pointing to a valid Field in the Record', RecordException::INVALID_ARGUMENT);
	    $field = $this->getRecord();
	    if(!$field) throw new RecordException("no Record has been attached to RecordEvaluator", RecordException::INVALID_STATE);
	    $field = $field->getFieldList()->getField($args[0]->getFieldName());
	    return ServiceProvider::getTranslationService()->t($this->getPrincipal(), $field->getFieldName(),$field->getXml());
	}
	
	/**
	 * Returns the first argument which is not null,
	 * returns null if no arg or all are null
	 */
	public function first($args){
		if($this->getNumberOfArgs($args) > 0) {
			foreach($args as $arg){
				$r = $this->evaluateArg($arg);
				if($r != null) return $r;
			}
		}
		else return null;
	}

	/**
	 * Returns the biggest argument (works with strings and numbers, uses php max function)
	 * Returns null if no arg
	 */
	public function max($args){
	    if($this->getNumberOfArgs($args) > 0) {
	        $temp = array();
	        foreach($args as $arg){
	            $r = $this->evaluateArg($arg);
	            if($r === 0 || $r != null) $temp[]=$r;
	        }
	        if($temp==null) return null;
	        return max($temp);
	    }
	    else return null;
	}
	
	/**
	 * Returns the smallest argument (works with string and numbers, uses php min function)
	 * Returns null if no arg
	 */
	public function min($args){
	    if($this->getNumberOfArgs($args) > 0) {
	        $temp = array();
	        foreach($args as $arg){
	            $r = $this->evaluateArg($arg);
	            if($r === 0 || $r != null) $temp[]=$r;
	        }
	        if($temp==null) return null;
	        return min($temp);
	    }
	    else return null;
	}
	
	/**
	 * Returns the number of args wich evaluate to something different from null
	 * Return 0 if no arg.
	 */
	public function count($args){
	    if($this->getNumberOfArgs($args) > 0) {
	        $c = 0;
	        foreach($args as $arg) {
	            $r = $this->evaluateArg($arg);
	            if($r === 0 || $r != null) $c++;
	        }
	        return $c;
	    }
	    else return 0;
	}

	/**
	 * Given a date in format dd.mm.yyyy, returns mm.dd
	 * This function allows to sort or group based only on the birthday and not on the year
	 */
	public function calculateBirthday($args){
		if($this->getNumberOfArgs($args) < 1) throw new RecordException("calculateBirthday should have 1 date parameter");
		$r = $this->evaluateArg($args[0]);
		if($r == null) return null;
		return substr($r, 3, 2).".".substr($r, 0, 2);
	}

	/**
	 * Returns evaluation of first arg if all other arguments evaluates to null, else returns null
	 */
	public function doOnNotNull($args){
		if($this->getNumberOfArgs($args) < 2) throw new RecordException("args should have at least 2 parameters");
		$returnValue = ''; $i = 0; $allIsFilled = true;
		foreach($args as $v)
		{
			if($i == 0)
			{
				$returnValue = $this->evaluateArg($v);
				$i++;
			}
			else
			{
				if($v == null || $this->evaluateArg($v) == null){
					$allIsFilled = false;
					break;
				}
				$i++;
			}
		}
		if(!$allIsFilled){
			return null;
		}
		return $returnValue;
	}

	/**
	 * Returns arg[0]/arg[1]*100
	 * Returns null if division by zero
	 */
	public function percentage($args){
		if($this->getNumberOfArgs($args) < 2) throw new RecordException("percentage should have at least 2 parameters", RecordException::INVALID_ARGUMENT);
		//if the evaluateArg is numeric it could have 1000 separators
		$arg1 = str_replace("'", "", $this->evaluateArg($args[1]));
		if($arg1!=0){
			return (str_replace("'", "", $this->evaluateArg($args[0]))/$arg1)*100;
		}
		else return null;
	}

	/**
	 * Sums the arguments, return 0 if no arg
	 */
	public function sum($args){
		if($this->getNumberOfArgs($args) > 0) {
			$total = 0;
			foreach($args as $arg){
				$v =  $this->evaluateArg($arg);
				if(is_array($v)) $v = array_sum($v);
				else $v = str_replace("'", "", $v);
				$total += $v;
			}
			return $total;
		}
		else return 0;
	}
	/**
	 * Multiply the arguments, return 0 if no arg
	 */
	public function product($args){
		if($this->getNumberOfArgs($args) > 0) {
			$total = 1;
			foreach($args as $arg){
				$v = $this->evaluateArg($arg);
				if(is_array($v)) $v = array_product($v);
				else $v = str_replace("'", "", $v);
				$total *= $v;
			}
			return $total;
		}
		else return 0;
	}
	/**
	 * Returns arg[0]/arg[1]
	 * Returns NA if division by zero
	 */
	public function div($args){
		if($this->getNumberOfArgs($args) < 2) throw new RecordException("div should have at least 2 parameters", RecordException::INVALID_ARGUMENT);
		//if the evaluateArg is numeric it could have 1000 separators
		$arg1 = str_replace("'", "", $this->evaluateArg($args[1]));
		if($arg1!=0){
			return (str_replace("'", "", $this->evaluateArg($args[0]))/$arg1);
		}
		else return "NA";
	}
	/**
	 * Returns -arg[0]
	 */
	public function minus($args) {
		if($this->getNumberOfArgs($args)>0) {
			return -($this->evaluateArg($args[0]));
		}
		else return 0;
	}
	
	/**
	 * Always returns true
	 */
	public function logTrue($args) {return true;}

	/**
	 * Always returns false
	 */
	public function logFalse($args) {return false;}

	/**
	 * Returns true if all arguments are equal
	 * A synonym to eq FuncExp
	 */
	public function equal($args) {
		return $this->eq($args);
	}
	/**
	 * Returns true if all arguments are equal
	 * A synonym to equal FuncExp
	 */
	public function eq($args) {
		if($this->getNumberOfArgs($args) < 2) throw new RecordException("For equality, the number of arguments should be at least 2", RecordException::INVALID_ARGUMENT);
		$first = true;
		foreach($args as $arg) {
			if($first) {
				$firstVal = $this->evaluateArg($arg);
				$first = false;
			}
			elseif($this->evaluateArg($arg) != $firstVal) return false;
		}
		return true;
	}

	/**
	 * Returns true if not all arguments are equal
	 * A synonym to not(equal) FuncExp
	 */
	public function neq($args) {
		return !$this->eq($args);
	}
	
	/**
	 * Returns true if first argument is null or equal to second
	 */
	public function nullOrEq($args) {
		$nArgs=$this->getNumberOfArgs($args);
		if($nArgs<2) throw new RecordException('nullOrEq takes two arguments for equality comparison', RecordException::INVALID_ARGUMENT);
		if($this->evaluateFuncExp(fx('isNull',$args[0]))) return true;
		else return $this->eq($args);
	}
	/**
	 * Returns true if first argument is not null and not equal to second
	 */
	public function notNullAndNotEq($args) {
		$nArgs=$this->getNumberOfArgs($args);
		if($nArgs<2) throw new RecordException('notNullAndNotEq takes two arguments for equality comparison', RecordException::INVALID_ARGUMENT);
		if($this->evaluateFuncExp(fx('isNull',$args[0]))) return false;
		else return $this->neq($args);
	}
	
	
	/**
	 * Returns true if first argument is smaller than all next arguments.
	 * A synonym to sm FuncExp
	 */
	public function smaller($args) {
		return $this->sm($args);
	}
	/**
	 * Returns true if first argument is smaller than all next arguments.
	 * A synonym to smaller FuncExp
	 */
	public function sm($args) {
		if($this->getNumberOfArgs($args) < 2) throw new RecordException("For comparison, the number of arguments should be at least 2", RecordException::INVALID_ARGUMENT);
		$first = true;
		foreach($args as $arg) {
			if($first) {
				$firstVal = $this->evaluateArg($arg);
				$first = false;
			}
			elseif($firstVal >= $this->evaluateArg($arg)) return false;
		}
		return true;
	}

	/**
	 * Returns true if first argument is greater than all next arguments.
	 * A synonym to gr FuncExp
	 */
	public function greater($args) {
		return $this->gr($args);
	}
	/**
	 * Returns true if first argument is greater than all next arguments.
	 * A synonmy to greater FuncExp
	 */
	public function gr($args) {
		if($this->getNumberOfArgs($args) < 2) throw new RecordException("For comparison, the number of arguments should be at least 2", RecordException::INVALID_ARGUMENT);
		$first = true;
		foreach($args as $arg) {
			if($first) {
				$firstVal = $this->evaluateArg($arg);
				$first = false;
			}
			elseif($firstVal <= $this->evaluateArg($arg)) return false;
		}
		return true;
	}

	/**
	 * Returns true if first argument is smaller or equal to all next arguments.
	 * A synonym of smeq FuncExp
	 */
	public function smallerOrEqual($args) {
		return $this->smeq($args);
	}
	/**
	 * Returns true if first argument is smaller or equal to all next arguments.
	 * A synonym of smallerOrEqual FuncExp
	 */
	public function smeq($args) {
		if($this->getNumberOfArgs($args) < 2) throw new RecordException("For comparison, the number of arguments should be at least 2", RecordException::INVALID_ARGUMENT);
		$first = true;
		foreach($args as $arg) {
			if($first) {
				$firstVal = $this->evaluateArg($arg);
				$first = false;
			}
			elseif($firstVal > $this->evaluateArg($arg)) return false;
		}
		return true;
	}

	/**
	 * Returns true if first argument is greater or equal to all next arguments.
	 * A synonym of greq FuncExp.
	 */
	public function greaterOrEqual($args) {
		return $this->greq($args);
	}
	/**
	 * Returns true if first argument is greater or equal to all next arguments.
	 * A synonym of greaterOrEqual FuncExp.
	 */
	public function greq($args) {
		if($this->getNumberOfArgs($args) < 2) throw new RecordException("For comparison, the number of arguments should be at least 2", RecordException::INVALID_ARGUMENT);
		$first = true;
		foreach($args as $arg) {
			if($first) {
				$firstVal = $this->evaluateArg($arg);
				$first = false;
			}
			elseif($firstVal < $this->evaluateArg($arg)) return false;
		}
		return true;
	}

	/**
	 * Returns true if the first argument is empty.<br/>
	 * FuncExp signature is: <code>isNull(value, strict=false)</code><br/>
	 * Where arguments are :
	 * - Arg(0) value: The value for which to evaluate if it is empty.
	 * - Arg(1) strict: optional boolean. If true, then forces strict comparison with null. Returns true if the PHP function is_null(value) returns true. Default to false.
	 * @return boolean 
	 */
	public function isNull($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException("isNull function takes at least one argument which is the value", FuncExpEvalException::INVALID_ARGUMENT);
		$value = $this->evaluateArg($args[0]);
		if($nArgs > 1) {
			$strict = $this->evaluateArg($args[1]);
		}
		else $strict = false;
		if($strict) return is_null($value);
		elseif(is_array($value)) {
			if(empty($value)) return true;
			elseif(count($value)==1 && $value['']==='') return true;
			else return false;
		}
		else return empty($value);
	}
	/**
	 * Returns true if the first argument is not empty.<br/>
	 * FuncExp signature is: <code>isNotNull(value, strict=false)</code><br/>
	 * Where arguments are :
	 * - Arg(0) value: The value for which to evaluate if it is empty.
	 * - Arg(1) strict: optional boolean. If true, then forces strict comparison with null. Returns true if the PHP function isset(value) returns true. Default to false.
	 * @return boolean
	 */
	public function isNotNull($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException("isNull function takes at least one argument which is the value", FuncExpEvalException::INVALID_ARGUMENT);
		$value = $this->evaluateArg($args[0]);
		if($nArgs > 1) {
			$strict = $this->evaluateArg($args[1]);
		}
		else $strict = false;
		if($strict) return isset($value);
		elseif(is_array($value)) {
			if(empty($value)) return false;
			elseif(count($value)==1 && $value['']==='') return false;
			else return true;
		}
		else return !empty($value);	
	}
	
	/**
	 * Executes a logical AND on all arguments
	 * Stops on first arg which evaluates to false
	 */
	public function logAnd($args) {
		if($this->getNumberOfArgs($args) < 2) throw new RecordException("For logical AND, the number of arguments should be at least 2", RecordException::INVALID_ARGUMENT);
		foreach($args as $arg) {
			if(!$this->evaluateArg($arg)) return false;
		}
		return true;
	}

	/**
	 * Executes a logical OR on all arguments
	 * Stops on first arg which evaluates to true
	 */
	public function logOr($args) {
		if($this->getNumberOfArgs($args) < 2) throw new RecordException("For logical OR, the number of arguments should be at least 2", RecordException::INVALID_ARGUMENT);
		foreach($args as $arg) {
			if($this->evaluateArg($arg)) return true;
		}
		return false;
	}

	/**
	 * Executes a logical NOT on first argument
	 */
	public function logNot($args) {
		if($this->getNumberOfArgs($args) < 1) throw new RecordException("For logical NOT, the number of argument should be 1", RecordException::INVALID_ARGUMENT);
		return !$this->evaluateArg($args[0]);
	}

	/**
	 * Evaluates all arguments in sequence
	 * and returns the value of the last argument or null if no args
	 */
	public function ctlSeq($args) {
		if($this->getNumberOfArgs($args) > 0) {
			$returnValue = null;
			foreach($args as $arg) {
				$returnValue = $this->evaluateArg($arg);
			}
			return $returnValue;
		}
		else return null;
	}

	/**
	 * Instructs the RecordEvaluator to ignore the last return value and do not update the underlying field value.
	 * FuncExp signature : <code>ctlIgnoreReturnValue(f1, f2, ...)</code><br/>
	 * Where arguments are :
	 * - Arg(0..n) fI: optional list of func exp to be executed in sequence. 
	 * Example: ctlSeq(f1, f2, ctlIgnoreReturnValue()) is equivalent to ctlIgnoreReturnValue(f1, f2)
	 */
	public function ctlIgnoreReturnValue($args) {
		if($this->getNumberOfArgs($args) > 0) {
			foreach($args as $arg) {
				$this->evaluateArg($arg);
			}
		}
		$this->setIgnoreReturnValue(true);
	}

	/**
	 * Evaluates first argument, if true, then evaluates second argument and returns the result,
	 * else evaluates the third argument and returns the result.
	 * If third argument is not defined, then returns null.
	 */
	public function ctlIf($args) {
		$n = $this->getNumberOfArgs($args);
		if($n < 2) throw new RecordException("For condition, the number of arguments should be at least 2", RecordException::INVALID_ARGUMENT);
		if($this->evaluateArg($args[0])) return $this->evaluateArg($args[1]);
		elseif($n >= 3) return $this->evaluateArg($args[2]);
		else return null;
	}

	/**
	 * Evaluates first argument, if true, then evaluates all next arguments in sequence,
	 * and this as long as first argument evaluates to true
	 * @return value of last evaluated arg
	 */
	public function ctlWhile($args) {
		$n = $this->getNumberOfArgs($args);
		if($n < 2) throw new RecordException("For while loop, the number of arguments should be at least 2", RecordException::INVALID_ARGUMENT);
		$returnValue = null;
		while($this->evaluateArg($args[0])) {
			for($i = 1; $i < $n; $i++) {
				$returnValue = $this->evaluateArg($args[$i]);
			}
		}
		return $returnValue;
	}

	/**
	 * Asserts that a given list of functions evaluate to true. 
	 * For each function which evaluates to false, then adds an error to the current field with the specified message.
	 * FuncExp signature : <code>ctlAssert(f1, err1, f2, err2, f3, err3, ...)</code><br/>
	 * Where arguments are :
	 * - Arg(0,2,4,...) fI: The functions to evaluate and check that return value is true,
	 * - Arg(1,3,5,...) errI: The error message to add to the current field if assertion fails. 
	 * The message is checked for translation using the TemplateRecordManager.
	 * @return boolean true if all assertions are valid, false if at least one assertion fails.
	 */
	public function ctlAssert($args) {
		$n = $this->getNumberOfArgs($args);
		$returnValue = true;
		if($n > 0) {
			$i = 0;
			while($i < $n) {
				// evaluates assertion
				$f = $this->evaluateArg($args[$i]);
				$fi = $i;
				$i++;
				// if assertion failed, then gets message
				if(!$f) {
					if($i < $n) $message = $this->evaluateArg($args[$i]);
					else $message = null;
					// gets an eventual translation of the message
					if(!empty($message)) $message = $this->getTrm()->t($message);
					else {
						$message = $args[$fi];
						if($message instanceof FieldSelector || $message instanceof FuncExp || is_array($message)) {
							$message = TechnicalServiceProvider::getFieldSelectorFuncExpParser()->funcExpToString($message);
						}
						elseif(is_object($message)) $message = get_class($message);
						$message = 'Assertion '.($fi+1).' failed: '.$message.' did not evaluate to true.';
					}
					// adds error to current field
					$form = $this->getFormExecutor();
					if(isset($form)) {
						$form->addErrorToField($message, $this->getCurrentField()->getFieldName());
					}
					$returnValue = false;
				}
				$i++;
			}
		}
		return $returnValue;
	}	
	
	/**
	 * Sets a calculated value to a field only if check returns true, else displays error message.
	 * FuncExp signature : <code>ctlCheck(calculatedValue, checkExp, errorMessage)</code><br/>
	 * Where arguments are :
	 * - Arg(0) calculatedValue: Any. The calculated value to set to the field
	 * - Arg(1) checkExp: Boolean. An expression which should evaluate to true if check is OK, else false. It can contain a FieldSelector pointing to current field, which will return the new calculated value.
	 * - Arg(2) errorMessage: String. Evaluates to a String that will be displayed as an error message if checkExp returns false.
	 * @return Any the calculated value to set to the field
	 */
	public function ctlCheck($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 3) throw new FuncExpEvalException("ctlCheck func exp takes three arguments: the calculatedValue, checkExp and errorMessage", FuncExpEvalException::INVALID_ARGUMENT);
		// udpates current value with new calculated value
		$returnValue = $this->evaluateArg($args[0]);
		$this->updateCurrentFieldSubFieldValue(null, $returnValue);
		// verifies the condition
		if(!$this->evaluateArg($args[1])) {
			// if not true, then shows an error message
			$message = $this->evaluateArg($args[2]);
			// gets an eventual translation of the message
			if(!empty($message)) $message = $this->getTrm()->t($message);				
			
			$form = $this->getFormExecutor();
			if(isset($form)) {
				$form->addErrorToField($message, $this->getCurrentField()->getFieldName());
			}
			else throw new FuncExpEvalException($message, FuncExpEvalException::INVALID_RETURN_VALUE);
		}
		return $returnValue;
	}
	
	/**
	 * Returns false if attached FormExecutor has some errors, else returns true.
	 * FuncExp signature : <code>ctlCheckNoError()</code><br/>
	 * @return boolean false if attached FormExecutor has some errors, else true.
	 */
	public function ctlCheckNoError($args=null) {
		$form = $this->getFormExecutor();
		if(isset($form) && $form->hasError()) return false;
		else return true;
	}
	/**
	 * Tries to evaluate given argument, catches any exception and displays message as an error attached to currrent field
	 * FuncExp signature : <code>ctlException2FieldError(calculatedValue)</code><br/>
	 * Where arguments are :
	 * - Arg(0) calculatedValue: Any. The calculated value to return after evaluation
	 * @return Any the calculated value or null if an error occured. Call FuncExp ctlCheckNoError to check if attached FormExecutor has some errors.
	 */
	public function ctlException2FieldError($args) {
	    $nArgs = $this->getNumberOfArgs($args);
	    if($nArgs < 1) throw new RecordException('ctlException2FieldError func exp takes one argument: the expression to evaluate and for which to catch any exceptions', RecordException::INVALID_ARGUMENT);
	    $form = $this->getFormExecutor();
	    if(isset($form)) {
	        // evaluates expression
	        try { return $this->evaluateArg($args[0]); }
	        // catches any exception
	        catch(Exception $e) {
	            // and displays message as Field error
	            if($e instanceof ServiceException) $e = $e->getWigiiRootException();
	            $form->addErrorToField($e->getMessage(), $this->getCurrentField()->getFieldName());
	        }	        
	    }
	    // else if no attached FormExecutor then evaluates the expression "as-is"
	    else return $this->evaluateArg($args[0]);
	}
	
	/**
	 * Adds an error message to a field in the record.
	 * FuncExp signature : <code>ctlAddError(fieldName, errorMessage)</code><br/>
	 * Where arguments are :
	 * - Arg(0) fieldName: String|FieldSelector. The name of the field to which to add an error message
	 * - Arg(1) errorMessage: String. Evaluates to a String that will be displayed as an error message. The message is automatically translated if needed.	 
	 */
	public function ctlAddError($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 2) throw new RecordException('ctlAddError func exp takes two arguments: the fieldName to which to add the error, and the errorMessage to display.', RecordException::INVALID_ARGUMENT);
		
		if($args[0] instanceof FieldSelector) $fieldName=$args[0]->getFieldName();
		else $fieldName = $this->evaluateArg($args[0]);
		if(!$this->getRecord()->getFieldList()->doesFieldExist($fieldName)) throw new RecordException("fieldName '".$fieldName."' is not a valid field in the record", RecordException::INVALID_ARGUMENT);

		$errorMessage = $this->evaluateArg($args[1]);
		// gets an eventual translation of the message
		if(!empty($errorMessage)) $errorMessage = $this->getTrm()->t($errorMessage);
		
		$form = $this->getFormExecutor();
		if(isset($form)) {
			$form->addErrorToField($errorMessage, $fieldName);
		}
		else throw new FuncExpEvalException($errorMessage, FuncExpEvalException::ASSERTION_FAILED);
	}
	
	/**
	 * Adds a comment to a field of type Blobs 
	 * FuncExp signature : <code>ctlAddComment(fieldName, comment)</code><br/>
	 * Where arguments are :
	 * - Arg(0) fieldName: String|FieldSelector. The name of the field to which to add a comment. Should be of type Blobs.
	 * - Arg(1) comment: String. Evaluates to a String that will added as a comment.
	 */
	public function ctlAddComment($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 2) throw new RecordException('ctlAddComment func exp takes two arguments: the fieldName to which to add a comment, and the comment string.', RecordException::INVALID_ARGUMENT);
		
		if($args[0] instanceof FieldSelector) $fieldName=$args[0]->getFieldName();
		else $fieldName = $this->evaluateArg($args[0]);
		$comment = $this->evaluateArg($args[1]);
		
        ServiceProvider::getWigiiBPL()->elementAddComment($this->getPrincipal(), $this, wigiiBPLParam(
            'element',$this->getRecord(),
            'fieldName', $fieldName,
            'comment', $comment
        ));        
		
		// if multiple edit then adds FieldSelector to FieldSelectorList for persistence
		if($this->getFormExecutor() instanceof EditMultipleElementFormExecutor) {
			$fsl=$this->getFormExecutor()->getFieldSelectorListForUpdate();
			if(!$fsl->containsFieldSelector($fieldName)) $fsl->addFieldSelector($fieldName);
		}		
	}
	
	/**
	 * Runs a step into a given state machine. The state machine is described as a Field holding the actual state and a list of conditional actions and new state calculations.
	 * FuncExp signature : <code>ctlStateMachine(stateField, newStateFx1, newStateFx2)</code><br/>
	 * Where arguments are :
	 * - Arg(0) stateField: String|FieldSelector. The name of the field or FieldSelector holding the state of the state machine. (Can be of type Strings,Numerics or Attributs).
	 * - Arg(1..n) newStateFxI: FuncExp. A list of FuncExp which conditionally calculates the new state. 
	 * The state machine executes in sequence each of the given newStateFxI and stops after the first one returning a non null value. The first non null value is the calculated new state. 
	 * To conditionally calculate a new state, use ctlCondSeq or ctlSeqIf or ctlIf as newStateFxI.
	 * To execute some actions on a transition as well as calculating new state, put all the actions into a sequence and put the new state calculation as the last step.
	 * @example ctlStateMachine(logState,
					ctlSeqIf(logAnd(eq(logState,"Edition"), eq(Form_Complete, "Submitted")),"Submitted"),
					ctlSeqIf(logAnd(eq(logState,"Submitted"), eq(Review_Complete, "Approved")), "Approved"),
					ctlSeqIf(logAnd(eq(logState,"Approved"), eq(Form_Complete, "Finalized")), action1(), action2(), "Finalized")
				)
	 * @return Boolean returns true if state has changed, else false.
	 */
	public function ctlStateMachine($args) {
		$nArgs=$this->getNumberOfArgs($args);
		if($nArgs<1) throw new FuncExpEvalException('ctlStateMachine takes at least one argument which is the state field', FuncExpEvalException::INVALID_ARGUMENT);
		// gets state FieldSelector
		$stateField=$args[0];
		if(!($stateField instanceof FieldSelector)) $stateField=fs($this->evaluateArg($args[0]));
		// evaluates each transition
		$returnValue=false;
		if($nArgs>1) {
			$newState=null;
			for($i=1;$i<$nArgs;$i++) {
				$newState=$this->evaluateArg($args[$i]);
				// breaks if newState is calculated
				if(isset($newState)) break;
			}
			// if new state is calculated and changed then updates it
			if(isset($newState) && $newState != $this->getFieldValue($stateField)) {
				$this->setFieldValue($stateField, $newState);
				$returnValue=true;
			}		
		}
		return $returnValue;
	}
	
	/**
	 * An expression compatible with the Element_beforeDeleteExp parameter. 
	 * By default blocks any deletion of elements by displaying a custom message, except if a given expression evaluates to true. 
	 * FuncExp signature : <code>ctlAuthorizeDeletion(exp,msg)</code><br/>
	 * Where arguments are :
	 * - Arg(0) exp: FuncExp|LogExp. A Functional Expression which should evaluate to a boolean or a Logical Expression evaluated against the Record candidate to be deleted.
	 * If expression returns true, then deletion is possible, else deletion is blocked and msg is displayed to end user.
	 * - Arg(1) msg: String. The message displayed to the user if deletion is blocked.
	 * @return stdClass returns a StdClass of the form {okToDelete:true|false,message:msg} compatible with the Element_beforeDeleteExp parameter
	 */
	public function ctlAuthorizeDeletion($args) {
	    $nArgs=$this->getNumberOfArgs($args);
	    if($nArgs<2) throw new FuncExpEvalException('ctlAuthorizeDeletion takes at least two arguments which are the expression to authorize deletion and the custom message displayed if deletion is blocked.', FuncExpEvalException::INVALID_ARGUMENT);
	    // Checks if element can be deleted
	    $okToDelete = $this->evaluateArg($args[0]);
	    // if expression is an instance of LogExp then evaluates it against the Record
	    if($okToDelete instanceof LogExp) {
	        $okToDelete = TechnicalServiceProvider::getFieldSelectorLogExpRecordEvaluator()->evaluate($this->getRecord(), $okToDelete);
	    }
	    // if not Ok to delete, then displays custom message
	    if(!$okToDelete) $returnValue = (object)array("okToDelete"=>false,"message"=>$this->evaluateArg($args[1]));	    
	    else $returnValue = (object)array("okToDelete"=>true,"message"=>null);
	    return $returnValue;
	}
	
	/**
	 * An expression compatible with the Element_beforeDeleteExp parameter.
	 * By default, if user has write access, allows any deletion of elements, except if one of the specified expression evaluates positively.
	 * In that case, displays the associated prevention message to the user.
	 * FuncExp signature : <code>ctlPreventDeletion(exp1,msg1,exp2,msg2,...)</code><br/>
	 * Where arguments are :
	 * - Arg(0,2,...,2n) exp: FuncExp|LogExp. A Functional Expression which should evaluate to a boolean or a Logical Expression evaluated against the Record candidate to be deleted.
	 * If one expression returns true, then deletion is blocked and associated message is displayed to end user, if all expressions are false, then deletion is possible.
	 * - Arg(1,3,...2n+1) msg: String. The message displayed to the user if deletion is blocked.
	 * @return stdClass returns a StdClass of the form {okToDelete:true|false,message:msg} compatible with the Element_beforeDeleteExp parameter
	 */
	public function ctlPreventDeletion($args) {
	    $nArgs=$this->getNumberOfArgs($args);
	    if($nArgs<2) throw new FuncExpEvalException('ctlPreventDeletion takes at least two arguments which are the expression to block deletion and the custom message displayed as an explanation.', FuncExpEvalException::INVALID_ARGUMENT);
	    // Checks if deletion should be blocked 
	    $koToDelete = $this->evaluateArg($args[0]);
	    // if expression is an instance of LogExp then evaluates it against the Record
	    if($koToDelete instanceof LogExp) {
	        $koToDelete = TechnicalServiceProvider::getFieldSelectorLogExpRecordEvaluator()->evaluate($this->getRecord(), $koToDelete);
	    }
	    // Deletion is blocked, show explanation
	    if($koToDelete) {
	        return (object)array("okToDelete"=>false,"message"=>$this->evaluateArg($args[1]));
	    }
	    // Checks other expressions
	    $i = 2;
	    while($i < $nArgs) {
	        $koToDelete = $this->evaluateArg($args[$i]);
	        // if expression is an instance of LogExp then evaluates it against the Record
	        if($koToDelete instanceof LogExp) {
	            $koToDelete = TechnicalServiceProvider::getFieldSelectorLogExpRecordEvaluator()->evaluate($this->getRecord(), $koToDelete);
	        }
	        // Deletion is blocked, show explanation
	        if($koToDelete) {
	            $i++;
	            if($i < $nArgs) return (object)array("okToDelete"=>false,"message"=>$this->evaluateArg($args[$i]));
	            else return (object)array("okToDelete"=>false,"message"=>null);
	        }
	        // else moves to next expression
	        else $i+=2;	        
	    }
	    // no blocking expression, ok to delete.
	    return (object)array("okToDelete"=>true,"message"=>null);
	}
	
	/**
	 * Returns the name of the flow the element is currently in.
	 * FuncExp signature : <code>ctlCurrentFlow()</code><br/>
	 * @return String one of 'element-read','element-add', 'element-edit', 'element-delete', 'element-copy', 'element-dataflow', 'multiple-add','multiple-edit', 'multiple-delete', 'multiple-copy', 'unspecified'
	 * Returns 'unspecified' if the RecordEvaluator cannot determine in which flow it is currently operating.
	 */
	public function ctlCurrentFlow($args) {
		return $this->getCurrentFlowName();
	}

	/**
	 * @return String returns the name of the current flow in which this element is evaluated.
	 * One of ELEMENT_FLOW_READ, ELEMENT_FLOW_ADD, ELEMENT_FLOW_EDIT, ELEMENT_FLOW_DELETE, ELEMENT_FLOW_COPY, ELEMENT_FLOW_DATAFLOW, ELEMENT_FLOW_MULTIPLE_ADD, ELEMENT_FLOW_MULTIPLE_EDIT, ELEMENT_FLOW_MULTIPLE_DELETE, ELEMENT_FLOW_MULTIPLE_COPY, ELEMENT_FLOW_UNSPECIFIED
	 * @return String 'unspecified'. Subclass ElementEvaluator implements the whole logic.
	 */
	protected function getCurrentFlowName() {
		return ElementEvaluator::ELEMENT_FLOW_UNSPECIFIED;
	}
	
	/**
	 * Returns the XML configuration of a Field in the Record
	 * FuncExp signature : <code>cfgFieldXml(fieldName, attribute=null)</code><br/>
	 * Where arguments are :
	 * - Arg(0) fieldName: String|FieldSelector. The name of the field for which to get the XML configuration.
	 * - Arg(1) attribute: String. Optional xml attribute name. If set, then returns the value of the xml attribute in the configuration.
	 * @return SimpleXMLElement|String if no attribute is specified, then returns the XML configuration node of the Field, else returns the value of the specified attribute.
	 */
	public function cfgFieldXml($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs<1) throw new RecordException('cfgFieldXml func exp takes at least one argument: the fieldName for which to extract the XML configuratoin node.', RecordException::INVALID_ARGUMENT);
		$fieldName = $args[0];
		if($fieldName instanceof FieldSelector) $fieldName = $fieldName->getFieldName();
		else $fieldName = $this->evaluateArg($fieldName);
				
		$attribute = null;
		if($nArgs>1) $attribute=$this->evaluateArg($args[1]);
		
		$returnValue = $this->getRecord()->getFieldList()->getField($fieldName);
		if(isset($returnValue)) $returnValue=$returnValue->getXml();
		if(isset($returnValue) && isset($attribute)) {
			$returnValue = (string)$returnValue[$attribute];
		}
		return $returnValue;
	}
	
	/**
	 * Returns the translated label of a Field in the Record
	 * FuncExp signature : <code>cfgFieldLabel(fieldName)</code><br/>
	 * Where arguments are :
	 * - Arg(0) fieldName: String|FieldSelector. The name of the field for which to get the translated label.
	 * @return String the field label
	 */
	public function cfgFieldLabel($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs<1) throw new RecordException('cfgFieldLabel func exp takes one argument: the fieldName for which to get the translated label.', RecordException::INVALID_ARGUMENT);
		$fieldName = $args[0];
		if($fieldName instanceof FieldSelector) $fieldName = $fieldName->getFieldName();
		else $fieldName = $this->evaluateArg($fieldName);
	
		return $this->getTrm()->t($fieldName, $this->getRecord()->getFieldList()->getField($fieldName)->getXml());
	}
	
	/**
	 * Updates a value in the wigii bag, given one or several field selectors
	 * Last argument is the value to be set, all previous arguments should evaluate to field selectors.
	 * Example: setVal(f1, f2.name, f3.city, "unknown")
	 * will update field f1, subfields f2.name, f3.city with value "unknown"
	 * @return Scalar the updated value
	 */
	public function setVal($args) {
		$n = $this->getNumberOfArgs($args);
		if($n < 2) throw new RecordException("For setVal, the number of arguments should be at least 2", RecordException::INVALID_ARGUMENT);
				
		// if multiple edit then adds FieldSelector to FieldSelectorList for persistence
		$isMultiple=($this->getFormExecutor() instanceof EditMultipleElementFormExecutor);
		if($isMultiple) {
			$fsl=$this->getFormExecutor()->getFieldSelectorListForUpdate();
		}
		else $fsl=null;
		
		// evaluates value
		$val = $this->evaluateArg($args[$n-1]);
		// updates each values
		for($i = 0; $i < $n-1; $i++) {
			$fs = $args[$i];
			if(!($fs instanceof FieldSelector)) {
				$fs = $this->evaluateArg($fs);
				if(!($fs instanceof FieldSelector)) throw new RecordException("argument $i does not evaluate to a FieldSelector", RecordException::INVALID_ARGUMENT);
			}
			$this->setFieldValue($fs, $val);
			
			if($isMultiple && !$fsl->containsFieldSelector($fs->getFieldName(), $fs->getSubFieldName())) $fsl->addFieldSelectorInstance($fs);
		}
		return $val;
	}
	
	/**
	 * Adds a value to a MultipleAttributs field in the wigii bag, given one or several field selectors
	 * Last argument is the value to be added, all previous arguments should evaluate to field selectors.
	 * Example: addVal(f1, f2, f3, "codeX")
	 * will add MultipleAttributs value "codeX" to fields f1, f2, f3 
	 * @return Scalar the added MultipleAttributs value
	 */
	public function addVal($args) {
	    $n = $this->getNumberOfArgs($args);
	    if($n < 2) throw new RecordException("For addVal, the number of arguments should be at least 2", RecordException::INVALID_ARGUMENT);
	    
	    // if multiple edit then adds FieldSelector to FieldSelectorList for persistence
	    $isMultiple=($this->getFormExecutor() instanceof EditMultipleElementFormExecutor);
	    if($isMultiple) {
	        $fsl=$this->getFormExecutor()->getFieldSelectorListForUpdate();
	    }
	    else $fsl=null;
	    
	    // evaluates value
	    $val = $this->evaluateArg($args[$n-1]);
	    // updates each values
	    for($i = 0; $i < $n-1; $i++) {
	        $fs = $args[$i];
	        if(!($fs instanceof FieldSelector)) {
	            $fs = $this->evaluateArg($fs);
	            if(!($fs instanceof FieldSelector)) throw new RecordException("argument $i does not evaluate to a FieldSelector", RecordException::INVALID_ARGUMENT);
	        }
	        if(!($this->getRecord()->getFieldList()->getField($fs->getFieldName())->getDataType() instanceof MultipleAttributs)) throw new RecordException('Field '.$fs->getFieldName()." is not of type MultipleAttributs. addVal FuncExp only works with MultipleAttributs, use setVal instead.", RecordException::INVALID_ARGUMENT);	        
	        $currentVal = $this->getFieldValue($fs);
	        if(is_array($currentVal)) $currentVal[$val] = $val;
	        elseif(isset($currentVal)) $currentVal = array($currentVal=>$currentVal,$val=>$val);
	        else $currentVal = array($val=>$val);	        
	        $this->setFieldValue($fs, $currentVal);
	        
	        if($isMultiple && !$fsl->containsFieldSelector($fs->getFieldName(), $fs->getSubFieldName())) $fsl->addFieldSelectorInstance($fs);
	    }
	    return $val;
	}
	/**
	 * Prepends a value (followed by an optional separator) to a field in the wigii bag, given one or several field selectors
	 * Last argument is the value to prepend, all previous arguments should evaluate to field selectors.
	 * Example: prependVal(f1, f2, f3, "val", sep)
	 * will prepend value "val", followed by the separator to fields f1, f2, f3
	 * Separator is put only if both value and existing field value are not null.
	 * @return Scalar the prepend value
	 */
	public function prependVal($args) {
	    $n = $this->getNumberOfArgs($args);
	    if($n < 2) throw new RecordException("For prependVal, the number of arguments should be at least 2", RecordException::INVALID_ARGUMENT);
	    
	    // if multiple edit then adds FieldSelector to FieldSelectorList for persistence
	    $isMultiple=($this->getFormExecutor() instanceof EditMultipleElementFormExecutor);
	    if($isMultiple) {
	        $fsl=$this->getFormExecutor()->getFieldSelectorListForUpdate();
	    }
	    else $fsl=null;
	    
	    // extracts value and separator
	    if($args[$n-2] instanceof FieldSelector) {
	        $val = $this->evaluateArg($args[$n-1]);
	        $sep = '';
	    }
        else {            
            $val = $this->evaluateArg($args[$n-2]);
            $sep = $this->evaluateArg($args[$n-1]);
        }	    
	    
	    // updates each values
	    for($i = 0; $i < $n-1; $i++) {
	        $fs = $args[$i];
	        if(!($fs instanceof FieldSelector)) {
	            $fs = $this->evaluateArg($fs);
	            if(!($fs instanceof FieldSelector)) throw new RecordException("argument $i does not evaluate to a FieldSelector", RecordException::INVALID_ARGUMENT);
	        }
	        $currentVal = $this->getFieldValue($fs);
	        if(!empty($sep) && (!empty($val)||$value===0) && (!empty($currentVal)||$value===0)) $this->setFieldValue($fs, $val.$sep.$currentVal);
	        else $this->setFieldValue($fs, $val.$currentVal);
	        
	        if($isMultiple && !$fsl->containsFieldSelector($fs->getFieldName(), $fs->getSubFieldName())) $fsl->addFieldSelectorInstance($fs);
	    }
	    return $val;
	}
	/**
	 * Appends a value (followed by an optional separator) to a field in the wigii bag, given one or several field selectors
	 * Last argument is the value to append, all previous arguments should evaluate to field selectors.
	 * Example: appendVal(f1, f2, f3, "val", sep)
	 * will append value "val", followed by the separator to fields f1, f2, f3
	 * Separator is put only if both value and existing field value are not null.
	 * @return Scalar the append value
	 */
	public function appendVal($args) {
	    $n = $this->getNumberOfArgs($args);
	    if($n < 2) throw new RecordException("For appendVal, the number of arguments should be at least 2", RecordException::INVALID_ARGUMENT);
	    
	    // if multiple edit then adds FieldSelector to FieldSelectorList for persistence
	    $isMultiple=($this->getFormExecutor() instanceof EditMultipleElementFormExecutor);
	    if($isMultiple) {
	        $fsl=$this->getFormExecutor()->getFieldSelectorListForUpdate();
	    }
	    else $fsl=null;
	    
	    // extracts value and separator
	    if($args[$n-2] instanceof FieldSelector) {
	        $val = $this->evaluateArg($args[$n-1]);
	        $sep = '';
	    }
	    else {
	        $val = $this->evaluateArg($args[$n-2]);
	        $sep = $this->evaluateArg($args[$n-1]);
	    }
	    
	    // updates each values
	    for($i = 0; $i < $n-1; $i++) {
	        $fs = $args[$i];
	        if(!($fs instanceof FieldSelector)) {
	            $fs = $this->evaluateArg($fs);
	            if(!($fs instanceof FieldSelector)) throw new RecordException("argument $i does not evaluate to a FieldSelector", RecordException::INVALID_ARGUMENT);
	        }
	        $currentVal = $this->getFieldValue($fs);
	        if(!empty($sep) && (!empty($val)||$value===0) && (!empty($currentVal)||$value===0)) $this->setFieldValue($fs, $currentVal.$sep.$val);
	        else $this->setFieldValue($fs, $currentVal.$val);
	        
	        if($isMultiple && !$fsl->containsFieldSelector($fs->getFieldName(), $fs->getSubFieldName())) $fsl->addFieldSelectorInstance($fs);
	    }
	    return $val;
	}
	
	/**
	 * Sets the content of a Field of type Files
	 * FuncExp signature : <code>setFile(fieldName, content, subFieldName1, subFieldValue1, ...)</code><br/>
	 * Where arguments are :
	 * - Arg(0) fieldName: String|FieldSelector. The name of the field of type Files for which to set the content.
	 * - Arg(1) content: Scalar|Array|StdClass|Element|ElementP|Record|WplObjectList|DataFlowSelector if content converts to a String then saves a text file, 
	 * else object is serialized to xml (if compatible with the list of supported types).
	 * - Arg(2,...) subFieldNameI,subFieldValueI: a list of Files subfield (name,date,username,user,type) and values to update File meta-data	
	 */
	public function setFile($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 2) throw new RecordException("setFile function takes at least two arguments: field name and content", RecordException::INVALID_ARGUMENT);	
		
		// Extracts fieldname
		$fieldName = $args[0];
		if($fieldName instanceof FieldSelector) $fieldName = $fieldName->getFieldName();
		else $fieldName = $this->evaluateArg($fieldName);
		if(empty($fieldName)) throw new RecordException('fieldName cannot be null', RecordException::INVALID_ARGUMENT);
		$field = $this->getRecord()->getFieldList()->getField($fieldName);
		if(!($field->getDataType() instanceof Files)) throw new RecordException("field '$fieldName' should be of datatype Files", RecordException::INVALID_ARGUMENT);
		
		// Extracts files subfields
		if($nArgs>2) {
			$k=$this->evaluateArg($args[2]);
			if(is_array($k)) $fileSubfields=$k;
			elseif($nArgs>3) {
				$i=3;
				$fileSubfields=array();
				while($i<$nArgs) {
					// evaluates value
					$v=$this->evaluateArg($args[$i]);
					$i++;
					// stores key/value in array
					$fileSubfields[$k]=$v;
					// evalues next key
					if($i<$nArgs) {
						$k=$this->evaluateArg($args[$i]);
						$i++;
					}
				}
			}
			else $fileSubfields=null;
		}
		// generates content
		$p=$this->getPrincipal();
		$content = $this->evaluateArg($args[1]);
		// if content is a DataFlowSelector then executes it
		if($content instanceof DataFlowSelector) {
			$content = ServiceProvider::getDataFlowService()->processDataFlowSelector($p, $content);
		}
		// if content is not empty, then serializes it
		if(isset($content)) {
			if(is_scalar($content)) {
				$type='.txt';
				if($content===true) $content = '1';
				elseif($content===false) $content = '0';				
			}
			elseif(is_array($content)||$content instanceof stdClass) {
				$type='.xml';
				$content = TechnicalServiceProvider::getWplToolbox()->stdClass2Xml($p, $fieldName, $content);
			}
			elseif($content instanceof Element || $content instanceof ElementP || $content instanceof Record) {
				$content = $content->getDbEntity();
				$type = '.xml';
				$content = TechnicalServiceProvider::getWplToolbox()->record2xml($p, $content->getFieldList(), $content->getWigiiBag(), false, null, $content->getId());
			}
			elseif($content instanceof WplObjectList) {
				$type = '.xml';
				$content = TechnicalServiceProvider::getWplToolbox()->wplObjectList2Xml($p, $fieldName, $content);
			}
			else throw new RecordException("FuncExp result of class '".get_class($content)."' cannot be serialized", RecordException::UNSUPPORTED_OPERATION);
			if(!$fileSubfields['type']) $fileSubfields['type'] = $type;
		}
		// if content is not empty, then saves it as a File
		if(isset($content)) {
			// element should be persisted straight away if current flow is element-data-flow or element-multiple-edit or unspecified
			// in other cases file is only saved into temporary location and element updated into memory.
			switch($this->getCurrentFlowName()) {
				case ElementEvaluator::ELEMENT_FLOW_DATAFLOW:
				case ElementEvaluator::ELEMENT_FLOW_MULTIPLE_EDIT:
				case ElementEvaluator::ELEMENT_FLOW_UNSPECIFIED:
					$persistElement=true;
					break;
				default: $persistElement=false;
			}
			// saves the file content into the element
			ServiceProvider::getDataFlowService()->processString($p, $content, dfasl(
				dfas('ElementFileOutputStreamDFA','setElement',$this->getRecord(),'setFieldName',$fieldName,
					'setPersistElement', $persistElement,
					'setFileSubfields', $fileSubfields
				)
			));
		}
	}	

	/**
	 * Creates an array containing all the evaluated arguments
	 * If no args, then returns an empty array.
	 */
	public function newList($args) {
		$returnValue = array();
		if($this->getNumberOfArgs($args) > 0) {
			foreach($args as $arg) {
				$returnValue[] = $this->evaluateArg($arg);
			}
		}
		return $returnValue;
	}

	/**
	 * Creates a Map.
	 * Two possible syntaxes :
	 * 1. Creates a map based on two lists (equivalent to php function array_combine)
	 *    First argument should evaluate to an array, that will be used for map keys
	 *    Second argument should evaluate to an array, that will be used for map values
	 * 2. Creates a map based on a list of pairs (key, value): newMap(key1, val1, key2, val2, key3, val3, ...)
	 * Returns an array.
	 */
	public function newMap($args) {
		if($this->getNumberOfArgs($args) < 2) throw new FuncExpEvalException("To create a map, there should be 2 arguments: the array of keys and the array of values", FuncExpEvalException::INVALID_ARGUMENT);
		$key = $this->evaluateArg($args[0]);
		$value = $this->evaluateArg($args[1]);
		// if arg0 is array -> then array combine
		if(is_array($key)) {
			if(!is_array($value)) throw new FuncExpEvalException("second argument did not evaluate to an array. Cannot use it as the array of values for the map.", FuncExpEvalException::INVALID_ARGUMENT);
			$returnValue = array_combine($key, $value);
			if(!$returnValue) throw new FuncExpEvalException("Could not create the map, keys and values don't match", FuncExpEvalException::INVALID_ARGUMENT);
			else return $returnValue;
		}
		// else (key, value) pairs in a list
		else {
			$returnValue = array($key => $value);
			$i = 2;
			while($i < $nArgs) {
				$key = $this->evaluateArg($args[$i]);
				if(!is_scalar($key)) throw new FuncExpEvalException("key at argument $i did not evaluate to a scalar", FuncExpEvalException::INVALID_ARGUMENT);
				$i++;
				if($i < $nArgs) {
					$value = $this->evaluateArg($args[$i]);
					$i++;
					$returnValue[$key] = $value;
				}
				else $returnValue[$key] = null;
			}
			return $returnValue;
		}
	}

	/**
	 * Evaluates the first argument and converts it to a string into the JSON syntax.
	 * The first argument should be an array.
	 * If no arg then returns empty string.
	 */
	public function newJsonString($args) {
		if($this->getNumberOfArgs($args) < 1) return '';
		$v = $this->evaluateArg($args[0]);
		if(!is_array($v)) throw new RecordException("first argument should evaluate to an array", RecordException::INVALID_ARGUMENT);
		$v = json_encode($v);
		if(!$v) throw new RecordException("could not create a JSON string based on the input", RecordException::INVALID_ARGUMENT);
		return $v;
	}
	
	/**
	 * Evaluates a LogExp against the Record and returns its result<br/>
	 * FuncExp signature : <code>evallx(logExp)</code><br/>
	 * Where arguments are :
	 * - Arg(0) logExp: Evaluates to a LogExp which is the solved against the Record field values.	 
	 * @return Boolean the result of the evaluation of the LogExp
	 */
	public function evallx($args) {
		$nArgs=$this->getNumberOfArgs($args);
		if($nArgs<1) throw new FuncExpEvalException('evallx takes at least one argument which is the LogExp to evaluate', FuncExpEvalException::INVALID_ARGUMENT);
		$lx=$this->evaluateArg($args[0]);
		// if value is a log exp, then solves it against the record
		if($lx instanceof LogExp) {
			$lx = TechnicalServiceProvider::getFieldSelectorLogExpRecordEvaluator()->evaluate($this->getRecord(), $lx);
		}
		// returns result
		if($lx) return true;
		else return false;
	}
	
	// Record matrix functions
	
	/**
	 * Gets a matrix out of the Record as a StdClass instance
	 * FuncExp signature: <code>getMatrix(fromRow, toRow, col1, col2, ...)</code><br/>
	 * Where arguments are :
	 * - Arg(0) fromRow: int. The start index from which to extract the matrix rows.
	 * - Arg(1) toRow: int. The stop index to which to extract the matrix rows.
	 * - Arg(2...) colI: string. The name of the matrix column to be extracted.
	 * @return stdClass StdClass {rows : array(StdClass as row), index : null, errors : null} as matrix
	 * @example With a module configuration containing the fields ProjectCode_1, Location_1, ProjectCode_2, Location_2, ProjectCode_3, Location_3 and data
	 * ProjectCode_1 = P1, Location_1 = L1,
	 * ProjectCode_2 = P2, Location_2 = L2,
	 * ProjectCode_3 = P3, Location_3 = L3
	 * The call of getMatrix("1", "3", "ProjectCode_", "Location_")
	 * will return an stdClass instance of the form 
	 * {rows => array(
	 * 		{ProjectCode_ => {value=>P1, other data type subfields...}, Location_ => {value => L1, other data type subfields...}}
	 * 		{ProjectCode_ => {value=>P2, other data type subfields...}, Location_ => {value => L2, other data type subfields...}}
	 * 		{ProjectCode_ => {value=>P3, other data type subfields...}, Location_ => {value => L3, other data type subfields...}}
	 * 	), 
	 * 	index => null, 
	 * 	errors => null
	 * }
	 */
	public function getMatrix($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 3) throw new RecordException('getMatrix func exp takes at least three arguments which are fromRow, toRow and matrix columns', RecordException::INVALID_ARGUMENT);
		$fromRow = $this->evaluateArg($args[0]);
		$toRow = $this->evaluateArg($args[1]);
		$columns = array();
		for($i = 2; $i<$nArgs;$i++) {
			$columns[] = $this->evaluateArg($args[$i]);
		}
		
		$returnValue = array(
				'rows'=>$this->getRecord()->exportMatrix($columns, $fromRow, $toRow), 
				'index'=>null, 
				'errors'=>null
		);
		$returnValue = (object)$returnValue;
		return $returnValue;
	}
	/**
	 * Stores back a matrix of type StdClass into a Record
	 * FuncExp signature: <code>storeMatrix(matrix, fromRow, toRow, col1, col2, ...)</code><br/>
	 * Where arguments are :
	 * - Arg(0) matrix: stdClass. The matrix to be stored in the Record based on the format of the object given by the getMatrix func exp.
	 * - Arg(1) fromRow: int. The start index from which to store the matrix rows in the Record.
	 * - Arg(2) toRow: int. The stop index to which to store the matrix rows in the Record.
	 * - Arg(3...) colI: string. The name of the matrix column to be saved.
	 * @return int the number of updated rows
	 */
	public function storeMatrix($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 4) throw new RecordException('storeMatrix func exp takes at least four arguments which are matrix, fromRow, toRow and matrix columns', RecordException::INVALID_ARGUMENT);
		$matrix = $this->evaluateArg($args[0]);
		if(!(is_object($matrix))) throw new RecordException('matrix should be a StdClass instance', RecordException::INVALID_ARGUMENT);
		$fromRow = $this->evaluateArg($args[1]);
		$toRow = $this->evaluateArg($args[2]);
		$columns = array();
		for($i = 3; $i<$nArgs;$i++) {
			$columns[] = $this->evaluateArg($args[$i]);
		}		
		$returnValue = $this->getRecord()->updateMatrix($columns, $fromRow, $toRow, $matrix->{'rows'});
		
		// displays any errors if FormExecutor is defined and matrix has some errors.
		$formExecutor = $this->getFormExecutor();		
		if(isset($formExecutor)) {
			// displays global errors
			$errors = $matrix->{'errors'};
			if(!empty($errors)) {
				foreach($errors as $error) {
					$formExecutor->addErrorToField($error, $this->getCurrentField()->getFieldName());
				}
				// clear global errors
				$matrix->{'errors'} = null;
			}			
			// displays field errors
			if($matrix->{'hasRowsInError'}) {
				$rows = $matrix->{'rows'};
				if(!empty($rows)) {
					$i = $fromRow;
					foreach($rows as $row) {
						// adds error to field
						foreach($columns as $col) {
							$fieldName = $col.$i;
							$fieldValue = $row->{$col};
							if(is_object($fieldValue)) {
								$errors = $fieldValue->{'errors'};
								// displays error only for rows between start and stop index
								if($i <= $toRow && !empty($errors)) {
									foreach($errors as $error) {
										$formExecutor->addErrorToField($error, $fieldName);
									}
								}
								// clears errors on field
								$fieldValue->{'errors'} = null;
							}
						}
						$i++;
					}					
				}
				$matrix->{'hasRowsInError'}=false;
			}						
		}
		
		return $returnValue;
	}
	/**
	 * Dumps the rows of a matrix into a given dataflow. 
	 * FuncExp signature: <code>visitMatrix(matrix, dfasl, resultField=null)</code><br/>
	 * Where arguments are :
	 * - Arg(0) matrix: stdClass. The matrix to dump into the data flow. The matrix should have the format of the object given by the getMatrix func exp.
	 * - Arg(1) dfasl: DataFlowActivitySelectorList. The data flow in which each row of the matrix is dumped.
	 * - Arg(2) resultField: String. Optional string specifying under which dynamic field in the matrix object the eventual result of the data flow should be stored.
	 * Meaning that if resultField is defined then matrix will be of the form {rows:..., index:..., errors:..., resultField:data flow result}.
	 * The DataFlowContext has a reference on the matrix under the 'matrix' attribute so that index or errors can be updated if needed.
	 * Dynamic columns on rows can be added by adding fields to the row stdClass instance.
	 * Field and subfield values can be updated directly in the objects coming through the data flow, rows are passed by reference and not cloned.
	 * @return stdClass returns the matrix for further use
	 */
	public function visitMatrix($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 2) throw new RecordException('visitMatrix func exp takes at least two arguments which are matrix and dfasl', RecordException::INVALID_ARGUMENT);
		$matrix = $this->evaluateArg($args[0]);
		if(!(is_object($matrix))) throw new RecordException('matrix should be a StdClass instance', RecordException::INVALID_ARGUMENT);
		$dfasl = $this->evaluateArg($args[1]);
		if($nArgs > 2) $resultField = $this->evaluateArg($args[2]);
		else $resultField = null;
		
		// executes the data flow
		$rows = $matrix->{'rows'};
		if(!empty($rows)) {
			$dfS = ServiceProvider::getDataFlowService();
			// opens
			$dfctx = $dfS->startStream($this->getPrincipal(), $dfasl);
			// initialises context
			$dfctx->setAttribute('matrix', $matrix);
			// runs data flow
			foreach($rows as $row) {
				$dfS->processDataChunk($row, $dfctx);
			}
			// ends
			$result = $dfS->endStream($dfctx);
		}
		else $result = null;
		// stores result
		if(isset($resultField)) {
			$matrix->{$resultField} = $result;
		}
		return $matrix;
	}
	/**
	 * Sorts the matrix rows against a column.
	 * FuncExp signature: <code>sortMatrix(matrix, col, asc=true)</code><br/>
	 * Where arguments are :
	 * - Arg(0) matrix: stdClass. The matrix to sort. The matrix should have the format of the object given by the getMatrix func exp.
	 * - Arg(1) col: String. The name of the column in the matrix against which the rows are sorted. 
	 * If multi-column sorting is needed, then add a dynamically calculated column using the visitMatrix function. 
	 * - Arg(2) asc: Boolean. If true then sorting is acending, else descending order. Default is ascending.
	 * @return stdClass returns the matrix for further use
	 */
	public function sortMatrix($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 2) throw new RecordException('sortMatrix func exp takes at least two arguments which are matrix and col', RecordException::INVALID_ARGUMENT);
		$matrix = $this->evaluateArg($args[0]);
		if(!(is_object($matrix))) throw new RecordException('matrix should be a StdClass instance', RecordException::INVALID_ARGUMENT);
		$col = $this->evaluateArg($args[1]);
		if($nArgs > 2) $asc = ($this->evaluateArg($args[2]) == true);
		else $asc = true;
		
		$rows = $matrix->{'rows'};
		if(!empty($rows)) {						
			// prepares sortable array
			$sortableArray = array();
			$pad = strlen((count($rows)-1));
			$i = 0;
			foreach($rows as $row) {
				$k = $row->{$col};
				if(is_object($k)) $k = $k->{'value'};
				// builds a key which can be sorted alphabetically even if numeric or null and not unique.
				$k = $k.'A'.str_pad($i, $pad, '0', STR_PAD_LEFT);
				$sortableArray[$k] = $row;
				$i++;
			}
			
			// sorts array
			if($asc) ksort($sortableArray);
			else krsort($sortableArray);
			
			// replaces rows array in matrix
			$matrix->{'rows'} = $sortableArray;
		}
		return $matrix;
	}
	/**
	 * Filters the matrix rows against a LogExp. If logExp evaluates false on a row, the row is removed from the matrix.
	 * FuncExp signature: <code>filterMatrix(matrix, logExp)</code><br/>
	 * Where arguments are :
	 * - Arg(0) matrix: stdClass. The matrix to filter. The matrix should have the format of the object given by the getMatrix func exp.
	 * - Arg(1) logExp: LogExp. The logExp to be evaluated on each row. If returns false, then the row is removed from the matrix.
	 * @return stdClass returns the matrix for further use
	 */
	public function filterMatrix($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 2) throw new RecordException('filterMatrix func exp takes at least two arguments which are matrix and logExp', RecordException::INVALID_ARGUMENT);
		$matrix = $this->evaluateArg($args[0]);
		if(!(is_object($matrix))) throw new RecordException('matrix should be a StdClass instance', RecordException::INVALID_ARGUMENT);
		$logExp = $this->evaluateArg($args[1]);
		if(!($logExp instanceof LogExp)) throw new RecordException('logExp should be a non null instance of LogExp');
		
		// evaluates log exp on each row
		if(!$this->getMatrixLogExpEvaluator()->evaluate($matrix, $logExp, 'filterResultFlag')) {
			$rows = $matrix->{'rows'};
			if(!empty($rows)) {
				$filteredRows = array();
			
				// keeps only rows for which logExp evaluates positively.
				foreach($rows as $row) {
					if($row->{'filterResultFlag'}) $filteredRows[] = $row;
				}
					
				$matrix->{'rows'} = $filteredRows;
			}
		}
		return $matrix;
	}
	/**
	 * Summarizes a column of a matrix using an aggregation function. The aggregation can be grouped against the value of another column.
	 * FuncExp signature: <code>summarizeMatrix(matrix, op=[SUM,COUNT,PRODUCT,AVG], col, groupByCol=null, resultField=op_col)</code><br/>
	 * Where arguments are :
	 * - Arg(0) matrix: stdClass. The matrix to summarize. The matrix should have the format of the object given by the getMatrix func exp.
	 * - Arg(1) op: String. The aggregation operation. One of the following strings "SUM", "COUNT", "PRODUCT", "AVG".
	 * - Arg(2) col: String. The name of the column to summarize.
	 * - Arg(3) groupByCol: String. Optional string, specifying the name of the column on which to group by first, before running the aggregation operation.
	 * - Arg(4) resultField: String. Optional string specifying under which dynamic field in the matrix object the summary result should be stored. Defaults to op_col.
	 * @example consider the matrix 
	 * $m = {rows => array(
	 * 			{ProjectCode_=>{value=>P1},Sector_=>{value=>S1},Percentage_=>{value=>30}},
	 * 			{ProjectCode_=>{value=>P1},Sector_=>{value=>S2},Percentage_=>{value=>70}},
	 * 			{ProjectCode_=>{value=>P2},Sector_=>{value=>S1},Percentage_=>{value=>40}},
	 * 			{ProjectCode_=>{value=>P2},Sector_=>{value=>S2},Percentage_=>{value=>80}},
	 * 		),
	 * 	index=>null,
	 * 	errors=>null
	 * }
	 * the call of summarizeMatrix($m,"SUM","Percentage_","ProjectCode_") will update the matrix by adding a dynamic field "SUM_Percentage_" with the grouped-sum result, 
	 * will update the index with the group by calculation and add an index entry for the aggregated field.
	 * $m = {rows => array(
	 * 			StdClass(1){ProjectCode_=>{value=>P1},Sector_=>{value=>S1},Percentage_=>{value=>30}},
	 * 			StdClass(2){ProjectCode_=>{value=>P1},Sector_=>{value=>S2},Percentage_=>{value=>70}},
	 * 			StdClass(3){ProjectCode_=>{value=>P2},Sector_=>{value=>S1},Percentage_=>{value=>40}},
	 * 			StdClass(4){ProjectCode_=>{value=>P2},Sector_=>{value=>S2},Percentage_=>{value=>80}},
	 * 		),
	 * 	SUM_Percentage_=>array(P1=>100,P2=>120)
	 * 	index=>array(
	 * 		ProjectCode_=> array(P1=>array(StdClass(1),StdClass(2)), P2=>array(StdClass(3),StdClass(4))),
	 * 		SUM_Percentage_=> array(P1=>array(StdClass(1),StdClass(2)), P2=>array(StdClass(3),StdClass(4))),
	 * 	),
	 * 	errors=null
	 * }
	 * @return stdClass returns the matrix for further use
	 */
	public function summarizeMatrix($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 3) throw new RecordException('summarizeMatrix func exp takes at least three arguments which are matrix, op and col', RecordException::INVALID_ARGUMENT);
		$matrix = $this->evaluateArg($args[0]);
		if(!(is_object($matrix))) throw new RecordException('matrix should be a StdClass instance', RecordException::INVALID_ARGUMENT);
		$op = $this->evaluateArg($args[1]);
		$op = strtoupper($op);
		switch($op) {
			case "SUM":
			case "COUNT":
			case "PRODUCT":
			case "AVG":
				break;
			default: throw new RecordException("op should be one of 'SUM', 'COUNT', 'PRODUCT', 'AVG'", RecordException::INVALID_ARGUMENT);	
		}
		$col = $this->evaluateArg($args[2]);
		if($nArgs > 3) $groupByCol = $this->evaluateArg($args[3]);
		else $groupByCol = null;
		if($nArgs > 4) $resultField = $this->evaluateArg($args[4]);
		else $resultField = $op.'_'.$col;
		
		$rows = $matrix->{'rows'};
		if(!empty($rows)) {
			// builds global stat object
			if(!isset($groupByCol)) {
				$stat = array('SUM'=>0,'COUNT'=>0,'PRODUCT'=>1,'AVG'=>0);
				$stat = (object)$stat;
				$groupedStats = array($stat);
				$index = null;
			}
			// prepares grouped stat object and retrieves index
			else {
				$stat = null;
				$groupedStats = array();
				$index = $matrix->{'index'};
				if(!isset($index)) $index = array();
				// clears groupBy index
				$index[$groupByCol] = array();
				$index[$resultField] = array();
			}
			foreach($rows as $row) {
				$value = $row->{$col};
				if(is_object($value)) $value = $value->{'value'};
				
				// group by
				if(isset($groupByCol)) {
					$groupByValue = $row->{$groupByCol};
					if(is_object($groupByValue)) $groupByValue = $groupByValue->{'value'};
					if(!is_scalar($groupByValue)) throw new RecordException('can only group on scalars', RecordException::INVALID_ARGUMENT);
					// gets stat object
					$stat = $groupedStats[$groupByValue];
					if(!isset($stat)) {
						$stat = array('SUM'=>0,'COUNT'=>0,'PRODUCT'=>1,'AVG'=>0);
						$stat = (object)$stat;
						$groupedStats[$groupByValue] = $stat;
					}
					// updates GroupBy index
					$subIndex = $index[$groupByCol];
					if(!isset($subIndex)) $subIndex = array();
					$refs = $subIndex[$groupByValue];
					if(!isset($refs)) $refs = array();
					$refs[] = $row;
					$subIndex[$groupByValue] = $refs;
					$index[$groupByCol] = $subIndex;
					// updates Summary index
					$subIndex = $index[$resultField];
					if(!isset($subIndex)) $subIndex = array();
					$refs = $subIndex[$groupByValue];
					if(!isset($refs)) $refs = array();
					$refs[] = $row;
					$subIndex[$groupByValue] = $refs;
					$index[$resultField] = $subIndex;
				}
				// only considers non-null values
				if(isset($value)) {
					// computes stat
					switch($op) {
						case "SUM":
							$stat->SUM += $value;
							break;							
						case "COUNT":
							$stat->COUNT += 1;
							break;
						case "PRODUCT":
							$stat->PRODUCT *= $value;
							break;
						case "AVG":
							$stat->SUM += $value;
							$stat->COUNT += 1;
							break;						
					}
				}
			}
			// finalizes stat
			foreach($groupedStats as &$stat) {
				switch($op) {			
					case "AVG":
						if($stat->COUNT > 0) $stat->AVG = $stat->SUM / $stat->COUNT;
						break;
				}
				$stat = $stat->{$op};
			}
			// stores result in matrix
			$matrix->{$resultField} = (isset($groupByCol)? $groupedStats: reset($groupedStats));
			// stores index back in matrix
			if(isset($index)) $matrix->{'index'} = $index;
		}
		return $matrix;
	}
	/**
	 * Checks the matrix by evaluating a LogExp. If it returns false, then an error message can be attached to the cells of a specified column for each row in error.
	 * FuncExp signature: <code>checkMatrix(matrix, checkExp, errorMessage, errorCol=null)</code><br/>
	 * Where arguments are :
	 * - Arg(0) matrix: stdClass. The matrix to check. The matrix should have the format of the object given by the getMatrix func exp.
	 * - Arg(1) checkExp: LogExp. A logExp to run on the matrix. The logExp can either check columns into a row, or check calculated fields at the matrix level (like the ones issued from the summarizeMatrix func exp).
	 * - Arg(2) errorMessage: String. The error message to be displayed. (automatically translated).
	 * - Arg(3) errorCol: String. Optional string, specifying the name of the column to which to attach the error message if check fails. If not specified, then the error message is attached to the current field of the record (the one to which the func exp is running)
	 * @example consider the matrix
	 * $m = {rows => array(
	 * 			StdClass(1){ProjectCode_=>{value=>P1},Sector_=>{value=>S1},Percentage_=>{value=>30}},
	 * 			StdClass(2){ProjectCode_=>{value=>P1},Sector_=>{value=>S2},Percentage_=>{value=>70}},
	 * 			StdClass(3){ProjectCode_=>{value=>P2},Sector_=>{value=>S1},Percentage_=>{value=>40}},
	 * 			StdClass(4){ProjectCode_=>{value=>P2},Sector_=>{value=>S2},Percentage_=>{value=>80}},
	 * 		),
	 * 	SUM_Percentage_=>array(P1=>100,P2=>120)
	 * 	index=>array(
	 * 		ProjectCode_=> array(P1=>array(StdClass(1),StdClass(2)), P2=>array(StdClass(3),StdClass(4))),
	 * 		SUM_Percentage_=> array(P1=>array(StdClass(1),StdClass(2)), P2=>array(StdClass(3),StdClass(4))),
	 * 	),
	 * 	errors=null
	 * }
	 * the call of checkMatrix($m,lxSmEq(fs("SUM_Percentage_"), "100"), "Sum of percentage should be smaller or equal to 100 per project.", "Percentage_") 
	 * will update the matrix by adding the error message on the rows affected by the failing check expression
	 * $m = {rows => array(
	 * 			StdClass(1){ProjectCode_=>{value=>P1},Sector_=>{value=>S1},Percentage_=>{value=>30}},
	 * 			StdClass(2){ProjectCode_=>{value=>P1},Sector_=>{value=>S2},Percentage_=>{value=>70}},
	 * 			StdClass(3){ProjectCode_=>{value=>P2},Sector_=>{value=>S1},Percentage_=>{value=>40, errors=>array("Sum of percentage should be smaller or equal to 100 per project.")}},
	 * 			StdClass(4){ProjectCode_=>{value=>P2},Sector_=>{value=>S2},Percentage_=>{value=>80, errors=>array("Sum of percentage should be smaller or equal to 100 per project.")}},
	 * 		),
	 * 	SUM_Percentage_=>array(P1=>100,P2=>120)
	 * 	index=>array(
	 * 		ProjectCode_=> array(P1=>array(StdClass(1),StdClass(2)), P2=>array(StdClass(3),StdClass(4))),
	 * 		SUM_Percentage_=> array(P1=>array(StdClass(1),StdClass(2)), P2=>array(StdClass(3),StdClass(4))),
	 * 	),
	 * 	errors=null
	 * 	hasRowsInError=true
	 * }
	 * @return stdClass returns the matrix for further use
	 */
	public function checkMatrix($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 3) throw new RecordException('checkMatrix func exp takes at least three arguments which are matrix, checkExp and errorMessage', RecordException::INVALID_ARGUMENT);
		$matrix = $this->evaluateArg($args[0]);
		if(!(is_object($matrix))) throw new RecordException('matrix should be a StdClass instance', RecordException::INVALID_ARGUMENT);
		$checkExp = $this->evaluateArg($args[1]);
		if(!($checkExp instanceof LogExp)) throw new RecordException('checkExp should be a non null instance of LogExp');
		$errorMessage = $this->evaluateArg($args[2]);
		if($nArgs > 3) $errorCol = $this->evaluateArg($args[3]);
		else $errorCol = null;
		
		// evaluates log exp on each row
		if(!$this->getMatrixLogExpEvaluator()->evaluate($matrix, $checkExp, 'checkResultFlag')) {
			// if logExp did not evaluate positively for all rows, then adds the error message to the matrix
			if(is_null($errorCol)) {
				$errors = $matrix->{'errors'};
				if(!isset($errors)) $errors = array();
				$errors[] = $errorMessage;
				$matrix->{'errors'} = $errors;
			}
			// if an errorCol is specified, then adds error to each field in error.
			else {
				$rows = $matrix->{'rows'};
				if(!empty($rows)) {
					foreach($rows as $row) {
						if($row->{'checkResultFlag'}===false) {
							$colVal = $row->{$errorCol};
							if(!is_object($colVal)) {
								$colVal = array('value'=>$colVal);
								$colVal = (object)$colVal;
								$row->{$errorCol} = $colVal;
							}
							$errors = $colVal->{'errors'};
							if(!isset($errors)) $errors = array();
							$errors[] = $errorMessage;
							$colVal->{'errors'} = $errors;
							$matrix->{'hasRowsInError'} = true;
						}
					}
				}
			}
		}
		return $matrix;
	}
	
	/**
	 * Renders a dynamic set of fields laid out as a matrix.
	 * The number of rows is dynamic, at least starting row (fromRow index) should be defined in configuration file.
	 * FuncExp signature: <code>renderFormMatrix(fromRow,toRow,fillMatrixValues,col1,col2,...)</code><br/>
	 * Where arguments are :
	 * - Arg(0) fromRow: int. The start index from which to render the matrix rows.
	 * - Arg(1) toRow: int. The stop index to which to render the matrix rows.
	 * - Arg(2) fillMatrixValues: FuncExp. A FuncExp to be called to fill the matrix values when editing an existing element.
	 * The FuncExp receive four arguments: the fromRow index, toRow index, the columns array and the element containing the matrix.
	 * - Arg(2...) colI: string. The name of the matrix columns to be rendered.
	 * 
	 * @example calling renderFormMatrix("1","4",fillMyMatrix,"articleNumber_","designation_","quantity_")
	 * creates a matrix on the current element using the fields
	 * articleNumber_1, designation_1, quantity_1 as a template,
	 * and dynamically add the fields:
	 * articleNumber_2, designation_2, quantity_2,
	 * articleNumber_3, designation_3, quantity_3,
	 * articleNumber_4, designation_4, quantity_4
	 * it will follow the same lifecycle as the FormExecutor::resolveForm function:
	 * 1. dynamically modifies Element FieldList to integrate new Fields to match the rows in matrix,
	 * 2. calls fillMatrixValues(fromRow,toRow,columns,__element.this) (fillMyMatrix in the example) to fill the Element matrix Fields with existing values or default values on add
	 * 3. renders the Form or Detail for the new fields
	 */
	public function renderFormMatrix($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs<4) throw new FuncExpEvalException('renderFormMatrix takes at least four arguments which are the fromRow index, toRow index, an optional FuncExp to fetch the field values and at least one column name', FuncExpEvalException::INVALID_ARGUMENT);
		$fromRow = $this->evaluateArg($args[0]);
		$toRow = $this->evaluateArg($args[1]);
		$fillMatrixValues = $args[2];
		$columns = array();
		for($i = 3; $i<$nArgs;$i++) {
			$columns[] = $this->evaluateArg($args[$i]);
		}
		$nCols = count($columns);
		
		$fe = $this->getFormExecutor();
		if(!isset($fe)) throw new FuncExpEvalException('renderFormMatrix can only be called in the scope of a Form lifecycle, make sure to call it in htmlExp attributes of a configuration file', FuncExpEvalException::INVALID_STATE);		
		// checks form if needed
		if($fe->getState() != "start") $formChecker = $fe->getFormChecker();
		else $formChecker = null;
		// extends Record FieldList with matrix fields (starting at row 2 as row 1 is already in the config file)
		$rec = $fe->getRecord();
		$fieldList = $rec->getFieldList();
		for($i=$fromRow+1;$i<=$toRow;$i++) {
			// creates row if doesn't exist
			if(!$fieldList->doesFieldExist($columns[0].$i)) {
				for($j=0;$j<$nCols;$j++) {
					// clones field of first row and renames
					$row1Field = $fieldList->getField($columns[$j].$fromRow);
					$rowJField = clone $row1Field;
					$rowJField->setFieldName($columns[$j].$i);
					// adds new field to row
					$fieldList->addField($rowJField);
					// checks posted value
					if(isset($formChecker)) $rowJField->acceptFieldListVisitor($formChecker);
				}
			}
		}
		// if Form is first displayed, fills form values using provided FuncExp
		if($fe->getState() == "start" && $fillMatrixValues instanceof FuncExp) {
			$fillMatrixValues->addArgument($fromRow);
			$fillMatrixValues->addArgument($toRow);
			$fillMatrixValues->addArgument($columns);
			$fillMatrixValues->addArgument($rec);
			$this->evaluateFuncExp($fillMatrixValues,$this);
		}
		// renders form or detail
		if($fe instanceof DetailElementFormExecutor) $fieldRenderer = $fe->getDetailRenderer();
		else $fieldRenderer = $fe->getFormRenderer();
		for($i=$fromRow+1;$i<=$toRow;$i++) {
			for($j=0;$j<$nCols;$j++) {
				$field = $fieldList->getField($columns[$j].$i);
				$field->acceptFieldListVisitor($fieldRenderer);
			}
		}
	}
	
	/**
	 * Fills the values of a matrix defined into an element, given a selected list of mapping elements.
	 * FuncExp signature: <code>fillMatrixFromElements(elementList,fromFields,fromRow,toRow,toColumns,matrixElt)</code><br/>
	 * Where arguments are :
	 * - Arg(0) elementList: ElementPListDataFlowConnector|ElementPList selected list of elements mapping matrix rows
	 * - Arg(1) fromFields: Array. A list of field names from which to extract the values.
	 * - Arg(2) fromRow: int. The start index from which to fill the matrix rows.
	 * - Arg(3) toRow: int. The stop index to which to fill the matrix rows.
	 * - Arg(4) toColumns: Array. The names of the matrix columns to be filled.
	 * - Arg(5) matrixElt: Element. The Element containing the matrix to be filled.
	 */
	public function fillMatrixFromElements($args) {
		$this->debugLogger()->logBeginOperation('fillMatrixFromElements');
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs<6) throw new FuncExpEvalException('fillMatrixFromElements takes at least six arguments which are elementList,fromFields,fromRow,toRow,toColumns,matrixElt', FuncExpEvalException::INVALID_ARGUMENT);
		$cacheKey = "fillMatrixFromElements_".md5(fx2str($args[0]));
		$elementList = $this->evaluateArg($args[0]);
		if(!isset($elementList)) return;
		$fromFields = $this->evaluateArg($args[1]);
		$fromRow = $this->evaluateArg($args[2]);
		$toRow = $this->evaluateArg($args[3]);
		$toColumns = $this->evaluateArg($args[4]);
		$matrixElt = $this->evaluateArg($args[5]);
		
		$i=ValueObject::createInstance($fromRow);
		ServiceProvider::getDataFlowService()->processDataSource($this->getPrincipal(),$elementList,dfasl(dfas('CallbackDFA','setProcessDataChunkCallback',function($elementP,$callbackDFA) use($i,$fromFields,$toRow,$toColumns,$matrixElt){
			if($i->getValue()<=$toRow) {
				$dataFlowContext = $callbackDFA->getDataFlowContext();			
				$element = $elementP->getDbEntity();
				$fieldList = $element->getFieldList();
				$nFields = count($fromFields);
				$nCols = count($toColumns);
				for($j=0;$j<$nFields;$j++) {
					$fieldName = $fromFields[$j];
					if($j<$nCols) {
						$colName = $toColumns[$j].$i->getValue();
						if($fieldName instanceof FieldSelector) {
							if($fieldName->isElementAttributeSelector()) $matrixElt->setFieldValue($element->getAttribute($fieldName),$colName);							
							else $matrixElt->setFieldValue($element->getFieldValue($fieldName->getFieldName(), $fieldName->getSubFieldName()),$colName);
						}
						elseif($fieldName instanceof FuncExp) {
							$matrixElt->setFieldValue(ServiceProvider::getWigiiBPL()->evaluateFuncExp($dataFlowContext->getPrincipal(), $fieldName, $element),$colName);
						}
						else {
							$dtXml = $fieldList->getField($fieldName)->getDataType()->getXml();
							foreach($dtXml as $subfieldName => $dbParams) {
								$matrixElt->setFieldValue($element->getFieldValue($fieldName, $subfieldName),$colName,$subfieldName);
							}
						}
					}
				}
				$i->setValue($i->getValue()+1);
			}
		})),true,null,$cacheKey);
		$this->debugLogger()->logEndOperation('fillMatrixFromElements');
	}
	
	/**
	 * Checks the posted values of a matrix form and fills the underlying Wigii bag.
	 * The number of rows is dynamic, at least starting row (fromRow index) should be defined in configuration file.
	 * FuncExp signature: <code>resolveFormMatrix(fromRow,toRow,col1,col2,...)</code><br/>
	 * Where arguments are :
	 * - Arg(0) fromRow: int. The start index from which to check the matrix rows.
	 * - Arg(1) toRow: int. The stop index to which to check the matrix rows.
	 * - Arg(2...) colI: string. The name of the matrix columns to be checked.
	 *
	 * @example calling resolveFormMatrix("1","4","articleNumber_","designation_","quantity_")
	 * check the posted form for the fields 
	 * articleNumber_1, designation_1, quantity_1,
	 * articleNumber_2, designation_2, quantity_2,
	 * articleNumber_3, designation_3, quantity_3,
	 * articleNumber_4, designation_4, quantity_4
	 * it will follow the same lifecycle as the FormExecutor::resolveForm function:
	 * 1. dynamically modifies Element FieldList to integrate new Fields to match the number of rows in matrix, 
	 * 2. calls FormChecker on each new fields, adds any error to field or fills underlying Wigii bag with the correct posted value
	 */
	public function resolveFormMatrix($args) {	
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs<3) throw new FuncExpEvalException('resolveFormMatrix takes at least three arguments which are the fromRow index, toRow index and at least one column name', FuncExpEvalException::INVALID_ARGUMENT);
		$fromRow = $this->evaluateArg($args[0]);
		$toRow = $this->evaluateArg($args[1]);
		$columns = array();
		for($i = 2; $i<$nArgs;$i++) {
			$columns[] = $this->evaluateArg($args[$i]);
		}
		$nCols = count($columns);
		
		$fe = $this->getFormExecutor();
		if(!isset($fe)) throw new FuncExpEvalException('resolveFormMatrix can only be called in the scope of a Form lifecycle, make sure to call it in htmlExp or funcExp attributes of a configuration file', FuncExpEvalException::INVALID_STATE);
		// extends Record FieldList with matrix fields (starting at row 2 as row 1 is already in the config file)
		$rec = $fe->getRecord();
		$fieldList = $rec->getFieldList();
		for($i=$fromRow+1;$i<=$toRow;$i++) {
			// creates row if doesn't exist
			if(!$fieldList->doesFieldExist($columns[0].$i)) {
				for($j=0;$j<$nCols;$j++) {
					// clones field of first row and renames
					$row1Field = $fieldList->getField($columns[$j].$fromRow);
					$rowJField = clone $row1Field;
					$rowJField->setFieldName($columns[$j].$i);
					// adds new field to row
					$fieldList->addField($rowJField);
				}
			}
		}
		// resolves matrix form lifecycle
		if($fe->getState() != "start") {
			// checks form
			$formChecker = $fe->getFormChecker();
			for($i=$fromRow+1;$i<=$toRow;$i++) {
				for($j=0;$j<$nCols;$j++) {
					$field = $fieldList->getField($columns[$j].$i);
					$field->acceptFieldListVisitor($formChecker);
				}
			}
		}
	}
	/**
	 * Clears the Element FieldList of the dynamically added matrix fields.
	 * FuncExp signature: <code>clearFormMatrix(fromRow,toRow,col1,col2,...)</code><br/>
	 * Where arguments are :
	 * - Arg(0) fromRow: int. The start index from which to clear the matrix row fields.
	 * - Arg(1) toRow: int. The stop index to which to clear the matrix row fields.
	 * - Arg(2...) colI: string. The name of the matrix columns to be cleared.
	 */
	public function clearFormMatrix($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs<3) throw new FuncExpEvalException('clearFormMatrix takes at least three arguments which are the fromRow index, toRow index and at least one column name', FuncExpEvalException::INVALID_ARGUMENT);
		$fromRow = $this->evaluateArg($args[0]);
		$toRow = $this->evaluateArg($args[1]);
		$columns = array();
		for($i = 2; $i<$nArgs;$i++) {
			$columns[] = $this->evaluateArg($args[$i]);
		}
		$nCols = count($columns);
		
		$fe = $this->getFormExecutor();
		if(!isset($fe)) throw new FuncExpEvalException('clearFormMatrix can only be called in the scope of a Form lifecycle, make sure to call it in htmlExp or funcExp attributes of a configuration file', FuncExpEvalException::INVALID_STATE);
		// reduces Record FieldList from matrix fields
		$rec = $fe->getRecord();
		$fieldList = $rec->getFieldList();
		if(!method_exists($fieldList, "removeField")) throw new FuncExpEvalException("FieldList ".get_class($fieldList)." does not support method removeField. Cannot clearFormMatrix.",FuncExpEvalException::UNSUPPORTED_OPERATION);
		for($i=$fromRow;$i<=$toRow;$i++) {			
			for($j=0;$j<$nCols;$j++) {
				// removes field if exists
				if($fieldList->doesFieldExist($columns[$j].$i)) $fieldList->removeField($columns[$j].$i);
			}
		}		
	}
	
	/**
	 * Saves a list of field values to a mapped list of fields into a specific element. 
	 * The element can belong to another namespace and module.
	 * If the target element doesn't exist, a new element in inserted.
	 * All subfields values are copied.
	 * FuncExp signature: <code>saveElementFieldsTo(fromFields,elementLogExp|elementId,groupLogExp|groupId,toFields,options=null)</code><br/>
	 * Where arguments are :
	 * - Arg(0) fromFields: Array. A list of field names from which to extract the values
	 * - Arg(1) elementLogExp|elementId: LogExp|int. The destination element selector.
	 * Can be an element ID or a business key log exp. If null, then element is always inserted.
	 * - Arg(2) groupLogExp|groupId: LogExp|int. The destination group selector. Can be an group ID or a group log exp.
	 * - Arg(3) toFields: Array. A list of mapping field names from the target element configuration
	 * - Arg(4) options: WigiiBPLParameter. A optional bag of options to configure the save element process. The following options are supported:
	 * 	persistModeExp: FuncExp. A FuncExp which is evaluated against the matching element and determines which persistence action should be done on it.
	 * 					It should return one of the constants ElementDFA::MODE_PERSIST (1), ElementDFA::MODE_DELETE (2) or ElementDFA::MODE_FILTER (4)
	 * @return ElementP the updated or new inserted element.
	 */
	public function saveElementFieldsTo($args) {		
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs<4) throw new FuncExpEvalException('saveElementFieldsTo takes at least four arguments which are the fromFields array of field names, element log exp or id to select target element, target group log exp or id and toFields mapping array', FuncExpEvalException::INVALID_ARGUMENT);
		$principal=$this->getPrincipal();
		$fe = $this->getFormExecutor();		
		if(!isset($fe)) throw new FuncExpEvalException('saveElementFieldsTo can only be called in the scope of a Form lifecycle, make sure to call it in funcExp attributes of a configuration file', FuncExpEvalException::INVALID_STATE);
		$configS = $fe->getWigiiExecutor()->getConfigurationContext();
		$exec = ServiceProvider::getExecutionService();
		
		$fromFields = $this->evaluateArg($args[0]);
		if(!($fromFields instanceof FieldSelectorList)) {
			$fsl = FieldSelectorListArrayImpl::createInstance(true,false);
			if(is_array($fromFields)) {
				foreach($fromFields as $fieldName) {
					$fsl->addFieldSelector($fieldName);
				}
			}
			$fromFields = $fsl;
		}
		if($fromFields->isEmpty()) throw new FuncExpEvalException('no element fields to save',FuncExpEvalException::INVALID_ARGUMENT);
		$elementLogExp = $this->evaluateArg($args[1]);
		if(isset($elementLogExp) && !($elementLogExp instanceof LogExp)) $elementLogExp = lxEq(fs_e('id'),$elementLogExp);
		$groupLogExp = $this->evaluateArg($args[2]);
		if(!isset($groupLogExp)) throw new FuncExpEvalException('groupLogExp cannot be null');
		if($groupLogExp instanceof LogExp) {
			$groupId = sel($principal,groupList($groupLogExp),dfasl(dfas("NullDFA")));
			$groupId = $groupId->getId();
		}
		else {
			$groupId = $groupLogExp;
			$groupLogExp = lxEq(fs('id'),$groupId);
		}
		$toFields = $this->evaluateArg($args[3]);
		if($nArgs>4) $options = $this->evaluateArg($args[4]);
		else $options = wigiiBPLParam();
		
		$returnValue = null;
		$crtNamespace = null; $hasAdaptiveWigiiNamespace = null;
		$this->debugLogger()->logBeginOperation('saveElementFieldsTo');
		try {
			$hasAdaptiveWigiiNamespace = $principal->hasAdaptiveWigiiNamespace();
			if(!$hasAdaptiveWigiiNamespace) {
				$crtNamespace = $principal->getWigiiNamespace();
				$principal->setAdaptiveWigiiNamespace(true);
			}
			
			// 1. extracts selected fields from record as stdClass
			$rec = $this->getRecord();
			if(!isset($rec)) throw new FuncExpEvalException('No record attached to evaluator', FuncExpEvalException::INVALID_STATE);
			$rec = $rec->toStdClass($fromFields);
			// 1.1 re-maps field names
			$i=0;
			foreach($fromFields->getListIterator() as $fs) {
				$toFieldName = $toFields[$i];
				// ignores any __element.id FieldSelector
				if($toFieldName instanceof FieldSelector && $toFieldName->isElementAttributeSelector()) {
					unset($rec->{$fs->getFieldName()});
				}
				elseif($toFieldName!= $fs->getFieldName()) {
					$rec->{$toFieldName} = $rec->{$fs->getFieldName()};
					unset($rec->{$fs->getFieldName()});
				}
				$i++;
			}
			// 2. inserts or updates element based on extracted selected fields
			$returnValue = ServiceProvider::getDataFlowService()->processDataSource($principal, array2df($rec), dfasl(
				/* 2.1 sets element values from record */
				dfas('MapObject2ElementDFA','setInGroupLogExp',lxInGR($groupLogExp),
					'setElementSelectorMethod',function($rec,$dataFlowContext) use($elementLogExp) {
						return $elementLogExp;
					},
					'setObject2ElementMappingMethod',function($rec,$elementP,$mapObject2ElementDFA) {
						$element = $elementP->getDbEntity();
						$element->getWigiiBag()->resetChanges();
						foreach($rec as $fieldName=>$subfields) {
							foreach($subfields as $subFieldName=>$value) {
								$element->setFieldValue($value,$fieldName,$subFieldName);
							}
						}
						$mapObject2ElementDFA->writeResultToOutput($elementP);
					}
				),
				/* 2.2 fetches old record for file management and old gids */
				dfas('CallbackDFA','setProcessWholeDataCallback',function($elementP,$callbackDFA) use($exec,$groupLogExp,$fe,$configS){
					$dfContext = $callbackDFA->getDataFlowContext();
					$p = $dfContext->getPrincipal();
					$element = $elementP->getDbEntity();
					if(!$element->isNew()) {
						// fetches old record
						$oldRecord = sel($p,elementPList(lxInGR($groupLogExp),lf(null,lxEq(fs_e('id'),$element->getId()))),dfasl(dfas("NullDFA")));
						$oldRecord = $oldRecord->getElement();
						$dfContext->setAttribute('oldRecord',$oldRecord);
						// saves current auto-sharing group ids
						$oldGids = ValueListArrayMapper::createInstance ( true, ValueListArrayMapper::Natural_Separators, true );
						$oldRecord->getLinkedIdGroupInRecord ($p, $oldGids);
						$dfContext->setAttribute('oldGids',$oldGids->getListIterator());
					}
					// stores an initialized FormExecutor on current element to allow update files on disk later on.
					$dfContext->setAttribute('FormExecutor',BasicFormExecutor::createInstance($fe->getWigiiExecutor(), $element, null, null));
					$dfContext->setAttribute('storeFileInWigiiBag',$configS->getParameter($p, null, "storeFileContentIntoDatabase") == "1");
					$callbackDFA->writeResultToOutput($elementP);
				}),
				/* 2.3 element recalculation */
				dfas('ElementRecalcDFA'),
				/* 2.4 persist element */
				dfas('ElementDFA','setMode',ElementDFA::MODE_MIXED,'setGroupId',$groupId,'setDecisionMethod',function($elementP,$dataFlowContext) use($options) {
					// if a persistModeExp is given into the options, then evaluates it to determine what should be done with the element
					$persistModeExp = $options->getValue('persistModeExp');
					if(isset($persistModeExp)) {
						return ServiceProvider::getWigiiBPL()->evaluateFuncExp($dataFlowContext->getPrincipal(), $persistModeExp, $elementP->getDbEntity());
					}
					// else always persists the element
					else return ElementDFA::MODE_PERSIST;
				}),
				/* 2.5 update files on disk */ 
				dfas('CallbackDFA','setProcessWholeDataCallback',function($elementP,$callbackDFA) use($exec){
					$dfContext = $callbackDFA->getDataFlowContext();
					$dfContext->getAttribute('FormExecutor')->updateFilesOnDisk($dfContext->getPrincipal(), $exec, $dfContext->getAttribute('storeFileInWigiiBag'), $dfContext->getAttribute('oldRecord'), false);
					$callbackDFA->writeResultToOutput($elementP);
				}),
				/* 2.6 updates autosharing */
				dfas('CallbackDFA','setProcessWholeDataCallback',function($elementP,$callbackDFA) {
					$dfContext = $callbackDFA->getDataFlowContext();
					ServiceProvider::getWigiiBPL()->elementUpdateSharing($dfContext->getPrincipal(), $callbackDFA, wigiiBPLParam(
						'element',$elementP->getDbEntity(),
						'oldGroupIds',$dfContext->getAttribute('oldGids')						
					));
					$callbackDFA->writeResultToOutput($elementP);
				})
			),true/*,$fe->getWigiiExecutor()->throwEvent() notifications not enabled for now.*/);
			
			if(isset($crtNamespace)) {
				$principal->bindToWigiiNamespace($crtNamespace);
				if(!$hasAdaptiveWigiiNamespace) $principal->setAdaptiveWigiiNamespace(false);
			}
		}
		catch(Exception $e) {
			if(isset($crtNamespace)) {
				$principal->bindToWigiiNamespace($crtNamespace);
				if(!$hasAdaptiveWigiiNamespace) $principal->setAdaptiveWigiiNamespace(false);
			}
			throw $e;
		}
		$this->debugLogger()->logEndOperation('saveElementFieldsTo');
	}
	
	/**
	 * Saves a list of field values to a mapped list of fields into a specific element.
	 * The element can belong to another namespace and module.
	 * If the target element doesn't exist, a new element in inserted.
	 * All subfields values are copied.
	 * FuncExp signature: <code>saveMatrixTo(fromRow,toRow,columns,toFields,keyColumn,groupLogExp|groupId,options=null)</code><br/>
	 * Where arguments are :
	 * - Arg(0) fromRow: int. The start index from which to save the matrix rows.
	 * - Arg(1) toRow: int. The stop index to which to save the matrix rows.
	 * - Arg(2) columns: Array. The names of the matrix columns to be saved.
	 * - Arg(3) toFields: Array. A list of mapping field names from the target element configuration
	 * - Arg(4) keyColumn: String. The name of the column in the matrix used as a business key to select the target element to update.
	 * - Arg(5) groupLogExp|groupId: LogExp|int. The destination group selector. Can be an group ID or a group log exp.
	 * - Arg(6) options: WigiiBPLParameter. A optional bag of options to configure the save element process. The following options are supported:
	 * 	persistModeExp: FuncExp. A FuncExp which is evaluated against the matching destination element and determines which persistence action should be done on it.
	 * 					It should return one of the constants ElementDFA::MODE_PERSIST (1), ElementDFA::MODE_DELETE (2) or ElementDFA::MODE_FILTER (4)	 
	 * @return ElementPList an ElementPList containing the updated or new inserted elements.
	 */
	public function saveMatrixTo($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs<6) throw new FuncExpEvalException('saveMatrixTo takes at least six arguments which are the fromRow index, toRow index, columns array, toFields mapping array, keyColumn and target group log exp or id', FuncExpEvalException::INVALID_ARGUMENT);
		$fromRow = $this->evaluateArg($args[0]);
		$toRow = $this->evaluateArg($args[1]);
		$columns = $this->evaluateArg($args[2]);
		$nCols = count($columns);
		$toFields = $this->evaluateArg($args[3]);
		$keyColumn = $this->evaluateArg($args[4]);
		$groupLogExp = $this->evaluateArg($args[5]);
		if($nArgs>6) $options = $this->evaluateArg($args[6]);
		else $options = null;
		
		// calls saveElementFieldsTo on each matrix row
		$rec = $this->getRecord();
		$fieldList = $rec->getFieldList();
		$fx = fx('saveElementFieldsTo');
		$keyColumnIndex = null;
		$returnValue = ElementPListArrayImpl::createInstance();
		for($i=$fromRow;$i<=$toRow;$i++) {
			$row = array();			
			for($j=0;$j<$nCols;$j++) {
				// looks for key column index to build element selector
				if(!isset($keyColumnIndex) && isset($keyColumn) && ($columns[$j]==$keyColumn)) $keyColumnIndex=$j;
				// checks if column name is a matrix field or a standard element field
				$fieldName = $columns[$j].$i;
				if(!$fieldList->doesFieldExist($fieldName)) $fieldName = $columns[$j];
				if(!$fieldList->doesFieldExist($fieldName)) throw new FuncExpEvalException("field $fieldName is not a valid field in matrix columns or element",FuncExpEvalException::INVALID_ARGUMENT);
				$row[] = $fieldName;
			}
			$elementLogExp=null;
			if(isset($keyColumnIndex)) {
				$elementLogExp=$rec->getFieldValue($row[$keyColumnIndex]);				
				if(!empty($elementLogExp)) {
					$fsKey = $toFields[$keyColumnIndex];
					if(!($fsKey instanceof FieldSelector)) $fsKey = fs($fsKey);
					$elementLogExp = lxEq($fsKey,$elementLogExp);
				}
				else $elementLogExp=null;
			}
			$fx->setArguments(array($row,
				$elementLogExp,
				$groupLogExp,
				$toFields,
				$options
			));
			$elementP = $this->evaluateFuncExp($fx,$this);
			if(isset($elementP)) $returnValue->addElementP($elementP);
		}
		return $returnValue;
	}
	
	// System functions
	
	/**
	 * Executes a given func exp with an ExecutionSink enabled that can be used to log messages.
	 * The log is then stored into the given field of type Blobs.
	 * FuncExp signature: <code>sysLogExec(fx, fs, flush=false, executionSinkClassName=StringBufferExecutionSink)</code><br/>
	 * Where arguments are :
	 * - Arg(0) fx: FuncExp. The func exp to execute.
	 * - Arg(1) fs: FieldSelector. The field selector of type Blobs in which to store the log
	 * - Arg(2) flush: Boolean. If true, then the existing log is emptied and replaced by the new one, else new log is added to existing one. Default to false.
	 * - Arg(3) executionSinkClassName: String. Sets the ExecutionSink implementation to be used to log messages.
	 * Should be a subclass of StringBufferExecutionSink. Defaults to StringBufferExecutionSink.
	 */
	public function sysLogExec($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs > 0) {
			// gets fx
			$fx = $args[0];
			if(!($fx instanceof FuncExp)) throw new RecordException('fx argument should be an instance of a FuncExp', RecordException::INVALID_ARGUMENT);
			// gets fs and checks that it is a field of type Blobs
			if($nArgs > 1) {
				$fs = $args[1];
				if($fs instanceof FieldSelector) {
					$f = $this->getRecord()->getFieldList()->getField($fs->getFieldName());
					if(is_null($f) || !($f->getDataType() instanceof Blobs)) $fs = null;					
				}
				else $fs = null;
				if(is_null($fs)) throw new RecordException('fs argument should be an instance of a FieldSelector pointing to a field of type Blobs', RecordException::INVALID_ARGUMENT);
			}
			else $fs = null;
			// flush log
			if($nArgs > 2) $flushLog = $this->evaluateArg($args[2]);
			else $flushLog = false;
			// execution sink class
			if($nArgs > 3) {
				$executionSinkClass = $this->evaluateArg($args[3]);
				if(empty($executionSinkClass)) $executionSinkClass = $this->sysLogExec_getDefaultStringBufferExecutionSinkClass();
			}
			else $executionSinkClass = $this->sysLogExec_getDefaultStringBufferExecutionSinkClass();
			
			// if fs is set and vm is activated, then creates an ExecutionSink
			if(isset($fs)) {
				$vm = $this->funcExpVM;
				if(!isset($vm) && $this->callingFuncExpEvaluator instanceof FuncExpVM) $vm = $this->callingFuncExpEvaluator;
				if(isset($vm)) {
					$executionSink = $this->sysLogExec_getStringBufferExecutionSink($executionSinkClass);
					$vm->getFuncExpVMServiceProvider()->setExecutionSink($executionSink);
				}
				else $executionSink = null;
				// gets field xml
				$fxml = $f->getXml(); 
			}
			
			// executes the func exp
			$startTime = microtime(true);
			try {
				$returnValue = $this->evaluateArg($fx);
				$endTime = microtime(true);
			}
			catch(Exception $e) {
				$endTime = microtime(true);
				// adds error to form if defined
				$form = $this->getFormExecutor();
				if(isset($form)) {
					if($e instanceof ServiceException) $e = $e->getWigiiRootException();
					$errorMessage = "Exception while evaluating func exp '".$fx->getName()."'. (".$e->getCode().") ".$e->getMessage();
					$form->addErrorToField($errorMessage, $this->getCurrentField()->getFieldName());
				}
				else throw $e;
			}
			
			// saves log
			if(isset($fs)) {
				if($fxml["htmlArea"] == "1"){
					$header .= '<p style="color:#666;">&gt; ';
				} else {
					$header .= "> ";
				}
				$header .= udate('d.m.Y H:i:s', $startTime)." ".$this->getPrincipal()->getRealUsername();
				if($fxml["htmlArea"] == "1"){
					$header .= "</p>";
				} else {
					$header .= "\n";
				}
				$log = '';
				if(isset($executionSink)) {
					$s = $executionSink->getString();
					if(!empty($s)) $log .= $s."\n";
				}
				if(isset($e)) {
					$log .= "\n".str_replace('\\', '/', (string)$e)."\n";
				}
				$log .= "duration: ".number_format($endTime-$startTime, 1, '.','')."s"."\n";
				if($fxml["htmlArea"] == "1") {
					$log = str_replace("\n", '<br/>', $log);
				}
				
				$log = $header.$log;
				if($fxml["htmlArea"] == "1"){
					$log .= "<p>&nbsp;</p>";
				} else {
					$log .= "\n";
				}
				if(!$flushLog) {
					if($fxml["isJournal"] == "1") {
						$log .= $this->getFieldValue($fs);
					}
					else {
						$log = $this->getFieldValue($fs).$log;
					}
				}
				
				$this->setFieldValue($fs, $log);
			}
			return $returnValue;
		}
	}
	protected function sysLogExec_getDefaultStringBufferExecutionSinkClass() {
		return 'StringBufferExecutionSink';
	}
	protected function sysLogExec_getStringBufferExecutionSink($className) {
		$returnValue = ServiceProvider::createWigiiObject($className);
		if(!($returnValue instanceof StringBufferExecutionSink)) throw new ServiceProviderException("class $className is not a subclass of StringBufferExecutionSink", ServiceProviderException::INVALID_ARGUMENT);
		$returnValue->reset();
		return $returnValue;
	}
	
	/**
	 * Tracks the history of the changes of some given fields and stores it into the current field of type Blobs
	 * FuncExp signature : <code>trackHistory(field1, field2, ...)</code><br/>
	 * Where arguments are :
	 * - Arg(0..n) fieldI: FieldSelector. The fields which need history to be tracked
	 * @return String the updated content of the history blobs.
	 */
	public function trackHistory($args){
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new RecordException('trackHistory takes at least one argument which is a FieldSelector pointing to the field which needs history to be tracked', RecordException::INVALID_ARGUMENT);
		if(!($this->getCurrentField()->getDataType() instanceof Blobs)) throw new RecordException('trackHistory should be attached to a field of type Blobs', RecordException::CONFIGURATION_ERROR);
		$currentVal = $this->getRecord()->getFieldValue($this->getCurrentField()->getFieldName());
		$result = "";
	
		for($i = 0; $i<$nArgs; $i++) {
			$fs= $args[$i];
			if(!($fs instanceof FieldSelector)) throw new RecordException('trackHistory takes arguments which should be FieldSelectors pointing to the fields which needs history to be tracked', RecordException::INVALID_ARGUMENT);
				
			if(is_a($this->getRecord()->getWigiiBag(), "FormBag") && $this->getRecord()->getWigiiBag()->isChanged($fs->getFieldName())){
				$fieldChanged = true;
				$isMultipleEdit = false;
			}
			else if(is_a($this->getFormExecutor(), "EditMultipleElementFormExecutor") && $this->getFormExecutor()->getRecordWigiiBag()->isMultipleChecked($fs->getFieldName())) {
				$fieldChanged = true;
				$isMultipleEdit = true;
			}
			else {
				$fieldChanged = false;
				$isMultipleEdit = false;
			}
			if($fieldChanged) {
				$value =  $this->getTrm()->formatValueFromFS($fs, $this->getRecord());
				$fxml=$this->getCurrentField()->getXml();
	
				$isHtmlArea = ($fxml["htmlArea"] == "1");
				$isJournal = ($fxml["isJournal"] == "1");
	
				$header = date("d.m.Y H:i")." ".$this->getPrincipal()->getRealUsername();
				if($nArgs > 1 || $isJournal) {
					$fieldLabel = $this->getTrm()->t($fs->getFieldName(), $this->getRecord()->getFieldList()->getField($fs->getFieldName())->getXml());
				}
				else {
					$fieldLabel = 'value';
				}
	
				// removes html from value and field label if not an html area
				if(!$isHtmlArea) {
					$html2text = new Html2text();
					$html2text->setHtml($value);
					$value = $html2text->getText();
// 					$html2text->clear();
						
					$html2text->setHtml($fieldLabel);
					$fieldLabel = $html2text->getText();
// 					$html2text->clear();
				}
	
				if($isMultipleEdit) {
					$value = "batch changed $fieldLabel to: ".$value;
				}
				else {
					$value = "changed $fieldLabel to: ".$value;
				}
	
				if($isJournal) {
					if($isHtmlArea) {
						$result .= '<p style="color:#666;">&gt; ';
						$result .= $header;
						$result .= "</p>";
						$result .= '<p>'.$value.'</p>';
						$result .= "<p>&nbsp;</p>";
					}
					else {
						$result .= "> ";
						$result .= $header;
						$result .= "\n";
						$result .= $value."\n";
						$result .= "\n";
					}
				}
				else {
					if($isHtmlArea) {
						$result .= "<p>".$header.' '.$value."</p>";
						$result .= "<p>&nbsp;</p>";
					}
					else {
						$result .= $header.' '.$value."\n";
						$result .= "\n";
					}
				}
			}
		}
	
		$result .= $currentVal;
		return $result;
	}
	
	// Data type validation functions
	
	/**
	 * Checks that two fields of type Files have their uploaded file versions in sync.
	 * FuncExp signature: <code>checkFilesVersionSync(file1, file2, showError=1)</code><br/>
	 * Where arguments are :
	 * - Arg(0) file1: a field selector pointing to a field of type Files.
	 * - Arg(1) file2: a field selector pointing to a field of type Files.
	 * - Arg(2) showError: optional boolean. If true, then out of sync error is added to the field, else does not show any error in the form. 
	 * By default, errors are shown if FormExecutor is attached to evaluator.
	 * Checks that file1.version = file2.version, if not in sync and showError, then adds error to file1.
	 * @return boolean true if the two fields have uploaded files which versions are in sync, false otherwise.
	 * Returns true if the fields are empty.
	 */
	public function checkFilesVersionSync($args) {
		$nArgs = $this->getNumberOfArgs($args);
		// checks and gets arguments file1 and file2
		$invalidArgMsg = 'function checkFilesVersionSync takes two arguments which should be field selectors pointing to existing fields of type Files';
		if($nArgs < 2) throw new RecordException($invalidArgMsg, RecordException::INVALID_ARGUMENT); 
		$file1 = $args[0];
		if(!($file1 instanceof FieldSelector)) throw new RecordException($invalidArgMsg, RecordException::INVALID_ARGUMENT);
		$file1 = $this->getRecord()->getFieldList()->doesFieldExist($file1->getFieldName());
		$file2 = $args[1];
		if(!($file2 instanceof FieldSelector)) throw new RecordException($invalidArgMsg, RecordException::INVALID_ARGUMENT);
		$file2 = $this->getRecord()->getFieldList()->doesFieldExist($file2->getFieldName());
		if(!isset($file1) || !isset($file2)) throw new RecordException($invalidArgMsg, RecordException::INVALID_ARGUMENT);
		if(!($file1->getDataType() instanceof Files) || !($file2->getDataType() instanceof Files)) throw new RecordException($invalidArgMsg, RecordException::INVALID_ARGUMENT);
		
		if($nArgs > 2) {
			$showError = !($this->evaluateArg($args[2]) == false);
		}
		else $showError = true;
		
		// checks file version sync
		if($this->getRecord()->getFieldValue($file1->getFieldName(), 'name') && $this->getRecord()->getFieldValue($file2->getFieldName(), 'name')) {
			$versionSync = ($this->getRecord()->getFieldValue($file1->getFieldName(), 'version') == $this->getRecord()->getFieldValue($file2->getFieldName(), 'version'));
		}
		else {
			$versionSync = (!($this->getRecord()->getFieldValue($file1->getFieldName(), 'name')) && !($this->getRecord()->getFieldValue($file2->getFieldName(), 'name')));
		}
		if(!$versionSync) {
			$form = $this->getFormExecutor();
			// show error in form
			if(isset($form) && $showError) {
				$trm = $this->getTrm();
				$errorMessage = $trm->t($file1->getFieldName(), $file1->getXml());
				$errorMessage .= ' '.$trm->t('filesVersionNotInSync').' ';
				$errorMessage .= $trm->t($file2->getFieldName(), $file2->getXml()).' ';
				$form->addErrorToField($errorMessage, $file1->getFieldName());
			}
		}
		return $versionSync;
	}
}
/**
 * A LogExp evaluator on a matrix extracted from a Record
 * Created by CWE on Sept. 8th 2015.
 */
class RecordMatrixLogExpEvaluator extends FieldSelectorLogExpAbstractEvaluator {
	private $_debugLogger;
	private $matrix;
	private $currentRow;
	
	// Object life cycle
	
	public static function createInstance() {
		$returnValue = new self();
		return $returnValue;
	}
	
	public function reset() {
		$this->freeMemory();
	}
	public function freeMemory() {
		unset($this->currentRow);
		unset($this->matrix);
	}
	
	// Dependency injection
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("RecordMatrixLogExpEvaluator");
		}
		return $this->_debugLogger;
	}
	
	// Implementation
	
	/**
	 * Evaluates a LogExp against each row of a given matrix, sets the boolean result to the result field of each row.  
	 * @param stdClass $matrix the matrix against which to evaluate the log exp.
	 * @param LogExp $logExp the log exp to evaluate. Can combine columns of a row and calculated fields at the matrix level.
	 * @param string $resultField the name of the column in the row in which to set the boolean result. Defaults to 'resultFlag'.
	 * @return boolean returns true if logExp evaluated to true for all rows, else false.
	 * @throws Exception in case of error.
	 */
	public function evaluate($matrix, $logExp, $resultField='resultFlag') {
		if(!(is_object($matrix))) throw new RecordException('matrix should be a StdClass instance', RecordException::INVALID_ARGUMENT);
		if(!($logExp instanceof LogExp)) throw new RecordException('logExp should be a LogExp instance', RecordException::INVALID_ARGUMENT);
		$returnValue = true;
		$this->debugLogger()->logBeginOperation('evaluate');
		$this->reset();
		$this->matrix = $matrix;
		$rows = $this->matrix->{'rows'};
		// evaluates log exp on each row
		if(!empty($rows)) {
			foreach($rows as $row) {
				$this->currentRow = $row;
				$result = $logExp->acceptLogExpVisitor($this);
				$row->{$resultField} = $result;
				if(!$result) $returnValue = false; 
			}
		}
		$this->debugLogger()->logEndOperation('evaluate');
		return $returnValue;
	}
	
	// LogExpVisitor implementation
	
	protected function getValue($obj){
		$returnValue = null;
		// if field is defined in row, then returns its value
		$fieldName = $obj->getFieldName();
		$fieldValue = $this->currentRow->{$fieldName};
		if(is_object($fieldValue)) {
			$subFieldName = $obj->getSubFieldName();
			if(is_null($subFieldName)) $subFieldName = 'value';
			$returnValue = $fieldValue->{$subFieldName};
		}
		elseif($fieldValue != null) $returnValue = $fieldValue;
		// else looks at matrix level
		else {
			$fieldValue = $this->matrix->{$fieldName};
			// if object then returns subfield value
			if(is_object($fieldValue)) {
				$subFieldName = $obj->getSubFieldName();
				if(is_null($subFieldName)) $subFieldName = 'value';
				$returnValue = $fieldValue->{$subFieldName};
			}						
			// else if array then distributes values in rows using index
			elseif(is_array($fieldValue)) {
				// retrieves index
				$index = $this->matrix->{'index'};
				if(isset($index)) $index = $index[$fieldName];
				// if there is no index that can be used to distribute the values, then returns the array
				if(empty($index)) $returnValue = $fieldValue;
				else {
					foreach($fieldValue as $key => $value) {
						// retrieves matching rows using the index
						$rows = $index[$key];
						// distribute value on all matching rows
						if(!empty($rows)) {
							foreach($rows as $row) {
								$row->{$fieldName} = $value;
							}
						}
					}
					// returns distributed value
					$returnValue = $this->currentRow->{$fieldName};
				}
			}
			else $returnValue = $fieldValue;
		}
		return $returnValue;
	}
}