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

/***
 * Created by LWR, on 21 July 2011
 */

//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."start groupSelectorPanel.tpl.php"] = microtime(true);
$this->executionSink()->publishStartOperation("TEMPLATE groupSelectorPanel.tpl.php");

if(!isset($groupS)) $groupS = ServiceProvider::getGroupAdminService();
if(!isset($elS)) $elS = ServiceProvider::getElementService();
if(!isset($transS)) $transS = ServiceProvider::getTranslationService();
if(!isset($exec)) $exec = ServiceProvider::getExecutionService();
if(!isset($configS)) $configS= $this->getConfigurationContext();

$lc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
//if no listContext, then take the ConfigurationContext
if($lc->getGroupPList() == null || $lc->getGroupPList()->isEmpty()){
	$lc->setGroupPList($this->getConfigurationContext()->getGroupPList($p, $exec->getCrtModule()), false);
}

if($exec->getIdAnswer()!='groupPanel' && $exec->getIsUpdating()){ //!$exec->getIsUpdating()){
	//$exec->addJsCode($exec->getCurrentUpdateJsCode($p, 'groupPanel', 'groupPanel', 'display/groupPanel'.($lc->getSearchBar() ? "/count" : "")));
	$exec->addRequests("groupPanel/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . '/display/groupPanel'.($lc->getSearchBar() ? "/count" : ""));
} else {

	$exec->cacheCrtAnswer($p, 'groupPanel');

	$selectedGroupIds = $lc->getGroupPList()->getIds();

	// gets trashbin
	$trashbinID = (string)$configS->getParameter($p, $exec->getCrtModule(), "trashBinGroup");
	if(empty($trashbinID)) $trashbinID = null;
	
	// gets Element_beforeDeleteExp
	$beforeDeleteExp = (string)$configS->getParameter($p, $exec->getCrtModule(), 'Element_beforeDeleteExp');
	
	/**
	 * group panel context menu
	 */
	?><div class="cm SBB"><?
		if($p->getRootGroupCreator($exec->getCrtModule())==null){
			$protectLevel1 = "level1";
		} else {
			$protectLevel1 = "";
		}
		?><div id="cm_exit" class="exit SBB">x</div><?
		?><div id="cm_add" class="write H fB"><?=$transS->t($p, "addElement");?>...</div><?
		if(false) { ?><div id="cm_select" class="H fB"><?=$transS->t($p, "cm_openGroup");?></div><? } //not necessary
		?><div id="cm_renameGroup" class="admin H fB <?=$protectLevel1;?>"><?=$transS->t($p, "cm_renameGroup");?>...</div><?
		?><div id="cm_createSubGroup" class="admin H fB"><?=$transS->t($p, "cm_createSubGroup");?>...</div><?
		?><div id="cm_copyGroup" class="admin H fB"><?=$transS->t($p, "cm_copyGroup");?>...</div><?
		// empty group is possible only if there is a trashbin until proper recursive deletion of files is coded (ticket 12796)
		// empty group is possible as soon as write right are on folder (in that case, deletes only content and not subfolders)
		// if enableDeleteOnlyForAdmin then empty group is possible only for admin
		// if Element_beforeDeleteExp is defined in configuration, then empty group is not authorized, except if explicitely set to 1
		if($trashbinID && ($beforeDeleteExp==null || $beforeDeleteExp==='1')) {
		?><div id="cm_emptyGroup" class="<?=($configS->getParameter($p, $exec->getCrtModule(), 'enableDeleteOnlyForAdmin')=='1'?'admin':'write')?> H fB"><?=$transS->t($p, "cm_emptyGroup");?>...</div><?
		}
		?><div id="cm_deleteGroup" class="admin H fB <?=$protectLevel1;?>"><?=$transS->t($p, "cm_deleteGroup");?>...</div><?
		?><div id="cm_findDuplicatesIn" class="H fB"><?=$transS->t($p, "findDuplicatesIn");?>...</div><?
		?><div id="cm_portal" class="write H fB"><?=$transS->t($p, "cm_portal");?>...</div><?
		?><div id="cm_htmlContent" class="write H fB"><?=$transS->t($p, "cm_htmlContent");?>...</div><?
		?><div id="cm_subscription" class="admin H fB"><?=$transS->t($p, "cm_subscription");?>...</div><?
		?><div id="cm_emailNotification" class="admin H fB"><?=$transS->t($p, "cm_emailNotification");?>...</div><?
		if(true) { ?><div id="cm_xmlPublish" class="admin H fB"><?=$transS->t($p, "cm_xmlPublish");?>...</div><? } //not necessary
		?><div id="cm_groupDetail" class="admin H fB"><?=$transS->t($p, "cm_groupDetail");?>...</div><?
	?></div><?

	?><div class="collapse SB grayFont">&laquo;</div><?

	//get all groups
	$groupPTree = GroupPTreeGroupPanelImpl::createInstance($p, $exec, $configS->getParameter($p, $exec->getCrtModule(), "nbOfLevelToExpandOnInit"), true);
	// injects trashbin	
	$groupPTree->setTrashBinGroup($trashbinID);
	$groupPTree->start($p, $exec);
	$groupS->getAllGroups($p, $exec->getCrtModule(), $groupPTree);
	$groupPTree->end($p, $exec);

	/**
	 * group panel toolbar
	 */
	if($configS->getParameter($p, $exec->getCrtModule(), "Notification_enable") =="1" && $configS->getParameter($p, $exec->getCrtModule(), "Notification_enableDynamicSubscription") =="1"){
		?><div class="keepNotify SB"><?
			?><span id="keepNotifyButton" class="L H"><?=$transS->t($p, "keepNotifiedButton");?></span><?
		?></div><?
	}

	if($selectedGroupIds && count($selectedGroupIds)==1) $exec->addJsCode("selectGroupInGroupPanel(".implode(", ", $selectedGroupIds).");");
	else $exec->addJsCode("unselectGroups('#groupPanel');");
	$exec->addJsCode("" .
		"$('#groupPanel .found, #groupPanel .hidden, #groupPanel .empty').removeClass('found').removeClass('hidden').removeClass('empty');" .
		"$('#groupPanel .nb').remove();" .
		"");
}

//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."end groupSelectorPanel.tpl.php"] = microtime(true);
$this->executionSink()->publishEndOperation("TEMPLATE groupSelectorPanel.tpl.php");