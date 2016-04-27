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
 * An Element Configuration Info class
 * Created by CWE on 23.09.2014
 */
class ElementConfigInfo extends Model
{
	private $groupList;
	
	// Object lifecycle
	
	/**
	 * Creates a new instance of ElementConfigInfo given the GroupList selecting the Element configuration.
	 * @param GroupList|Module $groupList the GroupList selecting the configuration or a Module.
	 */
	public static function createInstance($groupList) {
		$returnValue = new self();
		$returnValue->extractInfo($groupList);
		return $returnValue;
	}
	
	protected function extractInfo($groupList) {
		if(!isset($groupList) ||
			$groupList instanceof GroupList && $groupList->isEmpty()) throw new RecordException('groupList cannot be empty', RecordException::INVALID_ARGUMENT);
		$this->groupList = $groupList;
	}
	
	// Dependency injection
	
	private $configS;
	public function setConfigService($configService)
	{
		$this->configS = $configService;
	}
	protected function getConfigService()
	{
		// autowired
		if(!isset($this->configS))
		{
			$this->configS = ServiceProvider::getConfigService();
		}
		return $this->configS;
	}
	
	// Element Config Info
	
	/**
	 * Returns the list of Fields behind this ElementConfigInfo
	 * @param Principal $principal authenticated user performing the operation
	 * @param FieldList $fieldList the field list instance to be filled with the list of fields.
	 */
	public function getFields($principal, $fieldList) {
		if($this->groupList instanceof GroupList) {
			return $this->getConfigService()->getGroupsFields($principal, $this->groupList, null, $fieldList);
		}
		else {
			return $this->getConfigService()->getFields($principal, $this->groupList, null, $fieldList);
		}
	}
}

