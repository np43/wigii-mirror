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
 * An ExceptionSink which dispatches events to several additional ExceptionSinks
 * Created by CWE on 11 juillet 2013
 */
class MultipleExceptionSink extends ExceptionSink
{
	/**
	 * The default system exception sink
	 */
	protected $systemExceptionSink;	
	/**
	 * An array of ExceptionSink instances (does not contain the system ExceptionSink)
	 * key=ExceptionSink (sub) class name
	 * value=ExceptionSink instance
	 */
	protected $additionalExceptionSinks;

	// object lifecycle
	
	/**
	 * Creates a new instance of this MultipleExceptionSink
	 * @param ExceptionSink $systemExceptionSink the default ExceptionSink instance to be used. 
	 * This argument must point to a valid ExceptionSink running instance. 
	 */
	public function __construct($systemExceptionSink)
	{		
		$this->systemExceptionSink = $systemExceptionSink;
	}

	// configuration
	
	/**
	 * Adds a list of ExceptionSink classes to be used.
	 * If some classes are already setup as additional ExceptionSinks, keeps the original instances.
	 * @param array|string $additionalExceptionSinkClasses an array of class names or one single class name string
	 * or an array of (key=class name, value=ObjectConfigurator). In that case, the ObjectConfigurator will be applied 
	 * on each created instance.
	 */
	public function useAdditionalExceptionSinks($additionalExceptionSinkClasses) {
		if(empty($additionalExceptionSinkClasses)) return;
		if(!isset($this->additionalExceptionSinks)) $this->additionalExceptionSinks = array();
		// if multiple classes
		if(is_array($additionalExceptionSinkClasses)) {
			foreach($additionalExceptionSinkClasses as $key => $value) {
				if($value instanceof ObjectConfigurator) {
					$className = $key;
					$configurator = $value;
				}
				else {
					$className = $value;
					$configurator = null;
				}
				// creates instance only if not already created
				$o = $this->additionalExceptionSinks[$className];
				if(!isset($o)) $this->additionalExceptionSinks[$className] = $this->createExceptionSinkInstance($className, $configurator);
			}
		}
		// else one single string
		else {
			$o = $this->additionalExceptionSinks[$additionalExceptionSinkClasses];
			if(!isset($o)) $this->additionalExceptionSinks[$additionalExceptionSinkClasses] = $this->createExceptionSinkInstance($additionalExceptionSinkClasses);			
		}
	}
	/**
	 * Creates an instance of a ExceptionSink given its className
	 * By default, looks for a class on the classpath
	 * @param ObjectConfigurator $configurator optional ObjectConfigurator instance to configure created instance
	 */
	protected function createExceptionSinkInstance($className, $configurator = null) {
		$returnValue = new $className();
		if(isset($configurator)) {
			$configurator->configure($returnValue);
		}	
		return $returnValue;		
	}
	
	/**
	 * Removes a list of additional ExceptionSink classes.
	 * If some classes are not setup as additional ExceptionSinks, there are ignored.
	 * The system ExceptionSink instance cannot be removed (ignored if defined in the list)
	 * @param array|string $exceptionSinkClasses an array of class names or one single class name string
	 */
	public function removeAdditionalExceptionSinks($exceptionSinkClasses) {
		if(empty($exceptionSinkClasses)) return;
		if(!isset($this->additionalExceptionSinks)) return;		
		if(!is_array($exceptionSinkClasses)) $exceptionSinkClasses = array($exceptionSinkClasses);
		$this->additionalExceptionSinks = array_diff_key($this->additionalExceptionSinks, array_combine($exceptionSinkClasses, $exceptionSinkClasses));
	}
	
	// implementation
	
	/**
	 * Does the actual publication work
	 */
	protected function doPublish($exception)
	{
		// pushes to system exception sink
		$this->systemExceptionSink->doPublish($exception);
		// pushes to additional exception sinks
		if(isset($this->additionalExceptionSinks)) {
			foreach($this->additionalExceptionSinks as $exceptionSink) {
				$exceptionSink->doPublish($exception);
			}
		}
	}
}



