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
 * UserP - UserP matrix (Owned Users per User)
 * Created on 20 janv. 10 by LWR
 * Modified by Medair in 2016 for maintenance purposes (see SVN log for details)
 */
class MatrixUserPListUserPListRenderer extends MatrixRenderer implements UserPList, UserURPList {

	private $transS;
	public function setTranslationService($transS){ $this->transS = $transS; }
	protected function getTranslationService(){
		if(!isset($this->transS)){
			$this->transS = ServiceProvider::getTranslationService();
		}
		return $this->transS;
	}
	private $userAS;
	public function setUserAdminService($userAS){ $this->userAS = $userAS; }
	protected function getUserAdminService(){
		if(!isset($this->userAS)){
			$this->userAS = ServiceProvider::getUserAdminService();
		}
		return $this->userAS;
	}
	private $p;
	public function setP($p){ $this->p = $p; }
	protected function getp(){ return $this->p; }
	private $workingModule;
	public function setWorkingModule($workingModule){ $this->workingModule = $workingModule; }
	protected function getWorkingModule(){ return $this->workingModule; }

	public static function createInstance($p, $id, $workingModule){
		$r = new self();
		$r->setId($id);
		$r->setP($p);
		$r->setWorkingModule($workingModule);
		return $r;
	}

	//MatrixRenderer implementation
	public function prepareRenderingColHeader($p, $exec, $wigiiExecutor){

	}
	public function renderColHeader($p, $exec, $wigiiExecutor, $colId){
		$userP = $this->getMatrix()->getColHeader($colId);
		$user = $userP->getUser();
		$style = "";
		$wigiiNamespace = $user->getWigiiNamespace()->getWigiiNamespaceName();
		$help = ($wigiiNamespace!=null ? $wigiiNamespace." : ":"").$user->getUsername();
		if($user->getDetail() != null) $description = $user->getDetail()->getDescription();
		else {
			//this means the principal has nor access on this user
			$style .= "color:#ccc;";
		}
		if($description != null) $help .= "<br>".$description;
		$help .= "<br><span class=\'grayFont\'>Id: ".$user->getId()."</span>";

//		echo $userP->getUser()->getUsername();
//		return;

		if($user->isRole()){
			$style .= "font-weight:bold;";
		}
		if($user->getDetail()->hasAdminLevel()){
			$class .= " hasAdminLevel";
			$style .= "color:#D40D12;";
		}

		?><div id="col<?=$colId;?>" style="<?=$style;?>" onmouseover="showHelp(this, '<?=prepareTextForInfoBuble($help, true);?>', 25, 'fromLeft', 0, 200, 0)" onmouseout="hideHelp();" ><?
		echo $userP->getUser()->getUsername().($wigiiNamespace != $p->getWigiiNamespace()->getWigiiNamespaceName()?" (".$wigiiNamespace.")":"");
		?></div><?
	}
	public function endRenderingColHeader($p, $exec, $wigiiExecutor){
		$exec->addJsCode("
$('#".$this->getId()." .MatrixColHeaders td>div>div').mouseenter(highlightFromColHeader).mouseleave(unHighlight);
");
	}


	public function prepareRenderingRowHeader($p, $exec, $wigiiExecutor){
		//add a first empty div to make the scrolling effect
		?><div class="clear"></div><?

		if(false){
		//prepare the context menu
		$idContextMenu = $this->getId()."_user_contextMenu";
		?><div id="<?=$idContextMenu;?>" class="contextMenu grayBorder lightGray" style="display:none;"><?
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
				?><div class="button grayBorder white <?=$act;?>" onmouseover="showHelp(this, '<?=$this->getTranslationService()->h($p, $act."_help");?>', -0, 'right', 0, 200, 0);" onmouseout="hideHelp();" ><?=$img.$this->getTranslationService()->t($p, $act);?></div><?
			}
		?></div><?
		$crtWigiiNamespace = $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl();
		$crtModule = $exec->getCrtModule()->getModuleUrl();
		$workingModule = $this->getWorkingModule()->getModuleUrl();

		$exec->addJsCode("
$('#".$idContextMenu."').mouseleave(function(e){
	hideStandardContextMenu(e, this, '".$idContextMenu."');
});

$('#".$idContextMenu." div.button').click(function(e){
	clickOnAcitivityInUserUserMatrixUserContextMenu(this, '$crtWigiiNamespace', '$crtModule', '$workingModule');
});

$('#".$this->getId()." .MatrixRowHeaders div.userHeader').mouseenter(function(e){
	showStandardContextMenu(e, this, '".$idContextMenu."');
}).mouseleave(function(e){
	hideStandardContextMenu(e, this, '".$idContextMenu."');
});
$('#".$this->getId()." .MatrixRowHeaders').mouseleave(function(e){
	hideStandardContextMenu(e, this, '".$idContextMenu."');
});
");
		}
	}

	public function renderRowHeader($p, $exec, $wigiiExecutor, $rowId){
		$userP = $this->getMatrix()->getRowHeader($rowId);
		$user = $userP->getUser();
		$style = "";
		$wigiiNamespace = $user->getWigiiNamespace()->getWigiiNamespaceName();
		$help = $user->getUsername().($wigiiNamespace!=null ? " (".$wigiiNamespace.")":"");
		$description = $user->getDetail()->getDescription();
		if($description != null) $help .= "<br>".$description;
		$help .= "<br><span class=\'grayFont\'>Id: ".$user->getId()."</span>";

//		echo $userP->getUser()->getUsername();
//		return;

		if($user->isRole()){
			$style .= "font-weight:bold;";
		}
		$class = "";
		if($user->getDetail()->hasAdminLevel()){
			$class .= " hasAdminLevel";
			$style .= "color:#D40D12;";
		}

		?><div id="row<?=$rowId;?>" class="userHeader <?=$class;?>" style="<?=$style;?>" onmouseover=" highlightFromRowHeader('row<?=$rowId;?>');showHelp(this, '<?=prepareTextForInfoBuble($help, true);?>', 25, 'right', 0, 200, 0)" onmouseout="hideHelp();" ><?
		if($wigiiNamespace != null && $wigiiNamespace!=$p->getWigiiNamespace()->getWigiiNamespaceName()) echo $wigiiNamespace." : ";
		echo (($userP->getUser()->isRole() && (strpos($userP->getUser()->getUsername(), "@")!==false)) ? str_replace("@", '<font class="" style="margin-left:5px;font-weight:normal;color:#444;">(@', $userP->getUser()->getUsername()).')</font>': $userP->getUser()->getUsername());
		?></div><?
	}

	public function endRenderingRowHeader($p, $exec, $wigiiExecutor){

	}


	public function prepareRenderingValues($p, $exec, $wigiiExecutor){

	}
	public function renderValue($p, $exec, $wigiiExecutor, $rowId, $colId){
		$val = $this->getMatrix()->getValue($rowId, $colId);
		$userP = $this->getMatrix()->getColHeader($colId);
		if($val) $val = 'checked="on"';
		else $val = null;
		//detection of same id
		$_rowId = str_replace("row", "", $rowId);
		$_colId = str_replace("col", "", $colId);
		if(	($_rowId == $_colId) ||
			($p->getUserId() == $_rowId && (
				(($p->getWigiiNamespace()->getWigiiNamespaceName() == $userP->getUser()->getWigiiNamespace()->getWigiiNamespaceName()) && !$p->isReadAllUsersInWigiiNamespace()) ||
				(($p->getWigiiNamespace()->getWigiiNamespaceName() != $userP->getUser()->getWigiiNamespace()->getWigiiNamespaceName()) && !$p->isWigiiNamespaceCreator())
				)
			)
			){
			$val .= ' disabled="on" class="disabled" ';
		}
		?><input type="checkbox" <?=$val;?> /><?
	}
	public function endRenderingValues($p, $exec, $wigiiExecutor){
		$transS = $this->getTranslationService();
		$crtWigiiNamespace = $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl();
		$crtModule = $exec->getCrtModule()->getModuleUrl();
		$workingModule = $this->getWorkingModule()->getModuleUrl();

		$exec->addJsCode("
$('#".$this->getId()." .MatrixItems div:has(input:checked)').addClass('lightRed');
$('#".$this->getId()." .MatrixItems div').click(function(e){
	e.stopPropagation();
	$(this).find('input').get(0).checked = !$(this).find('input').get(0).checked;
	$(this).addClass('lightYellow').removeClass('lightRed');
	clickOnAcitivityInUserUserMatrixItems(this, '$crtWigiiNamespace', '$crtModule', '$workingModule');
}).mouseenter(function(e){
	user2 = $(this).parent().attr('class');
	user2 = $('#'+user2).text();
	showHelp(this, user2, 20, 'right', 0, 200, 0, e);
}).mouseleave(hideHelp);
$('#".$this->getId()." .MatrixItems div input').click(function(e){
	this.checked = !this.checked;
});
");
	}

	protected $createCol = false;
	public function setCreateCol(){
		$this->createCol = true;
	}
	public function setCreateRow(){
		$this->createCol = false;
	}
	//UserPList implementation
	public function addUserP($userP){
		$id = $userP->getUser()->getId();
		$m = $this->getMatrix();

		if($this->createCol){
			if($m->colExists($id)) throw new ListException("UserP $id already exist", ListException::ALREADY_EXISTS);
			$m->addCol($id, $userP);
			return;
		}

		if($m->rowExists($id)) throw new ListException("UserP $id already exist", ListException::ALREADY_EXISTS);
		$m->addRow($id, $userP);

		//getAllUserUsers
		$this->getUserAdminService()->getAllUserUsers($this->getP(), $id, $this);
	}

	//addValues
	public function addUserURP($userURP){
		$m = $this->getMatrix();
		$userId1 = $userURP->getUserUR()->getRights()->getUser1Id();
		$userId2 = $userURP->getUserUR()->getRights()->getUser2Id();
		//filter
		if($m->colExists($userId2)){
			$m->setValue($userId1, $userId2, $userURP->getUserUR()->getRights()->isOwner());
		} else {
			//this is users that the P cannot access
			//eput($userURP->getUserUR()->getUser()->getUsername()." ".$userId2);
		}
	}

	//ObjectList implementation
	public function getListIterator(){ throw new ServiceException('unsupported by this implementation', ServiceException::UNSUPPORTED_OPERATION); }
	public function isEmpty(){ throw new ServiceException('unsupported by this implementation', ServiceException::UNSUPPORTED_OPERATION); }
	public function count(){ throw new ServiceException('unsupported by this implementation', ServiceException::UNSUPPORTED_OPERATION); }
}
