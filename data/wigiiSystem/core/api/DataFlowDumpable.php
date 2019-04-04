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
 * An interface which indicates that the object which implements it
 * can be dumped into a data flow. The exact decomposition of the dump is implementation dependent.
 * Created by CWE on 23 novembre 2013
 */
interface DataFlowDumpable
{
	/**
	 * Asks the object to dump itself into an open dataflow given
	 * the data flow service holding the open data flow and the data flow context.
	 * The object should call $dataFlowService->processDataChunk(some data, $dataFlowContext)
	 * as many times as needed to dump itself entirely into the underlying data flow.
	 * The object is free to decompose itself in the way it wants.
	 * @param DataFlowService $dataFlowService a running instance of DataFlowService holding the open data flow
	 * @param DataFlowContext $dataFlowContext a pointer on the open data flow. 
	 */
	public function dumpIntoDataFlow($dataFlowService, $dataFlowContext);	
}