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
 * A zip file writer data flow activity
 * This data flow activity processes a list of file names (with complete path on disk to file location),
 * and stores each file into a zip. At the end, it writes one single output which is the complete name
 * of the zip file on disk.
 * A root folder path can be defined that will be used to mask the file paths.
 * If an intersection is found, then the file added to zip will be added into a subfolder 
 * which path corresponds to the unmasked subpath, else the file will be added at the root of the zip (no subfolder)
 * Optionally deletes the source file and empty relative subfolders
 * 
 * Created by CWE on 27 juin 2013
 */
class ZipFileWriterDFA implements DataFlowActivity
{		
	private $wigiiGraphService;
	private $deleteInputFiles;
	private $deleteEmptyFolders;
	private $deleteRootFolder;
	private $rootFolder; // with ending slash
	private $fileDir; // with ending slash
	private $fileName;
	private $zipArchive;
	private $fileFullPath;
	private $inputFiles;
	private $inputFolderPath;	
	
	// Object lifecycle
		
	public function reset() {		
		$this->freeMemory();
		$this->deleteInputFiles = false;
		$this->deleteEmptyFolders = true;
		$this->deleteRootFolder = false;		
	}	
	public function freeMemory() {
		unset($this->rootFolder);
		unset($this->fileDir);
		unset($this->fileName);
		unset($this->zipArchive);
		unset($this->fileFullPath);
		unset($this->inputFiles);
		unset($this->inputFolderPath);		
	}
	
	// Dependency injection

	private $_debugLogger;	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("ZipFileWriterDFA");
		}
		return $this->_debugLogger;
	}
	
	public function setWigiiGraphService($wigiiGraphService)
	{
		$this->wigiiGraphService = $wigiiGraphService;
	}
	protected function getWigiiGraphService()
	{
		// autowired
		if(!isset($this->wigiiGraphService))
		{
			$this->wigiiGraphService = TechnicalServiceProvider::getWigiiGraphService();
		}
		return $this->wigiiGraphService;
	}
	
	
	// configuration
	
	/**
	 * Sets the name of the zip file to be written.
	 * The file name should be of the form [directoryPath/]fileName.zip where
 	 * directoryPath is an optional folder path, absolute or relative
 	 * The directoryPath if defined, should exist on disk with write permissions.
 	 * The zip is opened in w mode (no append, rewrite, created if doesn't exist).
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
			if(!is_dir($this->fileDir)) throw new DataFlowServiceException("path '$this->fileDir' is not a valid folder on disk", DataFlowServiceException::INVALID_ARGUMENT);
			if(!is_writable($this->fileDir)) throw new DataFlowServiceException("path '$this->fileDir' is not accessible for writing", DataFlowServiceException::INVALID_ARGUMENT);
		}
		$this->fileFullPath = (isset($this->fileDir) ? $this->fileDir : '').$this->fileName;
	}
	
	/**
	 * Specifies a root folder path that will be used to mask the file paths.
 	 * If an intersection is found, then the file added to zip will be added into a subfolder 
 	 * which path corresponds to the unmasked subpath, else the file will be added at the root of the zip (no subfolder)
 	 * The root folder path is created if it does not exist on disk (with write permissions).
	 */
	public function setRootFolder($path) {
		if(is_null($path) || $path == '') throw new DataFlowServiceException("root folder path cannot be null", DataFlowServiceException::INVALID_ARGUMENT);
		$lastChar = $path[strlen($path)-1];
		if($lastChar != '/' && $lastChar != '\\') $path = $path.'/';
		$this->rootFolder = $path;
	}
	
	/**
	 * If true, then the source file will be deleted once added to the zip.
	 * False by default.
	 */
	public function setDeleteInputFiles($bool) {
		$this->deleteInputFiles = $bool;
	}
	
	/**
	 * If true, then all subfolders, from the root folder 
	 * up to the folder including the source file, will be deleted if empty.
	 * True by default (works only if delete input files is true).
	 */
	public function setDeleteEmptyFolders($bool) {
		$this->deleteEmptyFolders = $bool;
	}
	
	/**
	 * If true then the root folder is deleted if empty.
	 * False by default (works only if delete input files is true).
	 */
	public function setDeleteRootFolder($bool) {
		$this->deleteRootFolder= $bool;
	}

		
	// stream data event handling
	
	public function startOfStream($dataFlowContext) {
		$this->zipArchive = $this->createZipArchiveInstance();
		// creates zip on file system in write mode
		if($this->zipArchive->open($this->fileFullPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) throw new DataFlowServiceException("could not create zip file '$this->fileFullPath'", DataFlowServiceException::UNEXPECTED_ERROR);
		$this->debugLogger()->write("creates zip archive as ".$this->fileFullPath);
		// creates root folder path if does not exist
		if(isset($this->rootFolder) && !is_dir($this->rootFolder)) {
			if(!mkdir($this->rootFolder, 0777, true)) throw new DataFlowServiceException("could not create folder '$this->rootFolder'", DataFlowServiceException::UNEXPECTED_ERROR);
		}
		// creates tree representation of input files
		if($this->deleteInputFiles) {
			$this->inputFiles = array();			
		}		
	}
	public function processDataChunk($data, $dataFlowContext) {
		// separates directory from file
		$fileStart = strrpos($data, '/');
		// if not found -> no directory, only file name		
		if($fileStart === false) {
			$fileName = $data;
			$fileDir = '';
		}
		// else file directory exist
		else {
			$fileName = substr($data, $fileStart+1);
			$fileDir = substr($data, 0, $fileStart+1);
		}
		
		// reduces the directory to only subfolders compared to root folder
		if(isset($this->rootFolder) && (strpos($fileDir, $this->rootFolder) === 0)) {
			$l = strlen($this->rootFolder);
			if(strlen($fileDir) > $l) $fileDir = substr($fileDir, $l);
			else $fileDir = '';
		}
		else $fileDir = '';
		
		// adds file to zip
		$fileInZipPath = $fileDir.$fileName;
		if(!$this->zipArchive->addFile($data, $fileInZipPath)) throw new DataFlowServiceException("could not add file '$data' to zip", DataFlowServiceException::UNEXPECTED_ERROR);
		else $this->debugLogger()->write('added file '.$data.' to zip as local file '.$fileInZipPath);
		// adds file path to input files tree 
		if($this->deleteInputFiles) {
			$inputFilePath = explode('/', $fileInZipPath);
			$inputFilePath[] = $data; 
			$this->getWigiiGraphService()->storeNodeValueInArrayTree($this->inputFiles, $inputFilePath);			
		}
	}
	public function endOfStream($dataFlowContext) {
		// closes the zip
		$this->zipArchive->close();		
		// cleans up input files tree
		if($this->deleteInputFiles) {
			$this->inputFolderPath = array();
			$this->getWigiiGraphService()->walkThroughArrayTree($this->inputFiles, 
			'visitInputFolderForCleanup', 'actOnInputFolderForCleanup', 'actOnInputFileForCleanup', $this);
			
			// deletes root folder
			if($this->deleteRootFolder && isset($this->rootFolder)) {
				$l = strlen($this->rootFolder)-1;
				if($l > 0) {
					$path = substr($this->rootFolder, 0, $l);
					if(!@rmdir($path)) $this->debugLogger()->write("could not delete folder '$path'");
				}				
			}
		}			
		// writes the full path as a the DataFlowActivity output for next stage in pipe
		$dataFlowContext->writeResultToOutput($this->fileFullPath, $this);		
	}
	// input files cleanup callbacks
	public function visitInputFolderForCleanup($key, $depth, $numberOfChildren) {
		// push folder on stack
		$this->inputFolderPath[] = $key;
		return true;
	}
	public function actOnInputFolderForCleanup($key, $depth, $visitedAllChildren) {
		if($this->deleteEmptyFolders) $path = implode('/', $this->inputFolderPath);
		// pops folder from stack
		if(array_pop($this->inputFolderPath) != $key) throw new DataFlowServiceException("folder walker does not match internal stack", DataFlowServiceException::INVALID_STATE);
		// deletes folder
		if($this->deleteEmptyFolders && isset($this->rootFolder)) {
			$path = $this->rootFolder.$path;
			if(!@rmdir($path)) $this->debugLogger()->write("could not delete folder '$path'");
		}
		return true;
	}
	public function actOnInputFileForCleanup($key, $value, $depth) {
		// deletes input file
		if(is_dir($value)) {
			if(!@rmdir($value)) $this->debugLogger()->write("could not delete folder '$value'");
		}
		else {
			if(!@unlink($value)) $this->debugLogger()->write("could not delete file '$value'");
		}
		return true;
	}
	
	// single data event handling
	
	public function processWholeData($data, $dataFlowContext) {
		$this->startOfStream($dataFlowContext);
		$this->processDataChunk($data, $dataFlowContext);
		$this->endOfStream($dataFlowContext);
	}	

	
	// implementation
	
	protected function createZipArchiveInstance() {
		return new ZipArchive();
	}		
}