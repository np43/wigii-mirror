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
 * Created on 20 nov. 09
 * by LWR
 */

class FileStatisticService implements MultiplexedEvent {

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
			$this->_debugLogger = DebugLogger::getInstance("FileStatisticService");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("FileStatisticService");
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
		$element = $object->getElement();
		if(is_null($element)) throw new StatisticServiceException('element can not be null', StatisticServiceException::INVALID_ARGUMENT);
		$fieldname = $object->getFieldname();
		if(is_null($fieldname)) throw new StatisticServiceException('field can not be null', StatisticServiceException::INVALID_ARGUMENT);

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

		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForInsert('FileStatistic');
		$sqlB->insertValue("timestamp", time(), MySqlQueryBuilder::SQLTYPE_INT);
		$sqlB->insertValue("eventName", $eventName, MySqlQueryBuilder::SQLTYPE_VARCHAR);
		$sqlB->insertValue("entityName", $entityName, MySqlQueryBuilder::SQLTYPE_VARCHAR);
		$sqlB->insertValue("modulename", (isset($module)?$module->getModuleName():null), MySqlQueryBuilder::SQLTYPE_VARCHAR);
		$sqlB->insertValue("wigiiNamespace", (isset($wigiiNamespace)?$wigiiNamespace->getWigiiNamespaceName():null), MySqlQueryBuilder::SQLTYPE_VARCHAR);
		$sqlB->insertValue("userId", $userId, MySqlQueryBuilder::SQLTYPE_INT);
		$sqlB->insertValue("username", $username, MySqlQueryBuilder::SQLTYPE_VARCHAR);
		$sqlB->insertValue("realUserId", $realUserId, MySqlQueryBuilder::SQLTYPE_INT);
		$sqlB->insertValue("realUsername", $realUsername, MySqlQueryBuilder::SQLTYPE_VARCHAR);
		$sqlB->insertValue("elementId", $element->getid(), MySqlQueryBuilder::SQLTYPE_VARCHAR);
		$sqlB->insertValue("field", $fieldname, MySqlQueryBuilder::SQLTYPE_VARCHAR);

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



