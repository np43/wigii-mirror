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
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException('The fs_t function takes at least one argument which is the fieldName', FuncExpEvalException::INVALID_ARGUMENT);
		return $this->getFuncExpBuilder()->fs_t($this->evaluateArg($args[0]),
			($nArgs > 1 ? $this->evaluateArg($args[1]) : null),
			($nArgs > 2 ? $this->evaluateArg($args[2]) : null));
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
				$args[$i] = $this->evaluateArg($args[$i]);
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
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException('The strlx2fx function takes one argument', FuncExpEvalException::INVALID_ARGUMENT);
		return $this->getFuncExpBuilder()->strlx2fx($this->evaluateArg($args[0]));
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
			($nArgs > 3 ? $this->evaluateArg($args[3]) : null)
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
	 * - Arg(0) elementId: int. The element id used to fetch the element
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
	 * @return int selected group ID.
	 * @throws GroupAdminServiceException if start point is not accessible or if group cannot be created.
	 */
	public function group($args) {
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

	// Configuration builder

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
		$fsl = fsl(fs('value', 'value'), fs('idGroup', 'value'), fs('email', 'value'), fs('checked', 'value'), fs('label', 'value'));
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
		$fsl = fsl(fs('value', 'value'), fs('idGroup', 'value'), fs('email', 'value'), fs('checked', 'value'), fs('label', 'value'));
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
	 * Selects a group tree and returns a SimpleXmlElement compatible with attribute expressions.<br/>
	 * FuncExp signature : <code>cfgAttrGroupTree(module=null, wigiiNamespace=null, groupLogExp=null)</code><br/>
	 *  Where arguments are :
	 * - Arg(0) module: String|Module. The module for which to select the groups. If not defined, uses the current module.
	 * - Arg(1) wigiiNamespace: String|WiigiiNamespace. The wigii namespace in which to select the groups. If not defined, uses the current wigii namespace.
	 * - Arg(2) groupLogExp: LogExp. An optional log exp to filter the selected groups.
	 *
	 * Example:
	 * <someRootGroups type="MultipleAttributs"><label>Some root groups</label>
	 * 		<attributeExp funcExp='cfgAttrGroupTree(NULL, NULL, lxEq(fs("id_group_parent"), NULL))'/>
	 * </someRootGroups>
	 */
	public function cfgAttrGroupTree($args) {
		FuncExpEvalException::throwNotImplemented();
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
	
	// Selectors
	
	/**
	 * Counts the number of elements matching a FieldSelector LogExp into a given search space
	 * FuncExp signature : <code>countElements(logExp=null,groupId=null)</code><br/>
	 * Where arguments are :
	 * - Arg(0) logExp: LogExp. Evaluates to FieldSelector LogExp used to filter the Elements to count.
	 * - Arg(1) groupId: Int. The ID of the Group from which to search for Elements (includes group and subgroups). If not defined, takes current group.	 
	 * @return Int the number of matching elements or 0 if none.
	 */
	public function countElements($args) {
		$nArgs = $this->getNumberOfArgs($args);
		$logExp=null;
		if($nArgs>0) $logExp=$this->evaluateArg($args[0]);
		if($nArgs>1) $groupId=$this->evaluateArg($args[1]);
		else $groupId = $this->evaluateFuncExp(fx('cfgCurrentGroup', 'id'));
		
		$returnValue = $this->getElementService()->countSelectedElementsInGroups($this->getPrincipal(), lxInGR(lxEq(fs('id'),$groupId)), (isset($logExp)?lf(null,$logExp):null));
		if($returnValue>0) return $returnValue;
		else return 0;
	}
	
	/**
	 * Checks if some elements matching a FieldSelector LogExp into a given search space exist
	 * FuncExp signature : <code>elementExists(logExp=null,groupId=null)</code><br/>
	 * Where arguments are :
	 * - Arg(0) logExp: LogExp. Evaluates to FieldSelector LogExp used to filter the Elements to check for existance.
	 * - Arg(1) groupId: Int. The ID of the Group from which to search for Elements (includes group and subgroups). If not defined, takes current group.
	 * @return Boolean true if at least one element matches the criterias, else false.
	 */
	public function elementExists($args) {
		return ($this->evaluateFuncExp(fx('countElements',$args),$this)>0);
	}
	
	// Wigii Administration
	
	/**
	 * Creates or updates roles in a given list of namespaces, using a generic role name and an optional list of divisions.
	 * See method WigiiBPL::adminCreateRoleByWigiiNamespace
	 * FuncExp signature : <code>adminCreateRoleByWigiiNamespace(wigiiBPLParam)</code><br/>
	 * Where arguments are :
	 * - Arg(0) wigiiBPLParam: WigiiBPLParameter. The Wigii business process parameters as defined in the WigiiBPL::adminCreateRoleByWigiiNamespace method.
	 */
	public function adminCreateRoleByWigiiNamespace($args) {
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
	 * @return boolean
	 */
	public function adminIsPublicAccessEnabled($args) {
		$authS = $this->getAuthenticationService();
		if($authS instanceof AuthenticationServiceWebImpl) {
			return $authS->isPublicAccessEnabledForClient($this->getPrincipal()->getWigiiNamespace()->getClient()->getClientName());
		}
		else return false;
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
	 * - Arg(1) key: String|FuncExp. Optional cache key name if supported by the cache type.
	 * If cache type is 'cfgAttributeExp', then the key is mandatory and has to be the AttributeExp FuncExp used to populate the drop-down.
	 */
	public function ctlClearCache($args) {
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
				if($nArgs<2) throw new FuncExpEvalException("If cache type is 'cfgAttributeExp' then cache is mandatory and should be the AttributeExp FuncExp used to populate the drop-down.", FuncExpEvalException::INVALID_ARGUMENT);
				if($args[1] instanceof FuncExp) $key=fx2str($args[1]);
				else $key=$this->evaluateArg($args[1]);
				$this->debugLogger()->write('ctlClearCache cfgAttributeExp '.$key);
				$key = "AttributeExpConfigController_".md5($key);
				$this->debugLogger()->write($key);
				$this->getSessionAdminService()->clearDataKey($key);
				break;
			default: throw new FuncExpEvalException((empty($type)?'Cache type cannot be null.':"Cache type '".$type."' is not supported. Cache type should be one of 'session','config','sharedData','role' or 'cfgAttributeExp'"), FuncExpEvalException::INVALID_ARGUMENT);
		}
	}
	
	// System functions
	
	/**
	 * Evaluates the first argument as a string and logs its result into a file namespace_module.log in the client config folder
	 * FuncExp signature : <code>log(message)</code><br/>
	 * Where arguments are :
	 * - Arg(0) message: String. The message to log.
	 */
	public function sysLog($args) {
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
	 * @return String
	 */
	public function sysSiteRootUrl($args) {
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
	 * - Arg(0) index: Int. The parameter index 0..n. 
	 * @return String|Array if index is specified, returns the parameter value, else returns an array with all parameters.
	 */
	public function sysExecParameter($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs>0) $index = $this->evaluateArg($args[0]);
		return ServiceProvider::getExecutionService()->getCrtParameters($index);
	}
	
	/**
	 * Returns current WigiiNamespace URL
	 * FuncExp signature : <code>sysCrtWigiiNamespace(returnAttribute=url|name|object)</code><br/>
	 * Where arguments are :
	 * - Arg(0) returnAttribute: String. The name of the attribute to return. If object returns the WigiiNamespace instance.
	 * Defaults to url which returns the WigiiNamespace url.
	 * @return String|WigiiNamespace the current WigiiNamespace attribute or object
	 */
	public function sysCrtWigiiNamespace($args) {
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
	 * @return String|Module the current Module attribute or object
	 */
	public function sysCrtModule($args) {
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
	 * Returns an array containing the IDs of the elements currently beeing selected
	 * FuncExp signature : <code>sysMultipleSelection()</code><br/>
	 * @return Array an array with the element IDs or an empty array if no multiple selection
	 */
	public function sysMultipleSelection($args) {
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
	 * @throws AuthenticationException in case of login error.
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
			$currentPrincipal = $authS->login($username, $password, $currentPrincipal->getWigiiNamespace()->getClient()->getClientName());
			// password expiration check
			if ($currentPrincipal->passwordExpired()) {
				if ($currentPrincipal->canModifyRealUserPassword()) {
					throw new AuthenticationServiceException($currentPrincipal->getUserlabel() . " password is expired.", AuthenticationServiceException :: EXPIRED_PASSWORD);
				} else {
					throw new AuthenticationServiceException($currentPrincipal->getUserlabel() . " user is expired.", AuthenticationServiceException :: EXPIRED_PRINCIPAL);
				}
			}
			// calculates merged roles
			$this->getUserAdminService()->calculateAllMergedRoles($currentPrincipal);
			// refetches all roles
			$currentPrincipal->refetchAllRoles();
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
}