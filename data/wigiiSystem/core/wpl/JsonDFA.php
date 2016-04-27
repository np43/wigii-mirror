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
 * A JSON encoder data flow activity
 * Created by CWE on 5 décembre 2013
 */
class JsonDFA implements DataFlowActivity
{		
	private $firstChunk;
	
	// Object lifecycle
		
	public function reset() {
		$this->freeMemory();	
	}	
	public function freeMemory() {
		/* nothing to do */
	}
		
	// configuration
	
	private $prettyPrint;
	/**
	 * If true then writes the JSON output with identation and new lines, 
	 * else writes it into a compact one line form.
	 * @param Boolean $bool
	 */
	public function setPrettyPrint($bool) {
		$this->prettyPrint = $bool;
	}
	// stream data event handling
	
	public function startOfStream($dataFlowContext) {
		$dataFlowContext->writeResultToOutput("[", $this);		
		$this->firstChunk = true;
	}
	public function processDataChunk($data, $dataFlowContext) {	
		if(!$this->firstChunk) $dataFlowContext->writeResultToOutput(",", $this);					
		else $this->firstChunk = false;
		if($this->prettyPrint) $dataFlowContext->writeResultToOutput("\n\t", $this);		
		$dataFlowContext->writeResultToOutput(json_encode($data), $this);
	}
	public function endOfStream($dataFlowContext) {		
		if($this->prettyPrint) $dataFlowContext->writeResultToOutput("\n", $this);
		$dataFlowContext->writeResultToOutput("]", $this);
	}
	
	
	// single data event handling
	
	public function processWholeData($data, $dataFlowContext) {
		$dataFlowContext->writeResultToOutput(json_encode($data), $this);
	}	
}