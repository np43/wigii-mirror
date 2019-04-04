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
 * Created on 19 nov. 09 by LWR
 * Modified by CWE on 14 février 2014 to add the support of MultiplexedEvent to pipe event dispatchers.
 */

class WigiiEventsDispatcher implements WigiiEvents, MultiplexedEvent, EventSubscriberList {

	private $_debugLogger;
	private $_executionSink;
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("WigiiEventsDispatcher");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("WigiiEventsDispatcher");
		}
		return $this->_executionSink;
	}

	public static function getInstance()
	{
		return TechnicalServiceProvider::getWigiiEventsDispatcher();
	}

	/*
	 * dependency injection
	 */
	private $eventSubscriberS;
	public function setEventSubscriberService($eventSubscriberService){
		$this->eventSubscriberS = $eventSubscriberService;
	}
	protected function getEventSubscriberService(){
		// autowired
		if(!isset($this->eventSubscriberS))
		{
			$this->eventSubscriberS = TechnicalServiceProvider::getEventSubscriberService();
		}
		return $this->eventSubscriberS;
	}

	/**
	 * addEventSubscriber
	 * @param object : Object
	 * @param multiplexedEvent : Bool
	 */
	private $eventsStack;
	protected function pushEventStackInstance($eventStack){
		if(!isset($this->eventsStack)){
			$this->eventsStack = array();
		}
		$this->eventsStack[] = $eventStack;
	}
	protected function pushEvent($eventName, $entityName, $module, $object){
		if(!isset($this->eventsStack)){
			$this->eventsStack = array();
		}
		$this->eventsStack[] = $this->createEventStackInstance($eventName, $entityName, $module, $object);
	}
	protected function popEvent(){
		if(!isset($this->eventsStack)) return null;
		return array_pop($this->eventsStack);
	}
	protected function getEvent(){
		if(!isset($this->eventsStack)) return null;
		return current($this->eventsStack);
	}
	protected function createEventStackInstance($eventName, $entityName, $module, $eventInfo){
		$r = EventStack::createInstance($eventName, $entityName, $module, $eventInfo);
		return $r;
	}
	public function addEventSubscriber($eventSubscriber, $multiplexedEvent){
		$e = $this->getEvent();
//		eput("addEventSubscriber".$e->getEventInfo()->getElement()->getFieldValue("first_name"));
		if($multiplexedEvent){
			$eventSubscriber->event($e->getEventName(), $e->getEntityName(), $e->getModule(), $e->getEventInfo());
		} else {
			$eventSubscriber->{$e->getEventName().$e->getEntityName()}($e->getEventInfo());
		}
	}
	protected function dispatchEvent($e)
	{
		$this->pushEventStackInstance($e);
		$this->getEventSubscriberService()->getAllSubscribersForEvent($e->getEventName(), $e->getEntityName(), $e->getModule(), $this);
		$this->popEvent();
	}


	// WigiiEvents

	/**
	 * readElement
	 * this event is thrown each time an element is displayed in a detail window
	 */
	public function readElement($pWithElement){
		$e = $this->createEventStackInstance("read", "Element", $pWithElement->getElement()->getModule(), $pWithElement);
		$this->dispatchEvent($e);
	}

	/**
	 * download file
	 * this event is throw each time a file is downloaded from an element
	 * this does not inclued the download of the thumbnail.
	 */
	public function downloadFileFromElement($pWithElementWithField){
		$e = $this->createEventStackInstance("download", "Element", $pWithElementWithField->getElement()->getModule(), $pWithElementWithField);
		$this->dispatchEvent($e);
	}

	/**
	 * insertElement
	 */
	public function insertElement($pWithElementWithGroup){
		$e = $this->createEventStackInstance("insert", "Element", $pWithElementWithGroup->getElement()->getModule(), $pWithElementWithGroup);
		$this->dispatchEvent($e);
	}

	/**
	 * change Element status
	 */
	public function updateElementState($pWithElementWithGroupPList){
		$e = $this->createEventStackInstance("setState", "Element", $pWithElementWithGroupPList->getElement()->getModule(), $pWithElementWithGroupPList);
		$this->dispatchEvent($e);
	}
	/**
	 * updateElement
	 */
	public function updateElement($pWithElementWithGroupPList){
		$e = $this->createEventStackInstance("update", "Element", $pWithElementWithGroupPList->getElement()->getModule(), $pWithElementWithGroupPList);
		$this->dispatchEvent($e);
	}

	/**
	 * deleteElement
	 */
	public function deleteElement($pWithElementWithGroupPList){
//		eput("deleteElement".$pWithElement->getElement()->getFieldValue("first_name"));
		$e = $this->createEventStackInstance("delete", "Element", $pWithElementWithGroupPList->getElement()->getModule(), $pWithElementWithGroupPList);
		$this->dispatchEvent($e);
	}

	/**
	 * restoreElement
	 */
	public function restoreElement($pWithElementWithGroupPList){
//		eput("deleteElement".$pWithElement->getElement()->getFieldValue("first_name"));
		$e = $this->createEventStackInstance("restore", "Element", $pWithElementWithGroupPList->getElement()->getModule(), $pWithElementWithGroupPList);
		$this->dispatchEvent($e);
	}

	/**
	 * insertMultipleElement
	 */
	public function insertMultipleElement($pWithModuleWithElementPListWithGroupList){
		$e = $this->createEventStackInstance("insert", "MultipleElement", $pWithModuleWithElementPListWithGroupList->getModule(), $pWithModuleWithElementPListWithGroupList);
		$this->dispatchEvent($e);
	}

	/**
	 * updateMultipleElement
	 */
	public function updateMultipleElement($pWithRecordWithElementPListWithGroupPList){
		$e = $this->createEventStackInstance("update", "MultipleElement", $pWithRecordWithElementPListWithGroupPList->getRecord()->getModule(), $pWithRecordWithElementPListWithGroupPList);
		$this->dispatchEvent($e);
	}

	/**
	 * change multiple Element status
	 */
	public function updateMultipleElementState($pWithModuleWithElementPListWithState){
		$e = $this->createEventStackInstance("setState", "MultipleElement", $pWithModuleWithElementPListWithState->getModule(), $pWithModuleWithElementPListWithState);
		$this->dispatchEvent($e);
	}

	/**
	 * deleteMultipleElement
	 */
	public function deleteMultipleElements($pWithModuleWithElementPListWithGroupListWithArray){
		$e = $this->createEventStackInstance("delete", "MultipleElement", $pWithModuleWithElementPListWithGroupListWithArray->getModule(), $pWithModuleWithElementPListWithGroupListWithArray);
		$this->dispatchEvent($e);
	}

	/**
	 * sendFeedbackOnElement
	 */
	public function sendFeedbackOnElement($pWithElementWithRecord){
		$e = $this->createEventStackInstance("sendFeedback", "Element", $pWithElementWithRecord->getElement()->getModule(), $pWithElementWithRecord);
		$this->dispatchEvent($e);
	}

	/**
	 * sendFeedback
	 */
	public function sendFeedback($pWithModuleWithRecord){
		$e = $this->createEventStackInstance("sendFeedback", "Module", $pWithModuleWithRecord->getModule(), $pWithModuleWithRecord);
		$this->dispatchEvent($e);
	}

	/**
	 * Emailing
	 */
	public function Emailing($pWithModuleWithElementIdsWithRecord){
		$e = $this->createEventStackInstance("Emailing", "MultipleElement", $pWithModuleWithElementIdsWithRecord->getModule(), $pWithModuleWithElementIdsWithRecord);
		$this->dispatchEvent($e);
	}


	/**
	 * sendEmail
	 */
	public function sendEmail($pWithElementWithEmailWithFieldname){
		$e = $this->createEventStackInstance("sendEmail", "Element", $pWithElementWithEmailWithFieldname->getElement()->getModule(), $pWithElementWithEmailWithFieldname);
		$this->dispatchEvent($e);
	}

	/**
	 * sendExternalValidationLink
	 */
	public function sendExternalValidationLink($pWithElementWithEmailWithFieldname){
		$e = $this->createEventStackInstance("sendExternalValidationLink", "Element", $pWithElementWithEmailWithFieldname->getElement()->getModule(), $pWithElementWithEmailWithFieldname);
		$this->dispatchEvent($e);
	}

	/**
	 * setExternalAccessViewLink
	 */
	public function setExternalAccessViewLink($pWithElementWithEmailWithFieldname){
		$e = $this->createEventStackInstance("setExternalAccessViewLink", "Element", $pWithElementWithEmailWithFieldname->getElement()->getModule(), $pWithElementWithEmailWithFieldname);
		$this->dispatchEvent($e);
	}

	/**
	 * setExternalAccessEditLink
	 */
	public function setExternalAccessEditLink($pWithElementWithEmailWithFieldname){
		$e = $this->createEventStackInstance("setExternalAccessEditLink", "Element", $pWithElementWithEmailWithFieldname->getElement()->getModule(), $pWithElementWithEmailWithFieldname);
		$this->dispatchEvent($e);
	}

	/**
	 * stopExternalAccessLink
	 */
	public function stopExternalAccessLink($pWithElementWithFieldname){
		$e = $this->createEventStackInstance("stopExternalAccessLink", "Element", $pWithElementWithFieldname->getElement()->getModule(), $pWithElementWithFieldname);
		$this->dispatchEvent($e);
	}

	/**
	 * sendMultipleExternalValidationLink
	 */
	public function sendMultipleExternalValidationLink($pWithModuleWithElementPlistWithEmailWithFieldname){
		$e = $this->createEventStackInstance("sendMultipleExternalValidationLink", "MultipleElement", $pWithModuleWithElementPlistWithEmailWithFieldname->getModule(), $pWithModuleWithElementPlistWithEmailWithFieldname);
		$this->dispatchEvent($e);
	}

	/**
	 * setMultipleExternalAccessViewLink
	 */
	public function setMultipleExternalAccessViewLink($pWithModuleWithElementPlistWithEmailWithFieldname){
		$e = $this->createEventStackInstance("setMultipleExternalAccessViewLink", "MultipleElement", $pWithModuleWithElementPlistWithEmailWithFieldname->getModule(), $pWithModuleWithElementPlistWithEmailWithFieldname);
		$this->dispatchEvent($e);
	}

	/**
	 * setMultipleExternalAccessEditLink
	 */
	public function setMultipleExternalAccessEditLink($pWithModuleWithElementPlistWithEmailWithFieldname){
		$e = $this->createEventStackInstance("setMultipleExternalAccessEditLink", "MultipleElement", $pWithModuleWithElementPlistWithEmailWithFieldname->getModule(), $pWithModuleWithElementPlistWithEmailWithFieldname);
		$this->dispatchEvent($e);
	}

	/**
	 * stopMultipleExternalAccessLink
	 */
	public function stopMultipleExternalAccessLink($pWithModuleWithElementPlistWithFieldname){
		$e = $this->createEventStackInstance("stopMultipleExternalAccessLink", "MultipleElement", $pWithModuleWithElementPlistWithFieldname->getModule(), $pWithModuleWithElementPlistWithFieldname);
		$this->dispatchEvent($e);
	}


	/**
	 * exportElements
	 */
	public function exportElements($pWithModuleWithElementIds){
		$e = $this->createEventStackInstance("exportElements", "MultipleElement", $pWithModuleWithElementIds->getModule(), $pWithModuleWithElementIds);
		$this->dispatchEvent($e);
	}

	/**
	 * shareElement
	 */
	public function shareElement($pWithElementWithGroup){
		$e = $this->createEventStackInstance("share", "Element", $pWithElementWithGroup->getElement()->getModule(), $pWithElementWithGroup);
		$this->dispatchEvent($e);
	}

	/**
	 * unshareElement
	 */
	public function unshareElement($pWithElementWithGroup){
		$e = $this->createEventStackInstance("unshare", "Element", $pWithElementWithGroup->getElement()->getModule(), $pWithElementWithGroup);
		$this->dispatchEvent($e);
	}

	/**
	 * setMultipleElementSharing
	 */
	public function setMultipleElementSharing($pWithModuleWithElementPListWithGroupList){
		$e = $this->createEventStackInstance("setShare", "MultipleElement", $pWithModuleWithElementPListWithGroupList->getModule(), $pWithModuleWithElementPListWithGroupList);
		$this->dispatchEvent($e);
	}
	/**
	 * addMultipleElementSharing
	 */
	public function addMultipleElementSharing($pWithModuleWithElementPListWithGroupList){
		$e = $this->createEventStackInstance("share", "MultipleElement", $pWithModuleWithElementPListWithGroupList->getModule(), $pWithModuleWithElementPListWithGroupList);
		$this->dispatchEvent($e);
	}
	/**
	 * removeMultipleElementSharing
	 */
	public function removeMultipleElementSharing($pWithModuleWithElementPListWithGroupList){
		$e = $this->createEventStackInstance("unshare", "MultipleElement", $pWithModuleWithElementPListWithGroupList->getModule(), $pWithModuleWithElementPListWithGroupList);
		$this->dispatchEvent($e);
	}
	/**
	 * moveMultipleElementToModule
	 * module is original module, destination module is group module
	 */
	public function moveMultipleElementToModule($pWithModuleWithElementPListWithGroupList){
		$e = $this->createEventStackInstance("moveToModule", "MultipleElement", $pWithModuleWithElementPListWithGroupList->getModule(), $pWithModuleWithElementPListWithGroupList);
		$this->dispatchEvent($e);
	}

	/**
	 * navigate
	 */
	public function navigate($pWithUserIdWithWigiiNamespaceWithModule){
		$e = $this->createEventStackInstance("navigate", "User", null, $pWithUserIdWithWigiiNamespaceWithModule);
		$this->dispatchEvent($e);
	}

	/**
	 * selectGroup
	 */
	public function selectGroup($pWithGroupPList){
		$e = $this->createEventStackInstance("selectGroup", "Group", null, $pWithGroupPList);
		$this->dispatchEvent($e);
	}

	/**
	 * switchLanguage
	 */
	public function switchLanguage($pWithUserWithLanguage){
		$e = $this->createEventStackInstance("switchLanguage", "User", null, $pWithUserWithLanguage);
		$this->dispatchEvent($e);
	}

	/**
	 * changePassword
	 */
	public function changePassword($pWithUser){
		$e = $this->createEventStackInstance("changePassword", "User", null, $pWithUser);
		$this->dispatchEvent($e);
	}

	/**
	 * login
	 */
	public function login($pWithUser){
		$e = $this->createEventStackInstance("login", "User", null, $pWithUser);
		$this->dispatchEvent($e);
	}

	/**
	 * logout
	 */
	public function logout($pWithUser){
		$e = $this->createEventStackInstance("logout", "User", null, $pWithUser);
		$this->dispatchEvent($e);
	}

	/**
	 * insertGroup
	 */
	public function insertGroup($pWithGroup){
		$e = $this->createEventStackInstance("insert", "Group", $pWithGroup->getGroup()->getModule(), $pWithGroup);
		$this->dispatchEvent($e);
	}

	/**
	 * updateGroup
	 */
	public function updateGroup($pWithGroup){
		$e = $this->createEventStackInstance("update", "Group", $pWithGroup->getGroup()->getModule(), $pWithGroup);
		$this->dispatchEvent($e);
	}

	/**
	 * deleteGroup
	 */
	public function deleteGroup($pWithGroup){
		$e = $this->createEventStackInstance("delete", "Group", $pWithGroup->getGroup()->getModule(), $pWithGroup);
		$this->dispatchEvent($e);
	}

	/**
	 * insertUser
	 */
	public function insertUser($pWithUser){
		$e = $this->createEventStackInstance("insert", "User", null, $pWithUser);
		$this->dispatchEvent($e);
	}

	/**
	 * updateUser
	 */
	public function updateUser($pWithUser){
		$e = $this->createEventStackInstance("update", "User", null, $pWithUser);
		$this->dispatchEvent($e);
	}

	/**
	 * deleteUser
	 */
	public function deleteUser($pWithUser){
		$e = $this->createEventStackInstance("delete", "User", null, $pWithUser);
		$this->dispatchEvent($e);
	}

	// Wigii multiplexed events

	public function event($eventName, $entityName, $module, $object) {
		$e = $this->createEventStackInstance($eventName, $entityName, $module, $object);
		$this->dispatchEvent($e);
	}

}

/**
 * EventStack model
 * used in WigiiEventsDispatcher to store the event informations
 */
class EventStack {
	private $eventName;
	public function getEventName(){ return $this->eventName; }
	private $entityName;
	public function getEntityName(){ return $this->entityName; }
	private $module;
	public function getModule(){ return $this->module; }
	private $eventInfo;
	public function getEventInfo(){ return $this->eventInfo; }

	/**
	 * create an EventStack instance
	 * @param eventInfo : Object, this is a specific model depending on the event and the entity
	 */
	public static function createInstance($eventName, $entityName, $module, $eventInfo){
		$r = new self();
		$r->eventName = $eventName;
		$r->entityName = $entityName;
		$r->module = $module;
		$r->eventInfo = $eventInfo;
		return $r;
	}
}