<?php
/**
 *  This file is part of Wigii.
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

/*
 * Created on 19 nov. 09
 * by LWR
 */

class EventSubscriberServiceWebImpl extends EventSubscriberService {

	private $_debugLogger;
	private $_executionSink;
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("EventSubscriberServiceWebImpl");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("EventSubscriberServiceWebImpl");
		}
		return $this->_executionSink;
	}
	public function __construct()
	{
		$this->debugLogger()->write("creating instance");
	}

	/*
	 * dependency Injection
	 */
	private $notificationService;
	public function setNotificationService($notificationService){
		$this->notificationService = $notificationService;
	}
	protected function getNotificationService(){
		//autowired
		if(!isset($this->notificationService)){
			$this->notificationService = TechnicalServiceProviderWebImpl::getNotificationService();
		}
		return $this->notificationService;
	}

	/**
	 * getAllSubscribersForEvent
	 */
	public function getAllSubscribersForEvent($eventName, $entityName, $module, $eventSubscriberList){
		// adds parent subscription
		parent::getAllSubscribersForEvent($eventName, $entityName, $module, $eventSubscriberList);
		// adds supplementary web specific subscriptions
		$this->executionSink()->publishStartOperation("getAllSubscribersForEvent");
		try{
			switch($entityName){
				case "Element":
					switch($eventName){
						case "insert":
						case "update":
						case "delete":
						case "restore":
						case "share":
						case "unshare":
						case "setState":
							$eventSubscriberList->addEventSubscriber($this->getNotificationService(), true);
							break;
					}
					break;
				case "MultipleElement":
					switch($eventName){
						//case "insert": //do not link the email notification service to multipleInsert, because we prefer keeping maximum ressource for the import calculation
						case "update":
						case "delete":
						case "share":
						case "unshare":
						case "setShare":
						case "moveToModule":
						case "setState": //set state dosen't work properly as the elementPList contains the previous status and doesnot contain the list view.
							$eventSubscriberList->addEventSubscriber($this->getNotificationService(), true);
							break;
					}
					break;
			}
		} catch (Exception $e){
			$this->executionSink()->publishEndOperationOnError("getAllSubscribersForEvent", $e);
			throw new ServiceException('',ServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getAllSubscribersForEvent");
	}


}


