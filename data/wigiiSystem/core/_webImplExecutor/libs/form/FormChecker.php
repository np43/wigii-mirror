<?php
/**
 *  This file is part of Wigii (R) software.
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
 * Checks a posted Form and fills the Record WigiiBag.
 * Created on 1 sept. 09 by LWR
 * Modified by Medair on 21.07.2016 to integrate Box
 */
class FormChecker implements FieldListVisitor {

	// Dependency injection
	
	private $formExecutor;
	public function getFormExecutor(){ return $this->formExecutor; }
	public function setFormExecutor($formExecutor){ $this->formExecutor = $formExecutor; }

	private $p;
	protected function getP() {
		if(!isset($this->p)){
			$this->p = ServiceProvider::getAuthenticationService()->getMainPrincipal();
		}
		return $this->p;
	}
	public function setP($p){ $this->p = $p; return $this; }

	private $boxServiceFormExecutor;
	/**
	 * Injects a BoxServiceFormExecutor to use to communicate with Box and store uploaded files
	 * @param BoxServiceFormExecutor $boxServiceFormExecutor
	 */
	public function setBoxServiceFormExecutor($boxServiceFormExecutor) {
		$this->boxServiceFormExecutor = $boxServiceFormExecutor;
	}
	protected function getBoxServiceFormExecutor() {
		// lazy loading
		if(!isset($this->boxServiceFormExecutor)) {
			$this->boxServiceFormExecutor = TechnicalServiceProvider::getBoxServiceFormExecutor();
		}
		return $this->boxServiceFormExecutor;
	}
	
	
	// Object lifecycle
	
	public static function createInstance($formExecutor){
		$fc = new self();
		$fc->setFormExecutor($formExecutor);
		return $fc;
	}

	//return true if this field has been marked as changed by autoSave function
	//($_POST[changedAutoSaveField[fieldName]]==="true")
	public function isFieldChangedWithAutoSave($fieldName){
		$get =& $_POST;
		return $get['changedAutoSaveField'][$fieldName]==="true";
	}

	private $fieldGroupNbFilled = array();
	private $crtFieldGroupName = "root";
	private $fieldGroupStack = array();
	private $fieldGroupHasAtLeastFilled = array();
	protected function incrementNbFilledCrtFieldGroup($fieldName){
		$this->fieldGroupNbFilled[$this->getCrtFieldGroup()][$fieldName]=true;
	}
	protected function getCrtFieldGroupNbFilled(){
		if(!$this->fieldGroupNbFilled[$this->getCrtFieldGroup()]) return 0;
		return count($this->fieldGroupNbFilled[$this->getCrtFieldGroup()]);
	}
	protected function enterFieldGroup($fieldGroupName, $fieldGroupHasAtLeastFilled){
		if($fieldGroupName=="root") throw new FieldRendererException("cannot define a field group with 'root'. 'root' is reserved.", FieldRendererException::INVALID_ARGUMENT);
		$this->crtFieldGroupName = $fieldGroupName;
		$this->fieldGroupHasAtLeastFilled[$fieldGroupName]=$fieldGroupHasAtLeastFilled;
		array_push($this->fieldGroupStack, $fieldGroupName);
	}
	protected function leaveCrtFieldGroup(){
		array_pop($this->fieldGroupStack);
		if($this->fieldGroupStack) $this->crtFieldGroupName = end($this->fieldGroupStack);
		else $this->crtFieldGroupName = "root";
	}
	protected function getCrtFieldGroup(){
		return $this->crtFieldGroupName;
	}
	protected function getCrtFieldGroupHasAtLeastFilled(){
		return $this->fieldGroupHasAtLeastFilled[$this->getCrtFieldGroup()];
	}

	
	// FieldListVisitor implementation
	
	
	/**
	 * Acts on the current specified field linked to the generic datatype
	 * the action consist to control the field value, and fill the wigii bag with the value.
	 * the wigii bag is filled even if the value is wrong. except if reformating of the value
	 * is done in the check methods
	 */
	public function actOnField($field, $dataType){
		$ff = $this->getFormExecutor();
		$transS = ServiceProvider::getTranslationService();
		$exec = ServiceProvider::getExecutionService();
		$p = $this->getP();
		$isPublicPrincipal = ServiceProvider::getAuthorizationService()->isPublicPrincipal($p);

		if($ff->getRecord() == null) throw new FormCheckerException("try to check a form with no attached element.", FormCheckerException::NO_ATTACHED_RECORD);

		$fieldParams = $field->getXml();
		$fieldName = $field->getFieldName();

		/*
		 * FieldGroup management
		 */
		if($fieldParams["groupStart"]=="1"){
			$this->enterFieldGroup($fieldName, (int)$fieldParams["groupHasAtLeastFilled"]);
			return;
		} else if($fieldParams["groupEnd"]=="1"){
//			eput("Nb:".$this->getCrtFieldGroupNbFilled()."\n");
//			eput("Min:".$this->getCrtFieldGroupHasAtLeastFilled()."\n");
			if(!$ff->isMultiple() && $this->getCrtFieldGroupNbFilled()<$this->getCrtFieldGroupHasAtLeastFilled()){
				$ff->addErrorToField($this->getCrtFieldGroupHasAtLeastFilled().$transS->h($p, "crtGroupHasNotEnoughFilledFields"), $this->getCrtFieldGroup());
			}
			$this->leaveCrtFieldGroup();
			return;
		}

		//if the field is not a dataType, then we do no check.
		if($dataType == null) return;
		//if field is hidden, or disabled, or onlyInDetail, or not in Public and principal is public -> skip it
		if($ff->getRecord()->getWigiiBag()->isHidden($fieldName)) return;
		if($ff->getRecord()->getWigiiBag()->isDisabled($fieldName)){
			$subFieldName = "value";
			switch($dataType->getDataTypeName()){
				case "Addresses" : $subFieldName = "city"; break;
				case "Files" : $subFieldName = "size"; break;
				case "TimeRanges" : $subFieldName = "begDate"; break;
				case "Urls" : $subFieldName = "url"; break;
			}
			if($ff->getRecord()->getFieldValue($fieldName,$subFieldName)!=null) $this->incrementNbFilledCrtFieldGroup($fieldName);
			return;
		}
		if($fieldParams["onlyInDetail"]=="1") return;
		if($fieldParams["displayViewOnly"]=="1") return;
		if(($isPublicPrincipal && $fieldParams["notInPublic"]=="1")) return;
		if((!$isPublicPrincipal && $fieldParams["onlyInPublic"]=="1")) return;

		//$get will contain the content of the posted values
		$get =& $_POST;

		$dataTypeName = $dataType->getDataTypeName();
		$recBag = $ff->getRecord()->getWigiiBag();
		$isRequire = ($fieldParams["require"]=="1" && !$isPublicPrincipal ||
					$isPublicPrincipal && $fieldParams["requireInPublic"]=="1" ||
					$isPublicPrincipal && $fieldParams["require"]=="1" && $fieldParams["requireInPublic"]!="0"					
					);
		// resolves requireIfField attribute
		if(!$isRequire && $get["autoSaveFieldId"]==null && $fieldParams["requireIfField"]!="") {
			$isRequire = $this->resolveRequireIfField($p, $exec, (string)$fieldParams["requireIfField"]);
		}
		if($fieldParams["readonlyInPublic"]=="1" && $isPublicPrincipal){
			$recBag->setReadonly(true, $fieldName);
		}
		$fieldIsReadonly = $recBag->isReadonly($fieldName);

		//check if multiple that the field is checked
		if($ff->isMultiple()){
			$recBag->setMultipleChecked($get[$fieldName."_check"], $fieldName);
			$recBag->setMultipleAddOnlyChecked($get[$fieldName."_checkAddOnly"], $fieldName);
			//only treat checked field
			if(!$recBag->isMultipleChecked($fieldName)) return;
		}

		//si c'est un fichier, alors on vérifie qu'il y aie bien un path (indique qu'un fichier
		//est uploader). Dans le cas contraire on efface le nom du document, car il n'y a
		//pas de sens d'avoir un nom sans fichier... ATTENTION en multipleEdit il ne devrait jamais y avoir la possibilité de changer qqch à un Files, car ça n'a pas de sens
		//we need those checks here, because some of the hidden subfields of a file are require. --> it is important
		//to empty them before checking if the file has content based only on values in require subfields.
		$oldBoxId=null;$maxFileSize=0;
		if($dataTypeName == "Files"){
			if((!($fieldParams["htmlArea"]=="1" || $fieldParams["allowAudioRecording"]=="1") && $get[$fieldName."_path"]==null) || ($fieldParams["htmlArea"]=="1" && $get[$fieldName."_textContent"]==null) || ($fieldParams["allowAudioRecording"]=="1" && $get[$fieldName."_path"]==null && $get[$fieldName."_audioContent"]==null)){
				$get[$fieldName."_name"] = null;
				$get[$fieldName."_size"] = null;
				$get[$fieldName."_type"] = null;
				$get[$fieldName."_mime"] = null;
				$get[$fieldName."_content"] = null;
				$get[$fieldName."_textContent"] = null;
				$get[$fieldName."_thumbnail"] = null;
				$get[$fieldName."_date"] = null;
				$get[$fieldName."_user"] = null;
				$get[$fieldName."_username"] = null;
				$recBag->setValue(null, $ff->getRecord()->getId(), $dataTypeName, $fieldName, "name");
				$recBag->setValue(null, $ff->getRecord()->getId(), $dataTypeName, $fieldName, "size");
				$recBag->setValue(null, $ff->getRecord()->getId(), $dataTypeName, $fieldName, "type");
				$recBag->setValue(null, $ff->getRecord()->getId(), $dataTypeName, $fieldName, "mime");
				$recBag->setValue(null, $ff->getRecord()->getId(), $dataTypeName, $fieldName, "content");
				$recBag->setValue(null, $ff->getRecord()->getId(), $dataTypeName, $fieldName, "textContent");
				$recBag->setValue(null, $ff->getRecord()->getId(), $dataTypeName, $fieldName, "thumbnail");
				$recBag->setValue(null, $ff->getRecord()->getId(), $dataTypeName, $fieldName, "date");
				$recBag->setValue(null, $ff->getRecord()->getId(), $dataTypeName, $fieldName, "user");
				$recBag->setValue(null, $ff->getRecord()->getId(), $dataTypeName, $fieldName, "username");

				if((!$ff->isMultiple() || ($ff->isMultiple() && $recBag->isMultipleChecked($fieldName))) && $isRequire){
					$ff->addErrorToField($transS->h($p, "mustToUploadAtLeastOneFile"), $fieldName);
				}
			} else
				//manage if audio recording content is available
				if($fieldParams["allowAudioRecording"]=="1" && $get[$fieldName."_audioContent"]!=null) {
					//fput("audio recording found");
					$filename = date("d.m.Y H:i");
					$fileext = ".ogg";
					$uploadfile = $p->getWigiiNamespace()->getWigiiNamespaceName()."_".time().ipToStr($_SERVER["REMOTE_ADDR"]).$p->getUsername().$fieldName.substr(basename($filename), 0, 5);
					$uploadfile = preg_replace('/[^a-zA-Z0-9\.\-\_]/',"",$uploadfile).$fileext;
					$filepath = $uploadfile;
					$uploadfile = $this->getFormExecutor()->getTemporaryUploadedFilePath().$uploadfile;
					$uploadfile = str_replace("\\", "/", $uploadfile);
					$filesize = file_put_contents($uploadfile, $get[$fieldName."_audioContent"]);
					if($filesize){
						$get[$fieldName."_type"] = strtolower($fileext);
						$get[$fieldName."_size"] = $filesize;
						$get[$fieldName."_path"] = $filepath;
						$get[$fieldName."_name"] = $filename.$fileext;
						
					} else {
						//fput("problem!!");
						$this->executionSink()->log("!!!! problem in creating audio file ".$filename." in ".$uploadfile." !!!!\n");
					}
				}
			// Medair (CWE) 2017.03.14: expects file details, but checks that posted file date is newer than element file date
			// an inversion could happen if autosave is active, because autosave posting is concurrent to click on OK button.
			else {
				$postingDate = $get[$fieldName."_date"];
				if($postingDate) $postingDate = strtotime($postingDate);
				else $postingDate = 0;
				$fileDate = $recBag->getValue($ff->getRecord()->getId(), $dataTypeName, $fieldName, "date");
				if($fileDate) $fileDate = strtotime($fileDate);
				else $fileDate = 0;
				// clears obsolete posted data
				if($postingDate < $fileDate) {
					unset($get[$fieldName."_size"]);
					unset($get[$fieldName."_type"]);
					unset($get[$fieldName."_mime"]);
					unset($get[$fieldName."_date"]);
					unset($get[$fieldName."_user"]);
					unset($get[$fieldName."_username"]);
					
				}
				
			}
			//if no Active JS on file field, then prefill the name with the originalFilename
			//this is defined in FormExecutor when treating the upload process
			if($fieldParams["noActiveJSInPublic"]=="1" && !$get[$fieldName."_name"]){
				$get[$fieldName."_name"] = $get[$fieldName."_originalFilename"];
			}
			// records old Box ID if present
			$oldBoxId = $recBag->getValue($ff->getRecord()->getId(), $dataTypeName, $fieldName, "path");
			$oldBoxId = (strpos($oldBoxId,"box://")===0?str_replace("box://", '', $oldBoxId):null);
			// Medair (CWE) 19.10.2018: checks if the file size is limited
			$maxFileSize = (int)$fieldParams["maxFileSize"];			
		}

		//we need to go on each subfield then
		$fieldHasContent = false;
		$fieldIsChanged = false;
		$fileContentHasChanged = false; //change only if path or textContent has changed


		foreach ($dataType->getXml()->children() as $subFieldName=>$subFieldParams){
			//ignore system subfields:
			if(in_array($subFieldName, array("sys_user", "sys_username", "sys_date", "sys_creationUser", "sys_creationUsername", "sys_creationDate"))){
				continue;
			}

			$name = $fieldName."_".$subFieldName;

			//exception pour un champ de type Addresses, les valeurs
			//state ou country ne sont pas obligatoire si il n'y a
			//pas la config noState=0 ou noCountry=0
			if ($dataTypeName=="Addresses"){
				//si noState = 0, alors on passe les checks, car ce champ ne doit pas être rempli
				if($subFieldName=="state" && ($fieldParams["noState"] === null || $fieldParams["noState"] != "0")){
					unset($get[$name]);
					$ff->getRecord()->setFieldValue(null, $fieldName, $subFieldName);
					continue;
				}
				//si noCountrye = 0, alors on passe les checks, car ce champ ne doit pas être rempli
				if($subFieldName=="country" && ($fieldParams["noCountry"] === null || $fieldParams["noCountry"] != "0")){
					unset($get[$name]);
					$ff->getRecord()->setFieldValue(null, $fieldName, $subFieldName);
					continue;
				}
			}

			//special formating for Urls
			if($dataTypeName =="Urls" && $subFieldName == "url"){
				$value = $get[$name];
				$get[$name] = str_replace("\\\\", "/", $value);
			}

			//special formatting for checkboxes, as html is not posting if a check box or select option is not filled
			if($subFieldParams["type"]=="checkbox"){
				$get[$name] = $get[$name]!=null;
			}
			if(($dataTypeName=="Attributs" || $dataTypeName=="MultipleAttributs") && !isset($get[$name]) && $subFieldName=="value"){
				$get[$name] = ""; //=null dosen't set the variable... = "" does set
			}

			/**
			 * Fill the value and mark has changed if new value != oldValue (done in FormBag->setValue)
			 */
			try{
				//setValue only if it has been posted
				$isChanged = null;
				if(isset($get[$name])){
					$isChanged = $recBag->setValue($get[$name], $ff->getRecord()->getId(), $dataTypeName, $fieldName, $subFieldName, $subFieldParams);
				}

				$fieldIsChanged = $fieldIsChanged || $isChanged;

				if($isChanged && $dataTypeName =="Files" && (
					($fieldParams["htmlArea"]!="1" && $subFieldName == "path") ||
					($fieldParams["htmlArea"]=="1" && $subFieldName == "textContent")
					)){
					$fileContentHasChanged = true;
				}

			} catch (Exception $e){
				$errorText = $transS->h($p, $e->getMessage());
				if($subFieldName != "value") $errorText .= ": ".$transS->h($p, $dataTypeName."_".$subFieldName, $subFieldParams);
				$ff->addErrorToField($errorText, $fieldName);
			}

			/**
			 * Check filled require subField
			 * WARNING: multilanguage field is considered as filled if there is at least one language filled
			 * WARNING: Booleans are always filled, but the require needs that the value is true if required.
			 * WARNING: if the file is empty then there is already an error with the Please upload on file. --> no need to add the check on the other subfields which are mandatory but hidden
			 */
			if($isRequire && (($dataTypeName == "Booleans" && !$recBag->getValue(null, $dataTypeName, $fieldName)) || ($dataTypeName!="Files" && $recBag->subFieldIsRequiredAndNotFilled($dataTypeName, $fieldName, $subFieldParams)))){
				$errorText = $transS->h($p, "compulsory_field");
				//ignore dependencies of requireIfField when autosaving
				if($get["autoSaveFieldId"]==null && $fieldParams["requireIfField"]!="" && $ff->getRecord()->getFieldList()->doesFieldExist((string)$fieldParams["requireIfField"])){
					$errorText .= " ".$transS->h($p, "compulsory_whenField");
					$tempIfField = $ff->getRecord()->getFieldList()->getField((string)$fieldParams["requireIfField"]);
					$errorText .= " ".$transS->h($p, $tempIfField->getFieldName(), $tempIfField->getXml());
					$errorText .= " ".$transS->h($p, "compulsory_isFilled");
				}
				if($subFieldName != "value") $errorText .= ": ".$transS->h($p, $dataTypeName."_".$subFieldName, $subFieldParams);
				$ff->addErrorToField($errorText, $fieldName);
			}

			//add filename is mandatory on filled files type
			if($dataTypeName=="Files" && $subFieldName=="name" && !$recBag->getValue($ff->getRecord()->getId(), $dataTypeName, $fieldName, "name") && (
					$get[$fieldName."_path"] ||
					$get[$fieldName."_textContent"]
					)){
				$errorText = $transS->h($p, "compulsory_field_filename");
				$ff->addErrorToField($errorText, $fieldName);
			}

			// Medair (CWE) 19.10.2018: checks if the file size is limited
			if($dataTypeName=="Files" && $subFieldName=="size" && $maxFileSize>0) {
			    $size = $recBag->getValue($ff->getRecord()->getId(), $dataTypeName, $fieldName, "size");
			    if($size > $maxFileSize) {
			        $ff->addErrorToField($transS->t($p, "uploadedFileSize").formatFileSize($size).$transS->t($p, "totalAttachementFilesizeExceed").formatFileSize($maxFileSize), $fieldName);
			    }
			}
			
			/**
			 * Check filled subField
			 */
			if(!$fieldHasContent){
				$fieldHasContent = $recBag->subFieldIsRequiredAndFilled($dataTypeName, $fieldName, $subFieldParams);
			}
		}

		//Fill specific information on Files type if the content has changed
		if($fileContentHasChanged && $dataTypeName=="Files" && $fieldParams["htmlArea"]=="1" && $get[$fieldName."_textContent"]!=null && $fieldIsChanged && $fieldHasContent){
			$recBag->setValue(strlen($get[$fieldName."_textContent"]), $ff->getRecord()->getId(), $dataTypeName, $fieldName, "size");
			$recBag->setValue(".html", $ff->getRecord()->getId(), $dataTypeName, $fieldName, "type");
		}
		if ($fileContentHasChanged && $dataTypeName=="Files" && $fieldIsChanged && $fieldHasContent){
			//size, type and path are already filled in the formExecutor checkForm
			$mime = typeMime($recBag->getValue($ff->getRecord()->getId(), $dataTypeName, $fieldName, "type"));
			$recBag->setValue($mime, $ff->getRecord()->getId(), $dataTypeName, $fieldName, "mime");
			$recBag->setValue(date("Y-m-d H:i:s", time()), $ff->getRecord()->getId(), $dataTypeName, $fieldName, "date");
			$recBag->setValue($p->getRealUserId(), $ff->getRecord()->getId(), $dataTypeName, $fieldName, "user");
			$recBag->setValue($p->getRealUsername(), $ff->getRecord()->getId(), $dataTypeName, $fieldName, "username");

			//dans le cas où il y a un champ lié pour le type
			//on mets à jour la valeur...
			$type = null;
			if(strstr($mime, "audio/") !== false) $type = "audio";
			elseif(strpos($mime, "image/") !== false) $type = "image";
			elseif(strpos($mime, "video/") !== false) $type = "video";
			elseif(strpos($mime, "text/") !== false ||
				false!==array_search($mime, array("application/msword"))) $type = "text";
			elseif(false!==array_search($mime, array("application/pdf"))) $type = "pdf";
			elseif(false!==array_search($mime, array("application/vnd.ms-powerpoint"))) $type = "presentation";
			elseif(false!==array_search($mime, array("application/excel"))) $type = "table";
			elseif(false!==array_search($mime, array(
				"application/x-bzip",
				"application/x-gzip",
				"application/x-tar",
				"application/zip"
				))) $type = "ZIP";
			else $type = "other";
			$typeField = (string)$ff->getWigiiExecutor()->getConfigurationContext()->mf($p, $ff->getRecord()->getModule())->{$fieldName}['typeField'];
			if($typeField != null){
				$get[$typeField."_value"] = $type;
				$recBag->setValue($type, $ff->getRecord()->getId(), $dataTypeName, $typeField, "value");
				$recBag->setFilled(true, $typeField);
				$recBag->setChanged($typeField);
			}
			//eput($typeField." ".$type);
		}

		if ($dataTypeName=="Files" && $fieldIsChanged && $fieldHasContent && 
				($versionPattern = (string)$fieldParams['versionPattern']) && 
				($fileName = stripslashes($recBag->getValue($ff->getRecord()->getId(), $dataTypeName, $fieldName, "name")))) {
			// extract version number from filename using the pattern
			$versionRegExp = str_replace(array('.', '[', ']', '(', ')', ' ', '#'), array('\\.', '\\[', '\\]', '\\(', '\\)', '\\s*', '(\\d*)'), $versionPattern);
			$versionRegExp = '/(.*)\s*'.$versionRegExp.'/';
			$fileNameParts = array();
			if(preg_match($versionRegExp, $fileName, $fileNameParts)) {
				$fileName = $fileNameParts[1];
				$uploadedVersion = (int)$fileNameParts[2];
			}
			else {
				$uploadedVersion = 0;
			}
			$currentVersion = (int)$recBag->getValue($ff->getRecord()->getId(), $dataTypeName, $fieldName, 'version');
			// compares uploaded version to current version
			// if uploaded version < current version or if forceNewVersionOnUpload and uploaded version = current version then generate an error.
			if($uploadedVersion > 0 && ($uploadedVersion < $currentVersion || $fieldParams['forceNewVersionOnUpload'] == '1' && $uploadedVersion == $currentVersion)) {
				$ff->addErrorToField($transS->h($p, "cannotUploadOldVersion").$currentVersion, $fieldName);
			}
			else {
				// increments version and sets it
				if($fileContentHasChanged) {
					$currentVersion = max($uploadedVersion, $currentVersion+1);
					$recBag->setValue($currentVersion, $ff->getRecord()->getId(), $dataTypeName, $fieldName, "version");
				}
				elseif($uploadedVersion > $currentVersion) {
					$ff->addErrorToField($transS->h($p, "cannotChangeVersionWithoutUploading"), $fieldName);
				}
				// updates the file name with new version
				$fileName = trim($fileName);
				if(!empty($fileName)) {
					$recBag->setValue($fileName.str_replace('#', $currentVersion, $versionPattern), $ff->getRecord()->getId(), $dataTypeName, $fieldName, "name");
				}
				else {
					$errorText = $transS->h($p, "compulsory_field_filename");
					$ff->addErrorToField($errorText, $fieldName);
				}
			}
		}

		// Box file upload in box folder if the path is not like "box://..."	
		if ($dataTypeName=="Files" && $fieldHasContent && (!strstr($ff->getRecord()->getFieldValue($fieldName, "path"), "box://")) &&
			(((string)$fieldParams['boxFolderId']) || $fieldParams['boxAllowUpdate']=='1') && $this->getBoxServiceFormExecutor()->isBoxEnabled()){			

			try {
				$boxFolderId = (string)$fieldParams['boxFolderId'];
				// case boxFolderId and not boxAllowUpdate
				if($boxFolderId && $fieldParams['boxAllowUpdate']!='1') {
					$this->getBoxServiceFormExecutor()->boxUploadFileForField($p, wigiiBPLParam("element",$ff->getRecord(),"fieldName",$fieldName, "mimeType", $mime, "boxFolderId", $boxFolderId));
				}
				// case boxFolderId and boxAllowUpdate
				elseif($boxFolderId && $fieldParams['boxAllowUpdate']=='1') {
					// if already box then updates version
					if($oldBoxId) {
						$this->getBoxServiceFormExecutor()->boxUploadFileVersionForField($p, wigiiBPLParam("element",$ff->getRecord(),"fieldName",$fieldName, "mimeType", $mime, "boxFolderId", $boxFolderId, "fileId", $oldBoxId));
					}
					// else uploads
					else {
						$this->getBoxServiceFormExecutor()->boxUploadFileForField($p, wigiiBPLParam("element",$ff->getRecord(),"fieldName",$fieldName, "mimeType", $mime, "boxFolderId", $boxFolderId));
					}
				}
				// case not boxFolderId and boxAllowUpdate
				elseif(!$boxFolderId && $fieldParams['boxAllowUpdate']=='1') {
						// if already box then updates version
					if($oldBoxId) {
						$this->getBoxServiceFormExecutor()->boxUploadFileVersionForField($p, wigiiBPLParam("element",$ff->getRecord(),"fieldName",$fieldName, "mimeType", $mime, "boxFolderId", $boxFolderId, "fileId", $oldBoxId));
					}
				}				
			}
			catch(Exception $be) {
				$ff->addErrorToField($be->getMessage(), $fieldName);
			}
		}
		

		
		//Fill specific information on Emails type:
		if($dataTypeName=="Emails"){
			//if there is new content calculate the codes
			if($fieldHasContent && ($fieldIsChanged || $recBag->getValue($ff->getRecord()->getId(), $dataTypeName, $fieldName, "externalCode")== null) ){
				$value = $recBag->getValue($ff->getRecord()->getId(), $dataTypeName, $fieldName, "value");
				$newProofKey = ServiceProvider::getElementService()->getEmailValidationCode($p, $value);
				$newExternalCode = ServiceProvider::getElementService()->getEmailExternalCode($p, $ff->getRecord()->getId(), $fieldName, $value);
				$recBag->setValue($newProofKey, $ff->getRecord()->getId(), $dataTypeName, $fieldName, "proofKey");
				$recBag->setValue(0, $ff->getRecord()->getId(), $dataTypeName, $fieldName, "proofStatus");
				$recBag->setValue(null, $ff->getRecord()->getId(), $dataTypeName, $fieldName, "proof");
				//the config group is filled elsewhere
				$recBag->setValue(null, $ff->getRecord()->getId(), $dataTypeName, $fieldName, "externalConfigGroup");
				$recBag->setValue(0, $ff->getRecord()->getId(), $dataTypeName, $fieldName, "externalAccessLevel");
				$recBag->setValue(null, $ff->getRecord()->getId(), $dataTypeName, $fieldName, "externalAccessEndDate");
				$recBag->setValue($newExternalCode, $ff->getRecord()->getId(), $dataTypeName, $fieldName, "externalCode");
			} else if (!$fieldHasContent){
				$recBag->setValue(null, $ff->getRecord()->getId(), $dataTypeName, $fieldName, "proofKey");
				//keep possible delete status
				//$recBag->setValue(null, $ff->getRecord()->getId(), $dataTypeName, $fieldName, "proofStatus");
				//$recBag->setValue(null, $ff->getRecord()->getId(), $dataTypeName, $fieldName, "proof");
				$recBag->setValue(null, $ff->getRecord()->getId(), $dataTypeName, $fieldName, "externalConfigGroup");
				$recBag->setValue(null, $ff->getRecord()->getId(), $dataTypeName, $fieldName, "externalAccessLevel");
				$recBag->setValue(null, $ff->getRecord()->getId(), $dataTypeName, $fieldName, "externalAccessEndDate");
				$recBag->setValue(null, $ff->getRecord()->getId(), $dataTypeName, $fieldName, "externalCode");
			} else {
				//no change
			}
		}

        //if autoSave change fields is posted, update the fieldIsChanged accordingly
		if($this->isFieldChangedWithAutoSave($fieldName)){
			$recBag->setChanged($fieldName);
		}

		/**
		 * Add in the wigiiBag the information the field is filled or not:
		 */
		$recBag->setFilled($fieldHasContent, $fieldName);
		if($fieldHasContent) $this->incrementNbFilledCrtFieldGroup($fieldName);

		//appelle d'un contrôle spécifique du type de donnée
		try{
			$dataType->checkValues($p, $ff->getRecord()->getId(), $ff->getRecord()->getWigiiBag(), $field);
		} catch (Exception $e){
			$ff->addErrorToField($transS->h($p, $e->getMessage()), $fieldName);
		}

        //Check if the minDate or maxDate is respected in the field date
        if($dataTypeName=="Dates" && ($fieldParams['minDate'] || $fieldParams['maxDate'])){
            $d=$m=$y=null;
            $value = $recBag->getValue($ff->getRecord()->getId(), $dataTypeName, $fieldName, "value");
            $minDate = $fieldParams['minDate'];
            $maxDate = $fieldParams['maxDate'];

            if(!empty($minDate)){
                $minDateEval = $ff->getWigiiExecutor()->evaluateConfigParameter($p, $exec, $minDate);
                if(preg_match('^[0-9]{1,2}\.[0-12]{2}\.\d{4}^', $minDateEval)){
                    $date = explode('.', $minDateEval);
                    Dates::fromString($date, $d, $m, $y);

                    $minDate = $d . '.' . $m . '.' . $y;
                } else{
                    $minDate = $ff->getRecord()->getFieldValue($minDateEval);
                }
            }
            if(!empty($maxDate)){
                $maxDateEval = $ff->getWigiiExecutor()->evaluateConfigParameter($p, $exec, $maxDate);
                if(preg_match('^[0-9]{1,2}\.[0-12]{2}\.\d{4}^', $maxDateEval)){
                    $date = explode('.', $maxDateEval);
                    Dates::fromString($maxDateEval, $d, $m, $y);

                    $maxDate = $d . '.' . $m . '.' . $y;
                } else{
                    $maxDate = $ff->getRecord()->getFieldValue($maxDateEval);
                }
            }
                if(($minDate) && ($value < $minDate)){
                    if($value) {
                        $errorText = $transS->h($p, "endDate_error", $subFieldParams);
                        $ff->addErrorToField($errorText, $fieldName);
                    }
                }
                if(($maxDate) && ($value > $maxDate)){
                    if($value){
                        $errorText = $transS->h($p, "startDate_error", $subFieldParams);
                        $ff->addErrorToField($errorText, $fieldName);
                    }
                }
        }
	}
	
	/**
	 * Resolves requireIfField expression and returns true if required else false.
	 * @param Principal $p current principal
	 * @param ExecutionService $exec current ExecutionService instance
	 * @param String $requireIfFieldExp a field name which should be filled or a String FuncExp which should evaluate to a boolean.
	 * @return boolean true if field is required else false. 
	 */
	protected function resolveRequireIfField($p, $exec, $requireIfFieldExp) {
		if($requireIfFieldExp == '') return false;		
		// parses the value into a funcExp
		try {
			$requireIfFieldExp = str2fx($requireIfFieldExp);
		}
		catch(StringTokenizerException $ste) {
			// if syntax error, then assumes it is a fieldName, and therefore creates a FieldSelector
			if($ste->getCode() != StringTokenizerException::SYNTAX_ERROR) throw $ste;
			$requireIfFieldExp = fs($requireIfFieldExp);
		}		
		// executes the func exp
		if($requireIfFieldExp instanceof FuncExp) {
			$fxEval = $this->getFormExecutor()->getFuncExpEval($p, $exec);
			$requireIfFieldExp = $fxEval->evaluateFuncExp($requireIfFieldExp, $this);
			if(method_exists($fxEval, 'freeMemory')) $fxEval->freeMemory();
		}
		// if result is a FieldSelector then checks that Field is filled
		if($requireIfFieldExp instanceof FieldSelector) {
			$returnValue = $this->getFormExecutor()->getRecord()->getWigiiBag()->isFilled($requireIfFieldExp->getFieldName());
		} 		
		// if result is a log exp, then solves it against the record
		elseif($requireIfFieldExp instanceof LogExp) {
			$returnValue = TechnicalServiceProvider::getFieldSelectorLogExpRecordEvaluator()->evaluate($this->getFormExecutor()->getRecord(), $requireIfFieldExp);
		}
		// converts result to boolean
		else $returnValue = ($requireIfFieldExp == true);

		return $returnValue;
	}
}




