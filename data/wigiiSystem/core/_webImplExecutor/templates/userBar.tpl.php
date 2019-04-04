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

if(!isset($exec)) $exec = ServiceProvider::getExecutionService();
if(!isset($authS)) $authS = ServiceProvider::getAuthenticationService();
if(!isset($p)) $p = $authS->getMainPrincipal();
if(!isset($transS)) $transS = ServiceProvider::getTranslationService();
if(!isset($config)) $config = $this->getConfigurationContext();
if(!isset($moduleAS)) $moduleAS = ServiceProvider::getModuleAdminService();

$companyColor = $config->getParameter($p, null, "companyColor");
$companyReverseColor = $config->getParameter($p, null, "companyReverseColor");

?><div id="userBar" style="background-color:<?=$companyColor; ?>;" ><?

$clientBanner = CLIENT_WEB_PATH."banner.php";
if(file_exists($clientBanner)){
	include($clientBanner);
}

if(!$authS->isMainPrincipalMinimal()){
	/*
	 * LOGED 
	 */
	?><div id="userName" style="color:<?=$companyReverseColor; ?>;" ><?
		/**
		 * changes for new navigation menu
		 */
		if($p->isPlayingRole()){
			$realUser = $p->getRealUser();
			$wigiiNamespace = $realUser->getWigiiNamespace()->getWigiiNamespaceName();
		} else {
			$realUser = $p->getAttachedUser();
			$wigiiNamespace = $realUser->getWigiiNamespace()->getWigiiNamespaceName();
		}
//		if($wigiiNamespace != null) echo $wigiiNamespace." : ";
		echo $realUser->getUsername();			
		
	?></div><?
	
	?><div id="userBarButtons" class="userBar" ><?
	
} else {
	/*
	 * UNLOGED 
	 */
	
	?><div id="userBarButtons" class="userBar" ><?
	
}

	/*
	 * LOGED 
	 */
if(!$authS->isMainPrincipalMinimal()){
	?><div class="grayBorder logout " id="userLogout" onclick="<?=$this->getJsCodeBeforeLogout($p); ?> updateIfNoOpenItems('NoAnswer/<?=$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl();?>/<?=$exec->getCrtModule()->getModuleUrl();?>/logout/');" ><?
		echo $transS->t($p, "logout");		
	?></div><?
}

	/*
	 * LOGED 
	 */
if(!$authS->isMainPrincipalMinimal()){
	if($p->canModifyRealUserPassword()){
		?><div class="grayBorder userBar" id="userChangePassword" onclick="update('changePasswordDialog/<?=$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl();?>/<?=$exec->getCrtModule()->getModuleUrl();?>/changePassword');" ><?
			?><img style="width:18px;" src="<?=SITE_ROOT_forFileUrl;?>images/icones/18px/key.png" />&nbsp;<? echo $transS->t($p, "changeOwnPassword");		
		?></div><?
	}
	if($config->getParameter($p, $exec->getCrtModule(), "FeedbackOnSystem_enable") =="1"){
	?><div class="grayBorder userBar" id="userFeedbackOnSystem" onclick="if(!$(this).hasClass('disabled')) { update('feedbackDialog/<?=$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl();?>/<?=$exec->getCrtModule()->getModuleUrl();?>/giveFeedback/'); }"><?
		?><img style="width:18px;" src="<?=SITE_ROOT_forFileUrl;?>images/icones/18px/feedbacks.png" />&nbsp;<? echo $transS->t($p, "giveGeneralFeedback");
	?></div><?
	}
	if($config->getParameter($p, $exec->getCrtModule(), "Notification_enable") =="1" && $config->getParameter($p, $exec->getCrtModule(), "Notification_enableDynamicSubscription") =="1"){
		?><div class="grayBorder userBar" id="userManageAlertEmails"><?
		?><img style="width:18px;" src="<?=SITE_ROOT_forFileUrl;?>images/icones/18px/alert.png" />&nbsp;<? echo $transS->t($p, "userManageAlertEmails");
		?></div><?
		//clone the groupSelectorPanel and display it inside the menu
		?><div class="white whiteReverseFont grayBorder simpleTree" id="userManageAlertEmailsMenu" style="display:none;" ><?
		
/**
 * emailNotification
 * - fill the groups which has the principal email (showing including the children)
 * - allow to fill an oter email and manage it as desired (only if parameter allows it)
 */
 		$alertsTitle = "";
 		//$emailForAlerts = $p->getEmailAccount();
 		$alertsTitle .= '<div class="alertEmailMenuTitle lightGray lightGrayReverseFont" style="float:left;padding:5px; " >';
 		$alertsTitle .= '<label for="userEmailForRmailAlertInput" >'.$transS->h($p, "yourEmailForAlert").'</label><input id="userEmailForRmailAlertInput" type="text" value="" /><div class="grayBorder goButton" style="" >GO</div>';
 		$alertsTitle .= '<div class="clear"></div>';
 		$alertsTitle .= '</div>';
 		$alertsTitle .= '<div class="clear"></div>';
 		$alertsTitle2 = '<div style="width:300px;">'.$transS->h($p, "editAlertsTitle").'<div>';
 		$alertsTitle2 .= '<div class="clear"></div>';
 		$alertsTitle2 .= '<div class="grayBorder goButton" style="margin:2px 0px 2px 0px; float:left;width:auto; padding:2px 5px 0px 5px;" onclick="$(\\\'#userManageAlertEmailsMenu\\\').slideUp(\\\'fast\\\');" >'.$transS->h($p, "closeAndSave").'</div>';
 		$alertsTitle2 .= '<div class="clear"></div>';
		
		//all the JS code to manage the editAlertEmail button and input is done into prepareGroupPanelSelectorForEmailNotificationMenu
		$exec->addJsCode(" 
userManageAlertEmailsCloseTimeout = null;
userManageAlertEmailsInputValue = '".$p->getValueInGeneralContext("email")."';
$('#userManageAlertEmails')
.click(function(){
setVis('busyDiv', true);
prepareGroupPanelSelectorForEmailNotificationMenu('".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."', '".$exec->getCrtModule()->getModuleUrl()."', '".$alertsTitle."', '".$alertsTitle2."'); 
$('#userManageAlertEmailsMenu').slideDown('fast', function(){ 
	if(userManageAlertEmailsInputValue!=''){
		$('#userManageAlertEmailsMenu .goButton:first').click();
	}
	setVis('busyDiv', false);
}); 
});
");		
//		$exec->addJsCode(" 
//$('#userManageAlertEmailsMenu')
//.mouseenter(function(){ clearTimeout(userManageAlertEmailsCloseTimeout); })
//.mouseleave(function() { userManageAlertEmailsCloseTimeout = setTimeout(function(){ $('#userManageAlertEmailsMenu').slideUp('fast', function(){emptyGroupSelectorMenu('userManageAlertEmailsMenu'); }); }, 100); })
//;");
		?></div><?
	}
	
} else {
	/*
	 * UnLOGED 
	 */
	 
}

	/*
	 * LOGED 
	 * changing language on the login page is not usefull, it is better to be able to change the language when loged
	 */
if(!$authS->isMainPrincipalMinimal()){
	?><div class="grayBorder language" id="userChangeLanguage" ><?
		//TWO Language case --> toggle
		if(count($transS->getVisibleLanguage()) == 2){
			$languages = $transS->getVisibleLanguage();
			unset($languages[$transS->getLanguage()]);
			echo reset($languages);
			$exec->addJsCode(" 
$('#userChangeLanguage')
.click(function(){
updateIfNoOpenItems('NoAnswer/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/changeLanguage/".key($languages)."');
})
;");
		} else {
			//More than two language --> display a menu
			?><img style="width:16px;" src="<?=SITE_ROOT_forFileUrl;?>images/icones/sanscons/arrow2_s.gif" /><? 
			echo $transS->getVisibleLanguage($transS->getLanguage());
			$exec->addJsCode("
userLanguageMenuCloseTimeout = null;
$('#userChangeLanguage')
.mouseenter(function(){ clearTimeout(userLanguageMenuCloseTimeout); userLanguageMenuCloseTimeout = setTimeout(function(){ $('#userLanguageMenu').slideDown();}, 150); })
.mouseleave(function(){ clearTimeout(userLanguageMenuCloseTimeout); userLanguageMenuCloseTimeout = setTimeout(function(){ $('#userLanguageMenu').slideUp(); }, 1000); });
");
		}
	?></div><?
	
	if(count($transS->getVisibleLanguage()) != 2){
		?><div class="white whiteReverseFont grayBorder" id="userLanguageMenu" style="display:none;" ><?
		
		foreach($transS->getVisibleLanguage() as $lang=>$language){
			?><div class="<?=$lang;?>"><?
				echo $language;
			?></div><?
		}
		
		$exec->addJsCode(" 
$('#userLanguageMenu')
.css('top', $('#userChangeLanguage').outerHeight())
.css('left', $('#userChangeLanguage').position().left)
.mouseenter(function(){ clearTimeout(userLanguageMenuCloseTimeout); })
.mouseleave(function() { userLanguageMenuCloseTimeout = setTimeout(function(){ $('#userLanguageMenu').slideUp(); }, 100); })
.click(function(){ $(this).slideUp(); })
.find('div').click(function(){
updateIfNoOpenItems('NoAnswer/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/changeLanguage/'+$(this).attr('class'));
})
;");
		?></div><?
	}
	
	if($p->getModuleAccess(Module::ADMIN_MODULE)){
		?><div class="admin grayBorder" id="userAdminAccess" <?
		if($exec->getCrtModule()->isAdminModule()) echo ' style="border-style:inset; border-width:1px; padding-top:7px; padding-bottom:6px;" ';
		?>onclick="updateIfNoOpenItems('NoAnswer/<?=$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl();?>/<?=$exec->getCrtModule()->getModuleUrl();?>/toggleAdmin');" ><?
		if($exec->getCrtModule()->isAdminModule()) echo $transS->t($p, "closeAdmin"); 
		else echo $transS->t($p, "openAdmin");
		?></div><?
	}
}



?></div><?

?></div><?

//we add this div to allow to begin the line as if the userBar was not floating
?><div class="clear"></div><?

/************************
 * WigiiNamespace navigation
 ************************/

if(!$authS->isMainPrincipalMinimal()){
?><div id="navigationBar" style="float:left;"><?

	if(!isset($userAS)) $userAS = ServiceProvider::getUserAdminService();
	$roleList = UserListArrayImpl::createInstance();
	if($p->isPlayingRole()){
		$realUser = $p->getRealUser();
	} else {
		$realUser = $p->getAttachedUser();
	}
	$userAS->getMyRoles($p, $roleList, $userAS->getListFilterForRoleMenu());
	
	//display the current wigiiNamespace:
	$wigiiNamespace = $p->getWigiiNamespace()->getWigiiNamespaceName();
	if($wigiiNamespace || !$roleList->isEmpty()){
		?><div class="<?
		if($exec->getCrtModule()->isAdminModule()) echo "adminConsoleLabel";
		else echo "darkGrayFont";
		?>" style="margin:15px 15px 0px 7px;padding-bottom:15px;font-size:x-large;font-weight:bold;float:left;"><?
		
		?><span style="float:left;"><?
		if($exec->getCrtModule()->isAdminModule()) echo $transS->t($p, "adminModuleTitle")." ";
		if($p->isWigiiNamespaceCreator()){
			echo $transS->t($p, "superAdmin");
			if($exec->getCrtModule()->isAdminModule()){
				echo ' <font class="darkGrayFont" style="font-size:small;">('.$transS->t($p, "asInRoleMenu")." ".str_replace("@".$wigiiNamespace, "", $p->getUsername()).')</font>';
			}
		} else {
			if(!$wigiiNamespace){
				echo $p->getUsername();
			} else {
				echo $wigiiNamespace;
				if($exec->getCrtModule()->isAdminModule()){
					echo ' <font class="darkGrayFont" style="font-size:small;">('.$transS->t($p, "asInRoleMenu")." ".str_replace("@".$wigiiNamespace, "", $p->getUsername()).')</font>';
				}
			}
		}
		?></span><?
		
		if(($p->isWigiiNamespaceCreator() || $p->isModuleEditor() || !$exec->getCrtModule()->isAdminModule()) && !$roleList->isEmpty()){
			
			//option 1 with remote folder
			?><!--<?
			?><div class="footerBar ui-corner-all" style="float:left;margin:7px 0px 0px 5px;padding:2px 5px 2px 5px;background-image:url('<?=SITE_ROOT_forFileUrl;?>images/gui/layout_banner-bg.png');background-position:center bottom;background-repeat:repeat-x;"><?
			?><div class="grayBorder rollover ui-corner-all" style="float:left;margin:7px 0px 0px 5px;padding:2px 5px 2px 5px;"><?
			echo '<img src="'.SITE_ROOT_forFileUrl.'images/icones/tango/16x16/apps/preferences-system-windows.png" style="float:left;margin:0 5px 0 0px;" />';
			?><span class="darkGrayFont" style="float:left;font-size:small;font-weight:normal;"><?
			echo $transS->t($p, "navigateTo");
			?></span><?
//			echo '<img src="'.SITE_ROOT_forFileUrl.'images/icones/tango/16x16/places/folder-remote.png" vertical-align="bottom"/>';
//			echo '<img src="'.SITE_ROOT_forFileUrl.'images/icones/tango/16x16/places/start-here.png" vertical-align="bottom"/>';

			?>--><?

			//option 2 with drop down simulation
			?><div id="roleMenuOrigin" class="grayBorder ui-corner-all" style="float:left;margin:6px 0px 0px 5px;padding:2px 2px 2px 5px;"><?
			echo '<img src="'.SITE_ROOT_forFileUrl.'images/icones/tango/16x16/apps/preferences-system-windows.png" style="float:left;margin:0 5px 0 0px;" />';
			?><span class="darkGrayFont" style="float:left;font-size:small;font-weight:normal;"><?
			echo $transS->t($p, "navigateTo");
			?></span><?
			echo '<img src="'.SITE_ROOT_forFileUrl.'images/gui/drop-down.png" style="float:left;margin:0 0px 0 5px;" />';
			
			?></div><?
		}
		?></div><?
	} else {
		?><div class="darkGrayFont" style="padding:0px;margin:0px;height:20px;width:20px;"></div><?
		
	}
	?></div><?
	
	//add the role menu:
	?><div class="white whiteReverseFont grayBorder" id="roleMenu" style="display:none;" ><?
	
	if(($p->isWigiiNamespaceCreator() || $p->isModuleEditor() || !$exec->getCrtModule()->isAdminModule()) && !$roleList->isEmpty()){
		$list = array();
		if($realUser->getDetail()->getModuleAccess()){
			$list[($realUser->getDetail()->isWigiiNamespaceCreator() ? $transS->t($p, "superAdmin") : $realUser->getWigiiNamespace()->getWigiiNamespaceName())] = array($realUser->getUsername()=>$realUser->getId());
		}
		foreach($roleList->getListIterator() as $role){
			$wigiiNamespaceName = ($role->getDetail()->isWigiiNamespaceCreator() ? $transS->t($p, "superAdmin") : $role->getWigiiNamespace()->getWigiiNamespaceName());
			if(!isset($list[$wigiiNamespaceName])) $list[$wigiiNamespaceName] = array();
			$list[$wigiiNamespaceName][$role->getUsername()] = $role->getId();
		}
		ksort($list);
		foreach($list as $wigiiNamespace=>$roles){
			if(count($roles)>1){
				ksort($roles);
				?><div class="disabled"><?
					echo $wigiiNamespace;
				?></div><?
				foreach($roles as $roleName=>$roleId){
					?><div class="<?=$roleId.($roleId==$p->getUserId()?" selected":"");?>" style="padding-left:40px;"><?
						echo $transS->t($p, "asInRoleMenu")." ".str_replace("@".$wigiiNamespace, "", $roleName);
					?></div><?
				}
			} else {
				$roleName = key($roles);
				$roleId = current($roles);
				?><div class="<?=$roleId.($roleId==$p->getUserId()?" selected":"");?>"><?
					if(!$wigiiNamespace){
						echo $roleName;
					} else {
						echo $wigiiNamespace.' <font class="darkGrayFont" style="font-size:small;">('.$transS->t($p, "asInRoleMenu")." ".str_replace("@".$wigiiNamespace, "", $roleName).')</font>';
					}
				?></div><?
			}
		}
		$exec->addJsCode(" 
roleMenuCloseTimeout = null;
$('#roleMenu')
.css('top', $('#navigationBar').outerHeight()+$('#navigationBar').position().top-15)
.css('left', $('#roleMenuOrigin').position().left+5)
.mouseenter(function(){ clearTimeout(roleMenuCloseTimeout); })
.mouseleave(function() { roleMenuCloseTimeout = setTimeout(function(){ $('#roleMenu').slideUp('fast'); }, 100); })
.find('div:not(.disabled)').click(function(){
$('#roleMenu').slideUp('fast');
updateIfNoOpenItems('NoAnswer/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/changeRole/'+$(this).attr('class').split(' ')[0]);
})
;
$('#navigationBar')
.css('cursor','pointer')
.click(function(){ clearTimeout(roleMenuCloseTimeout); roleMenuCloseTimeout = setTimeout(function(){ 
	if($('#roleMenu:visible').length == 0){
		$('#roleMenu').slideDown('fast');
		if($('#elementDialog').parent().hasClass('ui-dialog')){
			tempZIndex = $('#elementDialog').parent().css('zIndex')+1;
		} else {
			tempZIndex = 100;
		}
		$('#roleMenu').css('zIndex', tempZIndex);
	} else {
		$('#roleMenu').slideUp('fast');
	}
}, 150); })
.mouseleave(function(){ clearTimeout(roleMenuCloseTimeout); roleMenuCloseTimeout = setTimeout(function(){ $('#roleMenu').slideUp('fast'); }, 500); })
;
");
	}
	
	?></div><?
	
?><div class="clear"></div><?

}
