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
 * An input data flow
 * Created by CWE on 21 novembre 2013
 */
interface InputDataFlow
{	
	/**
	 * Called by the client to indicate to the input data flow that 
	 * the client will start reading data from the stream
	 */
	public function startStream();
	
	/**
	 * Called by the client to check if the stream has some data available
	 * This method can be called several times before calling readDataChunk.
	 * @return Boolean true if some data can be read, else false.
	 */
	public function hasData();
	
	/**
	 * Called by the client to read the next available data chunk
	 * @return mixed a piece of data
	 * @throws DataFlowServiceException if no data is available to be read
	 */
	public function readDataChunk();
	
	/**
	 * Called by the client to indicate that he is finished reading some data
	 */
	public function endStream();
}