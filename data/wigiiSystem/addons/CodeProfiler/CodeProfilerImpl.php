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

/* Wigii Code profiler for performance monitoring
 * Created by CWE on 8.2.2013
 */
class CodeProfilerImpl
{	
	private $mysqlF;
	private $dbConnectionSettings;
	private $execStackFilter;
	private $htmlMapper;
	private $notConnectedToDb;	
	
	// Constructor
	
	public function __construct() {
		$this->notConnectedToDb = true;
	}
	
	// Dependency injection
	
	public function setMySqlFacade($mysqlFacade)
	{
		$this->mysqlF = $mysqlFacade;
	}
	protected function getMySqlFacade()
	{
		// autowired
		if(!isset($this->mysqlF))
		{
			$this->mysqlF = TechnicalServiceProviderCodeProfilingImpl::getMySqlFacade();
		}
		return $this->mysqlF;
	}
	
	public function setDbConnectionSettings($dbConnectionSettings)
	{
		$this->dbConnectionSettings = $dbConnectionSettings;
	}
	protected function getDbConnectionSettings()
	{
		// autowired
		if(!isset($this->dbConnectionSettings))
		{
			$this->dbConnectionSettings = TechnicalServiceProviderCodeProfilingImpl::getCodeProfilerDbConnectionSettings();
		}
		return $this->dbConnectionSettings;
	}
	
	public function setExecutionStackFilter($executionStackFilter)
	{
		$this->execStackFilter = $executionStackFilter;
	}
	protected function getExecutionStackFilter()
	{		
		return $this->execStackFilter;
	}
	
	public function setHTMLMapper($htmlMapper)
	{
		$this->htmlMapper = $htmlMapper;
	}
	protected function getHTMLMapper()
	{
		// autowired
		if(!isset($this->htmlMapper))
		{
			$this->htmlMapper = PerfLog2HTMLMapper::createInstance();
		}
		else {
			if($this->htmlMapper->isLockedForUse()) {
				return PerfLog2HTMLMapper::createInstance();
			}
			else $this->htmlMapper->reset();	
		}
		return $this->htmlMapper;
	}
	
	// Execution sink connector
	
	/**
	 * Publishes the start of an operation
	 */
	public function publishStartOperation($className, $operation, $principal)
	{
		$stackFilter = $this->getExecutionStackFilter();		
		if(!isset($stackFilter) || $stackFilter->shouldActOnStartOperation($className, $operation, $principal))
		{
			$this->storePerfLog($principal, $className, $operation, "BEGIN");
		}		
	}

	/**
	 * Publishes the end of an operation	 
	 */
	public function publishEndOperation($className, $operation, $principal)
	{
		$stackFilter = $this->getExecutionStackFilter();		
		if(!isset($stackFilter) || $stackFilter->shouldActOnEndOperation($className, $operation, $principal)) 
		{
			$this->storePerfLog($principal, $className, $operation, "END");
		}
	}

	/**
	 * Publishes the end of an operation in case of error
	 */
	public function publishEndOperationOnError($className, $operation, $exception, $principal)
	{
		$stackFilter = $this->getExecutionStackFilter();		
		if(!isset($stackFilter) || $stackFilter->shouldActOnEndOperationOnError($className, $operation, $exception, $principal)) 
		{
			$this->storePerfLog($principal, $className, $operation, "END_ON_ERROR");
		}
	}

	/**
	 * Sql trace
	 */
	public function logSql($className, $sqlCode, $principal)
	{
		$stackFilter = $this->getExecutionStackFilter();		
		if(!isset($stackFilter) || $stackFilter->shouldActOnSql($className, $sqlCode, $principal))
		{
			$this->storePerfLog($principal, $className, "SQL", $sqlCode);
		}
	} 
	
	
	// Performance results publication
	
	public function getHTMLPerformanceLog($principal) {		
		$htmlMapper = $this->getHTMLMapper();
		$returnValue = "- log is empty -";
		try {
			if($this->getMySqlFacade()->selectAllWithoutLog($principal, 
				$this->getSqlForGetPerfLog(), 
				$this->getDbConnectionSettings(), 
				$htmlMapper) > 0) {
				$returnValue = $htmlMapper->getHTML();
			}
			$htmlMapper->freeMemory();
		}
		catch(Exception $e) {
			$htmlMapper->freeMemory();
			throw $e;
		}		
		return $returnValue;
	}
	protected function getSqlForGetPerfLog() {
		return 'select id_perflog, timestamp , action, classname, operation from perflog where `timestamp` > ((UNIX_TIMESTAMP()-30)*1000) order by id_perflog desc';
	}
	
	// Implementation
	
	protected function storePerfLog($principal, $className, $operation, $action)
	{		
		if($this->notConnectedToDb)
		{
			$this->notConnectedToDb = false;
			$this->storePerfLog($principal, "CodeProfilerImpl", "startProfiler", "BEGIN");
			$this->storePerfLog($principal, "CodeProfilerImpl", "startProfiler", "END");			
		}
		$this->getMySqlFacade()->insertOneWithoutLog($principal, 
			$this->getSqlForInsertPerfLog($principal, $className, $operation, $action), 
			$this->getDbConnectionSettings());		
	}
	protected function getSqlForInsertPerfLog($principal, $className, $operation, $action) {
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForInsert('perflog');
		$sqlB->insertValue('realUsername', (isset($principal) ? $principal->getRealUsername(): null), MySqlQueryBuilder::SQLTYPE_VARCHAR);
		$sqlB->insertValue('executionId', (isset($principal) ? $principal->getExecutionId(): null), MySqlQueryBuilder::SQLTYPE_VARCHAR);
		$sqlB->insertValue('timestamp', $this->getMilliTime(), MySqlQueryBuilder::SQLTYPE_INT);
		$sqlB->insertValue('classname', $className, MySqlQueryBuilder::SQLTYPE_VARCHAR);
		$sqlB->insertValue('operation', $operation, MySqlQueryBuilder::SQLTYPE_VARCHAR);
		$sqlB->insertValue('action', $action, MySqlQueryBuilder::SQLTYPE_TEXT);
		return $sqlB->getSql();
	}
	private function getMilliTime(){
			list($usec, $sec) = explode(" ", microtime());			
			$millisec = $sec.substr($usec, 2, 3);
			return $millisec;
	}
}