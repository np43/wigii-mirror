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
 * Created 25 July 2011
 * by LWR
 */

class ElementPListRowsForElementBlogImpl extends ElementPListWebImplWithWigiiExecutor {

	private $isGroupedBy;
	private $crtGroupByValue;
	private $trm_;
	protected function getTRM(){
		if(!isset($this->trm_)){
			$this->trm_ = $this->createTRMInstance();
		}
		return $this->trm_;
	}
	protected function createTRMInstance(){
		return $this->getWigiiExecutor()->createTRM(null, false, false, false, true, false, true);
	}
	public function setTRM($var){ $this->trm_ = $var; }

	public function getDetailRenderer($rowId){
		if(!isset($this->setDetailRenderer)){
			$this->detailRenderer = DetailRenderer::createInstance($rowId, $this->getTRM());
			$this->getTRM()->setDetailRenderer($this->detailRenderer);
		} else {
			$this->setDetailRenderer->reset($rowId, $this->getTRM());
		}
		return $this->detailRenderer;
	}
	public function setDetailRenderer($detailRenderer){ $this->detailRenderer = $detailRenderer; }


	private $fieldList;
	public function setFieldList($fieldList){$this->fieldList = $fieldList;}
	protected function getFieldList(){ return $this->fieldList; }
	private $p;
	public function setP($p){$this->p = $p;}
	protected function getP(){ return $this->p; }
	private $exec;
	public function setExec($exec){$this->exec = $exec;}
	protected function getExec(){ return $this->exec; }

	protected function getMenuWidth(){ return 0; }
	protected function getMultipleWidth(){ return 5; }

	private $pageSize;
	public function setPageSize($size){ $this->pageSize = $size; }
	protected function getPageSize(){ return $this->pageSize; }
	private $desiredPage;
	public function setDesiredPage($page){ $this->desiredPage = $page; }
	protected function getDesiredPage(){ return $this->desiredPage; }

	public static function createInstance($wigiiExecutor, $listContext){
		$elPl = new self();
		$elPl->setWigiiExecutor($wigiiExecutor);
		$elPl->setListContext($listContext);
		$elPl->isGroupedBy = $listContext->getGroupBy();
		$elPl->crtGroupByValue = $listContext->getGroupByItemCurrentValue();
		return $elPl;
	}

	/**
	 * Adds an element to the list
	 * throws ListException::ALREADY_EXISTS if we try to put a second time the same element in the list
	 */
	private $elementIds; //array which stores the elements ids already added
	private $nb;
	private $globalNb; //numbering for manual paging
	private $headersMade = false;
	private $tableBegan = false;
	private $doOnlyRows = false;
	private $doOnlyRowsContent = false;
	public function doOnlyRows($doOnlyRowsContent = false){
		$this->doOnlyRows = true;
		$this->doOnlyRowsContent = $doOnlyRowsContent;
	}
	public function getOnlyRows(){
		return $this->doOnlyRows;
	}
	public function getTotalElementsIds(){
		return $this->elementIds;
	}

	public function getTotalNumberOfElements(){
		return $this->globalNb;
	}
	public function getNumberOfElementsProcessed(){
		return $this->nb;
	}

	//show only duplicates management
	private $previousElement = null;
	private $previousGroupByValue = null;
	private $groupByFS = null;
	private $shouldRecalculateMultipleElementState = false;
	public function addElementP($elementP){
		$rm = $this->getTRM();
		$p = $this->getP();

		//if only duplicates then keep in memory the last one and display only if groupBy value is the same...
		//WARNING, ensure that manual paging has not been activated in getSelectedElementsInGroups to
		//prevent error when displaying the results. As duplicates should not return too much of result not having paging issues
		//should not make any problem
		if($this->getListContext()->isGroupByOnlyDuplicates() && $this->previousElement !== $elementP){
			if(!isset($this->groupByFS)) $this->groupByFS = $this->getListContext()->getGroupByItemFieldSelector();
//			fput($this->groupByFS);
//			fput($elementP->getElement()->getFieldList());
			$crtGroupByValue = $elementP->getElement()->getFieldValue($this->groupByFS->getFieldName(), $this->groupByFS->getSubFieldName());
			if($crtGroupByValue == null) return;

			if($this->previousGroupByValue == $crtGroupByValue && $this->previousElement){
				$this->addElementP($this->previousElement);
				$this->previousElement = null;
			}
			if($this->previousGroupByValue != $crtGroupByValue){
				$this->previousGroupByValue = $crtGroupByValue;
				$this->previousElement = $elementP;
				return;
			}
		}

		$this->globalNb++;

		$element = $elementP->getElement();
		$elId = $element->getId();

		if($this->elementIds[$elId]!=null) throw new ListException("Id: $elId already exist in the ElementListFrame", ListException::ALREADY_EXIST);
		$this->elementIds[$elId] = $elId;

		//manual paging:
		if($this->getPageSize()!= 0 && $this->getDesiredPage()!=0){
			if(	($this->globalNb <= (($this->getDesiredPage()-1)*$this->getPageSize())) ||
				($this->globalNb > ($this->getDesiredPage()*$this->getPageSize()))
				){
				//do nothing
				return;
			}
		}

		$this->nb++;

		$rowId = "row_".$element->getId();

		$rm->reset($element);
		$this->getDetailRenderer($rowId);

		$elementFieldSelectorList = $this->getListContext()->getFieldSelectorList();
		if($elementFieldSelectorList == null){
			//we are in the list view, we need a field selector list
			throw new ServiceException("A FieldSelectorList is needed", ServiceException::INVALID_ARGUMENT);
		}

		if($this->doOnlyRows){
			if(!is_array($this->doOnlyRows)) $this->doOnlyRows = array();
			$this->doOnlyRows[] = $elementP->getId();
		}
		if(!$this->doOnlyRowsContent){
			echo "\n";
			if($this->isGroupedBy !=null && $this->isGroupedBy !== "null"){
				$fieldSelector = $this->getListContext()->getGroupByItemFieldSelector();
				$crtGroupByValue = null;
				if($fieldSelector){
					$crtGroupByValue = $rm->formatValueFromFS($fieldSelector, $element, true);
				}

				if($this->crtGroupByValue != $crtGroupByValue){
					$this->crtGroupByValue = $crtGroupByValue;
					$this->getListContext()->setGroupByItemCurrentValue($crtGroupByValue);
					?><div class="groupByTitle grayFont"><?=$crtGroupByValue;?></div><?
					echo "\n";
				}
			}

			//add the current selected item
			$cacheLookup = $this->getExec()->getCurrentCacheLookup($p, "selectElementDetail", "element/detail/".$elementP->getId());
			$class = "";
			$class .= " H el ";
			//add the readOnly class if this is the case:
			//if($elementP->getRights()->canShareElement() && !$elementP->getRights()->canWriteElement()) $class .= " shareElement ";
			if(!$elementP->getRights()->canWriteElement() || $element->isState_blocked() || $elementP->isParentElementState_blocked()) $class .= " readOnly ";
			if($this->getListContext()->getCrtSelectedItem()==$element->getId()) $class .= " S ";
			if($this->getListContext()->isInMultipleSelection($element->getId())) $class .= " M ";
			if($this->doOnlyRows) $class .= "new "; //to prevent resetting all rows events in JS: setListenersToRows

			?><div href="#<?=$cacheLookup;?>" <?=($class ? 'class="'.$class.'" ' : '');?> <?=($element->isState_dismissed() ? 'style="text-decoration:line-through" ' : '');?> id="<?=$rowId;?>"><?
		} else {
			echo ExecutionServiceImpl::answerRequestSeparator;
			echo $rowId;
			echo ExecutionServiceImpl::answerParamSeparator;
		}

		//add menu
		$elementEnableState = $this->computeEnableElementState($elementP);
		$elementState = $element->getStateAsInt();
		echo '<div class="field dragHandler">&nbsp;</div>';
		echo '<div class="max"></div>';
		echo '<div class="mul"></div>';
		echo '<div class="menu"><span class="elEnableState" style="display:none;">'.$elementEnableState.'</span><span class="elState" style="display:none">'.$elementState.'</span></div>';
		// updates list context multiple selection with element state
		if($this->getListContext()->isInMultipleSelection($element->getId())) {
			$this->getListContext()->updateElementStateInMultipleSelection($element->getId(), $elementEnableState, $elementState, false);
			$this->shouldRecalculateMultipleElementState = true;
		}

		//store in the context each duplicates ids to be able to click on the select all checkbox
		if($this->getListContext()->isGroupByOnlyDuplicates()){
			$isWritable = false;
			if($elementP->getRights() && $elementP->getRights()->canWriteElement()) $isWritable = true;
			$this->getListContext()->addDuplicatesId($elementP->getId(), $isWritable, $elementEnableState, $elementState);
		}

		if($element->isState_locked()){
			echo '<fieldset class="isPlayingRole ui-corner-all" style="border-color:#CC4B4B;" ><legend class="ui-corner-all" style="background-color:#CC4B4B;" >';
 			echo '<img alt="locked" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/22x22/emblems/emblem-locked.png" style="vertical-align:middle;" />&nbsp;';
			$info = $element->getArrayState_lockedInfo();
			if(is_array($info)){
				echo $rm->t("lockedThe");
				echo " ".date("d.m.Y H:i", $info["timestamp"]);
				echo " ".$rm->t("byUser")." ";
				if($info["realUserId"]==null){
					//if($info["userWigiiNamespace"]!=null) echo $info["userWigiiNamespace"]." : ";
					echo $info["username"];
				}
				if($info["realUserId"]!=null){
					//if($info["realUserWigiiNamespace"]!=null) echo $info["realUserWigiiNamespace"]." : ";
					echo $info["realUsername"];
					/*
					echo " ".$rm->t("usingRole")." ";
					echo $info["username"];
					*/
				}
			} else if($info != null){
				echo $info;
			}
			echo '</legend>';
//			$this->setTotalWidth($this->getTotalWidth()+$this->getCorrectionWidth()-15);
			if(is_array($info) && $info["message"]!=null){
				echo '<div class="field" style="border-bottom:1px #86A6B7 dotted; margin-top:2px; margin-bottom:5px;padding-bottom:5px;width:'.$this->getTotalWidth().'px;" >'.nl2br($info["message"]).'</div>';
			}
		}
		if($element->isState_blocked() && $elementP->isEnabledElementState_blocked()){
			echo '<fieldset class="isPlayingRole ui-corner-all" style="border-color:#EA2424;" ><legend class="ui-corner-all" style="background-color:#EA2424;" >';
			echo '<img alt="locked" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/22x22/documents/document-denied.png" style="vertical-align:middle;" />&nbsp;';
			$info = $element->getArrayState_blockedInfo();
			if(is_array($info)){
				echo $rm->t("blockedThe");
				echo " ".date("d.m.Y H:i", $info["timestamp"]);
				echo " ".$rm->t("byUser")." ";
				if($info["realUserId"]==null){
					//if($info["userWigiiNamespace"]!=null) echo $info["userWigiiNamespace"]." : ";
					echo $info["username"];
				}
				if($info["realUserId"]!=null){
					//if($info["realUserWigiiNamespace"]!=null) echo $info["realUserWigiiNamespace"]." : ";
					echo $info["realUsername"];
					/*
					echo " ".$rm->t("usingRole")." ";
					echo $info["username"];
					*/
				}
			} else if($info != null){
				echo $info;
			}
			echo '</legend>';
			//			$this->setTotalWidth($this->getTotalWidth()+$this->getCorrectionWidth()-15);
			//			$this->setTotalWidth($this->getTotalWidth()-15);
			if(is_array($info) && $info["message"]!=null){
				echo '<div class="field" style="border-bottom:1px #86A6B7 dotted; margin-top:2px; margin-bottom:5px;padding-bottom:5px;width:'.($this->getTotalWidth()).'px;" >'.nl2br($rm->t($info["message"])).'</div>';
			}
		}
		if($element->isState_dismissed()){
			echo '<fieldset class="isPlayingRole ui-corner-all" style="border-color:#EA2424;" ><legend class="ui-corner-all" style="background-color:#EA2424;" >';
			echo '<img alt="locked" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/22x22/emblems/emblem-unreadable.png" style="vertical-align:middle;" />&nbsp;';
			$info = $element->getArrayState_dismissedInfo();
			if(is_array($info)){
				echo $rm->t("dismissedThe");
				echo " ".date("d.m.Y H:i", $info["timestamp"]);
				echo " ".$rm->t("byUser")." ";
				if($info["realUserId"]==null){
					//if($info["userWigiiNamespace"]!=null) echo $info["userWigiiNamespace"]." : ";
					echo $info["username"];
				}
				if($info["realUserId"]!=null){
					//if($info["realUserWigiiNamespace"]!=null) echo $info["realUserWigiiNamespace"]." : ";
					echo $info["realUsername"];
					/*
					 echo " ".$rm->t("usingRole")." ";
					echo $info["username"];
					*/
				}
			} else if($info != null){
				echo $info;
			}
			echo '</legend>';
			//			$this->setTotalWidth($this->getTotalWidth()+$this->getCorrectionWidth()-15);
			//			$this->setTotalWidth($this->getTotalWidth()-15);
			if(is_array($info) && $info["message"]!=null){
				echo '<div class="field" style="border-bottom:1px #86A6B7 dotted; margin-top:2px; margin-bottom:5px;padding-bottom:5px;width:'.($this->getTotalWidth()).'px;" >'.nl2br($rm->t($info["message"])).'</div>';
			}
		}
		if($element->isState_finalized()){
			echo '<fieldset class="isPlayingRole ui-corner-all" style="border-color:#008AB8;" ><legend class="ui-corner-all" style="background-color:#008AB8;" >';
			//echo '<div class="field ui-corner-all" style="background-color:#008AB8;" >';
			$info = $element->getArrayState_finalizedInfo();
			if(is_array($info)){
				echo $rm->t("finalizedThe");
				echo " ".date("d.m.Y H:i", $info["timestamp"]);
				echo " ".$rm->t("byUser")." ";
				if($info["realUserId"]==null){
					//if($info["userWigiiNamespace"]!=null) echo $info["userWigiiNamespace"]." : ";
					echo $info["username"];
				}
				if($info["realUserId"]!=null){
					//if($info["realUserWigiiNamespace"]!=null) echo $info["realUserWigiiNamespace"]." : ";
					echo $info["realUsername"];
					/*
					echo " ".$rm->t("usingRole")." ";
					echo $info["username"];
					*/
				}
			} else if($info != null){
				echo $info;
			}
			echo '</legend>';
			//			$this->setTotalWidth($this->getTotalWidth()+$this->getCorrectionWidth()-15);
			//			$this->setTotalWidth($this->getTotalWidth()-15);
			if(is_array($info) && $info["message"]!=null){
				echo '<div class="field" style="border-bottom:1px #86A6B7 dotted; margin-top:2px; margin-bottom:5px;padding-bottom:5px;width:'.($this->getTotalWidth()).'px;" >'.nl2br($rm->t($info["message"])).'</div>';
			}
		}
		if($element->isState_approved()){
			echo '<fieldset class="isPlayingRole ui-corner-all" style="border-color:#A0E061;" ><legend class="ui-corner-all" style="background-color:#A0E061;" >';
			//echo '<div class="field ui-corner-all" style="background-color:#A0E061;" >';
			echo '<img alt="locked" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/22x22/status/available.png" style="vertical-align:middle;" />&nbsp;';
			$info = $element->getArrayState_approvedInfo();
			if(is_array($info)){
				echo $rm->t("approvedThe");
				echo " ".date("d.m.Y H:i", $info["timestamp"]);
				echo " ".$rm->t("byUser")." ";
				if($info["realUserId"]==null){
					//if($info["userWigiiNamespace"]!=null) echo $info["userWigiiNamespace"]." : ";
					echo $info["username"];
				}
				if($info["realUserId"]!=null){
					//if($info["realUserWigiiNamespace"]!=null) echo $info["realUserWigiiNamespace"]." : ";
					echo $info["realUsername"];
					/*
					echo " ".$rm->t("usingRole")." ";
					echo $info["username"];
					*/
				}
			} else if($info != null){
				echo $info;
			}
			echo '</legend>';
			//			$this->setTotalWidth($this->getTotalWidth()+$this->getCorrectionWidth()-15);
			//			$this->setTotalWidth($this->getTotalWidth()-15);
			if(is_array($info) && $info["message"]!=null){
				echo '<div class="field" style="border-bottom:1px #86A6B7 dotted; margin-top:2px; margin-bottom:5px;padding-bottom:5px;width:'.($this->getTotalWidth()).'px;" >'.nl2br($rm->t($info["message"])).'</div>';
			}
		}
		if($element->isState_deprecated()){
			echo '<fieldset class="isPlayingRole ui-corner-all" style="border-color:#FFCC33;" ><legend class="ui-corner-all" style="background-color:#FFCC33;" >';
			$info = $element->getArrayState_deprecatedInfo();
			if(is_array($info)){
				echo $rm->t("deprecatedThe");
				echo " ".date("d.m.Y H:i", $info["timestamp"]);
				echo " ".$rm->t("byUser")." ";
				if($info["realUserId"]==null){
					//if($info["userWigiiNamespace"]!=null) echo $info["userWigiiNamespace"]." : ";
					echo $info["username"];
				}
				if($info["realUserId"]!=null){
					//if($info["realUserWigiiNamespace"]!=null) echo $info["realUserWigiiNamespace"]." : ";
					echo $info["realUsername"];
					/*
					echo " ".$rm->t("usingRole")." ";
					echo $info["username"];
					*/
				}
			} else if($info != null){
				echo $info;
			}
			echo '</legend>';
//			$this->setTotalWidth($this->getTotalWidth()+$this->getCorrectionWidth()-15);
//			$this->setTotalWidth($this->getTotalWidth()-15);
			if(is_array($info) && $info["message"]!=null){
				echo '<div class="field" style="border-bottom:1px #86A6B7 dotted; margin-top:2px; margin-bottom:5px;padding-bottom:5px;width:'.($this->getTotalWidth()).'px;" >'.nl2br($rm->t($info["message"])).'</div>';
			}
		}

		//display the element detail
		foreach($elementFieldSelectorList->getListIterator() as $key=>$header) { //$fieldSelectorList->getListIterator() as $fieldSelector){
			$xmlHeader = $elementFieldSelectorList->getXml($key);
			if($xmlHeader == null) continue;
			if($xmlHeader["width"]=="0") continue;
			if($xmlHeader["hidden"]=="1") continue;

			if(	!$header->isElementAttributeSelector()){
				$field = $element->getFieldList()->getField($header->getFieldName());
				$fieldXml = $field->getXml();
				$fieldName = $field->getFieldName();
				$dataType = $field->getDataType();
			} else {
				$field = null;
				$fieldXml = null;
				$fieldName = $header->getFieldName();
				$dataType = null;
			}

			if($xmlHeader["displayDBValue"]=="1"){
				$value = $rm->formatValueToPreventInjection(($header->isElementAttributeSelector() ? $element->getAttribute($header) : $element->getFieldValue($header->getFieldName(), $header->getSubFieldName())));
			} else {
				$value = $rm->formatValueFromFS($header, $element);
				if($xmlHeader["displayAsBoolean"]=="1" && $field->getDataType()->getDataTypeName()!="Booleans"){
					$value = $rm->doFormatForBoolean($value!=null, false, $value);
				}
			}

//			fput($header);
//			fput($fieldName);
//			fput($value);

			if(!$value && !(
				$xmlHeader["displayEvenIfEmpty"]=="1" ||
				($dataType!= null && $dataType->getDataTypeName()=="Booleans") ||
				($dataType!= null && $dataType->getDataTypeName()=="Links") ||
				($dataType!= null && $dataType->getDataTypeName()=="Blobs" && $fieldXml["isJournal"]=="1") ||
				($dataType!= null && $dataType->getDataTypeName()=="Emails" && $rm->getRecord()->getFieldValue($fieldName, "proofStatus")==Emails::PROOF_STATUS_DELETED)
				)){
				continue;
			}

			$style = null;
			$class = null;
			if(!$elementP->getRights()->canWriteElement()) $class .= " readOnly ";
			$class .= " ".(string)$fieldXml["class"]." ";
			$class .= " ".(string)$xmlHeader["class"]." ";
			$idField = $rowId."__".$fieldName;
			$rm->put('<div id="'.$idField.'" class="field '.$class.'" style="width:100%; '.$style.'" >');

			//display label
			if((($xmlHeader != null && count($xmlHeader->children()) > 0) ||
				$header->isElementAttributeSelector()) && $xmlHeader["noLabel"]!="1") {
				$rm->put('<div class="label" >');
				$rm->displayHeaderLabel($header, $fieldXml, $xmlHeader);
				$rm->put('</div>');
			} elseif($dataType!=null && $fieldXml["noLabel"]!="1" && $xmlHeader["noLabel"]!="1"){
				$style = "";
				$rm->put('<div class="label" style="'.($fieldXml["isInLine"]=="1" ? "float:none;" : "").$style.'" >');
				if($dataType!= null && ($dataType->getDataTypeName()=="Files" || $dataType->getDataTypeName()=="Urls")){
					$rm->displayLabel($fieldName, null, null, false);
				} else {
					$rm->displayLabel($fieldName);
				}
				$rm->put('</div>');
			}
			//display value
			$style = "";
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
			$rm->put('<div class="'.$class.'" style="'.$style.'" >');
			if($dataType!= null && $dataType->getDataTypeName()!="Links" &&
				!$value &&
				!($dataType->getDataTypeName()=="Emails" && $rm->getRecord()->getFieldValue($field->getFieldName(), "proofStatus")==Emails::PROOF_STATUS_DELETED)
				){
				$rm->emptyContent();
			} else {
				$rm->put($value);
			}
			$rm->put('</div>');

			$rm->put('</div>');

//			// for Links datatype: if element is not writable, then hides the 'Add' button
//			if($dataType && $dataType->getDataTypeName()=="Links") {
//				if(!$this->getRecordIsWritable()){
//					//$this->addJsCodeAfterShow("$('#".$this->getDetailId()." .addNewSubElement').removeClass('Green').addClass('disabledBg').unbind('click').find('font').removeClass('H');");
//					$this->addJsCodeAfterShow("$('#".$this->getDetailId()."').find('.addNewSubElement, td.edit, td.delete, td.restore').removeClass('Green edit delete restore').addClass('disabledBg').unbind('click').find('font').removeClass('H');");
//				}
//			}
//
//			//add any JsCode if defined:
//			if((string)$fieldXml["jsCode"]!=null){
//				$this->addJsCodeAfterShow(str_replace('$$idForm$$', $this->getDetailId(), (string)$fieldXml["jsCode"]));
//			}
//			if((string)$fieldXml["jsCodeInDetail"]!=null){
//				$jsCode = str_replace('$$idForm$$', $this->getDetailId(), (string)$fieldXml["jsCodeInDetail"]);
//				if($dataType && ($dataType->getDataTypeName()=="Attributs")) {
//					$jsCode = str_replace('$$attrValue$$', $rm->getRecord()->getFieldValue($field->getFieldName()), $jsCode);
//				}
//				$this->addJsCodeAfterShow($jsCode);
//			}

		}

		if($element->isState_locked()){
			$rm->put('</fieldset> ');
		}
		if($element->isState_blocked() && $elementP->isEnabledElementState_blocked()){
			$rm->put('</fieldset> ');
		}
		if($element->isState_dismissed()){
			$rm->put('</fieldset> ');
		}
		if($element->isState_finalized()){
			$rm->put('</fieldset> ');
		}
		if($element->isState_approved()){
			$rm->put('</fieldset> ');
		}
		if($element->isState_deprecated()){
			$rm->put('</fieldset> ');
		}

		if(!$this->doOnlyRowsContent){
			$blogViewXml = $this->getWigiiExecutor()->getConfigurationContext()->ma($p, $this->getExec()->getCrtModule(), Activity::createInstance("blogView"));
			$nbOfColumns = ($blogViewXml["nbOfColumns"]<>"" ? $blogViewXml["nbOfColumns"] : 2);
			
			?></div><?
			if($this->nb%$nbOfColumns==0){
				?><div class="clear"></div><?
			}
		}

		flush();
	}


	/**
	 * Returns an integer encoding the "enableElementState" menu
	 */
	private $enableElementStateConfigCache = null;
	protected function computeEnableElementState($elementP) {
		$p = $this->getP();
		// creates config cache if not already done
		if(!isset($this->enableElementStateConfigCache)) {
			$m =  $this->getExec()->getCrtModule();
			$configS = $this->getWigiiExecutor()->getConfigurationContext();
			$this->enableElementStateConfigCache = array(
				'Element_enableLockedStatus' => $configS->getParameter($p, $m, 'Element_enableLockedStatus')=="1",
				'Element_enableBlockedStatus' => $configS->getParameter($p, $m, 'Element_enableBlockedStatus')=="1",
				'Element_enableImportant1Status' => $configS->getParameter($p, $m, 'Element_enableImportant1Status')=="1",
				'Element_enableImportant2Status' => $configS->getParameter($p, $m, 'Element_enableImportant2Status')=="1",
				'Element_enableFinalizedStatus' => $configS->getParameter($p, $m, 'Element_enableFinalizedStatus')=="1",
				'Element_enableApprovedStatus' => $configS->getParameter($p, $m, 'Element_enableApprovedStatus')=="1",
				'Element_enableDismissedStatus' => $configS->getParameter($p, $m, 'Element_enableDismissedStatus')=="1",
				'Element_enableArchivedStatus' => $configS->getParameter($p, $m, 'Element_enableArchivedStatus')=="1",
				'Element_enableDeprecatedStatus' => $configS->getParameter($p, $m, 'Element_enableDeprecatedStatus')=="1",
				'Element_enableHiddenStatus' => $configS->getParameter($p, $m, 'Element_enableHiddenStatus')=="1",
				'Element_enableHiddenDelete' => $configS->getParameter($p, $m, 'enableDeleteOnlyForAdmin')=="1"
			);
		}

		// initializes elementP with default policy
		$elementP->enableElementState_locked($this->enableElementStateConfigCache['Element_enableLockedStatus']);
		$elementP->enableElementState_blocked($this->enableElementStateConfigCache['Element_enableBlockedStatus']);
		$elementP->enableElementState_important1($this->enableElementStateConfigCache['Element_enableImportant1Status']);
		$elementP->enableElementState_important2($this->enableElementStateConfigCache['Element_enableImportant2Status']);
		$elementP->enableElementState_finalized($this->enableElementStateConfigCache['Element_enableFinalizedStatus']);
		$elementP->enableElementState_approved($this->enableElementStateConfigCache['Element_enableApprovedStatus']);
		$elementP->enableElementState_dismissed($this->enableElementStateConfigCache['Element_enableDismissedStatus']);
		$elementP->enableElementState_archived($this->enableElementStateConfigCache['Element_enableArchivedStatus']);
		$elementP->enableElementState_deprecated($this->enableElementStateConfigCache['Element_enableDeprecatedStatus']);
		$elementP->enableElementState_hidden($this->enableElementStateConfigCache['Element_enableHiddenStatus']);
		$elementP->enableElementState_delete($this->enableElementStateConfigCache['Element_enableHiddenDelete'] && !$elementP->getRights()->canModify());

		// updates policy using the ElementPolicyEvaluator
		$policyEval = $this->getElementPolicyEvaluator();
		if(isset($policyEval)) $policyEval->computeEnableElementState($p, $elementP);

		// converts the policy to a binary representation
		return $elementP->getEnableElementStateAsInt();
	}


	public function actOnBeforeAddElementP(){
		$this->globalNb = 0;
		$this->shouldRecalculateMultipleElementState = false;
		//in case of only doing the rows, then no headers needed
		if($this->doOnlyRows) return;

		$this->nb = 0;
		$this->nbGroup = 0;

		$p = $this->getP();

		$elementFieldSelectorList = $this->getListContext()->getFieldSelectorList();
		//we always enable the multiple boxes, it is only if one is check that it will enable the dialogBox
		$blogViewXml = $this->getWigiiExecutor()->getConfigurationContext()->ma($p, $this->getExec()->getCrtModule(), Activity::createInstance("blogView"));

		?><div class="dataBlog"><?

		?><div class="groupList"><?

		//add subfolders:
		$groupS = ServiceProvider::getGroupAdminService();
		$cgl = $this->getListContext()->getGroupPList();
		if($cgl->count()>1){
			//$lf = $groupS->getListFilterForGroupsInElementList($this->getExec()->getCrtModule(), $cgl->getIds());
			//we need to select all groups and filter on groupId to make the propagation working correctly. Otherwise we will miss some parent groups
			$lf = $groupS->getListFilterForSelectGroupWithoutDetail();
		} else {
			$gP = reset($cgl->getListIterator());
			$g = $gP->getDbEntity();
			//$lf = $groupS->getListFilterForSubGroupsInElementList($this->getExec()->getCrtModule(), $g->getId());
			$lf = $groupS->getListFilterForSelectGroupWithoutDetail();
		}

		if($cgl->count()==1){
			?><div id="groupList_<?=($g->getGroupParentId() ? $g->getGroupParentId() : '0');?>" class="folder folderUp L H"><?
				?><?=$this->getTRM()->h("groupUp");?><?
			?></div><?
		}

		if(!$this->getListContext()->doesGroupListIncludeChildren()){
			//$groupS->getAllGroups($p, $this->getExec()->getCrtModule(), $this, $lf);
			$groupS->getAllGroups($p, $this->getExec()->getCrtModule(), $this, $lf);
		}


		if($cgl->count()>1){
			//add list elements
			if(!$this->getListContext()->doesGroupListIncludeChildren()){
				$cacheLookup = $this->getExec()->getCurrentCacheLookup($this->getP(), "groupSelectorPanel", "groupSelectorPanel/selectGroupAndChildren/0");
				?><div id="groupList_0" href="#<?=$cacheLookup;?>" class="folder navigation subFoldersContent H L"><?=$this->getTrm()->t("listAllElements");?></div><?
			} else {
				?><div id="groupList_0" class="folder navigation subFoldersList H L"><?=$this->getTrm()->t("subFoldersList");?></div><?
			}
		} else {
			//add list elements
			if(!$this->getListContext()->doesGroupListIncludeChildren()){
				$cacheLookup = $this->getExec()->getCurrentCacheLookup($this->getP(), "groupSelectorPanel", "groupSelectorPanel/selectGroupAndChildren/0");
				?><div id="groupList_<?=($g->getGroupParentId() ? $g->getGroupParentId() : '0');?>" href="#<?=$cacheLookup;?>" class="folder navigation subFoldersContent H L"><?=$this->getTrm()->t("listAllElements");?></div><?
			} else {
				?><div id="groupList_<?=($g->getGroupParentId() ? $g->getGroupParentId() : '0');?>" class="folder navigation subFoldersList H L"><?=$this->getTrm()->t("subFoldersList");?></div><?
			}
		}

		?></div><?
	}

	private $nbGroup;
	public function addGroupP($groupP, $parentGroup){
		$groups = $this->getListContext()->getGroupPList()->getListIterator();
		if(count($groups)>1){
			if($parentGroup) return;
		} else {
			if(!$parentGroup || !$groups[$parentGroup->getId()]){
				return;
			}
		}
		$this->nbGroup++;
		$class .= "folder H ";
		$class .= ($groupP->getRights() == null ? "noRights ":"");
		$class .= (($groupP->getRights() != null && !$groupP->getRights()->canShareElement())?"readOnly ":"");
		$class .= (($groupP->getRights() != null && $groupP->getRights()->canShareElement() && !$groupP->getRights()->canWriteElement())?"share ":"");
		$class .= (($groupP->getRights() != null && $groupP->getRights()->canWriteElement())?"write ":"");
		$class .= (($groupP->getRights() != null && $groupP->getRights()->canModify())?"admin ":"");
		$class .= ($groupP->getGroup()->getGroupParentId()==0 ?"level1 ":"");
		?><div id="groupList_<?=$groupP->getId();?>" class="<?=$class;?>"><?
			echo ($this->getP()->isWigiiNamespaceCreator() || ($this->getP()->getWigiiNamespace()->getWigiiNamespaceName() != $groupP->getGroup()->getWigiiNamespace()->getWigiiNamespaceName() && $groupP->getGroup()->getWigiiNamespace()->getWigiiNamespaceName()) ? '<span class="grayFont">'.$groupP->getGroup()->getWigiiNamespace()->getWigiiNamespaceName()." : </span>" : "" ).$groupP->getGroup()->getGroupName();
		?></div><?
	}

	public function actOnFinishAddElementP($numberOfObjects){
		$p = $this->getP();
		$blogViewXml = $this->getWigiiExecutor()->getConfigurationContext()->ma($p, $this->getExec()->getCrtModule(), Activity::createInstance("blogView"));
		$nbOfColumns = ($blogViewXml["nbOfColumns"]<>"" ? $blogViewXml["nbOfColumns"] : 2);

		if($this->nb == 0 && $this->nbGroup==0){
			if($this->getListContext()->getFieldSelectorLogExp()!=null){
				$t = $this->getTRM()->t("noElementMatchSearchCriteria");
			} else {
				$t = $this->getTRM()->t("noElementInGroup");
			}
			?><div style="color:#666;padding:25px;padding-top:50px;"><?=$t;?></div><?
		}

		$this->getExec()->addJsCode("crtBlogViewNbOfColumns = $nbOfColumns;");

		if(!$this->doOnlyRows){
			$this->getExec()->addJsCode("crtElementId = '".$this->getListContext()->getCrtSelectedItem()."';");
			$this->getExec()->addJsCode("setListenersToElementBlog();");
			if($this->getListContext()->getSearchBar()){
				//hide subfolders not appearing in groupPanel
				$this->getExec()->addJsCode("matchSubFoldersInElementListToGroupPanel();");
			}
		} else {
			$this->getExec()->addJsCode("setListenersToRows('#moduleView .blog', '#moduleView .dataBlog div.el', '#moduleView .dataBlog div.folder', '#moduleView .blog>.cm');resize_blog();");
		}
		if($this->getListContext()->isMultipleSelection()){
			if($this->shouldRecalculateMultipleElementState) $this->getListContext()->computeMultipleElementStateInt();
			$this->getExec()->addRequests("multipleDialog/".$this->getExec()->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$this->getExec()->getCrtModule()->getModuleName()."/element/displayMultipleDialog");
		}
		if(!$this->doOnlyRows){
			?></div><?
			//addJsCode to match the column width on headers
			//this is done in the element_resize js function

			$this->displayNbItems($numberOfObjects);

		}

	}

	protected function displayNbItems($numberOfObjects){
		?><div class="nbItemsInList SBB ui-corner-all"><?
			if($this->nbGroup){
				echo $this->nbGroup;
				echo " ".$this->getTRM()->t("#-groups#");
				if($this->nb) echo "&nbsp;&nbsp;&nbsp;";
			}
			if($this->nb){
				echo '<span class="nb">'.$this->nb.'</span>';
				if($this->nb != $numberOfObjects) echo ' / <span class="total">'.$numberOfObjects.'</span>';
				if($this->nb != $numberOfObjects) echo ' (<span class="page">'.($this->getDesiredPage() ? $this->getDesiredPage() : $this->getListContext()->getDesiredPageNumber()).'</span><span class="H L loadMoreLines">'.$this->getTRM()->t("scrollToLoadNextElements").'</span>)';
				echo '<span class="type"> '.$this->getTRM()->t("#-elements#").'</span>';
			}
		?></div><?
	}

}

