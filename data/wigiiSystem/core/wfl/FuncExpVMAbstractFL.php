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
 * The root abstract class for all Func Exp libraries.
 * This class gives access to the VM context and helpers for Func Exp implementors 
 * Created by CWE on 1er octobre 2013
 */
abstract class FuncExpVMAbstractFL
{
	private $_debugLogger;
	
	// Dependency injection
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("FuncExpVMAbstractFL");
		}
		return $this->_debugLogger;
	}
	
	private $funcExpVM;
	/**
	 * Links this FuncExp library to a VM for execution
	 * @param FuncExpVM $funcExpVM an up and running FuncExpVM instance
	 */
	public function setFuncExpVM($funcExpVM) {
		$this->funcExpVM = $funcExpVM;
	}
	protected function getFuncExpVM() {
		if(!isset($this->funcExpVM)) throw new FuncExpEvalException('FuncExpVM has not been linked to library, please inject a valid instance', FuncExpEvalException::CONFIGURATION_ERROR);
		else return $this->funcExpVM;
	}
		
	/**
	 * Returns the FuncExpVMServiceProvider to use
	 */
	protected function getFuncExpVMServiceProvider() {return $this->getFuncExpVM()->getFuncExpVMServiceProvider();}
		
	/**
	 * Returns the Principal to use
	 */
	protected function getPrincipal() {return $this->getFuncExpVM()->getPrincipal();}
	
	
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
		else throw new FuncExpEvalException("funcExp args should be an array", FuncExpEvalException::INVALID_ARGUMENT);
	}
	
	/**
	 * Evaluates function arg and returns its value.
	 * If arg is a FieldSelector that doesn't point to any declared variable or field in parent evaluator,
	 * then an exception is thrown. 
	 * If you need a silent evaluation of FieldSelector, use the method 'evaluateFieldSelector' instead.
	 */
	protected function evaluateArg($arg) {return $this->getFuncExpVM()->evaluateFuncExp($arg, $this);}
	
	/**
	 * Evaluates a FuncExp and returns its value
	 * @param $funcExp an instance of a FuncExp
	 */
	protected function evaluateFuncExp($funcExp) {return $this->getFuncExpVM()->evaluateFuncExp($funcExp, $this);}
	
	/**
	 * Evaluates a FieldSelector and returns its value.
	 * If the field selector doesn't point to any declared variable or field in parent evaluator,
	 * then returns the given default value (null by default).
	 * If you prefer to get an exception in that case, use the method 'evaluateArg' instead.
	 * @param FieldSelector $fieldSelector the field selector to evaluate.
	 * @param Any $defaultVal the default value that should be returned if the field selector doesn't point 
	 * to any declared variable or field in parent evaluator
	 */
	protected function evaluateFieldSelector($fieldSelector, $defaultVal=null) {
		 try {return $this->evaluateArg($fieldSelector);}
		 catch(ServiceException $e) {
		 	switch($e->getWigiiRootException()->getCode()) {
		 		case ListException::DOES_NOT_EXIST:
		 		case ElementServiceException::NO_CORRESPONDANT_FIELD:
		 		case FuncExpEvalException::VARIABLE_NOT_DECLARED:
		 			return $defaultVal;
		 		default: throw $e;
		 	}
		 }
	}
	
	// Context
	
	/**
	 * Stores a value in the current context (on top of the stack)
	 * Any value stored under the same key at this level of the stack is replaced,
	 * any value stored under this key on a lower level of the stack are kept but occulted by this new value
	 * @param Scalar $key the key of the value to be stored
	 * @param Any $value the value to be stored (can be any object)
	 */
	protected function setValueInContext($key, $value) {$this->getFuncExpVM()->setValueInScope($key, $value);}
	
	/**
	 * Returns the value stored under the given key if defined, else returns null
	 * By default, it walks through the whole stack searching for the key, from top to down,
	 * and returns the first value found.
	 * Except if the argument goThroughParents is false, then it searches only in the context on the top of the stack
	 * @param Scalar $key the key for which to retrieve a value
	 * @param Boolean $goThroughParents if true (default), then searches for the key through the whole stack else only on top of the stack
	 */
	protected function getValueInContext($key, $goThroughParents=true) {return $this->getFuncExpVM()->getValueInScope($key, $goThroughParents);}
	
	
	// Utilities
	
	/**
	 * Returns the value of an attribute into a given WigiiGraphNode passed through a FuncExp call context
	 * @param String $attrKey the attribute key
	 * @param Array $funcExpArgs the func exp calling context. Should be an array of the form (wigiiGraphNode[, funcExpDFA])
	 * @param Any $defaultValue a default value if the WigiiGraphNode is not defined.
	 */
	protected function getWigiiGraphNodeAttribute($attrKey, $funcExpArgs, $defaultValue=null) {
		$nArgs = $this->getNumberOfArgs($funcExpArgs);
		if($nArgs > 0) {
			$wgn = $this->evaluateArg($funcExpArgs[0]);
			if(isset($wgn)) {
				if(!($wgn instanceof WigiiGraphNode)) throw new FuncExpEvalException("the object does not evaluate to a WigiiGraphNode", FuncExpEvalException::INVALID_ARGUMENT);
				else $returnValue = $wgn->getAttribute($attrKey);
			}
			else $returnValue = $defaultValue;
			if($nArgs > 1 && ($funcExpArgs[1] instanceof FuncExpDFA)) {
				$dfa = $funcExpArgs[1];
				if($dfa->isRunning() || $dfa->isSingleData()) return $returnValue;
			}
			else return $returnValue; 			
		}
		else return $defaultValue;
	}
	/**
	 * Sets the value of an attribute into a given WigiiGraphNode passed through a FuncExp call context
	 * @param String $attrKey the attribute key
	 * @param Array $funcExpArgs the func exp calling context. Should be an array of the form (wigiiGraphNode[, funcExpDFA], value)
	 */
	protected function setWigiiGraphNodeAttribute($attrKey, $funcExpArgs) {
		$nArgs = $this->getNumberOfArgs($funcExpArgs);
		if($nArgs < 2) throw new FuncExpEvalException("the funcExp takes at least two arguments: an object which evaluates to a WigiiGraphNode and a value", FuncExpEvalException::INVALID_ARGUMENT);
		$wgn = $this->evaluateArg($funcExpArgs[0]);
		//$this->debugLogger()->write((is_object($wgn) ? get_class($wgn) : json_encode($wgn)));
		if(isset($wgn) && !($wgn instanceof WigiiGraphNode)) throw new FuncExpEvalException("the object does not evaluate to a WigiiGraphNode", FuncExpEvalException::INVALID_ARGUMENT);
		if($nArgs > 2 && ($funcExpArgs[1] instanceof FuncExpDFA)) {
			$dfa = $funcExpArgs[1];
			$value = $funcExpArgs[2];
			if($value instanceof FuncExp) {
				$vArgs = $value->getArguments();
				if(isset($wgn)) {
					$ordinal = $wgn->getAttribute('ordinal');
					if(is_null($ordinal)) $ordinal = $wgn;
				}
				switch($dfa->getState()) {
					case FuncExpDFA::FUNCEXP_DFA_SINGLE_DATA:
						$dataFlowArgs = array($ordinal, $dfa);
						if(isset($vArgs)) $value->setArguments(array_merge($dataFlowArgs, $vArgs));
						else $value->setArguments($dataFlowArgs);
						break;
					case FuncExpDFA::FUNCEXP_DFA_STARTSTREAM:						
						$dataFlowArgs = array(null, $dfa);
						if(isset($vArgs)) $value->setArguments(array_merge($dataFlowArgs, $vArgs));
						else $value->setArguments($dataFlowArgs);
						break;
					case FuncExpDFA::FUNCEXP_DFA_RUNNING:
						$vArgs[0] = $ordinal;
						$vArgs[1] = $dfa;
						$value->setArguments($vArgs);
						break;
					case FuncExpDFA::FUNCEXP_DFA_ENDSTREAM:
						$vArgs[0] = null;
						$vArgs[1] = $dfa;
						$value->setArguments($vArgs);
						break;
				}
			}
			$value = $this->evaluateArg($value);
			if($dfa->isStartStream() || $dfa->isEndStream()) return;
		}
		else $value = $this->evaluateArg($funcExpArgs[1]);
		
		$wgn->setAttribute($attrKey, $value);
		return $wgn;
	}
	
	/**
	 * Updates the value of an attribute into a given WigiiGraphNode passed through a FuncExp call context
	 * @param String $attrKey the attribute key
	 * @param Array $funcExpArgs the func exp calling context. Should be an array of the form (wigiiGraphNode[, funcExpDFA], value)
	 */
	protected function updateWigiiGraphNodeAttribute($attrKey, $funcExpArgs) {
		$nArgs = $this->getNumberOfArgs($funcExpArgs);
		if($nArgs < 2) throw new FuncExpEvalException("the funcExp takes at least two arguments: an object which evaluates to a WigiiGraphNode and a value", FuncExpEvalException::INVALID_ARGUMENT);
		$wgn = $this->evaluateArg($funcExpArgs[0]);
		//$this->debugLogger()->write((is_object($wgn) ? get_class($wgn) : json_encode($wgn)));
		if(isset($wgn) && !($wgn instanceof WigiiGraphNode)) throw new FuncExpEvalException("the object does not evaluate to a WigiiGraphNode", FuncExpEvalException::INVALID_ARGUMENT);
		if($nArgs > 2 && ($funcExpArgs[1] instanceof FuncExpDFA)) {
			$dfa = $funcExpArgs[1];
			$value = $funcExpArgs[2];
			if($value instanceof FuncExp) {
				$vArgs = $value->getArguments();
				$origValue = null;
				if(isset($wgn)) {
					$origValue = $wgn->getAttribute($attrKey);					
				}
				switch($dfa->getState()) {
					case FuncExpDFA::FUNCEXP_DFA_SINGLE_DATA:
						$dataFlowArgs = array($origValue, $dfa);
						if(isset($vArgs)) $value->setArguments(array_merge($dataFlowArgs, $vArgs));
						else $value->setArguments($dataFlowArgs);
						break;
					case FuncExpDFA::FUNCEXP_DFA_STARTSTREAM:
						$dataFlowArgs = array(null, $dfa);
						if(isset($vArgs)) $value->setArguments(array_merge($dataFlowArgs, $vArgs));
						else $value->setArguments($dataFlowArgs);
						break;
					case FuncExpDFA::FUNCEXP_DFA_RUNNING:
						$vArgs[0] = $origValue;
						$vArgs[1] = $dfa;
						$value->setArguments($vArgs);
						break;
					case FuncExpDFA::FUNCEXP_DFA_ENDSTREAM:
						$vArgs[0] = null;
						$vArgs[1] = $dfa;
						$value->setArguments($vArgs);
						break;
				}
			}
			$value = $this->evaluateArg($value);
			if($dfa->isStartStream() || $dfa->isEndStream()) return;
		}
		else $value = $this->evaluateArg($funcExpArgs[1]);
	
		$wgn->setAttribute($attrKey, $value);
		return $wgn;
	}
	
	/**
	 * Tries to match a func exp used as a pattern to a func exp used as a subject.
	 * Any variables (field selectors) in the pattern will be synchronized with the corresponding element in the subject.
	 * The function recursively goes down the tree. Matching occurs if function names are equal and if arguments match.
	 * Two arguments match if :
	 * - they are two objects and === return true,
	 * - or if they are two arrays (doesn't check the array content),
	 * - or if they are two scalars and == returns true.
	 * @param FuncExp|FieldSelector|Any $pattern the pattern
	 * @param FuncExp|FieldSelector|Any $subject the subject
	 * @param FuncExpVMContext $matches the VM context in which to store the synchronized variables.
	 * @return boolean returns true if the subject matches the pattern, else false.
	 */
	protected function matchFuncExp($pattern, $subject, $matches) {
		// if pattern is a variable, then gets its value
		$pType = 0;
		if($pattern instanceof FieldSelector) {
			try {
				$pattern = $matches->getVariable($pattern);
			}
			catch(FuncExpEvalException $e) {
				if($e->getCode() == FuncExpEvalException::VARIABLE_NOT_DECLARED) $pType = 2;
				else throw $e;
			}
		}
		// if pattern is not a variable, then gets its type
		if($pType != 2) {
			if(is_null($pattern)) $pType = 0;
			elseif(is_array($pattern)) $pType = 1;
			elseif($pattern instanceof FuncExp) $pType = 3;
			elseif(is_object($pattern)) $pType = 4;
			else /*scalars*/ $pType = 5;
		}
	
		// if subject is a variable then gets its value (null if not declared)
		if($subject instanceof FieldSelector) {
			$subject = $this->evaluateFieldSelector($subject);
		}
		// gets subject datatype
		if(is_null($subject)) $sType = 0;
		elseif(is_array($subject)) $sType = 1;
		elseif($subject instanceof FuncExp) $sType = 3;
		elseif(is_object($subject)) $sType = 4;
		else /*scalars*/ $sType = 5;
	
		// if pattern is a variable, then sets its value with subject and returns
		if($pType == 2) {
			$matches->setVariable($pattern, $subject);
			return true;
		}
		// elseif type mismatch then return false
		elseif($pType != $sType) return false;
		// elseif type is null or array, then returns true
		elseif($pType == 0 || $pType == 1) return true;
		// elseif type is object then returns value of ===
		elseif($pType == 4) return $pattern === $subject;
		// elseif type is scalar then returns value of ==
		elseif($pType == 5) return $pattern == $subject;
		// elseif type is FuncExp then pattern matches recursively
		elseif($pType == 3) {
			// checks name equality
			if($pattern->getName() != $subject->getName()) return false;
			// checks that pattern arguments matches subject ones
			// subject can have more arguments.
			$pArgs = $pattern->getArguments();
			if(empty($pArgs)) $pNArgs = 0; else $pNArgs = count($pArgs);
			$sArgs = $subject->getArguments();
			if(empty($sArgs)) $sNArgs = 0; else $sNArgs = count($sArgs);
			if($sNArgs < $pNArgs) return false;
				
			$i = 0;
			while($i<$pNArgs) {
				if(!$this->matchFuncExp($pArgs[$i], $sArgs[$i], $matches)) return false;
				$i++;
			}
			/* if subject has more arguments than pattern then :
			 * - if last argument of pattern is a variable, then puts in 'tail' subfield the
			 * remaining arguments from subject,
			 * - else considers that pattern does not match subject.
			 */
			if($sNArgs > $i) {
				if($i > 0 && ($pArgs[$i-1] instanceof FieldSelector)) {
					$subFieldName = $pArgs[$i-1]->getSubFieldName();
					if(isset($subFieldName) && $subFieldName != 'tail') {
						$tailFs = fs($pArgs[$i-1]->getFieldName(), 'tail');	
					}
					else {
						$tailFs = fs($pArgs[$i-1]->getFieldName().'_tail');
					}
					// clones the current func exp and puts all remaining arguments
					$tailFx = FuncExp::createInstance($subject->getName());
					while($i<$sNArgs) {
						$tailFx->addArgument($sArgs[$i]);
						$i++;
					}
					// then matches this tail func exp with the tail variable
					return $this->matchFuncExp($tailFs, $tailFx, $matches);
				}
				else return false;
			}
			return true;
		}
		// else should not happen.
		else throw new FuncExpEvalException('unsupported type '.$pType, FuncExpEvalException::INVALID_STATE);
	}
}