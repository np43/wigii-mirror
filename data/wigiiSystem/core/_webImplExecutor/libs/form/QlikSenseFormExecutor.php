<?php
/**
*  This file has been added to Wigii by Medair.
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
*  @copyright  Copyright (c) 2016  Medair
*  @author     <http://www.medair.org>            Medair.org
*  @link       <http://www.wigii-system.net>      <https://github.com/wigii/wigii>   Source Code
*  @license    <http://www.gnu.org/licenses/>     GNU General Public License
*/

/**
 * QlikSense client
 * 
 * Allows to Single Sign On with QlikSense server
 * SSO Sequence diagram
 * 1. End user clicks on a folder in Wigii linked to QlikSense hub or opens a Wigii card containing a mashup calling a QlikSense report or object.
 * 2. QlikSense receives the request and checks that the user is not yet logged into QlikSense,
 * 3. QlikSense redirects the user to Wigii instance at url NoWigiiNamespace/NoModule/qliksense/login
 * 4. Wigii triggers QlikSenseFormExecutor web service and launches Single Sign On process.
 * 5. QlikSenseFormExecutor calls QlikSense server using json API and given technical user and ssl server certificate, to get a QlikSense web ticket. 
 * It passes the Principal list of roles as a Role custom attribute in the http request.
 * 6. QlikSenseFormExecutor redirects the user to QlikSense to ask again for the initial request passing the received web ticket in the url.
 * 7. QlikSense converts the web ticket as a session cookie and authorizes or not the view of the asked report.
 * 
 * QlikSenseFormExecutor needs .htaccess to Append Query Strings on Wigii index.php. Add [QSA] modifier if not present.
 * 
 * QlikSense uses security rules to check for :
 * - license pooling
 * - stream authorizations based on Role custom attributes.
 *
 * Created by Medair (Camille Weber) on October 31th 2016
 */
class QlikSenseFormExecutor extends WebServiceFormExecutor {
	
	private $_debugLogger;
	private $_executionSink;
	
	// Dependency injection
	private function debugLogger(){
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("QlikSenseFormExecutor");
		}
		return $this->_debugLogger;
	}
	
	
	private function executionSink(){
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("QlikSenseFormExecutor");
		}
		return $this->_executionSink;
	}
	
	private $guzzleHelper;
	public function setGuzzleHelper($guzzleHelper)
	{
		$this->guzzleHelper = $guzzleHelper;
	}
		
	protected function getGuzzleHelper()
	{
		// autowired
		if(!isset($this->guzzleHelper)){
			$this->guzzleHelper = TechnicalServiceProvider::getGuzzleHelper();
		}
		return $this->guzzleHelper;
	}
	
	// Configuration
		
	private $qlikSenseConfigFileName;
	public function setQlikSenseConfigFileName($fileName) {
		$this->qlikSenseConfigFileName = $fileName;
	}
	protected function getQlikSenseConfigFileName() {
		if(!isset($this->qlikSenseConfigFileName)) {
			$this->qlikSenseConfigFileName = CLIENT_CONFIG_PATH.'QlikSense_config.xml';
		}
		return $this->qlikSenseConfigFileName;
	}
	
	/**
	 * Returns the path to the QlikSense server SSL certificate which needs to encrypts the API requests
	 * This file should be a text file, in the PEM format, including the RSA private and public keys, as well as any CA chain.
	 * @return string
	 */
	protected function getQlikSenseCertificateFileName() {
		$returnValue = $this->getParameter('serverCertificateFileName');
		if(!isset($returnValue)) $returnValue = 'QlikSense_certificate.pem';
		return CLIENT_CONFIG_PATH.$returnValue;
	}
	
	/**
	 * Returns QlikSense url including virtual proxy as defined in config file.
	 * @return String QlikSense url with ending slash
	 */
	public function getQlikSenseUrl() {
		$returnValue = $this->getXmlConfig('qlikSenseUrl');
		if(!empty($returnValue)) {
			$sep = (substr($returnValue,-1)=='/'?'':'/');
			$proxy = $this->getXmlConfig('virtualProxy');
			if(!empty($proxy)) $returnValue .= $sep.$proxy.'/';
			else $returnValue.=$sep;
		}
		return $returnValue;
	}
	
	// QlikSense WebExecutor implementation

	public function isMinimalPrincipalAuthorized() {return false;}
	public function processAndEnds($p,$exec) {	
		try {
			$args = $exec->getCrtParameters();
			if(empty($args)) $nArgs = 0; else $nArgs = count($args);
			if($nArgs > 0) {
				// /login
				if(arrayMatch($args,'login')) {
					$returnValue = $this->doQlikSenseLogin($p, wigiiBPLParam('qlikSenseUrl',$_GET['proxyRestUri'], 'targetId', $_GET['targetId']));
					if($returnValue) header('Location: '.$returnValue,true,303);
					else throw new ServiceException('No redirection provided after QlikSense authentication', ServiceException::NOT_FOUND);
				}				
				else throw new ServiceException('unsupported request', ServiceException::UNSUPPORTED_OPERATION);
			}
			else throw new ServiceException('unsupported request', ServiceException::UNSUPPORTED_OPERATION);
		}
		catch(Exception $e) {
			header($_SERVER["SERVER_PROTOCOL"]." 500 Internal Error"); 
			header("Access-Control-Allow-Origin: *");
			header("Content-Type: text/xml; charset=UTF-8");			
			echo TechnicalServiceProvider::getWplToolbox()->stdClass2Xml($p, 'wigiiFxError', $this->getWigiiExecutor()->convertServiceExceptionToJson($p, $exec, $e));
			// signals fatal error to monitoring system
			ServiceProvider::getClientAdminService()->signalFatalError($e);
		}
	}	
	
	/**
	 * Authenticates the current principal to give him access to QlikSense server
	 * @param Principal $principal principal against which to get a QlikSense ticket
	 * @param WigiiBPLParameter $options a bag of parameters, must contain at least
	 * - qlikSenseUrl: String. The QlikSense server url to which ask the ticket and used as a final redirection
	 * - targetId: String. QlikSense report ID asked by the user
	 * @return String final URL that can be sent as a redirection to client to access the asked report
	 */
	protected function doQlikSenseLogin($principal, $options) {
		// Extracts parameters
		$qlikSenseUrl = $options->getValue('qlikSenseUrl');
		if(empty($qlikSenseUrl)) throw new ServiceException('qlikSenseUrl cannot be empty', ServiceException::INVALID_ARGUMENT);
		$targetId = $options->getValue('targetId');
		if(empty($targetId)) throw new ServiceException('QlikSense targetId cannot be empty', ServiceException::INVALID_ARGUMENT);
		
		// Checks host and virtualproxy matching between qlikSenseUrl and qlikSenseUrl defined in config, if not throws an UNAUTHORIZED excetion
		// Warning: ports are normally different.
		$configUrl = $this->getXmlConfig('qlikSenseUrl');
		$configUrl = substr($configUrl,0,strpos($configUrl,':'));
		$virtProxy = $this->getXmlConfig('virtualProxy');
		if(strpos($qlikSenseUrl, $configUrl)!==0 ||
			!empty($virtProxy) && strpos($qlikSenseUrl,$virtProxy)===false
		) throw new ServiceException("received QlikSense url '$qlikSenseUrl' does not match QlikSense URL defined in config. ".$configUrl, ServiceException::UNAUTHORIZED);
		
		// Gets QlikSense web ticket		
		$qlikSenseTicket = $this->getQlikSenseWebTicket($principal, $qlikSenseUrl, $targetId);
		// Builds final url
		$returnValue = $qlikSenseTicket->TargetUri.(strpos($qlikSenseTicket->TargetUri, '?')===false?'?':'&').'qlikTicket='.$qlikSenseTicket->Ticket;
		return $returnValue;
	}
	
	/**
	 * Asks for a QlikSense Web ticket
	 * @param Principal $principal current principal asking for a request
	 * @param String $qlikSenseUrl QlikSense server url
	 * @param String $targetId QlikSense report id
	 * @return stdClass an stdClass instance of the form {TargetUri: base redirect url to which add the web ticket,Ticket: generated web ticket}
	 */
	protected function getQlikSenseWebTicket($principal,$qlikSenseUrl,$targetId) {		
		// prepares POST request to get a qlik ticket
		$httpClient = $this->getGuzzleHelper()->createHttpClient($principal);
		$httpClient->setSslVerification(false);
		$xrfkey = substr(udate('YmdHisu'),0,16);
		$headers = array(
			'X-Qlik-Xrfkey'=>$xrfkey,
			'X-Qlik-User'=>'UserDirectory='.$this->getXmlConfig('qlikSenseTecUser_Directory').'; UserId='.$this->getXmlConfig('qlikSenseTecUser'),
			'Content-Type'=>'application/json'		
		);
		$certFile = dirname($_SERVER["SCRIPT_FILENAME"])."/".$this->getQlikSenseCertificateFileName();
		//$this->debugLogger()->write($certFile);
		$options = array(
			'cert'=> $certFile
		);		
		// Fills any custom attributes
		$attributes = array();
		if($this->getParameter('includeUserRolesOnLogin')!=='0') $this->fillAttributesWithPrincipalRoles($principal, $attributes); 
			
		// Prepares webticket request
		$body = array(
			'UserDirectory'=>$this->getXmlConfig('userDirectory'),
			'UserId'=>$principal->getRealUsername(),
			'TargetId'=>$targetId,
			'Attributes'=>$attributes
		);		
		$httpRequest = $httpClient->post($qlikSenseUrl.'ticket?Xrfkey='.$xrfkey,$headers,json_encode((object)$body),$options);		
		// Sends request
		$response = $this->getGuzzleHelper()->sendHttpRequest($principal, $httpClient, $httpRequest);
		// Parses answer
		$returnValue = $response->getBody();
		$returnValue = json_decode($returnValue);
		return $returnValue;
	}
	
	/**
	 * Fills the Web ticket request attributes array with principal roles
	 * If userRolesSeparator is defined in config, then creates only one Role attribute containing role list imploded with the given separator,
	 * If userRolesIncludeNamespace=1 is defined in config, then role names contain @WigiiNamespace suffix, else WigiiNamespace is removed. Default to 0.
	 * Role list is filtered for duplicates.
	 * @param Principal $principal current Principal
	 * @param Array $attributes open attributes array to be filled with StdClasses
	 */
	private function fillAttributesWithPrincipalRoles($principal,&$attributes) {
		// gets all principal roles (fetches them from db if not present)
		$roleList = $principal->getRoleListener();
		if(!($roleList instanceof UserListForNavigationBarImpl)) {
			// gets default WigiiNamespace
			$defaultWigiiNamespace = (string)$this->getConfigService()->getParameter($principal, null, "defaultWigiiNamespace");
			if(!$defaultWigiiNamespace){
				$defaultWigiiNamespace = $principal->getRealWigiiNamespace()->getWigiiNamespaceUrl();
			}
			// fetches all principal roles
			$principal->refetchAllRoles(lf(null, null, fskl(fsk('wigiiNamespace'), fsk('username'))), UserListForNavigationBarImpl::createInstance($defaultWigiiNamespace));
			$roleList = $principal->getRoleListener();
		}
		
		if(!$roleList->isEmpty()) {
			$includeWigiiNamespace = $this->getParameter('userRolesIncludeNamespace')=='1';
			$userRoleSeparator = $this->getParameter('userRolesSeparator');			
			$roleArr = array();
			// stores all role names, filters duplicates and calculated roles
			foreach($roleList->getListIterator() as $role) {
				if($role->isCalculatedRole() || !$role->isRole()) continue;
				// trims @WigiiNamespace from role name
				$roleName = $role->getUsername();
				if(!$includeWigiiNamespace) $roleName = str_replace('@'.$role->getWigiiNamespace()->getWigiiNamespaceName(), '', $roleName);
				$roleArr[$roleName] = $roleName;
			}
			// if a separator should be used, then creates only one attribute Role and implodes the array using the separator
			if(!empty($userRoleSeparator)) {
				$attributes[] = (object)array('Role'=>implode($userRoleSeparator,$roleArr));
			}
			// else creates as many attributes as array entries
			else {
				foreach($roleArr as $roleName) {
					$attributes[] = (object)array('Role'=>$roleName);
				}
			}
		}
	}
	
	// Implementation
	
	private $xmlConfig;
	
	/**
	 * Returns a QlikSense configuration value from the QlikSense_config xml file, given its name.
	 * @param String $name xml node name
	 * @return String|SimpleXmlElement the value of the node as a string or whole SimpleXmlElement if name is not defined.
	 */
	protected function getXmlConfig($name=null) {
		if(!isset($this->xmlConfig)) {
			$this->xmlConfig = simplexml_load_file($this->getQlikSenseConfigFileName());
			if(!$this->xmlConfig) throw new ServiceException("Error loading QlikSense configuration file '".$this->getQlikSenseConfigFileName()."'", ServiceException::CONFIGURATION_ERROR);
		}
		if(!empty($name)) {
			$returnValue = $this->xmlConfig->{$name};
			if(isset($returnValue)) $returnValue = (string)$returnValue;
		}
		else $returnValue = $this->xmlConfig;
		return $returnValue;
	}
	/**
	 * Returns QlikSense facade parameter given its name
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