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

/*
 * Created on 16 april 2010
 * by LWR
 */
class ModuleConfigEditFormExecutor extends FormExecutor {

	private $isNew;
	protected function setIsNew($var){ $this->isNew = $var; }
	protected function isNew(){ return $this->isNew; }

	private $filename;
	protected function setFilename($var){ $this->filename = $var; }
	protected function getFilename(){ return $this->filename; }

	private $actOnCheckedRecordRequest;
	protected function setActOnCheckedRecordRequest($request){ $this->actOnCheckedRecordRequest = $request; }
	protected function getActOnCheckedRecordRequest(){ return $this->actOnCheckedRecordRequest; }

	public static function createInstance($wigiiExecutor, $isNew, $filename, $record, $formId, $submitUrl, $actOnCheckedRecordRequest=""){
		$fe = new self();
		$fe->setWigiiExecutor($wigiiExecutor);
		$fe->setRecord($record);
		$fe->setFormId($formId);
		$fe->setSubmitUrl($submitUrl);
		$fe->setIsNew($isNew);
		if($filename!=null){
			$fe->setFilename(CLIENT_CONFIG_PATH.iconv("UTF-8", "ISO-8859-1", $filename));
		} else {
			$fe->setFilename(null);
		}
		$fe->setActOnCheckedRecordRequest($actOnCheckedRecordRequest);
		return $fe;
	}

	protected function setState($state){
		switch($state){
			case "start":
			case "check":
			case "save":
			case "saveAndClose":
				break;
			default: throw FormExecutorException("invalid state: ".$state, FormExecutorException::INVALID_STATE);
		}
		$this->state = $state;
	}
	protected function getState(){ return $this->state; }
	protected function goToNextState(){
		switch($this->state){
			case "save":
				$this->setState("save");
				break;
			case "saveAndClose":
			case "start":
			default:
				$this->setState("check");
		}
		return $this->state;
	}

	private $allowedPath;
	public function actOnFilename($path){
		$this->allowedPath[$path] = $path;
	}
	protected function doSpecificCheck($p, $exec){

		$transS = ServiceProvider::getTranslationService();
		$configS = $this->getWigiiExecutor()->getConfigurationContext();
		$rec = $this->getRecord();

		//check the filename (cannot contain directory)
		$filename = $rec->getFieldValue("moduleEditorFilenameField");
		$info = pathinfo($filename);
		if($info['dirname']!=="." || $info['extension']==null || $info['filename']==null){
			$this->addErrorToField($transS->h($p, "invalidFilename"), "moduleEditorFilenameField");
		}
		if(!$p->isWigiiNamespaceCreator()){
			//check that the edited file is a wigiiNamespace_module_config.xml file
			$this->allowedPath = array();
			$configS->listModuleConfigFilesForWigiiNamespace($p, $this);
			if($this->allowedPath[CLIENT_CONFIG_PATH.$info['basename']]==null){
				$this->addErrorToField($transS->h($p, "invalidFilename"), "moduleEditorFilenameField");
			}
		}
		if(!$this->hasError()){
			$this->setFilename(CLIENT_CONFIG_PATH.$info['basename']);
		}
		//if wigiiNamespace creator use the delete button to delete file. --> content is mandatory
		//for the moduleEditor content can be empty --> file will be deleted (but this is only his specific wigiiNamespace file)
		if($p->isWigiiNamespaceCreator() && $rec->getFieldValue("moduleEditorConfigField")==null){
			$this->addErrorToField($transS->h($p, "compulsory_field"), "moduleEditorConfigField");
		}

		//check xml content if xml file:
		if($info['extension']=="xml" && $rec->getFieldValue("moduleEditorConfigField")){
			libxml_use_internal_errors(true);
			libxml_clear_errors();
			$errors = array();
			simplexml_load_string(stripslashes($rec->getFieldValue("moduleEditorConfigField")));
			$errors = libxml_get_errors();
			libxml_use_internal_errors(false);
			if($errors){
				$errorsToDisplay = "";
				foreach($errors as $key=>$error){
		         	$errorsToDisplay .="<br />Line ".($error->line-1).": ".$error->message;
				}
				$this->addErrorToField($transS->h($p, "invalidXml").$errorsToDisplay, "moduleEditorConfigField");
			}
		}
		//check php syntax if php file:
		/*
		if($info['extension']=="php" && $rec->getFieldValue("moduleEditorConfigField")){
			$returnValue = null;
			$errorLevel = error_reporting(0);
			try {
				$returnValue = eval(stripslashes(str_replace('<?php', '', $rec->getFieldValue("moduleEditorConfigField"))));
			}
			catch(Exception $e) {
				// adds any catched exception
				if($e instanceof ServiceException) $e = $e->getWigiiRootException();
				$errorMessage = "Exception while evaluating php code (".$e->getCode().") ".$e->getMessage().'<br/>';
				$errorMessage .= str_replace("\n", '<br/>', str_replace('\\', '/', (string)$e));
				$this->addErrorToField($errorMessage, "moduleEditorConfigField");
			}
			error_reporting($errorLevel);
			// add any php error
			if($returnValue === false && ( $phpError = error_get_last() )) {
				$errorMessage = "PHP error while parsing php code (".$phpError['type'].") on line ".$phpError['line'].": ".$phpError['message'];
				$this->addErrorToField($errorMessage, "moduleEditorConfigField");
			}
			// add any output (prevent saving the file if the script generated some output)
			elseif($returnValue !== null) {
				$errorMessage = "PHP code returned some output. Cannot save the file. Output: <br/>".put($returnValue);
				$this->addErrorToField($errorMessage, "moduleEditorConfigField");
			}
		}
		*/
	}

	protected function actOnCheckedRecord($p, $exec) {

		$config = $this->getWigiiExecutor()->getConfigurationContext();
		$transS = ServiceProvider::getTranslationService();

		$rec = $this->getRecord();

		if(	!$p->isModuleEditor() ){
			throw new ServiceException('editing module config need to be module editor', ServiceException::FORBIDDEN);
		}
		if(	!$p->isWigiiNamespaceCreator() && $this->isNew() ){
			throw new ServiceException('adding new config files need to be wigiiNamespace creator', ServiceException::FORBIDDEN);
		}

		if($rec->getFieldValue("moduleEditorConfigField") != null){
			file_put_contents($this->getFilename(), stripslashes($rec->getFieldValue("moduleEditorConfigField")));
		} else {
			@unlink($this->getFilename());
		}

		if($this->getState()=="save"){
			//keep the dialog open.
			$this->addStateError();
			$exec->addJsCode('$("form .removeDisableOnSubmit").attr("disabled", true);');

		} else {
			//important to clear the cancel stack
			$exec->addJsCode("" .
				"actOnCloseDialog('".$exec->getIdAnswer()."');" .
				"adminModuleEditor_crtSelectedModuleConfig = '".basename($this->getFilename())."';" .
				"");

			$this->getWigiiExecutor()->operationSuccessfullMessage($exec->getIdAnswer(), 350, $transS->t($p, "operationDoneSuccessfully"), "", "done");

			if($this->getActOnCheckedRecordRequest()!=null){
				$exec->addRequests($this->getActOnCheckedRecordRequest());
			}
		}

	}


	protected function doRenderForm($p, $exec){
		$transS = ServiceProvider::getTranslationService();
		$config = $this->getWigiiExecutor()->getConfigurationContext();
		$rec = $this->getRecord();

//		$rec->getWigiiBag()->setHelp($transS->h($p, "authenticationServerHelp"), "authenticationServer");
		$this->getTrm()->setState($this->getState());
		$this->getTrm()->setFormRenderer($this->getFormRenderer());

		$this->getTrm()->openForm($this->getFormId(), $this->getSubmitUrl(), $this->getTotalWidth(), $this->isDialog());

		if($this->getState() == "start"){
			$cfile = $this->getFilename();
			if(file_exists($cfile)){
				$rec->setFieldValue(addslashes(file_get_contents($cfile)), "moduleEditorConfigField");
			}
			$rec->setFieldValue(basename($this->getFilename()), "moduleEditorFilenameField");
		}
		$rec->getWigiiBag()->setReadonly(!$this->isNew(), "moduleEditorFilenameField");

		$this->getTrm()->displayRemainingForms();

		$this->getTrm()->closeForm($this->getFormId(), $this->goToNextState(), $this->getSubmitLabel(), $this->isDialog());

		$this->getWigiiExecutor()->openAsDialogForm3B(
			$exec->getIdAnswer(), $this->getTotalWidth()+$this->getCorrectionWidth(),
			' $("form input[name=\'action\']", this).val("save"); $("form", this).submit(); ', $transS->t($p, $rec->getActivity()->getActivityName()),
			$transS->t($p, "save"), $transS->t($p, "saveAndClose"), $transS->t($p, "cancel"), null,
			' $("form input[name=\'action\']", this).val("saveAndClose"); $("form", this).submit(); ',
			'{ my : "center top", at: "center top" }', null, false, true, false, false, true);


		$this->getTrm()->addJsCodeAfterFormIsShown($this->getFormId());

		$this->getTrm()->addJsCode("" .
				"function getLineNumber(textarea, indicator) {" .
					"$(indicator).html($(textarea).val().substr(0, $(textarea).get()[0].selectionStart).split('\\n').length);" .
				"} " .
				"$('#".$this->getFormId()."_moduleEditorConfigField_value_textarea').after('line: <span id=\"".$this->getFormId()."_moduleEditorConfigField_value_textarea_lineNumber\">1</span>');" .
				"$('#".$this->getFormId()."_moduleEditorConfigField_value_textarea').keyup(function(){ getLineNumber($(this), $('#".$this->getFormId()."_moduleEditorConfigField_value_textarea_lineNumber')); }).mouseup(function(){ $(this).keyup(); });");


		//the tabby plugin is already called as the config editor has the parameter noWrap=1
	}
}



