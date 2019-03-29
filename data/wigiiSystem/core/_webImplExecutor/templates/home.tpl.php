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
 * Created by LWR, on 21 September 2011
 * Modified by CWE on 24.01.2019 to menu entries declared in the Admin_config/Navigation activity
 */
if(!isset($transS)) $transS = ServiceProvider::getTranslationService();
if(!isset($exec)) $exec = ServiceProvider::getExecutionService();
if(!isset($authS)) $authS = ServiceProvider::getAuthenticationService();
if(!isset($p)) $p = $authS->getMainPrincipal();
if(!isset($configS)) $configS = $this->getConfigurationContext();
if(!isset($userAS)) $userAS = ServiceProvider::getUserAdminService();
if(!isset($sessAS)) $sessAS = ServiceProvider::getSessionAdminService();

//nothing to display if module is not Home
if(!$exec->getCrtModule()->isHomeModule()) return;

if($exec->getIdAnswer()!="workZone"){
	$exec->addJsCode($exec->getCurrentUpdateJsCode($p, Module::HOME_MODULE, Module::HOME_MODULE, 'start'));
} else {
	$exec->cacheCrtAnswer($p, Module::HOME_MODULE);

	if($p->isPlayingRole()){
		$realUser = $p->getRealUser();
	} else {
		$realUser = $p->getAttachedUser();
	}
	$defaultWigiiNamespace = (string)$configS->getParameter($p, null, "defaultWigiiNamespace");
	if(!$defaultWigiiNamespace){
		$defaultWigiiNamespace = $realUser->getWigiiNamespace()->getWigiiNamespaceUrl();
	}
	$defaultWigiiNamespace=str_replace('%20', ' ', $defaultWigiiNamespace);

	$xmlFeedPath = (string)$configS->getParameter($p, null, "tabsContentUrlForHomePage");
	$xmlFeed = $xmlFeedPath;
	if($xmlFeed){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, SITE_ROOT.$xmlFeed);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$xmlFeed = curl_exec($ch);
		if($xmlFeed){
			$xmlFeed = @simplexml_load_string($xmlFeed);
		}
		curl_close($ch);


		if($xmlFeed){
			//lookup for any item with a field  type Files with a none empty path
			$tabs = array();
			foreach($xmlFeed->item as $item){
				$files = $item->xpath("fields/field[@type='Files']");
				if($files){
					foreach($files as $file){
						$name = (string)$file->name;
						if($name){
							$postFix = "";
							if((string)$file->type==".htm" || (string)$file->type==".html" ){
								$postFix = "/integrated";
							}
							if((string)$file->type==".zip" || (string)$file->type==".ZIP"){
								$postFix = "/unzipAndRead";
							}
							$path = (string)$file->path;
							if($path == null) $path = $xmlFeedPath."/download/".(string)$item->id."/".$file["name"];
							$tabs[] = '<li style="border-color:#D0D2DA;"><a style="color:#93a4c1;" href="'.$path.$postFix.'">'.$name.'</a></li>';
							continue 2;
						}
					}
				}
			}
		}
	}

	$roleList = $p->getRoleListener();

	$homePageNbColumn = (string)$configS->getParameter($p, null, "homePageNbColumn");
	if(!$homePageNbColumn) $homePageNbColumn = 6;
	?><div class="homeContent SBB"><? //the home content is to allow having a scroll bar just for the icon list

	if($tabs){
		?><div id="quickStartTabs" class="introduction" style="padding-top:13px;display:table;"><ul style="border-color:#D0D2DA;"><?
			echo implode("", $tabs);
		?></ul></div><?
		$exec->addJsCode("if(!(browserName == 'msie' && version < 8.0)){ $('#quickStartTabs').tabs(); }");
		if(count($tabs)==1){
			$exec->addJsCode("$('#quickStartTabs ul.ui-tabs-nav').hide().next().css('padding-top', '24px');");
		}
	}

	?><div id="Container<?=Module::HOME_MODULE;?>" style="padding-left:50px;padding-right:50px;width: 100%; max-width:<?=($homePageNbColumn*110)+10;?>px;"><?
		$title = $transS->t($p, "homePageTitle".$defaultWigiiNamespace);
		if($title == "homePageTitle".$defaultWigiiNamespace) $title = $transS->t($p, "homePageTitle");
		$introduction = $transS->t($p, "homePageIntroduction".$defaultWigiiNamespace);
		if($introduction == "homePageIntroduction".$defaultWigiiNamespace) $introduction = $transS->t($p, "homePageIntroduction");

		//add title
		if($title && $title!="homePageTitle"){
			?><div class="title"><?=$title;?></div><?
		}

		$haveAtLeastOneAccess = false;

		//add default wigiiNamespace module
		$crtWigiiNamespace = str_replace(' ', '%20', $defaultWigiiNamespace);
		if($roleList->getDefaultWigiiNamespaceModules()){
			if($transS->t($p, "defaultWigiiNamespace")!="defaultWigiiNamespace"){
				?><div class="SBB grayFont subtitle"><?=$transS->t($p, "defaultWigiiNamespace");?></div><?
			} else {
				if($defaultWigiiNamespace && $defaultWigiiNamespace!=WigiiNamespace::EMPTY_NAMESPACE_URL) {
					?><div class="SBB grayFont subtitle"><?=$defaultWigiiNamespace;?></div><?
				}
			}
			?><ul id="homePageModuleMenu"><?
				$moduleReorder = reorderTabBasedOnKeyPriority($roleList->getDefaultWigiiNamespaceModules(), (string)$configS->getParameter($p, null, "prioritizeModuleInHomePage"),true);
				foreach($moduleReorder as $module=>$roleId){
					$haveAtLeastOneAccess =true;
					$customImage = $this->getHtmlImgForModule($p, $module);
					?><li class="H M <?=($customImage ? "customImage " : "").$module;?>"><?
						if($customImage){
							echo $customImage;
						}
						?><a href="#<?=str_replace(' ', '%20', $crtWigiiNamespace)."/".$module;?>" <?
						?>><?
						$customLabel = $transS->t($p, "homePage_".$crtWigiiNamespace."_".$module);
						if($customLabel == "homePage_".$crtWigiiNamespace."_".$module) echo $transS->t($p, $module);
						else echo $customLabel;
						?></a><?
					?></li><?
				}
				?><div class="clear"></div><?
			?></ul><?
		}
		// add other namespaces
		$navigationActivity = $configS->ma($p,Module::ADMIN_MODULE,Activity::createInstance('Navigation'));
		if($roleList->getOtherWigiiNamespaces() || $navigationActivity){
			if($transS->t($p, "otherWigiiNamespace") != "otherWigiiNamespace"){ ?><div class="SBB grayFont subtitle"><?=$transS->t($p, "otherWigiiNamespace");?></div><? }
			?><ul id="homePageWigiiNamespaceMenu"><?
			$wigiiNamespaceReorder = reorderTabBasedOnKeyPriority($roleList->getOtherWigiiNamespaces(), (string)$configS->getParameter($p, null, "prioritizeWigiiNamespaceInHomePage"), true);
			foreach($wigiiNamespaceReorder as $crtWigiiNamespace=>$subMenu){								
				if($subMenu){
					$customImage = $transS->t($p, "homePage_".$crtWigiiNamespace);
					if($customImage == "homePage_".$crtWigiiNamespace) $customImage = '<img src="images/icones/88x88/icongeneric88x88.png" />';
					$customLabel = $transS->t($p, "homePageNamespaceLabel_".$crtWigiiNamespace);
					if($customLabel == "homePageNamespaceLabel_".$crtWigiiNamespace) $customLabel = str_replace('%20',' ',$crtWigiiNamespace);
					$haveAtLeastOneAccess=true;
					$moduleReorder = reorderTabBasedOnKeyPriority($subMenu, (string)$configS->getParameter($p, null, "prioritizeModuleInHomePage"), true);
					?><li class="H N <?=($customImage ? "customImage" : "");?>"><?
						if($customImage){
							echo $customImage;
						}
						?><a href="#<?=str_replace(' ', '%20', $crtWigiiNamespace);?>" onclick="return false;"<?
						?>><? echo $customLabel;?><span></span></a><?
						?><ul class="sub SBB" ><?
						$tempModuleLength = count($moduleReorder);
						foreach($moduleReorder as $module=>$roleId){
							$customImage = $this->getHtmlImgForModule($p, $module);
							?><li class="H M sub <?=($customImage ? "customImage " : "").$module;?>"><?
								if($customImage){
									echo $customImage;
								}
								?><a href="#<?=str_replace(' ', '%20', $crtWigiiNamespace)."/".$module;?>" class="SBB"<?
								?>><?
								$customLabel = $transS->t($p, "homePage_".$crtWigiiNamespace."_".$module);
								if($customLabel == "homePage_".$crtWigiiNamespace."_".$module) echo $transS->t($p, $module);
								else echo $customLabel;
								?></a><?
							?></li><?
						}
						?></ul><?
					?></li><?
				}
			}
			// CWE 24.01.2019: includes remote namespaces declared in Admin_config/Navigation activity
			if($navigationActivity) {
				foreach($navigationActivity->children() as $remoteNamespace) {
					$hostUrl = (string)$remoteNamespace['hostUrl'];
					if(empty($hostUrl)) continue;
					if(substr($hostUrl,-1)!='/') $hostUrl.='/';
					$namespaceUrl = (string)$remoteNamespace['namespaceUrl'];
					$remoteNamespaceName = !empty($namespaceUrl)?$namespaceUrl:$remoteNamespace->getName();
					$haveAtLeastOneAccess=true;
					
					$customImage = $transS->t($p, "remotePage_".$remoteNamespaceName);
					if($customImage == "remotePage_".$remoteNamespaceName) $customImage = null;
					if(!$customImage && !$namespaceUrl) $customImage = '<img src="images/icones/88x88/cms.png" />';
					
					$customLabel = $transS->t($p,$remoteNamespaceName,$remoteNamespace);
					if($customLabel == $remoteNamespaceName) $customLabel = $transS->t($p, "remotePageLabel_".$remoteNamespaceName);
					if($customLabel == "remotePageLabel_".$remoteNamespaceName) $customLabel = str_replace('%20',' ',$remoteNamespaceName);
					$haveAtLeastOneAccess=true;
					?><li class="H N <?=($customImage ? "customImage" : "");?>"><?
						if($customImage){
							echo $customImage;
						}
						?><a href="<?=$hostUrl.(!empty($namespaceUrl)?'#'.str_replace(' ', '%20', $namespaceUrl):'');?>" onclick="return false;"<?
						?>><? echo $customLabel;?><span></span></a><?
						/* no sub-menu for now until SSO is active
						?><ul class="sub SBB" ><?
						$tempModuleLength = count($moduleReorder);
						foreach($moduleReorder as $module=>$roleId){
							?><li class="H sub"><?
								?><a href="#<?=str_replace(' ', '%20', $crtWigiiNamespace)."/".$module;?>" class="SBB"<?
								?>><?
								$customLabel = $transS->t($p, "homePage_".$crtWigiiNamespace."_".$module);
								if($customLabel == "homePage_".$crtWigiiNamespace."_".$module) echo $transS->t($p, $module);
								else echo $customLabel;
								?></a><?
							?></li><?
						}
						?></ul><?
						*/
					?></li><?					
				}				
			}
			?><div class="clear"></div><?
			?></ul><?
		}		
		if(!$haveAtLeastOneAccess){
			?><div class="SBB ui-corner-all" style="font-size:large;padding:20px;"><?
				?><p class="R"><?=$transS->t($p, "youDontHaveAnyAccess");?></p><p>&nbsp;</p><?
				?><p style="">If you are administrator, please startup by opening the admin console.</p><p>&nbsp;</p><img src="<?=SITE_ROOT_forFileUrl."images/gui/help_openAdminConsole.jpg";?>" /><?
			?></div><?
		}

		//add introduction text
		if($introduction && $introduction!="homePageIntroduction"){
			?><div class="introduction"><?=$introduction;?></div><?
		}

	?></div><?

	?></div><?

    $exec->addJsCode("resize_homePage(); setListenerToHomePage('top'); setNavigationBarInHomeStateBsp(".$configS->getParameter($p, $exec->getCrtModule(), "FeedbackOnSystem_enable")."); ");
}


