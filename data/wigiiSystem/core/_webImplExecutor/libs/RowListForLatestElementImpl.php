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
 * Created 30 March 2012
 * by LWR
 */

class RowListForLatestElementImpl extends ElementPListWebImplWithWigiiExecutor {

	private $isGroupedBy;
	private $crtGroupByValue;
	private $trm_;
	protected function getTRM(){
		if(!isset($this->trm_)){
			$this->trm_ = $this->createTRMInstance();
		}
		return $this->trm_;
	}
	protected function createTRMInstance(){
		return $this->getWigiiExecutor()->createTRM(null, false, false, false, true, true, true);
	}
	public function setTRM($var){ $this->trm_ = $var; }

	private $p;
	public function setP($p){$this->p = $p;}
	protected function getP(){ return $this->p; }

	private $fslDisplay;
	public function setFieldSelectorToDisplay($fsl){
		$this->fslDisplay = $fsl;
	}
	protected function getFieldSelectorToDisplay(){
		return $this->fslDisplay;
	}

	private $color;
	public function setColor($color){
		$this->color = $color;
	}
	protected function getColor(){
		return $this->color;
	}
	private $width;
	public function setWidth($width){
		$this->width = $width;
	}
	protected function getWidth(){
		return $this->width;
	}

	private $crtModule;
	public function setCrtModule($crtModule){
		$this->crtModule = $crtModule;
	}
	protected function getCrtModule(){
		return $this->crtModule;
	}
	private $crtWigiiNamespace;
	public function setCrtWigiiNamespace($crtWigiiNamespace){
		$this->crtWigiiNamespace = $crtWigiiNamespace;
	}
	protected function getCrtWigiiNamespace(){
		return $this->crtWigiiNamespace;
	}
	private $myExec;
	public function setExec($exec){
		$this->myExec = $exec;
	}
	protected function getExec(){
		return $this->myExec;
	}

	public static function createInstance($wigiiExecutor){
		$elPl = new self();
		$elPl->setWigiiExecutor($wigiiExecutor);
		return $elPl;
	}

	/**
	 * Adds an element to the list
	 * throws ListException::ALREADY_EXISTS if we try to put a second time the same element in the list
	 */
	private $elementIds; //array which stores the elements ids already added
	private $nb;
	public function getTotalElementsIds(){
		return $this->elementIds;
	}
	public function addElementP($elementP){
		$this->nb++;

		$element = $elementP->getElement();
		$elId = $element->getId();
		if($this->elementIds[$elId]!=null) throw new ListException("Id: $elId already exist in the ElementListFrame", ListException::ALREADY_EXIST);
		$this->elementIds[$elId] = $elId;

		$this->getTrm()->reset($element);

		$elementFieldSelectorList = $this->getFieldSelectorToDisplay();
		if($elementFieldSelectorList == null){
			//we are in the list view, we need a field selector list
			throw new ServiceException("A FieldSelectorList is needed", ServiceException::INVALID_ARGUMENT);
		}

		$this->beginElement($elementP);

		$width = $this->getWidth();
		foreach($elementFieldSelectorList->getListIterator() as $fs) {
			$class = null;
			$value = $this->getTrm()->formatValueFromFS($fs, $element);
			$this->addCell($value, $class, $width[$fs->getFieldName().($fs->getSubFieldName() ? ".".$fs->getSubFieldName() : "")]);
		}

		$this->endElement();

	}

	protected function beginElement($elementP){
		?><tr class="H" id="row_<?=$elementP->getId();?>" onclick="<?=$this->getExec()->getUpdateJsCode($this->getP()->getRealUser(), $this->getP()->getUserId(), $this->getCrtWigiiNamespace(), $this->getCrtModule(), "NoAnswer", "NoAnswer", "navigate/item/".$elementP->getId()."/'+crtRoleId+'/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'", true, true);?>"><?
	}

	protected function endElement(){
		?></tr><?
	}

	protected function addCell($value, $class=null, $width=null){
		if(is_array($value)){
			$temp = "";
			foreach($value as $i){
				if($i != null){
					if($temp != null) $temp .= ", ";
					$temp .= $i;
				}
			}
			$value = $temp;
		}
		?><td><div<?=($class ? ' class="'.$class.'" ' : '');?><?=($width ? ' style="width:'.$width.'px;" ' : '');?>><?=$value;?></div></td><?
	}

	public function actOnBeforeAddElementP(){
		$this->nb = 0;

		//we always enable the multiple boxes, it is only if one is check that it will enable the dialogBox
		?><div class="dataList"><table style="color:<?=$this->getColor();?>"><tbody><?

	}

	public function actOnFinishAddElementP($numberOfObjects){
		?></tbody></table></div><?
	}

}

