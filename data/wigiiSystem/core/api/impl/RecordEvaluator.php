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
 * Gives the infrastructure to evaluate a record against funcExp attached to a field.
 * Base class, subclass can extend the FuncExp language.
 * Created by CWE on 17 avr. 10
 * Modified by CWE on 07 mars 2014 to activate by default the FuncExpVM when evaluating a record.
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
	 * @param Record $record a record instance defining the context of evaluation
	 */
	public function setContext($principal, $record) {
		if(is_null($record)) throw new RecordException("record cannot be null", RecordException::INVALID_ARGUMENT);
		if(is_null($principal)) throw new RecordException("principal cannot be null", RecordException::INVALID_ARGUMENT);
		if($this->lockedForEvaluation) throw new RecordException('the record evaluator is already in use', RecordException::INVALID_STATE);
		$this->lockedForEvaluation = true;
		$this->principal = $principal;
		$this->record = $record;
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
		elseif(is_null($caller) && isset($this->callingFuncExpEvaluator)) return $this->callingFuncExpEvaluator->evaluateFuncExp($funcExp);
		else {
			if($caller instanceof FuncExpEvaluator) $this->callingFuncExpEvaluator = $caller;
			if($funcExp instanceof FuncExp) {
				$fName = $funcExp->getName();
				if(method_exists($this, $fName)) {
					$returnVal = $this->$fName($funcExp->getArguments());
					if($this->debugLogger()->isEnabled()) $this->debugLogger()->write($fName." returns ".$returnVal);
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
		return $this->record->getFieldValue($this->currentField->getFieldName(), $subFieldName);
	}

	/**
	 * Updates current field subfield value in wigii bag with new value
	 */
	protected function updateCurrentFieldSubFieldValue($subFieldName, $newValue)
	{
		$this->record->setFieldValue($newValue, $this->currentField->getFieldName(), $subFieldName);
	}

	/**
	 * Returns the value of a field stored in underlying wigiiBag given a fieldSelector
	 */
	protected function getFieldValue($fieldSelector)
	{
		if(is_null($fieldSelector)) throw new RecordException("fieldSelector cannot be null", RecordException::INVALID_ARGUMENT);
		return $this->record->getFieldValue($fieldSelector->getFieldName(), $fieldSelector->getSubFieldName());
	}

	/**
	 * Sets the value of a field stored in underlying wigiiBag given a fieldSelector
	 */
	protected function setFieldValue($fieldSelector, $value) {
		if(is_null($fieldSelector)) throw new RecordException("fieldSelector cannot be null", RecordException::INVALID_ARGUMENT);
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
				if($r != null) $temp[]=$r;
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
				if($r != null) $temp[]=$r;
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
				if($r != null) $c++;
			}
			return $c;
		}
		else return 0;
	}

	/**
	 * the args should be a date only
	 * returns mm.dd --> this allow to sort or group based only on the birthday and not on the year
	 */
	public function calculateBirthday($args){
		if($this->getNumberOfArgs($args) != 1) throw new RecordException("args should have 1 date parameter");
		$r = $this->evaluateArg($args[0]);
		if($r == null) return null;
		return substr($r, 3, 2).".".substr($r, 0, 2);
	}

	/**
	 * firs arg: value if all further args are not null
	 * if one of further args is null, then returns null
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
		if($this->getNumberOfArgs($args) < 2) throw new RecordException("args should have at least 2 parameters", RecordException::INVALID_ARGUMENT);
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

	public function div($args){
		if($this->getNumberOfArgs($args) > 0 && $this->getNumberOfArgs($args)<3) {
			$a = $this->evaluateArg($args[0]);
			$b = $this->evaluateArg($args[1]);
			if($b==null) return "NA";
			return $a / $b;
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
	 * Updates a value in the wigii bag, given one or several field selectors
	 * Last argument is the value to be set, all previous arguments should evaluate to field selectors.
	 * Example: setVal(f1, f2.name, f3.city, "unknown")
	 * will update field f1, subfields f2.name, f3.city with value "unknown"
	 * @return the updated value
	 */
	public function setVal($args) {
		$n = $this->getNumberOfArgs($args);
		if($n < 2) throw new RecordException("For setVal, the number of arguments should be at least 2", RecordException::INVALID_ARGUMENT);
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
		}
		return $val;
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
					$html2text->html2text($value);
					$value = $html2text->get_text();
					$html2text->clear();
						
					$html2text->html2text($fieldLabel);
					$fieldLabel = $html2text->get_text();
					$html2text->clear();
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