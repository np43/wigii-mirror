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
 * Created on 30 june 2010
 * by LWR
 */

class BasicFormExecutor extends FormExecutor {

	public static function createInstance($wigiiExecutor, $record, $formId, $submitUrl){
		$fe = new self();
		$fe->setWigiiExecutor($wigiiExecutor); //important to be in the begining because other setter could use the configurationContext as configService
		$fe->setRecord($record);
		$fe->setFormId($formId);
		$fe->setSubmitUrl($submitUrl);
		return $fe;
	}
	
	private $_debugLogger;
	private $_executionSink;
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("FormExecutor");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("FormExecutor");
		}
		return $this->_executionSink;
	}

	protected function doSpecificCheck($p, $exec){
		throw new ServiceException("UNSUPPORTED_OPERATION, This class should be used only for the preventInjection and uploadedFileManagement", ServiceException::UNSUPPORTED_OPERATION);
	}

	protected function actOnCheckedRecord($p, $exec){
		throw new ServiceException("UNSUPPORTED_OPERATION, This class should be used only for the preventInjection and uploadedFileManagement", ServiceException::UNSUPPORTED_OPERATION);
	}

	protected function doRenderForm($p, $exec){
		throw new ServiceException("UNSUPPORTED_OPERATION, This class should be used only for the preventInjection and uploadedFileManagement", ServiceException::UNSUPPORTED_OPERATION);
	}
	
}



