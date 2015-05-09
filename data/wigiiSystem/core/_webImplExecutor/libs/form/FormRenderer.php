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

/*
 * Created on 3 déc. 09
 * by LWR
 */

class FormRenderer extends FieldRenderer implements FieldListVisitor {

	private $formExecutor;
	protected function setFormExecutor($formExecutor){ $this->formExecutor = $formExecutor; }
	public function getFormExecutor(){ return $this->formExecutor; }

	private $templateRecordManager;
	protected function setTemplateRecordManager($templateRecordManager){ $this->templateRecordManager = $templateRecordManager; }
	protected function getTemplateRecordManager(){ return $this->templateRecordManager; }

	private $languageVisible;
	public function getVisibleLanguage() { return $this->visibleLanguage; }
	public function setVisibleLanguage($visibleLanguage) { $this->visibleLanguage = $visibleLanguage; }

	private $isMultiple;
	public function setIsMultiple($enable){ $this->isMultiple = $enable; }
	public function isMultiple(){ return $this->isMultiple; }

	private $formId;
	public function getFormId() { return $this->formId; }
	protected function setFormId($id){ $this->formId = $id; }

	private $p;
	protected function getP() {
		if(!isset($this->p)){
			$this->p = ServiceProvider::getAuthenticationService()->getMainPrincipal();
		}
		return $this->p;
	}
	public function setP($p){ $this->p = $p; return $this; }

	public static function createInstance($formExecutor, $formId, $templateRecordManager, $totalWidth=null, $labelWidth = null, $visibleLanguage=null){
		$r = new self();
		$r->setFormExecutor($formExecutor);
		$r->setFormId($formId);
		$r->setTemplateRecordManager($templateRecordManager);
		$r->initializeFieldWidth($totalWidth, $labelWidth);
		$r->setVisibleLanguage($visibleLanguage);
		$r->isFirst();
		return $r;
	}

	private $jsCodeAfterShow;
	public function resetJsCodeAfterShow(){
		$this->jsCodeAfterShow = null;
	}
	public function addJsCodeAfterShow($code){
		$this->jsCodeAfterShow .= $code;
	}
	public function getJsCodeAfterShow(){
		return $this->jsCodeAfterShow;
	}

	//this variable will be updated to manage different areas
	private $crtEditGroup = null; //will contain the name of the current EditGroup of field if defined.
	private $editGroupTab = array(); //will contain the li's prepared for the tabs, the li's are displayed on closing EditGroup
	private $firstTabInEditGroupWithContent = false; //will contains the index of the first tab with content in the editGroup

	private $first = null;
	protected function isFirst(){
		$this->first = true;
	}

	public function actOnField($field, $dataType){
		$transS = ServiceProvider::getTranslationService();
		$exec = ServiceProvider::getExecutionService();
		$p = $this->getP();
		$isPublicPrincipal = ServiceProvider::getAuthorizationService()->isPublicPrincipal($p);

		$fieldXml = $field->getXml();
		$fieldName = $field->getFieldName();
		$rm = $this->getTemplateRecordManager();

		//if field is hidden, or onlyInDetail, or not in Public and principal is public -> skip it
		if(($isPublicPrincipal && $fieldXml["notInPublic"]=="1")) return;
		if((!$isPublicPrincipal && $fieldXml["onlyInPublic"]=="1")) return;
		if($rm->getRecord()->getWigiiBag()->isHidden($fieldName) || $fieldXml["onlyInDetail"]=="1") return;

		//before any field display if the form contains error
		if($this->first && $rm->getRecord()->getWigiiBag()->hasErrors()){
			$rm->put('<div class="fieldError" style="border-top-style:none;border-bottom-style:dotted;font-weight:bold;padding-bottom:10px;margin-bottom:5px;width:'.($this->getTotalWidth()).'px;">');
			if($rm->isForExternalAccess()){
				$rm->put($transS->t($p, "errorSummary").":<br />");
				foreach($rm->getRecord()->getWigiiBag()->getErrors() as $tempFieldName=>$tempError){
					if($tempFieldName=="captcha_code"){
						$rm->put($transS->t($p, "captchaInvalidCode")."<br />");
					} else {
						$tempField = $rm->getRecord()->getFieldList()->getField($tempFieldName);
						$rm->put($transS->t($p, $tempFieldName, $tempField->getXml()).": ".$tempError."<br />");
					}
				}
			} else {
				$rm->put('<br />'.$transS->t($p, "formHasErrors").' <img style="float:none;" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/22x22/emblems/emblem-unreadable.png" class="icon" />');
			}
			$rm->put('</div><div class="clear"></div>');
			$this->first = false;
		}

		// skips links attributes when external access or multiple.
		if($dataType instanceof Links && ($rm->isForExternalAccess() || $this->isMultiple())) return;

		//define base attributes of field such:
		//	- $idField
		//	- $error
		//	- $help
		//	- $fieldClass //if a custom class has been defined for this field
		//	- $isRequire
		//	- $isFilled
		//	- $isCollapse
		$idField = $this->getFormId()."__".$fieldName;
		$fieldClass = (string)$fieldXml["class"];
		$error = $rm->getRecord()->getWigiiBag()->getError($fieldName);
		$help = str_replace('"', '&quot;', $transS->t($p, $rm->getRecord()->getWigiiBag()->getHelp($fieldName)));
		$isRequire = (
			$fieldXml["require"]=="1" && !$isPublicPrincipal ||
			$isPublicPrincipal && $fieldXml["requireInPublic"]=="1" ||
			$isPublicPrincipal && $fieldXml["require"]=="1" && $fieldXml["requireInPublic"]!="0"
		);

		if($fieldXml["readonlyInPublic"]=="1" && $isPublicPrincipal){
			$rm->getRecord()->getWigiiBag()->setReadonly(true, $fieldName);
		}
		$isFilled = null;
		$isCollapse = null;
		//prefill default values
//		if($_POST["action"]==null && $rm->getRecord()->getId()==0 && $dataType!=null){
//			//principal email
//			if($dataType->getDataTypeName()=="Emails" && $fieldXml["autoFillWithPrincipalEmail"] && !$rm->getRecord()->getFieldValue($fieldName)){
//				$rm->getRecord()->setFieldValue($this->getP()->getValueInGeneralContext("email"), $fieldName);
//				$isFilled = $this->getP()->getValueInGeneralContext("email")!=null;
//			}
//			//Attributs or MultipleAttributs with checked="1" is prefilled in _displayForm.tpl.php
//			if($dataType->getDataTypeName()=="Attributs"){
//				$isFilled = $fieldXml->xpath("*[@checked='1']") || !$fieldXml->xpath("attribute[text()='none']");
//			}
//			if($dataType->getDataTypeName()=="MultipleAttributs"){
//				$isFilled = $fieldXml->xpath("*[@checked='1']");
//			}
//		}
		if($dataType!=null){
			$isFilled = $rm->getRecord()->getWigiiBag()->isFilled($fieldName);
			$isCollapse =
				$dataType->getDataTypeName()!="Files" &&
				$dataType->getDataTypeName()!="Links" &&
				$dataType->getDataTypeName()!="Booleans" &&
				!$isFilled &&
				$fieldXml["isJournal"]!="1" &&
				$fieldXml["displayOnRightSide"]!="1" &&
				$fieldXml["expand"]!="1" &&
				(!$isRequire || $fieldXml["expand"]=="0");
		}

		//fieldGroup management
		if($fieldXml["groupStart"]=="1"){
			$this->enterFieldGroup($rm, $fieldXml, $fieldName, $idField);
			if(!empty($error)){
				$rm->put('<div class="fieldError" style="width:'.($this->getIsInLineWidth()).'px;">'.$error.'</div>');
			}
			return;
		} else if($fieldXml["groupEnd"]=="1"){
			//we need to take the getCrtFieldGroup as it might be different to the current fieldName. Current fieldName is numeroted as fields must be unique
			$crtFieldGroupId = $this->getFormId()."__".$this->getCrtFieldGroup();
			//add fieldGroup expand/colapse feature
			$rm->addJsCode("" .
				"$('#".$crtFieldGroupId.">.label').css('cursor','pointer').click(function(){" .
					"if($('#".$crtFieldGroupId."_group:visible').length){" .
						"$('#".$crtFieldGroupId."_group').hide();" .
						"$('#".$crtFieldGroupId.">.label').append('<span class=\"expand\"> &nbsp; ".$rm->h("cickToShowGroupContent")."</span>');" .
					"} else {" .
						"$('#".$crtFieldGroupId.">.label>span.expand').remove();" .
						"$('#".$crtFieldGroupId."_group').show();" .
					"}" .
				"});");
			//collapse a fieldGroup only if there is no data and nothing is required
			if(((!$this->isCrtFieldGroupFilled() && $fieldXml["expand"]!="1") || $fieldXml["forceCollapse"]=="1")
			   && !$this->isCrtFieldGroupHasError()){
				$rm->addJsCode("" .
					"if($('#".$crtFieldGroupId.">.label').length){" .
						"$('#".$crtFieldGroupId."_group').hide();" .
						"$('#".$crtFieldGroupId.">.label').append('<span class=\"expand\"> &nbsp; ".$rm->h("cickToShowGroupContent")."</span>');" .
					"}" .
					"");
			}
			$this->leaveCrtFieldGroup($rm, $fieldXml);
			return;
		} else if($fieldXml["totalWidth"]!="" || $fieldXml["labelWidth"]!="" || ($fieldXml["useMultipleColumn"]!="" && $fieldXml["useRadioButtons"]!="1" && $fieldXml["useCheckboxes"]!="1")) {
			$this->updateWidthOnEnterField($fieldName, $fieldXml);
		}

		//field management

		//a group is filled if there is error inside or if there is mandatory fields
		if(	($dataType && $dataType->getDataTypeName()!="Booleans" && $isFilled) ||
			($dataType && $dataType->getDataTypeName()=="Booleans" && $rm->getRecord()->getFieldValue($field->getFieldName(), "value")) ||
			$rm->getRecord()->getWigiiBag()->hasError($fieldName) ||
			$isRequire
			){
			$this->setCrtFieldGroupIsFilled();
			if($rm->getRecord()->getWigiiBag()->hasError($fieldName)){
				$this->setCrtFieldGroupHasError();
			}
		}

		//open field div
		if(!empty($help)){
			$help = ' title="'.$help.'" ';
		}
		$style = "width:".$this->getTotalWidth()."px;";
		if($fieldXml["noMargin"]=="1"){
			$style .= "margin-right:0px;";
		}
		$rm->put('<div id="'.$idField.'" class="field '.$fieldClass.'" style="'.$style.'" '.$help.' >');
		if($field->getDataType()!=null){
			$additionalInformations = $rm->getAdditionalinInformation($fieldName);
			if($additionalInformations) $rm->put('<div class="addinfo ui-corner-all SBIB">'.$additionalInformations.'</div>');
		}
		if(!empty($error)){
			$rm->put('<div class="fieldError" style="width:'.($this->getIsInLineWidth()).'px;">'.$error.'</div>');
		}

		//display label
		$countSubFields = 0;
		if($dataType!=null && $fieldXml["noLabel"]!="1"){
			//display label on full width if field dispay more than one subfield
			$countSubFields = count($dataType->getXml()->xpath("*[@type!='hidden']")); //count only none hidden sub fields
			if(	($dataType->getDataTypeName()=="Urls" && $fieldXml["onlyUrl"] =="1") ||
				($dataType->getDataTypeName()=="TimeRanges" && $fieldXml["onlyDate"] =="1")
				){
				$countSubFields = 1;
			}

			//20 is the label padding
			$noPadding = "";
			if(!$isCollapse && ($countSubFields > 1 || $fieldXml["isInLine"] =="1")){
				$labelWidth = $this->getIsInLineWidth();
				$noPadding = "padding-right:0px;"; //don't need the right padding if is inline
			} else {
				$labelWidth = $this->getLabelWidth()-20;
			}
			$style = "width:".$labelWidth."px;$noPadding";
			$rm->put('<div class="label" style="'.$style.'" >');
			//if multiple mode then add multiple check box
			if($this->isMultiple()){
				$tempClass = "checkField";
				if($rm->getRecord()->getWigiiBag()->isDisabled($fieldName)){
					$tempClass .= " disabled ";
					$disabled = ' disabled="on" ';
				} else $disabled = null;
				$checked = $rm->getRecord()->getWigiiBag()->isMultipleChecked($fieldName);
				if($checked) $checked = ' checked="on" ';
				else $checked = null;
				$multipleHelp = ' onmouseover="showHelp(this, \''.$rm->h("multipleCheckboxCheck").'\');" onmouseout="hideHelp();" ';
				$rm->put('<span class="checkField M" style="padding:5px 0px 1px 0px;"><input id="'.$this->getFormId().'_'.$fieldName.'_check" type="checkbox" name="'.$fieldName.'_check" class="'.$tempClass.'" '.$checked.' '.$disabled.' '.$multipleHelp.' /></span> ');
				if(	($dataType->getDataTypeName() == "Emails" && $fieldXml["isMultiple"]=="1") ||
					($dataType->getDataTypeName() == "Addresses") ||
					($dataType->getDataTypeName() == "Blobs") ||
					($dataType->getDataTypeName() == "Floats") ||
					($dataType->getDataTypeName() == "Links") ||
					($dataType->getDataTypeName() == "Numerics") ||
					($dataType->getDataTypeName() == "Texts") ||
					$dataType->getDataTypeName() == "MultipleAttributs"){
					$checked = $rm->getRecord()->getWigiiBag()->isMultipleAddOnlyChecked($fieldName);
					//by default check this checkbox
					if($_POST["action"]==null) $checked = true;
					if($checked) $checked = ' checked="on" ';
					else $checked = null;
					$multipleHelp = ' onmouseover="showHelp(this, \''.$rm->h("multipleCheckboxCheckAddOnly").'\');" onmouseout="hideHelp();" ';
					$rm->put('<input id="'.$this->getFormId().'_'.$fieldName.'_checkAddOnly" type="checkbox" name="'.$fieldName.'_checkAddOnly" class="'.$tempClass.'" '.$checked.' '.$disabled.' '.$multipleHelp.' /> ');
				}
			}
			//add red cross on error
			if(!empty($error)){
				$rm->put('<img class="icon" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/22x22/emblems/emblem-unreadable.png" /> ');
			}
			//add * on require
			if($isRequire){
				$rm->put('* ');
			}
//			$rm->put('<label>');
			if($dataType->getDataTypeName() == "Files" && $fieldXml["displayPreviewOnly"]=="1"){
				//dispaly picture in form
				$rm->displayLabel($fieldName, $labelWidth, $this->getVisibleLanguage(), true);
			} else {
				$rm->displayLabel($fieldName, $labelWidth, $this->getVisibleLanguage(), false);
			}
//			$rm->put('</label>');
			//add a "+add" button
			if($dataType->getDataTypeName() == "Blobs" && $fieldXml["isJournal"]=="1"){
				$textAreaId = $this->getFormId()."_".$fieldName."_value_textarea";
				//readonly is added in Blobs_displayForm on the textarea field.
				if($fieldXml["protectExistingEntries"]=="1"){
					$rm->addJsCode("setListenerToAddJournalItem('".$exec->getIdAnswer()."', '', '$fieldName', '".$this->getFormId()."__".$fieldName."', '".$transS->getLanguage()."', ".($fieldXml["htmlArea"]==1 ? "true" : "false" ).", '".$rm->getNewJournalItemString($p, $fieldXml["htmlArea"]==1, true)."', '".$rm->t("ok")."', '".$rm->t("cancel")."', '', '".$rm->getNewJournalContentStringCode()."');");
					$rm->put('&nbsp;&nbsp;&nbsp; (<span class="H addJournalItem">'.$rm->t("addJournalItem").'</span>)');
				} else {
					$rm->addJsCode("setListenerToAddJournalItem('".$exec->getIdAnswer()."', '', '$fieldName', '".$this->getFormId()."__".$fieldName."', '".$transS->getLanguage()."', ".($fieldXml["htmlArea"]==1 ? "true" : "false" ).", '".$rm->getNewJournalItemString($p, $fieldXml["htmlArea"]==1, true)."', '".$rm->t("ok")."', '".$rm->t("cancel")."', '', '".$rm->getNewJournalContentStringCode()."');");
					if($fieldXml["htmlArea"]=="1"){ //if htmlArea then make the editor editable
						$rm->put('&nbsp;&nbsp;&nbsp; (<span onclick="CKEDITOR.instances[\''.$textAreaId.'\'].setReadOnly(false);" class="H addJournalItem">'.$rm->t("addJournalItem").'</span>)');
					} else { //if none htmlArea enable the textarea after
						$rm->put('&nbsp;&nbsp;&nbsp; (<span onclick="$(\'#'.$textAreaId.'\').removeAttr(\'disabled\').css(\'background-color\', \'#fff\');" class="H addJournalItem">'.$rm->t("addJournalItem").'</span>)');
					}
				}
			}
			$rm->put('</div>');
			if($isCollapse){
				$rm->put('<div class="addC d" style="');
				$rm->put('width:'.($this->getValueWidth()).'px;');
				$rm->put('"><span>+</span> <u>');
				$rm->put($rm->t("cickToAdd".$dataType->getDataTypeName()."Content"));
				$rm->put('</u></div>');
			}
		}

		//display value
		if($dataType==null || ($countSubFields > 1 || $fieldXml["isInLine"] =="1" || $fieldXml["noLabel"] =="1")){
			$valueWidth = $this->getIsInLineWidth();
		} else {
			$valueWidth = $this->getValueWidth();
		}
		$style = "width:".$valueWidth."px;";
		if($dataType != null){
			$class = "value";
		} else {
			//for freetext, use the class value only if isLabel!=1
			if($fieldXml["displayAsLabel"]=="1"){
				$class = "label";
			} else {
				$class = "value";
			}
		}
		$rm->put('<div class="'.$class.'" style="'.$style.'" >');
		$rm->displayForm($this->getFormId(), $fieldName, $valueWidth, $this->getLabelWidth(), $this->getVisibleLanguage());
		$rm->put('</div>');

		//add any JsCode if defined:
		if((string)$fieldXml["jsCode"]!=null){
			$this->addJsCodeAfterShow(str_replace('$$idForm$$', $this->getFormId(), (string)$fieldXml["jsCode"]));
		}
		if((string)$fieldXml["jsCodeInForm"]!=null){
			$this->addJsCodeAfterShow(str_replace('$$idForm$$', $this->getFormId(), (string)$fieldXml["jsCodeInForm"]));
		}

		//close the field div
		$rm->put('</div>');

		if($fieldXml["totalWidth"]!="" || $fieldXml["labelWidth"]!="" || ($fieldXml["useMultipleColumn"]!="" && $fieldXml["useRadioButtons"]!="1" && $fieldXml["useCheckboxes"]!="1")) {
			$this->updateWidthOnLeaveField($fieldName, $fieldXml);
		}
	}

	public function finish(){
		//nothing special to do
	}

}

