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
 * Created on 20 janv. 10 by LWR
 * Modified by Medair in 2016 for maintenance purposes (see SVN log for details)
 */

class UserPListRenderer implements UserPList {

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
	
	public static function createInstance($p, $id){
		$r = new self();
		$r->setId($id);
		$r->setP($p);
		return $r;
	}
	
	private $nb;
	public function prepareRendering($p, $exec, $wigiiExecutor){
		$this->nb = 0;
		$transS = ServiceProvider::getTranslationService();
		//add a first empty div to make the scrolling effect
		?><div class="clear"></div><?
		//add legend
		?><div class="userLegend SBB ui-corner-all" style="padding:5px;" ><?
//		echo "<b>".$transS->t($p, "roleAreBold")."</b>";
//		echo "<br />";
		echo '<font style="color:#f00;">'.$transS->t($p, "adminUsersAreRed").'</font>';
		?></div><?
		?><div class="clear"></div><?
		
	}

	public function renderItem($p, $rowId, $userP){
		$this->nb++;
		//$userP = $this->getMatrix()->getRowHeader($rowId);
		$user = $userP->getUser();
		$wigiiNamespace = $user->getWigiiNamespace()->getWigiiNamespaceName();
		$help = ""; //($wigiiNamespace!=null ? "".$wigiiNamespace." : ":"").$user->getUsername();
		$description = $user->getDetail()->getDescription();
		if($description != null) $help .= $description;
		if($help != null) $help .= "<br>";
		$help .= "<span class='grayFont'>Id: ".$user->getId()."</span>";

//		echo $userP->getUser()->getUsername();
//		return;
		
		$style = "";
		if($user->isRole()){
			//$style .= "font-weight:bold;";
		}
		
		$class = "";
		//if($user->getDetail()->hasAdminLevel()){
		if($user->getDetail()->hasAdminLevelForConsole()){
			$class .= " hasAdminLevel R";
			//$style .= "color:#D40D12;";
		}
		if($wigiiNamespace!=$p->getWigiiNamespace()->getWigiiNamespaceName()){
			$class .= " isOutOfWigiiNamespace ";
		}
		
		?><div id="row<?=$rowId;?>" class="H userHeader <?=$class;?>" style="<?=$style;?>" ><?
		if($p->isWigiiNamespaceCreator() || ($wigiiNamespace != null && $wigiiNamespace!=$p->getWigiiNamespace()->getWigiiNamespaceName())) echo '<font class="grayFont">'.$wigiiNamespace." : </font>";
		echo (($userP->getUser()->isRole() && (strpos($userP->getUser()->getUsername(), "@")!==false)) ? str_replace("@", '<font class="" style="margin-left:5px;font-weight:normal;color:#444;">(@', $userP->getUser()->getUsername()).')</font>': $userP->getUser()->getUsername());
		echo '<div class="description">'.$help.'</div>';
		?></div><?
	}

	public function endRendering($p, $exec, $wigiiExecutor){
		$crtWigiiNamespace = $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl();
		$crtModule = $exec->getCrtModule()->getModuleUrl();
		$workingModule = $wigiiExecutor->getAdminContext($p)->getWorkingModule()->getModuleUrl();
		
		$exec->addJsCode("
$('#".$this->getId()." div.userHeader').click(function(e){
	$('#".$this->getId()." *').removeClass('S');
	$(this).addClass('S');
	adminUser_crtSelectedUser = $(this).attr('id').substr(3);
	if(adminUser_crtSelectedUser==".$p->getUserId().") $('#adminUser_detail .commands>div.userEdit, #adminUser_detail .commands>div.userDelete').addClass('disabled');
	else $('#adminUser_detail .commands>div').removeClass('disabled');
	".(!$p->isAdminCreator() ? "if($(this).hasClass('hasAdminLevel')){
		$('#adminUser_detail .commands>div.userDelete').addClass('disabled');
	}" : "")."
	".(!$p->isWigiiNamespaceCreator() ? "if($(this).hasClass('isOutOfWigiiNamespace')){
		$('#adminUser_detail .commands>div.userDelete').addClass('disabled');
	}" : "")."
	self.location = '#$crtWigiiNamespace/$crtModule/$workingModule/user/'+adminUser_crtSelectedUser;
	update('elementDetail/$crtWigiiNamespace/$crtModule/userDetail/$workingModule/'+adminUser_crtSelectedUser);
}).hover(function(){ 
positionElementOnDom($('.description', this), $(this), 'right', 0); 
$('.description', this).show(); 
}, function(){ 
$('#".$this->getId()." .description').hide(); 
});

if($('#".$this->getId()." #row'+adminUser_crtSelectedUser).length>0){
	$('#".$this->getId()." #row'+adminUser_crtSelectedUser).click();
} else {
	if($('#".$this->getId()." div.userHeader:first').length>0){
		$('#".$this->getId()." div.userHeader:first').click();
	} else {
		$('#adminUser_detail .commands>div').not(':first').addClass('disabled').removeAttr('onclick');
	}
}

");
	}

	//UserPList implementation
	public function addUserP($userP){
		$this->renderItem($this->getP(), $userP->getUser()->getId(), $userP);
	}

	//ObjectList implementation
	public function getListIterator(){ throw new ServiceException('unsupported by this implementation', ServiceException::UNSUPPORTED_OPERATION); }
	public function isEmpty(){ return $this->nb == 0; }
	public function count(){ return $this->nb; }


}


