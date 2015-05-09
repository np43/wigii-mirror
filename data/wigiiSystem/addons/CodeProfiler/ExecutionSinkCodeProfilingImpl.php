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

/* Execution flow sink for performance monitoring
 * Created by CWE on 8.2.2013
 */
class ExecutionSinkCodeProfilingImpl extends ExecutionSink
{	
	private $codeProfiler;
	
	// dependency injection

	public function setCodeProfiler($codeProfiler)
	{
		$this->codeProfiler = $codeProfiler;
	}
	protected function getCodeProfiler()
	{
		// autowired
		if(!isset($this->codeProfiler))
		{
			$this->codeProfiler = TechnicalServiceProviderCodeProfilingImpl::getCodeProfiler();
		}
		return $this->codeProfiler;
	}
	
	
	// Implementation
	
	/**
	 * Publishes the start of an operation
	 * principal: optional argument specifying the actual authenticated user performing the operation
	 */
	public function publishStartOperation($operation, $principal=null)
	{
		$this->getCodeProfiler()->publishStartOperation($this->getAttachedClass(), $operation, $principal);		
	}

	/**
	 * Publishes the end of an operation
	 * principal: optional argument specifying the actual authenticated user performing the operation
	 */
	public function publishEndOperation($operation, $principal=null)
	{
		$this->getCodeProfiler()->publishEndOperation($this->getAttachedClass(), $operation, $principal);		
	}

	/**
	 * Publishes the end of an operation in case of error
	 * exception: the error
	 * principal: optional argument specifying the actual authenticated user performing the operation
	 */
	public function publishEndOperationOnError($operation, $exception, $principal=null)
	{
		$this->getCodeProfiler()->publishEndOperationOnError($this->getAttachedClass(), $operation, $exception, $principal);		
	}

	public function log($prodLogMessage)
	{
		/* does nothing */
	}
}




