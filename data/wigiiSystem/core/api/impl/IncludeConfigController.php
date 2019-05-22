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
 *  @copyright  Copyright (c) 2019  Wigii.org
 *  @author     <http://www.wigii.org/system>      Wigii.org 
 *  @link       <http://www.wigii-system.net>      <https://github.com/wigii/wigii>   Source Code
 *  @license    <http://www.gnu.org/licenses/>     GNU General Public License
 */

/**
 * A configuration controller which interprets the 'include' xml attribute 
 * and replaces the content of node with the included xml file.
 * Created by CWE on 10.04.2019
 */
class IncludeConfigController extends ConfigControllerWithFuncExpVM {    
    private $_debugLogger;
    private $includeNodes;    
    private $includeCache = array(); // include cache is living during whole php execution.
    
    private $configCache = array(); // config cache is living during whole php execution.
    const CACHE_LEVEL_NONE = 0;
    const CACHE_LEVEL_NAVIGATE = 1;
    const CACHE_LEVEL_SESSION = 2;
    const CACHE_LEVEL_PUBLIC = 3;
    
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
    
    private $configS;
    public function setConfigService($configService) {
        $this->configS = $configService;
    }
    protected function getConfigService() {
        // autowired
        if(!isset($this->configS)){
            $this->configS = ServiceProvider::getConfigService();
        }
        return $this->configS;
    }
    
    // ConfigControllerWithFuncExpVM implementation
    
    public function processConfigurationNode($principal, $xmlConfig, $getWritableNode, $lp) {
        // gets all nodes having include attribute
        $this->includeNodes = $xmlConfig->xpath('//*[@include]');
        if(!empty($this->includeNodes)) {                       
            // checks if resolved xml already exists in cache
            $returnValue = false; $lpParamAndXml=null;
            if(!empty($lp)) {
                $lpParamAndXml = '('.$lp['moduleName'].'('.$lp['clientName'].'('.$lp['wigiiNamespaceName'].'('.$lp['groupName'].'('.$lp['username'].')))))';
                $modifiedXml = $this->getCachedXml($lpParamAndXml,self::CACHE_LEVEL_SESSION);
                if(isset($modifiedXml)) {
                    // if modified xml exists in cache, then merges it with existing config
                    $this->mergeXmlNodeWithInclude($getWritableNode->invoke(), $modifiedXml);
                    $this->debugLogger()->write('found xml in cache for lp '.$lpParamAndXml);
                    $returnValue = true;
                }
            }            
            // if not in cache, then runs again the xpath on a writable node
            if(!$returnValue) {
                $this->includeNodes = $getWritableNode->invoke()->xpath('//*[@include]');
                // processes the include nodes and stores modified xml in cache
                if(parent::processConfigurationNode($principal, $xmlConfig, $getWritableNode, $lp)) {
                    if(isset($lpParamAndXml)) {
                        $modifiedXml = $getWritableNode->invoke();
                        $this->cacheXml($lpParamAndXml, $modifiedXml, self::CACHE_LEVEL_SESSION);
                    }
                    $returnValue=true;
                }
            }
        }
        return $returnValue;
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
                        // if evaluates to false, then node is removed from xml tree
                        if($includeExp===false) {
                            simplexml_removeNode($includeNode);
                            $updatedXml=true;
                        }
                        // replaces existing node with included one
                        elseif($includeExp instanceof ConfigIncludeSelector) {
                            $result = $this->getXmlNodeToInclude($principal, $includeExp->getFilePath(),$includeExp->getNodePath());
                            if(isset($result)) {
                                // if only selected xml attributes, then updates the xml node attributes with the included attributes
                                $xmlAttributes = $includeExp->getXmlAttr();
                                if(!empty($xmlAttributes)) {
                                    // if xml attributes contains wildcard, then recopies all attributes
                                    if(in_array('*', $xmlAttributes)) {
                                        foreach($result->attributes() as $name=>$value) {
                                            if(!$includeNode[$name]) simplexml_addAttribute($includeNode,$name,$value);
                                        }
                                    }
                                    // else only the ones defined in array
                                    else {
                                        foreach($xmlAttributes as $xmlAttr) {
                                            simplexml_addAttribute($includeNode,$xmlAttr,(string)$result[$xmlAttr]);
                                        }
                                    }
                                    // clears include attribute
                                    $includeNode['include']='';
                                }
                                // else replaces node with included ones
                                else $this->mergeXmlNodeWithInclude($includeNode,$result);
                                $updatedXml = true;
                            }
                        }
                        elseif($includeExp!='' && $includeExp!=null) {
                            $result = $this->getXmlNodeToInclude($principal, $includeExp);
                            if(isset($result)) {
                                $this->mergeXmlNodeWithInclude($includeNode,$result);
                                $updatedXml = true;
                            }
                        } 
                        // else if null, no modification, keeps existing node
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
    
    /**
     * The IncludeConfigController is always enabled, even when loading only parameters
     */
    public function enabledForLoadingOnlyParameters() {return true;}
    
    /**
     * Merges xml node with include node
     * @param SimpleXmlElement $xmlNode existing xml node
     * @param SimpleXmlElement|array $includeNode xml node(s) to include in existing node
     */
    protected function mergeXmlNodeWithInclude($xmlNode,$includeNode) {
        if(!isset($xmlNode)) throw new ConfigServiceException('xmlNode cannot be null',ConfigServiceException::INVALID_ARGUMENT);
        if(isset($includeNode)) {
            $rootNode = dom_import_simplexml($xmlNode);
            // if existing xml node is a root node
            if($rootNode->ownerDocument->documentElement === $rootNode) {
                // removes include attribute
                $rootNode->removeAttribute('include');
                // removes old children
                while ($rootNode->hasChildNodes()) {
                    $rootNode->removeChild($rootNode->firstChild);
                }                
                // if include node is an array, then includes nodes as children
                if(is_array($includeNode)) simplexml_appendChildren($xmlNode, $includeNode);
                // else merges root node with include node
                else {
                    // recopies attributes from include node, except existing attributes
                    foreach($includeNode->attributes() as $name=>$value) {
                        if(!$rootNode->hasAttribute($name)) $rootNode->setAttribute($name, $value);
                    }
                    // includes children nodes
                    simplexml_appendChildren($xmlNode, $includeNode);
                }
            }            
            // else
            else {
                // recopies existing attributes in include node
                $firstNode = $includeNode;
                if(is_array($includeNode)) $firstNode = reset($includeNode);
                foreach($xmlNode->attributes() as $name=>$value) {
                    if($name!='include') simplexml_addAttribute($firstNode, $name, $value);
                }
                // replaces existing node with include node
                if(is_array($includeNode)) simplexml_replaceNodeWithChildren($xmlNode, $includeNode);
                else simplexml_replaceNode($xmlNode, $includeNode);
            }
        }
    }
    
    // Fetch nodes to include
    
    /**
     * Resolves the inclusion path and gets the xml node
     * @param Principal $principal current principal
     * @param String $configFilePath a path describing where to find the xml node to include
     * @param String $nodePath an XPath in the xml file to a specific node or set of nodes
     * @return SimpleXMLElement|array returns on selected xml node or an array of selected nodes. Null if nothing to include.
     */
    protected function getXmlNodeToInclude($principal,$configFilePath,$nodePath=null) {
        $returnValue = null;
        // sanitizes path in class name
        $configFilePath = str_replace(array('../','..\\'), '', $configFilePath);
        
        // checks if xml node already exists in cache
        $returnValue = $this->includeCache[$configFilePath];
        // if not, then loads it
        if(!isset($returnValue)) {
            $configFile = $configFilePath;        
            // detects loading of a file located in a configPack folder
            if(strpos($configFile,'configPack/')===0) {
                $sep = strrpos($configFile, '/');
                $configPackPath = substr($configFile,0,$sep+1);
                $configFile = substr($configFile,$sep+1);
            }
            // else include is done from client config folder
            else {
                $configPackPath=$this->getConfigService()->getClientConfigFolderPath($principal);
            }
            // appends .xml if not present
            if(strpos($configFile,'.xml')===false) $configFile.='.xml';
            // checks authorization before loading file
            $this->assertPrincipalAuthorizedForIncludeConfig($principal, $configFile, $configPackPath);
            // loads xml if file exists        
            $configFile = str_replace('configPack/',CONFIGPACK_PATH,$configPackPath).$configFile;
            if(!file_exists($configFile)) throw new ConfigServiceException('Configuration file '.$configFilePath.' does not exist', ConfigServiceException::CONFIGURATION_ERROR);
            $returnValue = simplexml_load_file($configFile);
            if($returnValue===false || !isset($returnValue)) throw new ConfigServiceException('Error reading configuration file '.$configFilePath, ConfigServiceException::CONFIGURATION_ERROR);
            // adds xml file in memory cache
            $this->includeCache[$configFilePath] = $returnValue;
        }
        // selected node path if defined
        if(isset($nodePath)) {
            $xml = $returnValue;
            if(strpos($nodePath,'/')!==0) $nodePath = '/'.$xml->getName().'/'.$nodePath;
            $returnValue = $returnValue->xpath($nodePath);
            if($returnValue===false || empty($returnValue)) throw new ConfigServiceException('Invalid node selector '.$nodePath.' in configuration file '.$configFilePath, ConfigServiceException::CONFIGURATION_ERROR);
            // CWE 08.04.2019: detects groups and extracts group start, inner nodes and group end
            if(count($returnValue)==2 && $returnValue[0]['groupStart']=='1' && $returnValue[1]['groupEnd']=='1') {
                // xpath expression which selects all siblings between group start and group end using a Kayessian expression
                $xpath = $nodePath.'[1]/following-sibling::*[count(.|'.$nodePath.'[2]/preceding-sibling::*)=count('.$nodePath.'[2]/preceding-sibling::*)]';
                // takes group start, siblings and group end
                $xpath = $nodePath.'[1]|'.$xpath.'|'.$nodePath.'[2]';
                $returnValue = $xml->xpath($xpath);
            }
            elseif(count($returnValue)==1) $returnValue = reset($returnValue);
        }            
        return $returnValue;
    }
    /**
     * Asserts that principal is authorized to include the specific config file located in the specific config pack path
     * @param Principal $principal current principal
     * @param String $configFile name of config file to include
     * @param String $configPackPath config pack path where is located the config file to include
     */
    protected function assertPrincipalAuthorizedForIncludeConfig($principal, $configFile, $configPackPath=null)
    {
        /* nothing for now, always authorized */
    }
    
    
    // Config cache management
    
    /**
     * Returns the cached xml or null if not in cache
     * @param String $key the cache key
     * @param int $cacheLevel one of CACHE_LEVEL_{NONE, NAVIGATE, SESSION or PUBLIC}
     * @return SimpleXMLElement
     */
    protected function getCachedXml($key, $cacheLevel=self::CACHE_LEVEL_NONE) {
        $returnValue = $this->configCache[$key];
        // looks in session if cache level > none
        if(!isset($returnValue) && $cacheLevel > self::CACHE_LEVEL_NONE) {
            $returnValue = $this->getSessionAdminService()->getData($this, $key, ($cacheLevel == self::CACHE_LEVEL_PUBLIC));
            // puts found value in execution cache
            if(!empty($returnValue)) {
                // parses xml string to SimpleXmlElement
                $returnValue = simplexml_load_string($returnValue);
                $this->debugLogger()->write('loaded xml from persistant cache (cache level:'.$cacheLevel.')');
                $this->configCache[$key] = $returnValue;
            }
            else $returnValue = null;
        }
        return $returnValue;
    }
    /**
     * Caches resolved xml configuration file
     * @param String $key the cache key
     * @param SimpleXMLElement $xml the complete resolved xml configuration file
     * @param int $cacheLevel one of CACHE_LEVEL_{NONE, NAVIGATE, SESSION or PUBLIC}
     */
    protected function cacheXml($key, $xml, $cacheLevel=self::CACHE_LEVEL_NONE) {
        $this->configCache[$key] = $xml;
        // stores value in session if cache level > none
        if($cacheLevel > self::CACHE_LEVEL_NONE) {
            // serializes SimpleXmlElement
            if(isset($xml)) $xml = $xml->asXML();
            else $xml = '';
            $this->getSessionAdminService()->storeData($this, $key, $xml, ($cacheLevel < self::CACHE_LEVEL_SESSION), ($cacheLevel == self::CACHE_LEVEL_PUBLIC));
        }
    }       
}