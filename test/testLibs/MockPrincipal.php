<?php

/**
 * Class MockPrinciple
 * Sets up a principle that we can use to test Wiggii functions that require a principle to be set.
 */
class MockPrincipal extends Principal {

	/**
	 * set up all the credentials for a mock user so that we can test functions that use
	 * a Principle. This mock assumes that you are authorised
	 */
	protected $mockUser;

	public function __construct($userName, $wigiiNameSpaceName) {
		$client = new Client();
		$client->setClientName('TestClient');
		$namespace = WigiiNamespace::createInstance($client);
		$namespace->setWigiiNamespaceName($wigiiNameSpaceName);
		$this->mockUser = new MockUser();
		$this->mockUser->setUsername($userName);
		$this->mockUser->setId($userName . '_id');
		$this->mockUser->setWigiiNamespace($namespace);
		$this->attachUser($this->mockUser);
		$this->setWigiiNamespace($namespace);
	}

	protected function doBindToWigiiNamespace($wigiiNamespace) {
		// gets role name in cache
		if(!isset($this->roleNames)) $this->roleNames = array();
		$roleNameCacheKey = $this->getRoleNamesCacheKey($wigiiNamespace);
		$roleName = $this->roleNames[$roleNameCacheKey];
		// if role name is not in cache, retrieves role associated to namespace
		if(!isset($roleName)) {
			// fetches calculated role for this namespace in db
			$role = UserListArrayImpl::createInstance();
			$listFilter = ListFilter::createInstance();
			$listFilter->setDesiredPageNumber(1);
			$listFilter->setPageSize(1);
			$listFilter->setFieldSelectorLogExp($this->getLogExpForRoleByWigiiNamespace($wigiiNamespace));

			if(!$role->isEmpty()) {
				$role = $role->getFirstUser();
				$this->bindToUser($role->getUsername());
				// puts role in cache
				$this->attachUser($role, true);
				if(isset($this->roleListener)) $this->roleListener->addUser($role);
			}
			// else is real user in required namespace, then binds to real user
			else if($this->getRoleNamesCacheKey($this->getRealWigiiNamespace()) == $roleNameCacheKey) {
				$this->bindToRealUser();
				$this->roleNames[$roleNameCacheKey] = $this->getRealUsername();
			}
			// else returns false
			else {
				//$this->debugLogger()->write("no role matching namespace");
				return false;
			}
		}
		// else binds to role
		else $this->bindToUser($roleName);
		return true;
	}

	private function getRoleNamesCacheKey($wigiiNamespace) {
		if(is_null($wigiiNamespace) || $wigiiNamespace == '') return WigiiNamespace::EMPTY_NAMESPACE_URL;
		else if(is_object($wigiiNamespace)) return $wigiiNamespace->getWigiiNamespaceUrl();
		else return $wigiiNamespace;
	}
}