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
 * Created on 15 oct. 09
 * by LWR
 */

if(!isset($transS)) $transS = ServiceProvider::getTranslationService();
if(!isset($p)) $p = ServiceProvider::getAuthenticationService()->getMainPrincipal();
if(!isset($exec)) $exec = ServiceProvider::getExecutionService();
if(!isset($configS)) $configS = $this->getConfigurationContext();
if(!isset($ac)) $ac = $this->getAdminContext($p);

$crtWigiiNamespaceUrl = $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl();
$crtModuleUrl = $exec->getCrtModule()->getModuleUrl();

$subScreens = $ac->getSubScreens($p);
if(false===array_search($ac->getSubScreen(), $subScreens)){
	$ac->setSubScreen(reset($subScreens));
}

//get submenu
$modules = $p->getModuleAccess();
unset($modules[Module::ADMIN_MODULE]);
if($modules[Module::HELP_MODULE]){
	$help = $modules[Module::HELP_MODULE];
	unset($modules[Module::HELP_MODULE]);
	$modules[Module::HELP_MODULE] = $help;
}
$moduleReorder = reorderTabBasedOnKeyPriority($modules, (string)$configS->getParameter($p, null, "prioritizeModuleInHomePage"), true);
$roleId = $p->getUserId();
$crtWigiiNamespace = $p->getWigiiNamespace()->getWigiiNamespaceUrl();
$crtWigiiNamespace=str_replace('%20',' ',$crtWigiiNamespace);

$accessMenuBegan = false;
$accessMenuClosed = false;
foreach($subScreens as $subScreen){
	switch($subScreen){
		case "adminModuleEditor":
			if($accessMenuBegan && !$accessMenuClosed){
					?></li><?
					?></ul><?
				echo '</ul>'; //ends the adminAccessMenu
				$accessMenuClosed = true;
			}
		case "adminGroup":
			?><ul id="<?=$subScreen;?>" class="sf-menu first-level"> <?
				?><li><?
					?><a <?
					if($subScreen == 'adminModuleEditor'){
						?> href="javascript:adminButton_click(true, '<?=$crtWigiiNamespaceUrl;?>', '<?=$crtModuleUrl;?>', '<?=$subScreen;?>', '<?=$transS->h($p, "groupFilterLabel");?>', '<?=$transS->h($p, "userFilterLabel");?>', '<?=$transS->h($p, "roleFilterLabel");?>');" <?
					} else {
						?> href="#" onclick="$('#adminGroupMenu').css('visibility', 'hidden'); var find = $(this).text().slice( $(this).text().indexOf('(') +1 , $(this).text().indexOf(')')); window.location = $('#adminGroupMenu a:contains('+find+')').prop('href')"<? //window.location = $("#adminGroupMenu a:contains('Files')").prop('href');
					}
					?> onmouseover="showHelp(this, '<?=$transS->h($p, $subScreen."ButtonHelp")?>', 0, 'right',0,200,0);" onmouseout="hideHelp();" <?
					?> > <?
						if($subScreen != 'adminModuleEditor') {
							$customLabel = $transS->t($p, $subScreen."Button").' ('.trim($transS->t($p, $ac->getWorkingModule()->getModuleName())).')';
						} else {
							$customLabel = $transS->t($p, $subScreen."Button");
						}
						echo $customLabel;
						if($subScreen != 'adminModuleEditor'){ ?> <span class="sf-sub-indicator"> »</span> <?}
					?> </a> <?
					if($subScreen != 'adminModuleEditor'){
						?> <ul id="adminGroupMenu"><?
						foreach($moduleReorder as $moduleName=>$module){
							?><li> <?
								?><a id="<?=$moduleName; ?>" <?
								?> href="javascript:adminButton_click(true, '<?=$crtWigiiNamespaceUrl;?>', '<?=$crtModuleUrl;?>', '<?=$moduleName;?>', '<?=$transS->h($p, "groupFilterLabel");?>', '<?=$transS->h($p, "userFilterLabel");?>', '<?=$transS->h($p, "roleFilterLabel");?>');" <?
								?> ><?
								echo $transS->t($p, $moduleName);
								?> </a> <?
							?></li><?
						}
						?> </ul><?
					}
				?></li><?
			?></ul><?
			//submenu
			$exec->addJsCode("doShowAdminGroupMenuTimer = null; addNavigationToMenu('adminGroup', doShowAdminGroupMenuTimer)");
			break;
		case "adminUser":
		case "adminRole":
			?><ul id="<?=$subScreen;?>" class="sf-menu first-level" style=""> <?
				?><li><?
					?> <a  <?
					?> onmouseover="showHelp(this, '<?=$transS->h($p, $subScreen."ButtonHelp")?>', 35, 'fromLeft',0,200,0);" onmouseout="hideHelp();" <?
					?> href="javascript:adminButton_click(true, '<?=$crtWigiiNamespaceUrl;?>', '<?=$crtModuleUrl;?>', '<?=$subScreen;?>', '<?=$transS->h($p, "groupFilterLabel");?>', '<?=$transS->h($p, "userFilterLabel");?>', '<?=$transS->h($p, "roleFilterLabel");?>');" <?
					?> ><?
						echo $transS->t($p, $subScreen."Button");
					?> </a> <?  
				?></li><?
			?></ul><?
			break;
		case "adminGroupUser":
		case "adminUserRole":
		case "adminUserAdmin":
		case "adminUserUser":
			if(!$accessMenuBegan){
				?><ul id="adminAccess" class="sf-menu first-level"><?
					?><li> <?
					?><a href="#" onmouseover="showHelp(this, '<?=$transS->h($p, $subScreen."ButtonHelp")?>', 0, 'right',0,200,0);" onmouseout="hideHelp();"> <?
						echo $transS->t($p, "adminAccess");
						?> <span class="sf-sub-indicator"> »</span> <?
					?></a> <?
					?><ul id="adminAccessMenu" class="sf-with-ul"><?
				$accessMenuBegan = true;
				$exec->addJsCode("doShowAdminAccessMenuTimer = null; addNavigationToMenu('adminAccess', doShowAdminAccessMenuTimer)");
			}
			?><li> <?
				?> <a id="<?=$subScreen;?>" <?
				if($subScreen!='adminUserAdmin' && $subScreen!='adminGroupUser')
				?> href="javascript:adminButton_click(true, '<?=$crtWigiiNamespaceUrl;?>', '<?=$crtModuleUrl;?>', '<?=$subScreen;?>', '<?=$transS->h($p, "groupFilterLabel");?>', '<?=$transS->h($p, "userFilterLabel");?>', '<?=$transS->h($p, "roleFilterLabel");?>', '<?=$transS->h($p, "setFilterOk");?>', '<?=$transS->h($p, "setFilterCancel");?>', '<?=$transS->h($p, "setFilterDisplayAllLabel");?>', '<?=$transS->h($p, "guGroupFilterLabel");?>', '<?=$transS->h($p, "guGroupFilterExplanation");?>', '<?=$transS->h($p, "guUserFilterLabel");?>', '<?=$transS->h($p, "guUserFilterExplanation");?>', '<?=$transS->h($p, "guScreenshot");?>', '<?=$transS->h($p, "urUserFilterLabel");?>', '<?=$transS->h($p, "urUserFilterExplanation");?>', '<?=$transS->h($p, "urRoleFilterLabel");?>', '<?=$transS->h($p, "urRoleFilterExplanation");?>', '<?=$transS->h($p, "urScreenshot");?>', '<?=$transS->h($p, "uuUserFilterLabel");?>', '<?=$transS->h($p, "uuUserFilterExplanation");?>', '<?=$transS->h($p, "uuUser2FilterLabel");?>', '<?=$transS->h($p, "uuUser2FilterExplanation");?>', '<?=$transS->h($p, "uuScreenshot");?>', '<?=$transS->h($p, "setFilterDialogTitle");?>', '<?=$transS->h($p, "setFilterTitle");?>', '<?=$transS->h($p, "setFilterMessage");?>');" <?
				?> ><?
					echo $transS->t($p, $subScreen."Button");
					if($subScreen=='adminUserAdmin' || $subScreen=='adminGroupUser') {
					?> <span class="sf-sub-indicator"> »</span><?
					}
				?> </a> <?
				if($subScreen=='adminUserAdmin' || $subScreen=='adminGroupUser'){ //'adminUserAdmin'== Admin Level
				?> <ul> <?
						foreach($moduleReorder as $moduleName=>$module){
							$customLabel = $transS->t($p, "homePage_".$crtWigiiNamespace."_".$moduleName);
							if($customLabel == "homePage_".$crtWigiiNamespace."_".$moduleName) $customLabel=$transS->t($p, $moduleName);
							else $customLabel.=" (".$transS->t($p, $moduleName).")";
							?><li> <?
								?><a id="<?=$subScreen.'_'.$moduleName; ?>" <?
								?>  href="javascript:adminButton_click(true, '<?=$crtWigiiNamespaceUrl;?>', '<?=$crtModuleUrl;?>', '<?=$subScreen.'_'.$moduleName;?>', '<?=$transS->h($p, "groupFilterLabel");?>', '<?=$transS->h($p, "userFilterLabel");?>', '<?=$transS->h($p, "roleFilterLabel");?>', '<?=$transS->h($p, "setFilterOk");?>', '<?=$transS->h($p, "setFilterCancel");?>', '<?=$transS->h($p, "setFilterDisplayAllLabel");?>', '<?=$transS->h($p, "guGroupFilterLabel");?>', '<?=$transS->h($p, "guGroupFilterExplanation");?>', '<?=$transS->h($p, "guUserFilterLabel");?>', '<?=$transS->h($p, "guUserFilterExplanation");?>', '<?=$transS->h($p, "guScreenshot");?>', '<?=$transS->h($p, "urUserFilterLabel");?>', '<?=$transS->h($p, "urUserFilterExplanation");?>', '<?=$transS->h($p, "urRoleFilterLabel");?>', '<?=$transS->h($p, "urRoleFilterExplanation");?>', '<?=$transS->h($p, "urScreenshot");?>', '<?=$transS->h($p, "uuUserFilterLabel");?>', '<?=$transS->h($p, "uuUserFilterExplanation");?>', '<?=$transS->h($p, "uuUser2FilterLabel");?>', '<?=$transS->h($p, "uuUser2FilterExplanation");?>', '<?=$transS->h($p, "uuScreenshot");?>', '<?=$transS->h($p, "setFilterDialogTitle");?>', '<?=$transS->h($p, "setFilterTitle");?>', '<?=$transS->h($p, "setFilterMessage");?>', '<?=$moduleName;?>');" <?
								?> ><?
								echo $customLabel;
								?> </a> <?
							?></li><?
						}
				?></ul><?
				}
			?></li><?
			break;
	}
}
$exec->addJsCode("adminButton_click(false, '$crtWigiiNamespaceUrl', '$crtModuleUrl', '".$ac->getSubScreen()."', '".$transS->h($p, "groupFilterLabel")."', '".$transS->h($p, "userFilterLabel")."', '".$transS->h($p, "roleFilterLabel")."', '".$transS->h($p, "setFilterOk")."', '".$transS->h($p, "setFilterCancel")."', '".$transS->h($p, "setFilterDisplayAllLabel")."', '".$transS->h($p, "guGroupFilterLabel")."', '".$transS->h($p, "guGroupFilterExplanation")."', '".$transS->h($p, "guUserFilterLabel")."', '".$transS->h($p, "guUserFilterExplanation")."', '".$transS->h($p, "guScreenshot")."', '".$transS->h($p, "urUserFilterLabel")."', '".$transS->h($p, "urUserFilterExplanation")."', '".$transS->h($p, "urRoleFilterLabel")."', '".$transS->h($p, "urRoleFilterExplanation")."', '".$transS->h($p, "urScreenshot")."', '".$transS->h($p, "uuUserFilterLabel")."', '".$transS->h($p, "uuUserFilterExplanation")."', '".$transS->h($p, "uuUser2FilterLabel")."', '".$transS->h($p, "uuUser2FilterExplanation")."', '".$transS->h($p, "uuScreenshot")."', '".$transS->h($p, "setFilterDialogTitle")."', '".$transS->h($p, "setFilterTitle")."', '".$transS->h($p, "setFilterMessage")."');");
if($accessMenuBegan && !$accessMenuClosed){
	?></li><?
	?></ul><?
	echo '</ul>'; //ends the adminAccessMenu
	$accessMenuClosed = true;
}

?><div id="adminFilterGroup" class=" adminFilterGroup SBB" style="" ><?

	?><div class="goButton G SBB" ><?=strtoupper($transS->t($p, "go"));?></div><?

	$groupFilterValue = str_replace('"', "&quot;", stripslashes($ac->getGroupFilterPost(AdminContext::GroupFilterText)));
//	$groupFilterDefaultValue = $transS->t($p, "groupFilterLabel");
	if($groupFilterValue == null){
		?><div class="text" ><input class="empty SBB grayFont" type="text" name="<?=AdminContext::GroupFilterText;?>" value="<?=$groupFilterDefaultValue;?>" /></div><?
	} else {
		?><div class="text" ><input class="SBB" type="text" name="<?=AdminContext::GroupFilterText;?>" value="<?=$groupFilterValue;?>" /></div><?
	}
	?><div class="resetButton" onmouseover="showHelp(this, '<?=$transS->h($p, "adminResetFilterGroupButtonHelp")?>', 20, 'fromLeft',0,200,0);" onmouseout="hideHelp();" ><img src="<?=SITE_ROOT_forFileUrl."images/icones/18px/cancel.png";?>" /></div><?

	$exec->addJsCode("" .
		"$('#adminFilterGroup input').focus(adminFilterGroup_input_focus).blur(adminFilterGroup_input_blur)" .
		".keydown(function(e){ if(e.keyCode == 13){ e.preventDefault(); $(this).blur(); e.stopPropagation();}});" .
		"$('#adminFilterGroup div.goButton').click(function(){ adminFilterGroup_goButton_click('$crtWigiiNamespaceUrl', '$crtModuleUrl'); });" .
		"$('#adminFilterGroup div.resetButton').click(adminFilterGroup_reset);" .
		"");

?></div><?

?><div id="adminFilterUser" class=" adminFilterUser SBB" style="" ><?

	?><div class="goButton G SBB" ><?=strtoupper($transS->t($p, "go"));?></div><?

	$userFilterValue = str_replace('"', "&quot;", stripslashes($ac->getUserFilterPost(AdminContext::UserFilterText)));
	$userFilterType = $ac->getUserFilterPost(AdminContext::UserFilterType);
//	$userFilterDefaultValue = $transS->t($p, "userFilterLabel");
	if($userFilterValue == null){
		?><div class="text" ><input class="empty SBB grayFont" type="text" name="<?=AdminContext::UserFilterText;?>" value="<?=$userFilterDefaultValue;?>" /></div><?
	} else {
		?><div class="text" ><input class="SBB" type="text" name="<?=AdminContext::UserFilterText;?>" value="<?=$userFilterValue;?>" /></div><?
	}
	?><div class="select" ><select class="<?=($userFilterType=="none" || $userFilterType==null ? " empty grayFont ": "");?> grayBorder" name="<?=AdminContext::UserFilterType;?>" ><?
		$label = "--".$transS->t($p, "userType")."--";
		?><option class="grayFont" value="none" title="<?=$label;?>" <?=($userFilterType == "none" ? ' selected="selected" ': "");?> ><?=$label;?></option><?

		$label = $transS->t($p, "user");
		?><option class="blackFont" value="user" title="<?=$label;?>" <?=($userFilterType == "user" ? ' selected="selected" ': "");?> ><?=$label;?></option><?

		$label = $transS->t($p, "role");
		?><option class="blackFont" value="role" title="<?=$label;?>" <?=($userFilterType == "role" ? ' selected="selected" ': "");?> ><?=$label;?></option><?

		$label = $transS->t($p, "calculatedRole");
		?><option class="blackFont" value="calculatedRole" title="<?=$label;?>" <?=($userFilterType == "calculatedRole" ? ' selected="selected" ': "");?> ><?=$label;?></option><?
	?></select></div><?
	?><div class="select" ><select class="SBB grayFont" name="<?=AdminContext::HorizontalPagingText;?>" ><?
		?><option class="grayFont" value="1" selected="on" title="<?=$transS->t($p, "page");?> 1" ><?=$transS->t($p, "page");?> 1</option><?
	?></select></div><?

	?><div class="resetButton" onmouseover="showHelp(this, '<?=$transS->h($p, "adminResetFilterUserButtonHelp")?>', 20, 'fromLeft',0,200,0);" onmouseout="hideHelp();" ><img src="<?=SITE_ROOT_forFileUrl."images/icones/18px/cancel.png";?>" /></div><?

	$exec->addJsCode("" .
		"$('#adminFilterUser input').focus(adminFilterUser_input_focus).blur(adminFilterUser_input_blur)" .
		".keydown(function(e){ if(e.keyCode == 13){ e.preventDefault(); $(this).blur(); e.stopPropagation();}});" .
		"$('#adminFilterUser select').change(adminFilterUser_select_change);" .
		"$('#adminFilterUser div.goButton').click(function(){ adminFilterUser_goButton_click('$crtWigiiNamespaceUrl', '$crtModuleUrl'); });" .
		"$('#adminFilterUser div.resetButton').click(adminFilterUser_reset);" .
		"");

?></div><?

?><div id="adminFilterUser2" class=" adminFilterUser2 SBB" style="" ><?

	?><div class="goButton SBB G" ><?=strtoupper($transS->t($p, "go"));?></div><?

	$user2FilterValue = str_replace('"', "&quot;", stripslashes($ac->getUser2FilterPost(AdminContext::User2FilterText)));
	$user2FilterType = $ac->getUser2FilterPost(AdminContext::User2FilterType);
//	$user2FilterDefaultValue = $transS->t($p, "user2FilterLabel");
	if($user2FilterValue == null){
		?><div class="text" ><input class="empty SBB grayFont" type="text" name="<?=AdminContext::User2FilterText;?>" value="<?=$user2FilterDefaultValue;?>" /></div><?
	} else {
		?><div class="text" ><input class="SBB" type="text" name="<?=AdminContext::User2FilterText;?>" value="<?=$user2FilterValue;?>" /></div><?
	}
	?><div class="select" ><select class="<?=($user2FilterType=="none" || $user2FilterType==null ? " empty grayFont ": "");?> grayBorder" name="<?=AdminContext::User2FilterType;?>" ><?
		$label = "--".$transS->t($p, "userType")."--";
		?><option class="grayFont" value="none" title="<?=$label;?>" <?=($user2FilterType == "none" ? ' selected="selected" ': "");?> ><?=$label;?></option><?

		$label = $transS->t($p, "#User#");
		?><option class="blackFont" value="user" title="<?=$label;?>" <?=($user2FilterType == "user" ? ' selected="selected" ': "");?> ><?=$label;?></option><?

		$label = $transS->t($p, "role");
		?><option class="blackFont" value="role" title="<?=$label;?>" <?=($user2FilterType == "role" ? ' selected="selected" ': "");?> ><?=$label;?></option><?

		$label = $transS->t($p, "calculatedRole");
		?><option class="blackFont" value="calculatedRole" title="<?=$label;?>" <?=($user2FilterType == "calculatedRole" ? ' selected="selected" ': "");?> ><?=$label;?></option><?
	?></select></div><?
	?><div class="select" ><select class="SBB grayFont" name="<?=AdminContext::HorizontalPagingText;?>" ><?
		?><option class="grayFont" value="1" selected="on" title="<?=$transS->t($p, "page");?> 1" ><?=$transS->t($p, "page");?> 1</option><?
	?></select></div><?

	?><div class="resetButton" onmouseover="showHelp(this, '<?=$transS->h($p, "adminResetFilterUserButtonHelp")?>', 20, 'fromLeft',0,200,0);" onmouseout="hideHelp();" ><img src="<?=SITE_ROOT_forFileUrl."images/icones/18px/cancel.png";?>" /></div><?

	$exec->addJsCode("" .
		"$('#adminFilterUser2 input')" .
		".focus(adminFilterUser2_input_focus)" .
		".blur(adminFilterUser2_input_blur)" .
		".keydown(function(e){ if(e.keyCode == 13){ e.preventDefault(); $(this).blur(); e.stopPropagation();}});" .
		"$('#adminFilterUser2 select').change(adminFilterUser2_select_change);" .
		"$('#adminFilterUser2 div.goButton').click(function(){ " .
			"adminFilterUser2_goButton_click('$crtWigiiNamespaceUrl', '$crtModuleUrl'); " .
		"});" .
		"$('#adminFilterUser2 div.resetButton').click(adminFilterUser2_reset);" .
		"");

?></div><?

if(false){ //exporting admin info is not implemented yet
?><div class="adminToolbar" style="" ><?
	?><div class="" onclick="download('<?=$crtWigiiNamespaceUrl;?>/<?=$crtModuleUrl;?>/download/adminSubScreen/<?=$ac->getSubScreen();?>');"><img onmouseover="showHelp(this, '<?=$transS->h($p, "exportAdminTo");?>', 30, 'fromLeft',0,200,0);" onmouseout="hideHelp();" src="<?=SITE_ROOT_forFileUrl."images/icones/26px/save.png";?>" /></div><?
?></div><?
}
?><div class="clear" ></div><?


