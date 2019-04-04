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
 * Created on 03 may 2010
 * by LWR
 */
if(!isset($groupS)) $groupS = ServiceProvider::getGroupAdminService();
if(!isset($transS)) $transS = ServiceProvider::getTranslationService();
if(!isset($exec)) $exec = ServiceProvider::getExecutionService();
if(!isset($configS)) $configS= $this->getConfigurationContext();

if(!$p->getValueInRoleContext("lastView")==WigiiExecutor::ICONS_view){
	echo "lastView context is not ICONS_view: ".$p->getValueInRoleContext("lastView").". contact the support";
} else {
	
	//get the tree and build divs with it:
	$groupPTree = GroupPTreeWebImpl::createInstance();
	//fill groupPTree
	$groupS->getAllGroups($p, $exec->getCrtModule(), $groupPTree);
	$groupPTreeIterator = $groupPTree->getListIterator();
	
	if($groupPTreeIterator==null){
		echo "no group in Module: ".$exec->getCrtModule()->getModuleName();
	} else {
	
		$crtWigiiNamespace = $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl();
		$crtModule = $exec->getCrtModule()->getModuleUrl();
		
		//get the list context (same than the list one), to find which group to select first
		$lc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
		//if no listContext, then take the ConfigurationContext
		if($lc->getGroupPList() == null || $lc->getGroupPList()->isEmpty() || $groupPTreeIterator[reset($lc->getGroupPList()->getListIterator())->getId()]==null){
			$tempGroupList = $this->getConfigurationContext()->getGroupPList($p, $exec->getCrtModule());
			if($tempGroupList == null) $tempGroupList = $groupPTree;
			$lc->setGroupPList($tempGroupList, $this->getConfigurationContext()->doesGroupListIncludeChildren($p, $exec->getCrtModule()));
		}
		$selectedGroup = null;
		if($lc->getGroupPList() != null && !$lc->getGroupPList()->isEmpty()){
			//if the context has more than one selected group, that means we are in the ALL
			if($lc->getGroupPList()->count()>1){
				$selectedGroup = 0;
			} else {
				$selectedGroup = reset($lc->getGroupPList()->getListIterator())->getId();
			}
		}
				
		?><div id="iconsGroupPanel_container" ><?
		
		?><div id="iconsGroupPanel_crt" ><?
		
		?></div><?
		
		?><ul id="groupSelectorPanel_treeview" class="simpleTree" style="display:none;" ><?
		
		$id = 'group_0';
		
		?><li class="root" id="<?=$id;?>" ><?
		?><div class="divAsSpan level0" UNSELECTABLE="on" style="<?=$style;?>"<?
			?>><img src="<?=SITE_ROOT_forFileUrl;?>images/icones/26px/home.png" /><?
			echo '<span class="invisible" >'.$transS->t($p, "all").'</span>';
			?></div><?		
		?><ul class="level1" ><?
			
		$parentIdsStack = array(0=>0); //we need to initialise with the no parent
		$crtParentId = 0;
		$first = true;
		$level = 1;
		foreach($groupPTreeIterator as $groupP){
			$thisParentId = $groupP->getGroup()->getGroupParentId();
			if($thisParentId == null) $thisParentId=0;
			
		//	eput("\nparent: ".$thisParentId." crtParent:".$crtParentId);
			$counter=0;
			//check if parent changes
			if($crtParentId != $thisParentId){
				//check if this is a new parent or if we need to close parent
				if($parentIdsStack[$thisParentId] === null){
					$parentIdsStack[$thisParentId] = $thisParentId;
		//			eput("\nnew parent: ".$thisParentId);
					$crtParentId = $thisParentId;
					$level ++;
					?><ul class="level<?=$level;?>"><?
				} else {
					//we need to close the ul till the parent match again
					while($counter < 10000 && $thisParentId != $crtParentId){
						?></li></ul><?
						$level --;
		//				eput("\nclose parent: ".$crtParentId);
						array_pop($parentIdsStack);
						$crtParentId = end($parentIdsStack);
		//				eput(" new parent: ".$crtParentId);
						$counter++;
					}
				}
			} else if(!$first) {
		//		eput("\nclose leaf");
				?></li><?
			}
			
			//we create the current li
			
			$id = 'group_'.$groupP->getGroup()->getId();
			
			$class = "";
			if($groupP->getRights()==null){
				$class .= " readOnly disabled ";
			} else if(!$groupP->getRights()->canShareElement()){
				$class .= " readOnly RR ";
			} else if(!$groupP->getRights()->canWriteElement()){
				$class .= " RS ";
			} else if(!$groupP->getRights()->canModify()){
				$class .= " RW ";
			} else $class .= " RX ";
			
			$class .= " level".$level." ";
			//the &nbsp; just before the div is to prevent going next line in IE7
			?><li id="<?=$id?>" ><div class="divAsSpan <?=$class;?>" UNSELECTABLE="on" <?=$help;?> style="<?=$style; ?>" ><?
			
			$wigiiNamespace = $groupP->getGroup()->getWigiiNamespace()->getWigiiNamespaceName();
			if($wigiiNamespace!=null && $wigiiNamespace != $p->getWigiiNamespace()->getWigiiNamespaceName())
			echo $wigiiNamespace." : ";
			echo $groupP->getGroup()->getGroupName();
			
			/**
			 * URL portal zone
			 * 	if an portal is setted on the group, then display the content of the website
			 */
			
			if($configS->getParameter($p, $exec->getCrtModule(), "Group_enablePortal") == "1"){
				$url = null;
				$htmlContent = null;
				if($groupP->getGroup()->getDetail()!=null){ //if detail = null, then do nothing
				
					$portalRec = $this->createActivityRecordForForm($p, Activity::createInstance("groupPortal"), $exec->getCrtModule());
					$portalRec->getWigiiBag()->importFromSerializedArray($groupP->getGroup()->getDetail()->getPortal(), $portalRec->getActivity());
					$url = $portalRec->getFieldValue("url", "url");
					
					$htmlContentRec = $this->createActivityRecordForForm($p, Activity::createInstance("groupHtmlContent"), $exec->getCrtModule());
					$htmlContentRec->getWigiiBag()->importFromSerializedArray($groupP->getGroup()->getDetail()->getHtmlContent(), $htmlContentRec->getActivity());
					$htmlContent = $htmlContentRec->getFieldValue("text");
					
					if($url != null){
						?><div class="portal" style="overflow:hidden; display:none; "><?
							if(!preg_match('#^(((ht|f)tp(s?))\://)#i', $url)) $url = "http://".$url;
							?><a class="media {type:'html'}" href="<?=$url;?>" ></a><?
						?></div><?
					} else if($htmlContent != null){
						if($exec->getBrowserName()=="msie" && $exec->getBrowserVersion() == 7) $pad = " padding-top:10px;";
						?><div class="portal" style="overflow:hidden; display:none; padding-left:10px; padding-right:10px; <?=$pad;?>"><?
							echo $htmlContent;
						?></div><?
					}
				} //end if detail != null
			}
			
			?></div><?
			
			$first = false;
		}
		//if $first, that means there where noting in the tree
		if(!$first){
			?></li><?
		}
		?></ul></li></ul><?
		
		?></div><?
		
		//Add the good selection of the groups depending on the context:
		
		/**
		 * the js code where we remove component after loading list is in workZone.js ->loadElementListInIconsViewInGroup()
		 */
		$exec->addJsCode("
$('#groupSelectorPanel_treeview').css('position', 'relative').simpleTree({
	drag:false, animate:true, speed:false, docToFolderConvert:false
})
	.find('ul').css('display','block').height('auto').end()
	.find('li.line, li.line-last').remove().end()
	.find('span:not(.invisible), div.divAsSpan:not(.invisible)').css('display','inline').end()
	.find('.folder-close').removeClass('folder-close').addClass('folder-open').end()
	.find('.folder-close-last').removeClass('folder-close-last').addClass('folder-open-last').end()
;

function goToList(groupId){
	update('NoAnswer/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/loadElementListInIconsView/'+getGroupIdInIconsView(groupId));
}
lastGroupId = new Array();
idCounter = 0;
function selectIcon(groupId, onLoad, beforeGroup){
	
	groupId = groupId.split('_')[0]+'_'+groupId.split('_')[1];
	
	if(arguments.length < 2) onLoad = false;
	if(preventDblClickTimeout){
		return;
	} else {
		preventDblClickTimeout = true;
		setTimeout(function(){ preventDblClickTimeout = false; }, 300);
	}
	crtGroupIdInIconsView = groupId;
	ul = $('#groupSelectorPanel_treeview #'+groupId+'>ul');
	portal = null;
	htmlContent = null;
	if($('#iconsView>.portal').length > 0){
		$('#iconsView>.portal').remove();
	}
	if(!$('#groupSelectorPanel_treeview #'+groupId).hasClass('leaf')){
		if($('#groupSelectorPanel_treeview #'+groupId+'>div.divAsSpan>.portal>.media').length > 0){
			$('#groupSelectorPanel_treeview #'+groupId+'>div.divAsSpan>.portal').clone().appendTo('#iconsView').show();
			portal_resize();
		} else if($('#groupSelectorPanel_treeview #'+groupId+'>div.divAsSpan>.portal').length > 0){
			$('#groupSelectorPanel_treeview #'+groupId+'>div.divAsSpan>.portal').clone().appendTo('#iconsView').show();
			portal_resize();
		}
	}
	
	if(onLoad){
		lastGroupId = new Array();
	}
	$('#moduleView').hide();
	$('#iconsView').removeClass('lightGray');
	$('#iconsGroupPanel_crt').html('');
	ul.clone(false).appendTo('#iconsGroupPanel_crt');
	
	if(ul.length>0){
		$('#iconsGroupPanel_crt>ul ul').each(function(){
			$(this).parent().addClass('notLeaf');
			$(this).remove();
		});
		$('<div style=\"margin-top:30px;\"</div><div class=\"clear\"></div>').prependTo('#iconsGroupPanel_crt ul');
	} else {
		$('<ul></ul>').appendTo('#iconsGroupPanel_crt');
	}
	
	//add br
	$('<div class=\"clear\"></div>').prependTo('#iconsGroupPanel_crt ul');

	//add the path
	$('#groupSelectorPanel_treeview #'+groupId).each(function(){
		temp = $(this).clone(false);
		if(!temp.hasClass('leaf')){
			$('<img src=\"".str_replace("/", "\\/", SITE_ROOT_forFileUrl)."images/gui/layout_tree_expand_out.png\" />').appendTo(temp);		
		}
		temp.prependTo('#iconsGroupPanel_crt ul').find('ul', this).remove().end().addClass('path');
	});
	$('#groupSelectorPanel_treeview #'+groupId).parents('li').each(function(){
		temp = $(this).clone(false);
		if(!temp.hasClass('leaf')){
			$('<img src=\"".str_replace("/", "\\/", SITE_ROOT_forFileUrl)."images/gui/layout_tree_expand_out.png\" />').appendTo(temp);		
		}
		temp.prependTo('#iconsGroupPanel_crt ul').find('ul', this).remove().end().addClass('path');
	});

	//clean each identical id and make them unique:
	$('#iconsGroupPanel_crt li').each(function(){
		$(this).attr('id', $(this).attr('id')+'_'+(idCounter++));
	});
	
	//add the back
	$('#groupSelectorPanel_treeview #'+getLastGroupId()+'').clone(false).prependTo('#iconsGroupPanel_crt ul').find('ul', this).remove().end().addClass('path back').removeClass('selected').find('div.divAsSpan', this).text('".$transS->t($p, "back")."');
	
	//add the up
	$('#groupSelectorPanel_treeview #'+groupId+':not(.root)').parent().parent().clone(false).prependTo('#iconsGroupPanel_crt ul').find('ul', this).remove().end().addClass('path up').removeClass('selected').find('div.divAsSpan', this).text('".$transS->t($p, "up")."');
	
	//on click call selectIcon
	$('#iconsGroupPanel_crt li')
		.mousedown(function(){ if(!$(this).hasClass('path')) { $('#iconsGroupPanel_container .selected').removeClass('selected'); $(this).addClass('selected'); } })
		.click(function(){ if($(this).hasClass('back')){ popLastGroupId(groupId); } else { pushLastGroupId(groupId); } selectIcon($(this).attr('id'), false, groupId); })
		.mouseenter(function(){ 
				$('#iconsGroupPanel_crt .over').removeClass('over'); 
				clearTimeout(pathMenuForIconsViewRootCloseTimeout); 
				$('#pathMenuForIconsViewRoot *').hide();
				$('div.divAsSpan', this).toggleClass('whiteBorder').toggleClass('lightGrayBorder'); 
			})
		.mouseleave(function(){ $('div.divAsSpan', this).toggleClass('lightGrayBorder').toggleClass('whiteBorder'); })
		;
	$('#iconsGroupPanel_crt div.divAsSpan').addClass('ui-corner-all whiteBorder');
	$('#iconsGroupPanel_crt li:not(\".path\")').hide().fadeIn();
	
	//clean each identical id and make them unique:
	$('#iconsGroupPanel_crt li').each(function(){
		$(this).attr('id', $(this).attr('id')+'_'+(idCounter++));
	});
	
	//add menu to folders:
	pathMenuCloseTimeout = new Array();
	$('#iconsGroupPanel_crt li:not(.path)').mouseenter(function(){
		clearTimeout(pathMenuCloseTimeout[0]); 
		displayMenuForSubGroupsInIconsView(0, this, selectIcon, goToList, '".$transS->h($p, "openIconsView")."', '".$transS->h($p, "browseIconsView")."');
	}).mouseleave(function(){
		clearTimeout(pathMenuCloseTimeout[0]); 
		pathMenuCloseTimeout[0] = setTimeout(function(){ $('#pathMenuForIconsView'+0).slideUp(100); }, 500);
	});
	
	//add higlight on current group, or last group if going up
	$('#iconsGroupPanel_container .selected').removeClass('selected');
	if($('#iconsGroupPanel_crt li[id^='+beforeGroup+']:not(.path)').length==0){
		$('#iconsGroupPanel_crt li.path[id^='+groupId+']:not(.back):not(.up)').addClass('selected');
	} else {
		$('#iconsGroupPanel_crt li[id^='+beforeGroup+']:not(.path)').addClass('selected');
	}
	
	iconsView_resize();

	if($('#groupSelectorPanel_treeview #'+groupId).hasClass('leaf')){
		update('NoAnswer/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/loadElementListInIconsView/'+getGroupIdInIconsView(groupId)); 
	} else {
		if(".($configS->getParameter($p, $exec->getCrtModule(), "Group_IncludeChildrenGroupsOnSelect") == "1")."){
			update('NoAnswer/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/groupSelectorPanel/selectGroupAndChildrenWithNoUpdate/'+getGroupIdInIconsView(groupId));
		} else {
			update('NoAnswer/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/groupSelectorPanel/selectGroupWithNoUpdate/'+getGroupIdInIconsView(groupId));
		}
	}
}
selectIcon('group_".$selectedGroup."', true);

");	

	}
	
}

