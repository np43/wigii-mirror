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
 * Root service exception class
 * Created by CWE on 31 mai 09
 * error code range from 1000 to 1099 + http error codes
 */
class ServiceException extends Exception
{
	private $previousException;

	/**
	 * unauthorized user, http equivalent
	 */
	const UNAUTHORIZED = 401;
	/**
	 * access forbidden, http equivalent
	 */
	const FORBIDDEN = 403;
	/**
	 * default error
	 */
	const UNKNOWN_ERROR = 1000;
	/**
	 * development error, indicates that a method is still not implemented but will be in a short term
	 */
	const NOT_IMPLEMENTED = 1001;
	/**
	 * indicates that nested (or previous) exception exists.
	 */
	const WRAPPING = 1002;
	/**
	 * indicates that this operation is not supported by this implementation.
	 */
	const UNSUPPORTED_OPERATION = 1003;
	/**
	 * indicates that a method argument is invalid
	 */
	const INVALID_ARGUMENT = 1004;
	/**
	 * unexpected error, that should never happen.
	 */
	const UNEXPECTED_ERROR = 1005;
	/**
	 * database is dirty with corrupted or invalid data
	 */
	const DATA_INTEGRITY_ERROR = 1006;
	/**
	 * deprecated method
	 */
	const DEPRECATED = 1007;
	/**
	 * invalid state of object
	 */
	const INVALID_STATE = 1008;
	/**
	 * Indicates that there is a problem in the configuration of the system
	 */
	const CONFIGURATION_ERROR = 1009;
	/**
	 * Indicates that the current operation has been explicitely canceled by the service
	 * The service can provide a retryAfterCancel method.
	 */
	const OPERATION_CANCELED = 1010;

	public function __construct($message = "", $code = self::UNKNOWN_ERROR, $previous=null) {
		parent::__construct($message, $code);
		$this->previousException = $previous;
	}

	public function getPreviousException()
	{
		return $this->previousException;
	}
	
	/**
	 * Returns the wigii root exception of the wrapping stack
	 */
	public function getWigiiRootException() {
		if($this->getCode() == ServiceException::WRAPPING && isset($this->previousException)) {
			if($this->previousException instanceof ServiceException) return $this->previousException->getWigiiRootException(); 
			else return $this->previousException;
		}
		else return $this;
	}
	
	public static function throwNotImplemented() {
		throw new ServiceException("not implemented", ServiceException::NOT_IMPLEMENTED);
	}
}


