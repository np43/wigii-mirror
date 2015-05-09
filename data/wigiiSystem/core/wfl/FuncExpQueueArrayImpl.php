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
 * A FuncExpQueue based on an Array 
 * Created by CWE on 3 octobre 2013
 */
class FuncExpQueueArrayImpl implements FuncExpQueue, FuncExpIterator
{			
	private $currentValue;
	private $queue;
	
	// Object lifecycle
	
	public function reset() {
		$this->freeMemory();
	}
	
	public function freeMemory() {
		unset($this->queue);
		unset($this->currentValue);
	}
	
	// Dependency injection
	
	private $funcExpVM;
	public function setFuncExpVM($funcExpVM) {
		$this->funcExpVM = $funcExpVM;
	}
	protected function getFuncExpVM() {
		return $this->funcExpVM;
	}
	
	// FuncExp Queue implementation
		
	public function setCurrentFuncExpValue($val) {
		$this->currentValue = $val;
	}
	
	public function appendFuncExp($funcExp) {
		if(is_null($funcExp)) return;
		if(!isset($this->queue)) $this->queue = array();
		$this->queue[] = $funcExp;
	}
	
	public function prependFuncExp($funcExp) {
		if(is_null($funcExp)) return;
		if(!isset($this->queue)) $this->queue = array();
		array_unshift($this->queue, $funcExp);
	}
	
	public function getFuncExpIterator() {return $this;}	
	
	// FuncExp Iterator implementation
		
	public function getCurrentFuncExpValue() {
		return $this->currentValue;
	}
	
	public function evaluateNextFuncExp($stepInto=true) {
		$returnValue = null;
		if(!empty($this->queue)) {
			$f = array_pop($this->queue);
			$returnValue = $this->getFuncExpVM()->evaluateFuncExp($f, $this);
			// if return value is not an iterator or stepInto is false, then creates one on the next instruction in the queue
			if(isset($returnValue) && (!($returnValue instanceof FuncExpIterator) || !stepInto)) {
				$this->setCurrentFuncExpValue($returnValue);
				$returnValue = $this->getFuncExpIterator();
			}
		}
		return $returnValue;
	}	
}