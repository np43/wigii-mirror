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
 * A set of standard statistics on a flow of numbers:
 * COUNT, SUM, PRODUCT, MEAN, MEDIAN, MAX, MIN, STDDEV, VAR. 
 * Created by CWE on 28 August 2014
 */
class StandardStatisticsDFA implements DataFlowActivity
{
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
			if(count($this->statEnabled) > 1) {
				$dataFlowContext->writeResultToOutput((object)$this->stats, $this);
			}
			else $dataFlowContext->writeResultToOutput($this->stats[reset($this->statEnabled)], $this);
		}
	}
	
	
	// single data event handling
	
	public function processWholeData($data, $dataFlowContext) {
		$this->startOfStream($dataFlowContext);
		$this->processDataChunk($data, $dataFlowContext);
		$this->endOfStream($dataFlowContext);
	}	
}