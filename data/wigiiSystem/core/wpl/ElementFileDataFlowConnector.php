<?php
/**
 *  This file is part of Wigii.
 *
 *  Wigii is free software: you can redistribute it and\/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *  
 *  Wigii is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  
 *  You should have received a copy of the GNU General Public License
 *  along with Wigii.  If not, see <http:\//www.gnu.org/licenses/>.
 *  
 *  @copyright  Copyright (c) 2012 Wigii 		 http://code.google.com/p/wigii/    http://www.wigii.ch
 *  @license    http://www.gnu.org/licenses/     GNU General Public License
 */

/**
 * A connector which dumps the content of an Element field of type Files into a data flow.
 * The content is pushed chunk by chunk. Default chunk size is 512ko.
 * Created by CWE on 29 janvier 2016
 */
class ElementFileDataFlowConnector implements InputDataFlow
{
	private $_debugLogger;
	private $lockedForUse = true;	
	
	// Object lifecycle
	
	public function reset() {
		$this->freeMemory();				
		$this->lockedForUse = true;
		$this->chunkSize=512*1024;				
	}	
	public function freeMemory() {
		if($this->contentIsStream && isset($this->content)) {
			@fclose($this->content);			
		}
		unset($this->content);
		unset($this->contentIsStream);
		$this->lockedForUse = false;	
	}
		
	public function isLockedForUse() {
		return $this->lockedForUse;
	}
	
	/**
	 * @param Element $element element from which to dump the file content
	 * @param String|FieldSelector $fieldName the name of the Field of type Files from which to dump the content
	 * @param int $chunkSize optional chunk size
	 */
	public static function createInstance($element,$fieldName,$chunkSize=null) {
		$returnValue = new self();
		$returnValue->reset();
		$returnValue->setElement($element);
		$returnValue->setFieldName($fieldName);
		if(isset($chunkSize)) $returnValue->setChunkSize($chunkSize);
		return $returnValue;
	}
	
	// Dependency injection
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("ElementFileDataFlowConnector");
		}
		return $this->_debugLogger;
	}		
	
	// Configuration
	
	private $chunkSize;
	/**
	 * Sets the read chunk size. 
	 * If 0, then no chunk size, reads whole file in one shot.
	 */
	public function setChunkSize($chunkSize) {
		if($chunkSize<=0) $this->chunkSize = 0;
		else $this->chunkSize=$chunkSize;		
	}
	
	private $element;
	/**
	 * Sets the Element from which to dump the content of a File
	 * @param Element $element
	 */
	public function setElement($element) {
		$this->element = $element;
	}	
	
	private $fieldName;
	/**
	 * Sets the Field of type Files for which to dump the content
	 * @param String|FieldSelector $fieldName
	 */
	public function setFieldName($fieldName) {
		if($fieldName instanceof FieldSelector) $this->fieldName = $fieldName->getFieldName();
		else $this->fieldName = $fieldName;
	}
	
	// InputDataFlow implementation
	
	public function startStream() {
		// checks configuration
		if(!isset($this->element)) throw new DataFlowServiceException('element has not been set', DataFlowServiceException::CONFIGURATION_ERROR);
		if(!isset($this->fieldName)) throw new DataFlowServiceException('fieldName of type Files has not been set', DataFlowServiceException::CONFIGURATION_ERROR);
		$field = $this->element->getFieldList()->getField($this->fieldName);
		if(!($field->getDataType() instanceof Files)) throw new DataFlowServiceException("field '".$this->fieldName."' should be of type Files", DataFlowServiceException::CONFIGURATION_ERROR);
		$fieldXml = $field->getXml();
		
		// checks for internal data
		if ($fieldXml["htmlArea"] == "1") {
			$content = $this->element->getFieldValue($this->fieldName, "textContent");
		} else {
			$content = $this->element->getFieldValue($this->fieldName, "content");
		}
		// if no internal content then checks for file on disk
		if(empty($content)) {
			$fileName = $this->element->getFieldValue($this->fieldName, "path");
			if(empty($fileName)) throw new DataFlowServiceException("path is not set for field '".$this->fieldName."' of type Files");			
			// first checks in TEMPORARYUPLOADEDFILE_path
			$filePath = TEMPORARYUPLOADEDFILE_path.$fileName;
			if(file_exists($filePath)) {
				$this->content = fopen($filePath,'r');
				if($this->content===false) {
					unset($this->content);
					$this->contentIsStream=false;
				}
				else $this->contentIsStream=true;
			}
			// else checks in FILES_PATH
			else {
				$filePath = FILES_PATH.$fileName;
				if(file_exists($filePath)) {
					$this->content = fopen($filePath,'r');
					if($this->content===false) {
						unset($this->content);
						$this->contentIsStream=false;
					}
					else $this->contentIsStream=true;
				}
				else $this->contentIsStream=false;
			}
		}
		// else splits content into internal buffer 
		else {
			if($this->chunkSize>0) {
				$this->content = str_split($content, $this->chunkSize);
				$this->content = array_reverse($this->content); // reverses the order to take advantage of the pop function
			}
			else $this->content = array($content);
			$this->contentIsStream=false;
		}
	}
	
	public function hasData() {
		// case stream : checks availability of next chunk of data
		if($this->contentIsStream) {
			return !feof($this->content);
		}
		// case chunked array : checks that array is not empty
		else {
			return !empty($this->content);
		}
	}
	
	public function readDataChunk() {			
		// case stream : reads next chunk of data
		if($this->contentIsStream) {
			if($this->chunkSize>0) $returnValue = fread($this->content, $this->chunkSize);
			else $returnValue = stream_get_contents($this->content);
			if($returnValue===false)  throw new DataFlowServiceException('no data to read', DataFlowServiceException::INVALID_STATE);
			return $returnValue;
		}
		// case chunked array : returns next available chunk
		else {
			if(empty($this->content)) throw new DataFlowServiceException('no data to read', DataFlowServiceException::INVALID_STATE);
			return array_pop($this->content); // uses pop because array has been reversed to increase perf.
		}		
	}
	
	public function endStream() {
		if($this->contentIsStream && isset($this->content)) {
			@fclose($this->content);
			unset($this->content);
		}
	}

	// Implementation
	
	/**
	 * @var resource|array a pointer to the content of the file. Or an open stream, or an array of chunks. 
	 */
	private $content;
	/**
	 * @var boolean if true, then content is a pointer to an open stream, else it is an array of chunks.
	 */
	private $contentIsStream;
}