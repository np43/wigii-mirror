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
 * A String separator data flow activity.
 * If no separators are defined, then just lets the flow go through.
 * Created by CWE on 22 février 2014
 */
class StringSepDFA implements DataFlowActivity
{	
	private $_debugLogger;
	private $sep;
	private $evenSep;
	private $oddSep;
	private $groupSep;
	private $header;
	private $footer;
	private $isEven;	
	private $n;
	
	
	// Dependency injection
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("StringSepDFA");
		}
		return $this->_debugLogger;
	}	
	
	// Object lifecycle
		
	public function reset() {
		$this->freeMemory();	
		$this->n = 0;
		$this->isEven = true;		
	}	
	public function freeMemory() {
		unset($this->sep);
		unset($this->evenSep);
		unset($this->oddSep);
		unset($this->groupSep);
		unset($this->header);
		unset($this->footer);
	}

	// configuation

	/**
	 * Defines the header to be inserted once at the beginning of the flow
	 * @param String $header
	 */
	public function setHeader($header) {
		$this->header = $header;
	}
	/**
	 * Defines the footer to be inserted once at the end of the flow
	 * @param String $footer
	 */
	public function setFooter($footer) {
		$this->footer = $footer;
	}
	
	/**
	 * Sets the separator inserted after each chunk.
	 * This configuration has priority 1.
	 * @param String $sep
	 */
	public function setSeparator($sep) {
		$this->sep = $sep;
	}	
	/**	
	 * Sets the separator used on even numbers of chunks (pair in french)
	 * This configuration has priority 2.
	 * @param String $sep the separator used after each even indexed chunk
	 */
	public function setEvenSeparator($sep) {
		$this->evenSep = $sep;
	}
	/**	
	 * Sets the separator used on odd numbers of chunks (impair in french)
	 * This configuration has priority 2.
	 * @param String $sep the separator used after each odd indexed chunk
	 */
	public function setOddSeparator($sep) {
		$this->oddSep = $sep;
	}
	/**
	 * Sets the group separators.
	 * This configuration has priority 3.
	 * @param Array $arr an array of the form (chunkMultiple => separator) defining the separator to apply on each group of strings.
	 * For instance an array of the form (3 =>'\n', 12 => '*****\n') will insert a line feed after each group of three chunks 
	 * and insert *****\n after 12 chunks of data.
	 */
	public function setGroupSeparator($arr) {
		if(!is_array($arr) && !($arr instanceof FuncExpParameter)) throw new DataFlowServiceException('groupSeparator should be an array', DataFlowServiceException::INVALID_ARGUMENT);
		$this->groupSep = $arr;
	}
	
	// stream data event handling
	
	public function startOfStream($dataFlowContext) {
		if(!empty($this->header)) $dataFlowContext->writeResultToOutput($this->header, $this);
	}
	public function processDataChunk($data, $dataFlowContext) {
		if($this->n > 0) {
			if(!empty($this->sep)) $dataFlowContext->writeResultToOutput($this->sep, $this);
			if($this->isEven && !empty($this->evenSep)) $dataFlowContext->writeResultToOutput($this->evenSep, $this);
			if(!$this->isEven && !empty($this->oddSep)) $dataFlowContext->writeResultToOutput($this->oddSep, $this);
			if(!empty($this->groupSep)) {
				$this->debugLogger()->write(json_encode($this->groupSep));
				foreach($this->groupSep as $m => $s) {
					if(($this->n % $m == 0) && !empty($s)) $dataFlowContext->writeResultToOutput($s, $this);
				}
			}
		}
		$dataFlowContext->writeResultToOutput($data, $this);		
		$this->n++; $this->isEven = !$this->isEven;
	}
	public function endOfStream($dataFlowContext) {
		if(!empty($this->footer)) $dataFlowContext->writeResultToOutput($this->footer, $this);
	}
	
	
	// single data event handling
	
	public function processWholeData($data, $dataFlowContext) {
		$this->startOfStream($dataFlowContext);
		$this->processDataChunk($data, $dataFlowContext);
		$this->endOfStream($dataFlowContext);
	}	
}