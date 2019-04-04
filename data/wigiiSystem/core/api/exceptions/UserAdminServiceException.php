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
 * UserAdminService exception
 * Created by CWE on 31 mai 09
 * error code range from 2000 to 2099
 */
class UserAdminServiceException extends ServiceException
{
	const USERNAME_ALREADY_USED = 2000;
	const INVALID_USERNAME = 2001;
	const INVALID_PASSWORD = 2002;
	const INVALID_RIGHT_GROUPCREATOR = 2003;
	const INVALID_RIGHT_READALLGROUPSINNAMESPACE = 2004;
	const INVALID_RIGHT_ROOTGROUPCREATOR = 2005;
	const INVALID_RIGHT_MODULEACCESS = 2006;
	const INVALID_RIGHT_ADMINCREATOR = 2007;
	const INVALID_RIGHT_MODULECREATOR = 2008;
	const INVALID_RIGHT_NAMESPACECREATOR = 2009;
	const INVALID_RIGHT_USERCREATOR = 2010;
	const INVALID_RIGHT_READALLUSERSINNAMESPACE = 2011;


	public function __construct($message = "", $code = parent::UNKNOWN_ERROR, $previous=null) {
		parent::__construct($message, $code, $previous);
	}
}


