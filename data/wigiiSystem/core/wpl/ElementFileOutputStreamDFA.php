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
 * A data flow activity which writes a stream of data into a file attached to an element field
 * This DataFlowActivity cannot be called from public space (i.e. caller is located outside of the Wigii instance)
 * Created by CWE 13.04.2016
 * Modified by Medair (CWE) on 15.12.2016 to protect against Cross Site Scripting
 */
class ElementFileOutputStreamDFA implements DataFlowActivity
{
	private $_debugLogger;
	private $textMode;
	private $fileName;
	private $fileHandle;
	private $fileFullPath;
	
	// Object lifecycle
	
	public function reset() {
		$this->freeMemory();	
		$this->persistElement=true;			
	}	
	public function freeMemory() {
		unset($this->element);
		unset($this->fieldName);
		unset($this->fileSubfields);
		unset($this->fileName);
		unset($this->fileHandle);
		unset($this->fileFullPath);
	}		
	
	// Dependency injection
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("ElementFileOutputStreamDFA");
		}
		return $this->_debugLogger;
	}		
	
	public function getTemporaryUploadedFilePath() {
		if(!isset($this->temporaryUploadedFilePath)){
			$this->temporaryUploadedFilePath = TEMPORARYUPLOADEDFILE_path;
		}
		return $this->temporaryUploadedFilePath;
	}
	public function setTemporaryUploadedFilePath($path) { $this->temporaryUploadedFilePath = $path; }
	
	// Configuration	
	
	protected $element;
	/**
	 * Sets the Element from which to dump the content of a File
	 * @param Element $element
	 */
	public function setElement($element) {
		$this->element = $element;
	}	
	
	protected $fieldName;
	/**
	 * Sets the Field of type Files for which to dump the content
	 * @param String|FieldSelector $fieldName
	 */
	public function setFieldName($fieldName) {
		if($fieldName instanceof FieldSelector) $this->fieldName = $fieldName->getFieldName();
		else $this->fieldName = $fieldName;
	}
	
	protected $persistElement;
	/**
	 * Defines if Element field should be saved into DB now, or hold into memory for further treatments.
	 * By default, Element field is saved to keep data integrity between disk and DB.
	 * @param Boolean $boolean if true then Element field will be persisted into DB, 
	 * else file is only put into TemporaryUploadedFilePath and Element field is not saved into DB, allowing further treatments.
	 */
	public function setPersistElement($boolean) {
		$this->persistElement=($boolean!==false);
	}
	
	protected $fileSubfields;
	/**
	 * Defines the File subfields which overrides current subfields from the Element
	 * @param Array $array an array which contains some of the keys name,type,size,date,username,user
	 */
	public function setFileSubfields($array) {
		$this->fileSubfields=$array;
	}
	
	/**
	 * If true, sets the text mode modifier,
	 * else always considers the stream as binary
	 * See php function fopen, the t and b modifiers
	 * Binary by default.
	 */
	public function setTextMode($bool) {
		$this->textMode = $bool;
	}
	
	// stream data event handling
	
	public function startOfStream($dataFlowContext) {
		$dataFlowContext->assertOriginIsNotPublic();
		// checks configuration
		if(!isset($this->element)) throw new DataFlowServiceException('element has not been set', DataFlowServiceException::CONFIGURATION_ERROR);
		if(!isset($this->fieldName)) throw new DataFlowServiceException('fieldName of type Files has not been set', DataFlowServiceException::CONFIGURATION_ERROR);
		$field = $this->element->getFieldList()->getField($this->fieldName);
		if(!($field->getDataType() instanceof Files)) throw new DataFlowServiceException("field '".$this->fieldName."' should be of type Files", DataFlowServiceException::CONFIGURATION_ERROR);
		if(isset($this->fileSubfields) && !is_array($this->fileSubfields)) throw new DataFlowServiceException("fileSubfields should be an array containing some of the keys name,type,size,date,username,user", DataFlowServiceException::CONFIGURATION_ERROR);		

		// creates file name based on provided Element subfields
		if(!isset($this->fileSubfields)) $this->fileSubfields=array();
		$this->fileName = $this->fileSubfields['name'];
		if(empty($this->fileName)) $this->fileName = $this->element->getFieldValue($this->fieldName,'name');
		if(is_null($this->fileName)) $this->fileName='';		
		$type = $this->fileSubfields['type'];
		if(empty($type)) $type = $this->element->getFieldValue($this->fieldName,'type');
		if(is_null($type)) $type='';		
		$this->fileName = $this->createFileName($dataFlowContext->getPrincipal(), $this->fieldName, $this->fileName, $type);
		
		// opens file in write mode
		$this->fileFullPath = dirname($_SERVER["SCRIPT_FILENAME"])."/".$this->getTemporaryUploadedFilePath().$this->fileName;
		$mode = 'w'.($this->textMode ? 't' : 'b');
		$fHandle = @fopen($this->fileFullPath, $mode);
		if($fHandle === false) throw new DataFlowServiceException("could not open the temp file '$this->fileName' in '$mode' mode.", DataFlowServiceException::UNEXPECTED_ERROR);
		$this->fileHandle = $fHandle;	
	}
	public function processDataChunk($data, $dataFlowContext) {
		$chunkSize = strlen($data);
		$written = @fwrite($this->fileHandle, $data);
		//$this->debugLogger()->write("wrote $written bytes");
		if($written === false) throw new DataFlowServiceException("could not write chunk of data in open temp file '$this->fileName'", DataFlowServiceException::UNEXPECTED_ERROR);
		while($written < $chunkSize) {
			$w = @fwrite($this->fileHandle, substring($data, $written));
			if($w === false) throw new DataFlowServiceException("could not write chunk of data in open temp file '$this->fileName'", DataFlowServiceException::UNEXPECTED_ERROR);
			$written += $w;
			//$this->debugLogger()->write("wrote $w bytes");
		}	
	}
	public function endOfStream($dataFlowContext) {
		// closes the file
		@fclose($this->fileHandle);
		// updates file subfields
		$this->updateFileSubfields($dataFlowContext);
		// persists element into db if specified
		if($this->persistElement) ServiceProvider::getWigiiBPL()->elementPersistFileField($dataFlowContext->getPrincipal(), $this, wigiiBPLParam(
			'element', $this->element,
			'fieldName', $this->fieldName
		));
	}
	
	// single data event handling
	
	public function processWholeData($data, $dataFlowContext) {
		$this->startOfStream($dataFlowContext);
		$this->processDataChunk($data, $dataFlowContext);
		$this->endOfStream($dataFlowContext);
	}
	
	// implementation
	
	/**
	 * Creates the file name which will be stored into wigii
	 * (a copy of the algorithm found into method FormExecutor->manageUploadedFileForm)
	 * @param String fieldName the name of the field where the report will be stored
	 * @param String fileName the logical file name as set by the user 
	 * @param String type is the file extension with the dot
	 * (optional, if not set, then filename has no extension. It can be used to generate temp folder names)
	 */
	protected function createFileName($principal, $fieldName, $fileName, $type='') {
		if($type != '' && strpos($type, '.') !== 0) $type = '.'.$type;
	
		$returnValue = $principal->getWigiiNamespace()->getWigiiNamespaceName().
		"_".udate('Uu'). /* for files generated in batch mode, needs milliseconds precision */
		ipToStr($_SERVER["REMOTE_ADDR"]).
		$principal->getUsername().
		$fieldName.
		substr($fileName, 0, 5).
		$type;
	
		$returnValue = preg_replace('/[^a-zA-Z0-9\.\-\_]/',"",$returnValue);
		return $returnValue;
	}
	
	/**
	 * Updates the Element File subfields according to generated File meta-information and provided subfields.
	 * @param DataFlowContext $dataFlowContext current data flow context
	 */
	protected function updateFileSubfields($dataFlowContext) {		
		//path
		$this->element->setFieldValue($this->fileName, $this->fieldName, 'path');
		//name
		$val = $this->fileSubfields['name'];
		if($val) $this->element->setFieldValue($val, $this->fieldName, 'name');
		//size
		$val = $this->fileSubfields['size'];
		if(!$val) $val = @filesize($this->fileFullPath);					
		if($val) $this->element->setFieldValue($val, $this->fieldName, 'size');
		//type
		$val = $this->fileSubfields['type'];
		if(!$val && $this->fileSubfields['mime']) $val = mime2ext($this->fileSubfields['mime']);
		if($val) $this->element->setFieldValue($val, $this->fieldName, 'type');		
		//mime
		$val = $this->fileSubfields['mime'];
		if(!$val && $this->fileSubfields['type']) $val = typeMime($this->fileSubfields['type']);
		if($val) $this->element->setFieldValue($val, $this->fieldName, 'mime');
		//date
		$val = $this->fileSubfields['date'];
		if(!$val) $val=date("Y-m-d H:i:s");
		if($val) $this->element->setFieldValue($val, $this->fieldName, 'date');
		//user
		$val = $this->fileSubfields['user'];
		if(!$val) $val=$dataFlowContext->getPrincipal()->getRealUserId();
		if($val) $this->element->setFieldValue($val, $this->fieldName, 'user');
		//username
		$val = $this->fileSubfields['username'];
		if(!$val) $val=$dataFlowContext->getPrincipal()->getRealUsername();
		if($val) $this->element->setFieldValue($val, $this->fieldName, 'username');
		//version
		/* doesn't touch version for generated files */
	}
}