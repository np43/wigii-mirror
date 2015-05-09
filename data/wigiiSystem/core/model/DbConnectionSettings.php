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
 * Database connection settings
 * Created by CWE on 23 juin 09
 */
class DbConnectionSettings extends Model
{
	/**
	 * map name => value
	 */
	private $params;

	private $username;
	private $password;
	private $dbname;
	private $hostname;
	
	
	
	public static function createInstance()
	{
		return new DbConnectionSettings();
	}
	/**
	 * Returns a map (name => value) of parameters useful for the db connection
	 * The exact meaning of each stored named parameters depends of the database vendor.
	 */
	public function getParams()
	{
		return $this->params;
	}
	/**
	 * Stores a map of parameters for db connection
	 * map (name => value)
	 */
	public function setParams($parametersMap)
	{
		$this->params = $parametersMap;
	}

	/**
	 * Gets default placeholder value for db username
	 */
	public function getUsername()
	{
		return $this->username;
	}
	/**
	 * Sets default placeholder value for db username
	 */
	public function setUsername($username)
	{
		$this->username = $username;
	}
	/**
	 * Gets default placeholder value for user password
	 */
	public function getPassword()
	{
		return $this->password;
	}
	/**
	 * Sets default placeholder value for password
	 */
	public function setPassword($password)
	{
		$this->password = $password;
	}
	/**
	 * Gets default placeholder value for db name
	 */
	public function getDbName()
	{
		return $this->dbname;
	}
	/**
	 * Sets default placeholder value for db name
	 */
	public function setDbName($dbname)
	{
		$this->dbname = $dbname;
	}
	/**
	 * Gets default placeholder value for db host name
	 */
	public function getHost()
	{
		return $this->hostname;
	}
	/**
	 * Sets default placeholder value for db host name
	 */
	public function setHostName($hostname)
	{
		$this->hostname = $hostname;
	}
	
	public function displayDebug(){
		$returnValue = "CLASS: ".get_class($this)."\n";
		$returnValue .= "DbName: ".$this->getDbName()."\n";
		$returnValue .= "DbHost: ".$this->getHost()."\n";
		$returnValue .= "DbUser: ".$this->getUsername()."\n";
		return $returnValue;
	}
	
}


