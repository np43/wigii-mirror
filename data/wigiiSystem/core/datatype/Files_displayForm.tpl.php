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
 * Created on 4 déc. 09 by LWR
 * Modified by Medair on 21.07.2016 to integrate Box
 */
if(!isset($transS)) $transS = ServiceProvider::getTranslationService();
if(!isset($exec)) $exec = ServiceProvider::getExecutionService();

$boxServiceFormExecutor = TechnicalServiceProvider::getBoxServiceFormExecutor();

$fieldXml = $field->getXml();

//defining width if existant
if($parentWidth != null){
	$valueWidth = " width: 100%; max-width:".($parentWidth)."px; ";
}

//always activate jsCode
$activeJS = true || !($fieldXml["noActiveJSInPublic"]=="1" && $this->isForExternalAccess());

//defining readOnly or disabled
$readonly = $this->getRecord()->getWigiiBag()->isReadonly($fieldName);
$disabled = $this->getRecord()->getWigiiBag()->isDisabled($fieldName);

$path = $this->formatValueToPreventInjection($this->getRecord()->getFieldValue($fieldName, "path"));
$size = $this->formatValueToPreventInjection($this->getRecord()->getFieldValue($fieldName, "size"));
$type = $this->formatValueToPreventInjection($this->getRecord()->getFieldValue($fieldName, "type"));
$name = $this->formatValueToPreventInjection($this->getRecord()->getFieldValue($fieldName, "name"));
$mime = $this->formatValueToPreventInjection($this->getRecord()->getFieldValue($fieldName, "mime"));
$user = $this->formatValueToPreventInjection($this->getRecord()->getFieldValue($fieldName, "user"));
$username = $this->formatValueToPreventInjection($this->getRecord()->getFieldValue($fieldName, "username"));
$date = $this->formatValueToPreventInjection($this->getRecord()->getFieldValue($fieldName, "date"));
//$textContent = $this->formatValueFromRecord($fieldName, "textContent", $this->getRecord());
$textContent = $this->formatValueToPreventInjection($this->getRecord()->getFieldValue($fieldName, "textContent"));
$fileVersion = $this->getRecord()->getFieldValue($fieldName, "version");

$inputNameId = $formId.'_'.$fieldName.'_name_text';
$inputFileId = $formId.'_'.$fieldName.'_file_file';
$inputPathId = $formId.'_'.$fieldName.'_path_hidden';
$textContentId = $formId.'_'.$fieldName.'_textContent_textarea';
$inputBoxFileId =  $formId.'_'.$fieldName.'_box_hidden';
$boxFolderIdTag = (string)$fieldXml["boxFolderId"];

//display the type preview
$this->put('<div class="filePreview" style="'.(!($path || $textContent)? "display:none;" : "").'float:left; background:url(\''.SITE_ROOT_forFileUrl.'images/preview/prev.26'.$type.'.png\') no-repeat 0px -1px; margin-right:4px;width:26px;height:26px;"></div>');

//the name field
$subFieldName = "name";
$inputName = $fieldName.'_'.$subFieldName;
$this->put('<input id="'.$inputNameId.'" name="'.$inputName.'" ');
if($disabled) $this->put(' disabled ');
if($readonly) $this->put(' disabled class="removeDisableOnSubmit" ');

// resizes field to show Warning icon if upload to box enabled
if($boxFolderIdTag && $boxServiceFormExecutor->getShowWarningIfBoxUpload() && $boxServiceFormExecutor->isBoxEnabled()){
	$this->put(' style="'.(!($path || $textContent) ? "display:none;" : "").'height:18px;float:left;'." width: 100%; max-width:".($parentWidth-5-40-10)."px; ");
}else{
	$this->put(' style="'.(!($path || $textContent) ? "display:none;" : "").'height:18px;float:left;'." width: 100%; max-width:".($parentWidth-5-40)."px; ");
}

if($readonly) $this->put('background-color:#E3E3E3;'); //disabled make color as white in Google Chrome
$this->put('" value="');
$this->put($this->formatValueFromRecord($fieldName, $subFieldName, $this->getRecord()));
$this->put('" />');

// shows Warning icon if upload to box enabled
if($boxFolderIdTag && $boxServiceFormExecutor->getShowWarningIfBoxUpload() && $boxServiceFormExecutor->isBoxEnabled()){
	try{
		$array = $boxServiceFormExecutor->getFolderNameFromFolderId($this->getP(), $boxFolderIdTag);
		$this->put('<img class="box Icon" style="cursor: pointer" title="'.$this->t("boxFileFolder").': '.$array[1].'" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/16x16/status/software-update-urgent.png" onclick="window.open(\''.$boxServiceFormExecutor->getBoxWebInterfaceUrl().'/files/0/f/'.$array[0].'/'.$array[1].'\',\'_blank\');"/>');
	}catch (Exception $e){
		$this->put('<img class="box Icon" title="'.$this->t("boxFileFolder").': '.$array[1].'" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/16x16/status/software-update-urgent.png"/>');
	}
}

//the ssrc is used to download the current file
$ssrc = null;
if(is_a($this->getRecord(), "ActivityRecord")){
	if($this->getRecord()->getAttachedRecord()) $ssrc = $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/download/".$this->getRecord()->getActivity()->getActivityName()."/".$field->getFieldName()."/".$this->getRecord()->getAttachedRecord()->getId();
} else if($this->isForExternalAccess()) {
	if($this->getRecord()->getId()) $ssrc = $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/downloadFromExternalAccess/".$exec->getCrtParameters(0)."/download/".$this->getRecord()->getId()."/".$field->getFieldName();
} else {
	if($this->getRecord()->getId()) $ssrc = $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/download/".$this->getRecord()->getId()."/".$field->getFieldName();
}
if($ssrc) $src = SITE_ROOT.$ssrc;

if($fieldXml["htmlArea"]=="1"){
	//the textContent field
	$subFieldName = "textContent";
	$this->put('<textarea id="'.$textContentId.'" name="'.$fieldName.'_'.$subFieldName.'" ');
	if($disabled) $this->put(' disabled ');
	if($readonly) $this->put(' disabled class="removeDisableOnSubmit" ');
	$this->put(' style="float:left;display:none;width: 100%; max-width:'.($parentWidth).'px;">');
	$this->put($textContent);
	$this->put('</textarea>');
} else {
	//the file field
	$this->put('<input id="'.$inputFileId.'" name="'.$fieldName.'_file" ');
	$this->put(' type="file" ');
	if($disabled) $this->put(' disabled ');
	if($readonly) $this->put(' disabled class="removeDisableOnSubmit" ');
	$this->put(' style="float:left;'.($path && $activeJS ? "display:none;" : "").'width: 100%; max-width:'.($parentWidth).'px;" value="" />');
}

	// Box File picker integration (disabled on Activities, disabled if attachContentInNotification=1, disabled if isForExternalAccess)
	$boxElement = (string)$fieldXml["enableBoxIntegration"]; 
	$boxGeneral = (string)$this->getConfigService()->getParameter($this->getP(), $exec->getCrtModule(), "enableBoxIntegration");
	if(!is_a($this->getRecord(), "ActivityRecord") &&
		($boxElement == "1" || $boxGeneral == "1" && $boxElement !== "0") 
		&& $fieldXml["htmlArea"]!="1" 
		&& $boxServiceFormExecutor->isBoxEnabled()
		&& $fieldXml["attachContentInNotification"]!="1"
		&& !$this->isForExternalAccess()) {

		$this->put('<input id="'.$inputBoxFileId.'" name="'.$fieldName.'_box" class="ui-button-icon-box"');
		$this->put(' type="button" ');
		
		if($disabled) $this->put(' disabled ');
		if($readonly) $this->put(' disabled class="removeDisableOnSubmit" ');
		
		$this->put('style="'.($path && $activeJS ? "display:none;" : "").'width: 100%; max-width:130px;font-size:0.9em;" value="'.$transS->h($p, "boxChooseFile").'"/>');
		$elem = $xml->clientId;
	
		//initializes the Box file picker
		$boxServiceFormExecutor->initializeFilePicker($this->getP(),$exec);			
		$this->getExecutionService()->addJsCode("boxChooseFile('#$inputBoxFileId', '#$inputFileId', '#$inputNameId', '#$inputPathId', '".str_replace("//", "\/\/",SITE_ROOT_forFileUrl)."');");
	}
	
	// if File is linked to Box, tags field with box service
	// if File is linked to Box and boxAllowUpdate=1, tags field with box-upload service
	// if File field is linked to Box folder, tags field with box-upload service
	if(strstr($path, "box://")) {
		if($fieldXml["boxAllowUpdate"]=='1' && $boxServiceFormExecutor->isBoxEnabled()) $exec->addJsCode("domElementAddWigiiService('#".$formId."__".$fieldName."', 'box box-upload')");
		else $exec->addJsCode("domElementAddWigiiService('#".$formId."__".$fieldName."', 'box')");
	}
	if($boxFolderIdTag && $boxServiceFormExecutor->isBoxEnabled()) $exec->addJsCode("domElementAddWigiiService('#".$formId."__".$fieldName."', 'box-upload')");
	
$this->put('<div class="clear" style="margin-top:5px;"></div>');
if($fieldXml["htmlArea"]=="1"){
	//add the new online file button
	if(!$disabled && !$readonly) $this->put('<div class="newOnLineFile" style="'.($textContent ? "display:none;" : "").'background:url(\''.SITE_ROOT_forFileUrl.'images/icones/18px/new.png\') no-repeat 0px 0px; cursor:pointer; float:left; text-decoration:underline;padding-top:2px;padding-left:22px;">'.$transS->t($p, "newOnLineFile").'</div>');
	//download current file
	if($ssrc){
		$this->put('<div class="downloadCurrentFile" onclick="download(\''.$src.'\');" style="'.(!$textContent ? "display:none;" : "").'background:url(\''.SITE_ROOT_forFileUrl.'images/icones/tango/16x16/actions/down.png\') no-repeat 0px 0px;cursor:pointer; float:left; text-decoration:underline;padding-top:2px;padding-left:22px;margin-top:3px;">'.$transS->t($p, "downloadCurrentFile").($fileVersion > 0 ? ' (v.'.$fileVersion.')' : '').'</div>');
		$this->put('<div class="clear"></div>');
	}
	//add the update current on-line file button
	if(!$disabled && !$readonly) {
		$this->put('<div class="updateExistingOnLineFile" style="'.(!$textContent ? "display:none;" : "").'background:url(\''.SITE_ROOT_forFileUrl.'images/icones/18px/new.png\') no-repeat 0px 0px; cursor:pointer; float:left; text-decoration:underline;padding-top:2px;padding-left:22px;">'.$transS->t($p, "updateExistingOnLineFile").'</div>');
		if($fieldXml["require"]!="1"){
			//add the remove on-line file button
			$this->put('<div class="clear"></div>');
			$this->put('<div class="removeExistingOnLineFile" style="'.(!$textContent ? "display:none;" : "").'background:url(\''.SITE_ROOT_forFileUrl.'images/icones/18px/cancel.png\') no-repeat 0px 0px; cursor:pointer; float:left; text-decoration:underline;padding-top:2px;padding-left:22px;">'.$transS->t($p, "removeExistingOnLineFile").'</div>');
		}
	
		switch($transS->getLanguage()){
			case "l02" : $lang = "fr"; break;
			case "l01" :
			default:
				$lang = "en";
		}
		$ckTemplateFile = null;
		$ckTemplateFile = CLIENT_WEB_PATH.$this->getExecutionService()->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."_CKTemplates.js.php";
		if(!file_exists($ckTemplateFile)){
			$ckTemplateFile = CLIENT_WEB_PATH.$this->getExecutionService()->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."_CKTemplates.js";
		}
		if(!file_exists($ckTemplateFile)){
			$ckTemplateFile = CLIENT_WEB_PATH."CKTemplates.js.php";
		}
		if(!file_exists($ckTemplateFile)){
			$ckTemplateFile = CLIENT_WEB_PATH."CKTemplates.js";
		}
		// if autosave does not put a cancel button
		if($this->getConfigService()->getParameter($this->getP(), $exec->getCrtModule(), "autoSave")=="1"){
			$cancel = "false";
		}
		else $cancel = "'".$transS->h($p, "cancel")."'";
		//".$transS->h($p, "newDocument")." is removed since 14 may 2012, it is clearer to the user that there need to type here a name if it is not prefilled
		$this->getExecutionService()->addJsCode("addJsCodeOnOnLineFileInput('#$textContentId', '#$inputNameId', '".$fieldXml["template"]."', ".$cancel.", '".$transS->h($p, "ok")."', '".str_replace("//", "\/\/",SITE_ROOT_forFileUrl)."', '', '$lang', ($(window).height()-210), '$ckTemplateFile');");
	}
} else {
	if($activeJS){
		//download current file
		if($ssrc){
			$this->put('<div class="downloadCurrentFile" onclick="longDownload(\''.$src.'\',\''.$formId.'__'.$fieldName.'\');" style="'.(!$path ? "display:none;" : "").'background:url(\''.SITE_ROOT_forFileUrl.'images/icones/tango/16x16/actions/down.png\') no-repeat 0px 0px;cursor:pointer; float:left; text-decoration:underline;padding-top:2px;padding-left:22px;margin-top:3px;">'.$transS->t($p, "downloadCurrentFile").($fileVersion > 0 ? ' (v.'.$fileVersion.')' : '').'</div>');
			$this->put('<div class="clear"></div>');
		}
		if(!$disabled && !$readonly) {
			//back to filename
			$this->put('<div class="backToFilename" style="display:none;background:url(\''.SITE_ROOT_forFileUrl.'images/icones/tango/16x16/actions/go-previous.png\') no-repeat 0px 0px;cursor:pointer; float:left; text-decoration:underline;padding-top:2px;padding-left:22px;margin-top:3px;">'.$transS->t($p, "backToFilename").'</div>');
			//add the update current file button
			$this->put('<div class="updateCurrentFile" style="'.(!$path ? "display:none;" : "").'background:url(\''.SITE_ROOT_forFileUrl.'images/icones/18px/new.png\') no-repeat 0px 0px; cursor:pointer; float:left; text-decoration:underline;padding-top:2px;padding-left:22px;">'.$transS->t($p, "updateExistingFile").'</div>');
			if($fieldXml["require"]!="1"){
				//add the remove file button
				$this->put('<div class="clear"></div>');
				$this->put('<div class="removeCurrentFile" style="'.(!$path ? "display:none;" : "").'background:url(\''.SITE_ROOT_forFileUrl.'images/icones/18px/cancel.png\') no-repeat 0px 0px; cursor:pointer; float:left; text-decoration:underline;padding-top:2px;padding-left:22px;">'.$transS->t($p, "removeExistingFile").'</div>');
			}
			$this->getExecutionService()->addJsCode("addJsCodeOnFileInput('#$inputFileId', '#$inputNameId', '#$inputPathId', '".$transS->h($p, "clickToBrowseAndFindANewFile")."', '".str_replace("//", "\/\/",SITE_ROOT_forFileUrl)."');");
		}
	}
}

//if keepHistory, display folder content:
if($fieldXml["keepHistory"]>0){
	$dir = $this->getFormRenderer()->getFormExecutor()->getHistoryDir($p, $this->getExecutionService(), $this->getRecord(), $fieldName);
	$files = File::list_files($dir);
	if($files!=null){
		rsort($files);
		$this->put('<div class="clear"></div>');
		$this->put('<span class="grayFont" style="margin-top:font-size:x-small;font-weight:bold;">'.$this->t("previousVersions").":</span><br />");
		foreach($files as $fileName){
			$fileDetails = explode("_", $fileName);
			$histDate = implode(".", array_reverse(explode("-",$fileDetails[0])));
			$histTime = implode(":", array_slice(explode("-", $fileDetails[1]),0,2));
			$histUser = explode("@", $fileDetails[2]);
			$histName = implode("_", array_slice($fileDetails, 3));
			$histSrc = SITE_ROOT.$this->getExecutionService()->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$this->getRecord()->getModule()->getModuleUrl()."/download/".$this->getRecord()->getId()."/".$fieldName."/previousVersion/".$fileDetails[0]."_".$fileDetails[1];
			$this->put('<a href="javascript:download(\''.$histSrc.'\');" target="_self" style="text-decoration:none;" class="previousVersion"><span class="H grayFont" style="font-size:x-small;">'.$histName." ($histDate $histTime, ".$histUser[0].")</span></a><br />");
		}
		if(!$disabled && !$readonly) $exec->addJsCode("setListenerToPreviousVersions('$inputPathId', '".$this->h("areYouSureToDeleteVersion")."');");
	}
}

//HIDDEN FIELDS

//path
$subFieldName = "path";
$inputNode = "input";
$inputType = "hidden";
$inputId = $inputPathId;
$inputName = $fieldName.'_'.$subFieldName;
$this->put('<'.$inputNode.' id="'.$inputId.'" name="'.$inputName.'" ');
if($inputType != null) $this->put(' type="'.$inputType.'" ');
$this->put(' value="');
$this->put($path);
$this->put('" />');

//size
$subFieldName = "size";
$inputNode = "input";
$inputType = "hidden";
$inputId = $formId.'_'.$fieldName.'_'.$subFieldName.'_'.($inputType==null?$inputNode:$inputType);
$inputName = $fieldName.'_'.$subFieldName;
$this->put('<'.$inputNode.' id="'.$inputId.'" name="'.$inputName.'" ');
if($inputType != null) $this->put(' type="'.$inputType.'" ');
$this->put(' value="');
$this->put($size);
$this->put('" />');

//type
$subFieldName = "type";
$inputNode = "input";
$inputType = "hidden";
$inputId = $formId.'_'.$fieldName.'_'.$subFieldName.'_'.($inputType==null?$inputNode:$inputType);
$inputName = $fieldName.'_'.$subFieldName;
$this->put('<'.$inputNode.' id="'.$inputId.'" name="'.$inputName.'" ');
if($inputType != null) $this->put(' type="'.$inputType.'" ');
$this->put(' value="');
$this->put($type);
$this->put('" />');

//mime
$subFieldName = "mime";
$inputNode = "input";
$inputType = "hidden";
$inputId = $formId.'_'.$fieldName.'_'.$subFieldName.'_'.($inputType==null?$inputNode:$inputType);
$inputName = $fieldName.'_'.$subFieldName;
$this->put('<'.$inputNode.' id="'.$inputId.'" name="'.$inputName.'" ');
if($inputType != null) $this->put(' type="'.$inputType.'" ');
$this->put(' value="');
$this->put($mime);
$this->put('" />');

//date
$subFieldName = "date";
$inputNode = "input";
$inputType = "hidden";
$inputId = $formId.'_'.$fieldName.'_'.$subFieldName.'_'.($inputType==null?$inputNode:$inputType);
$inputName = $fieldName.'_'.$subFieldName;
$this->put('<'.$inputNode.' id="'.$inputId.'" name="'.$inputName.'" ');
if($inputType != null) $this->put(' type="'.$inputType.'" ');
$this->put(' value="');
$this->put($date);
$this->put('" />');

//user
$subFieldName = "user";
$inputNode = "input";
$inputType = "hidden";
$inputId = $formId.'_'.$fieldName.'_'.$subFieldName.'_'.($inputType==null?$inputNode:$inputType);
$inputName = $fieldName.'_'.$subFieldName;
$this->put('<'.$inputNode.' id="'.$inputId.'" name="'.$inputName.'" ');
if($inputType != null) $this->put(' type="'.$inputType.'" ');
$this->put(' value="');
$this->put($user);
$this->put('" />');

//username
$subFieldName = "username";
$inputNode = "input";
$inputType = "hidden";
$inputId = $formId.'_'.$fieldName.'_'.$subFieldName.'_'.($inputType==null?$inputNode:$inputType);
$inputName = $fieldName.'_'.$subFieldName;
$this->put('<'.$inputNode.' id="'.$inputId.'" name="'.$inputName.'" ');
if($inputType != null) $this->put(' type="'.$inputType.'" ');
$this->put(' value="');
$this->put($username);
$this->put('" />');






