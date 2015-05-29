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
 * Created on 18 janv. 10
 * by LWR
 */


if(!isset($userAS)) $userAS = ServiceProvider::getUserAdminService();
if(!isset($transS)) $transS = ServiceProvider::getTranslationService();
if(!isset($ac)) $ac = $this->getAdminContext($p);

?><div id="adminUser_detail" ><?
	//display the toolbar:
	$crtWigiiNamespace = $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl();
	$crtModule = $exec->getCrtModule()->getModuleUrl();
	$workingModule = $ac->getWorkingModule()->getModuleUrl();

	$exec->addJsCode(" adminUserDetailOnResize(); ");

	?><div id="elementDetail_toolbar" class="T" style="" ><?
		?><div class="commands" ><?
			if($p->isUserCreator()){
				if($isFromAdminUser){
					$activities = array("userNew", "userEdit", "userDelete", "userRoleAllocation", "userRights");
				} else {
					$activities = array("roleNew", "userEdit", "userDelete", "roleUserAllocation", "userRights");
				}
			} else {
				if($isFromAdminUser){
					$activities = array("userRoleAllocation", "userRights");
				} else {
					$activities = array("roleUserAllocation", "userRights");
				}
			}
			foreach($activities as $act){
				switch($act){
					case "userNew":
					case "roleNew":
						$img = '<img src="'.SITE_ROOT_forFileUrl.'images/icones/26px/new.png"/> ';
						break;
					case "userEdit":
						$img = '<img src="'.SITE_ROOT_forFileUrl.'images/icones/26px/edit.png"/> ';
						break;
					case "userDelete":
						$img = '<img src="'.SITE_ROOT_forFileUrl.'images/icones/26px/cancel.png"/> ';
						break;
					case "userRoleAllocation":
						$img = '<img src="'.SITE_ROOT_forFileUrl.'images/icones/32x32/man.png" align="absmiddle"/> ';
						break;
					case "roleUserAllocation":
						$img = '<img src="'.SITE_ROOT_forFileUrl.'images/icones/32x32/man.png" align="absmiddle"/> ';
						break;
					case "userRights":
						$img = '<img src="'.SITE_ROOT_forFileUrl.'images/icones/32x32/man-key.png" align="absmiddle"/> ';
						break;
					case "userConfigEdit": //DEPRECATED since the autoCalculatedRole
						$img = '<img src="'.SITE_ROOT_forFileUrl.'images/icones/tango/22x22/categories/applications-system.png" style="margin-top:4px; margin-right:2px;"/> ';
						break;
					default: $img = null;
				}
				if($act=="userRights"){
					//add a menu with the different available modules to select directly the good module
					$jsCode ="
if(!$(this).hasClass('disabled')){
	if($(this).next('.cm').length){
		$(this).next('.cm').remove();
	}
	$(this).after('<div class=\'cm SBB\' style=\'\'></div>');
	$('#navigateMenu li').each(function(){
		label = $(this).find('a').text();
		workingModule = $(this).find('a').attr('href').split('/').pop();
		$('#elementDetail_toolbar .userRights').next('.cm').append('<div class=\'H\' title=\''+workingModule+'\'>'+label+'</div>');
		$(this).find('a').attr('href').replace('#', '');
	});
	$('#elementDetail_toolbar .userRights').next('.cm').css('display','table').css('position', 'absolute').css('top',$('#elementDetail_toolbar .userRights').position().top +30).css('left', $('#elementDetail_toolbar .userRights').position().left+45).find('div').css('float','none').css('margin','0px').css('padding','6px 10px');
	$('#elementDetail_toolbar .userRights').next('.cm').mouseleave(function(){ $(this).remove(); });
	$('#elementDetail_toolbar .userRights').next('.cm').find('div').click(function(){
		update('elementDialog/$crtWigiiNamespace/$crtModule/$act/'+$(this).attr('title')+'/'+adminUser_crtSelectedUser);
		$(this).parent().remove();
	});
}
";
					//$jsCode = str_replace('"', '\\"', $jsCode);
					?><div class="<?=$act;?> H" onclick="<?=$jsCode;?>" ><?=$img;?><div class="SBB ui-corner-all"><?=$transS->t($p, $act);?></div></div><?
				} else {
					?><div class="<?=$act;?> H" onclick="if(!$(this).hasClass('disabled')) update('elementDialog/<?=$crtWigiiNamespace;?>/<?=$crtModule;?>/<?=$act;?>/<?=$workingModule;?>/'+adminUser_crtSelectedUser);" ><?=$img;?><div class="SBB ui-corner-all"><?=$transS->t($p, $act);?></div></div><?
				}
			}
		?></div><?
		?><div class="clear" style="padding:0px;"></div><?
	?></div><div class="clear"></div><?

	?><div id="elementDetail" class="elementDetail"><?
		if($userPRenderer->isEmpty()){
			echo $transS->t($p, "noUserFound");
			if($ac->getUserListFilter()->getFieldSelectorLogExp()!=null){
				echo $transS->t($p, "resetFilterUser");
			}
		}
	?></div><?

?></div><?



