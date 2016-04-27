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
 * Converts a flow of CSV lines to Objects
 * Created by CWE on 15.03.2016
 */
class CSV2ObjectDFA implements DataFlowActivity
{	
	private $nRows;
	private $header;
	private $sep;
	
	// Object lifecycle
		
	public function reset() {
		$this->freeMemory();
		$this->nRows=0;
	}	
	public function freeMemory() {
		unset($this->header);
		unset($this->sep);
	}
		
	// Configuration
	
	/**
	 * Sets the CSV separator. Defaults to comma.
	 * @param String $sep
	 */
	public function setSeparator($sep) {
		$this->sep = $sep;
	}
		
	// stream data event handling
	
	public function startOfStream($dataFlowContext) {
		if(!isset($this->sep)) $this->sep=',';
	}
	public function processDataChunk($data, $dataFlowContext) {
		if($data) {
			// parses header
			if($this->nRows==0) {
				$this->header=$this->parseHeaders($this->sep, $data);
			}
			// parses data
			else {
				$data=$this->parseDataRow($this->sep, $data);
				$nCols=count($data);
				// creates object
				$obj=array();
				foreach($this->header as $name=>$col) {
					// reads column value and stores it under right name
					if($col-1<$nCols) $obj[$name]=$data[$col-1];
					else $obj[$name]=null;
				}
				$obj=(object)$obj;
				$dataFlowContext->writeResultToOutput($obj, $this);
			}
			$this->nRows++;
		}
	}
	public function endOfStream($dataFlowContext) {/* nothing to do */}
	
	
	// single data event handling
	
	public function processWholeData($data, $dataFlowContext) {
		$this->startOfStream($dataFlowContext);
		$this->processDataChunk($data, $dataFlowContext);
		$this->endOfStream($dataFlowContext);
	}	
	
	
	// CSV parser implementation
	
	protected function parseHeaders($sep, $string){
		$string = stripslashes($string);
		//remove any newlines
		$string = str_replace(array("\n", "\r"), '', $string);
		$r = preg_split("/\\".$sep."\s*(?=(?:[^\"]*\"[^\"]*\")*(?![^\"]*\"))/", $string);
		$r2 = array();
		$pos = 1; //the header array stores foreach key the position of it. begins to 1 to prevent == null when position is first
		foreach($r as $item){
			$item2 = preg_replace("/^\"/", '', $item);
			if($item2 != $item){
				$item2 = preg_replace("/\"$/", '', $item2);
			}
			//replace double " with one
			$item2 = str_replace('""', '"', $item2);
			// replaces new line codes with new lines
			$item2 = str_replace('\n', "\n", $item2);
			$r2[$item2] = $pos++;
		}
		return $r2;
	}
	protected function parseDataRow($sep, $string){
		//do the split with the separator followed by any spacer
		$string = str_replace(array("\n", "\r"), '', $string);
		$r = preg_split("/\\".$sep."\s*(?=(?:[^\"]*\"[^\"]*\")*(?![^\"]*\"))/", $string);
		$r2 = array();
		foreach($r as $item){
			//take of first "
			$item2 = preg_replace("/^\"/", '', $item);
			//take of last if had the first "
			if($item2 != $item){
				$item2 = preg_replace("/\"$/", '', $item2);
			}
			//replace double " with one
			$item2 = str_replace('""', '"', $item2);
			//trim value
			$item2 = trim($item2);
			// replaces new line codes with new lines
			$item2 = str_replace('\n', "\n", $item2);
			$r2[] = $item2;
		}
		return $r2;
	}
}