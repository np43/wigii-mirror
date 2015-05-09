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
 * Created on 5 October 2011
 * by LWR
 */

class UserRoleAllocationRenderer implements UserList, UserPList {

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

	private $id;
	protected function setId($id){ $this->id = $id; }
	public function getId(){ return $this->id; }

	private $p;
	public function setP($p){ $this->p = $p; }
	protected function getp(){ return $this->p; }

	private $orgRole;
	public function setOrgRoles($orgRole){ $this->orgRole = $orgRole; }
	protected function getOrgRoles(){ return $this->orgRole; }
	protected function isOrgRole($roleId){
		if(!isset($this->orgRole)) return null;
		return $this->orgRole[$roleId];
	}

	public function createInstance($p, $id){
		$r = new self();
		$r->setId($id);
		$r->setP($p);
		return $r;
	}

	private $nb;
	public function prepareRendering($p, $exec, $wigiiExecutor){
		$this->nb = 0;
		$transS = ServiceProvider::getTranslationService();
		//add a filter input:
		?><input id="UserRoleAllocationFilterInput" class="SBIB" style="padding:2px;float:left;border-right:none;" type="text" name="UserRoleAllocationFilter" /><div style="float:left;font-size:small;font-weight:bold;height:17px;padding:2px 15px;" class="H G SBIB"><?=$transS->t($p, "userRoleAllocationFilterText");?></div><div class="clear"></div><?

		$exec->addJsCode("setListenerToUserRoleAllocationFilter();");
		//add a first empty div to make the scrolling effect
		?><div style="margin:5px 5px 15px 5px;font-weight:bold;" class="introduction"><span class="nb">0</span>&nbsp;<span><?=($exec->getCrtAction()=="userRoleAllocation" ? $transS->t($p, "roleSelected") : $transS->t($p, "userSelected"));?></span></div><?
		?><div id="<?=$this->getId();?>"><?

	}

	public function renderItem($p, $rowId, $user){
		$this->nb++;
		$wigiiNamespace = $user->getWigiiNamespace()->getWigiiNamespaceName();
		$help = ""; //($wigiiNamespace!=null ? "".$wigiiNamespace." : ":"").$user->getUsername();
		if($user->getDetail()) $description = $user->getDetail()->getDescription();
		if($description != null) $help .= $description;
		if($help != null) $help .= "<br>";
		$help .= "<span class='grayFont'>Id: ".$user->getId()."</span>";

		$style = "";
		$class = "";
		if($user->getDetail() && $user->getDetail()->hasAdminLevel()){
			$class .= " hasAdminLevel R";
		}
		if($wigiiNamespace!=$p->getWigiiNamespace()->getWigiiNamespaceName()){
			$class .= " isOutOfWigiiNamespace ";
		}
		if($this->isOrgRole($user->getId())){
			$class .= " S origS";
		}

		?><div id="row<?=$rowId;?>" class="H userHeader <?=$class;?>" style="<?=$style;?>" ><?
		if($p->isWigiiNamespaceCreator() || ($wigiiNamespace != null && $wigiiNamespace!=$p->getWigiiNamespace()->getWigiiNamespaceName())) echo '<font class="grayFont">'.$wigiiNamespace." : </font>";
		echo $user->getUsername(); //(($user->isRole() && (strpos($user->getUsername(), "@")!==false)) ? str_replace("@", '<font class="" style="margin-left:5px;font-weight:normal;color:#444;">(@', $user->getUsername()).')</font>': $user->getUsername());
		echo '<div class="description">'.$help.'</div>';
		?></div><?
	}

	public function endRendering($p, $exec, $wigiiExecutor){
		$crtWigiiNamespace = $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl();
		$crtModule = $exec->getCrtModule()->getModuleUrl();
		$workingModule = $wigiiExecutor->getAdminContext($p)->getWorkingModule()->getModuleUrl();
		$transS = $this->getTranslationService();
		?></div><?
		$id = $this->getId();
		$exec->addJsCode("
$('#$id').height($('#adminUser_list').height()-80);
$('#$id>div').click(function(){
	$(this).toggleClass('S');
	$('#$id').parent().find('.introduction .nb').text($('#$id>div.S').length);
}).hover(function(){
positionElementOnDom($('.description', this), $(this), 'fromCenter', 30, false, true);
$('.description', this).show();
}, function(){
$('#$id .description').hide();
});
$('#$id').parent().find('.introduction .nb').text($('#$id>div.S').length);
");

	}

	//UserPList implementation
	public function addUser($role){
		$this->renderItem($this->getP(), $role->getId(), $role);
	}
	//RolePList implementation
	public function addUserP($userP){
		$this->renderItem($this->getP(), $userP->getId(), $userP->getDbEntity());
	}

	//ObjectList implementation
	public function getListIterator(){ throw new ServiceException('unsupported by this implementation', ServiceException::UNSUPPORTED_OPERATION); }
	public function isEmpty(){ return $this->nb == 0; }
	public function count(){ return $this->nb; }


}


