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
 * A data flow selector : a combination of a source and a DataFlowActivitySelectorList
 * Created by CWE on 27 janvier 2014
 */
class DataFlowSelector
{
	private $source;
	private $dfasl;
	
	/**
	 * Creates a instance of a DataFlowSelector
	 * @param DataFlowDumpable|InputDataFlow|ObjectList $src the data flow source
	 * @param DataFlowActivitySelectorList $dfasl the DataFlowActivitySelectorList describing the data flow
	 */
	public static function createInstance($src, $dfasl) {
		$returnValue = new self();
		if(is_null($src)) throw new DataFlowServiceException("src cannot be null", DataFlowServiceException::INVALID_ARGUMENT);
		if(is_null($dfasl)) throw new DataFlowServiceException("dfasl cannot be null", DataFlowServiceException::INVALID_ARGUMENT);
		$returnValue->setSource($src);
		$returnValue->setDataFlowActivitySelectorList($dfasl);
		return $returnValue;
	}

	/**
	 * Returns the DataFlow source
	 * @return DataFlowDumpable|InputDataFlow|ObjectList the data flow source
	 */
	public function getSource() {
		return $this->source;
	}
	
	/**
	 * Sets the data flow source
	 * @param DataFlowDumpable|InputDataFlow|ObjectList $src the data flow source
	 */
	protected function setSource($src) {
		$this->source = $src;
	}	
	
	/**
	 * Returns the DataFlow activity selector list
	 * @return DataFlowActivitySelectorList the DataFlowActivitySelectorList describing the data flow
	 */
	public function getDataFlowActivitySelectorList() {
		return $this->dfasl;
	}
	
	/**
	 * Sets the DataFlowActivitySelector list describing the data flow
	 * @param DataFlowActivitySelectorList $dfasl the DataFlowActivitySelectorList describing the data flow
	 */
	protected function setDataFlowActivitySelectorList($dfasl) {
		$this->dfasl = $dfasl;
	}
}