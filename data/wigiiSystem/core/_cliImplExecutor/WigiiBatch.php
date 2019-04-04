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
 * Created on 19 march 2013
 * by CWE
 */

/**
 * A base class to execute batches in Wigii
 */
abstract class WigiiBatch {		
	
	// Batch execution context
	private $principal;
	/**
	 * Sets the principal executing the batch
	 */
	public function setPrincipal($principal) {
		$this->principal = $principal;
	}
	/**
	 * Returns the principal executing the batch
	 */
	public function getPrincipal() {
		return $this->principal;
	}
	
	private $client;
	/**
	 * Sets the client against which the batch is executing
	 */
	public function setClient($client) {
		$this->client = $client;
	}
	/**
	 * Returns the client against which the batch is executed
	 */
	public function getClient() {
		return $this->client;
	}
	
	/**
	 * Sets the current language in the TranslationService
	 * @param String $lang a valid installed language. 
	 * See TranslationService->getInstalledLanguage
	 */
	public function setLanguage($lang) {
		if(isset($lang)) ServiceProvider::getTranslationService()->setLanguage($lang);
	}
	
	// Batch execution
	
	/**
	 * Runs the batch with the given arguments.
	 * The batch can throw an exception in case of error.
	 * @param int $argc number of arguments on the whole command line
	 * @param array $argv array with all arguments
	 * @param int $subArgIndex index in argv array from which starts the batch specific arguments
	 */
	public abstract function run($argc, $argv, $subArgIndex);	
}