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
 * Created on 21 July 2011
 * by LWR
 */

class GroupPTreeGroupPanelImpl extends Model implements GroupPTree {
	
	protected $depth = 0;
	protected $nb;
	protected $groups;
	protected function setGroups($groups){ $this->groups = $groups; }
	protected function getGroups(){ 
		if(!isset($this->groups)){
			$this->groups = array();
		}
		return $this->groups; 
	}
	protected $p;
	protected function setP($p){ $this->p = $p;}
	protected function getP(){ return $this->p;}
	
	protected $exec;
	protected function setExec($exec){ $this->exec = $exec;}
	protected function getExec(){ return $this->exec;}
	
	protected $nbLevelToExpandOnInit;
	public function getNbLevelToExpandOnInit(){ return $this->nbLevelToExpandOnInit; }
	protected function setNbLevelToExpandOnInit($var){ $this->nbLevelToExpandOnInit = $var; }
	
	protected $trashBinGroup;
	/**
	 * Informs the GroupPanel of an eventual trashbin
	 * @param int $groupId the id of the group used as a trashbin, null if not initialized or not defined.
	 */
	public function setTrashBinGroup($groupId) {
		$this->trashBinGroup = $groupId;
	}
	/**
	 * @return int return the id of the group used as a trashbin or null if not defined.
	 */
	protected function getTrashBinGroup() {
		return $this->trashBinGroup;
	}
	
	protected $displayCM;
	public function getDisplayContextMenu(){ return $this->displayCM; }
	protected function setDisplayContextMenu($var){ $this->displayCM = $var; }
	
	protected $countData;
	public function getCountData(){ return $this->countData; }
	public function setCountData($countData){ $this->countData = $countData; }
	public function isCountDataForGroup(){ return isset($this->countData); } 
	public function getCountDataForGroup($groupId){ 
		if(!isset($this->countData)) return null;
		return $this->countData[$groupId];
	}
	
	public function createInstance($p, $exec, $nbLevelToExpandOnInit, $displayContextMenu = true, $countData=null){
		$gt = new self();
		$gt->setNbLevelToExpandOnInit($nbLevelToExpandOnInit);
		$gt->setP($p);
		$gt->setExec($exec);
		$gt->setDisplayContextMenu($displayContextMenu);
		$gt->setCountData($countData);
		return $gt;
	}
	
	/**
	 * Returns an iterator on this list
	 * Compatible with the foreach control structure
	 */
	public function getListIterator(){
		return $this->getGroups();
	}

	/**
	 * Returns true if the list is empty
	 */
	public function isEmpty(){
		return $this->nb == null;
	}

	/**
	 * Returns the number of items in the list
	 */
	public function count(){
		return $this->nb;
	}
	
	/**
	 * Adds a group to the tree
	 * parentGroup: the parent group to which attach the current group added
	 * can be null if no parent.
	 * throws ListException::ALREADY_EXISTS if we try to put a second time the same group in the tree
	 */
	private $parentStack = array();
	private function pushParent($parentGroup){
		if(!isset($this->parentStack)) $this->parentStack = array();
		$this->parentStack[$parentGroup->getId()] = $parentGroup;
	}
	private function popParent(){
		return array_pop($this->parentStack);
	}
	private function isCurrentParent($parentGroup){
		if($parentGroup == null){
			return $this->parentStack == null;
		}
		if($this->parentStack){
			$lastG = end($this->parentStack);
			return ($lastG->getId() == $parentGroup->getId());
		}
		return false;
	}
	/**
	 * Looks if a given group is one of the parent of the current item in the tree. 
	 * @param int|Group $parentGroup the parent group id or parent group to lookup
	 * @return boolean true if given group is a parent of current item in the list, else false
	 */
	protected function parentLookup($parentGroup){
		if($parentGroup == null){
			return true;
		}
		if(isset($this->parentStack)){
			if(is_object($parentGroup)) return array_key_exists($parentGroup->getId(), $this->parentStack);
			else array_key_exists($parentGroup, $this->parentStack);
		}
		return false;
	}
	private $first;
	public function addGroupP($groupP, $parentGroup){
		$this->nb ++;
		if($this->groups[$groupP->getGroup()->getId()] != null) throw new ListException("the group ".$groupP->getGroup()->getId().":".$groupP->getGroup()->getGroupName()." already exists in GroupPTree.", ListException::ALREADY_EXISTS);
		$this->groups[$groupP->getGroup()->getId()]=$groupP;
		$group = $groupP->getGroup();
		
		if($this->isCurrentParent($parentGroup)){
			//same parent
		} else if($this->parentLookup($parentGroup)){
			//elder parent.
			while(!$this->isCurrentParent($parentGroup)){
				$this->depth--;
				$this->popParent();
				?></li></ul><?
			}
		} else if($parentGroup) {
			//new parent
			$this->depth ++;
			// verifies if current group is contained in trashbin, if yes, does not unfold.
			$isInTrashBin = isset($this->trashBinGroup) && ($parentGroup->getId() == $this->trashBinGroup || $this->parentLookup($this->trashBinGroup));			
			?><ul<?=($this->getNbLevelToExpandOnInit()+1<=$this->depth || $isInTrashBin ? ' class="n" style="display:none;" ' : '');?>><?
			$this->pushParent($parentGroup);
		} else if(!$this->first){
			?></li><?
		} else {
			$this->first=false;
		}
		$id = 'group_'.$groupP->getGroup()->getId();
		$class .= "";
		$class .= ($groupP->getRights() == null ? "noRights ":"");
		$class .= (($groupP->getRights() != null && !$groupP->getRights()->canShareElement())?"readOnly ":"");
		$class .= (($groupP->getRights() != null && $groupP->getRights()->canShareElement() && !$groupP->getRights()->canWriteElement())?"share ":"");
		$class .= (($groupP->getRights() != null && $groupP->getRights()->canWriteElement())?"write ":"");
		$class .= (($groupP->getRights() != null && $groupP->getRights()->canModify())?"admin ":"");
		$class .= ($this->depth == 0 ?"level1 ":"");
		?><li id="<?=$id;?>" class="<?=$class;?>"><?
		 $this->writeHtmlForGroupItem($groupP);
		
		return $this;
	}
	
	protected function getHtmlCountData($groupId){
		if(!$this->isCountDataForGroup()) return null;
		$nb = $this->getCountDataForGroup($groupId);
		if($nb == null) return '&nbsp;<span class="R nb empty"></span>';
		return '&nbsp;<span class="R nb">('.$nb.')</span>';
	}
	protected function writeHtmlForGroupItem($groupP){
		$group = $groupP->getGroup();
		$description = ($groupP->getGroup()->getDetail()!=null && $groupP->getGroup()->getDetail()->getDescription()!=null ? "".$groupP->getGroup()->getDetail()->getDescription()."" : "")."<p class='descrId'>Id:".$groupP->getGroup()->getId()."</p>";
		$cacheLookup = $this->getExec()->getCurrentCacheLookup($this->getP(), "groupSelectorPanel", "groupSelectorPanel/selectGroup/".$groupP->getGroup()->getId());
		echo '<div style="'.$this->getStyleBasedOnDepth().'">';
		echo '<span class="folder">&nbsp;</span>';
		echo '<a href="#'.$cacheLookup.'" class="H">'.($groupP->getDbEntity()->getGroupParentId()==0 && ($this->getP()->isWigiiNamespaceCreator() || ($this->getP()->getWigiiNamespace()->getWigiiNamespaceName() != $group->getWigiiNamespace()->getWigiiNamespaceName() && $group->getWigiiNamespace()->getWigiiNamespaceName())) ? '<span class="grayFont">'.$group->getWigiiNamespace()->getWigiiNamespaceName()." : </span>" : "" ).$group->getGroupName().'</a>';
		echo $this->getHtmlCountData($group->getId());
		echo '<span class="description">'.$description.'</span>';
		if($this->getDisplayContextMenu()) $this->displayContextMenu($groupP);
		echo '</div>';
	}
	
	protected function displayContextMenu($groupP){
		echo '<span class="menu">&nbsp;</span>';
	}
	protected function getStyleBasedOnDepth(){
		return ($this->depth ? 'padding-left:'.($this->depth*20).'px;' : '');
	}
	
	public function start($p, $exec){
		$cacheLookup = $this->getExec()->getCurrentCacheLookup($this->getP(), "groupSelectorPanel", "groupSelectorPanel/selectGroup/0");
		?><ul id="group_0" href="#<?=$cacheLookup;?>"><?
		$this->first = true;
	}
	public function end($p, $exec){
		while(!$this->isCurrentParent(null)){
			$this->depth--;
			$this->popParent();
			?></li></ul><?
		}
		?></li></ul><?
		$this->addEndingJsCode($p, $exec);
	}
	
	protected function addEndingJsCode($p, $exec){
		$exec->addJsCode("setListenersToGroupPanel('".ServiceProvider::getTranslationService()->h($p, "doYouWantToRemoveYouMultipleSelectionText")."'); resize_groupPanel(); resize_elementList();");
	}
	
}


