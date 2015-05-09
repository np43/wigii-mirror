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

if($exec->getIdAnswer()!='moduleView' && $exec->getIsUpdating()){ //!$exec->getIsUpdating()){
	//the dicision is made that when we select multiple groups, that means we select the group_0
	//so the cache is about the group_0
	//the paramter 1 can contain additional info, if comming from groupSelectorPanel: as selectGroupAndChildren, or selectGroup
	//$exec->addJsCode($exec->getCurrentUpdateJsCode($p, 'moduleView', 'groupSelectorPanel', "groupSelectorPanel/".($exec->getCrtParameters(1)=="selectGroupAndChildren" || $lc->getSearchBar() ? "selectGroupAndChildren" : "selectGroup")."/".(count($selectedGroupIds)>1 ? "0" : implode(",",$selectedGroupIds))));
	$exec->addRequests("moduleView/". $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/groupSelectorPanel/".($exec->getCrtParameters(1)=="selectGroupAndChildren" || $lc->getSearchBar() ? "selectGroupAndChildren" : "selectGroup")."/".(count($selectedGroupIds)>1 ? "0" : implode(",",$selectedGroupIds)));
} else {

	if($configS->getParameter($p, null, "preventFolderContentCaching") !="1"){
		//the parameter 1 can contain additional info, if coming from groupSelectorPanel: as selectGroupAndChildren, or selectGroup
		$cachekey = $exec->cacheAnswer($p, ($exec->getIdAnswer() ? $exec->getIdAnswer() : 'moduleView'), 'groupSelectorPanel', "groupSelectorPanel/".($exec->getCrtParameters(1)=="selectGroupAndChildren" ? "selectGroupAndChildren" : "selectGroup")."/".(count($selectedGroupIds)>1 ? "0" : implode(",",$selectedGroupIds)));
		// informs navigation cache of module view cache key
		$exec->addJsCode("setModuleViewKeyCacheForNavigate('".$cachekey."')");
	}

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
}

//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."end moduleView.tpl.php"] = microtime(true);
$this->executionSink()->publishEndOperation("TEMPLATE moduleView.tpl.php");
