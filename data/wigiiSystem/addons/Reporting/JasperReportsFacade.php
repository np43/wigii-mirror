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
 * A facade on JasperReports server
 * Created by CWE on 16.07.2013
 */
class JasperReportsFacade implements ReportingFacade {
	private $_debugLogger;
	private $_executionSink;
	private $reportingOutputHelper;
	private $multipartHttpResponses;
	private $multipartFileNamesReplacement;
	private $principal;
	private $lockedForUse = true;
	private $httpClientCache;
	
	// Object lifecycle
		
	public function reset() {
		$this->freeMemory();
		$this->lockedForUse = true;
	}
	
	public function freeMemory() {
		$this->lockedForUse = false;
		unset($this->reportingOutputHelper);		
		unset($this->multipartHttpResponses);
		unset($this->multipartFileNamesReplacement);
		unset($this->principal);
		unset($this->httpClientCache);
	}
	
	public function isLockedForUse() {
		return $this->lockedForUse;
	}
	
	
	// Dependency injection
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("JasperReportsFacade");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("JasperReportsFacade");
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
	
	public function setReportingOutputHelper($reportingOutputHelper) {
		$this->reportingOutputHelper = $reportingOutputHelper;
	}
	protected function getReportingOutputHelper() {
		return $this->reportingOutputHelper;
	}
	
	private $guzzleHelper;
	public function setGuzzleHelper($guzzleHelper)
	{
		$this->guzzleHelper = $guzzleHelper;
	}
	protected function getGuzzleHelper()
	{
		// autowired
		if(!isset($this->guzzleHelper))
		{
			$this->guzzleHelper = TechnicalServiceProvider::getGuzzleHelper();
		}
		return $this->guzzleHelper;
	}	
	
	private $jasperServerUrl;
	/**
	 * Sets url where is deployed Jasper Report server
	 * @param string $host a valid url
	 */
	public function setJasperServerUrl($url) {
		$this->jasperServerUrl = $url;
	}
	/**
	 * Defaults to same server as Wigii with 8080 port
	 */
	protected function getJasperServerUrl() {
		if(!isset($this->jasperServerUrl)) {
			$this->jasperServerUrl = (empty($_SERVER['HTTPS']) ? 'http://':'https://').$_SERVER['HTTP_HOST'].':8080';
		}
		return $this->jasperServerUrl;
	}
	
	private $jasperServerRootPath;
	/**
	 * Sets jasper server root path on host
	 * @param string $relativeUri a relative url after host, example /jasperserver
	 */
	public function setJasperServerRootPath($relativeUri) {
		$this->jasperServerRootPath = $relativeUri;
	}
	/**
	 * Defaults to /jasperserver
	 */
	protected function getJasperServerRootPath() {
		if(!isset($this->jasperServerRootPath)) {
			$this->jasperServerRootPath = '/jasperserver';
		}
		return $this->jasperServerRootPath;
	}
	
	private $jasperUserFacadeMethodMapping;
	/**
	 * Sets which jasper user should be used for each JasperReportsFacade method
	 * @param array $mapping an array where key=JasperReportsFacade method name, value=JasperReport server user name
	 * example: key=executeReport value=joeuser
	 */
	public function setJasperUserFacadeMethodMapping($mapping) {
		if(!is_array($mapping)) throw new ReportingException("mapping should be an array", ReportingException::INVALID_ARGUMENT);
		$this->jasperUserFacadeMethodMapping = $mapping;
	}	
	/**	
	 * Gets the mapping between facade methods and jasper users
	 * @param string $methodName if given, then returns the associated jasper user name
	 * els returns the whole mapping array
	 * Defaults to predefined jasper users
	 */
	protected function getJasperUserFacadeMethodMapping($methodName=null) {
		if(!isset($this->jasperUserFacadeMethodMapping)) {
			$this->jasperUserFacadeMethodMapping = array('executeReport' => 'joeuser');
		}
		if(isset($methodName)) return $this->jasperUserFacadeMethodMapping[$methodName];
		else return $this->jasperUserFacadeMethodMapping;
	}
	
	private $jasperUserCredentials;
	/**
	 * Sets the password to be used for each JasperReport server user that should be used
	 * by this facade
	 * @param array $credentials an array where key=JasperReport user name, value=password
	 * example key=joeuser, value=joeuser
	 */
	public function setJasperUserCredentials($credentials) {
		if(!is_array($credentials)) throw new ReportingException("credentials should be an array", ReportingException::INVALID_ARGUMENT);
		$this->jasperUserCredentials = $credentials;
	}
	/**
	 * Returns the credentials associated to the jasper users
	 * @param string $jasperUserName if given, then returns the credentials associated to this jasper user,
	 * else returns the array with the credentials of all users.
	 * Defaults to predefined jasper users and passwords
	 */
	protected function getJasperUserCredentials($jasperUserName=null) {
		if(!isset($this->jasperUserCredentials)) {
			$this->jasperUserCredentials = array('joeuser' => 'joeuser', 'jasperadmin' => 'jasperadmin');
		}
		if(isset($jasperUserName)) return $this->jasperUserCredentials[$jasperUserName];
		else return $this->jasperUserCredentials;
	}
	
	private $httpResponseBufferSize;
	/**	
	 * Sets the http response buffer size in bytes
	 * @param int $bytes size of the buffer
	 */
	public function setHttpResponseBufferSize($bytes) {
		if($bytes < 0) throw new ReportingException("buffer size should be a positive integer", ReportingException::INVALID_ARGUMENT);
		elseif($bytes == 0) $this->httpResponseBufferSize = null; 
		else $this->httpResponseBufferSize = $bytes;
	}
	/**
	 * Returns the size of the http response buffer in bytes
	 * Defaults to 2048 bytes.
	 */
	protected function getHttpResponseBufferSize() {
		if(!isset($this->httpResponseBufferSize)) {
			$this->httpResponseBufferSize = 2048;
		}
		return $this->httpResponseBufferSize;
	}
	
	// Reporting functions 
		
	public function executeReport($principal, $reportName, $reportDefinition, $outputField, $format, $reportParams=null) {
		$this->executionSink()->publishStartOperation("executeReport", $principal);
		try
		{
			$format = strtoupper($format);
			$gh = $this->getGuzzleHelper();
			// gets http client
			$httpClient = $this->getHttpClientForMethod($principal, 'executeReport');
			// gets http request to execute the report
			$httpRequest = $this->getHttpRequestForExecuteReport($httpClient, $reportName, $format, 
				$this->getXmlCmdStringForExecuteReport($reportName, $reportDefinition, $reportParams));		
			// executes the report		
			try {
				$response = $gh->sendHttpRequest($principal, $httpClient, $httpRequest);
			}
			catch(Guzzle\Http\Exception\ClientErrorResponseException $e) {
				// if session expired, then reconnects and retries
				$statusCode = $e->getResponse()->getStatusCode();
				if($statusCode == 403 || $statusCode == 401) {
					$httpClient = $this->getHttpClientForMethod($principal, 'executeReport', true);
					$httpRequest = $this->getHttpRequestForExecuteReport($httpClient, $reportName, $format, 
						$this->getXmlCmdStringForExecuteReport($reportName, $reportDefinition, $reportParams));
					$response = $gh->sendHttpRequest($principal, $httpClient, $httpRequest);
				}
				else throw $e;
			}		
			unset($httpRequest);		
			$reportDescriptor = $response->xml();
			unset($response);
			if(!isset($reportDescriptor)) throw new ReportingException("Report descriptor is empty", ReportingException::UNEXPECTED_ERROR);
			$reportId = (string)$reportDescriptor->uuid;
			
			// gets all parts composing the report
			$reportParts = $reportDescriptor->xpath('file');
			unset($reportDescriptor);
			if(empty($reportParts)) throw new ReportingException("Report descriptor does not contain any file", ReportingException::UNEXPECTED_ERROR);
			$nParts = count($reportParts);						
			// if only one file (main report) or not HTML --> single report
			if($nParts == 1 || $format != 'HTML') {			
				foreach($reportParts as $reportPart) {
					$partName = (string)$reportPart;								
					if($partName == 'report') {
						$mime = (string)$reportPart['type'];
						$ext = mime2ext($mime);
						break;
					}
				}
				
				// gets the report and saves to the file system
				unset($reportParts);						
				$this->processHttpRequestForSingleReport($principal, 
					$this->getHttpRequestForGetReportPart($httpClient, $reportId, $partName), 
					$this->getReportingOutputHelper()->getDFASLForSingleReport($principal, $reportName, $mime, $ext));
			}
			// else multipart report
			else {			
				$multiPartHttpRequests = array();	
				$multiPartFiles = array();	
				$this->multipartFileNamesReplacement = array();		
				foreach($reportParts as $reportPart) {
					$partName = (string)$reportPart;
					$mime = (string)$reportPart['type'];
					$ext = mime2ext($mime);
					
					if($partName == 'report') $partFile = 'index.html';									
					else {
						$partFile = 'images/'.$partName.$ext;
						// builds string replacement array to update index.html with correct file paths 
						// for each report part.
						$this->multipartFileNamesReplacement['"images/'.$partName.'"'] = '"'.$partFile.'"';
					}
					
					// creates http request for each report part
					$multiPartHttpRequests[] = $this->getHttpRequestForGetReportPart($httpClient, $reportId, $partName);
					// saves part file name
					$multiPartFiles[] = $partFile;				
				}
				
				// executes all requests in burst mode
				$this->multipartHttpResponses = $gh->sendHttpRequest($principal, $httpClient, $multiPartHttpRequests);
				$this->debugLogger()->write('got all responses');
				$this->multipartHttpResponses = array_combine($multiPartFiles, $this->multipartHttpResponses);
				$this->debugLogger()->write('stored all responses');
				unset($multiPartHttpRequests);			
				
				// processes each report part
				$dfasl = $this->getReportingOutputHelper()->getDFASLForMultipartReport($principal, $reportName, $this);
				$dfs = $this->getDataFlowService();
				$this->principal = $principal;
				$dataFlowContext = $dfs->startStream($principal, $dfasl);
				foreach($multiPartFiles as $file) {
					$dfs->processDataChunk($file, $dataFlowContext);
				}
				$dfs->endStream($dataFlowContext);
				unset($multiPartFiles);
			}
			unset($httpClient);
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("executeReport", $e, $principal);
			throw $e;
		}
		$this->executionSink()->publishEndOperation("executeReport", $principal);
	}	
	
	// Multi-part report callback
		
	public function processReportPart($reportPartName, $dataFlowActivitySelectorList) {
		$this->debugLogger()->write('processes report part '.$reportPartName);				
		// configures data flow
		$dataFlowActivitySelectorList->setDataFlowActivityParameterForClass('FileOutputStreamDFA', 'setFilename', $reportPartName);
		// processes response
		if($reportPartName == 'index.html') {
			$returnValue = $this->processHttpResponseForReportHtmlIndex($this->principal, 
				$this->multipartHttpResponses[$reportPartName], 
				$dataFlowActivitySelectorList);
		}
		else {
			$returnValue = $this->processHttpResponseForReportPart($this->principal, 
				$this->multipartHttpResponses[$reportPartName], 
				$dataFlowActivitySelectorList);
		}
		unset($this->multipartHttpResponses[$reportPartName]);
		return $returnValue;			
	}
	
	// Implementation
	
	/**
	 * Returns an instance of Guzzle\Http\Client to be used for the given facade method
	 * @param Principal $principal the current principal wanting to use this http client
	 * @param string $methodName the facade method name that is currently executed
	 * @param boolean $replaceCache if true, then ignores cached http client and reconnects. 
	 * New created http client is stored in cache.
	 */
	protected function getHttpClientForMethod($principal, $methodName, $replaceCache=false) {
		// retrieves jasper user 
		$jasperUserName = $this->getJasperUserFacadeMethodMapping($methodName);
		if(!isset($jasperUserName)) throw new ReportingException("facade method '$methodName' has no mapped jasper user", ReportingException::INVALID_ARGUMENT);
		// returns http client
		return $this->getHttpClientForJasperUser($principal, $jasperUserName, $replaceCache);
	}
	
	/**
	 * Returns an instance of Guzzle\Http\Client to be used for the given jasper user name
	 * @param Principal $principal the current principal wanting to use this http client
	 * @param string $jasperUserName a JasperReport server valid username, for instance joeuser
	 * @param boolean $replaceCache if true, then ignores cached http client and reconnects. 
	 * New created http client is stored in cache.
	 */
	protected function getHttpClientForJasperUser($principal, $jasperUserName, $replaceCache=false) {		
		$cacheKey = $this->getHttpClientCacheKey($principal, $jasperUserName);
		$httpClient = null;
		// if use cache, then retrieves http client from cache
		if(!$replaceCache) {
			if(isset($this->httpClientCache)) $httpClient = $this->httpClientCache[$cacheKey];
			else $this->httpClientCache = array();
		}
		elseif(!isset($this->httpClientCache)) $this->httpClientCache = array();
				
		// creates http client if not found
		if(!isset($httpClient)) {
			$gh = $this->getGuzzleHelper();
			$httpClient = $gh->createHttpClient($principal, $this->getJasperServerUrl());
			// sets cookies
			if($replaceCache) $cookieJar = new Guzzle\Plugin\Cookie\CookieJar\ArrayCookieJar();
			else $cookieJar = $this->createCookieJarForJasperUser($principal, $jasperUserName);
			$httpClient->addSubscriber(new Guzzle\Plugin\Cookie\CookiePlugin($cookieJar));
			
			// if no cookies, then logs into Jasper server
			if($cookieJar->count() == 0) {
				// retrieves password for jasper user
				$password = $this->getJasperUserCredentials($jasperUserName);
				// gets http request to log into jasper server
				$httpRequest = $this->getHttpRequestForLogin($httpClient, $jasperUserName, $password);
				// executes request
				$response = $gh->sendHttpRequest($principal, $httpClient, $httpRequest);
				unset($httpRequest);
				if(!$response->isSuccessful()) {
					throw new ReportingException("Could not log into jasper server: ".$response->getReasonPhrase(), $response->getStatusCode());
				}
				// stores cookies in wigii session
				$this->storeCookieJarForJasperUserInSession($principal, $jasperUserName, $cookieJar);
			}
			
			// stores http client in cache
			$this->httpClientCache[$cacheKey] = $httpClient;
		}
		return $httpClient;
	}	
	private function getHttpClientCacheKey($principal, $jasperUserName) {
		return '('.$principal->getRealUserId().'('.$jasperUserName.'))';
	}
	
	/**
	 * Creates a cookie jar and fills it with the cookies attached to this jasper user
	 * @param Principal $principal the wigii principal behind all this
	 * @param string $jasperUserName the jasper user for which we want the jasper session cookies	 
	 * @return mixed a Guzzle\Plugin\Cookie\CookieJar\ArrayCookieJar instance with the cookies 
	 * or empty if no cookies where found
	 */
	protected function createCookieJarForJasperUser($principal, $jasperUserName) {
		// creates cookie jar
		$returnValue = new Guzzle\Plugin\Cookie\CookieJar\ArrayCookieJar();
		// retrieves serialized cookies from principal context		
		$cookies = $principal->getValueInGeneralContext($this->getCookieJarSessionKey($jasperUserName));
		// initializes cookies if found any
		if(!empty($cookies)) {			
			$returnValue->unserialize($cookies);
			$this->debugLogger()->write("restores cookies from session (".$returnValue->count()." cookies): ".$cookies);
		}
		return $returnValue;
	}
	
	/**
	 * Serializes the cookie jar in the principal general context for reuse.
	 * @param Principal $principal the wigii principal
	 * @param string $jasperUserName the jasper user name for which the cookies belong
	 * @param mixed $cookieJar an instance of Guzzle\Plugin\Cookie\CookieJar\ArrayCookieJar
	 * to serialize in principal session 
	 */
	protected function storeCookieJarForJasperUserInSession($principal, $jasperUserName, $cookieJar) {
		if(is_null($cookieJar)) throw new ReportingException("cookieJar cannot be null", ReportingException::INVALID_ARGUMENT);
		// serializes all cookies
		$cookies = json_encode(array_map(function ($cookie) {return $cookie->toArray();}, 
			$cookieJar->all(null, null, null, false, true)));
		$principal->setValueInGeneralContext($this->getCookieJarSessionKey($jasperUserName),
			$cookies);
		$this->debugLogger()->write("stores cookies in session (".$cookieJar->count()." cookies):".$cookies);
	}
	
	private function getCookieJarSessionKey($jasperUserName) {
		return '('.$this->getJasperServerUrl().$this->getJasperServerRootPath().'('.$jasperUserName.'))';
	}
	
	/**
	 * Returns the XML body to be used as a command to run the report
	 * (uses the REST Report Service) 
	 * @param string $reportUri the relative uri of the report to run
	 * @param Record $reportDefinition a wigii element containing the report definition and parameters
	 * @param FieldSelectorList $reportParams a fieldselector list with the report parameters
	 */
	protected function getXmlCmdStringForExecuteReport($reportUri, $reportDefinition, $reportParams=null) {
		$returnValue = "<?xml version='1.0' standalone='yes'?>".'<resourceDescriptor name="" wsType="reportUnit" uriString="'.$reportUri.'" isNew="false"><label></label>';
		//$returnValue .= '<resourceProperty name="RUN_OUTPUT_FORMAT"><value>'.$format.'</value></resourceProperty>';
		if(isset($reportParams)) {
			foreach($reportParams->getListIterator() as $fs) {				
				$param = $fs->getFieldName();			
				$value = $reportDefinition->getFieldValue($param);
				/* todo: validate date format to be compatible with jasper.
				$dtName = $reportDefinition->getFieldList()->getField($param)->getDataType()->getDataTypeName();
				if($dtName == 'Dates') {
					$value = Dates::formatDisplay($value, 'mm-dd-yyyy', null);
				}
				*/
				//$returnValue .='<parameter name="'.$param.'"><![CDATA['.$value.']]></parameter>';
				$returnValue .='<parameter name="'.$param.'">'.$value.'</parameter>';
			}
		}
		$returnValue .= '</resourceDescriptor>';
		return $returnValue;
	}
	
	/**
	 * Returns a Guzzle http request object to be used to run the report
	 * @param mixed $httpClient the Guzzle http client to use 
	 * @param mixed $executeReportXmlCmd the xml cmd body to be sent to JasperReport
	 */
	protected function getHttpRequestForExecuteReport($httpClient, $reportUri, $format, $executeReportXmlCmd) {
		return $httpClient->put($this->getJasperServerRootPath().'/rest/report'.$reportUri, null, $executeReportXmlCmd,
			array('query' => array('RUN_OUTPUT_FORMAT' => $format)));
	}	
	
	/**
	 * Returns a Guzzle http request object to be used to log into jasper
	 * @param mixed $httpClient the guzzle http client to use
	 * @param string $userName the jasper username
	 * @param string $password the jasper user password
	 */
	protected function getHttpRequestForLogin($httpClient, $userName, $password) {
		return $httpClient->get($this->getJasperServerRootPath().'/rest/login', null, 
			array('query' => array('j_username' => $userName, 'j_password' => $password)));
	}
	
	/**
	 * Returns a Guzzle http request object to be used to fetch a report part file once executed
	 * @param mixed $httpClient the guzzle http client to use
	 * @param mixed $reportId the generated report ID
	 * @param mixed $partName the file name of the report part as specified in the report execution summary
	 */
	protected function getHttpRequestForGetReportPart($httpClient, $reportId, $partName) {
		return $httpClient->get($this->getJasperServerRootPath().'/rest/report/'.$reportId, null, 
			array('query' => array('file' => $partName)));
	}

	/**
	 * Processes the http request that fetches a single report
	 * and sends the content to the given data flow.
	 * @param Principal $principal the wigii principal behind this
	 * @param mixed $httpRequest the guzzle http request used to fetch a single report
	 * @param DataFlowActivitySelectorList $dfasl the DataFlowActivitySelectorList describing the DataFlow where to send the content
	 */
	protected function processHttpRequestForSingleReport($principal, $httpRequest, $dfasl) {
		return $this->getGuzzleHelper()->processHttpRequest($principal, $httpRequest, $dfasl, $this->getHttpResponseBufferSize());
	}
	
	/**
	 * Processes the http response of the request that fetched a report part
	 * and sends the content to the given data flow.
	 * @param Principal $principal the wigii principal behind all this
	 * @param mixed $httpResponse the Guzzle http response
	 * @param DataFlowActivitySelectorList $dfasl a DataFlowActivitySelectorList describing the data flow where to send the content
	 */
	protected function processHttpResponseForReportPart($principal, $httpResponse, $dfasl) {
		return $this->getGuzzleHelper()->processHttpResponseBody($principal, $httpResponse->getBody(), $dfasl, $this->getHttpResponseBufferSize());
	}	
	
	protected function processHttpResponseForReportHtmlIndex($principal, $httpResponse, $dfasl) {
		// gets html index
		$htmlIndex = $httpResponse->getBody(true);		
		// replaces all part file names
		$originalFileNames = array_keys($this->multipartFileNamesReplacement);
		$this->debugLogger()->write('original file names: '.array2str($originalFileNames));
		$replacedFileNames = array_values($this->multipartFileNamesReplacement);
		$this->debugLogger()->write('replaced file names: '.array2str($replacedFileNames));
		$htmlIndex = str_replace($originalFileNames, $replacedFileNames, $htmlIndex);
		// processes the html index
		return $this->getDataFlowService()->processString($principal, $htmlIndex, $dfasl);
	}
}