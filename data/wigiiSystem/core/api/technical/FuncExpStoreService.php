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
 * A service which stores FuncExp for later execution
 * Created by CWE on 21.08.2019
 */
class FuncExpStoreService
{
    private $_debugLogger;
    private $_executionSink;
    
    // Dependency injection
    
    private function debugLogger()
    {
        if(!isset($this->_debugLogger))
        {
            $this->_debugLogger = DebugLogger::getInstance("FuncExpStoreService");
        }
        return $this->_debugLogger;
    }
    private function executionSink()
    {
        if(!isset($this->_executionSink))
        {
            $this->_executionSink = ExecutionSink::getInstance("FuncExpStoreService");
        }
        return $this->_executionSink;
    }
    
    private $sessionAS;
    public function setSessionAdminService($sessionAdminService){
        $this->sessionAS = $sessionAdminService;
    }
    protected function getSessionAdminService(){
        // autowired
        if(!isset($this->sessionAS)){
            $this->sessionAS = ServiceProvider::getSessionAdminService();
        }
        return $this->sessionAS;
    }
    
    private $authoS;
    public function setAuthorizationService($authorizationService)
    {
        $this->authoS = $authorizationService;
    }
    protected function getAuthorizationService()
    {
        // autowired
        if(!isset($this->authoS))
        {
            $this->authoS = ServiceProvider::getAuthorizationService();
        }
        return $this->authoS;
    }
    
    // Service methods
    
    /**
     * Opens a given Func Exp to be callable from public scope
     * @param Principal $principal current principal requesting the opening
     * @param FuncExp $fx functional expression to be stored for later execution
     * @param String $token optional logical token used to identify the stored Func Exp
     * @param WigiiBPLParameter $options optional bag to configure the life time of the FuncExp. It supports:
     * - multipleCalls: Boolean. If true, then stored Func Exp can be retrieved multiple times, else it is removed from store when retrieved. Defaults to false.
     * - expirationTime: Int. Maximum number of seconds a Func Exp stays available into the store for retrieval. Defaults to 1min. 
     * @return String FuncExp key for retrieval
     */
    public function openFxForPublic($principal,$fx,$token=null,$options=null) {        
        $this->getAuthorizationService()->assertPrincipalAuthorized($principal, 'FuncExpStoreService', 'openFxForPublic');
        return $this->storeFx($principal, $fx, $token, $options);
    }
    
    /**
     * Retrieves a stored Func Exp
     * @param Principal $principal current principal retrieving the Func Exp to execute it
     * @param String $key key identifying the stored Func Exp
     * @param String $token optional token acting as a two factor credentials combined with the key to retrieve the stored Func Exp.
     * @return FuncExp stored FuncExp or null if not found
     */
    public function getStoredFx($principal,$key,$token) {
        $this->getAuthorizationService()->assertPrincipalAuthorized($principal, 'FuncExpStoreService', 'getStoredFx');
        $storedFxKey = $key.($token?'_'.$token:'');
        $storedFx = $this->getSessionAdminService()->getData($this, $storedFxKey);        
        $returnValue = null;
        if(isset($storedFx)) {
            $multipleCalls = false; $now = time();
            if($storedFx['expirationDate'] >= $now) {
                $returnValue = $storedFx['fx'];                
                $returnValue = str2fx($returnValue);
                $multipleCalls = ($storedFx['multipleCalls']==true);
                if($multipleCalls) {
                    $storedFx['expirationDate'] = $now + $storedFx['expirationTime'];
                    $this->getSessionAdminService()->storeData($this, $storedFxKey, $storedFx);
                }
            }
            if(!$multipleCalls) $this->getSessionAdminService()->clearData($this, $storedFxKey);
        }        
        return $returnValue;
    }
    
    // Implementation
    
    /**
     * Stores a Func Exp into the session
     * @param Principal $principal current principal storing the Func Exp
     * @param FuncExp $fx functional expression to be stored for later execution
     * @param String $token optional logical token used to identify the stored Func Exp
     * @param WigiiBPLParameter $options optional bag to configure the life time of the FuncExp. It supports:
     * - multipleCalls: Boolean. If true, then stored Func Exp can be retrieved multiple times, else it is removed from store when retrieved. Defaults to false.
     * - expirationTime: Int. Maximum number of seconds a Func Exp stays available into the store for retrieval. Defaults to 1min.
     * @return String FuncExp key for retrieval
     */
    protected function storeFx($principal,$fx,$token=null,$options=null) {
        if(!isset($fx)) throw new FuncExpStoreServiceException('fx cannot be null', FuncExpStoreServiceException::INVALID_ARGUMENT);
        // initializes default options
        if(!isset($options)) $options = wigiiBPLParam();
        if($options->getValue('multipleCalls')===null) $options->setValue('multipleCalls', false);
        if($options->getValue('expirationTime')===null) $options->setValue('expirationTime', 60);
        // prepares stored Fx
        $storedFx = array();
        $storedFx['fx'] = fx2str($fx);
        if($token) $storedFx['token'] = $token;
        $storedFx['multipleCalls'] = $options->getValue('multipleCalls');
        $storedFx['expirationTime'] = $options->getValue('expirationTime');
        $storedFx['expirationDate'] = time() + $storedFx['expirationTime'];
        // stores Fx in session and returns retrieval key
        $returnValue = md5($storedFx['fx']);
        $this->getSessionAdminService()->storeData($this, $returnValue.($token?'_'.$token:''), $storedFx);
        return $returnValue;
    }
}