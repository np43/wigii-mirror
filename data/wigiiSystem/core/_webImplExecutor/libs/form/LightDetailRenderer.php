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
 * DetailRenderer implementation used by LightClientFormExecutor
 * Created by Medair (CWE,LMA) on 16.12.2016
 * Refactored by Wigii.org (CWE) on 17.06.2019 to allow calls from WigiiApi.js
 */
class LightDetailRenderer extends DetailRenderer {
	
	// Dependency injection
	
	private $_debugLogger;
	private $_executionSink;
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("LightDetailRenderer");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("LightDetailRenderer");
		}
		return $this->_executionSink;
	}
	
	
	// Object lifecycle
	
	
	/**
	 * Creates and initializes a new instance of LightDetailRenderer
	 * @param String $detailId
	 * @param LightClientTRM $templateRecordManager
	 * @return LightDetailRenderer
	 */
	public static function createInstance($detailId, $templateRecordManager, $totalWidth=null, $labelWidth = null, $visibleLanguage=null) {
		$returnValue = new self();
		$returnValue->setTemplateRecordManager($templateRecordManager);
		$returnValue->setDetailId($detailId);
		$returnValue->initializeFieldWidth($totalWidth, $labelWidth);
		$returnValue->setVisibleLanguage($visibleLanguage);
		return $returnValue;
	}
}


