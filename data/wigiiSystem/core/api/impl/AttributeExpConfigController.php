<?php
/**
 *  This file is part of Wigii.
 *
 *  Wigii is free software: you can redistribute it and\/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Wigii is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Wigii.  If not, see <http:\//www.gnu.org/licenses/>.
 *
 *  @copyright  Copyright (c) 2012 Wigii 		 http://code.google.com/p/wigii/    http://www.wigii.ch
 *  @license    http://www.gnu.org/licenses/     GNU General Public License
 */

/**
 * A configuration controller which replaces 'attributeExp' nodes with their expanded form.
 * Created by CWE on 03 March 2014
 */
class AttributeExpConfigController extends ConfigControllerWithFuncExpVM
{
	private $_debugLogger;
	private $attributeExpNodes;

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
		unset($this->attributeExpNodes);
		parent::freeMemory();
	}

	// Dependency injection

	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("AttributeExpConfigController");
		}
		return $this->_debugLogger;
	}

	private $dflowS;
	/**
	 * Injects a DataFlowService to be used by this config controller
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

	// ConfigControllerWithFuncExpVM implementation

	public function processConfigurationNode($principal, $xmlConfig, $getWritableNode, $lp) {
		// gets all attributeExp nodes
		$this->attributeExpNodes = $xmlConfig->xpath('//attributeExp');
		if(!empty($this->attributeExpNodes)) {
			// runs again the xpath on a writable node
			$this->attributeExpNodes = $getWritableNode->invoke()->xpath('//attributeExp');
			// processes the attribute exp nodes
			return parent::processConfigurationNode($principal, $xmlConfig, $getWritableNode, $lp);
		}
		else return false;
	}

	protected function doProcessConfigurationNode($principal, $xmlConfig, $getWritableNode, $lp) {
		$updatedXml = false;
		if(!empty($this->attributeExpNodes)) {
			$this->debugLogger()->logBeginOperation('doProcessConfigurationNode');
			foreach($this->attributeExpNodes as $attributeExp) {
				//$this->debugLogger()->write($attributeExp->asXml());
				//if($this->debugLogger()->isEnabled()) fput($attributeExp->asXml());

				// gets func exp
				$funcExp = (string)$attributeExp['funcExp'];
				//gets session cache level
				$cacheLevel = (string)$attributeExp['cacheLevel'];
				switch($cacheLevel) {
					case 'none': $cacheLevel = self::CACHE_LEVEL_NONE; break;
					case 'navigate': $cacheLevel = self::CACHE_LEVEL_NAVIGATE; break;
					case 'session': $cacheLevel = self::CACHE_LEVEL_SESSION; break;
					case 'public': $cacheLevel = self::CACHE_LEVEL_PUBLIC; break;
					//default: $cacheLevel = self::CACHE_LEVEL_SESSION;
					default: $cacheLevel = self::CACHE_LEVEL_PUBLIC;
				}
				// evaluates func exp
				if(!empty($funcExp)) {
					//$this->debugLogger()->write($funcExp);
					$funcExp = str2fx($funcExp);
					// looks in cache if we have already something
					$cacheKey = md5(TechnicalServiceProvider::getFieldSelectorFuncExpParser()->funcExpToString($funcExp));
					$result = $this->getCachedAttributes($cacheKey, $cacheLevel);
					// else evaluates the func exp
					if(!isset($result)) {
						$cacheResult = true;
						$result = $this->evaluateFuncExp($funcExp);
					}
					else $cacheResult = false;

				}
				else $result = null;
				// if result is a DataFlowSelector, then executes the data flow.
				if($result instanceof DataFlowSelector) {
					// ensures that CfgAttribut2XmlDFA exists at the end.
					$result->getDataFlowActivitySelectorList()->addDataFlowActivitySelector('CfgAttribut2XmlDFA');
					// executes the flow
					$result = $this->getDataFlowService()->processDataFlowSelector($principal, $result);
				}
				// else if result is a SimpleXmlElement instance then stores it
				elseif($result instanceof SimpleXMLElement) {
					/* nothing todo */
				}
				// else if result is a String, then assumes it is xml and parses it into a SimpleXmlElement
				elseif(is_string($result)) {
					$result = simplexml_load_string($result);
				}
				// else returns without any modifications
				else $result = null;

				// replaces the attributeExp node with the result if set.
				if(isset($result)) {
					// puts result in cache
					if($cacheResult) $this->cacheAttributes($cacheKey, $result, $cacheLevel);
					// updates parent xml
					simplexml_replaceNodeWithChildren($attributeExp, $result);
					$updatedXml = true;
				}
			}
			$this->debugLogger()->logEndOperation('doProcessConfigurationNode');
			//if($this->debugLogger()->isEnabled()) fput('endOperation doProcessConfigurationNode');
		}
		return $updatedXml;
	}

	// Config cache management

	/**
	 * Returns the cached attributes or null if not in cache
	 * @param String $key the cache key
	 * @param int $cacheLevel one of CACHE_LEVEL_{NONE, NAVIGATE, SESSION or PUBLIC}
	 * @return SimpleXMLElement
	 */
	protected function getCachedAttributes($key, $cacheLevel=self::CACHE_LEVEL_NONE) {
		$returnValue = $this->configCache[$key];
		// looks in session if cache level > none
		if(!isset($returnValue) && $cacheLevel > self::CACHE_LEVEL_NONE) {
			$returnValue = $this->getSessionAdminService()->getData($this, $key, ($cacheLevel == self::CACHE_LEVEL_PUBLIC));
			// puts found value in execution cache
			if(!empty($returnValue)) {
				// parses xml string to SimpleXmlElement
				$returnValue = simplexml_load_string($returnValue);
				$this->configCache[$key] = $returnValue;
			}
			else $returnValue = null;
		}
		return $returnValue;
	}
	/**
	 * Caches attributes
	 * @param String $key the cache key
	 * @param SimpleXMLElement $attributes the attributes xml node
	 * @param int $cacheLevel one of CACHE_LEVEL_{NONE, NAVIGATE, SESSION or PUBLIC}
	 */
	protected function cacheAttributes($key, $attributes, $cacheLevel=self::CACHE_LEVEL_NONE) {
		$this->configCache[$key] = $attributes;
		// stores value in session if cache level > none
		if($cacheLevel > self::CACHE_LEVEL_NONE) {
			// serializes SimpleXmlElement
			if(isset($attributes)) $attributes = $attributes->asXML();
			else $attributes = '';
			$this->getSessionAdminService()->storeData($this, $key, $attributes, ($cacheLevel < self::CACHE_LEVEL_SESSION), ($cacheLevel == self::CACHE_LEVEL_PUBLIC));
		}
	}
}