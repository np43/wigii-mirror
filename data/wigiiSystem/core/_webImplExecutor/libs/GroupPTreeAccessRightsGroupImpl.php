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
 * Created on 6 October 2011
 * by LWR
 */

class GroupPTreeAccessRightsGroupImpl extends GroupPTreeGroupPanelImpl {
	
	//vars from Parent
//	protected $depth = 0;
//	protected $nb;
//	protected $groups;
//	protected $p;
//	protected $exec;
//	protected $nbLevelToExpandOnInit;
//	protected $displayCM;
	
	public function createInstance($p, $exec, $nbLevelToExpandOnInit = 100, $displayContextMenu = false){
		$gt = new self();
		$gt->setNbLevelToExpandOnInit($nbLevelToExpandOnInit);
		$gt->setP($p);
		$gt->setExec($exec);
		$gt->setDisplayContextMenu($displayContextMenu);
		return $gt;
	}
	
	private $originalAR;
	public function setOriginalAccessRights($originalAR){
		$this->originalAR = $originalAR;
	}
	
	protected function writeHtmlForGroupItem($groupP){
		$group = $groupP->getGroup();
		$description = ($groupP->getGroup()->getDetail()!=null && $groupP->getGroup()->getDetail()->getDescription()!=null ? "".$groupP->getGroup()->getDetail()->getDescription()."" : "")."<p class='descrId'>Id:".$groupP->getGroup()->getId()."</p>";
		echo '<div style="'.$this->getStyleBasedOnDepth().'">';
		echo '<span class="folder">&nbsp;</span>';
		$width = 350;
		echo '<a style="'.($this->depth ? ' width:'.($width-($this->depth*20)).'px; ' : ' width:'.($width-10).'px; ').'" href="#" class="H">'.($groupP->getDbEntity()->getGroupParentId()==0 && ($this->getP()->isWigiiNamespaceCreator() || ($this->getP()->getWigiiNamespace()->getWigiiNamespaceName() != $group->getWigiiNamespace()->getWigiiNamespaceName() && $group->getWigiiNamespace()->getWigiiNamespaceName())) ? '<span class="grayFont">'.$group->getWigiiNamespace()->getWigiiNamespaceName()." : </span>" : "" ).$group->getGroupName().'</a>';
		echo '<span class="description">'.$description.'</span>';
		if($this->getDisplayContextMenu()) $this->displayContextMenu($groupP);
		echo '</div>';
	}
	
	protected function displayContextMenu($groupP){
		$transS = ServiceProvider::getTranslationService();
		$right = $this->originalAR[$groupP->getId()];
		if($groupP->getRights()!=null){ //} && $groupP->getRights()->canModify()){
			?><div class="SBB ar blackFont menu <?=($right ? $right." S selected origS" : '');?>"><?=($right==null ? $transS->t($this->getP(), "clicToDefineUgr") : $transS->t($this->getP(), "ugr_".$right));?></div><?
		} elseif($right) {
			?><div class="SBB ar menu <?=$right."";?>"><?=$transS->t($this->getP(), "ugr_".$right);?></div><?
		}
	}

	public function start($p, $exec){
		$transS = ServiceProvider::getTranslationService();
		?><div id="adminRoleAccessRightGroup_list" class="groupPanel"><?
		?><div class="cm SBB"><?
			?><div id="cm_exit" class="exit SBB">x</div><?
			?><div id="cm_ugr_" class="readOnly share write admin ugr_ H fB" onmouseover="showHelp(this, '<?=$transS->h($p, "ugr__help");?>', -0, 'left', 0, 200, 0);" onmouseout="hideHelp();"><?=$transS->t($p, "ugr_");?></div><?
			?><div id="cm_ugr_r" class="readOnly share write admin ugr_r H fB" onmouseover="showHelp(this, '<?=$transS->h($p, "ugr_r_help");?>', -0, 'left', 0, 200, 0);" onmouseout="hideHelp();"><?=$transS->t($p, "ugr_r");?></div><?
			?><div id="cm_ugr_s" class="share write admin ugr_s H fB" onmouseover="showHelp(this, '<?=$transS->h($p, "ugr_s_help");?>', -0, 'left', 0, 200, 0);" onmouseout="hideHelp();"><?=$transS->t($p, "ugr_s");?></div><?
			?><div id="cm_ugr_w" class="write admin ugr_w H fB" onmouseover="showHelp(this, '<?=$transS->h($p, "ugr_w_help");?>', -0, 'left', 0, 200, 0);" onmouseout="hideHelp();"><?=$transS->t($p, "ugr_w");?></div><?
			?><div id="cm_ugr_x" class="admin ugr_x H fB" onmouseover="showHelp(this, '<?=$transS->h($p, "ugr_x_help");?>', -0, 'left', 0, 200, 0);" onmouseout="hideHelp();"><?=$transS->t($p, "ugr_x");?></div><?
		?></div><?
		parent::start($p, $exec);
	}
	public function end($p, $exec){
		parent::end($p, $exec);
		?></div><?
	}
	
	protected function addEndingJsCode($p, $exec){
		$transS = ServiceProvider::getTranslationService();
		$exec->addJsCode("
$('#adminRoleAccessRightGroup_list>ul').css('height', $('#adminUser_list').outerHeight()-200);

setListenersToGroupTree('#adminRoleAccessRightGroup_list');
unfoldToSelectedGroup('#adminRoleAccessRightGroup_list');

$('#adminRoleAccessRightGroup_list li:not(.disabled)').unbind();
$('#adminRoleAccessRightGroup_list li:not(.disabled)').bind('contextmenu', function(e){
	$('>div>.menu', this).click();
	e.stopPropagation();
	return false;
});
$('#adminRoleAccessRightGroup_list li>div>a.H').click(function(e){ 
	$(this).prev().click();
	e.stopPropagation();
});
$('#adminRoleAccessRightGroup_list div.ar')
	.click(function(){ 
		$('#adminRoleAccessRightGroup_list .over').removeClass('over'); 
		$(this).parent().addClass('over'); 
	})
;
$('#adminRoleAccessRightGroup_list div.cm>div').click(function(e){
	$('#adminRoleAccessRightGroup_list .over>.menu')
		.removeClass('S selected x r w s');
	right = $(this).attr('id').split('_')[2];
	if(right != ''){
		$('#adminRoleAccessRightGroup_list .over>.menu')
			.addClass('M selected')
			.addClass(right)
			.text($(this).text());
	} else {
		$('#adminRoleAccessRightGroup_list .over>.menu')
			.addClass('M')
			.text('".$transS->t($this->getP(), "clicToDefineUgr")."');
	}
});

");
	}
}


