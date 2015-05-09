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
 * Wigii system authorization service
 * Created by CWE on 9 août 09
 * Modified by CWE on 23.09.2014 to add stamping support.
 */
interface AuthorizationService
{
	// SYSTEM PRINCIPALS

	/**
	 * Fills a list with freshly created system principal instances
	 * associates system principal with wigiiNamespace if not null, else EmptyWigiiNamespace for DefaultClient
	 * if moduleAccess defined, the moduleAccess will be seted
	 */
	public function createAllSystemPrincipals($principalList, $wigiiNamespace=null, $moduleAccess=null);

	/**
	 * Returns true if the principal calling this function is the root principal else false
	 * throws an AuthorizationServiceException in case of problem
	 * postcondition: checks with AuthenticationService for validity
	 */
	public function isRootPrincipal($principal);

	/**
	 * Asserts that principal calling this function is the root principal
	 * throws an AuthorizationServiceException with FORBIDDEN code if not.
	 * throws an AuthorizationServiceException in case of problem
	 * postcondition: checks with AuthenticationService for validity
	 */
	public function assertPrincipalIsRoot($principal);

	/**
	 * Returns the root principal stored in a principal list if it exists, else null
	 * postcondition: does not check with AuthenticationService for validity
	 */
	public function findRootPrincipal($principalList);

	/**
	 * Returns true if the principal calling this function is the public principal else false
	 * throws an AuthorizationServiceException in case of problem
	 * postcondition: checks with AuthenticationService for validity
	 */
	public function isPublicPrincipal($principal);

	/**
	 * Asserts that principal calling this function is the public principal
	 * throws an AuthorizationServiceException with FORBIDDEN code if not.
	 * throws an AuthorizationServiceException in case of problem
	 * postcondition: checks with AuthenticationService for validity
	 */
	public function assertPrincipalIsPublic($principal);

	/**
	 * Returns the public principal stored in a principal list if it exists, else null
	 * postcondition: does not check with AuthenticationService for validity
	 */
	public function findPublicPrincipal($principalList);

	// AUTHENTICATION SERVICE PRINCIPAL

	/**
	 * Returns true if the principal calling this function represents the AuthenticationService else false
	 * throws an AuthorizationServiceException in case of problem
	 */
	public function isPrincipalAuthenticationService($principal);

	/**
	 * Asserts that principal calling this function represents the AuthenticationService
	 * throws an AuthorizationServiceException with FORBIDDEN code if not.
	 * throws an AuthorizationServiceException in case of problem
	 */
	public function assertPrincipalIsAuthenticationService($principal);


	// ASSERTIONS

	/**
	 * Always throws an AuthorizationServiceException with FORBIDDEN code with a specific reason message
	 */
	public function fail($principal, $reason);

	/**
	 * Asserts that principal is authorized to call this method on this service
	 * throws an AuthorizationServiceException with FORBIDDEN code if not.
	 * throws an AuthorizationServiceException in case of problem
	 */
	public function assertPrincipalAuthorized($principal, $serviceName, $methodName);
	
	// ELEMENT POLICY 
	
	/**
	 * Returns true if an ElementPolicyEvaluator is enabled for this principal and module,
	 * else returns false
	 * @param Principal $principal
	 * @param Module $module
	 */
	public function isElementPolicyEvaluatorEnabled($principal, $module);
	
	// STAMPS
	
	/**
	 * Delivers an AuthorizationServiceStamp to the caller through a callback method.
	 * @param Object $caller any object instance which will receive the stamp.
	 * @param String $callbackMethod the name of the method to call on the caller to deliver the created stamp.
	 * The method takes one argument which is the created AuthorizationServiceStamp instance.
	 * The algorithm is the following :
	 * 1. AuthorizationService checks that the caller is authorized to get stamps.
	 * 		if not, then an AuthorizationServiceException::FORBIDDEN is thrown.
	 * 2. generates an instance of AuthorizationServiceStamp and registers it.
	 * 3. delivers the stamp by calling $caller->$callbackMethod($authorizationServiceStamp);
	 * @return boolean returns true if the stamp has been delivered.
	 * @throws AuthorizationServiceException if caller is not authorized to get stamps.
	 */
	public function getStamp($caller, $callbackMethod);
	
	/**
	 * Checks that the provided AuthorizationServiceStamp has been created by this AuthorizationService and is still valid.
	 * @param AuthorizationServiceStamp $authorizationServiceStamp the stamp instance to check for validity
	 * @return boolean true if stamp is valid, else false.
	 */
	public function isStampValid($authorizationServiceStamp);
}

/**
 * The AuthorizationService stamp
 * Created by CWE on 23.09.2014
 */
class AuthorizationServiceStamp {
	
	// Object lifecycle

	public static function createInstance($id, $microTime) {
		$returnValue = new self();
		$returnValue->setId($id);
		$returnValue->setMicroTimeStamp($microTime);
		return $returnValue;
	}
	
	// Stamp methods
	
	private $id;
	protected function setId($id) {$this->id = $id;}
	/**
	 * Returns the id of this stamp
	 */
	public function getId() {return $this->id;}
	
	private $microTimeStamp;
	protected function setMicroTimeStamp($microTime) {$this->microTimeStamp = $microTime;}
	/**
	 * Returns the time stamp in microseconds.
	 */
	public function getMicroTimeStamp() {return $this->microTimeStamp;}
}

