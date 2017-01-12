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
 * Wigii CMS module Element evaluator
 * Created by Weber wwigii-system.net for Wigii.org on 15.08.2016
 * Updated by Lionel Weber on 05.10.2016
 * Updated by Weber wwigii-system.net for Wigii.org on 15.11.2016
 */
class WigiiCMSElementEvaluator extends ElementEvaluator
{		
	private $_debugLogger;
	private $_executionSink;
	private $siteMap;
	
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
			
	// Content authoring and publishing
	
	/**
	 *@return String returns some JS code to be executed when Authoring form is loaded
	 */
	public function cms_authoringOnLoadJS($args) {
		return '(function(){
	var displayForm = function() {
		$("#$$idForm$$__groupSiteMap, #$$idForm$$__groupIntro, #$$idForm$$__groupLogo, #$$idForm$$__groupMenu, #$$idForm$$__groupContent, #$$idForm$$__groupImage, #$$idForm$$__groupFooter, #$$idForm$$__groupCSS, #$$idForm$$__groupJS").hide();
		if($("#$$idForm$$_contentType_value_select").val()=="none") $("#$$idForm$$_contentType_value_select").val("content");
		switch($("#$$idForm$$_contentType_value_select").val()) {
		case "content": $("#$$idForm$$__groupContent").show();break;
		case "siteMap": $("#$$idForm$$__groupSiteMap").show();break;
		case "intro": $("#$$idForm$$__groupIntro").show();break;
		case "logo": $("#$$idForm$$__groupLogo").show();break;
		case "menu": $("#$$idForm$$__groupMenu").show();break;
		case "image": $("#$$idForm$$__groupImage").show();break;
		case "footer": $("#$$idForm$$__groupFooter").show();break;
		case "css": $("#$$idForm$$__groupCSS").show();break;
		case "js": $("#$$idForm$$__groupJS").show();break;
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
				lxEq(fs('contentType'),'content'),
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
		case "content": $this->cms_authoringOnSaveContent(); break;
		case "siteMap": $this->cms_auhoringOnSaveSiteMap(); break;
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
					$html2text->html2text($t);
					$returnValue .= $html2text->get_text();
					$html2text->clear();
					$returnValue .= ' ';
				}
			}
			else {
				$html2text->html2text($txt);
				$returnValue = $html2text->get_text();
				$html2text->clear();
			}
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
					lxAnd(lxEq(fs('contentType'),'content'),lxSm(fs('contentPosition'),$nextPos)),
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
			if(empty($groupId)) throw new FuncExpEvalException("No content found at $url", FuncExpEvalException::NOT_FOUND);			
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
			if(isset($intro)) {			
				$title = $intro->getFieldValue('siteTitle');
				if(is_array($title)) $title = $title[$language];
				$options->setValue('title',$title);
				$intro = $intro->getFieldValue('contentIntro');
				if(is_array($intro)) $intro = $intro[$language];
			}
			
			// gets page options
			$forceHeight = $siteMap->getFieldValue('forceHeight');				
			if(!isset($forceHeight)) $forceHeight=false;
			$options->setValue('forceHeight',$forceHeight);
			$forceHeightFirst = $siteMap->getFieldValue('forceHeightFirst');				
			if(!isset($forceHeightFirst)) $forceHeightFirst=true;
			$options->setValue('forceHeightFirst',$forceHeightFirst);
			$marginWidth = $siteMap->getFieldValue('marginWidth');				
			if(!isset($marginWidth)) $marginWidth="11%";
			$options->setValue('marginWidth',$marginWidth);
			$logoTextColor = $siteMap->getFieldValue('logoTextColor');				
			if(!isset($logoTextColor)) $logoTextColor="666";
			$options->setValue('logoTextColor',$logoTextColor);
			$logoTextSize = $siteMap->getFieldValue('logoTextSize');				
			if(!isset($logoTextSize)) $logoTextSize="22px";
			$options->setValue('logoTextSize',$logoTextSize);
			$menuBgColor = $siteMap->getFieldValue('menuBgColor');				
			if(!isset($menuBgColor)) $menuBgColor="ccc";
			$options->setValue('menuBgColor',$menuBgColor);
			$menuTextColor = $siteMap->getFieldValue('menuTextColor');				
			if(!isset($menuTextColor)) $menuTextColor="fff";
			$options->setValue('menuTextColor',$menuTextColor);
			$titleTextColor = $siteMap->getFieldValue('titleTextColor');				
			if(!isset($titleTextColor)) $titleTextColor="696969";
			$options->setValue('titleTextColor',$titleTextColor);
			$menuTextHoverColor = $siteMap->getFieldValue('menuTextHoverColor');				
			if(!isset($menuTextHoverColor)) $menuTextHoverColor="5c523d";
			$options->setValue('menuTextHoverColor',$menuTextHoverColor);
			$titleTextSize = $siteMap->getFieldValue('titleTextSize');				
			if(!isset($titleTextSize)) $titleTextSize="24px";
			$options->setValue('titleTextSize',$titleTextSize);
			$footerBgColor = $siteMap->getFieldValue('footerBgColor');				
			if(!isset($footerBgColor)) $footerBgColor="696969";
			$options->setValue('footerBgColor',$footerBgColor);
			$footerTextColor = $siteMap->getFieldValue('footerTextColor');				
			if(!isset($footerTextColor)) $footerTextColor="fff";
			$options->setValue('footerTextColor',$footerTextColor);
			$linkTextColor = $siteMap->getFieldValue('linkTextColor');				
			if(!isset($linkTextColor)) $linkTextColor="646eff";
			$options->setValue('linkTextColor',$linkTextColor);
			$oddArticleBgColor = $siteMap->getFieldValue('oddArticleBgColor');				
			if(!isset($oddArticleBgColor)) $oddArticleBgColor="ebecff";
			$options->setValue('oddArticleBgColor',$oddArticleBgColor);
			
			// gets page Logo
			$logo = $this->cms_getLogo($options);

			// gets page Menu
			$menu = $this->cms_getMenu($options);

			// gets CSS definitions
			$css = $this->cms_getCSS($options);
			if(isset($css)) $options->setValue('css',$css);
			// gets JS code			
			$js = $this->cms_getJSCode($options);
			//gets page footer
			$footer = $this->cms_getFooter($options);
			// top link
			$atopLink = '<a href="./'.$language.'#top">▲ '.$transS->t($principal,"cmsAnchorTop",null,$language).'</a>';
			
			// renders header
			echo $this->cms_composeHeader($options,$logo,$menu,$intro)."\n";
			
			// renders article content
			sel($principal,elementPList(lxInG(lxEq(fs('id'),$groupId)), 
				lf(
					fsl(fs('contentTitle'),fs('contentHTML')),
					lxAnd(lxEq(fs('contentType'),'content'),lxEq(fs('status'),'published')),
					fskl(fsk('contentPosition'))
				)),
				dfasl(
				dfas('MapElement2ValueDFA','setElement2ValueFuncExp',fx('concat',
					fx('htmlStartTag','div','class','wigii-cms'),"\n",
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
						fx('htmlStartTag','div','class','wigii-cms content'),fx('first',fx('getAttr',fs('contentHTML'),$language),
							fx('concat',fx('htmlStartTag', 'p'),$transS->t($principal,"cmsNoContentAvailable",null,$language).$languages[$language],' ', 
								fx('htmlStartTag','a','target','_blank','href', fx('concat',fx('sysSiteRootUrl'),'#',fx('sysCrtWigiiNamespace'),'/',fx('sysCrtModule'),'/item/',fs_e('id'))), '(#',fs_e('id'),')',fx('htmlEndTag','a'),
							fx('htmlEndTag', 'p'))
						),
						//fx('htmlStartTag','p','style','text-align:center;'),$this->cms_getArticleSep($options),fx('htmlEndTag','p'),
						fx('htmlEndTag','div'),"\n",
					fx('htmlEndTag','div'),"\n")
				),
				dfas('StringSepDFA',
				'setSeparator',
				"\n"),
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
	protected function cms_composeHeader($options,$logo,$menu,$intro) {
		return $this->cms_getHtmlHeader($options)."\n<body>".'<div class="wigii-globalContainer">'."\n".
				(empty($logo)&&empty($menu)?' ':'<div class="wigii-menu">').
				(empty($logo)?' ':'<div id="wigii-logo">'.$logo.'</div>').
				(empty($menu)?' ':$menu).
				(empty($logo)&&empty($menu)?' ':'</div>').
				'<!-- top anchor -->'.
				'<div id="top" ></div><div style="clear:both;"></div>'.
				'<div class="wigii-cms">'."\n".
				'<div class="wigii-cms title"><div class="wigii-cms title-content"> </div><div class="wigii-cms a-top">'.$this->cms_getLanguageMenu($options).'</div></div>'."\n".
				'<div class="wigii-cms content">'.(empty($intro)?' ':$intro).'</div>'."\n".
				'</div>';
	}
	protected function cms_composeFooter($options,$footer,$js) {
		return 	'<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>'.
				'<script>
var enableArticleResize = '.($options->getValue("forceHeight") ? 'true' : 'false').';
var enableFirstArticleResize = '.($options->getValue("forceHeightFirst") ? 'true' : 'false').';
$(document).ready(function(){
	function resize(e){
		$("div#top").height($("div.wigii-menu").outerHeight());
		if(enableArticleResize) {  $("div.wigii-globalContainer>div.wigii-cms:not(.wigii-footer)").css("min-height",$(window).height()-$("div.wigii-menu").outerHeight()); }
		if(enableFirstArticleResize) {  $("div.wigii-globalContainer>div.wigii-cms:not(.wigii-footer):first").css("min-height",$(window).height()-$("div.wigii-menu").outerHeight()); }
		$("div.wigii-globalContainer>div.wigii-cms:not(.wigii-footer):last").css("min-height",$(window).height()-$("div.wigii-menu").outerHeight()-$("div.wigii-footer").outerHeight()-1);
		$("div.wigii-cms div.bottom").each(function(){ $(this).css("margin-top", Math.max(0,$(window).height()-$("div.wigii-menu").outerHeight()-$(this).parent().prev().outerHeight()-$(this).parent().outerHeight()-46)); });
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
			//alert(this.hash);
			//alert(window.location);
			//alert(href);
			//alert(href.substr(0,1));
			//alert(href.split("#")[0]);
			sameLocation = href.substr(0,1)=="." || href.substr(0,1)=="#" || href.split("#")[0]==(window.location+("")).split("#")[0];
		}
		if (!fromClick || sameLocation) {
			$("*").removeClass("over").find(".wigii-arrow").remove();
			if(fromClick){ 
				/* Prevent default anchor click behavior */
				e.preventDefault();
				hash = this.hash;
				if($(this).parents("#wigii-logo").length || $(this).parent().hasClass("a-top")){
					/* do nothing $(this).addClass("over").append("<span class=\"wigii-arrow\"> ▲</span>"); */
				} else {
					$(this).addClass("over").append("<span class=\"wigii-arrow\"> ▼</span>");
				}
			} else {
				hash = window.location.hash;
				if($(\'div.wigii-menu a[href*="\'+hash+\'"]\').parents("#wigii-logo").length  || $(\'div.wigii-menu a[href*="\'+hash+\'"]\').parent().hasClass("a-top")){
					/* do nothing */
				} else {
					$(\'div.wigii-menu a[href*="\'+hash+\'"]\').addClass("over").append("<span class=\"wigii-arrow\"> ▼</span>");
				}
			}
			/* if hash tag exist in the page */
			if($(hash).length){
				scrollTo = $(hash).offset().top-$("div.wigii-menu").outerHeight();
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
	resize();
	scrollToHash();
	$(window).resize(function(e){ resize(e); });
	$("a").click(scrollToHash);
	window.onhashchange = function() { scrollToHash(); }
});
</script>
'.
				(empty($footer)?' ':'<div class="wigii-footer wigii-cms content">'.$footer.'</div>').
				//'<div class="wigii-cms">'."\n".
				//'<div class="wigii-cms title" id="bottom"><div class="wigii-cms title-content"> </div><div class="wigii-cms a-top">'.$atopLink.'</div></div>'."\n".
				//'<div class="wigii-cms content">'.(empty($footer)?' ':$footer).'</div>'."\n".
				//'</div>'."\n".
				(!empty($js)?"<script>".$js."</script>\n":'')."</body>\n</html>";
	}
	/**
	 * Builds HTML Page intro string
	 * @param WigiiBPLParameter $options some rendering options
	 * @return Element found element with fields siteTitle and contentIntro filled or null if not found
	 */
	protected function cms_getIntro($options) {
		$returnValue = sel($this->getPrincipal(),elementPList(lxInG(lxEq(fs('id'),$options->getValue('groupId'))),
				lf(fsl(fs('siteTitle'),fs('contentIntro')),
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
		$title = $options->getValue('title');
		if(isset($title)) $title = '<title>'.$title.'</title>';
		$returnValue = <<<HTMLHEAD
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
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
*  @copyright  Copyright (c) 2016  Wigii.org
*  @author     <http://www.wigii.org/system>      Wigii.org
*  @link       <http://www.wigii-system.net>      <https://github.com/wigii/wigii>   Source Code
*  @license    <http://www.gnu.org/licenses/>     GNU General Public License
*
-->
<html>
<head>
<base href="$url" />
$title
<meta name="Copyright" content="Source Code: 2016 Wigii.org" />
<meta name="License" content="GNU GPL 3.0" />
<meta name="Generator" content="Wigii-system" />
<meta name="Description" content="Wigii is a web based system allowing management of any kind of data (contact, document, calendar, and any custom types). Find out documentation on http://www.wigii-system.net" />
<meta http-equiv="content-type" content="text/html;charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
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
		$logoTextColor = $options->getValue("logoTextColor");
		$logoTextSize = $options->getValue("logoTextSize");
		$menuBgColor = $options->getValue("menuBgColor");
		$menuTextColor = $options->getValue("menuTextColor");
		$menuTextHoverColor = $options->getValue("menuTextHoverColor");
		$titleTextColor = $options->getValue("titleTextColor");
		$titleTextSize = $options->getValue("titleTextSize");
		$footerBgColor = $options->getValue("footerBgColor");
		$footerTextColor = $options->getValue("footerTextColor");
		$linkTextColor = $options->getValue("linkTextColor");
		$oddArticleBgColor = $options->getValue("oddArticleBgColor");
		
		$returnValue = <<<HTMLCSS
html, body 						{ height:100%; padding: 0px; margin:0px; font-family:arial; }
a 								{ text-decoration: none;}
a:hover 						{ text-decoration: underline; }
div.wigii-globalContainer 		{ min-height:100%; }
div.wigii-globalContainer>div.wigii-cms
								{ padding-top:10px; padding-bottom:30px; }
div.wigii-cms 					{ width:100%; box-sizing:border-box; }
div.wigii-cms.title-content 	{ float:left; width:80%; }
div.wigii-cms.a-top 			{ float:right; width:20%; margin-top:24px; margin-bottom:24px; font-size:small; text-align:right; }
div.wigii-cms.content 			{ clear:left; margin:0px; }
div.wigii-cms.content p			{ margin-top:6px; margin-bottom:6px; }
div.wigii-globalContainer>div.wigii-footer.wigii-cms.content 
								{ position:absolute; width:100%; font-size:small; padding-top:10px; padding-bottom:10px; }
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
div.wigii-menu { background-color:#$menuBgColor;}
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
				lf(fsl(fs('siteUrl'),fs('forceHeight'),fs('forceHeightFirst'),fs('marginWidth'),fs('logoTextColor'),fs('logoTextSize'),fs('menuBgColor'),fs('menuTextColor'),fs('menuTextHoverColor'),fs('titleTextColor'),fs('titleTextSize'),fs('footerBgColor'),fs('footerTextColor'),fs('linkTextColor'),fs('oddArticleBgColor'),fs('supportedLanguage'),fs('defaultLanguage')),
				lxAnd(lxEq(fs('contentType'),'siteMap'),lxEq(fs('status'),'published')),
				null,1,1)),
				dfasl(dfas("NullDFA")));
		if(isset($returnValue)) $returnValue = $returnValue->getDbEntity();
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
		catch(Exception $e) {
			$this->executionSink()->publishEndOperationOnError("cms_getFile", $e, $principal);
			throw $e;
		}
		$this->executionSink()->publishEndOperation("cms_getFile", $principal);
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
				$formBag->emptyFieldValue($fieldName);
				$formBag->setChanged($fieldName);
			}
		}
	}
	protected function getFslForContentType($contentType) {
		$returnValue = null;
		switch($contentType) {
			case "content": $returnValue = fsl(fs("choosePosition"),fs("contentPosition"),fs("contentNextId"),fs("contentTitle"),fs("contentHTML")); break;
			case "siteMap": $returnValue = fsl(fs("siteUrl"),fs("folderId"),fs("forceHeight"),fs("forceHeightFirst"),fs('marginWidth'),fs('logoTextColor'),fs('logoTextSize'),fs('menuBgColor'),fs('menuTextColor'),fs('menuTextHoverColor'),fs('titleTextColor'),fs('titleTextSize'),fs('footerBgColor'),fs('footerTextColor'),fs('linkTextColor'),fs('oddArticleBgColor'),fs("siteMap"),fs("supportedLanguage"),fs("defaultLanguage")); break;
			case "intro": $returnValue = fsl(fs("siteTitle"),fs("contentIntro")); break;
			case "logo": $returnValue = fsl(fs("contentLogo")); break;
			case "menu": $returnValue = fsl(fs("contentMenu")); break;
			case "image": $returnValue = fsl(fs("contentImage")); break;
			case "footer": $returnValue = fsl(fs("contentFooter")); break;
			case "css": $returnValue = fsl(fs("contentCSS")); break;
			case "js": $returnValue = fsl(fs("contentJS")); break;
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
}