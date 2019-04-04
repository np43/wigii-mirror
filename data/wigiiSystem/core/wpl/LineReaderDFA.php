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
 * A data flow activity which reads a byte stream, encodes it to strings in UTF-8 and cuts the stream by lines.
 * Line feeds are removed.
 * Created by CWE on 29.01.2016
 */
class LineReaderDFA implements DataFlowActivity
{	
	private $partialLine;
	private $firstChunk;
	private $stringLiteralBuffer;
	
	// Object lifecycle
		
	public function reset() {
		$this->freeMemory();	
		$this->firstChunk=true;
		$this->cutMultilineStringLiterals = false;
		$this->escapeNewLines = false;
	}	
	public function freeMemory() {
		unset($this->partialLine);
		unset($this->encoding);
		unset($this->stringLiteralBuffer);		
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
	
	private $cutMultilineStringLiterals;
	/**
	 * If true, then Double Quoted String literal which are multiline will be cut by lines. 
	 * Else String literal is kept complete and inner line feeds are kept.
	 * By default, multiline string literals are not cut.
	 */
	public function setCutMultilineStringLiteral($bool) {
	    $this->cutMultilineStringLiterals = $bool;
	}
	
	private $escapeNewLines;
	/**
	 * If true, then multine line string literals will have their line feed character encoded with a \n (this is helpful when parsing CSV files).  
	 * Else inner line feed are kept. 
	 * This option is ignored if cutMultilineStringLiteral is true.
	 */
	public function setEscapeNewLines($bool) {
	    $this->escapeNewLines = $bool;
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
				$this->outputLine($this->processLine((isset($this->partialLine)?$this->partialLine.$line:$line)), $dataFlowContext);
			} 
			// if last line, stores it into partial line
			elseif($i==$n) {
				$this->partialLine=$line;
			}
			// else pushes line in flow
			else {
				$this->outputLine($this->processLine($line), $dataFlowContext);
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
	/**
	 * Outputs the line of data into the underlying DataFlow
	 * Re-assembles String literals if they where cut, except if cutMultilineStringLiterals is true.
	 * @param String $line
	 * @param DataFlowContext $dataFlowContext
	 */
	protected function outputLine($line, $dataFlowContext) {
	    if($this->cutMultilineStringLiterals) $dataFlowContext->writeResultToOutput($line, $this);
	    else {
	        // counts the number of double quotes in the line
	        // if odd, then a string literal is cut.
	        if(substr_count($line, '"') % 2 > 0) {
	            // if a partial string literal is already buffered, then closes it and dumps
	            if($this->stringLiteralBuffer) {
	                $dataFlowContext->writeResultToOutput($this->stringLiteralBuffer.($this->escapeNewLines?'\n':"\n").$line, $this);
	                unset($this->stringLiteralBuffer);
	            }
	            // else buffers the line
	            else $this->stringLiteralBuffer = $line;
	        }
	        // else if even
	        else {
	            // if a partial string literal is already buffered, then adds a new line and buffers
	            if($this->stringLiteralBuffer) {
	                $this->stringLiteralBuffer .= ($this->escapeNewLines?'\n':"\n").$line;
	            }
	            // else outputs the line
	            else $dataFlowContext->writeResultToOutput($line, $this);
	        }	        
	    }
	}
	
	public function endOfStream($dataFlowContext) {
		if(isset($this->partialLine)) $this->outputLine($this->processLine($this->partialLine), $dataFlowContext);
		if(isset($this->stringLiteralBuffer)) $dataFlowContext->writeResultToOutput($this->stringLiteralBuffer, $this);
	}
	
	
	// single data event handling
	
	public function processWholeData($data, $dataFlowContext) {
		$this->startOfStream($dataFlowContext);
		$this->processDataChunk($data, $dataFlowContext);
		$this->endOfStream($dataFlowContext);
	}	
}