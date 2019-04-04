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

/* wigii User admin service interface
 * Created by CWE on 31 mai 09
 */
interface UserAdminService
{
	/**
	 * Inserts a new user or updates an existing user in the database
	 * principal: authenticated user performing the operation
	 * user: user object to save in the database
	 * fieldSelectorList : only selected fields will be updated
	 * throws UserAdminServiceException in case of error
	 * precondition: User fields are mandatory even if no change, UserDetail fields are optional
	 * postcondition: user is in the database and user id is updated with database id
	 * returns 1 if user has been persisted, else 0
	 */
	public function persistUser($principal, $user, $fieldSelectorList=null);

	/**
	 * Updates user login information (this include current password, password history, and password date)
	 * principal: authenticated user performing the operation
	 * throws UserAdminServiceException in case of error
	 * returns 1 if user has been persisted, else 0
	 */
	public function persistUserLoginInformation($principal, $user);

	/**
	 * Update user session context information
	 * principal: authentication service principal
	 * user: User containing the session context information
	 * throws UserAdminServiceException in case of error
	 * returns 1 if user has been persisted, else 0
	 */
	public function persistUserSessionContext($principal, $user);

	/**
	 * Deletes a user given its database ref
	 * principal: authenticated user performing the operation
	 * userId: user database id we want to delete
	 * throws UserAdminServiceException in case of error
	 * returns 1 if user has been deleted, else 0
	 */
	public function deleteUser($principal, $userId);

	/**
	 * Deletes all users or roles created in a wigiiNamespace
	 * principal: authenticated user performing the operation, needs to be namespace creator
	 * wigiiNamespace: wigiiNamespace to delete
	 * throws UserAdminServiceException in case of error
	 * returns nb of deleted items
	 */
	public function deleteAllUserRolesFromWigiiNamespace($principal, $wigiiNamespace);
	
	/**
	 * Fills a list with the users accessible by the current principal
	 * principal: authenticated user performing the operation
	 * rolePList: list of users to be filled (with principal rights)
	 * listFilter: filter on the list; filters columns, does pagination and sorting
	 * returns the number of users added to the list
	 * throws UserAdminServiceException in case of error
	 */
	public function getAllUsers($principal, $rolePList, $listFilter=null);

	/**
	 * Fills a list with the all the users of the system
	 * principal: authenticated user performing the operation
	 * rolePList: list of users to be filled (with principal rights)
	 * listFilter: filter on the list; filters columns, does pagination and sorting
	 * returns the number of users added to the list
	 * throws UserAdminServiceException in case of error
	 */
	public function getAllUsersFromSystem($principal, $rolePList, $listFilter=null);
	
	/**
	 * Gets a user given its database id
	 * principal: authenticated user performing the operation
	 * fieldSelectorList : only selected fields will be filled
	 * throws UserAdminServiceException in case of error
	 * returns a UserP instance or null if not found
	 */
	public function getUser($principal, $userId, $fieldSelectorList=null);

	/**
	 * Gets a user given its database id
	 * principal: authenticated user performing the operation
	 * throws UserAdminServiceException in case of error
	 * returns a User instance without detail or null if not found
	 */
	public function getUserWithoutDetail($principal, $userId);

	/**
	 * Gets a user given its user name
	 * principal: authenticated user performing the operation
	 * fieldSelectorList : only selected fields will be filled
	 * throws UserAdminServiceException in case of error
	 * returns a UserP instance or null if not found
	 */
	public function findUserByName($principal, $userName, $fieldSelectorList=null);

	/**
	 * Returns all users managed by this user
	 * principal: authenticated user performing the operation
	 * userId: the owner database id
	 * userURPList: the list to fill with the owned users associated to the user rights and principal rights
	 * listFilter: filter on the list; filters columns, does pagination and sorting
	 * returns the number of UserURP added to the list
	 * throws UserAdminServiceException in case of error
	 */
	public function getAllUserUsers($principal, $userId, $userURPList, $listFilter=null);
	
	/**
	 * Calculate one merged role for each wigiiNamespace the user can access.
	 * Each merged role contains the fusion of all the rights of the roles (+ current user) of each wigiiNamespace.
	 * Fusion of the admin rights + dupplication of each UGR
	 * principal: authenticated user performing the operation
	 */
	public function calculateAllMergedRoles($principal);
	
	/**
	 * Returns the list of roles this principal can give (all role he isOwner + all role he hasRole)
	 * principal: authenticated user performing the operation
	 * userList: the list of users this principal can give with the details
	 * listFilter: filter on the list; filters columns, does pagination and sorting
	 * throws UserAdminServiceException in case of error
	 * returns the number of User add to the list
	 */
	public function getAllRoles($principal, $roleList, $listFilter=null);

	/**
	 * Returns the list of roles for this user given its id and that the principal has (intersection with getAllRoles)
	 * principal: authenticated user performing the operation
	 * userList: the list of users acting as roles for this user
	 * listFilter: filter on the list; filters columns, does pagination and sorting
	 * throws UserAdminServiceException in case of error
	 * returns the number of User add to the list
	 */
	public function getAllUserRoles($principal, $userId, $roleList, $listFilter=null);

	/**
	 * Returns the list of users that is allocated to this role
	 * principal: authenticated user performing the operation
	 * userList: the list of users having the role allocated
	 * listFilter: filter on the list; filters columns, does pagination and sorting
	 * throws UserAdminServiceException in case of error
	 * returns the number of User add to the list
	 */
	public function getAllRoleUsers($principal, $roleId, $userList, $listFilter=null);

	/**
	 * Gets a role given its database id. The role will contain all the details.
	 * To find the role only a hasRole right is needed.
	 * principal: authenticated user performing the operation
	 * fieldSelectorList : only selected fields will be filled
	 * throws UserAdminServiceException in case of error
	 * returns a User instance or null if not found
	 */
	public function getMyRole($principal, $roleId, $fieldSelectorList=null);

	/**
	 * Fills a list with the roles the current principal can play
	 * To find the role only a hasRole right is needed.
	 * principal: authenticated user performing the operation
	 * userList: list of roles to be filled
	 * listFilter: filter on the list; filters columns, does pagination and sorting
	 * returns the number of roles added to the list
	 * throws UserAdminServiceException in case of error
	 */
	public function getMyRoles($principal, $roleList, $listFilter=null);

	/**
	 * Persists a user1-user2 ownership right
	 * principal: authenticated user performing the operation
	 * throws UserAdminServiceException in case of error
	 * returns 1 if persisted else 0
	 */
	public function setUserOwnership($principal, $ownerId, $userId);

	/**
	 * Persists that user1 plays a role on user2
	 * principal: authenticated user performing the operation
	 * throws UserAdminServiceException in case of error
	 * returns 1 if persisted else 0
	 */
	public function setUserRole($principal, $userId, $roleId);

	/**
	 * Removes an owner from a user
	 * principal: authenticated user performing the operation
	 * throws UserAdminServiceException in case of error
	 * 		if all owners are removed for this user then an UserAdminServiceException with code DATA_INTEGRITY_ERROR is thrown
	 * 		because a user should be owned by at least one user.
	 * returns 1 if user ownership is removed else 0
	 */
	public function removeUserOwnership($principal, $ownerId, $userId);

	/**
	 * Removes a role from a user
	 * principal: authenticated user performing the operation
	 * throws UserAdminServiceException in case of error
	 * returns 1 if user role is removed else 0
	 */
	public function removeUserRole($principal, $userId, $roleId);

	/**
	 * Change the password of the realUser of the principal
	 * if no realUser, then this will be the attachedUser
	 * throws UserAdminServiceException in case of error
	 */
	public function changeOwnPassword($principal, $oldPassword, $newPassword);

	/**
	 * Update the moduleAccess of the user to match the rights he has
	 * throws UserAdminServiceException in case of error
	 * returns 1 if moduleAccess has been updated, else 0
	 */
	public function matchModuleAccessOnRights($principal, $userId);

	/**
	 * Get a ModuleAccess array which should be needed to use the admin rights,
	 * or the group rights (UGR) the user has
	 * throws UserAdminServiceException in case of error
	 * returns array
	 */
	public function getModuleAccessFromRights($principal, $userP);

	/**
	 * Lock user or list of user
	 * @param object: User or UserPList or UserList
	 * throws AuthorizationServiceException::OBJECT_IS_LOCKED if lock cannot be acquired by principal
	 * because it is already locked by someone else.
	 * If $object is a list, then lock should be acquired on all objects, not only partially.
	 * returns true if lock has been set for the first time,
	 * else returns false if lock has just been renewed by the same principal
	 */
	public function lock($principal, $object);

	/**
	 * UnLock user or list of user
	 * @param object: User or UserPList or UserList
	 */
	public function unLock($principal, $object);
	
	/**
	 * Fills a list with all group in which the user is participan, with his respective rights,
	 * each group is associated with the principal rights
	 * principal: authenticated user performing the operation
	 * userId: the user database id from which want the groups participants
	 * module: module in wich we opperate (for propagation)
	 * groupIds: all the groups that the P can currently reached, no check is done on the validity of those ids
	 * groupRPList: the GroupRP list to be filled
	 * listFilter: filter on the list; filters columns, does pagination and sorting
	 * includePropagation: if true then user rights are dynamically calculated based on propagation,
	 * else user rights are direct UGR only.
	 * returns the number of Group added to the list
	 * throws UserAdminServiceException in case of error
	 */
	public function getAllGroups($principal, $userId, $module, $groupIds, $groupRPList, $listFilter=null, $includePropagation=false);
	
	/**
	 * return true if a user is already defined in this wigiiNamespace
	 */
	public function doesWigiiNamespaceExist($principal, $wigiiNamespace);
	
	/**
	 * return true if a user already exist with this username
	 */
	public function doesUsernameExist($principal, $username);
	
	////////////////////////
	// OPERATION DELEGATION
	////////////////////////



	/**
	 * Looks for an existing user with this username in the client scope,
	 * returns a userP if found, else null.
	 * principal: authenticated user performing the operation
	 * fieldSelectorList : only selected fields will be filled
	 * throws UserAdminServiceException in case of error
	 */
	public function findUserForClient($principal, $username, $client, $fieldSelectorList=null);

	/**
	 * Looks for roles for one user (not loged yet)
	 * principal: authenticated service
	 */
	public function getUserRolesForClient($principal, $username, $client, $userList, $listFilter=null);

}
