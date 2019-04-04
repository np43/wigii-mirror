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
 * Created on 15 oct. 10
 * by LWR
 */

class SelectOptionForGroupTreeRenderer implements TreeInDepthVisitor {
	
	private $groupToDisableWithChildren;
	public function setGroupToDisableWithChildren($groupId){
		$this->groupToDisableWithChildren = $groupId;
	}
	protected function getGroupToDisableWithChildren(){ return $this->groupToDisableWithChildren; }
	
	private $disabledOnRightsLesThan;
	public function setDisabledOnRightsLesThan($char){
		$this->disabledOnRightsLesThan = $char;
	}
	protected function getDisabledOnRightsLesThan(){ return $this->disabledOnRightsLesThan; }
	
	private $currentWigiiNamespace;
	public function setCurrentWigiiNamespaceName($wigiiNamespace){
		$this->currentWigiiNamespace = $wigiiNamespace;
	}
	protected function getCurrentWigiiNamespaceName(){ return $this->currentWigiiNamespace; }
	
	private $showFullPathInLabel;
	private $path;
	public function setShowFullPathInLabel($boolean) {
		$this->showFullPathInLabel = $boolean;
	}
	public function getShowFullPathInLabel() {return $this->showFullPathInLabel;}
	
	private $result;
	public function getResult(){ return $this->result; }
	
	public static function createInstance($currentWigiiNamespaceName=null, $disabledOnRightsLesThan="w", $groupToDisableWithChildren = null){
		$r = new self();
		$r->reset();
		$r->setDisabledOnRightsLesThan($disabledOnRightsLesThan);
		$r->setGroupToDisableWithChildren($groupToDisableWithChildren);
		$r->setCurrentWigiiNamespaceName($currentWigiiNamespaceName);		
		return $r;
	}
	
	public function reset(){
		$this->freeMemory();
		$this->result = "";
	}
	
	public function freeMemory(){
		unset($this->result);
		unset($this->path);
	}
	
	private $disableChildren = null;
//	private $hadNoChildren = array();
//	private $hadOneChild = array();
	public function actOnTreeNode($object, $depth, $numberOfChildren){
		$disable = false;
		$this->hadNoChildren[$depth] = $numberOfChildren == 0;
		$this->hadOneChild[$depth] = $numberOfChildren === 1;
		switch($this->getDisabledOnRightsLesThan()){
			case "r": $disable = ($object->getRights() == null); break;
			case "s": $disable = ($object->getRights() == null || !$object->getRights()->canShareElement()); break;
			case "w": $disable = ($object->getRights() == null || !$object->getRights()->canWriteElement()); break;
			case "x": $disable = ($object->getRights() == null || !$object->getRights()->canModify()); break;
		}
		if($object->getId()===$this->getGroupToDisableWithChildren()){
			$this->disableChildren = $object->getId();
		}
		if($disable || $this->disableChildren){
			$disable = 'disabled="1"';
		}
		$this->result .= '<attribute '.$disable.'>'.$object->getId().'<label>';
//		for($i = 0; $i<$depth; $i++){
//			if($i == $depth-1) $this->result .= "├  ";
//			else {
//				if($this->hadOneChild[$i-1]){
//					$this->result .= "   ";
//				} else {
//					$this->result .= "│  ";
//				}
//			}
//		}

		$groupName = str_replace(array("&", '\\', "<", ">"), array("/", "/", "-", "-"), ($this->getCurrentWigiiNamespaceName()!=$object->getGroup()->getWigiiNamespace()->getWigiiNamespaceName() && $object->getGroup()->getWigiiNamespace()->getWigiiNamespaceName() ? $object->getGroup()->getWigiiNamespace()->getWigiiNamespaceName()." : " : "").$object->getGroup()->getGroupname());
		if($this->showFullPathInLabel) {
			if(!isset($this->path)) $this->path = array();
			if($depth > 0) $pathIndicator = implode(" / ", $this->path).' / ';
			$this->path[] = $groupName;
			$groupName = $groupName;
		}
		else {
			$pathIndicator = str_repeat("|   ", $depth);
		}
		$this->result .= $pathIndicator;
		$this->result .= $groupName.'</label></attribute>';
		return true;
	}
	
	public function actAfterTreeNode($object, $depth, $visitedAllChildren){
//		if(!$this->hadNoChildren[$depth] && $visitedAllChildren){
//			$pos = strrpos($this->result, '├');
//			if($pos !== false){
//				$this->result = substr_replace($this->result, '└', $pos, 3); //└├│
//			}
//		}
		if($this->disableChildren === $object->getId()){
			$this->disableChildren = null;
		}
		if($this->showFullPathInLabel) array_pop($this->path);
		return true;
	}
}



