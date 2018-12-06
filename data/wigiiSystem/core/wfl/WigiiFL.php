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
 * Wigii Objects created or fetched by FuncExp
 * Created by CWE on 5 decembre 2013
 * Modified by Medair on 04.11.2016 to integrate QlikSense and Box functions
 * Modified by Medair (CWE,LMA) on 08.12.2016 to hide password argument in login function and to protect against Cross Site Scripting
 * Modified by Medair (CWE) on 25.04.2017 to keep current namespace on re-login and ensure role list is synced with Web UI. 
 */
class WigiiFL extends FuncExpVMAbstractFL implements RootPrincipalFL
{
	// Dependency injection

	private $_debugLogger;
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("WigiiFL");
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

	private $translationService;
	/**
	 * Injects a TranslationService to be used by this library
	 * @param TranslationService $translationService
	 */
	public function setTranslationService($translationService){
		$this->translationService = $translationService;
	}
	/**
	 * Gets the injected TranslationService
	 * @return TranslationService
	 */
	protected function getTranslationService(){
		//autowired
		if(!isset($this->translationService)){
			$this->translationService = ServiceProvider::getTranslationService();
		}
		return $this->translationService;
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
	
	private $wigiiBPL;
	public function setWigiiBPL($wigiiBPL)
	{
		$this->wigiiBPL = $wigiiBPL;
	}
	protected function getWigiiBPL()
	{
		// autowired
		if(!isset($this->wigiiBPL))
		{
			$this->wigiiBPL = ServiceProvider::getWigiiBPL();
		}
		return $this->wigiiBPL;
	}

	private $rootPrincipal;
	public function setRootPrincipal($rootP) {
		$this->rootPrincipal = $rootP;
	}
	protected function getRootPrincipal() {
		return $this->rootPrincipal;
	}

	private $authS;
	public function setAuthenticationService($authenticationService)
	{
		$this->authS = $authenticationService;
	}
	protected function getAuthenticationService()
	{
		// autowired
		if(!isset($this->authS))
		{
			$this->authS = ServiceProvider::getAuthenticationService();
		}
		return $this->authS;
	}
	
	private $authoS;
	public function setAuthorizationService($authorizationService)
	{
	    $this->authoS = $authorizationService;
	}
	protected function getAuthorizationService()
	{
	    // autowired
	    if(!isset($this->authoS))
	    {
	        $this->authoS = ServiceProvider::getAuthorizationService();
	    }
	    return $this->authoS;
	}
	
	private $gAS;
	public function setGroupAdminService($groupAdminService)
	{
		$this->gAS = $groupAdminService;
	}
	protected function getGroupAdminService()
	{
		// autowired
		if(!isset($this->gAS))
		{
			$this->gAS = ServiceProvider::getGroupAdminService();
		}
		return $this->gAS;
	}
	
	private $userAS;
	public function setUserAdminService($userAdminService)
	{
		$this->userAS = $userAdminService;
	}
	protected function getUserAdminService()
	{
		// autowired
		if(!isset($this->userAS))
		{
			$this->userAS = ServiceProvider::getUserAdminService();
		}
		return $this->userAS;
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
	
	private $sessionAS;
	public function setSessionAdminService($sessionAdminService){
		$this->sessionAS = $sessionAdminService;
	}
	protected function getSessionAdminService(){
		// autowired
		if(!isset($this->sessionAS)){
			$this->sessionAS = ServiceProvider::getSessionAdminService();
		}
		return $this->sessionAS;
	}
	
	private $configS;
	public function setConfigService($configService)
	{
		$this->configS = $configService;
	}
	protected function getConfigService()
	{
		// autowired
		if(!isset($this->configS))
		{
			$this->configS = ServiceProvider::getConfigService();
		}
		return $this->configS;
	}
	
	private $qlikSenseFE;
	public function setQlikSenseFormExecutor($qlikSenseFormExecutor)
	{
		$this->qlikSenseFE = $qlikSenseFormExecutor;
	}
	protected function getQlikSenseFormExecutor()
	{
		// autowired
		if(!isset($this->qlikSenseFE))
		{
			$this->qlikSenseFE = TechnicalServiceProvider::getQlikSenseFormExecutor();
		}
		return $this->qlikSenseFE;
	}
	
	// FieldSelector builder

	/**
	 * Constructs a FieldSelector given the field name and optional subfield name
	 * See method 'fs' in FuncExpBuilder class.
	 * FuncExp signature : <code>fs(fieldName, subFieldName=null)</code><br/>
	 * Where arguments are :
	 * - Arg(0) fieldName: String. The field name
	 * - Arg(1) subFieldName: String. An optional subfield name
	 * @return FieldSelector a instance of a FieldSelector
	 */
	public function fs($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException('The fs function takes at least one argument which is the fieldName', FuncExpEvalException::INVALID_ARGUMENT);
		return $this->getFuncExpBuilder()->fs($this->evaluateArg($args[0]),
			($nArgs > 1 ? $this->evaluateArg($args[1]) : null));
	}

	/**
	 * Constructs a FieldSelector given the field name and optional subfield name
	 * which value will be formatted and translated on evaluation time.
	 * An optional language code can be provided, else uses the current language of
	 * the TranslationService
	 * See method 'fs_t' in FuncExpBuilder class.
	 * FuncExp signature : <code>fs_t(fieldName, subFieldName=null, lang=null)</code><br/>
	 * Where arguments are :
	 * - Arg(0) fieldName: String. The field name
	 * - Arg(1) subFieldName: String. An optional subfield name
	 * - Arg(2) lang: Language code. Should be one of the valid installed language,
	 * see TranslationService->getInstalledLanguage()
	 * @return FuncExp returns a FuncExp instance wrapping the FieldSelector that will ask the translation.
	 */
	public function fs_t($args) {
		$isOriginPublic = $this->isFxOriginPublic();
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException('The fs_t function takes at least one argument which is the fieldName', FuncExpEvalException::INVALID_ARGUMENT);
		$returnValue = $this->getFuncExpBuilder()->fs_t($this->evaluateArg($args[0]),
			($nArgs > 1 ? $this->evaluateArg($args[1]) : null),
			($nArgs > 2 ? $this->evaluateArg($args[2]) : null));
		if($isOriginPublic) $returnValue->setOriginIsPublic();
		return $returnValue;
	}

	/**
	 * Constructs a FieldSelector representing an element attribute
	 * See method 'fs_e' in FuncExpBuilder class.
	 * FuncExp signature : <code>fs_e(attributeName)</code><br/>
	 * Where arguments are :
	 * - Arg(0) attributeName: String. The element attribute name as defined in Element->fillFromArray
	 * @return FieldSelector a instance of a FieldSelector
	 */
	public function fs_e($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException('The fs_e function takes one argument', FuncExpEvalException::INVALID_ARGUMENT);
		return $this->getFuncExpBuilder()->fs_e($this->evaluateArg($args[0]));
	}
	
	/**
	 * return the value after evaluation of a field selector
	 * FuncExp signature : <code>fsVal(fieldSelector)</code><br/>
	 * Where arguments are :
	 * - Arg(0) fieldSelector: The field existing in the Element
	 * @return mixed, the value of the field
	 */
	public function fsVal($args) {
		if($args[0] instanceof FieldSelector){
			return $this->evaluateArg($args[0]);
		} else {
			//evaluate the expression as a fieldName and returns the field value
			return $this->evaluateArg(fs($this->evaluateArg($args[0])));
		}
	}
	
	/**
	 * Creates a FieldSelectorList based on an array of FieldSelectors
	 * See method 'fsl' in FuncExpBuilder class.
	 * FuncExp signature : <code>fsl(fs1, fs2, ...)</code><br/>
	 * Where arguments are :
	 * - Arg(0..n) fsI: evaluates to a FieldSelector instance
	 * @return FieldSelectorList a FieldSelectorList instance
	 */
	public function fsl($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs > 0) {
			for($i = 0; $i < $nArgs; $i++) {			    
				if(!($args[$i] instanceof FieldSelector)) $args[$i] = $this->evaluateArg($args[$i]);
			}
		}
		else $args = array();
		return $this->getFuncExpBuilder()->fsl($args);
	}

	/**
	 * Builds a FieldSelectorList based on a comma separated string
	 * Syntax is : 'field1.subfield1, field2.subfield2, field3'
	 * See method 'str2fsl' in FuncExpBuilder class.
	 * FuncExp signature : <code>str2fsl(str)</code><br/>
	 * @return FieldSelectorList a FieldSelectorList instance
	 */
	public function str2fsl($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException('The str2fsl function takes at least one argument which is a string', FuncExpEvalException::INVALID_ARGUMENT);
		return $this->getFuncExpBuilder()->str2fsl($this->evaluateArg($args[0]));
	}

	// LinkSelector builder

	/**
	 * Creates a LinkSelector given the owner element id and the field name
	 * See method 'ls' in FuncExpBuilder class.
	 * FuncExp signature : <code>ls(elementOwnerId, fieldName, rootConfigSelector=null)</code><br/>
	 * Where arguments are :
	 * - Arg(0) elementOwnerId: evaluates to a scalar which is an element id
	 * - Arg(1) fieldName: evaluates to string which is a field name
	 * - Arg(2) rootConfigSelector: optional. Evaluates to a ConfigSelector which defines the configuration of the root element
	 * @return LinkSelector a LinkSelector instance
	 */
	public function ls($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 2) throw new FuncExpEvalException('The ls function takes at least two arguments: an elementOwnerId and a fieldName', FuncExpEvalException::INVALID_ARGUMENT);
		return $this->getFuncExpBuilder()->ls($this->evaluateArg($args[0]),
			$this->evaluateArg($args[1]),
			($nArgs > 2 ? $this->evaluateArg($args[2]) : null));
	}

	// ConfigSelector builder

	/**
	 * Creates a ConfigSelector
	 * See method 'cs' in FuncExpBuilder class.
	 * FuncExp signature : <code>cs(wigiiNamespaceNameOrGroupExp, moduleName=null)</code><br/>
	 * Where arguments are :
	 * - Arg(0) wigiiNamespaceNameOrGroupExp: evaluates to a String representing a WigiiNamespace name
	 * or evaluates to a LogExp representing a Group selection log exp
	 * - Arg(1) fieldName: if WigiiNamespace name is defined, then a evaluates to a String representing a Module, else ignored.
	 * @return ConfigSelector a ConfigSelector instance
	 */
	public function cs($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException('The cs function takes or one argument: a group log exp, or two arguments: a wigiiNamespace name and a module name', FuncExpEvalException::INVALID_ARGUMENT);
		return $this->getFuncExpBuilder()->cs($this->evaluateArg($args[0]),
			($nArgs > 1 ? $this->evaluateArg($args[1]) : null));
	}

	/**
	 * Creates a ConfigSelector returning the closest group configuration of the given group in the hierarchy of groups.
	 * If no group configuration exists, then returns a ConfigSelector pointing on the given group.
	 * FuncExp signature : <code>cs_g(groupId)</code><br/>
	 * Where arguments are :
	 * - Arg(0) groupId: the group ID from which to fetch a configuration group
	 * @return ConfigSelector a ConfigSelector instance
	 */
	public function cs_g($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException('The cs_g function takes one argument the group ID', FuncExpEvalException::INVALID_ARGUMENT);
		$groupId = $this->evaluateArg($args[0]);
		return ServiceProvider::getWigiiBPL()->buildConfigSelectorForGroup($this->getPrincipal(), $groupId);
	}
	
	// CalculatedFieldSelector builder

	/**
	 * Creates a CalculatedFieldSelector given the fieldName, funcExp and optional subFieldName
	 * See method 'cfs' in FuncExpBuilder class.
	 * FuncExp signature : <code>cfs(fieldName, funcExp, subFieldName = null)</code><br/>
	 * Where arguments are :
	 * - Arg(0) fieldName: evaluates to string which is a field name
	 * - Arg(1) funcExp: a FuncExp that should be used as a calculated expression
	 * - Arg(2) subFieldName: optional. Evaluates to string which is the subfield name
	 * @return CalculatedFieldSelector a CalculatedFieldSelector instance
	 */
	public function cfs($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 2) throw new FuncExpEvalException('The cfs function takes at least two argument which is the field name and the FuncExp', FuncExpEvalException::INVALID_ARGUMENT);
		$funcExp = $args[1];
		if(!($funcExp instanceof FuncExp || $funcExp instanceof FieldSelector)) $funcExp = $this->evaluateArg($funcExp);
		return $this->getFuncExpBuilder()->cfs($this->evaluateArg($args[0]),
			$funcExp,
			($nArgs > 2 ? $this->evaluateArg($args[2]) : null));
	}

	/**
	 * Builds a CalculatedFieldSelectorMap based on an array of CalculatedFieldSelectors
	 * See method 'cfsMap' in FuncExpBuilder class.
	 * FuncExp signature : <code>cfsMap(cfs1, cfs2, ...)</code><br/>
	 * Where arguments are :
	 * - Arg(0..n) cfsI: evaluates to a CalculatedFieldSelector instance
	 * @return CalculatedFieldSelectorMap a CalculatedFieldSelectorMap instance
	 */
	public function cfsMap($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs > 0) {
			for($i = 0; $i < $nArgs; $i++) {
				$args[$i] = $this->evaluateArg($args[$i]);
			}
		}
		else $args = array();
		return $this->getFuncExpBuilder()->cfsMap($args);
	}

	// FieldSortingKey builder

	/**
	 * Creates a FieldSortingKey given the fieldName, optional subFieldName and direction (default to ascending)
	 * See method 'fsk' in FuncExpBuilder class.
	 * FuncExp signature : <code>fsk(fieldName, subFieldName = null, ascending=true)</code><br/>
	 * Where arguments are :
	 * - Arg(0) fieldName: evaluates to string which is a field name
	 * - Arg(1) subFieldName: optional. Evaluates to string which is the subfield name
	 * - Arg(2) ascending: optional. Evaluates to a boolean. If true (by default), then ascending, else descending.
	 * @return FieldSortingKey a FieldSortingKey instance
	 */
	public function fsk($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException('The fsk function takes at least one argument which is the field name', FuncExpEvalException::INVALID_ARGUMENT);
		return $this->getFuncExpBuilder()->fsk($this->evaluateArg($args[0]),
			($nArgs > 1 ? $this->evaluateArg($args[1]) : null),
			($nArgs > 2 ? $this->evaluateArg($args[2]) : true));
	}

	/**
	 * Builds a FieldSortingKeyList based on an array of FieldSortingKeys
	 * See method 'fskl' in FuncExpBuilder class.
	 * FuncExp signature : <code>fskl(fsk1, fsk2, ...)</code><br/>
	 * Where arguments are :
	 * - Arg(0..n) fskI: evaluates to a FieldSortingKey instance
	 * @return FieldSortingKeyList a FieldSortingKeyList instance
	 */
	public function fskl($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs > 0) {
			for($i = 0; $i < $nArgs; $i++) {
				$args[$i] = $this->evaluateArg($args[$i]);
			}
		}
		else $args = array();
		return $this->getFuncExpBuilder()->fskl($args);
	}

	/**
	 * Builds a FieldSortingKeyList based on a comma separated string
	 * Syntax is : 'field1.subfield1 ASC, field2.subfield2 DESC, field3 ASC'
	 * See method 'str2fskl' in FuncExpBuilder class.
	 * FuncExp signature : <code>str2fskl(str)</code><br/>
	 * @return FieldSortingKeyList a FieldSortingKeyList instance
	 */
	public function str2fskl($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException('The str2fskl function takes at least one argument which is a string', FuncExpEvalException::INVALID_ARGUMENT);
		return $this->getFuncExpBuilder()->str2fskl($this->evaluateArg($args[0]));
	}

	// LogExp builder

	/**
	 * Constructs a logical AND expression on the arguments
	 * See method 'lxAnd' in FuncExpBuilder class.
	 * FuncExp signature : <code>lxAnd(arg1, arg2, ...)</code><br/>
	 * Where arguments are :
	 * - Arg(0..n) argI: evaluates to a LogExp instance
	 * @return LogExpAnd a LogExpAnd instance
	 */
	public function lxAnd($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 2) throw new FuncExpEvalException('The lxAnd function takes at least two argument which evaluates to LogExp', FuncExpEvalException::INVALID_ARGUMENT);
		for($i = 0; $i < $nArgs; $i++) {
			$args[$i] = $this->evaluateArg($args[$i]);
		}
		return $this->getFuncExpBuilder()->lxAnd($args);
	}

	/**
	 * Constructs a logical OR expression on the arguments
	 * See method 'lxOr' in FuncExpBuilder class.
	 * FuncExp signature : <code>lxOr(arg1, arg2, ...)</code><br/>
	 * Where arguments are :
	 * - Arg(0..n) argI: evaluates to a LogExp instance
	 * @return LogExpOr a LogExpOr instance
	 */
	public function lxOr($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 2) throw new FuncExpEvalException('The lxOr function takes at least two argument which evaluates to LogExp', FuncExpEvalException::INVALID_ARGUMENT);
		for($i = 0; $i < $nArgs; $i++) {
			$args[$i] = $this->evaluateArg($args[$i]);
		}
		return $this->getFuncExpBuilder()->lxOr($args);
	}

	/**
	 * Constructs a logical NOT expression on the argument
	 * See method 'lxNot' in FuncExpBuilder class.
	 * FuncExp signature : <code>lxNot(arg)</code><br/>
	 * @return LogExpNot a LogExpNot instance
	 */
	public function lxNot($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException('The lxNot function takes one argument', FuncExpEvalException::INVALID_ARGUMENT);
		return $this->getFuncExpBuilder()->lxNot($this->evaluateArg($args[0]));
	}

	/**
	 * Constructs a logical EQUAL expression on the two arguments
	 * See method 'lxEq' in FuncExpBuilder class.
	 * FuncExp signature : <code>lxEq(arg1, arg2)</code><br/>
	 * @return LogExpBin a LogExpBin instance with operator "="
	 */
	public function lxEq($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 2) throw new FuncExpEvalException('The lxEq function takes two arguments', FuncExpEvalException::INVALID_ARGUMENT);
		return $this->getFuncExpBuilder()->lxEq($this->evaluateArg($args[0]), $this->evaluateArg($args[1]));
	}

	/**
	 * Constructs a logical NOTEQUAL expression on the two arguments
	 * See method 'lxNotEq' in FuncExpBuilder class.
	 * FuncExp signature : <code>lxNotEq(arg1, arg2)</code><br/>
	 * @return LogExpBin a LogExpBin instance with operator "!="
	 */
	public function lxNotEq($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 2) throw new FuncExpEvalException('The lxNotEq function takes two arguments', FuncExpEvalException::INVALID_ARGUMENT);
		return $this->getFuncExpBuilder()->lxNotEq($this->evaluateArg($args[0]), $this->evaluateArg($args[1]));
	}

	/**
	 * Constructs a LogExp to check if a FieldSelector is null
	 *@return LogExp
	 */
	public function lxIsNull($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException('The lxIsNull function takes one argument which is the FieldSelector to check for nullity', FuncExpEvalException::INVALID_ARGUMENT);
		return $this->getFuncExpBuilder()->lxEq($this->evaluateArg($args[0]),null);
	}
	/**
	 * Constructs a LogExp to check if a FieldSelector is not null
	 *@return LogExp
	 */
	public function lxIsNotNull($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException('The lxIsNotNull function takes one argument which is the FieldSelector to check for not nullity', FuncExpEvalException::INVALID_ARGUMENT);
		return $this->getFuncExpBuilder()->lxNotEq($this->evaluateArg($args[0]),null);
	}
	/**
	 * Constructs a LogExp to check that a FieldSelector is not null and not equal to a given value
	 */
	public function lxNotNullAndNotEq($args) {
		return $this->getFuncExpBuilder()->lxAnd($this->lxIsNotNull($args),$this->lxNotEq($args));
	}
	/**
	 * Constructs a LogExp to check that a FieldSelector is null or equal to a given value
	 */
	public function lxNullOrEq($args) {
		return $this->getFuncExpBuilder()->lxOr($this->lxIsNull($args),$this->lxEq($args));
	}
	
	/**
	 * Constructs a logical SMALLER expression on the two arguments
	 * See method 'lxSm' in FuncExpBuilder class.
	 * FuncExp signature : <code>lxSm(arg1, arg2)</code><br/>
	 * @return LogExpBin a LogExpBin instance with operator "<"
	 */
	public function lxSm($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 2) throw new FuncExpEvalException('The lxSm function takes two arguments', FuncExpEvalException::INVALID_ARGUMENT);
		return $this->getFuncExpBuilder()->lxSm($this->evaluateArg($args[0]), $this->evaluateArg($args[1]));
	}

	/**
	 * Constructs a logical SMALLER or EQUAL expression on the two arguments
	 * See method 'lxSmEq' in FuncExpBuilder class.
	 * FuncExp signature : <code>lxSmEq(arg1, arg2)</code><br/>
	 * @return LogExpBin a LogExpBin instance with operator "<="
	 */
	public function lxSmEq($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 2) throw new FuncExpEvalException('The lxSmEq function takes two arguments', FuncExpEvalException::INVALID_ARGUMENT);
		return $this->getFuncExpBuilder()->lxSmEq($this->evaluateArg($args[0]), $this->evaluateArg($args[1]));
	}

	/**
	 * Constructs a logical GREATER expression on the two arguments
	 * See method 'lxGr' in FuncExpBuilder class.
	 * FuncExp signature : <code>lxGr(arg1, arg2)</code><br/>
	 * @return LogExpBin a LogExpBin instance with operator ">"
	 */
	public function lxGr($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 2) throw new FuncExpEvalException('The lxGr function takes two arguments', FuncExpEvalException::INVALID_ARGUMENT);
		return $this->getFuncExpBuilder()->lxGr($this->evaluateArg($args[0]), $this->evaluateArg($args[1]));
	}

	/**
	 * Constructs a logical GREATER or EQUAL expression on the two arguments
	 * See method 'lxGrEq' in FuncExpBuilder class.
	 * FuncExp signature : <code>lxGrEq(arg1, arg2)</code><br/>
	 * @return LogExpBin a LogExpBin instance with operator ">="
	 */
	public function lxGrEq($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 2) throw new FuncExpEvalException('The lxGrEq function takes two arguments', FuncExpEvalException::INVALID_ARGUMENT);
		return $this->getFuncExpBuilder()->lxGrEq($this->evaluateArg($args[0]), $this->evaluateArg($args[1]));
	}

	/**
	 * Constructs a logical LIKE expression on the two arguments
	 * See method 'lxLike' in FuncExpBuilder class.
	 * FuncExp signature : <code>lxLike(arg1, arg2)</code><br/>
	 * @return LogExpBin a LogExpBin instance with operator "LIKE"
	 */
	public function lxLike($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 2) throw new FuncExpEvalException('The lxLike function takes two arguments', FuncExpEvalException::INVALID_ARGUMENT);
		return $this->getFuncExpBuilder()->lxLike($this->evaluateArg($args[0]), $this->evaluateArg($args[1]));
	}

	/**
	 * Constructs a logical NOT LIKE expression on the two arguments
	 * See method 'lxNotLike' in FuncExpBuilder class.
	 * FuncExp signature : <code>lxNotLike(arg1, arg2)</code><br/>
	 * @return LogExpBin a LogExpBin instance with operator "NOTLIKE"
	 */
	public function lxNotLike($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 2) throw new FuncExpEvalException('The lxNotLike function takes two arguments', FuncExpEvalException::INVALID_ARGUMENT);
		return $this->getFuncExpBuilder()->lxNotLike($this->evaluateArg($args[0]), $this->evaluateArg($args[1]));
	}

	/**
	 * Constructs a logical IN expression on the two arguments
	 * See method 'lxIn' in FuncExpBuilder class.
	 * FuncExp signature : <code>lxIn(arg1, arg2)</code><br/>
	 * Where Arg(1) arg2 : should evaluate to an array
	 * @return LogExpBin a LogExpBin instance with operator "IN"
	 */
	public function lxIn($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 2) throw new FuncExpEvalException('The lxIn function takes two arguments', FuncExpEvalException::INVALID_ARGUMENT);
		return $this->getFuncExpBuilder()->lxIn($this->evaluateArg($args[0]), $this->evaluateArg($args[1]));
	}

	/**
	 * Constructs a logical NOT IN expression on the two arguments
	 * See method 'lxNotIn' in FuncExpBuilder class.
	 * FuncExp signature : <code>lxNotIn(arg1, arg2)</code><br/>
	 * Where Arg(1) arg2 : should evaluate to an array
	 * @return LogExpBin a LogExpBin instance with operator "NOTIN"
	 */
	public function lxNotIn($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 2) throw new FuncExpEvalException('The lxNotIn function takes two arguments', FuncExpEvalException::INVALID_ARGUMENT);
		return $this->getFuncExpBuilder()->lxNotIn($this->evaluateArg($args[0]), $this->evaluateArg($args[1]));
	}

	// GroupExp builder

	/**
	 * Constructs a logical in group expression given a group selection expression
	 * See method 'lxInG' in FuncExpBuilder class.
	 * FuncExp signature : <code>lxInG(lx)</code><br/>
	 * Where Arg(1) lx : should evaluate to a LogExp used to select groups
	 * @return LogExpInGroup a LogExpInGroup instance without children
	 */
	public function lxInG($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException('The lxInG function takes one argument', FuncExpEvalException::INVALID_ARGUMENT);
		return $this->getFuncExpBuilder()->lxInG($this->evaluateArg($args[0]));
	}

	/**
	 * Constructs a logical in group expression given a group selection expression
	 * Children groups are also selected.
	 * See method 'lxInGR' in FuncExpBuilder class.
	 * FuncExp signature : <code>lxInGR(lx)</code><br/>
	 * Where Arg(1) lx : should evaluate to a LogExp used to select groups
	 * @return LogExpInGroup a LogExpInGroup instance with children
	 */
	public function lxInGR($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException('The lxInG function takes one argument', FuncExpEvalException::INVALID_ARGUMENT);
		return $this->getFuncExpBuilder()->lxInGR($this->evaluateArg($args[0]));
	}

	/**
	 * Constructs a logical not in group expression given a group selection expression
	 * See method 'lxNotInG' in FuncExpBuilder class.
	 * FuncExp signature : <code>lxNotInG(lx)</code><br/>
	 * Where Arg(1) lx : should evaluate to a LogExp used to select groups
	 * @return LogExpInGroup a LogExpNotInGroup instance ignoring children
	 */
	public function lxNotInG($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException('The lxNotInG function takes one argument', FuncExpEvalException::INVALID_ARGUMENT);
		return $this->getFuncExpBuilder()->lxNotInG($this->evaluateArg($args[0]));
	}

	/**
	 * Constructs a logical not in group expression given a group selection expression
	 * Children groups are also excluded.
	 * See method 'lxNotInGR' in FuncExpBuilder class.
	 * FuncExp signature : <code>lxNotInGR(lx)</code><br/>
	 * Where Arg(1) lx : should evaluate to a LogExp used to select groups
	 * @return LogExpInGroup a LogExpNotInGroup instance excluding children
	 */
	public function lxNotInGR($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException('The lxNotInGR function takes one argument', FuncExpEvalException::INVALID_ARGUMENT);
		return $this->getFuncExpBuilder()->lxNotInGR($this->evaluateArg($args[0]));
	}
		
	/**
	 * Builds a LogExp InGR which selects all useful groups in given namespace and module (includes root groups and excludes trashbin)
	 * See method 'lxInAllGroups' in FuncExpBuilder class.
	 * FuncExp signature : <code>lxInAllGroups(namespace,module)</code><br/>
	 * Where arguments are :
	 * - Arg(0) namespace: String|WigiiNamespace. specific namespace to search in.
	 * - Arg(1) module: String|Module
	 * @return LogExpInGroup
	 */
	public function lxInAllGroups($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 2) throw new FuncExpEvalException('The lxInAllGroups function takes two arguments: the namespace and module in which to search', FuncExpEvalException::INVALID_ARGUMENT);
		return $this->getFuncExpBuilder()->lxInAllGroups($this->evaluateArg($args[0]),$this->evaluateArg($args[1]));
	}
	
	/**
	 * Builds a LogExp which selects all useful groups in given namespace and module (includes root groups and excludes trashbin)
	 * See method 'lxAllGroups' in FuncExpBuilder class.
	 * FuncExp signature : <code>lxAllGroups(namespace,module)</code><br/>
	 * Where arguments are :
	 * - Arg(0) namespace: String|WigiiNamespace. specific namespace to search in.
	 * - Arg(1) module: String|Module
	 * @return LogExp
	 */
	public function lxAllGroups($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 2) throw new FuncExpEvalException('The lxAllGroups function takes two arguments: the namespace and module in which to search', FuncExpEvalException::INVALID_ARGUMENT);
		return $this->getFuncExpBuilder()->lxAllGroups($this->evaluateArg($args[0]),$this->evaluateArg($args[1]));
	}

	/**
	 * Constructs a logical expression to select a range of months given a length and a start date.
	 * The logical expression combines a Year FieldSelector and a Month FieldSelector in order to select the range correctly.
	 * See method 'lxMonthRange' in FuncExpBuilder class.
	 * FuncExp signature : <code>lxMonthRange(yearFs,monthFs,length,startDate=null)</code><br/>
	 * Where arguments are :
	 * - Arg(0) yearFs: FieldSelector. FieldSelector used to select the Year of the Element. Should point to a four digit Strings or Attributs field.
	 * - Arg(1) monthFs: FieldSelector. FieldSelector used to select the Month of the Element. Should point to a two digit Strings or Attributs field of the form '01','02',...'12'.
	 * - Arg(2) length: Int. The range length in months. For intance 6 for six months in future, -6 for six month in past.
	 * - Arg(3) startDate: Int. An optional timestamp from which to start calculating the month range. Defaults to now.
	 * @return LogExp the LogExp to select the month range based on the year and month field selectors
	 */
	public function lxMonthRange($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 3) throw new FuncExpEvalException('The lxMonthRange function takes at least 3 arguments: yearFs, monthFs and length', FuncExpEvalException::INVALID_ARGUMENT);
		if($args[0] instanceof FieldSelector) $yearFs=$args[0];
		else $yearFs=$this->evaluateArg($args[0]);		
		if($args[1] instanceof FieldSelector) $monthFs=$args[1];
		else $monthFs=$this->evaluateArg($args[1]);
		
		return $this->getFuncExpBuilder()->lxMonthRange($yearFs, $monthFs, 
			$this->evaluateArg($args[2]),
			($nArgs>3 ? $this->evaluateArg($args[3]):null));
	}
	
	/**
	 * Parses a String in a LogExp
	 * See method 'str2lx' in FuncExpBuilder class.
	 * FuncExp signature : <code>str2lx(str)</code><br/>
	 * @return LogExp
	 */
	public function str2lx($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException('The str2lx function takes one argument', FuncExpEvalException::INVALID_ARGUMENT);
		return $this->getFuncExpBuilder()->str2lx($this->evaluateArg($args[0]));
	}

	/**
	 * Converts a LogExp given into its string form to a semantically equivalent FuncExp
	 * See method 'strlx2fx' in FuncExpBuilder class.
	 * FuncExp signature : <code>strlx2fx(str)</code><br/>
	 * @return FuncExp
	 */
	public function strlx2fx($args) {
		$isOriginPublic = $this->isFxOriginPublic();
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException('The strlx2fx function takes one argument', FuncExpEvalException::INVALID_ARGUMENT);
		$returnValue = $this->getFuncExpBuilder()->strlx2fx($this->evaluateArg($args[0]));
		if($isOriginPublic) $returnValue->setOriginIsPublic();
		return $returnValue;
	}

	/**
	 * Converts a String representation of a LogExp into a semantically equivalent string representation of a FuncExp
	 * This function can be used to store LogExp into FuncExp language format.
	 * Example: call strlx2strfx('module = "Sequences" && wigiiNamespace = "Geometry" && id_group_parent = NULL');
	 * will return 'lxAnd(lxEq(fs("module"), "Sequences"), lxEq(fs("wigiiNamespace"), "Geometry"), lxEq(fs("id_group_parent"), NULL))'
	 * See method 'strlx2strfx' in FuncExpBuilder class.
	 * FuncExp signature : <code>strlx2strfx(str)</code><br/>
	 * @return String
	 */
	public function strlx2strfx($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException('The strlx2strfx function takes one argument', FuncExpEvalException::INVALID_ARGUMENT);
		return $this->getFuncExpBuilder()->strlx2strfx($this->evaluateArg($args[0]));
	}

	// ListFilter builder

	/**
	 * Constructs a ListFilter
	 * See method 'lf' in FuncExpBuilder class.<br/>
	 * FuncExp signature : <code>lf(fieldSelectorList=null, fieldSelectorLogExp=null, fieldSortingKeyList=null, pageNumber=null, pageSize=null)</code><br/>
	 * Where arguments are :
	 * - Arg(0) fieldSelectorList: FieldSelectorList. Optional field selector list
	 * - Arg(1) fieldSelectorLogExp: LogExp. An optional log exp to filter the list
	 * - Arg(2) fieldSortingKeyList: FieldSortingKeyList. An optional field sorting key list
	 * - Arg(3) pageNumber: int. An optional page number to be retrieved
	 * - Arg(4) pageSize: int. The optional page limit
	 * @return ListFilter. An instance of a ListFilter.
	 */
	public function lf($args) {
		$nArgs = $this->getNumberOfArgs($args);
		return $this->getFuncExpBuilder()->lf(
			($nArgs > 0 ? $this->evaluateArg($args[0]) : null),
			($nArgs > 1 ? $this->evaluateArg($args[1]) : null),
			($nArgs > 2 ? $this->evaluateArg($args[2]) : null),
			($nArgs > 3 ? $this->evaluateArg($args[3]) : null),
		    ($nArgs > 4 ? $this->evaluateArg($args[4]) : null)
		);
	}

	// ElementPList builder

	/**
	 * Creates an ElementPList based on selected elements using the ElementService
	 * and which can be dumped into a data flow
	 * See method 'elementPList' in FuncExpBuilder class.<br/>
	 * FuncExp signature : <code>elementPList(inGroupLogExp|linkSelector, listFilter=null)</code><br/>
	 * Where arguments are :
	 * - Arg(0) inGroupLogExp|linkSelector: LogExp|LinkSelector. The in group log exp used to select the elements or
	 * a link selector used to select sub elements or linked elements.
	 * - Arg(1) listFilter: ListFilter. An optional list filter to filter the selected elements
	 * @return ElementPListDataFlowConnector an instance of an ElementPListDataFlowConnector which
	 * is a fusion between an ElementPList and a DataFlowDumpable object.
	 */
	public function elementPList($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException('The elementPList function takes at least one argument which is the inGroupLogExp', FuncExpEvalException::INVALID_ARGUMENT);
		return $this->getFuncExpBuilder()->elementPList($this->evaluateArg($args[0]),
			($nArgs > 1 ? $this->evaluateArg($args[1]) : null));
	}

	// ElementP builder

	/**
	 * Fetches an existing element or sub element in the database and dumps it into a Data flow
	 * See method 'elementP' in FuncExpBuilder class<br/>
	 * FuncExp signature : <code>elementP(elementId, fieldSelectorList=null, configSelector=null)</code><br/>
	 * Where arguments are :
	 * - Arg(0) elementId: int|LogExp. The element id used to fetch the element or a LogExp to search the element based on a business key
	 * - Arg(1) fieldSelectorList: FieldSelectorList. An optional FieldSelectorList to filter the fields that are fetched.
	 * - Arg(2) configSelector: ConfigSelector|LogExp|String. Optional parameter. If set, then defines the configuration of the element or the root element in case of subitems.
	 * If LogExp then should be the group selection log exp used to define the configuration,
	 * if String then should be the wigiiNamespace name used to define the configuration.
	 * @return ElementPDataFlowConnector
	 */
	public function elementP($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException('The elementP function takes at least one argument which is the elementId', FuncExpEvalException::INVALID_ARGUMENT);
		return $this->getFuncExpBuilder()->elementP($this->evaluateArg($args[0]),
			($nArgs > 1 ? $this->evaluateArg($args[1]) : null),
			($nArgs > 2 ? $this->evaluateArg($args[2]) : null));
	}

	// Element builder

	/**
	 * Creates a new Element which can be dumped into a DataFlow
	 * See method 'newElement' in FuncExpBuilder class<br/>
	 * FuncExp signature : <code>newElement(groupId, linkSelector=null)</code><br/>
	 * Where arguments are :
	 * - Arg(0) groupId: int. The group id in which the Element should be inserted
	 * - Arg(1) linkSelector: LinkSelector. An optional LinkSelector. If set, then the created element is linked to
	 * the owner element described in the LinkSelector.
	 * @return ElementDataFlowConnector
	 */
	public function newElement($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException('The newElement function takes at least one argument which is the groupId', FuncExpEvalException::INVALID_ARGUMENT);
		return $this->getFuncExpBuilder()->newElement($this->evaluateArg($args[0]),
			($nArgs > 1 ? $this->evaluateArg($args[1]) : null));
	}
	
	/**
	 * Deletes an Element
	 * FuncExp signature : <code>deleteElement(elementId)</code><br/>
	 * Where arguments are :
	 * - Arg(0) elementId: int. The element id to be deleted
	 * 
	 * This function cannot be called from public space (i.e. caller is located outside of the Wigii instance)
	 * @return elementId
	 */
	public function deleteElement($args) {
		$this->assertFxOriginIsNotPublic();
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException('The deleteElement function takes at least one argument which is the elementId', FuncExpEvalException::INVALID_ARGUMENT);
		$elementId = $this->evaluateArg($args[0]);
		sel(
			$this->getPrincipal(),
			elementP($elementId),
			dfasl(
					dfas("ElementDFA","setMode","2")
					)
			);
		return $elementId;
	}
	
	/**
	 * Update fields of an existing Element
	 * FuncExp signature : <code>updateElementFields(elementId, fieldValuesMap, cs)</code><br/>
	 * Where arguments are :
	 * - Arg(0) elementId: int. The element id to be updated
	 * - Arg(1) fieldValuesMap: Array. The field values as a Map. Key = fieldname, value = field value (if value is a map then subfields are updated).
	 * - Arg(2) cs: ConfigSelector. Optional configSelector.
	 * 
	 * This function cannot be called from public space (i.e. caller is located outside of the Wigii instance)
	 * @return elementId
	 */
	public function updateElementFields($args) {
		$this->assertFxOriginIsNotPublic();
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 2) throw new FuncExpEvalException('The updateElementFields function takes at least two argument which are the elementId and the fieldValueMap', FuncExpEvalException::INVALID_ARGUMENT);
		$elementId = $this->evaluateArg($args[0]);
		$fieldMap = $this->evaluateArg($args[1]); //here the values are evaluated for each fields
		if($nArgs > 2) $cs = $this->evaluateArg($args[2]);
		else $cs = null;
		$cfsMap = CalculatedFieldSelectorMapArrayImpl::createInstance();
		foreach($fieldMap as $fieldName=>$value){
			if(strpos($fieldName, "__element.")===0){
				$cfsMap->setCalculatedFieldSelector(cfs(fs_e(str_replace("__element.","",$fieldName)),$value));
			} else {
				$cfsMap->setCalculatedFieldSelectorByFieldName($fieldName, $value);
			}
		}
		sel(
			$this->getPrincipal(),
			elementP($elementId,NULL,$cs),
			dfasl(
				dfas("ElementSetterDFA","setCalculatedFieldSelectorMap",$cfsMap),
				dfas("ElementRecalcDFA"),
				dfas("ElementDFA","setMode","1")
			)
			);
		return $elementId;
	}
	
	/**
	 * Print an element with a template and return the html
	 * if template is not defined then takes Print->template path
	 * FuncExp signature : <code>printElementWithTemplate(element, template=null, options=null)</code><br/>
	 * Where arguments are :
	 * - Arg(0) element: Element
	 * - Arg(1) template: string. The name of the template to use in Print activity
	 * - Arg(2) options: WigiiBPLParam. Optional bag of option available in the template
	 * @return String html
	 */
	public function printElementWithTemplate($args){
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException('The printElementWithTemplate function takes at least one argument which is the element', FuncExpEvalException::INVALID_ARGUMENT);
		$element = $this->evaluateArg($args[0]);
		if($nArgs > 1) $template = $this->evaluateArg($args[1]);
		else $template = null;
		if($nArgs > 2){
			$options = $this->evaluateArg($args[2]);
			if(!($options instanceof WigiiBPLParameter)) throw new FuncExpEvalException('Options should be a none null instance of WigiiBPLParameter', FuncExpEvalException::INVALID_ARGUMENT);
		} else {
			$options = wigiiBPLParam();
		}
		$options->setValue("element",$element);
		$options->setValue("template",$template);
		return $this->getWigiiBPL()->elementPrintWithTemplate($this->getPrincipal(),$this,$options,$this->getFuncExpVMServiceProvider()->getExecutionSink());
	}
	
	/**
	 * Build a map with subfield values for an Html File
	 * FuncExp signature : <code>buildHtmlFileSubFieldsMap(filename, htmlcontent, date=null)</code><br/>
	 * Where arguments are :
	 * - Arg(0) filename: string. The name of the file
	 * - Arg(1) htmlcontent: string. The html content of the file
	 * - Arg(2) date: string in format "Y-m-d H:i:s" : Optional the date for the file creation, if not defined takes now.
	 *
	 * @return array["textContent","name","type","mime","date","user","username"]
	 */
	public function buildHtmlFileSubFieldsMap($args){
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 2) throw new FuncExpEvalException('The buildHtmlFileSubFieldsMap function takes at least two argument which are the file name and the html content', FuncExpEvalException::INVALID_ARGUMENT);
		$filename = $this->evaluateArg($args[0]);
		if($filename==null) return null;
		$htmlContent = $this->evaluateArg($args[1]);
		if($nArgs > 2){
			$date = $this->evaluateArg($args[2]);
		} else {
			$date = date("Y-m-d H:i:s");
		}
		$p = $this->getPrincipal();
		return array(
				"textContent"=>$htmlContent,
				"size"=>strlen($htmlContent),
				"name"=>$filename,
				"type"=>".html",
				"mime"=>"text/html",
				"date"=>$date,
				"user"=>$p->getRealUserId(),
				"username"=>$p->getRealUsername()
			);
	}
	
	/**
	 * Build a map with subfield values for an email
	 * FuncExp signature : <code>buildEmailSubFieldsMap(email)</code><br/>
	 * Where arguments are :
	 * - Arg(0) email: string. The email
	 * @return array["value","proofKey","proofValue","externalCode","externalAccessLevel"]
	 */
	public function buildEmailSubFieldsMap($args){
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs != 1) throw new FuncExpEvalException('The buildEmailSubFieldsMap function takes two argument which is the email value', FuncExpEvalException::INVALID_ARGUMENT);
		$email = $this->evaluateArg($args[0]);
		if($email==null) return null;
		$p = $this->getPrincipal();
		$elS = ServiceProvider::getElementService();
		$now = date("Y-m-d H:i:s");
		return array(
				"value"=>$email,
				"proofKey" => $elS->getEmailValidationCode($p, $email),
				"proofValue" => 0,
				"externalCode" => $elS->getEmailExternalCode($p, null, null, $now, $value), //getEmailExternalCode use the id and the fieldname to complexify the md5, but there is no real value in it, so add random name
				"externalAccessLevel" => 0
			);
	}
	
	/**
	 * Create/Update an Element
	 * FuncExp signature : <code>createUpdateElement(groupId, elementId, fieldValuesMap, triggerNotification = true)</code><br/>
	 * Where arguments are :
	 * - Arg(0) groupId: int. The group in which to insert the element
	 * - Arg(1) elementId: int. or LogExp returning 1 element. The element id to be updated (if null then element is created)
	 * - Arg(2) fieldValuesMap: Array. The field values as a Map. Key = fieldname, value = field value (if value is a map then subfields are updated).
	 * - Arg(3) triggerNotification: Boolean. If true then notfications are dispatched
	 * This function cannot be called from public space (i.e. caller is located outside of the Wigii instance)
	 * @return Element
	 */
	public function createUpdateElement($args) {
		$this->assertFxOriginIsNotPublic();
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 3) throw new FuncExpEvalException('The createUpdateElement function takes at least three argument which are the groupId, the elementId and the fieldValueMap', FuncExpEvalException::INVALID_ARGUMENT);
		$groupId = $this->evaluateArg($args[0]);
		$elementId = $this->evaluateArg($args[1]);
		$fieldMap = $this->evaluateArg($args[2]); //here the values are evaluated for each fields
		if($nArgs > 3){
			$triggerNotification = $this->evaluateArg($args[3]);
		} else {
			$triggerNotification = true;
		}
		
		$cfsMap = CalculatedFieldSelectorMapArrayImpl::createInstance();
		//create a new dynamic attribut with current gids
		//this value will be used then in the ElementUpdateSharingDFA to remove existing sharing
		$cfsMap->setCalculatedFieldSelector(cfs(fs_e("oldGids"),fx("getLinkedGroupIds")));
		foreach($fieldMap as $fieldName=>$value){
			if(strpos($fieldName, "__element.")===0){
				$cfsMap->setCalculatedFieldSelector(cfs(fs_e(str_replace("__element.","",$fieldName)),$value));
			} else {
				$cfsMap->setCalculatedFieldSelectorByFieldName($fieldName, $value);
			}
		}
		$dataSource = null;
		if($elementId==null){
			$dataSource = newElement($groupId);
		} else {
			$dataSource = elementP($elementId,NULL,$this->evaluateFuncExp(fx("cs_g",$groupId)));
		}
		return ServiceProvider::getDataFlowService()->processDataSource(
			$this->getPrincipal(),
			$dataSource,
			dfasl(
				dfas("ElementSetterDFA","setCalculatedFieldSelectorMap",$cfsMap),
				dfas("ElementRecalcDFA"),
				dfas("ElementDFA","setMode","1"), //when persisting and more stages after, the element is reloaded and pushed further.
				//dfas("NullDFA") //with this additional stage the retun value is the element
				dfas("ElementUpdateSharingDFA","setOldGroupIds",fs_e("oldGids")) //update the autosharings
			),
			true,
			($triggerNotification?TechnicalServiceProvider::getWigiiEventsDispatcher():false) //activate the notifications if $triggerNotification
		);
	}
	
	/**
	 * Creates a new sub Element which can be dumped into a DataFlow
	 * See method 'newSubElement' in FuncExpBuilder class<br/>
	 * FuncExp signature : <code>newSubElement(masterElementId, fieldName, rootConfigSelector=null)</code><br/>
	 * Where arguments are :
	 * - Arg(0) masterElementId: int. The master element id to which this sub element belongs
	 * - Arg(1) fieldName: String. The field name defining the link with this sub element
	 * - Arg(2) rootConfigSelector: ConfigSelector|LogExp|String. The configuration selector of the master element defined as a
	 * ConfigSelector object or a GroupLogExp or a String which is the WigiiNamespace.
	 */
	public function newSubElement($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 2) throw new FuncExpEvalException('The newElement function takes at least two arguments which is the masterElementId and the fieldName', FuncExpEvalException::INVALID_ARGUMENT);
		return $this->getFuncExpBuilder()->newSubElement($this->evaluateArg($args[0]),
			$this->evaluateArg($args[1]),
			($nArgs > 2 ? $this->evaluateArg($args[2]) : null));
	}

	/**
	 * Fills an Element MultipleAttributs field based on matching keywords.
	 * A MultipleAttribut value matches if it is contained in the keywords string. Comparison is case insensitive.
	 * FuncExp signature : <code>matchElementMultipleAttibuts(element, fieldName, keywords)</code><br/>
	 * Where arguments are :
	 * - Arg(0) element: Element. The element in which to match a multiple attributs field with some keywords
	 * - Arg(1) fieldName: String|FieldSelector. The field of type MultipleAttributs against wich to match the given keywords
	 * - Arg(2) keywords: String. A string against which will be searched the MultipleAttributs values for matching.
	 * @return Boolean returns true if some matching occured, else false.
	 */
	public function matchElementMultipleAttributs($args) {
	    $nArgs = $this->getNumberOfArgs($args);
	    if($nArgs < 3) throw new FuncExpEvalException('The matchElementMultipleAttributs function takes at least three arguments which is the element, the fieldName and the keywords', FuncExpEvalException::INVALID_ARGUMENT);
	    $element = $this->evaluateArg($args[0]);
	    if($args[1] instanceof FieldSelector) $fieldName = $args[1]->getFieldName();
	    else $fieldName = $this->evaluateArg($args[1]);
	    $field = $element->getFieldList()->getField($fieldName);
	    if(!($field->getDataType() instanceof MultipleAttributs)) throw new FuncExpEvalException('Field '.$fieldName." is not of type MultipleAttributs", FuncExpEvalException::INVALID_ARGUMENT);
	    	    
	    $keywords = $this->evaluateArg($args[2]);
	    $returnValue = false;
	    if($keywords) {
	        foreach($field->getXml()->attribute as $attribute_key => $attribute){
	            $attribute = (string)$attribute;
	            if($attribute != 'none') {
	                // if attribute matches keywords, then adds it to Element field
	                if(stripos($keywords, $attribute)!==false) {
	                    $element->addValueToField($attribute,$fieldName);
	                    $returnValue = true;
	                }
	            }
	        }
	    }
	    return $returnValue;
	}
	
	// GroupList builder

	/**
	 * Creates an GroupList based on selected groups (without detail) using the GroupAdminService
	 * and which can be dumped into a data flow.
	 * See method 'groupList' in FuncExpBuilder class.<br/>
	 * FuncExp signature : <code>groupList(groupLogExp, asStdClass=false)</code><br/>
	 * Where arguments are :
	 * - Arg(0) groupLogExp: LogExp. The log exp used to select the groups.
	 * - Arg(1) asStdClass: bool. If true, then StdClass instances with the attributes {id, groupname, wigiiNamespace, module, id_group_parent}
	 * are pushed in the dataflow, else Group instances (without detail) are pushed. Default is false.
	 * @return GroupListDataFlowConnector
	 */
	public function groupList($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException('The groupList function takes at least one argument which is the groupLogExp', FuncExpEvalException::INVALID_ARGUMENT);
		return $this->getFuncExpBuilder()->groupList($this->evaluateArg($args[0]),
				($nArgs > 1 ? $this->evaluateArg($args[1]) : false));
	}
	
	// Group builder
	
	/**
	 * Selects a group given its path and returns its ID. Creates all missing groups in the path.
	 * FuncExp signature : <code>group(startPoint, groupName1, groupName2, ...)</code><br/>
	 * Where arguments are :
	 * - Arg(0) startPoint: String. Selects the group from which to start the navigation. Can be "." (current folder), ".." (parent folder of current folder), group ID.
	 * - Arg(1..n) groupNameI: String. The group name to which to navigate from the previous point.
	 * @example The call of group("..","..","2015","August") will return the ID of the group ../../2015/August starting from current selected group.
	 * The call of group(12345,"A") will select and/or create the group A under the group with ID 12345, and returns its ID.
	 * The call of group(".") will returns the ID of the current selected group.
	 * The call of group(".", "A") will select and/or create the group A under the current group and return its ID.
	 * 
	 * This function cannot be called from public space (i.e. caller is located outside of the Wigii instance)
	 * @return int selected group ID.
	 * @throws GroupAdminServiceException if start point is not accessible or if group cannot be created.
	 */
	public function group($args) {
		$this->assertFxOriginIsNotPublic();
		$this->debugLogger()->logBeginOperation('group');
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException('The group function takes at least one argument which is starting group ID or "." or ".."', FuncExpEvalException::INVALID_ARGUMENT);
		$groupId = $this->evaluateArg($args[0]);
		
		// retrieves starting point group
		$gAS = $this->getGroupAdminService();
		$p = $this->getPrincipal();
		// current group		
		if($groupId == '.') {
			$group = $this->evaluateFuncExp(fx('cfgCurrentGroup', 'group'));
		}
		// parent group
		elseif($groupId == '..') {
			$group = $this->evaluateFuncExp(fx('cfgCurrentGroup', 'group'));
			$groupId = $group->getGroupParentId();
			if(is_null($groupId)) throw new GroupAdminServiceException('current group is a root group, cannot retrieve parent group', GroupAdminServiceException::INVALID_ARGUMENT);
			$group = $gAS->getGroupWithoutDetail($p, $groupId);
			if(is_null($group)) throw new GroupAdminServiceException('group '.$groupId.' does not exist into database', GroupAdminServiceException::INVALID_ARGUMENT);
		}
		// specific group
		else {
			$group = $gAS->getGroupWithoutDetail($p, $groupId);
			if(is_null($group)) throw new GroupAdminServiceException('group '.$groupId.' does not exist into database', GroupAdminServiceException::INVALID_ARGUMENT);
		}
		
		// navigates through the group path and creates all missing groups
		$groupCache = array($groupId => $group);
		for($i = 1; $i < $nArgs; $i++) {
			$groupId = $this->evaluateArg($args[$i]);
			// navigates back
			if($groupId == '..') {
				$groupId = $group->getGroupParentId();
				if(is_null($groupId)) throw new GroupAdminServiceException("group '".$group->getGroupName()."' is a root group, cannot retrieve parent group", GroupAdminServiceException::INVALID_ARGUMENT);
				$group = $groupCache[$groupId];
				if(!isset($group)) {
					$group = $gAS->getGroupWithoutDetail($p, $groupId);
					if(is_null($group)) throw new GroupAdminServiceException('group '.$groupId.' does not exist into database', GroupAdminServiceException::INVALID_ARGUMENT);
					$groupCache[$groupId] = $group;
				}
			}
			// navigates down by name
			elseif($groupId != '.') {
				$group = $gAS->getOrCreateSubGroupByName($p, $group->getId(), $groupId);
				$group = $group->getDbEntity();
				$groupCache[$group->getId()] = $group;
			} 
		}
		$this->debugLogger()->logEndOperation('group');
		return $group->getId();
	}

	// DataFlow connectors
	
	/**
	 * Queries an existing table in the database and pushes the rows as StdClasses into a DataFlow.
	 * See method 'dbTable2df' in FuncExpBuilder class.<br/>
	 * FuncExp signature : <code>dbTable2df(dbTableName, listFilter=null,sqlTypeMap=null)</code><br/>
	 * Where arguments are :
	 * - Arg(0) dbTableName: String. The db table name from which to select the rows
	 * - Arg(1) listFilter: ListFilter. An optional list filter to filter the selected rows
	 * - Arg(2) sqlTypeMap: Array. An optional array mapping a column name to its SQL type. Array is the column name, value is one of MySqlQueryBuilder::SQLTYPE_* or BIGINT, DOUBLE, DECIMAL, BOOLEAN, DATE, DATETIME, INT, LONGBLOB, BLOB, TEXT, TIME, VARCHAR
     * By default, if column is not defined, then SQL type is mapped to VARCHAR.
	 * @return DbTableDataFlowConnector returns a DbTableDataFlowConnector instance that can be used as a DataFlow source.
	 */
	public function dbTable2df($args) {
	    $nArgs = $this->getNumberOfArgs($args);
	    if($nArgs < 1) throw new FuncExpEvalException('The dbTable2df function takes at least one argument which is the db table name', FuncExpEvalException::INVALID_ARGUMENT);
	    return $this->getFuncExpBuilder()->dbTable2df($this->evaluateArg($args[0]),
	        ($nArgs > 1 ? $this->evaluateArg($args[1]) : null),
	        ($nArgs > 2 ? $this->evaluateArg($args[2]) : null));
	}
	
	
	/**
	 * Selects a dimension and pushes cfgAttribut StdClasses into a DataFlow
	 * cfgAttribut StdClasses are of the form {value, attributes, label}. See cfgAttribut FuncExp for more details.
	 * See method 'dimension2df' in FuncExpBuilder class.<br/>
	 * FuncExp signature : <code>dimension2df(selector, attrLogExp = null, sortOrder = 3, namespace=null)</code><br/>
	 * Where arguments are :
	 * - Arg(0) selector: String|Int|LogExp. The dimension selector. Can be a group id, a group name or a group log exp.
	 * - Arg(1) attrLogExp: LogExp. An optional LogExp used to filter the list of attributes (for instance filtering some specific values, see module Dimensions for details about the available fields)
	 * - Arg(2) sortOrder: int. One of 0 = no sorting, keep dimension element id ordering, 1 = ascending by value, 2 = descending by value, 3 = ascending by label, 4 = descending by label. (by default is ascending by label)
	 * - Arg(3) namespace: WigiiNamespace|String. Optional WigiiNamespace into which to look for a Dimensions module. If not defined, then takes Client Setup namespace.
	 * @return DimensionDataFlowConnector returns a DimensionDataFlowConnector instance that can be used as a DataFlow source.
	 */
	public function dimension2df($args) {
	    $nArgs = $this->getNumberOfArgs($args);
	    if($nArgs < 1) throw new FuncExpEvalException('The dimension2df function takes at least one argument which is the dimension selector. Can be a group id, a group name or a group log exp.', FuncExpEvalException::INVALID_ARGUMENT);
	    return $this->getFuncExpBuilder()->dimension2df($this->evaluateArg($args[0]),
	        ($nArgs > 1 ? $this->evaluateArg($args[1]) : null),
	        ($nArgs > 2 ? $this->evaluateArg($args[2]) : 3),
	    	($nArgs > 3 ? $this->evaluateArg($args[3]) : null));
	}
	
	/**
	 * A connector which dumps the content of an Element field of type Files into a data flow.
	 * The content is pushed chunk by chunk. Default chunk size is 512ko.
	 * See class ElementFileDataFlowConnector for more details.<br/>
	 * FuncExp signature : <code>elementFile2df(element, fieldName, chunkSize=null)</code> or <code>elementFile2df(fieldName, chunkSize=null)</code><br/>
	 * Where arguments are :
	 * - Arg(0) element: Element. Element from which to dump the file content. If element is ommited, then takes current element (i.e __element.this)
	 * - Arg(1) fieldName: String|FieldSelector. The name of the Field of type Files from which to dump the content
	 * - Arg(2) chunkSize: Int. Optional chunk size.
	 * @return ElementFileDataFlowConnector
	 */
	public function elementFile2df($args) {
	    $nArgs = $this->getNumberOfArgs($args);
	    if($nArgs < 1) throw new FuncExpEvalException('The elementFile2df function takes at least one argument which is field name containing the file to dump', FuncExpEvalException::INVALID_ARGUMENT);	    
	    // if first argument is a FieldSelector assumes it represents the fieldName
	    if($args[0] instanceof FieldSelector) {
	        $fieldName = $args[0];
	        $element = $this->evaluateArg(fs_e('this'));
	        $chunkSizeIndex = 1;
	    }
	    // else evaluates it
	    else {
	        $element = $this->evaluateArg($args[0]);
	        if(!($element instanceof Record)) {
	            $fieldName = $element;
	            $element = $this->evaluateArg(fs_e('this'));
	            $chunkSizeIndex = 1;
	        }
	        elseif($nArgs > 1) {
	            $fieldName = $this->evaluateArg($args[1]);
	            $chunkSizeIndex = 2;
	        }
	        else throw new FuncExpEvalException('field name containing the file to dump is missing', FuncExpEvalException::INVALID_ARGUMENT);
	    }
	    if($nArgs > $chunkSizeIndex) $chunkSize = $this->evaluateArg($args[$chunkSizeIndex]);
	    else $chunkSize = null;
	    
	    return TechnicalServiceProvider::getFuncExpBuilder()->elementFile2df($element, $fieldName, $chunkSize);
	}
	
	/**
	 * Dumps the fields of a Wigii xml configuration file as cfgField stdClass instances into a DataFlow
	 * cfgField StdClasses are of the form {name, attributes, label, cfgAttributs}. See cfgField FuncExp for more details.
	 * See method 'moduleXml2df' in FuncExpBuilder class.<br/>
	 * FuncExp signature : <code>moduleXml2df(xmlFile,listFilter=null,setterI,valueI,...)</code><br/>
	 * Where arguments are :
	 * - Arg(0) xmlFile: String|ElementFileDataFlowConnector. The name of an existing Wigii configuration file to load or an already open connector to an xml file attached to current element
	 * - Arg(1) listFilter: ListFilter. An optional ListFilter instance to filter the fields to extract based on some attribute values
	 * - Arg(i) setterI,valueI: Pairs of (setter,value) which configures the ModuleXmlDataFlowConnector. Each setter should map to an existing public setter method in the ModuleXmlDataFlowConnector instance.
	 * @return ModuleXmlDataFlowConnector returns a ModuleXmlDataFlowConnector instance that can be used as a DataFlow source.
	 */
	public function moduleXml2df($args) {
	    $nArgs = $this->getNumberOfArgs($args);
	    if($nArgs < 1) throw new FuncExpEvalException('The moduleXml2df function takes at least one argument which is the Wigii configuration file. Can be an existing configuration file name or an open connector to a file attached to current element', FuncExpEvalException::INVALID_ARGUMENT);
	    $returnValue = $this->getFuncExpBuilder()->moduleXml2df($this->evaluateArg($args[0]),($nArgs > 1 ? $this->evaluateArg($args[1]) : null));
	    // Extracts any additional configuration
	    if($nArgs>2) {
	        $configurator = ObjectConfigurator::createInstance();
	        $i = 2;
	        while($i < $nArgs) {
	            $setter = $this->evaluateArg($args[$i]);
                $i++;
                if($i < $nArgs) {
                    $v = $this->evaluateArg($args[$i]);
                    $configurator->setConfigValue($setter, $v);
                }
                else {
                    $configurator->setConfigValue($setter, null);                    
                }
	            $i++;
	        }
	        // configures ModuleXmlDataFlowConnector instance
	        $configurator->configure($returnValue);
	    }
	    return $returnValue;
	}
	
	// Transformations
	
	/**
	 * Transforms a given element to a value using a transformation FuncExp<br/>
	 * FuncExp signature : <code>element2value(element,fx)</code><br/>
	 * Where arguments are :
	 * - Arg(0) element: Element|Record. An element or a record to be transformed into a value.
	 * - Arg(1) fx: FuncExp|FieldSelector. A transformation FuncExp which returns a value based on the element. Or a FieldSelector returning a value of the element.
	 * @return Any the result of the FuncExp
	 */
	public function element2value($args) {
	    $nArgs = $this->getNumberOfArgs($args);
	    $p = $this->getPrincipal();
	    if($nArgs < 2) throw new FuncExpEvalException('element2value takes two arguments, the element to transform and a transformation FuncExp or FieldSelector', FuncExpEvalException::INVALID_ARGUMENT);
	    $element = $this->evaluateArg($args[0]);
	    if(!isset($element)) throw new FuncExpEvalException('element cannot be null', FuncExpEvalException::INVALID_ARGUMENT);
	    $fx = $args[1];
	    if(($fx instanceof FuncExp) && $this->isFxOriginPublic()) $fx->setOriginIsPublic();
	    
	    // gets RecordEvaluator
	    if($element instanceof Element) $evaluatorClassName = (string)ServiceProvider::getConfigService()->getParameter($p, $element->getModule(), "Element_evaluator");
	    else $evaluatorClassName = null;
	    if(empty($evaluatorClassName)) $evaluatorClassName = (string)ServiceProvider::getConfigService()->getParameter($p, ServiceProvider::getExecutionService()->getCrtModule(), "Element_evaluator");
	    $fxEval= ServiceProvider::getRecordEvaluator($p, $evaluatorClassName);
	    // injects the context
	    $fxEval->setContext($p, $element);
	    // gets vm
	    $fxEval= ServiceProvider::getFuncExpVM($p, $fxEval);
	    $fxEval->setFreeParentEvaluatorOnFreeMemory(true);
	    
	    // evaluates the expression
	    $returnValue = null;
	    try {
	        $returnValue = $fxEval->evaluateFuncExp($fx, $this);
	        $fxEval->freeMemory();
	    }
	    catch(Exception $e) {
	        $fxEval->freeMemory();
	        throw $e;
	    }
	    return $returnValue;
	}
	
	// Configuration builder
	
	/**
	 * FuncExp signature : <code>dtAttributs_merge(field1,field2,field3...)</code><br/>
	 * Where arguments are :
	 * - Arg(0) value or multiple value (array): of type Attributs or MultipleAttributs
	 * - Arg(1) value or multiple value (array): of type Attributs or MultipleAttributs
	 * - Arg(2...) value or multiple value (array): of type Attributs or MultipleAttributs
	 * @return Array returns an array containing a merge of all values compatible for MultipleAttributs Value
	 * Note: use newMap("value",dtAttributs_merge(....)) if you want to store the result in a MultipleAttributs field.
	 */
	public function dtAttributs_merge($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 2) throw new FuncExpEvalException("dtAttributs_merge function takes at least two parameters, field1, field2", FuncExpEvalException::INVALID_ARGUMENT);
		$returnValue = array();
		for($i = 0; $i<$nArgs; $i++){
			$val = $this->evaluateArg($args[$i]);
			if($val===null) continue; //ignores null args
			if(is_array($val)){
				$returnValue = array_merge($returnValue,array_combine(array_values($val),array_values($val)));
			} else {
				$returnValue[$val] = $val;
			}
		}
		return $returnValue;
	}
	/**
	 * Creates an StdClass instance which maps a Field in the Wigii XML configuration file.
	 * See method 'cfgField' in FuncExpBuilder class.<br/>
	 * FuncExp signature : <code>cfgField(name, attributes=null, label=null, cfgAttributs=null)</code><br/>
	 * Where arguments are :
	 * - Arg(0) name: String. The name of the field
	 * - Arg(1) attributes: Array. An array [key => value] which defines some xml attributes.
	 * The type of the field should defined in this array if needed. For example array('type' => 'Attributs').
	 * - Arg(2) label: String|Array. The label of the field, or an array with the label translated in different languages.
	 * The array is indexed with the Wigii installed languages (see TranslationService). For instance [l01 => label in English, l02 => label in French]
	 * - Arg(3) cfgAttributs: StdClass|Array. An optional array of cfgAttribut StdClasses defining the list of attributes or one StdClass cfgAttribut
	 * @return StdClass returns an StdClass instance with the properties {name, attributes, label, cfgAttributs}
	 */
	public function cfgField($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException('The cfgField function takes at least one argument which is the field name', FuncExpEvalException::INVALID_ARGUMENT);
		return $this->getFuncExpBuilder()->cfgField($this->evaluateArg($args[0]),
				($nArgs > 1 ? $this->evaluateArg($args[1]) : null),
				($nArgs > 2 ? $this->evaluateArg($args[2]) : null),
				($nArgs > 3 ? $this->evaluateArg($args[3]) : null));
	}

	/**
	 * Creates an StdClass instance which maps an 'attribute' node in the Wigii XML configuration file.
	 * See method 'cfgAttribut' in FuncExpBuilder class.<br/>
	 * FuncExp signature : <code>cfgAttribut(value, attributes=null, label=null)</code><br/>
	 * Where arguments are :
	 * - Arg(0) value: Scalar. The value of the attribute
	 * - Arg(1) attributes: Array. An array [key => value] which defines some xml attributes.
	 * - Arg(2) label: String|Array. The label of the attribute, or an array with the label translated in different languages.
	 * The array is indexed with the Wigii installed languages (see TranslationService). For instance [l01 => label in English, l02 => label in French]
	 * @return StdClass returns an StdClass instance with the properties {value, attributes, label}
	 */
	public function cfgAttribut($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException('The cfgAttribut function takes at least one argument which is the attribute value', FuncExpEvalException::INVALID_ARGUMENT);
		return $this->getFuncExpBuilder()->cfgAttribut($this->evaluateArg($args[0]),
				($nArgs > 1 ? $this->evaluateArg($args[1]) : null),
				($nArgs > 2 ? $this->evaluateArg($args[2]) : null));
	}

	/**
	 * Creates an StdClass instance which maps an 'attributeExp' node in the Wigii XML configuration file.
	 * See method 'cfgAttributeExp' in FuncExpBuilder class.<br/>
	 * FuncExp signature : <code>cfgAttributeExp(funcExp)</code><br/>
	 * Where arguments are :
	 * - Arg(0) funcExp: String. The attribute func exp as a string.
	 * @return StdClass returns an StdClass instance with the property {funcExp}
	 */
	public function cfgAttributeExp($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException('The cfgAttributeExp function takes at least one argument which is the attribute funcExp', FuncExpEvalException::INVALID_ARGUMENT);
		return $this->getFuncExpBuilder()->cfgAttributeExp($this->evaluateArg($args[0]));
	}

	/**
	 * Selects the attributes of a dimension and returns a SimpleXmlElement compatible with attribute expressions.<br/>
	 * FuncExp signature : <code>cfgAttrDimension(selector, attrLogExp = null, sortOrder = 3, filterWithUserRights=false)</code><br/>
	 *  Where arguments are :
	 * - Arg(0) selector: String|Int|LogExp. The dimension selector. Can be a group id, a group name or a group log exp.
	 * - Arg(1) attrLogExp: LogExp. An optional LogExp used to filter the list of attributes (for instance filtering some specific values, see module Dimensions for details about the available fields)
	 * - Arg(2) sortOrder: Int. One of 0 = no sorting, keep dimension element id ordering, 1 = ascending by value, 2 = descending by value, 3 = ascending by label, 4 = descending by label. (by default is ascending by label)
	 * - Arg(3) filterWithUserRights: Boolean. If true, then uses current principal to fetch the dimension else uses the root principal. (By default uses the root principal).
	 *
	 * Examples: in a configuration file create an attribut:
	 * <country type="Attributs"><label>Country</label>
	 *     <attributeExp funcExp='cfgAttrDimension("Country")'/>
	 * </country>
	 *
	 * Append two dimensions in one drop down :
	 * <fundCodes type="Attributs"><label>Fund codes</label>
	 * 		<attribute optGroupStart="1"><label>Codes 1</label></attribute>
	 * 		<attributeExp funcExp='cfgAttrDimension("12342")'/>
	 * 		<attribute optGroupEnd="1"/>
	 * 		<attribute optGroupStart="1"><label>Codes 2</label></attribute>
	 * 		<attributeExp funcExp='cfgAttrDimension("12343")'/>
	 * 		<attribute optGroupEnd="1"/>
	 * 	</fundCodes>
	 *
	 * 	Merge two dimensions in one drop down :
	 * 	<articles type="Attributs"><label>Articles</label>
	 * 		<attributeExp funcExp='cfgAttrDimension(lxIn(fs("id"), newList("1243", "4564")))'/>
	 *  </articles>
	 *  @return SimpleXMLElement
	 */
	public function cfgAttrDimension($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1 || empty($args[0])) throw new FuncExpEvalException('The cfgAttrDimension function takes at least one argument which is the dimension selector. Can be a group id, a group name or a group log exp.', FuncExpEvalException::INVALID_ARGUMENT);
		$fxParser = TechnicalServiceProvider::getFieldSelectorFuncExpParser();
		$cacheKey = $fxParser->funcExpToString($args[0]);
		$selector = $this->evaluateArg($args[0]);
		if(empty($selector)) throw new FuncExpEvalException('The cfgAttrDimension function takes at least one argument which is the dimension selector. Can be a group id, a group name or a group log exp.', FuncExpEvalException::INVALID_ARGUMENT);

		if($nArgs > 1 && !empty($args[1])) {
			$cacheKey .= ','.$fxParser->funcExpToString($args[1]);
			$attrLogExp = $this->evaluateArg($args[1]);
		}
		else {
			$cacheKey .= ',NULL';
			$attrLogExp = null;
		}
		if($nArgs > 2) {
			$sortOrder = $this->evaluateArg($args[2]);
			if($sortOrder == 0) $sortOrder = 0;
			elseif($sortOrder == 1) $sortOrder = 1;
			elseif($sortOrder == 2) $sortOrder = 2;
			elseif($sortOrder == 3) $sortOrder = 3;
			elseif($sortOrder == 4) $sortOrder = 4;
			else throw new FuncExpEvalException("sortOrder should be one of 0 = no sorting, keep dimension element id ordering, 1 = ascending by value, 2 = descending by value, 3 = ascending by label, 4 = descending by label.", FuncExpEvalException::INVALID_ARGUMENT);
		}
		else $sortOrder = 3;

		if($nArgs > 3) $filterWithUserRights = $this->evaluateArg($args[3]);
		else $filterWithUserRights = false;

		// gets principal
		if($filterWithUserRights) $p = $this->getPrincipal();
		else $p = $this->getRootPrincipal();

		// gets setup wigii namespace and binds to it
		$setupNS =  $this->getWigiiNamespaceAdminService()->getSetupWigiiNamespace($p);
		$origNS = $p->getWigiiNamespace();
		$p->bindToWigiiNamespace($setupNS);
		$cacheKey .= ','.$p->getUsername();

		// builds dimension selector
		if(!($selector instanceof LogExp)) {
			if(is_numeric($selector)) $selector = lxEq(fs('id'), $selector);
			else $selector = lxEq(fs('groupname'), $selector);
		}
		$selector = lxAnd(lxEq(fs('module'), Module::DIMENSIONS_MODULE), lxEq(fs('wigiiNamespace'), $setupNS->getWigiiNamespaceName()), $selector);

		// builds fskl
		switch($sortOrder) {
			case 0:
				$fskl = fskl(fsk('__element', 'id', true));
				$sortOrder = 0;
				break;
			case 1:
				$fskl = fskl(fsk('value', null, true));
				$sortOrder = 0;
				break;
			case 2:
				$fskl = fskl(fsk('value', null, false));
				$sortOrder = 0;
				break;
			case 3:
				$fskl = null;
				$sortOrder = 1;
				break;
			case 4:
				$fskl = null;
				$sortOrder = 2;
				break;
		}

		// builds list filter
		$fsl = fsl(fs('value', 'value'), fs('idGroup', 'value'), fs('color', 'value'), fs('email', 'value'), fs('checked', 'value'), fs('label', 'value'));
		if(isset($attrLogExp) || isset($fskl)) $lf = lf($fsl, $attrLogExp, $fskl);
		else $lf = lf($fsl);

		$this->debugLogger()->write("cfgAttrDimension cache key = $cacheKey");
		try {
			$returnValue = $this->getDataFlowService()->processDataSource($p,
					elementPList(lxInGR($selector), $lf),
					dfasl(
							dfas("MapElement2ValueDFA", "setElement2ValueFuncExp", fx('cfgAttribut',
									fs('value'),
									fx('newMap', 
											'idGroup', fs('idGroup'), 
											'color', fs('color'), 
											'email', fs('email'), 
											'checked', fx('ctlIf', fs('checked'), '1', '0'),
											'disabled', fx('ctlIf', fs_e('state_deprecated'), '1', '0')
									),
									fs('label'))),
							dfas("FilterDuplicatesAndSortDFA",
									"setObjectClass", 'cfgAttribut',
									"setSortOrder", $sortOrder
							),
							dfas("CfgAttribut2XmlDFA")
					), true, null, 'cfgAttrDimension('.md5($cacheKey).')');
		}
		catch(Exception $e) {
			$message="Problem when retrieving dimension ".$fxParser->funcExpToString($args[0]).".\nCheck that dimension exist in ". $setupNS->getWigiiNamespaceName()." namespace.\n[technical error is: ".$e->getCode().' '.$e->getMessage()."]";
			throw new FuncExpEvalException($message,FuncExpEvalException::CONFIGURATION_ERROR);
		}
		$p->bindToWigiiNamespace($origNS);
		return $returnValue;
	}
	
	/**
	 * Selects the attributes of a dimension located in the current Wigii namespace and returns a SimpleXmlElement compatible with attribute expressions.<br/>
	 * FuncExp signature : <code>cfgAttrDimensionCrtNamespace(selector, attrLogExp = null, sortOrder = 3, filterWithUserRights=false)</code><br/>
	 *  Where arguments are :
	 * - Arg(0) selector: String|Int|LogExp. The dimension selector. Can be a group id, a group name or a group log exp.
	 * - Arg(1) attrLogExp: LogExp. An optional LogExp used to filter the list of attributes (for instance filtering some specific values, see module Dimensions for details about the available fields)
	 * - Arg(2) sortOrder: Int. One of 0 = no sorting, keep dimension element id ordering, 1 = ascending by value, 2 = descending by value, 3 = ascending by label, 4 = descending by label. (by default is ascending by label)
	 * - Arg(3) filterWithUserRights: Boolean. If true, then uses current principal to fetch the dimension else uses the root principal. (By default uses the root principal).
	 *
	 * Examples: in a configuration file create an attribut:
	 * <country type="Attributs"><label>Country</label>
	 *     <attributeExp funcExp='cfgAttrDimensionCrtNamespace("Country")'/>
	 * </country>
	 *
	 * Append two dimensions in one drop down :
	 * <fundCodes type="Attributs"><label>Fund codes</label>
	 * 		<attribute optGroupStart="1"><label>Codes 1</label></attribute>
	 * 		<attributeExp funcExp='cfgAttrDimensionCrtNamespace("12342")'/>
	 * 		<attribute optGroupEnd="1"/>
	 * 		<attribute optGroupStart="1"><label>Codes 2</label></attribute>
	 * 		<attributeExp funcExp='cfgAttrDimensionCrtNamespace("12343")'/>
	 * 		<attribute optGroupEnd="1"/>
	 * 	</fundCodes>
	 *
	 * 	Merge two dimensions in one drop down :
	 * 	<articles type="Attributs"><label>Articles</label>
	 * 		<attributeExp funcExp='cfgAttrDimensionCrtNamespace(lxIn(fs("id"), newList("1243", "4564")))'/>
	 *  </articles>
	 *  @return SimpleXMLElement
	 */
	public function cfgAttrDimensionCrtNamespace($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1 || empty($args[0])) throw new FuncExpEvalException('The cfgAttrDimensionCrtNamespace function takes at least one argument which is the dimension selector. Can be a group id, a group name or a group log exp.', FuncExpEvalException::INVALID_ARGUMENT);
		$fxParser = TechnicalServiceProvider::getFieldSelectorFuncExpParser();
		$cacheKey = $fxParser->funcExpToString($args[0]);
		$selector = $this->evaluateArg($args[0]);
		if(empty($selector)) throw new FuncExpEvalException('The cfgAttrDimensionCrtNamespace function takes at least one argument which is the dimension selector. Can be a group id, a group name or a group log exp.', FuncExpEvalException::INVALID_ARGUMENT);
		
		if($nArgs > 1 && !empty($args[1])) {
			$cacheKey .= ','.$fxParser->funcExpToString($args[1]);
			$attrLogExp = $this->evaluateArg($args[1]);
		}
		else {
			$cacheKey .= ',NULL';
			$attrLogExp = null;
		}
		if($nArgs > 2) {
			$sortOrder = $this->evaluateArg($args[2]);
			if($sortOrder == 0) $sortOrder = 0;
			elseif($sortOrder == 1) $sortOrder = 1;
			elseif($sortOrder == 2) $sortOrder = 2;
			elseif($sortOrder == 3) $sortOrder = 3;
			elseif($sortOrder == 4) $sortOrder = 4;
			else throw new FuncExpEvalException("sortOrder should be one of 0 = no sorting, keep dimension element id ordering, 1 = ascending by value, 2 = descending by value, 3 = ascending by label, 4 = descending by label.", FuncExpEvalException::INVALID_ARGUMENT);
		}
		else $sortOrder = 3;
		
		if($nArgs > 3) $filterWithUserRights = $this->evaluateArg($args[3]);
		else $filterWithUserRights = false;
		
		// gets principal
		if($filterWithUserRights) $p = $this->getPrincipal();
		else $p = $this->getRootPrincipal();
		
		// gets current wigii namespace and binds to it
		$setupNS =  ServiceProvider::getExecutionService()->getCrtWigiiNamespace();
		$origNS = $p->getWigiiNamespace();
		$p->bindToWigiiNamespace($setupNS);
		$cacheKey .= ','.$p->getUsername();
		
		// builds dimension selector
		if(!($selector instanceof LogExp)) {
			if(is_numeric($selector)) $selector = lxEq(fs('id'), $selector);
			else $selector = lxEq(fs('groupname'), $selector);
		}
		$selector = lxAnd(lxEq(fs('module'), Module::DIMENSIONS_MODULE), lxEq(fs('wigiiNamespace'), $setupNS->getWigiiNamespaceName()), $selector);
		
		// builds fskl
		switch($sortOrder) {
			case 0:
				$fskl = fskl(fsk('__element', 'id', true));
				$sortOrder = 0;
				break;
			case 1:
				$fskl = fskl(fsk('value', null, true));
				$sortOrder = 0;
				break;
			case 2:
				$fskl = fskl(fsk('value', null, false));
				$sortOrder = 0;
				break;
			case 3:
				$fskl = null;
				$sortOrder = 1;
				break;
			case 4:
				$fskl = null;
				$sortOrder = 2;
				break;
		}
		
		// builds list filter
		$fsl = fsl(fs('value', 'value'), fs('idGroup', 'value'), fs('color', 'value'), fs('email', 'value'), fs('checked', 'value'), fs('label', 'value'));
		if(isset($attrLogExp) || isset($fskl)) $lf = lf($fsl, $attrLogExp, $fskl);
		else $lf = lf($fsl);
		
		$this->debugLogger()->write("cfgAttrDimensionCrtNamespace cache key = $cacheKey");
		try {
			$returnValue = $this->getDataFlowService()->processDataSource($p,
					elementPList(lxInGR($selector), $lf),
					dfasl(
							dfas("MapElement2ValueDFA", "setElement2ValueFuncExp", fx('cfgAttribut',
									fs('value'),
									fx('newMap',
											'idGroup', fs('idGroup'),
											'color', fs('color'),
											'email', fs('email'),
											'checked', fx('ctlIf', fs('checked'), '1', '0'),
											'disabled', fx('ctlIf', fs_e('state_deprecated'), '1', '0')
											),
									fs('label'))),
							dfas("FilterDuplicatesAndSortDFA",
									"setObjectClass", 'cfgAttribut',
									"setSortOrder", $sortOrder
									),
							dfas("CfgAttribut2XmlDFA")
							), true, null, 'cfgAttrDimension('.md5($cacheKey).')');
		}
		catch(Exception $e) {
			$message="Problem when retrieving dimension ".$fxParser->funcExpToString($args[0]).".\nCheck that dimension exist in ". $setupNS->getWigiiNamespaceName()." namespace.\n[technical error is: ".$e->getCode().' '.$e->getMessage()."]";
			throw new FuncExpEvalException($message,FuncExpEvalException::CONFIGURATION_ERROR);
		}
		$p->bindToWigiiNamespace($origNS);
		return $returnValue;
	}

	/**
	 * Selects the attributes of a dimension, returns a SimpleXmlElement compatible with attribute expressions,
	 * which is navigable using the Wigii find functionality.<br/>
	 * FuncExp signature : <code>cfgAttrFindableDimension(selector, elementPList, keyField, attrLogExp = null, sortOrder = 3, filterWithUserRights=false)</code><br/>
	 *  Where arguments are :
	 * - Arg(0) selector: String|Int|LogExp. The dimension selector. Can be a group id, a group name or a group log exp.
	 * - Arg(1) elementPList: ElementPListDataFlowConnector. Defines the list of elements in which to search for the element related to value of the drop-down. Use the elementPList func exp to construct this object.
	 * - Arg(2) keyField: String|FieldSelector. The name of the field in the linked element on whic to match the drop-down value. Can also be a FieldSelector.
	 * - Arg(3) attrLogExp: LogExp. An optional LogExp used to filter the list of attributes (for instance filtering some specific values, see module Dimensions for details about the available fields)
	 * - Arg(4) sortOrder: Int. One of 0 = no sorting, keep dimension element id ordering, 1 = ascending by value, 2 = descending by value, 3 = ascending by label, 4 = descending by label. (by default is ascending by label)
	 * - Arg(5) filterWithUserRights: Boolean. If true, then uses current principal to fetch the dimension else uses the root principal. (By default uses the root principal).
	 *
	 * Examples: in a configuration file AuditFindings create an attribut:
	 * <auditNumber type="Attributs"><label>Audit number</label>
	 *     <attributeExp funcExp='cfgAttrDimension("Audit number", elementPList(lxInGR(lxEq(fs("id"),"1234"))), "auditNumber")'/>
	 * </auditNumber>
	 *
	 * which builds an hyperlink to Audits below root folder 1234, having auditNumber field matching.
	 * 
	 *  @return SimpleXMLElement
	 */
	public function cfgAttrFindableDimension($args) {
		$this->debugLogger()->logBeginOperation('cfgAttrFindableDimension');
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1 || empty($args[0])) throw new FuncExpEvalException('The cfgAttrFindableDimension function takes at least one argument which is the dimension selector. Can be a group id, a group name or a group log exp.', FuncExpEvalException::INVALID_ARGUMENT);
		$fxParser = TechnicalServiceProvider::getFieldSelectorFuncExpParser();
		$cacheKey = $fxParser->funcExpToString($args[0]);
		$selector = $this->evaluateArg($args[0]);
		if(empty($selector)) throw new FuncExpEvalException('The cfgAttrFindableDimension function takes at least one argument which is the dimension selector. Can be a group id, a group name or a group log exp.', FuncExpEvalException::INVALID_ARGUMENT);

		if($nArgs > 1) {
			$elementPListFx = $fxParser->funcExpToString($args[1]);
			$cacheKey .= ','.$elementPListFx;
		}
		else {
			$elementPListFx = null;
			$cacheKey .= ',NULL';
		}
		
		if($nArgs > 2) {
			$keyField = $this->evaluateArg($args[2]);
			if($keyField instanceof FieldSelector) $keyField = $keyField->getFieldName();			
		}
		
		if($nArgs > 3 && !empty($args[3])) {
			$cacheKey .= ','.$fxParser->funcExpToString($args[3]);
			$attrLogExp = $this->evaluateArg($args[3]);
		}
		else {
			$cacheKey .= ',NULL';
			$attrLogExp = null;
		}
		if($nArgs > 4) {
			$sortOrder = $this->evaluateArg($args[4]);
			if($sortOrder == 0) $sortOrder = 0;
			elseif($sortOrder == 1) $sortOrder = 1;
			elseif($sortOrder == 2) $sortOrder = 2;
			elseif($sortOrder == 3) $sortOrder = 3;
			elseif($sortOrder == 4) $sortOrder = 4;
			else throw new FuncExpEvalException("sortOrder should be one of 0 = no sorting, keep dimension element id ordering, 1 = ascending by value, 2 = descending by value, 3 = ascending by label, 4 = descending by label.", FuncExpEvalException::INVALID_ARGUMENT);
		}
		else $sortOrder = 3;

		if($nArgs > 5) $filterWithUserRights = $this->evaluateArg($args[5]);
		else $filterWithUserRights = false;

		// gets principal
		if($filterWithUserRights) $p = $this->getPrincipal();
		else $p = $this->getRootPrincipal();

		// gets setup wigii namespace and binds to it
		$setupNS =  $this->getWigiiNamespaceAdminService()->getSetupWigiiNamespace($p);
		$origNS = $p->getWigiiNamespace();
		$p->bindToWigiiNamespace($setupNS);
		$cacheKey .= ','.$p->getUsername();

		// builds dimension selector
		if(!($selector instanceof LogExp)) {
			if(is_numeric($selector)) $selector = lxEq(fs('id'), $selector);
			else $selector = lxEq(fs('groupname'), $selector);
		}
		$selector = lxAnd(lxEq(fs('module'), Module::DIMENSIONS_MODULE), lxEq(fs('wigiiNamespace'), $setupNS->getWigiiNamespaceName()), $selector);

		// builds fskl
		switch($sortOrder) {
			case 0:
				$fskl = fskl(fsk('__element', 'id', true));
				$sortOrder = 0;
				break;
			case 1:
				$fskl = fskl(fsk('value', null, true));
				$sortOrder = 0;
				break;
			case 2:
				$fskl = fskl(fsk('value', null, false));
				$sortOrder = 0;
				break;
			case 3:
				$fskl = null;
				$sortOrder = 1;
				break;
			case 4:
				$fskl = null;
				$sortOrder = 2;
				break;
		}

		// builds list filter
		$fsl = fsl(fs('value', 'value'), fs('idGroup', 'value'), fs('color', 'value'), fs('email', 'value'), fs('checked', 'value'), fs('label', 'value'));
		if(isset($attrLogExp) || isset($fskl)) $lf = lf($fsl, $attrLogExp, $fskl);
		else $lf = lf($fsl);

		$this->debugLogger()->write("cfgAttrFindableDimension cache key = $cacheKey");
		try {
			$returnValue = $this->getDataFlowService()->processDataSource($p,
					elementPList(lxInGR($selector), $lf),
					dfasl(
							dfas("MapElement2ValueDFA", "setElement2ValueFuncExp", fx('cfgAttribut',
									fs('value'),
									fx('newMap', 
											'idGroup', fs('idGroup'), 
											'color', fs('color'), 
											'email', fs('email'), 
											'checked', fx('ctlIf', fs('checked'), '1', '0'),
											'disabled', fx('ctlIf', fs_e('state_deprecated'), '1', '0')
									),
									fs('label'))),
							dfas("FilterDuplicatesAndSortDFA",
									"setObjectClass", 'cfgAttribut',
									"setSortOrder", $sortOrder
							),
							dfas("CallbackDFA", 
									'setProcessDataChunkCallback', CallableObject::createInstance('cfgAttrFindableDimension_LinkBackMethod', $this),
									'initializeContext', array('url'=>"find/", 'query'=>base64url_encode($elementPListFx), 'keyField'=>$keyField)
							),
							dfas("CfgAttribut2XmlDFA")
					), true, null, 'cfgAttrDimension('.md5($cacheKey).')');
		}
		catch(Exception $e) {
			$message="Problem when retrieving dimension ".$fxParser->funcExpToString($args[0]).".\nCheck that dimension exist in ". $setupNS->getWigiiNamespaceName()." namespace.\n[technical error is: ".$e->getCode().' '.$e->getMessage()."]";
			throw new FuncExpEvalException($message,FuncExpEvalException::CONFIGURATION_ERROR);
		}
		$p->bindToWigiiNamespace($origNS);
		$this->debugLogger()->logEndOperation('cfgAttrFindableDimension');
		return $returnValue;
	}
	
	/**
	 * cfgAttrFindableDimension callback to setup hyperlink in drop-down
	 * See CallbackDFA.
	 */
	public function cfgAttrFindableDimension_LinkBackMethod($data, $callbackDFA) {
		$url = $callbackDFA->getValueInContext('url');
		if(!empty($url)) {
			$query = $callbackDFA->getValueInContext('query');
			$keyField = $callbackDFA->getValueInContext('keyField');
			$businessKey = 'lxEq(fs("'.$keyField.'"),"'.$data->value.'")';
			$businessKey = base64url_encode($businessKey);
			
			$url .= $query.'/'.$businessKey.'/filter';		
				
			$onclick = "$(this).attr('href', prependCrtWigiiNamespaceAndModule2Url('".$url."')); event.stopPropagation(); return true;";
			$url = '';
			
			$labels = $data->label;
			if(empty($labels)) {
				$data->label = '<a href="'.$url.'" target="_blank" onclick="'.$onclick.'">'.$data->value.'</a>';
			}
			elseif(is_array($labels)) {			
				foreach($labels as $lang => &$label) {
					$label =  '<a href="'.$url.'" target="_blank" onclick="'.$onclick.'">'.(empty($label)?$data->value:$label).'</a>';
				}
				$data->label = $labels;
			}
			else {
				$data->label = '<a href="'.$url.'" target="_blank" onclick="'.$onclick.'">'.$data->label.'</a>';
			}
		}
		$callbackDFA->writeResultToOutput($data);
	}
		
	/**
	 * Returns a SimpleXmlElement compatible with attribute expressions, which contains the visible languages in the system
	 * See TranslationService::getVisibleLanguage
	 * FuncExp signature : <code>cfgAttrLanguage()</code><br/>
	 * @return SimpleXMLElement
	 */
	public function cfgAttrLanguage($args) {
		$returnValue = '<attributes>';
		foreach($this->getTranslationService()->getVisibleLanguage() as $lang=>$langName) {
			$returnValue .= '<attribute>';
			$returnValue .= $lang.'<label>'.$langName.'</label>';
			$returnValue .= '</attribute>';
		}
		$returnValue .= '</attributes>';
		return simplexml_load_string($returnValue);
	}
	
	/**
	 * Select some elements field values and returns a SimpleXmlElement compatible with attribute expressions.<br/>
	 * FuncExp signature : <code>cfgAttrElementField(elementSource, mapElement2ValueFx, mapElement2LabelFx=null, sortOrder=1)</code><br/>
	 * Where arguments are :
	 * - Arg(0) elementSource: ElementPListDataFlowConnector|DataFlowSelector|ElementPList. An element flow source,
	 * can be an elementPList or an elementPList data flow connector or a data flow selector defining a complete flow of elements.
	 * - Arg(1) mapElement2ValueFx: FieldSelector|FuncExp. A field selector or a func exp combining several field selectors into one value which is used as the attribute value.
	 * - Arg(2) mapElement2LabelFx: FieldSelector|FuncExp. A field selector or a func exp combining several field selectors into one value which is used as the attribute label.
	 * - Arg(3) sortOrder: Int. One of 0=no sorting, keep flow order; 1=ascending; 2=descending. Default is ascending order.
	 *
	 * Example:
	 * <someContacts type="MultipleAttributs"><label>Some contacts</label>
	 * 		<attributeExp funcExp='cfgAttrElementField(elementPList(lxInGR(lxEq(fs("id"),"1234")), lf(fsl(fs("first_name"), fs("last_name")), lxGr(fs("age"), "20"))), __element.id, concat(first_name, last_name))'/>
	 * </someContacts>
	 */
	public function cfgAttrElementField($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 2) throw new FuncExpEvalException('The cfgAttrElementField function takes at least two arguments which are the element flow source and a func exp or a fieldSelector which returns the attribute value', FuncExpEvalException::INVALID_ARGUMENT);
		$cacheKey = 'cfgAttrElementField('.md5(TechnicalServiceProvider::getFieldSelectorFuncExpParser()->funcExpToString($args[0])).')';
		$elementSource = $this->evaluateArg($args[0]);
		if($nArgs > 2 && isset($args[2])) $fxCfgAttribut = fx('cfgAttribut', $args[1], null, $args[2]);
		else $fxCfgAttribut = fx('cfgAttribut', $args[1]);
		if($nArgs > 3) {
			$sortOrder = $this->evaluateArg($args[3]);
			if($sortOrder == 0) $sortOrder = 0;
			elseif($sortOrder == 1) $sortOrder = 1;
			elseif($sortOrder == 2) $sortOrder = 2;
			else throw new FuncExpEvalException("sortOrder should be one of 0=no sorting, keep flow order; 1=ascending; 2=descending", FuncExpEvalException::INVALID_ARGUMENT);
		}
		else $sortOrder = 1;

		// enables adaptive wigii namespace on principal and records original namespace
		$p = $this->getPrincipal();
		$adaptiveWigiiNamespace = $p->hasAdaptiveWigiiNamespace();
		if(!$adaptiveWigiiNamespace) {
			$origNS = $p->getWigiiNamespace();
			$p->setAdaptiveWigiiNamespace(true);
		}
		// executes dataflow
		$returnValue = $this->getDataFlowService()->processDataSource($p, $elementSource,
			dfasl(
				dfas("MapElement2ValueDFA", "setElement2ValueFuncExp", $fxCfgAttribut),
				dfas("FilterDuplicatesAndSortDFA",
					"setObjectClass", 'cfgAttribut',
					"setSortOrder", $sortOrder
				),
				dfas("CfgAttribut2XmlDFA")
			), true, null, $cacheKey);
		// binds back to original wigii namespace if needed
		if(!$adaptiveWigiiNamespace) {
			$p->bindToWigiiNamespace($origNS);
			$p->setAdaptiveWigiiNamespace(false);
		}
		return $returnValue;
	}
	
	/**
	 * Manages a drop-down as a list of tags defined by the user. The drop-down should have allowNewValues=1<br/>
	 * FuncExp signature : <code>cfgAttrTags(field, groupLogExp, logExp)</code><br/>
	 * Where arguments are :
	 * - Arg(0) field: String|FieldSelector. The field of type Attributs or MultipleAttributs on which to manage values as tags.
	 * - Arg(1) inGroupLogExp|Strings: LogExp|Atrings. Group log exp in which to search for element values or modulename (in this case takes lxInAllGroups of current namespace and module)
	 * - Arg(2) logExp: LogExp. Optional logExp used to filter the matching elements
	 * @example 
	 * <tags type="MultipleAttributs" require="0" expand="1" chosen="1" allowNewValues="1" displayAsTag="1" isInLine="1">
	 * 		<label_l01>Tags</label_l01><label_l02>Etiquettes</label_l02>
	 *		<attribute>none</attribute>
	 *		<attributeExp funcExp='cfgAttrTags(tags,lxInAllGroups("MyNamespace","MyModule")|"ModuleName")'/>						
	 * </tags>
	 * To automatically clear the cache on element save, add an onSaveFuncExp hidden field :
	 * <onSaveFuncExp excelExport="none" type="Booleans" hidden="1" clearOnCopy="1" funcExp='ctlSeq(								
	 *		ctlClearCache("cfgAttributeExp",cfgAttrTags(tags,lxInAllGroups("MyNamespace","MyModule"))), 
	 *		logTrue())' />	 
	 */
	public function cfgAttrTags($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 2) throw new FuncExpEvalException('The cfgAttrTags function takes at least two arguments which are the field and a group log exp', FuncExpEvalException::INVALID_ARGUMENT);
		$cacheKey='cfgAttrTags('.md5(fx2str($args[0]).','.fx2str($args[1]).','.($nArgs>2?fx2str($args[2]):'NULL')).')';
		
		$fs = $args[0];
		if(!($fs instanceof FieldSelector)) $fs = fs($this->evaluateArg($args[0]));		
		
		$p = $this->getPrincipal();
		
		$inGroupLogExp = $this->evaluateArg($args[1]);
		//if second args is a string, it is considered as a modulename. In this case we use the root principal
		if(is_string($inGroupLogExp)){
			$inGroupLogExp = lxInAllGroups(ServiceProvider::getExecutionService()->getCrtWigiiNamespace()->getWigiiNamespaceName(), $inGroupLogExp);
			$p = $this->getRootPrincipal();
		}
		if($nArgs>2) $logExp = $this->evaluateArg($args[2]);
		else $logExp = null;
		
		// enables adaptive wigii namespace on principal and records original namespace
		$adaptiveWigiiNamespace = $p->hasAdaptiveWigiiNamespace();
		if(!$adaptiveWigiiNamespace) {
			$origNS = $p->getWigiiNamespace();
			$p->setAdaptiveWigiiNamespace(true);
		}
		
		// executes dataflow
		$returnValue = $this->getDataFlowService()->processDataSource($p, elementPList($inGroupLogExp,lf(fsl($fs),$logExp)),
				dfasl(
					dfas("CallbackDFA", "setProcessDataChunkCallback", function($elementP,$callbackDFA) use($fs) {
						$val = $elementP->getDbEntity()->getFieldValue($fs->getFieldName());
						//lookup current xml of the field to see if there are any predefined tags.
						//in this case, those tags are removed from the search bellow to prevent duplicate options
						$attributesToFilter = array();
						if(!$callbackDFA->getValueInContext("parsingFieldXmlAttributes")){
							$fxml = $elementP->getDbEntity()->getFieldList()->getField($fs->getFieldName())->getXml();
							foreach($fxml->attribute as $key => $value){
								$attributesToFilter[(string)$value] = (string)$value;
							}
							$callbackDFA->setValueInContext("parsingFieldXmlAttributesToFilter", $attributesToFilter);
							$callbackDFA->setValueInContext("parsingFieldXmlAttributes", true);
						} else {
							$attributesToFilter = $callbackDFA->getValueInContext("parsingFieldXmlAttributesToFilter");
						}
						if(is_array($val)) {
							foreach($val as $v) {
								if(empty($attributesToFilter[$v]) && !empty($v)) $callbackDFA->writeResultToOutput($this->getFuncExpBuilder()->cfgAttribut($v));
							}
						}
						elseif(empty($attributesToFilter[$val]) && !empty($val)) $callbackDFA->writeResultToOutput($this->getFuncExpBuilder()->cfgAttribut($val));
					}),
					dfas("FilterDuplicatesAndSortDFA",
							"setObjectClass", 'cfgAttribut',
							"setSortOrder", 1
							),
					dfas("CfgAttribut2XmlDFA")
				), true, null, $cacheKey);
		// binds back to original wigii namespace if needed
		if(!$adaptiveWigiiNamespace) {
			$p->bindToWigiiNamespace($origNS);
			$p->setAdaptiveWigiiNamespace(false);
		}
		return $returnValue;
	}	

	/**
	 * Creates an IndicatorList object to be used in indicatorListExp parameter in config
	 * FuncExp signature : <code>cfgIndicatorList(indicator1:Indicator, ...)</code><br/>
	 * @return IndicatorList
	 */
	public function cfgIndicatorList($args) {
	    $returnValue = IndicatorListArrayImpl::createInstance ();
	    $nArgs = $this->getNumberOfArgs ( $args );
	    if($nArgs>0) {
    	    foreach ( $args as $arg ) {
    	        $arg = $this->evaluateArg ( $arg );
    	        if ($arg) {
    	            $returnValue->addIndicatorInstance ( $arg );
    	        }
    	    }
	    }
	    return $returnValue;
	}
	/**
	 * Creates an Indicator object to be used in indicatorListExp parameter in config
	 * FuncExp signature : <code>cfgIndicator(function:Integer, fs:FieldSelector, label:String = null, isRecursive:Boolean = false)</code><br/>
	 * function: AVG = 1, COUNT = 3, COUNT_DISTINCT = 4, MAX = 7, MIN = 8, SUM = 9
	 * @return Indicator
	 */
	public function cfgIndicator($args) {
	    $nArgs = $this->getNumberOfArgs ( $args );
	    if ($nArgs < 2) {
	        throw new FuncExpEvalException ( "cfgIndicator takes at least 2 arguments the function and the field selector", FuncExpEvalException::INVALID_ARGUMENT );
	    }
	    
	    $configS = $this->getConfigService();
	    $transS = ServiceProvider::getTranslationService();
	    $exec = ServiceProvider::getExecutionService ();
	    
	    $function = ( int ) $this->evaluateArg ( $args [0] );
	    if ($args [1] instanceof FieldSelector) {
	        $fs = $args [1];
	    } else {
	        $fs = $this->evaluateArg ( $args [1] );
	    }
	    $label = null;
	    if ($nArgs > 2) {
	        $label = $this->evaluateArg ( $args [2] );
	    }
	    //find the field in config
	    $fl = FieldListArrayImpl::createInstance ( false, true );
	    $configS->getFields ( $this->getPrincipal (), $exec->getCrtModule (), null, $fl );
	    $field = $fl->getField ( $fs->getFieldName () );
	    //define a nice label if not defined
	    if($label == null){
	        $availableFunction = Indicator::getAvailableFunctions();
	        if($fs->isElementAttributeSelector()){
	            $label = $transS->t($p, $fs->getSubFieldName());
	            $label .= " (";
	            $label .= $transS->t($p, $availableFunction[$function]);
	            $label .= ")";
	        } else {
	            $label = $transS->t($p, $field->getFieldName(), $field->getXml());
	            $label .= " (";
	            $label .= $transS->t($p, $availableFunction[$function]);
	            $label .= ")";
	        }
	    }
	    $isRecursive = false;
	    if ($nArgs > 3) {
	        $isRecursive= $this->evaluateArg ( $args [3] );
	    }
	    $returnValue = Indicator::createInstance ( $fs, $field->getDataType (), $function, $label, null, $isRecursive, true);
	    return $returnValue;
	}
	
	/**
	 * Lookups in group hierarchy for for an existing value in a given drop-down which matches the group name (or a pattern inside the group name). Returns the first possible match.
	 * FuncExp signature : <code>getDropdownValueFromGroupOrParentGroups(fieldName)</code><br/>
	 * Where arguments are :
	 * - Arg(0) fieldName: FieldSelector of the dropDown field. A Field from which to get the possible values as a drop-down.
	 * - Arg(1) separator|start: = null
	 * 				if String. optional, If defined, then use the string as a separator to explode the groupname to consider for matching. I.E =" ", and next parameter to 0 then takes the first word of the groupname to do the matching
	 * 				if INT. opitonal, If defined do the comparaison starting from this character num
	 * - Arg(2) index|len: INT = 0
	 * 				if Arg1 is String : optional, If defined, then compare the exploded value at this index
	 * 				if Arg1 is Int : optional, If defined, then takes len char after start for the comparaison
	 * @return String the current nearest value found in the group hierachy from the drop down list
	 */
	public function getDropdownValueFromGroupOrParentGroups($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException('The getDropdownValueFromGroupOrParentGroups function takes at least one argument which is the field name', FuncExpEvalException::INVALID_ARGUMENT);
		$dropdown=$this->evaluateFuncExp(fx('cfgFieldXml',$args[0]));
		if($nArgs>1) $separator=$this->evaluateArg($args[1]);
		else $separator=null;
		if($nArgs>2) $index=$this->evaluateArg($args[2]);
		else $index = 0;
		
		// builds $dropdown array for pattern matching
		$matchers=array();
		foreach($dropdown->attribute as $attribute_key => $attribute){
			$val = (string)$attribute;
			$matchers[$val]=$val;
		}
		
		$returnValue = $this->evaluateFuncExp(fx('cfgCurrentGroup','groupname',true),$this);
		// checks current group matching
		if(isset($returnValue)) {
			if($separator && is_numeric($separator)){
				$returnValue= substr($returnValue, $separator, $index);
			} else if($separator && is_string($separator)){
				$returnValue= explode($separator, $returnValue)[$index];
			}
			if(!$matchers[$returnValue]) {
				// checks parent group matching
				$returnValue = $this->evaluateFuncExp(fx('cfgParentGroup',$matchers,'groupnameMatch',true,$separator,$index),$this);
			}
		}
		return $returnValue;
	}
	
	// WigiiBPLParameter builder
	
	/**
	 * Creates a WigiiBPLParameter based on a list of pairs (key, value) or other WigiiBPLParameter instances.
	 * FuncExp signature : <code>wigiiBPLParam(k1,v1,k2,v2,p1,k3,v3,p2,p3,...)</code><br/>
	 * Where arguments are :
	 * - ki,vi: pairs of (key, value) where key ki evaluates to a string and value to any value used as a parameter,
	 * - pi: if pi evaluates to a WigiiBPLParameter instance, then adds its content
	 * @return WigiiBPLParameter
	 */
	public function wigiiBPLParam($args) {
		$returnValue = TechnicalServiceProvider::createWigiiBPLParameterInstance();
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs > 0) {
			$i = 0;
			while($i < $nArgs) {				
				$k = $this->evaluateArg($args[$i]);
				// if we have an instance of a WigiiBPLParameter, then adds it to existing one
				if($k instanceof WigiiBPLParameter) {
					$returnValue->addWigiiBPLParameter($k);
				}
				// else evaluates value and sets (key, value) pair
				else {
					$i++;
					if($i < $nArgs) {
						$v = $this->evaluateArg($args[$i]);
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
	 * Creates a WigiiBPLParameter containing a list of boolean parameters.
	 * The list of boolean parameters is created by a list of triples (key, expression, reference) which will result in a list of pairs (key, expression==reference).
	 * FuncExp signature : <code>wigiiBPLBinaryMap(k1,exp1,ref1, k2,exp2,ref2, ...)</code><br/>
	 * Where arguments are :
	 * - ki: String. The parameter name.
	 * - expi: Any scalar. The expression to be evaluated and compared to the reference.
	 * - refi: Any scalar. The reference expression to be evaluated.
	 * @return WigiiBPLParameter
	 */
	public function wigiiBPLBinaryMap($args) {
		$returnValue = TechnicalServiceProvider::createWigiiBPLParameterInstance();
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs > 0) {
			$i = 0;
			while($i < $nArgs) {				
				$k = $this->evaluateArg($args[$i]);				
				$i++;
				if($i < $nArgs) {
					$exp = $this->evaluateArg($args[$i]);
					$i++;
					if($i < $nArgs) {
						$ref = $this->evaluateArg($args[$i]);
						$i++;
					}
					else $ref = null;
					$returnValue->setValue($k, $exp==$ref);
				}
				else $returnValue->setValue($k, false);
			}
		}
		return $returnValue;
	}
	
	// WplObjectList builder
	
	/**
	 * Creates a WplObjectList based on a list of objects.
	 * FuncExp signature : <code>wplObjectList(objectName, o1,o2,o3,...)</code><br/>
	 * Where arguments are :
	 * - Arg(0) objectName: String. the logical name of the Objects stored into the list (see WplObjectList for more details)
	 * - Arg(i) oI: Record|Element|ElementP|StdClass|WplObjectList. The object to store into the list.
	 * @return WplObjectList
	 */
	public function wplObjectList($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs>0) {
			$i=0;			
			while($i<$nArgs) {
				$o=$this->evaluateArg($args[$i]);
				if($i==0) {
					if(is_scalar($o)) $returnValue = TechnicalServiceProvider::getWplToolbox()->createWplObjectList($o);
					else {
						$returnValue = TechnicalServiceProvider::getWplToolbox()->createWplObjectList();
						$returnValue->addWplObject($o);
					}
				}
				else $returnValue->addWplObject($o);
				$i++;
			}
		}
		else $returnValue = TechnicalServiceProvider::getWplToolbox()->createWplObjectList();
		return $returnValue;
	}
	
	// Accessors

	/**
	 * Returns the value of a field inside an element, given a field selector
	 * or a field name and an optional subfield name.
	 * FuncExp signature : <code>getElementValue(element, fieldName, subfieldName=null)</code><br/>
	 * Where arguments are :
	 * - Arg(0) element: Element|ElementP. Evaluates to an element.
	 * - Arg(1) fieldName: String|FieldSelector. Evaluates to a String defining the field name for which to get the element value,
	 * or can be directly a FieldSelector that can be used to select a value in the element.
	 * - Arg(2) subfieldName: String. Optional argument which evaluates to a String defining the subfield name for which to get the element value.
	 * @return Any the Element Field value.
	 */
	public function getElementValue($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 2) throw new FuncExpEvalException('The getElementValue function takes at least two arguments which are the element and a fieldName or fieldSelector', FuncExpEvalException::INVALID_ARGUMENT);
		$element = $this->evaluateArg($args[0]);
		if($element instanceof ElementP) $element = $element->getDbEntity();
		if(!$element instanceof Record) throw new FuncExpEvalException('The first argument did not evaluate to a non null instance of Element or Record', FuncExpEvalException::INVALID_ARGUMENT);
		if($args[1] instanceof FieldSelector) {
			$fieldName = $args[1]->getFieldName();
			$subFieldName = $args[1]->getSubFieldName();
			$fs = $args[1];
		}
		else {
			$fieldName = $this->evaluateArg($args[1]);
			if($nArgs > 2) $subFieldName = $this->evaluateArg($args[2]);
			else $subFieldName = null;
			$fs = fs($fieldName, $subFieldName);
		}
		if($fs->isElementAttributeSelector()) return $element->getAttribute($fs);
		else return $element->getFieldValue($fieldName, $subFieldName);
	}

	/**
	 * Returns the value of an element attribute, given its name or an element attribute field selector
	 * FuncExp signature : <code>getElementAttr(element, attrName)</code><br/>
	 * Where arguments are :
	 * - Arg(0) element: Element|ElementP. Evaluates to an element.
	 * - Arg(1) attrName: String|FieldSelector. Evaluates to a String defining the attribute name for which to get the value,
	 * or can be directly an Element attribute FieldSelector that can be used to select the attribute.
	 * @return Any the Element Attribute value.
	 */
	public function getElementAttr($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 2) throw new FuncExpEvalException('The getElementAttr function takes at least two arguments which are the element and an attribute name or fieldSelector', FuncExpEvalException::INVALID_ARGUMENT);
		$element = $this->evaluateArg($args[0]);
		if($element instanceof ElementP) $element = $element->getDbEntity();
		if(!$element instanceof Element) throw new FuncExpEvalException('The first argument did not evaluate to a non null instance of Element', FuncExpEvalException::INVALID_ARGUMENT);
		if($args[1] instanceof FieldSelector) $fse = $args[1];
		else $fse = fs_e($this->evaluateArg($args[1]));
		return $element->getAttribute($fse);
	}
	
	/**
	 * Sets the value of an element attribute, given its name or an element attribute field selector
	 * FuncExp signature : <code>setElementAttr(attrValue, element, attrName)</code><br/>
	 * Where arguments are :
	 * - Arg(0) attrValue: Any. The value to set to the element attribute.
	 * - Arg(0) element: Element|ElementP. Evaluates to an element.
	 * - Arg(1) attrName: String|FieldSelector. Evaluates to a String defining the attribute name for which to set the value,
	 * or can be directly an Element attribute FieldSelector that can be used to set the attribute.
	 * If attrName does not match any element attributes, then creates a dynamic mutable element attribute with the given name.
	 */
	public function setElementAttr($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 3) throw new FuncExpEvalException('The setElementAttr function takes at least three arguments which are the attribute value, the element and an attribute name or fieldSelector', FuncExpEvalException::INVALID_ARGUMENT);
		$attrVal = $this->evaluateArg($args[0]);
		$element = $this->evaluateArg($args[1]);
		if($element instanceof ElementP) $element = $element->getDbEntity();
		if(!$element instanceof Element) throw new FuncExpEvalException('The first argument did not evaluate to a non null instance of Element', FuncExpEvalException::INVALID_ARGUMENT);
		if($args[2] instanceof FieldSelector) $fse = $args[2];
		else $fse = fs_e($this->evaluateArg($args[2]));
		try { $element->setAttribute($attrVal,$fse); }
		catch(Exception $e) {
			// if no dynamic attribute is defined, then creates one
			if(is_null($element->getDynamicAttribute($fse->getSubFieldName()))) {
				$element->setDynamicAttribute($fse->getSubFieldName(), ElementDynAttrMutableValueImpl::createInstance($attrVal));
			}
			else throw $e;
		}
	}
	
	// Selectors
	
	/**
	 * Counts the number of elements matching a FieldSelector LogExp into a given search space
	 * FuncExp signature : <code>countElements(logExp=null,groupId=null)</code><br/>
	 * Where arguments are :
	 * - Arg(0) logExp: LogExp. Evaluates to FieldSelector LogExp used to filter the Elements to count.
	 * - Arg(1) groupId: Int|LogExp. The ID of the Group from which to search for Elements (includes group and subgroups) or a group selection LogExp. If not defined, takes current group.	 
	 * @return Int the number of matching elements or 0 if none.
	 */
	public function countElements($args) {
		$nArgs = $this->getNumberOfArgs($args);
		$logExp=null;
		if($nArgs>0) $logExp=$this->evaluateArg($args[0]);
		if($nArgs>1) $groupId=$this->evaluateArg($args[1]);
		else $groupId = $this->evaluateFuncExp(fx('cfgCurrentGroup', 'id'));
		if(!($groupId instanceof LogExp)){
			$groupId = lxEq(fs('id'),$groupId);
		}
		$returnValue = $this->getElementService()->countSelectedElementsInGroups($this->getPrincipal(), lxInGR($groupId), (isset($logExp)?lf(null,$logExp):null));
		if($returnValue>0) return $returnValue;
		else return 0;
	}
	
	/**
	 * Checks if some elements matching a FieldSelector LogExp into a given search space exist
	 * FuncExp signature : <code>elementExists(logExp=null,groupId=null)</code><br/>
	 * Where arguments are :
	 * - Arg(0) logExp: LogExp. Evaluates to FieldSelector LogExp used to filter the Elements to check for existance.
	 * - Arg(1) groupId: Int|LogExp. The ID of the Group from which to search for Elements (includes group and subgroups) or a group selection LogExp. If not defined, takes current group.
	 * @return Boolean true if at least one element matches the criterias, else false.
	 */
	public function elementExists($args) {
		return ($this->evaluateFuncExp(fx('countElements',$args),$this)>0);
	}
	
	/**
	 * Reads an Element state info and returns the corresponding array, 
	 * or creates an array compatible with Element state info format given a message and returns it as a serialized string.
	 * FuncExp signature 1 : <code>elementStateInfo(elementStateInfoSelector,element)</code><br/>	 
	 * Where arguments are :
	 * - Arg(0) elementStateInfoSelector: FieldSelector. A FieldSelector of type element attribute pointing to a valid Element state info (approved, locked, etc.)
	 * One of state_lockedInfo, state_important1Info, state_important2Info, state_hiddenInfo, state_archivedInfo, state_deprecatedInfo, state_finalizedInfo, state_approvedInfo, state_dismissedInfo, state_blockedInfo
	 * - Arg(1) element: Element. Element instance from which to retrieve the Element state info
	 * FuncExp signature 2 : <code>elementStateInfo(msg)</code><br/>
	 * Where arguments are :
	 * - Arg(0) msg: String. Optional message to put into the Element State info array.
	 * @return Array | String
	 */
	public function elementStateInfo($args) {
	    $nArgs = $this->getNumberOfArgs($args);
	    
	    // if signature 1: return state info as array
	    if($nArgs>=2 && ($args[0] instanceof FieldSelector) && $args[0]->isElementAttributeSelector()) {
	        $element = $this->evaluateArg($args[1]);
	        if(!($element instanceof Element)) throw new FuncExpEvalException("elementStateInfo needs a non null Element");
	        $returnValue = $element->getAttribute($args[0]);
	        if(!empty($returnValue)) $returnValue = str2array($returnValue);
	        else $returnValue = null;	            
	    }
	    // else if signature 2: creates state info array
	    else {
	        if($nArgs>1) $msg = $this->evaluateArg($args[0]);
	        else $msg = null;
	        
	        $returnValue = array();
	        $principal = $this->getPrincipal();
	        if($principal->getRealUser()){
	            $returnValue["realUsername"] = $principal->getRealUser()->getUsername();
	            $returnValue["realUserWigiiNamespace"] = $principal->getRealUser()->getWigiiNamespace()->getWigiiNamespaceName();
	            $returnValue["realUserId"] = $principal->getRealUser()->getId();
	        }
	        if($principal->getAttachedUser()){
	            $returnValue["username"] = $principal->getAttachedUser()->getUsername();
	            $returnValue["userWigiiNamespace"] = $principal->getAttachedUser()->getWigiiNamespace()->getWigiiNamespaceName();
	            $returnValue["userId"] = $principal->getAttachedUser()->getId();
	        }
	        $returnValue["timestamp"] = time();
	        $returnValue["message"] = $msg;
	        $returnValue = array2str($returnValue);
	    }
	    return $returnValue;
	}
	
	/**
	 * Returns the group in which is contained the element. If the element is in several groups, takes first writable group or first readable group.
	 * FuncExp signature : <code>cfgElementGroup(element,returnAttribute=id|groupname|group)</code><br/>
	 * Where arguments are :
	 * - Arg(0) element: Element. 
	 * - Arg(1) returnAttribute: String. The name of the group attribute to return. Defaults to id. If 'group' then returns Group object.
	 * @return Int|String|Group returns null if not found
	 */
	public function cfgElementGroup($args) {
	    $this->debugLogger()->logBeginOperation('cfgElementGroup');
	    $nArgs = $this->getNumberOfArgs($args);
	    if($nArgs < 1) throw new FuncExpEvalException('The cfgElementGroup function takes at least one argument which is the element for which to retrieve groups', FuncExpEvalException::INVALID_ARGUMENT);
	    $element = $this->evaluateArg($args[0]);
	    $returnAttribute = 'id';
	    if($nArgs > 1) $returnAttribute = $this->evaluateArg($args[1]);
	    
	    $returnValue = GroupListAdvancedImpl::createInstance();
	    if($this->getElementService()->getAllGroupsContainingElement($this->getPrincipal(), $element, $returnValue) > 0) {
	        if($returnValue->count() > 1) {
	            if($returnValue->getWriteGroups()->count() > 0) $returnValue = reset($returnValue->getWriteGroups()->getListIterator());
	            else if($returnValue->getSGroups()->count() > 0) $returnValue = reset($returnValue->getSGroups()->getListIterator());
	            else $returnValue = reset($returnValue->getReadGroups()->getListIterator());
	        }
	        else $returnValue = reset($returnValue->getListIterator());
	    }
	    else $returnValue = null; 
	    
	    $this->debugLogger()->logEndOperation('cfgElementGroup');
	    if(isset($returnValue)) {
	        if($returnAttribute == 'group') return $returnValue;
	        else return $returnValue->getAttribute($returnAttribute);
	    }
	}
	
	/**
	 * Returns the ID of the trashbin associated to the given module or element.
	 * FuncExp signature : <code>cfgTrashbinGroup(module)</code><br/>
	 * Where arguments are :
	 * - Arg(0) forModule: Module|Element. Optional. A module for which to get the trashbin group ID or an element for which to find the associated trashbin. If not given, takes the current module.	 
	 * @return Int returns the trashbin ID if defined else null
	 */
	public function cfgTrashbinGroup($args) {	    
	    $nArgs = $this->getNumberOfArgs($args);
	    if($nArgs>0) $forModule = $this->evaluateArg($args[0]);
	    else $forModule = null;
	    return $this->getWigiiBPL()->groupGetTrashbin($this->getPrincipal(), $this, wigiiBPLParam(
	       "forModule",$forModule,
	       "includeSubGroups", false,
	       "returnAttribute", "id"
	    ));
	}
	/**
	 * Returns an array of group ids delimiting the trashbin space. It contains the trashbin group ID and all sub-folders IDs.
	 * FuncExp signature : <code>cfgTrashbinGroups(module)</code><br/>
	 * Where arguments are :
	 * - Arg(0) forModule: Module|Element. Optional. A module for which to get the trashbin group ID or an element for which to find the associated trashbin. If not given takes the current module.
	 * @return Array returns an array of group IDs or null if no defined trashbin.
	 */
	public function cfgTrashbinGroups($args) {
	    $nArgs = $this->getNumberOfArgs($args);
	    if($nArgs>0) $forModule = $this->evaluateArg($args[0]);
	    else $forModule = null;
	    return $this->getWigiiBPL()->groupGetTrashbin($this->getPrincipal(), $this, wigiiBPLParam(
	        "forModule",$forModule,
	        "includeSubGroups", true,
	        "returnAttribute", "id"
	    ));
	}
	
	/**
	 * Builds a URL which is compatible with the Wigii find functionality.<br/>
	 * FuncExp signature : <code>findUrl(searchSpace,searchQueryLogExp)</code><br/>
	 *  Where arguments are :
	 * - Arg(0) searchSpace: LogExp|ElementPListDataFlowConnector. The search space. Can be a group selector LogExp (like for instance lxAllGroups("namespace","module")) or an ElementPListDataFlowConnector instance (created with elementPList function)
	 * - Arg(1) searchQueryLogExp: LogExp. The where clause to apply on the search space to filter the elements (combined with any ListFilter already given in the search space)
	 *  @return String a Wigii find url of the form crtWigiiNamespace/crtModule/find/base64searchspace/base64searchquery/filter
	 */
	public function findUrl($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 2) throw new FuncExpEvalException('The findUrl function takes at least two arguments which are the searchSpace and the searchQueryLogExp', FuncExpEvalException::INVALID_ARGUMENT);
		$searchSpace = $this->evaluateArg($args[0]);
		// wraps group selector LogExp into ElementPListDataFlowConnector
		if($searchSpace instanceof LogExp) {
			$searchSpace = elementPList(lxInGR($searchSpace));
			$searchSpace = $searchSpace->toFx();
		}
		// else keeps given Fx
		else $searchSpace = $args[0];
		$searchSpace = base64url_encode(fx2str($searchSpace));
		// prepares search query log exp
		$searchQueryLogExp = $this->evaluateFuncExp(fx('obj2base64url',$args[1]));
		// return find url
		$exec = ServiceProvider::getExecutionService();
		return $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl().'/'.
				$exec->getCrtModule()->getModuleUrl().'/find/'.
				$searchSpace.'/'.
				$searchQueryLogExp.'/filter';
	}
	
	// Wigii Administration
	
	/**
	 * Creates or updates roles in a given list of namespaces, using a generic role name and an optional list of divisions.
	 * See method WigiiBPL::adminCreateRoleByWigiiNamespace
	 * FuncExp signature : <code>adminCreateRoleByWigiiNamespace(wigiiBPLParam)</code><br/>
	 * Where arguments are :
	 * - Arg(0) wigiiBPLParam: WigiiBPLParameter. The Wigii business process parameters as defined in the WigiiBPL::adminCreateRoleByWigiiNamespace method.
	 * 
	 * This function cannot be called from public space (i.e. caller is located outside of the Wigii instance)
	 */
	public function adminCreateRoleByWigiiNamespace($args) {
		$this->assertFxOriginIsNotPublic();
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException('The adminCreateRoleByWigiiNamespace function takes at least one argument which is the WigiiBPLParameter', FuncExpEvalException::INVALID_ARGUMENT);
		$parameter = $this->evaluateArg($args[0]);
		
		$executionSink = $this->getFuncExpVMServiceProvider()->getExecutionSink();
		$wigiiBPL = $this->getWigiiBPL();
		$principal = $this->getPrincipal();
		
		// gets accessible WigiiNamespaces and Modules
		$wigiiNamespacesAndModules = $wigiiBPL->adminGetPrincipalNamespacesAndModules($principal, $this, null, $executionSink);
		// filter requested WigiiNamespaces
		$requestedWigiiNamespaces = $parameter->getValue('wigiiNamespaces');
		if(empty($requestedWigiiNamespaces)) throw new FuncExpEvalException('wigiiNamespaces cannot be empty', FuncExpEvalException::INVALID_ARGUMENT);
		if(!is_array($requestedWigiiNamespaces)) $requestedWigiiNamespaces = array($requestedWigiiNamespaces => $requestedWigiiNamespaces);
		$wigiiNamespaces = array();
		foreach($requestedWigiiNamespaces as $wigiiNamespaceName) {
			if($wigiiNamespaceName == WigiiNamespace::EMPTY_NAMESPACE_URL) $wigiiNamespaceName = WigiiNamespace::EMPTY_NAMESPACE_NAME;
			if(isset($wigiiNamespacesAndModules[$wigiiNamespaceName])) $wigiiNamespaces[$wigiiNamespaceName] = $wigiiNamespaceName;
		}
		
		// filter requested Modules
		$requestedModulesByWigiiNamespace = $parameter->getValue('modulesByWigiiNamespace');
		if(empty($requestedModulesByWigiiNamespace)) $requestedModulesByWigiiNamespace = array();
		elseif(!is_array($requestedModulesByWigiiNamespace)) throw new FuncExpEvalException('modulesByWigiiNamespace should be an array of the form (wigiiNamespaceName=>array(moduleName))', FuncExpEvalException::INVALID_ARGUMENT);
		$modulesByWigiiNamespace = array();
		foreach($wigiiNamespaces as $wigiiNamespaceName) {
			$modules = $wigiiNamespacesAndModules[$wigiiNamespaceName];
			$requestedModules = $requestedModulesByWigiiNamespace[$wigiiNamespaceName];
			if(empty($requestedModules) && $wigiiNamespaceName == WigiiNamespace::EMPTY_NAMESPACE_NAME) $requestedModules = $requestedModulesByWigiiNamespace[WigiiNamespace::EMPTY_NAMESPACE_URL];
			if(!empty($requestedModules)) {
				$requestedModules = array_combine($requestedModules, $requestedModules);
				$modules = array_intersect_key($modules, $requestedModules);
			}
			$modulesByWigiiNamespace[$wigiiNamespaceName] = $modules;
		}
		
		$parameter->setValue('wigiiNamespaces', $wigiiNamespaces);
		$parameter->setValue('modulesByWigiiNamespace', $modulesByWigiiNamespace);
		
		// calls WigiiBPL->adminCreateRoleByWigiiNamespace
		//if(isset($executionSink)) $executionSink->log($parameter->displayDebug());
		$wigiiBPL->adminCreateRoleByWigiiNamespace($principal, $this, $parameter, $executionSink);
	}
	
	/**
	 * Returns true if public login is enabled for the current client
	 * FuncExp signature : <code>adminIsPublicAccessEnabled()</code>
	 * 
	 * This function cannot be called from public space (i.e. caller is located outside of the Wigii instance)
	 * @return boolean
	 */
	public function adminIsPublicAccessEnabled($args) {
		$this->assertFxOriginIsNotPublic();
		$authS = $this->getAuthenticationService();
		if($authS instanceof AuthenticationServiceWebImpl) {
			return $authS->isPublicAccessEnabledForClient($this->getPrincipal()->getWigiiNamespace()->getClient()->getClientName());
		}
		else return false;
	}
	
	/**
	 * Validates an array of usernames against the Users stored in the database and returns any users missing.
	 * FuncExp signature : <code>adminFindMissingUsernames(usernames)</code>	
	 * Where arguments are :
	 * - Arg(0) usernames: Array. An array of usernames to check.
	 * This function cannot be called from public space (i.e. caller is located outside of the Wigii instance)
	 * @return Array the list of usernames given as argument, but which are not declared in the database
	 */
	public function adminFindMissingUsernames($args) {	    
	    $this->assertFxOriginIsNotPublic();
	    $nArgs = $this->getNumberOfArgs($args);
	    if($nArgs<1) throw new FuncExpEvalException('adminFindMissingUsernames takes one parameter which is a non empty array of usernames');
	    $usernames = $this->evaluateArg($args[0]);
	    if(empty($usernames)) throw new FuncExpEvalException('usernames should be a non empty array');
	    if(!is_array($usernames)) $usernames = array($usernames);
	    $mySqlF = TechnicalServiceProvider::getMySqlFacade();
	    $sqlB = $mySqlF->getSqlBuilder();
	    $selectedUsernames = ValueListArrayImpl::createInstance();
	    $mySqlF->selectAll($this->getPrincipal(),
	        'select username from Users where '.$sqlB->formatBinExp('username', 'IN', $usernames, MySqlQueryBuilder::SQLTYPE_VARCHAR),
	        ServiceProvider::getDbAdminService()->getDbConnectionSettings($this->getPrincipal()),
	        ValueListMapper::createInstance($selectedUsernames, 'username')
	        );
	    return array_diff($usernames, $selectedUsernames->getListIterator());
	}	
	
	/**
	 * Given a group ID, returns all the users who have access to a given folder with their respective rights.
	 * FuncExp signature : <code>adminAccessToFolder(groupId, asStdClass=false)</code><br/>
	 * Where arguments are :
	 * - Arg(0) groupId: Int. The group ID on which to retrieve user access rights
	 * - Arg(1) asStdClass: Boolean. If true, then fills a ValueList with StdClass instances of the form {username:User name, accessRight: access right letter}, else formats the result as a string.
	 * @return String|ValueList a multiple-line list with the format Username: AccessRightLetter\n or a ValueList with StdClass instances of the form {username:User name, accessRight: access right letter}
	 */
	public function adminAccessToFolder($args) {
	    $nArgs = $this->getNumberOfArgs($args);
	    
	    if($nArgs<1) throw new FuncExpEvalException('adminAccessToFolder takes at least one argument which is the groupId');
	    $groupId = $this->evaluateArg($args[0]);
	    if($nArgs>1) $asStdClass = ($this->evaluateArg($args[1])==true);
	    else $asStdClass = false;
	    
	    $p = $this->getPrincipal();
	    $groupAS = ServiceProvider::getGroupAdminService();
	    $group = $groupAS->getGroupWithoutDetail($p, $groupId);
	    
	    // switches principal to Admin role
	    $currentRoleId = $p->getUserId();
	    $userRPList = UserRPListArrayImpl::createInstance();
	    try {
	        foreach($p->getRoleListener()->getAdminRoleIds() as $adminId) {
	            $adminRole = $p->getRoleListener()->getUser($adminId);
	            // finds first super admin role
	            if($adminRole->getDetail()->isWigiiNamespaceCreator()) {
	                $p->bindToRole($adminId);
	                break;
	            }
	            // or first admin role accessing all users rights
	            if($adminRole->getDetail()->isReadAllUsersInWigiiNamespace() && 
	                $p->getWigiiNamespace() === $adminRole->getWigiiNamespace() && 
	                $adminRole->getDetail()->getModuleAccess($group->getModule())) {
	                $p->bindToRole($adminId);
	                break;
	            }
	        }
	        // Fetches users
	        $lf = lf(null,null,fskl(fsk('username')));
	        $groupAS->getAllUsers($p, $groupId, $userRPList, $lf, true);
	        
	        // Cache of real users
	        $realUsers = array();
	        $userAS = ServiceProvider::getUserAdminService();
	        $userAllocation = UserListArrayImpl::createInstance();
	        // Dumps user list with access rights
	        $returnValue = '';
	        foreach($userRPList->getListIterator() as $userRP) {
	            $user = $userRP->getUserR()->getUser();
	            $userRight = $userRP->getUserR()->getRights()->getLetter();
	            // records role or direct user rights, but skips expired users
	            // 1. records highest access rights of non-calculated roles
	            // 2. user is a real user => he is not expired.
	            if($userRight > $realUsers[$user->getUsername()] && !$user->isCalculatedRole() &&
	                ($user->isRole() || !($user->getDetail() && $user->getDetail()->passwordExpired()))) {
	                    $realUsers[$user->getUsername()] = $userRight;
	                }
	                // if is role then checks for role allocation
	                if($user->isRole()) {
	                    $userAllocation->reset();
	                    if($userAS->getAllRoleUsers($p, $user->getId(),$userAllocation,$lf) > 0) {
	                        foreach($userAllocation->getListIterator() as $realUser) {
	                            // keeps only non-expired users and records highest access rights
	                            if(!($realUser->getDetail() && $realUser->getDetail()->passwordExpired()) &&
	                                ($userRight > $realUsers[$realUser->getUsername()])) {
	                                    $realUsers[$realUser->getUsername()] = $userRight;
	                                }
	                        }
	                    }
	                }
	        }
	        if(!empty($realUsers)) {
	            ksort($realUsers);
	            if($asStdClass) $returnValue = ValueListArrayImpl::createInstance();
	            foreach($realUsers as $realUsername => $userRight) {
	                if($asStdClass) {
	                    $returnValue->addValue((object)array('username'=>$realUsername,'accessRight'=>$userRight));
	                }
	                else {
    	                $returnValue .= $realUsername;
    	                $returnValue .= ': ';
    	                $returnValue .= $userRight;
    	                $returnValue .= "\n";
	                }
	            }
	        }
	        
	        $p->bindToRole($currentRoleId);
	    }
	    catch(Exception $e) {
	        $p->bindToRole($currentRoleId);
	        throw $e;
	    }
	    return $returnValue;
	}
	
	// Control Flow
	
	/**
	 * Clears session or calculated cache given the cache type and an option cache key.
	 * FuncExp signature : <code>clearCache(type,key)</code><br/>
	 * Where arguments are :
	 * - Arg(0) type: String. The type of cache to be cleared. One of :
	 *   'config': clears the user session config cache
	 *   'sharedData': clears the DB shared data cache (e.g. calculated drop-downs or calculated config)
	 *   'role': clears user calculated roles. Postcondition: principal is bound back to real user.
	 *   'cfgAttributeExp': clears one specific calculated drop-down
	 *   'cfgAttrDimension': clears one specific dimension in Setup namespace. Key is mandatory and should be the dimension folder name.
	 *   'cfgAttrDimensionCrtNamespace': clears one specific dimension in current namespace. Key is mandatory and should be the dimension folder name.
	 * - Arg(1) key: String|FuncExp. Optional cache key name if supported by the cache type.
	 * - Arg(2) cacheTokens: String|Array Optional cacheTokens to add to the cache key if supported by the cache type, this argument is always evaluated
	 * If cache type is 'cfgAttributeExp', then the key is mandatory and has to be the AttributeExp FuncExp used to populate the drop-down.
	 * This function cannot be called from public space (i.e. caller is located outside of the Wigii instance)
	 */
	public function ctlClearCache($args) {
		$this->assertFxOriginIsNotPublic();
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs<1) throw new FuncExpEvalException('The ctlClearCache takes at least one argument which is the cache type', FuncExpEvalException::INVALID_ARGUMENT);
		$type=$this->evaluateArg($args[0]);
		switch($type) {
			case 'config':
				$configS = $this->getConfigService();
				if(method_exists($configS, 'clearSessionData')) $configS->clearSessionData();
				ServiceProvider::clearSessionDataOfSubElementConfigService();
				break;
			case 'sharedData':
				$configS = $this->getConfigService();
				if(method_exists($configS, 'clearSessionData')) $configS->clearSessionData();
				ServiceProvider::clearSessionDataOfSubElementConfigService();
				$this->getSessionAdminService()->clearAllSharedData();
				break;
			case 'role':
				$configS = $this->getConfigService();
				$userAS = $this->getUserAdminService();
				if(method_exists($configS, 'clearSessionData')) $configS->clearSessionData();
				ServiceProvider::clearSessionDataOfSubElementConfigService();
				$p=$this->getPrincipal();
				$userAS->calculateAllMergedRoles($p);
				$defaultWigiiNamespace = (string)$this->getConfigService()->getParameter($p, null, "defaultWigiiNamespace");
				if(!$defaultWigiiNamespace) $defaultWigiiNamespace = $p->getRealWigiiNamespace()->getWigiiNamespaceUrl();
				$p->refetchAllRoles($userAS->getListFilterForNavigationBar(), UserListForNavigationBarImpl::createInstance($defaultWigiiNamespace));
				break;
			case 'cfgAttributeExp':
			case 'cfgAttrDimension':
			case 'cfgAttrDimensionCrtNamespace':
				if($nArgs<2) throw new FuncExpEvalException("If cache type is 'cfgAttributeExp' then cache is mandatory and should be the AttributeExp FuncExp used to populate the drop-down.", FuncExpEvalException::INVALID_ARGUMENT);
				if($type=='cfgAttributeExp') {
					if($args[1] instanceof FuncExp) $key=fx2str($args[1]);
					else $key=$this->evaluateArg($args[1]);
				}
				else $key = fx2str(fx($type,$this->evaluateArg($args[1])));
				$cacheTokens = "";
				if($args[2]){
					$cacheTokens=$this->evaluateArg($args[2]);
				}
				if(!is_array($cacheTokens)){
					$cacheTokens = [$cacheTokens];
				}
				$this->debugLogger()->write('ctlClearCache cfgAttributeExp '.$key);
				$key = "AttributeExpConfigController_".md5($key);
				foreach($cacheTokens as $cacheToken){
					if($cacheToken!==null && $cacheToken !== ""){
						$this->debugLogger()->write('cacheToken : '.$cacheToken);
						$keyAndToken = $key."_".md5($cacheToken);
					} else $keyAndToken = $key;
					$this->debugLogger()->write('key and Token after md5 : '.$keyAndToken);
					$this->getSessionAdminService()->clearDataKey($keyAndToken);
				}
				break;
			default: throw new FuncExpEvalException((empty($type)?'Cache type cannot be null.':"Cache type '".$type."' is not supported. Cache type should be one of 'session','config','sharedData','role' or 'cfgAttributeExp'"), FuncExpEvalException::INVALID_ARGUMENT);
		}
	}
	
	/**
	 * Returns true if current principal is a Public principal used in the scope of subscriptions or external access
	 * FuncExp signature : <code>ctlIsInPublic()</code><br/>
	 * @return Boolean true if current principal is a public principal else false.
	 */
	public function ctlIsInPublic($args) {
	    return $this->getAuthorizationService()->isPublicPrincipal($this->getPrincipal());
	}
	
	// System functions
	
	/**
	 * Evaluates the first argument as a string and logs its result into a file namespace_module.log in the client config folder
	 * FuncExp signature : <code>log(message)</code><br/>
	 * Where arguments are :
	 * - Arg(0) message: String. The message to log.
	 * 
	 * This function cannot be called from public space (i.e. caller is located outside of the Wigii instance)
	 */
	public function sysLog($args) {
		$this->assertFxOriginIsNotPublic();
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException('The sysLog function takes at least one argument which is the message', FuncExpEvalException::INVALID_ARGUMENT);
	
		$message = $this->evaluateArg($args[0]);
	
		$exec = ServiceProvider::getExecutionService();
		$crtModule = $exec->getCrtModule();
		if(isset($crtModule)) {
			$crtModuleUrl = $crtModule->getModuleUrl();
		}
		else $crtModuleUrl = Module::EMPTY_MODULE_URL;
		$crtWigiiNamespace = $exec->getCrtWigiiNamespace();
		if(isset($crtWigiiNamespace)) {
			$crtWigiiNamespaceUrl = $crtWigiiNamespace->getWigiiNamespaceUrl();
		}
		else $crtWigiiNamespaceUrl = WigiiNamespace::EMPTY_NAMESPACE_URL;
	
		$p = $this->getPrincipal();
		$message = udate('Y-m-d H:i:s:u').' '.$p->getUsername().': '.private_put($message, NULL, FALSE, 0, true)."\n";
	
		$fileName = $crtWigiiNamespaceUrl.'_'.$crtModuleUrl.'.log';
		$file = fopen(ServiceProvider::getConfigService()->getClientConfigFolderPath($p).$fileName, 'a');
		if(!$file) throw new FuncExpEvalException("Could not open log file $fileName in client config folder.", FuncExpEvalException::UNEXPECTED_ERROR);
		fwrite($file, $message);
		fclose($file);
	}
	
	/**
	 * Returns the complete http url to access the Wigii web site root
	 * FuncExp signature : <code>sysSiteRootUrl(area=null|'forFile'|'forClient')</code><br/>
	 * Where arguments are :
	 * - Arg(0) area: String. The site area type needed to be accessed.
	 * If 'forFile' then gives root url to access static content,
	 * elseif 'forClient' then gives root url to access to current CLIENT static content
	 * if null then gives access to site root. 
	 * 
	 * This function cannot be called from public space (i.e. caller is located outside of the Wigii instance)
	 * @return String
	 */
	public function sysSiteRootUrl($args) {
		$this->assertFxOriginIsNotPublic();
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs>0) $area = $this->evaluateArg($args[0]);
		else $area = '';
		$returnValue = '';
		switch($area) {
			case 'forFile': 
				if(defined("SITE_ROOT_forFileUrl")) $returnValue=SITE_ROOT_forFileUrl;
				break;
			case 'forClient':
				if(defined("SITE_ROOT_forFileUrl")) $returnValue=SITE_ROOT_forFileUrl;
				if(defined("CLIENT_WEB_URL")) $returnValue.=CLIENT_WEB_URL;
				break;
			default:
				if(defined("SITE_ROOT")) $returnValue=SITE_ROOT;
		}	
		return $returnValue;
	}	
	
	/**
	 * Returns current request parameter
	 * FuncExp signature : <code>sysExecParameter(index)</code><br/>
	 * Where arguments are :
	 * - Arg(0) index: Int|FieldSelector|String. The parameter index 0..n, 
	 * or a key name passed on the url in the form of /key=value/. Returns the first found key value on the url, or null if not found.
	 * Key can be either a string or a FieldSelector
	 * @return String|Array if index is specified, returns the parameter value, else returns an array with all parameters.
	 */
	public function sysExecParameter($args) {
		$nArgs = $this->getNumberOfArgs($args);		
		if($nArgs>0) {
			$index = $args[0];
			if(!($index instanceof FieldSelector)) $index = $this->evaluateArg($args[0]);
			if(is_numeric($index)) return ServiceProvider::getExecutionService()->getCrtParameters($index);
			else {
				if(!($index instanceof FieldSelector)) $index = fs($index);
				if(is_null($index->getSubFieldName())) $index->setSubFieldName('value');				
				// Searches for url parameter given the field name
				$params = ServiceProvider::getExecutionService()->getCrtParameters();
				foreach($params as $fieldDefault){
					list($fieldname, $value) = explode("=", $fieldDefault);
					list($fieldname, $subfieldname) = explode(".", $fieldname);
					if(is_null($subfieldname)) $subfieldname='value';
					// returns value if matching parameter
					if($fieldname==$index->getFieldName() && $subfieldname==$index->getSubFieldName()) return $value;
				}
				return null;
			}
		}
		else return ServiceProvider::getExecutionService()->getCrtParameters();
	}
	
	/**
	 * Returns current WigiiNamespace URL
	 * FuncExp signature : <code>sysCrtWigiiNamespace(returnAttribute=url|name|object)</code><br/>
	 * Where arguments are :
	 * - Arg(0) returnAttribute: String. The name of the attribute to return. If object returns the WigiiNamespace instance.
	 * Defaults to url which returns the WigiiNamespace url.
	 * 
	 * This function cannot be called from public space (i.e. caller is located outside of the Wigii instance)
	 * @return String|WigiiNamespace the current WigiiNamespace attribute or object
	 */
	public function sysCrtWigiiNamespace($args) {
		$this->assertFxOriginIsNotPublic();
		$nArgs=$this->getNumberOfArgs($args);
		if($nArgs>0) $returnAttribute=$this->evaluateArg($args[0]);
		else $returnAttribute='url';
		
		$currentWigiiNamespace = ServiceProvider::getExecutionService()->getCrtWigiiNamespace();
		$returnValue = null;		
		switch($returnAttribute) {
			case 'url': 
				if(isset($currentWigiiNamespace)) $returnValue = $currentWigiiNamespace->getWigiiNamespaceUrl();
				else $returnValue = WigiiNamespace::EMPTY_NAMESPACE_URL;
				break;
			case 'name':
				if(isset($currentWigiiNamespace)) $returnValue = $currentWigiiNamespace->getWigiiNamespaceName();
				else $returnValue = WigiiNamespace::EMPTY_NAMESPACE_NAME;
				break;
			case 'object':
				$returnValue = $currentWigiiNamespace;				
				break;
			default: throw new FuncExpEvalException("invalid return attribute '$returnAttribute', should be one of url, name or object", FuncExpEvalException::INVALID_ARGUMENT);
		}
		return $returnValue;
	}
	
	/**
	 * Returns current Module URL
	 * FuncExp signature : <code>sysCrtModule(returnAttribute=url|name|object)</code><br/>
	 * Where arguments are :
	 * - Arg(0) returnAttribute: String. The name of the attribute to return. If object returns the Module instance.
	 * Defaults to url which returns the Module url.
	 * 
	 * This function cannot be called from public space (i.e. caller is located outside of the Wigii instance)
	 * @return String|Module the current Module attribute or object
	 */
	public function sysCrtModule($args) {
		$this->assertFxOriginIsNotPublic();
		$nArgs=$this->getNumberOfArgs($args);
		if($nArgs>0) $returnAttribute=$this->evaluateArg($args[0]);
		else $returnAttribute='url';
		
		$currentModule = ServiceProvider::getExecutionService()->getCrtModule();
		$returnValue = null;		
		switch($returnAttribute) {
			case 'url': 
				if(isset($currentModule)) $returnValue = $currentModule->getModuleUrl();
				else $returnValue = Module::EMPTY_MODULE_URL;
				break;
			case 'name':
				if(isset($currentModule)) $returnValue = $currentModule->getModuleName();
				else $returnValue = Module::EMPTY_MODULE_NAME;
				break;
			case 'object':
				$returnValue = $currentModule;				
				break;
			default: throw new FuncExpEvalException("invalid return attribute '$returnAttribute', should be one of url, name or object", FuncExpEvalException::INVALID_ARGUMENT);
		}
		return $returnValue;
	}
	
	/**
	 * Returns the ID Answer of the current request beeing processed
	 * FuncExp signature : <code>sysCrtIdAnswer()</code><br/>
	 *
	 * This function cannot be called from public space (i.e. caller is located outside of the Wigii instance)
	 * @return String the ExecutionService ID Answer
	 */
	public function sysCrtIdAnswer($args) {
	    $this->assertFxOriginIsNotPublic();
	    return ServiceProvider::getExecutionService()->getIdAnswer();
	}
	
	/**
	 * Queues some waiting requests to be executed on server side.
	 * FuncExp signature : <code>sysAddRequests(url1, url2, ...)</code><br/>
	 * Where arguments are :
	 * - Arg(0..n) urlN: String|Array. A Wigii URL ready to be executed or an array of urls.
	 * 
	 * This function cannot be called from public space (i.e. caller is located outside of the Wigii instance)
	 * @return String the ExecutionService ID Answer
	 */
	public function sysAddRequests($args) {
	    $this->assertFxOriginIsNotPublic();
	    $nArgs = $this->getNumberOfArgs($args);	    
	    if($nArgs > 0) {
	        $exec = ServiceProvider::getExecutionService();
    	    for($i = 0; $i < $nArgs; $i++) {
    	        $url = $this->evaluateArg($args[$i]);
    	        if(!empty($url)) {    	            
    	            if(is_array($url)) {
    	                foreach($url as $subUrl) {
    	                    $exec->addRequests($subUrl);
    	                }
    	            }
    	            else {
    	                $exec->addRequests($url);
    	            }
    	            if($_POST["action"] != null) unset($_POST["action"]); 
    	        }
    	    }
	    }
	}
	
	/**
	 * Queues some js code to be executed on client side when sent back.
	 * FuncExp signature : <code>sysAddJsCode(jsCode)</code><br/>
	 * Where arguments are :
	 * - Arg(0) jsCode: String. Some valid js code to be executed on client side
	 *
	 * This function cannot be called from public space (i.e. caller is located outside of the Wigii instance)
	 * @return String the ExecutionService ID Answer
	 */
	public function sysAddJsCode($args) {
		$this->assertFxOriginIsNotPublic();
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs > 0) {
			$exec = ServiceProvider::getExecutionService();
			$exec->addJsCode($this->evaluateArg($args[0]));
		}
	}
	
	/**
	 * Returns an array containing the IDs of the elements currently beeing selected
	 * FuncExp signature : <code>sysMultipleSelection()</code><br/>
	 * 
	 * This function cannot be called from public space (i.e. caller is located outside of the Wigii instance)
	 * @return Array an array with the element IDs or an empty array if no multiple selection
	 */
	public function sysMultipleSelection($args) {
		$this->assertFxOriginIsNotPublic();
		$lc = ServiceProvider::getWigiiBPL()->getListContext($this->getPrincipal());
		if(isset($lc) && $lc->isMultipleSelection()) return $lc->getMultipleSelection();
		else return array();
	}
	
	/**
	 * Logs into the system given a username and password. 
	 * If username changes, then first logouts and then login with new user. 
	 * FuncExp signature : <code>sysLogin(username,password)</code><br/>
	 * Where arguments are :
	 * - Arg(0) username: String. The name of the user login in
	 * - Arg(1) password: String. The user password to check against for authentication (full password).
	 * Postcondition: if login is successful then FuncExpVM principal is changed.
	 * @return boolean true if user has successfully logged in, false if user was already logged in and no change, exception if login is not successful.
	 * @throws AuthenticationServiceException in case of login error.
	 */
	public function sysLogin($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs<2) throw new FuncExpEvalException('sysLogin takes two arguments: the username and the password', FuncExpEvalException::INVALID_ARGUMENT);
		$username = $this->evaluateArg($args[0]);
		$password = $this->evaluateArg($args[1]);
		if(empty($username)) throw new FuncExpEvalException('username cannot be null', FuncExpEvalException::INVALID_ARGUMENT);
		$currentPrincipal = $this->getPrincipal();
		// if username changes, then logout-login 
		if($currentPrincipal->getRealUsername() != $username) {
			$authS = $this->getAuthenticationService();
			// logout only if not MinimalPrincipal
			if(!$authS->isMainPrincipalMinimal()) $authS->logout();
			// login
			$currentWigiiNamespace = $currentPrincipal->getWigiiNamespace();
			$currentPrincipal = $authS->login($username, ValueObject::createInstance($password), $currentPrincipal->getWigiiNamespace()->getClient()->getClientName());
			// password expiration check
			if ($currentPrincipal->passwordExpired()) {
				if ($currentPrincipal->canModifyRealUserPassword()) {
					throw new AuthenticationServiceException($currentPrincipal->getUserlabel() . " password is expired.", AuthenticationServiceException :: EXPIRED_PASSWORD);
				} else {
					throw new AuthenticationServiceException($currentPrincipal->getUserlabel() . " user is expired.", AuthenticationServiceException :: EXPIRED_PRINCIPAL);
				}
			}
			$userAS = $this->getUserAdminService();
			// calculates merged roles
			$userAS->calculateAllMergedRoles($currentPrincipal);			
			// refetches all roles
			$defaultWigiiNamespace = (string)$this->getConfigService()->getParameter($currentPrincipal, null, "defaultWigiiNamespace");
			if(!$defaultWigiiNamespace) $defaultWigiiNamespace = $currentPrincipal->getRealWigiiNamespace()->getWigiiNamespaceUrl();
			$currentPrincipal->refetchAllRoles($userAS->getListFilterForNavigationBar(), UserListForNavigationBarImpl::createInstance($defaultWigiiNamespace));			
			// binds back to current namespace
			$currentPrincipal->bindToWigiiNamespace($currentWigiiNamespace);
			// changes FuncExpVM principal
			$this->getFuncExpVM()->setPrincipal($currentPrincipal);
			return true;
		}
		else return false;
	}
	
	/**
	 * Logs out from the system.
	 * FuncExp signature : <code>sysLogout()</code>
	 * @return boolean true if logout is successful, false if no user was currently logged in, exception if logout is not successful.
	 */
	public function sysLogout($args) {
		$authS = $this->getAuthenticationService();
		if(!$authS->isMainPrincipalMinimal()) {
			$authS->logout();
			// changes FuncExpVM principal
			$this->getFuncExpVM()->setPrincipal($authS->getMainPrincipal());
			return true;
		}
		else return false;
	}
	
	/**
	 * Returns current user name (real user)
	 * FuncExp signature : <code>sysUsername()</code>
	 * @return String the name of the real user connected
	 */
	public function sysUsername($args) {
		return $this->getPrincipal()->getRealUsername();
	}

	/**
	 * Returns current principal email (real user)
	 * FuncExp signature : <code>sysPrincipalEmail()</code>
	 * @return String the principal email of the real user connected
	 */
	public function sysPrincipalEmail($args) {
		return $this->getPrincipal()->getValueInGeneralContext("email");
	}
		
	/**
	 * Returns current user id (real user) or user object or principal object. Defaults to user id.
	 * FuncExp signature : <code>sysUser(returnAttribute=id|object|principal)</code>
	 * @return String|User|Principal the ID of the real user connected or User object or current principal.
	 */
	public function sysUser($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs>0) $returnAttribute = $this->evaluateArg($args[0]);
		else $returnAttribute = 'id';
		
		$principal = $this->getPrincipal();
		switch($returnAttribute) {
			case 'id': return $principal->getRealUserId();
			case 'object':
			case 'user': return $principal->getRealUser();
			case 'principal': return $principal;
			default: throw new FuncExpEvalException("invalid return attribute '$returnAttribute', should be one of id, object or principal", FuncExpEvalException::INVALID_ARGUMENT);
		}
	}
	
	/**
	 * Checks if current user has one of the specified roles, given by name. 
	 * Wildcard are accepted on role name or Wigii namespace name. Use * for 0 or more characters, use ? for 0 or 1 character.
	 * FuncExp signature : <code>sysUserHasRole(roleName1,roleName2, ...) || sysUserHasRole(roleNames,wigiiNamespaces)</code>
	 * Where arguments are :
	 * For first syntax :
	 * - Arg(0..n) roleNameI: String. The complete role name (name@wigiiNamespaceName) to be checked.
	 * Or in second syntax:
	 * - Arg(0) roleNames: Array|String. One role name or an array of role names, without WigiiNamespace suffix
	 * - Arg(1) wigiiNamespaces: Array. An array of WigiiNamespaces (Names or Objects) to combine with the given role names.
	 * @example 
	 *  sysUserHasRole('PM@Projects','PM@Logistics') returns true if User is PM at Projects OR PM at Logistics
	 *  To build some conjunction, use the logAnd FuncExp:
	 *  logAnd(sysUserHasRole('PM@Projects'), sysUserHasRole('PM@Logistics')) returns true if User is PM at Projects AND PM at Logistics
	 *  sysUserHasRole('PM',array('Projects','Logistics'))  returns true if User is PM at Projects OR PM at Logistics
	 *  sysUserHasRole('PM-*@*') returns true if User is a specific PM (per country) in any namespace.
	 * @return Boolean returns true if User has specified role.
	 */
	public function sysUserHasRole($args) {
	    $nArgs = $this->getNumberOfArgs($args);
	    if($nArgs < 1) throw new FuncExpEvalException('sysUserHasRole takes at least one argument which is a role name', FuncExpEvalException::INVALID_ARGUMENT);
	    
	    // 1. Builds list of roles to check based on arguments
	    $rolesToCheck = array();
	    $roleNames = $this->evaluateArg($args[0]);
	    if($nArgs>1) $wigiiNamespaces = $this->evaluateArg($args[1]);
	    else $wigiiNamespaces = null;
	    // checks for second syntax 
	    if(is_array($wigiiNamespaces) || is_array($roleNames)) {
	        if(!is_array($wigiiNamespaces)) $wigiiNamespaces = array($wigiiNamespaces);
	        if(!is_array($roleNames)) $roleNames = array($roleNames);
	        // Builds cross product of roleName X wigiiNamespace
	        foreach($wigiiNamespaces as $wigiiNamespace) {
	            if($wigiiNamespace instanceof WigiiNamespace) $wigiiNamespace = $wigiiNamespace->getWigiiNamespaceName();
	            foreach($roleNames as $roleName) {
	                $roleName = ($wigiiNamespace?$roleName.'@'.$wigiiNamespace:$roleName);
	                $rolesToCheck[$roleName] = $roleName;
	            }
	        }	        
	    }
	    // else first syntax
	    else {
	        $rolesToCheck[$roleNames] = $roleNames; // first argument
	        if($nArgs>1) $rolesToCheck[$wigiiNamespaces] = $wigiiNamespaces; // second argument
	        // more arguments
	        if($nArgs>2) {
	            for($i=2;$i<$nArgs;$i++) {
	                $role = $this->evaluateArg($args[$i]);
	                $rolesToCheck[$role] = $role;
	            }
	        }
	    }
	    
	    // 2. Ensures to have Principal role list loaded
	    $p = $this->getPrincipal();
	    if(!($p->getRoleListener() instanceof UserList)) {
	        $defaultWigiiNamespace = (string)$this->getConfigService()->getParameter($p, null, "defaultWigiiNamespace");
	        if(!$defaultWigiiNamespace) $defaultWigiiNamespace = $p->getRealWigiiNamespace()->getWigiiNamespaceUrl();
	        $p->refetchAllRoles($this->getUserAdminService()->getListFilterForNavigationBar(), UserListForNavigationBarImpl::createInstance($defaultWigiiNamespace));	
	    }
	    
	    // 3. Checks existence of roles into Principal role list
	    // builds a role name index
	    $pRoles = array();
	    foreach($p->getRoleListener()->getListIterator() as $role) {
	        $pRoles[$role->getUsername()] = $role;
	    }
	    // tries to find matching roles
	    $strPRoles=null;
	    foreach($rolesToCheck as $role) {	        
	        $wildcards=0;
	        $wildcardRole = str_replace(array('*','?'), array('[ \-\w]*','[ \-\w]?'), $role, $wildcards);
	        // tries pattern matching
	        if($wildcards>0) {
	            if(!$strPRoles) $strPRoles = implode(',',array_keys($pRoles));
	            if(preg_match('/'.$wildcardRole.'/', $strPRoles)) return true;
	        }
	        // else checks exact role name
	        elseif($pRoles[$role]) return true;
	    }
	    return false;
	}
	
	/**
	 * Reads an HTML file from an Element and echoes content to browser
	 * FuncExp signature : <code>sysReadFile(elementId,fieldName,configSelector=null)</code><br/>
	 * Where arguments are :
	 * - Arg(0) elementId: Id. The element ID to fetch
	 * - Arg(1) fieldName: String|FieldSelector. The field of type Files for which to dump the file content.
	 * - Arg(2) configSelector: ConfigSelector|LogExp|String optional parameter. If set, then defines the configuration of the element or the root element in case of subitems.
	 * If LogExp then should be the group selection log exp used to define the configuration,
	 * if String then should be the wigiiNamespace name used to define the configuration.
	 * Postcondition: header() is sent to browser.
	 */
	public function sysReadFile($args) {
		$nArgs = $this->getNumberOfArgs($args);	
		$p = $this->getPrincipal();
		if($nArgs<2) throw new FuncExpEvalException("sysReadFile takes at least two arguments which are the elementId and the fieldName", FuncExpEvalException::INVALID_ARGUMENT);
		// Extracts arguments
		$elementId = $this->evaluateArg($args[0]);
		if($fieldName instanceof FieldSelector) $fieldName = $fieldName->getFieldName();
		else {
			$fieldName = $this->evaluateArg($args[1]);
			if($fieldName instanceof FieldSelector) $fieldName = $fieldName->getFieldName();
		}
		if($nArgs>2) $configSelector = $this->evaluateArg($args[2]);
		else $configSelector = null;
		
		// Prepares fsl to fetch file
		$fslForFetch = FieldSelectorListArrayImpl::createInstance();
		$fslForFetch->addFieldSelector($fieldName, "path");
		$fslForFetch->addFieldSelector($fieldName, "name");
		$fslForFetch->addFieldSelector($fieldName, "size");
		$fslForFetch->addFieldSelector($fieldName, "type");
		$fslForFetch->addFieldSelector($fieldName, "mime");
		$fslForFetch->addFieldSelector($fieldName, "date");
		$fslForFetch->addFieldSelector($fieldName, "user");
		$fslForFetch->addFieldSelector($fieldName, "username");
		$fslForFetch->addFieldSelector($fieldName, "version");
		$fslForFetch->addFieldSelector($fieldName, "thumbnail");
		$fslForFetch->addFieldSelector($fieldName, "content");
		$fslForFetch->addFieldSelector($fieldName, "textContent");
						
		// sets adaptative WigiiNamespace
		$currentNamespace = $p->getWigiiNamespace();
		$hasAdaptiveWigiiNamespace = $p->hasAdaptiveWigiiNamespace();
		$p->setAdaptiveWigiiNamespace(true);
		
		// Fetches element
		$elementP = sel($p, elementP($elementId, $fslForFetch, $configSelector), dfasl(dfas("NullDFA")));		
		
		// switches back to original WigiiNamespace
		if(!$hasAdaptiveWigiiNamespace) {
			$p->setAdaptiveWigiiNamespace(false);
			$p->bindToWigiiNamespace($currentNamespace);
		}
		
		// Dumps File content back to user
		if(isset($elementP)) {
			$element = $elementP->getDbEntity();
			$fieldXml = $element->getFieldList()->getField($fieldName)->getXml();
			$path = $element->getFieldValue($fieldName, 'path');
			
			header("Content-Type: text/html; charset=UTF-8");
			if($fieldXml["htmlArea"] == "1") {
				echo $element->getFieldValue($fieldName, "textContent");
			}
			elseif($path) {
				$path = FILES_PATH.$path;
				if(!file_exists($path)) echo $element->getFieldValue($fieldName, "content");
				else readfile($path);
			}
			else echo $element->getFieldValue($fieldName, "content");		
		}
	}
	
	/**
	 * Sends an email out using the Wigii system
	 * FuncExp signature : <code>sysSendEmail(to,subject,content,options)</code><br/>
	 * Where arguments are :
	 * - Arg(0) to: String|Array|ValueList. The recipient email or list of recipients emails.
	 * - Arg(1) subject: String. The email subject.
	 * - Arg(2) content: String. The email content. Supports HTML.
	 * - Arg(3) options: WigiiBPLParameter. An optional bag of parameters to configure the email process like :
	 * - from: String. The sender's email address. If not specified uses the principal's email. If explicitely set to false, then uses the notification from address (no-reply@xxx)
	 * - hideRecipients: Boolean. If true, then recipients addresses are hidden to each other using bcc technology. (explicit cc list is always visible)
	 * - copySender: Boolean. If true, then sender receives a copy of the sent email.
	 * - mergeData: Array. Mail merge data array of array of the form 
	 * array(
	 *    'email1@server.com' => array("$token1$"=>"token replacement text","$token2$"=>"some more text"),
	 *    'email2@server.com' => array("$token1$"=>"token replacement text for email2","$token2$"=>"some more text for email2"),
	 * ) 
	 * - cc: String|Array|ValueList. Visible copy email or list of emails.
	 * - bcc: String|Array|ValueList. Hidden copy email or list of emails.
	 * - mailActivity: String|Activity. A specific activity from which to retrieve the email template. By default uses 'BaseEmail' activity.
	 * If mailActivity is explicitely equal to false, then no template is loaded and original content is used as body. 
	 * This function cannot be called from public space (i.e. caller is located outside of the Wigii instance)
	 * This function is a synonym of sysMailTo.
	 */
	public function sysSendEmail($args) {
	    $this->assertFxOriginIsNotPublic();
	    $nArgs = $this->getNumberOfArgs($args);
	    if($nArgs>3) $options = $this->evaluateArg($args[3]);
	    else $options = wigiiBPLParam();
	    if($nArgs>0) $options->setValue('to', $this->evaluateArg($args[0]));
	    if($nArgs>1) $options->setValue('subject', $this->evaluateArg($args[1]));
	    if($nArgs>2) $options->setValue('content', $this->evaluateArg($args[2]));
	    return $this->getWigiiBPL()->sendEmail($this->getPrincipal(), $this, $options,$this->getFuncExpVMServiceProvider()->getExecutionSink());
	}
	/**
	 * Sends an email out using the Wigii system
	 * FuncExp signature : <code>sysMailTo(to,subject,content,options)</code><br/>
	 * Where arguments are :
	 * - Arg(0) to: String|Array|ValueList. The recipient email or list of recipients emails.
	 * - Arg(1) subject: String. The email subject.
	 * - Arg(2) content: String. The email content. Supports HTML.
	 * - Arg(3) options: WigiiBPLParameter. An optional bag of parameters to configure the email process like :
	 * - from: String. The sender's email address. If not specified uses the principal's email. If explicitely set to false, then uses the notification from address (no-reply@xxx)
	 * - hideRecipients: Boolean. If true, then recipients addresses are hidden to each other using bcc technology. (explicit cc list is always visible)
	 * - copySender: Boolean. If true, then sender receives a copy of the sent email.
	 * - mergeData: Array. Mail merge data array of array of the form 
	 * array(
	 *     'email1@server.com' => array("$token1$"=>"token replacement text","$token2$"=>"some more text"),
	 *     'email2@server.com' => array("$token1$"=>"token replacement text for email2","$token2$"=>"some more text for email2"),
	 * )
	 * - cc: String|Array|ValueList. Visible copy email or list of emails.
	 * - bcc: String|Array|ValueList. Hidden copy email or list of emails.
	 * - mailActivity: String|Activity. A specific activity from which to retrieve the email template. By default uses 'BaseEmail' activity.
	 * If mailActivity is explicitely equal to false, then no template is loaded and original content is used as body. 
	 * This function cannot be called from public space (i.e. caller is located outside of the Wigii instance)
	 * This function is a synonym of sysSendEmail.
	 */
	public function sysMailTo($args) {
	    return $this->sysSendEmail($args);
	}
		
	/**
	 * Sends a notification email if a given list of fields changes.
	 * This function always triggers a notification if a field has changed, even if standard notifications are disabled.
	 * FuncExp signature : <code>sysSendNotif(forFields,to,subject=null,introduction=null,options=null)</code><br/>
	 * Where arguments are :
	 * - Arg(0) forFields: FieldSelector|FieldSelectorList|String|Array. The list of fields to monitor for changes.
	 * - Arg(1) to: String|Array|ValueList. The recipient email or list of recipients emails.
	 * - Arg(2) subject: String. The notification subject. If not defined, takes the standard notification subject.
	 * - Arg(3) introduction: String. An introduction text to be inserted before the notification body. Supports HTML.
	 * - Arg(4) options: WigiiBPLParameter. An optional bag of parameters to configure the notification process. Supports :
	 *     targetFolder: group ID. The proposed target folder in which to open the card when clicking on the access button in the notification email.
	 * @return Boolean returns true if a notif has been triggered on field modification else false
	 * This function cannot be called from public space (i.e. caller is located outside of the Wigii instance)
	 */
	public function sysSendNotif($args) {
	    $this->assertFxOriginIsNotPublic();
	    $nArgs = $this->getNumberOfArgs($args);
	    if($nArgs<2) throw new FuncExpEvalException('sysSendNotif takes at least two arguments, the list of fields to monitor for changes and the recipient email of the notification', FuncExpEvalException::INVALID_ARGUMENT);
	    // forFields
	    if($args[0] instanceof FieldSelector) $forFields = $args[0];
	    else $forFields = $this->evaluateArg($args[0]);
	    if(is_array($forFields)) {
	        $fslForNotif = FieldSelectorListArrayImpl::createInstance(true,false);
	        foreach($forFields as $fs) {
	            if(!($fs instanceof FieldSelector)) $fs = FieldSelector::createInstance($fs);
	            $fslForNotif->addFieldSelectorInstance($fs);
	        }
	        $forFields = $fslForNotif;
	    }
	    elseif(!($forFields instanceof FieldSelectorList)) {
	        $fslForNotif = FieldSelectorListArrayImpl::createInstance(true,false);
	        if(!($forFields instanceof FieldSelector)) $forFields = FieldSelector::createInstance($forFields);
	        $fslForNotif->addFieldSelectorInstance($forFields);
	        $forFields = $fslForNotif;
	    }	    
	    	    	    	   
	    // gets element currently beeing saved
	    $element = $this->evaluateArg(fs_e('this'));
	    // checks that element is updated and that a field in the given field selector list is touched
	    $sendNotif = false;
	    if($this->evaluateFuncExp(fx('ctlCurrentFlow')) == 'element-edit' && isset($element) && $element->getWigiiBag()->hasChanges()) {
	        $wigiiBag = $element->getWigiiBag();
	        foreach($forFields->getListIterator() as $fs) {
	            if($wigiiBag->isChanged($fs->getFieldName())) {
	                $sendNotif = true;
	                break;
	            }
	        }	        
	    }
	    
	    // prepares notification email
	    if($sendNotif) {
	        $notifS = ServiceProvider::getWigiiBPL()->getNotificationService();
	        $p = $this->getPrincipal(); 
	        $eventName = 'update';
	        $entityName = 'Element';
	        $module = $element->getModule(); 
	        $rec = $element; 
	        $gObj = $this->evaluateFuncExp(fx('cfgCurrentGroup','group',true)); 
	        
	        // gets options bag or creates an empty one
	        if($nArgs>4) $options = $this->evaluateArg($args[4]);
	        else $options = wigiiBPLParam();
	        
	        // subject (takes standard notification subject if not provided as argument)
	        $subject = null;
	        if($nArgs>2) $subject = $this->evaluateArg($args[2]);
	        if(empty($subject)) $subject = $options->getValue('subject');
	        if(empty($subject)) $subject = $notifS->getInitialSubject($p, $eventName, $entityName, $module, $rec, $gObj);
	        
	        // injects any given introduction
	        if($nArgs>3) $options->setValue('introduction', $this->evaluateArg($args[3]));
	        
	        // sets notification email options
	        $options->setValue('from', false);
	        $options->setValue('hideRecipients', true);
	        $options->setValue('mailActivity', false);
	        $options->setValue('fslForNotif',$forFields);
	        
	        // sends notification email
	        $this->evaluateFuncExp(fx('sysSendEmail',$args[1],$subject,
	            $notifS->getHtml($p, $eventName, $entityName, $module, $rec, $gObj, $options)
	           ,$options));
	    }
	    return $sendNotif;
	}
	
	// Box integration
	
	/**
	 * Checks if a given file path refers to a Box file
	 * Only checks path syntax, does not query Box.com
	 * FuncExp signature : <code>boxIsPathCorrect(filePath)</code><br/>
	 * Where arguments are :
	 * - Arg(0) filePath: String. The path of the file to check if it relates to a Box file
	 * @return boolean true if file has a path equal to box://... else false
	 */
	public function boxIsPathCorrect($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs>0) {
			return (strpos($this->evaluateArg($args[0]), "box://")!==false); 
		}
		else throw new FuncExpEvalException('boxIsPathCorrect takes at least one argument which is a file path', FuncExpEvalException::INVALID_ARGUMENT);
	}
	
	// QlikSense integration
	
	/**
	 * Returns the complete http url to access the linked QlikSense server
	 * FuncExp signature : <code>qlikSenseUrl()</code><br/>
	 * 
	 * This function cannot be called from public space (i.e. caller is located outside of the Wigii instance)
	 * @return String qlikSense url with ending slash, combining QlikSense base Url and virtual proxy, as defined in QlikSense_config.xml
	 */
	public function qlikSenseUrl($args) {
		$this->assertFxOriginIsNotPublic();
		return $this->getQlikSenseFormExecutor()->getQlikSenseUrl();
	}
	
	// Select2 Ajax drop-downs integration
	
	/**
	 * Runs a pattern matching selector and transforms the result to be compatible with select2 ajax data-sources.
	 * This FuncExp is used in the implementation of the attributeMatchExp configuration parameter.
	 * FuncExp signature : <code>select2Ajax(attributeMatchExp,inputPattern)</code><br/>
	 * Where arguments are :
	 * - Arg(0) attributeMatchExp: FuncExp. A FuncExp which selects some elements based on an input pattern and returns a SimpleXmlElement compatible with attribute expressions.
	 * The FuncExp signature is of the form cfgAttrElementFieldMatch(...,inputPattern) : SimpleXmlElement as produced by CfgAttribut2XmlDFA.
	 * - Arg(1) inputPattern: String. Evaluates to the input pattern passed to the matching function.
	 * @return StdClass A data object compatible with select2 data model cf. https://select2.org/data-sources/formats.
	 */
	public function select2Ajax($args) {
	    $nArgs = $this->getNumberOfArgs($args);
	    if($nArgs<1) throw new FuncExpEvalException('select2Ajax takes at least one argument which is the attribute matching expresssion', FuncExpEvalException::INVALID_ARGUMENT);
	    // extracts attribute matching exp
	    $attributeMatchExp = $args[0];
	    if(!($attributeMatchExp instanceof FuncExp)) throw new FuncExpEvalException('attributeMatchExp should be a valid FuncExp');
	    // extracts input pattern and adds it to the selector
	    if($nArgs>1) $attributeMatchExp->addArgument($args[1]);
	    // evaluates attribute matching exp
	    $attributeMatchExp = $this->evaluateFuncExp($attributeMatchExp);
	    // builds result
	    $returnValue = array();
	    if(isset($attributeMatchExp)) {
	        $transS = $this->getTranslationService();
	        $p = $this->getPrincipal();
	        $isPublicPrincipal = ServiceProvider::getAuthorizationService()->isPublicPrincipal($p);
	        $html2text = new Html2text();
	        foreach($attributeMatchExp->children() as $attribute) {
	            $label = $transS->t($p, (string)$attribute, $attribute);
	            // cleans up the html
	            $html2text->setHtml($label);
	            $label = $html2text->getText();
	            $label = trim($label);
	            // in public: filters disabled options
	            if($isPublicPrincipal && $attribute["disabled"]=="1") continue;
	            // Fills attribute object
	            $attributeObject = array('id'=>(string)$attribute,'text'=>$label);
	            if($attribute["disabled"]=="1") $attributeObject["disabled"] = true;
	            $returnValue[] = (object)$attributeObject;
	        }
	        unset($html2text);
	    }
	    // packages result
	    return (object)array("results"=>$returnValue);
	}
}