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
 */

class ElementPListRowsForElementListImpl extends ElementPListWebImplWithWigiiExecutor {

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
		$elPl->nbOfHeaders = $listContext->getCrtViewParams('ElementPListRows_makeHeaders_getNb');
        if($elPl->getListContext()->isGroupByOnlyDuplicates())trim(strtoupper($elPl->crtGroupByValue));
		if(!$elPl->nbOfHeaders) $elPl->nbOfHeaders=0;
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

		/*
		 * Changed by Medair (LMA) on 28.03.2017: Improved findOnlyDuplicate to compute groupBy in DB instead of php
		 */
        //DEPRECATED if only duplicates then keep in memory the last one and display only if groupBy value is the same...
		//WARNING, ensure that manual paging has not been activated in getSelectedElementsInGroups to
		//prevent error when displaying the results. As duplicates should not return too much of result not having paging issues
		//should not make any problem
        /*
		if($this->getListContext()->isGroupByOnlyDuplicates() && $this->previousElement !== $elementP){
			if(!isset($this->groupByFS)) $this->groupByFS = $this->getListContext()->getGroupByItemFieldSelector();
			$crtGroupByValue = $elementP->getElement()->getFieldValue($this->groupByFS->getFieldName(), $this->groupByFS->getSubFieldName());
			$this->previousGroupByValue = $crtGroupByValue;
            $this->previousGroupByValue = trim(strtoupper($this->previousGroupByValue));
            $crtGroupByValue = trim(strtoupper($crtGroupByValue));

			if($crtGroupByValue == null){
			    return;
            }
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
        //**/

		// CWE 2014.07.15: piece of code pushed down into the beginElement method in order to
		// use the calculated element state.
// 		//store in the context each duplicates ids to be able to click on the select all checkbox
// 		if($this->getListContext()->isGroupByOnlyDuplicates()){
// 			$isWritable = false;
// 			if($elementP->getRights() && $elementP->getRights()->canWriteElement()) $isWritable = true;
// 			$this->getListContext()->addDuplicatesId($elementP->getId(), $isWritable);
// 		}

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
		$this->getTrm()->reset($element);


		$elementFieldSelectorList = $this->getListContext()->getFieldSelectorList();
		if($elementFieldSelectorList == null){
			//we are in the list view, we need a field selector list
			throw new ServiceException("A FieldSelectorList is needed", ServiceException::INVALID_ARGUMENT);
		}

		$this->beginElement($elementP);

		foreach($elementFieldSelectorList->getListIterator() as $key=>$header) { //$fieldSelectorList->getListIterator() as $fieldSelector){
			$xmlHeader = $elementFieldSelectorList->getXml($key);
			if($xmlHeader == null) continue;
			if($xmlHeader["width"]=="0") continue;
			if($xmlHeader["hidden"]=="1") continue;

			if(	!$header->isElementAttributeSelector()){
				$field = $element->getFieldList()->getField($header->getFieldName());
			} else {
				$field = null;
			}

			$class = null;
			$class .= $xmlHeader["class"];
			if(	!$header->isElementAttributeSelector() &&
				$header->getSubFieldName()=="path" &&
				$field->getDataType()->getDataTypeName()=="Files" &&
				$element->getFieldValue($header->getFieldName(), "path")!=null){
				//if a file exist, then add the download class to make active the click
				$class .= " download";
				//to know what field to download on the element, we add in the class the fieldName after a download_
				$class .= " download_".$header->getFieldName();
			}
			if($xmlHeader["displayDBValue"]=="1"){
				$value = $this->getTrm()->formatValueToPreventInjection(($header->isElementAttributeSelector() ? $element->getAttribute($header) : $element->getFieldValue($header->getFieldName(), $header->getSubFieldName())));
			} else {
				$value = $this->getTrm()->formatValueFromFS($header, $element);
				if($xmlHeader["displayAsBoolean"]=="1" && $field->getDataType()->getDataTypeName()!="Booleans"){
					$value = $this->getTrm()->doFormatForBoolean($value!=null, false, $value);
				}
				if(	!$header->isElementAttributeSelector() &&
					($field->getDataType()->getDataTypeName()=="Blobs" || $field->getDataType()->getDataTypeName()=="Textes" )
					){
						$fieldXml = $field->getXml();
						if(!((string)$fieldXml["htmlArea"]=="1")){
							//remove any empty lines to make the better conscise view
							$value = preg_replace('/^\n+|^[\t\s]*\n+/m', "", $value);
							$value = nl2br($value);
						} else {
							$value = preg_replace('/^<p>[^a-zA-Z0-9]*<\\/p>|[\\n\\r]/', "", $value, -1);
						}
				}
				if($xmlHeader["helpWidth"]!=null){
					$class .= " helpWidth help_".$xmlHeader["helpWidth"]." ";
				}
			}
			
			$this->addCell($value, $class);
		}

		$this->endElement();

	}

	/**
	 * groupBy filter management
	 */
	protected function beginElement($elementP){
		$element = $elementP->getElement();
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
					$crtGroupByValue = $this->getTrm()->formatValueFromFS($fieldSelector, $element, true);
				}
				//We put all groupBy in upper case in case of a find duplicate
				if($this->getListContext()->isGroupByOnlyDuplicates()){
                    $crtGroupByValue = trim(strtoupper($crtGroupByValue));
                }
				if($this->crtGroupByValue != $crtGroupByValue){
					$this->crtGroupByValue = $crtGroupByValue;
					$this->getListContext()->setGroupByItemCurrentValue($crtGroupByValue);
					?><tr class="groupByTitle"><?
					?><td COLSPAN=<?=$this->nbOfHeaders;?> ><div class="grayFont"><?=$crtGroupByValue;?></div></td></tr><?
					echo "\n";
				}
			}

			//add the current selected item
			$cacheLookup = $this->getExec()->getCurrentCacheLookup($this->getP(), "selectElementDetail", "element/detail/".$elementP->getId());
			$class = "";
			$class .= " H el ";
			//add the readOnly class if this is the case:
			//if($elementP->getRights()->canShareElement() && !$elementP->getRights()->canWriteElement()) $class .= " shareElement ";
			if(!$elementP->getRights()->canWriteElement()) $class .= " readOnly ";
			if($this->getListContext()->getCrtSelectedItem()==$element->getId()) $class .= " S ";
			if($this->getListContext()->isInMultipleSelection($element->getId())) $class .= " M ";
			if($this->doOnlyRows) $class .= "new "; //to prevent resetting all rows events in JS: setListenersToRows
			
			//add class if classExp is defined in listView activity
			$classExp = (string)$this->getWigiiExecutor()->getConfigurationContext()->ma($this->getP(), $this->getExec()->getCrtModule(), Activity::createInstance("listView"))["classExp"];
			if($classExp){
				$class .= " ".$this->getWigiiExecutor()->evaluateFuncExp($this->getP(),$this->getExec(),str2Fx($classExp),$elementP->getElement())." ";
			}

			?><tr href="#<?=$cacheLookup;?>" <?=($class ? 'class="'.$class.'" ' : '');?> <?=($element->isState_dismissed() ? 'style="text-decoration:line-through" ' : '');?> id="row_<?=$element->getId();?>"><?
		} else {
			echo ExecutionServiceImpl::answerRequestSeparator;
			echo "row_".$elementP->getId();
			echo ExecutionServiceImpl::answerParamSeparator;
		}

		//add column for multiple select
		$this->addCell(null, "mul");
		//add column for menu
		$elementEnableState = $this->computeEnableElementState($elementP);
		$elementState = $element->getStateAsInt();
		$eltState = '<span class="elEnableState" style="display:none;">'.$elementEnableState.'</span><span class="elState" style="display:none">'.$elementState.'</span>';
		$this->addCell($eltState, "menu");
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

		flush();
	}

	/**
	 * Returns an integer encoding the "enableElementState" menu
	 */
	private $enableElementStateConfigCache = null;
	protected function computeEnableElementState($elementP) {
		$p = $this->getP();
		$configS = $this->getWigiiExecutor()->getConfigurationContext();
		// creates config cache if not already done
		if(!isset($this->enableElementStateConfigCache)) {
			$m =  $this->getExec()->getCrtModule();			
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
			    'Element_enableHiddenDelete' => $configS->getParameter($p, $m, 'enableDeleteOnlyForAdmin')=="1" || ((string)$configS->getParameter($p, $m, 'Element_beforeDeleteExp')==="0")
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
		$elementP->enableElementState_delete($this->enableElementStateConfigCache['Element_enableHiddenDelete'] && (!$elementP->getRights()->canModify() || ((string)$configS->getParameter($p, $elementP->getElement()->getModule(), 'Element_beforeDeleteExp')==="0")));

		// updates policy using the ElementPolicyEvaluator
		$policyEval = $this->getElementPolicyEvaluator();
		if(isset($policyEval)) $policyEval->computeEnableElementState($p, $elementP);

		// converts the policy to a binary representation
		return $elementP->getEnableElementStateAsInt();
	}

	protected function endElement(){
		if(!$this->doOnlyRowsContent){
			?></tr><?
		}
	}

	protected function addCell($value, $class=null){
		if(is_array($value)){
			$temp = "";
			foreach($value as $i){
				if($i != null){
					if($temp != null) $temp .= ", ";
					$temp .= $i;
				}
			}
			$value = $temp;
		}
		?><td><div<?=($class ? ' class="'.$class.'" ' : '');?>><?=$value;?></div></td><?
	}

	public function actOnBeforeAddElementP(){
		$this->globalNb = 0;
		$this->shouldRecalculateMultipleElementState = false;
		//in case of only doing the rows, then no headers needed
		if($this->doOnlyRows) return;

		$this->nb = 0;
		$this->nbGroup = 0;

		$p = $this->getP();
		$this->makeHeaders();

		$elementFieldSelectorList = $this->getListContext()->getFieldSelectorList();
		//we always enable the multiple boxes, it is only if one is check that it will enable the dialogBox
		$listViewXml = $this->getWigiiExecutor()->getConfigurationContext()->ma($p, $this->getExec()->getCrtModule(), Activity::createInstance("listView"));

		?><div class="dataList"><table class="<?=($listViewXml["lineHeight"]!="" ? $listViewXml["lineHeight"] : "");?>"><?

		//we need to add col attribute to be able to manage the column width in an easy way
		//add column for multiple select
		$style=' style="width:'.($this->getMultipleWidth()+$this->totalPaddingInCol).'px;" ';
		?><col<?=$style;?>></col><?
		//add column for menu
		$style=' style="width:'.($this->getMenuWidth()+$this->totalPaddingInCol).'px;" ';
		?><col<?=$style;?>></col><?
		foreach($elementFieldSelectorList->getListIterator() as $key=>$header){
			$xmlHeader = $elementFieldSelectorList->getXml($key);
			if($xmlHeader == null) continue;
			if($xmlHeader["width"]=="0") continue;
			if($xmlHeader["hidden"]=="1") continue;
			$style = ' style="';
			//If the column have a custom session width, we get it
			if($this->getListContext()->getListViewUIPref($xmlHeader['field'], 'width')!=null) {
				$style.='width:'.($this->getListContext()->getListViewUIPref($xmlHeader['field'], 'width')+17).'px; ';
			} else {
				if($xmlHeader["width"]!="null"){
					$style.='width:'.($xmlHeader["width"]+$this->totalPaddingInCol).'px; ';
				}
			}
			$style .= '" ';
			?><col<?=$style;?>></col><?
			
		}

		?><tbody><?
		$this->tableBegan = true;


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
			?><tr id="groupList_<?=($g->getGroupParentId() ? $g->getGroupParentId() : '0');?>" class="folder H"><?
				$this->addCell(null, "");
				$this->addCell(null, "");
				?><td colspan="<?=$this->nbOfHeaders-2;?>" ><?
					//onclick="if($('#groupPanel li.selected').hasClass('level1')){ $('#groupPanel li.selected').parent().click(); } else { $('#groupPanel li.selected').parent().parent().click(); }"
					?><div class="folderUp L H" ><?=$this->getTRM()->h("groupUp");?></div><?
				?></td><?
			?></tr><?
		}

		if(!$this->getListContext()->doesGroupListIncludeChildren()){
			//$groupS->getAllGroups($p, $this->getExec()->getCrtModule(), $this, $lf);
			$groupS->getAllGroups($p, $this->getExec()->getCrtModule(), $this, $lf);
		}

		if($cgl->count()>1){
			if(true){
				//add list elements
				//this line is finaly hidden in css, but the cm menu and the up button refers to it to navigate
				?><tr id="groupList_0" class="folder navigation"><?
					//add column for multiple select
					$this->addCell(null, "");
					?><td colspan="<?=$this->nbOfHeaders-1;?>" ><?
						if(!$this->getListContext()->doesGroupListIncludeChildren()){
							$cacheLookup = $this->getExec()->getCurrentCacheLookup($this->getP(), "groupSelectorPanel", "groupSelectorPanel/selectGroupAndChildren/0");
							?><div href="#<?=$cacheLookup;?>" class="folder subFoldersContent H L"><?=$this->getTrm()->t("listAllElements");?></div><?
						} else {
							?><div class="folder subFoldersList H L"><?=$this->getTrm()->t("subFoldersList");?></div><?
						}
					?></td><?
				?></tr><?
				//add an empty tr line with correct number of td to prevent IE9 display error if no elements underneaf
				?><tr><?=str_repeat("<td></td>", $this->nbOfHeaders)?></tr><?

			}
		} else {
			//this line is finaly hidden in css, but the cm menu and the up button refers to it to navigate
			//add go to parent group line
			?><tr id="groupList_<?=($g->getGroupParentId() ? $g->getGroupParentId() : '0');?>" class="folder navigation"><?
				//add column for multiple select
				$this->addCell(null, "");
				?><td colspan="<?=$this->nbOfHeaders-1;?>" ><?
					if(true){ ?><div class="folder folderUp H"><?=$this->getTrm()->t("groupUp");?></div><? }
					if(!$this->getListContext()->doesGroupListIncludeChildren()){
						$cacheLookup = $this->getExec()->getCurrentCacheLookup($this->getP(), "groupSelectorPanel", "groupSelectorPanel/selectGroupAndChildren/".$g->getId());
						?><div href="#<?=$cacheLookup;?>" class="folder subFoldersContent H L"><?=$this->getTrm()->t("subFoldersContent");?></div><?
					} else {
						?><div class="folder subFoldersList H L"><?=$this->getTrm()->t("subFoldersList");?></div><?
					}
				?></td><?
			?></tr><?
			//add an empty tr line with correct number of td to prevent IE9 display error if no elements underneaf
			?><tr><?=str_repeat("<td></td>", $this->nbOfHeaders)?></tr><?
		}


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
		?><tr id="groupList_<?=$groupP->getId();?>" class="<?=$class;?>"><?
			//add column for multiple select
			$this->addCell(null, "mul");
			?><td colspan="<?=$this->nbOfHeaders-1;?>" ><div class="folder"></div><div><?=($this->getP()->isWigiiNamespaceCreator() || ($this->getP()->getWigiiNamespace()->getWigiiNamespaceName() != $groupP->getGroup()->getWigiiNamespace()->getWigiiNamespaceName() && $groupP->getGroup()->getWigiiNamespace()->getWigiiNamespaceName()) ? '<span class="grayFont">'.$groupP->getGroup()->getWigiiNamespace()->getWigiiNamespaceName()." : </span>" : "" ).$groupP->getGroup()->getGroupName();?></td><?
		?></tr><?
	}

	public function actOnFinishAddElementP($numberOfObjects){
		if($this->nb == 0 && $this->nbGroup==0){
			if(!$this->doOnlyRowsContent) {
				?><tr><?
			}
				//add column for multiple select
				$this->addCell(null, "");
				//add column for menu
				$this->addCell(null, "");
				if($this->getListContext()->getFieldSelectorLogExp()!=null){
					$t = $this->getTrm()->t("noElementMatchSearchCriteria");
				} else {
					$t = $this->getTrm()->t("noElementInGroup");
				}
				?><td colspan="<?=$this->nbOfHeaders-2;?>" ><div class="disabledFont"><?=$t;?></td><?
			if(!$this->doOnlyRowsContent) {
				?></tr><?
			}
		}

		if(!$this->doOnlyRows){
			$this->getExec()->addJsCode("crtElementId = '".$this->getListContext()->getCrtSelectedItem()."';");
			$this->getExec()->addJsCode("setListenersToElementList();");
			if($this->getListContext()->getSearchBar()){
				//hide subfolders not appearing in groupPanel
				$this->getExec()->addJsCode("matchSubFoldersInElementListToGroupPanel();");
			}
		} else {
//			$this->getExec()->addJsCode("setListenersToRows('".implode("','",$this->doOnlyRows)."');");

			//since 17/12/2012 remove any events and reset the listeners
			$this->getExec()->addJsCode("setListenersToRows();");
		}
		if($this->getListContext()->isMultipleSelection()){
			if($this->shouldRecalculateMultipleElementState) $this->getListContext()->computeMultipleElementStateInt();
			if(!$this->doOnlyRows){
				$this->getExec()->addRequests("multipleDialog/".$this->getExec()->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$this->getExec()->getCrtModule()->getModuleName()."/element/displayMultipleDialog");
			}		
		}
		if(!$this->doOnlyRows && $this->tableBegan){
			?></tbody></table></div><?
			//addJsCode to match the column width on headers
			//this is done in the element_resize js function

			$this->displayNbItems($numberOfObjects);

		}

	}

	protected function displayNbItems($numberOfObjects){
		?><div class="nbItemsInList SBB ui-corner-all"><?
			if($this->nbGroup){
				echo $this->nbGroup;
				echo " ".$this->getTrm()->t("#-groups#");
				if($this->nb) echo "&nbsp;&nbsp;&nbsp;";
			}
			if($this->nb){
				echo '<span class="nb">'.$this->nb.'</span>';
				if($this->nb != $numberOfObjects) echo ' / <span class="total">'.$numberOfObjects.'</span>';
				if($this->nb != $numberOfObjects) echo ' (<span class="page">'.($this->getDesiredPage() ? $this->getDesiredPage() : $this->getListContext()->getDesiredPageNumber()).'</span><span class="H L loadMoreLines">'.$this->getTRM()->t("scrollToLoadNextElements").'</span>)';
				echo '<span class="type"> '.$this->getTrm()->t("#-elements#").'</span>';
			}
		?></div><?
	}

	private $totalPaddingInCol = 17; //include the border
	public function getTotalPaddingInCol(){ return $this->totalPaddingInCol; }
	private $nbNoWidth = 0;
	public function getNbNoWidth(){ return $this->nbNoWidth; }
	private $nbOfHeaders = 0;
	public function getNbOfHeaders(){ return $this->nbOfHeaders; }
	private $totalWidth = 1; //we begin with one border
	public function getTotalWidth(){ return $this->totalWidth; }

	public function makeHeaders(){
		$this->nbOfHeaders = 0;
		$elementFieldSelectorList = $this->getListContext()->getFieldSelectorList();
		$trm = $this->createTRMInstance();
		$trm->reset(null, false, false, false, true, false, false); //we want to be able to buffer the result
		?><div class="headerList lH"><?
			//add column for multiple select
			$width = $this->getMultipleWidth();
			?><div class="mul" style="width:<?=$width;?>px;">&nbsp;</div><?
			$this->totalWidth += $width+$this->totalPaddingInCol;
			$this->nbOfHeaders ++;
			//add column for menu
			$width = $this->getMenuWidth();
			?><div class="menu" style="width:<?=$width;?>px;">&nbsp;</div><?
			$this->totalWidth += $width+$this->totalPaddingInCol;
			$this->nbOfHeaders ++;

			foreach($elementFieldSelectorList->getListIterator() as $key=>$fieldSelector){
				if($fieldSelector->isElementAttributeSelector()) $fieldXml = null;
				else $fieldXml = $this->getFieldList()->getField($fieldSelector->getFieldName())->getXml();
				$xmlHeader = $elementFieldSelectorList->getXml($key);
				//if there is no xml attached, that means the fieldSelector is not a header, but an other field we needed
				if(!isset($xmlHeader)) continue;
				if($xmlHeader["width"]=="0") continue;
				if($xmlHeader["hidden"]=="1") continue;

				$trm->displayHeaderLabel($fieldSelector, $fieldXml, $xmlHeader);

				$width = 0;
				$class = "key_".$key;
				$this->nbOfHeaders ++;
				//If the column have a custom session width, we get it
				if($this->getListContext()->getListViewUIPref($xmlHeader['field'], 'width')!=null) {
					$width = (0+$this->getListContext()->getListViewUIPref($xmlHeader['field'], 'width'));
					//we calculate the total fixed width, we add the intern padding, + border
					$this->totalWidth += $width+$this->totalPaddingInCol;
				} else {
					if($xmlHeader["width"]!="null"){
						$width = (0+$xmlHeader["width"]);
						//we calculate the total fixed width, we add the intern padding, + border
						$this->totalWidth += $width+$this->totalPaddingInCol;
					} else{
						$width = 1; //in IE don't support no width, then this width will change after
						$class .= " noWidth";
						$this->nbNoWidth ++;
					}
				}

				//SORTING
				if($fieldSelector->isElementAttributeSelector()){
					$dataTypeName = $fieldSelector->getFieldName();
					$subFields = $this->getListcontext()->defineElementSubFieldForSortByFilter();
				} else {
					$dataTypeName = $this->getFieldList()->getField($fieldSelector->getFieldName())->getDataType()->getDataTypeName();
					$subFields = $this->getListcontext()->defineDataTypeSubFieldForSortByFilter($dataTypeName);
				}
				//in the case of the Emails, if no subField value is defined in a column no sorting is possible
				//it is easy to change that by defining in the configuration of the listView a subField value for the Emails
				if($this->getListcontext()->byPassDataTypeForSortByFilter($dataTypeName) || ($subFields ? array_search($fieldSelector->getSubFieldName(), $subFields)===false : false)){
					$class .= " noSorting";
				}
				if($xmlHeader["defaultSorted"]!=null){
					$class .= " defaultSorted defaultSorted_".trim((string)$xmlHeader["defaultSorted"]);
				}
				//add the current sorting key
				if($this->getListContext()->getSortedBy()==$key){
					if($this->getListContext()->isAscending()){
						$class .= " ASC ";
					} else {
						$class .= " DESC ";
					}
				}
				?><div <?=($class ? 'class="'.$class.'" ' : '');?>style="width:<?=$width;?>px;"><?=$trm->getHtmlAndClean();?></div><?
			}

			$trm->addJsCode("" .
				"ElementPListRows_makeHeaders_getNb = ".$this->getNbOfHeaders().";" .
				"ElementPListRows_makeHeaders_getTotalWidth = ".$this->getTotalWidth().";" .
				"ElementPListRows_makeHeaders_getNbNoWidth = ".$this->getNbNoWidth().";" .
				"ElementPListRows_makeHeaders_totalPaddingInCol = ".$this->totalPaddingInCol.";" .
				"");
			$this->getListContext()->setCrtViewParams($this->getNbOfHeaders(), 'ElementPListRows_makeHeaders_getNb');
		?></div><div class="clear"></div><?

		$this->headersMade = true;

	}

}

