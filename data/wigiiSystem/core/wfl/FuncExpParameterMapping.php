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
 * A mapping between FuncExpParameters and values
 * Created by CWE on 28 novembre 2013
 */
class FuncExpParameterMapping extends Model {	
	private $parameters;
	
	public static function createInstance() {
		return new self();
	}
	
	// Dependency injection
	
	private $fxpHolder;
	/**
	 * Attaches a FuncExpParameterHolder to this func exp parameter
	 * @param FuncExpParameterHolder $fxpHolder
	 */
	public function setFuncExpParameterHolder($fxpHolder) {
		$this->fxpHolder = $fxpHolder;
	}
	
	// FuncExp Parameter mapping
	
	public function setFxpValue($fxpName, $fxpValue) {
		if(!isset($this->parameters)) $this->parameters = array();
		$this->parameters[$fxpName] = $fxpValue;
	}
	public function getFxpValue($fxpName) {
		if(!isset($this->parameters)) return null;
		else return $this->parameters[$fxpName];
	}

	// Parameters instantiation
	
	/**
	 * Instiantiates the parameters defined in the attached FuncExpParameterHolder
	 * with the values found in the mapping.
	 */
	public function instantiateParameters() {
		if(!isset($this->fxpHolder)) throw new FuncExpEvalException("no attached FuncExpParameterHolder instance, please set one.", FuncExpEvalException::CONFIGURATION_ERROR);
		$this->fxpHolder->instantiateParameters($this);
	}
}