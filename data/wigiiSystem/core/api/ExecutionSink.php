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
 * Execution flow sink core technical service
 * Created by CWE on 3 juin 09
 */
class ExecutionSink
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
	 * Returns an instance of the execution sink attached to the given class
	 */
	public static function getInstance($typeName)
	{
		return TechnicalServiceProvider::getExecutionSink($typeName);
	}

	/**
	 * Checks if the execution sink is enabled
	 */
	public function isEnabled()
	{
		return $this->enabled;
	}
	/**
	 * Enables or not the execution sink
	 */
	public function setEnabled($enabled)
	{
		$this->enabled = $enabled;
	}

	/**
	 * Publishes the start of an operation
	 * principal: optional argument specifying the actual authenticated user performing the operation
	 */
	public function publishStartOperation($operation, $principal=null)
	{
		if($this->isEnabled())
		{
			$this->writeMessage((isset($principal) ? "USER ".$principal->getUsername()." " :"")
								."START OPERATION ".$operation);
		}
	}

	/**
	 * Publishes the end of an operation
	 * principal: optional argument specifying the actual authenticated user performing the operation
	 */
	public function publishEndOperation($operation, $principal=null)
	{
		if($this->isEnabled())
		{
			$this->writeMessage((isset($principal) ? "USER ".$principal->getUsername()." " :"")
								."END OPERATION ".$operation);
		}
	}

	/**
	 * Publishes the end of an operation in case of error
	 * exception: the error
	 * principal: optional argument specifying the actual authenticated user performing the operation
	 */
	public function publishEndOperationOnError($operation, $exception, $principal=null)
	{
		if($this->isEnabled())
		{
			$this->writeMessage((isset($principal) ? "USER ".$principal->getUsername()." " :"")
								."END OPERATION ".$operation
								." WITH ERROR ".$exception);
		}
	}

	/**
	 * Logs a message; for production trace only.
	 */
	public function log($prodLogMessage)
	{
		if($this->isEnabled())
		{
			$this->writeMessage($prodLogMessage);
		}
	}

	/**
	 * Dispatches an ExecFunction Wigii event
	 * @param String $functionName name of the function currently beeing executed
	 * @param Principal $principal current principal executing the function
	 * @param Element $element main element concerned by this function
	 */
	public function throwExecFunctionEvent($functionName, $principal, $element) {
	    TechnicalServiceProvider::getWigiiEventsDispatcher()->execFunction(PWithElementWithFunctionName::createInstance($principal,$element,$functionName));
	}
	
	// Utils

	protected function writeMessage($message)
	{
		echo "INFO ".$this->getAttachedClass()." : ".$message."<br/>";
	}

	protected function getAttachedClass()
	{
		return $this->attachedClass;
	}
}




