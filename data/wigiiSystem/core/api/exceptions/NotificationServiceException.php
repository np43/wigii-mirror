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
 * NotificationServiceException exception
 * Created by LWR on 6 january 2010
 * error code range from 3900 to 3999
 */
class NotificationServiceException extends ServiceException
{
	const UNKNOWN_EVENT_TYPE = 3901;
	/**
	 * occurs when no email is found to do the notification. This is not a problem, we just do nothing on this case.
	 */
	const NO_EMAIL_FOR_NOTIFICATION_FOUND = 3902;
	/**
	 * occurs when an event happens but not enough changes are done to do a notification
	 */
	const NO_NOTIFICATION_NEEDED = 3903;
	
	public function __construct($message = "", $code = parent::UNKNOWN_ERROR, $previous=null) {
		parent::__construct($message, $code, $previous);
	}
}


