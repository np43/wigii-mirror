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
 * HelpService main controller, manages Wigii /WigiiNamespace/Module/help/*  update URLs.
 * Created by CWE on November 20th 2015.
 */
class HelpServiceFormExecutor extends WebServiceFormExecutor {
	private $_debugLogger;
	private $_executionSink;

	// Dependency injection
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("HelpServiceFormExecutor");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("HelpServiceFormExecutor");
		}
		return $this->_executionSink;
	}

	// HelpService WebExecutor implementation
	
	public function processAndEnds($p,$exec) {
		try {
			// detects help request
			$args = $exec->getCrtParameters();
			if(empty($args)) $nArgs = 0; else $nArgs = count($args);
			if($nArgs > 0) {
				$id = ValueObject::createInstance();
				$fieldName = ValueObject::createInstance();
				// /item/id/fieldName
				if(arrayMatch($args, 'item',$id,$fieldName)) {
					$this->displayHelpFile($p, $exec, $id->getValue(), $fieldName->getValue());
				}
				else throw new FormExecutorException('Help not found',FormExecutorException::NOT_FOUND);
			}
			// else throws NOT_FOUND exception
			else throw new FormExecutorException('Help not found',FormExecutorException::NOT_FOUND);
		}
		catch(Exception $e) {$this->getWigiiExecutor()->pushJson($p,$exec,$e);}
	}
	
	/**
	 * Pushes Help content back to client taken from an Element field of type File. 
	 * @param Principal $p current principal
	 * @param ExecutionService $exec current request
	 * @param int $id Element ID containing some help content
	 * @param String $fieldName name of the field of type Files containing the help content.
	 * @param WigiiBPLParameter $options a map of options for the display process.
	 */
	public function displayHelpFile($p,$exec,$id,$fieldName,$options=null) {
		// fetches help content
		$element = $this->fetchElement($p, $exec, $id, $options)->getElement();
		$field = $element->getFieldList()->getField($fieldName);
		$fieldXml = $field->getXml();
		
		if (($field->getDataType() instanceof Files) && $fieldXml["htmlArea"] == "1") {
			$content = $element->getFieldValue($fieldName, "textContent");
		} else throw new FormExecutorException('Unsupported help content format',FormExecutorException::UNSUPPORTED_OPERATION);
				
		// renders help content
		if($content) echo $content;
		else throw new FormExecutorException('Help not found',FormExecutorException::NOT_FOUND);
	}
}