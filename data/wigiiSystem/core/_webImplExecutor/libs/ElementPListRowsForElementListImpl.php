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
 * Created 25 July 2011 by LWR
 * Changed by Medair (LMA) on 28.03.2017: Improved findOnlyDuplicate to compute groupBy in DB instead of php
 * Modified by Medair (ACA, CWE) on 16.05.2017 to hide delete button in contextual menu if enableDeleteOnlyForAdmin and not admin or if Element_beforeDeleteExp=0
 * Refactored by Medair (CWE) on 29.09.2017 to extend from ElementPGroupableSelectablePagedListImpl
 */
class ElementPListRowsForElementListImpl extends ElementPGroupableSelectablePagedListImpl {
    protected function getMenuWidth(){ return 0; }
    protected function getMultipleWidth(){ return 5; }

    public static function createInstance($wigiiExecutor, $listContext){
        $elPl = new self();
        $elPl->reset($wigiiExecutor,$listContext);
        $elPl->nbOfHeaders = $listContext->getCrtViewParams('ElementPListRows_makeHeaders_getNb');
        if(!$elPl->nbOfHeaders) $elPl->nbOfHeaders=0;
        return $elPl;
    }

    private $headersMade = false;
    private $tableBegan = false;
    /**
     * Does the real work of displaying an element in the list
     */
    protected function doAddElementP($elementP){
        $element = $elementP->getElement();

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
                	$value = $this->getTrm()->doFormatForBoolean($value!=null, false, $value, $xmlHeader);
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

    protected function beginElement($elementP){
        $element = $elementP->getElement();
    	if(!$this->getDoOnlyRowsContent()){
            echo "\n";
            if($this->isGroupedBy() && $this->hasGroupByValueChanged()){
                ?><tr class="groupByTitle"><?
                ?><td COLSPAN=<?=$this->nbOfHeaders;?> ><div class="grayFont"><?
                $groupByFS = $this->getListContext()->getGroupByItemFieldSelector();
                $groupByKey = $this->getListContext()->getGroupBy();
                $groupByLabel = null;
                if(!$groupByFS->isElementAttributeSelector()){
                	$fieldXml = $element->getFieldList()->getField($groupByFS->getFieldName())->getXml();
                	$xmlHeader = $this->getListContext()->getFieldSelectorList()->getXml($this->getListContext()->getGroupBy());
                	$this->getTRM()->displayHeaderLabel($groupByFS, $fieldXml, $xmlHeader);
                } else {
                	$this->getTRM()->displayHeaderLabel($groupByFS);
                }
                echo " : ".$this->getCrtGroupByValue()?></div></td></tr><?
                echo "\n";
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
            if($this->isDoOnlyRows()) $class .= "new "; //to prevent resetting all rows events in JS: setListenersToRows


            //add class if classExp is defined in listView activity
            $classExp = $this->getWigiiExecutor()->getConfigurationContext()->ma($this->getP(), $this->getExec()->getCrtModule(), Activity::createInstance("listView"));
            $classExp = (string)($classExp["classExp"]);
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

        flush();
    }

    protected function endElement(){
        if(!$this->getDoOnlyRowsContent()){
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
        parent::actOnBeforeAddElementP();
        //in case of only doing the rows, then no headers needed
        if($this->isDoOnlyRows()) return;

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
            $lf = $groupS->getListFilterForSelectGroupWithoutDetail();
        } else {
            $gP = reset($cgl->getListIterator());
            $g = $gP->getDbEntity();
            $lf = $groupS->getListFilterForSelectGroupWithoutDetail();
        }

        if($cgl->count()==1){
            ?><tr id="groupList_<?=($g->getGroupParentId() ? $g->getGroupParentId() : '0');?>" class="folder H"><?
            $this->addCell(null, "");
            $this->addCell(null, "");
            ?><td colspan="<?=$this->nbOfHeaders-2;?>" ><?
            ?><div class="folderUp L H" ><?=$this->getTRM()->h("groupUp");?></div><?
            ?></td><?
            ?></tr><?
        }

        if(!$this->getListContext()->doesGroupListIncludeChildren()){
            $groupS->getAllGroups($p, $this->getExec()->getCrtModule(), $this, $lf);
        }

        if($cgl->count()>1){
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
        } else {
            //this line is finaly hidden in css, but the cm menu and the up button refers to it to navigate
            //add go to parent group line
            ?><tr id="groupList_<?=($g->getGroupParentId() ? $g->getGroupParentId() : '0');?>" class="folder navigation"><?
            //add column for multiple select
            $this->addCell(null, "");
            ?><td colspan="<?=$this->nbOfHeaders-1;?>" ><?
            ?><div class="folder folderUp H"><?=$this->getTrm()->t("groupUp");?></div><?
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
        parent::actOnFinishAddElementP($numberOfObjects);
        // Finish HTML rendering
        if($this->getNumberOfElementsProcessed() == 0 && $this->nbGroup==0){
            if(!$this->getDoOnlyRowsContent()) {
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
            if(!$this->getDoOnlyRowsContent()) {
                ?></tr><?
            }
        }

        if(!$this->isDoOnlyRows()){
            $this->getExec()->addJsCode("crtElementId = '".$this->getListContext()->getCrtSelectedItem()."';");
            $this->getExec()->addJsCode("setListenersToElementList();");
            if($this->getListContext()->getSearchBar()){
                //hide subfolders not appearing in groupPanel
                $this->getExec()->addJsCode("matchSubFoldersInElementListToGroupPanel();");
            }
        } else {
            $this->getExec()->addJsCode("setListenersToRows();");
        }
        if(!$this->isDoOnlyRows() && $this->tableBegan){
            ?></tbody></table></div><?
            $this->displayNbItems($numberOfObjects);
        }
    }

    protected function displayNbItems($numberOfObjects){
        ?><div class="nbItemsInList SBB ui-corner-all"><?
        if($this->nbGroup){
            echo $this->nbGroup;
            echo " ".$this->getTrm()->t("#-groups#");
            if($this->getNumberOfElementsProcessed()) echo "&nbsp;&nbsp;&nbsp;";
        }
        if($this->getNumberOfElementsProcessed()){
            echo '<span class="nb">'.$this->getNumberOfElementsProcessed().'</span>';
            if($this->getNumberOfElementsProcessed() != $numberOfObjects) echo ' / <span class="total">'.$numberOfObjects.'</span>';
            if($this->getNumberOfElementsProcessed() != $numberOfObjects) echo ' (<span class="page">'.($this->getDesiredPage() ? $this->getDesiredPage() : $this->getListContext()->getDesiredPageNumber()).'</span><span class="H L loadMoreLines">'.$this->getTRM()->t("scrollToLoadNextElements").'</span>)';
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

