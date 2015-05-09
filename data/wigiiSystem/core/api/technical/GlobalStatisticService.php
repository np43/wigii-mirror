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
 * Created on 20 nov. 09
 * by LWR
 */

class GlobalStatisticService implements MultiplexedEvent {

	private $_debugLogger;
	private $_executionSink;
	private $dbAS;
	private $mysqlF;
	private $enabled;
	public function setEnabled($var){ $this->enabled = $var; }
	public function getEnabled(){ return $this->enabled; }

	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("GlobalStatisticService");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("GlobalStatisticService");
		}
		return $this->_executionSink;
	}
	public function __construct()
	{
		$this->debugLogger()->write("creating instance");
	}

	/*
	 * dependy injection
	 */
	public function setDbAdminService($dbAdminService)
	{
		$this->dbAS = $dbAdminService;
	}
	protected function getDbAdminService()
	{
		// autowired
		if(!isset($this->dbAS))
		{
			$this->dbAS = ServiceProvider::getDbAdminService();
		}
		return $this->dbAS;
	}

	public function setMySqlFacade($mysqlFacade)
	{
		$this->mysqlF = $mysqlFacade;
	}
	protected function getMySqlFacade()
	{
		// autowired
		if(!isset($this->mysqlF))
		{
			$this->mysqlF = TechnicalServiceProvider::getMySqlFacade();
		}
		return $this->mysqlF;
	}

	/*
	 * service implementation
	 */

	public function event($eventName, $entityName, $module, $object){
		$p = $this->getP($object);
		$this->executionSink()->publishStartOperation("event", $p);
		try
		{
			if($this->getEnabled()){
				$dbCS = $this->getDbAdminService()->getDbConnectionSettings($p);
				$returnValue = $this->getMySqlFacade()->insertOne($p,
					$this->getSqlForInsertEvent($eventName, $entityName, $module, $object),
					$dbCS);
			}
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("event", $e, $p);
			throw new StatisticServiceException('',StatisticServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("event", $p);
		return $returnValue;
	}

	protected function getSqlForInsertEvent($eventName, $entityName, $module, $object){
		if(is_null($eventName)) throw new StatisticServiceException('eventName can not be null', StatisticServiceException::INVALID_ARGUMENT);
		if(is_null($entityName)) throw new StatisticServiceException('entityName can not be null', StatisticServiceException::INVALID_ARGUMENT);
		$p = $this->getP($object);
		$wigiiNamespace = $p->getWigiiNamespace();
		$user = $p->getAttachedUser();
		$userId = $username = $realUserId = $realUsername = null;
		if(isset($user)) {
			$userId = $user->getId();
			$username = $user->getUsername();
		}
		$realUser = $p->getRealUser();
		if(isset($realUser)) {
			$realUserId = $realUser->getId();
			$realUsername = $realUser->getUsername();
		}
		
		$entityId = null;
		if(method_exists($object, "getGroup")){
			if($object->getGroup()!=null) $entityId = $object->getGroup()->getId();
		} else if(method_exists($object, "getGroupP")){
			if($object->getGroupP()!=null) $entityId = $object->getGroupP()->getId();
		} else if(method_exists($object, "getUser")){
			if($object->getUser()!=null) $entityId = $object->getUser()->getId();
		} else if(method_exists($object, "getUserP")){
			if($object->getUserP()!=null) $entityId = $object->getUserP()->getId();
		} else if(method_exists($object, "getElement")){
			if($object->getElement()!=null) $entityId = $object->getElement()->getId();
		} else if(method_exists($object, "getElementP")){
			if($object->getElementP()!=null) $entityId = $object->getElementP()->getId();
		} else if(method_exists($object, "getRecord")){
			if($object->getRecord()!=null) $entityId = $object->getRecord()->getId();
		}
		
		$wigiiNamespace = (isset($wigiiNamespace)?$wigiiNamespace->getWigiiNamespaceName():null);
		$module = (isset($module)?$module->getModuleName():null);
		
		if($eventName == "navigate"){
			$module = $object->getModuleName();
			$wigiiNamespace = $object->getWigiiNamespaceName();
			$entityId = $object->getUserId();
		}

		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForInsert('GlobalStatistic');
		$sqlB->insertValue("timestamp", time(), MySqlQueryBuilder::SQLTYPE_INT);
		$sqlB->insertValue("eventName", $eventName, MySqlQueryBuilder::SQLTYPE_VARCHAR);
		$sqlB->insertValue("entityName", $entityName, MySqlQueryBuilder::SQLTYPE_VARCHAR);
		$sqlB->insertValue("entityId", $entityId, MySqlQueryBuilder::SQLTYPE_INT);
		$sqlB->insertValue("modulename", $module, MySqlQueryBuilder::SQLTYPE_VARCHAR);
		$sqlB->insertValue("wigiiNamespace", $wigiiNamespace, MySqlQueryBuilder::SQLTYPE_VARCHAR);
		$sqlB->insertValue("userId", $userId, MySqlQueryBuilder::SQLTYPE_INT);
		$sqlB->insertValue("username", $username, MySqlQueryBuilder::SQLTYPE_VARCHAR);
		$sqlB->insertValue("realUserId", $realUserId, MySqlQueryBuilder::SQLTYPE_INT);
		$sqlB->insertValue("realUsername", $realUsername, MySqlQueryBuilder::SQLTYPE_VARCHAR);

		return $sqlB->getSql();
	}

	private function getP($object)
	{
		if(is_null($object)) throw new StatisticServiceException('object can not be null', StatisticServiceException::INVALID_ARGUMENT);
		$p = $object->getP();
		if(is_null($p)) throw new StatisticServiceException('object need to include P', StatisticServiceException::INVALID_ARGUMENT);
		return $p;
	}
}



