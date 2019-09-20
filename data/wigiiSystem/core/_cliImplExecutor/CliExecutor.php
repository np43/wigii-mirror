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
 * This class is the main service which will link the CLI and the API.
 * It interprets the semantic of the command line interpreter.
 * Created on 19 march 2013 by CWE
 * Modified by CWE on 17.04.2019 to integrate OS shell and allow calling FuncExps from command line
 */
class CliExecutor {	
	// singleton implementation
	private static $singleton;

	protected static function getInstance() {
		if (!isset (self :: $singleton)) {
			self :: $singleton = new CliExecutor();
		}
		return self :: $singleton;
	}

	/**
	 * Registers a CliExecutor subclass as current singleton instance
	 */
	protected static function registerSingleInstance($clExecutor) {
		if (isset (self :: $singleton))
			throw new ServiceException("CliExecutor singleton has already been set, cannot change it dynamically", ServiceException :: FORBIDDEN);
		self :: $singleton = $clExecutor;
	}

	// System principal management

	/**
	 * Adds a system principal or a list of system principals to the CLExecutor
	 */
	public function addSystemPrincipal($systemPrincipal) {
		if (is_null($systemPrincipal))
			return;
		$this->getSystemPrincipals()->unionPrincipalList($systemPrincipal);
		$this->debugLogger()->write("received " . $systemPrincipal->count() . " system principals.");
	}
	private $systemPrincipals;
	/**
	 * Returns the list of actual system principals owned by the WigiiExecutor
	 */
	protected function getSystemPrincipals() {
		//autowired
		if (!isset ($this->systemPrincipals)) {
			$this->systemPrincipals = PrincipalListArrayImpl :: createInstance();
		}
		return $this->systemPrincipals;
	}
	/**
	 * Gets the root principal
	 */
	protected function getRootPrincipal() {
		$this->executionSink()->publishStartOperation("getRootPrincipal");
		$returnValue = ServiceProvider :: getAuthorizationService()->findRootPrincipal($this->getSystemPrincipals());
		if (is_null($returnValue))
			throw new AuthorizationServiceException("root principal has not been initialized by Service Provider", AuthorizationServiceException :: FORBIDDEN);
		$this->executionSink()->publishEndOperation("getRootPrincipal");
		return $returnValue;
	}
	/**
	 * Gets the public principal
	 */
	protected function getPublicPrincipal() {
		$this->executionSink()->publishStartOperation("getPublicPrincipal");
		$returnValue = ServiceProvider :: getAuthorizationService()->findPublicPrincipal($this->getSystemPrincipals());
		if (is_null($returnValue))
			throw new AuthorizationServiceException("public principal has not been initialized by Service Provider", AuthorizationServiceException :: FORBIDDEN);
		$this->executionSink()->publishEndOperation("getPublicPrincipal");
		return $returnValue;
	}	
	/**
	 * Gets web socket server principal
	 */
	protected function getWebSockSrvPrincipal() {
	    $this->executionSink()->publishStartOperation("getWebSockSrvPrincipal");
	    $returnValue = ServiceProvider :: getAuthorizationService()->findWebSockSrvPrincipal($this->getSystemPrincipals());
	    if (is_null($returnValue))
	        throw new AuthorizationServiceException("web socket server principal has not been initialized by Service Provider", AuthorizationServiceException :: FORBIDDEN);
	        $this->executionSink()->publishEndOperation("getWebSockSrvPrincipal");
	        return $returnValue;
	}	

	//dependency injection

	private $_debugLogger;
	private function debugLogger() {
		if (!isset ($this->_debugLogger)) {
			$this->_debugLogger = DebugLogger :: getInstance("CliExecutor");
		}
		return $this->_debugLogger;
	}

	private $_executionSink;
	private function executionSink() {
		if (!isset ($this->_executionSink)) {
			$this->_executionSink = ExecutionSink :: getInstance("CliExecutor");
		}
		return $this->_executionSink;
	}
			
	private $authS;
	public function setAuthenticationService($authenticationService){
		$this->authS = $authenticationService;
	}
	protected function getAuthenticationService(){
		// autowired
		if(!isset($this->authS)){
			$this->authS = ServiceProvider::getAuthenticationService();
		}
		return $this->authS;
	}
	
	private $clientAS;
	public function setClientAdminService($clientAdminService)
	{
		$this->clientAS = $clientAdminService;
	}
	protected function getClientAdminService()
	{
		// autowired
		if(!isset($this->clientAS))
		{
			$this->clientAS = ServiceProvider::getClientAdminService();
		}
		return $this->clientAS;
	}		
	
	private $webSockSrv;
	public function setWebSocketServer($webSockServer) {
	    $this->webSockSrv = $webSockServer;
	}
	protected function getWebSocketServer() {
	    // autowired
	    if(!isset($this->webSockSrv)) {
	        $this->webSockSrv = TechnicalServiceProvider::getWebSocketServer();
	    }
	    return $this->webSockSrv;
	}
	
	private $principal;
	/**
	 * Registers the principal executing the command line
	 */
	protected function registerCurrentPrincipal($principal) {
		$this->principal = $principal;
	}
	/**
	 * Returns the current principal executing the command line
	 * @return Principal 
	 */
	public function getCurrentPrincipal() {
		return $this->principal;
	}
	
	private $client;
	/**
	 * Registers the client against which the command line is executing
	 */
	protected function registerCurrentClient($client) {
		$this->client = $client;
	}
	/**
	 * Returns the current client against which the command line is executed
	 */
	public function getCurrentClient() {
		return $this->client;
	}
	
	//functional

	/**
	 * Starts the current CliExecutor singleton
	 * Creates a default CliExecutor instance if needed
	 * Returns the current started singleton.
	 */
	public static function start() {
		$instance = self :: getInstance();
		$instance->doStart();
		return $instance;
	}

	/**
	 * Starts CliExecutor
	 */
	protected function doStart() {
		$this->startTechnicalServiceProvider();
		$this->startServiceProvider();
		$this->executionSink()->log("API is started");
		$this->executionSink()->log("CliExecutor ready");
	}
	/**
	 * Default starts TechnicalServiceProviderImpl
	 */
	protected function startTechnicalServiceProvider() {
	    TechnicalServiceProviderCliImpl :: start(DEBUG_EXECUTION_ENABLED,DEBUG_EXECUTION_ENABLED);
	}
	/**
	 * Default starts ServiceProviderCliImpl
	 */
	protected function startServiceProvider() {
		ServiceProviderCliImpl :: start($this);
	}
	
	// Command line interpreter
	
	/**
	 * process the command line
	 * postcondition: this method never throws an exception, all errors are trapped and displayed correctly to the user
	 */
	public function processAndEnds($argc, $argv) {
		$this->executionSink()->publishStartOperation("processAndEnds");
		try
		{
			// interprets command line
			if($argc < 4) $this->printUsage();
			else {
				$i = 1;
				
				// reads options
				if($i < $argc) {
				    // -noTrace option
				    if($argv[$i] == '-noTrace') {
				        /* ignores. Already interpreted in main.php */
				        $i++;
				    }
				    // -shell option
				    elseif($argv[$i] == '-shell') {
				        /* ignores. Already interpreted in main.php */
				        $i++;
				    }
				}
				
				// reads client name
				if($argv[$i++] != '-c') $this->printUsage();
				else if($i >= $argc) $this->printUsage();
				else {
					$clientName = $argv[$i++];					
					// extracts principal through the -u userName -p password option
					if($i >= $argc) $this->printUsage();
					else if($argv[$i] == '-u') {
						$i++;
						$username = $argv[$i++];
						if($argv[$i++] != '-p') $this->printUsage();
						else $password = $argv[$i++];
						
						$authS = $this->getAuthenticationService();
						$authS->login($username, $password, $clientName);
						$this->registerCurrentPrincipal($authS->getMainPrincipal());
					}
					// or extracs the root principal
					else if($argv[$i] == '-uRootPrincipal') {
						$i++;
						$this->registerCurrentPrincipal($this->getRootPrincipal());
					}
					// else use the public principal
					else {
						$this->registerCurrentPrincipal($this->getPublicPrincipal());
					}
				}
				
				$p = $this->getCurrentPrincipal();
				$this->debugLogger()->write("Current principal is ".$p->getUsername());

				// registers client
				$this->registerCurrentClient($this->getClientAdminService()->getClient($p, $clientName));
				
				// reads command name
				if($i >= $argc) $this->printUsage();
				else {
					$commandName = $argv[$i++];
					$this->executeCommand($commandName, $argc, $argv, $i);
				}
			}
			
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("processAndEnds", $e);
			ExceptionSink::publish($e);
			exit(intval($e->getCode()));
		}
		$this->executionSink()->publishEndOperation("processAndEnds");				
	}
	protected function printUsage() {
		echo MAIN_USAGE;
	}
	protected function executeCommand($commandName, $argc, $argv, $subArgIndex) {
		if(method_exists($this, $commandName)) {
			$this->executionSink()->publishStartOperation($commandName);
			try
			{
				$this->$commandName($argc, $argv, $subArgIndex);				
			}
			catch(Exception $e)
			{
				$this->executionSink()->publishEndOperationOnError($commandName, $e);
				throw $e;
			}
			$this->executionSink()->publishEndOperation($commandName);				
		}
		else throw new ServiceException("Command $commandName is not supported.", ServiceException::UNSUPPORTED_OPERATION); 			
	}
	/**
	 * Puts out a string
	 * @param String $str
	 */
	protected function put($str) {
	    if($this->executionSink()->isEnabled()) $this->executionSink()->log($str);
	    else echo $str;
	}
	/**
	 * Puts out a string followed by a new line
	 * @param String $str
	 */
	protected function putLine($str) {
	    if($this->executionSink()->isEnabled()) $this->executionSink()->log("\n".$str); // if log file, then puts new line before string
	    else echo $str."\n";
	}
	
	// CLI commands
	
	protected function execBatch($argc, $argv, $subArgIndex) {
		// gets batch name
		if($subArgIndex >= $argc) throw new ServiceException("Batch name is missing. Usage is execBatch batchClassName", ServiceException::INVALID_ARGUMENT);
		else {
			$batchClass = $argv[$subArgIndex++];
			try {
				$batch = ServiceProvider::createWigiiObject($batchClass);				
				$batchLoaded = true;
			}
			catch(Exception $e) {					
				$batchLoaded = false;
			}	
			if($batchLoaded) {
				// configures the batch execution context
				$batch->setPrincipal($this->getCurrentPrincipal());
				$batch->setClient($this->getCurrentClient());
				$batch->setLanguage(DEFAULT_LANGUAGE);
				// loads specific batch configuration if exists in Client config path
				if(file_exists(CLIENT_CONFIG_PATH . "config_$batchClass.php")) {
					include_once (CLIENT_CONFIG_PATH . "config_$batchClass.php");
				}
				// runs
				$batch->run($argc, $argv, $subArgIndex);
			}
			else throw new ServiceException("No batch definition found with name ".$batchClass, ServiceException::INVALID_ARGUMENT);
		}		
	}
	
	protected function execScript($argc, $argv, $subArgIndex) {
	    // gets script ID
	    if($subArgIndex >= $argc) throw new ServiceException('Script ID is missing. Usage is execScript elementId+', ServiceException::INVALID_ARGUMENT);
	    else {
	        // extracts the IDs of the scripts to execute
	        $scriptIds = array_slice($argv, $subArgIndex);
	        $principal = $this->getCurrentPrincipal();
	        // script should be located in Setup namespace and be of module Scripts
	        $cs = cs(ServiceProvider::getWigiiNamespaceAdminService()->getSetupWigiiNamespace($principal)->getWigiiNamespaceName(),"Scripts");
	        
            // execute each script in sequence
            foreach($scriptIds as $scriptId) {
                sel($principal, elementP($scriptId, null, $cs), dfasl(
                    dfas("ElementSetterDFA", "setCalculatedFieldSelectorMap", cfsMap(cfs("scriptFuncExp",true))),
                    dfas("ElementRecalcDFA"),
                    dfas("ElementDFA", "setMode", 1)
                ));
            }	        
	    }
	}
	
	/**
	 * Computes md5 hash from a given string
	 */
	protected function md5($argc, $argv, $subArgIndex) {
	    $this->put(md5($argv[$subArgIndex]));
	}
	
	/**
	 * Encodes a given string to a Base 64 string compatible with URL constraints
	 */
	protected function base64url_encode($argc, $argv, $subArgIndex) {
	    $this->put(base64url_encode($argv[$subArgIndex]));
	}
	
	/**
	 * Decodes a given Base 64 url string
	 */
	protected function base64url_decode($argc, $argv, $subArgIndex) {
	    $this->put(base64url_decode($argv[$subArgIndex]));
	}
	
	
	/**
	 * Executes an FuncExp given its expression as a string
	 */
	protected function fx($argc, $argv, $subArgIndex) {
	    $fxStr = $argv[$subArgIndex];
	    // replaces any single quotes by double quotes
	    $fxStr = str_replace("'", '"', $fxStr);
	    // tries to parse fx string
	    $fx = str2fx($fxStr);
	    // evaluates fx string and displays result
	    $this->put(evalfx($this->getCurrentPrincipal(), $fx));
	}
	
	/**
	 * Generates a self signed ssl certificate using an xml file describing the certificate details
	 * Xml file should be of the form:
	 * <sslcertrequest>
	 *     <commonName>localhost</commonName>
	 *     <countryName>CH</countryName>
	 *     <stateOrProvinceName></stateOrProvinceName>
	 *     <localityName></localityName>
	 *     <organizationName></organizationName>
	 *     <organizationUnitName></organizationUnitName>
	 *     <emailAddress></emailAddress>
	 *     <!-- optional config -->
	 *     <validity>3650</validity>
	 *     <keyBits>2048</keyBits>
	 *     <keyPassPhrase></keyPassPhrase>
	 * </sslcertrequest>
	 * Certifcate is valid for 10 years by default.
	 * Outputs the certificate and private key in pem format
	 */
	protected function genSslCert($argc, $argv, $subArgIndex) {
	    // gets ssl request xml file
	    if($subArgIndex >= $argc) throw new ServiceException('config file is missing. Usage is genSslCert sslCertConfigFilePath [certOutFilePath]', ServiceException::INVALID_ARGUMENT);
	    else {
	        $sslCertRequFile = $argv[$subArgIndex++];
	        // loads xml
	        $sslCertRequ = simplexml_load_file($sslCertRequFile);
	        if($sslCertRequ===false) throw new ServiceException('error loading ssl certificate xml config file '.$sslCertRequFile);
	        $keyBits = (string)$sslCertRequ->keyBits;
	        if(empty($keyBits)) $keyBits = 2048;
	        $validity = (string)$sslCertRequ->validity;
	        if(empty($validity)) $validity = 3650;
	        $passPhrase = (string)$sslCertRequ->passPhrase;
	        if(empty($passPhrase)) $passPhrase=null;
	        // generates new private key
	        $privkey = openssl_pkey_new(array(
	            "private_key_bits" => intval($keyBits),
	            "private_key_type" => OPENSSL_KEYTYPE_RSA
	        ));
	        if($privkey===false) throw new ServiceException('cannot generate new private key. '.openssl_error_string(),ServiceException::WRAPPING);
	        
	        // generates certificate
	        $cert = openssl_csr_new(array(
	            "commonName"=>(string)$sslCertRequ->commonName,
	            "countryName"=>(string)$sslCertRequ->countryName,
	            "stateOrProvinceName"=>(string)$sslCertRequ->stateOrProvinceName,
	            "localityName"=>(string)$sslCertRequ->localityName,
	            "organizationName"=>(string)$sslCertRequ->organizationName,
	            "organizationUnitName"=>(string)$sslCertRequ->organizationUnitName,
	            "emailAddress"=>(string)$sslCertRequ->emailAddress
	        ), $privkey);
	        $cert = openssl_csr_sign($cert, null, $privkey, intval($validity));
	        
	        // gets output file name
	        if($subArgIndex < $argc) $outputFile = $argv[$subArgIndex++];
	        else $outputFile=null;
	        
	        // exports certificate
	        openssl_x509_export($cert, $returnValue);
	        if(isset($outputFile)) $certContent = $returnValue;
	        else $this->put($returnValue);
	        // exports private key
	        openssl_pkey_export($privkey, $returnValue, $passphrase);
	        if(isset($outputFile)) $certContent.= $returnValue;
	        else $this->put($returnValue);
	        
	        if(isset($outputFile)) if(!file_put_contents($outputFile, $certContent)) throw new ServiceException('error writing certificate to file '.$outputFile, ServiceException::WRAPPING);
	        @unlink('.rnd');
	    }
	}
	
	protected function useConfigPackModule($argc, $argv, $subArgIndex) {
	    // gets config file path
	    if($subArgIndex >= $argc) throw new ServiceException('config file path is missing. Usage is useConfigPackModule configFilePath', ServiceException::INVALID_ARGUMENT);
	    else {
	        $configFilePath = $argv[$subArgIndex++];
	        $principal = $this->getCurrentPrincipal();
	        // sanitizes path in fileName
	        $configFilePath = str_replace(array('../','..\\'), '', $configFilePath);
	        // Read from configPack path and extract xml configuration file name
	        if(strpos($configFilePath,'configPack/')===0) {
	            $sep = strrpos($configFilePath, '/');
	            $configPackPath = substr($configFilePath,0,$sep+1);
	            $file = substr($configFilePath,$sep+1);
	        } else throw new ServiceException('can only read config pack modules', ServiceException::INVALID_ARGUMENT);
	        // extracts module name
	        if(!file_exists(str_replace('configPack/',CONFIGPACK_PATH,$configPackPath).$file) || strpos($file,'_config.xml')===false) throw new ServiceException($configFilePath.' is not a valid config pack module xml configuration file', ServiceException::INVALID_ARGUMENT);
	        $moduleName = explode('_', $file);
	        $moduleName = $moduleName[array_search('config.xml',$moduleName)-1];
	        // lookups for client config template
	        $configTemplate = 'newClient/configs/client_moduleTemplate.xml';
	        if(file_exists(str_replace('configPack/',CONFIGPACK_PATH,$configPackPath).$configTemplate)) $configTemplate = $configPackPath.$configTemplate;
	        // else lookups for module config template
	        else {
	            $configTemplate = 'newClient/configs/config_moduleTemplate.xml';
	            if(file_exists(str_replace('configPack/',CONFIGPACK_PATH,$configPackPath).$configTemplate)) $configTemplate = $configPackPath.$configTemplate;
	            // else takes standard config module template.
	            else $configTemplate=null;
	        }
	        // generates module xml configuration file based on usage of given config pack module
	        $this->put(ServiceProvider::getWigiiBPL()->useConfig($principal, $this, wigiiBPLParam(
	            'xmlFile',$configFilePath,
	            'moduleName', $moduleName,
	            'moduleConfigTemplate', $configTemplate,
	            'outputAsString', true
	        )));
	    }
	}
	
	protected function runWebSocketServer($argc, $argv, $subArgIndex) {
	    $this->getWebSocketServer()->run($this->getWebSockSrvPrincipal());
	}
	protected function stopWebSocketServer($argc, $argv, $subArgIndex) {
	    $this->getWebSocketServer()->stop($this->getWebSockSrvPrincipal());
	}
}