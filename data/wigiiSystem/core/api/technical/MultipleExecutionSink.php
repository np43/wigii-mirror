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
 * An ExecutionSink which dispatches events to several additional ExecutionSinks
 * Created by CWE on 11 juillet 2013
 */
class MultipleExecutionSink extends ExecutionSink
{
	/**
	 * The default system execution sink
	 */
	protected $systemExecutionSink;
	/**
	 * An array of ExecutionSink instances (does not contain the system ExecutionSink)
	 * key=ExecutionSink (sub) class name
	 * value=ExecutionSink instance
	 */
	protected $additionalExecutionSinks;
	 
	// object lifecycle
	
	/**
	 * Creates a new instance of this MultipleExecutionSink
	 * @param ExecutionSink $systemExecutionSink the default ExecutionSink instance to be used. 
	 * This argument must point to a valid ExecutionSink running instance. 
	 */
	public function __construct($systemExecutionSink)
	{
		parent::__construct($systemExecutionSink->getAttachedClass());
		$this->systemExecutionSink = $systemExecutionSink;
	}

	// configuration

	/**
	 * Checks if the execution sink is enabled
	 * Returns true if system ExecutionSink is enabled or 
	 * if some additional execution sinks have been defined
	 */
	public function isEnabled()
	{
		return $this->systemExecutionSink->isEnabled() || !empty($this->additionalExecutionSinks);
	}
	/**
	 * Enables or not attached system execution sink
	 */
	public function setEnabled($enabled)
	{
		$this->systemExecutionSink->setEnabled($enabled);
	}

	/**
	 * Adds a list of ExecutionSink classes to be used.
	 * If some classes are already setup as additional ExecutionSinks, keeps the original instances.
	 * @param array|string $additionalExecutionSinkClasses an array of class names or one single class name string
	 * or an array of (key=class name, value=ObjectConfigurator). In that case, the ObjectConfigurator will be applied 
	 * on each created instance.
	 */
	public function useAdditionalExecutionSinks($additionalExecutionSinkClasses) {
		if(empty($additionalExecutionSinkClasses)) return;
		if(!isset($this->additionalExecutionSinks)) $this->additionalExecutionSinks = array();
		// if multiple classes
		if(is_array($additionalExecutionSinkClasses)) {
			foreach($additionalExecutionSinkClasses as $key => $value) {
				if($value instanceof ObjectConfigurator) {
					$className = $key;
					$configurator = $value;
				}
				else {
					$className = $value;
					$configurator = null;
				}
				// creates instance only if not already created
				$o = $this->additionalExecutionSinks[$className];
				if(!isset($o)) $this->additionalExecutionSinks[$className] = $this->createExecutionSinkInstance($className, $configurator);
			}
		}
		// else one single string
		else {
			$o = $this->additionalExecutionSinks[$additionalExecutionSinkClasses];
			if(!isset($o)) $this->additionalExecutionSinks[$additionalExecutionSinkClasses] = $this->createExecutionSinkInstance($additionalExecutionSinkClasses);			
		}
	}
	/**
	 * Creates an instance of a ExecutionSink given its className
	 * By default, looks for a class on the classpath
	 * @param ObjectConfigurator $configurator optional ObjectConfigurator instance to configure created instance
	 */
	protected function createExecutionSinkInstance($className, $configurator = null) {		
		wigii_autoload($className);
		$returnValue = new $className($this->systemExecutionSink->getAttachedClass());
		if(isset($configurator)) {
			$configurator->configure($returnValue);
		}	
		return $returnValue;		
	}
	
	/**
	 * Removes a list of additional ExecutionSink classes.
	 * If some classes are not setup as additional ExecutionSinks, there are ignored.
	 * The system ExecutionSink instance cannot be removed (ignored if defined in the list)
	 * @param array|string $executionSinkClasses an array of class names or one single class name string
	 */
	public function removeAdditionalExecutionSinks($executionSinkClasses) {
		if(empty($executionSinkClasses)) return;
		if(!isset($this->additionalExecutionSinks)) return;		
		if(!is_array($executionSinkClasses)) $executionSinkClasses = array($executionSinkClasses);
		$this->additionalExecutionSinks = array_diff_key($this->additionalExecutionSinks, array_combine($executionSinkClasses, $executionSinkClasses));
	}
	
	// implementation

	/**
	 * Publishes the start of an operation
	 * principal: optional argument specifying the actual authenticated user performing the operation
	 */
	public function publishStartOperation($operation, $principal=null)
	{
		// pushes to system execution sink
		$this->systemExecutionSink->publishStartOperation($operation, $principal);
		// pushes to additional execution sinks
		if(isset($this->additionalExecutionSinks)) {
			foreach($this->additionalExecutionSinks as $executionSink) {
				$executionSink->publishStartOperation($operation, $principal);
			}
		}
	}

	/**
	 * Publishes the end of an operation
	 * principal: optional argument specifying the actual authenticated user performing the operation
	 */
	public function publishEndOperation($operation, $principal=null)
	{
		// pushes to system execution sink
		$this->systemExecutionSink->publishEndOperation($operation, $principal);
		// pushes to additional execution sinks
		if(isset($this->additionalExecutionSinks)) {
			foreach($this->additionalExecutionSinks as $executionSink) {
				$executionSink->publishEndOperation($operation, $principal);
			}
		}
	}

	/**
	 * Publishes the end of an operation in case of error
	 * exception: the error
	 * principal: optional argument specifying the actual authenticated user performing the operation
	 */
	public function publishEndOperationOnError($operation, $exception, $principal=null)
	{
		// pushes to system execution sink
		$this->systemExecutionSink->publishEndOperationOnError($operation, $exception, $principal);
		// pushes to additional execution sinks
		if(isset($this->additionalExecutionSinks)) {
			foreach($this->additionalExecutionSinks as $executionSink) {
				$executionSink->publishEndOperationOnError($operation, $exception, $principal);
			}
		}
	}

	/**
	 * Logs a message; for production trace only.
	 */
	public function log($prodLogMessage)
	{
		// pushes to system execution sink
		$this->systemExecutionSink->log($prodLogMessage);
		// pushes to additional execution sinks
		if(isset($this->additionalExecutionSinks)) {
			foreach($this->additionalExecutionSinks as $executionSink) {
				$executionSink->log($prodLogMessage);
			}
		}
	}	
}