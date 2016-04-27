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
 * Created on 12 oct. 09
 * by LWR
 */

class FieldSelectorListFromFieldList extends FieldSelectorListArrayWebImpl implements FieldList {

	//by default do not add SysInfo Fields
	//SysInfo fields are added only if searchable = 1, see ListContext.php->addLogExpOnTextSearch
	public static function createInstance($errorOnDuplicate=true, $addSysInfoFields=false)
	{
		$returnValue = new self();
		$returnValue->reset($errorOnDuplicate, $addSysInfoFields);
		return $returnValue;
	}

	function __sleep(){
		//serialize all except the xml
		return array("selectedLanguage", "objArray");
	}

	private $fieldsXml = array();

	//during the add of the field we directly create the headers
	//like this we can manage also the specialFields
	public function addField($field){
		$xml = $field->getXml();
		$f = $field->getFieldName();
		$this->addFieldSelector($f);
		$this->fieldsXml[$f] = $xml;
	}

	public function getField($fieldName){ throw new ServiceException('unsupported by this implementation', ServiceException::UNSUPPORTED_OPERATION); }
	public function doesFieldExist($fieldName){ throw new ServiceException('unsupported by this implementation', ServiceException::UNSUPPORTED_OPERATION); }

	public function getXml($key){ return $this->fieldsXml[$key]; }
	public function getFieldSelector($key){ return $this->objArray[$key]; }
}



