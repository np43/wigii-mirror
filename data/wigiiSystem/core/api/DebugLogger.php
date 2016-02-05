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
 * Debugger core technical service
 * Created by CWE on 3 juin 09
 */
class DebugLogger
{
	private $attachedClass;
	private $enabled;

	/**
	 * Do not call directly this constructor, use static getInstance method
	 */
	public function __construct($typeName)
	{
		$this->attachedClass =$typeName;
		$this->enabled = TRUE;
	}

	/**
	 * Returns an instance of the debugger attached to the given class
	 */
	public static function getInstance($typeName)
	{
		return TechnicalServiceProvider::getDebugLogger($typeName);
	}

	/**
	 * Checks if the debug logger is enabled
	 */
	public function isEnabled()
	{
		return $this->enabled;
	}
	/**
	 * Enables or not the debug logger
	 */
	public function setEnabled($enabled)
	{
		$this->enabled = $enabled;
	}

	/**
	 * Writes a debug message, only if enabled
	 */
	public function write($message)
	{
		if($this->isEnabled())
		{
			$this->writeMessage($message);
		}
	}

	/**
	 * Writes a debug message if condition is true and is enabled
	 */
	public function writeIf($condition, $message)
	{
		if($this->isEnabled())
		{
			if($condition)
			{
				$this->writeMessage($message);
			}
		}
	}

	/**
	 * Logs the start of an operation
	 */
	public function logBeginOperation($operation)
	{
		if($this->isEnabled())
		{
			$this->writeMessage("BEGIN ".$operation);
		}
	}

	/**
	 * Logs the end of an operation
	 */
	public function logEndOperation($operation)
	{
		if($this->isEnabled())
		{
			$this->writeMessage("END ".$operation);
		}
	}

	/**
	 * Does the actual job of writing a debug message
	 */
	protected function writeMessage($message)
	{
		echo "DEBUG ".$this->getAttachedClass()." : ".$message."<br/>";
	}

	protected function getAttachedClass()
	{
		return $this->attachedClass;
	}
}

