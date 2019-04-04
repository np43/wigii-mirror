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

/* A wigii WigiiNamespace
 * Created by CWE on 31 mai 09
 */
class WigiiNamespace extends Model
{
	private $wigiiNamespaceName;
	private $client;
	const EMPTY_NAMESPACE_URL = "NoWigiiNamespace";
	const EMPTY_NAMESPACE_NAME = "";
	
	/**
	 * @param $client, client
	 */
	public static function createInstance($client)
	{
		$wigiiNamespace = new WigiiNamespace();
		$wigiiNamespace->setClient($client);
		return $wigiiNamespace;
	}

	/**
	 * Returns wigii client containing wigiiNamespace
	 */
	public function getClient()
	{
		return $this->client;
	}
	protected function setClient($client)
	{
		$this->client = $client;
	}

	public function getWigiiNamespaceName()
	{
		return $this->wigiiNamespaceName;
	}
	public function setWigiiNamespaceName($wigiiNamespaceName)
	{
		if($wigiiNamespaceName == self::EMPTY_NAMESPACE_URL || $wigiiNamespaceName==="null"){
			$wigiiNamespaceName = self::EMPTY_NAMESPACE_NAME;
		}
		$this->wigiiNamespaceName = $wigiiNamespaceName;
	}
	public function getWigiiNamespaceUrl(){
		if($this->wigiiNamespaceName == null) {
			return self::EMPTY_NAMESPACE_URL;
		}
		return $this->wigiiNamespaceName;
	}
	
}



