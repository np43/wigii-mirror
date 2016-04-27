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
 * A DebugLogger which dispatches events to several additional DebugLoggers
 * Created by CWE on 11 juillet 2013
 */
class MultipleDebugLogger extends DebugLogger
{	
	/**
	 * The default system debug logger
	 */
	protected $systemDebugLogger;
	/**
	 * An array of DebugLogger instances (does not contain the system DebugLogger)
	 * key=DebugLogger (sub) class name
	 * value=DebugLogger instance
	 */
	protected $additionalDebugLoggers;
	 
	// object lifecycle
	
	/**
	 * Creates a new instance of this MultipleDebugLogger
	 * @param $systemDebugLogger the default DebugLogger instance to be used. 
	 * This argument must point to a valid DebugLogger running instance. 
	 */
	public function __construct($systemDebugLogger)
	{
		parent::__construct($systemDebugLogger->getAttachedClass());
		$this->systemDebugLogger = $systemDebugLogger;
	}

	// configuration

	/**
	 * Checks if the debug logger is enabled
	 * Returns true if system DebugLogger is enabled or 
	 * if some additional debug loggers have been defined
	 */
	public function isEnabled()
	{
		return $this->systemDebugLogger->isEnabled() || !empty($this->additionalDebugLoggers);
	}
	/**
	 * Enables or not attached system debug logger
	 */
	public function setEnabled($enabled)
	{
		$this->systemDebugLogger->setEnabled($enabled);
	}

	/**
	 * Adds a list of DebugLogger classes to be used.
	 * If some classes are already setup as additional DebugLoggers, keeps the original instances.
	 * @param $additionalDebugLoggerClasses an array of class names or one single class name string
	 * or an array of (key=class name, value=ObjectConfigurator). In that case, the ObjectConfigurator will be applied 
	 * on each created instance.
	 */
	public function useAdditionalDebugLoggers($additionalDebugLoggerClasses) {
		if(empty($additionalDebugLoggerClasses)) return;
		if(!isset($this->additionalDebugLoggers)) $this->additionalDebugLoggers = array();
		// if multiple classes
		if(is_array($additionalDebugLoggerClasses)) {
			foreach($additionalDebugLoggerClasses as $key => $value) {
				if($value instanceof ObjectConfigurator) {
					$className = $key;
					$configurator = $value;
				}
				else {
					$className = $value;
					$configurator = null;
				}
				// creates instance only if not already created
				$o = $this->additionalDebugLoggers[$className];
				if(!isset($o)) $this->additionalDebugLoggers[$className] = $this->createDebugLoggerInstance($className, $configurator);
			}
		}
		// else one single string
		else {
			$o = $this->additionalDebugLoggers[$additionalDebugLoggerClasses];
			if(!isset($o)) $this->additionalDebugLoggers[$additionalDebugLoggerClasses] = $this->createDebugLoggerInstance($additionalDebugLoggerClasses);			
		}
	}
	/**
	 * Creates an instance of a DebugLogger given its className
	 * By default, looks for a class on the classpath
	 * @param $configurator optional ObjectConfigurator instance to configure created instance
	 */
	protected function createDebugLoggerInstance($className, $configurator = null) {
		wigii_autoload($className);
		$returnValue = new $className($this->systemDebugLogger->getAttachedClass());
		if(isset($configurator)) {
			$configurator->configure($returnValue);
		}	
		return $returnValue;	
	}
	
	/**
	 * Removes a list of additional DebugLogger classes.
	 * If some classes are not setup as additional DebugLoggers, there are ignored.
	 * The system DebugLogger instance cannot be removed (ignored if defined in the list)
	 * @param $debugLoggerClasses an array of class names or one single class name string
	 */
	public function removeAdditionalDebugLoggers($debugLoggerClasses) {
		if(empty($debugLoggerClasses)) return;
		if(!isset($this->additionalDebugLoggers)) return;		
		if(!is_array($debugLoggerClasses)) $debugLoggerClasses = array($debugLoggerClasses);
		$this->additionalDebugLoggers = array_diff_key($this->additionalDebugLoggers, array_combine($debugLoggerClasses, $debugLoggerClasses));
	}
	
	// implementation
	
	/**
	 * Writes a debug message, only if enabled
	 */
	public function write($message)
	{
		// pushes to system debug logger
		$this->systemDebugLogger->write($message);
		// pushes to additional debug loggers
		if(isset($this->additionalDebugLoggers)) {
			foreach($this->additionalDebugLoggers as $debugLogger) {
				$debugLogger->write($message);
			}
		}
	}

	/**
	 * Writes a debug message if condition is true and is enabled
	 */
	public function writeIf($condition, $message)
	{
		// pushes to system debug logger
		$this->systemDebugLogger->writeIf($condition, $message);
		// pushes to additional debug loggers
		if(isset($this->additionalDebugLoggers)) {
			foreach($this->additionalDebugLoggers as $debugLogger) {
				$debugLogger->writeIf($condition, $message);
			}
		}
	}

	/**
	 * Logs the start of an operation
	 */
	public function logBeginOperation($operation)
	{
		// pushes to system debug logger
		$this->systemDebugLogger->logBeginOperation($operation);
		// pushes to additional debug loggers
		if(isset($this->additionalDebugLoggers)) {
			foreach($this->additionalDebugLoggers as $debugLogger) {
				$debugLogger->logBeginOperation($operation);
			}
		}
	}

	/**
	 * Logs the end of an operation
	 */
	public function logEndOperation($operation)
	{
		// pushes to system debug logger
		$this->systemDebugLogger->logEndOperation($operation);
		// pushes to additional debug loggers
		if(isset($this->additionalDebugLoggers)) {
			foreach($this->additionalDebugLoggers as $debugLogger) {
				$debugLogger->logEndOperation($operation);
			}
		}
	}
}

