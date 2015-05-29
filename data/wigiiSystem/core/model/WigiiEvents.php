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

/*
 * Created on 19 nov. 09
 * by LWR
 */

interface WigiiEvents {

	/**
	 * readElement
	 * this event is thrown each time an element is displayed in a detail window
	 */
	public function readElement($pWithElement);

	/**
	 * download file
	 * this event is throw each time a file is downloaded from an element
	 * this does not inclued the download of the thumbnail.
	 */
	public function downloadFileFromElement($pWithElementWithField);

	/**
	 * insertElement
	 */
	public function insertElement($pWithElementWithGroup);

	/**
	 * updateElement
	 */
	public function updateElement($pWithElementWithGroupPList);

	/**
	 * change Element status
	 */
	public function updateElementState($pWithElement);

	/**
	 * deleteElement
	 * the groupPList contains the groups in which the element was
	 */
	public function deleteElement($pWithElementWithGroupPList);

	/**
	 * restoreElement
	 * the groupPList contains the groups in which the element is restored in
	 */
	public function restoreElement($pWithElementWithGroupPList);

	/**
	 * insertMultipleElement
	 */
	public function insertMultipleElement($pWithModuleWithElementPListWithGroupList);

	/**
	 * updateMultipleElement
	 */
	public function updateMultipleElement($pWithRecordWithElementPList);
	/**
	 * change multiple Element status
	 */
	public function updateMultipleElementState($pWithModuleWithElementPListWithState);

	/**
	 * deleteMultipleElement
	 */
	public function deleteMultipleElements($pWithModuleWithElementPListWithGroupListWithArray);

	/**
	 * sendFeedbackOnElement
	 */
	public function sendFeedbackOnElement($pWithElementWithRecord);

	/**
	 * sendFeedback
	 */
	public function sendFeedback($pWithModuleWithRecord);

	/**
	 * Emailing
	 * elementIds is an array in the form [elementId=>[fieldName=>email]]
	 */
	public function Emailing($pWithModuleWithElementIdsWithRecord);

	/**
	 * sendEmail
	 */
	public function sendEmail($pWithElementWithEmailWithFieldname);

	/**
	 * sendExternalValidationLink
	 */
	public function sendExternalValidationLink($pWithElementWithEmailWithFieldname);

	/**
	 * setExternalAccessViewLink
	 */
	public function setExternalAccessViewLink($pWithElementWithEmailWithFieldname);

	/**
	 * setExternalAccessEditLink
	 */
	public function setExternalAccessEditLink($pWithElementWithEmailWithFieldname);

	/**
	 * stopExternalAccessLink
	 */
	public function stopExternalAccessLink($pWithElementWithFieldname);

	/**
	 * sendMultipleExternalValidationLink
	 */
	public function sendMultipleExternalValidationLink($pWithModuleWithElementPlistWithEmailWithFieldname);

	/**
	 * setMultipleExternalAccessViewLink
	 */
	public function setMultipleExternalAccessViewLink($pWithModuleWithElementPlistWithEmailWithFieldname);

	/**
	 * setMultipleExternalAccessEditLink
	 */
	public function setMultipleExternalAccessEditLink($pWithModuleWithElementPlistWithEmailWithFieldname);

	/**
	 * stopMultipleExternalAccessLink
	 */
	public function stopMultipleExternalAccessLink($pWithModuleWithElementPlistWithFieldname);

	/**
	 * exportElements
	 */
	public function exportElements($pWithModuleWithElementIds);

	/**
	 * shareElement
	 */
	public function shareElement($pWithElementWithGroup);

	/**
	 * unshareElement
	 */
	public function unshareElement($pWithElementWithGroup);

	/**
	 * setMultipleElementSharing
	 */
	public function setMultipleElementSharing($WithModuleWithElementPListWithGroupList);

	/**
	 * moveMultipleElementToModule
	 * module is original module, destination module is group module
	 */
	public function moveMultipleElementToModule($WithModuleWithElementPListWithGroupList);

	/**
	 * addMultipleElementSharing
	 */
	public function addMultipleElementSharing($WithModuleWithElementPListWithGroupList);

	/**
	 * removeMultipleElementSharing
	 */
	public function removeMultipleElementSharing($WithModuleWithElementPListWithGroupList);

	/**
	 * navigate
	 */
	public function navigate($pWithUserIdWithWigiiNamespaceNameWithModuleName);

	/**
	 * selectGroup
	 */
	public function selectGroup($pWithGroupPList);

	/**
	 * switchLanguage
	 */
	public function switchLanguage($pWithUserWithLanguage);

	/**
	 * changePassword
	 */
	public function changePassword($pWithUser);

	/**
	 * login
	 */
	public function login($pWithUser);

	/**
	 * logout
	 */
	public function logout($pWithUser);

	/**
	 * insertGroup
	 */
	public function insertGroup($pWithGroup);

	/**
	 * updateGroup
	 */
	public function updateGroup($pWithGroup);

	/**
	 * deleteGroup
	 */
	public function deleteGroup($pWithGroup);

	/**
	 * insertUser
	 */
	public function insertUser($pWithUser);

	/**
	 * updateUser
	 */
	public function updateUser($pWithUser);

	/**
	 * deleteUser
	 */
	public function deleteUser($pWithUser);

}


