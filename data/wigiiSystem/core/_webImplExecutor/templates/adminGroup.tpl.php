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


if(!isset($groupAS)) $groupAS = ServiceProvider::getGroupAdminService();
if(!isset($userAS)) $userAS = ServiceProvider::getUserAdminService();
if(!isset($transS)) $transS = ServiceProvider::getTranslationService();
if(!isset($configS)) $configS = $this->getConfigurationContext();
if(!isset($ac)) $ac = $this->getAdminContext($p);


?><div id="adminGroup_list" class="BSB groupPanel"><?

$groupPTreeArrayImpl = GroupPListTreeArrayImpl::createInstance(); //GroupPTreeWebImpl::createInstance(); //
$groupAS->getAllGroups($p, $ac->getWorkingModule(), $groupPTreeArrayImpl, $ac->getGroupListFilter());
$groupPTreeArrayImpl->cleanOnMarkup("x");

$groupPTree = GroupPTreeAdminGroupImpl::createInstance($p, $exec, $configS->getParameter($p, $ac->getWorkingModule(), "nbOfLevelToExpandOnInit"), false);
$groupPTree->start($p, $exec);
foreach($groupPTreeArrayImpl->getListIterator() as $groupP){
	//eput($groupP->getDbEntity()->getGroupName()." parent: ".put($groupPTreeArrayImpl->getParentGroup($groupP)));
	$groupPTree->addGroupP($groupP, $groupPTreeArrayImpl->getParentGroup($groupP));
}
$groupPTree->end($p, $exec);


?></div><?
?><div id="adminGroup_detail" ><?
	//display the toolbar:
	$crtWigiiNamespace = $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl();
	$crtModule = $exec->getCrtModule()->getModuleUrl();
	$workingModule = $ac->getWorkingModule()->getModuleUrl();
	
	?><div id="elementDetail_toolbar" class="T" style="" ><?
		?><div class="commands" ><?
			$activities = array();
			if($p->getGroupCreator($workingModule)){
				$activities[] = "groupNew";
				$activities[] = "groupNewCopy";
				$activities[] = "groupEdit";
				$activities[] = "groupDelete";
				if($this->getConfigurationContext()->allowGroupDynamicConfig($p, $ac->getWorkingModule())) $activities[] = "groupConfigEdit";
			}
			if($this->getConfigurationContext()->getParameter($p, $ac->getWorkingModule(), "Group_enableSubscription")=="1") $activities[] = "groupSubscription";
			if($this->getConfigurationContext()->getParameter($p, $ac->getWorkingModule(), "Notification_enable")=="1") $activities[] = "groupEmailNotification";
			if($this->getConfigurationContext()->getParameter($p, $ac->getWorkingModule(), "Group_enablePortal")=="1") $activities[] = "groupPortal";
			if($this->getConfigurationContext()->getParameter($p, $ac->getWorkingModule(), "Group_enablePortal")=="1") $activities[] = "groupHtmlContent";
			if($this->getConfigurationContext()->getParameter($p, $ac->getWorkingModule(), "Group_enableXmlPublish")=="1") $activities[] = "groupXmlPublish";
			
			foreach($activities as $act){
				$breakLine = false;
				if($p->getRootGroupCreator($workingModule)==null){
					$protectLevel1 = "level1";
				} else {
					$protectLevel1 = "";
				}
				switch($act){
					case "groupNew":
						$img = '<img src="'.SITE_ROOT_forFileUrl.'/images/icones/22x22/folderadd.png"/> ';
						$r = "";
						break;
					case "groupNewCopy":
						$img = '<img src="'.SITE_ROOT_forFileUrl.'/images/icones/22x22/foldercopy.png"/> ';
						$r = "";
						break;
					case "groupEdit":
						$img = '<img src="'.SITE_ROOT_forFileUrl.'/images/icones/22x22/iconfolder22x22.png"/> ';
						$r = "admin $protectLevel1";
						break;
					case "groupDelete":
						$img = '<img src="'.SITE_ROOT_forFileUrl.'/images/icones/26px/cancel.png"/> ';
						$r = "admin $protectLevel1";
						break;
					case "groupConfigEdit":
						$img = '<img src="'.SITE_ROOT_forFileUrl.'/images/icones/tango/22x22/categories/applications-system.png" style="margin-top:4px;margin-right:2px;"/> ';
						$r = "admin";
						break;
					case "groupEmailNotification":
						//$breakLine = true;
						$img = '<img src="'.SITE_ROOT_forFileUrl.'/images/icones/26px/alert.png"/> ';
						$r = "admin";
						break;
					case "groupSubscription":
						$img = '<img src="'.SITE_ROOT_forFileUrl.'/images/icones/tango/22x22/emblems/directory-object-class.png" style="margin-top:3px;margin-right:2px;"/> ';
						$r = "admin";
						break;
					case "groupPortal":
						$img = '<img src="'.SITE_ROOT_forFileUrl.'/images/icones/tango/22x22/categories/applications-internet.png" style="margin-top:4px;" /> ';
						$r = "write";
						break;
					case "groupXmlPublish":
						$img = '<img src="'.SITE_ROOT_forFileUrl.'/images/icones/tango/22x22/places/network-server.png" style="margin-top:4px;" /> ';
						$r = "admin";
						break;
					case "groupHtmlContent":
						$img = '<img src="'.SITE_ROOT_forFileUrl.'/images/icones/tango/22x22/actions/edit-select-all.png" style="margin-top:4px;" /> ';
						$r = "write";
						break;
					default: $img = null;
				}
				if($breakLine){
					?><div class="clear"></div><?
				}
				
				?><div class="H <?=$act." ".$r;?>" onmouseover="showHelp(this, '<?=$transS->h($p, $act."_help");?>', 30, 'fromLeft', 0, 200, 0);" onmouseout="hideHelp();" onclick="if(!$(this).hasClass('disabled')) update('elementDialog/<?=$crtWigiiNamespace;?>/<?=$crtModule;?>/<?=$act;?>/<?=$workingModule;?>/'+adminGroup_crtSelectedGroup+'/adminModule');" ><?=$img;?><div><?=$transS->t($p, $act);?></div></div><?
			}
		?></div><?
		?><div class="clear" style="padding:0px;"></div><?
	?></div><div class="clear"></div><?
	
	?><div id="elementDetail" class="elementDetail"><?
		if($groupPTree->isEmpty()){
			echo $transS->t($p, "noGroupFound");
			if($ac->getGroupListFilter()->getFieldSelectorLogExp()!=null){
				echo $transS->t($p, "resetFilterGroup");
			}
		}
	?></div><?

?></div><?




