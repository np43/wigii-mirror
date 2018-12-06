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
 * ElementEvaluator to generate reports
 * Created by CWE 19.2.2013
 */
class ReportingElementEvaluator extends ElementEvaluator implements ReportingOutputHelper
{	
	private $_debugLogger;
	private $_executionSink;
	private $reportParamsFsl;
	private $reportDFASL;	
	
	// object lifecycle

	public static function createInstance()
	{
		$returnValue = new self();
		$returnValue->reset();
		return $returnValue;
	}
	public function freeMemory($keepContext=false)
	{		
		parent::freeMemory($keepContext);
		unset($this->reportParamsFsl);
		unset($this->reportDFASL);		
	}	
	
	// dependency injection
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("ReportingElementEvaluator");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("ReportingElementEvaluator");
		}
		return $this->_executionSink;
	}
	
	// Configuration
	
	private $defaultReportingFacadeClassName;
	/**
	 * Sets default ReportingFacade classname
	 * @param $onlyIfUnset optional boolean, if true, then sets only if not set yet, else always sets the value. Defaults to false.
	 */
	public function setDefaultReportingFacadeClassName($className, $onlyIfUnset=false) {
		$this->defaultReportingFacadeClassName = $className;	
	}
	/**
	 * Defaults to JasperReportsFacade	 
	 */
	protected function getDefaultReportingFacadeClassName() {
		if(!isset($this->defaultReportingFacadeClassName) || $this->defaultReportingFacadeClassName == '') {
			$this->defaultReportingFacadeClassName = "JasperReportsFacade";
		}
		return $this->defaultReportingFacadeClassName;
	}
	
	private $temporaryUploadedFilePath;
	/**
	 * Sets the path to temporary folder where uploaded files are stored
	 * (relative to www folder)
	 */
	public function setTemporaryUploadedFilePath($path) 
	{ 
		$this->temporaryUploadedFilePath = $path; 
	}
	/**
	 * Defaults to TEMPORARYUPLOADEDFILE_path definition
	 */
	protected function getTemporaryUploadedFilePath() {
		if(!isset($this->temporaryUploadedFilePath)){
			$this->temporaryUploadedFilePath = TEMPORARYUPLOADEDFILE_path;
		}
		return $this->temporaryUploadedFilePath;
	}
	
	
	// Reporting FunExp API
	
	/**
	 * Executes a report and fills the current field subfields. 
	 * Precondition: the current Field datatype should be Files
	 * funcExp signature: executeReport(reportName, format, optional reportClass)
	 * where arguments are:
	 * - Arg(0) reportName: evaluates to a String identifying the report to be executed by the report engine
	 * - Arg(1) format: evaluates to a String representing the output format (in upper case letters).
	 * Exact supported values depends on the underlying reporting engine, but PDF, HTML, RTF, XLS are standard
	 * - Arg(2) reportClass: optional argument. If set, evaluates to a String representing the class name of 
	 * the report engine client which will execute the report. If not set, then uses the default reporting engine.
	 * 
	 * Any field having xml attribute reportParam="1" will be considered as a report parameter and will be passed
	 * to the underlying reporting engine in the report parameters field selector list.	 
	 */
	public function executeReport($args) {
		$principal = $this->getPrincipal();
		$this->executionSink()->publishStartOperation("executeReport", $principal);
		try
		{
			$field = $this->getCurrentField();		
			$dt = $field->getDataType();
			if(is_null($dt) || $dt->getDataTypeName() != 'Files') throw new RecordException("Field '".$field->getFieldName()."' should be of datatype Files", RecordException::INVALID_ARGUMENT);
			$nArgs = $this->getNumberOfArgs($args);
			if($nArgs < 2) throw new ReportingException("executeReport funcExp should have at least two arguments: the reportName and the format", ReportingException::INVALID_ARGUMENT);
			// gets report name
			$reportName = $this->evaluateArg($args[0]);
			if(is_null($reportName) || $reportName == '') throw new ReportingException("reportName canno be null", ReportingException::INVALID_ARGUMENT);
			// gets format
			$format = $this->evaluateArg($args[1]);
			if(is_null($format) || $format == '') throw new ReportingException("format should be a non null string", ReportingException::INVALID_ARGUMENT);
			$format = strtoupper((string)$format);
			// gets optional report class		
			if($nArgs > 2) {
				$reportClass = $this->evaluateArg($args[2]);
				if(is_null($reportClass) || $reportClass == '') throw new ReportingException("reportClass should be a non null string", ReportingException::INVALID_ARGUMENT);
			}
			else $reportClass = $this->getDefaultReportingFacadeClassName();
					
			// gets the reporting facade
			$reportingFacade = $this->getReportingFacade($reportClass);
			
			// executes the report
			try {
				$reportingFacade->executeReport($principal, 
					$reportName, $this->getElement(), $field, $format, 
					$this->getReportParametersFieldSelectorList());
				$reportingFacade->freeMemory();
			}
			catch(Exception $e1) {
				$reportingFacade->freeMemory();
				throw $e1;
			}		
			$this->setIgnoreReturnValue(true);
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("executeReport", $e, $principal);
			throw $e;
		}
		$this->executionSink()->publishEndOperation("executeReport", $principal);
	}
	
	/**
	 * Returns an instance of the ReportingFacade given its class name
	 */
	protected function getReportingFacade($className) {
		$returnValue = ServiceProvider::getExclusiveAccessObject($className);
		if(!($returnValue instanceof ReportingFacade)) {
			try {if(isset($returnValue)) $returnValue->freeMemory();}catch(Exception $e) {/*nothing to do*/}
			throw new ServiceProviderException("reporting facade instance is not an instance of ReportingFacade, but of ".get_class($returnValue), ServiceProviderException::INVALID_ARGUMENT);
		}
		// sets reporting output helper
		$returnValue->setReportingOutputHelper($this);
		return $returnValue;
	}
	
	/**
	 * Returns a FieldSelectorList containing the report parameters
	 * or null if no parameters.
	 */
	protected function getReportParametersFieldSelectorList() {
		if(!isset($this->reportParamsFsl)) {
			$this->reportParamsFsl = FieldSelectorListArrayImpl::createInstance(true, false);
			if($this->extractReportParameters($this->reportParamsFsl) > 0) return $this->reportParamsFsl;
			else return null;
		}
		elseif($this->reportParamsFsl->isEmpty()) return null;
		else return $this->reportParamsFsl;
	}
	
	/**
	 * Fills a FieldSelectorList with all report parameters
	 * Any field in the element having an xml attribute reportParam="1" is considered as a report parameter
	 * @return returns the number of parameters
	 */
	protected function extractReportParameters($fieldSelectorList) {
		if(is_null($fieldSelectorList)) throw new ReportingException("fieldSelectorList cannot be null", ReportingException::INVALID_ARGUMENT);
		$returnValue = 0;
		foreach($this->getElement()->getFieldList()->getListIterator() as $field) {
			$fxml = $field->getXml();
			//$this->debugLogger()->write("field xml: ".$fxml->asXml());
			if($fxml["reportParam"] == "1") {
				$fieldSelectorList->addFieldSelector($field->getFieldName());
				$this->debugLogger()->write("adds report parameter '".$field->getFieldName()."'");
				$returnValue++;
			} 
		}		
		return $returnValue;
	}
	
	// ReportingOutputHelper implementation
		
	public function getDFASLForSingleReport($principal, $reportName, $mime, $type, $configurator=null) {
		if(is_null($mime) && is_null($type)) throw new DataFlowServiceException("mime and type cannot be both null", DataFlowServiceException::INVALID_ARGUMENT);
		if(is_null($mime)) $mime = typeMime($type);
		elseif(is_null($type)) $type = mime2ext($mime);
		elseif(strtolower($mime) != typeMime($type)) throw new DataFlowServiceException("mime '$mime' does not match file extension '$type'", DataFlowServiceException::INVALID_ARGUMENT);
		
		// gets DFASL for field
		if(!isset($this->reportDFASL)) $this->reportDFASL = array();
		$fName = $this->getCurrentField()->getFieldName();
		$key = $this->getDFASLKey($fName);
		$dfasl = $this->reportDFASL[$key];
		// creates DFASL if not set
		if(!isset($dfasl)) {
			$dfasl = $this->createDFASLForSingleReport();
			$this->reportDFASL[$key] = $dfasl;		
		}
		// destination folder
		$destFolder = $this->createRootFolderPathStringForReport();

		// configures FileOutputStream
		$dfas = $dfasl->getDataFlowActivitySelector(0);		
		$dfas->setDataFlowActivityParameter('setRootFolder', $destFolder);
		$dfas->setDataFlowActivityParameter('setFilename', $this->createReportFileName($principal, $fName, $reportName, $type));
		$dfas->setDataFlowActivityParameter('setOpeningMode', 'w');
		
		// configures File Field Filler
		$dfas = $dfasl->getDataFlowActivitySelector(1);
		$dfas->setDataFlowActivityParameter('setElement', $this->getElement());
		$dfas->setDataFlowActivityParameter('setFieldName', $fName);
		$dfas->setDataFlowActivityParameter('setPathMask', $destFolder);
		
		// sets Configurator
		if(isset($configurator)) {
			if(is_null($configurator->getConfigValue('name'))) $configurator->setConfigValue('name', $reportName);
			if(is_null($configurator->getConfigValue('type'))) $configurator->setConfigValue('type', $type);
			if(is_null($configurator->getConfigValue('mime'))) $configurator->setConfigValue('mime', $mime);
		}
		// gets Configurator for Report
		else {
			$key = $this->getDFASLKey($fName, "reportConfigurator");
			$configurator = $this->reportDFASL[$key];
			// creates Configurator if not set
			if(!isset($configurator)) {
				$configurator = ObjectConfigurator::createInstance();
				$this->reportDFASL[$key] = $configurator;		
			}
			else $configurator->reset();
			// initializes configurator
			$configurator->setConfigValue('name', $reportName);
			$configurator->setConfigValue('type', $type);
			$configurator->setConfigValue('mime', $mime);
		}
		$dfas->setDataFlowActivityParameter('setObjectConfigurator', $configurator);
		
		return $dfasl;		
	}
	protected function createDFASLForSingleReport() {
		$returnValue = DataFlowActivitySelectorListArrayImpl::createInstance();
		$returnValue->addDataFlowActivitySelector("FileOutputStreamDFA");
		$returnValue->addDataFlowActivitySelector("ReportingEltEvalFileFieldFillerDFA");
		return $returnValue;
	}		
	public function getDFASLForMultipartReport($principal, $reportName, $reportFacade) {		
		// gets DFASL for field
		if(!isset($this->reportDFASL)) $this->reportDFASL = array();
		$fName = $this->getCurrentField()->getFieldName();
		$key = $this->getDFASLKey($fieldName);
		$dfasl = $this->reportDFASL[$key];
		// creates DFASL if not set
		if(!isset($dfasl)) {
			$dfasl = $this->createDFASLForMultipartReport();
			$this->reportDFASL[$key] = $dfasl;		
		}
		// destination folder
		$destFolder = $this->createRootFolderPathStringForReport();
		
		// report part temp folder
		$reportPartFolder = $this->createReportFileName($principal, $fName, $reportName);		
		// zip name
		$zipName = $reportPartFolder.".zip";
		
		// creates report part temp folder for zip building
		$reportPartFolder = $destFolder.$reportPartFolder;				
		if(!mkdir($reportPartFolder)) throw new DataFlowServiceException("Could not create temporary folder '$reportPartFolder'", DataFlowServiceException::UNEXPECTED_ERROR);
		
		// gets DFASL for report part
		$key = $this->getDFASLKey($fName, "reportPart");
		$dfaslReportPart = $this->reportDFASL[$key];
		// creates DFASL if not set
		if(!isset($dfaslReportPart)) {
			$dfaslReportPart = $this->createDFASLForReportPart();
			$this->reportDFASL[$key] = $dfaslReportPart;		
		}
		// configures Report part File Output Stream
		$dfas = $dfaslReportPart->getDataFlowActivitySelector(0);
		$dfas->setDataFlowActivityParameter('setRootFolder', $reportPartFolder);
		$dfas->setDataFlowActivityParameter('setOpeningMode', 'w');
		
		// configures Substream Tap
		$dfas = $dfasl->getDataFlowActivitySelector(0);
		$dfas->setDataFlowActivityParameter('setSubstreamDescriptor', $dfaslReportPart);
		$dfas->setDataFlowActivityParameter('setSubstreamSource', array('substreamSource' => $reportFacade, 'callbackMethod' => 'processReportPart'));
		
		// configures Zip File Writer
		$dfas = $dfasl->getDataFlowActivitySelector(1);
		$dfas->setDataFlowActivityParameter('setFilename', $destFolder.$zipName);
		$dfas->setDataFlowActivityParameter('setRootFolder', $reportPartFolder);
		$dfas->setDataFlowActivityParameter('setDeleteInputFiles', true);
		$dfas->setDataFlowActivityParameter('setDeleteEmptyFolders', true);
		$dfas->setDataFlowActivityParameter('setDeleteRootFolder', true);
		
		// configures File Field Filler
		$dfas = $dfasl->getDataFlowActivitySelector(2);
		$dfas->setDataFlowActivityParameter('setElement', $this->getElement());
		$dfas->setDataFlowActivityParameter('setFieldName', $fName);
		$dfas->setDataFlowActivityParameter('setPathMask', $destFolder);
		
		// gets Configurator for zip file
		$key = $this->getDFASLKey($fName, "zipConfigurator");
		$zipConfigurator = $this->reportDFASL[$key];
		// creates Configurator if not set
		if(!isset($zipConfigurator)) {
			$zipConfigurator = ObjectConfigurator::createInstance();
			$this->reportDFASL[$key] = $zipConfigurator;		
		}
		else $zipConfigurator->reset();
		// initializes configurator
		$zipConfigurator->setConfigValue('name', $reportName);
		$zipConfigurator->setConfigValue('type', '.zip');
		$zipConfigurator->setConfigValue('mime', typeMime('.zip'));		
		$dfas->setDataFlowActivityParameter('setObjectConfigurator', $zipConfigurator);
		
		return $dfasl;		
	}
	private function getDFASLKey($fieldName, $subkey='') {
		return '('.$fieldName.'('.$subkey.'))';
	}	
	protected function createDFASLForMultipartReport() {
		$returnValue = DataFlowActivitySelectorListArrayImpl::createInstance();
		$returnValue->addDataFlowActivitySelector("SubstreamTapDFA");
		$returnValue->addDataFlowActivitySelector("ZipFileWriterDFA");
		$returnValue->addDataFlowActivitySelector("ReportingEltEvalFileFieldFillerDFA");
		return $returnValue;
	}	
	protected function createDFASLForReportPart() {
		$returnValue = DataFlowActivitySelectorListArrayImpl::createInstance();
		$returnValue->addDataFlowActivitySelector("FileOutputStreamDFA");		
		return $returnValue;
	}	
	/**
	 * Creates the string pointing to the root folder where to store the report
	 * (with ending slash)
	 */
	protected function createRootFolderPathStringForReport() {
		$returnValue = dirname($_SERVER["SCRIPT_FILENAME"])."/".$this->getTemporaryUploadedFilePath();
		$lastChar = $returnValue[strlen($returnValue)-1];
		if($lastChar != '/' && $lastChar != '\\') $returnValue = $returnValue.'/';
		return $returnValue;
	}
	/**
	 * Creates the report file name which will be stored into wigii
	 * (a copy of the algorithm found into method FormExecutor->manageUploadedFileForm)	 
	 * @param type is the file extension with the dot 
	 * (optional, if not set, then filename has no extension. It can be used to generate temp folder names)	
	 * @param fieldName the name of the field where the report will be stored
	 */
	protected function createReportFileName($principal, $fieldName, $reportName, $type='') {
		if($type != '' && strpos($type, '.') !== 0) $type = '.'.$type;		
		
		$returnValue = $principal->getWigiiNamespace()->getWigiiNamespaceName().
		"_".time().
		ipToStr($_SERVER["REMOTE_ADDR"]).
		$principal->getUsername().
		$fieldName.
		substr($reportName, 0, 5).
		$type;

		$returnValue = preg_replace('/[^a-zA-Z0-9\.\-\_]/',"",$returnValue);
		return $returnValue;
	}	
}
/**
 * Reporting Element Evaluator File Field Filler Data Flow Activity
 * A data flow activity which receives a file name as input and fills the subfields of a Field of type File
 * Only works in single data mode, doesn't support streams.
 * Created by CWE 2 juillet 2013
 */
class ReportingEltEvalFileFieldFillerDFA implements DataFlowActivity
{				
	private $_debugLogger;
	private $element;
	private $fieldName;
	private $pathMask; // with ending slash
	private $configurator;
	
	// Object lifecycle
		
	public function reset() {
		$this->freeMemory();	
	}	
	public function freeMemory() {
		unset($this->element);
		unset($this->fieldName);
		unset($this->pathMask);
		unset($this->configurator);
	}
	
	// dependency injection
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("ReportingEltEvalFileFieldFillerDFA");
		}
		return $this->_debugLogger;
	}
	
	// configuration
	
	/**
	 * Sets the element in which to store the file
	 */
	public function setElement($element) {
		if(is_null($element)) throw new DataFlowServiceException("element cannot be null", DataFlowServiceException::INVALID_ARGUMENT);
		$this->element = $element;
	}

	/**
	 * Sets the field name of type File in which to store the File
	 */
	public function setFieldName($fieldName) {
		if(is_null($fieldName) || $fieldName == '') throw new DataFlowServiceException("fieldName cannot be null", DataFlowServiceException::INVALID_ARGUMENT);
		$this->fieldName = $fieldName;
	}
			
	/**
	 * Specifies a file path which will act as a mask on the complete file path
	 * of the file which will be stored in the field.
	 * The subfield path will contain the difference between the file complete path and this mask
	 */
	public function setPathMask($path) {
		if(is_null($path) || $path == '') throw new DataFlowServiceException("pathMask cannot be null", DataFlowServiceException::INVALID_ARGUMENT);
		$lastChar = $path[strlen($path)-1];
		if($lastChar != '/' && $lastChar != '\\') $path = $path.'/';
		$this->pathMask = $path;
	}

	/**
	 * Accepts an object configurator to be used to set the File type subfields
	 */
	public function setObjectConfigurator($configurator) {
		if(is_null($configurator)) throw new DataFlowServiceException("configurator cannot be null", DataFlowServiceException::INVALID_ARGUMENT);
		$this->configurator = $configurator;
	}
	
	// stream data event handling
	
	public function startOfStream($dataFlowContext) {
		throw new DataFlowServiceException("only works in single data mode, does not support streams", DataFlowServiceException::UNSUPPORTED_OPERATION);
	}
	public function processDataChunk($data, $dataFlowContext) {
		throw new DataFlowServiceException("only works in single data mode, does not support streams", DataFlowServiceException::UNSUPPORTED_OPERATION);
	}
	public function endOfStream($dataFlowContext) {
		throw new DataFlowServiceException("only works in single data mode, does not support streams", DataFlowServiceException::UNSUPPORTED_OPERATION);
	}
	
	
	// single data event handling
	
	public function processWholeData($data, $dataFlowContext) {
		if(!isset($this->element)) throw new DataFlowServiceException("element has not been set", DataFlowServiceException::CONFIGURATION_ERROR);
		if(!isset($this->fieldName)) throw new DataFlowServiceException("fieldName has not been set", DataFlowServiceException::CONFIGURATION_ERROR);
		$field = $this->element->getFieldList()->getField($this->fieldName);
		if(is_null($field)) throw new DataFlowServiceException("invalid fieldName '$this->fieldName'", DataFlowServiceException::CONFIGURATION_ERROR);
		$dt = $field->getDataType();
		if(is_null($dt) || $dt->getDataTypeName() != 'Files') throw new DataFlowServiceException("field '$this->fieldName' is not of type Files", DataFlowServiceException::CONFIGURATION_ERROR);

		if(!isset($this->configurator)) throw new DataFlowServiceException("configurator has not been set", DataFlowServiceException::CONFIGURATION_ERROR);
		
		$id = $this->element->getId();
		$wigiiBag = $this->element->getWigiiBag();
		$p = $dataFlowContext->getPrincipal();
		
		// extracts file name and directory
		$delim = strrpos($data, '/');
		if($delim === false) {
			$fileName = $data;
			$fileDir = '';
		}
		else {
			$fileName = substr($data, $delim+1);
			$fileDir = substr($data, 0, $delim+1);
		}		
		// extracts file extension
		$delim = strrpos($fileName, '.');
		if($delim === false) {
			$ext = '';
		}
		else {
			$ext = substr($fileName, $delim);
			$fileName = substr($fileName, 0, $delim);
		}
		
		
		
		// name
		$val = $this->configurator->getConfigValue('name');	
		if(is_null($val)) {
			$val = $fileName;
		}	
		$wigiiBag->setValue($val, $id, 'Files', $this->fieldName, 'name');
		// type		
		$val = $this->configurator->getConfigValue('type');	
		if(is_null($val)) {
			if($ext != '') $val = $ext;
			else {
				$mime = $this->configurator->getConfigValue('mime');
				if(isset($mime) && $mime != '') {
					$val = mime2ext($mime);
				}
				else $val = null;
			}
		}	
		$wigiiBag->setValue($val, $id, 'Files', $this->fieldName, 'type');
		// size
		$val = $this->configurator->getConfigValue('size');	
		if(is_null($val)) {
			$val = @filesize($data);
			if($val === false) $val = null;
		}	
		$wigiiBag->setValue($val, $id, 'Files', $this->fieldName, 'size');
		// mime
		$val = $this->configurator->getConfigValue('mime');	
		if(is_null($val)) {
			$extconf = $this->configurator->getConfigValue('type');
			if(isset($extconf) && $extconf != '') $mime = typeMime($extconf);
			elseif($ext != '') $mime = typeMime($ext);
			else $mime = typeMime('.-'); 
		}	
		$wigiiBag->setValue($val, $id, 'Files', $this->fieldName, 'mime');
		// path
		$val = $this->configurator->getConfigValue('path');	
		if(is_null($val)) {
			$this->debugLogger()->write('pathMask: '.$this->pathMask.', file: '.$data);
			// reduces the directory to only subfolders compared to path mask
			if(isset($this->pathMask) && (strpos($data, $this->pathMask) === 0)) {
				$l = strlen($this->pathMask);
				if(strlen($data) > $l) $val = substr($data, $l);
				else $val = $data;
			}
			else $val = $data;
		}	
		$wigiiBag->setValue($val, $id, 'Files', $this->fieldName, 'path');
		// date
		$val = $this->configurator->getConfigValue('date');	
		if(is_null($val)) {
			$val = date("Y-m-d H:i:s", time());
		}	
		$wigiiBag->setValue($val, $id, 'Files', $this->fieldName, 'date');
		// user
		$val = $this->configurator->getConfigValue('user');	
		if(is_null($val)) {
			$val = $p->getRealUserId();
		}	
		$wigiiBag->setValue($val, $id, 'Files', $this->fieldName, 'user');
		// username
		$val = $this->configurator->getConfigValue('username');	
		if(is_null($val)) {
			$val = $p->getRealUsername();
		}	
		$wigiiBag->setValue($val, $id, 'Files', $this->fieldName, 'username');
	}	
}