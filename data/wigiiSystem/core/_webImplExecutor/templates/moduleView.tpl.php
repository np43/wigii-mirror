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

/***
 * Created by LWR, on 21 July 2011
 */
//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."start moduleView.tpl.php"] = microtime(true);
$this->executionSink()->publishStartOperation("TEMPLATE moduleView.tpl.php");

if(!isset($transS)) $transS = ServiceProvider::getTranslationService();
if(!isset($exec)) $exec = ServiceProvider::getExecutionService();
if(!isset($configS)) $configS = $this->getConfigurationContext();

$lc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
//if no listContext, then take the ConfigurationContext
if($lc->getGroupPList() == null || $lc->getGroupPList()->isEmpty()){
	$lc->setGroupPList($this->getConfigurationContext()->getGroupPList($p, $exec->getCrtModule()), false);
}
$selectedGroupIds = $lc->getGroupPList()->getIds();

//the parameter 1 can contain additional info, if coming from groupSelectorPanel: as selectGroupAndChildren, or selectGroup
if($exec->getCrtParameters(1)=="selectGroupAndChildren") $groupSelectorPanelParam = "selectGroupAndChildren";
elseif($exec->getCrtParameters(1)=="selectGroup") $groupSelectorPanelParam = "selectGroup";
// in case of filtering (search), if param is not passed on the URL, then takes ListContext->doesGroupListIncludeChildren
elseif($lc->doesGroupListIncludeChildren()) $groupSelectorPanelParam = "selectGroupAndChildren";
else $groupSelectorPanelParam = "selectGroup";

if($exec->getIdAnswer()!='moduleView' && $exec->getIsUpdating()){ //!$exec->getIsUpdating()){
	//the decision is made that when we select multiple groups, that means we select the group_0
	//so the cache is about the group_0
	$exec->addRequests("moduleView/". $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/groupSelectorPanel/".$groupSelectorPanelParam."/".(count($selectedGroupIds)>1 ? "0" : implode(",",$selectedGroupIds)));
} else {

	if($configS->getParameter($p, $exec->getCrtModule(), "preventFolderContentCaching") !="1"){
		$cachekey = $exec->cacheAnswer($p, ($exec->getIdAnswer() ? $exec->getIdAnswer() : 'moduleView'), 'groupSelectorPanel', "groupSelectorPanel/".$groupSelectorPanelParam."/".(count($selectedGroupIds)>1 ? "0" : implode(",",$selectedGroupIds)));
		// informs navigation cache of module view cache key
		$exec->addJsCode("setModuleViewKeyCacheForNavigate('".$cachekey."')");
	}

	$this->manageListViewDockedSize();
	
	if($lc->getGroupPList()==null){
		//if no group selected, then diplay nothing in the moduleView
		echo($transS->t($p, "noGroupSelected"));
	} else {
		switch($exec->getCrtModule()->getModuleName()){
			default:
				//$exec->addJsCode(" moduleView_setHeight(); ");
				include(TEMPLATE_PATH . $lc->getCrtTemplate());
		}
	}

	//if more than one, then we don't select groups, because we are not diplaying the content
	if($selectedGroupIds && count($selectedGroupIds)==1) $exec->addJsCode("selectGroupInGroupPanel(".implode(", ", $selectedGroupIds).");");
	else $exec->addJsCode("unselectGroups('#groupPanel'); resize_elementList();");
	
	// Medair (CWE) 05.12.2017 manage config parameters CSVImport_enableExp or CSVUpdate_enableExp to enable or disable CSV import or update menus
	$enableCSVMenu = (string)$configS->getParameter($p, $exec->getCrtModule(), 'CSVImport_enableExp');
	$enableCSVMenu = $this->evaluateConfigParameter($p,$exec,$enableCSVMenu);
	if($enableCSVMenu !== '0') {
	    $exec->addJsCode('$("#userMenuImport").removeClass("disabled").find("> a").css("pointer-events","")');
	}
	else {
	    $exec->addJsCode('$("#userMenuImport").addClass("disabled").find("> a").css("pointer-events","none")');
	}
	$enableCSVMenu = (string)$configS->getParameter($p, $exec->getCrtModule(), 'CSVUpdate_enableExp');
	$enableCSVMenu = $this->evaluateConfigParameter($p,$exec,$enableCSVMenu);
	if($enableCSVMenu !== '0') {
	    $exec->addJsCode('$("#userMenuUpdate").removeClass("disabled").find("> a").css("pointer-events","")');
	}
	else {	    
	    $exec->addJsCode('$("#userMenuUpdate").addClass("disabled").find("> a").css("pointer-events","none")');
	}
}
//Move the searchBar in the navigation bar
$exec->addJsCode(
	'if($(".toolBar #searchBar").length){
         var toolBar = null;
         $("#navToolBar #searchBar").remove();
        toolBar = $(".toolBar #searchBar").clone(true,true);
        toolBar.appendTo("#navToolBar");
        $(".toolBar #searchBar").remove();
        $("#searchBar").show();
        $("#navToolBar .toolbarBox").css(\'width\',\'\');
    }');

//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."end moduleView.tpl.php"] = microtime(true);
$this->executionSink()->publishEndOperation("TEMPLATE moduleView.tpl.php");
