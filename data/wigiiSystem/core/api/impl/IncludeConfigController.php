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
 *  @copyright  Copyright (c) 2019  Wigii.org
 *  @author     <http://www.wigii.org/system>      Wigii.org 
 *  @link       <http://www.wigii-system.net>      <https://github.com/wigii/wigii>   Source Code
 *  @license    <http://www.gnu.org/licenses/>     GNU General Public License
 */

/**
 * A configuration controller which interprets the 'include' xml attribute and 
 * replaces the content of node with the included xml file.
 * Created by CWE on 28.03.2019
 */
class IncludeConfigController extends ConfigControllerWithFuncExpVM {    
    private $_debugLogger;
    private $includeNodes;
    private $configCache = array(); // config cache is living during whole php execution.
    
    // Object lifecycle
    
    public function reset() {
        parent::reset();
    }
    
    public function freeMemory() {
        unset($this->includeNodes);
        parent::freeMemory();
    }
       
    // Dependency injection
    
    private function debugLogger()
    {
        if(!isset($this->_debugLogger))
        {
            $this->_debugLogger = DebugLogger::getInstance("IncludeConfigController");
        }
        return $this->_debugLogger;
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
    
    // ConfigControllerWithFuncExpVM implementation
    
    public function processConfigurationNode($principal, $xmlConfig, $getWritableNode, $lp) {
        // gets all nodes having include attribute
        $this->includeNodes = $xmlConfig->xpath('//*[@include]');
        if(!empty($this->includeNodes)) {
            // runs again the xpath on a writable node
            $this->includeNodes = $getWritableNode->invoke()->xpath('//*[@include]');
            // processes the include nodes
            return parent::processConfigurationNode($principal, $xmlConfig, $getWritableNode, $lp);
        }
        else return false;
    }
    
    protected function doProcessConfigurationNode($principal, $xmlConfig, $getWritableNode, $lp) {
        $updatedXml = false;
        if(!empty($this->includeNodes)) {
            //$isPublicPrincipal = $this->getAuthorizationService()->isPublicPrincipal($principal);
            $this->debugLogger()->logBeginOperation('doProcessConfigurationNode');
            foreach($this->includeNodes as $includeNode) {
                //$this->debugLogger()->write($includeNode->asXml());
                // gets include exp
                $includeExp = (string)$includeNode['include'];
                $result=null;
                if(!empty($includeExp)) {
                    try {
                        // try to parse the include exp into a funcExp 
                        try {$includeExp = str2fx($includeExp);}
                        catch(StringTokenizerException $ste) {if($ste->getCode() != StringTokenizerException::SYNTAX_ERROR) throw $ste;}                    
                        // executes the func exp
                        $includeExp = $this->evaluateFuncExp($includeExp);
                        // replaces existing node with included one
                        if($includeExp instanceof ConfigIncludeSelector) {
                            $result = $this->getXmlNodeToInclude($principal, $includeExp->getFilePath(),$includeExp->getNodePath());
                            if(isset($result)) {
                                // if only selected xml attributes, then updates the xml node attributes with the included attributes
                                $xmlAttributes = $includeExp->getXmlAttr();
                                if(!empty($xmlAttributes)) {
                                    foreach($xmlAttributes as $xmlAttr) {
                                        simplexml_addAttribute($includeNode,$xmlAttr,(string)$result[$xmlAttr]);
                                    }
                                }
                                // else replaces node with included one
                                else {
                                    simplexml_replaceNode($includeNode, $result);
                                }
                                $updatedXml = true;
                            }
                        }
                        elseif($includeExp!='') {
                            $result = $this->getXmlNodeToInclude($principal, $includeExp);
                            if(isset($result)) {
                                simplexml_replaceNode($includeNode, $result);
                                $updatedXml = true;
                            }
                        }                        
                    }
                    catch(Exception $e) {
                        $message="Configuration error";
                        if(!empty($lp) && $lp['moduleName']) $message.=" in module '".$lp['moduleName']."'";
                        $message.=" Invalid include expression: \n";
                        $message.=$e->getMessage().' ('.$e->getCode().')';
                        throw new ConfigServiceException($message,ConfigServiceException::CONFIGURATION_ERROR);
                    }
                }
            }
            $this->debugLogger()->logEndOperation('doProcessConfigurationNode');
        }
        return $updatedXml;
    }
    
    protected function getXmlNodeToInclude($principal,$configFilePath,$nodePath=null) {
        $returnValue = null;
        // sanitizes path in class name
        $configFilePath = str_replace(array('../','..\\'), '', $configFilePath);
        
        // checks if xml node already exists in cache
        $returnValue = $this->configCache[$configFilePath];
        // if not, then loads it
        if(!isset($returnValue)) {
            $configFile = $configFilePath;        
            // detects loading of a file located in a configPack folder
            if(strpos($configFile,'configPack/')===0) {
                $sep = strrpos($configFile, '/');
                $configPackPath = substr($configFile,0,$sep+1);
                $configFile = substr($configFile,$sep+1);
            }
            else $configPackPath=null;
            // appends .xml if not present
            if(strpos($configFile,'.xml')===false) $configFile.='.xml';
            // checks authorization before loading file
            $this->assertPrincipalAuthorizedForIncludeConfig($principal, $configFile, $configPackPath);
            // loads xml if file exists        
            $configFile = str_replace('configPack/',CONFIGPACK_PATH,$configPackPath).$configFile;
            if(!file_exists($configFile)) throw new ConfigServiceException('Configuration file '.$configFilePath.' does not exist', ConfigServiceException::CONFIGURATION_ERROR);
            $returnValue = simplexml_load_file($configFile);
            if($returnValue===false || !isset($returnValue)) throw new ConfigServiceException('Error reading configuration file '.$configFilePath, ConfigServiceException::CONFIGURATION_ERROR);
            // adds xml file to in memory cache
            $this->configCache[$configFilePath] = $returnValue;
            // selected node path if defined
            if(isset($nodePath)) {
                if(strpos($nodePath,'/')!==0) $nodePath = './'.$nodePath;
                $returnValue = $returnValue->xpath($nodePath);
                if($returnValue===false || empty($returnValue)) throw new ConfigServiceException('Invalid node selector '.$nodePath.' in configuration file '.$configFilePath, ConfigServiceException::CONFIGURATION_ERROR);
                $returnValue = reset($returnValue);
            }            
        }
        return $returnValue;
    }
    protected function assertPrincipalAuthorizedForIncludeConfig($principal, $configFile, $configPackPath=null)
    {
        /* nothing for now, always authorized */
    }
}