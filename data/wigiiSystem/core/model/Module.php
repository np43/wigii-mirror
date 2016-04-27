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
 * A wigii module
 * Created by CWE on 31 mai 09
 */
class Module extends Model
{
	private $moduleName;
	const EMPTY_MODULE_URL = "NoModule";
	const EMPTY_MODULE_NAME = "";
	const ADMIN_MODULE = "Admin";
	const HOME_MODULE = "Home";
	const HELP_MODULE = "Help";
	const DIMENSIONS_MODULE = "Dimensions";

	public static function createInstance()
	{
		return new Module();
	}
	
	public function getModuleName()
	{
		return $this->moduleName;
	}
	public function setModuleName($moduleName)
	{
		if($moduleName == self::EMPTY_MODULE_URL){
			$moduleName = self::EMPTY_MODULE_NAME;
		}
		$this->moduleName = $moduleName;
		return $this;
	}
	
	public function getModuleUrl(){
		if($this->moduleName == null){
			return self::EMPTY_MODULE_URL;
		}
		return $this->moduleName;
	}
	
	public function isAdminModule(){
		return $this->moduleName == Module::ADMIN_MODULE;
	}
	public function isHomeModule(){
		return $this->moduleName == Module::HOME_MODULE;
	}
	public function isHelpModule(){
		return $this->moduleName == Module::HELP_MODULE;
	}
	public function isDimensionsModule(){
		return $this->moduleName == Module::DIMENSIONS_MODULE;
	}
	public function isUserModule(){
		return !($this->isAdminModule() || $this->isHomeModule() || $this->isHelpModule() || $this->moduleName==self::EMPTY_MODULE_NAME);
	}
}



