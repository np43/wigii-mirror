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
 * WigiiBag for Extraction of Emails implementation and Merge Data
 */

class WigiiBagExtractEmailsAndMergeDataImpl implements WigiiBag {

	private $emails;
	private $elementIds;
	private $emailLabel;
	private $fieldList;
	private $templateRecordManager;
	public static function createInstance(){
		$var = new self();
		return $var;
	}

	public function reset($fieldList = null, $templateRecordManager=null){
		$this->emails = array();
		$this->elementIds = array();
		$this->emailLabel = array();
		$this->fieldList = $fieldList;
		$this->templateRecordManager = $templateRecordManager;
	}

	public function getEmails(){
		if(!isset($this->emails)) return array();
		return $this->emails;
	}
	/**
	 * returns all needed information regarding the emails:
	 * array(
	 * 	element id=>array(
	 * 		label=>string constructed with implode(labelSep, label1, label2, label3, label4)
	 * 		email value=>array(
	 * 			value=>email value
	 * 			proofKey=>confirmation code
	 * 			externalAccessLevel=>int representing if external access is available (0 = stop, 1 = read, 2 = edit)
	 * 			externalCode=>access code
	 * 			)
	 * 		(optional) email value 2=>array(...
	 *	 	...
	 * 	)
	 * )
	 */
	public function getElementIds(){
		if(!isset($this->elementIds)) return array();
		return $this->elementIds;
	}
	/**
	 * add a label field in the return for email merging.
	 * emailLabel must be an array formated:
	 * 	label1=>field containing a value
	 * 	(optional) label2=>field containing a value
	 * 	(optional) label3=>field containing a value
	 * 	(optional) label4=>field containing a value
	 * 	(optional) labelSep=>string representing the separator to implode label1-->label4
	 */
	public function setLabels($emailLabel){
		$this->emailLabel = $emailLabel;
	}
	protected function getLabels($key=null, $fieldName=null){
		if($key===null) return $this->emailLabel;
		else if($fieldName==null) return $this->emailLabel[$key];
		else return $this->emailLabel[$key][$fieldName];
	}
	public function setFieldList($fieldList){
		$this->fieldList = $fieldList;
	}
	protected function getFieldList(){
		return $this->fieldList;
	}
	public function setTRM($trm){
		$this->templateRecordManager = $trm;
	}
	protected function getTRM(){
		return $this->templateRecordManager;
	}

	public function setValue($value, $elementId, $dataTypeName, $fieldName, $subFieldName=null){
		if(!isset($this->emails)){
			$this->emails = array("allFields"=>array(), "perFields"=>array());
		}
		if(!isset($this->elementIds)){
			$this->elementIds = array();
		}
		if(!isset($this->elementIds[$elementId])){
			$this->elementIds[$elementId] = array();
		}
		if($dataTypeName!="Emails"){ //for other fields do the format
			$value = $this->getTRM()->formatValue($fieldName, $subFieldName, $value, $this->getFieldList()->getField($fieldName));
		}
		if($value!=null){
			//this works only with the subField value except of Emails where we take everything
			if($dataTypeName=="Emails"){
				$this->elementIds[$elementId][$fieldName][$subFieldName] = $value;
			} elseif($subFieldName =="value") {
				$this->elementIds[$elementId][$fieldName] = $value;
			}
		}
		if($dataTypeName=="Emails" && $subFieldName=="value" && $value != null){
			$value = trim($value);
			$this->emails["allFields"][$value] = 1;
			$this->emails["perFields"][$fieldName][$value] = 1;
		}
//
//		if($dataTypeName=="Emails" && $subFieldName=="value" && $value != null){
//			$value = trim($value);
//			$this->emails[$value] = 1;
//			$this->elementIds[$elementId][$value]["value"] = $value;
//			$this->elementIds[$elementId][$value]["fieldName"] = $fieldName;
//			$this->crtEmail = $value;
//			$this->crtEmailHasExternalAccess = false;
//		}
//		if($dataTypeName=="Emails" && $subFieldName=="proofKey" && $value != null){
//			$this->elementIds[$elementId][$this->crtEmail]["proofKey"] = $value;
//		}
//		if($dataTypeName=="Emails" && $subFieldName=="externalAccessLevel" && $value && $value!= Emails::EXTERNAL_ACCESS_STOP){
//			$this->crtEmailHasExternalAccess = true;
//			$this->elementIds[$elementId][$this->crtEmail]["externalAccessLevel"] = $value;
//		}
//		if($this->crtEmailHasExternalAccess && $dataTypeName=="Emails" && $subFieldName=="externalCode" && $value != null){
//			$this->elementIds[$elementId][$this->crtEmail]["externalCode"] = $value;
//		}

//		if(($this->getLabels("label1", $fieldName) ||
//			$this->getLabels("label2", $fieldName) ||
//			$this->getLabels("label3", $fieldName) )
//			 && $value != null){
//			if($this->elementIds[$elementId]["label"][$fieldName]) $this->elementIds[$elementId]["label"][$fieldName] .= $this->getLabels("labelSep", $fieldName);
//			$this->elementIds[$elementId]["label"][$fieldName] .= $value;
//		}
//		if($this->getLabels("other1", $fieldName) && $value != null){
//			$this->elementIds[$elementId]["other1"][$fieldName] = $value;
//		}
//		if($this->getLabels("other2", $fieldName) && $value != null){
//			$this->elementIds[$elementId]["other2"][$fieldName] = $value;
//		}
//		if($this->getLabels("other3", $fieldName) && $value != null){
//			$this->elementIds[$elementId]["other3"][$fieldName] = $value;
//		}
	}

	public function getValue($elementId, $dataTypeName, $fieldName, $subFieldName=null){ new ServiceException('unsupported by this implementation', ServiceException::UNSUPPORTED_OPERATION); }

	public function applyOperator($operator, $value, $dataTypeName, $fieldName, $subFieldName=null, $field=null){ new ServiceException('unsupported by this implementation', ServiceException::UNSUPPORTED_OPERATION); }
}



