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
 * Created by LWR, on 21 September 2011
 */
if(!isset($transS)) $transS = ServiceProvider::getTranslationService();
if(!isset($exec)) $exec = ServiceProvider::getExecutionService();
if(!isset($authS)) $authS = ServiceProvider::getAuthenticationService();
if(!isset($p)) $p = $authS->getMainPrincipal();
if(!isset($config)) $config = $this->getConfigurationContext();
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
	$defaultWigiiNamespace = (string)$config->getParameter($p, null, "defaultWigiiNamespace");
	if(!$defaultWigiiNamespace){
		$defaultWigiiNamespace = $realUser->getWigiiNamespace()->getWigiiNamespaceUrl();
	}


	$xmlFeedPath = (string)$config->getParameter($p, null, "tabsContentUrlForHomePage");
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

	$homePageNbColumn = (string)$config->getParameter($p, null, "homePageNbColumn");
	if(!$homePageNbColumn) $homePageNbColumn = 6;
	?><div class="homeContent SBB"><? //the home content is to allow having a scroll bar just for the icon list

	if($tabs){
		?><div id="quickStartTabs" class="introduction" style="padding-top:13px;display:table;"><ul style="border-color:#D0D2DA;"><?
			echo implode("", $tabs);
		?></ul></div><?
		$exec->addJsCode("if(!(jQuery.browser.msie && version < 8.0)){ $('#quickStartTabs').tabs(); }");
		if(count($tabs)==1){
			$exec->addJsCode("$('#quickStartTabs ul.ui-tabs-nav').hide().next().css('padding-top', '24px');");
		}
	}

	?><div id="Container<?=Module::HOME_MODULE;?>" style="padding-left:50px;padding-right:50px;width:<?=($homePageNbColumn*110)+10;?>px;"><?
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
		$crtWigiiNamespace = $defaultWigiiNamespace;
		if($roleList->getDefaultWigiiNamespaceModules()){
			if($transS->t($p, "defaultWigiiNamespace")!="defaultWigiiNamespace"){
				?><div class="SBB grayFont subtitle"><?=$transS->t($p, "defaultWigiiNamespace");?></div><?
			} else {
				if($defaultWigiiNamespace && $defaultWigiiNamespace!=WigiiNamespace::EMPTY_NAMESPACE_URL) {
					?><div class="SBB grayFont subtitle"><?=$defaultWigiiNamespace;?></div><?
				}
			}
			?><ul id="homePageModuleMenu"><?
				$moduleReorder = reorderTabBasedOnKeyPriority($roleList->getDefaultWigiiNamespaceModules(), (string)$config->getParameter($p, null, "prioritizeModuleInHomePage"));
				foreach($moduleReorder as $module=>$roleId){
					if(strpos($module, "[title]")===0) continue;
					$haveAtLeastOneAccess =true;
					$customImage = $transS->t($p, "homePage_".$module);
					if($customImage == "homePage_".$module) $customImage = null;
					?><li class="H <?=($customImage ? "customImage" : "M ".$module);?>"><?
						if($customImage){
							echo $customImage;
						}
						?><a href="#<?=str_replace(' ', '%20', $crtWigiiNamespace)."/".$module;?>" <?
						?>><?
						$customLabel = $transS->t($p, "homePage_".$crtWigiiNamespace."_".$module);
						if($customLabel == "homePage_".$crtWigiiNamespace."_".$module) echo $transS->t($p, $module);
						else echo $customLabel; //." (".$transS->t($p, $module).")";
						?></a><?
					?></li><?
				}
				?><div class="clear"></div><?
			?></ul><?
		}
		//add other wigiiNamespaces
		if($roleList->getOtherWigiiNamespaces()){
			if($transS->t($p, "otherWigiiNamespace") != "otherWigiiNamespace"){ ?><div class="SBB grayFont subtitle"><?=$transS->t($p, "otherWigiiNamespace");?></div><? }
			?><ul id="homePageWigiiNamespaceMenu"><?
			$wigiiNamespaceReorder = reorderTabBasedOnKeyPriority($roleList->getOtherWigiiNamespaces(), (string)$config->getParameter($p, null, "prioritizeWigiiNamespaceInHomePage"), true);
			foreach($wigiiNamespaceReorder as $crtWigiiNamespace=>$subMenu){
				$customImage = $transS->t($p, "homePage_".$crtWigiiNamespace);
				if($customImage == "homePage_".$crtWigiiNamespace) $customImage = null;
				if($subMenu){
					$haveAtLeastOneAccess =true;
					$moduleReorder = reorderTabBasedOnKeyPriority($subMenu, (string)$config->getParameter($p, null, "prioritizeModuleInHomePage"), true);
					?><li class="H <?=($customImage ? "customImage" : "N");?>"><?
						if($customImage){
							echo $customImage;
						}
						?><a href="#<?=str_replace(' ', '%20', $crtWigiiNamespace);?>" onclick="return false;"<?
						?>><? echo $crtWigiiNamespace;?><span></span></a><?
						?><ul class="sub SBB" ><?
						$tempModuleLength = count($moduleReorder);
						foreach($moduleReorder as $module=>$roleId){
							?><li class="H sub"><?
								?><a href="#<?=str_replace(' ', '%20', $crtWigiiNamespace)."/".$module;?>" class="SBB"<?
								?>><?
								$customLabel = $transS->t($p, "homePage_".$crtWigiiNamespace."_".$module);
								if($customLabel == "homePage_".$crtWigiiNamespace."_".$module) echo $transS->t($p, $module);
								else echo $customLabel; //." (".$transS->t($p, $module).")";
								?></a><?
							?></li><?
						}
						?></ul><?
					?></li><?
				} else if(false){
					?><li class="H customImage adminMenu" onclick="$('#userMenuAdmin a[href=\'#<?=str_replace(' ', '%20', $crtWigiiNamespace);?>/Admin\']:first').click(); return false;"><?
						?><img src="images/icones/88x88/folderadd.png" /><?
						?><a href="#<?=str_replace(' ', '%20', $crtWigiiNamespace);?>/Admin" onclick="return false;"<?
						?>><? echo $crtWigiiNamespace;?><br /><i>(Click to startup by creating root folders in admin console)</i></a><?
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

			//from 27/11/2012, remove the auto-log out if no access but having some roles (cf. loginFormExecutor::actOnCheckedRecord)
			if(false){
				?><div class="SBB ui-corner-all" style="font-size:large;padding:20px;"><?
					if(!$p->isWigiiNamespaceCreator() && !$p->getRootGroupCreator()){
						?><p class="R"><?=$transS->t($p, "youDontHaveAnyAccess");?></p><?
						?><p style="font-weight:bold;"><?=$transS->t($p, "logoutInProgress");?></p><?
					} else {
						?><p style="font-weight:bold;">To startup open admin console through the user menu. Then create root folders in the tabs you want to activate. Then logout / login to upgrade your new accesses.</p><?
					}
				?></div><?
				if(!$p->isWigiiNamespaceCreator() && !$p->getRootGroupCreator()){
					$exec->addJsCode($this->getJsCodeBeforeLogout($p)." logout(); "); //. " setTimeout(function(){ self.location = '" . str_replace("//", '\/\/', SITE_ROOT) . "'; }, 1500);");
					$authS->logout();
				}
			}
		}

		//add introduction text
		if($introduction && $introduction!="homePageIntroduction"){
			?><div class="introduction"><?=$introduction;?></div><?
		}


	?></div><?

	?></div><?

	$exec->addJsCode("resize_homePage(); setListenerToHomePage(); setNavigationBarInHomeState(".$config->getParameter($p, $exec->getCrtModule(), "FeedbackOnSystem_enable").");");
}


