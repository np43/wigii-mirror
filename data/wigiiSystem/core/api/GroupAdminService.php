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

/* wigii Group admin service interface
 * Created by CWE on 31 mai 09
 */
interface GroupAdminService
{
	/**
	 * getGroupsPath from groupPList
	 * groupPList / groupList / array list of groups / group ids we want to display the full path
	 * @return array[id_group]=array([id_group_parent]=>groupParentName,...,[id_group]=>[groupname]
	 * id_group represent each group id of the groupList 
	 * if the wigiiNamespace of a group is different than the principal wigiiNamespace the groupname is prefixed with "wigiiNamespace : "
	 * this method use a cache in a same execution.
	 */
	public function getGroupsPath($principal, $groupPList);
	public function resetGetGroupsPathCache();
	
	/**
	 * Inserts a new group or updates an existing group in the database
	 * principal: authenticated user performing the operation
	 * group: group object to save in the database
	 * fieldSelectorList : only selected fields will be updated
	 * throws GroupAdminServiceException in case of error
	 * precondition: Group fields are mandatory even if no change, GroupDetail fields are optional
	 * postcondition: group is in the database and group id is updated with database id
	 * returns 1 if persisted, else 0
	 */
	public function persistGroup($principal, $group, $fieldSelectorList=null);

	/**
	 * Deletes a group given its database ref
	 * principal: authenticated user performing the operation
	 * groupId: group database id we want to delete
	 * moveContentAndChildrenToParent: if false all content and children groups will be deleted else the children group will be attached to parent and elements linked to group too. Default to false.
	 * throws GroupAdminServiceException in case of error
	 * returns total number of groups deleted
	 */
	public function deleteGroup($principal, $groupId, $moveContentAndChildrenToParent=false);
	
	/**
	 * Deletes all groups created in a wigiiNamespace
	 * principal: authenticated user performing the operation, needs to be namespace creator
	 * wigiiNamespace: wigiiNamespace to delete
	 * throws UserAdminServiceException in case of error
	 * returns nb of deleted items
	 */
	public function deleteAllGroupsFromWigiiNamespace($principal, $wigiiNamespace);

	/**
	 * Fills a tree with all groups, associated with the principal rights,
	 * in which the current principal participates for the given module
	 * principal: authenticated user performing the operation
	 * module: the module for which we want the groups
	 * groupPTree: the GroupP tree to be filled
	 * listFilter: filter on the list; filters columns, does pagination and sorting
	 * returns the number of groups added to the tree
	 * throws GroupAdminServiceException in case of error
	 */
	public function getAllGroups($principal, $module, $groupPTree, $listFilter=null);

	/**
	 * Fills the valueList with the value of the fieldSelector of each groups defined
	 * in the groupLogExp. The groupList defined with the logExp is then extended with
	 * each child or each parent of those groups depending on the state of parentOrChildren.
	 * @param $parentOrChildren : int, 1, 0, -1, 2 (with parent, only groupList, with children, both)
	 * WARNING, this method needs the root principal to be able to access to details of groups
	 * without checking read rights. The principal is checked in the authorizationService.
	 * throws GroupAdminServiceException in case of error
	 * returns the number of values added to the list
	 */
	public function getSelectedGroupsField($principal, $fieldSelector, $groupLogExp, $parentOrChildren, $valueList);

	/**
	 * Fills the valueList with the value of the fieldSelector of each groups defined
	 * in the GroupList. The GroupList can also be a GroupPList. The groupList is extended with
	 * each child or each parent of those groups depending on the state of parentOrChildren.
	 * @param $parentOrChildren : int, 1, 0, -1, 2 (with parent, only groupList, with children, both)
	 * @param $groupLogExp: GroupLogExp filter the group selection according to logExp
	 * WARNING, this method needs the root principal to be able to access to details of groups
	 * without checking read rights. The principal is checked in the authorizationService.
	 * throws GroupAdminServiceException in case of error
	 * returns the number of values added to the list
	 */
	public function getGroupsField($principal, $fieldSelector, $groupList, $parentOrChildren, $valueList, $groupLogExp=null);

	/**
	 * Gets a group given it database id
	 * principal: authenticated user performing the operation
	 * fieldSelectorList : only selected fields will be filled
	 * countBlockedElements : optional boolean, if true then GroupDetail will contain the number of blocked elements 
	 * contained in this folder or one of its subfolders. Defaults to false.
	 * throws GroupAdminServiceException in case of error
	 * returns a GroupP or null if no group exists with this Id
	 */
	public function getGroup($principal, $groupId, $fieldSelectorList=null, $countBlockedElements=false);

	/**
	 * Gets a group given it database id
	 * principal: authenticated user performing the operation
	 * throws GroupAdminServiceException in case of error
	 * returns a Group instance without detail or null if not found
	 */
	public function getGroupWithoutDetail($principal, $groupId);

	/**
	 * Fills a list of groups given an array of database ids
	 * principal: authenticated user performing the operation
	 * groupIds : an array of group database ids to fetch
	 * groupList: the GroupList to be filled, group instance do not have detail instanciated.
	 * throws GroupAdminServiceException in case of error
	 */
	public function getGroupsWithoutDetail($principal, $groupIds, $groupList);

	/**
	 * Fills a list of groups given group selection LogExp
	 * principal: authenticated user performing the operation
	 * groupSelectionLogExp : can be one groupSelectionLogExp or a combination of LogExpInGroup and LogExpNotInGroup
	 * groupList: the GroupList to be filled, group instance do not have detail instanciated.
	 * throws GroupAdminServiceException in case of error
	 */
	public function getSelectedGroupsWithoutDetail($principal, $groupSelectionLogExp, $groupList);
	
	/**
	 * Fills a list of groups, including children, given a parent group selection LogExp and an optional children group filtering LogExp
	 * @param Principal $principal authenticated user performing the operation
	 * @param LogExp $parentGroupSelectionLogExp LogExp which selects the groups from which we will extract the children.
	 * @param GroupList $groupList the GroupList to be filled with all selected parent and children groups, without details. 
	 * @param LogExp $childrenGroupFilterLogExp an optional LogExp used to filter the selected children groups.
	 * @param LogExp $groupFilterLogExp an optional LogExp which filters resulting list of groups. 
	 * @throws GroupAdminServiceException in case of error
	 * @return the number of selected groups.
	 */
	public function getSelectedGroupsWithChildrenWithoutDetail($principal, $parentGroupSelectionLogExp, $groupList, $childrenGroupFilterLogExp=null, $groupFilterLogExp=null);

	/**
	 * Fills a list of groups given group selection LogExp
	 * principal: authenticated user performing the operation
	 * listFilter : the mandatory ListFilter with selection LogExp + optional FieldSelectorList, pagination and sorting
	 * groupPList: the GroupPList to be filled, group instance have detail instanciated if principal has at least read rights.
	 * returns the number of Groups added to the list
	 * throws GroupAdminServiceException in case of error
	 */
	public function getSelectedGroups($principal, $listFilter, $groupPList);

	/**
	 * Fills a list with all participants in a group, with their respective rights,
	 * each user is associated with the principal rights
	 * principal: authenticated user performing the operation
	 * groupId: the group database id on which we want the participants
	 * userRPList: the UserRP list to be filled
	 * listFilter: filter on the list; filters columns, does pagination and sorting
	 * includePropagation: if true then user rights are dynamically calculated based on propagation,
	 * else user rights are direct UGR only.
	 * returns the number of User added to the list
	 * throws GroupAdminServiceException in case of error
	 */
	public function getAllUsers($principal, $groupId, $userRPList, $listFilter=null, $includePropagation=false);

	/**
	 * Sets user rights on a group.
	 * principal: authenticated user performing the operation
	 * ugr: the UserGroupRight to persist
	 * throws GroupAdminServiceException in case of error
	 * returns 1 if persisted else 0
	 */
	public function setUserRight($principal, $ugr);

	/**
	 * Removes a participant from a group
	 * principal: authenticated user performing the operation
	 * throws GroupAdminServiceException in case of error
	 * returns 1 if participant is removed from group else 0
	 */
	public function removeUser($principal, $groupId, $userId);

	/**
	 * Removes all participants from a group
	 * @param Principal $principal authenticated user performing the operation. 
	 * The principal should have authorization to delete the group to be able to call this method. 
	 * @param int $groupId the group id for which to remove the UGRs
	 * @param boolean $includeChildrenGroups if true, then also removes all the UGRs from the children groups. Defaults to false.
	 * @throws GroupAdminServiceException in case of error
	 * @return int the number of participants removed.
	 */
	public function removeAllUsers($principal, $groupId, $includeChildrenGroups=false);
	
	/**
	 * Moves a group to connect it to a new parent group.
	 * New parent group should have same module. 
	 * If keep user group rights, then namespace should be equal, else the namespace is updated to be the new one.
	 * @param Principal $principal should be the root principal.
	 * @param int $groupId the ID of the group we want to move.
	 * @param int $newParentGroupId the group ID to which we want to attach the group.
	 * @param boolean $keepUserGroupRights if true, then existing User Group Rights are kept for all children groups,
	 * else existing rights a removed. Default to false.
	 * @throws GroupAdminServiceException in case of error.
	 * @return the group without details, with new parent id.
	 */
	public function moveGroup($principal, $groupId, $newParentGroupId, $keepUserGroupRights=false);
	
	/**
	 * Set a specific list of email to the emailNotification field of a group
	 * The only right needed is to have a ugr set (or inherited)
	 * @param emails, string with emails separated by a sep (;,: /\n)
	 */
	public function addToEmailNotification($principal, $groupId, $emails);
	/**
	 * Unset a specific list of email of the emailNotification field of a group
	 * The only right needed is to have a ugr set (or inherited)
	 * @param emails, string with emails separated by a sep (;,: /\n)
	 */
	public function eraseFromEmailNotification($principal, $groupId, $emails);
	
	/**
	 * Set a specific email in the emailNotification field for groups
	 * The only right needed is to have a ugr set (or inherited)
	 * WARNING: No lock is performed on the groups.
	 * @param groupIds, array of groupId
	 * @param email, string with an email
	 * return the number of affected items
	 */
	public function addEmailNotificationToGroups($principal, $groupIds, $email);
	/**
	 * Unset a specific email in the emailNotification field for groups
	 * The only right needed is to have a ugr set (or inherited)
	 * WARNING: No lock is performed on the groups.
	 * @param groupIds, array of groupId
	 * @param email, string with an email
	 * return number of affected items
	 */
	public function eraseEmailNotificationToGroups($principal, $groupIds, $email);
	
	/**
	 * Sets the description of a group. the only right needed is canModify && canWriteElement on
	 * this group (or inherited)
	 */
	public function setGroupDescription($principal, $groupId, $description);
	/**
	 * Sets the htmlContent of a group. the only right needed is canWriteElement on
	 * this group (or inherited)
	 */
	public function setHtmlContent($principal, $groupId, $value);
	/**
	 * Sets the Portal of a group. the only right needed is canWriteElement on
	 * this group (or inherited)
	 */
	public function setPortal($principal, $groupId, $value);
	/**
	 * Sets the EmailNotification of a group. the right needed is canModify on
	 * this group (or inherited)
	 */
	public function setEmailNotification($principal, $groupId, $value);
	/**
	 * Sets the XmlPublish of a group. the right needed is canModify on
	 * this group (or inherited)
	 */
	public function setXmlPublish($principal, $groupId, $value);
	/**
	 * Sets the Subscription of a group. the right needed is canModify on
	 * this group (or inherited)
	 */
	public function setSubscription($principal, $groupId, $value);

	/**
	 * Lock group or list of group
	 * @param object: Group or GroupPList or GroupList
	 * throws AuthorizationServiceException::OBJECT_IS_LOCKED if lock cannot be acquired by principal
	 * because it is already locked by someone else.
	 * If $object is a list, then lock should be acquired on all objects, not only partially.
	 * returns true if lock has been set for the first time,
	 * else returns false if lock has just been renewed by the same principal
	 */
	public function lock($principal, $object);

	/**
	 * UnLock group or list of group
	 * @param object: Group or GroupPList or GroupList
	 */
	public function unLock($principal, $object);
	
	
	// Helpers
	
	
	/**
	 * Gets an existing direct subgroup given its name, or creates it if not exists.
	 * WARNING: The groupName should point to a unique sub group.
	 * @param Principal $principal authenticated user performing the operation
	 * @param Int $parentGroupId the id of the parent group for which to get the first sub group
	 * @param String $groupName the name of the group to fetch or to create. Should be unique.
	 * @return GroupP the groupP model wrapping the fetched or created group.
	 * @throws GroupAdminServiceException in case of error. 
	 */
	public function getOrCreateSubGroupByName($principal, $parentGroupId, $groupName);
}
