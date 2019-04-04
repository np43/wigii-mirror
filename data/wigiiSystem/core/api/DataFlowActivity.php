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
 * A data flow activity
 * Created by CWE on 28 mai 2013
 */
interface DataFlowActivity
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
	
	
	// stream data event handling
	
	/**
	 * The DataFlowService triggers this event to inform the current DataFlowActivity 
	 * that a new stream of data begins.
	 * The DataFlowActivity should prepare itself to receive chunk of data
	 * Stream of data are not mixed up, so it is safe to have local instance variables.
	 * The order of methods calls are startOfStream, processDataChunk (several times), endOfStream
	 * Then the object is recycled and the events can be triggered again.
	 * @param DataFlowContext $dataFlowContext the current data flow executing context.
	 * @throws DataFlowServiceException in case of error
	 */
	public function startOfStream($dataFlowContext);
	
	/**
	 * The DataFlowService passes a chunk of data to be processed in the current execution context	 
	 * @param mixed $data to be processed
	 * @param DataFlowContext $dataFlowContext the current data flow executing context.
	 * @throws DataFlowServiceException in case of error,
	 * in particular INVALID_ARGUMENT code if data is invalid 
	 * or not compatible with this dataflow activity.
	 */
	public function processDataChunk($data, $dataFlowContext);
	
	/**
	 * The DataFlowService triggers this event to inform the current DataFlowActivity
	 * that the stream has no more data to be processed.
	 * The data flow activity can do some closing activities.
	 * It is still valid to output some data for the next steps.
	 * @param DataFlowContext $dataFlowContext the current data flow executing context
	 * @throws DataFlowServiceException in case of error
	 */
	public function endOfStream($dataFlowContext);
	
	
	// single data event handling
	
	/**
	 * The DataFlowService triggers this event to ask to process at once the whole bunch of data.
	 * This method cannot be called if a stream is currently beeing executed.
	 * Local instance variable are safe to use.
	 * @param mixed $data the data to be processed
	 * @param DataFlowContext $dataFlowContext the current data flow executing context
	 * @throws DataFlowServiceException in case of error
	 */
	public function processWholeData($data, $dataFlowContext);
}