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
 * Created on 4 march 2010
 * by LWR
 */

class MatrixUserPListRoleListRenderer extends MatrixRenderer implements UserList, UserPList {

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

	public function createInstance($p, $id, $workingModule){
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
		$user = $this->getMatrix()->getColHeader($colId);
		$style = "";
		$wigiiNamespace = $user->getWigiiNamespace()->getWigiiNamespaceName();
		$help = ($wigiiNamespace!=null ? $wigiiNamespace." : ":"").$user->getUsername();
		$description = $user->getDetail()->getDescription();
		if($description != null) $help .= "<br>".$description;
		$help .= "<br><span class=\'grayFont\'>Id: ".$user->getId()."</span>";
		if($user->isRole()){
			$style .= "font-weight:bold;";
		}
		if($user->getDetail()->hasAdminLevel()){
			$class .= " hasAdminLevel";
			$style .= "color:#D40D12;";
		}

		?><div id="col<?=$colId;?>" style="<?=$style;?>" onmouseover="showHelp(this, '<?=prepareTextForInfoBuble($help, true);?>', 25, 'fromLeft', 0, 200, 0)" onmouseout="hideHelp();" ><?
		echo $user->getUsername().($wigiiNamespace != $p->getWigiiNamespace()->getWigiiNamespaceName()?" (".$wigiiNamespace.")":"");
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
	}

	public function renderRowHeader($p, $exec, $wigiiExecutor, $rowId){
		$userP = $this->getMatrix()->getRowHeader($rowId);
		$user = $userP->getUser();
		$style = "";
		$wigiiNamespace = $user->getWigiiNamespace()->getWigiiNamespaceName();
		$help = ($wigiiNamespace!=null ? $wigiiNamespace." : ":"").$user->getUsername();
		$description = $user->getDetail()->getDescription();
		if($description != null) $help .= "<br>".$description;
		$help .= "<br><span class=\'grayFont\'>Id: ".$user->getId()."</span>";

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
		if($val) $val = ' checked="on" ';
		else $val = null;

		$_colId = str_replace("col", "", $colId);
		$_rowId = str_replace("row", "", $rowId);
		//detection of same id, or same than principal
		//here we miss the case where it is a role with no ownership and where rowId == p->userId
		//in this implementation the getAllRoles does not give back the ownership
		if(	($_colId== $_rowId) ||
			($p->getUserId()==$_colId && $p->getRealUserId()==$_rowId)
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
	clickOnAcitivityInUserRoleMatrixItems(this, '$crtWigiiNamespace', '$crtModule', '$workingModule');
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

	//roleList implementation
	private $fromUserRoles = false;
	public function addUser($user){
		$id = $user->getId();
		$m = $this->getMatrix();

		//creating the columns
		if(!$this->fromUserRoles){
			if($m->colExists($id)) throw new ListException("User $id already exist", ListException::ALREADY_EXISTS);
			$m->addCol($id, $user);
			return;
		}

		//adding the values
		if(!$m->colExists($user->getId())){
			return; //the matrix is paged
		}
		$m->setValue($this->fromUserRoles, $user->getId(), true);
	}

	//userPList implementation
	public function addUserP($userP){
		$id = $userP->getUser()->getId();
		$m = $this->getMatrix();

		if($m->rowExists($id)) throw new ListException("UserP $id already exist", ListException::ALREADY_EXISTS);
		$m->addRow($id, $userP);

		//getAllUserUsers
		$this->fromUserRoles = $id;
		$this->getUserAdminService()->getAllUserRoles($this->getP(), $id, $this);
		$this->fromUserRoles = false;
	}

	//ObjectList implementation
	public function getListIterator(){ throw new ServiceException('unsupported by this implementation', ServiceException::UNSUPPORTED_OPERATION); }
	public function isEmpty(){ throw new ServiceException('unsupported by this implementation', ServiceException::UNSUPPORTED_OPERATION); }
	public function count(){ throw new ServiceException('unsupported by this implementation', ServiceException::UNSUPPORTED_OPERATION); }


}


