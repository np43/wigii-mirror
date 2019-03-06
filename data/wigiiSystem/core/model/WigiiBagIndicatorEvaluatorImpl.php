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
 * WigiiBag for evaluating Indicator model implementation
 * is designed only for Field with DataType. use ElementListIndicatorEvaluatorImpl for elementAttributes
 */

class WigiiBagIndicatorEvaluatorImpl implements WigiiBag, IndicatorEvaluator {
	
	private $indicator;
	private $values;
	private $subFields;
	private $elementIdMap;
	private $i;
	
	public static function createInstance($indicator){
		$var = new self();
		$var->reset($indicator);
		return $var;
	}
	
	public function reset($indicator){
		$this->indicator = null;
		unset($this->values);
		unset($this->subFields);
		unset($this->elementIdMap);
		unset($this->i);
		$this->setIndicator($indicator);
	}
	
	protected function getIndicator(){
		return $this->indicator;
	}
	protected function setIndicator($indicator){
		$this->indicator = $indicator;
	}
	
	protected function getIndicatorSubfields(){
		if(!isset($this->subFields)){
			$this->subFields = $this->getIndicator()->getSubFields();
		}
		return $this->subFields;
	}
	protected function isInIndicatorSubfields($subFieldName){
		return in_array($subFieldName, $this->getIndicatorSubfields());
	}
	
	protected function getValues(){
		if(!isset($this->values)) return null;
		return $this->values;
	}
	
	protected function getNextI(){
		if(!isset($this->i)){
			$this->i = -1; //begin at 0
		}
		$this->i++;
		return $this->i;
	}
	//in this implementation the dataTypeName,fieldname, is always the same, the subFieldName could change
	public function setValue($value, $elementId, $dataTypeName, $fieldName, $subFieldName=null){
		if(!$this->isInIndicatorSubfields($subFieldName)) new ServiceException('Invalid subFieldName '.$subFieldName.' for indicator: '.put($this->getIndicator()), ServiceException::INVALID_ARGUMENT);
		if(!isset($this->values)) $this->values = array();
		
		//in the case of the count function, we could get multiple subfields for the same field (addresses, multilanguage)
		//in this case we just want to keep if there is at least one of the subfields which are not field.
		if(	$this->getIndicator()->getFunction()==Indicator::FUNC_COUNT &&
			($dataTypeName == "Addresses" || $dataTypeName == "Texts" || $dataTypeName == "Varchars")
			){
			//the value is added only if not null and if no existing value is set for this elementId
			if($this->elementIdMap[$elementId]==null && $value){
				$i = $this->getNextI();
				$this->values[$i] = $value;
				$this->elementIdMap[$elementId][] = $i;
			} //if a value is already set for this elementId we don't add a new one 
		} else {
			$i = $this->getNextI();
			$this->values[$i] = $value;
			$this->elementIdMap[$elementId][] = $i;
		}
	}
	
	public function isIndicatorFunctionSupported($indicator){
		$f = $this->getSupportedIndicatorFunctions();
		return $f[$indicator->getFunction()];
	}
	public function getSupportedIndicatorFunctions(){
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
	public function evaluateIndicator($principal, $indicator){
		$ind = $indicator;
		$ind->reset(); //reset value
		//calculate the value based on values and function
		$values = $this->getValues();
		
		$result = 0;
		
		switch($ind->getFunction()){
			case Indicator::FUNC_AVG:
				if($values==null) $result = null;
				else {
					if(	$ind->getDataType()->getDataTypeName() == "Dates" ||
						$ind->getDataType()->getDataTypeName() == "TimeRanges"
						){
						//eput($values);
						$counter = 0; //count nb of values
						$result = (double)0.0; //timestamp total
						foreach($values as $value){
							if(is_numeric($value)){
								//$value is a timestamp
								//nothing to do, value is stamp
							} else {
								$d = $m = $y = $h = $i = $s = null;
								if($value!=null && Dates::fromString($value, $d, $m, $y, $h, $i, $s)){
									if(($h || $i || $s) && !($h==0 && $i==0 && $s==0)) $time = "$h:$i:$s";
									else $time = "";
									$value = strtotime("$y/$m/$d $time");
								} else {
									$value = null;
								}
							}
							if($value != null){
								$result += (double)$value;
								$counter++;
							}
						}
						if($counter!=0){
							$result = (int)($result/$counter);
						} else {
							$result = null;
						}
					} else {
						$result = array_sum($values)/(float)count($values);
					}
				}
				break;
			case Indicator::FUNC_COUNT: //count none empty values
				//for multiple subfields datatype as Addresses or multilanguage
				//the setValue don't set more than one none empty subfieldValue to prevent calculating several time
				//the same field
				if($values==null) $result = null;
				else {
					$result = 0;
					foreach($values as $key=>$val){
						if($val) $result++;
					}
					//$result = array_reduce($values, reduce_count, 0);
				}
				break;
			case Indicator::FUNC_COUNT_DISTINCT:
				if($values==null) $result = null;
				else {
					$result = array_fill_keys($values, 1);
					unset($result[""]);
					unset($result[null]);
					$result = count($result);
				}
				break;
			case Indicator::FUNC_MAX:
				if($values==null) $result = null;
				else $result = max($values);
				break;
			case Indicator::FUNC_MIN:
				//delete empty values
				if($values==null) $result = null;
				else {
					$result = array_fill_keys($values, 1);
					unset($result[""]);
					unset($result[null]);
					if($result==null) $result = null;
					else {
						$result = array_keys($result);
						$result = min($result);
					}
				}
				break;
			case Indicator::FUNC_SUM:
				if($values==null) $result = null;
				else $result = array_sum($values);
				break;
//			case Indicator::FUNC_SQRT:
//				break;
//			case Indicator::FUNC_STDDEV_POP:
//				break;
//			case Indicator::FUNC_STDDEV_SAMP:
//				break;
//			case Indicator::FUNC_VAR_POP:
//				break;
//			case Indicator::FUNC_VAR_SAMP:
//				break;
			default:
				new ServiceException('Unknown function: '.$ind->getFunction(), ServiceException::INVALID_ARGUMENT);
		}
		
		$ind->setValue($result);
		//return the calculated value
		return $result;
	}
	
	public function getValue($elementId, $dataTypeName, $fieldName, $subFieldName=null){ throw new ServiceException('unsupported by this implementation', ServiceException::UNSUPPORTED_OPERATION); }
	
	public function applyOperator($operator, $value, $dataTypeName, $fieldName, $subFieldName=null, $field=null){ throw new ServiceException('unsupported by this implementation', ServiceException::UNSUPPORTED_OPERATION); }
}









