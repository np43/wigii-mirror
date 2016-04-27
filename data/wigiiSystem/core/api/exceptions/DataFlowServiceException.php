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
 * DataFlowService exception
 * Created by CWE on 28 mai 2013
 * error code range from 4300 to 4399
 */
class DataFlowServiceException extends ServiceException
{			
	/**
	 * Indicates a syntax error in the data submitted in the flow
	 */
	const SYNTAX_ERROR = 4300;
	/**
	 * Indicates a format error in the data submitted in the flow
	 */
	const DATA_FORMAT_ERROR = 4301;
	
	private $dataFlowActivitySpecificErrorCode = null;
	
	/**
	 * @param $dataFlowActivitySpecificErrorCode use this argument to pass a DataFlowActivity specific error code (for instance a PHP API error code, like XML_ERROR_SYNTAX, etc)	 
	 */
	public function __construct($message = "", $code = parent::UNKNOWN_ERROR, $dataFlowActivitySpecificErrorCode = null, $previous=null) {
		parent::__construct($message, $code, $previous);
		$this->setDataFlowActivitySpecificErrorCode($dataFlowActivitySpecificErrorCode);
	}
		
	/**
	 * Returns true if a specific DataFlowActivity error code has been set
	 */
	public function hasDataFlowActivitySpecificErrorCode() {
		return isset($this->dataFlowActivitySpecificErrorCode);
	}
	
	/**
	 * Sets a specific DataFlowActivity error code
	 */
	public function setDataFlowActivitySpecificErrorCode($code) {
		$this->dataFlowActivitySpecificErrorCode = $code;
	}
	
	/**
	 * Returns the specific DataFlowActivity error code if set
	 */
	public function getDataFlowActivitiySpecificErrorCode() {
		return $this->dataFlowActivitySpecificErrorCode;
	}
}


