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
 * A set of standard statistics on a flow of numbers:
 * COUNT, SUM, PRODUCT, MEAN, MEDIAN, MAX, MIN, STDDEV, VAR. 
 * Created by CWE on 28 August 2014
 * Modified by CWE on 22 April 2016 to support flows of stdClasses or Elements
 */
class StandardStatisticsDFA implements DataFlowActivity
{
	private $_debugLogger;
	private $statEnabled;
	private $buffer;
	private $stats; 
	
	
	// Object lifecycle
		
	public function reset() {
		$this->freeMemory();	
		$this->statEnabled = array();
	}	
	public function freeMemory() {
		unset($this->statEnabled);
		unset($this->buffer);
		unset($this->stats);
		unset($this->fieldName);
		unset($this->resultHolder);
	}
	
	// dependency injection
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("StandardStatisticsDFA");
		}
		return $this->_debugLogger;
	}
	
	// configuration
	
	/**
	 * Enables the 'count' statistic.
	 */
	public function enableCount($bool) {
		$this->statEnabled['count'] = true;
	}
	
	/**
	 * Enables the 'sum' statistic.
	 */
	public function enableSum($bool) {
		$this->statEnabled['sum'] = true;
	}
	
	/**
	 * Enables the 'product' statistic.
	 */
	public function enableProduct($bool) {
		$this->statEnabled['product'] = true;
	}
	
	/**
	 * Enables the 'mean' statistic.
	 */
	public function enableMean($bool) {
		$this->statEnabled['mean'] = true;
	}
	
	/**
	 * Enables the 'median' statistic.
	 */
	public function enableMedian($bool) {
		$this->statEnabled['median'] = true;
	}
	
	/**
	 * Enables the 'max' statistic.
	 */
	public function enableMax($bool) {
		$this->statEnabled['max'] = true;
	}
	
	/**
	 * Enables the 'min' statistic.
	 */
	public function enableMin($bool) {
		$this->statEnabled['min'] = true;
	}
	
	/**
	 * Enables the 'stddev' statistic.
	 */
	public function enableStdDev($bool) {
		$this->statEnabled['stddev'] = true;
	}
	
	/**
	 * Enables the 'var' statistic.
	 */
	public function enableVar($bool) {
		$this->statEnabled['var'] = true;
	}
	
	private $fieldName;
	/**
	 * Sets the name of the field on which to do the statistics in case of a flow of stdClasses or Elements.
	 * @param String $fieldName should be a valid Field in the stdClass or Element
	 */
	public function setFieldName($fieldName) {
		$this->fieldName = $fieldName;
	}
	
	private $resultHolder;
	/**
	 * Sets a box to get the statistics back to caller at the end instead of pushing them down the flow.
	 * @param ValueObject $box
	 */
	public function setResultHolder($box) {
		$this->resultHolder = $box;
	}
	
	// stream data event handling
	
	public function startOfStream($dataFlowContext) {
		if(!empty($this->statEnabled)) $this->stats = array();
		if($this->statEnabled['median'] || 
			$this->statEnabled['stddev'] || 
			$this->statEnabled['var']) {
			$this->buffer = array();		
		}
	}
	public function processDataChunk($data, $dataFlowContext) {
		// if result holder is set, then pushes down data in the flow, else holds it here
		if(isset($this->resultHolder)) $dataFlowContext->writeResultToOutput($data, $this);
		
		// extract the number from the flow
		if(isset($this->fieldName) && is_object($data)) {
			// flow of Elements
			if($data instanceof Record) $data = $data->getFieldValue($this->fieldName);
			elseif($data instanceof ElementP) $data = $data->getDbEntity()->getFieldValue($this->fieldName);
			else $data = $data->{$this->fieldName};		
		}
		// computes the statistics
		if($this->statEnabled['count'] ||
			$this->statEnabled['mean'] ||
			$this->statEnabled['var'] ||
			$this->statEnabled['stddev'] ||
			$this->statEnabled['median']) {
			if(!isset($this->stats['count'])) $this->stats['count'] = 1;
			else $this->stats['count'] = $this->stats['count'] + 1;
		}
		if($this->statEnabled['sum'] ||
			$this->statEnabled['mean'] ||
			$this->statEnabled['var'] ||
			$this->statEnabled['stddev']) {
			if(!isset($this->stats['sum'])) $this->stats['sum'] = $data;
			else $this->stats['sum'] = $this->stats['sum'] + $data;
		}
		if($this->statEnabled['product']) {
			if(!isset($this->stats['product'])) $this->stats['product'] = $data;
			else $this->stats['product'] = $this->stats['product'] * $data;
		}
		if($this->statEnabled['max']) {
			if(!isset($this->stats['max'])) $this->stats['max'] = $data;
			elseif($data > $this->stats['max']) $this->stats['max'] = $data;
		}
		if($this->statEnabled['min']) {
			if(!isset($this->stats['min'])) $this->stats['min'] = $data;
			elseif($data < $this->stats['min']) $this->stats['min'] = $data;
		}
		if($this->statEnabled['median'] ||
			$this->statEnabled['stddev'] ||
			$this->statEnabled['var']) {
			$this->buffer[] = $data;		
		}
	}
	public function endOfStream($dataFlowContext) {
		if(isset($this->stats['count'])) {
			if($this->statEnabled['mean'] ||
				$this->statEnabled['var'] ||
				$this->statEnabled['stddev']) {
				$this->stats['mean'] = (float)$this->stats['sum']/(float)$this->stats['count'];
			}
			
			if($this->statEnabled['median']) {
				$this->buffer = sort($this->buffer);
				$m = $this->stats['count'];
				if($m % 2 == 0){
					$this->stats['median'] = (float)($this->buffer[$m/2-1]+$this->buffer[$m/2])/2.0;
				}
				else {
					$this->stats['median'] = (float)$this->buffer[($m-1)/2];
				}
			}
			
			if($this->statEnabled['var'] ||
				$this->statEnabled['stddev']) {
				$m = $this->stats['mean']; $v = 0;	
				foreach($this->buffer as $d) {
					$v += ($d - $m)*($d - $m); 
				}
				$v = (float)$v/(float)$this->stats['count'];
				
				if($this->statEnabled['stddev']) $this->stats['stddev'] = sqrt($v);
				if($this->statEnabled['var']) $this->stats['var'] = $v;
			}
		}
		if(!empty($this->stats)) {		
			if(count($this->statEnabled) > 1) $result = (object)$this->stats;
			else $result = $this->stats[key($this->statEnabled)];
			// if result holder then updates it with result			
			if(isset($this->resultHolder)) {
				$this->resultHolder->setValue($result);
			}
			// else pushes result to flow				
			else $dataFlowContext->writeResultToOutput($result, $this);
		}
	}
	
	
	// single data event handling
	
	public function processWholeData($data, $dataFlowContext) {
		$this->startOfStream($dataFlowContext);
		$this->processDataChunk($data, $dataFlowContext);
		$this->endOfStream($dataFlowContext);
	}	
}