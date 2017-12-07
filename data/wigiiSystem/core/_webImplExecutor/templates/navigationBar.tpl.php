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
 * Created on 29 June 2011 by LWR
 * @deprecated since 4.602 28.11.2017, replaced by navigationBar.bsp
 */
if(!isset($exec)) $exec = ServiceProvider::getExecutionService();
if(!isset($authS)) $authS = ServiceProvider::getAuthenticationService();
if(!isset($p)) $p = $authS->getMainPrincipal();
if(!isset($transS)) $transS = ServiceProvider::getTranslationService();
if(!isset($config)) $config = $this->getConfigurationContext();
if(!isset($sessAS)) $sessAS = ServiceProvider::getSessionAdminService();
//if(!isset($moduleAS)) $moduleAS = ServiceProvider::getModuleAdminService();
if(!isset($userAS)) $userAS = ServiceProvider::getUserAdminService();

//navigation bar is called only when principal is logued in

if($p->isPlayingRole()){
	$realUser = $p->getRealUser();
	$wigiiNamespace = $realUser->getWigiiNamespace()->getWigiiNamespaceName();
} else {
	$realUser = $p->getAttachedUser();
	$wigiiNamespace = $realUser->getWigiiNamespace()->getWigiiNamespaceName();
}
$roleList = $p->getRoleListener();
//add the real user itself in the list, to ensure to appear if he has some admin rights
$roleList->addUser($p->getRealUser());
$defaultWigiiNamespace = $roleList->getDefaultWigiiNamespace();
$adminRoleIds = $roleList->getAdminRoleIds();
$calculatedRolesIds = $roleList->getCalculatedRoleIds();

if($exec->getCrtModule()->isAdminModule()){

	//back to calculated role in namespace, except if superadmin
	$backUserId = null;
	if(!$p->isWigiiNamespaceCreator()){
		$backUserId = $roleList->getCalculatedRoleId($exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl());
	}
	if($backUserId==null){
		$backUserId = reset($roleList->getCalculatedRoleIds());
	}
    $backUser = $roleList->getUser($backUserId);
}

if(!isset($configS)) $configS = $this->getConfigurationContext();
$companyColor = $configS->getParameter($p, null, "companyColor");
$rCompanyColor = $configS->getParameter($p, null, "companyReverseColor");
if(!$companyColor) $companyColor = "#3E4552";
if(!$rCompanyColor) $rCompanyColor = "#fff";

?>
<div id="navigationBar" style="background-color:<?=$companyColor;?>;color:<?=$rCompanyColor;?>;float:left; width:100%;" >
<?php
include(TEMPLATE_PATH . "navigationUserMenu.tpl.php");

//add navigation in different roles
?><div class="navL SBB notHome"></div><?
?><div id="NMContainer"><?
?><ul id="navigateMenu" class="notHome sf-menu sf-navbar" style="" ><?

	if($exec->getCrtModule()->isAdminModule()){
		/*$modules = $p->getModuleAccess();
		unset($modules[Module::ADMIN_MODULE]);
		if($modules[Module::HELP_MODULE]){
			$help = $modules[Module::HELP_MODULE];
			unset($modules[Module::HELP_MODULE]);
			$modules[Module::HELP_MODULE] = $help;
		}

		$roleId = $p->getUserId();
		$crtWigiiNamespace = $p->getWigiiNamespace()->getWigiiNamespaceUrl();
		$crtWigiiNamespace=str_replace('%20',' ',$crtWigiiNamespace);
		$moduleReorder = reorderTabBasedOnKeyPriority($modules, (string)$config->getParameter($p, null, "prioritizeModuleInHomePage"), true);
		foreach($moduleReorder as $moduleName=>$module){
			?><li<?=($roleId == $p->getUserId() && $p->getValueInRoleContext("lastWorkingModule") == $moduleName ? ' class="selected" ': '');?>><?
				?><a href="#<?=str_replace(' ', '%20', $crtWigiiNamespace)."/".Module::ADMIN_MODULE."/".$moduleName;?>" <?
					?>onclick="<?=$exec->getUpdateJsCode($p->getRealUserId(), $p->getUserId(), $exec->getCrtWigiiNamespace(), "'+crtModuleName+'", 'NoAnswer', 'adminNavigate', "navigate/user/$roleId/'+crtRoleId+'/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/$moduleName", true, true);?> return false;"<?
				?>><?
				$customLabel = $transS->t($p, "homePage_".$crtWigiiNamespace."_".$moduleName);
				if($customLabel == "homePage_".$crtWigiiNamespace."_".$moduleName) echo $transS->t($p, $moduleName);
				else echo $customLabel." (".$transS->t($p, $moduleName).")";
				?></a><?
			?></li><?
		}//*/
		?>
		<li class="selected"></li>
		<?
	} else {

		$crtWigiiNamespace = $defaultWigiiNamespace;
		$crtWigiiNamespace=str_replace('%20',' ',$crtWigiiNamespace);
		if($roleList->getDefaultWigiiNamespaceModules()){
			$moduleReorder = reorderTabBasedOnKeyPriority($roleList->getDefaultWigiiNamespaceModules(), (string)$config->getParameter($p, null, "prioritizeModuleInHomePage"), true);
			foreach($moduleReorder as $module=>$roleId){
				?><li<?=($roleId == $p->getUserId() && $exec->getCrtModule()->getModuleName() == $module ? ' class="selected" ': '');?>><?
					?><a href="#<?=str_replace(' ', '%20', $crtWigiiNamespace)."/".$module;?>" <?
						?>onclick="if(!ctrlPressed) { <?=$exec->getUpdateJsCode($p->getRealUser(), $roleId, $crtWigiiNamespace, $module, "NoAnswer", "userNavigate", "navigate/user/$roleId/'+crtRoleId+'/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'", true, true);?> }"<?
					?>><?
					$customLabel = $transS->t($p, "homePage_".$crtWigiiNamespace."_".$module);
					if($customLabel == "homePage_".$crtWigiiNamespace."_".$module) echo $transS->t($p, $module);
					else echo $customLabel; //." (".$transS->t($p, $module).")";
					?></a><?
				?></li><?
			}
		}
		if($roleList->getOtherWigiiNamespaces()){
			$wigiiNamespaceReorder = reorderTabBasedOnKeyPriority($roleList->getOtherWigiiNamespaces(), (string)$config->getParameter($p, null, "prioritizeWigiiNamespaceInHomePage"), true);
			foreach($wigiiNamespaceReorder as $crtWigiiNamespace=>$subMenu){
				$crtWigiiNamespace=str_replace('%20',' ',$crtWigiiNamespace);
				if($subMenu){
					$moduleReorder = reorderTabBasedOnKeyPriority($subMenu, (string)$config->getParameter($p, null, "prioritizeModuleInHomePage"), true);
					if(count($moduleReorder)==1){
						$roleId = reset($moduleReorder);
						$module = key($moduleReorder);
						?><li<?=($roleId == $p->getUserId() && $exec->getCrtModule()->getModuleName() == $module ? ' class="selected" ': '');?>><?
							?><a href="#<?=str_replace(' ', '%20', $crtWigiiNamespace)."/".$module;?>" <?
								?>onclick="if(!ctrlPressed) { <?=$exec->getUpdateJsCode($p->getRealUser(), $roleId, $crtWigiiNamespace, $module, "NoAnswer", "userNavigate", "navigate/user/$roleId/'+crtRoleId+'/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'", true, true);?> }"<?
							?>><? echo $crtWigiiNamespace;?></a><?
						?></li><?
					} else {
						?><li class="with-ul"><?
							?><a href="#<?=str_replace(' ', '%20', $crtWigiiNamespace);?>" onclick="return false;" class="sf-with-ul"<?
							?>><? echo $crtWigiiNamespace;?><span class="sf-sub-indicator"></span></a><?
							?><ul><?
							foreach($moduleReorder as $module=>$roleId){
								if(strpos($module, "[title]")===0) continue;
								?><li<?=($roleId == $p->getUserId() && $exec->getCrtModule()->getModuleName() == $module ? ' class="selected" ': '');?>><?
									?><a href="#<?=str_replace(' ', '%20', $crtWigiiNamespace)."/".$module;?>" <?
										?>onclick="if(!ctrlPressed) { <?=$exec->getUpdateJsCode($p->getRealUser(), $roleId, $crtWigiiNamespace, $module, "NoAnswer", "userNavigate", "navigate/user/$roleId/'+crtRoleId+'/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'", true, true);?> }"<?
									?>><?
									$customLabel = $transS->t($p, "homePage_".$crtWigiiNamespace."_".$module);
									if($customLabel == "homePage_".$crtWigiiNamespace."_".$module) echo $transS->t($p, $module);
									else echo $customLabel; //." (".$transS->t($p, $module).")";
									?></a><?
								?></li><?
							}
							?></ul><?
						?></li><?
					}
				}
			}
		}

	}

?></ul><?
?></div><?
?><div class="navR SBB notHome"></div><?

$exec->addJsCode("refreshNavigateMenu(); setListenerToNavigateMenu();");
if($exec->getCrtModule()->isAdminModule()){
	$exec->addJsCode("setNavigationBarInAdminState();");
} else {
	$exec->addJsCode("setNavigationBarNotInHomeState(".$config->getParameter($p, $exec->getCrtModule(), "FeedbackOnSystem_enable").");");
}
$exec->addJsCode("resize_navigateMenu();");
?>
</div>
