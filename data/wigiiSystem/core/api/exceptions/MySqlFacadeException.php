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
 * MySql database facade exception
 * Created by CWE on 23 juin 09
 * error code range from 2500 to 2599
 */
class MySqlFacadeException extends ServiceException
{
	private $mysqlErrorMsg;
	private $mysqlErrorNo;
	private $connectionFailureDetails;
	const MYSQL_NOERROR = 0;
	const MYSQL_SQLERROR = 2501;
	const MYSQL_NOTUNIQUE = 2502;
	const MYSQL_NORECORDAFFECTED = 2503;
	const MYSQL_CONNECTION_FAILED = 2504;
	
	const MYSQL_ERROR_NO_SUCH_TABLE = 1146;

	public function __construct($message = "", $code = parent::UNKNOWN_ERROR, $previous=null,
								$mySqlErrorMsg="", $mySqlErrorNo=MySqlFacadeException::MYSQL_NOERROR) {
	    if($code==MySqlFacadeException::MYSQL_CONNECTION_FAILED) {
	        $this->connectionFailureDetails = $message;
	        $message = 'Failed to connect to database';
	    }
        parent::__construct($message, $code, $previous);
		$this->mysqlErrorMsg = $mySqlErrorMsg;
		$this->mysqlErrorNo = $mySqlErrorNo;
	}

	public function getMySqlErrorMsg()
	{
		return $this->mysqlErrorMsg;
	}
	public function getMySqlErrorNo()
	{
		return $this->mysqlErrorNo;
	}
	public function getConnectionFailureDetails() {
	    return $this->connectionFailureDetails;
	}
}


