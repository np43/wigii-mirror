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
 * Created 25 July 2011 by LWR
 * Changed by Medair (LMA) on 28.03.2017: Improved findOnlyDuplicate to compute groupBy in DB instead of php
 * Modified by Medair (ACA, CWE) on 16.05.2017 to hide delete button in contextual menu if enableDeleteOnlyForAdmin and not admin or if Element_beforeDeleteExp=0
 * Refactored by Medair (CWE) on 29.09.2017 to extend from ElementPGroupableSelectablePagedListImpl
 */
class ElementPListRowsForElementBlogImpl extends ElementPGroupableSelectablePagedListImpl {
    protected function getMenuWidth(){ return 0; }
    protected function getMultipleWidth(){ return 5; }
    
    private $detailRenderer;
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

	
	public static function createInstance($wigiiExecutor, $listContext){
		$elPl = new self();
		$elPl->reset($wigiiExecutor,$listContext);
		return $elPl;
	}

	/**
	 * Does the real work of displaying an element in the list
	 */
	protected function doAddElementP($elementP){
		$rm = $this->getTRM();
		$p = $this->getP();
		$element = $elementP->getElement();
		$rowId = "row_".$element->getId();

		$this->getDetailRenderer($rowId);

		$elementFieldSelectorList = $this->getListContext()->getFieldSelectorList();
		if($elementFieldSelectorList == null){
			//we are in the list view, we need a field selector list
			throw new ServiceException("A FieldSelectorList is needed", ServiceException::INVALID_ARGUMENT);
		}

		if(!$this->getDoOnlyRowsContent()){
			echo "\n";
			if($this->isGroupedBy() && $this->hasGroupByValueChanged()){
				?><div class="groupByTitle grayFont"><?=$this->getCrtGroupByValue();?></div><?
				echo "\n";
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
			if($this->isDoOnlyRows()) $class .= "new "; //to prevent resetting all rows events in JS: setListenersToRows

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
				echo '<div class="field" style="border-bottom:1px #86A6B7 dotted; margin-top:2px; margin-bottom:5px;padding-bottom:5px;width: 100%; max-width:'.$this->getTotalWidth().'px;" >'.nl2br($info["message"]).'</div>';
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
				echo '<div class="field" style="border-bottom:1px #86A6B7 dotted; margin-top:2px; margin-bottom:5px;padding-bottom:5px;width: 100%; max-width:'.($this->getTotalWidth()).'px;" >'.nl2br($rm->t($info["message"])).'</div>';
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
				echo '<div class="field" style="border-bottom:1px #86A6B7 dotted; margin-top:2px; margin-bottom:5px;padding-bottom:5px;width: 100%; max-width:'.($this->getTotalWidth()).'px;" >'.nl2br($rm->t($info["message"])).'</div>';
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
				echo '<div class="field" style="border-bottom:1px #86A6B7 dotted; margin-top:2px; margin-bottom:5px;padding-bottom:5px;width: 100%; max-width:'.($this->getTotalWidth()).'px;" >'.nl2br($rm->t($info["message"])).'</div>';
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
				echo '<div class="field" style="border-bottom:1px #86A6B7 dotted; margin-top:2px; margin-bottom:5px;padding-bottom:5px;width: 100%; max-width:'.($this->getTotalWidth()).'px;" >'.nl2br($rm->t($info["message"])).'</div>';
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
				echo '<div class="field" style="border-bottom:1px #86A6B7 dotted; margin-top:2px; margin-bottom:5px;padding-bottom:5px;width: 100%; max-width:'.($this->getTotalWidth()).'px;" >'.nl2br($rm->t($info["message"])).'</div>';
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
            //Define the width
            $totalWidth = ($xmlHeader["totalWidth"]?(string)$xmlHeader["totalWidth"].'px':'100%');
			$style = "width: $totalWidth;";

			$rm->put('<div id="'.$idField.'" class="field '.$class.'" style="'.$style.'" >');

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
			$style = "";
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

		if(!$this->getDoOnlyRowsContent()){
			$blogViewXml = $this->getWigiiExecutor()->getConfigurationContext()->ma($p, $this->getExec()->getCrtModule(), Activity::createInstance("blogView"));
			$nbOfColumns = ($blogViewXml["nbOfColumns"]<>"" ? $blogViewXml["nbOfColumns"] : 2);
            $blogItemWidth = ($blogViewXml["blogItemWidth"]<>"" ? $blogViewXml["blogItemWidth"] : false);
			
			?></div><?
			if($this->getNumberOfElementsProcessed()%$nbOfColumns==0 && !$blogItemWidth){
				?><div class="clear"></div><?
			}
		}

		flush();
	}

	public function actOnBeforeAddElementP(){
	    parent::actOnBeforeAddElementP();
		//in case of only doing the rows, then no headers needed
		if($this->isDoOnlyRows()) return;

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
			$lf = $groupS->getListFilterForSelectGroupWithoutDetail();
		} else {
			$gP = reset($cgl->getListIterator());
			$g = $gP->getDbEntity();
			$lf = $groupS->getListFilterForSelectGroupWithoutDetail();
		}

		if($cgl->count()==1){
			?><div id="groupList_<?=($g->getGroupParentId() ? $g->getGroupParentId() : '0');?>" class="folder folderUp L H"><?
				?><?=$this->getTRM()->h("groupUp");?><?
			?></div><div class="clear"></div><?
		}

		if(!$this->getListContext()->doesGroupListIncludeChildren()){
			$groupS->getAllGroups($p, $this->getExec()->getCrtModule(), $this, $lf);
		}

		?>
        <div class="clear"></div>
        <?php

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
	    parent::actOnFinishAddElementP($numberOfObjects);
	    $p = $this->getP();
		$blogViewXml = $this->getWigiiExecutor()->getConfigurationContext()->ma($p, $this->getExec()->getCrtModule(), Activity::createInstance("blogView"));
		$nbOfColumns = ($blogViewXml["nbOfColumns"]<>"" ? $blogViewXml["nbOfColumns"] : 2);
        $blogItemWidth = ($blogViewXml["blogItemWidth"]<>"" ? $blogViewXml["blogItemWidth"] : false);

        if($this->getNumberOfElementsProcessed() == 0 && $this->nbGroup==0){
			if($this->getListContext()->getFieldSelectorLogExp()!=null){
				$t = $this->getTRM()->t("noElementMatchSearchCriteria");
			} else {
				$t = $this->getTRM()->t("noElementInGroup");
			}
			?><div style="color:#666;padding:25px;padding-top:50px;"><?=$t;?></div><?
		}

		$this->getExec()->addJsCode("crtBlogViewNbOfColumns = $nbOfColumns;");
		if($blogItemWidth){
            $this->getExec()->addJsCode("blogItemWidth = $blogItemWidth");
        }
		if(!$this->isDoOnlyRows()){
			$this->getExec()->addJsCode("crtElementId = '".$this->getListContext()->getCrtSelectedItem()."';");
			$this->getExec()->addJsCode("setListenersToElementBlog();");
			if($this->getListContext()->getSearchBar()){
				//hide subfolders not appearing in groupPanel
				$this->getExec()->addJsCode("matchSubFoldersInElementListToGroupPanel();");
			}
		} else {
			$this->getExec()->addJsCode("setListenersToRows('#moduleView .blog', '#moduleView .dataBlog div.el', '#moduleView .dataBlog div.folder', '#moduleView .blog>.cm');resize_blog();");
		}
		if(!$this->isDoOnlyRows()){
			?></div><?
			$this->displayNbItems($numberOfObjects);
		}
	}

	protected function displayNbItems($numberOfObjects){
		?><div class="nbItemsInList SBB ui-corner-all"><?
			if($this->nbGroup){
				echo $this->nbGroup;
				echo " ".$this->getTRM()->t("#-groups#");
				if($this->getNumberOfElementsProcessed()) echo "&nbsp;&nbsp;&nbsp;";
			}
			if($this->getNumberOfElementsProcessed()){
				echo '<span class="nb">'.$this->getNumberOfElementsProcessed().'</span>';
				if($this->getNumberOfElementsProcessed() != $numberOfObjects) echo ' / <span class="total">'.$numberOfObjects.'</span>';
				if($this->getNumberOfElementsProcessed() != $numberOfObjects) echo ' (<span class="page">'.($this->getDesiredPage() ? $this->getDesiredPage() : $this->getListContext()->getDesiredPageNumber()).'</span><span class="H L loadMoreLines">'.$this->getTRM()->t("scrollToLoadNextElements").'</span>)';
				echo '<span class="type"> '.$this->getTRM()->t("#-elements#").'</span>';
			}
		?></div><?
	}
}

