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

/*
 * Created on 26/06/2013
 * by LWR
 */

interface SysInformation {
	
	/**
	 * gets the timestamp of creation
	 */
	public function getSys_creationDate();
	public function setSys_creationDate($var);
	
	/**
	 * get the timestamp of last update
	 */
	public function getSys_date();
	public function setSys_date($var);
	
	/**
	 * gets the id of the user having done the last update
	 */
	public function getSys_user();
	public function setSys_user($var);
	
	/**
	 * gets the username of the user having done the last update
	 */
	public function getSys_username();
	public function setSys_username($var);
	
	/**
	 * gets the id of the user having done the creation
	 */
	public function getSys_creationUser();
	public function setSys_creationUser($var);
	
	/**
	 * gets the username of the user having done the creation
	 */
	public function getSys_creationUsername();
	public function setSys_creationUsername($var);
	
}


