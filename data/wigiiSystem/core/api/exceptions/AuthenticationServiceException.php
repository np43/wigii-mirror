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
 * An authentication service exception
 * Created by CWE on 1 juin 09
 * error code range from 1100 to 1199
 */
class AuthenticationServiceException extends ServiceException
{
	/**
	 * Not a public user
	 */
	const NOT_PUBLIC_USER = 1100;
	
	/**
	 * occurs when a login is attempted and the MainPrincipal is not a MinimalPrincipal
	 */
	const NOT_MINIMAL_PRINCIPAL = 1101;
	
	/**
	 * occurs when the operation needs a principal not minimal (login needed)
	 */
	const FORBIDDEN_MINIMAL_PRINCIPAL = 1102;
	
	/**
	 * occurs when the operation is not permitted for a public user
	 */
	const FORBIDDEN_PUBLIC_USER = 1105;

	/**
	 * can be used when the principal password is expired
	 * This exception is not thrown in the API. This exception is thrown for certain activities in WigiiExecutor for instance.
	 */
	const EXPIRED_PRINCIPAL = 1103;
	const EXPIRED_PASSWORD = 1104; //the password is expired but the user can modify it
	
	/**
	 * those exception is thrown when the authentication failed
	 */
	const WAIT = 1130; //the user failed to log too often and need to wait.
	const INVALID_USERNAME = 1131; //the user is not existing
	const INVALID_PASSWORD = 1132; //the password is not matching
	const INVALID_PASSWORD_MORE_THAN_2_TIMES = 1134; //the password is not matching and it is already more than 2 tries
	const INVALID_CODE = 1133; //the catpcha code is not matching
	
	const POP3_AUTHENTICATION_SERVER_CONNECTION_PROBLEM = 1121;
	const POP3_AUTHENTICATION_UNKNOWN_USERNAME = 1122;

	public function __construct($message = "", $code = parent::UNKNOWN_ERROR, $previous=null) {
		parent::__construct($message, $code, $previous);
	}
}


