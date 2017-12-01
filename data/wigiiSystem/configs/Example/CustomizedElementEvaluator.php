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
// version G99
/**
 * My customized ElementEvaluator
 * Created by LWR on 28 may 10
 * Updated by CWE on 4 decembre 13 to refactor automatic translations of values
 * Updated by LWR on 03.10.2017 (remove automatic translation for funcExp evaluation)
 * Updated by Medair (CWE) on 03.10.2017 to include Wigii online help system
 */
class CustomizedElementEvaluator extends ElementEvaluator
{
	
	private $myTrm;
	/**
	 * A template record manager that can be used to do some formatting on demand
	 */
	private function getTrm(){
		if(!isset($this->myTrm)){
			$this->myTrm = TemplateRecordManager::createInstance();
		}
		return $this->myTrm;
	}	
	
// 	/**
// 	 * If you need to translate/format all Field Selector values customize the evaluateRecord function 
// 	 * in the following way.
// 	 * @see impl/RecordEvaluator::evaluateFuncExp()
// 	 */
// 	public function evaluateFuncExp($funcExp, $caller=null) {
// 		$this->setTranslateAllValues(true);
// 		return parent::evaluateFuncExp($funcExp, $caller);
// 	}
	
	
	
	// Wigii contextual help system
	
	/**
	 * Returns Wigii contextual help url portion.
	 */
	protected function getWigiiHelpUrl() {
	    return 'Wigii_HelpService/User%20guide/Filemanager/help/item/';
	}
	/**
	 * Puts a help icon besides a field.
	 * Can be used in divExp,divInFormExp or divInDetailExp.
	 * FuncExp signature : <code>wigiiHelp(elementId,displayOption,divExp)</code><br/>
	 * Where arguments are :
	 * - Arg(0) elementId: Int. The ID of element containing Wigii contextual help.
	 * - Arg(1) displayOption: String. Classifies the type of contextual help window. One of "small","medium","large". Defaults to "medium".
	 * - Arg(2) divExp: Array. The current divExp array for chaining other DIV in field.
	 * @return Array the DIV exp array.
	 */
	public function wigiiHelp($args) {
	    $nArgs = $this->getNumberOfArgs($args);
	    
	    if($nArgs>0) $elementId = $this->evaluateArg($args[0]);
	    else $elementId = 0;
	    
	    if($nArgs>1) {
	        $displayOptions = $this->evaluateArg($args[1]);
	        switch($displayOptions) {
	            case 'small':break;
	            case 'medium': break;
	            case 'large': break;
	            default: $displayOptions='medium';
	        }
	    }
	    else $displayOptions = 'medium';
	    
	    if($nArgs>2) {
	        $divExp = $this->evaluateArg($args[2]);
	        if(is_null($divExp)) $divExp = array();
	        elseif(!is_array($divExp)) throw new RecordException('divExp should evaluate to an array', RecordException::INVALID_ARGUMENT);
	    }
	    else $divExp = array();
	    
	    $helpOptions = array();
	    $helpOptions['content'] = $this->getWigiiHelpUrl().$elementId.'/integratedFile';
	    switch($displayOptions) {
	        case 'small':
	            $helpOptions['data-popup-height'] = 150;
	            $helpOptions['data-popup-width'] = 350;
	            break;
	        case 'medium':
	            $helpOptions['data-popup-height'] = 400;
	            $helpOptions['data-popup-width'] = 700;
	            break;
	        case 'large':
	            $helpOptions['data-popup-height'] = 600;
	            $helpOptions['data-popup-width'] = 1000;
	            break;
	    }
	    
	    $divExp['wigiiHelp remoteContent'] = $helpOptions;
	    
	    return $divExp;
	}
	/**
	 * Puts a help icon in an element list view.
	 * Can be used only in module config parameter WigiiHelp_onModule.
	 * FuncExp signature : <code>wigiiHelpOnModule(elementId,displayOption)</code><br/>
	 * Where arguments are :
	 * - Arg(0) elementId: Int. The ID of element containing Wigii contextual help.
	 * - Arg(1) displayOption: String. Classifies the type of contextual help window. One of "small","medium","large". Defaults to "medium".
	 * @return Array the DIV exp array for WigiiHelp_onModule module config parameter.
	 */
	public function wigiiHelpOnModule($args) {
	    $nArgs = $this->getNumberOfArgs($args);
	    
	    if($nArgs>0) $elementId = $this->evaluateArg($args[0]);
	    else $elementId = 0;
	    
	    if($nArgs>1) {
	        $displayOptions = $this->evaluateArg($args[1]);
	        switch($displayOptions) {
	            case 'small':break;
	            case 'medium': break;
	            case 'large': break;
	            default: $displayOptions='medium';
	        }
	    }
	    else $displayOptions = 'medium';
	    
	    $helpOptions = array();
	    $helpOptions['content'] = $this->getWigiiHelpUrl().$elementId.'/integratedFile';
	    switch($displayOptions) {
	        case 'small':
	            $helpOptions['data-popup-height'] = 200;
	            $helpOptions['data-popup-width'] = 600;
	            break;
	        case 'medium':
	            $helpOptions['data-popup-height'] = 400;
	            $helpOptions['data-popup-width'] = 1000;
	            break;
	        case 'large':
	            $helpOptions['data-popup-height'] = 600;
	            $helpOptions['data-popup-width'] = 1300;
	            break;
	    }
	    return $helpOptions;
	}
	/**
	 * Puts a help icon in an element add dialog.
	 * Can be used only in module config parameter WigiiHelp_onAdd.
	 * FuncExp signature : <code>wigiiHelpOnAdd(elementId,displayOption)</code><br/>
	 * Where arguments are :
	 * - Arg(0) elementId: Int. The ID of element containing Wigii contextual help.
	 * - Arg(1) displayOption: String. Classifies the type of contextual help window. One of "small","medium","large". Defaults to "medium".
	 * @return Array the DIV exp array for WigiiHelp_onAdd module config parameter.
	 */
	public function wigiiHelpOnAdd($args) {
	    $nArgs = $this->getNumberOfArgs($args);
	    
	    if($nArgs>0) $elementId = $this->evaluateArg($args[0]);
	    else $elementId = 0;
	    
	    if($nArgs>1) {
	        $displayOptions = $this->evaluateArg($args[1]);
	        switch($displayOptions) {
	            case 'small':break;
	            case 'medium': break;
	            case 'large': break;
	            default: $displayOptions='medium';
	        }
	    }
	    else $displayOptions = 'medium';
	    
	    $helpOptions = array();
	    $helpOptions['content'] = $this->getWigiiHelpUrl().$elementId.'/integratedFile';
	    switch($displayOptions) {
	        case 'small':
	            $helpOptions['data-popup-height'] = 200;
	            $helpOptions['data-popup-width'] = 400;
	            break;
	        case 'medium':
	            $helpOptions['data-popup-height'] = 400;
	            $helpOptions['data-popup-width'] = 700;
	            break;
	        case 'large':
	            $helpOptions['data-popup-height'] = 600;
	            $helpOptions['data-popup-width'] = 1000;
	            break;
	    }
	    
	    return $helpOptions;
	}
	/**
	 * Puts a help icon in an element edit dialog.
	 * Can be used only in module config parameter WigiiHelp_onEdit.
	 * FuncExp signature : <code>wigiiHelpOnEdit(elementId,displayOption)</code><br/>
	 * Where arguments are :
	 * - Arg(0) elementId: Int. The ID of element containing Wigii contextual help.
	 * - Arg(1) displayOption: String. Classifies the type of contextual help window. One of "small","medium","large". Defaults to "medium".
	 * @return Array the DIV exp array for WigiiHelp_onEdit module config parameter.
	 */
	public function wigiiHelpOnEdit($args) {
	    $nArgs = $this->getNumberOfArgs($args);
	    
	    if($nArgs>0) $elementId = $this->evaluateArg($args[0]);
	    else $elementId = 0;
	    
	    if($nArgs>1) {
	        $displayOptions = $this->evaluateArg($args[1]);
	        switch($displayOptions) {
	            case 'small':break;
	            case 'medium': break;
	            case 'large': break;
	            default: $displayOptions='medium';
	        }
	    }
	    else $displayOptions = 'medium';
	    
	    $helpOptions = array();
	    $helpOptions['content'] = $this->getWigiiHelpUrl().$elementId.'/integratedFile';
	    switch($displayOptions) {
	        case 'small':
	            $helpOptions['data-popup-height'] = 200;
	            $helpOptions['data-popup-width'] = 400;
	            break;
	        case 'medium':
	            $helpOptions['data-popup-height'] = 400;
	            $helpOptions['data-popup-width'] = 700;
	            break;
	        case 'large':
	            $helpOptions['data-popup-height'] = 600;
	            $helpOptions['data-popup-width'] = 1000;
	            break;
	    }
	    
	    return $helpOptions;
	}
	/**
	 * Binds wigii contextual help to html anchor tags in labels or free texts having the 'wigiiHelp' class.
	 * This function should be called in Example/config.xml, in config parameter jsCodeAfterShowExp and jsCodeForListExp.
	 * FuncExp signature : <code>wigiiHelpBinder(target=null)</code><br/>
	 * Where arguments are :
	 * - Arg(0) target: String. Optional target from which to search for help anchors. Defaults to token $$idForm$$
	 * @return String js code to be added to the jsCodeAfterShowExp and jsCodeForListExp.
	 */
	public function wigiiHelpBinder($args) {
	    $nArgs = $this->getNumberOfArgs($args);
	    if($nArgs>0) $target = $this->evaluateArg($args[0]);
	    else $target = '$$idForm$$';
	    
	    return '(function(){
var url = "'.$this->getWigiiHelpUrl().'";
var transform = function() {
	var e = $(this);
	var size = e.attr("data-wigiihelp");
	switch(size) {
	case "small":
		e.attr("data-popup-height",150);
		e.attr("data-popup-width",350);
		break;
	case "large":
		e.attr("data-popup-height",600);
		e.attr("data-popup-width",1000);
		break;
	case "medium":
	default:
		e.attr("data-popup-height",400);
		e.attr("data-popup-width",600);
	}
	e.attr("href",url+e.attr("href")+"/integratedFile");
};
$("#'.$target.' a.wigiiHelp").each(transform);
})();
	';
	}
}