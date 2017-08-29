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
 * The Func Exp VM Standard Library
 * Created by CWE on 1er octobre 2013
 * Modified by CWE on 14 octobre 2013
 * Modified by Medair (CWE) on 28.11.2016 to protect against Cross Site Scripting
 * Modified by Medair in 2016 for maintenance purposes (see SVN log for details)
 */
class FuncExpVMStdFL extends FuncExpVMAbstractFL
{
	// Dependency injection

	private $_debugLogger;
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("FuncExpVMStdFL");
		}
		return $this->_debugLogger;
	}

	private $dflowS;
	/**
	 * Injects a DataFlowService to be used by this library
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

	private $fxParser;
	public function setFieldSelectorFuncExpParser($funcExpParser) {
		$this->fxParser = $funcExpParser;
	}
	protected function getFieldSelectorFuncExpParser() {
		// autowired
		if(!isset($this->fxParser)) {
			$this->fxParser = TechnicalServiceProvider::getFieldSelectorFuncExpParser();
		}
		return $this->fxParser;
	}

	private $translationService;
	public function setTranslationService($translationService){
	    $this->translationService = $translationService;
	}
	protected function getTranslationService(){
	    //autowired
	    if(!isset($this->translationService)){
	        $this->translationService = ServiceProvider::getTranslationService();
	    }
	    return $this->translationService;
	}
		
	// Generators

	/**
	 * Generates a flow of data and processes it through a DataFlow.<br/>
	 * FuncExp signature : <code>gen(funcExpIterator, dataFlowActivitySelectorList)</code><br/>
	 * Where arguments are :
	 * - Arg(0) funcExpIterator: evaluates to a FuncExpIterator instance which is used to generate a flow of data
	 * - Arg(1) dataFlowActivitySelectorList: evaluates to a DataFlowActivitySelectorList which describes a DataFlow to process the data
	 * - Arg(2) stepInto : optional argument which evaluates to a boolean. Defaults to true.
	 * If true then instructs the iterator to go recursively down, else only iterates first level. See FuncExpIterator for more details.
	 */
	public function gen($args) {
		$isOriginPublic = $this->isFxOriginPublic();
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 2) throw new FuncExpEvalException('For the generator function the number of arguments should be 2: a FuncExpIterator and a DataFlowActivitySelectorList', FuncExpEvalException::INVALID_ARGUMENT);
		// gets func exp iterator
		$funcExpQueue = null; $returnValue = null;
		try {
			if($args[0] instanceof FuncExpIterator) $iterator = $args[0];
			elseif(isset($args[0])) {
				// puts a new func exp queue on the stack that can be used by arg(0)
				$funcExpQueue = $this->getFuncExpVMServiceProvider()->getFuncExpQueue(true);
				// evaluates arg(0) and gets the func exp iterator
				$iterator = $this->evaluateArg($args[0]);
				if(isset($iterator) && !($iterator instanceof FuncExpIterator)) throw new FuncExpEvalException('first argument should evaluate to a valid FuncExpIterator instance', FuncExpEvalException::INVALID_ARGUMENT);
			}
			else $iterator = null;

			if(isset($iterator)) {
				// gets data flow activity selector list
				if($args[1] instanceof DataFlowActivitySelectorList) $dfasl = $args[1];
				else {
					$dfasl = $this->evaluateArg($args[1]);
					if(isset($dfasl) && !($dfasl instanceof DataFlowActivitySelectorList)) throw new FuncExpEvalException('second argument should evaluate to a valid DataFlowActivitySelectorList instance', FuncExpEvalException::INVALID_ARGUMENT);
				}
				
				// gets stepInto optional arg
				if($nArgs > 2 && isset($args[2])) {
					$stepInto = $this->evaluateArg($args[2]);
					if($stepInto) $stepInto = true;
					else $stepInto = false;
				}
				else $stepInto = true;

				$currentVal = $iterator->getCurrentFuncExpValue();
				$iterator = $iterator->evaluateNextFuncExp($stepInto);
				$dataFlowExists = isset($dfasl);
				$streamExists = isset($iterator);
				$dfs = $this->getDataFlowService();
				if($dataFlowExists) {
					if($isOriginPublic) $dfasl->setOriginIsPublic();
					// if more than one data chunk then opens stream
					if($streamExists) {
						$dfCtx = $dfs->startStream($this->getPrincipal(), $dfasl);
						$dfs->processDataChunk($currentVal, $dfCtx);
					}
					// else processes whole data at once
					else $returnValue = $dfs->processWholeData($this->getPrincipal(), $currentVal, $dfasl);
				}
				if($streamExists) {
					while(isset($iterator))	{
						if($dataFlowExists) $dfs->processDataChunk($iterator->getCurrentFuncExpValue(), $dfCtx);
						$iterator = $iterator->evaluateNextFuncExp($stepInto);
					}
					if($dataFlowExists) $returnValue = $dfs->endStream($dfCtx);
					else $returnValue = null;
				}
			}
			else $returnValue = null;
		}
		catch(Exception $e) {
			if(isset($funcExpQueue)) $funcExpQueue->freeMemory();
			throw $e;
		}
		if(isset($funcExpQueue)) $funcExpQueue->freeMemory();
		return $returnValue;
	}

	/**
	 * Generates a specified quantity of objects using a given constructor.<br/>
	 * FuncExp signature : <code>genQuantity(constructor, quantity, ordinal)</code><br/>
	 * Where arguments are :
	 * - Arg(0) constructor: a FuncExp which returns a new object.
	 * The ordinal is added as last argument to the constructor before beeing called, so that it can be used if needed.
	 * - Arg(1) quantity: evaluates to a positive integer
	 * - Arg(2) ordinal: optional value which evaluates to a positive integer
	 * and indicates the ordinal of the next object to be created.
	 * @return FuncExpIterator a FuncExpIterator instance to browse through the list.
	 */
	public function genQuantity($args) {
		$isOriginPublic = $this->isFxOriginPublic();
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1 || empty($args[0]) || !($args[0] instanceof FuncExp)) throw new FuncExpEvalException("the genQuantity funcexp takes two arguments: a constructor which is a FuncExp and a quantity which is an integer", FuncExpEvalException::INVALID_ARGUMENT);
		$constructor = $args[0];
		$quantity = 0;
		if($nArgs > 1 && isset($args[1])) {
			$quantity = $this->evaluateArg($args[1]);
		}
		if(!($quantity >= 0)) throw new FuncExpEvalException("the quantity should evaluate to a positive integer", FuncExpEvalException::INVALID_ARGUMENT);
		$ordinal = 1;
		if($nArgs > 2 && isset($args[2])) {
			$ordinal = $this->evaluateArg($args[2]);
		}
		if(!($ordinal > 0)) throw new FuncExpEvalException("the ordinal should evaluate to a positive integer", FuncExpEvalException::INVALID_ARGUMENT);
		if($quantity > 0) {
			if($constructor instanceof FuncExp) {
				$orderedConstructor = FuncExp::createInstance($constructor->getName(), $constructor->getArguments());
				$orderedConstructor->addArgument($ordinal);
			}
			else $orderedConstructor = $constructor;
			if($isOriginPublic) $orderedConstructor->setOriginIsPublic();
			return $this->getFuncExpVMServiceProvider()->getFuncExpIteratorForRecursiveCall($this->evaluateArg($orderedConstructor),
			array($constructor, $quantity-1, $ordinal+1));
		}
		else return null;
	}

	/**
	 * Generates some objects using a given constructor as long as a condition is true
	 * FuncExp signature : <code>genIfTrue(constructor, condition, ordinal)</code><br/>
	 * Where arguments are :
	 * - Arg(0) constructor: a FuncExp which returns a new object
	 * - Arg(1) condition: a FuncExp which returns a boolean
	 * - Arg(2) ordinal: optional value which evaluates to a positive integer
	 * and indicates the ordinal of the next object to be created.
	 * @return FuncExpIterator a FuncExpIterator instance to browse through the list.
	 */
	public function genIfTrue($args) {
		$isOriginPublic = $this->isFxOriginPublic();
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 2 || empty($args[0]) || !($args[0] instanceof FuncExp)
		|| empty($args[1]) || !($args[1] instanceof FuncExp)) throw new FuncExpEvalException("the genQuantity funcexp takes two arguments of type FuncExp: a constructor and a condition", FuncExpEvalException::INVALID_ARGUMENT);
		$constructor = $args[0];
		$condition = $args[1];
		$ordinal = 1;
		if($nArgs > 2 && isset($args[2])) {
			$ordinal = $this->evaluate($args[2]);
		}
		if(!($ordinal > 0)) throw new FuncExpEvalException("the ordinal should evaluate to a positive integer", FuncExpEvalException::INVALID_ARGUMENT);

		if($this->evaluateArg($condition)) {
			if($constructor instanceof FuncExp) {
				$orderedConstructor = FuncExp::createInstance($constructor->getName(), $constructor->getArguments());
				$orderedConstructor->addArgument($ordinal);
			}
			else $orderedConstructor = $constructor;
			if($isOriginPublic) $orderedConstructor->setOriginIsPublic();
			return $this->getFuncExpVMServiceProvider()->getFuncExpIteratorForRecursiveCall($this->evaluateArg($orderedConstructor),
			array($constructor, $condition, $ordinal+1));
		}
		else return null;
	}

	// Selectors

	/**
	 * Selects some objects and dumps them into a data flow
	 * See method 'sel' in FuncExpBuilder class.
	 * FuncExp signature : <code>sel(dataFlowDumpable, dfasl)</code><br/>
	 * Where arguments are :
	 * - Arg(0) dataFlowDumpable: evaluates to a DataFlowDumpable|InputDataFlow|ObjectList the objects to be selected and pushed into the data flow
	 * - Arg(1) dfasl : evaluates to a DataFlowActivitySelectorList the data flow description
	 * @return Any optionally returns some data if the last stage of the data flows writes some output.
	 */
	public function sel($args) {
		$isOriginPublic = $this->isFxOriginPublic();
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 2 || empty($args[0]) || empty($args[1])) throw new FuncExpEvalException("the sel funcexp takes two arguments : a DataFlowDumpable object and a DataFlowActivitySelectorList", FuncExpEvalException::INVALID_ARGUMENT);
		$dfasl = $this->evaluateArg($args[1]);
		if(isset($dfasl) && $isOriginPublic) $dfasl->setOriginIsPublic();
		return $this->getFuncExpBuilder()->sel($this->getPrincipal(), $this->evaluateArg($args[0]), $dfasl);
	}

	// Aggregators

	/**
	 * FuncExp DataFlow Activity : Aggregates equivalent objects in one data flow.
	 * Two objects are equivalent if they belong to a same class according to a given classifier function.<br/>
	 * FuncExp signature : <code>aggrEquiv(data, dfa, classifier, dataFlowActivitySelectorList)</code><br/>
	 * Where arguments are :
	 * - Arg(0) data: the current data chunk in the running data flow
	 * - Arg(1) dfa: a reference to the underlying FuncExpDFA instance
	 * - Arg(2) classifier: a FuncExp used to determine the class of a chunk of data<br/>
	 * FuncExp signature is : $classifier($a). Returns a Scalar (int or string) identifying the class.
	 * If classifier returns null as a class, then the data chunk will be filtered (removed from any further processing)
	 * - Arg(3) dataFlowActivitySelectorList: evaluates to a DataFlowActivitySelectorList that will be used
	 * to start a DataFlow where equivalent objects will be processed.<br/>
	 * This DataFlow activity does not generate any output in the current running data flow but creates
	 * as many data flows as there are equivalency classes in the set of data beeing processed.
	 */
	public function aggrEquiv($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 4 || empty($args[2]) || !($args[2] instanceof FuncExp) || empty($args[3])) throw new FuncExpEvalException("the aggrEquiv func exp should have a third argument which is a FuncExp taking 2 arguments and a fourth argument which evaluates to a DataFlowActivitySelectorList", FuncExpEvalException::INVALID_ARGUMENT);
		$classifier = $args[2];
		if($dfa->isRunning() || $dfa->isEndStream()) {
			$dfasl = $dfa->getValueInContext('dfasl');
		}
		else {
			if($args[3] instanceof DataFlowActivitySelectorList) $dfasl = $args[3];
			else {
				$dfasl = $this->evaluateArg($args[3]);
				if(isset($dfasl) && !($dfasl instanceof DataFlowActivitySelectorList)) throw new FuncExpEvalException("the aggrEquiv func exp should have a fourth argument which evaluates to a DataFlowActivitySelectorList", FuncExpEvalException::INVALID_ARGUMENT);
			}
			if($dfa->isStartStream()) $dfa->setValueInContext('dfasl', $dfasl);
		}

		$dfs = $this->getDataFlowService();
		$data = $args[0];
		$dfa = $args[1];
		switch($dfa->getState()) {
			case FuncExpDFA::FUNCEXP_DFA_SINGLE_DATA:
				if(isset($dfasl)) $dfs->processWholeData($this->getPrincipal(), $data, $dfasl);
				break;
			case FuncExpDFA::FUNCEXP_DFA_STARTSTREAM:
				$dfa->setValueInContext('streams', array());
				$dfa->setValueInContext('equivalencyClasses', array());
				break;
			case FuncExpDFA::FUNCEXP_DFA_RUNNING:
				$equivClasses = $dfa->getValueInContext('equivalencyClasses');
				$streams = $dfa->getValueInContext('streams');
				$classifier->setArguments(array($data));
				$classId = $this->evaluateArg($classifier);
				if(is_null($classId)) break;
				$equivClass = $equivClasses[$classId];
				$equivClasses[$classId] = $data;
				$dfa->setValueInContext('equivalencyClasses', $equivClasses);
				if(isset($equivClass)) {
					$streams = $dfa->getValueInContext('streams');
					$dfCtx = $streams[$classId];
					if(!isset($dfCtx)) {
						$dfCtx = $dfs->startStream($this->getPrincipal(), $dfasl);
						$streams[$classId] = $dfCtx;
						$dfa->setValueInContext('streams', $streams);
					}
					$dfs->processDataChunk($equivClass, $dfCtx);
				}
				break;
			case FuncExpDFA::FUNCEXP_DFA_ENDSTREAM:
				$streams = $dfa->getValueInContext('streams');
				foreach($dfa->getValueInContext('equivalencyClasses') as $classId=>$obj) {
					$dfCtx = $streams[$classId];
					if(isset($dfCtx)) {
						$dfs->processDataChunk($obj, $dfCtx);
						$dfs->endStream($dfCtx);
					}
					else if(isset($dfasl)) $dfs->processWholeData($this->getPrincipal(), $obj, $dfasl);
				}
				break;
		}
	}

	// Modifiers

	/**
	 * FuncExp DataFlow Activity : Modifies a flow of objects 2 by 2<br/>
	 * FuncExp signature : <code>modifyInPair(data, dfa, modifyInContext)</code><br/>
	 * Where arguments are :
	 * - Arg(0) data: the current data chunk in the running data flow
	 * - Arg(1) dfa: a reference to the underlying FuncExpDFA instance
	 * - Arg(2) modifyInContext: a FuncExp used to modify a chunk of data knowing the coming data.<br/>
	 * FuncExp signature is : $modifyInContext($data, $nextChunk). Modifies a chunk of data knowing the next coming chunk and returns the modified chunk,
	 * or creates a new chunk of data knowing the original data and the next chunk to come and returns the new data.<br/>
	 * The last value of the stream will call $modifyInContext($data, null) where $data is equal to the last value.
	 * @return Any the next stage in the data flow will receive the modified chunks.
	 */
	public function modifyInPair($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 3 || empty($args[2]) || !($args[2] instanceof FuncExp)) throw new FuncExpEvalException("the genDiff func exp must have a third argument which is a FuncExp taking 2 arguments", FuncExpEvalException::INVALID_ARGUMENT);
		$modifyInContext = $args[2];
		$data = $args[0];
		$dfa = $args[1];
		switch($dfa->getState()) {
			case FuncExpDFA::FUNCEXP_DFA_SINGLE_DATA: return $data; break;
			case FuncExpDFA::FUNCEXP_DFA_STARTSTREAM: $dfa->setValueInContext('readFirstValue', false); break;
			case FuncExpDFA::FUNCEXP_DFA_RUNNING:
				if($dfa->getValueInContext('readFirstValue')) {
					$modifyInContext->setArguments(array($dfa->getValueInContext('lastValue'), $data));
					$dfa->setValueInContext('lastValue', $data);
					return $this->evaluateArg($modifyInContext);
				}
				else {
					$dfa->setValueInContext('lastValue', $data);
					$dfa->setValueInContext('readFirstValue', true);
				}
				break;
			case FuncExpDFA::FUNCEXP_DFA_ENDSTREAM:
				if($dfa->getValueInContext('readFirstValue')) {
					$modifyInContext->setArguments(array($dfa->getValueInContext('lastValue'), null));
					return $this->evaluateArg($modifyInContext);
				}
				break;
		}
	}

	/**
	 * FuncExp DataFlow Activity : Links a flow of objects 2 by 2<br/>
	 * FuncExp signature : <code>linkByPair(data, dfa, closePath, groupSize, wigiiGraphNodeConstructor, linkName)</code><br/>
	 * Where arguments are :
	 * - Arg(0) data: the current data chunk in the running data flow
	 * - Arg(1) dfa: a reference to the underlying FuncExpDFA instance
	 * - Arg(2) closePath: if true, then last object is linked to first object. Defaults to false.
	 * - Arg(3) groupSize: optional positive integer, which defines the maximum number of objects which should be linked together.
	 * Then a gap in the links is done and a new group starts, like a modulo. Defaults to 0 which means only one group with all objects in the flow.
	 * - Arg(4) wigiiGraphNodeConstructor: optional FuncExp which should return a new instance of a WigiiGraphNode.
	 * This constructor is called in case the current data chunk is not an instance of a WigiiGraphNode, in order to box it.
	 * - Arg(5) linkName: optional argument which should evaluate to a string which will be used
	 * as the link name between pairs of data. On each node, two links are created 'previousLinkName' and 'nextLinkName'.
	 * @return  WigiiGraphNode the next stage of the data flow will receive an instance of a WigiiGraphNode
	 * linked to its previous node. If the current data chunk is not an instance of a WigiiGraphNode, then
	 * it is boxed into a new instance of a WigiiGraphNode using the given wigiiGraphNodeConstructor FuncExp.
	 */
	public function linkByPair($args) {
		$nArgs = $this->getNumberOfArgs($args);
		$dfa = $args[1];

		$wigiiGraphNodeConstructor = null;
		if($dfa->isRunning() || $dfa->isSingleData()) {
			if($args[0] instanceof WigiiGraphNode) $data = $args[0];
			else {
				if($nArgs > 4) $wigiiGraphNodeConstructor = $args[4];
				else throw new FuncExpEvalException("the wigiiGraphNodeConstructor is missing", FuncExpEvalException::INVALID_ARGUMENT);
				if(!($wigiiGraphNodeConstructor instanceof FuncExp)) throw new FuncExpEvalException("the wigiiGraphNodeConstructor is not an instance of a FunExp", FuncExpEvalException::INVALID_ARGUMENT);

				$data = $this->evaluateArg($wigiiGraphNodeConstructor);
				if(!($data instanceof WigiiGraphNode)) throw new FuncExpEvalException("the given constructor for WigiiGraphNodes did not return a non null WigiiGraphNode instance", FuncExpEvalException::INVALID_RETURN_VALUE);
				$data->setValue($args[0]);
			}
		}
		else $data = null;

		if($dfa->isRunning() || $dfa->isEndStream()) {
			$closePath = $dfa->getValueInContext('closePath');
			if(!isset($closePath)) {
				if($nArgs > 2) $closePath = $this->evaluateArg($args[2]);
				else $closePath = false;
				$this->setValueInContext('closePath', $closePath);
			}
		}
		else $closePath = false;

		if($dfa->isRunning()) {
			$groupSize = $dfa->getValueInContext('groupSize');
			if(!isset($groupSize)) {
				if($nArgs > 3) {
					$groupSize = $this->evaluateArg($args[3]);
				}
				else $groupSize = 0;
				$this->setValueInContext('groupSize', $groupSize);
			}

			$linkName = $dfa->getValueInContext('linkName');
			if(!isset($linkName)) {
				if(isset($wigiiGraphNodeConstructor) && $nArgs > 5) $linkName = $this->evaluateArg($args[5]);
				elseif(!(isset($wigiiGraphNodeConstructor) && $nArgs > 4)) $linkName = $this->evaluateArg($args[4]);
				else $linkName = '';
				$this->setValueInContext('linkName', $linkName);
			}
		}
		else {
			$linkName = '';
			$groupSize = 0;
		}

		switch($dfa->getState()) {
			case FuncExpDFA::FUNCEXP_DFA_SINGLE_DATA: return $data; break;
			case FuncExpDFA::FUNCEXP_DFA_STARTSTREAM:
				$dfa->setValueInContext('readFirstValue', false);
				$dfa->setValueInContext('count', 0);
				break;
			case FuncExpDFA::FUNCEXP_DFA_RUNNING:
				$count = $dfa->getValueInContext('count');
				if($dfa->getValueInContext('readFirstValue')) {
					$returnValue = $dfa->getValueInContext('lastValue');
					if($groupSize <= 0 || $count < $groupSize) {
						$data->setLink('previous'.$linkName, $returnValue);
						$returnValue->setLink('next'.$linkName, $data);
					}
					if($groupSize > 0 && $count >= $groupSize) {
						if($closePath) {
							$firstValue = $dfa->getValueInContext('firstValue');
							$firstValue->setLink('previous'.$linkName, $returnValue);
							$returnValue->setLink('next'.$linkName, $firstValue);
							$dfa->setValueInContext('firstValue', $data);
						}
						$count = 0;
					}
					$dfa->setValueInContext('count', $count+1);
					$dfa->setValueInContext('lastValue', $data);
					return $returnValue;
				}
				else {
					$dfa->setValueInContext('lastValue', $data);
					$dfa->setValueInContext('readFirstValue', true);
					if($closePath) $dfa->setValueInContext('firstValue', $data);
					if($groupSize > 0 && $count >= $groupSize) $count = 0;
					$dfa->setValueInContext('count', $count+1);
				}
				break;
			case FuncExpDFA::FUNCEXP_DFA_ENDSTREAM:
				if($dfa->getValueInContext('readFirstValue')) {
					$returnValue = $dfa->getValueInContext('lastValue');
					if($closePath) {
						$firstValue = $dfa->getValueInContext('firstValue');
						$firstValue->setLink('previous'.$linkName, $returnValue);
						$returnValue->setLink('next'.$linkName, $firstValue);
					}
					return $returnValue;
				}
				break;
		}
	}

	/**
	 * FuncExp DataFlow Activity : Adds an error message to a matrix field if a certain condition on the matrix row is true.
	 * This function should be used in the context of the visitMatrix func exp.<br/>
	 * FuncExp signature: <code>matrixField_addError(row, dfa, col, errorMessageExp)</code><br/>
	 * Where arguments are :
	 * - Arg(0) row: stdClass. A matrix row as given by the visitMatrix func exp.
	 * - Arg(1) dfa: FuncExpDFA. A reference to the underlying DataFlowActivity instance.
	 * - Arg(2) col: String. The name of the column in the matrix (or equivalently the field in the row) on which to add the error
	 * - Arg(3) errorMessageExp: FuncExp|String|FieldSelector. The expression (as a FuncExp, string constant or FieldSelector) which
	 * will be evaluated in the context of a matrix row and that will return an error message (or nothing) to add to the field row.
	 * @example consider a matrix $m having the columns Key_, Fund_ and Percentage_, we'd like to :
	 * 1. attach an error rule on Fund_ field :
	 *	if Key_ is not null then
	 *		if Fund_ is null then return "Fund should be filled"
	 *	end if
	 * 2. attach an error rule on Percentage_ field :
	 * 	if Key_ is not null then
	 *		if Percentage_ is null then return "Percentage should be filled"
	 *		else if !(0 <= Percentage_ <= 100) then return "Percentage should be between 0 and 100"
	 *	end if
	 *
	 * write : visitMatrix($m, dfaslfx(
	 *	 matrixField_addError("FundCode_", ctlIf(isNotNull(Key_), ctlIf(isNull(FundCode_), "Fund should be filled"))),
	 *	 matrixField_addError("Percentage_", ctlIf(isNotNull(Key_), ctlIf(isNull(Percentage_), "Percentage should be filled", ctlIf(logNot(logAnd(smeq("0",Percentage_), smeq(Percentage_, "100"))), "Percentage should be between 0 and 100"))))
	 * ))
	 */
	public function matrixField_addError($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 4) throw new FuncExpEvalException('matrixField_addError DataFlowActivity takes at least two arguments: the col to which to add the error and the errorMessageExp function expression which evaluates the error message', FuncExpEvalException::INVALID_ARGUMENT);
		$dfa = $args[1];
		if(!($dfa instanceof FuncExpDFA)) throw new FuncExpEvalException('matrixField_addError FuncExp can only be evaluated into a DataFlow context. For instance in context of the visitMatrix func exp.', FuncExpEvalException::INVALID_STATE);
		
		// gets error decision tree and error field
		if($dfa->isStartStream() || $dfa->isSingleData()) {			
			$errorMessageExp = $args[3];
			$errorCol = $this->evaluateArg($args[2]);
			if($dfa->isStartStream()) {
				$dfa->setValueInContext('errorMessageExp', $errorMessageExp);
				$dfa->setValueInContext('errorCol', $errorCol);
			}
		}
		elseif($dfa->isRunning()) {
			$errorMessageExp = $dfa->getValueInContext('errorMessageExp');
			$errorCol = $dfa->getValueInContext('errorCol');
		}
		
		if($dfa->isRunning() || $dfa->isSingleData()) {
			// gets matrix and row		
			$matrix = $dfa->getDataFlowContext()->getAttribute('matrix');
			if(!isset($matrix)) throw new FuncExpEvalException('matrix has not been set into the DataFlowContext', FuncExpEvalException::INVALID_STATE);
			$row = $this->evaluateArg($args[0]);		
	
			if(!empty($row)) {
				if(!is_object($row)) throw new FuncExpEvalException('row is not a stdClass instance', FuncExpEvalException::INVALID_ARGUMENT);
				
				// opens new context
				$ctx = $this->getFuncExpVMServiceProvider()->getFuncExpVMContext(true);
				// copies content of row into local variables
				foreach($row as $col=>$val) {
					$ctx->setVariable(fs($col), $val);
				}
				// evaluates error message				
				$errorMessage = $this->evaluateArg($errorMessageExp);
				// adds error message to row
				if(!empty($errorMessage)) {
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
			if(!$dfa->getDataFlowContext()->isCurrentStepTheLastStep()) return $row;
		}
	}
	
	// Object constructors

	/**
	 * Constructs a func exp given its name and some arguments
	 * If args is already a FuncExp then returns it.
	 * FuncExp signature is: <code>fx(name, arg1, arg2, ...)</code><br/>
	 * or <code>fx(funcExp)</code>
	 */
	public function fx($args) {
		$isOriginPublic = $this->isFxOriginPublic();
		$nArgs = $this->getNumberOfArgs($args);
		$returnValue = null;
		// if we have only one funcexp returns it
		if($nArgs == 1) {
			if($args[0] instanceof FuncExp) $returnValue = $args[0];
			else $returnValue = FuncExp::createInstance($this->evaluateArg($args[0]));
		}
		// if we have a name and some arguments, then creates a func exp with them.
		elseif($nArgs > 1) {
			$returnValue = FuncExp::createInstance($this->evaluateArg($args[0]));
			for($i = 1; $i < $nArgs; $i++) {
				$returnValue->addArgument($args[$i]);
			}
		}
		if(isset($returnValue) && $isOriginPublic) $returnValue->setOriginIsPublic();
		return $returnValue;
	}

	/**
	 * Evaluates a FuncExp and returns its result<br/>
	 * FuncExp signature : <code>evalfx(f,modules)</code><br/>
	 * Where arguments are :
	 * - Arg(0) f: a func exp or a field selector. If the FuncExp generates another FuncExp then returns the result of the generated FuncExp.
	 * - Arg(1..n) modules: An array of Module names or a comma separated list of Module names to load into the FuncExpVM before evaluating the FuncExp 
	 * @return Any the result of the FuncExp
	 */
	public function evalfx($args) {
		$nArgs=$this->getNumberOfArgs($args);
		if($nArgs<1) throw new FuncExpEvalException('evalfx takes at least one argument which is the FuncExp to evaluate', FuncExpEvalException::INVALID_ARGUMENT);		
		// loads the modules into the VM if provided
		if($nArgs>1) {
			$sp=$this->getFuncExpVMServiceProvider();
			// extracts the module names
			$modules=$this->evaluateArg($args[1]);
			if(!is_array($modules)) $modules=array($modules);
			for($i=2;$i<$nArgs;$i++) {
				$m=$this->evaluateArg($args[$i]);
				$modules[]=$m;
			}
			// loads a new context in VM
			$sp->getFuncExpVMContext(true);
			// loads the modules
			$sp->useModules($modules);			
		}
		// executes the FuncExp
		$returnValue=$this->evaluateFuncExp($args[0]);
		// if result is a FuncExp then evaluates it again
		if(($returnValue instanceof FuncExp) || ($returnValue instanceof FieldSelector)) $returnValue=$this->evaluateArg($returnValue);
		return $returnValue;
	}
	/**
	 * Replaces the Arguments into a given FuncExp by the ones provided in an array and returns the updated FuncExp. 
	 * No evaluation is done. To also evaluate the FuncExp use the fxRemap FuncExp instead.<br/>
	 * FuncExp signature : <code>fxReplaceArgs(f,args)</code><br/>
	 * Where arguments are :
	 * - Arg(0) f: FuncExp. An instanciated FuncExp. 
	 * - Arg(1) args: Array. An array of arguments which will replace the existing arguments of the FuncExp (in same order). The arguments are not evaluated, just passed to the FuncExp.
	 * @return FuncExp the updated FuncExp
	 */
	public function fxReplaceArgs($args) {
		$nArgs=$this->getNumberOfArgs($args);
		$returnValue = null;
		if($nArgs>0 && ($args[0] instanceof FuncExp)) {
			$returnValue = $args[0];
			$newArgs=null;
			if($nArgs==2) {
				$newArgs = $this->evaluateFuncExp($args[1]);
				if(!(is_array($newArgs))) $newArgs=array($newArgs);
			}
			elseif($args>2) {
				$newArgs=array();
				for($i=1;$i<$nArgs;$i++) {
					$newArgs[] = $args[$i];
				}
			}
			if(!empty($newArgs)) {
				$oldArgs = $returnValue->getArguments();
				if(!empty($oldArgs)) $oldArgs = $newArgs;
				else {
					$i=0;
					foreach($newArgs as $arg)  {
						$oldArgs[$i] = $arg;
						$i++;
					}					
				}
				$returnValue->setArguments($oldArgs);
			}
		}
		return $returnValue;
	}
	/**
	 * Remaps the Arguments into a given FuncExp by the ones provided in an array and evaluates the FuncExp. 
	 * To not evaluate the FuncExp directly, use the fxReplaceArgs FuncExp instead.<br/>
	 * FuncExp signature : <code>fxRemap(f,args)</code><br/>
	 * Where arguments are :
	 * - Arg(0) f: FuncExp. An instanciated FuncExp.
	 * - Arg(1) args: Array. An array of arguments which will replace the existing arguments of the FuncExp (in same order).
	 * @return Any the result of the FuncExp evaluation
	 */
	public function fxRemap($args) {
		return $this->evaluateFuncExp($this->fxReplaceArgs($args));
	}
	/**
	 * Converts a string to a FuncExp
	 * See method 'str2fx' in FuncExpBuilder class.
	 * FuncExp signature : <code>str2fx(str)</code><br/>
	 * @return FuncExp
	 */
	public function str2fx($args) {
		$isOriginPublic = $this->isFxOriginPublic();
		$nArgs = $this->getNumberOfArgs($args);		
		
		if($nArgs < 1) throw new FuncExpEvalException('The str2fx function takes one argument', FuncExpEvalException::INVALID_ARGUMENT);
		$returnValue = $this->getFuncExpBuilder()->str2fx(stripslashes($this->evaluateArg($args[0])));
		// propagates public origin to new parsed function
		if($isOriginPublic && $returnValue instanceof FuncExp) $returnValue->setOriginIsPublic();
		return $returnValue;
	}

	/**
	 * Converts a string to a FuncExp and then to its Base 64 Url representation
	 * Calls fx2base64url(str2fx(str))
	 * FuncExp signature : <code>str2fxBase64url(str)</code><br/>
	 * @return FuncExp
	 */
    public function str2fxBase64url($args) {
        return $this->evaluateFuncExp(fx('fx2base64url',$this->str2fx($args)));
    }
    
	/**
	 * Returns a string representation of the input argument<br/>
	 * FuncExp signature : <code>fx2str(f)</code><br/>
	 * Where arguments are :
	 * - Arg(0) f: a func exp or a field selector.
	 * @return String the string reprensentation of f
	 */
	public function fx2str($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs > 0) {
			if(!empty($args[0])) return $this->getFieldSelectorFuncExpParser()->funcExpToString($args[0]);
			else return '';
		}
		else return '';
	}

	/**
	 * Returns a Base 64 Url representation of given FuncExp<br/>
	 * FuncExp signature : <code>fx2base64url(f)</code><br/>
	 * Where arguments are :
	 * - Arg(0) f: a func exp or a field selector.
	 * @return String the string representation of f encoded in Base 64 Url
	 */
	public function fx2base64url($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs > 0) {
			if(!empty($args[0])) return base64url_encode($this->getFieldSelectorFuncExpParser()->funcExpToString($args[0]));
			else return '';
		}
		else return '';
	}
	/**
	 * Returns a Base 64 Url representation of given Object converted to its FuncExp equivalent<br/>
	 * FuncExp signature : <code>obj2base64url(obj)</code><br/>
	 * Where arguments are :
	 * - Arg(0) obj: Any object
	 * @return String the FuncExp representation of object encoded in Base 64 Url
	 */
	public function obj2base64url($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs > 0) {
			$obj = $this->evaluateArg($args[0]);
			$obj = $this->getFuncExpBuilder()->object2fx($obj);
			if(!empty($obj)) return base64url_encode($this->getFieldSelectorFuncExpParser()->funcExpToString($obj));
			else return '';
		}
		else return '';
	}
	/**
	 * Deserializes an object which has been serialized using obj2base64url<br/>
	 * FuncExp signature : <code>base64url2obj(str)</code><br/>
	 * Where arguments are :
	 * - Arg(0) str: A serialized Object using the obj2base64url function
	 * @return Any the deserialized object
	 */
	public function base64url2obj($args) {
		$isOriginPublic = $this->isFxOriginPublic();
		$nArgs = $this->getNumberOfArgs($args);		
		
		if($nArgs > 0) {
			$obj = $this->evaluateArg($args[0]);
			if(!empty($obj)) {
				$obj = base64url_decode($obj);
				$obj = $this->getFieldSelectorFuncExpParser()->createFuncExpFromString($obj);
				// propagates public origin to new parsed function
				if($isOriginPublic && $obj instanceof FuncExp) $obj->setOriginIsPublic();
				// evaluates Func Exp
				return $this->evaluateFuncExp($obj);
			}			
			else return null;
		}
		else return null;
	}
	
	/**
	 * Constructs a DataFlowActivitySelector instance that wraps a FuncExp.<br/>
	 * FuncExp signature is: <code>dfasfx(funcExp)</code><br/>
	 * Where arguments are :
	 * - Arg(0) funcExp: an instance of a FuncExp that will be executed by the data flow
	 * The funcExp signature should be $funcName(data, dfa, args...)
	 * where arguments are :
	 * - Arg(0) data : a chunk of data passed by the data flow, or null when startStream or endStream state
	 * - Arg(1) dfa : a reference to the FuncExpDataFlowActivity instance wrapping the executing FuncExp.
	 * This object can be used to get the state, save some information in the context (stateful activity),
	 * and send some output in the DataFlow for further processing.
	 * - Arg(2..n) args : the other func exp arguments as defined by the programmer.
	 * @return DataFlowActivitySelector a DataFlowActivitySelector instance
	 */
	public function dfasfx($args) {
		$isOriginPublic = $this->isFxOriginPublic();
		if($this->getNumberOfArgs($args) < 1 ||
			empty($args[0]) ||
			!($args[0] instanceof FuncExp)) throw new FuncExpEvalException("funcExp dfasfx takes at least one argument which should be a non null instance of a FuncExp", FuncExpEvalException::INVALID_ARGUMENT);
		$returnValue = DataFlowActivitySelector::createInstance('FuncExpDFA');
		$returnValue->setDataFlowActivityParameter('setFuncExp', $args[0]);
		// forks the current vm (except parent evaluator)
		// and sets it as the func exp evaluator to be used by the FuncExpDFA
		$returnValue->setDataFlowActivityParameter('setFuncExpEvaluator', ServiceProvider::getFuncExpVM($this->getPrincipal(), null, get_class($this->getFuncExpVM())));
		if($isOriginPublic) $returnValue->setOriginIsPublic();
		return $returnValue;
	}
	/**
	 * Constructs a DataFlowActivitySelectorList instance that wraps FuncExps.<br/>
	 * FuncExp signature is: <code>dfaslfx(funcExp1, funcExp2, ...)</code><br/>
	 * Where arguments are :
	 * - Arg(0..n) funcExpI: instances of FuncExps that define the different activities in the data flow
	 * The $funcExpI signature should be $funcName(data, dfa, args...)
	 * where arguments are :
	 * - Arg(0) data : a chunk of data passed by the data flow, or null when startStream or endStream state
	 * - Arg(1) dfa : a reference to the FuncExpDataFlowActivity instance wrapping the executing FuncExp.
	 * This object can be used to get the state, save some information in the context (stateful activity),
	 * and send some output in the DataFlow for further processing.
	 * - Arg(2..n) args : the other func exp arguments as defined by the programmer.
	 * @return DataFlowActivitySelectorList a DataFlowActivitySelectorList instance
	 */
	public function dfaslfx($args) {
		$isOriginPublic = $this->isFxOriginPublic();
		$nArgs = $this->getNumberOfArgs($args);
		$returnValue = null;
		if($nArgs > 0) {
			$vmClass = get_class($this->getFuncExpVM());
			$p = $this->getPrincipal();
			for($i = 0; $i < $nArgs; $i++) {
				if(!empty($args[$i])) {
					if(!($args[$i] instanceof FuncExp)) throw new FuncExpEvalException("funcExp dfaslfx takes at least one argument which should be a non null instance of a FuncExp", FuncExpEvalException::INVALID_ARGUMENT);
					if(!isset($returnValue)) $returnValue = DataFlowActivitySelectorListArrayImpl::createInstance();
					$dfas = DataFlowActivitySelector::createInstance('FuncExpDFA');
					$dfas->setDataFlowActivityParameter('setFuncExp', $args[$i]);
					// forks the current vm (except parent evaluator)
					// and sets it as the func exp evaluator to be used by the FuncExpDFA
					$dfas->setDataFlowActivityParameter('setFuncExpEvaluator', ServiceProvider::getFuncExpVM($p, null, $vmClass));
					$returnValue->addDataFlowActivitySelectorInstance($dfas);
				}
			}
		}		
		if(isset($returnValue) && $isOriginPublic) $returnValue->setOriginIsPublic();
		return $returnValue;
	}
	/**
	 * Constructs a DataFlowActivitySelector instance.<br/>
	 * FuncExp signature is: <code>dfas(className, paramName1, paramValue1, paramName2, paramValue2, ...)</code><br/>
	 * Where arguments are :
	 * - Arg(0) className: a FieldSelector instance or evaluates to a String which defines the class name of the DataFlowActivity that should be executed.
	 * - Arg(1,3,...) paramNameI: a FieldSelector instance or evaluates to a String which defines a configuration parameter name for the DataFlowActivity that will be executed.
	 * - Arg(2,4,...) paramValueI: evaluates to any object which represents the value of the configuration parameter.
	 * @return DataFlowActivitySelector a DataFlowActivitySelector instance
	 */
	public function dfas($args) {
		$isOriginPublic = $this->isFxOriginPublic();
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1 || empty($args[0])) throw new FuncExpEvalException("func exp should have at least one non null argument which is the class name", FuncExpEvalException::INVALID_ARGUMENT);
		// gets class name
		if($args[0] instanceof FieldSelector) $className = $args[0]->getFieldName();
		else {
			$className = $this->evaluateArg($args[0]);
			if(empty($className) || !is_string($className)) throw new FuncExpEvalException("func exp should have at least one non null argument which is the class name", FuncExpEvalException::INVALID_ARGUMENT);
		}
		$returnValue = DataFlowActivitySelector::createInstance($className);
		// gets configuration parameters
		$i = 1;
		while($i < $nArgs) {
			// gets parameter name
			if($args[$i] instanceof FieldSelector) $paramName = $args[$i]->getFieldName();
			else {
				$paramName = $this->evaluateArg($args[$i]);
				if(empty($paramName) || !is_string($paramName)) throw new FuncExpEvalException("DataFlowActivity selector configuration parameter name must be a non empty string", FuncExpEvalException::INVALID_ARGUMENT);
			}
			$i++;
			if($i < $nArgs) $paramValue = $this->evaluateArg($args[$i]);
			else $paramValue = null;
			$returnValue->setDataFlowActivityParameter($paramName, $paramValue);
			$i++;
		}
		if($isOriginPublic) $returnValue->setOriginIsPublic();
		return $returnValue;
	}
	/**
	 * Constructs a DataFlowActivitySelectorList instance.<br/>
	 * FuncExp signature is: <code>dfasl(dfas1, dfas2, ...)</code><br/>
	 * Where arguments are :
	 * - Arg(0..n) dfasI: evaluates to DataFlowActivitySelector instances
	 * @return DataFlowActivitySelectorList a DataFlowActivitySelectorList instance
	 */
	public function dfasl($args) {
		$isOriginPublic = $this->isFxOriginPublic();
		$nArgs = $this->getNumberOfArgs($args);
		$returnValue = null;
		if($nArgs > 0) {
			for($i = 0; $i < $nArgs; $i++) {
				$dfas = $this->evaluateArg($args[$i]);
				if(isset($dfas)) {
					if(!($dfas instanceof DataFlowActivitySelector)) throw new FuncExpEvalException("each func exp argument should evaluate to a DataFlowActivitySelector instance", FuncExpEvalException::INVALID_ARGUMENT);
					if(!isset($returnValue)) $returnValue = DataFlowActivitySelectorListArrayImpl::createInstance();
					$returnValue->addDataFlowActivitySelectorInstance($dfas);
					//$this->debugLogger()->write("adds dfas '".$dfas->getClassName()."' to dfasl");
				}
			}
		}
		if(isset($returnValue) && $isOriginPublic) $returnValue->setOriginIsPublic();
		return $returnValue;
	}

	/**
	 * Appends dfasl2, ..., dfasln to dfasl1 and returns modifed dfasl1.
	 * FuncExp signature is: <code>appendDfasl(dfasl1, dfasl2, ...)</code><br/>
	 * Where arguments are :
	 * - Arg(0) dfasl1: evaluates to an object of type DataFlowActivitySelectorList
	 * - Arg(1..n) dfaslI: evaluates to an object of type DataFlowActivitySelectorList
	 * @return DataFlowActivitySelectorList returns dfasl1 instance with the content of dfasli appended.
	 */
	public function appendDfasl($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException("appendDfasl takes at least one argument which is a DataFlowSelectorList", FuncExpEvalException::INVALID_ARGUMENT);
		$returnValue = $this->evaluateArg($args[0]);
		if(!($returnValue instanceof DataFlowActivitySelectorList)) throw new FuncExpEvalException("dfasl1 should evaluate to a DataFlowSelectorList", FuncExpEvalException::INVALID_ARGUMENT);
		$i = 1;
		while($i < $nArgs) {
			$dfasl = $this->evaluateArg($args[$i]);
			if(!($dfasl instanceof DataFlowActivitySelectorList)) throw new FuncExpEvalException("dfaslI should evaluate to a DataFlowSelectorList", FuncExpEvalException::INVALID_ARGUMENT);
			foreach($dfasl->getListIterator() as $dfas) {
				$returnValue->addDataFlowActivitySelectorInstance($dfas);
			}
			$i++;
		}
		return $returnValue;
	}

	/**
	 * Constructs a DataFlowSelector instance.<br/>
	 * FuncExp signature is: <code>dfs(src, dfasl)</code><br/>
	 * Where arguments are :
	 * - Arg(0) src: evaluates to an object of type DataFlowDumpable|InputDataFlow|ObjectList
	 * - Arg(1) dfasl: evaluates to an object of type DataFlowActivitySelectorList
	 * @return DataFlowSelector a DataFlowSelector instance
	 */
	public function dfs($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 2 || empty($args[0]) || empty($args[1])) throw new FuncExpEvalException("func exp should have at two non null arguments which are the source and the dfasl", FuncExpEvalException::INVALID_ARGUMENT);
		return DataFlowSelector::createInstance($this->evaluateArg($args[0]), $this->evaluateArg($args[1]));
	}

	/**
	 * Constructs a DataFlowSelectorList instance.<br/>
	 * FuncExp signature is: <code>dfsList(dfs1, dfs2, ...)</code><br/>
	 * Where arguments are :
	 * - Arg(0..n) dfsI: evaluates to DataFlowSelector instances
	 * @return DataFlowSelectorList a DataFlowSelectorList instance
	 */
	public function dfsList($args) {
		$nArgs = $this->getNumberOfArgs($args);
		$returnValue = null;
		if($nArgs > 0) {
			for($i = 0; $i < $nArgs; $i++) {
				$dfs = $this->evaluateArg($args[$i]);
				if(isset($dfs)) {
					if(!($dfs instanceof DataFlowSelector)) throw new FuncExpEvalException("each func exp argument should evaluate to a DataFlowSelector instance", FuncExpEvalException::INVALID_ARGUMENT);
					if(!isset($returnValue)) $returnValue = DataFlowSelectorListArrayImpl::createInstance();
					$returnValue->addDataFlowSelector($dfs);
				}
			}
		}
		return $returnValue;
	}

	/**
	 * Constructs a DataFlowSelectorList instance based on key indexed DataFlowSelectors<br/>
	 * FuncExp signature is: <code>dfsMap(key1, dfs1, key2, dfs2, ...)</code><br/>
	 * Where arguments are :
	 * - Arg(0, 2, 4,..2*n) keyI: evaluates to a Scalar which is used as a key
	 * - Arg(1, 3, 5,..2*n+1) dfsI: evaluates to DataFlowSelector instances
	 * @return DataFlowSelectorList a DataFlowSelectorList instance indexed with keys.
	 */
	public function dfsMap($args) {
		$nArgs = $this->getNumberOfArgs($args);
		$returnValue = null;
		if($nArgs > 0) {
			$i = 0;
			while($i < $nArgs) {
				$key = $this->evaluateArg($args[$i]);
				if(!is_scalar($key)) throw new FuncExpEvalException("the key did not evaluate to a non null scalar", FuncExpEvalException::INVALID_ARGUMENT);
				$i++;
				if($i < $nArgs) {
					$dfs = $this->evaluateArg($args[$i]);
					if(isset($dfs)) {
						if(!($dfs instanceof DataFlowSelector)) throw new FuncExpEvalException("each func exp argument should evaluate to a DataFlowSelector instance", FuncExpEvalException::INVALID_ARGUMENT);
						if(!isset($returnValue)) $returnValue = DataFlowSelectorListArrayImpl::createInstance();
						$returnValue->addDataFlowSelector($dfs, $key);
					}
				}
				$i++;
			}
		}
		return $returnValue;
	}

	/**
	 * Connects an array as a dataflow source.
	 * FuncExp signature is: <code>array2df(arr)</code><br/>
	 * Where arguments are :
	 * - Arg(0) arr: evaluates to the array to use as the dataflow source.
	 * This function also supports variable number of arguments, that means that instead of passing one array,
	 * you can pass several arguments to the function, in a comma separated list.
	 * example: array2df(val1, val2, val3, ..., valn) is equivalent to array2df(array(val1, val2, val3, ..., valn))
	 * @return ArrayDataFlowConnector returns an ArrayDataFlowConnector instance that can be used as a DataFlow source.
	 */
	public function array2df($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException('array2df takes at least one argument which should be the array to be used as a dataflow source');
		elseif($nArgs > 1) {
			$arr = array();
			foreach($args as $arg) {
				$arr[] = $this->evaluateArg($arg);
			}
		}
		else $arr = $this->evaluateArg($args[0]);
		return $this->getFuncExpBuilder()->array2df($arr);
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
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 2) throw new FuncExpEvalException("To create a map, there should be 2 arguments: the array of keys and the array of values", FuncExpEvalException::INVALID_ARGUMENT);
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
	 * The first argument should be an array or an instance of StdClass.
	 * If no arg then returns empty string.
	 */
	public function newJsonString($args) {
		if($this->getNumberOfArgs($args) < 1) return '';
		$v = $this->evaluateArg($args[0]);
		if(!(is_array($v) || is_a($v, 'stdClass'))) throw new FuncExpEvalException("first argument should evaluate to an array or PHP stdClass", FuncExpEvalException::INVALID_ARGUMENT);
		$v = json_encode($v);
		if(!$v) throw new FuncExpEvalException("could not create a JSON string based on the input", FuncExpEvalException::INVALID_ARGUMENT);
		return $v;
	}
	
	/**
	 * Creates a new StdClass instance based on a list of pairs (key,value) or a given array.
	 * @return StdClass the instanciated object or an empty object if no pairs (key,value) are given.
	 */
	public function newObject($args) {
		$nArgs = $this->getNumberOfArgs($args);
		$returnValue=array();
		if($nArgs==1) {
			$v=$this->evaluateArg($args[0]);
			if(!empty($v)) {
				if(is_array($v)) $returnValue=$v;
				else throw new FuncExpEvalException('newObject function takes a list of pairs (key,value) as arguments or an array (map)', FuncExpEvalException::INVALID_ARGUMENT);
			}			
		}
		elseif($nArgs>1) {
			$i=0;
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
		}
		return (object)$returnValue;
	}
	
	// Accessors

	/**
	 * Gets the value of an attribute in an array or on an object.<br/>
	 * FuncExp signature is: <code>getAttr(obj, key1, key2, ...)</code><br/>
	 * Where arguments are :
	 * - Arg(0) obj: evaluates to an instance of a PHP StdClass or an array or any Object.
	 * - Arg(1, 2, ...) keys: evaluates to a scalar (int or string) which is either an array key or an object attribute name or an object method.
	 * If obj is a StdClass, then getAttr(obj, key1, key2, key3, ...) is equivalent to calling obj->key1->key2->key3->...
	 * If obj is an Array, then getAttr(obj, key1, key2, key3, ...) is equivalent to calling obj[key1][key2][key3][...]
	 * If obj is any object, then getAttr(obj, key1, key2, key3, ...) is equivalent to calling obj->key1()->key2()->key3()->...()
	 * @return Any returns the value accessed by this chain of accessors.
	 */
	public function getAttr($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException("getAttr function takes at least one argument which evaluates to an object or an array.", FuncExpEvalException::INVALID_ARGUMENT);
		else {
			$o = $this->evaluateArg($args[0]);
			//$this->debugLogger()->write(json_encode($o));
			if(is_null($o)) return null;
			$path = 'arg[0]';
			for($i = 1; $i < $nArgs; $i++) {
				$key = $this->evaluateArg($args[$i]);
				if(empty($key) || !is_scalar($key)) throw new FuncExpEvalException("keys should evaluate to non null scalars.", FuncExpEvalException::INVALID_ARGUMENT);
				if($o instanceof StdClass) {
					$o = $o->{$key};
				}
				elseif(is_array($o)) {
					$o = $o[$key];
				}
				elseif(is_object($o)) {
					if(method_exists($o, $key)) {
						$o = $o->{$key}();
					}
					else throw new FuncExpEvalException("method '$key' does not exist on object returned by $path", FuncExpEvalException::SYMBOL_NOT_FOUND);
				}
				else throw new FuncExpEvalException("tries to apply method or key '$key' on a scalar instead of an array or an object", FuncExpEvalException::INVALID_ARGUMENT);
				$path .= "->".$key;
			}
			return $o;
		}
	}

	/**
	 * Returns the value of an attribute of an object.
	 * If attribute is not given, returns the value of the 'value' attribute.
	 * If object is an array, returns the array value stored under the given attribute taken as a key.
	 * Else returns the object as is.<br/>
	 * FuncExp signature is: <code>oVal(obj, attrName=null)</code><br/>
	 * Where arguments are :
	 * - Arg(0) obj: stdClass|Array. Evaluates to the object from which to extract an attribute
	 * - Arg(1) attrName: string. Object attribute name or 'value' if not defined
	 *  @return Any or null if attribute is not defined.
	 */
	public function oVal($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException("oVal function takes at least one argument which evaluates to an object or an array.", FuncExpEvalException::INVALID_ARGUMENT);
		return TechnicalServiceProvider::getFuncExpBuilder()->oVal($this->evaluateArg($args[0]),($nArgs>1?$this->evaluateArg($args[1]):null));
	}
	
	/**
	 * Calls a method on an object.<br/>
	 * FuncExp signature is: <code>oCall(obj, methodName, arg1, arg2, ...)</code><br/>
	 * Where arguments are :
	 * - Arg(0) obj: Object. Evaluates to the object on which to call the method
	 * - Arg(1) methodName: String. The name of the method to call on the Object
	 * - Arg(2..n) argI: Any. Method arguments
	 * 
	 * This function cannot be called from public space (i.e. caller is located outside of the Wigii instance)
	 * @return Any the method return value
	 */
	public function oCall($args) {
		$this->assertFxOriginIsNotPublic();
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs<2) throw new FuncExpEvalException('oCall takes at least two arguments which are the object and the method to be called on the object', FuncExpEvalException::INVALID_ARGUMENT);
		$obj = $this->evaluateArg($args[0]);
		if(!is_object($obj)) throw new FuncExpEvalException('oCall can only call methods on objects', FuncExpEvalException::INVALID_ARGUMENT);
		$methodName = $this->evaluateArg($args[1]);
		if(empty($methodName)) throw new FuncExpEvalException('method name canno be null', FuncExpEvalException::INVALID_ARGUMENT);
		if(!method_exists($obj, $methodName)) throw new FuncExpEvalException("method '$methodName' does not exist on object of class ".get_class($obj), FuncExpEvalException::UNSUPPORTED_OPERATION);
		$params = array();
		if($nArgs>2) {
			for($i=2;$i<$nArgs;$i++) {
				$params[$i] = $this->evaluateArg($args[$i]);
			}			
		}
		return call_user_func_array(array($obj,$methodName), $params);
	}
	
	// Logic

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
		if($this->getNumberOfArgs($args) < 2) throw new FuncExpEvalException("For equality, the number of arguments should be at least 2", FuncExpEvalException::INVALID_ARGUMENT);
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
	 * Counts the number of equivalent classes. Ignores null values.<br/>
	 * FuncExp signature is: <code>ctlCountEqClasses(val1, val2, ...)</code><br/>
	 * Where arguments are :
	 * - Arg(1..n) valI: Scalar. The values to classify in equivalent classes
	 * @example 
	 * Checks that all filled values are different from one another (ignores non filled values).
	 * eq(ctlCountEqClasses(v1,v2,v3)), count(v1,v2,v3))
	 * 
	 * If all filled, then at least two should be different
	 * logOr(sm(count(v1,v2,v3),"3"), greq(ctlCountEqClasses(v1,v2,v3),"2"))
	 * 
	 * Checks that at any time, at least two filled values are different (ignores non filled values)
	 * greq(ctlCountEqClasses(v1,v2,v3), min(count(v1,v2,v3),"2"))
	 * 
	 * @return int the number of equivalent classes in the population
	 */
	public function ctlCountEqClasses($args) {
	    $nArgs=$this->getNumberOfArgs($args);
	    $returnValue = 0;
	    if($nArgs>0) {
	        $returnValue = array();
	        for($i=0;$i<$nArgs;$i++) {
	            $val = $this->evaluateArg($args[$i]);
	            if(is_array($val)) {
	                foreach($val as $subVal) {
	                    if(is_array($subVal)) throw new FuncExpEvalException('ctlCountEqClasses does not support array of arrays.', FuncExpEvalException::INVALID_ARGUMENT);
	                    if($subVal===0 || $subVal!=null) $returnValue[$subVal] += 1;
	                }
	            }
	            elseif($val===0 || $val!=null) $returnValue[$val] += 1;
	        }
	        $returnValue = count($returnValue);
	    }
	    return $returnValue;
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
		if($this->getNumberOfArgs($args) < 2) throw new FuncExpEvalException("For comparison, the number of arguments should be at least 2", FuncExpEvalException::INVALID_ARGUMENT);
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
		if($this->getNumberOfArgs($args) < 2) throw new FuncExpEvalException("For comparison, the number of arguments should be at least 2", FuncExpEvalException::INVALID_ARGUMENT);
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
		if($this->getNumberOfArgs($args) < 2) throw new FuncExpEvalException("For comparison, the number of arguments should be at least 2", FuncExpEvalException::INVALID_ARGUMENT);
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
		if($this->getNumberOfArgs($args) < 2) throw new FuncExpEvalException("For comparison, the number of arguments should be at least 2", FuncExpEvalException::INVALID_ARGUMENT);
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
		if($this->getNumberOfArgs($args) < 2) throw new FuncExpEvalException("For logical AND, the number of arguments should be at least 2", FuncExpEvalException::INVALID_ARGUMENT);
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
		if($this->getNumberOfArgs($args) < 2) throw new FuncExpEvalException("For logical OR, the number of arguments should be at least 2", FuncExpEvalException::INVALID_ARGUMENT);
		foreach($args as $arg) {
			if($this->evaluateArg($arg)) return true;
		}
		return false;
	}

	/**
	 * Executes a logical NOT on first argument
	 */
	public function logNot($args) {
		if($this->getNumberOfArgs($args) < 1) throw new FuncExpEvalException("For logical NOT, the number of argument should be 1", FuncExpEvalException::INVALID_ARGUMENT);
		return !$this->evaluateArg($args[0]);
	}	
	
	/**
	 * Returns true if first arguments has a value contained in the following list of arguments
	 * FuncExp signature: <code>logIn(exp, val1, val2, val3, ...)</code><br/>
	 * Where arguments are :
	 * - Arg(0) exp: FuncExp | FieldSelector. The expression which needs to be evaluated.
	 * - Arg(1..n) valI: The values composing the list. Can be constants or expressions.
	 * @return Boolean Returns true if exp evaluates to a value contained in the list val1,val2,val3,... else returns false.
	 */
	public function logIn($args) {
		$nArgs=$this->getNumberOfArgs($args);
		if($nArgs<2) throw new FuncExpEvalException('logIn function takes at least two arguments, the expression to be evaluated and a value');
		$val = $this->evaluateArg($args[0]);
		$i=1;
		while($i<$nArgs) {
			if($this->evaluateArg($args[$i]) == $val) return true;
			$i++;
		}
		return false;
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
	
	// Control structures

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
	 * Evaluates a sequence of actions only if a condition is true.
	 * This func exp is a synonym to the<code>ctlSeqIf</code> function.
	 * FuncExp signature: <code>ctlCondSeq(condition, action1, action2, action3, ...)</code><br/>
	 * Where arguments are :
	 * - Arg(0) condidition: FuncExp | FieldSelector | LogExp. The condition to test if evaluates to true. Can be a FuncExp or a FieldSelector or a LogExp on data stored into the underlying Record.
	 * - Arg(1..n) actionI: If condition is true, then evaluates in sequence each action. Returns the result of the last action.
	 * @return Any the result of the last action if condition is true, else null.
	 */
	public function ctlCondSeq($args) {
		$nArgs=$this->getNumberOfArgs($args);
		$returnValue=null;
		if($nArgs>0) {
			// gets condition
			$cond=$this->evaluateArg($args[0]);
			// if a LogExp then evaluates against underlying Record
			if($cond instanceof LogExp) $cond=$this->evaluateFuncExp(fx('evallx',$cond));
			// if condition is true, then evaluates each action in the sequence and returns last result
			if($cond) {
				for($i=1;$i<$nArgs;$i++) {
					$returnValue=$this->evaluateArg($args[$i]);
				}
			}			
		}
		return $returnValue;
	}
	/**
	 * Evaluates a sequence of actions only if a condition is true.
	 * This func exp is a synonym to the<code>ctlCondSeq</code> function.
	 * FuncExp signature: <code>ctlSeqIf(condition, action1, action2, action3, ...)</code><br/>
	 * Where arguments are :
	 * - Arg(0) condidition: FuncExp | FieldSelector | LogExp. The condition to test if evaluates to true. Can be a FuncExp or a FieldSelector or a LogExp on data stored into the underlying Record.
	 * - Arg(1..n) actionI: If condition is true, then evaluates in sequence each action. Returns the result of the last action.
	 * @return Any the result of the last action if condition is true, else null.
	 */
	public function ctlSeqIf($args) {
		return $this->ctlCondSeq($args);
	}
	
	/**
	 * Evaluates first argument, if true, then evaluates second argument and returns the result,
	 * else evaluates the third argument and returns the result.
	 * If third argument is not defined, then returns null.
	 */
	public function ctlIf($args) {
		$n = $this->getNumberOfArgs($args);
		if($n < 2) throw new FuncExpEvalException("For condition, the number of arguments should be at least 2", FuncExpEvalException::INVALID_ARGUMENT);
		if($this->evaluateArg($args[0])) return $this->evaluateArg($args[1]);
		elseif($n >= 3) return $this->evaluateArg($args[2]);
		else return null;
	}
	
	/**
	 * Switches some input data against rules and returns the selected result.
	 * This func exp is a synonym to the<code>ctlMatchOne</code> function.
	 * The rules are evaluated in order. Once a rule matches, then the corresponding result is returned and evaluation stops. 
	 * FuncExp signature: <code>ctlSwitch(data, [dfa], rule1, result1, rule2, result2, ...)</code><br/>
	 * Where arguments are :
	 * - Arg(0) data: The data on which to evaluate the switch.
	 * - Arg(1) dfa: If the func exp is evaluated into a data flow context, then this argument is a reference to the underlying FuncExpDFA instance.
	 * for i=1..nArgs or i=2..nArgs:
	 * - Arg(i) ruleI: a FuncExp or FieldSelector or constant used to match against incoming data.
	 * If ruleI is a FuncExp, then data is evaluated and result is put into variable 'value'. Then ruleI is evaluated as a boolean.
	 * Else data and ruleI are evaluated and compared using ==.
	 * - Arg(i+1) resultI: Evaluates to the result that the function should return if ruleI fires.
	 * @return Any the resultI of the ruleI which has fired or no return value if no rule has fired.
	 */
	public function ctlSwitch($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 3) throw new FuncExpEvalException('func exp takes at least 3 arguments which are data, rule1 and result1', FuncExpEvalException::INVALID_ARGUMENT);;
		// sets args index on which to find first rule.
		if($args[1] instanceof FuncExpDFA) {
			// nothing todo on start or end stream
			if($args[1]->isStartStream() || $args[1]->isEndStream()) return;
			$ruleIndex = 2;
		}
		else $ruleIndex = 1;

		// opens new context
		$ctx = $this->getFuncExpVMServiceProvider()->getFuncExpVMContext(true);
		// evaluates input data
		$data = $this->evaluateArg($args[0]);
		// stores result into variable 'value'
		$ctx->setVariable(fs('value'), $data);
		// evaluates each rules
		while($ruleIndex < $nArgs) {
			$rule = $args[$ruleIndex];
			if($rule instanceof FuncExp) $ruleEvaluation = ($this->evaluateArg($rule) == true);
			else $ruleEvaluation = ($this->evaluateArg($rule) == $data);
			
			$ruleIndex++;
			// if rule fires, then return result
			if($ruleEvaluation) {
				if($ruleIndex < $nArgs) return $this->evaluateArg($args[$ruleIndex]); 
				else return;
			}
			$ruleIndex++;
		}
	}
	
	/**
	 * Matches some input data against rules and returns the selected result.
	 * This func exp is a synonym to the<code>ctlSwitch</code> function.
	 * The rules are evaluated in order. Once a rule matches, then the corresponding result is returned and evaluation stops.
	 * FuncExp signature: <code>ctlMatchOne(data, [dfa], rule1, result1, rule2, result2, ...)</code><br/>
	 * Where arguments are :
	 * - Arg(0) data: The data on which to evaluate the switch.
	 * - Arg(1) dfa: If the func exp is evaluated into a data flow context, then this argument is a reference to the underlying FuncExpDFA instance.
	 * for i=1..nArgs or i=2..nArgs:
	 * - Arg(i) ruleI: a FuncExp or FieldSelector or constant used to match against incoming data.
	 * If ruleI is a FuncExp, then data is evaluated and result is put into variable 'value'. Then ruleI is evaluated as a boolean.
	 * Else data and ruleI are evaluated and compared using ==.
	 * - Arg(i+1) resultI: Evaluates to the result that the function should return if ruleI fires.
	 * @return Any the resultI of the ruleI which has fired or no return value if no rule has fired.
	 */
	public function ctlMatchOne($args) {
		return $this->ctlSwitch($args);
	}
	
	/**
	 * FuncExp DataFlowActivity which matches input data to rules and returns all matching results. The rules are evaluated in order.
	 * FuncExp signature: <code>ctlMatchAll(data, dfa, rule1, result1, rule2, result2, ...)</code><br/>
	 * Where arguments are :
	 * - Arg(0) data: The data on which to evaluate the switch.
	 * - Arg(1) dfa: A reference to the underlying FuncExpDFA instance.
	 * for i=2..nArgs:
	 * - Arg(i) ruleI: a FuncExp or FieldSelector or constant used to match against incoming data.
	 * If ruleI is a FuncExp, then data is evaluated and result is put into variable 'value'. Then ruleI is evaluated as a boolean.
	 * Else data and ruleI are evaluated and compared using ==.
	 * - Arg(i+1) resultI: Evaluates to the result that the function should return if ruleI fires.
	 * @return Any the resultI of all ruleI which have fired or no return value if no rule have fired.
	 */
	public function ctlMatchAll($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 3) throw new FuncExpEvalException('func exp takes at least 3 arguments which are data, dfa and rule1', FuncExpEvalException::INVALID_ARGUMENT);;
		// gets dfa
		$dfa = $args[1];
		if(!($dfa instanceof FuncExpDFA)) throw new FuncExpEvalException('ctlMatchAll only works into a DataFlow context', FuncExpEvalException::INVALID_STATE);
		// nothing todo on start or end stream
		if($dfa->isStartStream() || $dfa->isEndStream()) return;
		$ruleIndex = 2;

		// opens new context
		$ctx = $this->getFuncExpVMServiceProvider()->getFuncExpVMContext(true);
		// evaluates input data
		$data = $this->evaluateArg($args[0]);
		// stores result into variable 'value'
		$ctx->setVariable(fs('value'), $data);
		// evaluates each rules
		while($ruleIndex < $nArgs) {
			$rule = $args[$ruleIndex];
			if($rule instanceof FuncExp) $ruleEvaluation = ($this->evaluateArg($rule) == true);
			else $ruleEvaluation = ($this->evaluateArg($rule) == $data);
			
			$ruleIndex++;
			// if rule fires, then outputs result
			if($ruleEvaluation) {
				if($ruleIndex < $nArgs) {
					$dfa->writeResultToOutput($this->evaluateArg($args[$ruleIndex]));
				}
			}
			$ruleIndex++;
		}
	}
	
	/**
	 * Patterns matches an input func exp with some rules and returns the selected result.
	 * The rules are evaluated in order. Once a rule matches, then the corresponding result is returned and evaluation stops.
	 * FuncExp signature: <code>ctlMatchFx(data, [dfa], rule1, result1, rule2, result2, ...)</code><br/>
	 * Where arguments are :
	 * - Arg(0) data: The input func exp on which to pattern match.
	 * - Arg(1) dfa: If the func exp is evaluated into a data flow context, then this argument is a reference to the underlying FuncExpDFA instance.
	 * for i=1..nArgs or i=2..nArgs:
	 * - Arg(i) ruleI: a FuncExp or FieldSelector or constant used to match against incoming data.
	 * If data and ruleI are FuncExp then data is pattern matched against ruleI. Any variables present in the rule are instanciated.
	 * Elseif data is not a FuncExp and ruleI is a FuncExp, then data is put into variable 'value' and ruleI is evaluated as a boolean.
	 * Else data and ruleI are evaluated and compared using ==.
	 * - Arg(i+1) resultI: Evaluates to the result that the function should return if ruleI fires.
	 * @return Any the resultI of the ruleI which has fired or no return value if no rule has fired.
	 */
	public function ctlMatchFx($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 3) throw new FuncExpEvalException('func exp takes at least 3 arguments which are data, rule1 and result1', FuncExpEvalException::INVALID_ARGUMENT);;
		// sets args index on which to find first rule.
		if($args[1] instanceof FuncExpDFA) {
			// nothing todo on start or end stream
			if($args[1]->isStartStream() || $args[1]->isEndStream()) return;
			$ruleIndex = 2;
		}
		else $ruleIndex = 1;

		// opens new context
		$ctx = $this->getFuncExpVMServiceProvider()->getFuncExpVMContext(true);
		
		$dataIsFx = ($args[0] instanceof FuncExp);
		$dataIsEvaluated = false; $data = null;
		// evaluates each rules
		while($ruleIndex < $nArgs) {
			$rule = $args[$ruleIndex];
			
			// if rule and data are FuncExp then pattern match
			if($dataIsFx && $rule instanceof FuncExp) {
				$ruleEvaluation = $this->matchFuncExp($rule, $args[0], $ctx);
			}
			// else evaluates rule and data and compare values
			else {
				// evaluates input data
				if(!$dataIsEvaluated) {
					$data = $this->evaluateArg($args[0]);
					$dataIsEvaluated = true;
				}
				
				// stores result into variable 'value'
				$ctx->setVariable(fs('value'), $data);
				
				if($rule instanceof FuncExp) $ruleEvaluation = ($this->evaluateArg($rule) == true);
				else $ruleEvaluation = ($this->evaluateArg($rule) == $data);
			}
			
			$ruleIndex++;
			// if rule fires, then return result
			if($ruleEvaluation) {
				if($ruleIndex < $nArgs) return $this->evaluateArg($args[$ruleIndex]); 
				else return;
			}
			$ctx->clearAllVariables();
			$ruleIndex++;
		}
	}
	
	/**
	 * FuncExp DataFlowActivity which patterns matches a flow of func exp to rules and returns all matching results. The rules are evaluated in order.
	 * FuncExp signature: <code>ctlMatchAllFx(data, dfa, rule1, result1, rule2, result2, ...)</code><br/>
	 * Where arguments are :
	 * - Arg(0) data: The input func exp on which to pattern match.
	 * - Arg(1) dfa: A reference to the underlying FuncExpDFA instance.
	 * for i=2..nArgs:
	 * - Arg(i) ruleI: a FuncExp or FieldSelector or constant used to match against incoming data.
	 * If data and ruleI are FuncExp then data is pattern matched against ruleI. Any variables present in the rule are instanciated.
	 * Elseif data is not a FuncExp and ruleI is a FuncExp, then data is put into variable 'value' and ruleI is evaluated as a boolean.
	 * Else data and ruleI are evaluated and compared using ==.
	 * - Arg(i+1) resultI: Evaluates to the result that the function should return if ruleI fires.
	 * @return Any the resultI of all ruleI which have fired or no return value if no rule have fired.
	 */
	public function ctlMatchAllFx($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 3) throw new FuncExpEvalException('func exp takes at least 3 arguments which are data, dfa and rule1', FuncExpEvalException::INVALID_ARGUMENT);;
		// gets dfa
		$dfa = $args[1];
		if(!($dfa instanceof FuncExpDFA)) throw new FuncExpEvalException('ctlMatchAllFx only works into a DataFlow context', FuncExpEvalException::INVALID_STATE);
		// nothing todo on start or end stream
		if($dfa->isStartStream() || $dfa->isEndStream()) return;
		$ruleIndex = 2;

		// opens new context
		$ctx = $this->getFuncExpVMServiceProvider()->getFuncExpVMContext(true);
		
		$dataIsFx = ($args[0] instanceof FuncExp);
		$dataIsEvaluated = false; $data = null;
		// evaluates each rules
		while($ruleIndex < $nArgs) {
			$rule = $args[$ruleIndex];
			
			// if rule and data are FuncExp then pattern match
			if($dataIsFx && $rule instanceof FuncExp) {
				$ruleEvaluation = $this->matchFuncExp($rule, $args[0], $ctx);
			}
			// else evaluates rule and data and compare values
			else {
				// evaluates input data
				if(!$dataIsEvaluated) {
					$data = $this->evaluateArg($args[0]);
					$dataIsEvaluated = true;
				}
		
				// stores result into variable 'value'
				$ctx->setVariable(fs('value'), $data);
		
				if($rule instanceof FuncExp) $ruleEvaluation = ($this->evaluateArg($rule) == true);
				else $ruleEvaluation = ($this->evaluateArg($rule) == $data);
			}
				
			$ruleIndex++;
			// if rule fires, then outputs result
			if($ruleEvaluation) {
				if($ruleIndex < $nArgs) {
					$dfa->writeResultToOutput($this->evaluateArg($args[$ruleIndex]));
				}
			}
			$ctx->clearAllVariables();
			$ruleIndex++;
		}
	}

	/**
	 * Runs a Decision Tree described as a list of conditional rules which calculates the decision result.
	 * FuncExp signature : <code>ctlDecisionTree(rule1,rule2,...)</code><br/>
	 * Where arguments are :
	 * - Arg(0..n) ruleI: FuncExp. A list of FuncExp which conditionally calculates the decision result.
	 * The decision tree executes in sequence each of the given ruleI and stops after the first one returning a non null value. The first non null value is the decision result.
	 * To conditionally calculate a result, use ctlIf as ruleI.	 
	 * @example ctlDecisionTree(
	 * 				ctlIf(logAnd(eq(logState,"Edition"), eq(Form_Complete, "Submitted")),"Submitted"),
					ctlIf(logAnd(eq(logState,"Submitted"), eq(Review_Complete, "Approved")), "Approved"),
					ctlIf(logAnd(eq(logState,"Approved"), eq(Form_Complete, "Finalized")), "Finalized")
				)
	 * @return Any returns the decision value or null if none.
	 */
	public function ctlDecisionTree($args) {
		$nArgs=$this->getNumberOfArgs($args);		
		if($nArgs>0) {
			$returnValue=null;
			for($i=0;$i<$nArgs;$i++) {
				$returnValue=$this->evaluateArg($args[$i]);
				// returns calculated result if not null
				if(isset($returnValue)) break;
			}
			return $returnValue;
		}
		else return null;
	}
	
	/**
	 * Filters a value against a list of non-desired contants. If value matches one of these constant, returns null, else returns the value itself.
	 * FuncExp signature : <code>ctlFilterVal(val,filter1,...)</code><br/>
	 * Where arguments are :
	 * - Arg(0) val: Evaluates to the value to be returned
	 * - Arg(1..n) filterI: Evaluates to the non-desired values.
	 * @return Any if(val is equal to one of filterI) then returns null, else returns val.
	 */ 
	public function ctlFilterVal($args) {
		$nArgs=$this->getNumberOfArgs($args);
		if($nArgs<1) throw new FuncExpEvalException('ctlFilterVal takes at least one argument which is the value to return', FuncExpEvalException::INVALID_ARGUMENT);
		$val = $this->evaluateArg($args[0]);
		// checks if val matches against filters
		for($i=1;$i<$nArgs;$i++) {
			$filter = $this->evaluateArg($args[$i]);
			if($val == $filter) return null;
		}
		return $val;
	}
	
	/**
	 * Evaluates first argument, if true, then evaluates all next arguments in sequence,
	 * and this as long as first argument evaluates to true
	 * @return value of last evaluated arg
	 */
	public function ctlWhile($args) {
		$n = $this->getNumberOfArgs($args);
		if($n < 2) throw new FuncExpEvalException("For while loop, the number of arguments should be at least 2", FuncExpEvalException::INVALID_ARGUMENT);
		$returnValue = null;
		while($this->evaluateArg($args[0])) {
			for($i = 1; $i < $n; $i++) {
				$returnValue = $this->evaluateArg($args[$i]);
			}
		}
		return $returnValue;
	}

	// Text functions

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
	 * Returns an hash tag character
	 * @return string
	 */
	public function txtHashTag($args) {
		return '#';
	}
	
	/**
	 * Returns an ampestand character
	 * @return string
	 */
	public function txtAmp($args) {
		return '&';
	}
	
	/**
	 * Returns a greater than character
	 * @return string
	 */
	public function txtGt($args) {
	    return '>';
	}
	/**
	 * Returns a lower than character
	 * @return string
	 */
	public function txtLt($args) {
	    return '<';
	}
		
	/**
	 * Returns the translation of a key using the Wigii dictionary and the current language of the user
	 * FuncExp signature : <code>txtDico(key)</code><br/>
	 * Where arguments are :
	 * - Arg(0) key : a defined entry in the dictionary.
	 * @return String the translated key or the key itself if no entry is defined in the dictionary	 
	 */
	public function txtDico($args) {
	    $nArgs = $this->getNumberOfArgs($args);
	    if($nArgs<1) throw new FuncExpEvalException('txtDico takes one argument which is the key to lookup into the dictionary', FuncExpEvalException::INVALID_ARGUMENT);
	    return $this->getTranslationService()->t($this->getPrincipal(), $this->evaluateArg($args[0]));
	}
	
	/**
	 * Creates an html open tag
	 * FuncExp signature : <code>htmlStartTag(tagName,key1,value1,key2,value2,...)</code><br/>
	 * Where arguments are :
	 * - Arg(0) tagName: String. The name of the html tag, for example "div" or "p"
	 * - Arg(1,3,5,...) keyI: String. An html attribute name, for example "class"
	 * - Arg(2,4,6,...) valueI: String. An html attribute value, for example "ui-dialog"	
	 * 
	 * This function cannot be called from public space (i.e. caller is located outside of the Wigii instance)
	 * @return String
	 */
	public function htmlStartTag($args) {
		$this->assertFxOriginIsNotPublic();
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs<1) throw new FuncExpEvalException("htmlStartTag func exp takes at least one argument which is the html tagName", FuncExpEvalException::INVALID_ARGUMENT);
		$tagName = $this->evaluateArg($args[0]);
		if(empty($tagName)) throw new FuncExpEvalException("htmlStartTag func exp takes at least one argument which is the html tagName", FuncExpEvalException::INVALID_ARGUMENT);
		$returnValue = '<'.$tagName;
		if($nArgs>1) {
			$i = 1;
			$key = null;
			$value = null;
			while($i<$nArgs) {
				$key = $this->evaluateArg($args[$i]);
				if(empty($key)) throw new FuncExpEvalException("html attribute cannot be null", FuncExpEvalException::INVALID_ARGUMENT);
				$i++;
				if($i<$nArgs) {
					$value = $this->evaluateArg($args[$i]);
					$i++;
				}
				else $value = '';
				$returnValue .= ' '.$key.'="'.$value.'"';
			}
		}
		$returnValue .= '>';
		return $returnValue;
	}
	/**
	 * Creates an html close tag
	 * FuncExp signature : <code>htmlEndTag(tagName)</code><br/>
	 * Where arguments are :
	 * - Arg(0) tagName: String. The name of the html tag, for example "div" or "p"
	 * 
	 * This function cannot be called from public space (i.e. caller is located outside of the Wigii instance)
	 * @return String
	 */
	public function htmlEndTag($args) {
		$this->assertFxOriginIsNotPublic();
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs<1) throw new FuncExpEvalException("htmlStartTag func exp takes at least one argument which is the html tagName", FuncExpEvalException::INVALID_ARGUMENT);
		$tagName = $this->evaluateArg($args[0]);
		if(empty($tagName)) throw new FuncExpEvalException("htmlStartTag func exp takes at least one argument which is the html tagName", FuncExpEvalException::INVALID_ARGUMENT);
		return '</'.$tagName.'>';
	}
	
	/**
	 * Returns an html nbsp entity repeated n times
	 * FuncExp signature : <code>htmlNbsp(multiplier)</code><br/>
	 * Where arguments are :
	 * - Arg(0) multiplier : optional argument. Evaluates to a positive integer which indicates the number of time
	 * the nbsp html entity must be repeated. Default is one time.
	 * @return String
	 */
	public function htmlNbsp($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) $multiplier = 1;
		else $multiplier = $this->evaluateArg($args[0]);
		if($multiplier <= 0) throw new FuncExpEvalException("The multiplier must be a postive integer", FuncExpEvalException::INVALID_ARGUMENT);
		return str_repeat("&nbsp;", $multiplier);
	}
	
	/**
	 * Returns an html quot entity
	 * 
	 * This function cannot be called from public space (i.e. caller is located outside of the Wigii instance)
	 * @return String
	 */
	public function htmlQuot($args) {
		return '&quot;';
	}
	/**
	 * Returns an html apos entity
	 * @return string
	 */
	public function htmlApos($args) {
		return '&apos;';
	}
	/**
	 * Returns an html lt entity
	 * @return string
	 */
	public function htmlLt($args) {
		return '&lt;';
	}
	/**
	 * Returns an html gt entity
	 * @return string
	 */
	public function htmlGt($args) {
		return '&gt;';
	}
	/**
	 * Returns an html amp entity
	 * @return string
	 */
	public function htmlAmp($args) {
		return '&amp;';
	}
	/**
	 * Builds a URL Query based given the hierarchical part and the query arguments 
	 * FuncExp signature : <code>htmlUrlQuery(baseUrl,key1,value1,key2,value2,...)</code><br/>
	 * Where arguments are :
	 * - Arg(0) baseUrl : String. The hierarchical part of the url (sheme://host/path)
	 * - Arg(1,3,5,...) keyI: String. The query key
	 * - Arg(2,4,6,...) valueI: String. The query value
	 * 
	 * This function cannot be called from public space (i.e. caller is located outside of the Wigii instance)
	 * @return String. Calling htmlUrlQuery(baseUrl,key1,value1,key2,value2) will return baseUrl?key1=value1&key2=value2
	 */	
	public function htmlUrlQuery($args) {
		$this->assertFxOriginIsNotPublic();
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs<1) throw new FuncExpEvalException("htmlUrlQuery func exp takes at least one argument which is the base url", FuncExpEvalException::INVALID_ARGUMENT);
		$baseUrl = $this->evaluateArg($args[0]);
		if(empty($baseUrl)) throw new FuncExpEvalException("htmlUrlQuery func exp takes at least one argument which is the base url", FuncExpEvalException::INVALID_ARGUMENT);
		$returnValue = array();
		if($nArgs>1) {
			$i = 1;
			$key = null;
			$value = null;
			while($i<$nArgs) {
				$key = $this->evaluateArg($args[$i]);
				if(empty($key)) throw new FuncExpEvalException("query key cannot be null", FuncExpEvalException::INVALID_ARGUMENT);
				$i++;
				if($i<$nArgs) {
					$value = $this->evaluateArg($args[$i]);
					$i++;
				}
				else $value = '';
				$returnValue[$key] = $value;
			}
		}
		$returnValue = $baseUrl.'?'.http_build_query($returnValue);
		return $returnValue;
	}
	
	/**
	 * Returns an html document headeer
	 * 
	 * This function cannot be called from public space (i.e. caller is located outside of the Wigii instance)
	 * @return String
	 */
	public function htmlHeader($args) {
		$this->assertFxOriginIsNotPublic();
		return '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
	} 
		
	/**
	 * Returns a string representing a date in a Wigii compatible format (Y-m-d H:i:s).
	 * For general formatting use the func exp date (defined in PhpStdFL).
	 * FuncExp signature : <code>txtDate(timestamp)</code><br/>
	 * Where arguments are :
	 * - Arg(0) timestamp : optional argument. Evaluates to a positive integer which indicates a timestamp. If null, then gets the php current time.
	 * @return String the formatted date
	 */
	public function txtDate($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) $timestamp = time();
		else $timestamp = $this->evaluateArg($args[0]);
		return date('Y-m-d H:i:s', $timestamp);		
	}

	/**
	 * Converts a MIME type to its file extension
	 * FuncExp signature : <code>txtMime2Ext(mime)</code><br/>
	 * Where arguments are :
	 * - Arg(0) mime : the MIME type as a string
	 * @return String the file extension with the dot. (example: .html or .pdf), returns null if MIME is not known by the system.
	 */
	public function txtMime2Ext($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException("func exp 'txtMime2Ext' takes at least one argument: the MIME type as a string", FuncExpEvalException::INVALID_ARGUMENT);
		return mime2ext($this->evaluateArg($args[0]));
	}
	
	/**
	 * Converts a file extension to its MIME type
	 * FuncExp signature : <code>txtExt2Mime(mime)</code><br/>
	 * Where arguments are :
	 * - Arg(0) ext : the file extension with the dot (example: .html or .pdf)
	 * @return String the MIME type as a String. Returns 'application/octet-stream' if the extension is not known by the system.
	 */
	public function txtExt2Mime($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException("func exp 'txtExt2Mime' takes at least one argument: the file extension with the dot (example: .html or .pdf)", FuncExpEvalException::INVALID_ARGUMENT);
		return typeMime($this->evaluateArg($args[0]));
	}
	
	/**
	 * Converts a string to be html compatible. If already html, then keeps it like this, else replaces needed entities
	 * FuncExp signature : <code>txt2html(str)</code><br/>
	 * Where arguments are :
	 * - Arg(0) str : the string to be converted
	 * 
	 * This function cannot be called from public space (i.e. caller is located outside of the Wigii instance)
	 * @return String the String html compatible.
	 */
	public function txt2html($args) {
		$this->assertFxOriginIsNotPublic();
		$nArgs = $this->getNumberOfArgs($args);
		$returnValue = '';
		if($nArgs > 0) {
			$str = $this->evaluateArg($args[0]);
			$tmpTag = 'txt2html'.time();			
			$returnValue = tryStr2Xml("<$tmpTag>".$str."</$tmpTag>");			
			if($returnValue instanceof SimpleXMLElement) {
				$returnValue = $returnValue->asXml();
				$returnValue = str_replace(array('<?xml version="1.0"?>',"\n<$tmpTag>","<$tmpTag>","</$tmpTag>\n","</$tmpTag>"), '', $returnValue);
			}
			else $returnValue = htmlentities($str,ENT_COMPAT,'UTF-8');			
		}
		return $returnValue;
	}
	
	/**
	 * Implodes values.
	 * FuncExp signature: <code>implode(sep, val1, val2, ..., valn)</code><br/>
	 * Where arguments are :
	 * - Arg(0) sep: Evaluates to a String which is the separator to use between the values
	 * - Arg(1..n) valI: Evaluates to any value that can be converted to a String.
	 * If evaluates to an array, then first implodes the array using the given separator.
	 * @return String the imploded string
	 */
	public function implode($args)
	{
		if($this->getNumberOfArgs($args) < 1) throw new FuncExpEvalException("args should have at least one value which is the separator", FuncExpEvalException::INVALID_ARGUMENT);
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
	 * Implodes only unique values. (filters duplicates)
	 * FuncExp signature: <code>implodeUnique(sep, val1, val2, ..., valn)</code><br/>
	 * Where arguments are :
	 * - Arg(0) sep: Evaluates to a String which is the separator to use between the values
	 * - Arg(1..n) valI: Evaluates to any value that can be converted to a String.
	 * If evaluates to an array, then first implodes the array using the given separator.
	 * @return String the imploded string
	 */
	public function implodeUnique($args) {
		if($this->getNumberOfArgs($args) < 1) throw new FuncExpEvalException("args should have at least one value which is the separator", FuncExpEvalException::INVALID_ARGUMENT);
		$returnValue = array(); $i = 0; $s = ", ";		
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
					if(is_array($tv)) $returnValue = array_merge($returnValue, $tv);
					else $returnValue[] = $tv;
					$i++;
				}
			}
		}
		$returnValue = array_unique($returnValue,SORT_REGULAR);
		if(!empty($returnValue)) return implode($s,$returnValue);
		else return '';
	}

	/**
	 * Concatenates the arguments, returns null if no arg
	 * FuncExp signature: <code>concat(val0, val1, ..., valn)</code><br/>
	 * Where arguments are :
	 * - Arg(0..n) valI: Evaluates to any value that can be converted to a String.
	 * @return String the concatenated string or null if no argument.
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
	 * Prepends a prefix to some content only if content is not null.
	 * FuncExp signature: <code>prepend(prefix,str)</code><br/>
	 * Where arguments are :
	 * - Arg(0) prefix: Evaluates to a string.
	 * - Arg(1) str: Evaluates to a string.
	 * @return String if(str is not empty) returns prefix.str else return ''
	 */
	public function prepend($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs>0) $prefix = $this->evaluateArg($args[0]);
		else $prefix = '';
		if($nArgs>1) $str = $this->evaluateArg($args[1]);
		else $str = '';
		if(!empty($str)) return $prefix.$str;
		else return '';
	}	
	
	/**
	 * Creates an array from a list of values represented as a string
	 * FuncExp signature: <code>str2list(str, sep=',', trim=true)</code><br/>
	 * Where arguments are :
	 * - Arg(0) str: String. The list of values as a string.
	 * - Arg(1) sep: String. The separator used to split the string. Defaults to comma. (can be a regex pattern like [,;])
	 * - Arg(2) trim: Boolean. If true, then all spaces and line separators between values are removed. Default to true.
	 * @return Array an array containing the values.
	 */
	public function str2list($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException('str2list function takes at least one argument which is the string to split', FuncExpEvalException::INVALID_ARGUMENT);
		$s = $this->evaluateArg($args[0]);
		if($nArgs > 1) $sep = $this->evaluateArg($args[1]);
		else $sep = ',';
		if($nArgs > 2) $trim = $this->evaluateArg($args[2]);
		else $trim = true;
		
		if($trim) $s = trim($s);
		if(!empty($s)) return preg_split(($trim ? '/\s*'.$sep.'\s*/' : '/'.$sep.'/'), $s);
		else return array();
	}
	
	/**
	 * Creates a multi valued map (an array of array) from a string.
	 * The string should have the following format by default : 
	 * key1: value11, value12, value13, ...
	 * key2: value21, value22, value23, ...
	 * ...
	 * Key separator is semicolon, value separator is comma and entry separator is new line. 
	 * FuncExp signature: <code>str2multiValuedMap(str, keySep=':', valueSep=',', entrySep='\n', trim=true)</code><br/>
	 * Where arguments are :
	 * - Arg(0) str: String. The multi valued map as a string.
	 * - Arg(1) keySep: String. The separator used to split the key from the list of values. Defaults to semicolon. (can be a regex pattern like :|=>|=)
	 * - Arg(2) valueSep: String. The separator used to split the list of values. Defaults to comma. (can be a regex pattern like [,;])
	 * - Arg(3) entrySep: String. The separator used to split the map entries. Defaults to new line. (can be a regex pattern)
	 * - Arg(4) trim: Boolean. If true, then all spaces and line separators between values are removed. Default to true.
	 * @return Array an array containing the values.
	 */
	public function str2multiValuedMap($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException('str2multiValuedMap function takes at least one argument which is the string to split', FuncExpEvalException::INVALID_ARGUMENT);
		$s = $this->evaluateArg($args[0]);
		if($nArgs > 1) $keySep = $this->evaluateArg($args[1]);
		else $keySep = ':';
		if($nArgs > 2) $valueSep = $this->evaluateArg($args[2]);
		else $valueSep = ',';
		if($nArgs > 3) $entrySep = $this->evaluateArg($args[3]);
		else $entrySep = '\v';
		if($nArgs > 4) $trim = $this->evaluateArg($args[4]);
		else $trim = true;
		
		if($trim) $s = trim($s);
		if(!empty($s)) {
			$returnValue = array();
			// splits map entries
			$a1 = preg_split('/'.$entrySep.'/', $s);
			if(!(empty($a1))) {
				foreach($a1 as $entry) {
					if($trim) $entry = trim($entry);
					// split key / value list pair
					$a2 = array();
					if(preg_match('/([\S\s]*)'.$keySep.'([\S\s]*)/', $entry, $a2)) {
						$key = $a2[1]; $values = $a2[2];
						if($trim) {
							$key = trim($key); 
							$values = trim($values);
						}
						// split values
						if(!empty($key)) {
							if(!empty($values)) $values = preg_split(($trim ? '/\s*'.$valueSep.'\s*/' : '/'.$valueSep.'/'), $values);
							else $values = array();
							$returnValue[$key] = $values;
						}
					}					
				}
			}
			return $returnValue;
		}
		else return array();
	}
}