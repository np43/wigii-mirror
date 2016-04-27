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
 * GroupPList array based implementation which organizes the groups as a tree
 * Created by LWR on 4 oct 10
 */
class GroupPListTreeArrayImpl extends ObjectListArrayImpl implements GroupPList, TreeInDepthVisitor
{
	protected $allGroups;
	protected $childrenGroups;
	protected $rootGroups;
	protected $rootGroupPList;
	protected $noRightsGroupPList;
	protected $readGroupPList;
	protected $shareGroupPList;
	protected $writeGroupPList;
	protected $canModifyGroupPList;
	protected $hierarchicalMarkups;
	public function getHierarchicalMarkups($groupId=null){
		if($groupId ===null){
			return $this->hierarchicalMarkups;
		}
		return $this->hierarchicalMarkups[$groupId];
	}

	// Object lifecycle

	public static function createInstance()
	{
		$returnValue = new self();
		$returnValue->reset();
		return $returnValue;
	}

	public function reset()
	{
		parent::reset();
		$this->allGroups = array();
		$this->childrenGroups = array();
		$this->rootGroups = array();
		$this->rootGroupPList = GroupPListArrayImpl::createInstance();
		$this->noRightsGroupPList = GroupPListArrayImpl::createInstance();
		$this->readGroupPList = GroupPListArrayImpl::createInstance();
		$this->shareGroupPList = GroupPListArrayImpl::createInstance();
		$this->writeGroupPList = GroupPListArrayImpl::createInstance();
		$this->canModifyGroupPList = GroupPListArrayImpl::createInstance();
		$this->hierarchicalMarkups = null;
	}
	public function freeMemory()
	{
		parent::freeMemory();
		unset($this->allGroups);
		unset($this->childrenGroups);
		unset($this->rootGroups);
		unset($this->hierarchicalMarkups);
		unset($this->rootGroupPList);
		unset($this->noRightsGroupPList);
		unset($this->readGroupPList);
		unset($this->shareGroupPList);
		unset($this->writeGroupPList);
		unset($this->canModifyGroupPList);
	}

	public function getRootGroupPList(){ return $this->rootGroupPList; }
	public function getNoRightsGroupPList(){ return $this->noRightsGroupPList; }
	public function getReadGroupPList(){ return $this->readGroupPList; }
	public function getShareGroupPList(){ return $this->shareGroupPList; }
	public function getWriteGroupPList(){ return $this->writeGroupPList; }
	public function getCanModifyGroupPList(){ return $this->canModifyGroupPList; }

	// Tree implementation

	/**
	 * Visits the Group Tree in depth, starting from roots (level 0)
	 */
	public function visitInDepth($treeInDepthVisitor)
	{
		if(is_null($treeInDepthVisitor)) throw new ListException("treeInDepthVisitor cannot be null", ListException::INVALID_ARGUMENT);
		if(isset($this->rootGroups))
		{
			foreach($this->rootGroups as $rg)
			{
				if(!$this->recursiveVisitInDepth($rg, $treeInDepthVisitor, 0)) break;
			}
		}
	}
	protected function recursiveVisitInDepth($currentGroup, $treeInDepthVisitor, $depth)
	{
		if(isset($currentGroup))
		{
			$gId = $currentGroup->getId();
			if(isset($this->childrenGroups[$gId])) $nChildren = count($this->childrenGroups[$gId]);
			else $nChildren = 0;
			// visits current node
			$visitedAllChildren = true;
			if($treeInDepthVisitor->actOnTreeNode($currentGroup, $depth, $nChildren))
			{
				// ok to visit children
				if($nChildren > 0)
				{
					foreach($this->childrenGroups[$gId] as $cg)
					{
						if(!$this->recursiveVisitInDepth($cg, $treeInDepthVisitor, $depth+1))
						{
							$visitedAllChildren = false;
							break;
						}
					}
				}
			}
			else $visitedAllChildren = false;
			// acts on current node after children visit
			return $treeInDepthVisitor->actAfterTreeNode($currentGroup, $depth, $visitedAllChildren);
		}
		return true;
	}

	public function getIds(){
		if(!isset($this->objArray)) return array();
		return array_keys($this->objArray);
	}
	public function getParentGroup($group){
		if($group == null) return null;
		$key = $group->getDbEntity()->getGroupParentId();
		if($key == null) return null;
		if($this->rootGroups[$key]) return $this->rootGroups[$key];
		if($this->allGroups[$key]) return $this->allGroups[$key];
		return null;
	}
	public function getGroupP($groupId){
		if(!isset($this->objArray)) return array();
		return $this->objArray[$groupId];
	}
	// GroupList implementation
	public function addGroupP($groupP){
		if(!isset($groupP)) throw new ListException("groupP cannot be null", ListException::INVALID_ARGUMENT);
		$key = $groupP->getId();
		if(isset($this->objArray[$key])) throw new ListException("a group with same key $key has already been added", ListException::ALREADY_EXISTS);
		$this->objArray[$key] = $groupP;
		$parentGroupId = $groupP->getGroup()->getGroupParentId();
		// adds root group
		$this->allGroups[$key] = $groupP;

		if(is_null($parentGroupId))
		{
			$this->rootGroups[$key] = $groupP;
			$this->rootGroupPList->addGroupP($groupP);
		}
		// adds child group
		else
		{
			$this->childrenGroups[$parentGroupId][$key] = $groupP;
		}
		if($groupP->getRights() == null){
			$this->noRightsGroupPList->addGroupP($groupP);
		}
		if($groupP->getRights() != null && !$groupP->getRights()->canShareElement()){
			$this->readGroupPList->addGroupP($groupP);
		}
		if($groupP->getRights() != null && $groupP->getRights()->canShareElement()){
			$this->shareGroupPList->addGroupP($groupP);
		}
		if($groupP->getRights() != null && $groupP->getRights()->canWriteElement()){
			$this->writeGroupPList->addGroupP($groupP);
		}
		if($groupP->getRights() != null && $groupP->getRights()->canModify()){
			$this->canModifyGroupPList->addGroupP($groupP);
		}
		return $this;
	}

	//this will fill the hierarchicalMarkups for each groups. The idea is then to know for one group
	//if this group contains some groups with what kind of rights
	public function doHierarchicalRightsMarkup(){
		$this->parentStack = array();
		$this->hierarchicalMarkups = array();
		$this->visitInDepth($this);
	}

	private $parentStack;
	public function actOnTreeNode($groupP, $depth, $numberOfChildren){
		$key = $groupP->getId();
		$parentGroupId = $groupP->getGroup()->getGroupParentId();
		if(count($this->parentStack)<$depth){
			array_push($this->parentStack, $parentGroupId);
		} else if(count($this->parentStack)>$depth){
			while(count($this->parentStack)>$depth){
				array_pop($this->parentStack);
			}
		} else {
			//if the stack is = to depth --> no parent change
		}
		if($this->parentStack){
			$markup = array();
			if($groupP->getRights() != null && $groupP->getRights()->canModify()){
				$markup["x"] = "x";
			}else if($groupP->getRights() != null && $groupP->getRights()->canWriteElement()){
				$markup["w"] = "w";
			}else if($groupP->getRights() != null && $groupP->getRights()->canShareElement()){
				$markup["s"] = "s";
			}else if($groupP->getRights() != null && !$groupP->getRights()->canShareElement()){
				$markup["r"] = "r";
			}else if($groupP->getRights() == null){
				$markup["-"] = "-";
			}
			foreach($this->parentStack as $i_parentId){
				if($this->hierarchicalMarkups[$i_parentId]){
					$this->hierarchicalMarkups[$i_parentId] = array_merge($this->hierarchicalMarkups[$i_parentId], $markup);
				} else {
					$this->hierarchicalMarkups[$i_parentId] = $markup;
				}
			}
		}
		return true;
	}

	public function actAfterTreeNode($object, $depth, $visitedAllChildren){
		return true;
	}

	/**
	 * filter the Tree only to keep the groups corresponding to the markups and the good rights and their parents.
	 */
	public function cleanOnMarkup($minMarkup){
		if(!isset($this->hierarchicalMarkups)) $this->doHierarchicalRightsMarkup();
		$markups = $this->getHierarchicalMarkups();
		if($minMarkup == null || $minMarkup ==="-") return true; //nothing to do
		foreach($this->getListIterator() as $key=>$groupP){
			$id = $groupP->getId();
			$markup = $this->getHierarchicalMarkups($id);
			$rights = $groupP->getRights();
			$keep = true;
			if($markup == null || !is_array($markup)){
//				eput("markup =>");
				switch($minMarkup){
					case "-": $keep = true; break;
					case "r": $keep = $rights!=null; break;
					case "s": $keep = $rights!=null && $rights->canShareElement(); break;
					case "w": $keep = $rights!=null && $rights->canWriteElement(); break;
					case "x": $keep = $rights!=null && $rights->canModify(); break;
					default: $keep = false;
				}
//				eput($keep);
//				eput("\n");
			} else {
				switch($minMarkup){
					case "-": $keep = ($markup["-"]!=null || $markup["r"]!=null || $markup["s"]!=null || $markup["w"]!=null || $markup["x"]!=null); break;
					case "r": $keep = ($markup["r"]!=null || $markup["s"]!=null || $markup["w"]!=null || $markup["x"]!=null); break;
					case "s": $keep = ($markup["s"]!=null || $markup["w"]!=null || $markup["x"]!=null); break;
					case "w": $keep = ($markup["w"]!=null || $markup["x"]!=null); break;
					case "x": $keep = ($markup["x"]!=null); break;
					default:  $keep = false;
				}
			}
			if(!$keep){
//				eput("del ".$groupP->getGroup()->getGroupname()." ".$groupP->getId().($markup ? implode(", ", $markup): "")."\n");
				unset($this->objArray[$key]);
				unset($this->rootGroups[$id]);
				//delete from parent children
				unset($this->childrenGroups[$groupP->getGroup()->getGroupParentId()][$id]);
				//delete the group itself
				unset($this->childrenGroups[$id]);
			} else {
//				eput("keep ".$groupP->getGroup()->getGroupname()." ".$groupP->getId().($markup ? implode(", ", $markup): "")."\n");
			}
		}
	}
}


