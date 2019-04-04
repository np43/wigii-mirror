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
 * Guzzle framework helper
 * Guzzle is an http client, see http://guzzlephp.org/ 
 * Created by CWE on 18 juillet 2013
 */
class GuzzleHelper
{
	private $_debugLogger;
	private $_executionSink;
	
	// Dependency injection
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("GuzzleHelper");			
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("GuzzleHelper");
		}
		return $this->_executionSink;
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
	
	// Helper functions
	
	/**
	 * Wrapper around the Guzzle\Http\Client constructor
	 * see http://guzzlephp.org/api/class-Guzzle.Http.Client.html	
	 */
	public function createHttpClient($principal, $baseUrl='', $config=null) {
		$this->executionSink()->publishStartOperation("createHttpClient", $principal);		
		try
		{
			$returnValue = new Guzzle\Http\Client($baseUrl, $config);
			// injects DebugLogger into Guzzle framework
			$debugLogger = $this->debugLogger();
			if($debugLogger->isEnabled()) {			
				$returnValue->addSubscriber(new Guzzle\Plugin\Log\LogPlugin(
					new DebugLoggerGuzzleLogAdapter($debugLogger), 
					Guzzle\Log\MessageFormatter::DEBUG_FORMAT));				
			}
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("createHttpClient", $e, $principal);
			throw $e;
		}		
		$this->executionSink()->publishEndOperation("createHttpClient", $principal);
		return $returnValue;
	}
	
	/**
	 * Wrapper arount the Guzzle\Http\Client send method
	 * @param mixed $httpClient the Guzzle http client
	 * @param mixed $httpRequest guzzle http request or an array of guzzle http requests
	 * @return mixed a guzzle http response or an array of guzzle http responses
	 */
	public function sendHttpRequest($principal, $httpClient, $httpRequests) {
		$this->executionSink()->publishStartOperation("sendHttpRequest", $principal);		
		try
		{
			$returnValue = $httpClient->send($httpRequests);
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("sendHttpRequest", $e, $principal);
			throw $e;
		}		
		$this->executionSink()->publishEndOperation("sendHttpRequest", $principal);
		return $returnValue;
	}
	
	/**
	 * Processes the http request as a stream and pushes the data chunks in the data flow
	 * @param Principal $principal the wigii principal
	 * @param mixed $httpRequest the guzzle http request to execute
	 * @param DataFlowActivitySelectorList $dataFlowActivitySelectorList the data flow where to push the data chunks
	 * @param int $bufferLimit the input buffer size when reading the http response 
	 * before sending to the dataflow, default to 2048 bytes
	 */
	public function processHttpRequest($principal, $httpRequest, $dataFlowActivitySelectorList, $bufferLimit=2048) {
		$this->executionSink()->publishStartOperation("processHttpRequest", $principal);		
		try
		{
			$streamFactory = new Guzzle\Stream\PhpStreamRequestFactory();
			$stream = $streamFactory->fromRequest($httpRequest);
			$returnValue = null;
			if(!$stream->feof()) {
				$dfs = $this->getDataFlowService();
				$dataFlowContext = $dfs->startStream($principal, $dataFlowActivitySelectorList);			
				while(!$stream->feof()) {
					$dfs->processDataChunk($stream->read($bufferLimit), $dataFlowContext);
				}
				$returnValue = $dfs->endStream($dataFlowContext);
			}
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("processHttpRequest", $e, $principal);
			throw $e;
		}		
		$this->executionSink()->publishEndOperation("processHttpRequest", $principal);
		return $returnValue;
	}
	
	/**
	 * Processes the http response body as a stream and pushes the data chunks in the data flow
	 * @param Principal $principal the wigii principal
	 * @param mixed $body the guzzle http response body to process
	 * @param DataFlowActivitySelectorList $dataFlowActivitySelectorList the data flow where to push the data chunks
	 * @param int $bufferLimit the input buffer size when reading the http response 
	 * before sending to the dataflow, default to 2048 bytes
	 */
	public function processHttpResponseBody($principal, $body, $dataFlowActivitySelectorList, $bufferLimit=2048) {
		$this->executionSink()->publishStartOperation("processHttpResponseBody", $principal);		
		try
		{
			if($body->isSeekable()) $body->rewind();		
			$returnValue = null;
			if(!$body->feof()) {
				$dfs = $this->getDataFlowService();
				$dataFlowContext = $dfs->startStream($principal, $dataFlowActivitySelectorList);			
				while(!$body->feof()) {
					$data = $body->read($bufferLimit);				
					$dfs->processDataChunk($data, $dataFlowContext);				
				}
				$returnValue = $dfs->endStream($dataFlowContext);
			}
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("processHttpResponseBody", $e, $principal);
			throw $e;
		}		
		$this->executionSink()->publishEndOperation("processHttpResponseBody", $principal);
		return $returnValue;
	}
}
