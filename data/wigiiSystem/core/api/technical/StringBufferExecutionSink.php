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
 * An ExecutionSink which buffers the logs into a String
 * Created by CWE on 26.11.2014
 */
class StringBufferExecutionSink extends ExecutionSink
{
	private $buffer;
	private $sep; // not reseted
	
	// Object lifecycle
	
	public static function createInstance() {
		$returnValue = new self();
		$returnValue->reset();
		return $returnValue;
	}
	
	public function __construct()
	{
		parent::__construct('StringBufferExecutionSink');
		$this->sep = "\n";
	}
	
	public function reset() {
		$this->freeMemory();
		$this->buffer = array();
	}
	public function freeMemory() {
		unset($this->buffer);
	}
	
	// configuration
	
	/**
	 * Sets the log separator
	 */
	public function setLogSeparator($sep) {
		$this->sep = $sep;
	}
	
	// accessors
	
	/**
	 * Returns the string stored into the buffer
	 * @return String returns a string equal to implode(sep, buffer);
	 */
	public function getString() {
		return implode($this->sep, $this->buffer);
	}
	
	// implementation
	
	protected function writeMessage($message)
	{
		if(!empty($message)) $this->buffer[] = $message;
	}
}




