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
 * Module view template displaying Wigii NCD components
 * Created by CWE, on 08.02.2018
 */

//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."start elementWncd.tpl.php"] = microtime(true);
$this->executionSink()->publishStartOperation("TEMPLATE elementWncd.tpl.php");

if(!isset($groupAS)) $groupAS = ServiceProvider::getGroupAdminService();
if(!isset($sessAS)) $sessAS = ServiceProvider::getSessionAdminService();
if(!isset($elS)) $elS = ServiceProvider::getElementService();
if(!isset($transS)) $transS = ServiceProvider::getTranslationService();
if(!isset($exec)) $exec = ServiceProvider::getExecutionService();
if(!isset($configS)) $configS= $this->getConfigurationContext();

$lc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
if($lc->getGroupPList()->count()==1) $crtGroupP = reset($lc->getGroupPList()->getListIterator());
else $crtGroupP = null;
$crtWigiiNamespace = $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl();
$crtModule = $exec->getCrtModule()->getModuleUrl();

$lc->setDesiredPageNumber(1);
$pageSize = (int)$configS->getParameter($p, $exec->getCrtModule(), "listPageSize");
if($pageSize == null) $pageSize = 250; //default value
$lc->setPageSize($pageSize);

// Hide searchbox when parameters is equal to 1
if($configS->getParameter($p, $exec->getCrtModule(), "hide_searchBar") == "1"){
    $exec->addJsCode("$('nav #searchField').hide();");
} else {
    $exec->addJsCode("$('nav #searchField').show();");
}

/**
 * URL portal zone
 * if a portal is set on the group, then display the content of the website
 */
$url = null;
if($configS->getParameter($p, $exec->getCrtModule(), "Group_enablePortal") == "1" && $crtGroupP){
    $crtGroupP = $groupAS->getGroup($p, $crtGroupP->getId());
    $url = $this->includeGroupPortal($crtGroupP, $p, $exec, $transS, $configS);
}


if($url){
    $exec->addJsCode("$('nav #searchField').hide();");
    $exec->addJsCode("$('nav #searchBar').hide();");
}

/**
 * List View zone
 */
if(!$url){ //displays list only if no url

    $lastConfigKey = $sessAS->getData($this, "elementListLastConfigKey");
    $currentConfigKey = $this->getCurrentConfigContextKey($p, $exec);
    $sessAS->storeData($this, "elementListLastConfigKey", $currentConfigKey);
    $configChanged = ($lastConfigKey!=$currentConfigKey);
    
?><div class="toolBar SB">
    <div id="indicators" style="float: left;"><?
        //$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."start indicator"] = microtime(true);
        $this->debugLogger()->write("start indicator");
        $this->includeTemplateIndicators($p, $exec);
        ?></div>
    <div id="searchBar" style="display:none"><?
    include("moduleToolsBar.bsp.php");?>
	</div><?
	//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."start searchBar toolbar"] = microtime(true);
	$this->executionSink()->log("start searchBar toolbar");

	//refreshes module help icon if config changed
	$this->refreshModuleHelpAnchor($p,$exec);
	
	// Caches in session sortBy and groupBy
	$sortByOptions = $this->prepareSortByOptions($lc, $currentConfigKey, $p, $exec, $transS, $configS, $sessAS);
	$groupByOptions = $this->prepareGroupyByOptions($lc, $currentConfigKey, $p, $exec, $transS, $configS, $sessAS);
	
	//add element
	$this->includeAddElementButton($crtGroupP, $p, $exec, $transS, $configS);

	//switchView
	$this->includeSwitchViewButton($lc, $p, $exec, $transS, $configS);

    // Run menu
    $this->includeRunMenu($p, $exec, $transS, $configS);

	//duplicates filter is on
	if($lc->isGroupByOnlyDuplicates()){
		?><div class="removeDuplicatesFilter ui-corner-all  R White" <?
			?> onclick="update('moduleView/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/changeGroupByKey/reset');" <?
			?>><font style="float:left;"><?=$transS->h($p, "removeDuplicatesFilter");?></font> <div class="H" style="font-size:11px;float:right;padding:3px;margin-top:-5px;margin-right:-5px;margin-left:2px;cursor:pointer;">x</div></div><?
		$exec->addJsCode("$('#moduleView>div.toolBar').addClass('Red');");
	}

?></div><?
?><div class="clear"></div><?
/** INDICATORS */
?><div class="clear"></div><?

?><div class="dataZone wncd"><?
	$wncdXml = $configS->ma($p, $exec->getCrtModule(), Activity::createInstance($lc->getCrtViewActivityName()));
	// loads wncd template if defined
	$wncdFile = (string)$wncdXml['wncdTemplate'];
	// reads wncd template from wncdTemplate.tpl file into configuration folder
	if(!empty($wncdFile) && file_exists(CLIENT_CONFIG_PATH.$wncdFile.".tpl")) {
		readfile(CLIENT_CONFIG_PATH.$wncdFile.".tpl");
	}
	// loads wncd model
	$wncdModel = $this->getAllElementsInWncdView($p, $exec, $lc, false, false, 1, $configChanged)/*reset the groupBy if config change*/;
	// refreshes group by and sort by menus
	$this->includeGroupByMenu($lc, $groupByOptions, $p, $exec, $transS, $configS);
	$this->includeSortByMenu($lc, $sortByOptions, $p, $exec, $transS, $configS);
?></div><?

// sends wncd model to client
$wncdModel= json_encode($wncdModel,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK|JSON_UNESCAPED_SLASHES);
if($wncdModel=== false) throw new ServiceException('JSON encode error '.json_last_error().' '.json_last_error_msg(), ServiceException::UNEXPECTED_ERROR);
if (!defined("WEB_WNCD")) throw new AuthorizationServiceException("wncd views are not supported in this Wigii instance.",AuthorizationServiceException::UNSUPPORTED_OPERATION);
$exec->addJsCode('wncd.program.context.'.$lc->getCrtView().'='.$wncdModel);

// loads wncd view
$exec->addJsCode('wigii().context.crtView="'.$lc->getCrtView().'"');
// resets wncd container
$exec->addJsCode('wigii().getWncdContainer(wncd).reset()');

// loads wncd module if defined
$wncdFile = (string)$wncdXml['wncdModule'];

// reads wncd src code from wncdModule.ncd file into configuration folder
if(!empty($wncdFile) && file_exists(CLIENT_CONFIG_PATH.$wncdFile.".ncd")) {
	$wncdSrc = file_get_contents(CLIENT_CONFIG_PATH.$wncdFile.".ncd");
}
else $wncdSrc = null;
// sends wncd view to client
if(!empty($wncdSrc)) $exec->addJsCode('$("#moduleView div.dataZone.wncd").wncd("run").program('.$wncdSrc.');');

// runs wncd expression if defined
$wncdSrc= (string)$wncdXml['wncd'];
if(!empty($wncdSrc)) $exec->addJsCode('$("#moduleView div.dataZone.wncd").wncd("run").program(scripte(function(){'.$wncdSrc.'}));');

// loads other js services
$this->bindJsServicesOnModuleView($p,$exec);

/**
 * Cover page zone
 */
if($configS->getParameter($p, $exec->getCrtModule(), "Group_enablePortal") == "1" && $crtGroupP){
    $this->includeCoverPage($crtGroupP, $p, $exec, $transS, $configS);
}

} //display list only if no url

$this->executionSink()->publishEndOperation("TEMPLATE elementWncd.tpl.php");

//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."end elementWncd.tpl.php"] = microtime(true);