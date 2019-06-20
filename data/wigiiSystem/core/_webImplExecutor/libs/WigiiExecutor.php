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
 * Wigii system http facade.
 * Main service which interprets the Wigii communication protocol and allows extension by plugin WebExecutor instances.
 * Created on 24 juil. 09 by LWR
 * Refactored in two classes WigiiExecutor and WigiiCoreExecutor by CWE on November 23rd 2015.
 * Modified by Medair in 2016 to integrate Box and QlikSense
 */
class WigiiExecutor extends WigiiCoreExecutor {

	// Dependency injection

	private $_debugLogger;
	private function debugLogger() {
		if (!isset ($this->_debugLogger)) {
			$this->_debugLogger = DebugLogger :: getInstance("WigiiExecutor");
		}
		return $this->_debugLogger;
	}

	private $_executionSink;
	private function executionSink() {
		if (!isset ($this->_executionSink)) {
			$this->_executionSink = ExecutionSink :: getInstance("WigiiExecutor");
		}
		return $this->_executionSink;
	}
	
	private $webExecutorActionMapping;
	/**
	 * Defines a mapping between an URL action and a WebExecutor class name
	 * @param Array $map an array of the form [action => className]
	 * @example array('help'=>'HelpServiceFormExecutor','c'=>'LighClientFormExecutor')
	 */
	public function setWebExecutorActionMapping($map) {
		if(!is_array($map)) throw new ServiceException('map should be an array of the form [action=>webExecutorClassName]', ServiceException::INVALID_ARGUMENT);
		if(!isset($this->webExecutorActionMapping)) $this->webExecutorActionMapping = array();
		$this->webExecutorActionMapping = array_merge($this->webExecutorActionMapping,$map);
	}
	/**
	 * Returns the defined mapping of URL actions
	 * @param String $action url action name, if defined, then returns only the WebExecutor class name associated to this action
	 * else returns whole array or null if not defined.
	 * @return Array|String array if action is null, class name if action is defined, null if not defined.
	 */
	protected function getWebExecutorActionMapping($action=null) {
		$returnValue = null;
		if(isset($this->webExecutorActionMapping)) {
			if(isset($action)) $returnValue = $this->webExecutorActionMapping[$action];
			else $returnValue = $this->webExecutorActionMapping;
		}
		return $returnValue;
	}
	
	// Specialization
	
	protected function shouldByPassHeader($action) {
		switch ($action) {
			case 'public': return true;
			case 'inpublic': return false;
			case 'fx': return true;
			case 'infx': return false;
			case 'box': return true;
			case 'www': return true;
			case 'inwww': return false;
			case 'qliksense': return true;
			default: return parent::shouldByPassHeader($action);
		}
	}
	protected function shouldByPassFooter($action, $parameters) {
		switch ($action) {
			case 'public': return true;
			case 'inpublic': return false;
			case 'fx': return true;
			case 'infx': return false;
			case 'box': return true;
			case 'www': return true;
			case 'inwww': return false;
			case 'qliksense': return true;
			case 'help':return true;
			default: return parent::shouldByPassFooter($action, $parameters);
		}
	}	
	
	// WebExecutor provider
	
	protected function findWebExecutorForAction($action) {
		// first looks in injected mapping
		$returnValue = $this->getWebExecutorActionMapping($action);
		// if not defined, returns default mapping
		if(empty($returnValue)) {
			switch($action) {
				case "c": 
				    if(defined("LightClient_Authorized_DirectCalls") && LightClient_Authorized_DirectCalls) $returnValue = (object)array('className'=>'LightClientFormExecutor','options'=>ObjectConfigurator::createInstance(array('setRootPrincipal'=>$this->getRootPrincipal())));
				    else throw new AuthorizationServiceException("Access to LightClient is forbidden", AuthorizationServiceException::FORBIDDEN);
				    break;
				case "help": $returnValue = 'HelpServiceFormExecutor'; break;
				case "public": $returnValue = 'PublicWebServiceFormExecutor'; break;
				case "inpublic": $returnValue = (object)array('className'=>'PublicWebServiceFormExecutor','options'=>ObjectConfigurator::createInstance(array('setIsIntegrated'=>true))); break;
				case "fx": $returnValue = 'FxWebServiceFormExecutor'; break;
				case "infx": $returnValue = (object)array('className'=>'FxWebServiceFormExecutor','options'=>ObjectConfigurator::createInstance(array('setIsIntegrated'=>true))); break;
				case "box" : $returnValue = 'BoxServiceFormExecutor'; break;
				case "www": $returnValue = (object)array('className'=>'WigiiWebCMSFormExecutor','options'=>ObjectConfigurator::createInstance(array('setPublicPrincipal'=>$this->getPublicPrincipal()))); break;
				case "inwww": $returnValue = (object)array('className'=>'WigiiWebCMSFormExecutor','options'=>ObjectConfigurator::createInstance(array('setIsIntegrated'=>true,'setPublicPrincipal'=>$this->getPublicPrincipal()))); break;
				case "qliksense" : $returnValue = 'QlikSenseFormExecutor'; break;
				default: $returnValue = null;
			}
		}	
		return $returnValue;
	}
}