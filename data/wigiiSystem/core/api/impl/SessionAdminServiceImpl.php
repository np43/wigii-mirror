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
 * Created on 8 juil. 09
 * Craeted by LWR
 */


class SessionAdminServiceImpl implements SessionAdminService, MultiplexedEvent {

	private $_debugLogger;
	private $_executionSink;
	private $mysqlF;
	private $dbAS;
	private $authS;

	// Dependency injection
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("SessionAdminServiceImpl");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("SessionAdminServiceImpl");
		}
		return $this->_executionSink;
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
	public function setAuthenticationService($authenticationService)
	{
		$this->authS = $authenticationService;
	}
	protected function getAuthenticationService()
	{
		// autowired
		if(!isset($this->authS)) {
			$this->authS = ServiceProvider::getAuthenticationService();
		}
		return $this->authS;
	}
	
	// Object lifecycle
	
	public function __sleep(){
		$this->debugLogger()->write("store datas in session");
	}
	public function __construct()
	{
		$this->debugLogger()->write("creating instance");
		// creates empty navigation cache if not already present into session
		if(!isset($_SESSION[$this->getNavigationCacheKey()])) $_SESSION[$this->getNavigationCacheKey()] = array();
		// creates empty shared data index if not already present into session
		if(!isset($_SESSION[$this->getSharedDataIndexKey()])) $_SESSION[$this->getSharedDataIndexKey()] = array();
	}


	/**
	 * store $value into $_SESSION. The values needs to be
	 * basic values, or array of basic values
	 * to much complexity in data storage brings to bad result
	 */
	public function storeData($obj, $name, $value, $clearOnNavigate=false, $sharedData=false){
		$k = $this->getKey($obj, $name);
		$storedSharedData = false;
		// if clearOnNavigate then puts into navigation cache
		if($clearOnNavigate) $_SESSION[$this->getNavigationCacheKey()][$k] = $value;
		// if shared data then puts into database
		elseif($sharedData && $this->storeSharedData($k, $value)) {
			$storedSharedData = true;
			// caches a local copy in navigation cache to speed up access
			$_SESSION[$this->getNavigationCacheKey()][$k] = $value;
		}
		// else puts into session
		else $_SESSION[$k] = $value;
		// updates the shared data index into the session
		$_SESSION[$this->getSharedDataIndexKey()][$k] = $storedSharedData;
		return;
	}

	public function getData($obj, $name, $fromSharedData=false){
		$k = $this->getKey($obj, $name);		
		// first looks in navigation cache
		$returnValue = $_SESSION[$this->getNavigationCacheKey()][$k];
		if(isset($returnValue)) return $returnValue;
		// then looks in session cache
		$returnValue = $_SESSION[$k];
		if(isset($returnValue)) return $returnValue; 		
		// else looks in shared memory
		if($fromSharedData && $_SESSION[$this->getSharedDataIndexKey()][$k] !== false) {
			$returnValue = $this->getSharedData($k);
			// caches a local copy in navigation cache to speed up access
			if(isset($returnValue)) $_SESSION[$this->getNavigationCacheKey()][$k] = $returnValue;
		}
		return $returnValue;
	}

	public function clearData($obj, $name){
		$k = $this->getKey($obj, $name);
		return $this->clearDataKey($k);
	}
	public function clearDataKey($key){
		// clears from navigation cache
		unset($_SESSION[$this->getNavigationCacheKey()][$key]);
		// clears from session
		unset($_SESSION[$key]);
		// clears from shared memory
		if($_SESSION[$this->getSharedDataIndexKey()][$key] !== false) {
			$this->clearSharedData($key);
		}
		return;
	}
	public function clearObjData($obj, $clearSharedData=false) {
		if(empty($obj)) return;
		elseif(is_object($obj)) $key = get_class($obj);
		else $key = $obj;
		if($clearSharedData) $sharedDataKey = $key."_%";
		$key = '/'.$key.'_.*/';

		// clears from navigation cache
		$nk = $this->getNavigationCacheKey();
		$objKeys = preg_grep($key, array_keys($_SESSION[$nk]));
		foreach($objKeys as $k) {
			unset($_SESSION[$nk][$k]);
		}
		// clears from session
		$objKeys = preg_grep($key, array_keys($_SESSION));
		foreach($objKeys as $k) {
			unset($_SESSION[$k]);
		}
		// clears from shared memory
		if($clearSharedData) $this->clearSharedData($sharedDataKey, true);
	}

	private function getKey($obj, $name){
		return get_class($obj)."_".$name;
	}
	private function getNavigationCacheKey() {
		return 'SessionAdminServiceImpl_navigationCache';
	}
	private function getNavigationKey() {
		return 'SessionAdminServiceImpl_navigationKey';
	}

	/**
	 * WARNING, this method needs to be used very quarefully and only for debug reason
	 * because of the amount of data it can contain the function put will probably
	 * do a Fatal error: Allowed memory size of xx bytes exhausted (tried to allocate yy bytes) in...
	 */
	public function displayDebug(){
		return put($_SESSION);
	}

	// MultiplexedEvent implementation

	public function event($eventName, $entityName, $module, $object) {
		// clears navigation cache when user navigates
		if($eventName == 'navigate' && $entityName == 'User' && isset($object)) {
			$destWigiiNamespaceName = explode('->', $object->getWigiiNamespaceName());
			$destWigiiNamespaceName = end($destWigiiNamespaceName);
			if(empty($destWigiiNamespaceName) || $destWigiiNamespaceName == WigiiNamespace::EMPTY_NAMESPACE_URL) $destWigiiNamespaceName = WigiiNamespace::EMPTY_NAMESPACE_NAME;
			$destModuleName = explode('->', $object->getModuleName());
			$destModuleName = end($destModuleName);
			if(empty($destModuleName) || $destModuleName == Module::EMPTY_MODULE_URL) $destModuleName = Module::EMPTY_MODULE_NAME;

			$navigationKey = "($destWigiiNamespaceName($destModuleName))";
			$nk = $this->getNavigationKey();
			if($_SESSION[$nk] != $navigationKey) {
				$_SESSION[$nk] = $navigationKey;
				$nk = $this->getNavigationCacheKey();
				unset($_SESSION[$nk]);
				$_SESSION[$nk] = array();
			}
		}
		// clears navigation cache when user logins, logouts
		elseif($entityName == 'User' && ($eventName == 'login' || $eventName == 'logout')) {
			unset($_SESSION[$this->getNavigationKey()]);
			$nk = $this->getNavigationCacheKey();
			unset($_SESSION[$nk]);
			$_SESSION[$nk] = array();
		}
		// clears navigation cache when user switches language, but keeps navigation key
		elseif($entityName == 'User' && $eventName == 'switchLanguage') {
			$nk = $this->getNavigationCacheKey();
			unset($_SESSION[$nk]);
			$_SESSION[$nk] = array();
		}
	}
	
	// Shared data stored into database
	
	private function getSharedDataIndexKey() {
		return 'SessionAdminServiceImpl_sharedDataIndex';
	}
	/**	 
	 * @return Boolean true if data was stored into shared memory, else false if an error occured	 	
	 */
	protected function storeSharedData($key, $value) {
		$principal = $this->getAuthenticationService()->getMainPrincipal();
		$this->executionSink()->publishStartOperation("storeSharedData", $principal);
		try
		{			
			$this->getMySqlFacade()->update($principal, 
					$this->getSqlForStoreSharedData($key, $value), 
					$this->getDbAdminService()->getDbConnectionSettings($principal)
			);			
		}		
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("storeSharedData", $e, $principal);
			//throw $e;
			return false;
		}
		$this->executionSink()->publishEndOperation("storeSharedData", $principal);
		return true;
	}
	protected function getSqlForStoreSharedData($key, $value) {
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForUpdate('SessionAdminService', true);
		$sqlB->updateValue('key', $key, MySqlQueryBuilder::SQLTYPE_VARCHAR);
		$sqlB->updateValue('value', $value, MySqlQueryBuilder::SQLTYPE_TEXT);
		$timestamp = time();
		$sqlB->updateValue('sys_creationDate', $timestamp, MySqlQueryBuilder::SQLTYPE_INT);
		$sqlB->updateValue('sys_date', $timestamp, MySqlQueryBuilder::SQLTYPE_INT);
		return $sqlB->getSql();
	}
	
	protected function getSharedData($key) {
		$principal = $this->getAuthenticationService()->getMainPrincipal();
		$this->executionSink()->publishStartOperation("getSharedData", $principal);
		$returnValue = null;
		try
		{			
			$returnValue = $this->getMySqlFacade()->selectOne($principal, 
					$this->getSqlForGetSharedData($key), 
					$this->getDbAdminService()->getDbConnectionSettings($principal)
			);
			if(isset($returnValue)) $returnValue = $returnValue['value'];
		}		
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("getSharedData", $e, $principal);
			//throw $e;
			return null;
		}
		$this->executionSink()->publishEndOperation("getSharedData", $principal);
		return $returnValue;
	}
	protected function getSqlForGetSharedData($key) {
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		return "select `value` from SessionAdminService where ".$sqlB->formatBinExp('`key`', '=', $key, MySqlQueryBuilder::SQLTYPE_VARCHAR);		
	}
	
	protected function clearSharedData($key, $includeWildcard=false) {
		$principal = $this->getAuthenticationService()->getMainPrincipal();
		$this->executionSink()->publishStartOperation("clearSharedData", $principal);
		$returnValue = null;
		try
		{			
			$returnValue = $this->getMySqlFacade()->delete($principal, 
					$this->getSqlForClearSharedData($key, $includeWildcard), 
					$this->getDbAdminService()->getDbConnectionSettings($principal)
			);			
		}		
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("clearSharedData", $e, $principal);
			//throw $e;
			return null;
		}
		$this->executionSink()->publishEndOperation("clearSharedData", $principal);
		return $returnValue;
	}
	protected function getSqlForClearSharedData($key, $includeWildcard=false) {
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForDelete('SessionAdminService');
		if($includeWildcard) {
			$sqlB->setWhereClause($sqlB->formatBinExp('`key`', 'LIKE', $key, MySqlQueryBuilder::SQLTYPE_VARCHAR));
		}
		else $sqlB->setWhereClauseSingleId('`key`', $key, MySqlQueryBuilder::SQLTYPE_VARCHAR);
		return $sqlB->getSql();
	}
	
	/**
	 * Clears all shared data
	 */
	public function clearAllSharedData() {
		$principal = $this->getAuthenticationService()->getMainPrincipal();
		$this->executionSink()->publishStartOperation("clearAllSharedData", $principal);
		$returnValue = null;
		try
		{
			$returnValue = $this->getMySqlFacade()->execDDLCmd($principal,
					$this->getSqlForClearAllSharedData(),
					$this->getDbAdminService()->getDbConnectionSettings($principal)
			);
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("clearAllSharedData", $e, $principal);
			//throw $e;
			return null;
		}
		$this->executionSink()->publishEndOperation("clearAllSharedData", $principal);
		return $returnValue;
	}
	protected function getSqlForClearAllSharedData() {
		return 'truncate SessionAdminService';
	}
}
