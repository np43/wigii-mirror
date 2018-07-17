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
 * ExecutionService implementation which supports the Wigii communication protocol
 * Created on 21 juil. 09 by LWR
 */
class ExecutionServiceImpl implements ExecutionService {

	const requestSeparator = "/__/";
	const paramSeparator = "/";
	const answerParamSeparator = "----/----";
	const answerRequestSeparator = "---- UPDATES SEPARATOR ----";
	const answerFoundInJSCache = "foundInCache";

	private $_debugLogger;
	private $_executionSink;

	private $isUpdating; //if true, then only text formatted with the communication protocol is returned. Otherwise, html is returned
	private $originalContext; //this is the object containing the original context
	private $idAnswer; //this is the id for the answer request
	private $crtWigiiNamespace; //this is the wigiiNamespace of the request, it contains the client
	private $crtModule; //this is the module of the request
	private $crtContext = 0; //this is the context id defining if the request comes from a new window or from an existing one
	private $crtAction; //this is the action of the request
	private $crtParameters; //this is an array of parameter for the request
	private $crtRequest; //this a string with the current request (just for information, not really used)
	private $crtFragment; //this a string with the current fragments (www.domain.ch/#fragments)
	private $remainingRequests; //store in an array each request. A loop will be done on each request

	//dependant service
	private $wigiiNamespaceAS;
	private $moduleAS;
	private $authenticationS;
	private $sessionAS;


	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("ExecutionService");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("ExecutionService");
		}
		return $this->_executionSink;
	}

	public function __construct(){
		$this->debugLogger()->write("creating instance");
	}

	// Dependency injection

	public function setAuthenticationService($authenticationService){
		$this->authenticationS = $authenticationService;
	}
	protected function getAuthenticationService(){
		// autowired
		if(!isset($this->authenticationS)){
			$this->authenticationS = ServiceProvider::getAuthenticationService();
		}
		return $this->authenticationS;
	}

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
	public function setModuleAdminService($moduleAdminService)
	{
		$this->moduleAS = $moduleAdminService;
	}
	protected function getModuleAdminService()
	{
		// autowired
		if(!isset($this->moduleAS))
		{
			$this->moduleAS = ServiceProvider::getModuleAdminService();
		}
		return $this->moduleAS;
	}

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


	// attributs get/set

	public function getIsUpdating(){
		return $this->isUpdating;
	}

	public function getExecPrincipal(){
		return $this->getAuthenticationService()->getMainPrincipal();;
	}

	public function wasFoundInJSCache(){
		return $this->idAnswer == ExecutionServiceImpl::answerFoundInJSCache;
	}
	public function getIdAnswer(){
		if(!isset($this->idAnswer)){
			$this->setIdAnswer(null);
		}
		return $this->idAnswer;
	}
	protected function setIdAnswer($id){
		$this->idAnswer = $id;
	}

	public function getOriginalContext($key=null){
		if($key) return $this->originalContext[$key];
		return $this->originalContext;
	}
	protected function setOriginalContext($var){
		$this->originalContext = $var;
	}

	public function getCrtWigiiNamespace(){
//		if(!isset($this->crtWigiiNamespace)){
//			$this->setCrtWigiiNamespace(null);
//		}
		return $this->crtWigiiNamespace;
	}
	protected function setCrtWigiiNamespace($ns){
		$this->crtWigiiNamespace = $ns;
//		if($ns == null){
//			$this->crtWigiiNamespace = $this->getWigiiNamespaceAdminService()->getEmptyWigiiNamespaceForClient($this->getExecPrincipal(), $this->getExecPrincipal()->getWigiiNamespace()->getClient());
//		} else if (is_string($ns)) {
//			$this->crtWigiiNamespace = $this->getWigiiNamespaceAdminService()->getWigiiNamespaceForClient($this->getExecPrincipal(), $ns, $this->getExecPrincipal()->getWigiiNamespace()->getClient());
//		} else {
//			$this->crtWigiiNamespace = $ns;
//		}
	}
	public function getCrtModule(){
//		if(!isset($this->crtModule)){
//			$this->setCrtModule(null);
//		}
		return $this->crtModule;
	}
	protected function setCrtModule($m){
		$this->crtModule = $m;
//		if($m == null || is_string($m)){
//			$this->crtModule = $this->getModuleAdminService()->getModule($this->getExecPrincipal(), $m, $this->getCrtWigiiNamespace()->getClient());
//		} else {
//			$this->crtModule = $m;
//		}
	}
	public function getCrtContext(){
		return $this->crtContext;
	}
	protected function setCrtContext($cId){
		$lastExecutionServiceContextId = $this->getSessionAdminService()->getData($this,"lastExecutionServiceContextId");
		//if greater context exist, then upgrade lastExecutionContext
		//this can happen if an old window remain opened and the session ends
		if($cId>$lastExecutionServiceContextId){
			$this->getSessionAdminService()->storeData($this,"lastExecutionServiceContextId", $cId);
		}
		$this->crtContext = $cId;
	}
	protected function loadNewContext(){
		//create a none existing context ID in this php session
		$lastExecutionServiceContextId = $this->getSessionAdminService()->getData($this,"lastExecutionServiceContextId");
		//increment
		$lastExecutionServiceContextId++;
		$this->setCrtContext($lastExecutionServiceContextId);
		$this->getSessionAdminService()->storeData($this,"lastExecutionServiceContextId", $lastExecutionServiceContextId);
	}
	public function getCrtAction(){
		return $this->crtAction;
	}
	protected function setCrtAction($a){
		$this->crtAction = $a;
	}
	public function getCrtRequest(){
		return $this->crtRequest;
	}
	protected function setCrtRequest($r){
		$this->crtRequest = $r;
	}
	public function getCrtParameters($key=null){
		if(!isset($key)){
			return $this->crtParameters;
		} else {
			return $this->crtParameters[$key];
		}
	}
	protected function setCrtParameters($array){
		$this->crtParameters = $array;
	}
	public function getRemainingRequests(){
		return $this->remainingRequests;
	}

	// utils

	/**
	 * loads the requests and prepare for execution
	 */
	public function start(){
		//throw new ExecutionServiceException("The start of the ExecutionService can not be done, because need of an implementation. Use Web Impl for i.e.", ExecutionServiceException::UNSUPPORTED_OPERATION);
		$this->executionSink()->log("Start");
		$this->remainingRequests = $this->addRequests($this->findUrl());
	}

	/**
	 * ends the execution
	 * needs an implementation
	 */
	public function end(){
		throw new ExecutionServiceException("End in the ExecutionService can not be done, because need of an implementation. Use Web Impl for i.e.", ExecutionServiceException::UNSUPPORTED_OPERATION);
	}

	/**
	 * needs an implementation
	 */
	protected function findUrl(){
		throw new ExecutionServiceException("Find Url in the ExecutionService can not be done, because need of an implementation. Use Web Impl for i.e.", ExecutionServiceException::UNSUPPORTED_OPERATION);
	}

	/**
	 * needs an implementation
	 * return an array(wigiiNamespace=>val, module=>val, type=>val, id=>val).
	 * if no fragment it return an empty array
	 */
	protected function parseFragment(){
		throw new ExecutionServiceException("Find Fragment in the ExecutionService can not be done, because need of an implementation. Use Web Impl for i.e.", ExecutionServiceException::UNSUPPORTED_OPERATION);
	}

	/**
	 * return an array(wigiiNamespace=>val, module=>val, type=>val, id=>val, targetModifier=>null|val).
	 * if no fragment availabe it return an empty array
	 * (www.domain.ch/#fragment)
	 */
	public function getFragment(){
		if(!isset($this->crtFragment)){
			$this->crtFragment = $this->parseFragment();
		}
		return $this->crtFragment;
	}

	/**
	 * return true if there is some fragment options (www.domain.ch/#fragment)
	 */
	public function isFragmentAvailable(){
		$this->getFragment();
		return $this->crtFragment!=null;
	}
	public function clearFragment(){
		$this->crtFragment = null;
		unset($this->crtFragment);
	}
	//fragment are url parameters after the #
	public function addRequestOnFragment(){
		if($this->isFragmentAvailable()){
			if($this->crtFragment["module"]==Module::HOME_MODULE){
				$this->addRequests(($this->getIsUpdating() ? "mainDiv/":'').WigiiNamespace :: EMPTY_NAMESPACE_URL . "/" . Module :: HOME_MODULE . "/start");
			} else {
			    $this->addRequests(($this->getIsUpdating() ? "mainDiv/":'').$this->crtFragment["wigiiNamespace"] . "/" . $this->crtFragment["module"] . "/navigate/" . $this->crtFragment["type"] . "/" . $this->crtFragment["id"] .($this->crtFragment["targetModifier"]?"/".$this->crtFragment["targetModifier"]:''));
			}
		}
	}

	/**
	 * process the url to make it array of requests, and add it to the remaining request.
	 * @return return the array of request which was defined in this url.
	 */
	public function addRequests($url){
		$sepRequests = explode(ExecutionServiceImpl::requestSeparator, $url);
		$returnValue = array();
		$minRequestParams = 3;
		foreach($sepRequests as $i=>$request){
			if($request == null) continue; //this happens for example if there is no request at all...
			//prevent having empty parameters at the end
			if($request{strlen($request)-1} == ExecutionServiceImpl::paramSeparator){
				$request = substr($request, 0, strlen($request)-1);
			}
			$explRequest = explode(ExecutionServiceImpl::paramSeparator, $request);
			if(strtolower($explRequest[0]) == "update" && $i == 0){
				$this->isUpdating = true;
				//lookup current context
				$this->setCrtContext($explRequest[1]);
				//if we are in update mode, then we need a minimum of 4 params to have the idAnswer
				$minRequestParams += 1;
				continue;
			}
			if(strtolower($explRequest[0]) == "usecontext" && $i == 0){
				//lookup current context
				$this->setCrtContext($explRequest[1]);
				continue;
			}
			//if the request has not at least 4 parameters that means it is not a valid
			//request.
			if(count($explRequest)<$minRequestParams){
				$this->executionSink()->log("Invalid request: ".$request);
			} else {
				$returnValue[] = $explRequest;
			}
		}
		if(isset($this->remainingRequests)){
			$this->remainingRequests = array_merge($this->remainingRequests, $returnValue);
		} else {
			$this->remainingRequests = $returnValue;
		}
		return $returnValue;
	}

	public function cleanRemainingRequest(){
		$this->remainingRequests=null;
	}

	/**
	 * set the crt's attributes to the state of the next request.
	 * if there was a request then everything is loaded and it returns true.
	 * if there was no more request then false is returned and the crt's are empty
	 * if current context is empty, then load new context	 
	 */
	private $requestCounter = 0;
	public function loadNextRequest($wigiiExecutor){
		$sessAS = $this->getSessionAdminService();
		$p = $this->getExecPrincipal();

		$this->requestCounter++;

		//if not first request for this execution, store current principal role for this context
		if($this->requestCounter>1){
			$lastPrincipalIdContext = $sessAS->getData($this, "lastPrincipalIdContext");
			if(!$lastPrincipalIdContext) $lastPrincipalIdContext = array();
			$lastPrincipalIdContext[$this->getCrtContext()] = $p->getUserId();
			$sessAS->storeData($this, "lastPrincipalIdContext", $lastPrincipalIdContext);
		}

		if($this->remainingRequests!=null){
			$crtRequest = array_shift($this->remainingRequests);
			$i = 0;
			if($this->getIsUpdating()) $this->setIdAnswer($crtRequest[$i++]);
			$this->setCrtWigiiNamespace($this->getWigiiNamespaceAdminService()->getWigiiNamespaceForClient($p, str_replace('%20',' ',$crtRequest[$i++]), $p->getWigiiNamespace()->getClient()));
			$this->setCrtModule($this->getModuleAdminService()->getModule($p, $crtRequest[$i++]));
			$this->setCrtAction($crtRequest[$i++]);
			$this->setCrtParameters(array_slice($crtRequest, $i++));
			$this->setCrtRequest(implode(ExecutionServiceImpl::paramSeparator, $crtRequest));

			// CWE 17.07.2018: activates adaptive wigii namespace selection on calculated roles			
			if($p->isPlayingRole() && $p->getAttachedUser()->isCalculatedRole()) $p->setAdaptiveWigiiNamespace(true);
			else $p->setAdaptiveWigiiNamespace(false);			
			
			//if new context, then select appropriate calculated role
			//else bind last role id used in context
			if(!$this->getCrtContext()){
				//prevent incrementing contextIds on download or light client
				if($this->getCrtAction()!="download" && $this->getCrtAction()!="c") $this->loadNewContext();
				if($p->getRoleListener()) $roleId = $p->getRoleListener()->getCalculatedRoleId($this->getCrtWigiiNamespace()->getWigiiNamespaceUrl());
				if($roleId && $p->getUserId()!=$roleId){
					$p = ServiceProvider :: getAuthenticationService()->changeToRole($p, $roleId);
				}
			} elseif(!$p->hasAdaptiveWigiiNamespace() || 
					$p->hasAdaptiveWigiiNamespace() && !$p->bindToWigiiNamespace($this->getCrtWigiiNamespace())) {				
				//bind principal to lastPrincipalIdContext
				$lastPrincipalIdContext = $sessAS->getData($this, "lastPrincipalIdContext");
				if($lastPrincipalIdContext && $lastPrincipalIdContext[$this->getCrtContext()]){
					$roleId = $lastPrincipalIdContext[$this->getCrtContext()];
					if($roleId && $p->getUserId()!= $roleId){
						$p = ServiceProvider :: getAuthenticationService()->changeToRole($p, $roleId);
					}
				}
			}
			
			if($this->executionSink()->isEnabled()) $this->executionSink()->log("\n".str_repeat("-",100)."\n".str_repeat("-",100)."\n"."Request: ".$this->getCrtRequest()." loaded."."\n".str_repeat("-",100)."\n".str_repeat("-",100));

			return true;
		} else{
			//reset the request
			$this->emptyCrtRequest();
			return false;
		}
	}

	/**
	 * reset each attributs which is a crt's attribut as:
	 * crtWigiiNamespace, crtModule, crtActionn, crtParameters
	 */
	protected function emptyCrtRequest(){
		$this->setCrtWigiiNamespace(null);
		$this->setCrtModule(null);
		$this->setCrtAction(null);
		$this->setCrtParameters(null);
		$this->setCrtRequest(null);
		//do not reset the crt context as it kept for all this execution
	}


	// debugs

	public function displayDebug(){
		$returnValue = "ExecutionService datas:\n";
		$returnValue .= (is_object($this->getCrtWigiiNamespace()) ? $this->getCrtWigiiNamespace()->displayDebug() : '');
		$returnValue .= (is_object($this->getCrtModule()) ? $this->getCrtModule()->displayDebug() : '');
		$returnValue .= "Action:\n".put($this->getCrtAction())."\n";
		$returnValue .= "Parameter:\n".put($this->getCrtParameters());
		$returnValue .= "Remaining requests:\n".put($this->remainingRequests);

		return $returnValue;
	}

	/**
	 * get current system version
	 */
	public function getVersion(){
		return VERSION_NUMBER;
	}
	/**
	 * get current system version type as void/beta/rc/ etc..
	 */
	public function getVersionType(){
		return VERSION_TYPE;
	}



}
