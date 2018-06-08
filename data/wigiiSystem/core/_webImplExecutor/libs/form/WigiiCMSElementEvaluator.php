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
 *  @copyright  Copyright (c) 2016-2018  Wigii.org
 *  @author     <http://www.wigii.org/system>      Wigii.org
 *  @link       <http://www.wigii-system.net>      <https://github.com/wigii/wigii>   Source Code
 *  @license    <http://www.gnu.org/licenses/>     GNU General Public License
 */
/**
 * Wigii CMS module Element evaluator
 * Created by Weber wwigii-system.net for Wigii.org on 15.08.2016
 * Updated by Wigii.org (Lionel Weber) on 05.10.2016
 * Updated by Weber wwigii-system.net for Wigii.org on 15.11.2016
 * Updated by Wigii.org (Camille Weber) on 15.01.2017 to allow publication of html files through the link elementId.html
 * Updated by Wigii.org (Camille Weber) on 03.04.2017 to personalize site META information
 * Updated by Wigii.org (Camille Weber) on 15.06.2017 to manage internal url forwarding
 * Updated by Wigii.org (Lionel and Camille Weber) on 27.09.2017 to add public comment management
 * Updated by Weber wwigii-system.net for Wigii.org on 29.11.2017 to enforce the support of javascript into the page rendering process.
 * Updated by Weber wwigii-system.net for Wigii.org on 22.01.2018 to support NCD based articles.
 * Updated by Wigii.org (Camille Weber) on 05.03.2018 to move it to standard Wigii distribution
 */
class WigiiCMSElementEvaluator extends ElementEvaluator
{
	private $_debugLogger;
	private $_executionSink;
	private $siteMap;
	private $forwardMap;
	private $jsCode;
	
	// Dependency injection
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("WigiiCMSElementEvaluator");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("WigiiCMSElementEvaluator");
		}
		return $this->_executionSink;
	}
	
	protected function createTrmInstance($isOutputEnabled = true){
		$formExecutor = $this->getFormExecutor();
		$returnValue = $formExecutor->getWigiiExecutor()->createTRM(null, $formExecutor->isForNotification(), $formExecutor->isForPrint(), $formExecutor->isForExternalAccess(), false, false, $isOutputEnabled);
		$returnValue->setFormExecutor($formExecutor);
		return $returnValue;
	}
		
	// Content authoring and publishing
	
	/**
	 *@return String returns some JS code to be executed when Authoring form is loaded
	 */
	public function cms_authoringOnLoadJS($args) {
		return '(function(){
	var displayForm = function() {
		$("#$$idForm$$__groupSiteMap, #$$idForm$$__groupIntro, #$$idForm$$__groupLogo, #$$idForm$$__groupMenu, #$$idForm$$__groupNCD, #$$idForm$$__groupContent, #$$idForm$$__groupImage, #$$idForm$$__groupFooter, #$$idForm$$__groupCSS, #$$idForm$$__groupJS, #$$idForm$$__groupForward").hide();
		if($("#$$idForm$$_contentType_value_select").val()=="none") $("#$$idForm$$_contentType_value_select").val("content");
		switch($("#$$idForm$$_contentType_value_select").val()) {
		case "content": $("#$$idForm$$__groupContent, #$$idForm$$__contentHTML").show();break;
		case "ncd": $("#$$idForm$$__groupContent, #$$idForm$$__groupNCD").show();$("#$$idForm$$__contentHTML").hide();break;
		case "siteMap": $("#$$idForm$$__groupSiteMap").show();break;
		case "intro": $("#$$idForm$$__groupIntro").show();break;
		case "logo": $("#$$idForm$$__groupLogo").show();break;
		case "menu": $("#$$idForm$$__groupMenu").show();break;
		case "image": $("#$$idForm$$__groupImage").show();break;
		case "footer": $("#$$idForm$$__groupFooter").show();break;
		case "css": $("#$$idForm$$__groupCSS").show();break;
		case "js": $("#$$idForm$$__groupJS").show();break;
		case "forward": $("#$$idForm$$__groupForward").show();break;
		}
	};
	$("#$$idForm$$_contentType_value_select").change(displayForm);
	displayForm();
})();';
	}
	
	/**
	 *@return String returns some JS code which updates the ChoosePosition drop-down with the current articles
	 */
	public function cms_authoringLoadChoosePosition($args) {
		// gets content folder ID
		$groupId = $this->evaluateFuncExp(fx('cfgCurrentGroup','id'),$this);
		// gets the ChoosePosition drop-down as html
		$choosePosition = sel($this->getPrincipal(), elementPList(lxInG(lxEq(fs('id'),$groupId)),
				lf(
						fsl(fs('contentPosition'),fs('contentSummary'),fs('contentNextId')),
						lxIn(fs('contentType'),array('content','ncd')),
						fskl(fsk('contentPosition','value'))
						)),
				dfasl(
						/* add missing links to next element */
						dfas('CallbackDFA','setProcessDataChunkCallback',function($data,$callbackDFA){
							$currentElement = $data->getDbEntity();
							$previousElement = $callbackDFA->getValueInContext('previousElement');
							// stores first element
							if(!isset($previousElement)) {
								$callbackDFA->setValueInContext('previousElement',$currentElement);
							}
							else {
								$previousElement->setDynamicAttribute('contentNextIdChanged',ElementDynAttrMutableValueImpl::createInstance(false));
								// else if previous element does not link to current element, then corrects contentNextId field
								if($previousElement->getFieldValue('contentNextId') != $currentElement->getId()) {
									$previousElement->setFieldValue($currentElement->getId(), 'contentNextId');
									// marks element as changed to make it persisted.
									$previousElement->setDynamicAttributeValue('contentNextIdChanged',true);
									// if previousElement equals current element in Form, then updates contentNextId in Form to allow drop-down sync
									if($previousElement->getId() == $this->getElement()->getId()) {
										$this->getElement()->setFieldValue($previousElement->getFieldValue('contentNextId'),'contentNextId');
									}
								}
								// stores currentElement as new previousElement
								$callbackDFA->setValueInContext('previousElement',$currentElement);
								// pushes previousElement down in stream
								$callbackDFA->writeResultToOutput($previousElement);
							}
						},
						'setEndOfStreamCallback',function($callbackDFA){
						// updates last element if needed
						$previousElement = $callbackDFA->getValueInContext('previousElement');
						if(isset($previousElement)) {
							$previousElement->setDynamicAttribute('contentNextIdChanged',ElementDynAttrMutableValueImpl::createInstance(false));
							if($previousElement->getFieldValue('contentNextId') != 'last') {
								$previousElement->setFieldValue('last', 'contentNextId');
								// marks element as changed to make it persisted.
								$previousElement->setDynamicAttributeValue('contentNextIdChanged',true);
								// if previousElement equals current element in Form, then updates contentNextId in Form to allow drop-down sync
								if($previousElement->getId() == $this->getElement()->getId()) {
									$this->getElement()->setFieldValue($previousElement->getFieldValue('contentNextId'),'contentNextId');
								}
							}
							// pushes previousElement down in stream
							$callbackDFA->writeResultToOutput($previousElement);
						}
						}),
						/* persists elements which have missing link added */
						dfas('ElementDFA','setFieldSelectorList',fsl(fs('contentNextId')),'setMode',3,'setDecisionMethod',function($element,$dataFlowContext){
							if($element->getDynamicAttributeValue('contentNextIdChanged')) return 1;
							else return 5;
						}),
						/* creates drop-down (filters current element) */
						dfas('MapElement2ValueDFA','setElement2ValueFuncExp',fx('ctlIf',fx('neq',fs_e('id'),$this->getElement()->getId()),fx('concat',fx('htmlStartTag','option','value',fs_e('id')),fx('str_replace',"'","\\'",fs('contentSummary')),fx('htmlEndTag','option')))),
						dfas('StringBufferDFA')
						)
				);
		// Builds next article drop-down and syncs with current next ID
		if(isset($choosePosition)) {
			$nextId = $this->getElement()->getFieldValue('contentNextId');
			return '(function(){$("#$$idForm$$_choosePosition_value_select").append('."'".$choosePosition."'".')'.($nextId?".val('$nextId')":'').';})();';
		}
	}
	
	/**
	 * Called when saving some Content to CMS
	 */
	public function cms_authoringOnSave($args) {
		// ignore if multiple-edit
		if($this->getCurrentFlowName() == 'multiple-edit') return;
		
		$contentType = $this->getElement()->getFieldValue('contentType');
		// clears unwanted fields
		$this->clearUnwantedFields($this->getFslForContentType($contentType));
		// specific authoring process depending on content type
		switch($contentType) {
			case "content": 
			case "ncd":	
				$this->cms_authoringOnSaveContent(); break;
			case "siteMap": $this->cms_auhoringOnSaveSiteMap(); break;
			case "forward": $this->cms_auhoringOnSaveForward(); break;
		}
	}
	
	/**
	 * Summarizes article content or title into a single line of non html text<br/>
	 * FuncExp signature is: <code>cms_summarize(txt)</code><br/>
	 * Where arguments are :
	 * - Arg(0) txt: String. The html string to summarize
	 * @return String. The summarized string
	 */
	public function cms_summarize($args) {
		$nArgs = $this->getNumberOfArgs($args);
		$returnValue = '';
		if($nArgs>0) {
			$txt = $this->evaluateArg($args[0]);
			$html2text = new Html2text();
			if(is_array($txt)) {
				foreach($txt as $t) {
					$html2text->setHtml($t);
					$returnValue .= $html2text->getText();
					$returnValue .= ' ';
				}
			}
			else {
				$html2text->setHtml($txt);
				$returnValue = $html2text->getText();
			}
			unset($html2text);
			$returnValue = substr($returnValue,0,255);
		}
		return $returnValue;
	}
	
	/**
	 * Specific authoring process when saving content
	 * - calculates the position of the article
	 */
	protected function cms_authoringOnSaveContent() {
		$content = $this->getElement();
		if($content->isNew()) $currentId = 'new';
		else $currentId = $content->getId();
		
		// gets next article position
		$nextId = $content->getFieldValue('choosePosition');
		if($nextId!='last') {
			$nextPos = sel($this->getPrincipal(),elementP($nextId,fsl(fs('contentPosition'))),
					dfasl(dfas('MapElement2ValueDFA','setElement2ValueFuncExp',fs('contentPosition')))
					);
		}
		else $nextPos = 10000;
		
		// calculates the position if not already done or if next ID changed
		if($content->getFieldValue('contentPosition') == null || $content->getFieldValue('contentNextId') != $nextId) {
			// gets content folder ID
			$groupId = $this->evaluateFuncExp(fx('cfgCurrentGroup','id'),$this);
			
			// retrieves previous article
			$prevPos = sel($this->getPrincipal(), elementPList(lxInG(lxEq(fs('id'),$groupId)),
					lf(
							fsl(fs('contentPosition'),fs('contentNextId')),
							lxAnd(lxIn(fs('contentType'),array('content','ncd')),lxSm(fs('contentPosition'),$nextPos)),
							fskl(fsk('contentPosition','value',false)),
							1,1
							)), dfasl(
									/* updates contentNextId link to current article */
									dfas('ElementSetterDFA','setCalculatedFieldSelectorMap',cfsMap(cfs('contentNextId',$currentId))),
									dfas('ElementDFA','setFieldSelectorList',fsl(fs('contentNextId')),'setMode','1'),
									/* and returns position */
									dfas('MapElement2ValueDFA','setElement2ValueFuncExp',fs('contentPosition'))
									)
					);
			
			// calculates article position (compacts at the end to let more space at the beginning)
			$content->setFieldValue(0.25*$prevPos+0.75*$nextPos, 'contentPosition');
			// stores next ID
			$content->setFieldValue($nextId, 'contentNextId');
			// resets choosePosition option
			$content->setFieldValue('last', 'choosePosition');
		}
	}
	/**
	 * Specific authoring process when saving site map
	 */
	protected function cms_auhoringOnSaveSiteMap() {
		$siteMap = $this->getElement();
		// always ensures that URL ends with a slash
		$url = $siteMap->getFieldValue('siteUrl');
		$url = rtrim($url, "\n\t /")."/";
		$siteMap->setFieldValue($url,'siteUrl');
	}
	/**
	 * Specific authoring process when saving forward
	 */
	protected function cms_auhoringOnSaveForward() {
		$forward = $this->getElement();
		// always ensures that URL ends with a slash
		$url = $forward->getFieldValue('fromUrl');
		$url = rtrim($url, "\n\t /")."/";
		$forward->setFieldValue($url,'fromUrl');
	}
	
	// Content rendering
	
	/**
	 * Main entry point to process the incoming CMS URL<br/>
	 * FuncExp signature is: <code>cms_processUrl(params)</code><br/>
	 * Where arguments are :
	 * - Arg(0) params: WigiiBPLParameter. A map of key values specifying the request and its environment
	 *   parsedUrl: Array. The url parsed into arguments by the ExecutionService
	 *   isIntegrated: Boolean. True if the answer is integrated into a Wigii protocol answer, false if http header should be sent.
	 * @return mixed. Can echo directly the output or return an object which will be serialized by the WigiiWebCMSFormExecutor.
	 */
	public function cms_processUrl($args) {
		$nArgs = $this->getNumberOfArgs($args);
		$returnValue = null;
		$principal = $this->getPrincipal();
		$this->executionSink()->publishStartOperation("cms_processUrl", $principal);
		try {
			// extracts parameters
			if($nArgs<1) throw new FuncExpEvalException('cms_processUrl takes at least one argument which is a WigiiBPLParameter object containing the parsedUrl array', FuncExpEvalException::INVALID_ARGUMENT);
			$params = $this->evaluateArg($args[0]);
			if(!($params instanceof WigiiBPLParameter)) throw new FuncExpEvalException('cms_processUrl takes at least one argument which is a WigiiBPLParameter object containing the parsedUrl array', FuncExpEvalException::INVALID_ARGUMENT);
			
			// 1. if POST verb, forwards request to appropriate method
			if($_SERVER['REQUEST_METHOD']=='POST') $returnValue = $this->cms_processPost($params);
			
			// 2. else treats it a standard content rendering
			else {
				// extracts parsedUrl
				$parsedUrl = $params->getValue('parsedUrl');
				
				// extracts folder path and file name
				if(is_array($parsedUrl)) {
					$fileName = array_pop($parsedUrl);
					$folderPath = implode('/', $parsedUrl);
					if($folderPath) $folderPath = '/'.$folderPath.'/';
					elseif($fileName) $folderPath = '/';
				}
				else {
					$folderPath = '/';
					$fileName = $parsedUrl;
				}
				$ext = strrpos($fileName,'.');
				// file identified
				if($ext!==false) {
					$fileExt = substr($fileName,$ext);
					$fileName = substr($fileName,0,$ext);
					$params->setValue('fileName',$fileName);
					$params->setValue('fileExt',$fileExt);
					$returnValue = $this->evaluateFuncExp(fx('cms_getFile',array($folderPath.$fileName.$fileExt,$params)),$this);
				}
				// else folder identified
				else {
					// extracts language
					$languages = ServiceProvider::getTranslationService()->getVisibleLanguage();
					// if last parameter is a valid language then extracts it
					if($languages[$fileName]) {
						$params->setValue('language', $fileName);
					}
					// else keeps complete folder path
					else $folderPath .= $fileName.'/';
					
					$returnValue = $this->evaluateFuncExp(fx('cms_getContent',array($folderPath,$params)),$this);
				}
			}
		}
		catch(Exception $e) {
			$this->executionSink()->publishEndOperationOnError("cms_processUrl", $e, $principal);
			throw $e;
		}
		$this->executionSink()->publishEndOperation("cms_processUrl", $principal);
		return $returnValue;
	}
	/**
	 * Returns the group ID storing the content behind the given url or null if not found<br/>
	 * FuncExp signature is: <code>cms_getGroupIdForUrl(url)</code><br/>
	 * Where arguments are :
	 * - Arg(0) url: String. A logical URL pointing to an existing Site Map
	 * @return Int. Group ID if found, else null
	 */
	public function cms_getGroupIdForUrl($args) {
		$nArgs = $this->getNumberOfArgs($args);
		$returnValue = null;
		$principal = $this->getPrincipal();
		$this->executionSink()->publishStartOperation("cms_getGroupIdForUrl", $principal);
		try {
			// extracts parameters
			if($nArgs<1) throw new FuncExpEvalException('cms_getGroupIdForUrl takes at least one argument which is the logical URL of the content', FuncExpEvalException::INVALID_ARGUMENT);
			$url = $this->evaluateArg($args[0]);
			if(empty($url)) throw new FuncExpEvalException('cms_getGroupIdForUrl takes at least one argument which is the logical URL of the content', FuncExpEvalException::INVALID_ARGUMENT);
			
			$sessionAS = ServiceProvider::getSessionAdminService();
			$siteMapKey = 'siteMap_'.$principal->getWigiiNamespace()->getWigiiNamespaceUrl();
			// loads siteMap from Session if not yet loaded
			if(!isset($this->siteMap)) {
				$this->siteMap = $sessionAS->getData($this,$siteMapKey);
				if(!isset($this->siteMap)) $this->siteMap = array();
			}
			// lookups groupId from siteMap
			$returnValue = $this->siteMap[$url];
			// if not found, loads SiteMap for current WigiiNamespace
			if(!$returnValue) {
				$this->siteMap = sel($principal,elementPList(lxInGR($this->getSiteMapLx($principal)),
						lf(fsl(fs('siteUrl'),fs('folderId')), lxAnd(lxEq(fs('contentType'),'siteMap'),lxEq(fs('status'),'published')))),
						dfasl(dfas('ArrayBufferDFA','setUnpair', true, 'setKeyField','siteUrl','setValueField','folderId'))
						);
				// stores siteMap into session
				if($this->siteMap) {
					$sessionAS->storeData($this,$siteMapKey,$this->siteMap);
					// lookups groupId from siteMap
					$returnValue = $this->siteMap[$url];
				}
				else $returnValue = null;
			}
		}
		catch(Exception $e) {
			$this->executionSink()->publishEndOperationOnError("cms_getGroupIdForUrl", $e, $principal);
			throw $e;
		}
		$this->executionSink()->publishEndOperation("cms_getGroupIdForUrl", $principal);
		return $returnValue;
	}
	/**
	 * Returns the internal relative URL where to forward the current request<br/>
	 * FuncExp signature is: <code>cms_getForwardUrlForUrl(url)</code><br/>
	 * Where arguments are :
	 * - Arg(0) url: String. A logical URL for which a forward url is defined
	 * @return String. The relative URL to forward to if found, else null
	 */
	public function cms_getForwardUrlForUrl($args) {
		$nArgs = $this->getNumberOfArgs($args);
		$returnValue = null;
		$principal = $this->getPrincipal();
		$this->executionSink()->publishStartOperation("cms_getForwardUrlForUrl", $principal);
		try {
			// extracts parameters
			if($nArgs<1) throw new FuncExpEvalException('cms_getForwardUrlForUrl takes at least one argument which is the logical URL for which to look for a forward', FuncExpEvalException::INVALID_ARGUMENT);
			$url = $this->evaluateArg($args[0]);
			if(empty($url)) throw new FuncExpEvalException('cms_getForwardUrlForUrl takes at least one argument which is the logical URL for which to look for a forward', FuncExpEvalException::INVALID_ARGUMENT);
			
			$sessionAS = ServiceProvider::getSessionAdminService();
			$forwardMapKey = 'forwardMap_'.$principal->getWigiiNamespace()->getWigiiNamespaceUrl();
			// loads forwardMap from Session if not yet loaded
			if(!isset($this->forwardMap)) {
				$this->forwardMap = $sessionAS->getData($this,$forwardMapKey);
				if(!isset($this->forwardMap)) $this->forwardMap = array();
			}
			// lookups forward Url from forwardMap
			$returnValue = $this->forwardMap[$url];
			// if not found, loads forwardMap for current WigiiNamespace
			if(!$returnValue) {
				$this->forwardMap = sel($principal,elementPList(lxInGR($this->getSiteMapLx($principal)),
						lf(fsl(fs('fromUrl'),fs('toUrl')), lxAnd(lxEq(fs('contentType'),'forward'),lxEq(fs('status'),'published')))),
						dfasl(dfas('ArrayBufferDFA','setUnpair', true, 'setKeyField','fromUrl','setValueField','toUrl'))
						);
				// stores forwardMap into session
				if($this->forwardMap) {
					$sessionAS->storeData($this,$forwardMapKey,$this->forwardMap);
					// lookups forward Url from forwardMap
					$returnValue = $this->forwardMap[$url];
				}
				else $returnValue = null;
			}
		}
		catch(Exception $e) {
			$this->executionSink()->publishEndOperationOnError("cms_getForwardUrlForUrl", $e, $principal);
			throw $e;
		}
		$this->executionSink()->publishEndOperation("cms_getForwardUrlForUrl", $principal);
		return $returnValue;
	}
	/**
	 * Gets the content associated to the given URL<br/>
	 * FuncExp signature is: <code>cms_getContent(url,options)</code><br/>
	 * Where arguments are :
	 * - Arg(0) url: String. A logical URL pointing to an existing Site Map
	 * - Arg(1) options: WigiiBPLParameter. An optional map of options used to parametrize the rendering process.
	 * @return mixed. Or content is directly echoed to output stream or returned as a serializable object
	 */
	public function cms_getContent($args) {
		$nArgs = $this->getNumberOfArgs($args);
		$returnValue = null;
		$principal = $this->getPrincipal();
		$this->executionSink()->publishStartOperation("cms_getContent", $principal);
		try {
			// extracts parameters
			if($nArgs<1) throw new FuncExpEvalException('cms_getContent takes at least one argument which is the logical URL of the content', FuncExpEvalException::INVALID_ARGUMENT);
			$url = $this->evaluateArg($args[0]);
			if(empty($url)) throw new FuncExpEvalException('cms_getContent takes at least one argument which is the logical URL of the content', FuncExpEvalException::INVALID_ARGUMENT);
			// gets options
			if($nArgs>1) $options = $this->evaluateArg($args[1]);
			if(!($options instanceof WigiiBPLParameter)) $options = wigiiBPLParam();
			$options->setValue('url',$url);
			
			// lookups groupId associated to url
			$groupId = $this->evaluateFuncExp(fx('cms_getGroupIdForUrl',$url),$this);
			// if url is not mapped to any groupId, then looks for a forward url
			if(empty($groupId)) {
				$forwardUrl = $this->evaluateFuncExp(fx('cms_getForwardUrlForUrl',$url),$this);
				if(empty($forwardUrl)) throw new FuncExpEvalException("No content found at $url", FuncExpEvalException::NOT_FOUND);
				// internally forwards request to new url
				else {
					$this->executionSink()->publishEndOperation("cms_getContent", $principal);
					$parsedUrl = array();
					$forwardUrl = explode("/",$forwardUrl);
					foreach($forwardUrl as $url) {
						if($url) $parsedUrl[] = $url;
					}
					$options->setValue('parsedUrl',$parsedUrl);
					return $this->evaluateFuncExp(fx('cms_processUrl',$options),$this);
				}
			}
			else $options->setValue('groupId',$groupId);
			
			// gets site map
			$siteMap = $this->cms_getSiteMap($options);
			if(!isset($siteMap)) throw new FuncExpEvalException("No content found at $url", FuncExpEvalException::NOT_FOUND);
			
			// gets languages
			$transS = ServiceProvider::getTranslationService();
			$languages = $siteMap->getFieldValue('supportedLanguage');
			if(empty($languages)) $languages = $transS->getVisibleLanguage();
			elseif(is_array($languages)) $languages = array_intersect_key($transS->getVisibleLanguage(), $languages);
			else $languages = array($languages=>$languages);
			$options->setValue('languages',$languages);
			
			// gets default language
			$language = $options->getValue('language');
			if(!isset($language)) {
				$language = $siteMap->getFieldValue('defaultLanguage');
				if(!isset($language)) $language='l01';
				$options->setValue('language',$language);
			}
			
			// gets page title and intro
			$intro = $this->cms_getIntro($options);
			
			// maps page options
			$this->cms_initializePageOptions($options, $intro, $siteMap);
			
			// gets page Logo
			$logo = $this->cms_getLogo($options);
			
			// gets page Menu
			$menu = $this->cms_getMenu($options);
			
			// gets CSS definitions
			$css = $this->cms_getCSS($options);

			// gets JS code
			$js = $this->cms_getJSCode($options);
			
			//gets page footer
			$footer = $this->cms_getFooter($options);
			// top link
			$atopLink = '<a href="./'.$language.'#top">▲ '.$transS->t($principal,"cmsAnchorTop",null,$language).'</a>';
			$options->setValue('atopLink', $atopLink);
			
			// renders header
			echo $this->cms_composeHeader($options,$css,$logo,$menu,$intro)."\n";
			
			// renders article content
			sel($principal,elementPList(lxInG(lxEq(fs('id'),$groupId)),
				lf(
					fsl(fs('contentType'),fs('contentTitle'),fs('contentHTML'),fs('contentNCD'),fs('articleBgColor'),fs('articleBgAlpha'),fs('imgArticleBG','url')),
					lxAnd(lxIn(fs('contentType'),array('content','ncd')),lxEq(fs('status'),'published')),
					fskl(fsk('contentPosition'))
				)),
				dfasl(
					/* dispatches the composition of the article based on its contentType */
					dfas('CallbackDFA','setProcessDataChunkCallback',function($article,$callbackDFA) use($options){
						$article = $article->getDbEntity();
						$articleType = $article->getFieldValue('contentType');
						try {
							switch($articleType) {
								case 'content': $this->cms_composeArticle($article, $options, fx('oCall',$this,'cms_getArticleHtmlContent',$article,$options),$callbackDFA); break;
								case 'ncd': $this->cms_composeArticle($article, $options, fx('oCall',$this,'cms_getArticleNcdContent',$article,$options),$callbackDFA); break;
							}
						}
						catch(Exception $e) {
							$this->publishException($e);
						}
					}),							
					dfas('StringSepDFA', 'setSeparator',"\n"),
					dfas('EchoDFA')
				)
			);
			
			// renders footer
			echo "\n".$this->cms_composeFooter($options,$footer,$js);
		}
		catch(Exception $e) {
			$this->executionSink()->publishEndOperationOnError("cms_getContent", $e, $principal);
			throw $e;
		}
		$this->executionSink()->publishEndOperation("cms_getContent", $principal);
		return $returnValue;
	}
	/**
	 * Pushes some JS code to the browser.
	 * This method can be called from anywhere.
	 * @param String $code some valid js code
	 */
	protected function addJsCode($code) {
		if(!empty($code)) $this->jsCode .= $code.'; ';
	}
	/**
	 * @return String returns the content of the JS queue
	 */
	protected function getJsCode() {
		return $this->jsCode;
	}
	/**
	 * Publishes an exception as HTML at the current position in the html stream
	 * @param Exception $e
	 */
	protected function publishException($e) {
		if($e instanceof ServiceException) $e = $e->getWigiiRootException();
		if(isset($e)) {
			echo '<p style="color:red;font-weight:bold;">'.$e->getCode().' '.$e->getMessage().'</p>';
		}
	}
	/**
	 * Renders the article structure
	 * @param Element $article article of any type
	 * @param WigiiBPLParameter $options the current set of page options
	 * @param FuncExp $fxHtmlContent FuncExp returning the article content as HTML or sending some JS code using the addJsCode method.
	 * @param CallbackDFA $callbackDFA current data flow activity and context
	 */
	public function cms_composeArticle($article,$options,$fxHtmlContent,$callbackDFA) {
		$transS = ServiceProvider::getTranslationService();
		$language = $options->getValue('language');
		$languages = $options->getValue('languages');
		$atopLink = $options->getValue('atopLink');
		// uses an Fx to transform the article element to HTML, but could use another piece of code.
		$returnValue = $this->evaluateFuncExp(fx('element2value',$article,
			fx('concat',
				fx('htmlStartTag','div','class','wigii-cms','style',fx('oCall',$this,'cms_getArticleStyle',fs_e('this'))),"\n",
				fx('htmlStartTag','div','class','wigii-cms title', 'id', fs_e('id')),
				fx('htmlStartTag','div', 'class', 'wigii-cms title-content'),
				fx('first',fx('getAttr',fs('contentTitle'),$language),
						fx('concat',fx('htmlStartTag', 'p'), $transS->t($principal,"cmsNoTitleAvailable",null,$language).$languages[$language],' ',
								fx('htmlStartTag','a','target','_blank','href', fx('concat',fx('sysSiteRootUrl'),'#',fx('sysCrtWigiiNamespace'),'/',fx('sysCrtModule'),'/item/',fs_e('id'))), '(#',fs_e('id'),')',fx('htmlEndTag','a'),
								fx('htmlEndTag', 'p'))
						),
				fx('htmlEndTag','div'),
				fx('htmlStartTag','div','class', 'wigii-cms a-top'),$atopLink,fx('htmlEndTag','div'),
				fx('htmlEndTag','div'),"\n",
				fx('htmlStartTag','div','class','wigii-cms content'),fx('first',$fxHtmlContent,
						fx('concat',fx('htmlStartTag', 'p'),$transS->t($principal,"cmsNoContentAvailable",null,$language).$languages[$language],' ',
								fx('htmlStartTag','a','target','_blank','href', fx('concat',fx('sysSiteRootUrl'),'#',fx('sysCrtWigiiNamespace'),'/',fx('sysCrtModule'),'/item/',fs_e('id'))), '(#',fs_e('id'),')',fx('htmlEndTag','a'),
								fx('htmlEndTag', 'p'))
						),
				//fx('htmlStartTag','p','style','text-align:center;'),$this->cms_getArticleSep($options),fx('htmlEndTag','p'),
				fx('htmlEndTag','div'),"\n",
				fx('htmlEndTag','div'),"\n")
			)
		);
		$callbackDFA->writeResultToOutput($returnValue);
	}
	/**
	 * Gets the content of an HTML article<br/>
	 * @param Element $article A Wigii CMS element of type article content
	 * @param WigiiBPLParameter $options An optional map of options used to parametrize the rendering process.
	 * @return String HTML code to be added to article content div
	 */
	public function cms_getArticleHtmlContent($article,$options) {
		if(isset($article)) {
			$language = $options->getValue('language');			
			return $article->getFieldValue('contentHTML')[$language];
		}
	}
	/**
	 * Generates some javascript to run the given NCD article and pushes it to browser through the JS channel<br/>
	 * @param Element $article A Wigii CMS element of type article content
	 * @param WigiiBPLParameter $options An optional map of options used to parametrize the rendering process.
	 * @return String HTML code to be added to article content div
	 */
	public function cms_getArticleNcdContent($article,$options) {
		if(isset($article)) {
			$ncdCode = $article->getFieldValue('contentNCD');
			if(!empty($ncdCode)) {
				$this->addJsCode('$("#'.$article->getId().'").parent().find("div.wigii-cms.content").wncd("run").program('.$ncdCode.')');
				return ' ';/* returns a non null HTML code string */
			}
		}
	}	
	/**
	 * Initializes the bag of options with some key/value pairs found into the intro and site map elements
	 * @param WigiiBPLParameter $options the bag of options for the current page
	 * @param Element $intro the intro element
	 * @param Element $siteMap the site map element
	 * @return WigiiBPLParameter the updated bag of options
	 */
	protected function cms_initializePageOptions($options,$intro,$siteMap) {
		if(!isset($options)) throw new FuncExpEvalException('options must be a non null instance of WigiiBPLParameter', FuncExpEvalException::INVALID_ARGUMENT);
		// Extracts page options from introduction element
		if(isset($intro)) {
			$this->mapField2Option('siteTitle',$intro,$options,null,'title');
			$this->mapField2Option('metaDescription',$intro,$options);
			$this->mapField2Option('metaKeywords',$intro,$options);
			$this->mapField2Option('metaAuthor',$intro,$options);
			$this->mapField2Option('introBgColor',$intro,$options);
			$this->mapField2Option('introBgAlpha',$intro,$options);
			$this->mapField2Option(fs('imgIntroBG','url'),$intro,$options);			
		}
		// Extracts page options from site map element
		if(isset($siteMap)) {
			$this->mapField2Option('forceHeight',$siteMap,$options,false);
			$this->mapField2Option('forceHeightFirst',$siteMap,$options,true);
			$this->mapField2Option('marginWidth',$siteMap,$options,"11%");
			$this->mapField2Option('logoTextColor',$siteMap,$options,"666");
			$this->mapField2Option('logoTextSize',$siteMap,$options,"22px");
			$this->mapField2Option('menuBgColor',$siteMap,$options,"ccc");
			$this->mapField2Option('menuTextColor',$siteMap,$options,"fff");			
			$this->mapField2Option('titleTextColor',$siteMap,$options,"696969");
			$this->mapField2Option('menuTextHoverColor',$siteMap,$options,"5c523d");
			$this->mapField2Option('titleTextSize',$siteMap,$options,"24px");
			$this->mapField2Option('publicCommentsBgColor',$siteMap,$options,"ccc");
			$this->mapField2Option('publicCommentsTextColor',$siteMap,$options,"fff");
			$this->mapField2Option('footerBgColor',$siteMap,$options,"696969");
			$this->mapField2Option('footerTextColor',$siteMap,$options,"fff");
			$this->mapField2Option('linkTextColor',$siteMap,$options,"646eff");
			$this->mapField2Option('evenArticleBgColor',$siteMap,$options,"fff");
			$this->mapField2Option('oddArticleBgColor',$siteMap,$options,"ebecff");
		}
	}
	/**
	 * Generates a String used as an inline Style for the given article
	 *@param Element $article
	 *@return String inline Style for the article
	 */
	public function cms_getArticleStyle($article) {
		$returnValue = '';
		if(isset($article)) {
			// background-color
			$s = $article->getFieldValue('articleBgColor');
			if($s){
				//remove any # inside
				$s = str_replace("#","", $s);
				if(strlen($s)>3) list($r, $g, $b) = sscanf($s, "%02x%02x%02x");
				else list($r, $g, $b) = sscanf($s, "%1x%1x%1x");
				$returnValue .= 'background-color: rgba('.$r.', '.$g.', '.$b.', ';
				// opacity
				$s = $article->getFieldValue('articleBgAlpha');
				if($s) $returnValue .= $s;
				else $returnValue .= '1';
				$returnValue .= ');';
			}
			// background-image
			$s = $article->getFieldValue('imgArticleBG','url');
			if($s) $returnValue .= "background:url('".$s."') no-repeat center center; -webkit-background-size: cover; -moz-background-size: cover; -o-background-size: cover; background-size: cover;";
		}
		return $returnValue;
	}
	/**
	 * Composes the page header
	 * @param WigiiBPLParameter $options some rendering options
	 * @param String $css a custom CSS section
	 * @param String $logo the html to insert the logo
	 * @param String $menu the html of the menu
	 * @param Element $intro the introduction element
	 * @return String the complete HTML string of the header of the page
	 */
	protected function cms_composeHeader($options,$css,$logo,$menu,$intro) {
		$transS = ServiceProvider::getTranslationService();
		$principal = $this->getPrincipal();
		$language = $options->getValue('language');
		
		$style = '';
		// background-color
		$s = $options->getValue('introBgColor');
		if($s){
			$s = str_replace("#","", $s);
			if(strlen($s)>3) list($r, $g, $b) = sscanf($s, "%02x%02x%02x");
			else list($r, $g, $b) = sscanf($s, "%1x%1x%1x");
			$style .= 'background-color: rgba('.$r.', '.$g.', '.$b.', ';
			// opacity
			$s = $options->getValue('introBgAlpha');
			if($s) $style .= $s;
			else $style .= '1';
			$style .= ');';
		}
		// background-image
		$s = $options->getValue('imgIntroBG');
		if($s) $style .= "background:url('".$s."') no-repeat center center; -webkit-background-size: cover; -moz-background-size: cover; -o-background-size: cover; background-size: cover;";
		
		// stores css string into options
		if(!empty($css)) $options->setValue('css',$css);
		
		// Retrieves intro info
		$introContent = $intro->getFieldValue('contentIntro');
		if(is_array($introContent)) $introContent = $introContent[$language];
		$enablePublicComments = $intro->getFieldValue('enablePublicComments');
		$introComments = $intro->getFieldValue('introComments');
		// saves some options used into the js part
		if($enablePublicComments) {
			$options->setValue('enablePublicComments',true);
			$options->setValue('introElementId',$intro->getId());
		}
		
		return $this->cms_getHtmlHeader($options)."\n<body>".
				'<div class="wigii-globalContainer">'."\n".
				(empty($logo)&&empty($menu)?' ':'<div class="wigii-menu">').
				(empty($logo)?' ':'<div id="wigii-logo">'.$logo.'</div>').
				(empty($menu)?' ':$menu).
				(empty($logo)&&empty($menu)?' ':'</div>').
				'<!-- top anchor -->'.
				'<div id="top" ></div><div style="clear:both;"></div>'.
				($enablePublicComments ?
						'<div class="wigii-cms-public-comments"><div class="wigii-cms-public-comments-title">'.$transS->t($principal,"cmsPublicComments",null,$language).'</div><div class="wigii-cms-public-comments-hr"></div><div class="wigii-cms-add-public-comments">'.$transS->t($principal,"addJournalItem",null,$language).'</div><div id="wigii-cms-public-comments-content">'.$introComments.'</div></div>'
						: '').
						'<div class="wigii-cms" style="'.$style.'">'."\n".
						'<div class="wigii-cms title"><div class="wigii-cms title-content"> </div><div class="wigii-cms a-top">'.$this->cms_getLanguageMenu($options).'</div></div>'."\n".
						'<div class="wigii-cms content">'.(empty($introContent)?' ':$introContent).'</div>'."\n".
						'</div>';
	}
	/**
	 * Composes the page footer
	 * @param WigiiBPLParameter $options some rendering options
	 * @param String $footer the html of the footer
	 * @param String $js some custom js to send to browser
	 * @return String the html string of the page footer
	 */
	protected function cms_composeFooter($options,$footer,$js) {
		$transS = ServiceProvider::getTranslationService();
		$principal = $this->getPrincipal();
		$language = $options->getValue('language');
		
		// Variables
		$forceHeight = ($options->getValue("forceHeight") ? 'true' : 'false');
		$forceHeightFirst = ($options->getValue("forceHeightFirst") ? 'true' : 'false');
		$jsPublicComments = ($options->getValue("enablePublicComments")? $this->cms_getPublicCommentsJS($options):'');
		$jsQueue = $this->getJsCode();
		$footer = (!empty($footer)?'<div class="wigii-footer wigii-cms content">'.$footer.'</div>':'');
		$js = (!empty($js)?"<script>".$js."</script>":'');
		$ncdProgramOutput = '<div id="programOutput"></div>';
		
		// Creates footer
		$returnValue = <<<PAGEFOOTER
<script>
var enableArticleResize = $forceHeight;
var enableFirstArticleResize = $forceHeightFirst;
$(document).ready(function(){
	function resize(e){
		$("div#top").height($("div.wigii-menu").outerHeight());
		if(enableArticleResize) {  $("div.wigii-globalContainer>div.wigii-cms:not(.wigii-footer)").css("min-height",$(window).height()-$("div.wigii-menu").outerHeight()); }
		if(enableFirstArticleResize) {  $("div.wigii-globalContainer>div.wigii-cms:not(.wigii-footer):first").css("min-height",$(window).height()-$("div.wigii-menu").outerHeight()); }
		$("div.wigii-globalContainer>div.wigii-cms:not(.wigii-footer):last").css("min-height",$(window).height()-$("div.wigii-menu").outerHeight()-$("div.wigii-footer").outerHeight()-1);
		$("div.wigii-cms div.middle").each(function(){ $(this).css("margin-top", Math.max(0,($(window).height()-$("div.wigii-menu").outerHeight()-$(this).parent().outerHeight())/2)); });
		$("div.wigii-cms div.bottom").each(function(){ $(this).css("margin-top", Math.max(0,$(window).height()-$("div.wigii-menu").outerHeight()-$(this).parent().prev().outerHeight()-$(this).parent().outerHeight()-46)); });
		$("div.wigii-cms-public-comments").height($(window).height()-$("div.wigii-menu").outerHeight());
	}
	function scrollToHash(e){
		/* Make sure this.hash has a value before overriding default behavior */
		/* check if link refers to host with a . */
		var fromClick = arguments.length > 0;
		var sameLocation = false;
		var hash = "";
		var scrollTo = "";
		if(this.hash !== ""){
			/* check if link goes in a new location from the current location */
			var href = $(this).attr("href")+"";
			sameLocation = href.substr(0,1)=="." || href.substr(0,1)=="#" || href.split("#")[0]==(window.location+("")).split("#")[0];
		}
		if (!fromClick || sameLocation) {
			$("*").removeClass("over").find(".wigii-arrow").remove();
			if(fromClick){
				/* Prevent default anchor click behavior */
				e.preventDefault();
				hash = this.hash;
				if($(this).parents("#wigii-logo").length || $(this).parent().hasClass("a-top")){
					/* do nothing $(this).addClass("over").append('<span class="wigii-arrow"> ▲</span>'); */
				} else {
					$(this).addClass("over").append('<span class="wigii-arrow"> ▼</span>');
				}
			} else {
				hash = window.location.hash;
				if($('div.wigii-menu a[href*="'+hash+'"]').parents("#wigii-logo").length  || $('div.wigii-menu a[href*="'+hash+'"]').parent().hasClass("a-top")){
					/* do nothing */
				} else {
					$('div.wigii-menu a[href*="'+hash+'"]').addClass("over").append('<span class="wigii-arrow"> ▼</span>');
				}
			}
			/* if hash tag exist in the page */
			if($(hash).length){
				scrollTo = $(hash).offset().top-$("div.wigii-menu").outerHeight()-10;
				$("html, body").animate({
							scrollTop: scrollTo
					}, 800, function(){
						/* Add hash (#) to URL when done scrolling (default click behavior) */
						if(fromClick){
							window.location.hash = hash;
						}
						$(window).scrollTop(scrollTo);
					});
			}
		}
	}
	$jsPublicComments
	resize();
	scrollToHash();
	$(window).resize(function(e){ resize(e); });
	$("a").click(scrollToHash);
	window.onhashchange = function() { scrollToHash(); };
	$jsQueue
});
</script>
$ncdProgramOutput
<script src="https://www.wigii.org/system/libs/wigii-ncd-core.min.js"></script>
$footer
$js
</body>
</html>
PAGEFOOTER;

		return $returnValue;
	}
	
	
	protected function cms_getPublicCommentsJS($options) {
		$transS = ServiceProvider::getTranslationService();
		$principal = $this->getPrincipal();
		$language = $options->getValue('language');
		
		$introElementId = $options->getValue("introElementId");
		$publicCommentsNamePlaceHolder = $transS->t($principal,"first_name", null, $language)." ".$transS->t($principal,"last_name", null, $language);
		$publicCommentsPostUrl = SITE_ROOT.$principal->getWigiiNamespace()->getWigiiNamespaceUrl().'/CMS/www/addPublicComment/'.$introElementId;
		$returnValue = <<<JSPUBLICCOMMENTS
$("div.wigii-cms-add-public-comments").click(function(){
	if(!$("button",this).length){
		$(this).append('<br /><input type="text" placeholder="$publicCommentsNamePlaceHolder" style="box-sizing:border-box;width:100%;margin-top:5px;margin-bottom:2px;"/><textarea style="box-sizing:border-box;width:100%;margin-bottom:5px;"></textarea><br /><button>Ok</button>');
		$(":input:first",this).focus();
		autosize($("textarea",this).css("max-height",250).css("min-height",30));
		$("textarea",this).wordlimit({ allowed: 77 });
		$("button", this).click(function(e){
			var name = $(this).prev().prev().prev().val();
			var message = $(this).prev().prev().val();
			if(!name){
				$(this).prev().prev().prev().css("border-color","red");
			} else {
				$(this).prev().prev().prev().css("border-color","");
			}
			if(!message){
				$(this).prev().prev().css("border-color","red");
			} else {
				$(this).prev().prev().css("border-color","");
			}
			if(name && message){
				var myAjax = new jQuery.ajax({
					type: "POST",
					url: encodeURI("$publicCommentsPostUrl"),
					success : function(data){ $("div.wigii-cms-add-public-comments :input,  div.wigii-cms-add-public-comments br").remove(); $("#wigii-cms-public-comments-content").html(data); },
					cache:false,
					crossDomain: true,
					xhrFields: {withCredentials: true},
					data: {
						name: name,
						addJournalItemMessage: message,
						elementId: $introElementId,
						journalFieldName: "introComments",
						toRefreshId: "wigii-cms-public-comments-content"
					},
					error: function(data){
						var fxError = data.responseXML;
						wigii("HelpService").showFloatingHelp($("#wigii-cms-public-comments-content"),e,
							wigii().exception2html($(fxError).find("exception"),undefined),
							{localContent:true,position:"NW",removeOnClose:true}
						);
					}
				});
			}
		});
	}
});
JSPUBLICCOMMENTS;
		return $returnValue;
	}
	
	/**
	 * Builds HTML Page intro string
	 * @param WigiiBPLParameter $options some rendering options
	 * @return Element found element with fields siteTitle and contentIntro filled or null if not found
	 */
	protected function cms_getIntro($options) {
		$returnValue = sel($this->getPrincipal(),elementPList(lxInG(lxEq(fs('id'),$options->getValue('groupId'))),
				lf(fsl(fs('siteTitle'),fs("metaDescription"),fs("metaKeywords"),fs("metaAuthor"),fs('contentIntro'),fs('enablePublicComments'),fs('introComments'),fs('introBgColor'),fs('introBgAlpha'),fs('imgIntroBG','url')),
						lxAnd(lxEq(fs('contentType'),'intro'),lxEq(fs('status'),'published')),
						null,1,1)),
				dfasl(dfas("NullDFA")));
		if(isset($returnValue)) $returnValue = $returnValue->getDbEntity();
		return $returnValue;
	}
	/**
	 * Builds HTML Page logo string
	 * @param WigiiBPLParameter $options some rendering options
	 * @return String
	 */
	protected function cms_getLogo($options) {
		$returnValue = sel($this->getPrincipal(),elementPList(lxInG(lxEq(fs('id'),$options->getValue('groupId'))),
				lf(fsl(fs('contentLogo')),
						lxAnd(lxEq(fs('contentType'),'logo'),lxEq(fs('status'),'published')),
						null,1,1)),
				dfasl(
						dfas('MapElement2ValueDFA','setElement2ValueFuncExp',fs('contentLogo')),
						dfas('StringBufferDFA','setChunkSeparator',"\n")
						)
				);
		if(is_array($returnValue)) $returnValue = $returnValue[$options->getValue('language')];
		return $returnValue;
	}
	/**
	 * Builds HTML Page menu string
	 * @param WigiiBPLParameter $options some rendering options
	 * @return String
	 */
	protected function cms_getMenu($options) {
		$returnValue = sel($this->getPrincipal(),elementPList(lxInG(lxEq(fs('id'),$options->getValue('groupId'))),
				lf(fsl(fs('contentMenu')),
						lxAnd(lxEq(fs('contentType'),'menu'),lxEq(fs('status'),'published')),
						null,1,1)),
				dfasl(
						dfas('MapElement2ValueDFA','setElement2ValueFuncExp',fs('contentMenu')),
						dfas('StringBufferDFA','setChunkSeparator',"\n")
						)
				);
		if(is_array($returnValue)) $returnValue = $returnValue[$options->getValue('language')];
		return $returnValue;
	}
	/**
	 * Builds HTML Page footer string
	 * @param WigiiBPLParameter $options some rendering options
	 * @return String
	 */
	protected function cms_getFooter($options) {
		$returnValue = sel($this->getPrincipal(),elementPList(lxInG(lxEq(fs('id'),$options->getValue('groupId'))),
				lf(fsl(fs('contentFooter')),
						lxAnd(lxEq(fs('contentType'),'footer'),lxEq(fs('status'),'published')),
						null,1,1)),
				dfasl(
						dfas('MapElement2ValueDFA','setElement2ValueFuncExp',fs('contentFooter')),
						dfas('StringBufferDFA','setChunkSeparator',"\n")
						)
				);
		if(is_array($returnValue)) $returnValue = $returnValue[$options->getValue('language')];
		return $returnValue;
	}
	/**
	 * Builds CSS definition
	 * @param WigiiBPLParameter $options some rendering options
	 * @return String HTML string
	 */
	protected function cms_getCSS($options) {
		return sel($this->getPrincipal(),elementPList(lxInG(lxEq(fs('id'),$options->getValue('groupId'))),
				lf(
						fsl(fs('contentCSS')),
						lxAnd(lxEq(fs('contentType'),'css'),lxEq(fs('status'),'published'))
						)),
				dfasl(
						dfas('MapElement2ValueDFA','setElement2ValueFuncExp',fs('contentCSS')),
						dfas('StringBufferDFA','setChunkSeparator',"\n")
						)
				);
	}
	/**
	 * Builds JS code of page
	 * @param WigiiBPLParameter $options some rendering options
	 * @return String HTML string
	 */
	protected function cms_getJSCode($options) {
		return sel($this->getPrincipal(),elementPList(lxInG(lxEq(fs('id'),$options->getValue('groupId'))),
				lf(
						fsl(fs('contentJS')),
						lxAnd(lxEq(fs('contentType'),'js'),lxEq(fs('status'),'published'))
						)),
				dfasl(
						dfas('MapElement2ValueDFA','setElement2ValueFuncExp',fs('contentJS')),
						dfas('StringBufferDFA','setChunkSeparator',"\n")
						)
				);
	}
	/**
	 * Builds HTML header string
	 * @param WigiiBPLParameter $options some rendering options
	 * @return String HTML string
	 */
	protected function cms_getHtmlHeader($options) {
		$url = $options->getValue('url');
		$language = $options->getValue('language');
		$css = $this->cms_getHtmlStyles($options);
		$titleAndMeta = '';
		$title = $options->getValue('title');
		if(isset($title)) $title = '<title>'.$title.'</title>';
		$metaDescription = $options->getValue('metaDescription');
		if(isset($metaDescription)) $metaDescription = '<meta name="description" content="'.str_replace('"','',$metaDescription).'"/>'."\n";
		$metaKeywords = $options->getValue('metaKeywords');
		if(isset($metaKeywords)) $metaKeywords = '<meta name="keywords" content="'.str_replace('"','',$metaKeywords).'"/>'."\n";
		$metaAuthor = $options->getValue('metaAuthor');
		if(isset($metaAuthor)) $metaAuthor = '<meta name="author" content="'.str_replace('"','',$metaAuthor).'"/>'."\n";		
		$wigiiJS = '<script type="text/javascript" src="https://resource.wigii.org/assets/js/wigii_'.ASSET_REVISION_NUMBER.'.js"></script>';
		//$wigiiCSS = '<link rel="stylesheet" href="'.SITE_ROOT_forFileUrl.'/assets/css/wigii_'.ASSET_REVISION_NUMBER.'.css" type="text/css" media="all" />';
		$wigiiCSS = '';/* not compatible yet with CMS */
		$returnValue = <<<HTMLHEAD
<!DOCTYPE html>
<!--
**
*  This page has been generated by Wigii.
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
*  @copyright  Copyright (c) 2016-2018  Wigii.org
*  @author     <http://www.wigii.org/system>      Wigii.org
*  @link       <http://www.wigii-system.net>      <https://github.com/wigii/wigii>   Source Code
*  @license    <http://www.gnu.org/licenses/>     GNU General Public License
*
-->
<html>
<head>
<base href="$url" />
$title
$metaDescription $metaKeywords $metaAuthor
<meta name="generator" content="Wigii-system   http://www.wigii-system.net" />
<meta http-equiv="content-type" content="text/html;charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
$wigiiJS
$wigiiCSS
<link media="all" type="text/css" href="https://www.wigii.org/system/libs/wigii-ncd-stdlib.css" rel="stylesheet"/>
<style>
$css
</style>
</head>
HTMLHEAD;
		return $returnValue;
	}
	/**
	 * Builds HTML CSS string
	 * @param WigiiBPLParameter $options some rendering options
	 * @return String HTML CSS string included with header
	 */
	protected function cms_getHtmlStyles($options) {
		$marginWidth = $options->getValue("marginWidth");
		$logoTextColor = str_replace("#", "", $options->getValue("logoTextColor"));
		$logoTextSize = $options->getValue("logoTextSize");
		$menuBgColor = str_replace("#", "", $options->getValue("menuBgColor"));
		$menuTextColor = str_replace("#", "", $options->getValue("menuTextColor"));
		$menuTextHoverColor = str_replace("#", "", $options->getValue("menuTextHoverColor"));
		$titleTextColor = str_replace("#", "", $options->getValue("titleTextColor"));
		$titleTextSize = str_replace("#", "", $options->getValue("titleTextSize"));
		$publicCommentsBgColor = str_replace("#", "", $options->getValue("publicCommentsBgColor"));
		$publicCommentsTextColor = str_replace("#", "", $options->getValue("publicCommentsTextColor"));
		$footerBgColor = str_replace("#", "", $options->getValue("footerBgColor"));
		$footerTextColor = str_replace("#", "", $options->getValue("footerTextColor"));
		$linkTextColor = str_replace("#", "", $options->getValue("linkTextColor"));
		$evenArticleBgColor = str_replace("#", "", $options->getValue("evenArticleBgColor"));
		$oddArticleBgColor = str_replace("#", "", $options->getValue("oddArticleBgColor"));
		$enablePublicComments = $options->getValue("enablePublicComments");
		$publicCommentsBgColor = str_replace("#", "", $options->getValue("publicCommentsBgColor"));
		$publicCommentsTextColor = str_replace("#", "", $options->getValue("publicCommentsTextColor"));
		
		$articleWidth = "100%";
		if($enablePublicComments) $articleWidth = "80%";
		$publicCommentsWidth = "20%";
		
		$returnValue = <<<HTMLCSS
html, body 						{ height:100%; padding: 0px; margin:0px; font-family:arial; }
a 								{ text-decoration: none;}
a:hover 						{ text-decoration: underline; }
div.wigii-globalContainer 		{ min-height:100%; }
div.wigii-globalContainer>div.wigii-cms
								{ padding-top:10px; padding-bottom:30px; border-bottom:2px solid #fff; }
div.wigii-cms-public-comments	{ width:$publicCommentsWidth; color:#$publicCommentsTextColor;background-color:#$publicCommentsBgColor;padding:0px 5px 0px 10px; box-sizing:border-box; position:fixed;right:0;border-left:2px solid #fff;}
div.wigii-cms-public-comments:hover	{ overflow-y:auto;}
div.wigii-cms-public-comments p	{ margin:0px;}
div.wigii-cms-public-comments-title	{ padding-top:10px; padding-bottom:5px; font-style:italic; text-align:center;}
div.wigii-cms-public-comments-hr	{ background-color:#$publicCommentsTextColor; height:2px; width:40px; display:block;margin:0 auto 5px; text-align:center;}
div.wigii-cms-add-public-comments	{ cursor:pointer; text-align:center;margin-bottom:16px;}
div.wigii-cms 					{ width:$articleWidth; box-sizing:border-box; }
div.wigii-cms div.wigii-cms		{ width:100%; }
div.wigii-cms  div.wigii-cms.title-content 	{ float:left; width:80%; }
div.wigii-cms  div.wigii-cms.a-top { float:right; width:20%; margin-top:24px; margin-bottom:24px; font-size:small; text-align:right; }
div.wigii-cms.content 			{ clear:left; margin:0px; }
div.wigii-cms.content p			{ margin-top:6px; margin-bottom:6px; }
div.wigii-globalContainer>div.wigii-footer.wigii-cms.content
								{ position:absolute; width:100%; font-size:small; padding-top:10px; padding-bottom:10px;border-bottom:none; }
div.wigii-footer p 				{ margin:0px; padding:0px; }
div.wigii-menu 					{ z-index:1;padding:10px 0px; position:fixed; width:100%; @media (max-height:600px) { padding-top:1px; padding-bottom:1px; } }
div.wigii-menu #wigii-logo 		{ padding-left:-10px; margin-top:0px; float:left; }
div.wigii-menu #wigii-logo p	{ margin:0px; }
div.wigii-menu #wigii-logo span	{ font-size:22px; vertical-align:bottom; }
div.wigii-menu #wigii-logo a:hover
								{ text-decoration:none; }
div.wigii-menu ul 				{ list-style-type:none; float:right; padding:0px; margin:0px; margin-top:17px; }
div.wigii-menu ul li 			{ float:left; margin-left:22px; margin-bottom:10px; font-weight:bold; @media (max-height:600px) { margin-bottom:1px; } }
div.wigii-menu a:hover, div.wigii-menu a.over
								{ text-decoration:none; }
div#top 						{ float:left; }

/* color and size of text in logo */
div.wigii-menu #wigii-logo a { color: #$logoTextColor; font-size:$logoTextSize; vertical-align:bottom; }
/* color of menu */
div.wigii-menu { border-bottom: 2px solid #fff; background-color:#$menuBgColor;}
/* color of text in menu */
div.wigii-menu a { color: #$menuTextColor; }
div.wigii-menu a:hover, div.wigii-menu a.over { color: #$menuTextHoverColor; }
/* color and size of title of article */
div.wigii-cms.title { color:#$titleTextColor; font-size:$titleTextSize }
/* background-color of footer */
div.wigii-globalContainer>div.wigii-footer.wigii-cms.content { background-color:#$footerBgColor; }
/* text color of footer */
div.wigii-footer.wigii-cms.content a { color:#$footerTextColor; }
/* side margin percentage */
div.wigii-menu #wigii-logo { margin-left:$marginWidth; }
div.wigii-menu ul { margin-right:$marginWidth; }
div.wigii-cms.a-top { padding-right:$marginWidth; }
div.wigii-cms.title-content { padding-left:$marginWidth;  padding-right:$marginWidth; }
div.wigii-cms.content { padding-left:$marginWidth; padding-right:$marginWidth; }
/* color of links in article*/
a { color: #$linkTextColor; }
/* background-color of odd articles */
div.wigii-globalContainer>div.wigii-cms:nth-child(even) { background-color:#$evenArticleBgColor; /* #FFF; */}
div.wigii-globalContainer>div.wigii-cms:nth-child(odd) { background-color:#$oddArticleBgColor; /* #646EFF; */}
HTMLCSS;
		$customCSS = $options->getValue('css');
		if(isset($customCSS)) $returnValue .= "\n".$customCSS;
		return $returnValue;
	}
	
	/**
	 * Returns article separator string
	 * @param WigiiBPLParameter $options some rendering options
	 * @return String used to separate the articles
	 */
	protected function cms_getArticleSep($options) {
		return "* * *";
	}
	
	/**
	 * Returns language selection menu
	 * @param WigiiBPLParameter $options some rendering options
	 * @return String html menu to choose language
	 */
	protected function cms_getLanguageMenu($options) {
		$languages = $options->getValue('languages');
		if(!isset($languages)) $languages = ServiceProvider::getTranslationService()->getVisibleLanguage();
		$returnValue = '';
		if(count($languages)>1) {
			foreach($languages as $lan=>$language) {
				if($returnValue) $returnValue .= ' | ';
				$returnValue .= '<a href="./'.$lan.'">'.$language.'</a>';
			}
		}
		return $returnValue;
	}
	
	/**
	 * Returns a SiteMap element given its folder ID
	 * @param WigiiBPLParameter $options some rendering options
	 * @return Element found element with fields siteUrl, supportedLanguage, defaultLanguage or null if not found
	 */
	protected function cms_getSiteMap($options) {
		$returnValue = sel($this->getPrincipal(),elementPList(lxInG(lxEq(fs('id'),$options->getValue('groupId'))),
				lf(fsl(fs('siteUrl'),fs('forceHeight'),fs('forceHeightFirst'),fs('marginWidth'),fs('logoTextColor'),fs('logoTextSize'),fs('menuBgColor'),fs('menuTextColor'),fs('menuTextHoverColor'),fs('titleTextColor'),fs('titleTextSize'),fs('publicCommentsBgColor'),fs('publicCommentsTextColor'),fs('footerBgColor'),fs('footerTextColor'),fs('linkTextColor'),fs('evenArticleBgColor'),fs('oddArticleBgColor'),fs('supportedLanguage'),fs('defaultLanguage')),
						lxAnd(lxEq(fs('contentType'),'siteMap'),lxEq(fs('status'),'published')),
						null,1,1)),
				dfasl(dfas("NullDFA")));
		if(isset($returnValue)) $returnValue = $returnValue->getDbEntity();
		return $returnValue;
	}
	
	/**
	 * Gets all the accessible URLs managed by this CMS instance<br/>
	 * FuncExp signature is: <code>cms_getAllUrls()</code><br/>
	 * @return Array An array of accessible URLs relative to root.
	 */
	public function cms_getAllUrls($args=null) {
		$principal = $this->getPrincipal();
		// gets published site maps
		$returnValue = sel($principal,elementPList(lxInGR($this->getSiteMapLx($principal)),
			lf(fsl(fs('siteUrl'),fs('folderId')), lxAnd(lxEq(fs('contentType'),'siteMap'),lxEq(fs('status'),'published')))),
			dfasl(dfas('ArrayBufferDFA','setUnpair', true, 'setKeyField','siteUrl','setValueField','folderId'))
		);
		if(!isset($returnValue)) $returnValue = array();
		// gets all published forward urls
		$forwardUrls = sel($principal,elementPList(lxInGR($this->getSiteMapLx($principal)),
			lf(fsl(fs('fromUrl'),fs('toUrl')), lxAnd(lxEq(fs('contentType'),'forward'),lxEq(fs('status'),'published')))),
			dfasl(dfas('ArrayBufferDFA','setUnpair', true, 'setKeyField','fromUrl','setValueField','toUrl'))
		);
		if(!isset($forwardUrls)) $forwardUrls = array();
		// merges the two arrays and sorts.
		$returnValue = array_merge($returnValue,$forwardUrls);
		$returnValue = array_keys($returnValue);
		sort($returnValue);
		return $returnValue;
	}
	
	/**
	 * Gets the file associated to the given URL<br/>
	 * FuncExp signature is: <code>cms_getFile(url,options)</code><br/>
	 * Where arguments are :
	 * - Arg(0) url: String. A logical URL pointing to an existing Site Map, associated with an existing file name.
	 * - Arg(1) options: WigiiBPLParameter. An optional map of options used to parametrize the rendering process.
	 *    - fileName: String. Optional string containing the name of the file to get
	 *    - fileExt: String. Optional string containing the name of the file extension (with the separating dot)
	 * @return mixed. Or content is directly echoed to output stream or returned as a serializable object
	 */
	public function cms_getFile($args) {
		$nArgs = $this->getNumberOfArgs($args);
		$returnValue = null;
		$principal = $this->getPrincipal();
		$this->executionSink()->publishStartOperation("cms_getFile", $principal);
		try {
			// extracts parameters
			if($nArgs<1) throw new FuncExpEvalException('cms_getFile takes at least one argument which is the logical URL of the file to get', FuncExpEvalException::INVALID_ARGUMENT);
			$url = $this->evaluateArg($args[0]);
			if($nArgs>1) $options = $this->evaluateArg($args[1]);
			else $options = null;
			// extracts folderPath, fileName and fileExt
			if($options instanceof WigiiBPLParameter) {
				$fileName = $options->getValue('fileName');
				$fileExt = $options->getValue('fileExt');
			}
			else {
				$fileName = null;
				$fileExt = null;
			}
			if($fileName && $fileExt) $folderPath = str_replace($fileName.$fileExt,'',$url);
			else {
				$pos = strrpos($url,'.');
				if($pos!==false) {
					$fileExt = substr($url,$pos);
					$folderPath = substr($url,0,$pos);
					$pos = strrpos($folderPath,'/');
					if($pos!==false) {
						$fileName = substr($folderPath,$pos+1);
						$folderPath = substr($folderPath,0,$pos+1);
					}
					else throw new FuncExpEvalException("No file found at $url",FuncExpEvalException::NOT_FOUND);
				}
				else throw new FuncExpEvalException("No file found at $url",FuncExpEvalException::NOT_FOUND);
			}
			
			if(empty($folderPath)) throw new FuncExpEvalException("No file found at $url",FuncExpEvalException::NOT_FOUND);
			// lookups groupId associated to folderPath
			$groupId = $this->evaluateFuncExp(fx('cms_getGroupIdForUrl',$folderPath),$this);
			if(empty($groupId)) throw new FuncExpEvalException("No file found at $url", FuncExpEvalException::NOT_FOUND);
			
			// Fetches files of type HTML through the pattern elementId.html
			if($fileExt == '.html') {
				// retrieves element based on ID
				if(!is_numeric($fileName)) throw new FuncExpEvalException("No file found at $url", FuncExpEvalException::NOT_FOUND);
				$fieldName = 'contentImage';
				$fslForFetch = FieldSelectorListArrayImpl::createInstance();
				$fslForFetch->addFieldSelector($fieldName, "path");
				//$fslForFetch->addFieldSelector($fieldName, "name");
				$fslForFetch->addFieldSelector($fieldName, "size");
				//$fslForFetch->addFieldSelector($fieldName, "type");
				//$fslForFetch->addFieldSelector($fieldName, "mime");
				//$fslForFetch->addFieldSelector($fieldName, "date");
				//$fslForFetch->addFieldSelector($fieldName, "user");
				//$fslForFetch->addFieldSelector($fieldName, "username");
				//$fslForFetch->addFieldSelector($fieldName, "version");
				//$fslForFetch->addFieldSelector($fieldName, "thumbnail");
				$fslForFetch->addFieldSelector($fieldName, "content");
				//$fslForFetch->addFieldSelector($fieldName, "textContent");
				
				$element = sel($principal,elementPList(lxInG(lxEq(fs('id'),$groupId)),
						lf($fslForFetch,
								lxAnd(lxEq(fs_e('id'),$fileName),lxEq(fs($fieldName,'type'),'.nohtml.txt'),lxEq(fs('status'),'published')),
								null,1,1)),
						dfasl(dfas("NullDFA")));
				
				// dumps file content
				if($element) {
					$element = $element->getDbEntity();
					$s = 'text/html';
					if($s) header('Content-type: '.$s);
					$s = $element->getFieldValue($fieldName,'size');
					if($s) header('Content-Length: '.$s);
					
					$path = FILES_PATH.$element->getFieldValue($fieldName, "path");
					if(!file_exists($path)) echo $element->getFieldValue($fieldName, "content");
					else readfile($path);
				}
				else throw new FuncExpEvalException("No file found at $url", FuncExpEvalException::NOT_FOUND);
			}
			// if sitemap.fx then returns the list of accessible URLs
			elseif($fileName == 'sitemap' && $fileExt == '.fx') {
				return json_encode($this->cms_getAllUrls(),JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			}
			// Else fetches standard File according to mime
			else {
				// retrieves element
				$fieldName = 'contentImage';
				$fslForFetch = FieldSelectorListArrayImpl::createInstance();
				$fslForFetch->addFieldSelector($fieldName, "path");
				$fslForFetch->addFieldSelector($fieldName, "name");
				$fslForFetch->addFieldSelector($fieldName, "size");
				$fslForFetch->addFieldSelector($fieldName, "type");
				$fslForFetch->addFieldSelector($fieldName, "mime");
				$fslForFetch->addFieldSelector($fieldName, "date");
				//$fslForFetch->addFieldSelector($fieldName, "user");
				//$fslForFetch->addFieldSelector($fieldName, "username");
				//$fslForFetch->addFieldSelector($fieldName, "version");
				//$fslForFetch->addFieldSelector($fieldName, "thumbnail");
				$fslForFetch->addFieldSelector($fieldName, "content");
				//$fslForFetch->addFieldSelector($fieldName, "textContent");
				
				$element = sel($principal,elementPList(lxInG(lxEq(fs('id'),$groupId)),
						lf($fslForFetch,
								lxAnd(lxEq(fs($fieldName,'name'),$fileName),lxEq(fs($fieldName,'type'),$fileExt),lxEq(fs('status'),'published')),
								null,1,1)),
						dfasl(dfas("NullDFA")));
				
				// dumps file content
				if($element) {
					$element = $element->getDbEntity();
					$s = $element->getFieldValue($fieldName,'mime');
					if($s) header('Content-type: '.$s);
					$s = $element->getFieldValue($fieldName,'size');
					if($s) header('Content-Length: '.$s);
					
					$path = FILES_PATH.$element->getFieldValue($fieldName, "path");
					if(!file_exists($path)) echo $element->getFieldValue($fieldName, "content");
					else readfile($path);
				}
				else throw new FuncExpEvalException("No file found at $url", FuncExpEvalException::NOT_FOUND);
			}
		}
		catch(Exception $e) {
			$this->executionSink()->publishEndOperationOnError("cms_getFile", $e, $principal);
			throw $e;
		}
		$this->executionSink()->publishEndOperation("cms_getFile", $principal);
		return $returnValue;
	}
	
	
	// POST processing
	
	/**
	 * Entry point to process the incoming CMS POST request<br/>
	 * FuncExp signature is: <code>cms_processPost(params)</code><br/>
	 * Where arguments are :
	 * - Arg(0) params: WigiiBPLParameter. A map of key values specifying the request and its environment
	 *   parsedUrl: Array. The url parsed into arguments by the ExecutionService
	 *   isIntegrated: Boolean. True if the answer is integrated into a Wigii protocol answer, false if http header should be sent.
	 * @return mixed. Can echo directly the output or return an object which will be serialized by the WigiiWebCMSFormExecutor.
	 */
	public function cms_processPost($args) {		
		$returnValue = null;
		$principal = $this->getPrincipal();
		$this->executionSink()->publishStartOperation("cms_processPost", $principal);
		try {
			// check we are in POST
			if($_SERVER['REQUEST_METHOD']!='POST') throw new FuncExpEvalException('cms_processPost requires http verb is POST and not '.$_SERVER['REQUEST_METHOD'], FuncExpEvalException::INVALID_STATE);
			// extracts parameters
			if(is_array($args)) {
				$nArgs = $this->getNumberOfArgs($args);
				if($nArgs<1) throw new FuncExpEvalException('cms_processPost takes at least one argument which is a WigiiBPLParameter object containing the parsedUrl array', FuncExpEvalException::INVALID_ARGUMENT);
				$params = $this->evaluateArg($args[0]);
			}
			else $params = $args;
			if(!($params instanceof WigiiBPLParameter)) throw new FuncExpEvalException('cms_processPost takes at least one argument which is a WigiiBPLParameter object containing the parsedUrl array', FuncExpEvalException::INVALID_ARGUMENT);
			// extracts parsedUrl
			$parsedUrl = $params->getValue('parsedUrl');
			if(empty($parsedUrl)) throw new FuncExpEvalException('Empty request', FuncExpEvalException::NOT_FOUND);
			
			// injects TRM
			$this->getFormExecutor()->setTrm($this->createTrmInstance());/* by default output is enabled. */
			
			// dispatches POST action
			
			// case /addComment/elementId
			$elementId = ValueObject::createInstance();
			if(arrayMatch($parsedUrl, 'addPublicComment',$elementId)) {
				$params->setValue('elementId', $elementId->getValue());
				$returnValue = $this->cms_addPublicComment($params);
			}			
			// default unknown request 
			else throw new FuncExpEvalException('Unknown request '.implode('/',$parsedUrl), FuncExpEvalException::NOT_FOUND);			
		}
		catch(Exception $e) {
			$this->executionSink()->publishEndOperationOnError("cms_processPost", $e, $principal);
			throw $e;
		}
		$this->executionSink()->publishEndOperation("cms_processPost", $principal);
		return $returnValue;
	}
	/**
	 * Adds a public comment to an article
	 * @param WigiiBPLParameter $options some posting options
	 * @return String returns the updated comments content
	 */
	protected function cms_addPublicComment($options) {
		$p = $this->getPrincipal();
		$rm = $this->getFormExecutor()->getTrm();
		
		$this->debugLogger()->logBeginOperation('cms_addPublicComment');		
		// sanitizes posted form
		$this->getFormExecutor()->preventInjectionForm($p, ServiceProvider::getExecutionService());
		
		$fieldName = $_POST["journalFieldName"];
		$message = $_POST["addJournalItemMessage"];
		$author = $_POST["name"];
		
		// prepares comment
		$header = date("d.m.Y H:i")." ".$rm->formatValueToPreventInjection($author);
		$returnValue = '<p style="color:#666;">&gt; ';
		$returnValue.= $header;
		$returnValue.= "</p>";
		$returnValue.= '<p>'.nl2br($rm->formatValueToPreventInjection($message)).'</p>';
		$returnValue.= "<p> </p>";
		
		// adds comment to article
		$returnValue = ServiceProvider::getDataFlowService()->processDataSource($p, elementP($options->getValue('elementId')), dfasl(
			dfas('ElementSetterDFA','setCalculatedFieldSelectorMap',cfsMap(
				cfs($fieldName, fx('concat',$returnValue,fs($fieldName)))
			)),
			dfas('ElementDFA','setMode',1),
			dfas('MapElement2ValueDFA','setElement2ValueFuncExp',fs($fieldName))
		), true, TechnicalServiceProvider::getWigiiEventsDispatcher());
		$this->debugLogger()->logEndOperation('cms_addPublicComment');
		return $returnValue;
	}
	
	
	// Utilities
	
	protected function clearUnwantedFields($fslForUpdate) {
		$formBag = $this->getElement()->getWigiiBag();
		$fieldList = $this->getElement()->getFieldList();
		foreach($fieldList->getListIterator() as $field) {
			$fieldName = $field->getFieldName();
			if($fieldName == 'contentType') continue;
			if($fieldName == 'status') continue;
			if($fieldName == 'comments') continue;
			if(!$fslForUpdate->containsFieldSelector($fieldName)) {
				if(!is_null($field->getDataType()) && $formBag->isFilled($fieldName)) {
					$formBag->emptyFieldValue($fieldName);
					$formBag->setChanged($fieldName);
				}
			}
		}
	}
	protected function getFslForContentType($contentType) {
		$returnValue = null;
		switch($contentType) {
			case "content": $returnValue = fsl(fs("choosePosition"),fs("contentPosition"),fs("contentNextId"),fs("contentTitle"),fs("contentHTML"),fs('articleBgColor'),fs('articleBgAlpha'),fs('imgArticleBG'),fs('imgArticleBG','url')); break;
			case "ncd": $returnValue = fsl(fs("choosePosition"),fs("contentPosition"),fs("contentNextId"),fs("contentTitle"),fs("contentNCD"),fs('articleBgColor'),fs('articleBgAlpha'),fs('imgArticleBG'),fs('imgArticleBG','url')); break;
			case "siteMap": $returnValue = fsl(fs("siteUrl"),fs("folderId"),fs("forceHeight"),fs("forceHeightFirst"),fs('marginWidth'),fs('logoTextColor'),fs('logoTextSize'),fs('menuBgColor'),fs('menuTextColor'),fs('menuTextHoverColor'),fs('titleTextColor'),fs('titleTextSize'),fs('publicCommentsBgColor'),fs('publicCommentsTextColor'),fs('footerBgColor'),fs('footerTextColor'),fs('linkTextColor'),fs('evenArticleBgColor'),fs('oddArticleBgColor'),fs("siteMap"),fs("supportedLanguage"),fs("defaultLanguage")); break;
			case "intro": $returnValue = fsl(fs("siteTitle"),fs("metaDescription"),fs("metaKeywords"),fs("metaAuthor"),fs('contentIntro'),fs('enablePublicComments'),fs('introComments'),fs('introBgColor'),fs('introBgAlpha'),fs('imgIntroBG'),fs('imgIntroBG','url')); break;
			case "logo": $returnValue = fsl(fs("contentLogo")); break;
			case "menu": $returnValue = fsl(fs("contentMenu")); break;
			case "image": $returnValue = fsl(fs("contentImage")); break;
			case "footer": $returnValue = fsl(fs("contentFooter")); break;
			case "css": $returnValue = fsl(fs("contentCSS")); break;
			case "js": $returnValue = fsl(fs("contentJS")); break;
			case "forward": $returnValue = fsl(fs("fromUrl"),fs("toUrl")); break;
		}
		return $returnValue;
	}
	/**
	 *@param Principal $principal current principal executing the request
	 *@return LogExp returns a logical expression selecting the groups in which to search SiteMap content
	 */
	protected function getSiteMapLx($principal) {
		// root groups in current WigiiNamespace,
		$currentWigiiNamespace = $principal->getWigiiNamespace();
		// with CMS module,
		$cmsModule = ServiceProvider::getModuleAdminService()->getModule($principal,'CMS');
		// without trashbin
		$trashBinGroup = (string)ServiceProvider::getConfigService()->getParameter($principal,$cmsModule,'trashBinGroup');
		
		$returnValue = lxAnd(lxEq(fs('wigiiNamespace'),$currentWigiiNamespace->getWigiiNamespaceName()),
				lxEq(fs('module'),$cmsModule->getModuleName()),
				lxEq(fs('id_group_parent'),null)
				);
		if($trashBinGroup) $returnValue->addOperand(lxNotEq(fs('id'),$trashBinGroup));
		return $returnValue;
	}
	
	/**
	 * Copies the value of a given field from an element to a bag of options.
	 * If the value is multilanguage, then uses the language defined in the options to get the right value,
	 * If the value is null, then can be optionally initialized with a given default value.
	 * @param String|FieldSelector $fieldName the field name in the element from which to fetch the value, 
	 * and option name under which the value is stored, except if optionName is defined.
	 * @param Element $element the element from which to fetch values
	 * @param WigiiBPLParameter $options the bag of options to be filled
	 * @param Any $defaultValue a default value if retrieved value is null
	 * @param String $optionName option name under which to store the option value. If not set, uses the fieldName.
	 * @return WigiiBPLParameter the updated set of options
	 */
	private function mapField2Option($fieldName,$element,$options,$defaultValue=null,$optionName=null) {
		if(!isset($element)) throw new FuncExpEvalException('element cannot be null', FuncExpEvalException::INVALID_ARGUMENT);
		if(!isset($options)) throw new FuncExpEvalException('options cannot be null', FuncExpEvalException::INVALID_ARGUMENT);		
		if($fieldName instanceof FieldSelector) {
			$subFieldName = $fieldName->getSubFieldName();
			$fieldName = $fieldName->getFieldName();
		}
		else $subFieldName = null;
		
		if(!isset($optionName)) $optionName = $fieldName;
		$value = $element->getFieldValue($fieldName,$subFieldName);
		// if value is null, then puts default value
		if(!isset($value)) $options->setValue($optionName, $defaultValue);
		// if value is array and field type is Varchars or Texts then gets the right language
		elseif(is_array($value)) {
			$dataType= $element->getFieldList()->getField($fieldName)->getDataType();
			if($dataType instanceof Varchars || $dataType instanceof Texts) $options->setValue($optionName, $value[$options->getValue('language')]);
			else $options->setValue($optionName,$value);
		}
		// else copies value as-is
		else $options->setValue($optionName,$value);
		return $options;
	}	
}