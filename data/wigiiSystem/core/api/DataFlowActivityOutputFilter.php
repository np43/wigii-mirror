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
 * A data flow activity output filter
 * Created by CWE on 10 juin 2013
 */
interface DataFlowActivityOutputFilter
{	
	// Object lifecycle
	
	/**
	 * Resets the object to its default state
	 * This method is called before configuring the object for a new stream and
	 * before the start of the stream
	 */
	public function reset();
	
	/**
	 * Frees the memory used by this object
	 * This method is called after the end of a stream and before the object is recycled.
	 */
	public function freeMemory();
	
	
	// Link to data flow context for output management
	
	/**
	 * Injects a dependency on the DataFlow context to let the filter manage output
	 * @param DataFlowActivity $dataFlowActivity a reference to parent DataFlowActivity
	 */
	public function setDataFlowContext($dataFlowContext, $dataFlowActivity);
}