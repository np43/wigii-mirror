<?php
/**
 *  This file is part of Wigii (R) software.
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
 * Web CMS end point which serves content stored into a CMS module
 * See CMS_config.xml in configs/Example
 * And class WigiiCMSElementEvaluator for engine details
 * 
 * Created by Weber wwigii-system.net for Wigii.org on 15.08.2016
 */
class WigiiWebCMSFormExecutor extends WebServiceFormExecutor {
	private $_debugLogger;
	private $_executionSink;
	
	// Dependency injection
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("WigiiWebCMSFormExecutor");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("WigiiWebCMSFormExecutor");
		}
		return $this->_executionSink;
	}
	
	private $cmsMainProcessingFuncExp;
	public function setCMSMainProcessingFuncExp($fx) {
		$this->cmsMainProcessingFuncExp = $fx;
	}
	/**
	 * Builds the CMS main processing FuncExp to call to respond to the incoming URL
	 * @param WigiiBPLParameter $params the request parameters
	 * @return FuncExp
	 */
	protected function getCMSMainProcessingFuncExp($params) {
		if(!isset($this->cmsMainProcessingFuncExp)) {
			$this->cmsMainProcessingFuncExp = 'cms_processUrl';
		}
		return fx($this->cmsMainProcessingFuncExp, array($params));
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
	
	private $publicPrincipal;
	/**
	 * Injects Public Principal to be used to access content
	 * @param Principal $p
	 */
	public function setPublicPrincipal($p) {
		$this->publicPrincipal = $p;		
	}	
	
	// WebService implementation
	
	public function isMinimalPrincipalAuthorized() {return false;}
	public function isPublicPrincipalAuthorized() {return true;}
	public function processAndEnds($p,$exec) {				
		$fxEval = null;
		try {
			// uses public principal if defined
			if(isset($this->publicPrincipal)) $p = $this->publicPrincipal;
			// binds to WigiiNamespace from request
			$p->bindToWigiiNamespace($exec->getCrtWigiiNamespace());
			// evaluates FuncExp
			$fxEval = $this->getFuncExpEval($p, $exec);
			$result = $fxEval->evaluateFuncExp($this->getCMSMainProcessingFuncExp(wigiiBPLParam(
					'parsedUrl', $exec->getCrtParameters(),
					'isIntegrated', $this->getIsIntegrated()
			)), $this);
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
			// CWE 13.02.2019 forwards to client http code if in 400 range
			if(400 < $e->getCode() && $e->getCode() < 500) header($_SERVER["SERVER_PROTOCOL"]." ".$e->getCode());
			// else wraps it into a 500 error.
			else header($_SERVER["SERVER_PROTOCOL"]." 500 Internal Error");
			// CWE 14.02.2019 if 404 page not found and config parameter publicUrl is defined, then redirects client to publicUrl.
			$publicUrl = null;
			if($e->getCode() == 404) $publicUrl = $this->getConfigService()->getParameter($p, $exec->getCrtModule(), 'publicUrl');
			if(!empty($publicUrl)) {
				header("Access-Control-Allow-Origin: *");
				header("Content-Type: text/html; charset=UTF-8");
				?><!DOCTYPE html>
<HTML>
<HEAD>
<script type="text/javascript">
self.location = "<?=$publicUrl?>";
</script>
</HEAD>
<BODY>
<h1>Page not found</h1>
<p>Please go to <a href="<?=$publicUrl?>"><?=$publicUrl?></a></p>
</BODY>
</HTML><?
			}
			// else displays exception as xml
			else {
				header("Access-Control-Allow-Origin: *");
				header("Content-Type: text/xml; charset=UTF-8");
				$wigiiFxError = $this->getWigiiExecutor()->convertServiceExceptionToJson($p, $exec, $e);
				// CWE 23.07.2019 logs remote client IP address and reference url
				if($wigiiFxError->context != null) {
				    $wigiiFxError->context->{'clientIP'} = $_SERVER["REMOTE_ADDR"].':'.$_SERVER["REMOTE_PORT"];
				    $wigiiFxError->context->{'referer'} = $_SERVER["HTTP_REFERER"];
				}
				echo TechnicalServiceProvider::getWplToolbox()->stdClass2Xml($p, 'wigiiFxError', $wigiiFxError);
			}
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
		if(!$isIntegrated) header("Access-Control-Allow-Origin: *");
		else if ($exec->getIsUpdating()) {			
			echo ExecutionServiceImpl :: answerRequestSeparator;
			echo $exec->getIdAnswer();
			echo ExecutionServiceImpl :: answerParamSeparator;
		}
		
		if(is_scalar($result)) {
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



