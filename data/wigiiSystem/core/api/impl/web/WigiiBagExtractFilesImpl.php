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
 * WigiiBag for Extraction of Files implementation
 */

class WigiiBagExtractFilesImpl implements WigiiBag {

	private $files;
	private $elementIds;
	public static function createInstance(){
		$var = new self();
		return $var;
	}

	public function getFilePaths(){
		if(!isset($this->files)) return array();
		return $this->files;
	}
	public function getElementIds(){
		if(!isset($this->elementIds)) return array();
		return $this->elementIds;
	}

	public function setValue($value, $elementId, $dataTypeName, $fieldName, $subFieldName=null){
		if($value != null){
			if(!isset($this->elementIds)) $this->elementIds = array();
			if(!isset($this->elementIds[$elementId])) $this->elementIds[$elementId] = array();
			if(!isset($this->files)) $this->files = array();
			if(!isset($this->files[$fieldName])) $this->files[$fieldName] = array();
			if(!isset($this->files[$fieldName][$elementId])) $this->files[$fieldName][$elementId] = array();
			$this->files[$fieldName][$elementId][$subFieldName] = $value;
		}
	}

	public function getValue($elementId, $dataTypeName, $fieldName, $subFieldName=null){ throw new ServiceException('unsupported by this implementation', ServiceException::UNSUPPORTED_OPERATION); }
	
	public function applyOperator($operator, $value, $dataTypeName, $fieldName, $subFieldName=null, $field=null){ ServiceException('unsupported by this implementation', ServiceException::UNSUPPORTED_OPERATION); }
}



