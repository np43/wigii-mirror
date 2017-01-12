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
 * WebExecutor base implementation to handle Wigii http requests.
 * Supports GET and POST request,
 * Form management and File uploads,
 * Wigii update protocol and direct http request.
 */
class WebServiceFormExecutor extends FormExecutor implements WebExecutor {
	
	// Dependency injection
	
	private $_debugLogger;
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("WebServiceFormExecutor");
		}
		return $this->_debugLogger;
	}
	private $_executionSink;
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("WebServiceFormExecutor");
		}
		return $this->_executionSink;
	}
	
	private $wigiiExecutor;
	public function setWigiiExecutor($wigiiExecutor){$this->wigiiExecutor = $wigiiExecutor;}
	/**
	 * @return WigiiExecutor
	 */
	public function getWigiiExecutor(){return $this->wigiiExecutor; }
	
	private $configService;
	public function setConfigService($configService) {$this->configService = $configService;}
	/**
	 * @return ConfigService
	 */
	public function getConfigService() {return $this->configService;}
	
	
	// WebExecutor implementation
	
	public function isMinimalPrincipalAuthorized() {return false;}
	public function isPublicPrincipalAuthorized()  {return false;}
	
	public function processAndEnds($p,$exec) {
		/* does nothing */
	}	
	
	// FormExecutor implementation
	
	protected function doSpecificCheck($p, $exec){/*nothing to do*/}

	protected function actOnCheckedRecord($p, $exec){/*nothing to do*/}

	protected function doRenderForm($p, $exec){/*nothing to do*/}
	
	// Business delegates
	
	/**
	 * Fetches an Element in the database given its ID
	 * @param Principal $p current principal
	 * @param ExecutionService $exec current request
	 * @param int $id Element ID
	 * @param WigiiBPLParameter $options some options for the fetch process
	 * @return ElementP
	 */
	protected function fetchElement($p,$exec,$id,$options=null) {
		// prepares fetching parameters
		$param = TechnicalServiceProvider::createWigiiBPLParameterInstance();
		$param->setValue('elementId', $id);
		$param->setValue('wigiiNamespace', $exec->getCrtWigiiNamespace());
		$param->setValue('module', $exec->getCrtModule());
		// fetches element
		$wigiiBPL = ServiceProvider::getWigiiBPL();
		$elementP = $wigiiBPL->elementFetch($p, $this, $param);
		// if element does not exist or is not readable throw a NOT_FOUND exception
		if(is_null($elementP)) throw $this->createElementNotFoundException($p, $id);
		return $elementP;
	}
	
	protected function createElementNotFoundException($p,$elementId) {
		$transS = ServiceProvider::getTranslationService();
		return new ElementServiceException($transS->t($p,'elementUnreachable'),ElementServiceException::NOT_FOUND);
	}
}



