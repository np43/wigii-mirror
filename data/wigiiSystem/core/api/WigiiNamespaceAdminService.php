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
 * Wigii wigiiNamespaces administration service
 * Created by CWE on 12 juin 09
 * Modified by CWE on 23 mars 14 to add Wigii Setup functions
 */
interface WigiiNamespaceAdminService
{
	
	/**
	 * Returns a wigiiNamespace given its name, the wigiiNamespace is in the same client as the principal
	 * @param Principal principal authenticated user performing the operation
	 * @throws WigiiNamespaceAdminServiceException if an error occurs
	 * @return WigiiNamespace
	 */
	public function getWigiiNamespace($principal, $wigiiNamespaceName);

	/**
	 * Returns empty wigiiNamespace of empty client
	 * @return WigiiNamespace
	 */
	public function getEmptyWigiiNamespaceForEmptyClient();

	/**
	 * Returns empty wigiiNamespace of default client
	 * @return WigiiNamespace
	 */
	public function getEmptyWigiiNamespaceForDefaultClient();
	
	/**
	 * Returns the WigiiNamespace where is stored the setup data
	 * @param Principal principal authenticated user performing the operation
	 * @throws WigiiNamespaceAdminServiceException if an error occurs
	 * @return WigiiNamespace
	 */
	public function getSetupWigiiNamespace($principal);
	

	////////////////////////
	// OPERATION DELEGATION
	////////////////////////



	/**
	 * Returns the wigiiNamespace with the given name in the specified client scope
	 * @param Principal principal authenticated user performing the operation
	 * @throws WigiiNamespaceAdminServiceException if an error occurs
	 * @return WigiiNamespace
	 */
	public function getWigiiNamespaceForClient($principal, $wigiiNamespaceName, $client);

	/**
	 * Returns the empty wigiiNamespace associated to a client.
	 * Each client can have one empty wigiiNamespace.
	 * @param Principal principal authenticated user performing the operation
	 * @throws WigiiNamespaceAdminServiceException if an error occurs
	 * @return WigiiNamespace
	 */
	public function getEmptyWigiiNamespaceForClient($principal, $client);
}
