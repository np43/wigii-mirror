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

class DetailRenderer extends FieldRenderer implements FieldListVisitor {

	private $templateRecordManager;
	protected function setTemplateRecordManager($templateRecordManager){ $this->templateRecordManager = $templateRecordManager; }
	protected function getTemplateRecordManager(){ return $this->templateRecordManager; }

	private $languageVisible;
	public function getVisibleLanguage() { return $this->visibleLanguage; }
	public function setVisibleLanguage($visibleLanguage) { $this->visibleLanguage = $visibleLanguage; }

	private $detailId;
	public function getDetailId() { return $this->detailId; }
	protected function setDetailId($id){ $this->detailId = $id; }

	private $recordIsWritable;
	protected function getRecordIsWritable(){ return $this->recordIsWritable; }
	/**
	 * this says to the detailRender that the Record is writable.
	 */
	public function setRecordIsWritable($isWritable){ $this->recordIsWritable = $isWritable; }

	private $p;
	protected function getP() {
		if(!isset($this->p)){
			$this->p = ServiceProvider::getAuthenticationService()->getMainPrincipal();
		}
		return $this->p;
	}
	public function setP($p){ $this->p = $p; return $this; }

	public static function createInstance($detailId, $templateRecordManager, $totalWidth=null, $labelWidth = null, $visibleLanguage=null){
		$r = new DetailRenderer();
		$r->setTemplateRecordManager($templateRecordManager);
		$r->initializeFieldWidth($totalWidth, $labelWidth);
		$r->setVisibleLanguage($visibleLanguage);
		$r->setDetailId($detailId);
		return $r;
	}
	public function reset($detailId, $templateRecordManager, $totalWidth=null, $labelWidth = null, $visibleLanguage=null){
		parent::reset();
		$this->setTemplateRecordManager($templateRecordManager);
		if($totalWidth && $labelWidth) $this->initializeFieldWidth($totalWidth, $labelWidth);
		if($visibleLanguage) $this->setVisibleLanguage($visibleLanguage);
		if($detailId) $this->setDetailId($detailId);
		$this->resetJsCodeAfterShow();
		return $this;
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

	public function actOnField($field, $dataType){
		$fieldXml = $field->getXml();
		$fieldName = $field->getFieldName();
		$rm = $this->getTemplateRecordManager();
		$p = $this->getP();
		if($p!=null){
			$isPublicPrincipal = ServiceProvider::getAuthorizationService()->isPublicPrincipal($p);
		} else {
			$isPublicPrincipal = false;
		}

		//if field is hidden, or onlyInForm, or onlyInWrite with a none writable record, or not in Public and principal is public -> skip it
		if(($isPublicPrincipal && $fieldXml["notInPublic"]=="1")) return;
		if((!$isPublicPrincipal && $fieldXml["onlyInPublic"]=="1")) return;
		if($rm->getRecord()->getWigiiBag()->isHidden($fieldName) || $fieldXml["onlyInForm"]=="1") return;
		if($fieldXml["onlyInWrite"]=="1" && $this->getRecordIsWritable()===false) return;

		//if no value then do not display, except:
		//if proof status is Deleted -> show the email field
		if($dataType!= null && $dataType->getDataTypeName()=="Emails" && $rm->getRecord()->getFieldValue($field->getFieldName(), "proofStatus")==Emails::PROOF_STATUS_DELETED){
			//display the delete sign with unsubscribe info
		//do an exception for Blobs with isJournal to make the +add button available
		} else if($dataType!= null && $dataType->getDataTypeName()=="Blobs" && $fieldXml["isJournal"]=="1"){
			//display the label
		//always display Links if record is writable
		} else if($dataType!= null && $dataType->getDataTypeName()=="Links"){
			//display the label
		//always display field if displayEvenIfEmpty
		} else if($fieldXml["displayEvenIfEmpty"]=="1"){
			//display the label
		//prevent empty booleans to be displayed
		} else if($dataType!= null && $dataType->getDataTypeName()=="Booleans" && $rm->getRecord()->getFieldValue($field->getFieldName(), "value")==false){
			return;
		} else if($field->getDataType()!= null && !$rm->getRecord()->getWigiiBag()->isFilled($field->getFieldName())){
			return;
		}

		$idField = $this->getDetailId()."__".$fieldName;
		$fieldClass = (string)$fieldXml["class"];
		if($fieldXml["allowOnReadOnly"]=="1") $fieldClass .=" allowOnReadOnly ";
		if($rm->getRecord() instanceof Element && $rm->getRecord()->isState_blocked()) $fieldClass .= " readOnly ";
		$isTitle = ($fieldXml["displayAsTitle1"]=="1" || $fieldXml["displayAsTitle2"]=="1" || $fieldXml["displayAsTitle3"]=="1");
		if($isTitle && $field->getDataType()!= null){
			$dtName = $field->getDataType()->getDataTypeName();
			if($dtName == "Files" || $dtName == "Urls" || $dtName == "Addresses" || $dtName == "Blobs" || $dtName == "Texts"){
				$isTitle = false;
			}
		}

		//fieldGroup management
		if($fieldXml["groupStart"]=="1"){
			$this->enterFieldGroup($rm, $fieldXml, $fieldName, $idField);
			return;
		} else if($fieldXml["groupEnd"]=="1"){
			//we need to take the getCrtFieldGroup as it might be different to the current fieldName. Current fieldName is numeroted as fields must be unique
			$crtFieldGroupId = $this->getDetailId()."__".$this->getCrtFieldGroup();
			//check if data in group
			if($this->isCrtFieldGroupFilled()){
				$rm->addJsCode("" .
						"$('#".$crtFieldGroupId.">.label').css('cursor','pointer').click(function(){ " .
						"if($('#".$crtFieldGroupId."_group:visible').length){ " .
							"$('#".$crtFieldGroupId."_group').hide();" .
							"$('#".$crtFieldGroupId.">.label').append('<span class=\"expand\"> &nbsp; ".$rm->h("cickToShowGroupContent")."</span>');" .
						"} else { " .
							"$('#".$crtFieldGroupId.">.label>span.expand').remove();" .
							"$('#".$crtFieldGroupId."_group').show();" .
						"}" .
					"});");
				if($fieldXml["expand"]=="0" || $fieldXml["forceCollapse"]=="1"){
					$rm->addJsCode("" .
						"$('#".$crtFieldGroupId."_group').hide();" .
						"$('#".$crtFieldGroupId.">.label').append('<span class=\"expand\"> &nbsp; ".$rm->h("cickToShowGroupContent")."</span>');" .
						"");
				}
			} else {
				$rm->addJsCode("$('#".$crtFieldGroupId."').hide(); ");
			}
			$this->leaveCrtFieldGroup($rm, $fieldXml);
			return;
		} else if($fieldXml["totalWidth"]!="" || $fieldXml["labelWidth"]!="" || ($fieldXml["useMultipleColumn"]!="" && $fieldXml["useRadioButtons"]!="1" && $fieldXml["useCheckboxes"]!="1")) {
			$this->updateWidthOnEnterField($fieldName, $fieldXml);
		}

		/**
		 * Exception for Integrated html files with displayContentInDetail
		 */
		if($dataType && $dataType->getDataTypeName()=="Files" && $fieldXml["htmlArea"]=="1" && $fieldXml["displayContentInDetail"]=="1"){
			$rm->displayValue($fieldName, $this->getIsInLineWidth(), $this->getVisibleLanguage());
			if($fieldXml["totalWidth"]!="" || $fieldXml["labelWidth"]!="" || ($fieldXml["useMultipleColumn"]!="" && $fieldXml["useRadioButtons"]!="1" && $fieldXml["useCheckboxes"]!="1")) {
				$this->updateWidthOnLeaveField($fieldName, $fieldXml);
			}
			return;
		}

		//field management

		//if we render this area that means fieldGroup is filled
		$this->setCrtFieldGroupIsFilled();

		//open field div
		$style = "width:".$this->getTotalWidth()."px;";
		if($fieldXml["noMargin"]=="1"){
			$style .= "margin-right:0px;";
		}
		$rm->put('<div id="'.$idField.'" class="field '.$fieldClass.'" style="'.$style.'" >');
		if($field->getDataType()!=null){
			$additionalInformations = $rm->getAdditionalinInformation($fieldName);
			if($additionalInformations) $rm->put('<div class="addinfo ui-corner-all SBIB">'.$additionalInformations.'</div>');
		}

		//display label
		if($dataType!=null && $fieldXml["noLabel"]!="1"){
			//20 is the label padding
			$noPadding = "";
			if($dataType->getDataTypeName()=="Files"){
				if($fieldXml["displayPreviewOnly"]=="1"){
					$labelWidth = $this->getIsInLineWidth()-20;
				} else {
					$labelWidth = min($this->getLabelWidth()-20, 100-20);
				}
			} else if($dataType->getDataTypeName()=="Urls" && $fieldXml["bigLabel"]=="1"){
				$labelWidth = min($this->getLabelWidth()-20, 100-20);
			} else if(($isTitle || $fieldXml["isInLine"] =="1") && $fieldXml["displayAsTag"]!="1"){
				$labelWidth = $this->getIsInLineWidth();
				$noPadding = "padding-right:0px;"; //don't need the right padding if is inline
			} else {
				$labelWidth = $this->getLabelWidth()-20;
			}
			$style = "width:".$labelWidth."px;$noPadding";
			if($dataType->getDataTypeName()=="Files"){
				$style .= "text-align:center;";
			}
			$rm->put('<div class="label" style="'.$style.'" >');
			$rm->displayLabel($fieldName, $labelWidth, $this->getVisibleLanguage());
			$rm->put('</div>');
		}

		//display value
		if($dataType && ($dataType->getDataTypeName()=="Files")){
			if($fieldXml["displayPreviewOnly"]=="1"){
				$valueWidth = $this->getIsInLineWidth();
			} else {
				$valueWidth = $this->getIsInLineWidth()-($labelWidth+20);
			}
		} else if($dataType && ($dataType->getDataTypeName()=="Urls" && $fieldXml["bigLabel"]=="1")){
			$valueWidth = $this->getIsInLineWidth()-($labelWidth+20);
		} else if((($isTitle || $fieldXml["isInLine"] =="1") && $fieldXml["displayAsTag"]!="1") || $dataType==null || $fieldXml["noLabel"] =="1"){
			$valueWidth = $this->getIsInLineWidth();
		} else {
			$valueWidth = $this->getValueWidth();
		}
		$style = "width:".$valueWidth."px;";
		$class = "";
		if($dataType!=null){
			if($dataType->getDataTypeName() == "Blobs" ||
				$dataType->getDataTypeName() == "Texts"){
				$class .= " text ";
			}
			if($dataType->getDataTypeName() == "Files"){
				$class .= " file ";
			}
			$class = "value ".$class;
		} else {
			//for freetext, use the class value only if isLabel!=1
			if($fieldXml["displayAsLabel"]=="1"){
				$class = "label";
			} else {
				$class = "value";
			}
		}
		//for print we don't limit the width to prevent hidding some information
		if($rm->isForPrint() && $dataType!= null && ($dataType->getDataTypeName()=="Blobs" || ($dataType->getDataTypeName()=="Files" && $fieldXml["htmlArea"] && $fieldXml["displayContentInDetail"]))){
			$style = preg_replace('/width:(.*)px/', "", $style);
			$valueWidth =null;
		}
		$rm->put('<div class="'.$class.'" style="'.$style.'" >');
		if($dataType!= null && $dataType->getDataTypeName()!="Links" &&
			!$rm->getRecord()->getWigiiBag()->isFilled($field->getFieldName()) &&
			!($dataType->getDataTypeName()=="Emails" && $rm->getRecord()->getFieldValue($field->getFieldName(), "proofStatus")==Emails::PROOF_STATUS_DELETED)
			){
			$rm->displayEvenIfEmpty($fieldName, $valueWidth, $this->getVisibleLanguage());
		} else {
			$rm->displayValue($fieldName, $valueWidth, $this->getVisibleLanguage());
		}
		$rm->put('</div>');

		// for Links datatype: if element is not writable, then hides the 'Add' button
		if($dataType && $dataType->getDataTypeName()=="Links") {
			if(!$this->getRecordIsWritable()){
				//$this->addJsCodeAfterShow("$('#".$this->getDetailId()." .addNewSubElement').removeClass('Green').addClass('disabledBg').unbind('click').find('font').removeClass('H');");
				$this->addJsCodeAfterShow("$('#".$this->getDetailId()."').find('.addNewSubElement, td.edit, td.delete, td.restore').removeClass('Green edit delete restore').addClass('disabledBg').unbind('click').find('font').removeClass('H');");
			}
		}

		//add any JsCode if defined:
		if((string)$fieldXml["jsCode"]!=null){
			$this->addJsCodeAfterShow(str_replace('$$idForm$$', $this->getDetailId(), (string)$fieldXml["jsCode"]));
		}
		if((string)$fieldXml["jsCodeInDetail"]!=null){
			$jsCode = str_replace('$$idForm$$', $this->getDetailId(), (string)$fieldXml["jsCodeInDetail"]);
			if($dataType && ($dataType->getDataTypeName()=="Attributs")) {
				$jsCode = str_replace('$$attrValue$$', $rm->getRecord()->getFieldValue($field->getFieldName()), $jsCode);
			}
			$this->addJsCodeAfterShow($jsCode);
		}

		//close the field div
		$rm->put('</div>');

		if($fieldXml["totalWidth"]!="" || $fieldXml["labelWidth"]!="" || ($fieldXml["useMultipleColumn"]!="" && $fieldXml["useRadioButtons"]!="1" && $fieldXml["useCheckboxes"]!="1")) {
			$this->updateWidthOnLeaveField($fieldName, $fieldXml);
		}
	}

	public function finish(){
		$rm = $this->getTemplateRecordManager();
		$this->setRecordIsWritable(null);
		//reset the previewCrtHeight for next use
		$rm->addJsCode("previewCrtHeight = 10;");
	}

}


