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

/*
 * Created on 19 nov. 09
 * by LWR
 */

class EventSubscriberService {

	private $_debugLogger;
	private $_executionSink;
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("EventSubscriberService");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("EventSubscriberService");
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

	private $fileStatisticService;
	public function setFileStatisticService($fileStatisticService){
		$this->fileStatisticService = $fileStatisticService;
	}
	protected function getFileStatisticService(){
		//autowired
		if(!isset($this->fileStatisticService)){
			$this->fileStatisticService = TechnicalServiceProvider::getFileStatisticService();
		}
		return $this->fileStatisticService;
	}

	private $elementStatisticService;
	public function setElementStatisticService($elementStatisticService){
		$this->elementStatisticService = $elementStatisticService;
	}
	protected function getElementStatisticService(){
		//autowired
		if(!isset($this->elementStatisticService)){
			$this->elementStatisticService = TechnicalServiceProvider::getElementStatisticService();
		}
		return $this->elementStatisticService;
	}

	private $globalStatisticService;
	public function setGlobalStatisticService($globalStatisticService){
		$this->globalStatisticService = $globalStatisticService;
	}
	protected function getGlobalStatisticService(){
		//autowired
		if(!isset($this->globalStatisticService)){
			$this->globalStatisticService = TechnicalServiceProvider::getGlobalStatisticService();
		}
		return $this->globalStatisticService;
	}

	private $sessionAS;
	public function setSessionAdminService($sessionAdminService){
		$this->sessionAS = $sessionAdminService;
	}
	protected function getSessionAdminService(){
		// autowired
		if(!isset($this->sessionAS)){
			$this->sessionAS = ServiceProvider::getSessionAdminService();
		}
		return $this->sessionAS;
	}

	/**
	 * getAllSubscribersForEvent
	 */
	public function getAllSubscribersForEvent($eventName, $entityName, $module, $eventSubscriberList){
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
							$this->debugLogger()->write("event not implemented : ".$eventName. " for entity: ".$entityName);
							break;
						case "download":
							$eventSubscriberList->addEventSubscriber($this->getFileStatisticService(), true);
							break;
						case "read":
						case "sendFeedback":
						case "sendExternalValidationLink":
						case "setExternalAccessViewLink":
						case "setExternalAccessEditLink":
						case "stopExternalAccessLink":
						case "sendEmail":
							$this->debugLogger()->write("event not implemented : ".$eventName. " for entity: ".$entityName);
							break;
						default:
							throw new ServiceException("unknown event: ".$eventName." for entity: ".$entityName, ServiceException::INVALID_ARGUMENT);
					}
					$eventSubscriberList->addEventSubscriber($this->getElementStatisticService(), true);
					break;
				case "MultipleElement":
					switch($eventName){
						case "insert":
						case "update":
						case "delete":
						case "setState":
						case "share":
						case "unshare":
						case "setShare":
						case "moveToModule":
						case "Emailing":
						case "exportElements":
						case "sendMultipleExternalValidationLink":
						case "setMultipleExternalAccessViewLink":
						case "setMultipleExternalAccessEditLink":
						case "stopMultipleExternalAccessLink":
							$this->debugLogger()->write("event not implemented : ".$eventName. " for entity: ".$entityName);
							break;
						default:
							throw new ServiceException("unknown event: ".$eventName." for entity: ".$entityName, ServiceException::INVALID_ARGUMENT);
					}
					$eventSubscriberList->addEventSubscriber($this->getElementStatisticService(), true);
					break;
				case "User":
					switch($eventName){
						case "navigate":
						case "login":
						case "logout":
						case "switchLanguage":
							$eventSubscriberList->addEventSubscriber($this->getSessionAdminService(), true);
							break;
						case "insert":
						case "update":
						case "delete":
						case "changePassword":
							$this->debugLogger()->write("event not implemented : ".$eventName. " for entity: ".$entityName);
							break;
						default:
							throw new ServiceException("unknown event: ".$eventName." for entity: ".$entityName, ServiceException::INVALID_ARGUMENT);
					}
					break;
				case "Group":
					switch($eventName){
						case "selectGroup":
						case "insert":
						case "update":
						case "delete":
							$this->debugLogger()->write("event not implemented : ".$eventName. " for entity: ".$entityName);
							break;
						default:
							throw new ServiceException("unknown event: ".$eventName." for entity: ".$entityName, ServiceException::INVALID_ARGUMENT);
					}
					break;
				case "Module":
					switch($eventName){
						case "sendFeedback":
							$this->debugLogger()->write("event not implemented : ".$eventName. " for entity: ".$entityName);
							break;
						default:
							throw new ServiceException("unknown event: ".$eventName." for entity: ".$entityName, ServiceException::INVALID_ARGUMENT);
					}
					break;
				case "Function":
				    $eventSubscriberList->addEventSubscriber($this->getElementStatisticService(), true);
				    break;
				default:
					throw new ServiceException("unknown entity: ".$entityName, ServiceException::INVALID_ARGUMENT);

			}
			$eventSubscriberList->addEventSubscriber($this->getGlobalStatisticService(), true);
		} catch (Exception $e){
			$this->executionSink()->publishEndOperationOnError("getAllSubscribersForEvent", $e);
			throw new ServiceException('',ServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getAllSubscribersForEvent");
	}


}


