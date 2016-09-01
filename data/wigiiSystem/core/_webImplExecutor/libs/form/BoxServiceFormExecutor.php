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
 * Created by Medair (Arnaud Mader) on June 21st 2016
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
	
	/**	
	 * 
	 * @param unknown $principal
	 * @param unknown $id -> FileId
	 * @throws BoxServiceException
	 * @throws Exception
	 * @return NULL
	 * @see https://docs.box.com/reference#update-metadata
	 */
	protected function boxFileMetadata($principal, $id){
		
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
					// Gets file meta information: file creator id					
					$obj = $this->getFileInformation($principal, $httpClient, $id);
					
					$idCreater = $obj->{'created_by'}->{'id'};
					
					// Get temporary url to preview file
					$httpRequest = $this->getHttpRequestEmbedLink($httpClient, $id);
					$httpRequest->addHeader('Authorization', 'Bearer '.$this->getXmlElement('accessToken'));
					
					//Test if the creator of the file is the App, if yes adding the As-User header
					if($this->getXmlElement('userId') !== $idCreater){
						$httpRequest->addHeader('As-User', $idCreater);	
					}
					$response = $this->boxQuery($principal, $httpClient, $httpRequest);
					
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
					$idCreater = $obj->{'created_by'}->{'id'};
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
	 * @param unknown $principal
	 * @param unknown $httpClient
	 * @param unknown $idFile : the id of the file we want to change the name
	 * @param unknown $fileName : the new name of the file
	 * @param unknown $extension : the extension of the new file
	 * @throws BoxServiceException
	 * This function change the name of the file in Box with the name of the local's file that the user want to modify
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
	 * Function that upload a new version of a file only if the bothe mime type are the same. If the name of the new file is different of the old name, 
	 * we change the name of the box's file with the new one.
	 * @param unknown $principal
	 * @param unknown $httpClient
	 * @param unknown $idOldVersionFile : the old id of the file
	 * @param unknown $fileName : the new file name
	 * @param unknown $filePath : the path in the temporary folder
	 * @param unknown $mimeType : the mime type of the file
	 * @param unknown $fieldName : the field that contains the name of the document
	 * @param unknown $element : a set of parameters with the name, path, mimetype of the element (file)
	 * @throws BoxServiceException
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
			throw new BoxServiceException("Both file is not the same format, it must be $extension", ServiceException::UNEXPECTED_ERROR);
		}
	}
	
	/**
	 * return the download's url of a file
	 * @param unknown $principal
	 * @param unknown $httpClient
	 * @param unknown $id : the id of a file
	 * @throws BoxServiceException
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
	 * return information about a file
	 * @param unknown $principal
	 * @param unknown $httpClient
	 * @param unknown $id : the id of the file
	 * @throws BoxServiceException
	 * @return mixed
	 */
	private function getFileInformation($principal, $httpClient, $id){
		try {
			$httpRequest = $this->getHttpRequestFile($httpClient, $id);
			$httpRequest->addHeader('Authorization', 'Bearer '.$this->getXmlElement('accessToken'));
			$response = $this->boxQuery($principal, $httpClient, $httpRequest);
			// Parse reponse
			$data = $response->getBody();
			$obj = json_decode($data);
		
			return $obj;
		}catch(BoxServiceException $be){
			throw $be;
		}
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
	 * @throws BoxServiceException
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
	 * Return the folder's name
	 * @param unknown $folderId : the id of the folder we want the name
	 * @param unknown $httpClient
	 * @throws BoxServiceException
	 * @return unknown
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
	
	/**
	 * Function that returns an array with the id of the folder and the name of the folder
	 * @param unknown $principal
	 * @param unknown $folderId : the id of the folder we want the name
	 * @throws BoxServiceException
	 * @throws Exception
	 */
	public function getFolderNameFromFolderId($principal, $folderId){
		
		$this->executionSink()->publishStartOperation("getFolderNameFromFolderId", $principal);
		$returnValue = null;
		$array = array();
		
		try {
			// throws exception if Box integration is disabled
			if(!$this->isBoxEnabled()) throw new BoxServiceException('Box integration has been disabled. Cannot have Box file information.', BoxServiceException::CONFIGURATION_ERROR);
		
			$httpClient = $this->getGuzzleHelper()->createHttpClient($principal);
			// Tries max 5 times to get a valid token in case of busy or expired token
			$retry=false; $nTries=0;
			do {
				try {
						
					$array[0] = $folderId;
					$array[1] = $this->getFolderName($folderId, $httpClient);
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
		
		return $array;
		
		@unlink($filePath);
		$this->executionSink()->publishEndOperation("getFolderNameFromFolderId", $principal);
		return $returnValue;
		
	}
	
	/**
	 * Function that returns an array with the id of the folder and the name of the folder
	 * @param unknown $principal
	 * @param unknown $idFile : the id of the file we want have information about the parent folder (id and name)
	 * @throws BoxServiceException
	 * @throws Exception
	 */
	public function getFolderId($principal, $idFile){
		
		$this->executionSink()->publishStartOperation("getFolderId", $principal);
		$returnValue = null;
		$array = array();
		
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
				
					$array[0] = $folderId;
					$array[1] = $this->getFolderName($folderId, $httpClient);
					
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
		
		return $array;
		
		$this->executionSink()->publishEndOperation("fileInfo", $principal);
		return $returnValue;
	
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