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
 * A data flow activity which echoes the data
 * This DataFlowActivity cannot be called from public space (i.e. caller is located outside of the Wigii instance)
 * Created by CWE on 18.05.2016
 * Modified by Medair (CWE) on 15.12.2016 to protect against Cross Site Scripting
 */
class EchoDFA implements DataFlowActivity
{
	// Object lifecycle
		
	public function reset() {
		$this->freeMemory();	
	}	
	public function freeMemory() {/*nothing to do*/}
	
	// stream data event handling
	
	public function startOfStream($dataFlowContext) {
		$dataFlowContext->assertOriginIsNotPublic();
	}
	public function processDataChunk($data, $dataFlowContext) {
		echo $data;
	}
	public function endOfStream($dataFlowContext) {/* nothing to do */}
	
	
	// single data event handling
	
	public function processWholeData($data, $dataFlowContext) {
		$dataFlowContext->assertOriginIsNotPublic();
		echo $data;
	}	
}