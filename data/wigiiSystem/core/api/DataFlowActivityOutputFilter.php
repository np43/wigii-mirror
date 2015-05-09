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
	 * @param $dataFlowActivity a reference to parent DataFlowActivity
	 */
	public function setDataFlowContext($dataFlowContext, $dataFlowActivity);
}