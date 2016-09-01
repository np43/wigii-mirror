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
 * This service will provide the possibility to do translation of keys
 * in either the default language, either a specific language
 * the dictionary is stored in session.
 * Created by LWE on 16 juil. 09
 * Modified by CWE on 24 mars 2015 to store the dictionary in shared memory into the db
 */
class TranslationServiceImpl implements TranslationService {

	private $_debugLogger;
	private $_executionSink;

	private $language; //curent language of the translation service
	protected $dictionary; //current dictionary of the translation service
	private $systemDictionaryPath; //file path to load a system Dictionary
	private $executionModule; //this is used to solve element traduction
	private $subExecutionModule; //temporary execution module in case of subItems

	public function setSubExecutionModule($module){
		$this->executionSink()->log("setSubExecutionModule");
		$this->subExecutionModule = $module;
	}
	public function resetSubExecutionModule(){
		$temp = $this->subExecutionModule;
		unset($this->subExecutionModule);
		$this->executionSink()->log("resetSubExecutionModule");
//		fput("sub: ".put($this->subExecutionModule));
//		fput("temp:".put($temp));
//		fput("exec: ".put($this->executionModule));
		return $temp;
	}
	public function setExecutionModule($module){
		$this->executionSink()->log("setExecutionModule");
		$this->executionModule = $module;
		$this->resetSubExecutionModule();
		//on garde en session cette information
		$this->getSessionAdminService()->storeData($this,"executionModule", $this->executionModule);
	}
	protected function getExecutionModule(){
		if(isset($this->subExecutionModule)) return $this->subExecutionModule;
		//load the ExecutionModule from session
		if(!isset($this->executionModule)){
			$executionModule = $this->getSessionAdminService()->getData($this,"executionModule");
			if($executionModule != null){
				$this->executionModule = $executionModule;
				$this->debugLogger()->write("ExecutionModule: ".$this->executionModule->getModuleName()." set from session");
			}
		}
		return $this->executionModule;
	}

	private $sessionCacheEnabled;
	public function setSessionCacheEnabled($sessionCacheEnabled){
		$this->sessionCacheEnabled = $sessionCacheEnabled;
	}
	protected function isSessionCacheEnabled(){
		return $this->sessionCacheEnabled;
	}

	private $sharedCacheEnabled;
	/**
	 * Enables or not the shared cache of the dictionary data into the database.
	 * This works only if session cache is also enabled.
	 * Activated by default.
	 */
	public function setSharedCacheEnabled($sharedCacheEnabled){
		$this->sharedCacheEnabled = $sharedCacheEnabled;
	}
	/**
	 * @return boolean
	 */
	public function isSharedCacheEnabled(){
		return $this->sharedCacheEnabled;
	}
	
	private $sessionAS;
	private $configS;

	private $language_installed = array(
		"l01"=>"l01",
		"l02"=>"l02",
		"l03"=>"l03",
		"l04"=>"l04",
		"l05"=>"l05",
		"l06"=>"l06",
		"l07"=>"l07",
		"l08"=>"l08",
		"l09"=>"l09",
		"l10"=>"l10"
		);
	private $language_visible = array("l01"=>"English", "l02"=>"Français");

	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("TranslationService");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("TranslationService");
		}
		return $this->_executionSink;
	}

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
	public function setConfigService($configService)
	{
		$this->configS = $configService;
	}
	protected function getConfigService()
	{
		// autowired
		if(!isset($this->configS))
		{
			$this->configS = ServiceProvider::getConfigService();
		}
		return $this->configS;
	}

	public function __construct(){
		$this->setSessionCacheEnabled(true);
		$this->setSharedCacheEnabled(false);
		$this->debugLogger()->write("creating instance");
	}

	public function getInstalledLanguage(){
		return $this->language_installed;
	}
	protected function setInstalledLanguage($languages){
		$this->language_installed = $languages;
	}
	/**
	 * @return Array [l01=>English,l02=>Français]
	 */
	public function getVisibleLanguage($key = null){
		if($key == null) return $this->language_visible;
		return $this->language_visible[$key];
	}
	public function setVisibleLanguage($languages){
		$this->language_visible = $languages;
	}

	public function getSystemDictionaryPath(){
		//load the systemDictionaryPath from session
		if(!isset($this->systemDictionaryPath) && $this->isSessionCacheEnabled()){
			$sessSysDicoPath = $this->getSessionAdminService()->getData($this,"systemDictionaryPath", $this->isSharedCacheEnabled());
			if($sessSysDicoPath != null){
				$this->systemDictionaryPath = $sessSysDicoPath;
				$this->debugLogger()->write("SystemDictionaryPath: ".$this->systemDictionaryPath." set from session");
			}
		}
		return $this->systemDictionaryPath;
	}
	public function setSystemDictionaryPath($path){
		if(is_null($path))
		{
			$this->systemDictionaryPath = null;
		}
		else
		{
			$tpath = trim($path);
			if($tpath == '')
			{
				$this->systemDictionaryPath = null;
			}
			else
			{
				$this->systemDictionaryPath = $tpath;
			}
		}
		$this->debugLogger()->write("Set system dictionary path to: ".$this->systemDictionaryPath);
		//on garde en session cette information
		if($this->isSessionCacheEnabled()) $this->getSessionAdminService()->storeData($this,"systemDictionaryPath", $this->systemDictionaryPath, false, $this->isSharedCacheEnabled());
	}

	public function getLanguage($translated=false){
		//load the language from session
		//in this case we just want to have it stored in session.
		//it is only the dictionnary cache that we want to be able to prevent
		if(!isset($this->language) && (true || $this->isSessionCacheEnabled())){
			$sessLang = $this->getSessionAdminService()->getData($this,"language");
			if($sessLang != null){
				$this->language = $sessLang;
				$this->debugLogger()->write("Language: ".$this->language_visible[$this->language]." set from session");
			}
		}
		if($translated){
			return $this->language_visible[$this->language];
		} else {
			return $this->language;
		}
	}
	//if the language is not a valid language, then throws TranslationServiceException::INVALID_ARGUMENT
	public function setLanguage($language){
		if(!isset($this->language_installed[$language])){
			//if bad then select the first language
			reset($this->language_visible);
			$this->language = key($this->language_visible);
//			$key = array_search($language, $this->language_visible);
//			if(false === $key){
//				//if bad then select the first language
//				reset($this->language_visible);
//				$this->language = key($this->language_visible);
//				//throw new TranslationServiceException('Bad language parameter', TranslationServiceException::INVALID_ARGUMENT);
//			} else {
//				$this->language = $key;
//			}
		} else {
			$this->language = $language;
		}
		//in this case we just want to have it stored in session.
		//it is only the dictionnary cache that we want to be able to prevent
		if(true || $this->isSessionCacheEnabled()) $this->getSessionAdminService()->storeData($this,"language", $this->language);
		$this->executionSink()->log("Language set to: ".$this->language_visible[$this->language]);
	}

	protected function getDictionary(){
		if(!isset($this->dictionary) && $this->sessionCacheEnabled){
			$sessDico = $this->getSessionAdminService()->getData($this,"dictionary", $this->isSharedCacheEnabled());
			if(isset($sessDico)){
				if($this->isSharedCacheEnabled() && !is_array($sessDico)) {
					$this->dictionary = str2array($sessDico);
				}
				else {
					$this->dictionary = $sessDico;
					// checks compatibility 
					if(!is_array($this->dictionary)) $this->dictionary = str2array($this->dictionary);												
				}
				$this->debugLogger()->write("Dictionary loaded from session");
				//$this->debugLogger()->write(private_put($this->dictionary));
			}
		}
		return $this->dictionary;
	}
	/**
	 * set the dictionary depending of the principal settings.
	 */
	protected function setDictionary($principal){

		list($sysKey, $clientKey, $nspKey) = $this->getDictionaryKeys($principal);

		if(!isset($this->dictionary)){
			$this->getDictionary();
			if(!isset($this->dictionary)) $this->dictionary = array();
			$loadedFromSession = true;
		}
		else $loadedFromSession = false;

		$updateSession = false;
		
		//load the system dictionary
		if(!isset($this->dictionary[$sysKey])){
			$this->dictionary[$sysKey] = array();
			//load the dictionary system file
			if(is_file($this->systemDictionaryPath)){
				$this->loadFileInDictionary($this->systemDictionaryPath, $sysKey);
				$updateSession = true;
			}
		}
		//load the client dictionary
		if(!isset($this->dictionary[$clientKey])){
			$this->dictionary[$clientKey] = array();
			$path = $this->getConfigService()->getConfigFolderPath().$principal->getWigiiNamespace()->getClient()->getClientName()."/dico_".$principal->getWigiiNamespace()->getClient()->getClientName().".txt";
			if(is_file($path)){
				$this->loadFileInDictionary($path, $clientKey);
				$updateSession = true;
			}
		}
		//load the client wigiiNamespace dictionary
		if(!isset($this->dictionary[$nspKey])){
			$this->dictionary[$nspKey] = array();
			$path = $this->getConfigService()->getConfigFolderPath().$principal->getWigiiNamespace()->getClient()->getClientName()."/".strtolower($principal->getWigiiNamespace()->getWigiiNamespaceName())."_dico_".$principal->getWigiiNamespace()->getClient()->getClientName().".txt";
			if(is_file($path)){
				$this->loadFileInDictionary($path, $nspKey);
				$updateSession = true;
			}
		}

		if($this->isSessionCacheEnabled() && $updateSession) {			
			$this->getSessionAdminService()->storeData($this,"dictionary", 
					($this->isSharedCacheEnabled() ? array2str($this->dictionary) : $this->dictionary),
					false, $this->isSharedCacheEnabled());
			$this->debugLogger()->write("Dictionary stored in session");
		}
		
		// merges dictionaries into memory
		if($loadedFromSession) {
			// builds client dico
			$this->dictionary[$clientKey] = array_merge($this->dictionary[$sysKey], $this->dictionary[$clientKey]);
			// builds wigiiNamespace dico
			$this->dictionary[$nspKey] = array_merge($this->dictionary[$clientKey], $this->dictionary[$nspKey]);
		}
		elseif($updateSession || empty($this->dictionary[$nspKey])) {
			// builds wigiiNamespace dico
			$this->dictionary[$nspKey] = array_merge($this->dictionary[$clientKey], $this->dictionary[$nspKey]);
		}
	}

	/**
	 * add the content of a file in the specified dictionary
	 */
	protected function loadFileInDictionary($filepath, $dicoKey){
		$this->debugLogger()->write("Load dictionary from file: ".$filepath." in dictionary key: ".$dicoKey);
		$file = file($filepath);
		foreach($file as $line){
			$line = rtrim($line);
			$line = str_replace("\\:", "%dp%", $line);
			$line = explode(":", $line);
			$i = 1; //place of the language in the file
			foreach($this->language_installed as $lang=>$language){
				$this->dictionary[$dicoKey][$line[0]][$lang] = str_replace("%dp%", ":", $line[$i++]);
			}
		}
	}

	/**
	 * translate the key in the given language. If no language defined then
	 * translate based on the language of the service
	 */
	public function translate($principal, $key, $xmlNode=null, $lang=null){
		if($lang == null){
			$lang = $this->getLanguage();
			if(!isset($lang)) throw new TranslationServiceException('Try to translate but no language is set', TranslationServiceException::NO_LANGUAGE_SET);
		}

		$returnValue = null;

		//xmlNode management
		//dans le cas ou le XML_node est envoyé, on check si ce noeud contient
		//une traduction.
//		eput("\n".$key.":");
//		eput($xmlNode);
		if($xmlNode !== null){
			$label = $xmlNode->{"label_".$lang};
			//malheureusement lorsque l'on fait un asXML le node actuelle est aussi
			//retourné. Il faut donc enlever le <label_fr> et le </label_fr>
//			eput("0");
			if($label !== null && $label->asXML()){
//				eput("1");
				//eput((string)$label);
				$returnValue = substr($label->asXML(), strlen("label_".$lang)+2, -(strlen("label_".$lang)+3));
			} else {
//				eput("2");
				$label = $xmlNode->label;
				if($label !== null && $label->asXML()){
//					eput("3");
					//eput((string)$label);
					$returnValue = substr($label->asXML(), strlen("label")+2, -(strlen("label")+3));
//					eput($returnValue);
				}
			}
		}

		//if no xmlNode, or xmlNode was not successful look in the dictionary
		if(empty($returnValue)){
			$dicoKey = $this->getDictionaryKey($principal);
			if(!isset($this->dictionary) || !isset($this->dictionary[$dicoKey])){
				$this->setDictionary($principal);
			}
			$returnValue = $this->dictionary[$dicoKey][$key][$lang];
			if(empty($returnValue) && $this->dictionary[$dicoKey][$key]){
				$returnValue = reset($this->dictionary[$dicoKey][$key]);
			}
		}

//		//on essaie encore en ajoutant le string _value après
//		if(empty($returnValue)){
//			$returnValue=$this->dictionary[$dicoKey][str_replace("tags_", "", str_replace("_value", "", $key))][$lang];
//		}
		//on essaie encore avec les champs traduit
		if(empty($returnValue)){
			foreach($this->language_installed as $tempLang=>$langLabel){
				if( empty($returnValue)) $returnValue= $this->dictionary[$dicoKey][str_replace("_value_".$tempLang, "", $key)][$lang];
				if(!empty($returnValue)) $returnValue .= " (".strtoupper($tempLang).")";
				if(!empty($returnValue)) break;
			}
		}
		//si l'on a toujours pas trouvé on enlève tout de même le truc _value
		//dans la clé, pour que cela s'affiche de la meilleure manière
		if(empty($returnValue)){
			$returnValue = str_replace("_value", "", $key);
		}

		if (is_string($returnValue) && $this->getExecutionModule()!=null){
			//on remplace les termes entre #..# par leur traduction
			$returnValue = str_replace('\#', '$dieze$', $returnValue);
			$returnValue = explode("#", $returnValue);
			foreach($returnValue as $i=>$token){
				if(($i % 2)!=0){
					if(strstr($token, "lement")!==false){
						$token .= $this->getExecutionModule()->getModuleName();
					}
					$temp = $this->dictionary[$dicoKey]["#".$token."#"][$lang];
//					eput($token); echo "|";
//					eput($dicoKey); echo "|";
//					eput($this->dictionary[$dicoKey]["#elements#"]); echo "|";
					if($temp != null) $returnValue[$i]=$temp;
				}
			}
			$returnValue = implode("", $returnValue);
			$returnValue = str_replace('$dieze$', '#', $returnValue);
		}
//		eput($returnValue);
		return $returnValue;

	}
	public function t($principal, $key, $xmlNode=null, $lang=null){
		return $this->translate($principal, $key, $xmlNode, $lang);
	}
	/**
	 * the h function replace all ' with \' in the translation result
	 */
	public function h($principal, $key, $xmlNode=null, $lang=null){
		return str_replace("'", "\'", $this->translate($principal, $key, $xmlNode, $lang));
	}

	/**
	 * WARNING, this method needs to be used very quarefully and only for debug reason
	 * because of the amount of data it can contain the function put will probably
	 * do a Fatal error: Allowed memory size of xx bytes exhausted (tried to allocate yy bytes) in...
	 */
	public function displayDebug(){
		return put($this->dictionary);
	}

	/**
	 * returns a string used find the dictionary to use for this principal
	 */
	protected function getDictionaryKey($principal){
		list($sysKey, $clientKey, $nspKey) = $this->getDictionaryKeys($principal);
		return $nspKey;
	}

	/**
	 * return an array with 3 keys based on principal attributes
	 * those keys will be used to store and find a dictionary
	 */
	protected function getDictionaryKeys($principal){
		$sysKey = "system";
		$clientKey = $sysKey;
		$nspKey = $sysKey;

		if($principal == null) return array($sysKey, $clientKey, $nspKey);

		$wigiiNamespaceName = $principal->getWigiiNamespace()->getWigiiNamespaceName();
		$clientName = $principal->getWigiiNamespace()->getClient()->getClientName();

		if($clientName != null){
			$clientKey .= "_".$clientName;
			//if wigiiNamespace is empty, then we still look for a _
			$nspKey = $clientKey."_".$wigiiNamespaceName;
		}
//		if($clientName!=null && $wigiiNamespaceName != null){
//			$nspKey = "_".$wigiiNamespaceName;
//		}

		return array($sysKey, $clientKey, $nspKey);
	}
}
