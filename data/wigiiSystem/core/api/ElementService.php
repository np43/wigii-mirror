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
 * Wigii core service to manage elements
 * Created by CWE on 1 juin 09
 * Modified by CWE on 3 février 2014 to add support of sub elements
 */
interface ElementService
{
	/**
	 * Fills a list with all elements accessible by this user in the given group
	 * principal: authenticated user performing the operation
	 * group: the group from which we want to retrieve elements
	 * includeChildrenGroups: if true then includes also elements in children groups accessible by principal
	 * listFilter: filter on the list; filters columns, does pagination and sorting
	 * elementPList: list of elements, associated to principal rights, to be filled
	 * returns the number of elements added to the list
	 * throws ElementServiceException if an error occurs
	 */
	public function getAllElementsInGroup($principal, $group, $elementPList, $includeChildrenGroups=true, $listFilter=null);

	/**
	 * Fills a wigiiBag with all element values accessible by this user in the given group
	 * principal: authenticated user performing the operation
	 * group: the group from which we want to retrieve elements
	 * includeChildrenGroups: if true then includes also elements in children groups accessible by principal
	 * listFilter: filter on the list; filters columns, does pagination and sorting
	 * wigiiBag: wigiiBag to be filled
	 * returns the number of elements fetched
	 * throws ElementServiceException if an error occurs
	 */
	public function fillWigiiBagWithAllElementsInGroup($principal, $group, $wigiiBag, $includeChildrenGroups=true, $listFilter=null);

	/**
	 * Fills a list with all elements accessible by this user in the given list of group
	 * principal: authenticated user performing the operation
	 * groupList: the group list from which we want to retrieve elements
	 * includeChildrenGroups: if true then includes also elements in children groups accessible by principal
	 * listFilter: filter on the list; filters columns, does pagination and sorting
	 * elementPList: list of elements, associated to principal rights, to be filled
	 * returns the number of elements added to the list
	 * throws ElementServiceException if an error occurs
	 */
	public function getAllElementsInGroups($principal, $groupList, $elementPList, $includeChildrenGroups=true, $listFilter=null);

	/**
	 * Fills a wigiiBag with all elements accessible by this user in the given list of group
	 * principal: authenticated user performing the operation
	 * groupList: the group list from which we want to retrieve elements
	 * includeChildrenGroups: if true then includes also elements in children groups accessible by principal
	 * listFilter: filter on the list; filters columns, does pagination and sorting
	 * wigiiBag: wigiiBag to be filled
	 * returns the number of elements fetched
	 * throws ElementServiceException if an error occurs
	 */
	public function fillWigiiBagWithAllElementsInGroups($principal, $groupList, $wigiiBag, $includeChildrenGroups=true, $listFilter=null);

	/**
	 * Fills a list with all elements which id is in the ids list and accessible by this user
	 * principal: authenticated user performing the operation
	 * elementIds: element ids array
	 * elementPList: list of elements, associated to principal rights, to be filled
	 * listFilter: filter on the list; filters columns, does pagination and sorting
	 * returns the number of elements added to the list
	 * throws ElementServiceException if an error occurs
	 */
	public function getSelectedElements($principal, $elementIds, $elementPList, $listFilter=null);
	
	/**
	 * Fills a list with all elements satisfying group belonging logical expression and accessible by this user
	 * @param Principal $principal authenticated user performing the operation
	 * @param LogExpInGroup|LogExpNotInGroup|LogExp $inGroupLogExp a logical expression composed of LogExpInGroup and LogExpNotInGroup
	 * 				  expressing element in group belonging and group selection
	 * @param ListFilter $listFilter filter on the list; filters columns, does pagination and sorting
	 * @param ElementPList $elementPList list of elements, associated to principal rights, to be filled
	 * @return int the number of elements added to the list
	 * @throws ElementServiceException if an error occurs
	 */
	public function getSelectedElementsInGroups($principal, $inGroupLogExp, $elementPList, $listFilter=null);

	/**
	 * Fills a wigiiBag with all element values satisfying group belonging logical expression and accessible by this user
	 * principal: authenticated user performing the operation
	 * inGroupLogExp: a logical expression composed of LogExpInGroup and LogExpNotInGroup
	 * 				  expressing element in group belonging and group selection
	 * listFilter: filter on the list; filters columns, does pagination and sorting
	 * wigiiBag: wigiiBag to be filled
	 * returns the number of elements fetched
	 * throws ElementServiceException if an error occurs
	 */
	public function fillWigiiBagWithSelectedElementsInGroups($principal, $inGroupLogExp, $wigiiBag, $listFilter=null);
	
	/**
	 * Creates a database table with all element values satisfying group belonging logical expression and accessible by this user
	 * principal: authenticated user performing the operation
	 * inGroupLogExp: a logical expression composed of LogExpInGroup and LogExpNotInGroup
	 * 				  expressing element in group belonging and group selection
	 * listFilter: filter on the list; filters columns, does pagination and sorting
	 * dbTable: the database table to be created and filled
	 * returns the number of elements fetched
	 * throws ElementServiceException if an error occurs
	 */
	public function createDbTableWithSelectedElementsInGroups($principal, $inGroupLogExp, $dbTable, $listFilter=null);
	
	/**
	 * Fills a database table with all element values satisfying group belonging logical expression and accessible by this user
	 * principal: authenticated user performing the operation
	 * inGroupLogExp: a logical expression composed of LogExpInGroup and LogExpNotInGroup
	 * 				  expressing element in group belonging and group selection
	 * listFilter: filter on the list; filters columns, does pagination and sorting
	 * dbTable: the database table to be filled
	 * returns the number of elements fetched
	 * throws ElementServiceException if an error occurs
	 */
	public function fillDbTableWithSelectedElementsInGroups($principal, $inGroupLogExp, $dbTable, $listFilter=null);
	
	/**
	 * Create an array with each group id containing elements as a key and the nb of containing elements as a value
	 * principal: authenticated user performing the operation
	 * inGroupLogExp: a logical expression composed of LogExpInGroup and LogExpNotInGroup
	 * 				  expressing element in group belonging and group selection
	 * elementIds: filter on the element ids
	 * throws ElementServiceException in case of error
	 * returns array(groupId=>nbElementInGroup)
	 */
	public function countElementsInGroups($principal, $inGroupLogExp, $elementIds);
	
	/**
	 * Count all elements satisfying group belonging logical expression and accessible by this user
	 * @param Principal principal: authenticated user performing the operation
	 * @param InGroupLogExp inGroupLogExp: a logical expression composed of LogExpInGroup and LogExpNotInGroup
	 * 				  expressing element in group belonging and group selection
	 * @param ListFilter listFilter: filter on the list; filters columns, does pagination and sorting
	 * @return the number of elements selected
	 * throws ElementServiceException if an error occurs
	 */
	public function countSelectedElementsInGroups($principal, $inGroupLogExp, $listFilter=null);

	/**
	 * Counts all elements accessible by this user in the given list of group
	 * principal: authenticated user performing the operation
	 * groupList: the group list from which we want to retrieve elements
	 * includeChildrenGroups: if true then includes also elements in children groups accessible by principal
	 * listFilter: filter on the list; filters columns, does pagination and sorting
	 * returns the number of selected elements
	 * throws ElementServiceException if an error occurs
	 */
	public function countAllElementsInGroups($principal, $groupList, $includeChildrenGroups=true, $listFilter=null);
	
	/**
	 * Gets or refreshes an element with data from the database.
	 * principal: authenticated user performing the operation
	 * element : an element instance to fill
	 * fieldSelectorList : only selected fields will be filled
	 * throws ElementServiceException in case of error
	 * returns an ElementP wrapping the element with principal rights or null if no element with this id.
	 * precondition: WigiiBag and FieldList instances must have been set in element
	 */
	public function fillElement($principal, $element, $fieldSelectorList=null);

	/**
	 * Fills a list with all groups having access to this element
	 * principal: authenticated user performing the operation
	 * element: the element for which we want to retrieve groups
	 * groupPList: the list of groups, associated to principal rights, to be filled
	 * listFilter: filter on the list; filters columns, does pagination and sorting
	 * returns the number of groups added to the list
	 * throws ElementServiceException if an error occurs
	 */
	public function getAllGroupsContainingElement($principal, $element, $groupPList, $listFilter=null);
	
	/**
	 * getGroupsPathContainingElement
	 * principal: authenticated user performing the operation
	 * elmentP / element: the element for which we want to retrieve groups
	 * @return array[id_group]=array([id_group_parent]=>groupParentName,...,[id_group]=>[groupname]
	 * id_group is the group id of the group containing elements (can be multiple) 
	 * if the wigiiNamespace of a group is different than the principal wigiiNamespace the groupname is prefixed with "wigiiNamespace : "
	 */
	public function getGroupsPathContainingElement($principal, $elementP);
	
	/**
	 * Fills a list with all groups having access to those elements
	 * @param Principal $principal authenticated user performing the operation
	 * @param ElementPAdvancedList $elementPAdvancedList the element list for which we want to retrieve groups
	 * @param GroupList $groupList the list of groups to be filled
	 * @param ListFilter $listFilter filters the list of groups on the list, does pagination and sorting (fieldSelector is not took in consideration)
	 * @return int the number of groups added to the list
	 * @throws ElementServiceException if an error occurs
	 */
	public function getAllGroupsWithoutDetailContainingElements($principal, $elementPAdvancedList, $groupList, $listFilter=null);

	/**
	 * Fills a list with the groups that all elements are into. Any group that would contain only a part of those element
	 * will not be listed
	 * principal: authenticated user performing the operation
	 * elementPAdvancedList: the element list for which we want to retrieve groups
	 * groupList: the list of groups to be filled
	 * listFilter: fieldSelector is not took in consideration, filter on the list, does pagination and sorting
	 * returns the number of groups added to the list
	 * throws ElementServiceException if an error occurs
	 */
	public function getGroupsWithoutDetailContainingAllElements($principal, $elementPAdvancedList, $groupList, $listFilter=null);
	
	/**
	 * Fills the valueList with the value of the fieldSelector of each groups containing this element
	 * The groupList is extended with each child or each parent of those groups depending on the state of parentOrChildren.
	 * @param $parentOrChildren : int, 1, 0, -1 (with parent, only groups containing element, with children)
	 * @param $groupLogExp : GroupLogExp if filled, filter the find groups
	 * WARNING, this method needs the root principal to be able to access to details of groups
	 * without checking read rights. The principal is checked in the authorizationService.
	 * throws ElementServiceException in case of error
	 * returns the number of values added to the list
	 */
	public function getFieldFromGroupsContainingElement($principal, $fieldSelector, $element, $parentOrChildren, $valueList, $groupLogExp=null);

	/**
	 * Fills the valueList with the value of the fieldSelector of each groups containing those elements
	 * The groupList is extended with each child or each parent of those groups depending on the state of parentOrChildren.
	 * @param $parentOrChildren : int, 1, 0, -1 (with parent, only groups containing element, with children)
	 * @param $groupLogExp : GroupLogExp if filled, filter the find groups
	 * WARNING, this method needs the root principal to be able to access to details of groups
	 * without checking read rights. The principal is checked in the authorizationService.
	 * throws ElementServiceException in case of error
	 * returns the number of values added to the list
	 */
	public function getFieldFromGroupsContainingElements($principal, $fieldSelector, $elementPAdvancedList, $parentOrChildren, $valueList, $groupLogExp=null);

	/**
	 * Share element with provided groups
	 * principal: authenticated user performing the operation
	 * elementId: element database id from which to retrieve the element to share
	 * groupIds: an array of Group IDs with which the element is shared
	 * throws ElementServiceException if an error occurs
	 * returns total number of changes
	 */
	public function shareElement($principal, $elementId, $groupIds);

	/**
	 * Stops sharing the element with the provided groups.
	 * If element was not shared in group then does nothing.
	 * principal: authenticated user performing the operation
	 * elementId: element database id from which to retrieve the element to remove shares
	 * groupIds: an array of Group IDs with which the element should not be shared anymore
	 * $preventRemovingLastSharingInWritableGroup if (default) yes This method prevent removing all sharing within the writable groups of the user
	 * if false then the system will not check if the remove of sharing will make the element no more accessible from the user (this is usefull in the case of removing sharing when sending to trashBin)
	 * throws ElementServiceException if an error occurs
	 * 		if all shares are removed for this element then an ElementServiceException with code DATA_INTEGRITY_ERROR is thrown
	 * 		because an element should be shared at least with one group.
	 * returns total number of changes
	 */
	public function unshareElement($principal, $elementId, $groupIds, $preventRemovingLastSharingInWritableGroup=true);

	/**
	 * Updates the elements sharing to match exactly the list of provided groups
	 * rootPrincipal : ROOTPRINCIPAL
	 * principal: authenticated user performing the operation
	 * ElementPAList: already checked element list
	 * groupIds: an array of Group IDs,
	 * all previous shares which are not in the list are deleted (only on write groups of Principal)
	 * throws ElementServiceException if an error occurs
	 * returns total number of changes
	 */
	public function setMultipleElementSharing($rootPrincipal, $principal, $elementPAList, $groupIds);
	
	/**
	 * Move the elements to the provided groups. The module of the elements is updated with the newModule.
	 * rootPrincipal : ROOTPRINCIPAL, to trust ElementPAList and groupIds
	 * principal: authenticated user performing the operation
	 * ElementPAList: element list, already checked 
	 * groupIds: destination groups
	 * all previous shares are deleted (if some share are in read groups throw exceptions)
	 * throws ElementServiceException if an error occurs
	 * returns total number of changes
	 */
	public function moveMultipleElementToModule($rootPrincipal, $principal, $elementPAList, $newModule, $groupIds);

	/**
	 * Adds some sharings to multiple elements
	 * rootPrincipal : ROOTPRINCIPAL
	 * principal: authenticated user performing the operation
	 * ElementPAList: already checked element list
	 * groupIds: an array of Group IDs,
	 * adds only non already existing shares, does not remove any share.
	 * throws ElementServiceException if an error occurs
	 * returns total number of changes
	 */
	public function addMultipleElementSharing($rootPrincipal, $principal, $elementPAList, $groupIds);
	
	/**
	 * Remove the groups sharings to multiple elements
	 * rootPrincipal : ROOTPRINCIPAL
	 * principal: authenticated user performing the operation
	 * ElementPAList: already checked element list
	 * groupIds: an array of Group IDs,
	 * $preventRemovingLastSharingInWritableGroup if (default) yes This method prevent removing all sharing within the writable groups of the user
	 * if false then the system will not check if the remove of sharing will make the element no more accessible from the user (this is usefull in the case of removing sharing when sending to trashBin)
	 * remove only existing shares with those groups, does not remove other shares.
	 * throws ElementServiceException if an error occurs
	 * 		if all shares are removed for this element then an ElementServiceException with code DATA_INTEGRITY_ERROR is thrown
	 * 		because an element should be shared at least with one group.
	 * returns total number of changes
	 */
	public function removeMultipleElementSharing($rootPrincipal, $principal, $elementPAList, $groupIds, $preventRemovingLastSharingInWritableGroup=true);

	/**
	 * Inserts a new element in the given group
	 * If a wigii bag is linked to the element, then all its values are stored in the db
	 * principal: authenticated user performing the operation
	 * element: element object to save in the database
	 * groupId: db id of the group to which to add the element
	 * fieldSelectorList : only selected fields will be inserted
	 * throws ElementServiceException in case of error
	 * postcondition: element is in the database and element id is updated with database id
	 * returns 1 if persisted, else 0
	 */
	public function insertElement($principal, $element, $groupId, $fieldSelectorList=null);

	/**
	 * Updates an existing element in the database
	 * If a wigii bag is linked to the element, then all its values are stored in the db
	 * principal: authenticated user performing the operation
	 * element: element object to save in the database
	 * fieldSelectorList : only selected fields will be updated
	 * throws ElementServiceException in case of error
	 * postcondition: element is updated in the database
	 * returns 1 if persisted, else 0
	 */
	public function updateElement($principal, $element, $fieldSelectorList=null);

	/**
	 * Updates existing elements in the database
	 * All the values of the WigiiBag are stored in the db
	 * ROOTPRINCIPAL: root principal, to trust ElementPAList
	 * principal: authenticated user performing the operation
	 * elementPAList: element list object to save in the database
	 * fieldSelectorList : mandatory, only selected fields will be updated
	 * throws ElementServiceException in case of error
	 * postcondition: elements are updated in the database
	 * returns the number of Element persisted, else 0
	 */
	public function updateMultipleElement($rootPrincipal, $principal, $elementPAList, $fieldSelectorList);

	/**
	 * Import new elements in the database
	 * All the values of the WigiiBag are stored in the db
	 * ROOTPRINCIPAL: root principal, to trust ElementPAList and GroupP
	 * principal: authenticated user performing the operation
	 * elementPAList: element list object to save in the database
	 * GroupP: group in which the importation will be done
	 * fieldSelectorList : mandatory, only selected fields will be updated
	 * throws ElementServiceException in case of error
	 * postcondition: elements are inserted in the database
	 * returns the number of Element persisted, else 0
	 */
	public function insertMultipleElement($rootPrincipal, $principal, $groupP, $elementPAList, $fieldSelectorList);

	/**
	 * Updates the element state
	 * this will be done only on one specific element, and only if principal canWriteElement
	 * the sys_date and sys_user is updated too
	 */
	public function setState_locked($principal, $elementId, $checked, $message=null);
	public function setState_blocked($principal, $elementId, $checked, $message=null);
	public function setState_important1($principal, $elementId, $checked, $message=null);
	public function setState_important2($principal, $elementId, $checked, $message=null);
	public function setState_finalized($principal, $elementId, $checked, $message=null);
	public function setState_approved($principal, $elementId, $checked, $message=null);
	public function setState_dismissed($principal, $elementId, $checked, $message=null);
	public function setState_archived($principal, $elementId, $checked, $message=null);
	public function setState_deprecated($principal, $elementId, $checked, $message=null);
	public function setState_hidden($principal, $elementId, $checked, $message=null);
	public function toggleState_locked($principal, $elementId, $message=null);
	public function toggleState_blocked($principal, $elementId, $message=null);
	public function toggleState_important1($principal, $elementId, $message=null);
	public function toggleState_important2($principal, $elementId, $message=null);
	public function toggleState_finalized($principal, $elementId, $message=null);
	public function toggleState_approved($principal, $elementId, $message=null);
	public function toggleState_dismissed($principal, $elementId, $message=null);
	public function toggleState_archived($principal, $elementId, $message=null);
	public function toggleState_deprecated($principal, $elementId, $message=null);
	public function toggleState_hidden($principal, $elementId, $message=null);
	
	/**
	 * Updates the element state for a list of element
	 * if checked is null, then the final value will be true, except if all elements
	 * are already at true. In that case the final value will be false.
	 * this will be done only if principal canWriteElement
	 * the sys_date and sys_user is updated too
	 * @return ElementPList
	 */
	public function setMultipleState_locked($principal, $elementIds, $checked, $message=null);
	public function setMultipleState_blocked($principal, $elementIds, $checked, $message=null);
	public function setMultipleState_important1($principal, $elementIds, $checked, $message=null);
	public function setMultipleState_important2($principal, $elementIds, $checked, $message=null);
	public function setMultipleState_finalized($principal, $elementIds, $checked, $message=null);
	public function setMultipleState_approved($principal, $elementIds, $checked, $message=null);
	public function setMultipleState_dismissed($principal, $elementIds, $checked, $message=null);
	public function setMultipleState_archived($principal, $elementIds, $checked, $message=null);
	public function setMultipleState_deprecated($principal, $elementIds, $checked, $message=null);
	public function setMultipleState_hidden($principal, $elementIds, $checked, $message=null);
	
	/**
	 * Update all the element sys_date to now
	 * @return ElementPList
	 */
	public function updateSys_dateToNow($principal, $elementIds);

	/**
	 * Deletes an element given its database reference
	 * principal: authenticated user performing the operation
	 * elementId: element database id we want to delete
	 * throws ElementServiceException in case of error
	 * returns 1 if element has been deleted from this user perspective, else 0
	 */
	public function deleteElement($principal, $elementId);

	/**
	 * Deletes a list of elements given their database references
	 * rootPrincipal: ROOT PRINCIPAL needed to trust the elementPAdvancedLIst
	 * principal: authenticated user performing the operation
	 * elementPAdvancedList: an element list with elements to delete
	 * throws ElementServiceException in case of error
	 * returns the number of elements deleted
	 */
	public function deleteMultipleElements($rootPrincipal, $principal, $elementPAdvancedList);

	/**
	 * Search for elements in the database
	 * principal: authenticated user performing the operation
	 * activityRecord: user input form with search criterias and specified search activity to execute
	 * 				search engine will be selected according to activity
	 * elementListFilter: specification on the result set, such as pagination, element columns filtering and sorting
	 * elementSearchResultList: the element search result to fill
	 * throws ElementServiceException in case of error
	 * returns the number of elements added to the list
	 */
	public function searchElements($principal, $activityRecord, $elementListFilter, $elementSearchResultList);

	/**
	 * Lock element or list of element
	 * @param object: Element or ElementPList or ElementList
	 * throws AuthorizationServiceException::OBJECT_IS_LOCKED if lock cannot be acquired by principal
	 * because it is already locked by someone else.
	 * If $object is a list, then lock should be acquired on all objects, not only partially.
	 * returns true if lock has been set for the first time,
	 * else returns false if lock has just been renewed by the same principal
	 */
	public function lock($principal, $object);

	/**
	 * UnLock element or list of element
	 * @param object: Element or ElementPList or ElementList
	 */
	public function unLock($principal, $object);

	/**
	 * returns an array with key as element id and value basic element field if element exists in DB
	 */
	public function doesElementExists($principal, $elementId);

	/**
	 * returns an array with key as element id and value basic element field if element exists in DB
	 */
	public function doElementsExist($principal, $elementPAdvancedList);

	/**
	 * Evaluates all calculated fields (i.e. with an attached funcExp) in the whole list
	 * using the given ElementEvaluator.
	 * elementList can also be an elementPList.
	 */
	public function evaluateElementList($principal, $elementList, $elementEvaluator);

	/**
	 * Evaluates all calculated fields in all elements in selected groups using the given ElementEvaluator.
	 * Database is directly updated.
	 * principal executing the operation, he should have write rights on all elements selected
	 * the evaluator for this principal will be used. (depends on config)
	 * inGroupLogExp: a logical expression composed of LogExpInGroup and LogExpNotInGroup
	 * 				  expressing element in group belonging and group selection
	 * throws ElementServiceException if an error occurs
	 */
	public function evaluateSelectedElementsInGroups($principal, $inGroupLogExp);
	
	/**
	 * Evaluate the indicator in all elements in groups matching the listFilter
	 */
	public function evaluateIndicatorOnElementsInGroups($principal, $indicator, $inGroupLogExp, $listFilter=null);
	
	
	// Sub elements
	
	/**
	 * Fills a LinkSelectorList with the path from the root element to the provided sub element
	 * @param Principal $principal authenticated user performing the operation 
	 * @param Scalar $subElementId the sub element id for which to retrieve the path
	 * @param LinkSelectorList $linkSelectorList the LinkSelectorList instance to be filled with the path from root to sub element.
	 * The list will be of the form :
	 * linkSelectorList(linkSelector(rootElementId, moduleName, fieldName), linkSelector(subElement1Id, moduleName, fieldName), ...)
	 * The list does not contain the sub element itself, but stops on its direct parent.
	 * @return int the depth of the path or 0 if subElementId is not a sub element but a root element.
	 */
	public function getSubElementPathFromRoot($principal, $subElementId, $linkSelectorList);	
	
	/**
	 * Fills a list with all the sub elements of an element for a given field of type Links/subitem and accessible by this user
	 * @param Principal $principal authenticated user performing the operation
	 * @param Scalar $masterElementId the master element id for which to fetch the sub elements
	 * @param String $fieldName the field in the master element of type Links/subitem for which to fetch the sub elements
	 * @param ListFilter $listFilter filter on the list; filters columns, does pagination and sorting
	 * @param ElementPList $elementPList list of elements, associated to principal rights, to be filled
	 * @return int the number of elements added to the list
	 * @throws ElementServiceException if an error occurs
	 */
	public function getSubElementsForField($principal, $masterElementId, $fieldName, $elementPList, $listFilter=null);
	
	/**
	 * Fills a list with all the sub elements linked to root elements satisfying group belonging logical expression and 
	 * satisfying SubElementSelector conditions (such as Module selection or subelement configuration path) and accessible by this user.
	 * @param Principal $principal authenticated user performing the operation
	 * @param LogExpInGroup|LogExpNotInGroup|LogExp $inGroupLogExp a logical expression composed of LogExpInGroup and LogExpNotInGroup
	 * 				  expressing root element in group belonging and group selection.
	 * @param SubElementSelector $subElementSelector the sub element selector object specifying the sub element configuration path or the selected module.
	 * @param ListFilter $listFilter filter on the list; filters columns, does pagination and sorting
	 * @param ElementPList $elementPList list of elements, associated to principal rights, to be filled
	 * @return int the number of elements added to the list
	 * @throws ElementServiceException if an error occurs
	 */
	public function getSelectedSubElements($principal, $inGroupLogExp, $subElementSelector, $elementPList, $listFilter=null);
	
	/**
	 * Inserts a new element as a sub element of the given master element
	 * If a wigii bag is linked to the element, then all its values are stored in the db	 
	 * @param Principal $principal authenticated user performing the operation
	 * @param Scalar $masterElementId the master element id for which to add the sub element
	 * @param String $fieldName the field in the master element of type Links/subitem for which to add the sub element
	 * @param Element $element element object to save in the database as a sub element	 
	 * @param FieldSelectorList $fieldSelectorList only selected fields will be inserted
	 * @throws ElementServiceException in case of error
	 * @return 1 if persisted, else 0
	 * postcondition: element is in the database, element id is updated with database id and sub element info are filled (id_element_parent, linkName)	 
	 */
	public function insertSubElement($principal, $masterElementId, $fieldName, $element, $fieldSelectorList=null);

	/**
	 * Renames the link to which the sub elements are attached.
	 * @param Principal $principal authenticated user performing the operation
	 * @param Array|Int $subElementIds an array of subelements ids or one id.
	 * @param String $newLinkName the new link name.
	 * Only updates the database, the configurator should update the link name in the parent config 
	 * if he wants the subelements to be displayed.
	 * @param String $searchToken an optional String. If defined, then the newLinkName will replace all the occurrences of searchToken in the existing link name. 
	 * Example 1: existing link name = "children"
	 * call renameSubElementsLink($p,array(123, 456),'__deleted_children') will result in link name = "__deleted_children".
	 * Example 2: existing link name = "__deleted_children"
	 * call renameSubElementsLink($p,array(123, 456),'','__deleted_') will result in link name = "children".
	 * @throws ElementServiceException in case of error
	 */
	public function renameSubElementsLink($principal, $subElementIds, $newLinkName, $searchToken=null);
}



