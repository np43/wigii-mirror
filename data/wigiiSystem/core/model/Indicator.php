<?php
/**
 *  This file is part of Wigii (R) software.
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
 * A wigii Indicator
 * An indicator is a myslq function applied to a specific field in a selection of elements in groups 
 * Created by LWR on 23 February 2011
 * Updated by Wigii.org(Lionel Weber) on October 2017 to support system indicators and recursive indicators
 */
class Indicator extends Model
{
	//mysql standard functions
	const FUNC_AVG = 1;
	const FUNC_COUNT = 3;
	const FUNC_COUNT_DISTINCT = 4;
	const FUNC_MAX = 7;
	const FUNC_MIN = 8;
	const FUNC_SUM = 9;
	const FUNC_SQRT = 10;
	const FUNC_STDDEV_POP = 11;
	const FUNC_STDDEV_SAMP = 12;
	const FUNC_VAR_POP = 13;
	const FUNC_VAR_SAMP = 14;
	
	private $fieldSelector = null;
	private $dataType = null;
	private $func = null;
	private $value = null;
	private $timestamp = null;
	private $id = null;
	private $label = null;
	private $isSystemIndicator = null;
	private $isRecursive = null; //if true the indicator include the children groups content (recursively)
	
	public static function createInstance($fieldSelector, $dataType, $func, $label=null, $id=null, $isRecursive=false, $isSystemIndicator=false)
	{
		$r = new self();
		$r->setFieldSelector($fieldSelector);
		if($fieldSelector && !$fieldSelector->isElementAttributeSelector()){
			$r->setDataType($dataType);
		}
		$r->setFunction($func);
		if($id!==null) $r->id = $id;
		$r->reset($label, $isRecursive, $isSystemIndicator);
		return $r;
	}
	
	public function reset($label=null, $isRecursive=false, $isSystemIndicator=false){
		$this->value = null;
		$this->timestamp = null;
		$this->setSystemIndicator($isSystemIndicator);
		$this->setRecursive($isRecursive);
		$this->setLabel($label);
		$this->getId();
	}
	
	public function getId(){
		if(!isset($this->id)){
			if($this->isSystemIndicator()){
				//system indicator id must not be dependant from time as we need that the id remains when it is evaluated (as it is not stored in session)
				$this->id = md5($this->getLabel().$this->getFunction().($this->getFieldSelector()->isElementAttributeSelector() ? $this->getFieldSelector()->getFieldName() : $this->getDataType()->getDataTypeName()).$this->getFieldSelector()->getFieldName().$this->getFieldSelector()->getSubFieldname());
			} else {
				$this->id = md5(time().($this->getFieldSelector()->isElementAttributeSelector() ? $this->getFieldSelector()->getFieldName() : $this->getDataType()->getDataTypeName()).$this->getFieldSelector()->getFieldName().$this->getFieldSelector()->getSubFieldname());
			}
		}
		return $this->id;
	}
	
	//system indicators are not persisted and are defined in configuration of the module
	public function isSystemIndicator(){
		return $this->isSystemIndicator;
	}
	public function setSystemIndicator($isSystemIndicator){
		$this->isSystemIndicator = $isSystemIndicator;
	}
	//if the indicator is tagged as recursive, then the value include all the sub folders content
	public function isRecursive(){
		return $this->isRecursive;
	}
	public function setRecursive($isRecursive){
		$this->isRecursive= $isRecursive;
	}
	public function getLabel(){
		return $this->label;
	}
	public function setLabel($label){
		if($label ==null){
			$this->label = $this->getFieldSelector()->getFieldName();
			$this->label .= "(".$this->getFunctionName().($this->isRecursive() ? " R" : "").")";
		} else $this->label = $label;
	}
	
	public static function getAvailableFunctions(){
		return array(
			Indicator::FUNC_AVG=>"FUNC_AVG",
			Indicator::FUNC_COUNT=>"FUNC_COUNT",
			Indicator::FUNC_COUNT_DISTINCT=>"FUNC_COUNT_DISTINCT",
			Indicator::FUNC_MAX=>"FUNC_MAX",
			Indicator::FUNC_MIN=>"FUNC_MIN",
			Indicator::FUNC_SUM=>"FUNC_SUM"
//			,Indicator::FUNC_SQRT=>"FUNC_SQRT",
//			Indicator::FUNC_STDDEV_POP=>"FUNC_STDDEV_POP",
//			Indicator::FUNC_STDDEV_SAMP=>"FUNC_STDDEV_SAMP",
//			Indicator::FUNC_VAR_POP=>"FUNC_VAR_POP",
//			Indicator::FUNC_VAR_SAMP=>"FUNC_VAR_SAMP"
			);
	}
	
	public static function getAvailableFunctionsForElementAttribute($attribute){
		if($attribute==null) throw new ElementServiceException("Element attribute cannot be null.", ElementServiceException::INVALID_ARGUMENT);
		switch($attribute){
			case "sys_creationDate":
			case "sys_date":
				return array(
					Indicator::FUNC_AVG=>"FUNC_AVG", 
					Indicator::FUNC_COUNT=>"FUNC_COUNT",
					Indicator::FUNC_COUNT_DISTINCT=>"FUNC_COUNT_DISTINCT", 
					Indicator::FUNC_MAX=>"FUNC_MAX", //for dates most in future (begDate)
					Indicator::FUNC_MIN=>"FUNC_MIN", //for dates most in past
					);
				break;
			case "sys_creationUser":
			case "sys_user":
				return array(
					Indicator::FUNC_COUNT=>"FUNC_COUNT", //count none null values
					Indicator::FUNC_COUNT_DISTINCT=>"FUNC_COUNT_DISTINCT", //count none null unique values 
					);
				break;
			case "state_locked":
			case "state_important2":
			case "state_important1":
			case "state_hidden":
			case "state_archived":
			case "state_deprecated":
			case "state_finalized" :
			case "state_approved" :
			case "state_dismissed" :
			case "state_blocked" :	
				return array(
					Indicator::FUNC_COUNT=>"FUNC_COUNT", //count none null values
					);
				break;
		}
		return array();
	}
	public static function getAvailableFunctionsForDataType($dataType){
		if($dataType==null) throw new ElementServiceException("DataType cannot be null.", ElementServiceException::INVALID_ARGUMENT);
		switch($dataType->getDataTypeName()){
			case "Links":
			case "Floats":
			case "Numerics":
				return array(
					Indicator::FUNC_AVG=>"FUNC_AVG",
					Indicator::FUNC_COUNT=>"FUNC_COUNT",
					Indicator::FUNC_MAX=>"FUNC_MAX",
					Indicator::FUNC_MIN=>"FUNC_MIN",
					Indicator::FUNC_SUM=>"FUNC_SUM"
//					,Indicator::FUNC_SQRT=>"FUNC_SQRT",
//					Indicator::FUNC_STDDEV_POP=>"FUNC_STDDEV_POP",
//					Indicator::FUNC_STDDEV_SAMP=>"FUNC_STDDEV_SAMP",
//					Indicator::FUNC_VAR_POP=>"FUNC_VAR_POP",
//					Indicator::FUNC_VAR_SAMP=>"FUNC_VAR_SAMP"
					);
				break;
			case "Addresses":
			case "Booleans":
			case "Blobs":
			case "Texts":
			case "Varchars":
				return array(
					Indicator::FUNC_COUNT=>"FUNC_COUNT", //count none null values
					);
				break;
			case "Dates":
			case "Times":
			case "TimeRanges":
				return array(
					Indicator::FUNC_AVG=>"FUNC_AVG", 
					Indicator::FUNC_COUNT=>"FUNC_COUNT",
					Indicator::FUNC_COUNT_DISTINCT=>"FUNC_COUNT_DISTINCT", 
					Indicator::FUNC_MAX=>"FUNC_MAX", //for dates most in future (begDate)
					Indicator::FUNC_MIN=>"FUNC_MIN", //for dates most in past
					);
				break;
			case "Files":
				return array(
					Indicator::FUNC_SUM=>"FUNC_SUM", //based on size
					Indicator::FUNC_AVG=>"FUNC_AVG", //based on size
					Indicator::FUNC_COUNT=>"FUNC_COUNT",
					Indicator::FUNC_COUNT_DISTINCT=>"FUNC_COUNT_DISTINCT", //based on type
					Indicator::FUNC_MAX=>"FUNC_MAX", //based on size
					Indicator::FUNC_MIN=>"FUNC_MIN", //based on size
					);
				break;
			case "MultipleAttributs":
			case "Emails":
			case "Urls":
				return array(
					Indicator::FUNC_COUNT=>"FUNC_COUNT", //count none null values
					Indicator::FUNC_COUNT_DISTINCT=>"FUNC_COUNT_DISTINCT", //count none null unique values 
					);
				break;
			case "Strings":
			case "Attributs":
				return array(
					Indicator::FUNC_COUNT=>"FUNC_COUNT", //count none null values
					Indicator::FUNC_COUNT_DISTINCT=>"FUNC_COUNT_DISTINCT", //count none null unique values 
					Indicator::FUNC_SUM=>"FUNC_SUM",
					Indicator::FUNC_AVG=>"FUNC_AVG",
					Indicator::FUNC_MAX=>"FUNC_MAX",
					Indicator::FUNC_MIN=>"FUNC_MIN"
					);
				break;
		}
		return array();
	}
	
	//return the subFields names depending on the dataType and the function
	public function getSubFields(){
		$dataType = $this->getDataType();
		$func = $this->getFunction();
		$fs = $this->getFieldSelector();		
		if($fs==null) throw new ElementServiceException("FieldSelector cannot be null.", ElementServiceException::INVALID_ARGUMENT);
		if($dataType==null && !$fs->isElementAttributeSelector()) throw new ElementServiceException("DataType cannot be null.", ElementServiceException::INVALID_ARGUMENT);
		if($func==null) throw new ElementServiceException("Function cannot be null.", ElementServiceException::INVALID_ARGUMENT);
		
		if($fs->isElementAttributeSelector()){
			return array($fs->getSubFieldName());
		}
		
		$subFields = null;
		switch($dataType->getDataTypeName()){
			case "Links":
			case "Floats":
			case "Numerics":
			case "Booleans":
			case "Blobs":
			case "Dates":
			case "Times":
			case "Strings":
			case "Attributs":
			case "MultipleAttributs":
			case "Emails":
				$subFields = array("value");
				break;
			case "Addresses":
				$subFields = array("street", "zip_code", "city", "state", "country");
				break;
			case "TimeRanges":
				$subFields = array("begDate");
				break;
			case "Files":
				if($func== Indicator::FUNC_SUM) return array("size");
				if($func== Indicator::FUNC_AVG) return array("size");
				if($func== Indicator::FUNC_COUNT) return array("path");
				if($func== Indicator::FUNC_COUNT_DISTINCT) return array("type");
				if($func== Indicator::FUNC_MAX) return array("size");
				if($func== Indicator::FUNC_MIN) return array("size");
				break;
			case "Urls":
				$subFields = array("url");
				break;
			case "Texts":
			case "Varchars":
				//look if not null in any languages
				$subFields = array("value_l01","value_l02","value_l03","value_l04","value_l05","value_l05","value_l06","value_l07","value_l08","value_l09","value_l10");
				break;
		}
		//look in the fieldSelector if a subField is defined
		//if yes check that the subfield is matching
		$subField = $this->getFieldSelector()->getSubFieldName();
		if($subField){
			if(in_array($subField, $subFields)){
				return array($subField);
			} else {
				throw new ElementServiceException("SubField: ".$subField." defined in FieldSelector is not includeded in possible subFields (".implode(", ", $subFields).") for DataType: ".$dataType->getDataTypeName()." and Func: ".$func, ElementServiceException::INVALID_ARGUMENT);
			}
		}
		return $subFields;
	}
	
	protected function setFieldSelector($fl){
		$this->fieldSelector = $fl;
	}
	public function getFieldSelector(){
		return $this->fieldSelector;
	}
	
	protected function setDataType($dataType){
		if($dataType==null) throw new ElementServiceException("DataType cannot be null.", ElementServiceException::INVALID_ARGUMENT);
		$this->dataType = $dataType;
	}
	public function getDataType(){
		return $this->dataType;
	}
	
	/**
	 * @param $func int/strin : func can either be a string representing the constant name or the constant number
	 */
	protected function setFunction($func){
		if($func===null) throw new ElementServiceException("Function cannot be null.", ElementServiceException::INVALID_ARGUMENT);
		if($this->getFieldSelector()->isElementAttributeSelector()){
			$validFunc = $this->getAvailableFunctionsForElementAttribute($this->getFieldSelector()->getSubFieldName());
		} else {
			$validFunc = $this->getAvailableFunctionsForDataType($this->getDataType());
		}
		if($validFunc[$func]!=null){
			$this->func = $func;
		} else if(in_array($func, $validFunc)){
			$this->func = array_search($func, $validFunc);
		} else {
			if($this->getFieldSelector()->isElementAttributeSelector()){
				throw new ElementServiceException("invalid indicator function: ".$func." for element attribute: ".$this->getFieldSelector()->getSubFieldName(), ElementServiceException::INVALID_ARGUMENT);
			} else {
				throw new ElementServiceException("invalid indicator function: ".$func." for DataType: ".$this->getDataType()->getDataTypeName(), ElementServiceException::INVALID_ARGUMENT);
			}
		}
		$this->func = $func;
	}
	public function getFunction(){
		return $this->func;
	}
	public function getFunctionName(){
		$func = $this->getAvailableFunctions();
		return $func[$this->getFunction()];
	}
	
	//set value updates the timestamp to now
	public function setValue($val){
		$this->value = $val;
		$this->setTimestamp();
	}
	public function getValue(){
		return $this->value;
	}
	
	protected function setTimestamp(){
		$this->timestamp = time();
	}
	
	public function getTimestamp(){
		return $this->timestamp;
	}
	
}





