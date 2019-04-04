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
 * A list of rows selected from a database table which are pushed into a DataFlow in the form of StdClass instances
 * This data flow connector cannot be called from public space (i.e. caller is located outside of the Wigii instance)
 * Additionally only root principal or superadmins can use it.
 * Created by Medair (CWE) on 02.06.2017
 */
class DbTableDataFlowConnector implements RowList, DataFlowDumpable
{
	private $_debugLogger;
	private $lockedForUse = true;
	private $dataFlowService;
	private $dataFlowContext;
	private $nRows;
	private $startRowNumber;
	private $endRowNumber;
	private $currentRowNumber;
	
	// Object lifecycle
	
	public function reset() {
		$this->freeMemory();	
		$this->lockedForUse = true;		
		$this->startRowNumber = 0;
		$this->endRowNumber = 0;
		$this->currentRowNumber = 0;
	}	
	public function freeMemory() {		
		unset($this->listFilter);
		unset($this->dbTableName);
		unset($this->sqlTypeMap);
		unset($this->dataFlowService);
		unset($this->dataFlowContext);
		$this->nRows = 0;	
		$this->lockedForUse = false;	
	}
		
	public function isLockedForUse() {
		return $this->lockedForUse;
	}
	
	public static function createInstance($dbTableName=null,$listFilter=null,$sqlTypeMap=null) {
		$returnValue = new self();
		$returnValue->reset();
		if(isset($dbTableName)) $returnValue->setDbTableName($dbTableName);
		if(isset($listFilter)) $returnValue->setListFilter($listFilter);
		if(isset($sqlTypeMap)) $returnValue->setSqlTypeMap($sqlTypeMap);
		return $returnValue;
	}
	
	// Dependency injection
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("DbTableDataFlowConnector");
		}
		return $this->_debugLogger;
	}
	
	private $dbAS;
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
	
	private $mysqlF;
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
			
	private $authoS;
	public function setAuthorizationService($authorizationService)
	{
	    $this->authoS = $authorizationService;
	}
	protected function getAuthorizationService()
	{
	    // autowired
	    if(!isset($this->authoS))
	    {
	        $this->authoS = ServiceProvider::getAuthorizationService();
	    }
	    return $this->authoS;
	}	
	
	private $wigiiBPL;
	public function setWigiiBPL($wigiiBPL)
	{
	    $this->wigiiBPL = $wigiiBPL;
	}
	/**	 
	 * @return WigiiBPL
	 */
	protected function getWigiiBPL()
	{
	    // autowired
	    if(!isset($this->wigiiBPL))
	    {
	        $this->wigiiBPL = ServiceProvider::getWigiiBPL();
	    }
	    return $this->wigiiBPL;
	}
	
	// Configuration
	
	private $dbTableName;
	/**
	 * Sets the db table name from which to select the rows
	 * @param String $dbTableName the name of an existing table into the database
	 */
	public function setDbTableName($dbTableName) {
	    $this->dbTableName = $dbTableName;
	}	
	
	private $listFilter;
	/**
	 * Sets the list filter used to select the rows
	 * @param ListFilter $listFilter a ListFilter
	 */
	public function setListFilter($listFilter) {
		$this->listFilter = $listFilter;
	}
	/**
	 * Returns the list filter used to select the rows
	 * @return ListFilter
	 */
	public function getListFilter() {
		return $this->listFilter;
	}
	
	private $sqlTypeMap;
	/**
	 * Defines the SQL Type of each fetched column.
	 * If a column is not defined, then default SQL type is VARCHAR
	 * @param Array $map an array with key equal to the column name and value equal to one of MySqlQueryBuilder::SQLTYPE_* or BIGINT, DOUBLE, DECIMAL, BOOLEAN, DATE, DATETIME, INT, LONGBLOB, BLOB, TEXT, TIME, VARCHAR
	 */
	public function setSqlTypeMap($map) {
	    $this->sqlTypeMap = $map;
	}	
	
	// DataFlowDumpable implementation
	
	public function dumpIntoDataFlow($dataFlowService, $dataFlowContext) {		
		$this->dataFlowService = $dataFlowService;
		$this->dataFlowContext = $dataFlowContext;
		// creates ListFilter to select elements in a paged way
		$lf = ListFilter::createInstance();
		$lf->setDesiredPageNumber(1);
		$lf->setPageSize($this->maxWigiiBagSize);		
		$maxElements = -1;
		// clones information coming from user listfilter
		if(isset($this->listFilter)) {
			$lf->setFieldSelectorList($this->listFilter->getFieldSelectorList());
			$lf->setFieldSelectorLogExp($this->listFilter->getFieldSelectorLogExp());
			$lf->setFieldSortingKeyList($this->listFilter->getFieldSortingKeyList());
			$lf->setConfigGroupList($this->listFilter->getConfigGroupList());
			if($this->listFilter->isPaged()) {
				$maxElements = $this->listFilter->getPageSize();
				if($lf->getPageSize() > $maxElements) $lf->setPageSize($maxElements);
				$this->startRowNumber = ($this->listFilter->getDesiredPageNumber()-1)*$maxElements+1;
				$this->endRowNumber = $this->startRowNumber + $maxElements;
			}
			// stores ListFilter into data flow context so that it can be used in next stages
			$dataFlowContext->setAttribute('ListFilter', $this->listFilter);
		}
		// stores FieldSelectorList into data flow context so that it can be used in next stages
		$fsl = $lf->getFieldSelectorList();
		if(isset($fsl)) $dataFlowContext->setAttribute('FieldSelectorList', $fsl);
				
		$this->nRows = 0;	
		if($this->startRowNumber > 0) {
			$p = $this->startRowNumber / $lf->getPageSize();
			$lf->setDesiredPageNumber(($p > (int)$p) ? (int)$p+1 : (int)$p);
		}
		$this->currentRowNumber = ($lf->getDesiredPageNumber()-1)*$lf->getPageSize();
		$principal = $dataFlowContext->getPrincipal();
		
		
		//$n = $this->getMySqlFacade()->selectPage(...);
		DataFlowServiceException::throwNotImplemented();
		
		// updates user ListFilter with totalNumberOfObjects if set
		if(isset($this->listFilter)) {
			$this->listFilter->setTotalNumberOfObjects($lf->getTotalNumberOfObjects());
		}
		
		// fetches all the elements until reaching the max
		while($n < $lf->getTotalNumberOfObjects() &&
				($maxElements < 0 || $n < $maxElements)) {			
			$lf->setDesiredPageNumber($lf->getDesiredPageNumber()+1);
			$this->nRows = 0;
			//$n = $this->getMySqlFacade()->selectPage(...);
			DataFlowServiceException::throwNotImplemented();
			
			if($this->nRows == 0) break;					
		}

		// updates the number of elements to make it readable from calling process
		$this->nRows = $n;
	}
	
	/**
	 * @param DataFlowContext $dataFlowContext
	 */
    protected function assertPrincipalAuthorizedToDumpIntoDataFlow($dataFlowContext) {
        // not authorized to be called from public space
        $dataFlowContext->assertOriginIsNotPublic();
        $principal = $dataFlowContext->getPrincipal();
        // authorized only if root principal        
        // or if principal has a SuperAdmin role
        if(!($this->getAuthorizationService()->isRootPrincipal($principal) ||
             $this->getWigiiBPL()->adminIsPrincipalSuperAdmin($principal))) throw new AuthorizationServiceException('The DbTableDataFlowConnector can be used only by the root pincipal or if current principal is superadmin', AuthorizationServiceException::FORBIDDEN);        
    }
	
	// RowList implementation		
	
	public function addRow($row) {	    
	    $this->nRows++;
	    $this->currentRowNumber++;
	    if($this->startRowNumber == 0 ||
	        $this->startRowNumber <= $this->currentRowNumber &&
	        $this->currentRowNumber < $this->endRowNumber) {	            
	            // pushes down to data flow
	            DataFlowServiceException::throwNotImplemented();
	            $this->dataFlowService->processDataChunk($row, $this->dataFlowContext);
	        }
	}	
	
	public function getListIterator() {throw new DataFlowServiceException("The DbTableDataFlowConnector cannot be iterated. It is a forward only push of data rows into the data flow.", DataFlowServiceException::UNSUPPORTED_OPERATION);}
	
	public function isEmpty() {return ($this->nRows == 0);}
	
	public function count() {return $this->nRows;}
	
	/**
	 * Converts this DbTable data flow connector to its FuncExp equivalent
	 * @return FuncExp
	 */
	public function toFx() {
		$args = array();
		$args[] = $this->dbTableName;
		if(isset($this->listFilter)) $args[] = $this->listFilter->toFx();
		elseif(isset($this->sqlTypeMap)) $args[] = null;
		if(isset($this->sqlTypeMap)) $args[] = $this->sqlTypeMap;
		return fx('dbTable2df', $args);
	}
}