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
 * A Wigii Business Process Library parameter
 * Created by CWE on 19.11.2014
 */
interface WigiiBPLParameter {
	
	/**
	 * Sets a value for the given parameter
	 * @param String $key parameter name
	 * @param Any $value the parameter value
	 */
	public function setValue($key, $value);
	
	/**
	 * Returns the value of the given parameter
	 * @param String $key the parameter name
	 * @return Any the parameter value
	 */
	public function getValue($key);
	
	/**
	 * Adds the given parameters pairs (key/value) to the existing one.
	 * @param WigiiBPLParameter $param the parameters to add. Replaces any existing key/value with the one given by argument.
	 */
	public function addWigiiBPLParameter($param);
	
	/**
	 * Returns true if this WigiiBPLParameter is empty (no key/value pairs)
	 * @return boolean
	 */
	public function isEmpty();
	
	/**
	 * Returns an iterator on the key/value pairs, compatible with the foreach structure
	 */
	public function getIterator();
}