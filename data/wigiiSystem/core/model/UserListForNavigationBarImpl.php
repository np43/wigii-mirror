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
 * UserList implementation for the navigation bar template
 * particularity:
 * 	first level contain the module of the default wigiiNamespace + all other wigiiNamespace
 * 	second level contain the list of modules of the other wigiiNamespaces
 * Created by LWR on 8 July 2011
 */
class UserListForNavigationBarImpl extends ObjectListArrayImpl implements UserList {
	//herited attributs:
	//protected $objArray = array()

	private $roleIds;
	private $roleIdsPerWigiiNamespaceModules;
	private $roleNamesPerWigiiNamespaceModules;
	private $defaultWigiiNamespace;
	private $defaultWigiiNamespaceModules;
	private $otherWigiiNamespaces;
	private $calculatedRoleIds;
	private $adminRoleIds;


	public static function createInstance($defaultWigiiNamespace) {
		$returnValue = new self();
		$returnValue->reset($defaultWigiiNamespace);
		return $returnValue;
	}

	public function reset($defaultWigiiNamespace)
	{
		parent::reset();
		$this->roleIds = array();
		$this->rolesPerWigiiNamespaceModules = array();
		$this->defaultWigiiNamespace = $defaultWigiiNamespace;
		$this->defaultWigiiNamespaceModules = array();
		$this->otherWigiiNamespaces = array();
		$this->calculatedRoleIds = array();
		$this->adminRoleIds = array();
	}
	public function freeMemory()
	{
		parent::freeMemory();
		unset($this->roleIds);
		unset($this->rolesPerWigiiNamespaceModules);
		unset($this->defaultWigiiNamespace);
		unset($this->defaultWigiiNamespaceModules);
		unset($this->otherWigiiNamespaces);
		unset($this->calculatedRoleIds);
		unset($this->adminRoleIds);
	}

	public function getRoleIds(){
		return $this->roleIds;
	}
	/**
	 * returns the list of roles (not calculated roles) that the user has for the specifc wigiiNamespace and module
	 */
	public function getRolesPerWigiiNamespaceModule($wigiiNamespaceUrl, $moduleUrl){
		if($this->rolesPerWigiiNamespaceModules && $this->rolesPerWigiiNamespaceModules[$wigiiNamespaceUrl]){
			return $this->rolesPerWigiiNamespaceModules[$wigiiNamespaceUrl][$moduleUrl];
		}
		return null;
	}
	public function getDefaultWigiiNamespace(){
		return $this->defaultWigiiNamespace;
	}
	public function getDefaultWigiiNamespaceModules() {
		return $this->defaultWigiiNamespaceModules;
	}

	public function getOtherWigiiNamespaces() {
		return $this->otherWigiiNamespaces;
	}
	public function getCalculatedRoleIds() {
		return $this->calculatedRoleIds;
	}
	public function getCalculatedRoleId($wigiiNamespaceUrl) {
		return $this->calculatedRoleIds[$wigiiNamespaceUrl];
	}
	public function getCalculatedRole($wigiiNamespaceUrl) {
		return $this->getUser($this->getCalculatedRoleId($wigiiNamespaceUrl));
	}
	public function getAdminRoleIds() {
		return $this->adminRoleIds;
	}

	public function addUser($user) {
		if (!isset ($user))
			throw new ListException("user cannot be null", ListException :: INVALID_ARGUMENT);

		$this->objArray[$user->getId()] = $user;

		if($user->isCalculatedRole()){

			$this->calculatedRoleIds[$user->getWigiiNamespace()->getWigiiNamespaceUrl()] = $user->getId();

			if ($user->getWigiiNamespace()->getWigiiNamespaceUrl() == $this->defaultWigiiNamespace) {
				$this->defaultWigiiNamespaceModules = array ();
				if ($user->getDetail()->getModuleAccess()) {
					foreach ($user->getDetail()->getModuleAccess() as $moduleName => $module) {
						if ($module->isAdminModule())
							continue;
						$this->defaultWigiiNamespaceModules[$moduleName] = $user->getId();
					}
					//reorder module on alphabetical order + move help module to the end
					if (is_array($this->defaultWigiiNamespaceModules)) {
						ksort($this->defaultWigiiNamespaceModules);
						$help = $this->defaultWigiiNamespaceModules[Module :: HELP_MODULE];
						if ($help) {
							unset ($this->defaultWigiiNamespaceModules[Module :: HELP_MODULE]);
							$this->defaultWigiiNamespaceModules[Module :: HELP_MODULE] = $help;
						}
					}
				}
			} else {
				$wigiiNamespace = $user->getWigiiNamespace()->getWigiiNamespaceUrl();
				$this->otherWigiiNamespaces[$wigiiNamespace] = array ();
				if ($user->getDetail()->getModuleAccess()) {
					foreach ($user->getDetail()->getModuleAccess() as $moduleName => $module) {
						if ($module->isAdminModule())
							continue;
						$this->otherWigiiNamespaces[$wigiiNamespace][$moduleName] = $user->getId();
					}
					//reorder module on alphabetical order + move help module to the end
					if (is_array($this->otherWigiiNamespaces[$wigiiNamespace])) {
						ksort($this->otherWigiiNamespaces[$wigiiNamespace]);
						$help = $this->otherWigiiNamespaces[$wigiiNamespace][Module :: HELP_MODULE];
						if ($help) {
							unset ($this->otherWigiiNamespaces[$wigiiNamespace][Module :: HELP_MODULE]);
							$this->otherWigiiNamespaces[$wigiiNamespace][Module :: HELP_MODULE] = $help;
						}
					}
				}
			}

		} else {
			$wigiiNamespace = $user->getWigiiNamespace()->getWigiiNamespaceUrl();
			if ($user->getDetail()->getModuleAccess()) {
				foreach ($user->getDetail()->getModuleAccess() as $moduleName => $module) {
					if ($module->isAdminModule())
						continue;
					$this->rolesPerWigiiNamespaceModules[$wigiiNamespace][$moduleName][$user->getId()] = $user->getUsername();
				}
			}
		}
		if($user->getDetail()->isUserCreator() || $user->getDetail()->getRootGroupCreator()!=null){
			$this->adminRoleIds[$user->getId()]=$user->getId();
		}
	}

	public function getUser($userId) {
		return $this->objArray[$userId];
	}

}