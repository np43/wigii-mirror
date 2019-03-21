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
 *  @copyright  Copyright (c) 2019  Wigii.org
 *  @author     <http://www.wigii.org/system>      Wigii.org 
 *  @link       <http://www.wigii-system.net>      <https://github.com/wigii/wigii>   Source Code
 *  @license    <http://www.gnu.org/licenses/>     GNU General Public License
 */

/**
 * Wigii Medidata interface exception
 * Created by CWE on 20.09.2018
 * error code range from 10200 to 10299
 */
class WigiiMedidataException extends ServiceException
{			
	const XML_VALIDATION_ERROR = 10200;
	/**
	 * Mediport Communicator is not installed on client machine
	 */
	const MPC_NOT_INSTALLED = 10201;
	/**
	 * WinSCP is not installed on client machine
	 */
	const WINSCP_NOT_INSTALLED = 10202;
	/**
	 * WinSCP communication error. See WinSCP log file for more details.
	 */
	const WINSCP_ERROR = 10203;
	
	public function __construct($message = "", $code = parent::UNKNOWN_ERROR, $previous=null) {
		parent::__construct($message, $code, $previous);
	}
}


