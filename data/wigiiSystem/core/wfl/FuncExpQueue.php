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
 * A queue of FuncExp to be evaluated in a sequence
 * And a box for the last evaluated value. 
 * Created by CWE on 3 octobre 2013
 */
interface FuncExpQueue
{	
	// Object lifecycle
	
	/**
	 * Resets the object to its default state	 
	 */
	public function reset();
	
	/**
	 * Frees the memory used by this object
	 */
	public function freeMemory();
		
	// Queue methods
		
	/**
	 * Sets the current func exp value. Any previous value is replaced.
	 */
	public function setCurrentFuncExpValue($val);	
	
	/**
	 * Appends a FuncExp instance to the end of the queue to be further evaluated.
	 * @param FuncExp $funcExp a FuncExp instance
	 */
	public function appendFuncExp($funcExp);
	
	/**
	 * Prepends a FuncExp instance at the beginning of the queue to be first evaluated.
	 * @param FuncExp $funcExp a FuncExp instance
	 */
	public function prependFuncExp($funcExp);
	
	/**
	 * Returns an instance of a FuncExpIterator that can be used to consume the queue
	 * WARNING: the FuncExpIterator is not compatible with the foreach control structure,
	 * it is a forward only consumer and not a repeatable loop.
	 */
	public function getFuncExpIterator();
}