<?php
/**
 *  This file has been added to Wigii by Medair.
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
 *  @copyright  Copyright (c) 2016  Medair
 *  @author     <http://www.medair.org>            Medair.org
 *  @link       <http://www.wigii-system.net>      <https://github.com/wigii/wigii>   Source Code
 *  @license    <http://www.gnu.org/licenses/>     GNU General Public License
 */

/**
 * Box.com Service client
 * Allows to preview, download or upload files located on Box
 * Created by Medair (AMA, CWE) on June 21st 2016
 */
class BoxServiceFormExecutor extends WebServiceFormExecutor {
	
	// Box Configuration file 
	// Lock state
	const BUSY = "Busy";
	const IDLE = "Idle";
	// Value cache
	private $boxConfigCache;
	
	private $_debugLogger;
	private $_executionSink;
	
	// Dependency injection
	private function debugLogger(){
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("BoxServiceFormExecutor");
		}
		return $this->_debugLogger;
	}
	
	
	private function executionSink(){
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("BoxServiceFormExecutor");
		}
		return $this->_executionSink;
	}
	
	private $guzzleHelper;
	public function setGuzzleHelper($guzzleHelper)
	{
		$this->guzzleHelper = $guzzleHelper;
	}
	
	
	protected function getGuzzleHelper()
	{
		// autowired
		if(!isset($this->guzzleHelper)){
			$this->guzzleHelper = TechnicalServiceProvider::getGuzzleHelper();
		}
		return $this->guzzleHelper;
	}
	
	private $mySqlF;
	public function setMySqlFacade($mysqlFacade)
	{
		$this->mysqlF = $mysqlFacade;
	}
	protected function getMySqlFacade()
	{
		// autowired
		if(!isset($this->mysqlF))
		{
			$this->mysqlF = TechnicalServiceProvider::getMySqlFacade();
		}
		return $this->mysqlF;
	}
	private $dbAS;
	public function setDbAdminService($dbAdminService)
	{
		$this->dbAS = $dbAdminService;
	}
	protected function getDbAdminService()
	{
		// autowired
		if(!isset($this->dbAS))
		{
			$this->dbAS = ServiceProvider::getDbAdminService();
		}
		return $this->dbAS;
	}	
	
	// Configuration
	
	/**
	 * Box Service is authorized to run with a valid Public Principal (used from external Access)
	 */
	public function isPublicPrincipalAuthorized() {return $this->getWigiiExecutor()->doesPrincipalForDownloadRequestShouldBePublic();}
	
	private $fileName;
	public function setBoxConfigFileName($fileName) {
		$this->fileName = $fileName;
	}
	protected function getBoxConfigFileName() {
		if(!isset($this->fileName)) {
			$this->fileName = CLIENT_CONFIG_PATH.'Box_config.xml';
		}
		return $this->fileName;
	}
	
	private $showWarning;
	public function setShowWarningIfBoxUpload($showWarning) {
		$this->showWarning = $showWarning;
	}
	public function getShowWarningIfBoxUpload() {
		if(!isset($this->showWarning)) {
			$this->showWarning = false;
		}
		return $this->showWarning;
	}
	
	
	private $syncBoxFileNameInWigii;
	private $fileFieldIdToSync;
	/**
	 * Requests Box service to update File information into the Wigii database, next time Box service reads file information of a given file.
	 * @param String $fileFieldId if given, a JS code will be sent back to the given field of type Files in the Browser, to update the name and other meta data.
	 */
	public function setSyncBoxFileInfoIntoWigii($fileFieldId) {
		$this->syncBoxFileNameInWigii = true;
		$this->fileFieldIdToSync = $fileFieldId;
	}
	
	
	/**
	 * Returns a box configuration token value from the Box_config.xml, given its name
	 * @throws ServiceException
	 * @return String the configuration element value
	 */
	protected function getXmlElement($element){
		// First looks in cache
		if(!isset($this->boxConfigCache)) $this->boxConfigCache = array();
		$returnValue = $this->boxConfigCache[$element];
		// If not in cache then loads it
		if(empty($returnValue)) {
			$xml = @simplexml_load_file($this->getBoxConfigFileName());
			if(!$xml) throw new ServiceException("Problem with the config document", ServiceException::CONFIGURATION_ERROR);
			$returnValue = (string)$xml->$element;
			// Puts the static values in cache
			$this->boxConfigCache['clientId'] = (string)$xml->clientId;
			$this->boxConfigCache['clientSecret'] = (string)$xml->clientSecret;
			$this->boxConfigCache['refreshUrl'] = (string)$xml->refreshUrl;
			$this->boxConfigCache['boxUrl'] = (string)$xml->boxUrl;
			$this->boxConfigCache['boxUpload'] = (string)$xml->boxUpload;
		}
		return $returnValue;
	}
	
	protected function setDateStatus(){
		$xml = @simplexml_load_file($this->getBoxConfigFileName());
		if(!$xml){
			throw new ServiceException("Problem with the config document", ServiceException::CONFIGURATION_ERROR);
		}else{
			$xml->statusDate = (string)date('Y-m-d H:i:s');
			$xml->asXML($this->getBoxConfigFileName());
		}
	}
	
	protected function setDateUpdate(){
		$xml = @simplexml_load_file($this->getBoxConfigFileName());
		if(!$xml){
			throw new ServiceException("Problem with the config document", ServiceException::CONFIGURATION_ERROR);
		}else{
			$xml->modificationDate = (string)date('Y-m-d H:i:s');
			$xml->asXML($this->getBoxConfigFileName());
		}
	}
	
	
	protected function setLock($state){
		$xml = @simplexml_load_file($this->getBoxConfigFileName());
		
		if(!$xml){
			throw new ServiceException("Problem with the config document", ServiceException::CONFIGURATION_ERROR);
		}else{
			$xml->lock = $state;
			$xml->asXML($this->getBoxConfigFileName());
		}
	}
	
	
	protected function setToken($access, $refresh){
		$xml = @simplexml_load_file($this->getBoxConfigFileName());
		if(!$xml){
			throw new ServiceException("Problem with the config document", ServiceException::CONFIGURATION_ERROR);
		}else{
			$xml->accessToken = $access;
			$xml->refreshToken = $refresh;
			$xml->asXML($this->getBoxConfigFileName());
		}
	}
	
	
	// BoxService WebExecutor implementation
	
	
	public function processAndEnds($p,$exec) {	
		// Extract arguemtns
		$args = $exec->getCrtParameters();		
		if(empty($args)) $nArgs = 0; else $nArgs = count($args);
		
		if($nArgs > 0) {
			$id = ValueObject::createInstance();
			// case preview
			if(arrayMatch($args, "preview", $id)) {
				try{
					$this->boxPreview($p, $id->getValue());
				}catch(Exception $e){
					echo $e->getMessage();
				}
			}
			// case download
			elseif(arrayMatch($args, "download", $id)) {
				try{
					$this->boxDownload($p, $id->getValue());
				}catch(Exception $e){
					echo $e->getMessage();
				}
			// default
			}else throw new FormExecutorException('Unsupported Box request',FormExecutorException::UNSUPPORTED_OPERATION);
		}else throw new FormExecutorException('Unsupported Box request',FormExecutorException::UNSUPPORTED_OPERATION);
	}
	
	protected function refreshToken($principal){
		$this->executionSink()->publishStartOperation("refreshToken", $principal);
		$returnValue = null;
		try {
			$httpClient = $this->getGuzzleHelper()->createHttpClient($principal, $this->getRefreshUrl());
			// Prepares token refresh request
			$httpRequest = $this->getHttpRequestRefresh($httpClient);
			$body = array(
					'grant_type' => 'refresh_token',
					'refresh_token' => $this->getXmlElement('refreshToken'),
					'client_id' => $this->getXmlElement('clientId'),
	  				'client_secret' => $this->getXmlElement('clientSecret')
			);			
			$httpRequest->setBody($body);
			// Sends request
			$response = $this->getGuzzleHelper()->sendHttpRequest($principal, $httpClient, $httpRequest);
			// Parses new token pair
			$data = $response->getBody();
			$obj = json_decode($data);
			$refresh = $obj->{'refresh_token'};
			$access = $obj->{'access_token'};
			// Stores new token pair in box xml config file
			$this->setToken($access, $refresh);
			$this->setDateUpdate();
			$this->setDateStatus();
			$this->setLock($this::IDLE);
		}
		catch(Exception $e) {
			$this->executionSink()->publishEndOperationOnError("refreshToken", $e, $principal);
			throw new ServiceException("Contact the IT Development to update the Box Access\n", ServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("refreshToken", $principal);
		return $returnValue;
	}
	
	protected function boxPreview($principal, $id) {
		$this->executionSink()->publishStartOperation("boxPreview", $principal);
		$returnValue = null;
		try {
			// throws exception if Box integration is disabled
			if(!$this->isBoxEnabled()) throw new BoxServiceException('Box integration has been disabled. Cannot preview Box file.', BoxServiceException::CONFIGURATION_ERROR);		
			
			$httpClient = $this->getGuzzleHelper()->createHttpClient($principal);
			// Tries max 5 times to get a valid token in case of busy or expired token
			$retry=false; $nTries=0;

			do {
				try {
					// Gets file meta information: 
					$obj = $this->getFileInformation($principal, $httpClient, $id);
					$creatorId = $obj->{'created_by'}->{'id'};
					$lastModifierId = $obj->{'modified_by'}->{'id'};
					$ownerId = $obj->{'owned_by'}->{'id'};
					
					// Get temporary url to preview file
					$httpRequest = $this->getHttpRequestEmbedLink($httpClient, $id);
					$httpRequest->addHeader('Authorization', 'Bearer '.$this->getXmlElement('accessToken'));
					
					$appUserId = $this->getXmlElement('userId');
					$response = null;
					// 1. first tries as file owner
					if(is_null($response) && $ownerId!==$appUserId) {
						$httpRequest->setHeader('As-User', $ownerId);
						try {$response = $this->boxQuery($principal, $httpClient, $httpRequest);}
						catch(BoxServiceException $asUserExc) {
							if($asUserExc->getCode()!=BoxServiceException::ERROR403) throw $asUserExc;
							$response = null;
						}
					}
					// 2. then tries as file last modifier
					if(is_null($response) && $lastModifierId!==$appUserId) {
						$httpRequest->setHeader('As-User', $lastModifierId);
						try {$response = $this->boxQuery($principal, $httpClient, $httpRequest);}
						catch(BoxServiceException $asUserExc) {
							if($asUserExc->getCode()!=BoxServiceException::ERROR403) throw $asUserExc;
							$response = null;
						}
					}
					// 3. then tries as file creator
					if(is_null($response) && $creatorId!==$appUserId) {
						$httpRequest->setHeader('As-User', $creatorId);
						try {$response = $this->boxQuery($principal, $httpClient, $httpRequest);}
						catch(BoxServiceException $asUserExc) {
							if($asUserExc->getCode()!=BoxServiceException::ERROR403) throw $asUserExc;
							$response = null;
						}
					}
					// 4. then tries with App user.
					if(is_null($response)) {
						$httpRequest->removeHeader('As-User');
						$response = $this->boxQuery($principal, $httpClient, $httpRequest);
					}
					
					$data = $response->getBody();
					$obj = json_decode($data);
					$fileUrl = $obj->{'expiring_embed_link'}->{'url'};
					$retry=false;
					// sends back preview iframe
					?>
						<html>
							<head></head>
							<body>									
								<iframe align="center" width="100%" height="100%" frameborder="0" src="<?php echo $fileUrl;?>"></iframe>									
							</body>
						</html>
					<?
				}catch(BoxServiceException $be) {
					
					if(($be->getCode() == BoxServiceException::NEW_TOKEN) || ($be->getCode() == ServiceException::OPERATION_CANCELED)){
						$retry=true;
					}else{
						throw $be;
					}
				}
				
				if($nTries>=4){
					$retry=false;
					throw new BoxServiceException("There is a problem with the Box access or the access document is busy. Please try later or if the problem persists contact the IT team.", ServiceException::UNEXPECTED_ERROR);
				}
				$nTries++;
			} while($retry);	
		}
		catch(Exception $e) {
			$this->executionSink()->publishEndOperationOnError("boxPreview", $e, $principal);
			throw $e;
		}
		$this->executionSink()->publishEndOperation("boxPreview", $principal);
		return $returnValue;
	}

	protected function boxDownload($principal, $id) {
		$this->executionSink()->publishStartOperation("boxDownload", $principal);
		$returnValue = null;
		try {
			// throws exception if Box integration is disabled
			if(!$this->isBoxEnabled()) throw new BoxServiceException('Box integration has been disabled. Cannot download Box file.', BoxServiceException::CONFIGURATION_ERROR);
			
			$httpClient = $this->getGuzzleHelper()->createHttpClient($principal);
			// Tries max 5 times to get a valid token in case of busy or expired token
			$retry=false; $nTries=0;
			do {
				try {
					// 1. Request file meta information on Box
					$obj = $this->getFileInformation($principal, $httpClient, $id);
					
					$filename = $obj->{'name'};
					$filesize = $obj->{'size'};
					$mime = typeMime('.'.strtolower(array_pop(explode('.',$filename))));
						
					// 2. Request file location on Box
					$url = $this->getUrlForDownload($principal, $httpClient, $id);
					
					$retry = false;
					
					header('Pragma: public');
					header('Cache-Control: max-age=0');
					header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
					header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
					header('Content-type: '.$mime);
					header('Content-Length: '.$filesize);
					header('Content-Disposition: attachment; filename="'.$filename.'"');
					header('Location:'.$url);
					
				}catch(BoxServiceException $be) {
						
					if(($be->getCode() == BoxServiceException::NEW_TOKEN) || ($be->getCode() == BoxServiceException::OPERATION_CANCELED)){
						$retry=true;
					}else{
						throw $be;
					}
				}
				
				if($retry && $nTries>=4){
					$retry=false;
					throw new BoxServiceException("There is a problem with the Box access or the access document is busy. Please try later or if the problem persists contact the IT team.", BoxServiceException::OPERATION_CANCELED);
				}
				$nTries++;
				
			} while($retry);
		}
		catch(Exception $e) {
			$this->executionSink()->publishEndOperationOnError("boxDownload", $e, $principal);
			throw $e;
		}
		$this->executionSink()->publishEndOperation("boxDownload", $principal);
		return $returnValue;
	}
	
	/**
	 * @throws BoxServiceException::NOT_IMPLEMENTED
	 */
	protected function boxFileMetadata($principal, $id){
	
		BoxServiceException::throwNotImplemented();
	
		$this->executionSink()->publishStartOperation("boxFileMetadata", $principal);
		$returnValue = null;
	
		try {
			// throws exception if Box integration is disabled
			if(!$this->isBoxEnabled()) throw new BoxServiceException('Box integration has been disabled. Cannot preview Box file.', BoxServiceException::CONFIGURATION_ERROR);
	
			$httpClient = $this->getGuzzleHelper()->createHttpClient($principal);
			// Tries max 5 times to get a valid token in case of busy or expired token
			$retry=false; $nTries=0;
	
			do {
				try{
					$attributes = array(array("op"=>"add", "path"=>"/", "value"=>""), array("op"=>"add", "path"=>"/", "value"=>""), array("op"=>"add", "path"=>"/", "value"=>""));
	
					$httpRequest = $this->getMetadataUrl($httpClient, $id);
					$httpRequest->addHeader('Authorization', 'Bearer '.$this->getXmlElement('accessToken'));
					$httpRequest->addHeader('Content-Type', 'application/json-patch+json');
	
					$httpRequest->setBody(json_encode($attributes));
	
					$response = $this->boxQuery($principal, $httpClient, $httpRequest);
				}catch(Exception $e){
						
					if($e->getCode() === ServiceException::WRAPPING){
							
						try{
							$attributes = array(""=>"", ""=>"", ""=>"");
	
							$httpRequest = $this->createMetadataUrl($httpClient, $id);
							$httpRequest->addHeader('Authorization', 'Bearer '.$this->getXmlElement('accessToken'));
							$httpRequest->addHeader('Content-Type', 'application/json');
								
							$httpRequest->setBody(json_encode($attributes));
								
							$response = $this->boxQuery($principal, $httpClient, $httpRequest);
						}catch(Exception $e){
							throw new Exception("Problem with the creation of metadata for the file".$e->getCode());
						}
					}else{
						throw new Exception("Problem with the update of the metadat for the file".$e->getCode());
					}
				}
				if($nTries>=4){
					$retry=false;
					throw new BoxServiceException("There is a problem with the Box access or the access document is busy. Please try later or if the problem persists contact the IT team.", ServiceException::UNEXPECTED_ERROR);
				}
				$nTries++;
			} while($retry);
		}catch(Exception $e) {
			$this->executionSink()->publishEndOperationOnError("boxFileMetadata", $e, $principal);
			throw $e;
		}
		$this->executionSink()->publishEndOperation("boxFileMetadata", $principal);
		return $returnValue;
	}
	
	// HTTP requests and Box URLs
	
	/**
	 * Returns the Box Web user interface URL (not the API)
	 */
	public function getBoxWebInterfaceUrl() {
		return $this->getXmlElement('boxWebUrl');
	}
	
	protected function getBoxUpload(){
		return $this->getXmlElement('boxUpload');
	}
	
	protected function getBoxUrl($id){
		return $this->getXmlElement('boxUrl').$id;
	}
	
	
	protected function getBoxDownload($id){
		return $this->getXmlElement('boxUrl').$id."/content";
	}
	
	
	protected function getBoxFolderUrl($folderId){
		return $this->getXmlElement('boxFolderUrl').$folderId;
	}
	
	
	protected function getRefreshUrl(){
		return $this->getXmlElement('refreshUrl');
	}
	
	
	protected function getHttpRequestEmbedLink($httpClient, $id){
		return $httpClient->get($this->getBoxUrl($id),null,array('query' => array('fields' => 'expiring_embed_link')));
	}
	
	protected function getHttpRequestFolderInfo($httpClient, $folderId){
		return $httpClient->get($this->getBoxFolderUrl($folderId),null,array());
	}

	
	protected function getHttpRequestFile($httpClient, $id){
		return $httpClient->get($this->getBoxUrl($id),null, array());
	}
	
	
	protected function getHttpRequestDownload($httpClient, $id){
		return $httpClient->get($this->getBoxDownload($id),null, array('allow_redirects'=>false));
	}
	
	
	protected function getHttpRequestRefresh($httpClient){
		return $httpClient->post($this->getRefreshUrl(),null, array());
	}
	
	
	protected function getHttpUploadFile($httpClient){
		return $httpClient->post($this->getBoxUpload()."content",null, array());
	}	
	
	
	protected function getHttpUploadFileVersion($httpClient, $id){
		return $httpClient->post($this->getBoxUpload()."$id/content",null, array());
	}
	
	
	protected function getHttpUpdateFilesInfoOnBox($httpClient, $id){
		return $httpClient->put($this->getBoxUrl($id),null, array());
	}
	
		
	protected function getMetadataUrl($httpClient, $id){
		return $httpClient->put($this->getBoxUrl($id)."/metadata/global/properties",null,array());
	}
	
	
	protected function createMetadataUrl($httpClient, $id){
		return $httpClient->post($this->getBoxUrl($id)."/metadata/global/properties",null,array());
	}
	
	protected function diffDate(){
		$timestamp_fichier = strtotime($this->getXmlElement('statusDate'));
		$timestamp_local = time();
		$differenceMinutes = floor(abs($timestamp_fichier - $timestamp_local)/60);
			
		return $differenceMinutes;
	}
	
	
	// Box facade Server side calls
	
	
	private $boxEnabled=null;
	/**
	 * Checks if Box integration is possible for current client
	 * @return Boolean Returns true if Client has a Box_config.xml with a filled client ID and DISABLE_BOX_INTEGRATION==false. 
	 */
	public function isBoxEnabled() {
		if($this->boxEnabled===null) {
			if(defined('DISABLE_BOX_INTEGRATION')) $disableBox = (DISABLE_BOX_INTEGRATION==true);
			else $disableBox=false;
			$this->boxEnabled = file_exists($this->getBoxConfigFileName()) && $this->getXmlElement('clientId') && !$disableBox;
		}
		return $this->boxEnabled;
	}
	
	/**
	 * Sends a JS code to client which initializes the Box File picker
	 * Box File Picker is stored under global JS variable boxSelect
	 * @param Principal $p current principal
	 * @param ExecutionServiceWebImpl $exec 
	 */
	public function initializeFilePicker($p,$exec) {
		$exec->addJsCode("if(!window.boxSelect) boxSelect = new BoxSelect({clientId:'".$this->getXmlElement('clientId')."',linkType:'shared',multiselect:'false'});");		
	}
	
	
	/**
	 * Uploads a File attached to a given element field into box
	 * @param Principal $principal authenticated user executing the process
	 * @param WigiiBPLParameter $parameter a map of parameters :
	 * - element: Element. The filled element containing the field of type Files
	 * - fieldName: String. The field name containing the File to persist.
	 * - mimeType: String. The mime type of the File.
	 * - boxFolderId: String. Box folder id in which to push the file
	 * @throws BoxServiceException
	 */
	public function boxUploadFileForField($principal, $parameter) {
	
		$this->executionSink()->publishStartOperation("boxUploadFileForField", $principal);
		$returnValue = null;
	
		$element = $parameter->getValue('element');
		$fieldName = $parameter->getValue('fieldName');
		$path = $element->getFieldValue($fieldName, "path");
	
	
		$boxFolderId = $parameter->getValue('boxFolderId');
		$fileName = $element->getFieldValue($fieldName, 'name');
		$fileType = $element->getFieldValue($fieldName, 'type');
		$file_name = $fileName.$fileType;
	
		$filePath = realpath(TEMPORARYUPLOADEDFILE_path.$path);
			
		$returnValue = null;
		try {
			// throws exception if Box integration is disabled
			if(!$this->isBoxEnabled()) throw new BoxServiceException('Box integration has been disabled. Cannot upload file to Box.', BoxServiceException::CONFIGURATION_ERROR);
				
			$httpClient = $this->getGuzzleHelper()->createHttpClient($principal);
			// Tries max 5 times to get a valid token in case of busy or expired token
			$retry=false; $nTries=0;
			do {
				try {
						
					$boxFileId = $element->getFieldValue($fieldName, 'path');
						
					if(strstr($boxFileId, "box://")){
						$retry=false;
					}else{
	
						$file_up = "@".$filePath;
	
						$httpRequest = $this->getHttpUploadFile($httpClient);
						$httpRequest->addHeader('Authorization', 'Bearer '.$this->getXmlElement('accessToken'));
	
						$attributes = array('name'=>$file_name,'parent'=>array('id'=>$boxFolderId));
	
						$httpRequest->setPostField('attributes',  json_encode($attributes));
						$httpRequest->addPostFile('file',  $file_up);
	
						$response = $this->boxQuery($principal, $httpClient, $httpRequest);
	
						$data = $response->getBody();
						$obj = json_decode($data);
						$fileId = $obj -> {'entries'}[0] -> {'id'};
	
						$element->setFieldValue("box://".$fileId, $fieldName, 'path');
						$retry=false;
					}
						
				}catch(BoxServiceException $be) {
					if(($be->getCode() == BoxServiceException::NEW_TOKEN) || ($be->getCode() == ServiceException::OPERATION_CANCELED)){
						$retry=true;
						// If file already exists into Box, then uploads a new version of it.
					}elseif ($be->getCode() == BoxServiceException::BOX_EXISTING_FILE){
						$retry=false;
	
						$errorBody = $be->getPreviousException()->getResponse()->getBody(true);
						$obj = json_decode($errorBody);
	
						$oldBoxId = $obj->{'context_info'}->{'conflicts'}->{'id'};
	
						$this->boxUploadFileVersionForField($p, wigiiBPLParam("element",$element,"fieldName",$fieldName, "mimeType", $parameter->getValue('mimeType'), "boxFolderId", $boxFolderId, "fileId", $oldBoxId));
	
					}else{
						throw $be;
					}
				}
	
				if($nTries>=5){
					$retry=false;
					throw new BoxServiceException("There is a problem with the Box access or the access document is busy. Please try later or if the problem persists contact the IT team.", ServiceException::UNEXPECTED_ERROR);
				}
				$nTries++;
			} while($retry);
		}
		catch(Exception $e) {
			$this->executionSink()->publishEndOperationOnError("boxUploadFileForField", $e, $principal);
			throw $e;
		}
	
		@unlink($filePath);
		$this->executionSink()->publishEndOperation("boxUploadFileForField", $principal);
		return $returnValue;
	}
	
	/**
	 * Uploads a new version into Box of a File attached to a given element field
	 * @param Principal $principal authenticated user executing the process
	 * @param WigiiBPLParameter $parameter a map of parameters :
	 * - element: Element. The filled element containing the field of type Files
	 * - fieldName: String. The field name containing the File to persist.
	 * - mimeType: String. The mime type of the File.
	 * - boxFolderId: String. Box folder id in which to push the file
	 * - fileId: String. Existing file id
	 * @throws BoxServiceException in case of error
	 */
	public function boxUploadFileVersionForField($principal, $parameter){
	
		$this->executionSink()->publishStartOperation("boxUploadFileVersionForField", $principal);
		$returnValue = null;
	
		$element = $parameter->getValue('element');
		$fieldName = $parameter->getValue('fieldName');
			
		$path = $element->getFieldValue($fieldName, "path");
			
		$mimeType = $parameter->getValue('mimeType');
		$boxFolderId = $parameter->getValue('boxFolderId');
		$idOldVersionFile = $parameter->getValue('fileId');
		$fileName = $element->getFieldValue($fieldName, 'name');
		$fileType = $element->getFieldValue($fieldName, 'type');
	
		$filePath = realpath(TEMPORARYUPLOADEDFILE_path.$path);
	
		try {
			// throws exception if Box integration is disabled
			if(!$this->isBoxEnabled()) throw new BoxServiceException('Box integration has been disabled. Cannot upload new version of file to Box.', BoxServiceException::CONFIGURATION_ERROR);
	
			$httpClient = $this->getGuzzleHelper()->createHttpClient($principal);
			// Tries max 5 times to get a valid token in case of busy or expired token
			$retry=false; $nTries=0;
			$erreur=false;
			do {
				try {
					$this->uploadFileVersion($principal, $httpClient, $idOldVersionFile, $fileName, $filePath, $mimeType, $fieldName, $element);
				}catch(BoxServiceException $be) {
					if(($be->getCode() == BoxServiceException::NEW_TOKEN) || ($be->getCode() == ServiceException::OPERATION_CANCELED)){
						$retry=true;
					}else{
						throw $be;
					}
				}
	
				if($nTries>=5){
					$retry=false;
					throw new BoxServiceException("There is a problem with the Box access or the access document is busy. Please try later or if the problem persists contact the IT team.", ServiceException::UNEXPECTED_ERROR);
				}
				$nTries++;
			} while($retry);
		}
		catch(Exception $e) {
			$this->executionSink()->publishEndOperationOnError("boxUploadFileVersionForField", $e, $principal);
			throw $e;
		}
	
		@unlink($filePath);
		$this->executionSink()->publishEndOperation("boxUploadFileVersionForField", $principal);
		return $returnValue;
	
	}
	
	/**
	 * Changes the name of the file in Box with the name of the local's file that the user wants to modify
	 * @param Principal $principal current principal
	 * @param Guzzle\Http\Client $httpClient
	 * @param String $idFile the Box id of the file we want to change the name
	 * @param String $fileName the new name of the file
	 * @param String $extension the extension of the new file
	 * @throws BoxServiceException in case of error
	 */	
	public function changeBoxFileName($principal, $httpClient, $idFile, $fileName, $extension){	
		try{
			$attributes = array('name'=>"$fileName$extension");
			$httpRequest = $this->getHttpUpdateFilesInfoOnBox($httpClient, $idFile);
			$httpRequest->addHeader('Authorization', 'Bearer '.$this->getXmlElement('accessToken'));
				
			$httpRequest->setBody(json_encode($attributes));
				
			$response = $this->boxQuery($principal, $httpClient, $httpRequest);
			
		}catch(Exception $e){
			if($e->getCode() === BoxServiceException::BOX_EXISTING_FILE){
				throw new BoxServiceException("A document with the same name already exist, please change the file's name", ServiceException::UNEXPECTED_ERROR);
			}else{
				throw new BoxServiceException("Some problem with the file's name updating occured ", ServiceException::UNEXPECTED_ERROR);
			}
		}
	}
	
	/**
	 * Uploads a new version of a file only if the mime type are the same. 
	 * If the name of the new file is different of the old name, we change the name of the box's file with the new one.
	 * @param Principal $principal current principal
	 * @param Guzzle\Http\Client $httpClient
	 * @param String $idOldVersionFile the old Box id of the file
	 * @param String $fileName the new file name
	 * @param String $filePath the path in the temporary folder
	 * @param String $mimeType the mime type of the file
	 * @param String $fieldName the field that contains the name of the document
	 * @param Element $element current element into which to update the File metadatas.
	 * @throws BoxServiceException in case of error
	 */
	private function uploadFileVersion($principal, $httpClient, $idOldVersionFile, $fileName, $filePath, $mimeType, $fieldName, $element){
	
		// Gets file meta information: file creator id
		$obj = $this->getFileInformation($principal, $httpClient, $idOldVersionFile);
	
		$etag = $obj->{'etag'};
	
		$oldFile = $obj->{'name'};
		$extension = '.'.strtolower(array_pop(explode('.',$oldFile)));
	
		$mime = typeMime($extension);
	
		if($mimeType === $mime){
	
			if($fileName !== $oldFile){
				$this->changeBoxFileName($principal, $httpClient, $idOldVersionFile, $fileName, $extension);
			}
			
			$file_up = "@".$filePath;
			$httpRequest = $this->getHttpUploadFileVersion($httpClient, $idOldVersionFile);
			$httpRequest->addHeader('Authorization', 'Bearer '.$this->getXmlElement('accessToken'));
				
			$httpRequest->addPostFile('file',  $file_up);
				
			$response = $this->boxQuery($principal, $httpClient, $httpRequest);
				
			$data = $response->getBody();
			$obj = json_decode($data);
			$fileId = $obj -> {'entries'}[0] -> {'id'};
	
			$element->setFieldValue("box://".$fileId, $fieldName, 'path');
				
			$retry=false;
		}else{
			throw new BoxServiceException("Both file are not the same format, it must be $extension", ServiceException::INVALID_ARGUMENT);
		}
	}
	
	/**
	 * Returns the download's url of a file
	 * @param Principal $principal current principal
	 * @param Guzzle\Http\Client $httpClient $httpClient
	 * @param String $id the Box id of a file
	 * @throws BoxServiceException in case of error
	 */
	private function getUrlForDownload($principal, $httpClient, $id){
	
		$httpRequest = $this->getHttpRequestDownload($httpClient, $id);
		$httpRequest->addHeader('Content-length', 0);
		$httpRequest->addHeader('Content-type', 'application/json');
		$httpRequest->addHeader('Authorization', 'Bearer '.$this->getXmlElement('accessToken'));
	
		$response = $this->boxQuery($principal, $httpClient, $httpRequest);
			
		if($response->getStatusCode() != 302) throw new BoxServiceException("Cannot retrieve file location on Box (http return code is ".$response->getStatusCode().")", BoxServiceException::UNEXPECTED_ERROR);
		$url = (string)$response->getHeader('Location');
	
		return $url;
	}
	/**
	 * Return information about a file
	 * @param Principal $principal current principal
	 * @param Guzzle\Http\Client $httpClient $httpClient
	 * @param String $id the Box id of the file
	 * @throws BoxServiceException in case of error
	 * @return StdClass Box file information Std Object
	 */
	private function getFileInformation($principal, $httpClient, $id){
		try {
			$httpRequest = $this->getHttpRequestFile($httpClient, $id);
			$httpRequest->addHeader('Authorization', 'Bearer '.$this->getXmlElement('accessToken'));
			$response = $this->boxQuery($principal, $httpClient, $httpRequest);
			// Parse reponse
			$data = $response->getBody();
			$obj = json_decode($data);
			// Syncs file info if requested
			if(isset($obj) && $this->syncBoxFileNameInWigii && $obj->{'id'}==$id) {
				$this->debugLogger()->write('syncs file info from Box to Wigii');
				$this->updateFileInformationIntoDb($principal, $obj);
				if($this->fileFieldIdToSync) {
					$this->updateFileInformationOnBrowser($principal, $this->fileFieldIdToSync, $obj);
					$this->fileFieldIdToSync=null;
				}
				$this->syncBoxFileNameInWigii=false;
			}
			return $obj;
		}catch(BoxServiceException $be){
			throw $be;
		}
	}
	/**
	 * Updates meta info of all fields of type Files stored into the database with a path equal to box://boxFileID
	 * @param Principal $principal current principal
	 * @param StdClass $fileInfoObj Box File info object as retrieved by calling getFileInformation.
	 */
	private function updateFileInformationIntoDb($principal, $fileInfoObj) {
		$this->debugLogger()->logBeginOperation('updateFileInformationIntoDb');
		if(isset($fileInfoObj)) {
			// Prepares File info details
			$boxFileId = $fileInfoObj->{'id'};
			$name = explode('.',$fileInfoObj->{'name'});
			$type = '.'.strtolower(array_pop($name));
			$name = implode('.',$name);
			$size = $fileInfoObj->{'size'};
			$date = $fileInfoObj->{'modified_at'};
			if(!empty($date)) $date = date_timestamp_get(date_create_from_format('Y-m-d\\TH:i:sT', $date));
			// Updates Files element fields linked to the given Box ID with the new file info
			$this->getMySqlFacade()->update($principal,
				$this->getSqlForUpdateFileInformationIntoDb($principal,$boxFileId,$name,$type,$size,$date),
				$this->getDbAdminService()->getDbConnectionSettings($principal)
			);
		}
		$this->debugLogger()->logEndOperation('updateFileInformationIntoDb');
	}
	private function getSqlForUpdateFileInformationIntoDb($principal,$boxFileId,$name,$type,$size,$date) {
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForUpdate('Files');
		$sqlB->updateValue('name', $name, MySqlQueryBuilder::SQLTYPE_VARCHAR);
		$sqlB->updateValue('type', $type, MySqlQueryBuilder::SQLTYPE_VARCHAR);
		$sqlB->updateValue('size', $size, MySqlQueryBuilder::SQLTYPE_BIGINT);
		$sqlB->updateValue('mime', typeMime($type), MySqlQueryBuilder::SQLTYPE_VARCHAR);
		$sqlB->updateValue('date', $date, MySqlQueryBuilder::SQLTYPE_DATETIME);
		$sqlB->updateSysUser($principal);
		$sqlB->setWhereClause($sqlB->formatBinExp('path', '=', 'box://'.$boxFileId, MySqlQueryBuilder::SQLTYPE_VARCHAR).' AND ('.
			$sqlB->formatBinExp('name', '!=', $name, MySqlQueryBuilder::SQLTYPE_VARCHAR).' OR '.
			$sqlB->formatBinExp('type', '!=', $type, MySqlQueryBuilder::SQLTYPE_VARCHAR).' OR '.
			$sqlB->formatBinExp('size', '!=', $size, MySqlQueryBuilder::SQLTYPE_BIGINT).' OR '.
			$sqlB->formatBinExp('date', '!=', $date, MySqlQueryBuilder::SQLTYPE_DATETIME)
		.')');
		return $sqlB->getSql();
	}
	/**
	 * Sends back a JS code which updates the File info for the given field of type Files based on box info.
	 * @param Principal $principal current principal
	 * @param String $fileFieldId HTML ID of field of type Files for which to send back a JS query to update File info into Browser.
	 * @param StdClass $fileInfoObj Box File info object as retrieved by calling getFileInformation.
	 */
	private function updateFileInformationOnBrowser($principal, $fileFieldId, $fileInfoObj) {
		$this->debugLogger()->logBeginOperation('updateFileInformationOnBrowser');
		if(isset($fileInfoObj)) {
			// File ID, name and type
			$boxFileId = $fileInfoObj->{'id'};
			$fileName = explode('.',$fileInfoObj->{'name'});
			$fileType = '.'.strtolower(array_pop($fileName));
			$fileName = implode('.',$fileName);

			// File mime
			$fileMime = typeMime($fileType);
			
			// File size
			$fileSize = $fileInfoObj->{'size'};
			$fileSizeFormatted = formatFileSize($fileSize);
			
			// File date
			$date = $fileInfoObj->{'modified_at'};
			$fileDate = null;
			$fileDateFormatted = null;
			if(!empty($date)) {
				$date = date_timestamp_get(date_create_from_format('Y-m-d\\TH:i:sT', $date));
				$fileDate = date('Y-m-d H:i', $date);
				$fileDateFormatted = date('d.m.Y H:i', $date);
			}
			
			// Sends back JS callback
			ServiceProvider::getExecutionService()->addJsCode("boxUpdateFileInfo('$fileFieldId', '$fileName', '$fileType', '$fileMime', '$fileSize', '$fileSizeFormatted', '$fileDate', '$fileDateFormatted');");
		}
		$this->debugLogger()->logEndOperation('updateFileInformationOnBrowser');
	}
	
	/**
	 * Function that returns an array with the id of the folder and the name of the folder
	 * @param Principal $principal current principal
	 * @param unknown $folderId : the id of the folder we want the name
	 * @throws BoxServiceException in case of error
	 * @return Array [0=>folder ID, 1=>folder name]
	 */
	public function getFolderNameFromFolderId($principal, $folderId){
		
		$this->executionSink()->publishStartOperation("getFolderNameFromFolderId", $principal);
		$returnValue = array();
		try {
			// throws exception if Box integration is disabled
			if(!$this->isBoxEnabled()) throw new BoxServiceException('Box integration has been disabled. Cannot have Box file information.', BoxServiceException::CONFIGURATION_ERROR);
		
			$httpClient = $this->getGuzzleHelper()->createHttpClient($principal);
			// Tries max 5 times to get a valid token in case of busy or expired token
			$retry=false; $nTries=0;
			do {
				try {
						
					$returnValue[0] = $folderId;
					$returnValue[1] = $this->getFolderName($folderId, $httpClient);
					$retry=false;
					
				}catch(BoxServiceException $be) {
					if(($be->getCode() == BoxServiceException::NEW_TOKEN) || ($be->getCode() == ServiceException::OPERATION_CANCELED)){
						$retry=true;
					}else{
						throw $be;
					}
				}
		
				if($nTries>=4){
					$retry=false;
					throw new BoxServiceException("There is a problem with the Box access or the access document is busy. Please try later or if the problem persists contact the IT team.", ServiceException::UNEXPECTED_ERROR);
				}
				$nTries++;
			} while($retry);
		}
		catch(Exception $e) {
			$this->executionSink()->publishEndOperationOnError("getFolderNameFromFolderId", $e, $principal);
			throw $e;
		}
	
		$this->executionSink()->publishEndOperation("getFolderNameFromFolderId", $principal);
		return $returnValue;
		
	}
	
	/**
	 * Function that returns an array with the id of the folder and the name of the folder
	 * @param Principal $principal current principal
	 * @param String $idFile the Box id of the file we want have information about the parent folder (id and name)
	 * @throws BoxServiceException in case of error
	 * @return Array [0=>file folderID, 1=>folder name]
	 */
	public function getFolderId($principal, $idFile){
		$this->executionSink()->publishStartOperation("getFolderId", $principal);
		$returnValue = array();
		try {
			// throws exception if Box integration is disabled
			if(!$this->isBoxEnabled()) throw new BoxServiceException('Box integration has been disabled. Cannot have Box file information.', BoxServiceException::CONFIGURATION_ERROR);
				
			$httpClient = $this->getGuzzleHelper()->createHttpClient($principal);
			// Tries max 5 times to get a valid token in case of busy or expired token
			$retry=false; $nTries=0;
			do {
				try {
					// Gets file meta information: file creator id
					$obj = $this->getFileInformation($principal, $httpClient, $idFile);
					
					$folderId = $obj->{'parent'}->{'id'};
				
					$returnValue[0] = $folderId;
					$returnValue[1] = $this->getFolderName($folderId, $httpClient);
					
					$retry = false;
					
				}catch(BoxServiceException $be) {
					if(($be->getCode() == BoxServiceException::NEW_TOKEN) || ($be->getCode() == ServiceException::OPERATION_CANCELED)){
						$retry=true;
					}else{
						throw $be;
					}
				}
		
				if($nTries>=4){
					$retry=false;
					throw new BoxServiceException("There is a problem with the Box access or the access document is busy. Please try later or if the problem persists contact the IT team.", ServiceException::UNEXPECTED_ERROR);
				}
				$nTries++;
			} while($retry);
		}
		catch(Exception $e) {
			$this->executionSink()->publishEndOperationOnError("getFolderId", $e, $principal);
			throw $e;
		}
		
		$this->executionSink()->publishEndOperation("getFolderId", $principal);
		return $returnValue;
	}
	
	/**
	 * Return the folder's name give its ID
	 * @param String $folderId the id of the folder we want the name
	 * @param Guzzle\Http\Client $httpClient
	 * @throws BoxServiceException in case of error
	 * @return String then name of the folder
	 */
	public function getFolderName($folderId, $httpClient){
	
		try{
			$httpRequest = $this->getHttpRequestFolderInfo($httpClient, $folderId);
			$httpRequest->addHeader('Authorization', 'Bearer '.$this->getXmlElement('accessToken'));
	
			$response = $this->boxQuery($principal, $httpClient, $httpRequest);
	
			$data = $response->getBody();
			$obj = json_decode($data);
	
			$folderName = $obj->{'name'};
				
			return $folderName;
		}catch(BoxServiceException $be){
			throw $be;
		}
	}
	
	
	// Box facade implementation	
	
	private function boxQuery($principal, $httpClient, $httpRequest){	

		$lock = $this->getXmlElement('lock');
		$i = 0;
		
		while($lock === $this::BUSY && $this->diffDate()<15){
			sleep(1);
			$lock = $this->getXmlElement('lock');
			$i = $i + 1;
			
			if($i > 5){
				throw new BoxServiceException("There is a problem with the Box access or the access document is busy. Please try later or if the problem persists contact the IT team.", ServiceException::OPERATION_CANCELED);
			}
		}
		
		try{
			$response = $this->getGuzzleHelper()->sendHttpRequest($principal, $httpClient, $httpRequest);
			return $response;
		}catch(Exception $e){
			
			$statusCode = $e->getResponse()->getStatusCode();
			
			if($statusCode == 401) {
					
				if($lock=== $this::BUSY && $this->diffDate()<15){
					sleep(1);
					throw new BoxServiceException("Busy new Token", ServiceException::OPERATION_CANCELED);
				}else{
					
					$this->setLock($this::BUSY);
					$this->refreshToken($principal);
					
					throw new BoxServiceException("Busy new Token", BoxServiceException::NEW_TOKEN);

				}
			}elseif ($statusCode == 400){
				throw new BoxServiceException("There is a problem with the Box access. Please try later or if the problem persists contact the IT team.\n", ServiceException::WRAPPING, $e);
			}elseif ($statusCode == 403){
				throw new BoxServiceException("Permission Error ".$e->getMessage(), BoxServiceException::ERROR403);
			}elseif ($statusCode == 404){
				throw new BoxServiceException("File not found", ServiceException::WRAPPING, $e);
			}elseif($statusCode == 405){
				throw new BoxServiceException("Contact the IT Development to update the Box Access ". $e->getResponse()->getMessage()."\n", ServiceException::WRAPPING, $e);
			}elseif($statusCode == 409){
				throw new BoxServiceException("The name of the file is already existing in the Box folder, please change the name of the file \n", BoxServiceException::BOX_EXISTING_FILE, $e);
			}elseif ($statusCode == 415){
				throw new BoxServiceException("This format is not supported\n", ServiceException::WRAPPING, $e);
			}else{
				throw new BoxServiceException("There is a problem with the Box access. Please try later or if the problem persists contact the IT team whith the error code ".$statusCode.".\n", ServiceException::WRAPPING, $e);
			}
		}
	}
}