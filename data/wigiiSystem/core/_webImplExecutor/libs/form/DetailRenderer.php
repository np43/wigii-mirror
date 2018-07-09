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
 * Renders a Record for detail view (read).
 * Created on 3 déc. 09 by LWR
 * Modified by Medair in 2016 for maintenance purposes (see SVN log for details)
 */
class DetailRenderer extends FieldRenderer implements FieldListVisitor {
	private $_debugLogger;
	private $_executionSink;
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("DetailRenderer");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("DetailRenderer");
		}
		return $this->_executionSink;
	}

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
	public function getRecordIsWritable(){ return $this->recordIsWritable; }
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

	private $funcExpEvaluator;
	/**
	 * Returns a configured FuncExpEvaluator that can be used to evaluate FuncExps during Field rendering.
	 * @return FuncExpEvaluator
	 * @throws FieldRendererException::CONFIGURATION_ERROR in TemplateRecordManager is not injected properly
	 */
	protected function getFuncExpEval() {
		if(!isset($this->funcExpEvaluator)) {
			$trm = $this->getTemplateRecordManager();
			if(!isset($trm)) throw new FieldRendererException('TemplateRecordManager has not be injected properly into the FormRenderer',FieldRendererException::CONFIGURATION_ERROR);
			$this->funcExpEvaluator = $trm->getFuncExpEvaluator($this->getP(),$trm->getRecord());
		}
		return $this->funcExpEvaluator;
	}
	protected function freeFuncExpEval() {
		if(isset($this->funcExpEvaluator) && method_exists($this->funcExpEvaluator, 'freeMemory')) $this->funcExpEvaluator->freeMemory();
	}
	
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
		$dataTypeName = ($dataType!=null?$dataType->getDataTypeName():null);
		$rm = $this->getTemplateRecordManager();
		$p = $this->getP();
		if($p!=null){
			$isPublicPrincipal = ServiceProvider::getAuthorizationService()->isPublicPrincipal($p);
		} else {
			$isPublicPrincipal = false;
		}
		$this->resetFieldCorrectionDueToExternalFactors(); //remove any field correction first
		
		//if field is hidden, or onlyInForm, or onlyInWrite with a none writable record, or not in Public and principal is public -> skip it
		if(($isPublicPrincipal && $fieldXml["notInPublic"]=="1")) return;
		if((!$isPublicPrincipal && $fieldXml["onlyInPublic"]=="1")) return;
		if($rm->isForPrint() && $fieldXml["notInPrint"]=="1") return;
		if($rm->isForPrint() && $fieldXml["notHiddenInPrint"]!="1" && $rm->getRecord()->getWigiiBag()->isHidden($fieldName)) return;
		if(!$rm->isForPrint() && $rm->getRecord()->getWigiiBag()->isHidden($fieldName)) return;
		if($fieldXml["onlyInForm"]=="1") return;
		if($fieldXml["onlyInWrite"]=="1" && $this->getRecordIsWritable()===false) return;

		//if no value then do not display, except:
		//if proof status is Deleted -> show the email field
		if($dataTypeName=="Emails" && $rm->getRecord()->getFieldValue($field->getFieldName(), "proofStatus")==Emails::PROOF_STATUS_DELETED){
			//display the delete sign with unsubscribe info
		//do an exception for Blobs with isJournal to make the +add button available
		} else if($dataTypeName=="Blobs" && $fieldXml["isJournal"]=="1"){
			//display the label
		// hides Links if external access and empty
		} else if($dataTypeName=="Links"){		    
		    if($rm->isForExternalAccess() && $rm->getRecord()->getFieldValue($fieldName) == 0 && $fieldXml['displayEvenIfEmpty']!='1') return;
		//always display field if displayEvenIfEmpty
		} else if($fieldXml["displayEvenIfEmpty"]=="1"){
			//display the label
		//prevent empty booleans to be displayed
		} else if($dataTypeName=="Booleans" && $rm->getRecord()->getFieldValue($field->getFieldName(), "value")==false){
			return;
		} else if($dataType!= null && !$rm->getRecord()->getWigiiBag()->isFilled($field->getFieldName())){
			return;
		}

		$idField = $this->getDetailId()."__".$fieldName;
		$fieldClass = (string)$fieldXml["class"];
		$fieldClass .= $rm->getAdditionalFieldClass($fieldName,$dataTypeName);
		if(strpos($fieldClass, "updatedRecently")!==false) $this->increaseFieldCorrectionDueToExternalFactors(-5); //updatedRecently adds 5px of right border.
		// CWE 10.02.2016: if element is blocked, enables add comments if allowOnReadOnly
		if($fieldXml["allowOnReadOnly"]=="1") $fieldClass .=" allowOnReadOnly ";
		if($rm->getRecord() instanceof Element && $rm->getRecord()->isState_blocked()) {
			$fieldClass .= " readOnly ";
		}
		$isTitle = ($fieldXml["displayAsTitle1"]=="1" || $fieldXml["displayAsTitle2"]=="1" || $fieldXml["displayAsTitle3"]=="1");
		if($isTitle && $dataType!= null){
			if($dataTypeName == "Files" || $dataTypeName == "Urls" || $dataTypeName == "Addresses" || $dataTypeName == "Blobs" || $dataTypeName == "Texts"){
				$isTitle = false;
			}
		}

		//fieldGroup management
		if($fieldXml["groupStart"]=="1"){
			$this->enterFieldGroup($rm, $fieldXml, $fieldName, $idField);
			// CWE 21.06.2018 interprets htmlExp or htmlInDetailExp
			if((string)$fieldXml["htmlExp"]!=null) {
				$htmlExp = str2fx((string)$fieldXml["htmlExp"]);
				if($htmlExp) {
					$htmlExp = $this->getFuncExpEval()->evaluateFuncExp($htmlExp,$this);
					if(!empty($htmlExp)) $rm->put($htmlExp);
				}
			}
			if((string)$fieldXml["htmlInDetailExp"]!=null) {
				$htmlExp = str2fx((string)$fieldXml["htmlInDetailExp"]);
				if($htmlExp) {
					$htmlExp = $this->getFuncExpEval()->evaluateFuncExp($htmlExp,$this);
					if(!empty($htmlExp)) $rm->put($htmlExp);
				}
			}
			return;
		} else if($fieldXml["groupEnd"]=="1"){
			//we need to take the getCrtField as it might be different to the current fieldName. Current fieldName is numeroted as fields must be unique
			$crtFieldGroupId = $this->getDetailId()."__".$this->getCrtField();
			//check if data in group
			if($this->isCrtFieldFilled() || $fieldXml["displayEvenIfEmpty"]=="1"){
				$rm->addJsCode("" .
						"$('#".$crtFieldGroupId.">.label').css('cursor','pointer').click(function(){ " .
						"if($('#".$crtFieldGroupId."_group:visible').length){ " .
							"$('#".$crtFieldGroupId."_group').hide();" .
							"$('#".$crtFieldGroupId.">.label').append('<span class=\"expand\"> &nbsp; ".$rm->h("cickToShowGroupContent")."</span>');" .
						"} else { " .
							"$('#".$crtFieldGroupId.">.label>span.expand').remove();" .
							"$('#".$crtFieldGroupId."_group').show();" .
							"resize_scrollArea(true);".
						"}" .
					"});");

                    if($fieldXml["displayAsTag"]=="1"){
                        $rm->addJsCode("" .
                            "$('#".$crtFieldGroupId." .lessBsp').css('cursor','pointer').click(function(){" .
                            "if($('#".$crtFieldGroupId."_group:visible').length){" .
                                "$('#".$crtFieldGroupId."_group').hide();" .
                                "$('#".$crtFieldGroupId.">.label').append('<span class=\"expand\"> &nbsp; ".$rm->h("cickToShowGroupContent")."</span>');" .
                            "} else {" .
                                "$('#".$crtFieldGroupId."_group').show();" .
                                "resize_scrollArea(true);".
                            "}" .
                            "});");
                    }

				if($fieldXml["expand"]=="0" || $fieldXml["forceCollapse"]=="1"){
					$rm->addJsCode("" .
						"$('#".$crtFieldGroupId."_group').hide();" .
						"$('#".$crtFieldGroupId.">.label').append('<span class=\"expand\"> &nbsp; ".$rm->h("cickToShowGroupContent")."</span>');" .
						"");
				}
			} else {
				$rm->addJsCode("$('#".$crtFieldGroupId."').hide(); ");
			}
			// CWE 09.06.2018 interprets htmlExp or htmlInDetailExp
			if((string)$fieldXml["htmlExp"]!=null) {
				$htmlExp = str2fx((string)$fieldXml["htmlExp"]);
				if($htmlExp) {
					$htmlExp = $this->getFuncExpEval()->evaluateFuncExp($htmlExp,$this);
					if(!empty($htmlExp)) $rm->put($htmlExp);
				}
			}
			if((string)$fieldXml["htmlInDetailExp"]!=null) {
				$htmlExp = str2fx((string)$fieldXml["htmlInDetailExp"]);
				if($htmlExp) {
					$htmlExp = $this->getFuncExpEval()->evaluateFuncExp($htmlExp,$this);
					if(!empty($htmlExp)) $rm->put($htmlExp);
				}
			}
			$this->leaveCrtFieldGroup($rm, $fieldXml);
			return;
		}
		$this->updateWidthOnEnterField($fieldName, $fieldXml);
		
// 		else if($fieldXml["totalWidth"]!="" || $fieldXml["labelWidth"]!="" || ($fieldXml["useMultipleColumn"]!="" && $fieldXml["useRadioButtons"]!="1" && $fieldXml["useCheckboxes"]!="1")) {
// 			$this->updateWidthOnEnterField($fieldName, $fieldXml);
// 		}

		/**
		 * Exception for Integrated html files with displayContentInDetail
		 */
		if($dataType && $dataTypeName=="Files" && $fieldXml["htmlArea"]=="1" && $fieldXml["displayContentInDetail"]=="1"){
			$rm->displayValue($fieldName, $this->getIsInLineWidth(), $this->getVisibleLanguage());
			if($fieldXml["totalWidth"]!="" || $fieldXml["labelWidth"]!="" || ($fieldXml["useMultipleColumn"]!="" && $fieldXml["useRadioButtons"]!="1" && $fieldXml["useCheckboxes"]!="1")) {
				$this->updateWidthOnLeaveField($fieldName, $fieldXml);
			}
			return;
		}

		//field management

		//if we render this area that means fieldGroup is filled (freetexts don't count as filling group)
		if($dataType && $rm->getRecord()->getWigiiBag()->isFilled($field->getFieldName())) $this->setCrtFieldIsFilled();

		//open field div
		
		$style = "width: 100%; max-width:".$this->getTotalWidth()."px;";
		if($fieldXml["noMargin"]=="1"){
			$style .= "margin-right:0px;";
		}
        if($fieldXml["displayHidden"]=="1"){
            $style .= "display:none;";
        }
		$rm->put('<div id="'.$idField.'" class="field '.$fieldClass.'" style="'.$style.'" '.($dataType!=null?'data-wigii-datatype="'.$dataTypeName.'"':'').' >');
		if($dataType!=null){
			$additionalInformations = $rm->getAdditionalInformation($fieldName);
			if($additionalInformations) $rm->put('<div class="addinfo ui-corner-all SBIB">'.$additionalInformations.'</div>');
		}

		//display label
		if($dataType!=null && $fieldXml["noLabel"]!="1"){
			//20 is the label padding
			$noPadding = "";
			if($dataTypeName=="Files"){
				if($fieldXml["displayPreviewOnly"]=="1"){
					$labelWidth = $this->getIsInLineWidth();
				} else {
					$labelWidth = min($this->getLabelWidth(), 100);
				}
			} else if($dataTypeName=="Urls" && $fieldXml["bigLabel"]=="1"){
				$labelWidth = min($this->getLabelWidth(), 100);
			} else if(($isTitle || $fieldXml["isInLine"] =="1") && $fieldXml["displayAsTag"]!="1"){
				$labelWidth = $this->getIsInLineWidth();
				$noPadding = "padding-right:0px;"; //don't need the right padding if is inline
			} else {
				$labelWidth = $this->getLabelWidth();
			}
			$style = "width: 100%; max-width:".$labelWidth."px;$noPadding";
			if($dataTypeName=="Files"){
				$style .= "text-align:center;";
			}
			$rm->put('<div class="label" style="'.$style.'" >');
			$rm->displayLabel($fieldName, $labelWidth, $this->getVisibleLanguage());
			$rm->put('</div>');
		}

		//display value
		if($dataType && ($dataTypeName=="Files")){
			if($fieldXml["displayPreviewOnly"]=="1"){
				$valueWidth = $this->getIsInLineWidth();
			} else {
				$valueWidth = $this->getIsInLineWidth()-($labelWidth+20);
			}
		} else if($dataType && ($dataTypeName=="Urls" && $fieldXml["bigLabel"]=="1")){
			$valueWidth = $this->getIsInLineWidth()-($labelWidth+20);
		} else if((($isTitle || $fieldXml["isInLine"] =="1") && $fieldXml["displayAsTag"]!="1") || $dataType==null || $fieldXml["noLabel"] =="1"){
			$valueWidth = $this->getIsInLineWidth();
		} else {
			$valueWidth = $this->getValueWidth();
		}
		$style = "width: 100%; max-width:".$valueWidth."px;overflow:hidden;";
		$class = "";
		if($dataType!=null){
			if($dataTypeName == "Blobs" ||
				$dataTypeName == "Texts"){
				$class .= " text ";
			}
			if($dataTypeName == "Files"){
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
		if($rm->isForPrint() && $dataType!= null && ($dataTypeName=="Blobs" || ($dataTypeName=="Files" && $fieldXml["htmlArea"] && $fieldXml["displayContentInDetail"]))){
			$style = preg_replace('/width: 100%; max-width:(.*)px/', "", $style);
			$valueWidth =null;
		}
		$dataAttributes='';
		if($dataTypeName=='Attributs') {
			$dataAttributes='data-wigii-dbvalue="'.$rm->formatValueToPreventInjection($rm->getRecord()->getFieldValue($field->getFieldName())).'"';
		}	
		elseif($dataTypeName=='MultipleAttributs') {
			$dbValue = $rm->getRecord()->getFieldValue($field->getFieldName());
			if(is_array($dbValue)) $dbValue = implode(',',$dbValue);
			$dataAttributes='data-wigii-dbvalue="'.$rm->formatValueToPreventInjection($dbValue).'"';
		}	
		$rm->put('<div class="'.$class.'" style="'.$style.'" '.$dataAttributes.'>');
		if($dataType!= null && $dataTypeName!="Links" &&
			!$rm->getRecord()->getWigiiBag()->isFilled($field->getFieldName()) &&
			!($dataTypeName=="Emails" && $rm->getRecord()->getFieldValue($field->getFieldName(), "proofStatus")==Emails::PROOF_STATUS_DELETED)
			){
			$rm->displayEvenIfEmpty($fieldName, $valueWidth, $this->getVisibleLanguage());
		} else {
			$rm->displayValue($fieldName, $valueWidth, $this->getVisibleLanguage());
		}
		$rm->put('</div>');			
		
		// adds any dynamically generated hidden divs
		if((string)$fieldXml["divExp"]!=null) $this->resolveDivExp((string)$fieldXml["divExp"]);
		if((string)$fieldXml["divInDetailExp"]!=null) $this->resolveDivExp((string)$fieldXml["divInDetailExp"]);
		
		//add any JsCode if defined:
		if((string)$fieldXml["jsCode"]!=null){
			$this->addJsCodeAfterShow(str_replace('$$idForm$$', $this->getDetailId(), (string)$fieldXml["jsCode"]));
		}
		if((string)$fieldXml["jsCodeInDetail"]!=null){
			$jsCode = str_replace('$$idForm$$', $this->getDetailId(), (string)$fieldXml["jsCodeInDetail"]);
			if($dataType && ($dataTypeName=="Attributs")) {
				$jsCode = str_replace('$$attrValue$$', $rm->getRecord()->getFieldValue($field->getFieldName()), $jsCode);
			}
			$this->addJsCodeAfterShow($jsCode);
		}
		//add any dynamically generated JsCode if defined:
		if((string)$fieldXml["jsCodeExp"]!=null){
			$this->addJsCodeAfterShow(str_replace('$$idForm$$', $this->getDetailId(), (string)$fieldXml["jsCodeExp"]));
		}
		if((string)$fieldXml["jsCodeInDetailExp"]!=null){
			$jsCode = str_replace('$$idForm$$', $this->getDetailId(), (string)$fieldXml["jsCodeInDetailExp"]);
			if($dataType && ($dataTypeName=="Attributs")) {
				$jsCode = str_replace('$$attrValue$$', $rm->getRecord()->getFieldValue($field->getFieldName()), $jsCode);
			}
			$this->addJsCodeAfterShow($jsCode);
		}
		
		//close the field div
		$rm->put('</div>');

// 		if($fieldXml["totalWidth"]!="" || $fieldXml["labelWidth"]!="" || ($fieldXml["useMultipleColumn"]!="" && $fieldXml["useRadioButtons"]!="1" && $fieldXml["useCheckboxes"]!="1")) {
// 			$this->updateWidthOnLeaveField($fieldName, $fieldXml);
// 		}
		$this->updateWidthOnLeaveField($fieldName, $fieldXml);
	}

	public function finish(){
		$rm = $this->getTemplateRecordManager();
		$this->setRecordIsWritable(null);
		//reset the previewCrtHeight for next use
		$rm->addJsCode("previewCrtHeight = 10;");
		$this->freeFuncExpEval();
	}

	protected function resolveDivExp($divExp) {
		if(empty($divExp)) return;
		//$this->debugLogger()->logBeginOperation('resolveDivExp');
		$divExp = str2fx($divExp);
		if($divExp) {
			$evalFx = $this->getFuncExpEval();
			$divArray = $evalFx->evaluateFuncExp($divExp,$this);
			if(is_array($divArray)) {
				$rm = $this->getTemplateRecordManager();
				foreach($divArray as $className=>$htmlContent) {
					// extracts html attributes
					$htmlAttributes = '';
					if(is_array($htmlContent)) {
						foreach($htmlContent as $attrName=>$attrVal) {
							if($attrName!='content') $htmlAttributes .= ' '.$attrName.'="'.$attrVal.'"';
						}
						$htmlContent=$htmlContent['content'];
					}
					$rm->put('<div class="'.$className.'" style="display:none"'.$htmlAttributes.'>');
					$rm->put($htmlContent);
					$rm->put('</div>');
				}
			}
		}
		//$this->debugLogger()->logEndOperation('resolveDivExp');
	}
}


