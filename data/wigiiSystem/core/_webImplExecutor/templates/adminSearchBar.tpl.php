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

$accessMenuBegan = false;
$accessMenuClosed = false;
foreach($subScreens as $subScreen){
	switch($subScreen){
		case "adminModuleEditor":
			if($accessMenuBegan && !$accessMenuClosed){
				echo '</div>'; //ends the adminAccessMenu
				$accessMenuClosed = true;
			}
		case "adminGroup":
		case "adminUser":
		case "adminRole":
			?><div class="adminButton SBB <?=($ac->getSubScreen() == $subScreen ? " S " : "");?>" style="" <?
				?> id="<?=$subScreen;?>" <?
				?> onmouseover="showHelp(this, '<?=$transS->h($p, $subScreen."ButtonHelp")?>', 35, 'fromLeft',0,200,0);" onmouseout="hideHelp();" <?
				?> onclick="adminButton_click(true, '<?=$crtWigiiNamespaceUrl;?>', '<?=$crtModuleUrl;?>', $(this).attr('id'), '<?=$transS->h($p, "groupFilterLabel");?>', '<?=$transS->h($p, "userFilterLabel");?>', '<?=$transS->h($p, "roleFilterLabel");?>');" <?
				?> ><?
				echo $transS->t($p, $subScreen."Button");
			?></div><?
			break;
		case "adminGroupUser":
		case "adminUserRole":
		case "adminUserAdmin":
		case "adminUserUser":
			if(!$accessMenuBegan){
				?><div class="adminButton SBB" style="" id="adminAccess" ><?
					echo $transS->t($p, "adminAccess");
				?></div><?
				?><div class="SBB" id="adminAccessMenu" style="display:none;" ><?
				$accessMenuBegan = true;
				$exec->addJsCode("" .
					"adminAccessMenuCloseTimeout = null;" .
					"$('#adminAccessMenu')" .
					".css('top', $('#adminAccess').position().top+$('#adminAccess').outerHeight())" .
					".css('left', $('#adminAccess').position().left)" .
					".mouseenter(function(){ clearTimeout(adminAccessMenuCloseTimeout); })" .
					".mouseleave(function() { adminAccessMenuCloseTimeout = setTimeout(function(){ $('#adminAccessMenu').slideUp(200); }, 100); })" .
					".click(function(){ $(this).slideUp(200); })" .
					";" .
					"$('#adminAccess')" .
					".mouseenter(function(){ clearTimeout(adminAccessMenuCloseTimeout); " .
					"adminAccessMenuCloseTimeout = setTimeout(function(){" .
						"$('#adminAccessMenu').slideDown(200);" .
						"tempZIndex = 100;" .
						"$('#adminAccessMenu').css('zIndex', tempZIndex);" .
						"}, 150);" .
					"})" .
					".mouseleave(function(){ clearTimeout(adminAccessMenuCloseTimeout); adminAccessMenuCloseTimeout = setTimeout(function(){ $('#adminAccessMenu').slideUp(); }, 1000); })" .
					".click(function(){ $('#adminAccessMenu').show(); })" .
					";" .
					"if($('#adminAccessMenu div.selected').length>0){" .
						"$('#adminAccess').addClass('selected');" .
					"}" .
					"");
			}
			?><div class="H <?=($ac->getSubScreen() == $subScreen ? " S " : "");?>" style="" <?
				?> id="<?=$subScreen;?>" <?
				?> onmouseover="showHelp(this, '<?=$transS->h($p, $subScreen."ButtonHelp")?>', 0, 'right',0,200,0);" onmouseout="hideHelp();" <?
				?> onclick="adminButton_click(true, '<?=$crtWigiiNamespaceUrl;?>', '<?=$crtModuleUrl;?>', $(this).attr('id'), '<?=$transS->h($p, "groupFilterLabel");?>', '<?=$transS->h($p, "userFilterLabel");?>', '<?=$transS->h($p, "roleFilterLabel");?>', '<?=$transS->h($p, "setFilterOk");?>', '<?=$transS->h($p, "setFilterCancel");?>', '<?=$transS->h($p, "setFilterDisplayAllLabel");?>', '<?=$transS->h($p, "guGroupFilterLabel");?>', '<?=$transS->h($p, "guGroupFilterExplanation");?>', '<?=$transS->h($p, "guUserFilterLabel");?>', '<?=$transS->h($p, "guUserFilterExplanation");?>', '<?=$transS->h($p, "guScreenshot");?>', '<?=$transS->h($p, "urUserFilterLabel");?>', '<?=$transS->h($p, "urUserFilterExplanation");?>', '<?=$transS->h($p, "urRoleFilterLabel");?>', '<?=$transS->h($p, "urRoleFilterExplanation");?>', '<?=$transS->h($p, "urScreenshot");?>', '<?=$transS->h($p, "uuUserFilterLabel");?>', '<?=$transS->h($p, "uuUserFilterExplanation");?>', '<?=$transS->h($p, "uuUser2FilterLabel");?>', '<?=$transS->h($p, "uuUser2FilterExplanation");?>', '<?=$transS->h($p, "uuScreenshot");?>', '<?=$transS->h($p, "setFilterDialogTitle");?>', '<?=$transS->h($p, "setFilterTitle");?>', '<?=$transS->h($p, "setFilterMessage");?>');" <?
				?> ><?
				echo $transS->t($p, $subScreen."Button");
			?></div><?
			break;
	}
}
$exec->addJsCode("adminButton_click(false, '$crtWigiiNamespaceUrl', '$crtModuleUrl', '".$ac->getSubScreen()."', '".$transS->h($p, "groupFilterLabel")."', '".$transS->h($p, "userFilterLabel")."', '".$transS->h($p, "roleFilterLabel")."', '".$transS->h($p, "setFilterOk")."', '".$transS->h($p, "setFilterCancel")."', '".$transS->h($p, "setFilterDisplayAllLabel")."', '".$transS->h($p, "guGroupFilterLabel")."', '".$transS->h($p, "guGroupFilterExplanation")."', '".$transS->h($p, "guUserFilterLabel")."', '".$transS->h($p, "guUserFilterExplanation")."', '".$transS->h($p, "guScreenshot")."', '".$transS->h($p, "urUserFilterLabel")."', '".$transS->h($p, "urUserFilterExplanation")."', '".$transS->h($p, "urRoleFilterLabel")."', '".$transS->h($p, "urRoleFilterExplanation")."', '".$transS->h($p, "urScreenshot")."', '".$transS->h($p, "uuUserFilterLabel")."', '".$transS->h($p, "uuUserFilterExplanation")."', '".$transS->h($p, "uuUser2FilterLabel")."', '".$transS->h($p, "uuUser2FilterExplanation")."', '".$transS->h($p, "uuScreenshot")."', '".$transS->h($p, "setFilterDialogTitle")."', '".$transS->h($p, "setFilterTitle")."', '".$transS->h($p, "setFilterMessage")."');");
if($accessMenuBegan && !$accessMenuClosed){
	echo '</div>'; //ends the adminAccessMenu
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


