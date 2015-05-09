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

/**
 * WigiiBag for Extraction of Unsubscribe Emails implementation
 */

class WigiiBagExtractUnsubscribeEmailsImpl implements WigiiBag {

	private $emails;
	private $elementIds;
	
	public static function createInstance(){
		$var = new self();
		return $var;
	}

	public function getEmails(){
		if(!isset($this->emails)) return array();
		return array_keys($this->emails);
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
	
	private $crtEmail;
	private $crtEmailHasExternalAccess;
	
	public function setValue($value, $elementId, $dataTypeName, $fieldName, $subFieldName=null){
		if($dataTypeName!="Emails"){ //for other fields do the format
			return;
		}
		
		if(!isset($this->emails)){
			$this->emails = array();
		}
		if(!isset($this->elementIds)){
			$this->elementIds = array();
		}
		if(!isset($this->elementIds[$elementId])){
			$this->elementIds[$elementId] = array();
		}
		
		if($dataTypeName=="Emails" && $subFieldName=="value" && $value != null){
			$value = trim($value);
			$this->emails[$value] = 1;
			$this->elementIds[$elementId][$value]["value"] = $value;
			$this->crtEmail = $value;
			$this->crtEmailHasExternalAccess = false;
		}
		if($dataTypeName=="Emails" && $subFieldName=="proofKey" && $value != null){
			$this->elementIds[$elementId][$this->crtEmail]["proofKey"] = $value;
		}
		if($dataTypeName=="Emails" && $subFieldName=="externalAccessLevel" && $value && $value!= Emails::EXTERNAL_ACCESS_STOP){
			$this->crtEmailHasExternalAccess = true;
			$this->elementIds[$elementId][$this->crtEmail]["externalAccessLevel"] = $value;
		}
		if($this->crtEmailHasExternalAccess && $dataTypeName=="Emails" && $subFieldName=="externalCode" && $value != null){
			$this->elementIds[$elementId][$this->crtEmail]["externalCode"] = $value;
		}
	}

	public function getValue($elementId, $dataTypeName, $fieldName, $subFieldName=null){ new ServiceException('unsupported by this implementation', ServiceException::UNSUPPORTED_OPERATION); }
	
	public function applyOperator($operator, $value, $dataTypeName, $fieldName, $subFieldName=null, $field=null){ new ServiceException('unsupported by this implementation', ServiceException::UNSUPPORTED_OPERATION); }
}



