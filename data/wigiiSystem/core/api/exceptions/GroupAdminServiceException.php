<?php
/**
 *  This file is part of Wigii.
 *
 *  Wigii is free software: you can redistribute it and\/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *  
 *  Wigii is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  
 *  You should have received a copy of the GNU General Public License
 *  along with Wigii.  If not, see <http:\//www.gnu.org/licenses/>.
 *  
 *  @copyright  Copyright (c) 2012 Wigii 		 http://code.google.com/p/wigii/    http://www.wigii.ch
 *  @license    http://www.gnu.org/licenses/     GNU General Public License
 */

/**
 * GroupAdminService exception
 * Created by CWE on 31 mai 09
 * error code range from 1500 to 1599
 */
class GroupAdminServiceException extends ServiceException
{
	const GROUPNAME_ALREADY_USED_IN_NAMESPACE = 1500;
	
	public function __construct($message = "", $code = parent::UNKNOWN_ERROR, $previous=null) {
		parent::__construct($message, $code, $previous);
	}
}



