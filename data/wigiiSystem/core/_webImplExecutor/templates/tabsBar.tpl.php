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
/*
 * Created on 15 oct. 09
 * by LWR
 */
if(!isset($transS)) $transS = ServiceProvider::getTranslationService();
if(!isset($authS)) $authS = ServiceProvider::getAuthenticationService();
if(!isset($p)) $p = $authS->getMainPrincipal();
if(!isset($exec)) $exec = ServiceProvider::getExecutionService();

//if($exec->getCrtModule()->isAdminModule()) echo '<div class="adminFont" style="font-size:18px; font-weight:bold; margin:0px 0px 10px 5px;">'.$transS->t($p, "adminModuleTitle")."</div>";

?><ul><?

	$modules = $p->getModuleAccess();
	unset($modules[Module::ADMIN_MODULE]);
	$helpModule = false;
	if($modules[Module::HELP_MODULE]){
		unset($modules[Module::HELP_MODULE]);
		$hasHelpModule = true;
	}
	$modules = array_keys($modules);
	sort($modules);
	if($hasHelpModule){
		$modules[] = Module::HELP_MODULE;
	}
	
	foreach ($modules as $module){
		?><li <?
			echo "class=' ui-corner-tr ui-corner-tl ";
			if($exec->getCrtModule()->getModuleName() == $module || ($exec->getCrtModule()->isAdminModule() && $this->getAdminContext($p)->getWorkingModule()->getModuleName() == $module)){
				echo " activeTab ";
				if($exec->getCrtModule()->isAdminModule()) echo " adminBackground adminReverseFont ";
				else echo " lightGray activeTabF";
				//this peace of code is to help good translation in admin module (especialy if the lastModule of the user was admin)
				if($exec->getCrtModule()->isAdminModule()){
					$transS->setExecutionModule($this->getAdminContext($p)->getWorkingModule());
				} else {
					$transS->setExecutionModule($exec->getCrtModule());
				}
			} else{
				echo " lightGray tabF";
			}
			echo " ' ";
			if($exec->getCrtModule()->isAdminModule()){
				?>onclick="updateIfNoOpenItems('NoAnswer/<?=$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl();?>/<?=Module::ADMIN_MODULE;?>/switchModuleInAdminTo/<?=$module;?>')"><?
			} else {
				if($module == Module::HELP_MODULE){
					?>onclick="if(!event.ctrlKey){ window.open('<?=SITE_ROOT.$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl();?>/<?=$module;?>/display/detachModule', '<?=$transS->h($p, $module);?>', 'status=1, height=600, width=950, scrollbars=1'); } else { updateIfNoOpenItems('mainDiv/<?=$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl();?>/<?=$module;?>/display/all'); }"<?
				} else {
					?>onclick="if(event.ctrlKey){ window.open('<?=SITE_ROOT.$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl();?>/<?=$module;?>/display/detachModule', '<?=$transS->h($p, $module);?>', 'status=1, height=600, width=950, scrollbars=1'); } else { updateIfNoOpenItems('mainDiv/<?=$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl();?>/<?=$module;?>/display/all'); }"<?
				}
				//add popupbox on tabs
				$key = $module."_tab_help_".$exec->getCrtWigiiNamespace()->getWigiiNamespaceName();
				$popup = $transS->h($p, $key);
				if($popup == $key){
					$key = $module."_tab_help";
					$popup = $transS->h($p, $key);
				}
				if($popup != $key){
					?>onmouseover="showHelp(this, '<?=$popup;?>')"<?
					?>onmouseout="hideHelp();"<?					
				}
				?> ><?
			}
			?><span><?
				echo strtoupper($transS->t($p, $module));
			?></span><?
		?></li><?
	}
	
?></ul><?
?><div class="clear"></div><?

