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

/**
 * A data flow activity which inserts/updates/deletes a flow of objects into a Db table.
 * This DataFlowActivity cannot be called from public space (i.e. caller is located outside of the Wigii instance)
 * Additionally only root principal or superadmins can use it.
 * Created by Medair (CWE) on 16.06.2017
 */
class DbTableDFA implements DataFlowActivity
{		
	
	// Object lifecycle
		
	public function reset() {
		$this->freeMemory();
	}	
	public function freeMemory() {
		unset($this->mode);
		unset($this->decisionMethod);
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
		
	const MODE_PERSIST = 1;
	const MODE_DELETE = 2;
	const MODE_MIXED = 3;
	const MODE_FILTER = 4;
	const MODE_IGNORE = 5;
	private $mode;
	
	/**
	 * Sets the execution mode: one of PERSIST, DELETE or MIXED
	 * if PERSIST then all objects in the flow are persisted (updated if exists, or inserted if new)
	 * if DELETE then all objects in the flow are deleted
	 * if MIXED then the configured closure (or method) is executed to determine what should be done with each object.
	 * @param int $mode the execution mode
	 * @throws DataFlowServiceException if argument is invalid.
	 */
	public function setMode($mode) {
		switch($mode) {
			case self::MODE_PERSIST:
			case self::MODE_DELETE:
			case self::MODE_MIXED:
				$this->mode = $mode;
				break;
			default: throw new DataFlowServiceException("mode should be one of PERSIST, DELETE or MIXED", DataFlowServiceException::INVALID_ARGUMENT);
		}	
	}
	
	private $decisionMethod;	
	/**
	 * Sets the decision method that should be executed on each object in the flow to
	 * determine what should be done with the object.
	 * The method signature takes one argument which is the current object and
	 * a second argument which is the current data flow context. 
	 * The method should return an integer which should be one of :
	 * MODE_PERSIST if the object should be persisted (updated or inserted) and go further in the data flow,
	 * MODE_DELETE if the object should be deleted and not go further in the data flow,
	 * MODE_FILTER if nothing should be done with the object and 
	 * the object should not go further in the data flow,
	 * MODE_IGNORE if nothing should be done with the object and 
	 * the object should go further in the data flow. 
	 * @param Closure|String $method a closure representing the code that should be executed or 
	 * a string which is an object method name or a function name
	 * @param mixed $object an optional object instance which holds the method instance that should be executed.
	 */
	public function setDecisionMethod($method, $object=null) {
	    $this->decisionMethod = CallableObject::createInstance($method, $object); 
	}
	
	private $dbTableName;
	/**
	 * Sets the db table name where to update the rows
	 * @param String $dbTableName the name of an existing table into the database
	 */
	public function setDbTableName($dbTableName) {
	    $this->dbTableName = $dbTableName;
	}	
	
	private $sqlTypeMap;
	/**
	 * Defines the SQL Type of each updated column.
	 * If a column is not defined, then default SQL type is VARCHAR
	 * @param Array $map an array with key equal to the column name and value equal to one of MySqlQueryBuilder::SQLTYPE_* or BIGINT, DOUBLE, DECIMAL, BOOLEAN, DATE, DATETIME, INT, LONGBLOB, BLOB, TEXT, TIME, VARCHAR
	 */
	public function setSqlTypeMap($map) {
	    $this->sqlTypeMap = $map;
	}	
	
	// stream data event handling
	
	public function startOfStream($dataFlowContext) {
		$dataFlowContext->assertOriginIsNotPublic();
		if(!isset($this->mode)) throw new DataFlowServiceException("mode has not been set", DataFlowServiceException::CONFIGURATION_ERROR);

		DataFlowServiceException::throwNotImplemented();
	}
	public function processDataChunk($data, $dataFlowContext) {
		// if mixed mode, then the action depends of the execution of the decision function
		if($this->mode == self::MODE_MIXED) {
			// uses the closure if defined			
			if(isset($this->decisionMethod)) $action = $this->decisionMethod->invoke($data, $dataFlowContext);
			// else calls the protected method
			else $action = $this->decideActionOnObject($data, $dataFlowContext);
		}	
		// else the action is the current mode
		else $action = $this->mode;
		
		
		DataFlowServiceException::throwNotImplemented();
		
		/*
		 * ALGORITHM TO BE IMPLEMENTED
		 * 
		 * if a logExp is set, then update the selected objects using the logExp
		 * if an ID field is defined, the update the object where the ID matches 
		 */
		
		
		
		// extracts the element
		$element = $data->getDbEntity();		
		$elementIsNew = $element->isNew();
		
		// gets FieldSelectorList in configuration or context
		$fieldSelectorList = $this->fieldSelectorList;
		if(is_null($fieldSelectorList)) {
			$fieldSelectorList = $dataFlowContext->getAttribute('FieldSelectorList');
		}
		// clones FieldSelectorList to include sys info fields
		if(isset($fieldSelectorList)) $fieldSelectorList = FieldSelectorListArrayImpl::createInstance(true,true,$fieldSelectorList);
				
		// executes the action on the current element
		$principal = $dataFlowContext->getPrincipal();
		switch($action) {
			case self::MODE_PERSIST:			    	   
			    // if new, then inserts
			    if($elementIsNew) {
    				if($this->ignoreLockedElements) {
    					try {$element = $this->doPersistElement($principal, $element, $fieldSelectorList);}				
    					catch(AuthorizationServiceException $ase) {if($ase->getCode() != AuthorizationServiceException::OBJECT_IS_LOCKED) throw $ase;}
    				}
    				else $element = $this->doPersistElement($principal, $element, $fieldSelectorList);
			    }
				// if element already exists in database, then checks validity of its ElementInfo and prevents updating if blocked
				else {
				    if($this->isElementBlocked($element)) {
				        if(!$this->ignoreLockedElements) throw new AuthorizationServiceException("Cannot update an element having status blocked.", AuthorizationServiceException::NOT_ALLOWED);
				    }
				    else {
				        if($this->ignoreLockedElements) {
				            try {$element = $this->doPersistElement($principal, $element, $fieldSelectorList);}
				            catch(AuthorizationServiceException $ase) {if($ase->getCode() != AuthorizationServiceException::OBJECT_IS_LOCKED) throw $ase;}
				        }
				        else $element = $this->doPersistElement($principal, $element, $fieldSelectorList);
				    }
				}
				// pushes the element further
				if(!$dataFlowContext->isCurrentStepTheLastStep()) {
					// if element has been inserted, then reads again from the database
					if($elementIsNew != $element->isNew() && $this->reloadElementAfterInsert) {
						// resets the WigiiBag
						$wigiiBag = $element->getWigiiBag();
						if(method_exists($wigiiBag, 'reset')) $wigiiBag->reset();
						else {
							$wigiiBag = $this->createWigiiBagInstance();
							$element->setWigiiBag($wigiiBag);
						}	
						// resets the FieldList
						$fieldList = $element->getFieldList();
						if(method_exists($fieldList, 'reset')) $fieldList->reset();
						else {
							$fieldList = FieldListArrayImpl::createInstance();
							$element->setFieldList($fieldList);
						}				
						// fills the element
						$elementP = $this->getElementService()->fillElement($principal, $element, $fieldSelectorList);
						$dataFlowContext->writeResultToOutput($elementP, $this);
					}
					else $dataFlowContext->writeResultToOutput($data, $this);
				}
				break;
			case self::MODE_DELETE:
			    // ignores new elements
			    if(!$elementIsNew) {
			        // if element already exists in database, then checks validity of its ElementInfo and prevents deleting if blocked
			        if($this->isElementBlocked($element)) {
			            if(!$this->ignoreLockedElements) throw new AuthorizationServiceException("Cannot delete an element having status blocked.", AuthorizationServiceException::NOT_ALLOWED);
			        }
			        else {
			            // if enableDeleteOnlyForAdmin=1, then prevents deleting element if current principal is not admin
			            if($this->getConfigService()->getParameter($principal, $element->getModule(), 'enableDeleteOnlyForAdmin') == "1" && !$this->isPrincipalAdmin($element, $dataFlowContext)) {
			                if(!$this->ignoreLockedElements) throw new AuthorizationServiceException("Element can only be deleted by Administrators.", AuthorizationServiceException::NOT_ALLOWED);
			            }
			            else {
			                // checks if an Element_beforeDeleteExp is defined in configuration and checks if deletion is possible
			                $beforeDeleteExp = (string)$this->getConfigService()->getParameter($principal, $element->getModule(), "Element_beforeDeleteExp");
			                $beforeDeleteExp = $this->evaluateBeforeDeleteExp($element,$dataFlowContext, $beforeDeleteExp);
			                if(!$beforeDeleteExp->okToDelete) {
			                    if(!$this->ignoreLockedElements) throw new AuthorizationServiceException("Element cannot be deleted: ".$beforeDeleteExp->message, AuthorizationServiceException::NOT_ALLOWED);
			                }			                
			                // else standard deletion of non-locked elements
			                else {
                				if($this->ignoreLockedElements) {
                					try {$this->doDeleteElement($principal, $element);}				
                					catch(AuthorizationServiceException $ase) {if($ase->getCode() != AuthorizationServiceException::OBJECT_IS_LOCKED) throw $ase;}
                				}
                				else $this->doDeleteElement($principal, $element);
			                }
			            }
			        }
			    }
				break;
			case self::MODE_IGNORE:
				if(!$dataFlowContext->isCurrentStepTheLastStep()) $dataFlowContext->writeResultToOutput($data,$this);
				break;
			// case MODE_FILTER :: does not touch element and does not push it any further.
		}		
	}
	public function endOfStream($dataFlowContext) {
		/* nothing to do */
	}
	
	
	// single data event handling
	
	public function processWholeData($data, $dataFlowContext) {
		$this->startOfStream($dataFlowContext);
		$this->processDataChunk($data, $dataFlowContext);
		return $this->endOfStream($dataFlowContext);
	}	
	
	// implementation	
	
	/**
	 * Decides which action should be executed with the object.
	 * Subclass should implement this method or set a closure using the setDecisionMethod
	 * @param Object $data current object in the data flow
	 * @param DataFlowContext $dataFlowContext the current data flow context
	 * @return int should return on of the following action code :
	 * MODE_PERSIST if the object should be persisted (updated or inserted) and go further in the data flow,
	 * MODE_DELETE if the object should be deleted and not go further in the data flow,
	 * MODE_FILTER if nothing should be done with the object and 
	 * the object should not go further in the data flow,
	 * MODE_IGNORE if nothing should be done with the object and 
	 * the object should go further in the data flow. 
	 */
	protected function decideActionOnObject($data, $dataFlowContext) {
		throw new DataFlowServiceException("implement the 'decideActionOnObject' method into a subclass or set a closure using the 'setDecisionMethod'", DataFlowServiceException::CONFIGURATION_ERROR);
	}
	
	
}