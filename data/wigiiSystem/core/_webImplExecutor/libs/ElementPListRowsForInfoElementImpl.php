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
 * Created 3 April 2012
 * by LWR
 */

class ElementPListRowsForInfoElementImpl extends ElementPListWebImplWithWigiiExecutor {

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

	private $readMore;
	public function setIsReadMore($readMore){
		$this->readMore = $readMore;
	}
	protected function getReadMore(){
		return $this->readMore;
	}
	private $heightPerInfo;
	public function setMaxHeight($maxHeight){
		$this->maxHeight = $maxHeight;
	}
	protected function getMaxHeight(){
		return $this->maxHeight;
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
	private $display;
	public function setDisplay($display){
		$this->display = $display;
	}
	protected function getDisplay(){
		return $this->display;
	}

	public static function createInstance($wigiiExecutor, $listContext){
		$elPl = new self();
		$elPl->setWigiiExecutor($wigiiExecutor);
		$elPl->setListContext($listContext);
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

		$this->beginElement($elementP->getId());

		foreach($elementFieldSelectorList->getListIterator() as $fs) {
			$class = null;
			$value = $this->getTrm()->formatValueFromFS($fs, $element);
			$this->addCell($value, $class);
		}

		$this->endElement();

	}

	/**
	 * groupBy filter management
	 */
	protected function beginElement($elementId){
		//$cacheLookup = $this->getExec()->getCacheLookup($this->getP()->getRealUserId(), $this->getP()->getUserId(), $this->getCrtWigiiNamespace(), $this->getCrtModule(), "selectElementDetail", "element/detail/".$elementP->getId());
		//$lookup = $this->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$this->getCrtModule()->getModuleUrl()."/item/".$elementP->getId();
		?><div class="info<?=($this->getReadMore() ? " readMore": "");?>" <?=($this->getMaxHeight() ? 'style="max-height:'.$this->getMaxHeight().'px;'.($this->getReadMore() ? 'overflow:hidden;' : 'overflow-y:auto;').'"' : '');?> id="row_<?=$elementId;?>" <?=($this->getReadMore() ? 'onclick="'.$this->getExec()->getUpdateJsCode($this->getP()->getRealUser(), $this->getP()->getUserId(), $this->getCrtWigiiNamespace(), $this->getCrtModule(), "NoAnswer", "NoAnswer", "navigate/item/".$elementId."/'+crtRoleId+'/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'", true, true).'"' : '');?>><?
	}

	protected function endElement(){
		?></div><?
	}

	protected function addCell($value, $class=null, $width=null){
		echo $value;
	}

	public function actOnBeforeAddElementP(){
		$this->nb = 0;
		$p = $this->getP();
	}

	public function actOnFinishAddElementP($numberOfObjects){

	}

	public function addRow($row){
		$this->beginElement($row["id_element"]);
		$disp = $this->getDisplay();
		$fsl = $this->getFieldSelectorToDisplay();
		//eput($row);
		foreach($row as $key=>$value){
			if($key == "id_element") continue;
			list($fieldName, $subFieldName) = explode(".", $key);
			$d = $disp[$fieldName.($subFieldName ? ".".$subFieldName : '')];
			if($d){
				if($subFieldName == "path"){
					readfile(FILES_PATH.$value);
				} else {
					$this->addCell($value);
				}
			}
		}
		$this->endElement();
	}

}

