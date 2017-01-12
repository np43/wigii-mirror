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
 * A file output stream data flow activity
 * This data flow activity writes incoming data into a file
 * The physical file on disk is located using two different information :
 * - an optional root directory path with gives a start from which the file relative path should be searched
 *   If this root directory is specified, then it should already exist on disk, with write permissions.
 * - a file name of the form [directoryPath/]fileName.ext where
 *   directoryPath is a folder path, absolute or relative
 *   ext is a classical file extension
 *   If the file or the directoryPath do not exist (or partially exist) then they are created
 * The supported opening modes are w, a, x and c (see php fopen function)
 * An optional setter to force text mode (t option in php fopen function), default is always binary.
 * This DataFlowActivity generates one single output which is the complete file path to the written file
 * This DataFlowActivity cannot be called from public space (i.e. caller is located outside of the Wigii instance)
 * 
 * Created by CWE on 27 juin 2013
 * Modified by Medair (CWE) on 15.12.2016 to protect against Cross Site Scripting
 */
class FileOutputStreamDFA implements DataFlowActivity
{		
	private $_debugLogger;
	private $textMode;
	private $rootFolder; // with ending slash
	private $fileDir; // with ending slash
	private $fileName;
	private $fileOpeningMode;
	private $fileHandle;	
	private $fileFullPath;
	
	// Object lifecycle
		
	public function reset() {
		$this->freeMemory();
		$this->textMode = false;
		$this->fileOpeningMode = 'w';		
	}	
	public function freeMemory() {
		unset($this->rootFolder);
		unset($this->fileDir);
		unset($this->fileName);
		unset($this->fileHandle);	
		unset($this->fileFullPath);	
	}
	
	// dependency injection
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("FileOutputStreamDFA");
		}
		return $this->_debugLogger;
	}
	
	// configuration
	
	/**
	 * Specifies a root directory path which gives a start 
	 * from which the file relative path should be searched
	 * The folder should exist with write permissions
	 */
	public function setRootFolder($path) {
		if(is_null($path) || $path == '') $this->rootFolder = null;
		else {
			if($path[strlen($path)-1] == '/') {
				$dir = $path; $addSlash = false;
			}
			else {
				$dir = $path; $addSlash = true;
			}
			if(!is_dir($dir)) throw new DataFlowServiceException("path '$dir' is not a valid folder on disk", DataFlowServiceException::INVALID_ARGUMENT);
			if(!is_writable($dir)) throw new DataFlowServiceException("path '$dir' is not accessible for writing", DataFlowServiceException::INVALID_ARGUMENT);
			
			$this->rootFolder = $dir;
			if($addSlash) $this->rootFolder .= '/';						
		}
	}
	
	/**
	 * Sets the name of the file to be written.
	 * The file name should be of the form [directoryPath/]fileName.ext where
 	 * - directoryPath is an optional folder path, absolute or relative
	 * - ext is a classical file extension
 	 * If the file or the directoryPath do not exist (or partially exist) then they are created
	 */
	public function setFilename($filePath) {
		if(is_null($filePath) || $filePath == '') throw new DataFlowServiceException("filePath cannot be null", DataFlowServiceException::INVALID_ARGUMENT);
		$fileStart = strrpos($filePath, '/');
		// if not found -> no directory, only file name		
		if($fileStart === false) {
			$this->fileName = $filePath;
			$this->fileDir = null;
		}
		// else file directory exist
		else {
			$this->fileName = substr($filePath, $fileStart+1);
			$this->fileDir = substr($filePath, 0, $fileStart+1);
		}
	}
	
	/**
	 * Sets the file opening mode (see php function fopen)
	 * Supported modes are w, a, x and c
	 */
	public function setOpeningMode($mode) {
		if(is_null($mode) || $mode == '') throw new DataFlowServiceException("file opening mode cannot be null. Supported modes are w, a, x, c", DataFlowServiceException::INVALID_ARGUMENT);
		$mode = strtolower($mode);
		switch($mode)
		{
			case 'w':
			case 'a':
			case 'x':
			case 'c':
				$this->fileOpeningMode = $mode;
				break;
			default: throw new DataFlowServiceException("Invalid file opening mode. Supported modes are w, a, x, c", DataFlowServiceException::INVALID_ARGUMENT);
		}
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
		if(is_null($this->fileName) || $this->fileName == '') throw new DataFlowServiceException("file name has not been initialized", DataFlowServiceException::CONFIGURATION_ERROR);
		
		// creates files directory if does not exist
		$dir = (isset($this->rootFolder) ? $this->rootFolder : '').(isset($this->fileDir) ? $this->fileDir : '');		
		if($dir != '' && !is_dir($dir)) {
			if(!mkdir($dir, 0777, true)) throw new DataFlowServiceException("could not create folder '$dir'", DataFlowServiceException::UNEXPECTED_ERROR);
		}
		// opens file in write mode
		$this->fileFullPath = $dir.$this->fileName;
		$mode = $this->fileOpeningMode.($this->textMode ? 't' : 'b');
		$fHandle = fopen($this->fileFullPath, $mode);
		if($fHandle === false) throw new DataFlowServiceException("could not open the file '$this->fileFullPath' in '$mode' mode.", DataFlowServiceException::UNEXPECTED_ERROR);
		$this->fileHandle = $fHandle;			
	}
	public function processDataChunk($data, $dataFlowContext) {
		$chunkSize = strlen($data);
		$written = fwrite($this->fileHandle, $data);
		//$this->debugLogger()->write("wrote $written bytes");
		if($written === false) throw new DataFlowServiceException("could not write chunk of data in open file '$this->fileFullPath'", DataFlowServiceException::UNEXPECTED_ERROR);
		while($written < $chunkSize) {
			$w = fwrite($this->fileHandle, substring($data, $written));
			if($w === false) throw new DataFlowServiceException("could not write chunk of data in open file '$this->fileFullPath'", DataFlowServiceException::UNEXPECTED_ERROR);
			$written += $w;
			//$this->debugLogger()->write("wrote $w bytes");
		}		 
	}
	public function endOfStream($dataFlowContext) {
		// closes the file
		fclose($this->fileHandle);
		// writes the full path as a the DataFlowActivity output for next stage in pipe
		$dataFlowContext->writeResultToOutput($this->fileFullPath, $this);
	}
	
	
	// single data event handling
	
	public function processWholeData($data, $dataFlowContext) {
		$this->startOfStream($dataFlowContext);
		$this->processDataChunk($data, $dataFlowContext);
		$this->endOfStream($dataFlowContext);
	}	
}