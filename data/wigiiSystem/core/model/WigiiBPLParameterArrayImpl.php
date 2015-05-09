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
 * WigiiBPLParameter array implementation
 * Created by CWE on 26.11.2014
 */
class WigiiBPLParameterArrayImpl extends Model implements WigiiBPLParameter {
	private $parameters;
	
	// Object lifecycle

	public static function createInstance() {
		$returnValue = new self();
		$returnValue->reset();
		return $returnValue;
	}
	
	public function reset() {
		$this->freeMemory();
		$this->parameters = array();
	}
	
	public function freeMemory() {
		unset($this->parameters);
	}
	
	// Implementation
	
	public function setValue($key, $value) {
		$this->parameters[$key] = $value;
	}
	
	public function getValue($key) {
		return $this->parameters[$key];
	}
	
	public function addWigiiBPLParameter($param) {
		if(isset($param) && !$param->isEmpty()) {
			if($param instanceof WigiiBPLParameterArrayImpl) {
				$this->parameters = array_merge($this->parameters, $param->getIterator());
			}
			else {
				foreach($param->getIterator() as $k => $v) {
					$this->setValue($k, $v);
				}
			}
		}
	}
	
	public function isEmpty() {
		return empty($this->parameters);
	}
	
	public function getIterator() {
		return $this->parameters;
	}
}