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

	private $fieldHasError = array();
	private $fieldIsFilled = array();
	private $fieldStack = array();
	private $fieldGroupParentOffset = 12; //number of pixel the inner group is less wide than the group. padding 10 + 2 border
	private $crtFieldName;
	private $crtFieldDepth = 0;
	private $fieldTotalWidth; //the total width can be more than label + value as there is some possible margins or paddings
	private $fieldMultipleColumn;
	private $fieldLabelWidth;
	private $fieldValueWidth;
	private $fieldIsInLineWidth;
	private $fieldOffset = 10; //padding right of 10px for the fields
	private $rootOffset = 0; //padding of the dialog div;
	private $fieldCorrectionDueToExternalFactors = 0; //this can be updated based on additional classes, this correction is reset after each field

	public function reset(){
		$this->fieldHasError = array();
		$this->fieldIsFilled = array();
		$this->fieldStack = array();
		$this->fieldGroupParentOffset = 12;
		$this->crtFieldName = null;
		$this->crtFieldDepth = 0;
		$this->fieldTotalWidth = null;
		$this->fieldLabelWidth = null;
		$this->fieldMultipleColumn= null;
		$this->fieldValueWidth = null;
		$this->fieldIsInLineWidth = null;
		$this->fieldOffset = 5;
		$this->resetFieldCorrectionDueToExternalFactors();
	}
	/**
	 * Field  stack management.
	 * Field group is a group of field. This is defined in the configuration with groupStart="1" and groupEnd="1" on a field without any type.
	 * Field groups can be imbricated.
	 * CrtField is the name of the current field in which the renderer opperates (=root if not in a fieldGroup)
	 * CrtfieldDepth is the depth of groups/fields, 0 is outside of any group (root)
	 *
	 */

	/**
	 * Field renderer must call this method on createInstance
	 */
	protected function initializeFieldWidth($totalWidth, $labelWidth){
		if($this->crtFieldName==null){
			$this->crtFieldName = "root";
		}
		$this->setFieldWidth($this->crtFieldName, $totalWidth, $labelWidth);
	}
	protected function enterFieldGroup($rm, $fieldXml, $fieldName, $idField){
		$fieldClass = (string)$fieldXml["class"];
		
		$parentTotalWidth = $this->getTotalWidth();
		$parentLabelWidth = $this->getLabelWidth();
		//eput($fieldName." ".$parentTotalWidth." ".$parentLabelWidth);
		
		$totalWidth = (string)$fieldXml["totalWidth"];
		if($totalWidth==null){
			if($fieldXml["expandOnMultipleColumn"]!=""){
				$factor = floatval($fieldXml["expandOnMultipleColumn"]);
			} else {
				$factor = 1;
			}
			$totalWidth = $parentTotalWidth*$factor;
		}
		
		//open fieldGroup div
		$style = "width: 100%; max-width:".($totalWidth)."px;";
        if($fieldXml["displayHidden"]=="1"){
            $style .= "display:none;";
        }
		$rm->put('<div id="'.$idField.'" class="field '.($fieldXml["noFieldset"] =="1" ? ' noFieldset ' : '').$fieldClass.'" style="'.$style.'" >');

		//display label if necessary
		if($fieldXml["noLabel"]!="1"){
			$labelWidth = $totalWidth;

            $style = "width: 100%; max-width:".$labelWidth."px;";
			$rm->put('<div class="label" style="'.$style.'" >');
			if($fieldXml["displayAsTitle"]=="1") $rm->put('<h4>');
			$rm->displayLabel($fieldName, $labelWidth, $this->getVisibleLanguage());
			if($fieldXml["displayAsTitle"]=="1") $rm->put('</h4>');
			$rm->put('</div>');

            if($fieldXml["displayAsTag"]=="1") $rm->put('<div class="displayAsTag"></div>');
		}

		//create the group container
		$rm->put('<div id="'.$idField.'_group" ');
		$rm->put('class="value fieldGroup SBIB ui-corner-all'.($fieldXml["noFieldset"] =="1" ? ' noFieldset ' : '').'" ');

// 		if((string)$fieldXml["totalWidth"]!=null){
// 			$rm->put('style="'. $padding. ' width: 100%; max-width:'.(string)$fieldXml["totalWidth"].'px;"');
// 		} else {
// 			$rm->put('style="'. $padding.  ' width: 100%; max-width:'.($this->getIsInLineWidth()+($fieldXml["noFieldset"]=="1" ? $this->getFieldOffset() : 0)).'px;"');
// 		}
		$rm->put('style="'. $padding.  ' width: 100%; max-width:'.($totalWidth).'px;"');
		$rm->put('>');

		$this->updateWidthOnEnterField($fieldName, $fieldXml);
	}

	protected function leaveCrtFieldGroup($rm, $fieldXml){
		$this->updateWidthOnLeaveField($fieldXml);

        if($fieldXml["displayAsTag"]=="1"){
            $rm->put('<div style="clear: both;"></div>');
            $rm->put('<div class="lessBsp"><span class="glyphicon glyphicon-menu-up" aria-hidden="true"></span> Less</div>');
            $rm->put('<div  class="displayAsTag"></div>');
        }

		$rm->put('</div></div>'); //close div.value, div.field
	}


	protected function isCrtFieldFilled(){
		return $this->fieldIsFilled[$this->getCrtField()];
	}
	/**
	 * set the current Field as filled.
	 */
	protected function setCrtFieldIsFilled(){
		//mark as filled the whole field stack
		foreach($this->fieldStack as $fieldName){
			if($fieldName=="root") continue;
			$this->fieldIsFilled[$fieldName] = true;
		}
	}
	protected function isCrtFieldHasError(){
		return $this->fieldHasError[$this->getCrtField()];
	}
	/**
	 * set the current Field as hasError.
	 */
	protected function setCrtFieldHasError(){
		//mark as filled the whole field stack
		foreach($this->fieldStack as $fieldName){
			if($fieldName=="root") continue;
			$this->fieldHasError[$fieldName] = true;
		}
	}

	/**
	 * Enter in a new field or fieldGroup
	 * @param $fieldName : name of the field or fieldGroup (this name is not always equal to $fieldGroupXml->getName() in the case of there is multiple times the same name in the configuration for a fieldGroup
	 * @param $fieldXml : xml, contains the xml with all the parameters of this new field.
	 */
	protected function updateWidthOnEnterField($fieldName, $fieldXml){
		$parentTotalWidth = $this->getTotalWidth();
		$parentLabelWidth = $this->getLabelWidth();
		//eput($fieldName." ".$parentTotalWidth." ".$parentLabelWidth);
		
		$totalWidth = (string)$fieldXml["totalWidth"];
		if($totalWidth==null){
			if($fieldXml["expandOnMultipleColumn"]!=""){
				$factor = floatval($fieldXml["expandOnMultipleColumn"]);
			} else {
				$factor = 1;
			}
			$totalWidth = $parentTotalWidth*$factor;
		}
		$labelWidth = (string)$fieldXml["labelWidth"];
		//if($labelWidth==null) $labelWidth = $parentLabelWidth;
		//eput($fieldName." ".$totalWidth." ".$labelWidth);
		
		$this->pushField($fieldName);
		$useMultipleColumn = (int)(string)$fieldXml["useMultipleColumn"];
		
		//if group with fieldset change the width
		if(($fieldXml["groupStart"]=="1" || $fieldXml["groupEnd"]=="1") && $fieldXml["noFieldset"]!="1"){
			$totalWidth = $totalWidth - $this->getFieldGroupParentOffset() - $this->getFieldOffset();
		}
		//eput($fieldName." ".$totalWidth." ".$labelWidth);
		
		//define the useMultiplecolumn
		if(($fieldXml["groupStart"]=="1" || $fieldXml["groupEnd"]=="1") && $useMultipleColumn){
			$totalWidth = floor($totalWidth/ $useMultipleColumn);
		}
		//eput($fieldName." ".$totalWidth." ".$labelWidth);
		
		if($labelWidth == null) $labelWidth = floor($totalWidth * ($parentLabelWidth / $parentTotalWidth));
		//eput($fieldName." ".$totalWidth." ".$labelWidth);
		
		//if label is too big making the value size less than 10 then adapt the label to leave the value minimum with a size of 10
		if(10>=($totalWidth-$labelWidth)){
			$labelWidth = $totalWidth-10;
		}
		//eput($fieldName." ".$totalWidth." ".$labelWidth);
		
		$this->setFieldWidth($fieldName, $totalWidth, $labelWidth);

	}

	/**
	 * Leave crt field or group
	 */
	protected function updateWidthOnLeaveField(){
		$this->popField();
		return $this->getCrtField();
	}


	// implementation

	protected function getCrtField(){
		return $this->crtFieldName;
	}
	protected function getCrtFieldDepth(){
		return $this->fieldDepth;
	}
	protected function getFieldGroupParentOffset(){
		return $this->fieldGroupParentOffset;
	}
	protected function setFieldGroupParentOffset($offset){
		$this->fieldGroupParentOffset = floatval($offset);
	}
	protected function getFieldCorrectionDueToExternalFactors(){
		return $this->fieldCorrectionDueToExternalFactors;
	}
	protected function increaseFieldCorrectionDueToExternalFactors($offset){
		$this->fieldCorrectionDueToExternalFactors += floatval($offset);
	}
	public function resetFieldCorrectionDueToExternalFactors(){
		$this->fieldCorrectionDueToExternalFactors= 0;
	}
	protected function pushField($fieldName){
		if($fieldName=="root") throw new FieldRendererException("cannot define a field  with 'root'. 'root' is reserved.", FieldRendererException::INVALID_ARGUMENT);
		$this->fieldDepth++;
		$this->crtFieldName = $fieldName;
		array_push($this->fieldStack, $fieldName);
	}
	protected function popField(){
		$this->fieldDepth--;
		array_pop($this->fieldStack);
		if($this->fieldStack) $this->crtFieldName = end($this->fieldStack);
		else $this->crtFieldName = "root";
	}

	public function getTotalWidth(){
		if(!$this->fieldTotalWidth) return null;
		return $this->fieldTotalWidth[$this->getCrtField()];
	}
	public function getLabelWidth(){
		if(!$this->fieldLabelWidth) return null;
		return $this->fieldLabelWidth[$this->getCrtField()]; //the correction due to external factor is already added from the value, no need to remove from the label
	}
	public function getValueWidth(){
		if(!$this->fieldValueWidth) return null;
		return $this->fieldValueWidth[$this->getCrtField()]+$this->fieldCorrectionDueToExternalFactors;
	}
	public function getIsInLineWidth(){
		if(!$this->fieldIsInLineWidth) return null;
		return $this->fieldIsInLineWidth[$this->getCrtField()];
	}
	protected function getFieldOffset(){
		return $this->fieldOffset;
	}
	protected function setFieldOffset($offset){
		$this->fieldOffset = $offset;
	}
	protected function setFieldWidth($fieldName, $totalWidth, $labelWidth){
		$this->fieldTotalWidth[$fieldName] = $totalWidth;
		$this->fieldLabelWidth[$fieldName] = $labelWidth;
		$this->fieldValueWidth[$fieldName] = $totalWidth-$labelWidth-$this->getFieldOffset();
		$this->fieldIsInLineWidth[$fieldName] = $totalWidth-$this->getFieldOffset();
	}
}


