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
 * An ExceptionSink which integrates Apache Log4Php (http://logging.apache.org/log4php)
 * Created by CWE on 12 juillet 2013
 */
class ApacheExceptionSink extends ExceptionSink
{
	private $apacheLogger;
	
	// Object lifecycle
	
	public function __construct()
	{		
		$this->apacheLogger = Logger::getRootLogger();
		// sets level to error if disabled
		if(!$this->apacheLogger->isErrorEnabled()) {
			$this->apacheLogger->setLevel(LoggerLevel::getLevelError());
		}
	}
	
	// Implementation
	
	protected function doPublish($exception)
	{
		$this->apacheLogger->error("An exception occured", $exception);
	}
}



