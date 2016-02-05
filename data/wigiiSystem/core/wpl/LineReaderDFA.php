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
 * A data flow activity which reads a byte stream, encodes it to strings in UTF-8 and cuts the stream by lines.
 * Line feeds are removed.
 * Created by CWE on 29.01.2016
 */
class LineReaderDFA implements DataFlowActivity
{	
	private $partialLine;
	private $firstChunk;
	 
	// Object lifecycle
		
	public function reset() {
		$this->freeMemory();	
		$this->firstChunk=true;
	}	
	public function freeMemory() {
		unset($this->partialLine);
	}
	
	// configuration
		
	private $encoding;
	/**
	 * Specifies the character encoding of the stream. 
	 * If not defined, then tries to detect it.
	 * @param String $encoding a standard character encoding scheme
	 */
	public function setEncoding($encoding) {
		$this->encoding = $encoding;
	}
	
	// stream data event handling
	
	public function startOfStream($dataFlowContext) {
		/* nothing to do */
	}
	public function processDataChunk($data, $dataFlowContext) {
		// detects encoding
		if(!isset($this->encoding)) {
			$this->encoding = mb_detect_encoding($data, array('ASCII', 'UTF-8', 'ISO-8859-1', 'Windows-1252', 'Windows-1251', 'ISO-8859-15', 'ISO-8859-6'), true);
		}
		// splits incoming data by lines
		$lines = explode("\n", $data);
		// pushes each line down in the flow
		$n=count($lines);$i=1;
		foreach($lines as $line) {
			// if first line, then appends previous partial line
			if($i==1) {
				$dataFlowContext->writeResultToOutput($this->processLine((isset($this->partialLine)?$this->partialLine.$line:$line)), $this);
			} 
			// if last line, stores it into partial line
			elseif($i==$n) {
				$this->partialLine=$line;
			}
			// else pushes line in flow
			else {
				$dataFlowContext->writeResultToOutput($this->processLine($line), $this);
			}
			$i++;
		}
	}
	
	/**
	 * Processes a current line of data:
	 * - removes ending carriage return,
	 * - converts to UTF-8
	 * - removes BOM 
	 * @param String $line
	 */
	protected function processLine($line) {
		$returnValue = rtrim($line,"\r");
		if($this->encoding=='UTF-8') {
			if($this->firstChunk) {
				$returnValue=utf8_decode($returnValue);
				$returnValue=utf8_encode($returnValue);
				if(substr($returnValue,0,1)=="?") $returnValue = substr($returnValue,1);
				$this->firstChunk=false;
			}			
		}
		else $returnValue = mb_convert_encoding($returnValue, 'UTF-8', $this->encoding);
		return $returnValue;
	}
	
	public function endOfStream($dataFlowContext) {
		if(isset($this->partialLine)) $dataFlowContext->writeResultToOutput($this->processLine($this->partialLine), $this);
	}
	
	
	// single data event handling
	
	public function processWholeData($data, $dataFlowContext) {
		$this->startOfStream($dataFlowContext);
		$this->processDataChunk($data, $dataFlowContext);
		$this->endOfStream($dataFlowContext);
	}	
}