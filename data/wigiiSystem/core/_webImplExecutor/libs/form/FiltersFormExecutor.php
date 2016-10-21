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
 * Created on 12 October 2011 by LWR
 * Modified by Medair in 2016 for maintenance purposes (see SVN log for details)
 */
class FiltersFormExecutor extends FormExecutor {

	private $_debugLogger;
	private $_executionSink;
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("FiltersFormExecutor");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("FiltersFormExecutor");
		}
		return $this->_executionSink;
	}

	private $listContext;
	public function setListContext($lc){ $this->listContext = $lc; }
	protected function getListContext(){ return $this->listContext; }
	private $isSimpleFilters; //in this case the context is reset to filter in all root folders and subfolders + count data in each folder
	public function setIsSimpleFilters($var){ $this->isSimpleFilters = $var; }
	protected function isSimpleFilters(){ return $this->isSimpleFilters; }
	private $filterFormGroupTree;
	/**
	 * Injects the GroupPTree used in the limitFilterInGroup and excludeGroups drop-down.
	 * This GroupPTree is used to calculate the resulting group IDs for search depending on the include/exclude groups constraints.
	 * @param GroupPListTreeArrayImpl $groupPTreeArrayImpl
	 */
	public function setFilterFormGroupTree($groupPTreeArrayImpl) {
		$this->filterFormGroupTree = $groupPTreeArrayImpl;
	}
	protected function getFilterFormGroupTree() {return $this->filterFormGroupTree;}
	
	public static function createInstance($wigiiExecutor, $record, $formId, $submitUrl){
		$fe = new self();
		$fe->setWigiiExecutor($wigiiExecutor);
		$fe->setRecord($record);
		$fe->setFormId($formId);
		$fe->setSubmitUrl($submitUrl);
		return $fe;
	}

	protected function actOnCheckedRecord($p, $exec) {
		$transS = ServiceProvider::getTranslationService();
		$groupAS = ServiceProvider::getGroupAdminService();
		$configS = $this->getWigiiExecutor()->getConfigurationContext();

		$rec = $this->getRecord();

		$exec->addJsCode("setVis('filteringBar', false);");

		if(!$this->getListContext()->getSearchBar()){
			$exec->addRequests("filtersDialog/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/removeFilters/");
			return;
		}
		
		// CWE 2015.11.24: trashbin exclusion and folder space constraints are defined in ListContext::getExcludeGroupsInSearch and ListContext::getLimitFilterInGroupForSearch
		// and managed by the WigiiExecutor / filters action.
		if($configS->getParameter($p, $exec->getCrtModule(), "Group_selectAllGroupsOnSearch") != "0"){
			$this->getListContext()->setGroupPList($configS->getRootGroupsInModule($p, $exec->getCrtModule()), true);
		} else {
			$this->getListContext()->setGroupPList($this->getListContext()->getGroupPList(), true);
		}
		
		//change the config context to the new context
		$configS->setGroupPList($p, $exec->getCrtModule(), $this->getListContext()->getGroupPList(), true);

		//invalid moduleView cache to let each click on the filtering result displaying the correct content
		$exec->addJsCode("invalidCache('moduleView'); setFiltersButton(".($this->getListContext()->getSearchBar() ? 'true' : 'false').");");

		//update the __textSearch with the defined value:
		$exec->addJsCode("$('#workZone #searchBar input:first').val('".addSlashes($this->getListContext()->getTextSearch())."');");

		if ($this->getListContext()->isCrtViewCalendar()) {
			$this->getListContext()->setCrtViewParams(true, "redefineSearchCriterias");
			$exec->addJsCode("$('#moduleView .calendar').fullCalendar('refetchEvents');");
		} else {
			$exec->addRequests("moduleView/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/display/moduleView/");
			//reload groupPanel with result numbers
			$exec->addRequests("NoAnswer/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/countElementsInGroupPanel/");
		}
	}

	protected function doSpecificCheck($p, $exec){
		$lc = $this->getListContext();
		try {
			$lc->setSearchBar($p, $this->getWigiiExecutor(), $_POST, $this->getFilterFormGroupTree());
		} catch (ListContextException $e) {
			//echo $e->getMessage();
			$transS = ServiceProvider :: getTranslationService();
			$message = $transS->t($p, "parseErrorInTextSearch")."(" . $e->getMessage().")";
			if($e->getCode() == ListContextException::INVALID_TextFilter){
				$this->addErrorToField($message, ListContext::TextSearchField);
			} else if($e->getCode() == ListContextException::INVALID_TextAdvancedFilter){
				$this->addErrorToField($message, ListContext::TextAdvancedSearchField);
			} else if($e->getcode() == ListContextException::INVALID_TextGroupFilter){
				$this->addErrorToField($message, ListContext::TextGroupSearchField);
			} else throw $e;
		}
	}

	protected function doRenderForm($p, $exec){
		$transS = ServiceProvider::getTranslationService();

		$this->getTrm()->setState($this->getState());
		$this->getTrm()->setFormRenderer($this->getFormRenderer());

		$this->getTrm()->openForm($this->getFormId(), $this->getSubmitUrl(), $this->getTotalWidth(), $this->isDialog());

		$this->getTrm()->displayRemainingForms();

		$this->getTrm()->getCaptchaHTML($this);

		$this->getTrm()->closeForm($this->getFormId(), $this->goToNextState(), $this->getSubmitLabel(), $this->isDialog());

//		$this->getWigiiExecutor()->openAsDialogForm(
//			$exec->getIdAnswer(), $this->getTotalWidth()+$this->getCorrectionWidth(),
//			'convertTimestamps($("#filters_form___textAdvancedSearch_value_textarea")); $("form", this).submit();', $transS->t($p, "defineFiltersForElementsTitle"),
//			$transS->t($p, "ok"), $transS->t($p, "cancel"));
		$this->getWigiiExecutor()->openAsDialogForm3B(
			$exec->getIdAnswer(), $this->getTotalWidth()+$this->getCorrectionWidth(),
			'convertTimestamps($("#filters_form___textAdvancedSearch_value_textarea")); $("form", this).submit();', $transS->t($p, "defineFiltersForElementsTitle"),
			$transS->t($p, "ok"), $transS->t($p, "resetFilters"), $transS->t($p, "cancel"), null, "actOnCancelDialog('".$exec->getIdAnswer()."'); $('#workZone #searchBar #removeFiltersButton').click();");

		//add the advanced and group search wizard
		$configS = $this->getWigiiExecutor()->getConfigurationContext();
		$elS = ServiceProvider::getElementService();
		$lc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
		$html2text = new Html2text();
	    ?><div class="cm SBB" id="addFieldMenu" ><?
	    	?><div class="exit SBB">x</div><?
	    	?><div class="grayFont"><?=$transS->t($p, "as_af_dataFields");?></div><?
		    $fl = FieldListArrayImpl::createInstance(true, true);
		    $configS->getFields($p, $exec->getCrtModule(), null, $fl);
			foreach($fl->getListIterator() as $field){
				if($field->getDataType()==null) continue;
				$dxml = $field->getDataType()->getXml();
				?><div class="searchField"><?
				//display field name		
				$html2text->setHtml($transS->t($p, $field->getFieldName(), $field->getXml()));
				$translatedFieldName = $html2text->getText();
// 				$html2text->clear();
				?><div class="H fB handler" ><?
	    			echo $translatedFieldName;
	    		?></div><?
				foreach($dxml->children() as $subf){
			    	//the label is the field format to add in the input text
			    	$sqlType = $elS->dataTypeSqlType2sqlType($subf["sqlType"]);
			    	switch($sqlType) {
						case MySqlQueryBuilder::SQLTYPE_BIGINT:
						case MySqlQueryBuilder::SQLTYPE_DOUBLE:
						case MySqlQueryBuilder::SQLTYPE_DECIMAL:
						case MySqlQueryBuilder::SQLTYPE_INT:
							switch($subf->getName()){
								case "sys_date":
								case "sys_creationDate":
									$op = ">=";
					    			$val = "TIMESTAMP(YYYY-MM-DD [HH:MM:SS])";
									break;
								default:
							    	$op = "=";
							    	$val = "X";
							}
							break;
						case MySqlQueryBuilder::SQLTYPE_BOOLEAN:
					    	$op = "=";
					    	$val = true;
							break;
						case MySqlQueryBuilder::SQLTYPE_TEXT:
						case MySqlQueryBuilder::SQLTYPE_VARCHAR:
					    	$op = "LIKE";
					    	$val = '"%example%"';
							break;
						case MySqlQueryBuilder::SQLTYPE_DATE:
						case MySqlQueryBuilder::SQLTYPE_DATETIME:
					    	$op = ">=";
					    	$val = "TIMESTAMP(YYYY-MM-DD [HH:MM:SS])";
							break;
						case MySqlQueryBuilder::SQLTYPE_TIME:
					    	$op = ">=";
					    	$val = "HH:MM";
							break;
						case MySqlQueryBuilder::SQLTYPE_LONGBLOB:
						case MySqlQueryBuilder::SQLTYPE_BLOB:
					    	$op = "!=";
					    	$val = "NULL";
							break;
						default:
							throw new MySqlFacadeException('invalid SQL type', MySqlFacadeException::INVALID_ARGUMENT);
					}
			    	if($subf->getName()=="value" && ($field->getDataType()->getDataTypeName() == "Attributs" || $field->getDataType()->getDataTypeName() == "MultipleAttributs" )){
			    		?><div class="searchOptions"><?
			    		foreach($field->getXml()->xpath('attribute') as $att){
						    if((string)$att == null || (string)$att=="none") continue;
				    		?><div class="H fB"><?
				    			echo $transS->t($p, (string)$att, $att);
				    			?><label style="display:none;"><?=$field->getFieldName().".".$subf->getName()." ".$op." ".'"'.$att.'"';?></label><?
				    		?></div><?
			    		}
			    		?></div><?
			    	} else {
			    		$help = $transS->h($p, "advancedFilterHelp_".$field->getDataType()->getDataTypeName().($subf->getName()!="value" ? "_".$subf->getName() : ""));
//			    		fput($help);
//			    		fput("advancedFilterHelp_".$field->getDataType()->getDataTypeName().($subf->getName()!="value" ? "_".$subf->getName() : ""));
			    		if($help !="advancedFilterHelp_".$field->getDataType()->getDataTypeName().($subf->getName()!="value" ? "_".$subf->getName() : "")){
			    			$help = 'onmouseover="showHelp(this, \''.$help.'\',20,\'fromCenter\',0,200,0);" onmouseout="hideHelp();"';
			    		} else $help = null;
			    		?><div class="H fB" <?=(strpos($subf->getName(), "sys_")===0 ? 'style="color:#666;"' : "");?> <?=$help;?>><?
			    			echo (strpos($subf->getName(), "sys_")===0 || $subf->getName()=="value" ? $transS->t($p, $subf->getName()) : $transS->t($p, $field->getDataType()->getDataTypeName()."_".$subf->getName())); //$transS->t($p, $field->getFieldName(), $field->getXml()).($subf->getName()=="value" ? "" : " ".$transS->t($p, $field->getDataType()->getDataTypeName()."_".$subf->getName()));
			    			?><label style="display:none;"><?=$field->getFieldName().".".$subf->getName()." ".$op." ".$val;?></label><?
			    		?></div><?
			    	}
				}
				?></div><?
		    }
		    ?><div class="searchField"><?
		    ?><div class="grayFont"><?=$transS->t($p, "as_af_elementFields");?></div><?
			$fsLElement = $elS->getFieldSelectorListForElementAttributForImport();
		    foreach($fsLElement->getListIterator() as $fs){
		    	$doNotShow = false;
		    	switch($fs->getSubFieldName()) {
					case "sys_creationDate":
					case "sys_date":
				    	$sqlType = MySqlQueryBuilder::SQLTYPE_BIGINT;
				    	$op = ">=";
				    	$val = "TIMESTAMP(YYYY-MM-DD [HH:MM:SS])";
						break;
					case "sys_creationUser":
					case "sys_user":
				    	$sqlType = MySqlQueryBuilder::SQLTYPE_BIGINT;
				    	$op = "=";
				    	$val = "userId";
						break;
					case "sys_creationUsername":
					case "sys_username":
				    	$sqlType = MySqlQueryBuilder::SQLTYPE_VARCHAR;
				    	$op = "=";
				    	$val = '"example"';;
						break;
					case "state_locked":
					case "state_important1":
					case "state_important2":
					case "state_hidden":
					case "state_archived":
					case "state_deprecated":
					case "state_finalized":
					case "state_approved":
					case "state_dismissed":
					case "state_blocked":
				    	$sqlType = MySqlQueryBuilder::SQLTYPE_BOOLEAN;
				    	$op = "=";
				    	$val = true;
						break;
					default:
				    	$doNotShow = true;
		    	}
		    	if($doNotShow) continue;
		    	?><div class="H fB"><?
	    			echo $transS->t($p, $fs->getSubFieldName());
			    	?><label style="display:none;"><?=$fs->getFieldName().".".$fs->getSubFieldName()." ".$op." ".$val;?></label><?
	    		?></div><?
		    }
		    ?></div><?
		?></div><?

	    ?><div class="cm SBB" id="addGroupOperatorMenu" ><?
			?><div class="exit SBB">x</div><?
			?><div class="grayFont"><?=$transS->t($p, "as_addGroupOperator");?></div><?
			?><div class="H fB"><?
				echo $transS->t($p, "INGR");
				?><label style="display:none;">INGR(id IN (X, Y, ...))</label><?
			?></div><?
			?><div class="H fB"><?
				echo $transS->t($p, "NOTINGR");
				?><label style="display:none;">NOTINGR(id IN (X, Y, ...))</label><?
			?></div><?
			?><div class="H fB"><?
				echo $transS->t($p, "ING");
				?><label style="display:none;">ING(id IN (X, Y, ...))</label><?
			?></div><?
			?><div class="H fB"><?
				echo $transS->t($p, "NOTING");
				?><label style="display:none;">NOTING(id IN (X, Y, ...))</label><?
			?></div><?
		?></div><?
		$exec->addJsCode("
setVis('filteringBar', false);
addFieldMenuTimeout = null;" .
"$('#addFieldMenu .searchField>*:not(.handler)').hide();" .
"$('#addFieldMenu .H:not(.handler)').css('font-style', 'italic');" .
"$('#addFieldMenu .handler').click(function(){
	$('#addFieldMenu .searchField>*:not(.handler)').hide();" .
	"$('>*', $(this).parent()).show();
});
$('#filters_form___textAdvancedSearch_value_textarea').focus(function(){
	if(!$('#addFieldMenu:visible').length){
		positionElementOnDom($('#addFieldMenu'), $('#filtersDialog'), 'right', 0, false, true); /*$('#filters_form__advancedFilters_group')*/
		$('#addFieldMenu').css({'height': $('#filtersDialog').parent().css('height'), 'width': '250px', 'overflow-y': 'auto'}).show();
	}
}).blur(function(){
	/* IE and Chrome doubled the blur event, then test if the timer already exists. If so we suppress it. */
	if(typeof(addFieldMenuTimeout)=='number') clearTimeout(addFieldMenuTimeout);
	addFieldMenuTimeout = setTimeout(function(){ $('#addFieldMenu').hide(); }, 200);
});
$('#addFieldMenu div.grayFont,#addFieldMenu .searchField,#addFieldMenu .searchOptions').click(function(){
	clearTimeout(addFieldMenuTimeout);
});
$('#addFieldMenu .searchOptions div.H').css('font-style', 'italic').css('padding-left','25px');
$('#addFieldMenu div:not(.grayFont, .searchField, .searchOptions, .handler)').click(function(){
	if($(this).hasClass('exit')){
		$('#addGroupOperatorMenu').hide();
		return;
	}
	clearTimeout(addFieldMenuTimeout);
	obj = $('#filters_form___textAdvancedSearch_value_textarea');
	obj.focus();
	crtText = obj.val();
	if(crtText!=''){
		crtText = crtText+' AND ';
	}
	obj.val(crtText+$('label', this).text());
});
addGroupOperatorMenuTimeout = null;
$('#filters_form___textGroupSearch_value_text').focus(function(){
	if(!$('#addGroupOperatorMenu:visible').length){
		positionElementOnDom($('#addGroupOperatorMenu'), $('#filtersDialog'), 'right', 120, false, true);
		/* correction for the top postition */ 
		if($('#filtersDialog').height()!==undefined) $('#addGroupOperatorMenu').css('top', $('#filtersDialog').height()/3*2);
		$('#addGroupOperatorMenu').css('width', 250).show();
	}
}).blur(function(){
	/* IE and Chrome doubled the blur event, then test if the timer already exists. If so we suppress it. */
	if(typeof(addGroupOperatorMenuTimeout)=='number') clearTimeout(addGroupOperatorMenuTimeout);
	addGroupOperatorMenuTimeout = setTimeout(function(){ $('#addGroupOperatorMenu').hide(); }, 200);
});
$('#addGroupOperatorMenu div.grayFont,#addGroupOperatorMenu .searchField,#addGroupOperatorMenu .searchOptions').click(function(){
	clearTimeout(addGroupOperatorMenuTimeout);
});
$('#addGroupOperatorMenu div:not(.grayFont, .searchField, .searchOptions)').click(function(){
	if($(this).hasClass('exit')){
		$('#addGroupOperatorMenu').hide();
		return;
	}
	clearTimeout(addGroupOperatorMenuTimeout);
	obj = $('#filters_form___textGroupSearch_value_text');
	obj.focus();
	crtText = obj.val();
	if(crtText!=''){
		crtText = crtText+' AND ';
	}
	obj.val(crtText+$('label', this).text());
});

$('#filters_form select:has(option:selected)').addClass('filled');
$('#filters_form input[checked]').addClass('filled');
$('#filters_form textarea:not(:empty)').addClass('filled');
$('#filters_form input:not([value=\"\"]):not([type=radio]):not([type=checkbox])').addClass('filled');
$('#filters_form .value:not(.fieldGroup):has(.filled)').addClass('filled');

$('#filters_form :input').bind('click blur', function(){
	tempSelect = $(this);
	setTimeout(function(){
		if(tempSelect.val()!=null){
			$(tempSelect).closest('.value').find('.filled').removeClass('filled');
			$(tempSelect).addClass('filled');
			$(tempSelect).closest('.value').find('div:has(.filled)').addClass('filled');
		} else {
			$(tempSelect).closest('.value').find('.filled').removeClass('filled');
		}
	}, 100);
});
$('#filters_form select.chosen').on('select2:select', function(){ 
	$(this).addClass('filled').find('~ span.select2').addClass('filled');
}).on('select2:unselect', function(){ 
	if(!$(this).select2('val')) $(this).removeClass('filled').find('~ span.select2').removeClass('filled');
});

$('#filtersDialog').parent().append($('#addFieldMenu')).append($('#addGroupOperatorMenu'));				
");


		$this->getTrm()->addJsCodeAfterFormIsShown($this->getFormId());
	}
}



