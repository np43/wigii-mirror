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
 * Wigii File Admin Service Exception
 * Created by DJC on 24.06.2016
 * error code range from 4900 to 4999
 */
class ElementFileAdminServiceException extends ServiceException
{	
	/**
	 * indicates that a file could not be found at the specified path
	 */
	const FILE_NOT_FOUND = 4900;

	const FILE_PERMISSIONS_ERROR = 4901;

	const UNKNOWN_FILE_TYPE = 4902;

	public function __construct($message = "", $code = parent::UNKNOWN_ERROR, $previous=null) {
		parent::__construct($message, $code, $previous);
	}
}


