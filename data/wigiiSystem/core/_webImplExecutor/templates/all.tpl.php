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
 * Created on 29 June 2011
 * by LWR
 */
//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."start all.tpl.php"] = microtime(true);
$this->executionSink()->publishStartOperation("TEMPLATE all.tpl.php");

if(!isset($authS)) $authS = ServiceProvider::getAuthenticationService();

/**
 * company banner
 */
$this->includeTemplateCompanyBanner($p, $exec);

if(!$authS->isMainPrincipalMinimal()){

	if(!$exec->getCrtModule()->isHomeModule() && !$p->getModuleAccess($exec->getCrtModule())){
		throw new ServiceException("No module access for the module: ".$exec->getCrtModule()->getModuleName()." for user: ".$p->getUsername(), ServiceException::FORBIDDEN);
	}

	/**
	 * Navigation bar
	 */
	if(!isset($configS)) $configS = $this->getConfigurationContext();
	$companyColor = $configS->getParameter($p, null, "companyColor");
	$rCompanyColor = $configS->getParameter($p, null, "companyReverseColor");
	if(!$companyColor) $companyColor = "#3E4552";
	if(!$rCompanyColor) $rCompanyColor = "#fff";
	?><div id="navigationBar" style="background-color:<?=$companyColor;?>;color:<?=$rCompanyColor;?>;float:left; width:100%;" ><?
		$this->includeTemplateNavigation($p, $exec);
	?></div><?

	/**
	 * WorkZone
	 */
	?><div id="workZone" ><?
		if(!$exec->getIsUpdating()){
			?><div id="loadingBar" style="background-color:#fff;font-size:large;position:absolute; top:40%; left:45%; z-index:999999; padding:5px 10px;"><?=ServiceProvider::getTranslationService()->t($p, "wigiiBusyLoading");?>&nbsp;&nbsp;&nbsp;<img src="<?=SITE_ROOT_forFileUrl;?>images/gui/busyBlue.gif" style="vertical-align:middle;"/></br>
			<span style="font-size:small;"><?=ServiceProvider::getTranslationService()->t($p, "wigiiNotLoadedMessage");?></span>
			</div><?
		}
		if($exec->getCrtModule()->isAdminModule()){
			$this->includeTemplateAdminWorkZone($p, $exec);
		} else {
			$this->includeTemplateWorkZone($p, $exec);
		}
	?></div><?

} else {
	?><div id="loginPage" ><?
		$this->includeTemplateLogin($p, $exec);
	?></div><?
}

$this->includeTemplateFooterBar($p, $exec);

//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."end all.tpl.php"] = microtime(true);
$this->executionSink()->publishEndOperation("TEMPLATE all.tpl.php");


