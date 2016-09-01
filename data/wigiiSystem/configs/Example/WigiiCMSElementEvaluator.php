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
		$("#$$idForm$$__groupSiteMap, #$$idForm$$__groupIntro, #$$idForm$$__groupContent, #$$idForm$$__groupImage, #$$idForm$$__groupFooter, #$$idForm$$__groupCSS, #$$idForm$$__groupJS").hide();
		if($("#$$idForm$$_contentType_value_select").val()=="none") $("#$$idForm$$_contentType_value_select").val("content");
		switch($("#$$idForm$$_contentType_value_select").val()) {
		case "content": $("#$$idForm$$__groupContent").show();break;
		case "siteMap": $("#$$idForm$$__groupSiteMap").show();break;
		case "intro": $("#$$idForm$$__groupIntro").show();break;
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
				fsl(fs('contentPosition'),fs('contentSummary')),
				lxEq(fs('contentType'),'content'),
				fskl(fsk('contentPosition','value'))
			)), 
			dfasl(
				dfas('MapElement2ValueDFA','setElement2ValueFuncExp',fx('concat',fx('htmlStartTag','option','value',fs('contentPosition')),fs('contentSummary'),fx('htmlEndTag','option'))),
				dfas('StringBufferDFA')
			)
		);
		if(isset($choosePosition)) return '(function(){$("#$$idForm$$_choosePosition_value_select").append('."'".$choosePosition."'".');})();';
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
	 * Specific authoring process when saving content
	 * - calculates the position of the article
	 */
	protected function cms_authoringOnSaveContent() {
		$content = $this->getElement();
		// calculates the position if not already done
		if($content->getFieldValue('contentPosition') == null) {
			// gets content folder ID
			$groupId = $this->evaluateFuncExp(fx('cfgCurrentGroup','id'),$this);
			// gets next article position
			$nextPos = $content->getFieldValue('choosePosition');
			// retrieves previous article position
			$prevPos = sel($this->getPrincipal(), elementPList(lxInG(lxEq(fs('id'),$groupId)),
				lf(
					fsl(fs('contentPosition')),
					lxAnd(lxEq(fs('contentType'),'content'),lxSm(fs('contentPosition'),$nextPos)),
					fskl(fsk('contentPosition','value',false)),
					1,1
				)), 
				dfasl(dfas('MapElement2ValueDFA','setElement2ValueFuncExp',fs('contentPosition')))
			);
			
			// calculates article position (compacts at the end to let more space at the beginning)
			$content->setFieldValue(0.25*$prevPos+0.75*$nextPos, 'contentPosition');
			// resets choosePosition option
			$content->setFieldValue(10000, 'choosePosition');
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
			// extracts language
			$languages = ServiceProvider::getTranslationService()->getVisibleLanguage();
			if(is_array($parsedUrl)) {
				// if first parameter is a valid language then extracts it
				if($languages[$parsedUrl[0]]) {
					$params->setValue('language', $parsedUrl[0]);
					array_shift($parsedUrl);
				}
				// else default language
				else  $params->setValue('language', 'l01');
			}
			elseif($languages[$parsedUrl]) $params->setValue('language', $parsedUrl);
			else $params->setValue('language', 'l01');
			
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
				$folderPath .= $fileName.'/';
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
			if($options instanceof WigiiBPLParameter) {
				$language = $options->getValue('language');
			}
			// else default options
			else {
				$language = 'l01';
				$options = wigiiBPLParam('language',$language);
			}
			$options->setValue('url',$url);
			
			// lookups groupId associated to url
			$groupId = $this->evaluateFuncExp(fx('cms_getGroupIdForUrl',$url),$this);
			if(empty($groupId)) throw new FuncExpEvalException("No content found at $url", FuncExpEvalException::NOT_FOUND);			
			else $options->setValue('groupId',$groupId);
			// gets languages
			$transS = ServiceProvider::getTranslationService();
			$languages = $transS->getVisibleLanguage();
			
			// gets page title and intro
			$intro = $this->cms_getIntro($options);
			if(isset($intro)) {			
				$title = $intro->getFieldValue('siteTitle');
				if(is_array($title)) $title = $title[$language];
				$options->setValue('title',$title);
				$intro = $intro->getFieldValue('contentIntro');
				if(is_array($intro)) $intro = $intro[$language];
			}
			// gets CSS definitions
			$css = $this->cms_getCSS($options);
			if(isset($css)) $options->setValue('css',$css);
			// gets JS code			
			$js = $this->cms_getJSCode($options);
			//gets page footer
			$footer = $this->cms_getFooter($options);
			// top link
			$atopLink = '<a href="./'.$language.'#top">∧ '.$transS->t($principal,"cmsAnchorTop",null,$language).'</a>';
			
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
						fx('htmlStartTag','p','style','text-align:center;'),fx('htmlStartTag','span','class','wigii-cms content-sep'),$this->cms_getArticleSep($options),fx('htmlEndTag','span'),fx('htmlEndTag','p'),
						fx('htmlEndTag','div'),"\n",
					fx('htmlEndTag','div'),"\n")
				),
				dfas('StringSepDFA','setHeader',$this->cms_getHtmlHeader($options)."\n<body>\n".
				'<div class="wigii-cms">'."\n".
				'<div class="wigii-cms title" id="top"><div class="wigii-cms title-content">&nbsp;</div><div class="wigii-cms a-top">'.$this->cms_getLanguageMenu($options).'</div></div>'."\n".
				'<div class="wigii-cms content">'.(empty($intro)?'&nbsp;':$intro).'</div>'."\n".
				'</div>'."\n",
				'setFooter',"\n".
				'<div class="wigii-cms">'."\n".
				'<div class="wigii-cms title" id="bottom"><div class="wigii-cms title-content">&nbsp;</div><div class="wigii-cms a-top">'.$atopLink.'</div></div>'."\n".
				'<div class="wigii-cms content">'.(empty($footer)?'&nbsp;':$footer).'</div>'."\n".
				'</div>'."\n".
				(!empty($js)?"<script>".$js."</script>\n":'')."</body>\n</html>",'setSeparator',"\n"),
				dfas('EchoDFA')
				)
			);
		}
		catch(Exception $e) {
			$this->executionSink()->publishEndOperationOnError("cms_getContent", $e, $principal);
			throw $e;
		}
		$this->executionSink()->publishEndOperation("cms_getContent", $principal);
		return $returnValue;
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
<base href=".$url" />
$title
<meta name="Copyright" content="Source Code: 2016 Wigii.org" />
<meta name="License" content="GNU GPL 3.0" />
<meta name="Generator" content="Wigii-system" />
<meta name="Description" content="Wigii is a web based system allowing management of any kind of data (contact, document, calendar, and any custom types). Find out documentation on http://www.wigii-system.net" />
<meta http-equiv="content-type" content="text/html;charset=utf-8" />
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
		$returnValue = <<<HTMLCSS
html, body { height:100%; padding: 0px; margin:0px; font-family:arial; }
a { text-decoration: none;}
a:hover { text-decoration: underline; }
div.wigii-cms {
	width:100%;
	box-sizing:border-box;
}
div.wigii-cms.title {
	color:#696969;
	font-size:24px;	
}
div.wigii-cms.title-content {
	float:left;	
	width:80%;
	padding-left:50px;
	
}
div.wigii-cms.a-top {
	float:right;
	width:20%;	
	margin-top:24px;
	margin-bottom:24px;
	font-size:small;
	text-align:right;
	padding-right:50px;
}
div.wigii-cms.a-top>a {
	color:#696969;	
}
div.wigii-cms.content {
	clear:left;
	color: #696969;
	margin:0px;
	padding-left:50px;	
}
span.wigii-cms.content-sep {
	font-size:22px;
	font-weight:strong;
	color: #646EFF;
}
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
		return "⌘ ⌘ ⌘ ";
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
		foreach($languages as $lan=>$language) {
			if($returnValue) $returnValue .= '&nbsp;|&nbsp;';
			$returnValue .= '<a href="./'.$lan.'">'.$language.'</a>';
		}
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
			case "content": $returnValue = fsl(fs("choosePosition"),fs("contentPosition"),fs("contentTitle"),fs("contentHTML")); break;
			case "siteMap": $returnValue = fsl(fs("siteUrl"),fs("folderId"),fs("siteMap")); break;
			case "intro": $returnValue = fsl(fs("siteTitle"),fs("contentIntro")); break;
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