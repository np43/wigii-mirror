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
 * Abstract FormExecutor class, parent class of all Form controllers present in the Wigii WebImpl.
 * Created on 15 sept. 09 by LWR
 * Modified by Medair in 2016 for maintenance purposes (see SVN log for details)
 */
abstract class FormExecutor extends Model implements RecordStructureFactory, TRMProducer {
	private $detailRenderer;
	private $formChecker;
	private $formRenderer;
	//private $record;
	private $protectWithCaptcha = false;
	public function protectWithCaptcha(){ return $this->protectWithCaptcha; }
	public function setProtectWithCaptcha($doProtection){ $this->protectWithCaptcha = $doProtection; }

	private $wigiiExecutor;
	protected function setWigiiExecutor($var){$this->wigiiExecutor = $var; }
	/**
	 * @return WigiiExecutor
	 */
	public function getWigiiExecutor(){return $this->wigiiExecutor; }

	public static function createInstance($wigiiExecutor, $record, $formId, $submitUrl){
		$fe = new self();
		$fe->setWigiiExecutor($wigiiExecutor); //important to be in the begining because other setter could use the configurationContext as configService
		$fe->setRecord($record);
		$fe->setFormId($formId);
		$fe->setSubmitUrl($submitUrl);
		return $fe;
	}

	private $_debugLogger;
	private $_executionSink;
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("FormExecutor");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("FormExecutor");
		}
		return $this->_executionSink;
	}

	public function createFieldList(){
		$fl = FormFieldList::createInstance($this->getRecord()->getWigiiBag());
		return $fl;
	}
	public function createWigiiBag(){
		$wb = FormBag::createInstance();
		return $wb;
	}

	// association with DetailRenderer class
	public function getDetailRenderer(){
		if(!isset($this->detailRenderer)){
			$this->detailRenderer = DetailRenderer::createInstance($this->getFormId(), $this->getTrm(), $this->getTotalWidth(), $this->getLabelWidth(), $this->getLanguageVisible());
		}
		return $this->detailRenderer;
	}
	public function setDetailRenderer($detailRenderer){ $this->detailRenderer = $detailRenderer; }
	// association with FormChecker class
	public function getFormChecker(){
		if(!isset($this->formChecker)){
			$this->formChecker = FormChecker::createInstance($this);
		}
		return $this->formChecker;
	}
	public function setFormChecker($formChecker){ $this->formChecker = $formChecker; }
	// association with FormRenderer class
	public function getFormRenderer(){
		if(!isset($this->formRenderer)){
			$this->formRenderer = FormRenderer::createInstance($this, $this->getFormId(), $this->getTrm(), $this->getTotalWidth(), $this->getLabelWidth(), $this->getLanguageVisible());
			$this->formRenderer->setIsMultiple($this->isMultiple());
		}
		return $this->formRenderer;
	}
	public function setFormRenderer($formRenderer){ $this->formRenderer = $formRenderer; }
	// association with Record class
	//and automatically create a trm

	private $elementPolicyEvaluator;
	/**
	 * Injects the ElementPolicyEvaluator instance to be used by the FormExecutor
	 * @param ElementPolicyEvaluator $elementPolicyEvaluator
	 */
	public function setElementPolicyEvaluator($elementPolicyEvaluator) {
		$this->elementPolicyEvaluator = $elementPolicyEvaluator;
		$this->elementPolicyEvaluator->setFormExecutor($this);
	}
	/**
	 * Returns the injeced ElementPolicyEvaluator to be used. Can be null, if no policy should be calculated.
	 * @return ElementPolicyEvaluator or null if not injected.
	 */
	protected function getElementPolicyEvaluator() {
		return $this->elementPolicyEvaluator;
	}

	private $isForNotification; //in that case we check the attribut NotAlert of the field
	public function setIsForNotification($var) {
		$this->isForNotification = $var;
		if($this->getTrm()){
			$this->getTrm()->setForNotification($var);
		}
	}
	public function isForNotification(){return $this->isForNotification;}

	private $isForPrint; //in that case we check the attribut NotAlert of the field
	public function setIsForPrint($var) {
		$this->isForPrint = $var;
		if($this->getTrm()){
			$this->getTrm()->setForPrint($var);
		}
	}
	public function isForPrint(){return $this->isForPrint;}

	private $isForExternalAccess; //in that case we disable a few actions on DT details
	public function setIsForExternalAccess($var) {
		$this->isForExternalAccess = $var;
		if($this->getTrm()){
			$this->getTrm()->setForExternalAccess($var);
		}
	}
	public function isForExternalAccess(){return $this->isForExternalAccess;}

	private $templateRecordManager;
	public function setTrm($trm){ $this->templateRecordManager = $trm; }
	public function getTrm(){ return $this->templateRecordManager; }
	public function getRecord() {
		if(isset($this->templateRecordManager))
		return $this->getTrm()->getRecord();
		else return null;
	}
	public function setRecord($record) {
		if(!isset($this->templateRecordManager)){
			$this->templateRecordManager = $this->createTrmInstance();
		}
		$this->templateRecordManager->reset($record);
	}
	protected function createTrmInstance(){
		$returnValue = $this->getWigiiExecutor()->createTRM(null, $this->isForNotification(), $this->isForPrint(), $this->isForExternalAccess(), false, false, true);
		$returnValue->setFormExecutor($this);
		return $returnValue;
	}

	private $submitUrl;
	public function getSubmitUrl() { return $this->submitUrl; }
	public function setSubmitUrl($url) {
		$this->submitUrl = "Update/".ServiceProvider::getExecutionService()->getCrtContext()."/__/".$url;
	}
	private $submitLabel;
	public function getSubmitLabel() {
		if(!isset($this->submitLabel)){
			$transS = ServiceProvider::getTranslationService();
			$p = ServiceProvider::getAuthenticationService()->getMainPrincipal();
			$this->submitLabel = $transS->t($p, "ok");
		}
		return $this->submitLabel;
	}
	public function setSubmitLabel($label) { $this->submitLabel = $label; }
	private $formId;
	public function getFormId() { return $this->formId; }
	public function setFormId($id) { $this->formId = $id; }
	private $isDialog;
	public function isDialog() {
		if(!isset($this->isDialog)){
			$this->isDialog = true;
		}
		return $this->isDialog;
	}
	public function setIsDialog($isDialog) { $this->isDialog = $isDialog; }
	
	public function isWorkzoneViewDocked() {
		return $this->getWigiiExecutor()->isWorkzoneViewDocked();
	}
	
	private $languageVisible;
	public function getLanguageVisible() {
		if(!isset($this->languageVisible)){
			$this->languageVisible = ServiceProvider::getTranslationService()->getVisibleLanguage();
		}
		return $this->languageVisible;
	}
	private $isMultiple;
	public function isMultiple() {
		return $this->isMultiple;
	}
	public function setIsMultiple($isMultiple) { $this->isMultiple = $isMultiple; }
	private $datatypePath;
	public function getDatatypePath() {
		if(!isset($this->datatypePath)){
			$this->datatypePath = DATATYPE_PATH;
		}
		return $this->datatypePath;
	}
	public function setDatatypePath($path) { $this->datatypePath = $path; }
	private $temporaryUploadedFilePath;
	public function getTemporaryUploadedFilePath() {
		if(!isset($this->temporaryUploadedFilePath)){
			$this->temporaryUploadedFilePath = TEMPORARYUPLOADEDFILE_path;
		}
		return $this->temporaryUploadedFilePath;
	}
	public function setTemporaryUploadedFilePath($path) { $this->temporaryUploadedFilePath = $path; }
	private $temporaryUploadedFileLifetime;
	public function getTemporaryUploadedFileLifetime() {
		if(!isset($this->temporaryUploadedFileLifetime)){
			$this->temporaryUploadedFileLifetime = TEMPORARYUPLOADEDFILE_lifetime;
		}
		return $this->temporaryUploadedFileLifetime;
	}
	public function setTemporaryUploadedFileLifetime($maxTime) { $this->temporaryUploadedFileLifetime = $maxTime; }

	private $labelWidth;
	public function getLabelWidth() {
		if(!isset($this->labelWidth)){
			$this->labelWidth = 150;
		}
		return $this->labelWidth;
	}
	public function setLabelWidth($width) {
	    $this->labelWidth = $width;
	}
	private $totalWidth;
	public function getTotalWidth() {
		if(!isset($this->totalWidth)){
			$this->totalWidth = 400;
		}
		return $this->totalWidth;
	}
	public function setTotalWidth($width) {$this->totalWidth = $width; }
	private $correctionWidth;
	public function getCorrectionWidth() {return $this->correctionWidth; }
	public function setCorrectionWidth($width) { $this->correctionWidth = $width; }

	private $hasError;
	public function hasError() { return $this->hasError; }
	public function addErrorToField($error,$fieldName) {
		$this->executionSink()->log("Add error to field: $fieldName ($error)");
		$this->getRecord()->getWigiiBag()->addError($error, $fieldName);
		$this->hasError = true;
	}
	public function addStateError(){
		$this->executionSink()->log("Add error to prevent ending loop. Form state is: ".$this->getState());
		$this->hasError = true;
	}

	protected $state;
	protected function setState($state){
		switch($state){
			case "start":
			case "check":
				break;
			default: throw new FormExecutorException("invalid state: ".$state, FormExecutorException::INVALID_STATE);
		}
		$this->state = $state;
	}
	public function getState(){ return $this->state; }
	protected function goToNextState(){
		switch($this->state){
			case "start":
			default:
				$this->setState("check");
		}
		return $this->state;
	}
	protected abstract function doSpecificCheck($p, $exec);

	protected abstract function actOnCheckedRecord($p, $exec);

	protected abstract function doRenderForm($p, $exec);

	public function manageUploadedFileForm($p, $exec){
		$get =& $_POST;

		//dans le cas où la variable $_FILES n'est pas vide, on prérempli les champs
		//propre aux fichiers en se basant sur le nom du champ file
		$uploaddir = dirname($_SERVER["SCRIPT_FILENAME"])."/".$this->getTemporaryUploadedFilePath();
		//eput($_FILES);

		//CLEAN OLD TEMPORARY UPLOADED FILE
		if($_FILES!=null){
			//nettoyage de la zone fichier uploader temporaire, basé sur leur nom...
			//on efface ceux qui sont plus vieux que tant de temps:
			$files = File::list_files($this->getTemporaryUploadedFilePath());
			if ($files==null) $files=array();
			$time = time();
			foreach ($files as $fileName){
				//on recherche dans le nom du fichier le timestamp unix qui correpsond au
				//10 caractères du nom qui suive le wigiiNamespace du user courant
				//on le compare avec le temps actuel
				//s'il y a un écart plus grand qu'une journée, on efface le fichier
				//$timeUploded = (int)substr($fileName, 0, 10);
				$timeUploded = array();
				preg_match("/".preg_replace('/[^a-zA-Z0-9\.\-\_]/',"",$p->getWigiiNamespace()->getWigiiNamespaceName())."_([0-9]{10})/", $fileName, $timeUploded);
				$timeUploded = $timeUploded[1];
				if($timeUploded == null) continue; //this case is when the temp file is not in the same wigiiNamespace. -> we ignore it
				//$exec->debug(put($timeUploded));
				if($time>=$timeUploded+$this->getTemporaryUploadedFileLifetime()){
					if (!@unlink($this->getTemporaryUploadedFilePath().$fileName)){
						$this->debugLogger()->write("impossible to erase the temporary upload file:".$this->getTemporaryUploadedFilePath().$fileName);
					}
				}
			}
		}

		//UPLOADED FILE MANAGEMENT
		//- move in temporaryUploaded file
		//- add mime type, size, user information etc.
		foreach($_FILES as $fieldName=>$fileData){
			//s'il y a plusieurs upload de fichier, même si rien n'est mis dans le
			//champ input d'un fichier il apparaîtra tout de même dans la variable $_FILES
			//dans le cas où il n'y a pas de nom, ni de nom temporaire cela indique qu'en fait
			//il n'y a pas eu pour ce fichier d'upload
			if($_FILES[$fieldName]["name"]==null && $_FILES[$fieldName]["tmp_name"] == null) continue;

			//pour obtenir le nom du fieldName il suffit de virer dans le string
			//le dernier "_file" qui est ajouté en raison du type de donnée
			$fieldName = preg_replace('/_file$/', "", $fieldName); //on prend réellement le nom du field
			//eput($fieldName);

			//on ajoute au nom du fichier l'ip de l'utilisateur courant, le moment ou ce fichier
			//est uploader, surtout le nom du field, l'utilisateur courant et les 5 premier caractère du nom du fichier
			//on supprime tout caractère qui n'est pas alphanumérique dans le nom du fichier, pour éviter
			//des problèmes d'accès par la suite
			//on garde tout de même l'extension sur le disque, ainsi on sait quel genre de fichier c'est...
			$ext = explode(".", $fileData["name"]);
			if(count($ext) > 1){ //s'il n'y a pas d'extension, alors l'extension est vide...
				$ext = end($ext);
				//prevent script extensions
				if(preg_match(Files::Script_Extensions,$ext)){
					$ext = "no".$ext.".txt";
				}
				$ext = ".".$ext;
			} else $ext = "";


			//ici on ajoute un préfix du wigiiNamespace à chaque fichier de manière
			//a pouvoir retrouver de manière très simple les fichiers par wigiiNamespace
			$uploadfile = $p->getWigiiNamespace()->getWigiiNamespaceName()."_".time().ipToStr($_SERVER["REMOTE_ADDR"]).$p->getUsername().$fieldName.substr(basename($fileData['name']), 0, 5).$ext;

			//on supprime tous les caractères qui serait bizarre dans le nouveau nom du fichier...
			$uploadfile = preg_replace('/[^a-zA-Z0-9\.\-\_]/',"",$uploadfile);
			$path = $uploadfile;
			$uploadfile = $this->getTemporaryUploadedFilePath().$uploadfile;
			$uploadfile = str_replace("\\", "/", $uploadfile);
//				eput("try to rename ".$fileData['tmp_name']." in ".$uploadfile."\n");
			if (rename($fileData['tmp_name'], $uploadfile)) {
				$this->debugLogger()->write("move ".$fileData['tmp_name']." in ".$uploadfile);
				$old = umask(0000);
				if(chmod($uploadfile, 0666)){
					//$exec->debug("filemod is changed to 666.\n");
					$this->debugLogger()->write("filemod is changed to 666 for ".$uploadfile);
				}
				umask($old);
//					eput("upload ".$fileData['tmp_name']." in ".$uploadfile."\n");

				//$get[$fieldName."_name"] = str_replace($ext, "", $fileData["name"]);
				$get[$fieldName."_type"] = strtolower($ext);
				$get[$fieldName."_size"] = $fileData["size"];
				$get[$fieldName."_path"] = $path;
				//to keep the information of the originalFilename in case we want to prepopulate it in the _name
				//used in FormChecker if($fieldParams["noActiveJS"]=="1" && !$get[$fieldName."_name"])
				$get[$fieldName."_originalFilename"] = $fileData['name'];
			} else {
				$this->executionSink()->log("!!!! problem in move_uploaded_file ".$fileData['tmp_name']." in ".$uploadfile." !!!!\n");
			}
		}
	}

	public function preventInjectionForm($p, $exec){
		$get =& $_POST;

		//FORM INJECTION PREVENTION

		//traitement de sécurité pour des injections dans les formulaires
		//si l'option magic_quotes_gpc est ON alors cela ajoute en trop des slashes
		//attention ini_get renvoie null ou 0 si c'est une valeur boolean à off
		//et sinon renvoie -1 ou 1
		$magicQuotes = false;
		if(ini_get("magic_quotes_gpc")){
			//c'est le plus simples, tout ce qui passe à vrai on ne fait rien..
			//car de mettre un ! ne fonctionne pas nécessairement...
			//je suppose qu'il faudrait mettre != true :)
			$magicQuotes = true;
		}
		foreach ($get as $key=>$value){
			if(is_array($value)){
				foreach($value as $tempKey=>$tempValue){
					//important to do the check with null, to prevent transforming null in ""
					if(!$magicQuotes && $get[$key][$tempKey]!=null) $get[$key][$tempKey] = addslashes(str_replace('\\', '&#92;',$get[$key][$tempKey]));
				}
			} else {
				//important to do the check with null, to prevent transforming null in ""
				if(!$magicQuotes && $get[$key]!=null) $get[$key] = addslashes(str_replace('\\', '&#92;', $get[$key]));
			}
		}
	}

	/**
	 * usually this method is only called from the resolveForm method.
	 */
	public function CheckForm($p, $exec) {
		$this->executionSink()->publishStartOperation("CheckForm", $p);
		try {

			$config = $this->getWigiiExecutor()->getConfigurationContext(); //ServiceProvider::getConfigService();

			$this->manageUploadedFileForm($p, $exec);

			$this->preventInjectionForm($p, $exec);

			//VISITOR CHECK ON FIELDS
			//check de chaque champ du formulaire
			$this->getFormChecker()->setP($p);
			//before checking the record unset changes set when filling form
			$this->getRecord()->getWigiiBag()->resetChanges();
			$fieldListIterator = $this->getRecord()->getFieldList()->getListIterator();
			if($fieldListIterator){
				foreach($fieldListIterator as $field){
					$field->acceptFieldListVisitor($this->getFormChecker());
				}
				//AUTO CALC fields:
				if(!$this->isMultiple() && !$this->hasError()){ //evaluation should not happen on multiple modify as the evaluation is done then on each item
					// if subelement -> then tries to get the sub element evaluator
					// if not found then gets root evaluator
					$rec = $this->getRecord();
					if($rec instanceof Element && $rec->isSubElement()) {
						$evaluatorClassName = (string)$config->getParameter($p, $rec->getModule(), "Element_evaluator");
					}
					else $evaluatorClassName = null;
					if(empty($evaluatorClassName)) $evaluatorClassName = (string)$config->getParameter($p, $exec->getCrtModule(), "Element_evaluator");
					$evaluator = ServiceProvider::getRecordEvaluator($p, $evaluatorClassName);
					// injects a reference to the current FormExecutor
					$evaluator->setFormExecutor($this);
					// evaluates the record
					if($exec->getCrtAction()!="autoSave"){
						$evaluator->evaluateRecord($p, $this->getRecord());
					}
				}
			}

		} catch(Exception $e) {
			$this->executionSink()->publishEndOperationOnError("CheckForm", $e, $p);
			throw new FormExecutorException('',ServiceException::WRAPPING, $e);
		}

		$this->validateCaptcha();

		$this->executionSink()->publishEndOperation("CheckForm", $p);
	}

	protected function validateCaptcha(){
		$this->getTrm()->validateCaptcha($this);
	}

	/**
	 * updateHiddenFields update some hidden subfield from files and return a fieldSelector with subFields which need to be updated
	 * $oldRecord is to check if there is change of Files in Files field or not, or in Emails fields for proof
	 * returns null or A FieldSelectorList filled with the specific File fields which has a new File or changes in Emails
	 */
	public function updateHiddenFields($p, $exec, $storeFileInWigiiBag, $oldRecord=null){
		$fieldSelectorList = $this->createInstanceFieldSelectorList();
		$record = $this->getRecord();
		$fieldListIterator = $this->getRecord()->getFieldList()->getListIterator();
		if($fieldListIterator){
			foreach($fieldListIterator as $field){
				if($field->getDataType() != null && $field->getDataType()->getDataTypeName()=="Emails"){
					$fieldName = $field->getFieldName();
					$fieldSelectorList->addFieldSelector($fieldName, "value"); //if there is no change only the value should be updated to not loose the other fields
					//the insertElement currently use dataType strategy so all subfields must be added in FS
					if($record->getWigiiBag()->isChanged($fieldName)){
						$fieldSelectorList->addFieldSelector($fieldName, "proofKey");
						$fieldSelectorList->addFieldSelector($fieldName, "proofStatus");
						$fieldSelectorList->addFieldSelector($fieldName, "proof");
						$fieldSelectorList->addFieldSelector($fieldName, "externalConfigGroup");
						$fieldSelectorList->addFieldSelector($fieldName, "externalCode");
						$fieldSelectorList->addFieldSelector($fieldName, "externalAccessLevel");
						$fieldSelectorList->addFieldSelector($fieldName, "externalAccessEndDate");
					}
				}
				if($field->getDataType() != null && $field->getDataType()->getDataTypeName()=="Files"){
					$fieldName = $field->getFieldName();

					$fieldXml = $field->getXml();
					if($fieldXml["htmlArea"]=="1"){
						$fieldSelectorList->addFieldSelector($fieldName, "path");
						$fieldSelectorList->addFieldSelector($fieldName, "name");
						$fieldSelectorList->addFieldSelector($fieldName, "size");
						$fieldSelectorList->addFieldSelector($fieldName, "type");
						$fieldSelectorList->addFieldSelector($fieldName, "mime");
						$fieldSelectorList->addFieldSelector($fieldName, "date");
						$fieldSelectorList->addFieldSelector($fieldName, "user");
						$fieldSelectorList->addFieldSelector($fieldName, "username");
						$fieldSelectorList->addFieldSelector($fieldName, "version");
						$fieldSelectorList->addFieldSelector($fieldName, "textContent");
						//no utf8 conversion needed, because the textContent is collate with utf8. if it was binary then conversion should occurs
						$record->setFieldValue($record->getFieldValue($fieldName, "textContent"), $fieldName, "textContent");
						continue;
					}

					$fileName = $record->getFieldValue($fieldName, "path");
					$oldFileName = ($oldRecord != null ? $oldRecord->getFieldValue($fieldName, "path") : null);

					//fput("field $fieldName is $fileName and was before $oldFileName");
					//if the deleteFile is checked then delete the oldFile + thumbnail
					if($fileName == null){
	//					fput("toDelete checked: empty File field $fieldName in DB");
						//delete all information of the file
						$record->setFieldValue(null, $fieldName, "path");
						$record->setFieldValue(null, $fieldName, "name");
						$record->setFieldValue(null, $fieldName, "size");
						$record->setFieldValue(null, $fieldName, "type");
						$record->setFieldValue(null, $fieldName, "mime");
						$record->setFieldValue(null, $fieldName, "date");
						$record->setFieldValue(null, $fieldName, "user");
						$record->setFieldValue(null, $fieldName, "username");
						$record->setFieldValue(null, $fieldName, "thumbnail");
						$record->setFieldValue(null, $fieldName, "content");
						$record->setFieldValue(null, $fieldName, "textContent");

						$fieldSelectorList->addFieldSelector($fieldName, "path");
						$fieldSelectorList->addFieldSelector($fieldName, "name");
						$fieldSelectorList->addFieldSelector($fieldName, "size");
						$fieldSelectorList->addFieldSelector($fieldName, "type");
						$fieldSelectorList->addFieldSelector($fieldName, "mime");
						$fieldSelectorList->addFieldSelector($fieldName, "date");
						$fieldSelectorList->addFieldSelector($fieldName, "user");
						$fieldSelectorList->addFieldSelector($fieldName, "username");
						$fieldSelectorList->addFieldSelector($fieldName, "thumbnail");
						$fieldSelectorList->addFieldSelector($fieldName, "content");
						$fieldSelectorList->addFieldSelector($fieldName, "textContent");
						
						// resets version number, except if keepLastVersionNumberOnDelete=1
						if($fieldXml["keepLastVersionNumberOnDelete"]!="1") {
							$record->setFieldValue(null, $fieldName, "version");
							$fieldSelectorList->addFieldSelector($fieldName, "version");
						}
					}
					//else if storeFileInWigiiBag and new document set then update the content
					else if($fileName!=null &&
						($oldFileName == null || $oldFileName!= $fileName)){

						//if new file then update the content in every case
						$fieldSelectorList->addFieldSelector($fieldName, "path");
						$fieldSelectorList->addFieldSelector($fieldName, "name");
						$fieldSelectorList->addFieldSelector($fieldName, "size");
						$fieldSelectorList->addFieldSelector($fieldName, "type");
						$fieldSelectorList->addFieldSelector($fieldName, "mime");
						$fieldSelectorList->addFieldSelector($fieldName, "date");
						$fieldSelectorList->addFieldSelector($fieldName, "user");
						$fieldSelectorList->addFieldSelector($fieldName, "username");
						$fieldSelectorList->addFieldSelector($fieldName, "version");
						$fieldSelectorList->addFieldSelector($fieldName, "thumbnail");
						$fieldSelectorList->addFieldSelector($fieldName, "content");
						$fieldSelectorList->addFieldSelector($fieldName, "textContent");

						//load the content in the wigiiBag if storeFileInWigiiBag
						if($storeFileInWigiiBag){
							$this->moveTempFileToWigiiBag($record, $field);
						}
					}
				}
			}
		}			
		
		if(!$fieldSelectorList->isEmpty()) return $fieldSelectorList;
		else return null;
	}	
	/**
	 * Moves a temp file into the WigiiBag for one given Field into a Record
	 * @param Record $record the record containing the file to move
	 * @param Field $field the field of type Files
	 */
	public function moveTempFileToWigiiBag($record,$field) {
		$fieldName = $field->getFieldName();
		$fileName = $record->getFieldValue($fieldName, "path");
		$filePath = TEMPORARYUPLOADEDFILE_path.$fileName;
		$fp = @fopen($filePath, 'rb');
		if ($fp === false) throw new ServiceException("Error on opening the temporaryUploadedFile ".$filePath, ServiceException::FORBIDDEN);
		//if (!file_exists($filePath)) throw new ServiceException("Error on opening the temporaryUploadedFile ".$filePath, ServiceException::FORBIDDEN);
		else {
			//						fput("move  $filePath in DB");
			//$val = "LOAD_FILE('".dirname($_SERVER["SCRIPT_FILENAME"])."/".$filePath."')";
			$val = addslashes(fread($fp, filesize($filePath)));
			//						fput("fread: ".md5($val));
			$record->setFieldValue($val, $fieldName, "content");
			fclose($fp);
		
			//if this is an image then create the thumbnail
			if(isImage($record->getFieldValue($fieldName, "mime"))){
				$fieldXml = $field->getXml();
				$thumbFilePath = TEMPORARYUPLOADEDFILE_path."tn_".$fileName;
				if($fieldXml["displayPreviewOnly"] == "1"){
					//								fput("cutImage ".$thumbFilePath." as preview");
					cutImage($filePath, $thumbFilePath, 150);
				} else {
					//								fput("cutImage ".$thumbFilePath." as thumbnail");
					cutImage($filePath, $thumbFilePath, 80, 80);
				}
				//load the thumbnail in DB
				$fp = @fopen($thumbFilePath, 'rb');
				if ($fp !== false){ //can be the case if the original file is to big
					$val = addslashes(fread($fp, filesize($thumbFilePath)));
					fclose($fp);
					//								fput("move  thumbnail $thumbFilePath in DB");
					$record->setFieldValue($val, $fieldName, "thumbnail");
					//know we can delete the thumbnail
					if (!@unlink($thumbFilePath)) $this->executionSink()->log("Unable to delete the temporary thumb file:".$thumbFilePath." after loading his content in the database.");
				}
			} else {
				$record->setFieldValue(null, $fieldName, "thumbnail");
			}
		
			//						fput("delete the temp file $filePath");
			if (!@unlink($filePath)) $this->executionSink()->log("Unable to delete the temporary file:".$filePath." after loading his content in the database.");
		}
	}
	/**
	 * Updates the Files on disk for all Fields of type Files or Texts/Blobs with HTML.
	 * @param Principal $p current principal executing the action	 
	 * @param ExecutionService $exec current instance of ExecutionService
	 * @param Record $record Record for which Files are beeing updated
	 * @param Field $field field for which Files are beeing updated
	 * @param boolean $storeFileInWigiiBag if true, then Files content are stored into the DB and not on the File system
	 * @param Record $oldRecord old Record, as currently into the DB (is to check if there is changes in the Files fields)
	 * @param Boolean $isAfterInsert should be true, if current Element has just been inserted into the DB and not yet reloaded (will make getFieldValueBeforeInsert to fetch the values)
	 */
	public function updateFilesOnDisk($p, $exec, $storeFileInWigiiBag, $oldRecord=null, $isAfterInsert=false){
		$record = $this->getRecord();
		$fieldListIterator = $this->getRecord()->getFieldList()->getListIterator();
		if($fieldListIterator){
			foreach($fieldListIterator as $field){
				$this->updateFilesOnDiskForField($p, $exec, $record, $field, $storeFileInWigiiBag, $oldRecord, $isAfterInsert, true);
			}
		}
	}

	/**
	 * Updates the Files on disk which are related to a given Field of type Files or Blobs with HTML.
	 * @param Principal $p current principal executing the action	 
	 * @param ExecutionService $exec current instance of ExecutionService
	 * @param Record $record Record for which Files are beeing updated
	 * @param Field $field field for which Files are beeing updated
	 * @param boolean $storeFileInWigiiBag if true, then Files content are stored into the DB and not on the File system
	 * @param Record $oldRecord old Record, as currently into the DB (is to check if there is changes in the Files fields)
	 * @param Boolean $isAfterInsert should be true, if current Element has just been inserted into the DB and not yet reloaded (will make getFieldValueBeforeInsert to fetch the values)
	 * @param Boolean $enableJsCode if true, then JS code is allowed to be sent back to client, defaults to false to use it in batch mode.
	 */
	public function updateFilesOnDiskForField($p, $exec, $record, $field, $storeFileInWigiiBag, $oldRecord=null, $isAfterInsert=false, $enableJsCode=false){
		if($field->getDataType() != null && $field->getDataType()->getDataTypeName()=="Texts"){
			$fieldXml = $field->getXml();
			if($fieldXml["htmlArea"]=="1"){
				if($oldRecord!=null){
					//delete any CLIENT_WEB_PATH."imageForHtmlEditor/*.*" which are not in the new text
					$oldMatch = array();
					$multiLanguageValues = $oldRecord->getFieldValue($field->getFieldName());
					if($multiLanguageValues) {
						foreach($multiLanguageValues as $key=>$val){
							$oldMatch[$key] = array();
							preg_match_all("(".CLIENT_WEB_PATH."imageForHtmlEditor/[^/]*\.[a-zA-Z]*)", $val, $oldMatch[$key]);
						}
					}
					$newMatch = array();
					$multiLanguageValues = $record->getFieldValue($field->getFieldName());
					if($multiLanguageValues) {
						foreach($multiLanguageValues as $key=>$val){
							$newMatch[$key] = array();
							preg_match_all("(".CLIENT_WEB_PATH."imageForHtmlEditor/[^/]*\.[a-zA-Z]*)", $val, $newMatch[$key]);
						}
					}
		
					foreach($oldMatch as $key=>$oldMat){
						if($oldMat && $oldMat[0]){
							foreach($oldMat[0] as $tempPath){
								if(!$newMatch[$key][0] || false===array_search($tempPath, $newMatch[$key][0])){
									@unlink($tempPath);
								}
							}
						}
					}
				}
				return;
			}
		}
		if($field->getDataType() != null && $field->getDataType()->getDataTypeName()=="Blobs"){
			$fieldXml = $field->getXml();
			if($fieldXml["htmlArea"]=="1"){
				if($oldRecord!=null){
					//delete any CLIENT_WEB_PATH."imageForHtmlEditor/*.*" which are not in the new text
					$oldMatch = array();
					preg_match_all("(".CLIENT_WEB_PATH."imageForHtmlEditor/[^/]*\.[a-zA-Z]*)", $oldRecord->getFieldValue($field->getFieldName(), "value"), $oldMatch);
					$newMatch = array();
					preg_match_all("(".CLIENT_WEB_PATH."imageForHtmlEditor/[^/]*\.[a-zA-Z]*)", $record->getFieldValue($field->getFieldName(), "value"), $newMatch);
					if($oldMatch && $oldMatch[0]){
						foreach($oldMatch[0] as $tempPath){
							if(!$newMatch[0] || false===array_search($tempPath, $newMatch[0])){
								@unlink($tempPath);
							}
						}
					}
				}
				return;
			}
		}
		if($field->getDataType() != null && $field->getDataType()->getDataTypeName()=="Files"){
			$fieldXml = $field->getXml();
			$fieldName = $field->getFieldName();
			if($fieldXml["htmlArea"]=="1"){
				if($oldRecord!=null && $oldRecord->getFieldValue($fieldName, "size") && $oldRecord->getFieldValue($fieldName, "date") != $record->getFieldValue($fieldName, "date")){
					if($fieldXml["keepHistory"]>0 && $exec->getCrtParameters($exec->getCrtAction() == 'externalAccess' ? 4 : 2) != 'blockHistory'){
						$this->pushHtmlFileToHistory($p, $exec, (string)$fieldXml["keepHistory"], $oldRecord, $fieldName);
						if($enableJsCode) $exec->addJsCode("actOnHistorizedHtmlFile('$fieldName');");
						return;
					}
		
					//delete any CLIENT_WEB_PATH."imageForHtmlEditor/*.*" which are not in the new text
					$oldMatch = array();
					preg_match_all("(".CLIENT_WEB_PATH."imageForHtmlEditor/[^/]*\.[a-zA-Z]*)", $oldRecord->getFieldValue($field->getFieldName(), "textContent"), $oldMatch);
					$newMatch = array();
					preg_match_all("(".CLIENT_WEB_PATH."imageForHtmlEditor/[^/]*\.[a-zA-Z]*)", $record->getFieldValue($field->getFieldName(), "textContent"), $newMatch);
					if($oldMatch && $oldMatch[0]){
						foreach($oldMatch[0] as $tempPath){
							if(!$newMatch[0] || false===array_search($tempPath, $newMatch[0])){
								@unlink($tempPath);
							}
						}
					}
				}
				return;
			}
		
		
			if($isAfterInsert) $fileName = $record->getFieldValueBeforeInsert($fieldName, "path");
			else $fileName = $record->getFieldValue($fieldName, "path");
			$oldFileName = ($oldRecord != null ? $oldRecord->getFieldValue($fieldName, "path") : null);
			
			// Detects Box files
			$isBoxFile = $fileName && strstr($fileName, "box://");
			$isOldBoxFile = $oldFileName && strstr($oldFileName, "box://");
			
			//if the deleteFile is checked then delete the oldFile + thumbnail
			//this work only if oldRecord is defined
			if($fileName == null && $oldRecord!=null && !$isOldBoxFile){
				if (isImage($oldRecord->getFieldValue($fieldName, "mime"))) @unlink(FILES_PATH."tn_".$oldFileName);
				if($oldFileName && $fieldXml["keepHistory"]>0){
					$this->pushFileToHistory($p, $exec, (string)$fieldXml["keepHistory"], $oldRecord, $fieldName);
				} else {
					@unlink(FILES_PATH.$oldFileName);
				}
			}
			//else if new Element then either move the file in Client folder and create a thumb
			//either delete the file and the old file
			else if($fileName!=null && !$isBoxFile &&
					($oldFileName == null || $oldFileName!= $fileName)){
		
				$filePath = TEMPORARYUPLOADEDFILE_path.$fileName;
		
				if(!$storeFileInWigiiBag){
					//						fput("move  $filePath in ".FILES_PATH.$fileName);
					//move the tempFile into client folder
					if(!rename($filePath, FILES_PATH.$fileName)) throw new ServiceException("Error on storing the temporaryUploadedFile ".$filePath." in the Client folder:".FILES_PATH.$fileName, ServiceException::FORBIDDEN);
		
					//if this is an image then create the thumbnail
					if($isAfterInsert) $mime = $record->getFieldValueBeforeInsert($fieldName, "mime");
					else $mime = $record->getFieldValue($fieldName, "mime");
					if(isImage($mime)){
						$fieldXml = $field->getXml();
						if($fieldXml["displayPreviewOnly"] == "1"){
							//								fput("cutImage ".FILES_PATH.$fileName." as preview");
							cutImage(FILES_PATH.$fileName, FILES_PATH."tn_".$fileName, 150);
						} else {
							//								fput("cutImage ".FILES_PATH.$fileName." as thumbnail");
							cutImage(FILES_PATH.$fileName, FILES_PATH."tn_".$fileName, 80, 80);
						}
					}
				}
		
				//					fput("delete old file $fieldName:".$oldFileName);
				//delete old file if setted
				if($oldFileName != null && !$isOldBoxFile && isImage($oldRecord->getFieldValue($fieldName, "mime"))){
					@unlink(FILES_PATH."tn_".$oldFileName);
				}
				if($oldFileName != null && !$isOldBoxFile){
					if($oldFileName && $fieldXml["keepHistory"]>0){
						$this->pushFileToHistory($p, $exec, (string)$fieldXml["keepHistory"], $oldRecord, $fieldName);
					} else {
						@unlink(FILES_PATH.$oldFileName);
					}
				}
			}
		}
	}
	public function pushHtmlFileToHistory($p, $exec, $keepHistory, $oldRecord, $fieldName){
		$historyName = $this->getHistoryFilename($p, $exec, $oldRecord, $fieldName);
		$dir = $this->cleanHistory($p, $exec, $keepHistory, $oldRecord, $fieldName);
		file_put_contents($dir.$historyName, $this->getTrm()->wrapHtmlFilesWithHeader($oldRecord->getFieldValue($fieldName, "textContent"), $oldRecord->getFieldValue($fieldName, "name").$oldRecord->getFieldValue($fieldName, "type")));
	}
	public function pushFileToHistory($p, $exec, $keepHistory, $oldRecord, $fieldName){
		$historyName = $this->getHistoryFilename($p, $exec, $oldRecord, $fieldName);
		$dir = $this->cleanHistory($p, $exec, $keepHistory, $oldRecord, $fieldName);
		// CWE 01.04.2016: keeps silent if old file does not exist anymore and cannot move it to history folder.
		//if(!rename(FILES_PATH.$oldRecord->getFieldValue($fieldName, "path"), $dir.$historyName)) throw new ServiceException("Error on renaming old file ".$oldRecord->getFieldValue($fieldName, "path")." in ".$dir.$historyName." in folder:".FILES_PATH, ServiceException::FORBIDDEN);		
		@rename(FILES_PATH.$oldRecord->getFieldValue($fieldName, "path"), $dir.$historyName);
	}
	public function cleanHistory($p, $exec, $keepHistory, $oldRecord, $fieldName){
		$dir = $this->getHistoryDir($p, $exec, $oldRecord, $fieldName);
		$files = File::list_files($dir);
		if($files==null) return $dir;
		//-1 because we are in the process of adding one new file just after
		if(count($files)>$keepHistory-1){
			$filesToRemove = array_slice($files, 0, count($files)-$keepHistory+1);
			foreach($filesToRemove as $fileName){
				if (!@unlink($dir.$fileName)) throw new ServiceException("impossible to erase the history file:".$dir.$fileName, ServiceException::FORBIDDEN);
			}
		}
		return $dir;
	}
	public function getHistoryFilename($p, $exec, $oldRecord, $fieldName){
		$historyName = str_replace(array(" ", ":"), array("_", "-"), $oldRecord->getFieldValue($fieldName, "date"))."_".$oldRecord->getFieldValue($fieldName, "username")."_".$oldRecord->getFieldValue($fieldName, "name").$oldRecord->getFieldValue($fieldName, "type");
		return $historyName;
	}
	//define $fieldName to null to get the folder of this element id
	public function getHistoryDir($p, $exec, $oldRecord, $fieldName){
		$historyDir = $oldRecord->getId();
		$historyDir = preg_replace('/[^a-zA-Z0-9\.\-\_]/',"-",$historyDir);
		$historyDir = FILES_PATH.$historyDir;
		if(!is_dir($historyDir)){
			mkdir($historyDir);
		}

		if($fieldName==null) return $historyDir;

		$historyDir .= "/".$fieldName;
		if(!is_dir($historyDir)){
			mkdir($historyDir);
		}
		return $historyDir."/";
	}

	/**
	 * @param $state string: start / check. If the checks pass then the method
	 * actOnCheckedRecord is called and the process is finished
	 */
	protected $fieldSelectorList;
	protected function getFieldSelectorList(){
		if(!isset($this->fieldSelectorList)){
			$this->fieldSelectorList = $this->createInstanceFieldSelectorList();
		}
		return $this->fieldSelectorList;
	}
	protected function createInstanceFieldSelectorList(){
		return FieldSelectorListArrayWebImpl::createInstance();
	}
	/**
	 * @var FieldSelectorList field selector list defined by ElementPolicyEvaluator on element save.
	 */
	protected $fieldSelectorListFromPolicyEvaluator = null;
	
	public function ResolveForm($p, $exec, $state){
		$configS = $this->getWigiiExecutor()->getConfigurationContext(); //ServiceProvider::getConfigService();
		$this->executionSink()->publishStartOperation("ResolveForm", $p);
		try {
			$this->setState($state);
			$rec = $this->getRecord();
			//set the list and the bag
			if($rec->getWigiiBag() == null) $rec->setWigiiBag($this->createWigiiBag());
			if($rec->getFieldList() == null){
				$rec->setFieldList($this->createFieldList());
				//eput($rec->getFieldList()->getListIterator());
				$configS->getFields($p, $rec->getModule(), $rec->getActivity(), $rec->getFieldList());
				//eput($rec->getFieldList()->getListIterator());
			}
			
			//initialize default values only on add (record id = 0) or on activities			
			if($rec->getId()==0 && $rec->getFieldList()->getListIterator()){
				$this->initializeDefaultValues($p, $exec);
			}
			// Resolves any field dynamic attributes
			//CWE 09.03.2016: prevent dynamic parameters on Multiple			
			if(!$this->isMultiple() && $configS->getParameter($p, $rec->getModule(), "Field_enableDynamicAttributes") == "1") {
				$this->resolveFieldDynamicAttributes($p, $exec);
			}
			
			if($this->getState() != "start"){				
				// Checks the form
				$this->CheckForm($p, $exec);

				// evaluates element policy if not multiple and no error
				if(!$this->isMultiple() && !$this->hasError() && $rec instanceof Element) {
					$policyEval = $this->getElementPolicyEvaluator();
					if(isset($policyEval)) {
						$policyEval->setExecutionService($exec);
						$this->fieldSelectorListFromPolicyEvaluator = $policyEval->updateElementStateOnSave($p, $rec);
					}
				}

				$this->doSpecificCheck($p, $exec);

				if(!$this->hasError()){

					$this->actOnCheckedRecord($p, $exec);

					if(!$this->hasError()){
						$this->executionSink()->publishEndOperation("ResolveForm", $p);
						return;
					}
				}
			}
			$this->doRenderForm($p, $exec);
			$this->bindJsServices($p, $exec);
		}
		catch(Exception $e) {
			$this->executionSink()->publishEndOperationOnError("ResolveForm", $e, $p);
			throw new FormExecutorException('',ServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("ResolveForm", $p);
	}

	/**
	 * Initializes the default values in the Wigii bag using the default expressions found in the config
	 */
	public function initializeDefaultValues($p, $exec) {
		$rec = $this->getRecord();
		$fxEval = $this->getFuncExpEval($p, $exec);
		foreach($rec->getFieldList()->getListIterator() as $field) {

			//added by LWR on 10/11/2014 in order to prefill Attributs and MultipleAttributs with prechecked options
			//this code was before in the FormRenderer. It has been moved here in order to be executed on autoSave="1"
			$dataType = $field->getDataType();
			$fieldXml = $field->getXml();
			$fieldName = $field->getFieldName();
			if($dataType  && !$rec->getFieldValue($fieldName)){
				/***************
				 * datatype default values
				 ***************/
				//principal email
				if($dataType->getDataTypeName()=="Emails" && $fieldXml["autoFillWithPrincipalEmail"]){
					$rec->setFieldValue($p->getValueInGeneralContext("email"), $fieldName);
					$isFilled = $p->getValueInGeneralContext("email")!=null;
				}
				//Attributs or MultipleAttributs with checked="1" is prefilled in _displayForm.tpl.php
				if($dataType->getDataTypeName()=="Attributs"){
					//prefill the value if checked
					$checked = $fieldXml->xpath("*[@checked='1']");
					if($checked){
						$rec->setFieldValue((string)$checked[0], $fieldName);
					} else if((string)$fieldXml->attribute[0]!="none"){
						//if no checked, then take the first value if not none
						$rec->setFieldValue((string)$fieldXml->attribute[0], $fieldName);
					}
				}
				if($dataType->getDataTypeName()=="MultipleAttributs"){
					$checked = $fieldXml->xpath("*[@checked='1']");
					$res = array();
					foreach($checked as $val){
						$res[(string)$val] = (string)$val;
					}
					if($res){
						$rec->setFieldValue($res, $fieldName);
					}
				}

				if($dataType->getDataTypeName()=="Booleans"){
					if((string)$fieldXml["checked"]=="1"){
						$rec->setFieldValue(true, $fieldName);
					}
				}

				/***************
				 * defaultExp
				 ***************/
				//CWE 09.03.2016: prevent default values on multiple
				if(!$this->isMultiple()) {
					try {
						$defaultExp = $this->getDefaultExpFromField($field);
						// if we have a default expression
						if(!empty($defaultExp)) {
							// parses it into a funcExp
							try {
								$defaultExp = str2fx($defaultExp);
							}
							catch(StringTokenizerException $ste) {
								// if syntax error, then keeps the defaultExp as is
								// or we have a real syntax error, that then will be corrected by the user
								// or it is not a funcExp but a constant, in that case keeps the value.
								if($ste->getCode() != StringTokenizerException::SYNTAX_ERROR) throw $ste;
							}
							// executes the func exp
							$defaultVal = $fxEval->evaluateFuncExp($defaultExp, $this);
							// sets the default value if not empty
							if(!empty($defaultVal)) $this->processFieldDefaultValue($field, $defaultVal);
						}
					}
					catch(Exception $e) {
						if($this->debugLogger()->isEnabled()) $this->debugLogger()->write("Exception while executing default expression '".$this->getDefaultExpFromField($field)."' attached to field '".$field->getFieldName()."': ".$e);
					}
				}
			}
		}
		if(method_exists($fxEval, 'freeMemory')) $fxEval->freeMemory();
	}
	/**
	 * Returns a default expression string for this field or null if not defined.
	 * @param Field $field
	 */
	protected function getDefaultExpFromField($field) {
		$xml = $field->getXml();
		return (string)$xml["defaultExp"];
	}
	/**
	 * Processes current Field defaultExp evaluation result
	 * If result is not an array, then stores result as field value
	 * else, assumes that the array are matching pairs of (subfieldname => value)
	 * and stores result[subfieldname] as subfield value
	 */
	protected function processFieldDefaultValue($field, $defaultVal)
	{
		$rec = $this->getRecord();
		if(is_array($defaultVal)){
			foreach($defaultVal as $subFieldName=>$value){
				$rec->setFieldValue($value, $field->getFieldName(), $subFieldName);
			}
		} else {
			$rec->setFieldValue($defaultVal, $field->getFieldName());
		}
	}
	
	//factorisation of the piece of code doing the calculation in order to reuse it in children nodes
	protected function calculateValue(&$allowedDynamicAttributes,&$fxEval, &$resolvedValues, $name, $value){
		$value = (string)$value;
		// checks that attribute is allowed to be dynamic
		if(isset($allowedDynamicAttributes[$name])) {
			// checks that value is not already resolved
			if(empty($value) || is_numeric($value)) return;
			// parses the value into a funcExp
			try {
				$value = str2fx($value);
			}
			catch(StringTokenizerException $ste) {
				// if syntax error, then keeps the value as is
				// or we have a real syntax error, that then will be corrected by the user
				// or it is not a funcExp but a constant, in that case keeps the value.
				if($ste->getCode() != StringTokenizerException::SYNTAX_ERROR) throw $ste;
			}
			// executes the func exp
			$value = $fxEval->evaluateFuncExp($value, $this);
			// if value is a log exp, then solves it against the record
			if($value instanceof LogExp) {
				$value = TechnicalServiceProvider::getFieldSelectorLogExpRecordEvaluator()->evaluate($rec, $value);
			}
			// stores the resolved value
			if(is_numeric($value)) $resolvedValues[$name] = $value;
			elseif(is_string($value) && !empty($value)) $resolvedValues[$name] = $value;
			else {
				if($value) $resolvedValues[$name] = "1";
				else $resolvedValues[$name] = "0";
			}
		}
	}
	
	/**
	 * Resolves any func exp or log exp attached to an xml attribute in the field config.
	 * The result of the func exp or log exp is then stored as the value of the xml attribute.
	 * The function is executed only on Fields with enableDynamicAttributes=1 and in a module for which the parameter Field_enableDynamicAttributes=1.
	 * The func exp or log exp are resolved only on xml attributes whose name are defined in the Field_dynamicAttributeList (config.xml). 
	 */
	public function resolveFieldDynamicAttributes($p, $exec) {
		//$this->debugLogger()->logBeginOperation('resolveFieldDynamicAttributes');
		$rec = $this->getRecord();
		$fieldList = $rec->getFieldList();
		// gets fields with dynamic attributes
		if($fieldList instanceof FormFieldList) {
			if($fieldList->hasDynamicAttributeFields()) {
				$dynamicAttributeFields = $fieldList->getDynamicAttributeFields();
			}
			else $dynamicAttributeFields = null;
		}
		else {
			$dynamicAttributeFields = array();
			foreach($fieldList->getListIterator() as $field) {
				$fxml = $field->getXml();
				if(isset($fxml) && $fxml["enableDynamicAttributes"] == "1") $dynamicAttributeFields[$field->getFieldName()] = $field->getFieldName();
			}
		}
		// resolves each dynamic attribute field
		if(!empty($dynamicAttributeFields)) {
			$config = $this->getWigiiExecutor()->getConfigurationContext();
			if(($rec instanceof Element) && $rec->isSubElement()) $currentLs = $config->getCurrentLinkSelector();
			else $currentLs = null;
			
			// gets the list of allowed dynamic attributes
			$allowedDynamicAttributes = (string)$config->getParameter($p, $rec->getModule(), "Field_dynamicAttributeList");
			if(!empty($allowedDynamicAttributes)) {
				$allowedDynamicAttributes = preg_split("/".ValueListArrayMapper::Natural_Separators."/", $allowedDynamicAttributes);
				$allowedDynamicAttributes = array_combine($allowedDynamicAttributes, $allowedDynamicAttributes);
			}
			if(empty($allowedDynamicAttributes)) return;
			
			$fxEval = $this->getFuncExpEval($p, $exec);
			// goes through the list of fields
			foreach($dynamicAttributeFields as $fieldName) {
				$f = $fieldList->getField($fieldName);
				if(!isset($f)) continue;
				$fxml = $f->getXml();
				if(!isset($fxml)) continue;
				
				// goes through the list of attributes
				$resolvedValues = array();
				foreach($fxml->attributes() as $name => $value) {
					$this->calculateValue($allowedDynamicAttributes,$fxEval, $resolvedValues, $name, $value);
				}
				// stores resolved values into the xml node
				if(!empty($resolvedValues)) {
					$formBag = $rec->getWigiiBag();
					if(!($formBag instanceof FormBag)) $formBag = null;
					
					foreach($resolvedValues as $name => $value) {
						$fxml[$name] = $value;
						// updates form bag for some specific attributes
						if(isset($formBag)) {
							if($name == 'disabled') $formBag->setDisabled($value=="1", $fieldName); 
							if($name == 'readonly') $formBag->setReadonly($value=="1", $fieldName);
							if($name == 'hidden') $formBag->setHidden($value=="1", $fieldName);
						}
					}
				}
				//lookup if there is any attribute children in the node to solve specificaly Attributes or MultipleAttributes options
				$keyIndex = 0;
				foreach($fxml->attribute as $key => $node) {
					if($node["enableDynamicAttributes"] == "1"){
						$resolvedValues = array();
						foreach($node->attributes() as $name => $value){
							$this->calculateValue($allowedDynamicAttributes,$fxEval, $resolvedValues, $name, $value);
						}
						// stores resolved values into the xml node
						if(!empty($resolvedValues)) {
							fput($resolvedValues);
							fput($keyIndex);
							foreach($resolvedValues as $name => $value) {
								$fxml->attribute[$keyIndex][$name] = $value;
							}
						}
					}
					$keyIndex ++;
				}
			}
			if(method_exists($fxEval, 'freeMemory')) $fxEval->freeMemory();
			// recenters on subelement if needed
			if(isset($currentLs)) $config->selectSubElementsConfig($p, $currentLs->getOwnerElementId(), $currentLs->getFieldName());
		}
		//$this->debugLogger()->logEndOperation('resolveFieldDynamicAttributes');
	}
	
	/**
	 * Binds all required JS services to the form beeing displayed.
	 * @param Principal $p current principal
	 * @param ExecutionService $exec current request
	 */
	public function bindJsServices($p,$exec) {
		// binds standard Wigii JS services
		$formId = $this->getFormId();		
		$config = $this->getWigiiExecutor()->getConfigurationContext();
		$rec = $this->getRecord();
		if($rec instanceof Element) $module = $rec->getModule();
		else $module = $exec->getCrtModule();
		$userModule = isset($module) && $module->isUserModule();
		
		// adds JS code after show exp		
		if(!$this->isForPrint() && !$this->isForExternalAccess() && !$this->isForNotification() && $userModule) {
			$jsCodeAfterShowExp = (string)$config->getParameter($p, $module, "jsCodeAfterShowExp");
			if(!empty($jsCodeAfterShowExp)) {
				// parses FuncExp
				$jsCodeAfterShowExp = str2fx($jsCodeAfterShowExp);
				// executes the func exp
				if($jsCodeAfterShowExp instanceof FuncExp) {
					$fxEval = $this->getFuncExpEval($p, $exec);
					try {
						$jsCodeAfterShowExp = $fxEval->evaluateFuncExp($jsCodeAfterShowExp,$this);
					}
					catch(Exception $e) {
						if(isset($fxEval)) $fxEval->freeMemory();
						throw $e;
					}
					if(isset($fxEval)) $fxEval->freeMemory();
					
					if($jsCodeAfterShowExp != '') {
						$jsCodeAfterShowExp = str_replace('$$idForm$$',$formId,$jsCodeAfterShowExp);
						$this->debugLogger()->write('jsCodeAfterShowExp = '.$jsCodeAfterShowExp);
						$exec->addJsCode($jsCodeAfterShowExp);
					}
				}
			}
		}
		
		// HelpService on Fields (not in print, not in notification)
		// In externalAccess, allows only local content
		if(!$this->isForPrint() && !$this->isForExternalAccess() && !$this->isForNotification()) $exec->addJsCode("$('#$formId .wigiiHelp').wigii('bindHelpService');");
		else if($this->isForExternalAccess()) $exec->addJsCode("$('#$formId span.wigiiHelp, #$formId div.wigiiHelp.localContent').wigii('bindHelpService');");
	}
	
	/**
	 * @return FuncExpEvaluator returns a configured FuncExpEvaluator ready to be used to executes func exp
	 */
	public function getFuncExpEval($p, $exec,$rec=null) {
		$returnValue = $this->getWigiiExecutor()->getFuncExpEvaluator($p, $exec, (isset($rec)?$rec:$this->getRecord()));
		// injects a reference to the current FormExecutor
		$returnValue->getParentFuncExpEvaluator()->setFormExecutor($this);
		return $returnValue;
	}

	/**
	 * Evaluates a FuncExp in the context of the given Record.
	 * @param Principal $p principal executing the request
	 * @param ExecutionService $exec current ExecutionService instance
	 * @param FuncExp $fx the FuncExp instance to evaluate
	 * @return Any FuncExp result
	 */
	public function evaluateFuncExp($p,$exec,$fx,$rec=null) {
	    $fxEval = $this->getFuncExpEval($p, $exec, $rec);
	    $returnValue = null;
	    try {
	        $returnValue = $fxEval->evaluateFuncExp($fx, $this);
	        $fxEval->freeMemory();
	    }
	    catch(Exception $e) {
	        $fxEval->freeMemory();
	        throw $e;
	    }
	    return $returnValue;
	}
	
	/**
	 * Template Renderer implementation
	 */
	public function f($trm, $fieldName, $width=null){
		return $trm->displayForm($this->getFormId(), $fieldName, $width, $this->getLabelWidth(), $this->getLanguageVisible());
	}
	public function v($trm, $fieldName, $width=null){
		return $trm->displayValue($fieldName, $width, $this->getLanguageVisible());
	}
	public function l($trm, $fieldName, $width=null, $allowSpecialLabel = true){
		return $trm->displayLabel($fieldName, $width, $this->getLanguageVisible(), $allowSpecialLabel);
	}
	public function allD($trm){
		if($trm->getDetailRenderer() == null){
			$trm->setDetailRenderer($this->getDetailRenderer());
		}
		return $trm->displayRemainingDetails();
	}
	public function allF($trm){
		if($trm->getFormRenderer() == null){
			$trm->setFormRenderer($this->getFormRenderer());
		}
		return $trm->displayRemainingForms();
	}
}



