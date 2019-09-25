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
 * Wigii Web Socket Server Exception
 * Created by CWE on 17.09.2019
 * error code range from 5100 to 5199
 */
class WigiiWebSocketServerException extends ServiceException
{	
    
    /**
     * Trying to create a new group with a name already in use
     */
    const GROUP_ALREADY_EXISTS = 5101;
    
    /**
     * Connection ID points to an invalid connection. It should be removed or discarded.
     */
    const INVALID_CONNECTION = 5102;
    
    /**
     * Group name does not exist
     */
    const INVALID_GROUP = 5103;
    
	public function __construct($message = "", $code = parent::UNKNOWN_ERROR, $previous=null) {
		parent::__construct($message, $code, $previous);
	}
}


