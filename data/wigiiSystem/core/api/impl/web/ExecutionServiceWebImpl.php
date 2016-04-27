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
 * ExecutionService specialization which integrates http GET, COOKIES, JS code push.
 * Created by LWR on 21 july 09
 * Modified by CWE on 01.12.2015 to support JS notifications
 */
class ExecutionServiceWebImpl extends ExecutionServiceImpl
{
	private $_debugLogger;
	private $_executionSink;
	private $systemConsoleS;
	private $sessionAS;
	private $translationS;
	private $browserName;
	public function getBrowserName(){
		if(!isset($this->browserName)){
			//look in cache
			$browserName = $this->getSessionAdminService()->getData($this, "browserName");
			if(isset($browserName)){
				$this->browserName = $browserName;
			}
		}
		return $this->browserName;
	}
	public function setBrowserName($browserName){
		$this->browserName = $browserName;
		$this->getSessionAdminService()->storeData($this, "browserName", $this->browserName);
	}
	private $browserVersion;
	public function getBrowserVersion(){
		if(!isset($this->browserVersion)){
			//look in cache
			$browserVersion = $this->getSessionAdminService()->getData($this, "browserVersion");
			if(isset($browserVersion)){
				$this->browserVersion = $browserVersion;
			}
		}
		return $this->browserVersion;
	}
	public function setBrowserVersion($browserVersion){
		$this->browserVersion = $browserVersion;
		$this->getSessionAdminService()->storeData($this, "browserVersion", $this->browserVersion);
	}
//	private $windowHeight;
//	public function getWindowHeight(){
//		if(!isset($this->windowHeight)){
//			//look in cache
//			$windowHeight = $this->getSessionAdminService()->getData($this, "windowHeight");
//			if(isset($windowHeight)){
//				$this->windowHeight = $windowHeight;
//			}
//		}
//		return $this->windowHeight;
//	}
//	public function setWindowHeight($windowHeight){
//		$this->windowHeight = $windowHeight;
//		$this->getSessionAdminService()->storeData($this, "windowHeight", $this->windowHeight);
//	}

	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("ExecutionServiceWebImpl");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("ExecutionServiceWebImpl");
		}
		return $this->_executionSink;
	}

	// dependency injection

	public function setSystemConsoleService($systemConsoleService)
	{
		$this->systemConsoleS = $systemConsoleService;
	}
	protected function getSystemConsoleService()
	{
		// autowired
		if(!isset($this->systemConsoleS))
		{
			$this->systemConsoleS = ServiceProvider::getSystemConsoleService();
		}
		return $this->systemConsoleS;
	}
	public function setSessionAdminService($sessionAdminService)
	{
		$this->sessionAS = $sessionAdminService;
	}
	protected function getSessionAdminService()
	{
		// autowired
		if(!isset($this->sessionAS))
		{
			$this->sessionAS = ServiceProvider::getSessionAdminService();
		}
		return $this->sessionAS;
	}
	public function setTranslationService($translationService){
		$this->translationS = $translationService;
	}
	protected function getTranslationService(){
		// autowired
		if(!isset($this->translationS)){
			$this->translationS = ServiceProvider::getTranslationService();
		}
		return $this->translationS;
	}

	//In the Web Implementation, the URL is found in the $_GET magic variable
	public function findUrl(){
		return $_GET['url'];
	}
	//return an array(wigiiNamespace=>val, module=>val, type=>val, id=>val).
	protected function parseFragment(){
		//$frag = str_replace('#', '', $_POST['wigii_anchor']); //the header has a reload if cookie anchor is not set yet
		$frag = str_replace('#', '', $_COOKIE['wigii_anchor']); //the header has a reload if cookie anchor is not set yet
		if($frag == Module::HOME_MODULE || $frag == "logout"){
			return array("wigiiNamespace"=>null, "module"=>Module::HOME_MODULE, "type"=>null, "id"=>null);
		}
		$frag = explode(ExecutionServiceImpl::paramSeparator, $frag);
		if($frag && $frag[1] == Module::ADMIN_MODULE){
			return array();
		}
//		fput($_SERVER);
		if($frag==null || $frag[0]==null) return array();
		//once used reset the cookie to no fragment
		//this is done directly in the header.php to prevent the update setBrowser to send the cookie wigii_anchor once again
		//$this->addJsCode("$.cookie('wigii_anchor', '#',  { path: '/' });");
		return array("wigiiNamespace"=>str_replace("%20", " ", $frag[0]), "module"=>str_replace("%20", " ", $frag[1]), "type"=>$frag[2], "id"=>$frag[3]);
	}

	protected function loadNewContext(){
		$returnValue = parent::loadNewContext();
		$otherJsCode = "";

		$this->addJsCode("
crtRoleId = '" . $this->getExecPrincipal()->getUserId() . "';
crtContextId = '".$this->getCrtContext()."';
crtWigiiNamespaceUrl = '" . $this->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "';
crtModuleName = '" . $this->getCrtModule()->getModuleUrl() . "';
$otherJsCode
");
		return $returnValue;
	}

	public function reload($url = null){
		echo ExecutionServiceImpl::answerRequestSeparator;
		echo "Reload";
		echo ExecutionServiceImpl::answerParamSeparator;
		echo $url;
		echo ExecutionServiceImpl::answerRequestSeparator;
	}
	public function alert($text = null){
		echo ExecutionServiceImpl::answerRequestSeparator;
		echo "Alert";
		echo ExecutionServiceImpl::answerParamSeparator;
		echo $text;
		echo ExecutionServiceImpl::answerRequestSeparator;
	}
	/**
	 * Pushes some JS code to the browser.
	 * This method can be called from anywhere.
	 */
	public function addJsCode($code){
		$this->getSystemConsoleService()->addJsCode($code, $this->getIdAnswer());
	}
	/**
	 * Pushes some JS notification to the browser.
	 * @param String $target notification target. One of searchBar,elementDialog.
	 * @param String $type notification type. One of help,info,warning,error,notif.
	 * @param String $url notification callback url to get notification content.
	 * @param WigiiBPLParameter $options an optional map of options to be passed to the WigiiApi JS client.
	 * example: addJsNotif("searchBar","help","User Guide/Filemanager/help/item/12345/integratedFile")
	 * will add a (?) icon in the searchBar toolbox, displaying a help popup with the html content of the 12345 element of the User Guide.
	 * Some options could be "width","height","top","left","title",... see WigiiApi.js Popup class for more detail.
	 */
	public function addJsNotif($target,$type,$url,$options=null){
		$this->getSystemConsoleService()->addJsNotif($target, $type, $url, $options);
	}

	//action can have more parameters with /
	public function getCurrentUpdateJsCode($p, $idAnswer, $idLookup, $action, $checkAnyOpenItem=false, $informIfFoundInCache=false){
		$module = $this->getCrtModule()->getModuleName();
		$wigiiNamespace = $this->getCrtWigiiNamespace()->getWigiiNamespaceUrl();
		if(!$checkAnyOpenItem) $checkAnyOpenItem = 0;
		if(!$informIfFoundInCache) $informIfFoundInCache = 0;
		return "updateThroughCache('$idAnswer', '".$this->getCurrentCacheLookup($p, $idLookup, $action)."', '$idAnswer/$wigiiNamespace/$module/$action', $checkAnyOpenItem, $informIfFoundInCache);";
	}
	public function getUpdateJsCode($realUserId, $userId, $wigiiNamespace, $module, $idAnswer, $idLookup, $action, $checkAnyOpenItem=false, $informIfFoundInCache=false){
		if($module && is_object($module)) $module = $module->getModuleName();
		if($wigiiNamespace && is_object($wigiiNamespace)) $wigiiNamespace = $wigiiNamespace->getWigiiNamespaceUrl();
		if(!$checkAnyOpenItem) $checkAnyOpenItem = 0;
		if(!$informIfFoundInCache) $informIfFoundInCache = 0;
		return "updateThroughCache('$idAnswer', '".$this->getCacheLookup($realUserId, $userId, $wigiiNamespace, $module, $idLookup, $action)."', '$idAnswer/$wigiiNamespace/$module/$action', $checkAnyOpenItem, $informIfFoundInCache);";
	}
	public function getCurrentCacheLookup($p, $idLookup=null, $action=null){
		if($idLookup==null){
			$idLookup = $this->getIdAnswer();
		}
		if($action==null){
			$action = $this->getCrtAction();
		}
		return $this->getCacheLookup($p->getRealUserId(), $p->getUserId(), $this->getCrtWigiiNamespace(), $this->getCrtModule(), $idLookup, $action);
	}
	public function getCacheLookup($realUserId, $userId, $wigiiNamespace, $module, $idLookup, $action){
		if($module && is_object($module)) $module = $module->getModuleName();
		if($wigiiNamespace && is_object($wigiiNamespace)) $wigiiNamespace = $wigiiNamespace->getWigiiNamespaceUrl();

		$reduceLookup = true;
		switch($idLookup){
			//unupdated area: those html code is not updated when the change of module and users are done
			//that means that in the cacheLookup there is some JS code.
			//that implicates no php reduce function possible to manage the cache
			case Module::HOME_MODULE:
				$returnValue = $idLookup.$realUserId.$module;
				//$reduceLookup = false;
				break;
			case "giveGeneralFeedback":
				$returnValue = $idLookup.$userId.$module;
				//$reduceLookup = false;
				break;
			//updated area: those areas are updated when the module and user/role changes. -> we can reduce the lookup, because there are directly php generated
			case "groupSelectorPanel":
				$returnValue = $idLookup.$userId.$module.$action; //the module is usefull for the group_0
				break;
			case "groupPanel":
				$returnValue = $idLookup.$userId.$module;
				break;
			case "selectElementDetail":
				$returnValue = $idLookup.$userId.$module.$action;
				break;
			case "addElement":
				$returnValue = $idLookup.$userId.$module.$action;
				break;
			case "userNavigate":
				// action is not part of the key, because it contains the origin which changes.
				$returnValue = $idLookup.$userId.$wigiiNamespace.$module;
				break;
			default: return null;
		}
		if($reduceLookup) return "cacheKey_".md5($returnValue);
		return "cacheKey_".$returnValue;
	}
	/**
	 * add in return buffer information to cache the answer of the current request.
	 * This method should be called before any adds in the output buffer (as echo)
	 * @return String the generated cache key
	 */
	public function cacheAnswer($p, $idAnswer, $idLookup=null, $action = null){
		$cacheKey = $this->getCurrentCacheLookup($p, $idLookup, $action);
		if($this->getIsUpdating()){
			echo $cacheKey;
			echo ExecutionServiceImpl::answerParamSeparator;
		} else {
			$this->addJsCode("setCacheFromDom('".$idAnswer."', '".$cacheKey."');");
		}
		return $cacheKey;
	}
	/**
	 * @return String the generated cache key
	 */
	public function cacheCrtAnswer($p, $idLookup){
		$cacheKey = $this->getCurrentCacheLookup($p, $idLookup);
		if($this->getIsUpdating()){
			echo $cacheKey;
			echo ExecutionServiceImpl::answerParamSeparator;
		} else {
			$this->addJsCode("setCacheFromDom('".$this->getIdAnswer()."', '".$cacheKey."');");
		}
		return $cacheKey;
	}
	public function invalidCache($p, $idAnswer, $idLookup = null, $action = null, $wigiiNamespace=null, $module=null){
		$this->addJsCode($this->getInvalidCacheJsCode($p, $idAnswer, $idLookup, $action, $wigiiNamespace, $module));
	}
	public function getInvalidCacheJsCode($p, $idAnswer, $idLookup = null, $action = null, $wigiiNamespace=null, $module=null) {
		if($module && is_object($module)) $module = $module->getModuleName();
		else $module = $this->getCrtModule()->getModuleName();
		if($wigiiNamespace && is_object($wigiiNamespace)) $wigiiNamespace = $wigiiNamespace->getWigiiNamespaceUrl();
		else $wigiiNamespace = $this->getCrtWigiiNamespace()->getWigiiNamespaceUrl();
		return "invalidCache('".$idAnswer."', '".$this->getCacheLookup($p->getRealUserId(), $p->getUserId(), $wigiiNamespace, $module, $idLookup, $action)."');";
	}

	//each multipleParam value are added to the action
	public function invalidMultipleCache($p, $idAnswer, $idLookup, $action, $multipleParam){
		foreach($multipleParam as $param){
			$this->addJsCode("invalidCache('".$idAnswer."', '".$this->getCurrentCacheLookup($p, $idLookup, $action.$param)."');");
		}
	}
	public function flushJsCode(){
		//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++."start ExecutionService flushJsCode"] = microtime(true);
		$this->executionSink()->publishStartOperation("flushJsCode");
		if($this->getSystemConsoleService()->isJsCodePending()){
			$first = true;
			$sysConsole = $this->getSystemConsoleService();
			foreach($sysConsole->getJsCodeIterator() as $requestId=>$jsCode){
				if(!$this->getIsUpdating()){
					?><script id="JSCode<?=$requestId;?>" type="text/javascript" ><?
				} else {
					if(!$first) echo ExecutionServiceImpl::answerRequestSeparator;
					else $first = false;
					echo "JSCode";
					echo ExecutionServiceImpl::answerParamSeparator;
					echo $requestId;
					echo ExecutionServiceImpl::answerParamSeparator;
				}
				echo ' $(document).ready(function(){ ';
				$sysConsole->flushJSCodeForRequest($requestId);
				echo ' }); ';
				if(!$this->getIsUpdating()){
					?></script><?
				}
			}
		}
		//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++."end ExecutionService flushJsCode"] = microtime(true);
		$this->executionSink()->publishEndOperation("flushJsCode");
	}
	public function flushMessages(){
		//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++."start ExecutionService flushMessages"] = microtime(true);
		$this->executionSink()->log("flushMessages"); //cannot start publish operation as the end is after the flushMessages

		if($this->getSystemConsoleService()->isMessagePending()){
			if($this->getIsUpdating()){
				echo ExecutionServiceImpl::answerRequestSeparator;
			}
			if(!$this->getIsUpdating()){
				?><script type="text/javascript" ><?
			} else {
				echo "JSCode";
				echo ExecutionServiceImpl::answerParamSeparator;
			}
			echo ' $(document).ready(function(){ ';
			$this->getSystemConsoleService()->flushMessages();
			echo ' }); ';
			if(!$this->getIsUpdating()){
				?></script><?
			}
		}
		//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++."end ExecutionService flushMessages"] = microtime(true);
	}
	public function flushJsNotif() {
		//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++."start ExecutionService flushJsNotif"] = microtime(true);
		$this->executionSink()->publishStartOperation("flushJsNotif");
		if($this->getSystemConsoleService()->isJsNotifPending()){
			$this->getSystemConsoleService()->flushJSNotif();
		}
		//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++."end ExecutionService flushJsNotif"] = microtime(true);
		$this->executionSink()->publishEndOperation("flushJsNotif");
	}
	public function end(){
		//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++."start ExecutionService end"] = microtime(true);

		$this->executionSink()->log("end"); //cannot start publish operation as the end is after the flushMessages

		$this->flushJsCode();

		$this->flushJsNotif();
		
		$this->flushMessages();
	}

	public function displayTechnicalInfo($p){
		$transS = ServiceProvider::getTranslationService();
		echo $transS->t($p, ($p->getRealUser()!= null ? "role" : "user")).": <b>".$p->getUsername()."</b>";
		echo " # ".$p->getUserId();
		echo "<br>";
		if($p->getRealUser()!= null){
			echo $transS->t($p, "realUser").": <b>".$p->getRealUser()->getUsername()."</b>";
			echo " # ".$p->getRealUser()->getId();
			echo "<br>";
		}
		echo $transS->t($p, "language").": <b>".$transS->getVisibleLanguage($transS->getLanguage())."</b>"; echo "<br>";
		echo $transS->t($p, "module").": <b>".$transS->t($p, $this->getCrtModule()->getModuleName())."</b>"; echo "<br>";
		echo $transS->t($p, "wigiiNamespace").": <b>".$this->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."</b>"; echo "<br>";
		echo $transS->t($p, "browser").": <b>".$this->getBrowserName()."</b> # ".$this->getBrowserVersion(); echo "<br>";
		echo "<b>".VERSION_LABEL."</b>"; echo "<br>";
		echo "<b>".date("G\hi, d.m.Y ")."</b>"; echo "<br>";

	}

	private $crtModule;
	public function getCrtModule(){
		return $this->crtModule;
	}
	protected function setCrtModule($m){
		$this->crtModule = $m;
		if(isset($m) && !$m->isAdminModule() && $m->getModuleName()!=Module::EMPTY_MODULE_URL){
			$this->getTranslationService()->setExecutionModule($m);
		}
	}

}
