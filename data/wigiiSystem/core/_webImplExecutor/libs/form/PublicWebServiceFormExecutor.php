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
 * A WebService which executes some public calls and returns data to the client.
 * Created by LWR on 12.09.2018
 */
class PublicWebServiceFormExecutor extends WebServiceFormExecutor {
	private $_debugLogger;
	private $_executionSink;
	
	// Dependency injection
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("PublicWebServiceFormExecutor");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("PublicWebServiceFormExecutor");
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
	public function isPublicPrincipalAuthorized() {return true;}
	public function processAndEnds($p,$exec) {
		$fxEval = null;
		try {
			$callname = $exec->getCrtParameters(0);
			if(empty($callname)) {
				// if no parameter then checks in POST data
				throw new FormExecutorException('callname cannot be empty', FormExecutorException::INVALID_ARGUMENT);
			}
			$fx = FuncExp::createInstance($callname);
			//add current parameters as args
			foreach($exec->getCrtParameters() as $key=>$arg){
				if($key==0) continue;
				$fx->addArgument($arg);
			}
			$fx->setOriginIsPublic();
			
			// evaluates FuncExp if authorized by evaluator
			$fxEval = $this->getFuncExpEval($p, $exec);
			if(method_exists($fxEval->getParentFuncExpEvaluator(),"isCallAuthorizedFromPublic") && $fxEval->getParentFuncExpEvaluator()->isCallAuthorizedFromPublic($fx)===true){
				$result = $fxEval->evaluateFuncExp($fx, $this);
			} else throw new AuthorizationServiceException($callname.' is forbidden', AuthorizationServiceException::FORBIDDEN);
			
			// returns result to client if not null
			if(isset($result)) $this->serializePublicCallResult($p,$exec,$result);
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
	 * Serializes the PublicCall result to client according to data type
	 * - scalar : mime text/plain with scalar value
	 * - array|stdClass|WplObjectList : mime text/xml with <wigiiFxAnswer>result as xml</wigiiFxAnswer>
	 * - Record|Element : mime text/xml serialize Record or Element as xml
	 * - SimpleXmlElement : mime text/xml
	 * - Object : exception UNSUPPORTED_OPERATION
	 */
	protected function serializePublicCallResult($p, $exec, $result) {
		$this->debugLogger()->logBeginOperation('serializePublicCallResult');
		
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
		elseif($result instanceof SimpleXMLElement) {
		    if(!$isIntegrated) header("Content-Type: text/xml; charset=UTF-8");
		    echo $result->asXML();
		}
		elseif($result instanceof WplObjectList) {
			if(!$isIntegrated) header("Content-Type: text/xml; charset=UTF-8");
			echo TechnicalServiceProvider::getWplToolbox()->wplObjectList2Xml($p, 'wigiiFxAnswer', $result);
		}
		else throw new FormExecutorException("PublicCall result of class '".get_class($result)."' cannot be serialized", FormExecutorException::UNSUPPORTED_OPERATION);		
		$this->debugLogger()->logEndOperation('serializePublicCallResult');
	}
}



