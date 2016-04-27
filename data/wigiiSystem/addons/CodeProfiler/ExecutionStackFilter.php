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

/* Execution stack filter
 * Created by CWE on 8.2.2013
 */
class ExecutionStackFilter
{	
	// State machine
	const STATE_NOT_STARTED = 0;
	const STATE_MOVING = 1;
	const STATE_ERROR = 2;
	const STATE_CUT = 3;
	private $state;
	
	// Execution stack
	private $execStack;
	private $stackIndex;
	private $cutIndex;
	
	// Instance creation
	
	public static function createInstance() {
		return new ExecutionStackFilter();
	}
	
	protected function __construct() {
		$this->state = ExecutionStackFilter::STATE_NOT_STARTED;
		$this->execStack = array();
		$this->stackIndex = 0;
		$this->cutIndex = 0;
	}
	 
	// Filter implementation
	
	/**
	 * Returns true if this principal should be profiled, else false
	 */
	protected function shouldProfilePrincipal($principal)
	{		
		if(!isset($principal) || $principal == null) return false;		
		return $principal->getRealUsername() == 'profiler';
	}
	/**
	 * Returns true if this operation in this class should be profiled, else false
	 */
	protected function shouldProfileOperation($classname, $operation)
	{
		switch($classname)
		{			
			case "WigiiExecutor":
			case "FormExecutor":
			case "ElementServiceImpl":
			case "DataFlowServiceImpl":			
				return true;
		}
		return false;
	}
	/**
	 * Returns true if this operation in this class and all the stack below should not be profiled
	 */
	protected function shouldCutOperation($classname, $operation)
	{		
		switch($classname)
		{
			case "FormExecutor":
			case "WigiiExecutor":
			case "ElementServiceImpl":
			case "GroupAdminServiceImpl":
			case "UserAdminServiceImpl":
			case "DbAdminServiceImpl":
			case "MySqlFacade":
			case "MySqlFacadeWithPConnections" :	
			case "MySqlFacadeCodeProfilingImpl":	
			case "ReportingElementEvaluator":		
			case "DataFlowServiceImpl":
			case "GuzzleHelper":
			case "JasperReportsFacade":
				return false;
		}
		return true;
	}
	
	// Code profiler listener
	
	/**
	 * Returns true if should act on the start of an operation
	 */
	public function shouldActOnStartOperation($className, $operation, $principal)
	{
		if($this->shouldProfilePrincipal($principal))
		{
			$this->pushOperation($className, $operation);
			return $this->state == ExecutionStackFilter::STATE_MOVING;
		}
		else return false;
	}

	/**
	 * Returns true if should act on the end of an operation	 
	 */
	public function shouldActOnEndOperation($className, $operation, $principal)
	{
		if($this->shouldProfilePrincipal($principal))
		{
			$returnValue = $this->state == ExecutionStackFilter::STATE_MOVING;
			$this->popOperation($className, $operation);
			return $returnValue;
		}
		else return false;
	}

	/**
	 * Returns true if should act on the end of an operation in case of error
	 */
	public function shouldActOnEndOperationOnError($className, $operation, $exception, $principal)
	{
		if($this->shouldProfilePrincipal($principal))
		{
			$returnValue = $this->state == ExecutionStackFilter::STATE_MOVING;
			$this->popOperation($className, $operation);
			return $returnValue;
		}
		else return false;
	}

	/**
	 * Returns true if should act on an SQL query
	 */
	public function shouldActOnSql($className, $sqlCode, $principal)
	{
		if($this->shouldProfilePrincipal($principal))
		{
			return $this->state == ExecutionStackFilter::STATE_MOVING;
		}
		else return false;
	} 
	
	protected function pushOperation($className, $operation) {
		if($this->state != ExecutionStackFilter::STATE_ERROR) {
			if($this->state == ExecutionStackFilter::STATE_NOT_STARTED &&
				$this->shouldProfileOperation($className, $operation)) 
			{
				$this->state = ExecutionStackFilter::STATE_MOVING;
			}
			else if($this->state == ExecutionStackFilter::STATE_MOVING && 
				$this->shouldCutOperation($className, $operation))
			{
				$this->state = ExecutionStackFilter::STATE_CUT;
				$this->cutIndex = $this->stackIndex;
			}
			if($this->state == ExecutionStackFilter::STATE_MOVING ||
				$this->state == ExecutionStackFilter::STATE_CUT) {
				$this->stackIndex++;
				$this->execStack[$this->stackIndex] = $className.'.'.$operation;
			}
		}
	}
	protected function popOperation($className, $operation) {
		$op = $className.'.'.$operation;
		while($this->stackIndex > 0 && $op != $this->execStack[$this->stackIndex]) {
			$this->stackIndex--;
		}
		if($this->stackIndex > 0) {
			$this->stackIndex--;
			if($this->stackIndex == 0) {
				$this->state = ExecutionStackFilter::STATE_NOT_STARTED;	
			}
			else if($this->cutIndex == $this->stackIndex) {
				$this->state = ExecutionStackFilter::STATE_MOVING;
			}
		}
		else ExecutionStackFilter::STATE_ERROR;
	}
}




