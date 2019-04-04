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
 * PrincipalList array implementation
 * Created by CWE on 9 janv. 10
 */
class PrincipalListArrayImpl extends ObjectListArrayImpl implements PrincipalList
{
	public static function createInstance()
	{
		$returnValue = new self();
		$returnValue->reset();
		return $returnValue;
	}
	public function addPrincipal($principal)
	{
		if(is_null($principal)) throw new ListException("principal cannot be null", ListException::INVALID_ARGUMENT);
		$key = $this->getKey($principal->getUsername(), $principal->getWigiiNamespace()->getClient()->getClientName());
		if(isset($this->objArray[$key])) throw new ListException("a principal with same key $key has already been added", ListException::ALREADY_EXISTS);
		$this->objArray[$key] = $principal;
	}
	public function getPrincipal($username, $client)
	{
		if(is_null($username)) throw new ListException("username cannot be null", ListException::INVALID_ARGUMENT);
		if(is_null($client)) throw new ListException("client cannot be null", ListException::INVALID_ARGUMENT);
		return $this->objArray[$this->getKey($username, $client->getClientName())];
	}

	/**
	 * Adds content of principal list to this one
	 */
	public function unionPrincipalList($principalList)
	{
		if(is_null($principalList)) return;
		if($principalList instanceof PrincipalList)
		{
			foreach($principalList->getListIterator() as $principal)
			{
				try
				{
					$this->addPrincipal($principal);
				}
				catch(ListException $le)
				{
					if($le->getCode() != ListException::ALREADY_EXISTS) throw $le;
				}
			}
		}
		elseif($principalList instanceof Principal)
		{
			$this->addPrincipal($principalList);
		}
		else throw new ListException("principalList should be a PrincipalList or a Principal", ListException::INVALID_ARGUMENT);
	}


	protected function getKey($username, $clientName)
	{
		return '('.(is_null($clientName) ? '' : $clientName).'('.(is_null($username) ? '' : $username).'))';
	}
}
