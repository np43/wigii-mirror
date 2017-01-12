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
 * A WebService which executes some FuncExps and returns data to the client.
 * Created by CWE on January 07th 2016.
 * Modified by Medair (CWE) on 25.11.2016 to secure against Cross Site Scripting
 */
class FxWebServiceFormExecutor extends WebServiceFormExecutor {
	private $_debugLogger;
	private $_executionSink;
	
	// Dependency injection
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("FxWebServiceFormExecutor");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("FxWebServiceFormExecutor");
		}
		return $this->_executionSink;
	}
	
	// Configuration
	
	private $isIntegrated = false;
	/**
	 * If true, then WebService response is integrated into a Wigii protocol response, else WebService response is a full http response with headers.
	 * Defaults to false.
	 */
	public function setIsIntegrated($bool) {
		$this->isIntegrated = $bool;
	}
	protected function getIsIntegrated() {
		return $this->isIntegrated;
	}
	
	// WebService implementation
	
	public function isMinimalPrincipalAuthorized() {return true;}
	public function processAndEnds($p,$exec) {
		$fxEval = null;
		try {
			// decodes FuncExp			
			$fx = $exec->getCrtParameters(0);			
			if(empty($fx)) {
				// if no parameter then checks in POST data
				$fx=ServiceProvider::getWigiiBPL()->dataFetchFromPost($p, $this, wigiiBPLParam('type','fx'));
				if(is_null($fx)) throw new FormExecutorException('FuncExp cannot be empty', FormExecutorException::INVALID_ARGUMENT);
			}
			// decodes and parses FuncExp passed on URL
			else {
				$fx = base64url_decode($fx);
				// parses the FuncExp
				$fx = str2fx($fx);
			}	
			// Sets Origin as Public
			if($fx instanceof FuncExp) $fx->setOriginIsPublic();
			
			// tries to login with a login request if provided and needed
			$authS = ServiceProvider::getAuthenticationService();
			if($authS->isMainPrincipalMinimal()) {
				// separate loginFx from fx
				$loginFx = null;
				if($fx->getName()=='sysLogin') {
					$loginFx = $fx;
					$fx = null;
				}
				elseif($fx->getName()=='ctlSeq') {
					$newArguments = array();
					foreach($fx->getArguments() as $arg) {
						if($arg instanceof FuncExp && $arg->getName()=='sysLogin') {
							$loginFx = $arg;
							$newArguments[] = fx('logTrue');
						}
						else {
							$newArguments[] = $arg;
						}
					}
					$fx->setArguments($newArguments);					
				}
				// login
				if(isset($loginFx)) {
					$fxEval = ServiceProvider::getFuncExpVM($p);
					$result = $fxEval->evaluateFuncExp($loginFx);
					$fxEval->freeMemory();
					// retrieves new Principal after login
					$p = $authS->getMainPrincipal();					
				}
				// evaluates FuncExp
				if(isset($fx)) {
					// binds to WigiiNamespace from request
					if(!$authS->isMainPrincipalMinimal()) $p->bindToWigiiNamespace($exec->getCrtWigiiNamespace());
					// evaluates FuncExp
					$fxEval = $this->getFuncExpEval($p, $exec);
					$result = $fxEval->evaluateFuncExp($fx, $this);
				}
			}
			// else directly evaluates the FuncExp
			else {
				// password expiration check
				if ($p->passwordExpired()) {
					if ($p->canModifyRealUserPassword()) {
						throw new AuthenticationServiceException($p->getUserlabel() . " password is expired.", AuthenticationServiceException :: EXPIRED_PASSWORD);
					} else {
						throw new AuthenticationServiceException($p->getUserlabel() . " user is expired.", AuthenticationServiceException :: EXPIRED_PRINCIPAL);
					}
				}
				// binds to WigiiNamespace from request
				$p->bindToWigiiNamespace($exec->getCrtWigiiNamespace());
				// evaluates FuncExp
				$fxEval = $this->getFuncExpEval($p, $exec);
				$result = $fxEval->evaluateFuncExp($fx, $this);
			}
			// if result is a DataFlowSelector then executes it
			if($result instanceof DataFlowSelector) {
				$result = ServiceProvider::getDataFlowService()->processDataFlowSelector($p, $result);
			}
			// returns result to client if not null
			if(isset($result)) $this->serializeFxResult($p,$exec,$result);
			if(method_exists($fxEval, 'freeMemory')) $fxEval->freeMemory();
		}
		catch(Exception $e) {
			if(isset($fxEval) && method_exists($fxEval, 'freeMemory')) $fxEval->freeMemory();
			header($_SERVER["SERVER_PROTOCOL"]." 500 Internal Error"); 
			header("Access-Control-Allow-Origin: ".$_SERVER['HTTP_ORIGIN']);
			header("Access-Control-Allow-Credentials: true");
			header("Content-Type: text/xml; charset=UTF-8");			
			echo TechnicalServiceProvider::getWplToolbox()->stdClass2Xml($p, 'wigiiFxError', $this->getWigiiExecutor()->convertServiceExceptionToJson($p, $exec, $e));
			// signals fatal error to monitoring system
			ServiceProvider::getClientAdminService()->signalFatalError($e);
		}		
	}

	/**
	 * Serializes the FuncExp result to client according to data type
	 * - scalar : mime text/plain with scalar value
	 * - array|stdClass|WplObjectList : mime text/xml with <wigiiFxAnswer>result as xml</wigiiFxAnswer>
	 * - Record|Element : mime text/xml serialize Record or Element as xml
	 * - Object : exception UNSUPPORTED_OPERATION
	 */
	protected function serializeFxResult($p, $exec, $result) {
		$this->debugLogger()->logBeginOperation('serializeFxResult');
		
		$isIntegrated = $this->getIsIntegrated();
		if(!$isIntegrated) {
			header("Access-Control-Allow-Origin: ".$_SERVER['HTTP_ORIGIN']);
			header("Access-Control-Allow-Credentials: true");
		}
		else if ($exec->getIsUpdating()) {			
			echo ExecutionServiceImpl :: answerRequestSeparator;
			echo $exec->getIdAnswer();
			echo ExecutionServiceImpl :: answerParamSeparator;
		}
		
		if(is_scalar($result)) {
			// CWE 25.11.2016: Should always be text/plain and never text/html to prevent sending links which build fake HTML pages (Cross Site Scripting attack)
			if(!$isIntegrated) header("Content-Type: text/plain charset=UTF-8");
			if($result===true) echo 1;
			elseif($result===false) echo 0;
			else echo $result;
		}
		elseif(is_array($result)||$result instanceof stdClass) {
			if(!$isIntegrated) header("Content-Type: text/xml; charset=UTF-8");
			echo TechnicalServiceProvider::getWplToolbox()->stdClass2Xml($p, 'wigiiFxAnswer', $result);
		}
		elseif($result instanceof Element || $result instanceof ElementP || $result instanceof Record) {			
			$result = $result->getDbEntity();
			if(!$isIntegrated) header("Content-Type: text/xml; charset=UTF-8");
			echo TechnicalServiceProvider::getWplToolbox()->record2xml($p, $result->getFieldList(), $result->getWigiiBag(), false, null, $result->getId());
		}
		elseif($result instanceof WplObjectList) {
			if(!$isIntegrated) header("Content-Type: text/xml; charset=UTF-8");
			echo TechnicalServiceProvider::getWplToolbox()->wplObjectList2Xml($p, 'wigiiFxAnswer', $result);
		}
		else throw new FormExecutorException("FuncExp result of class '".get_class($result)."' cannot be serialized", FormExecutorException::UNSUPPORTED_OPERATION);		
		$this->debugLogger()->logEndOperation('serializeFxResult');
	}
}



