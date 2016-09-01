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
 * A WebExecutor plugin which handles WigiiExecutor request delegation
 * Created by CWE on November 23rd 2015
 */
interface WebExecutor {
	
	// Dependency injection
	
	/**
	 * Sets the WigiiExecutor instance which delegates the http request handling
	 * @param WigiiExecutor $wigiiExecutor
	 */
	public function setWigiiExecutor($wigiiExecutor);
	
	/**
	 * Sets the ConfigService instance that should be used to access to the configuration
	 * @param ConfigService $configService
	 */
	public function setConfigService($configService);
	
	// Configuration
	
	/**
	 * Returns true if MinimalPrincipal is authorized to run this WebExecutor, else false.
	 */
	public function isMinimalPrincipalAuthorized();
	
	/**
	 * Returns true if PublicPrincipal is authorized to run this WebExecutor, else false.
	 */
	public function isPublicPrincipalAuthorized();
	
	
	// HTTP request delegation
	
	/**
	 * Processes the http request and returns.
	 * HTML code can be directly output by using echo function, 
	 * or buffered using a TemplateRecordManager instance (WigiiExecutor::createTRM()),
	 * JSON object serialization can be done using WigiiExecutor::pushJSON method,
	 * JS code can be output by using ExecutionService::addJsCode() method.
	 * Wigii API can be accessed using the ServiceProvider class,
	 * State can be stored into the session using the SessionAdminService.
	 * This object is exclusive and not recycled. Its lifecycle starts with the http request and ends when processing is finished.
	 * @param Principal $p current authenticated user executing the request. (Principal is never a minimal principal).
	 * @param ExecutionService $exec ExecutionService instance holding all the request details.
	 * @throws Exception this method can throw exceptions which will be handled by the WigiiExecutor.
	 */
	public function processAndEnds($p,$exec);
}
