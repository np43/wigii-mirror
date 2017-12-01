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
 * Created by LWR on 21 april 2010
 * Modified by Medair(CWE) on 01.09.2017 to authorize public principal to be groupCreator
 */
class AuthorizationServiceWebImpl extends AuthorizationServiceImpl {

	private $_debugLogger;
	private $_executionSink;
	private $moduleAS;

	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("AuthorizationServiceWebImpl");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("AuthorizationServiceWebImpl");
		}
		return $this->_executionSink;
	}
	
	//dependancy injection
	
	public function setModuleAdminService($moduleAdminService)
	{
		$this->moduleAS = $moduleAdminService;
	}
	protected function getModuleAdminService()
	{
		// autowired
		if(!isset($this->moduleAS))
		{
			$this->moduleAS = ServiceProvider::getModuleAdminService();
		}
		return $this->moduleAS;
	}
	
	
	public function __construct()
	{
		$this->debugLogger()->write("creating instance");
	}
	
	protected function createPublicPrincipal($wigiiNamespace, $moduleAccess)
	{
		if(is_null($wigiiNamespace)) throw new AuthorizationServiceException("wigiiNamespace cannot be null", AuthorizationServiceException::INVALID_ARGUMENT);
		$publicPrincipal = Principal::createInstanceFromArray(array(
			"username"=>'AUTZSIPP_webImpl',
			"wigiiNamespace"=>$wigiiNamespace,
			"moduleAccess"=>$moduleAccess,
		    "groupCreator"=>$moduleAccess
		));
		return $publicPrincipal;
	}
	
	public function assertPrincipalAuthorized($principal, $serviceName, $methodName)
	{
		switch($serviceName)
		{
			case "ElementService":
				switch($methodName)
				{
					case "fillEmptyEmailValidationAndExternalCode":
					case "correctEmailValidationCode":
						$this->assertPrincipalIsRootOrHasAttachedUser($principal);
						return null;
					case "setElementState": //needed for external access
					case "updateElement": //needed for external access
					case "deleteElement": //needed for external access
						if($this->isPublicPrincipal($principal)){
							return PrincipalRights::createInstance(array("canWriteElement"=>true, "canShareElement"=>true));
						} else {
							$this->assertPrincipalIsRootOrHasAttachedUser($principal);
						}
						return null; /* principal gets no special rights */
					case "fillElementFromExternalCode":
					case "getElementInfoFromExternalCode":
						$this->assertPrincipalIsRootOrIsPublic($principal);
						return null; /* principal gets no special rights */
					case "setExternalAccess":
						$this->assertPrincipalIsRootOrIsPublicOrHasAttachedUser($principal);
						return null; /* principal gets no special rights */
					case "validateEmailFromCode":
					case "unsubscribeEmailFromCode":
						$this->assertPrincipalIsPublic($principal);
						return null; /* principal gets no special rights */
				}
				break;
			case "EmailService":
				switch($methodName)
				{
					case "send":
						$this->assertPrincipalIsRootOrIsPublicOrHasAttachedUser($principal);
						return null; /* principal gets no special rights */
					case "wakeup":
						$this->assertPrincipalIsRoot($principal);
						return null; /* principal gets no special rights */
				}
				break;
		}
		return parent::assertPrincipalAuthorized($principal, $serviceName, $methodName);
	}
	
}


