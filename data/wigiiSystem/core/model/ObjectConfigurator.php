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
 * An object configurator
 * This objects holds some configuration values, each value is stored under a unique key.
 * The key can be a method name, in that case, you can use the configure method to automatically 
 * set the configuration values into an object which would expose the corresponding methods
 * Created by CWE on 2 juillet 2013
 */
class ObjectConfigurator extends Model
{
	protected $configValues;	
	
	// object lifecycle

	/**
	 * Creates an instance of an ObjectConfigurator
	 * @param $configValues optional array of configuration values passed at construction time
	 */
	public static function createInstance($configValues=null)
	{
		$returnValue = new self();
		$returnValue->reset();
		if(isset($configValues)) $returnValue->setConfigValues($configValues);		
		return $returnValue;
	}
	public function reset()
	{
		$this->freeMemory();
		$this->configValues = array();		
	}
	public function freeMemory()
	{
		unset($this->configValues);
	}
	
	// Implementation

	/**
	 * Returns the configuration value associated to this key or null if not found
	 */
	public function getConfigValue($configKey) {
		if(is_null($configKey)) throw new ServiceException("configKey cannot be null", ServiceException::INVALID_ARGUMENT);
		return $this->configValues[$configKey];
	}

	/**
	 * Sets a configuration value for this key.
	 * The original value, if any, is replaced.
	 */
	public function setConfigValue($configKey, $value) {
		if(is_null($configKey)) throw new ServiceException("configKey cannot be null", ServiceException::INVALID_ARGUMENT);
		$this->configValues[$configKey] = $value;
	}
	
	/**
	 * Sets several configuration values
	 * @param $configValues an array of config values (configKey, value)
	 */
	public function setConfigValues($configValues) {
		if(!is_array($configValues)) throw new ServiceException("configValues should be an array", ServiceException::INVALID_ARGUMENT);
		foreach($configValues as $configKey => $value) {
			$this->configValues[$configKey] = $value;
		}
	}
	
	/**
	 * Returns an iterator on the pairs of (config key, config value)
	 */
	public function getConfigIterator()
	{
		return $this->configValues;
	}

	/**
	 * Returns true if this object doesn't hold any configuration values
	 */
	public function isEmpty()
	{
		return ($this->count() === 0);
	}

	/**
	 * Returns the number of configuration values stores in this object
	 */
	public function count()
	{
		return (count($this->configValues));
	}

	/**
	 * Configures an object and returns the number of configuration done
	 * Goes through each configuration keys and 
	 * calls a method with the same name on the object (if exists)
	 */
	public function configure($object) {
		$returnValue = 0;	
		foreach($this->configValues as $setter => $value) {
			if(method_exists($object, $setter)) {
				$object->$setter($value);
				$returnValue++;
			}
		}
		return $returnValue;
	}	
}