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
 * Created on 20 janv. 10
 * by LWR
 */

class GroupPListRenderer implements GroupPList {

	private $transS;
	public function setTranslationService($transS){ $this->transS = $transS; }
	protected function getTranslationService(){
		if(!isset($this->transS)){
			$this->transS = ServiceProvider::getTranslationService();
		}
		return $this->transS;
	}
	private $groupAS;
	public function setGroupAdminService($groupAS){ $this->groupAS = $groupAS; }
	protected function getGroupAdminService(){
		if(!isset($this->groupAS)){
			$this->groupAS = ServiceProvider::getGroupAdminService();
		}
		return $this->groupAS;
	}

	private $id;
	protected function setId($id){ $this->id = $id; }
	public function getId(){ return $this->id; }

	private $p;
	public function setP($p){ $this->p = $p; }
	protected function getp(){ return $this->p; }

	public static function createInstance($p, $id){
		$r = new self();
		$r->setId($id);
		$r->setP($p);
		return $r;
	}

	//render group tree

	private $firstGroup;
	private $groupParentIdsStack;
	private $groupCrtParentId;
	private $groupLevel;
	private $nb;
	public function prepareRendering($p, $exec, $wigiiExecutor){
		$this->nb = 0;
		$transS = $this->getTranslationService();
		//because all the other divs are like spans
		?><ul id="<?=$this->getId();?>_treeview" class="simpleTree"><?
				?><img id="<?=$this->getId();?>_treeview_expandAll" class="" src="<?=SITE_ROOT_forFileUrl."images/gui/layout_tree_plus.png";?>" <?
					?> onmouseover=" showHelp(this, '<?=$transS->h($p, "expandAll");?>', 25, 'fromLeft'); " <?
					?> onmouseout=" hideHelp(); " <?
					?> /><?
				?><img id="<?=$this->getId();?>_treeview_collapseAll" class="" src="<?=SITE_ROOT_forFileUrl."images/gui/layout_tree_minus.png";?>" <?
					?> onmouseover=" showHelp(this, '<?=$transS->h($p, "collapseAll");?>', 25, 'fromLeft'); " <?
					?> onmouseout=" hideHelp(); " <?
					?> /><?
				?><div class="clear"></div><?
				?><li class="root" id="group_ALL_0"><?
					?><ul class="level1" ><?

		$this->firstGroup = true;
		$this->groupParentIdsStack = array("root"=>"root");
		$this->groupCrtParentId = "root";
		$this->groupLevel = 1;
	}

	public function renderItem($p, $rowId, $groupP){
		$this->nb++;
		//$groupP = $this->getMatrix()->getRowHeader($rowId);

		$thisParentId = $groupP->getGroup()->getGroupParentId();
		if($thisParentId == null) $thisParentId="root";

		$counter=0;
		//check if parent changes
		if($this->groupCrtParentId != $thisParentId){
			//check if this is a new parent or if we need to close parent
			if($this->groupParentIdsStack[$thisParentId] === null){
				$this->groupParentIdsStack[$thisParentId] = $thisParentId;
	//			eput("\nnew parent: ".$thisParentId);
				$this->groupCrtParentId = $thisParentId;
				$this->groupLevel ++;
				?><ul class="level<?=$this->groupLevel;?>"><?
			} else {
				//we need to close the ul till the parent match again
				while($counter < 10000 && $thisParentId != $this->groupCrtParentId){
					?></li></ul><?
					$this->groupLevel --;
	//				eput("\nclose parent: ".$crtParentId);
					array_pop($this->groupParentIdsStack);
					$this->groupCrtParentId = end($this->groupParentIdsStack);
	//				eput(" new parent: ".$crtParentId);
					$counter++;
				}
			}
		} else if(!$this->firstGroup) {
	//		eput("\nclose leaf");
			?></li><?
		}

		//we create the current li

		$wigiiNamespace = $groupP->getGroup()->getWigiiNamespace()->getWigiiNamespaceName();

		$help = ' onmouseover=" ';
		$help .= ' showHelp(this, \''.prepareTextForInfoBuble(($wigiiNamespace != $p->getWigiiNamespace()->getWigiiNamespaceName() ? $wigiiNamespace." : " : "" ).$groupP->getGroup()->getGroupName()."<br>".($groupP->getGroup()->getDetail()!=null && $groupP->getGroup()->getDetail()->getDescription()!=null ? "<span style='font-style:italic;' >".$groupP->getGroup()->getDetail()->getDescription()."</span><br>" : "")."<span class='grayFont idGrp' >Id:".$groupP->getGroup()->getId()."</span>", true).'\', 0, \'right\', 0, 200, 0); ';
		$help .= '" onmouseout=" ';
		$help .= ' hideHelp(); ';
		$help .= '" ';

		$style = ' -moz-user-select: none; -khtml-user-select: none; unselectable:on; user-select: none; ';

		$id = 'group_'.$groupP->getGroup()->getId(); //.'_'.(($groupP->getRights()->canWriteElement())?"1":"0");

		$class = "";
		$class .= " level".$this->groupLevel." ";
		if($groupP->getRights()==null){
			$class .= " disabled ";
		} else if(!$groupP->getRights()->canShareElement()){
			$class .= " RR ";
			$style .= "font-style:italic;";
		} else if(!$groupP->getRights()->canWriteElement()){
			$class .= " RS ";
			$style .= "font-style:italic;";
		} else if(!$groupP->getRights()->canModify()){
			$class .= " RW ";
			$style .= "font-style:italic;";
		} else $class .= " RX ";

		?><li id="<?=$id;?>" >&nbsp;<span id="row<?=$rowId;?>" class="<?=$class;?>" UNSELECTABLE="on" <?=$help;?> style="<?=$style; ?>" ><?

		if($wigiiNamespace!=null && $wigiiNamespace != $p->getWigiiNamespace()->getWigiiNamespaceName())
		echo $wigiiNamespace." : ";
		echo $groupP->getGroup()->getGroupName(); //.($markup ? " (".implode(", ", $markup).")" : "");

		?></span><?

		$this->firstGroup = false;
	}

	public function endRendering($p, $exec, $wigiiExecutor){
				?></li><?
			?></ul></li></ul><?

			?><div class="clear"></div><?
			$configS = $wigiiExecutor->getConfigurationContext();

			$idContextMenu = $this->getId()."_contextMenu";
			$crtWigiiNamespace = $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl();
			$crtModule = $exec->getCrtModule()->getModuleUrl();
			$workingModule = $wigiiExecutor->getAdminContext($p)->getWorkingModule();
			$workingModuleUrl = $workingModule->getModuleUrl();

			$exec->addJsCode("
$('#".$this->getId()."_list').css('position', 'relative');
$('#".$this->getId()."_treeview').css('position', 'relative').simpleTree({
	drag:false, animate:true, speed:false, docToFolderConvert:false,
	nbOfLevelToExpandOnInit:".$configS->getParameter($p, $workingModule, "nbOfLevelToExpandOnInit").",
	expandAllButton: $('#".$this->getId()."_treeview_expandAll'),
	collapseAllButton: $('#".$this->getId()."_treeview_collapseAll'),
	afterClick:function(li){if($(li).children('span.disabled.text').length == 0){ }},
	afterToggleOrClick:function(cross){
		adminGroupDetailOnToggleTreeNode();
	}
});

$('#".$this->getId()."_treeview span').click(function(e){
	if($(this).hasClass('disabled')){
		return;
	}
	$('#".$this->getId()."_treeview *').removeClass('selected');
	$(this).addClass('selected');
	adminGroup_crtSelectedGroup = $(this).attr('id').substr(3);
	if($(this).hasClass('RR') || $(this).hasClass('RS')){
		$('#adminGroup_detail .commands div.groupEdit').addClass('disabled');
		$('#adminGroup_detail .commands div.groupDelete').addClass('disabled');
		$('#adminGroup_detail .commands div.groupConfigEdit').addClass('disabled');
		$('#adminGroup_detail .commands div.groupEmailNotification').addClass('disabled');
		$('#adminGroup_detail .commands div.groupPortal').addClass('disabled');
		$('#adminGroup_detail .commands div.groupHtmlContent').addClass('disabled');
		$('#adminGroup_detail .commands div.groupXmlPublish').addClass('disabled');
		$('#adminGroup_detail .commands div.groupSubscription').addClass('disabled');
	} else if($(this).hasClass('RW')){
		$('#adminGroup_detail .commands div.groupEdit').addClass('disabled');
		$('#adminGroup_detail .commands div.groupDelete').addClass('disabled');
		$('#adminGroup_detail .commands div.groupConfigEdit').addClass('disabled');
		$('#adminGroup_detail .commands div.groupEmailNotification').addClass('disabled');
		$('#adminGroup_detail .commands div.groupXmlPublish').addClass('disabled');
		$('#adminGroup_detail .commands div.groupSubscription').addClass('disabled');
		$('#adminGroup_detail .commands div.groupPortal').removeClass('disabled');
		$('#adminGroup_detail .commands div.groupHtmlContent').removeClass('disabled');
	} else if($(this).hasClass('RX')){
		if(	($(this).hasClass('level1') &&  ".($p->getRootGroupCreator($workingModule)!=null ? "true" : "false").") ||
			(!$(this).hasClass('level1') && (!$('#adminFilterGroup input:first').hasClass('empty') || $(this).parent().parent().parent().find('span:first').hasClass('RX')))
			){
			$('#adminGroup_detail .commands div.groupEdit').removeClass('disabled');
			$('#adminGroup_detail .commands div.groupDelete').removeClass('disabled');
			$('#adminGroup_detail .commands div.groupConfigEdit').removeClass('disabled');
			$('#adminGroup_detail .commands div.groupEmailNotification').removeClass('disabled');
			$('#adminGroup_detail .commands div.groupXmlPublish').removeClass('disabled');
			$('#adminGroup_detail .commands div.groupSubscription').removeClass('disabled');
		} else {
			$('#adminGroup_detail .commands div.groupEdit').addClass('disabled');
			$('#adminGroup_detail .commands div.groupDelete').addClass('disabled');
			$('#adminGroup_detail .commands div.groupConfigEdit').removeClass('disabled');
			$('#adminGroup_detail .commands div.groupEmailNotification').removeClass('disabled');
			$('#adminGroup_detail .commands div.groupXmlPublish').removeClass('disabled');
			$('#adminGroup_detail .commands div.groupSubscription').removeClass('disabled');
		}
		$('#adminGroup_detail .commands div.groupPortal').removeClass('disabled');
		$('#adminGroup_detail .commands div.groupHtmlContent').removeClass('disabled');
	}
	update('elementDetail/$crtWigiiNamespace/$crtModule/groupDetail/$workingModuleUrl/'+adminGroup_crtSelectedGroup);
});

li = $('#".$this->getId()."_treeview #row'+adminGroup_crtSelectedGroup);
if(li.length>0){
	$(li).parents('.folder-close').removeClass('folder-close').addClass('folder-open');
	$(li).parents('.folder-close-last').removeClass('folder-close-last').addClass('folder-open-last');
	$(li).parents(':hidden:not(.description)').css('display','block');
	$('#".$this->getId()."_treeview #row'+adminGroup_crtSelectedGroup).click();
} else {
	if($('#".$this->getId()."_treeview span:first').length>0){
		li = $('#".$this->getId()."_treeview span:not(.disabled):first');
		$(li).parents('.folder-close').removeClass('folder-close').addClass('folder-open');
		$(li).parents('.folder-close-last').removeClass('folder-close-last').addClass('folder-open-last');
		$(li).parents(':hidden:not(.description)').css('display','block');
		li.click();
	} else {
		$('#adminGroup_detail .commands>div').not(':first').addClass('disabled');
	}
}

");

	}

	//GroupPList implementation
	private $crtGroupPForAddUserRP;
	public function addGroupP($groupP, $markup=null){
		$this->renderItem($this->getP(), $groupP->getGroup()->getId(), $groupP, $markup);
	}

	//ObjectList implementation
	public function getListIterator(){ throw new ServiceException('unsupported by this implementation', ServiceException::UNSUPPORTED_OPERATION); }
	public function isEmpty(){ return $this->nb==0; }
	public function count(){ return $this->nb; }


}


