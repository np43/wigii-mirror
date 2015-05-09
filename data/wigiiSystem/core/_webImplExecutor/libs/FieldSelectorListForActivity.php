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

/*
 * Created on 12 oct. 09
 * by LWR
 */

class FieldSelectorListForActivity extends FieldSelectorListArrayWebImpl implements FieldList {

	protected $forceValueSubfieldIfnotdefined;

	//by default do not retrieve SysInfo Fields
	//by default if a field is defined without subfield all the subfields are took
	public static function createInstance($errorOnDuplicate=true, $addSysInfoFields=true, $forceValueSubfieldIfnotdefined=false)
	{
		$returnValue = new self();
		$returnValue->reset($errorOnDuplicate, $addSysInfoFields, $forceValueSubfieldIfnotdefined);
		return $returnValue;
	}

	public function reset($errorOnDuplicate=null, $addSysInfoFields=null, $forceValueSubfieldIfnotdefined=null)
	{
		parent::reset();
		if($forceValueSubfieldIfnotdefined!==null){
			$this->forceValueSubfieldIfnotdefined = $forceValueSubfieldIfnotdefined;
		}
	}

	function __sleep(){
		//serialize all except the xml
		return array("selectedLanguage", "objArray");
	}
	private $fieldsXml = array();
	private $defaultSortingKey = null;
	private $defaultSortingAscending = true;
	private $defaultGroupByKey = null;
	//during the add of the field we directly create the headers
	//like this we can manage also the specialFields
	public function addField($field, $subFieldName=null){
		$xml = $field->getXml();
		$addFieldSelectorSysInfoSubFieldsXml = false;
		if($xml["elementAttribut"]!=null){
			$subFieldName = (string)$xml["elementAttribut"];
			$fs = FieldSelector::createElementAttributeSelector($subFieldName);
			$key = $this->addFieldSelectorInstance($fs);
		} else if($xml["field"]!=null) {
			$f = (string)$xml["field"];
			$sub = (string)$xml["subField"];
			if($sub) $subFieldName = $sub;
			else if($this->forceValueSubfieldIfnotdefined) $subFieldName = "value";
			//else $subFieldName = "value"; //add this to prevent the selection of sys fields in the db query
			if($subFieldName) $addFieldSelectorSysInfoSubFieldsXml = true;
			$key = $this->addFieldSelector($f, $subFieldName);
		} else {
			if($subFieldName) $addFieldSelectorSysInfoSubFieldsXml = true;
			$key = $this->addFieldSelector($field->getFieldName(), $subFieldName);
		}
		if($xml["isDefaultSortingKey"]=="1"){
			$this->setDefaultSortingKey($key);
			$this->setDefaultSortingAscending(!stripos($xml["defaultSorted"], "desc")!==false);
		}
		if($xml["isDefaultGroupByKey"]=="1"){
			$this->setDefaultGroupByKey($key);
		}
		if($addFieldSelectorSysInfoSubFieldsXml && $this->addSysInfoFields){
			$this->addFieldSelectorSysInfoSubFieldsXml($field->getFieldName(), $xml);
		}
		$this->fieldsXml[$key] = $xml;
	}

	protected function addFieldSelectorSysInfoSubFieldsXml($fieldName, $xml=null){
		$sysInfo = array("sys_user", "sys_username", "sys_date", "sys_creationUser", "sys_creationUsername", "sys_creationDate");
		foreach($sysInfo as $sysSubField){
			$key = $this->getFieldSelectorKey($fieldName, $sysSubField);
			if(!isset($this->fieldsXml[$key])){
				$this->fieldsXml[$key] = $xml;
			}
		}
	}

	//add $fieldSelector to actual objects. if fields are common no problem
	public function mergeFieldSelectorList($fieldSelectorList){
		parent::mergeFieldSelectorList($fieldSelectorList);
		if(is_a($fieldSelectorList, "FieldSelectorListForActivity")){
			$this->fieldsXml = array_merge($this->fieldsXml, $fieldSelectorList->getFieldsXml());
		}
	}

	public function getField($fieldName){ throw new ServiceException('unsupported by this implementation', ServiceException::UNSUPPORTED_OPERATION); }
	public function doesFieldExist($fieldName){ throw new ServiceException('unsupported by this implementation', ServiceException::UNSUPPORTED_OPERATION); }

	public function getFieldsXml(){ return $this->fieldsXml; }
	public function getXml($key){ return $this->fieldsXml[$key]; }
	public function getXmlFromField($fieldName, $subFieldName=null){
		if($subFieldName==null && $this->forceValueSubfieldIfnotdefined) $subFieldName = "value";
		return $this->fieldsXml[$this->getFieldSelectorKey($fieldName, $subFieldName)];
	}
	//public function getNbXml(){ return count($this->fieldsXml); }

	public function setDefaultSortingKey($key){ $this->defaultSortingKey = $key; }
	public function getDefaultSortingKey(){ return $this->defaultSortingKey; }
	public function setDefaultSortingAscending($ascending){ $this->defaultSortingAscending = $ascending; }
	public function getDefaultSortingAscending(){ return $this->defaultSortingAscending; }
	public function setDefaultGroupByKey($key){ $this->defaultGroupByKey = $key; }
	public function getDefaultGroupByKey(){ return $this->defaultGroupByKey; }

}



