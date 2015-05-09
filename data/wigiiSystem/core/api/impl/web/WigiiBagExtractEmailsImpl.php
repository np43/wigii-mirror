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
 * WigiiBag for Extraction of Emails implementation
 */

class WigiiBagExtractEmailsImpl implements WigiiBag {

	private $emails;
	private $elementIds;
	
	public static function createInstance(){
		$var = new self();
		return $var;
	}
	
	public function reset(){
		$this->emails = array();
		$this->elementIds = array();
	}

	public function getEmails(){
		if(!isset($this->emails)) return array();
		return $this->emails;
	}
	/**
	 * returns all needed information regarding the emails:
	 * array(
	 * 	element id=>array(
	 * 			value=>email value
	 * 			proofKey=>confirmation code
	 * 			externalAccessLevel=>int representing if external access is available (0 = stop, 1 = read, 2 = edit)
	 * 			externalCode=>access code
	 * 			)
	 *	 	...
	 * 	)
	 * )
	 */
	public function getElementIds(){
		if(!isset($this->elementIds)) return array();
		return $this->elementIds;
	}
	
	public function setValue($value, $elementId, $dataTypeName, $fieldName, $subFieldName=null){
		if(!isset($this->emails)){
			$this->emails = array();
		}
		if(!isset($this->elementIds)){
			$this->elementIds = array();
		}
		if(!isset($this->elementIds[$elementId])){
			$this->elementIds[$elementId] = array();
		}
		if(!isset($this->elementIds[$elementId][$fieldName])){
			$this->elementIds[$elementId][$fieldName] = array();
		}
		if($value!=null){
			$this->elementIds[$elementId][$fieldName][$subFieldName] = $value;
			if($subFieldName=="value" && $dataTypeName=="Emails"){
				$this->emails[$value] = $value;
			}
		}
	}

	public function getValue($elementId, $dataTypeName, $fieldName, $subFieldName=null){ new ServiceException('unsupported by this implementation', ServiceException::UNSUPPORTED_OPERATION); }
	
	public function applyOperator($operator, $value, $dataTypeName, $fieldName, $subFieldName=null, $field=null){ new ServiceException('unsupported by this implementation', ServiceException::UNSUPPORTED_OPERATION); }
}



