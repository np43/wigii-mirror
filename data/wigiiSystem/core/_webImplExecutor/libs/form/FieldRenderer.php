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
 * Base class for Detail and Form renderers
 * Created on 3 july 2013 by LWR
 */
class FieldRenderer extends Model {

	private $fieldGroupHasError = array();
	private $fieldGroupIsFilled = array();
	private $fieldGroupStack = array();
	private $fieldGroupParentOffset = 8; //number of pixel the inner group is less wide than the group.
	private $crtFieldGroupName;
	private $crtFieldGroupDepth = 0;
	private $fieldTotalWidth; //the total width can be more than label + value as there is some possible margins or paddings
	private $fieldLabelWidth;
	private $fieldValueWidth;
	private $fieldIsInLineWidth;
	private $fieldOffset = 0; //number of pixel the value is smaller than the total - label

	public function reset(){
		$this->fieldGroupHasError = array();
		$this->fieldGroupIsFilled = array();
		$this->fieldGroupStack = array();
		$this->fieldGroupParentOffset = 8;
		$this->crtFieldGroupName = null;
		$this->crtFieldGroupDepth = 0;
		$this->fieldTotalWidth = null;
		$this->fieldLabelWidth = null;
		$this->fieldValueWidth = null;
		$this->fieldIsInLineWidth = null;
		$this->fieldOffset = 0;
	}
	/**
	 * Field group stack management.
	 * Field group is a group of field. This is defined in the configuration with groupStart="1" and groupEnd="1" on a field without any type.
	 * Field groups can be imbricated.
	 * CrtFieldGroup is the name of the current group in which the renderer opperates (=root if not in a fieldGroup)
	 * CrtfieldGroupDepth is the depth of groups, 0 is outside of any group (root)
	 *
	 */

	/**
	 * Field renderer must call this method on createInstance
	 */
	protected function initializeFieldWidth($totalWidth, $labelWidth){
		if($this->crtFieldGroupName==null){
			$this->crtFieldGroupName = "root";
		}
//		$this->setFieldWidth($this->crtFieldGroupName, $totalWidth, $labelWidth);
		$this->fieldTotalWidth[$this->crtFieldGroupName] = $totalWidth;
		$this->fieldLabelWidth[$this->crtFieldGroupName] = $labelWidth;
		//in root do not reduce by $this->getFieldOffset
		$this->fieldValueWidth[$this->crtFieldGroupName] = $totalWidth-$labelWidth;
		$this->fieldIsInLineWidth[$this->crtFieldGroupName] = $totalWidth;
	}
	protected function enterFieldGroup($rm, $fieldXml, $fieldName, $idField){
		$fieldClass = (string)$fieldXml["class"];
		//open fieldGroup div
		if((string)$fieldXml["totalWidth"]!=null){
			$style = "width:".((string)$fieldXml["totalWidth"])."px;";
		} else {
			$style = "width:".($this->getTotalWidth())."px;";
		}
		$rm->put('<div id="'.$idField.'" class="field '.$fieldClass.'" style="'.$style.'" >');

		//display label if necessary, 20 is label padding
		if($fieldXml["noLabel"]!="1"){
			if((string)$fieldXml["totalWidth"]!=null){
				$labelWidth = ((string)$fieldXml["totalWidth"]-$this->getFieldGroupParentOffset()-20);
			} else {
				$labelWidth = ($this->getIsInLineWidth()-20);
			}
			$style = "width:".$labelWidth."px;";
			$rm->put('<div class="label" style="'.$style.'" >');
			if($fieldXml["displayAsTitle"]=="1") $rm->put('<h4>');
			$rm->displayLabel($fieldName, $labelWidth, $this->getVisibleLanguage());
			if($fieldXml["displayAsTitle"]=="1") $rm->put('</h4>');
			$rm->put('</div>');
		}

		//create the group container, 10 is group padding
		$rm->put('<div id="'.$idField.'_group" ');
		$rm->put('class="value fieldGroup SBIB ui-corner-all'.($fieldXml["noFieldset"] =="1" ? ' noFieldset ' : '').'" ');
		//add in CSS background-color:#fff; position:relative;margin-left:20px;margin-top:0px;margin-bottom:5px;padding:4px;
		//or border-bottom-width:2px;border-bottom-style:solid;border-top-width:2px;border-top-style:solid;
		//+10 when noFieldset is to take advantage of the unused margin-right
		if((string)$fieldXml["totalWidth"]!=null){
			$rm->put('style="width:'.(($fieldXml["noFieldset"]=="1" ? (string)$fieldXml["totalWidth"]+10 : ((string)$fieldXml["totalWidth"]-$this->getFieldGroupParentOffset()-10-4))).'px;"'); //-10 is for left padding, 4 is for the two borders of 4 px;
		} else {
			$rm->put('style="width:'.(($fieldXml["noFieldset"]=="1" ? $this->getIsInLineWidth()+10 : ($this->getIsInLineWidth()-10-4))).'px;"'); //-10 is for left padding, 4 is for the two borders of 4 px;
		}
		$rm->put('>');

		$this->updateWidthOnEnterField($fieldName, $fieldXml);
	}

	protected function leaveCrtFieldGroup($rm, $fieldXml){
		$this->updateWidthOnLeaveField($fieldXml);
		$rm->put('</div></div>'); //close div.value, div.field
	}


	protected function isCrtFieldGroupFilled(){
		return $this->fieldGroupIsFilled[$this->getCrtFieldGroup()];
	}
	/**
	 * set the current FieldGroup as filled.
	 */
	protected function setCrtFieldGroupIsFilled(){
		//mark as filled the whole fieldGroup stack
		foreach($this->fieldGroupStack as $fieldGroupName){
			if($fieldGroupName=="root") continue;
			$this->fieldGroupIsFilled[$fieldGroupName] = true;
		}
	}
	protected function isCrtFieldGroupHasError(){
		return $this->fieldGroupHasError[$this->getCrtFieldGroup()];
	}
	/**
	 * set the current FieldGroup as hasError.
	 */
	protected function setCrtFieldGroupHasError(){
		//mark as filled the whole fieldGroup stack
		foreach($this->fieldGroupStack as $fieldGroupName){
			if($fieldGroupName=="root") continue;
			$this->fieldGroupHasError[$fieldGroupName] = true;
		}
	}

	/**
	 * Enter in a new fieldGroup (typically called when field with groupStart="1")
	 * @param $fieldGroupName : name of the fieldGroup (this name is not always equal to $fieldGroupXml->getName() in the case of there is multiple times the same name in the configuration for a fieldGroup
	 * @param $fieldGroupXml : xml, contains the xml with all the parameters of this new fieldGroup.
	 */
	protected function updateWidthOnEnterField($fieldGroupName, $fieldGroupXml){
		$parentTotalWidth = $this->getTotalWidth();
		$parentLabelWidth = $this->getLabelWidth();
		$totalWidth = (string)$fieldGroupXml["totalWidth"];
		if($totalWidth==null) $totalWidth = $parentTotalWidth;
		$labelWidth = (string)$fieldGroupXml["labelWidth"];
		//if($labelWidth==null) $labelWidth = $parentLabelWidth;

		$this->pushFieldGroup($fieldGroupName);
		$useMultipleColumn = (int)(string)$fieldGroupXml["useMultipleColumn"];

		//if group with fieldset change the width
		if(($fieldGroupXml["groupStart"]=="1" || $fieldGroupXml["groupEnd"]=="1") && $fieldGroupXml["noFieldset"]!="1"){
			$totalWidth = $totalWidth - $this->getFieldGroupParentOffset();
			$totalWidth = $totalWidth - 20; //fieldGroup padding, this is not the real padding but it is to make a good blank proportion on the right

			//note: a fieldGroup always take the full width of the parent. --> no need to check here the isInLine property.

		}

		//define the useMultiplecolumn
		if(($fieldGroupXml["groupStart"]=="1" || $fieldGroupXml["groupEnd"]=="1") && $useMultipleColumn){
			//take in consideration the margin-right of 10 for each column except the last one
			$totalWidth = floor(($totalWidth-(($useMultipleColumn-1)*10)) / $useMultipleColumn);
		}

		if($labelWidth == null) $labelWidth = floor($totalWidth * ($parentLabelWidth / $parentTotalWidth));

		//if label is too big making the value size less than 10 then adapt the label to leave the value minimum with a size of 10
		if(10>=($totalWidth-$labelWidth-$this->getFieldOffset())){
			$labelWidth = $totalWidth-10-$this->getFieldOffset();
		}
		$this->setFieldWidth($fieldGroupName, $totalWidth, $labelWidth);

	}

	/**
	 * Leave crt fieldGroup (typically called when field with groupEnd="1")
	 */
	protected function updateWidthOnLeaveField(){
		$this->popFieldGroup();
		return $this->getCrtFieldGroup();
	}


	// implementation

	protected function getCrtFieldGroup(){
		return $this->crtFieldGroupName;
	}
	protected function getCrtFieldGroupDepth(){
		return $this->fieldGroupDepth;
	}
	protected function getFieldGroupParentOffset(){
		return $this->fieldGroupParentOffset;
	}
	protected function setFieldGroupParentOffset($offset){
		$this->fieldGroupParentOffset = $offset;
	}
	protected function pushFieldGroup($fieldGroupName){
		if($fieldGroupName=="root") throw new FieldRendererException("cannot define a field group with 'root'. 'root' is reserved.", FieldRendererException::INVALID_ARGUMENT);
		$this->fieldGroupDepth++;
		$this->crtFieldGroupName = $fieldGroupName;
		array_push($this->fieldGroupStack, $fieldGroupName);
	}
	protected function popFieldGroup(){
		$this->fieldGroupDepth--;
		array_pop($this->fieldGroupStack);
		if($this->fieldGroupStack) $this->crtFieldGroupName = end($this->fieldGroupStack);
		else $this->crtFieldGroupName = "root";
	}

	public function getTotalWidth(){
		if(!$this->fieldTotalWidth) return null;
		return $this->fieldTotalWidth[$this->getCrtFieldGroup()];
	}
	public function getLabelWidth(){
		if(!$this->fieldLabelWidth) return null;
		return $this->fieldLabelWidth[$this->getCrtFieldGroup()];
	}
	public function getValueWidth(){
		if(!$this->fieldValueWidth) return null;
		return $this->fieldValueWidth[$this->getCrtFieldGroup()];
	}
	public function getIsInLineWidth(){
		if(!$this->fieldIsInLineWidth) return null;
		return $this->fieldIsInLineWidth[$this->getCrtFieldGroup()];
	}
	protected function getFieldOffset(){
		return $this->fieldOffset;
	}
	protected function setFieldOffset($offset){
		$this->fieldOffset = $offset;
	}
	protected function setFieldWidth($fieldGroupName, $totalWidth, $labelWidth){
		$this->fieldTotalWidth[$fieldGroupName] = $totalWidth;
		$this->fieldLabelWidth[$fieldGroupName] = $labelWidth;
		$this->fieldValueWidth[$fieldGroupName] = $totalWidth-$labelWidth-$this->getFieldOffset();
		$this->fieldIsInLineWidth[$fieldGroupName] = $totalWidth-$this->getFieldOffset();
	}
}


