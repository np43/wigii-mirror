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
 * Evaluate an indicator on a list of element
 * is designed only for elementAttribute. use WigiiBagIndicatorEvaluatorImpl for Field with DataType
 */
class ElementListIndicatorEvaluatorImpl implements ElementList, ElementPList, IndicatorEvaluator
{
	private $indicator;
	private $values;
	private $i;
	
	public static function createInstance($indicator){
		$r = new self();
		$r->reset($indicator);
		return $r;
	}
	
	public function reset($indicator){
		$this->indicator = $indicator;
		$this->values = null;
		$this->i = 0;
	}
	
	public function isEmpty(){
		return $this->i == 0;
	}
	public function count(){
		return $this->i;
	}
	
	public function addElement($element){
		$this->values[$this->i++] = $element->getAttribute($this->indicator->getFieldSelector());
	}
	public function addElementP($elementP){
		$this->addElement($elementP->getDbEntity());
	}
	
	public function getListIterator(){ throw new ServiceException('', ServiceException::UNSUPPORTED_OPERATION); }
	
	public function createFieldList(){
		return FieldListArrayImpl::createInstance();
	}
	public function createWigiiBag(){
		return WigiiBagBaseImpl::createInstance();
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
		$values = $this->values;
		
		$result = 0;
		
		switch($ind->getFunction()){
			case Indicator::FUNC_AVG:
				if($values==null) $result = null;
				else {
					if(	$ind->getFieldSelector()->getSubFieldName() == "sys_creationDate" ||
						$ind->getFieldSelector()->getSubFieldName() == "sys_date"
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
				else $result = array_reduce($values, reduce_count, 0);
				break;
			case Indicator::FUNC_COUNT_DISTINCT:
				$result = array_fill_keys($values, 1);
				unset($result[""]);
				unset($result[null]);
				$result = count($result);
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
}