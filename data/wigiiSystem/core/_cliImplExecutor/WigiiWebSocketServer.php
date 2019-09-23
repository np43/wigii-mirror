<?php
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use React\EventLoop\LoopInterface;
use React\EventLoop\Factory as LoopFactory;
use React\EventLoop\TimerInterface;
use React\Socket\Server as Reactor;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\WebSocket\WsServerInterface;
use Ratchet\Http\HttpServer;
use Ratchet\Http\HttpServerInterface;
use Ratchet\Http\OriginCheck;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Ratchet\Http\Router;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;

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
 * A companion server which handles web sockets on a specific port,
 * This server is intended to work on the same machine as the main Wigii instance.
 * Each Wigii client has its own web socket server instance. Users belonging to the same Wigii client can talk to each other, 
 * but Wigii clients are isolated from one another.
 * Depends on cboden/ratchet software suite for network protocols implementation.
 * Created by CWE on 17.09.2019
 */
class WigiiWebSocketServer implements MessageComponentInterface, WsServerInterface
{   
    /**
     * @var LoopInterface main server event loop
     */
    protected $srvLoop;
    /**
     * @var TimerInterface server periodic timer
     */
    protected $srvTimer;
    /**
     * @var array map of WigiiWebSocketServer instances, one per wigii client. (array key is client name, array value is WigiiWebSocketServer instance)
     */
    protected $clientWorkers;
    /**
     * @var SplObjectStorage set of open sockets for current wigii client.
     */
    protected $connections;
    /**
     * @var ConnectionInterface current active connection
     */
    private $crtConnection;

    // Server network details
    
    protected $httpHost;
    protected $port;
    protected $ipAddress;
    
    // Object lifecycle
    
    /**
     * Creates a new server instance bound to a given Wigii client
     * @param Client $client
     * @return WigiiWebSocketServer
     */
    public static function createInstance($client,$mode) {
        $returnValue = new self();
        $returnValue->setClient($client);
        $returnValue->setMode($mode);
        return $returnValue;
    }
    
    // Dependency injection

    private $_debugLogger;
    private $_executionSink;
    
    
    private function debugLogger()
    {
        if(!isset($this->_debugLogger))
        {
            $this->_debugLogger = DebugLogger::getInstance("WigiiWebSocketServer");
        }
        return $this->_debugLogger;
    }
    private function executionSink()
    {
        if(!isset($this->_executionSink))
        {
            $this->_executionSink = ExecutionSink::getInstance("WigiiWebSocketServer");
        }
        return $this->_executionSink;
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
    
    private $dflowS;
    /**
     * Injects a DataFlowService to be used by this library
     * @param DataFlowService $dataFlowService
     */
    public function setDataFlowService($dataFlowService)
    {
        $this->dflowS = $dataFlowService;
    }
    /**
     * Gets the injected DataFlowService
     * @return DataFlowService
     */
    protected function getDataFlowService()
    {
        // autowired
        if(!isset($this->dflowS))
        {
            $this->dflowS = ServiceProvider::getDataFlowService();
        }
        return $this->dflowS;
    }
    
    private $clientAS;
    public function setClientAdminService($clientAdminService)
    {
        $this->clientAS = $clientAdminService;
    }
    /**
     * @return ClientAdminService
     */
    protected function getClientAdminService()
    {
        // autowired
        if(!isset($this->clientAS))
        {
            $this->clientAS = ServiceProvider::getClientAdminService();
        }
        return $this->clientAS;
    }
    
    // Configuration
    
    private $configFileName;
    public function setConfigFileName($fileName) {
        $this->configFileName = $fileName;
    }
    protected function getConfigFileName() {
        if(!isset($this->configFileName)) {
            $this->configFileName = CLI_PATH.'WebSockets_config.xml';
        }
        return $this->configFileName;
    }
    
    private $clientName;
    /**
     * Sets the client attached to this Web socket server
     * @param Client $client
     */
    protected function setClient($client) {
        if(!isset($client)) throw new WigiiWebSocketServerException('client cannot be null', WigiiWebSocketServerException::INVALID_ARGUMENT);
        $this->clientName = $client->getClientName();
    }
    /**
     * @return String returns the name of the Wigii client attached to this server
     */
    public function getClientName() {
        return $this->clientName;
    }      
    
    public function getClientUrl() {
        if($this->isNoClient()) return 'NoClient';
        else return $this->getClientName();
    }
    
    public function isNoClient() {
        return ($this->getClientAdminService()->getEmptyClientName() === $this->clientName);
    }
    
    /**
     * Server handling web sockets
     */
    const MODE_SRV=1;
    /**
     * Administration command line interface
     */
    const MODE_CLI=2;
    /**
     * Wigii integrated proxy
     */
    const MODE_PROXY=3;
    /**
     * Wigii client web sockets worker
     */
    const MODE_WORKER=4;
    
    private $executionMode;
    /**
     * Sets execution mode of server
     * @param int $mode one of MODE_SRV, MODE_CLI, MODE_PROXY, MODE_WORKER
     */
    protected function setMode($mode) {
        switch($mode) {
            case self::MODE_SRV:
            case self::MODE_CLI:
            case self::MODE_PROXY:
            case self::MODE_WORKER:
                $this->executionMode = $mode;
                break;
            default: throw new WigiiWebSocketServerException('unsupported execution mode '.$mode, WigiiWebSocketServerException::INVALID_ARGUMENT);
        }
    }
    /**
     * @return int server execution mode, one of MODE_SRV, MODE_CLI, MODE_PROXY, MODE_WORKER
     */
    public function getMode() {
        return $this->executionMode;
    }    
        
    private function getClientDataPath() {
        $returnValue = wigiiSystem_PATH."../../users/";
        if($this->isNoClient()) $returnValue.= 'NoClient/';
        else $returnValue.= $this->getClientName().'/';
        return $returnValue;
    }
    
    public function getSubProtocols() {
        return array('wigii','wncd','js');
    }

    /**
     * Returns protocol supported by current open web socket
     */
    public function getCrtProtocol() {
        if(!isset($this->crtConnection)) throw new WigiiWebSocketServerException('no web socket currently open', WigiiWebSocketServerException::INVALID_STATE);
        return $this->crtConnection->wigiiProtocol;
    }
    
    // Administration
    
    /**
     * Runs the Web socket server as configured in the WebSockets_config.xml file
     * @param Principal $principal current principal
     */
    public function run($principal) {
        $this->getAuthorizationService()->assertPrincipalIsWebSockSrv($principal);
        if($this->getMode()!=self::MODE_CLI) throw new WigiiWebSocketServerException('server must be in mode CLI to start running', WigiiWebSocketServerException::NOT_ALLOWED);
        // upgrades execution mode to server
        $this->setMode(self::MODE_SRV);
        // gets server configuration
        $this->port = $this->getParameter('port');
        if($this->port==null) $this->port='8080';
        $this->httpHost = 'localhost';
        $this->ipAddress = '0.0.0.0';
        $sslCert = $this->getParameter('sslCert');
        $passPhrase = $this->getParameter('passPhrase');
        // creates main server event loop
        $this->srvLoop = LoopFactory::create();
        // adds periodic check of server fx queue
        $srv = $this;
        $this->srvTimer = $this->srvLoop->addPeriodicTimer(20, function() use($srv, $principal){
            try {
                $srv->onCheckForFx($principal);
            }
            catch(Exception $e) {
                $srv->endOnError($principal,$e);
            }
        });
        // prepares client workers
        $clientWorkersRouting = new RouteCollection();
        if($this->getXmlConfig()->clients) {
            if(!isset($this->clientWorkers)) $this->clientWorkers=array();
            foreach($this->getXmlConfig()->clients->children() as $clientXmlConfig) {
                $clientWorker = $this->prepareClientWorker($principal, $clientXmlConfig);
                $this->clientWorkers[$clientWorker->getClientUrl()] = $clientWorker;
                $clientWorkersRouting->add($clientXmlConfig->getName(), $clientWorker->workerRoute);
            }
        }
        if(empty($this->clientWorkers)) throw new WigiiWebSocketServerException('no Wigii clients active for web sockets connections',WigiiWebSocketServerException::CONFIGURATION_ERROR);
        // configures ssl
        $context=array();
        if($sslCert!=null) {
            $context['tls'] = array(
                'local_cert'=>CLI_PATH.$sslCert,
                'passphrase'=>$passPhrase,
                'allow_self_signed' => true, 
                'verify_peer' => false
            );
        }
        // creates TCP server socket
        $ioServer = new Reactor(($sslCert!=null?'tls://':'tcp://').$this->ipAddress.':'.$this->port, $this->srvLoop,$context);        
        // wraps socket with message components
        $ioServer = new IoServer(new HttpServer(new Router(new UrlMatcher($clientWorkersRouting, new RequestContext()))), $ioServer, $this->srvLoop);
        // starts the event loop
        $this->executionSink()->log('Server started');
        $ioServer->run();
    }
    
    private $workerRoute;
    /**
     * Prepares a worker for a Wigii client
     * @param Principal $p current principal
     * @param SimpleXMLElement $clientXmlConfig web socket wigii client xml config node as defined in the WebSockets_config.xml
     * @return WigiiWebSocketServer a Wigii web socket server instance ready to handle routed messages for specific wigii client
     */
    protected function prepareClientWorker($p, $clientXmlConfig) {
        if($this->getMode()!=self::MODE_SRV) throw new WigiiWebSocketServerException('new client workers can only be added by the main server', WigiiWebSocketServerException::NOT_ALLOWED);
        if(!$clientXmlConfig) throw new WigiiWebSocketServerException('clientXmlConfig cannot be null', WigiiWebSocketServerException::INVALID_ARGUMENT);
        // extracts configuration parameters
        $clientName = (string)$clientXmlConfig['clientName'];
        if(empty($clientName)) $clientName = $clientXmlConfig->getName();
        $clientUrl = (string)$clientXmlConfig['clientUrl'];
        if(empty($clientUrl)) $clientUrl = '/'.$clientName;        
        // creates client worker instance
        $returnValue = new self();
        $returnValue->setMode(self::MODE_WORKER);
        $returnValue->setClient($this->getClientAdminService()->getClient($p, $clientName));
        $this->debugLogger()->write('prepares client worker for '.$clientName.' listening on '.$clientUrl);       
        // prepares WebSocket protocol component
        $comp = new WsServer($returnValue);
        $comp->enableKeepAlive($this->srvLoop);
        $comp->setStrictSubProtocolCheck(true);
        // checks origin: only allows from same server
        $comp = new OriginCheck($comp,array($this->httpHost));
        // prepares routing component
        $hostMatcher = $this->httpHost;
        $returnValue->workerRoute = new Route($clientUrl, array('_controller' => $comp), array('Origin' => $this->httpHost), array(), $hostMatcher, array(), array('GET'));
        return $returnValue;
    }
    
    /**
     * Stops the server properly
     * @param Principal $principal current principal
     */
    public function stop($principal) {
        $this->getAuthorizationService()->assertPrincipalIsWebSockSrv($principal);
        // if currently in cli admin, then enqueues a stop signal
        if($this->getMode()==self::MODE_CLI) {
            $this->queueFx($principal, fx('wssrvStop'), 60);
            return;
        }
        elseif($this->getMode()!=self::MODE_SRV) throw new WigiiWebSocketServerException('not allowed to stop server', WigiiWebSocketServerException::NOT_ALLOWED);
        // else if mode is server then stops it properly
        $this->executionSink()->log("Server is going to stop.");
        $this->doStop($principal);
        $this->executionSink()->log("Server stopped.");
    }
    /**
     * Does the actual work of shutting down the server.
     * This method does not need to check authorization, but should always shutdown in a best effort way.
     * @param Principal $principal current principal for information
     */
    protected function doStop($principal) {
        // stop server timer
        if(isset($this->srvTimer) && isset($this->srvLoop)) {
            try{
                $this->srvLoop->cancelTimer($this->srvTimer);
                unset($this->srvTimer);
                $this->executionSink()->log("server timer stopped.");
            } catch(Exception $e) {
                $this->executionSink()->logError('Error stopping server timer.', $e);
            }
        }        
        // closes each open connection
        if(isset($this->connections)) {
            foreach($this->connections as $connection) {
                try{
                    $connection->close();                    
                    $this->executionSink()->log("connection closed.");
                } catch(Exception $e) {
                    $this->executionSink()->logError('Error closing connection.', $e);
                }
            }
            unset($this->connections);            
        }
        // stops each wigii client web socket workers
        if(!empty($this->clientWorkers)) {
            foreach($this->clientWorkers as $clientWorker) {
                $clientWorker->doStop($principal);
                $this->executionSink()->log("worker for client ".$clientWorker->getClientName()." stopped.");
            }
            unset($this->clientWorkers);
        }
        // stops main server loop
        if(isset($this->srvLoop)) {
            try{
                $this->srvLoop->stop();
                $this->executionSink()->log("server loop stopped.");
            } catch(Exception $e) {
                $this->executionSink()->logError('Error stopping server loop.', $e);
            }
            unset($this->srvLoop);
            // switches back in CLI mode
            $this->setMode(self::MODE_CLI);
        }
    }
    
    /**
     * Queues a FuncExp to be executed by the Web Socket server
     * @param Principal $principal current principal
     * @param FuncExp $fx FuncExp to execute later
     * @param int $expirationTime Maximum number of seconds the FuncExp should wait before execution. Default to 20s.
     */
    public function queueFx($principal, $fx, $expirationTime=20) {
        if(!($this->getMode()==self::MODE_PROXY || $this->getMode()==self::MODE_CLI)) {
            throw new WigiiWebSocketServerException('not allowed to queue fx', WigiiWebSocketServerException::NOT_ALLOWED);
        }
        if(!isset($fx)) throw new WigiiWebSocketServerException('fx cannot be null', WigiiWebSocketServerException::INVALID_ARGUMENT);
        if($expirationTime<=0) throw new WigiiWebSocketServerException('expirationTime should be a positive integer', WigiiWebSocketServerException::INVALID_ARGUMENT);;
        // serializes Fx
        $fx = fx2str($fx);        
        // stores serialized Fx into a file in the Web socket server queue of the client
        $expirationTime = time()+$expirationTime;        
        $fileName = 'fx_'.$principal->getMicroTime().'_'.$expirationTime.'.txt';
        $fileName = "data/webSocketQueue/".$fileName;
        $this->getDataFlowService()->processString($principal, $fx, dfasl(
            dfas("FileOutputStreamDFA","setRootFolder",$this->getClientDataPath(),"setFileName",$fileName)
        ));
    }
    
    // Web socket server implementation
    
    public function onOpen(ConnectionInterface $conn) {
        // registers new connection in list of open connections
        if(!isset($this->connections)) $this->connections = new SplObjectStorage();
        $conn->wigiiProtocol = reset($conn->httpRequest->getHeader('Sec-WebSocket-Protocol'));
        $conn->wigiiSessionId = null;
        $this->connections->attach($conn);        
        $this->executionSink()->log('new connection for client '.$this->getClientUrl());
    }
    
    public function onMessage(ConnectionInterface $from, $msg) {
        $this->executionSink()->publishStartOperation('onMessage');
        // switches context and loads current session
        //$from->wigiiSessionId = $this->switchContext($from->wigiiSessionId);
        $p = ServiceProvider::getAuthenticationService()->getMainPrincipal();
        $this->crtConnection = $from;
        try {
            $this->debugLogger()->write($msg);
            // parses incoming fx
            $fx = str2fx($msg);
            // evaluates Fx
            if($fx instanceof FuncExp) {
                // sets origin as public
                $fx->setOriginIsPublic();                
                $this->evalFx($p, $fx);
                // ignores return value
            }            
            $this->executionSink()->publishEndOperation('onMessage');
        }
        catch(Exception $e) {
            $this->executionSink()->publishEndOperationOnError('onMessage', $e);
            // sends back error to client
            $this->jsCallFunction($p, 'wigii().displayWigiiException', $this->convertServiceExceptionToJson($p, $from, $e));
            // signals fatal error to monitoring system
            ServiceProvider::getClientAdminService()->signalFatalError($e);            
        }
        $this->crtConnection = null;
    }
    
    /**
     * Sends a message to current open web socket
     * @param Principal $p current principal handling the web socket connection
     * @param String $msg a message to send to the browser. Should be compatible with current protocol
     */
    public function sendMessage($p,$msg) {
        if(!isset($this->crtConnection)) throw new WigiiWebSocketServerException('no web socket currently open', WigiiWebSocketServerException::INVALID_STATE);
        $this->debugLogger()->write($msg);
        $this->crtConnection->send($msg);
    }
    
    /**
     * Sends some js code to browser
     * @param Principal $p current principal handling the web socket connection
     * @param String $jsCode valid js code to be executed on client side
     */
    public function sendJsCode($p,$jsCode) {
        if($jsCode==null) return;
        $msg = '';
        switch($this->getCrtProtocol()) {
            case "wncd":
                $msg = 'wncd.script(function(){';
                $msg .= $jsCode;
                $msg .= ';})';
                break;
            case "js":
                $msg = $jsCode.';';
                break;
            case "wigii":
                $msg = ExecutionServiceImpl::answerRequestSeparator;
                $msg .= 'JSCode';
                $msg .= ExecutionServiceImpl::answerParamSeparator;
                $msg .= $jsCode.';';
                break;
        }
        if($msg!='') $this->sendMessage($p, $msg);
    }
    
    /**
     * Assigns a value to a variable on client side
     * @param Principal $p current principal handling the web socket connection
     * @param String $varName path to variable (can be a chain of names separated by dots)
     * @param mixed $varValue value to assign to variable. Should be JSON serializable.
     */
    public function jsAssignVar($p, $varName,$varValue) {
        $this->sendJsCode($p, $this->formatJsName($varName).'='.$this->formatJsValue($varValue));
    }
    
    /**
     * Calls a function on client side
     * @param Principal $p current principal handling the web socket connection
     * @param String $funcName path to function (can be a chain of names separated by dots)
     * @param Array|mixed $args array of arguments or one single argument. Each argument should be JSON serializable.
     */
    public function jsCallFunction($p, $funcName,$args) {
        $funcCall = $this->formatJsName($funcName).'(';
        if(is_array($args)) {
            $first=true;
            foreach($args as $arg) {
                if($first) $first=false;
                else $funcCall .= ',';
                $funcCall .= $this->formatJsValue($arg);
            }
        }
        else $funcCall .= $this->formatJsValue($args);
        $funcCall .= ')';
        $this->sendJsCode($p, $funcCall);
    }
    
    /**
     * Checks and format a js name. Only allows dots separated chains.
     * Asserts that name is safe against unwanted code injection.
     * @param String $jsName a path to a variable or function
     * @return String sanitized js name
     */
    public function formatJsName($jsName) {
        if(empty($jsName)) throw new WigiiWebSocketServerException('jsName cannot be null.', WigiiWebSocketServerException::INVALID_ARGUMENT);
        // sanitizes js name by keeping only alphanumeric chars, dots and parenthesis
        $matches=null;
        if(preg_match('/[_a-zA-Z0-9\.\(\)]+/',$jsName,$matches)===false || $matches[0]!=$jsName) throw new WigiiWebSocketServerException('invalid jsName '.$jsName, WigiiWebSocketServerException::INVALID_ARGUMENT);
        return $matches[0];
    }
    
    /**
     * Checks, serializes and formats a value to be sent as js to browser
     * @param mixed $jsValue any JSON serializable value
     * @return String valid json string
     */
    public function formatJsValue($jsValue) {
        $returnValue = json_encode($jsValue,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK|JSON_UNESCAPED_SLASHES);
        if($returnValue=== false) throw new WigiiWebSocketServerException('Invalid js value. JSON encode error '.json_last_error().' '.json_last_error_msg(), WigiiWebSocketServerException::INVALID_ARGUMENT);
        return $returnValue;
    }
    
    public function onClose(ConnectionInterface $conn) {
        // unregisters connection
        if(isset($this->connections)) $this->connections->detach($conn);
        $this->executionSink()->log('closing connection for client '.$this->getClientUrl());
    }
    
    public function onError(ConnectionInterface $conn, \Exception $e) {
        $this->executionSink()->logError('error on connection for client '.$this->getClientUrl(), $e);
        $conn->close();       
    }
    
    /**
     * Periodic event to check for waiting Fx in the queue
     * @param Principal $principal current principal
     */
    protected function onCheckForFx($principal) {
        // gets next fx
        $fx = $this->popFx();
        // if one is present, then evaluates it
        if(isset($fx)) $this->evalFx($principal, $fx);
    }
    
    /**
     * Ends server on error
     * @param Principal $principal
     * @param Exception $exception
     */
    protected function endOnError($principal, $exception) {
        $this->executionSink()->log("Error occured. Server is going to stop.");
        $this->doStop($principal);
        $this->executionSink()->log("Server stopped.");
        ExceptionSink::publish($exception);
    }
    
    // Implementation
    
    protected function switchContext($sessionId) {
        WigiiWebSocketServerException::throwNotImplemented();
    }
    
    /**
     * Pops next Fx to be executed from the queue or null if queue is empty
     * @return FuncExp
     */
    protected function popFx() {
        $returnValue = null;
        // gets queue of fx files
        $fxDir = $this->getClientDataPath().'data/webSocketQueue/';
        $fxFiles = @scandir($fxDir);
        if($fxFiles===false) throw new WigiiWebSocketServerException('error reading fx queue for client '.$this->getClientUrl(), WigiiWebSocketServerException::UNKNOWN_ERROR);
        // pops next valid fx, discards any deprecated files
        if(!empty($fxFiles)) {
            $deprecatedFiles = array();
            $now = time();
            foreach($fxFiles as $fxFile) {
                // skips . and .. directories
                if($fxFile=='.' || $fxFile=='..') continue;
                // skips sub-directories
                if(!is_file($fxDir.$fxFile)) continue;
                // extracts expiration time
                $fxFileParts = explode('_',str_replace('.txt', '', $fxFile));
                if(count($fxFileParts)!=3) continue;
                if($fxFileParts[0]!='fx') continue;
                // removes deprecated file
                if($now > $fxFileParts[2]) $deprecatedFiles[] = $fxFile;
                // else reads content
                else {
                    $returnValue = file_get_contents($fxDir.$fxFile);
                    if($returnValue===false) throw new WigiiWebSocketServerException('error reading file '.$fxFile.' in queue for client '.$this->getClientUrl(), WigiiWebSocketServerException::UNKNOWN_ERROR);
                    // parses content as a FuncExp
                    if(!empty($returnValue)) $returnValue = str2fx($returnValue);
                    else $returnValue = null;
                    // marks fx origin as public
                    if($returnValue instanceof FuncExp) $returnValue->setOriginIsPublic();
                    // marks file to be deleted
                    $deprecatedFiles[] = $fxFile;
                    // returns
                    break;
                }
            }
            // deletes deprecated files
            if(!empty($deprecatedFiles)) {
                foreach($deprecatedFiles as $fxFile) {
                    @unlink($fxDir.$fxFile);
                }
            }
        }
        return $returnValue;
    }
    
    /**
     * Evaluates FuncExp using given principal and returns result
     * @param Principal $principal
     * @param FuncExp $fx
     * @return mixed FuncExp result
     */
    protected function evalFx($principal,$fx) {
        $vm = ServiceProvider::getFuncExpVM($principal);
        try {
            // Loads WigiiWebSocketFL
            $vm->useModules('WigiiWebSocketFL');
            // configures it
            $wigiiWebSocketFL = $vm->getFuncExpVMServiceProvider()->getFuncExpVMContext()->getModule('WigiiWebSocketFL');
            $wigiiWebSocketFL->setWebSocketServer($this);
            // evaluates FuncExp
            return $vm->evaluateFuncExp($fx,$this);
        }
        catch(Exception $e) {
            $vm->freeMemory();
            throw $e;
        }
    }
    
    /**
     * Converts a PHP ServiceException to a StdClass ready to be pushed in JSON to client
     * @param Principal $p current principal executing the request
     * @param ConnectionInterface $conn current web socket connection
     * @param ServiceException|Exception $exception the exception to be converted to StdClass
     * @return StdClass a stdClass instance of the form :
     * {context: calls getExecutionContext(), exception:{class: exception class, message: error message, code: exception code}}
     */
    protected function convertServiceExceptionToJson($p,$conn,$exception) {
        if(!isset($exception)) return null;
        if($exception instanceof ServiceException) $exception = $exception->getWigiiRootException();
        $returnValue = array();
        $returnValue['name'] = get_class($exception);
        $returnValue['message'] = $exception->getMessage();
        $returnValue['code'] = $exception->getCode();
        $returnValue = (object)$returnValue;
        
        $returnValue = array('context'=>$this->getExecutionContext($p, $conn),'exception'=>$returnValue);
        return (object)$returnValue;
    }
    
    /**
     * Gets an StdClass instance representing the current execution context, ready to be pushed in JSON to client
     * @param Principal $p current principal executing the request
     * @param ConnectionInterface $conn current web socket connection
     * @return StdClass a stdClass instance of the form :
     * {protocol: wigii application protocol, realUsername: real user name, username: role name, principalNamespace: principal current namespace,
     *  version: Wigii system version label}
     */
    protected function getExecutionContext($p,$conn) {
        if(!isset($p) || !isset($conn)) throw new ServiceException("principal and web socket connection cannot be null", ServiceException::INVALID_ARGUMENT);
        $returnValue = array();
        $returnValue['protocol'] = $conn->wigiiProtocol;
        $returnValue['realUsername'] = $p->getRealUsername();
        $returnValue['username'] = $p->getUsername();
        $returnValue['principalNamespace'] = $p->getWigiiNamespace()->getWigiiNamespaceUrl();
        $returnValue['version'] = VERSION_LABEL;
        return (object)$returnValue;
    }
    
    private $xmlConfig;
    
    /**
     * Returns a Web sockets configuration value from the WebSockets_config xml file, given its name.
     * @param String $name xml node name
     * @return String|SimpleXmlElement the value of the node as a string or whole SimpleXmlElement if name is not defined.
     */
    protected function getXmlConfig($name=null) {
        if(!isset($this->xmlConfig)) {
            $this->xmlConfig = simplexml_load_file($this->getConfigFileName());
            if(!$this->xmlConfig) throw new ServiceException("Error loading WebSockets configuration file '".$this->getConfigFileName()."'", ServiceException::CONFIGURATION_ERROR);
        }
        if(!empty($name)) {
            $returnValue = $this->xmlConfig->{$name};
            if(isset($returnValue)) $returnValue = (string)$returnValue;
        }
        else $returnValue = $this->xmlConfig;
        return $returnValue;
    }
    /**
     * Returns Web sockets server parameter given its name
     * @param String $name the name of the parameter as listed in the parameters section of the xml configuration file
     * @return String the parameter value or null if not defined.
     */
    protected function getParameter($name) {
        $params = $this->getXmlConfig()->parameters;
        $returnValue = null;
        if($params) $returnValue = (string)$params[$name];
        return $returnValue;
    }	
}