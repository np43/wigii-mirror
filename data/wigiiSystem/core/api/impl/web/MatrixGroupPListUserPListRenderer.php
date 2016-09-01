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
 * GroupP - UserP matrix (Group rights per User and Roles)
 * Created on 20 janv. 10 by LWR
 */
class MatrixGroupPListUserPListRenderer extends MatrixRenderer implements GroupPList, UserPList, UserRPList {

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
	private $p;
	public function setP($p){ $this->p = $p; }
	protected function getP(){ return $this->p; }

	private $ac;
	public function setAdminContext($ac){ $this->ac = $ac; }
	protected function getAdminContext(){ return $this->ac; }

	private $crtPage;
	public function setCrtPage($page){ $this->crtPage = $page; }
	protected function getCrtPage(){ return $this->crtPage; }
	private $pageSize;
	public function setPageSize($size){ $this->pageSize = $size; }
	protected function getPageSize(){ return $this->pageSize; }
	private $totalNbOfUsers;
	public function setTotalNumberOfUsers($total){ $this->totalNbOfUsers = $total; }
	protected function getTotalNumberOfUsers(){ return $this->totalNbOfUsers; }

	//we need the adminContext to add the userListFilter to the GroupAdminService->getAllUsers()
	public function createInstance($p, $id, $ac){
		$r = new self();
		$r->setId($id);
		$r->setP($p);
		$r->setAdminContext($ac);
		return $r;
	}

	//MatrixRenderer implementation
	public function prepareRenderingColHeader($p, $exec, $wigiiExecutor){
		if(false){
		//prepare the context menu
		$idContextMenu = $this->getId()."_user_contextMenu";
		?><div id="<?=$idContextMenu;?>" class="contextMenu BSB lightGray" style="display:none;"><?
			?><div class="title"><?=$this->getTranslationService()->t($p, "userActivities");?></div><?
			$activities = array("userEdit", "userDelete");
			foreach($activities as $act){
				switch($act){
					case "userEdit":
						$img = '<img src="'.SITE_ROOT_forFileUrl.'/images/icones/18px/edit.png" align="top" /> ';
						break;
					case "userDelete":
						$img = '<img src="'.SITE_ROOT_forFileUrl.'/images/icones/18px/cancel.png" align="top" /> ';
						break;
					default: $img = null;
				}
				?><div class="button SBB white <?=$act;?>" onmouseover="showHelp(this, '<?=$this->getTranslationService()->h($p, $act."_help");?>', -0, 'left', 0, 200, 0);" onmouseout="hideHelp();" ><?=$img.$this->getTranslationService()->t($p, $act);?></div><?
			}
		?></div><?
		$crtWigiiNamespace = $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl();
		$crtModule = $exec->getCrtModule()->getModuleUrl();
		$workingModule = $this->getAdminContext()->getWorkingModule()->getModuleUrl();

		$exec->addJsCode("
$('#".$idContextMenu."').mouseleave(function(e){
	hideStandardContextMenu(e, this, '".$idContextMenu."');
});

$('#".$idContextMenu." div.button').click(function(e){
	clickOnAcitivityInGroupUserMatrixUserContextMenu(this, '$crtWigiiNamespace', '$crtModule', '$workingModule');
});

$('#".$this->getId()." .MatrixColHeaders td>div>div').mouseenter(function(e){
	showStandardContextMenu(e, this, '".$idContextMenu."');
}).mouseleave(unHighlight).mouseleave(function(e){
	hideStandardContextMenu(e, this, '".$idContextMenu."');
});
$('#".$this->getId()." .MatrixColHeaders').mouseleave(function(e){
	hideStandardContextMenu(e, this, '".$idContextMenu."');
});
");
		}
	}
	public function renderColHeader($p, $exec, $wigiiExecutor, $colId){
		$userP = $this->getMatrix()->getColHeader($colId);
		if(is_a($userP, "UserRP")){
			$user = $userP->getUserR()->getUser();
		} else {
			$user = $userP->getUser();
		}
		$wigiiNamespace = $user->getWigiiNamespace()->getWigiiNamespaceName();
		$help = ($wigiiNamespace!=null?$wigiiNamespace." : ":"").$user->getUsername();

		if($userP->getRights()==null){
			$class = "noRights grayFont";
			$help .= "<br>".$this->getTranslationService()->h($p, "noRightsOnUser");
		} else {
			$class = "";
			if($user->getDetail()!=null){
				$description = $user->getDetail()->getDescription();
			}
			if($description != null) $help .= "<br>".$description;
		}
		$help .= "<br><span class=\'grayFont\'>Id: ".$user->getId()."</span>";

//		echo $userP->getUser()->getUsername();
//		return;

		$style = "";
		if($user->isRole()){
			$style .= "font-weight:bold;";
		}
		if($user->getDetail()!=null && $user->getDetail()->hasAdminLevel()){
			$class .= " hasAdminLevel";
			$style .= "color:#D40D12;";
		}

		?><div class="<?=$class;?>" id="col<?=$colId;?>" style="<?=$style;?>" onmouseover="showHelp(this, '<?=prepareTextForInfoBuble($help, true);?>', 20, 'fromLeft', 0, 200, 0)" onmouseout="hideHelp();" ><?
		echo $user->getUsername().($wigiiNamespace!=null && $wigiiNamespace != $p->getWigiiNamespace()->getWigiiNamespaceName()?" (".$wigiiNamespace.")":"");
		?></div><?
	}
	public function endRenderingColHeader($p, $exec, $wigiiExecutor){
		$exec->addJsCode(" $('#".$this->getId()." .MatrixColHeaders td>div>div').mouseenter(highlightFromColHeader); ");
	}


	//render group tree

	private $firstGroup;
	private $groupParentIdsStack;
	private $groupCrtParentId;
	private $groupLevel;
	public function prepareRenderingRowHeader($p, $exec, $wigiiExecutor){

		//add a first empty div to make the scrolling effect
		?><div class="clear"></div><?

		//because all the other divs are like spans
		?><ul id="groupAdminForMatrix_treeview" class="simpleTree"><?
				?><li class="root" id="group_ALL_0"><?
					?><ul class="level1" ><?

		$this->firstGroup = true;
		$this->groupParentIdsStack = array("root"=>"root");
		$this->groupCrtParentId = "root";
		$this->groupLevel = 1;
	}

	public function renderRowHeader($p, $exec, $wigiiExecutor, $rowId){
		$groupP = $this->getMatrix()->getRowHeader($rowId);

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

		$help = ' onmouseover=" highlightFromRowHeader(\'row'.$rowId.'\'); ';
		$help .= ' showHelp(this, \''.prepareTextForInfoBuble(($wigiiNamespace!=null && $wigiiNamespace != $p->getWigiiNamespace()->getWigiiNamespaceName() ? $wigiiNamespace." : " : "" ).$groupP->getGroup()->getGroupName()."<br>".($groupP->getGroup()->getDetail()!=null && $groupP->getGroup()->getDetail()->getDescription()!=null ? "<span style='font-style:italic;' >".$groupP->getGroup()->getDetail()->getDescription()."</span><br>" : "")."<span class='grayFont idGrp' >Id:".$groupP->getGroup()->getId()."</span>", true).'\', 0, \'right\', 0, 200, 0); ';
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
		} else if(!$groupP->getRights()->canWriteElement()){
			$class .= " RS ";
		} else if(!$groupP->getRights()->canModify()){
			$class .= " RW ";
		} else $class .= " RX ";

		?><li id="<?=$id;?>" ><div id="row<?=$rowId;?>" class="divFolder <?=$class;?>" UNSELECTABLE="on" <?=$help;?> style="<?=$style; ?>" ><?

		if($wigiiNamespace!=null && $wigiiNamespace != $p->getWigiiNamespace()->getWigiiNamespaceName())
		echo $wigiiNamespace." : ";
		echo $groupP->getGroup()->getGroupName();

		?></div><?

		$this->firstGroup = false;
	}

	public function endRenderingRowHeader($p, $exec, $wigiiExecutor){
				?></li><?
			?></ul></li></ul><?

			?><div class="clear"></div><?

	}


	public function prepareRenderingValues($p, $exec, $wigiiExecutor){
		//prepare the context menu
		$idContextMenu = $this->getId()."_value_contextMenu";
		?><div id="<?=$idContextMenu;?>" class="contextMenu SBB SB" style="display:none;"><?
			?><div class="title SB"><?=$this->getTranslationService()->t($p, "changeRightsTo");?></div><?
			$activities = array("ugr_", "ugr_r", "ugr_s", "ugr_w");
			if($p->isAdminCreator() && $p->getGroupCreator($this->getAdminContext()->getWorkingModule())) {
				$activities[] = "ugr_x";
			}
			foreach($activities as $act){
				switch($act){
					case "ugr_":
						$img = '<img src="'.SITE_ROOT_forFileUrl.'/images/icones/18px/cancel.png" align="top" /> ';
						break;
					case "ugr_r":
						$img = '<img src="'.SITE_ROOT_forFileUrl.'/images/icones/18px/details.png" align="top" /> ';
						break;
					case "ugr_s":
						$img = '<img src="'.SITE_ROOT_forFileUrl.'/images/icones/tango/16x16/actions/edit-find-replace.png" align="top" /> ';
						break;
					case "ugr_w":
						$img = '<img src="'.SITE_ROOT_forFileUrl.'/images/icones/18px/edit.png" align="top" /> ';
						break;
					case "ugr_x":
						$img = '<img src="'.SITE_ROOT_forFileUrl.'/images/icones/tango/16x16/categories/applications-system.png" align="top" /> ';
						break;
					default: $img = null;
				}
				?><div class="button SBB H <?=$act;?>" style="background:#fff;" onmouseover="showHelp(this, '<?=$this->getTranslationService()->h($p, $act."_help");?>', -0, 'left', 0, 200, 0);" onmouseout="hideHelp();" ><?=$img.$this->getTranslationService()->t($p, $act);?></div><?
			}
		?></div><?
		$crtWigiiNamespace = $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl();
		$crtModule = $exec->getCrtModule()->getModuleUrl();
		$workingModule = $this->getAdminContext()->getWorkingModule()->getModuleUrl();

		$exec->addJsCode("
$('#".$idContextMenu."').mouseleave(function(e){
	hideValueContextMenu(e, this, '".$idContextMenu."');
});

$('#".$idContextMenu." div.button').click(function(e){
	clickOnRightInGroupUserMatrixValueContextMenu(this, '$crtWigiiNamespace', '$crtModule', '$workingModule');
	hideValueContextMenu(e, this, '".$idContextMenu."');
});

$('#".$this->getId()." .MatrixItems td>div').mouseenter(function(e){
	showHelp(this, $('#'+$(this).parent().attr('class')).text(), 20, 'right', 0, 200, 0, e);
}).click(function(e){
	hideHelp();
	if($('div.disabled', this).length>0){
		return;
	}
	showValueContextMenu(e, this, '".$idContextMenu."');
	$('#$idContextMenu .title').append('<span>&nbsp;'+$('#'+$(this).parent().attr('class')).text()+'</span>');
	if($('#$idContextMenu .title span').length > 1){
		$('#$idContextMenu .title span:first').remove();
	}
}).mouseleave(function(e){
	hideHelp();
	hideValueContextMenu(e, this, '".$idContextMenu."');
})
;
$('#".$this->getId()." .MatrixItems').mouseleave(function(e){
	hideValueContextMenu(e, this, '".$idContextMenu."');
});

$('#".$this->getId()." .MatrixColHeaders div.noRights').each(function(){
	$('#".$this->getId()." .MatrixItems .'+$(this).attr('id')).css('color', '#666').css('background-color','#ccc');
});


");
	}
	public function renderValue($p, $exec, $wigiiExecutor, $rowId, $colId){

		$val = $this->getMatrix()->getValue($rowId, $colId);
		//check that id_user in ugr is not the current principal
		$groupP = $this->getMatrix()->getRowHeader($rowId);
		$disabled = "";
		if($p->getUserId()==$colId && (
			($groupP->getGroup()->getWigiiNamespace()->getWigiiNamespaceName() == $p->getWigiiNamespace()->getWigiiNamespaceName() && !$p->getReadAllGroupsInWigiiNamespace($groupP->getGroup()->getModule())) ||
			($groupP->getGroup()->getWigiiNamespace()->getWigiiNamespaceName() != $p->getWigiiNamespace()->getWigiiNamespaceName() && !$p->isWigiiNamespaceCreator())
			)
			){
			$disabled = 'disabled';
		}
		if($val == "x" && (
			!$p->isAdminCreator() ||
			!$p->getGroupCreator($groupP->getGroup()->getModule())
			)){
			$disabled = 'disabled';
		}
		?><div class="ugr_<?=$val.($disabled ? " ".$disabled : "");?>" ><?
		echo ($val==null ? "&nbsp;" : $this->getTranslationService()->t($p, "ugr_".$val));
		?></div><?
	}
	public function endRenderingValues($p, $exec, $wigiiExecutor){

	}

	//GroupPList implementation
	private $crtGroupPForAddUserRP;
	public function addGroupP($groupP){
		$id = $groupP->getGroup()->getId();
		$m = $this->getMatrix();
		if($m->rowExists($id)) throw new ListException("GroupP $id already exist", ListException::ALREADY_EXISTS);
		$m->addRow($id, $groupP);

		//load the values
		if($groupP->getRights() == null) return;
		$this->crtGroupPForAddUserRP = $groupP;
		$lf = $this->getAdminContext()->getUserListFilter();
		//if the folder is in another namespace than the Principal then don't find the roles outside of current namespace
		$origLE = $lf->getFieldSelectorLogExp();
		if($groupP->getGroup()->getWigiiNamespace()->getWigiiNamespaceName()!= $this->getP()->getWigiiNamespace()->getWigiiNamespaceName()){
			$fsle = LogExp::createAndExp();
			$fsle->addOperand(LogExp::createEqualExp(FieldSelector::createInstance("wigiiNamespace"), $this->getP()->getWigiiNamespace()->getWigiiNamespaceName()));
			if($origLE){
				$fsle->addOperand($origLE->reduceNegation(true));
			}
			$lf->setFieldSelectorLogExp($fsle);
		}
		$this->getGroupAdminService()->getAllUsers($this->getP(), $id, $this, $lf);
		$lf->setFieldSelectorLogExp($origLE);
	}

	//UserPList implementation
	public function addUserP($userP){
		$id = $userP->getUser()->getId();
		$m = $this->getMatrix();
		if($m->colExists($id)) throw new ListException("UserP $id already exist", ListException::ALREADY_EXISTS);
		$m->addCol($id, $userP);
	}

	public function addUserRP($userRP){

		$m = $this->getMatrix();

		//if principal has no rights on it, then create a new column at the end
		//check if the col is existing, if not, create it
		$userId = $userRP->getUserR()->getUser()->getId();
		if(!$m->colExists($userId)){
			if(($userRP->getRights()==null || !$userRP->getRights()->isOwner())){
				//if the matrix is paged, and if it is the last page:
				if($this->getCrtPage() == ceil($this->getTotalNumberOfUsers()/$this->getPageSize())){
					$m->addCol($userId, $userRP);
				} else {
					return;
				}
			} else {
				//if the matrix is paged the col is not existing
				return;
			}
		}
		//add the value
		$right = $userRP->getUserR()->getRights();
		if(!isset($right)) $val = "";
		else $val = $right->getLetter();
		$m->setValue($this->crtGroupPForAddUserRP->getGroup()->getId(), $userId, $val);
	}

	//ObjectList implementation
	public function getListIterator(){ throw new ServiceException('unsupported by this implementation', ServiceException::UNSUPPORTED_OPERATION); }
	public function isEmpty(){ throw new ServiceException('unsupported by this implementation', ServiceException::UNSUPPORTED_OPERATION); }
	public function count(){ throw new ServiceException('unsupported by this implementation', ServiceException::UNSUPPORTED_OPERATION); }
}