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
 * A class to help building Functional Expressions in PHP
 * It supports FuncExp, FieldSelectors, LogExp, GroupExp and FieldSortingKeys
 * Created by CWE on 13 novembre 2013
 */
class FuncExpBuilder {	
	// FieldSelector cache
	private $fsCache;
	
	
	// Dependency injection
	
	private $_debugLogger;
	private function debugLogger() {
		if (!isset ($this->_debugLogger)) {
			$this->_debugLogger = DebugLogger :: getInstance("FuncExpBuilder");
		}
		return $this->_debugLogger;
	}
	
	private $dflowS;
	public function setDataFlowService($dataFlowService)
	{
		$this->dflowS = $dataFlowService;
	}
	protected function getDataFlowService()
	{
		// autowired
		if(!isset($this->dflowS))
		{
			$this->dflowS = ServiceProvider::getDataFlowService();
		}
		return $this->dflowS;
	}
	
	private $eltS;
	public function setElementService($elementService)
	{
		$this->eltS = $elementService;
	}
	protected function getElementService()
	{
		// autowired
		if(!isset($this->eltS))
		{
			$this->eltS = ServiceProvider::getElementService();
		}
		return $this->eltS;
	}
	
	// FuncExp builder
	
	/**
	 * Constructs a FuncExp given its name and an array of arguments.
	 * This function supports variable number of arguments, that means that
	 * instead of providing an array with the arguments, you can pass the arguments
	 * in a comma separated list as a normal function call.
	 * example: $funcExpBuilder->fx('myFunction', 'valarg1', 'valarg2', 'valarg3') is equivalent
	 * to $funcExpBuilder->fx('myFunction', array('valarg1', 'valarg2', 'valarg3'))
	 * @param String $funcName the name of the function
	 * @param Array $funcArgs an array of arguments
	 * @return FuncExp a FuncExp instance.
	 */
	public function fx($funcName, $funcArgs=null) {
		$nArgs = func_num_args();
		$returnValue = FuncExp::createInstance($funcName);
		if($funcName instanceof FuncExpParameter) $funcName->registerSetterMethod('setName', $returnValue);
		if($nArgs > 2) {
			for($i = 1; $i < $nArgs; $i++) {
				$returnValue->addArgument(func_get_arg($i));
			}
		}
		else if(isset($funcArgs)) {
			if($funcArgs instanceof FuncExpParameter) $funcArgs->registerSetterMethod('setArguments', $returnValue);
			if(!is_array($funcArgs)) $funcArgs = array($funcArgs);
			$returnValue->setArguments($funcArgs);
		}
		return $returnValue; 
	}
	
	/**
	 * Creates a FuncExp which callbacks a PHP method or executes a closure.
	 * @param String|Closure $method the method name or function name or closure that should be called.
	 * The callback signature is callback(principal, valarg1, valarg2, ...),
	 * where arguments are :
	 * - principal: Principal, the principal executing the function
	 * - valarg1, ..., valargn: Any, the function arguments.
	 * The return value of the callback is returned as the return value of the wrapping func exp.
	 * @param Object $obj if set, then should be the object instance which holds the method that should be called.
	 * @param Array $funcArgs an array of arguments
	 * This function supports variable number of arguments, that means that
	 * instead of providing an array with the arguments, you can pass the arguments
	 * in a comma separated list as a normal function call.
	 * example: $funcExpBuilder->fx_callback('myMethod', $obj, 'valarg1', 'valarg2', 'valarg3') is equivalent
	 * to $funcExpBuilder->fx_callback('myMethod', $obj, array('valarg1', 'valarg2', 'valarg3'))
	 * @return FuncExp a FuncExp instance wrapping the callback
	 */
	public function fx_callback($method, $obj, $funcArgs=null) {
		$nArgs = func_num_args();
		// creates the callback FuncExp wrapper
		$returnValue = FuncExp::createInstance('callbackFx');
		// creates the callback object
		$callback = CallableObject::createInstance($method, $obj);
		// sets the callback arguments
		$returnValue->addArgument($callback);
		$returnValue->addArgument(false); /* set calling style to normal method call (not func exp style) */
		if($nArgs > 3) {
			for($i = 2; $i < $nArgs; $i++) {
				$returnValue->addArgument(func_get_arg($i));
			}
		}
		else if(isset($funcArgs)) {
			if(is_array($funcArgs)) {
				foreach($funcArgs as $arg) {
					$returnValue->addArgument($arg);
				}
			}
			else $returnValue->addArgument($funcArgs);
		}
		return $returnValue; 
	}
	
	/**
	 * Creates a FuncExp which callbacks a PHP method or executes a closure in a "func exp style", 
	 * i.e. that arguments are not evaluated before calling the function.
	 * @param String|Closure $method the method name or function name or closure that should be called.
	 * @param Object $obj if set, then should be the object instance which holds the method that should be called.
	 * The callback signature is callback(callbackFL, args),
	 * where arguments are :
	 * - callbackFL: CallbackFL, a reference to the func exp executing environment that can be used to evaluate arguments, get the principal or other information.
	 * - args: Array, an array containing the function arguments. Each argument should be evaluated using the callbackFL->evaluateArg() method.
	 * The return value of the callback is returned as the return value of the wrapping func exp.
	 * @param Array $funcArgs an array of arguments
	 * This function supports variable number of arguments, that means that
	 * instead of providing an array with the arguments, you can pass the arguments
	 * in a comma separated list as a normal function call.
	 * example: $funcExpBuilder->fx_fxcallback('myMethod', $obj, 'valarg1', 'valarg2', 'valarg3') is equivalent
	 * to $funcExpBuilder->fx_fxcallback('myMethod', $obj, array('valarg1', 'valarg2', 'valarg3'))
	 * @return FuncExp a FuncExp instance wrapping the callback
	 */
	public function fx_fxcallback($method, $obj, $funcArgs=null) {
		$nArgs = func_num_args();
		// creates the callback FuncExp wrapper
		$returnValue = FuncExp::createInstance('callbackFx');
		// creates the callback object
		$callback = CallableObject::createInstance($method, $obj);
		// sets the callback arguments
		$returnValue->addArgument($callback);
		$returnValue->addArgument(true); /* set calling style to func exp style */
		if($nArgs > 3) {
			for($i = 2; $i < $nArgs; $i++) {
				$returnValue->addArgument(func_get_arg($i));
			}
		}
		else if(isset($funcArgs)) {
			if(is_array($funcArgs)) {
				foreach($funcArgs as $arg) {
					$returnValue->addArgument($arg);
				}
			}
			else $returnValue->addArgument($funcArgs);
		}
		return $returnValue;
	}
	
	/**
	 * Evaluates a given FuncExp using the wigii FuncExpVM and the provided FuncExp modules.
	 * @param Principal $principal the principal evaluating the FuncExp
	 * @param FuncExp|String $funcExp the func exp to evaluate. 
	 * If funcExp is a string, then first parses it.
	 * @param Array|String $modules an array of class names or one string
	 * This function supports variable number of arguments, that means that
	 * instead of providing an array of modules, you can pass the arguments
	 * in a comma separated list as a normal function call.
	 * example: $funcExpBuilder->evalfx($principal, $myFuncExp, 'module1', 'module2', 'module3') is equivalent
	 * to $funcExpBuilder->evalfx($principal, $myFuncExp, array('module1', 'module2', 'module2'))
	 * @return Any returns the value of the evaluated func exp.
	 */
	public function evalfx($principal, $funcExp, $modules=null) {
		if(is_string($funcExp)) $funcExp = $this->str2fx($funcExp);		
		$nArgs = func_num_args();		
		$vm = ServiceProvider::getFuncExpVM($principal);
		$returnValue = null;
		try {		
			if($nArgs > 3) {
				for($i = 2; $i < $nArgs; $i++) {
					$vm->useModules(func_get_arg($i));
				}
			}
			elseif(isset($modules)) $vm->useModules($modules);
			$returnValue = $vm->evaluateFuncExp($funcExp);
			$vm->freeMemory();			
		}
		catch(Exception $e) {
			$vm->freeMemory();
			throw $e;
		}
		return $returnValue;
	}
	
	/**
	 * Converts a string to a FuncExp
	 */
	public function str2fx($str) {
		return TechnicalServiceProvider::getFieldSelectorFuncExpParser()->createFuncExpFromString($str);
	}
	
	/**
	 * Converts a LogExp given into its string form to a semantically equivalent FuncExp
	 * @param String $str a log exp in its string form
	 * @return FuncExp
	 */
	public function strlx2fx($str) {
		return TechnicalServiceProvider::getFieldSelectorLogExpParser()->createFuncExpFromStringLogExp($str);
	}	
	
	/**
	 * Tries to convert any object to its FuncExp equivalent
	 * Or the Object exposes a toFx() method, or the object can convert to a string, or it has a specific built in conversion mechanism,
	 * or it is unsupported.
	 * @param Any $obj
	 * @return Scalar|FuncExp a FuncExp able to create back the Object or its scalar equivalent
	 */
	public function object2fx($obj) {
		$returnValue = null;
		if(empty($obj)) $returnValue = null;
		elseif($obj === true) $returnValue = fx('logTrue');
		elseif($obj === false) $returnValue = fx('logFalse');
		elseif(is_scalar($obj)) $returnValue = (string)$obj;
		elseif(is_array($obj)) {
			$returnValue = fx('newMap');			
			foreach($obj as $k => $v) {
				$returnValue->addArgument($this->object2fx($k));
				$returnValue->addArgument($this->object2fx($v));
			}			
		}
		elseif($obj instanceof stdClass) {
			$returnValue = fx('newObject');
			foreach($obj as $k => $v) {
				$returnValue->addArgument($this->object2fx($k));
				$returnValue->addArgument($this->object2fx($v));
			}
		}
		elseif($obj instanceof FieldSelectorList) $returnValue = $this->fsl2fx($obj);
		elseif($obj instanceof FieldSortingKeyList) $returnValue = $this->fskl2fx($obj);
		elseif($obj instanceof WigiiBPLParameter) $returnValue = $this->wigiiBPLParam2fx($obj);
		elseif($obj instanceof LogExp) {
		    $lxFxBuilder = TechnicalServiceProvider::getFieldSelectorLogExpFuncExpBuilder();
		    $returnValue = $lxFxBuilder->logExp2funcExp($obj);
		    $lxFxBuilder->freeMemory();
		}
		elseif(method_exists($obj, 'toFx')) $returnValue = $obj->toFx();
		elseif(method_exists($obj, '__toString')) $returnValue = (string)$obj;
		else throw new FuncExpEvalException('cannot convert object of class '.get_class($obj).' to its FuncExp equivalent.', FuncExpEvalException::UNSUPPORTED_OPERATION);
		return $returnValue;
	}
	
	/**
	 * Tries to convert any object to its FuncExp equivalent, and then down to a String
	 * Or the Object exposes a toFx() method, or the object can convert to a string, or it has a specific built in conversion mechanism,
	 * or it is unsupported.
	 * @param Any $obj
	 * @return String a String representation of a FuncExp able to create back the Object or its scalar equivalent
	 */
	public function object2strfx($obj) {
	    return TechnicalServiceProvider::getFieldSelectorFuncExpParser()->funcExpToString($this->object2fx($obj));
	}
	
	// FieldSelector builder
	
	/**
	 * Constructs a FieldSelector given the field name and optional subfield name
	 * @param String $fieldName the field name
	 * @param String $subFieldName optional subfield name
	 * @return FieldSelector a instance of a FieldSelector
	 */
	public function fs($fieldName, $subFieldName=null) {
		$fieldFxParam = ($fieldName instanceof FuncExpParameter);
		$subfieldFxParam = ($subFieldName instanceof FuncExpParameter);
		// if FieldSelector is well defined (no FuncExpParam), then caches it
		if(!$fieldFxParam && !$subfieldFxParam) {
			if(!isset($this->fsCache)) $this->fsCache = array();
			$key = "($fieldName($subFieldName))";
			//$this->debugLogger()->write("FieldSelector cache key:".$key);
			$returnValue = $this->fsCache[$key];
			if(!isset($returnValue) 
				|| $returnValue->getFieldName() != $fieldName || $returnValue->getSubFieldName() != $subfFieldName) {
				$returnValue = FieldSelector::createInstance($fieldName, $subFieldName);
				if(isset($returnValue)) $this->fsCache[$key] = $returnValue;
			}	
			else $this->debugLogger()->write("reused FieldSelector($fieldName".($subFieldName ? ",$subFieldName)":")")." from cache");	
		}
		// else always creates a new instance
		else $returnValue = FieldSelector::createInstance($fieldName, $subFieldName);
		if($fieldFxParam) $fieldName->registerSetterMethod('setFieldName', $returnValue);
		if($subfieldFxParam) $subFieldName->registerSetterMethod('setSubFieldName', $returnValue);
		return $returnValue;
	}
	
	/**
	 * Constructs a FieldSelector given the field name and optional subfield name
	 * which value will be formatted and translated on evaluation time.
	 * An optional language code can be provided, else uses the current language of 
	 * the TranslationService 
	 * @param String $fieldName the field name
	 * @param String $subFieldName optional subfield name
	 * @param String $lang language code. Should be one of the valid installed language,
	 * see TranslationService->getInstalledLanguage()
	 * @return FuncExp returns a FuncExp instance wrapping the FieldSelector that will ask the translation.
	 */
	public function fs_t($fieldName, $subFieldName=null, $lang=null) {
		return $this->fx('formatValueFromFS', $this->fs($fieldName, $subFieldName), $lang);
	}
	
	/**
	 * Constructs a FieldSelector representing an element attribute
	 * @param String $attributeName the element attribute name as defined in Element->fillFromArray
	 * @return FieldSelector a instance of a FieldSelector
	 */
	public function fs_e($attributeName) {		
		$attrFxParam = ($attributeName instanceof FuncExpParameter);
		// if FieldSelector is well defined (no FuncExpParam), then caches it
		if(!$attrFxParam) {
			if(!isset($this->fsCache)) $this->fsCache = array();
			$key = "(__element($attributeName))";
			$returnValue = $this->fsCache[$key];
			if(!isset($returnValue) || $returnValue->getSubFieldName() != $attributeName) {
				$returnValue = FieldSelector::createElementAttributeSelector($attributeName);
				if(isset($returnValue)) $this->fsCache[$key] = $returnValue;
			}	
				
		}
		// else always creates a new instance
		else $returnValue = FieldSelector::createElementAttributeSelector($attributeName);
		if($attrFxParam) $attributeName->registerSetterMethod('setSubFieldName', $returnValue);					
		return $returnValue;
	}
	
	/**
	 * Creates a FieldSelectorList based on an array of FieldSelectors
	 * This function supports variable number of arguments, that means that
	 * instead of providing an array with the arguments, you can pass the arguments
	 * in a comma separated list as a normal function call.
	 * example: $funcExpBuilder->fsl(fs1, fs2, fs3) is equivalent
	 * to $funcExpBuilder->fsl(array(fs1, fs2, fs3))
	 * @param Array $fsArr an array of FieldSelector or one FieldSelector
	 */
	public function fsl($fsArr) {
		$nArgs = func_num_args();		
		if($nArgs > 1) {
			$returnValue = FieldSelectorListArrayImpl::createInstance(false, false);
			for($i = 0; $i < $nArgs; $i++) {
				$returnValue->addFieldSelectorInstance(func_get_arg($i));
			}
		}
		else if(isset($fsArr)) {
			$returnValue = FieldSelectorListArrayImpl::createInstance(false, false);
			if(!is_array($fsArr)) $returnValue->addFieldSelectorInstance($fsArr);
			else {
				foreach($fsArr as $fs) {
					$returnValue->addFieldSelectorInstance($fs);
				}
			}
		}
		else $returnValue = null;
		return $returnValue; 
	}
	
	/**
	 * Builds a FieldSelectorList based on a comma separated string
	 * Syntax is : 'field1.subfield1, field2.subfield2, field3'
	 */
	public function str2fsl($str) {		
		if(!empty($str)) {
			$returnValue = FieldSelectorListArrayImpl::createInstance(false, false);
			$fsArr = explode(',', $str);
			foreach($fsArr as $fs) {
				$fs = explode(".", trim($fs));
				$returnValue->addFieldSelector($fs[0], (empty($fs[1]) ? null : $fs[1]));
			}
		}
		else $returnValue = null;
		return $returnValue;
	}
	
	/**
	 * Converts a FieldSelectorList to its FuncExp equivalent
	 * @param FieldSelectorList $fsl
	 * @return FuncExp
	 */
	public function fsl2fx($fsl) {
		if(isset($fsl) && !$fsl->isEmpty()) {
			$fsArr = array();
			foreach($fsl->getListIterator() as $fs) {
				$subFieldName = $fs->getSubFieldName();
				if(isset($subFieldName)) $fsArr[] = fx('fs', $fs->getFieldName(), $subFieldName);
				else $fsArr[] = fx('fs', $fs->getFieldName());				
			}
			$returnValue = fx('fsl', $fsArr);
		}
		else $returnValue = null;
		return $returnValue;
	}
	
	// LinkSelector builder
	
	/**
	 * Creates a LinkSelector given the owner element id and the field name
	 * @param ConfigSelector|LogExp|String $rootConfigSelector optional parameter. If set, then defines the configuration of the root element.
	 * If LogExp then should be the group selection log exp used to define the configuration,
	 * if String then should be the wigiiNamespace name used to define the configuration.
	 * @return LinkSelector
	 */
	public function ls($ownerElementId, $fieldName, $rootConfigSelector=null) {
		if($rootConfigSelector instanceof LogExp) $rootConfigSelector = ConfigSelector::createInstanceForGroupConfig($rootConfigSelector);
		elseif(is_string($rootConfigSelector)) $rootConfigSelector = ConfigSelector::createInstanceForWigiiNamespaceConfig($rootConfigSelector);

		$returnValue = LinkSelector::createInstance($ownerElementId, $fieldName, $rootConfigSelector);
		if($ownerElementId instanceof FuncExpParameter) $ownerElementId->registerSetterMethod('setOwnerElementId', $returnValue);
		if($fieldName instanceof FuncExpParameter) $fieldName->registerSetterMethod('setFieldName', $returnValue);		
		if($rootConfigSelector instanceof FuncExpParameter) $rootConfigSelector->registerSetterMethod('setRootConfigSelector', $returnValue);
		return $returnValue;
	}
	
	// ConfigSelector builder
	
	/**
	 * Creates a ConfigSelector
	 * @param String|LogExp $wigiiNamespaceNameOrGroupExp Or a String representing a WigiiNamespace name 
	 * or a LogExp representing a Group selection log exp
	 * @param String $moduleName if WigiiNamespace name is defined, then a String representing a Module, else ignored.
	 * @return ConfigSelector
	 */
	public function cs($wigiiNamespaceNameOrGroupExp, $moduleName=null) {
		if($wigiiNamespaceNameOrGroupExp instanceof FuncExpParameter) {
			if(is_null($moduleName)) {
				$returnValue = ConfigSelector::createInstanceForGroupConfig($wigiiNamespaceNameOrGroupExp);				
			}
			else {
				$returnValue = ConfigSelector::createInstanceForWigiiNamespaceConfig($wigiiNamespaceNameOrGroupExp, $moduleName);				
			}
			$wigiiNamespaceNameOrGroupExp->registerSetterMethod('setWigiiNamespaceNameOrGroupExp', $returnValue);			
		}
		else {
			if($wigiiNamespaceNameOrGroupExp instanceof LogExp) {
				$returnValue = ConfigSelector::createInstanceForGroupConfig($wigiiNamespaceNameOrGroupExp);
			}
			else {
				$returnValue = ConfigSelector::createInstanceForWigiiNamespaceConfig($wigiiNamespaceNameOrGroupExp, $moduleName);
			}
		}
		if($moduleName instanceof FuncExpParameter) $moduleName->registerSetterMethod('setModuleName', $returnValue);
		return $returnValue;			
	}
	
	// CalculatedFieldSelector builder
	
	/**
	 * Creates a CalculatedFieldSelector
	 * @param String|FieldSelector $fieldName the fieldName or directly a FieldSelector instance.
	 * @param FuncExp $funcExp the associated FuncExp
	 * @param String $subFieldName optional string to be used as a sub field.
	 */
	public function cfs($fieldName, $funcExp, $subFieldName=null) {
		if($fieldName instanceof FieldSelector) $fs = $fieldName;
		else $fs = $this->fs($fieldName, $subFieldName);
		$returnValue = CalculatedFieldSelector::createInstance($fs, $funcExp);
		if($funcExp instanceof FuncExpParameter) $funcExp->registerSetterMethod('setFuncExp', $returnValue);
		return $returnValue;
	}
	
	/**
	 * Builds a CalculatedFieldSelectorMap based on an array of CalculatedFieldSelectors
	 * This function supports variable number of arguments, that means that
	 * instead of providing an array with the arguments, you can pass the arguments
	 * in a comma separated list as a normal function call.
	 * example: $funcExpBuilder->cfsMap(cfs1, cfs2, cfs3) is equivalent
	 * to $funcExpBuilder->cfsMap(array(cfs1, cfs2, cfs3))
	 * @param Array $cfsArr an array of CalculatedFieldSelectors or one CalculatedFieldSelector
	 */
	public function cfsMap($cfsArr) {
		$nArgs = func_num_args();		
		if($nArgs > 1) {
			$returnValue = CalculatedFieldSelectorMapArrayImpl::createInstance();
			for($i = 0; $i < $nArgs; $i++) {
				$returnValue->setCalculatedFieldSelector(func_get_arg($i));
			}
		}
		else if(isset($cfsArr)) {
			$returnValue = CalculatedFieldSelectorMapArrayImpl::createInstance();
			if(!is_array($cfsArr)) $returnValue->setCalculatedFieldSelector($cfsArr);
			else {
				foreach($cfsArr as $cfs) {
					$returnValue->setCalculatedFieldSelector($cfs);
				}
			}
		}
		else $returnValue = null;
		return $returnValue; 
	}
	
	// FieldSortingKey builder
	
	/**
	 * Creates a FieldSortingKey given the fieldName, optional subFieldName and direction (default to ascending)
	 */
	public function fsk($fieldName, $subFieldName = null, $ascending=true) {
		$returnValue = FieldSortingKey::createInstance($fieldName, $subFieldName, $ascending);
		if($fieldName instanceof FuncExpParameter) $fieldName->registerSetterMethod('setFieldName', $returnValue);
		if($subFieldName instanceof FuncExpParameter) $subFieldName->registerSetterMethod('setSubFieldName', $returnValue);
		if($ascending instanceof FuncExpParameter) $ascending->registerSetterMethod('setAscending', $returnValue);
		return $returnValue;
	}
	
	/**
	 * Builds a FieldSortingKeyList based on an array of FieldSortingKeys
	 * This function supports variable number of arguments, that means that
	 * instead of providing an array with the arguments, you can pass the arguments
	 * in a comma separated list as a normal function call.
	 * example: $funcExpBuilder->fskl(fsk1, fsk2, fsk3) is equivalent
	 * to $funcExpBuilder->fskl(array(fsk1, fsk2, fsk3))
	 * @param Array $fskArr an array of FieldSortingKey or one FieldSortingKey
	 */
	public function fskl($fskArr) {
		$nArgs = func_num_args();		
		if($nArgs > 1) {
			$returnValue = FieldSortingKeyListArrayImpl::createInstance();
			for($i = 0; $i < $nArgs; $i++) {
				$returnValue->addFieldSelectorInstance(func_get_arg($i));
			}
		}
		else if(isset($fskArr)) {
			$returnValue = FieldSortingKeyListArrayImpl::createInstance();
			if(!is_array($fskArr)) $returnValue->addFieldSortingKeyInstance($fskArr);
			else {
				foreach($fskArr as $fsk) {
					$returnValue->addFieldSortingKeyInstance($fsk);
				}
			}
		}
		else $returnValue = null;
		return $returnValue; 
	}
	
	/**
	 * Builds a FieldSortingKeyList based on a comma separated string
	 * Syntax is : 'field1.subfield1 ASC, field2.subfield2 DESC, field3 ASC'
	 */
	public function str2fskl($str) {
		if(!empty($str)) {
			$returnValue = FieldSortingKeyListArrayImpl::createInstance();
			$fskArr = explode(',', $str);
			foreach($fskArr as $fsk) {
				// extracts fieldName, subfieldName and direction
				$fskDetail = array();
				if(preg_match('/^(.*?)(\\.(.*))? ((?i)ASC|DESC)$/s',trim($fsk),$fskDetail) > 0) {
					$returnValue->addFieldSortingKey($fskDetail[1], (empty($fskDetail[3]) ? null : $fskDetail[3]), strtoupper($fskDetail[4])!="DESC");
				}
				else throw new StringTokenizerException("invalid FieldSelectorKey syntax '".$fsk."' correct syntax is field.subfield ASC|DESC", StringTokenizerException::SYNTAX_ERROR);
			}
		}
		else $returnValue = null;
		return $returnValue;
	}
	
	/**
	 * Converts a FieldSortingKeyList to its FuncExp equivalent
	 * @param FieldSortingKeyList $fskl
	 * @return FuncExp
	 */
	public function fskl2fx($fskl) {
		if(isset($fskl) && !$fskl->isEmpty()) {
			$fskArr = array();
			foreach($fskl->getListIterator() as $fsk) {
				$fskArr[] = fx('fsk', $fsk->getFieldName(), $fsk->getSubFieldName(), $fsk->isAscending());
			}
			$returnValue = fx('fskl', $fskArr);
		}
		else $returnValue = null;
		return $returnValue;
	}
	
	// LogExp builder
	
	/**
	 * Constructs a logical AND expression on the arguments
	 * This function supports variable number of arguments, that means that
	 * instead of providing an array with the arguments, you can pass the arguments
	 * in a comma separated list as a normal function call.
	 * example: $funcExpBuilder->lxAnd(arg1, arg2, arg3) is equivalent
	 * to $funcExpBuilder->lxAnd(array(arg1, arg2, arg3))
	 * @return LogExpAnd a LogExpAnd instance
	 */
	public function lxAnd($args) {
		$nArgs = func_num_args();		
		if($nArgs > 1) {
			$returnValue = LogExp::createAndExp();
			for($i = 0; $i < $nArgs; $i++) {
				$returnValue->addOperand(func_get_arg($i));
			}
		}
		else if(isset($args)) {			
			if(!is_array($args) || count($args) < 2) throw new FuncExpEvalException("For logical AND, the number of arguments should be at least 2", ServiceException::INVALID_ARGUMENT);
			else {
				$returnValue = LogExp::createAndExp();
				foreach($args as $op) {
					$returnValue->addOperand($op);
				}
			}
		}
		else $returnValue = null;
		return $returnValue; 
	}
	
	/**
	 * Constructs a logical OR expression on the arguments
	 * This function supports variable number of arguments, that means that
	 * instead of providing an array with the arguments, you can pass the arguments
	 * in a comma separated list as a normal function call.
	 * example: $funcExpBuilder->lxOr(arg1, arg2, arg3) is equivalent
	 * to $funcExpBuilder->lxOr(array(arg1, arg2, arg3))
	 * @return LogExpOr a LogExpOr instance
	 */
	public function lxOr($args) {
		$nArgs = func_num_args();		
		if($nArgs > 1) {
			$returnValue = LogExp::createOrExp();
			for($i = 0; $i < $nArgs; $i++) {
				$returnValue->addOperand(func_get_arg($i));
			}
		}
		else if(isset($args)) {			
			if(!is_array($args) || count($args) < 2) throw new FuncExpEvalException("For logical OR, the number of arguments should be at least 2", ServiceException::INVALID_ARGUMENT);
			else {
				$returnValue = LogExp::createOrExp();
				foreach($args as $op) {
					$returnValue->addOperand($op);
				}
			}
		}
		else $returnValue = null;
		return $returnValue; 
	}
	
	/**
	 * Constructs a logical NOT expression on the argument	
	 * @return LogExpNot a LogExpNot instance
	 */
	public function lxNot($arg) {
		$returnValue = LogExp::createNotExp($arg);
		if($arg instanceof FuncExpParameter) $arg->registerSetterMethod('setLogExp', $returnValue);
		return $returnValue;
	}
	
	/**
	 * Constructs a logical EQUAL expression on the two arguments
	 * @return LogExpBin a LogExpBin instance with operator "="
	 */
	public function lxEq($arg1, $arg2) {
		$returnValue = LogExp::createEqualExp($arg1, $arg2);
		if($arg1 instanceof FuncExpParameter) $arg1->registerSetterMethod('setObject', $returnValue);
		if($arg2 instanceof FuncExpParameter) $arg2->registerSetterMethod('setValue', $returnValue);
		return $returnValue;
	}
	
	/**
	 * Constructs a logical NOTEQUAL expression on the two arguments
	 * @return LogExpBin a LogExpBin instance with operator "!="
	 */
	public function lxNotEq($arg1, $arg2) {
		$returnValue = LogExp::createNotEqualExp($arg1, $arg2);
		if($arg1 instanceof FuncExpParameter) $arg1->registerSetterMethod('setObject', $returnValue);
		if($arg2 instanceof FuncExpParameter) $arg2->registerSetterMethod('setValue', $returnValue);
		return $returnValue;
	}
	
	/**
	 * Constructs a LogExp to check if a FieldSelector is null
	 * @return LogExp
	 */
	public function lxIsNull($arg) {
	    return $this->lxEq($arg,null);
	}
	/**
	 * Constructs a LogExp to check if a FieldSelector is not null
	 * @return LogExp
	 */
	public function lxIsNotNull($arg) {
	    return $this->lxNotEq($arg,null);
	}
	
	/**
	 * Constructs a logical SMALLER expression on the two arguments
	 * @return LogExpBin a LogExpBin instance with operator "<"
	 */
	public function lxSm($arg1, $arg2) {
		$returnValue = LogExp::createSmallerExp($arg1, $arg2);
		if($arg1 instanceof FuncExpParameter) $arg1->registerSetterMethod('setObject', $returnValue);
		if($arg2 instanceof FuncExpParameter) $arg2->registerSetterMethod('setValue', $returnValue);
		return $returnValue;
	}
	
	/**
	 * Constructs a logical SMALLER or EQUAL expression on the two arguments
	 * @return LogExpBin a LogExpBin instance with operator "<="
	 */
	public function lxSmEq($arg1, $arg2) {
		$returnValue = LogExp::createSmallerEqExp($arg1, $arg2);
		if($arg1 instanceof FuncExpParameter) $arg1->registerSetterMethod('setObject', $returnValue);
		if($arg2 instanceof FuncExpParameter) $arg2->registerSetterMethod('setValue', $returnValue);
		return $returnValue;
	}
	
	/**
	 * Constructs a logical GREATER expression on the two arguments
	 * @return LogExpBin a LogExpBin instance with operator ">"
	 */
	public function lxGr($arg1, $arg2) {
		$returnValue = LogExp::createGreaterExp($arg1, $arg2);
		if($arg1 instanceof FuncExpParameter) $arg1->registerSetterMethod('setObject', $returnValue);
		if($arg2 instanceof FuncExpParameter) $arg2->registerSetterMethod('setValue', $returnValue);
		return $returnValue;
	}
	
	/**
	 * Constructs a logical GREATER or EQUAL expression on the two arguments
	 * @return LogExpBin a LogExpBin instance with operator ">="
	 */
	public function lxGrEq($arg1, $arg2) {
		$returnValue = LogExp::createGreaterEqExp($arg1, $arg2);
		if($arg1 instanceof FuncExpParameter) $arg1->registerSetterMethod('setObject', $returnValue);
		if($arg2 instanceof FuncExpParameter) $arg2->registerSetterMethod('setValue', $returnValue);
		return $returnValue;
	}
	
	/**
	 * Constructs a logical LIKE expression on the two arguments
	 * @return LogExpBin a LogExpBin instance with operator "LIKE"
	 */
	public function lxLike($arg1, $arg2) {
		$returnValue = LogExp::createLikeExp($arg1, $arg2);
		if($arg1 instanceof FuncExpParameter) $arg1->registerSetterMethod('setObject', $returnValue);
		if($arg2 instanceof FuncExpParameter) $arg2->registerSetterMethod('setValue', $returnValue);
		return $returnValue;
	}
	
	/**
	 * Constructs a logical NOT LIKE expression on the two arguments
	 * @return LogExpBin a LogExpBin instance with operator "NOTLIKE"
	 */
	public function lxNotLike($arg1, $arg2) {
		$returnValue = LogExp::createNotLikeExp($arg1, $arg2);
		if($arg1 instanceof FuncExpParameter) $arg1->registerSetterMethod('setObject', $returnValue);
		if($arg2 instanceof FuncExpParameter) $arg2->registerSetterMethod('setValue', $returnValue);
		return $returnValue;
	}
	
	/**
	 * Constructs a logical IN expression on the two arguments
	 * @param Array $arg2 should be an array
	 * @return LogExpBin a LogExpBin instance with operator "IN"
	 */
	public function lxIn($arg1, $arg2) {
		$returnValue = LogExp::createInExp($arg1, $arg2);
		if($arg1 instanceof FuncExpParameter) $arg1->registerSetterMethod('setObject', $returnValue);
		if($arg2 instanceof FuncExpParameter) $arg2->registerSetterMethod('setValue', $returnValue);
		return $returnValue;
	}
	
	/**
	 * Constructs a logical NOT IN expression on the two arguments
	 * @param Array $arg2 should be an array
	 * @return LogExpBin a LogExpBin instance with operator "NOTIN"
	 */
	public function lxNotIn($arg1, $arg2) {
		$returnValue = LogExp::createNotInExp($arg1, $arg2);
		if($arg1 instanceof FuncExpParameter) $arg1->registerSetterMethod('setObject', $returnValue);
		if($arg2 instanceof FuncExpParameter) $arg2->registerSetterMethod('setValue', $returnValue);
		return $returnValue;
	}
	
	// GroupExp builder
	
	/**
	 * Constructs a logical in group expression given a group selection expression
	 * @param LogExp $lx a LogExp instance to select groups
	 * @return LogExpInGroup a LogExpInGroup instance without children
	 */
	public function lxInG($lx) {
		$returnValue = LogExp::createInGroupExp($lx, false);
		if($lx instanceof FuncExpParameter) $lx->registerSetterMethod('setGroupSelectionLogExp', $returnValue);
		return $returnValue;
	}
	
	/**
	 * Constructs a logical in group expression given a group selection expression
	 * Children groups are also selected.
	 * @param LogExp $lx a LogExp instance to select groups
	 * @return LogExpInGroup a LogExpInGroup instance with children
	 */
	public function lxInGR($lx) {
		$returnValue = LogExp::createInGroupExp($lx, true);
		if($lx instanceof FuncExpParameter) $lx->registerSetterMethod('setGroupSelectionLogExp', $returnValue);
		return $returnValue;
	}
	
	/**
	 * Constructs a logical not in group expression given a group selection expression
	 * @param LogExp $lx a LogExp instance to select groups
	 * @return LogExpInGroup a LogExpNotInGroup instance ignoring children
	 */
	public function lxNotInG($lx) {
		$returnValue = LogExp::createNotInGroupExp($lx, false);
		if($lx instanceof FuncExpParameter) $lx->registerSetterMethod('setGroupSelectionLogExp', $returnValue);
		return $returnValue;
	}
	
	/**
	 * Constructs a logical not in group expression given a group selection expression
	 * Children groups are also excluded.
	 * @param LogExp $lx a LogExp instance to select groups
	 * @return LogExpInGroup a LogExpNotInGroup instance excluding children
	 */
	public function lxNotInGR($lx) {
		$returnValue = LogExp::createNotInGroupExp($lx, true);
		if($lx instanceof FuncExpParameter) $lx->registerSetterMethod('setGroupSelectionLogExp', $returnValue);
		return $returnValue;
	}
	
	/**
	 * Builds a LogExp InGR which selects all useful groups in given namespace and module (includes root groups and excludes trashbin)
	 * @param String|WigiiNamespace $namespace specific namespace to search in.
	 * @param String|Module $module
	 * @return LogExpInGroup
	 */
	public function lxInAllGroups($namespace,$module) {
		if($module instanceof Module) $module = $module->getModuleName();
		if($namespace instanceof WigiiNamespace) $namespace = $namespace->getWigiiNamespaceName();
		return lxInGR($this->lxAllGroups($namespace, $module));
	}
	
	/**
	 * Builds a LogExp which selects all useful groups in given namespace and module (includes root groups and excludes trashbin)
	 * @param String|WigiiNamespace $namespace specific namespace to search in.
	 * @param String|Module $module
	 * @return LogExp
	 */
	public function lxAllGroups($namespace,$module) {
		if($module instanceof Module) $module = $module->getModuleName();
		if($namespace instanceof WigiiNamespace) $namespace = $namespace->getWigiiNamespaceName();
		return lxAnd(lxEq(fs('module'), $module), lxEq(fs('wigiiNamespace'), $namespace), lxOr(lxEq(fs('id_group_parent'),0),lxIsNull(fs('id_group_parent'))), lxAnd(lxNotLike(fs('groupname'), '%trashbin%'), lxNotLike(fs('groupname'), '%corbeille%')));
	}
	
	/**
	 * Constructs a logical expression to select a range of months given a length and a start date.
	 * The logical expression combines a Year FieldSelector and a Month FieldSelector in order to select the range correctly.
	 * @param FieldSelector $yearFs FieldSelector used to select the Year of the Element. Should point to a four digit Strings or Attributs field.
	 * @param FieldSelector $monthFs FieldSelector used to select the Month of the Element. Should point to a two digit Strings or Attributs field of the form '01','02',...'12'.
	 * @param Int $length the range length in months. For intance 6 for six months in future, -6 for six month in past.
	 * @param Int $startDate an optional timestamp from which to start calculating the month range. Defaults to now.
	 * @return LogExp the LogExp to select the month range based on the year and month field selectors
	 */
	public function lxMonthRange($yearFs,$monthFs,$length,$startDate=null) {
		$monthRange = $this->monthRange($length,$startDate);
		$returnValue=null;
		if(!empty($monthRange)) {
			$n=count($monthRange);
			if($n>1) $returnValue=LogExp::createOrExp();
			foreach($monthRange as $year=>$months) {
				$monthExp = lxAnd(lxEq($yearFs,$year), lxIn($monthFs,$months));
				if($n>1) $returnValue->addOperand($monthExp);
				else $returnValue=$monthExp;
			}
		}
		return $returnValue;
	}
	
	/**
	 * Parses a String in a LogExp	
	 */
	public function str2lx($str) {
		return TechnicalServiceProvider::getFieldSelectorLogExpParser()->createLogExpFromString($str);
	}
	
	/**
	 * Converts a String representation of a LogExp into a semantically equivalent string representation of a FuncExp
	 * This function can be used to store LogExp into FuncExp language format.
	 * Example: call strlx2strfx('module = "Sequences" && wigiiNamespace = "Geometry" && id_group_parent = NULL');
	 * will return 'lxAnd(lxEq(fs("module"), "Sequences"), lxEq(fs("wigiiNamespace"), "Geometry"), lxEq(fs("id_group_parent"), NULL))'
	 * @param String $str
	 * @return String
	 */
	public function strlx2strfx($str) {
		return TechnicalServiceProvider::getFieldSelectorFuncExpParser()->funcExpToString($this->strlx2fx($str));
	}
	
	// DataFlowActivitySelector builder
	
	/**
	 * Constructs a DataFlowActivitySelector given its class name and some configuration parameters
	 * This function supports variable number of arguments, that means that
	 * instead of providing an array with the parameters, you can pass the parameters
	 * in a comma separated list as a normal function call.
	 * example: $funcExpBuilder->dfas(className, param1, param1value, param2, param2value) is equivalent
	 * to $funcExpBuilder->dfas(className, array(param1 => param1value, param2 => param2value))
	 * @param String $className the class name of the DataFlowActivity that should be executed
	 * @param Array $params an array of configuration parameters (paramName => paramValue, ...)
	 * @return DataFlowActivitySelector a DataFlowActivitySelector instance
	 */
	public function dfas($className, $params=null) {
		$nArgs = func_num_args();
		$returnValue = DataFlowActivitySelector::createInstance($className);
		if($className instanceof FuncExpParameter) $className->registerSetterMethod('setClassName', $returnValue);	
		if($nArgs > 2) {
			$i = 1;
			while($i < $nArgs) {
				// get parameter name
				$paramName = func_get_arg($i);
				$i++;				
				// get parameter value
				if($i < $nArgs) $paramValue = func_get_arg($i);
				else $paramValue = null;
				$returnValue->setDataFlowActivityParameter($paramName, $paramValue);
				if($paramValue instanceof FuncExpParameter) $paramValue->registerIndexedSetterMethod('setDataFlowActivityParameter', $returnValue, $paramName);
				$i++;
			}			
		}
		else if(isset($params)) {
			if(!is_array($params)) throw new ServiceException("params should be an array of (parameterName => parameterValue)", ServiceException::INVALID_ARGUMENT);
			foreach($params as $paramName => $paramValue) {
				$returnValue->setDataFlowActivityParameter($paramName, $paramValue);
				if($paramValue instanceof FuncExpParameter) $paramValue->registerIndexedSetterMethod('setDataFlowActivityParameter', $returnValue, $paramName);
			}
		}
		return $returnValue; 
	}
	
	/**
	 * Constructs a DataFlowActivitySelectorList based on an array of DataFlowActivitySelectors
	 * This function supports variable number of arguments, that means that
	 * instead of providing an array with the data flow activities selectors, you can pass them
	 * in a comma separated list as a normal function call.
	 * example: $funcExpBuilder->dfasl(dfas1, dfas2, dfas3) is equivalent
	 * to $funcExpBuilder->dfasl(array(dfas1, dfas2, dfas3))
	 * @param Array $dfasArr an array of DataFlowActivitySelectors or one DataFlowActivitySelector
	 */
	public function dfasl($dfasArr) {
		$nArgs = func_num_args();		
		if($nArgs > 1) {
			$returnValue = DataFlowActivitySelectorListArrayImpl::createInstance();
			for($i = 0; $i < $nArgs; $i++) {
				$returnValue->addDataFlowActivitySelectorInstance(func_get_arg($i));
			}
		}
		else if(isset($dfasArr)) {
			$returnValue = DataFlowActivitySelectorListArrayImpl::createInstance();
			if(!is_array($dfasArr)) $returnValue->addDataFlowActivitySelectorInstance($dfasArr);
			else {
				foreach($dfasArr as $dfas) {
					$returnValue->addDataFlowActivitySelectorInstance($dfas);
				}
			}
		}
		else $returnValue = null;
		return $returnValue; 
	}
	
	// DataFlowSelector builder
	
	/**
	 * Creates a DataFlowSelector instance
	 * @param DataFlowDumpable|InputDataFlow|ObjectList $src the data flow source
	 * @param DataFlowActivitySelectorList $dfasl the DataFlowActivitySelectorList describing the data flow
	 */
	public function dfs($src, $dfasl) {
		$returnValue = DataFlowSelector::createInstance($src, $dfasl);
		if($src instanceof FuncExpParameter) $src->registerSetterMethod('setSource', $returnValue);
		if($dfasl instanceof FuncExpParameter) $dfasl->registerSetterMethod('setDataFlowActivitySelectorList', $returnValue);
		return $returnValue;
	}
	
	/**
	 * Constructs a DataFlowSelectorList based on an array of DataFlowSelectors
	 * This function supports variable number of arguments, that means that
	 * instead of providing an array with the data flow selectors, you can pass them
	 * in a comma separated list as a normal function call.
	 * example: $funcExpBuilder->dfsList(dfs1, dfs2, dfs3) is equivalent
	 * to $funcExpBuilder->dfsList(array(dfs1, dfs2, dfs3))
	 * @param Array $dfsArr an array of DataFlowSelectors or one DataFlowSelector
	 */
	public function dfsList($dfsArr) {
		$nArgs = func_num_args();		
		if($nArgs > 1) {
			$returnValue = DataFlowSelectorListArrayImpl::createInstance();
			for($i = 0; $i < $nArgs; $i++) {
				$returnValue->addDataFlowSelector(func_get_arg($i));
			}
		}
		else if(isset($dfsArr)) {
			$returnValue = DataFlowSelectorListArrayImpl::createInstance();
			if(!is_array($dfsArr)) $returnValue->addDataFlowSelector($dfsArr);
			else {
				foreach($dfsArr as $dfs) {
					$returnValue->addDataFlowSelector($dfs);
				}
			}
		}
		else $returnValue = null;
		return $returnValue; 
	}
	
	/**
	 * Constructs a DataFlowSelectorList based on an array of DataFlowSelectors indexed by keys.
	 * This function supports variable number of arguments, that means that
	 * instead of providing an array with the data flow selectors, you can pass them
	 * in a comma separated list as a normal function call.
	 * example: $funcExpBuilder->dfsMap(key1, dfs1, key2, dfs2, key3, dfs3) is equivalent
	 * to $funcExpBuilder->dfsList(array(key1 => dfs1, key2 => dfs2, key3 => dfs3))
	 * @param Array $dfsArr an array of DataFlowSelectors
	 */
	public function dfsMap($dfsArr) {
		$nArgs = func_num_args();
		if($nArgs > 1) {
			$returnValue = DataFlowSelectorListArrayImpl::createInstance();
			$i = 0;
			while($i < $nArgs) {
				$key = func_get_arg($i);
				$i++;
				if($i < $nArgs) $returnValue->addDataFlowSelector(func_get_arg($i), $key);
				$i++;
			}
		}
		else if(isset($dfsArr)) {
			$returnValue = DataFlowSelectorListArrayImpl::createInstance();
			if(!is_array($dfsArr)) $returnValue->addDataFlowSelector($dfsArr);
			else {
				foreach($dfsArr as $key => $dfs) {
					$returnValue->addDataFlowSelector($dfs, $key);
				}
			}
		}
		else $returnValue = null;
		return $returnValue;
	}
	
	// DataFlow connectors
	
	/**
	 * Connects an array as a dataflow source.
	 * @param Array $arr the array to use as the dataflow source. 
	 * This function also supports variable number of arguments, that means that instead of passing one array, 
	 * you can pass several arguments to the function, in a comma separated list.
	 * example: array2df(val1, val2, val3, ..., valn) is equivalent to array2df(array(val1, val2, val3, ..., valn))
	 * @return ArrayDataFlowConnector returns an ArrayDataFlowConnector instance that can be used as a DataFlow source.
	 */
	public function array2df($arr) {
		$nArgs = func_num_args();
		if($nArgs > 1) $_arr = func_get_args();
		else $_arr = $arr;
		$returnValue = ServiceProvider::getExclusiveAccessObject('ArrayDataFlowConnector');
		$returnValue->setArray($_arr);
		if($_arr instanceof FuncExpParameter) $_arr->registerSetterMethod('setArray', $returnValue);
		return $returnValue;	
	}
	
	/**
	 * Queries an existing table in the database and pushes the rows as StdClasses into a DataFlow.
	 * @param String $dbTableName the db table name from which to select the rows
	 * @param ListFilter $listFilter an optional list filter used to select the rows
	 * @param Array $sqlTypeMap an optional array mapping a column to an SQL type. Key is the column name, value is one of MySqlQueryBuilder::SQLTYPE_* or BIGINT, DOUBLE, DECIMAL, BOOLEAN, DATE, DATETIME, INT, LONGBLOB, BLOB, TEXT, TIME, VARCHAR
	 * By default, if column is not defined, then SQL type is mapped to VARCHAR.
	 * @return DbTableDataFlowConnector returns a DbTableDataFlowConnector instance that can be used as a DataFlow source.
	 */
	public function dbTable2df($dbTableName,$listFilter=null,$sqlTypeMap=null) {
	    $returnValue = ServiceProvider::getExclusiveAccessObject('DbTableDataFlowConnector');
	    $returnValue->setDbTableName($dbTableName);
	    if($dbTableName instanceof FuncExpParameter) $dbTableName->registerSetterMethod('setDbTableName', $returnValue);
	    $returnValue->setListFilter($listFilter);
	    if($listFilter instanceof FuncExpParameter) $listFilter->registerSetterMethod('setListFilter', $returnValue);
	    $returnValue->setSqlTypeMap($sqlTypeMap);
	    if($sqlTypeMap instanceof FuncExpParameter) $sqlTypeMap->registerSetterMethod('setSqlTypeMap', $returnValue);
	    return $returnValue;	    
	}
	
	/**
	 * Selects a dimension and pushes cfgAttribut StdClasses into a DataFlow
	 * cfgAttribut StdClasses are of the form {value, attributes, label}. See cfgAttribut FuncExp for more details.
	 * @param String|Int|LogExp $selector The dimension selector. Can be a group id, a group name or a group log exp.
	 * @param LogExp $attrLogExp An optional LogExp used to filter the list of attributes (for instance filtering some specific values, see module Dimensions for details about the available fields)
	 * @param Int $sortOrder One of 0 = no sorting, keep dimension element id ordering, 1 = ascending by value, 2 = descending by value, 3 = ascending by label, 4 = descending by label. (by default is ascending by label)
	 * @return DimensionDataFlowConnector returns a DimensionDataFlowConnector instance that can be used as a DataFlow source.
	 */
	public function dimension2df($selector, $attrLogExp = null, $sortOrder = 3) {
	    $returnValue = ServiceProvider::getExclusiveAccessObject('DimensionDataFlowConnector');
	    $returnValue->setSelector($selector);
	    if($selector instanceof FuncExpParameter) $selector->registerSetterMethod('setSelector', $returnValue);
	    $returnValue->setAttrLogExp($attrLogExp);
	    if($attrLogExp instanceof FuncExpParameter) $attrLogExp->registerSetterMethod('setAttrLogExp', $returnValue);
	    $returnValue->setSortOrder($sortOrder);
	    if($sortOrder instanceof FuncExpParameter) $sortOrder->registerSetterMethod('setSortOrder', $returnValue);
	    return $returnValue;
	}
	
	/**
	 * A connector which dumps the content of an Element field of type Files into a data flow.
	 * The content is pushed chunk by chunk. Default chunk size is 512ko.
	 * See class ElementFileDataFlowConnector for more details.
	 * @param Element $element element from which to dump the file content
	 * @param String|FieldSelector $fieldName the name of the Field of type Files from which to dump the content
	 * @param int $chunkSize optional chunk size
	 * @return ElementFileDataFlowConnector
	 */
	public function elementFile2df($element, $fieldName, $chunkSize=null) {
	    return ElementFileDataFlowConnector::createInstance($element, $fieldName, $chunkSize);
	}
	
	/**
	 * Dumps the fields of a Wigii xml configuration file as cfgField stdClass instances into a DataFlow
	 * cfgField StdClasses are of the form {name, attributes, label, cfgAttributs}. See cfgField FuncExp for more details.
	 * @param String|ElementFileDataFlowConnector $xmlFile The name of an existing Wigii configuration file to load or an already open connector to an xml file attached to current element
	 * @param ListFilter $listFilter An optional ListFilter instance to filter the fields to extract based on some attribute values
	 * @return ModuleXmlDataFlowConnector returns a ModuleXmlDataFlowConnector instance that can be used as a DataFlow source.
	 */
	public function moduleXml2df($xmlFile, $listFilter=null) {
	    $returnValue = ServiceProvider::getExclusiveAccessObject('ModuleXmlDataFlowConnector');
	    $returnValue->setXmlFile($xmlFile);
	    if($xmlFile instanceof FuncExpParameter) $xmlFile->registerSetterMethod('setXmlFile', $returnValue);
	    if(isset($listFilter)) {
	        $returnValue->setListFilter($listFilter);
	        if($listFilter instanceof FuncExpParameter) $listFilter->registerSetterMethod('setListFilter', $returnValue);
	    }
	    return $returnValue;
	}
	
	// ListFilter builder
	
	/**
	 * Constructs a ListFilter
	 * @param FieldSelectorList $fieldSelectorList optional field selector list
	 * @param LogExp $fieldSelectorLogExp an optional log exp to filter the list
	 * @param FieldSortingKeyList $fieldSortingKeyList an optional field sorting key list
	 * @param int $pageNumber an optional page number to be retrieved
	 * @param int $pageSize the optional page limit	 
	 */
	public function lf($fieldSelectorList=null, $fieldSelectorLogExp=null, $fieldSortingKeyList=null, $pageNumber=null, $pageSize=null) {
		$returnValue = ListFilter::createInstance();
		if(isset($fieldSelectorList)) {
			$returnValue->setFieldSelectorList($fieldSelectorList);
			if($fieldSelectorList instanceof FuncExpParameter) $fieldSelectorList->registerSetterMethod('setFieldSelectorList', $returnValue);
		}
		if(isset($fieldSelectorLogExp)) {
			$returnValue->setFieldSelectorLogExp($fieldSelectorLogExp);
			if($fieldSelectorLogExp instanceof FuncExpParameter) $fieldSelectorLogExp->registerSetterMethod('setFieldSelectorLogExp', $returnValue);
		}
		if(isset($fieldSortingKeyList)) {
			$returnValue->setFieldSortingKeyList($fieldSortingKeyList);
			if($fieldSortingKeyList instanceof FuncExpParameter) $fieldSortingKeyList->registerSetterMethod('setFieldSortingKeyList', $returnValue);
		}
		if(isset($pageNumber)) {
			$returnValue->setDesiredPageNumber($pageNumber);
			if($pageNumber instanceof FuncExpParameter) $pageNumber->registerSetterMethod('setDesiredPageNumber', $returnValue);
		}
		if(isset($pageSize)) {
			$returnValue->setPageSize($pageSize);
			if($pageSize instanceof FuncExpParameter) $pageSize->registerSetterMethod('setPageSize', $returnValue);
		}
		return $returnValue;
	}
	
	// ElementPList builder
	
	/**
	 * Creates an ElementPList based on selected elements or subitems using the ElementService
	 * and which can be dumped into a data flow
	 * @param LogExp|LinkSelector $inGroupLogExpOrLinkSelector the in group log exp used to select the elements or
	 * the LinkSelector describing the sub elements or linked elements to be selected.
	 * @param ListFilter $listFilter an optional list filter to filter the selected elements
	 * @return ElementPListDataFlowConnector an instance of an ElementPListDataFlowConnector which
	 * is a fusion between an ElementPList and a DataFlowDumpable object.
	 */
	public function elementPList($inGroupLogExpOrLinkSelector, $listFilter=null) {
		$returnValue = ServiceProvider::getExclusiveAccessObject('ElementPListDataFlowConnector');
		if($inGroupLogExpOrLinkSelector instanceof LogExp) $returnValue->setInGroupLogExp($inGroupLogExpOrLinkSelector);
		else $returnValue->setLinkSelector($inGroupLogExpOrLinkSelector);
		$returnValue->setListFilter($listFilter);
		$returnValue->setElementService($this->getElementService());
		if($inGroupLogExpOrLinkSelector instanceof FuncExpParameter) $inGroupLogExpOrLinkSelector->registerSetterMethod('setInGroupLogExpOrLinkSelector', $returnValue);
		if($listFilter instanceof FuncExpParameter) $listFilter->registerSetterMethod('setListFilter', $returnValue);
		return $returnValue;
	}
	
	// ElementP builder
	
	/**
	 * Fetches an existing element or sub element in the database and dumps it into a Data flow
	 * @param int|LogExp $elementId the element id used to fetch the element or a LogExp to search the element based on a business key
	 * @param FieldSelectorList $fieldSelectorList an optional FieldSelectorList to filter the fields that are fetched.
	 * @param ConfigSelector|LogExp|String $configSelector optional parameter. If set, then defines the configuration of the element or the root element in case of subitems.
	 * If LogExp then should be the group selection log exp used to define the configuration,
	 * if String then should be the wigiiNamespace name used to define the configuration.
	 * @return ElementPDataFlowConnector
	 */
	public function elementP($elementId, $fieldSelectorList=null, $configSelector=null) {
		if($configSelector instanceof LogExp) $configSelector = ConfigSelector::createInstanceForGroupConfig($configSelector);
		elseif(is_string($configSelector)) $configSelector = ConfigSelector::createInstanceForWigiiNamespaceConfig($configSelector);
		
		// CWE 05.07.2018 supports fetching an element based on a business key
		if($elementId instanceof LogExp) {
			// if no ConfigSelector, creates one on current WigiiNamespace and Module
			if(!isset($configSelector)) $configSelector = ConfigSelector::createInstanceForCurrentWigiiNamespace();
			//fetches element based on LogExp in ConfigSelector space
			$returnValue = $this->elementPList((is_null($configSelector->getGroupLogExp())?
					$this->lxInAllGroups($configSelector->getWigiiNamespaceName(), $configSelector->getModuleName()):
					$this->lxInGR($configSelector->getGroupLogExp())
				),$this->lf($fieldSelectorList,$elementId,null,1,1));
		}
		// fetches element based on ID
		else {	
			$returnValue = ServiceProvider::getExclusiveAccessObject('ElementPDataFlowConnector');
			$returnValue->setElementId($elementId);
			$returnValue->setFieldSelectorList($fieldSelectorList);
			$returnValue->setConfigSelector($configSelector);
			if($elementId instanceof FuncExpParameter) $elementId->registerSetterMethod('setElementId', $returnValue);
			if($fieldSelectorList instanceof FuncExpParameter) $fieldSelectorList->registerSetterMethod('setFieldSelectorList', $returnValue);
			if($configSelector instanceof FuncExpParameter) $configSelector->registerSetterMethod('setConfigSelector', $returnValue);
		}
		return $returnValue;
	}
	
	// Element builder
	
	/**
	 * Creates a new Element which can be dumped into a DataFlow
	 * @param int $groupId the group id in which the Element should be inserted
	 * @param LinkSelector $linkSelector an optional LinkSelector. If set, then the created element is linked to
	 * the owner element described in the LinkSelector.
	 * @return ElementDataFlowConnector
	 */
	public function newElement($groupId, $linkSelector=null) {
		$returnValue = ServiceProvider::getExclusiveAccessObject('ElementDataFlowConnector');		
		$returnValue->setGroupId($groupId);
		$returnValue->setLinkSelector($linkSelector);
		if($groupId instanceof FuncExpParameter) $groupId->registerSetterMethod('setGroupId', $returnValue);
		if($linkSelector instanceof FuncExpParameter) $linkSelector->registerSetterMethod('setLinkSelector', $returnValue);
		return $returnValue;
	}
	
	/**
	 * Creates a new sub Element which can be dumped into a DataFlow
	 * @param int $masterElementId the master element id to which this sub element belongs
	 * @param String $fieldName the field name defining the link with this sub element
	 * @param ConfigSelector|LogExp|String $rootConfigSelector the configuration selector of the master element defined as a 
	 * ConfigSelector object or a GroupLogExp or a String which is the WigiiNamespace.
	 * @return ElementDataFlowConnector
	 */
	public function newSubElement($masterElementId, $fieldName, $rootConfigSelector=null) {
		$returnValue = ServiceProvider::getExclusiveAccessObject('ElementDataFlowConnector');				
		$returnValue->setLinkSelector($this->ls($masterElementId, $fieldName, $rootConfigSelector));		
		return $returnValue;
	}
	
	// GroupList builder
	
	/**
	 * Creates an GroupList based on selected groups (without detail) using the GroupAdminService
	 * and which can be dumped into a data flow
	 * @param LogExp $groupLogExp the log exp used to select the groups.
	 * @param bool $asStdClass if true, then StdClass instances with the attributes {id, groupname, wigiiNamespace, module, id_group_parent}
	 * are pushed in the dataflow, else Group instances (without detail) are pushed. 
	 * @return GroupListDataFlowConnector an instance of a GroupListDataFlowConnector which
	 * is a fusion between an GroupList and a DataFlowDumpable object.
	 */
	public function groupList($groupLogExp, $asStdClass=false) {
		$returnValue = ServiceProvider::getExclusiveAccessObject('GroupListDataFlowConnector');
		$returnValue->setGroupLogExp($groupLogExp);
		$returnValue->setAsStdClass($asStdClass);
		if($groupLogExp instanceof FuncExpParameter) $groupLogExp->registerSetterMethod('setGroupLogExp', $returnValue);
		if($asStdClass instanceof FuncExpParameter) $asStdClass->registerSetterMethod('setAsStdClass', $returnValue);
		return $returnValue;
	}
	
	// Configuration builder
	
	/**
	 * Creates an StdClass instance which maps a Field in the Wigii XML configuration file.
	 * @param String $name the name of the field
	 * @param Array $attributes an array [key => value] which defines some xml attributes.
	 * The type of the field should defined in this array if needed. For example array('type' => 'Attributs').
	 * @param String|Array $label the label of the field, or an array with the label translated in different languages.
	 * The array is indexed with the Wigii installed languages (see TranslationService). For instance [l01 => label in English, l02 => label in French]
	 * @param StdClass|Array $cfgAttributs an optional array of cfgAttribut StdClasses defining the list of attributes or one StdClass cfgAttribut
	 * @return StdClass returns an StdClass instance with the properties {name, attributes, label, cfgAttributs}
	 */
	public function cfgField($name, $attributes=null, $label=null, $cfgAttributs=null) {
		return (object)array('name' => $name, 'attributes'=> $attributes, 'label'=> $label, 'cfgAttributs' => $cfgAttributs);
	}
	
	/**
	 * Creates an StdClass instance which maps an 'attribute' node in the Wigii XML configuration file.
	 * @param Scalar $value the value of the attribute
	 * @param Array $attributes an array [key => value] which defines some xml attributes
	 * @param String|Array $label the label of the attribute, or an array with the label translated in different languages.
	 * The array is indexed with the Wigii installed languages (see TranslationService). For instance [l01 => label in English, l02 => label in French]
	 * @return StdClass returns an StdClass instance with the properties {value, attributes, label}
	 */
	public function cfgAttribut($value, $attributes=null, $label=null) {
		return (object)array('value' => $value, 'attributes' => $attributes, 'label' => $label);
	}
	
	/**
	 * Creates an StdClass instance which maps an 'attributeExp' node in the Wigii XML configuration file.
	 * @param String $funcExp the attribute func exp as a string.
	 * @return StdClass returns an StdClass instance with the property {funcExp} 
	 */
	public function cfgAttributeExp($funcExp) {
		return (object)array('funcExp' => $funcExp);
	}
	
	// Wigii BPL Builder
	
	/**
	 * Creates a WigiiBPLParameter based on a list of pairs (key, value) or other WigiiBPLParameter instances.
	 * @param $args a list of arguments of the form wigiiBPLParam(k1,v1,k2,v2,p1,k3,v3,p2,p3,...) where
	 * - ki,vi: pairs of (key, value) where key ki evaluates to a string and value to any value used as a parameter,
	 * - pi: if pi evaluates to a WigiiBPLParameter instance, then adds its content
	 * @return WigiiBPLParameter
	 */
	public function wigiiBPLParam($args=null) {
		$nArgs = func_num_args();
		if($nArgs>1) {
			$_args = func_get_args();			
		}
		elseif($nArgs==1) {
			if(isset($args)) {
				if(!is_array($args)) throw new FuncExpEvalException('args should be an array or a list of parameters', FuncExpEvalException::INVALID_ARGUMENT);
				$_args = $args;
				$nArgs=count($_args);
			} else $nArgs=0;
		}
		$returnValue = TechnicalServiceProvider::createWigiiBPLParameterInstance();		
		if($nArgs > 0) {
			$i = 0;
			while($i < $nArgs) {
				$k = $_args[$i];
				// if we have an instance of a WigiiBPLParameter, then adds it to existing one
				if($k instanceof WigiiBPLParameter) {
					$returnValue->addWigiiBPLParameter($k);
				}
				// else evaluates value and sets (key, value) pair
				else {
					$i++;
					if($i < $nArgs) {
						$v = $_args[$i];
						$returnValue->setValue($k, $v);
					}
					else {
						$returnValue->setValue($k, null);
					}
				}
				$i++;
			}
		}
		return $returnValue;
	}
	
	/**
	 * Converts a WigiiBPLParameter instance to its FuncExp equivalent
	 * @param WigiiBPLParameter $param
	 * @return FuncExp
	 */
	public function wigiiBPLParam2fx($param) {
		$returnValue = fx('wigiiBPLParam');
		if(isset($param)) {
			foreach($param->getIterator() as $k => $v) {
				$returnValue->addArgument($this->object2fx($k));
				$returnValue->addArgument($this->object2fx($v));
			}
		} 
		else throw new FuncExpEvalException('param cannot be null', FuncExpEvalException::INVALID_ARGUMENT);
		return $returnValue;
	}
	
	// Selectors
	
	/**
	 * Selects some objects and dumps them into a data flow
	 * @param Principal $principal the principal executing the pocess
	 * @param DataFlowDumpable|InputDataFlow|ObjectList $dataFlowDumpable the objects to be selected and pushed into the data flow
	 * @param DataFlowActivitySelectorList $dataFlowActivitySelectorList the data flow description
	 * @param FuncExpParameterMapping $fxpMapping an optional FuncExpParameterMapping instance used to instanciate any parameters 
	 * that would be present in the arguments with a given value.
	 * @return Any optionally returns some data if the last stage of the data flows writes some output. 
	 */
	public function sel($principal, $dataFlowDumpable, $dataFlowActivitySelectorList, $fxpMapping=null) {
		$returnValue = $this->doSel($principal, $dataFlowDumpable, $dataFlowActivitySelectorList, $fxpMapping);
		// frees memory of data source
		if(method_exists($dataFlowDumpable, 'freeMemory')) $dataFlowDumpable->freeMemory();
		return $returnValue;		
	}
	
	/**
	 * Executes a sel dataflow several times, using a list of FuncExp parameters
	 * @param Principal $principal the principal executing the pocess
	 * @param DataFlowDumpable|InputDataFlow|ObjectList $dataFlowDumpable the objects to be selected and pushed into the data flow
	 * @param DataFlowActivitySelectorList $dataFlowActivitySelectorList the data flow description
	 * @param FuncExpParameterMappingList $fxpMappingList the list of FuncExpParameterMapping against which the data flow will be executed.
	 * @param DataFlowResultList $dataFlowResultList an optional list to store the data flows result. 
	 * @return DataFlowResultList optionally fills a provided DataFlowResultList with the result of the executed data flows. 
	 */
	public function multiSel($principal, $dataFlowDumpable, $dataFlowActivitySelectorList, $fxpMappingList, $dataFlowResultList=null) {
		// if we have a list of mappings, then executes the data flow for each parameter mapping
		if(isset($fxpMappingList)) {
			foreach($fxpMappingList->getListIterator() as $resKey => $fxpMapping) {
				$returnValue = $this->doSel($principal, $dataFlowDumpable, $dataFlowActivitySelectorList, $fxpMapping);
				if(isset($returnValue) && isset($dataFlowResultList)) $dataFlowResultList->addDataFlowResult($returnValue, $resKey);
			}
		}
		// executes only once the data flow
		else {
			$returnValue = $this->doSel($principal, $dataFlowDumpable, $dataFlowActivitySelectorList);
			if(isset($returnValue) && isset($dataFlowResultList)) $dataFlowResultList->addDataFlowResult($returnValue);
		}
		// frees memory of data source
		if(method_exists($dataFlowDumpable, 'freeMemory')) $dataFlowDumpable->freeMemory();
		return $dataFlowResultList;
	}	
	
	/**
	 * Selects some objects and dumps them into a data flow
	 * @param Principal $principal the principal executing the pocess
	 * @param DataFlowDumpable|InputDataFlow|ObjectList $dataFlowDumpable the objects to be selected and pushed into the data flow
	 * @param DataFlowActivitySelectorList $dataFlowActivitySelectorList the data flow description
	 * @param FuncExpParameterMapping $fxpMapping an optional FuncExpParameterMapping instance used to instanciate any parameters 
	 * that would be present in the arguments with a given value.
	 * @return Any optionally returns some data if the last stage of the data flows writes some output. 
	 */
	protected function doSel($principal, $dataFlowDumpable, $dataFlowActivitySelectorList, $fxpMapping=null) {
		// instantiates the parameters if any mapping is given
		if(isset($fxpMapping)) $fxpMapping->instantiateParameters();
		// sets adaptative WigiiNamespace on the Principal
		$currentNamespace = $principal->getWigiiNamespace();
		$hasAdaptiveWigiiNamespace = $principal->hasAdaptiveWigiiNamespace();
		$principal->setAdaptiveWigiiNamespace(true);	
		try {	
			// executes the data flow and returns the result
			if($dataFlowDumpable instanceof DataFlowDumpable) $returnValue = $this->getDataFlowService()->processDumpableObject($principal, $dataFlowDumpable, $dataFlowActivitySelectorList, false);
			elseif($dataFlowDumpable instanceof InputDataFlow) $returnValue =  $this->getDataFlowService()->processInputDataFlow($principal, $dataFlowDumpable, $dataFlowActivitySelectorList, false);
			else $returnValue =  $this->getDataFlowService()->processObjectList($principal, $dataFlowDumpable, $dataFlowActivitySelectorList);
		}
		catch(Exception $e) {
			// sets back current WigiiNamespace
			if(!$hasAdaptiveWigiiNamespace) $principal->setAdaptiveWigiiNamespace(false);
			$principal->bindToWigiiNamespace($currentNamespace);
			throw $e;
		}
		// sets back current WigiiNamespace
		if(!$hasAdaptiveWigiiNamespace) $principal->setAdaptiveWigiiNamespace(false);
		$principal->bindToWigiiNamespace($currentNamespace);
		return $returnValue;		
	}
	
	/**
	 * Matches an input array with a pattern and binds any given ValueObject on both sides.
	 * @param Array $input input array
	 * @param Array $pattern pattern
	 * This function supports variable number of arguments, that means that
	 * instead of providing an array with the arguments, you can pass the arguments
	 * in a comma separated list as a normal function call.
	 * example: $funcExpBuilder->arrayMatch(array("item","12345","edit"), "item", $x, "edit") is equivalent
	 * to $funcExpBuilder->arrayMatch(array("item","12345","edit"), array("item", $x, "edit"))
	 * @example $x = ValueObject::createInstance(); $y = ValueObject::createInstance();
	 * $funcExpBuilder->arrayMatch(array("item","12345",$y), "item", $x, "edit") will return true 
	 * and $x->getValue() will return "12345" and $y->getValue() will return "edit".
	 * - If one array is longer than the other and that the smallest array has a ValueObject as last item, 
	 * then it will has its value bound to the tail of the longest array.
	 *  $funcExpBuilder->arrayMatch(array("item","12345","edit"), "item", $x) will return true and $x->getValue() will returny array("12345","edit");
	 *  - If two variables are on both sides, then the non null value will be bound to the variable with a null value, if both are non null, then matching is true if values are equal. 
	 * @return Boolean true if input array matched pattern
	 */
	public function arrayMatch($input,$pattern) {
		// aligns left and right arguments
		$nArgs = func_num_args();
		if($nArgs>2) {
			$pattern = array_slice(func_get_args(),1);
		}
		else {
			if(empty($pattern)) $pattern = array();
			elseif(!is_array($pattern)) $pattern = array($pattern);
		}
		if(empty($input)) $input = array();
		elseif(!is_array($input)) $input = array();
		
		// pattern match the two arrays
		$i=0;
		$n = count($input);
		$m = count($pattern);		
		$k=$n;if($m<$k)$k=$m;
		$touchedVars = array();
		while($i<$k) {
			// variable detection
			$leftVar = ($input[$i] instanceof ValueObject);
			$rightVar = ($pattern[$i] instanceof ValueObject);
			$leftVal = ($leftVar?$input[$i]->getValue():$input[$i]);
			$rightVal = ($rightVar?$pattern[$i]->getValue():$pattern[$i]);
			$leftVar = $leftVar && ($leftVal===null);
			$rightVar = $rightVar && ($rightVal===null);			
			// tail detection
			if($i==$k-1 && $n!=$m) {
				// left variable takes right tail
				if($leftVar && $n<$m && $input[$i]->supportsArray()) $input[$i]->setValue(array_slice($pattern,$i));
				// right variable takes left tail
				elseif($rightVar && $n>$m && $pattern[$i]->supportsArray()) $pattern[$i]->setValue(array_slice($input,$i));	
				// else no tail grabbing then does not match
				else {
					// resets any touched variables
					foreach($touchedVars as $var) {
						$var->reset();
					}
					return false;			
				}
			}
			// variable instantiation and value matching
			elseif($leftVar && !$rightVar) {
				$input[$i]->setValue($rightVal);
				$touchedVars[] = $input[$i];
			}
			elseif(!$leftVar && $rightVar) {
				$pattern[$i]->setValue($leftVal);
				$touchedVars[] = $pattern[$i];
			}
			elseif(!$leftVar && !$rightVar && $leftVal!=$rightVal) {
				// resets any touched variables
				foreach($touchedVars as $var) {
					$var->reset();
				}
				return false;
			}
			$i++;; 
		}
		return true;		
	}
	
	/**
	 * Returns the value of an attribute of an object.
	 * If attribute is not given, returns the value of the 'value' attribute.
	 * If object is an array, returns the array value stored under the given attribute taken as a key.
	 * Else returns the object as is.
	 * @param stdClass|Array $obj the object from which to extract an attribute
	 * @param string $attrName object attribute or 'value' if not defined
	 * @return Any or null if attribute is not defined.
	 */
	public function oVal($obj,$attrName=null) {
		if($attrName===null) $attrName='value';
		if($obj instanceof stdClass) return $obj->{$attrName};
		elseif(is_array($obj)) return $obj[$attrName];
		else return $obj;
	}
	
	// Dates and Time
	
	/**
	 * Builds an array representing a range of months, given the number of months and a start date.
	 * @param Int $length the range length in months. For intance 6 for six months in future, -6 for six month in past.
	 * @param Int $startDate an optional timestamp from which to start calculating the month range. Defaults to now.
	 * @return Array an array of years where each year contains an array of months. 
	 * Year is a four digit string, month is a two digit string '01','02',...,'12'.
	 * @example monthRange(-6, strtotime('2016-02-19')) returns array('2016'=>array('02'=>'02','01'=>'01'),'2015'=>array('12'=>'12','11'=>'11','10'=>'10','09'=>'09'))
	 * monthRange(6, strtotime('2016-02-19')) returns array('2016'=>array('02'=>'02','03'=>'03','04'=>'04','05'=>'05','06'=>'06','07'=>'07'))
	 */
	public function monthRange($length,$startDate=null) {
		$returnValue=array();
		if(is_null($startDate)) $startDate=time();
		if($length<=-1) {
			$length=-$length;
			$shift='-1 month';
		}
		elseif($length>=1) {
			$shift='+1 month';
		}
		else {
			$length=1;
			$shift=null;
		}
		$year=date('Y',$startDate);
		$month=date('m',$startDate);
		if(!isset($returnValue[$year])) $returnValue[$year]=array();
		$returnValue[$year][$month]=$month;
		// shifts down or up
		for($i=1;$i<$length;$i++) {
			$startDate=strtotime($shift,$startDate);
			$year=date('Y',$startDate);
			$month=date('m',$startDate);
			if(!isset($returnValue[$year])) $returnValue[$year]=array();
			$returnValue[$year][$month]=$month;
		}
		return $returnValue;
	}
}