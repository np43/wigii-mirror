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

/*
 * Created on 4 déc. 09
 * by LWR
 */

$fieldXml = $field->getXml();

$readonly = $this->getRecord()->getWigiiBag()->isReadonly($fieldName);
$disabled = $this->getRecord()->getWigiiBag()->isDisabled($fieldName);
$isFilled = $this->getRecord()->getWigiiBag()->isFilled($fieldName);
$isPublicPrincipal = ServiceProvider::getAuthorizationService()->isPublicPrincipal($this->getP());
$isRequire = 	$fieldXml["require"]=="1" && !$isPublicPrincipal ||
				$isPublicPrincipal && $fieldXml["requireInPublic"]=="1" ||
				$isPublicPrincipal && $fieldXml["require"]=="1" && $fieldXml["requireInPublic"]!="0";
$isNotExpanded = !$isFilled && $fieldXml["expand"]!="1" && (!$isRequire || $fieldXml["expand"]=="0");

$val = $this->formatValueToPreventInjection($this->getRecord()->getFieldValue($fieldName));

$id = $formId."_".$fieldName;
$height = 150;
if($fieldXml["height"]!=null) $height = $fieldXml["height"];


$this->put('<div id="'.$id.'_tabs" class="multilanguage" style="');
if($isNotExpanded){
	$this->put(' display:none; ');
}
if($parentWidth != null){
	$this->put(' width: 100%; max-width:'.($parentWidth).'px; float:left; ');
	if($fieldXml["isInLine"]=="1") $this->put(' clear:left; ');
}
$this->put('">');

$texts = array();
$lis = array();
$selectedTabIndex = 0;
$ind = 0;
foreach ($visibleLanguage as $idLang=>$languageLabel){
	$languageLabel = str_replace("'", "&#39;", $languageLabel);
	$lis[] = "<li><a style='font-size:x-small;' href='#".$id."_value_".$idLang."'>".$languageLabel."</a></li>";
	$tab = "";
	$tab .= "<div id='".$id."_value_".$idLang."' >";
	//since jquery ui 1.10 no more need of this launchHtmlArea button
	if(false && $fieldXml["htmlArea"]=="1" && $fieldXml["activateHtmlArea"]!="1") {
		$tab .= '<div style="width: 100%; max-width:18px; height:18px; border: 1px solid #AAA; clear:both; padding:2px; background:#FFFFFF url('.SITE_ROOT_forFileUrl.'/assets/css/jHtmlArea_Toolbar_Group_BG.png) repeat-x scroll 0 0;"><div class="launchHtmlArea" style=" width: 100%; max-width:width:16px; height:16px; cursor:pointer; background:url('.SITE_ROOT_forFileUrl.'/assets/css/jHtmlArea.png) no-repeat scroll -16px -500px; background-position:-192px 0px;"></div></div>';
	}
	$tab .= "<div class='value' style='width: 100%; max-width:".($parentWidth)."'><textarea id='".$id."_value_".$idLang."_textarea' name='".$fieldName."_value[".$idLang."]' ";
	$tab .= ' class="';
	if($readonly) $tab .= 'removeDisableOnSubmit ';
	if($fieldXml["htmlArea"]=="1") $tab .= 'htmlArea ';
	if($fieldXml["fullToolbarHtmlArea"]=="1") $tab .= 'fullToolbarHtmlArea ';
	if($fieldXml["mediumToolbarHtmlArea"]=="1") $tab .= 'mediumToolbarHtmlArea ';
	if($fieldXml["activateHtmlArea"]=="1") $tab .= 'activateHtmlArea ';
	if((string)$fieldXml["wordlimit"]>0) $this->put('wordlimit wordlimit_'.(string)$fieldXml["wordlimit"].' ');
	if($fieldXml["height"]!=null) $this->put('difH ');
	$tab .= '" ';
	if($disabled || $readonly) $tab .=' disabled ';
	$tab .=' style="width: 100%; max-width:'.($parentWidth).'px; max-height:'.$height.'px; overflow-y:auto;';
	if($readonly || $disabled) $tab .='color:#a9a9a9; background-color:#E3E3E3;';
	$tab .='" >';
//	$tab .= str_replace('<', '&lt;', str_replace('>', '&gt;', $this->doFormatForHtmlText($val[$idLang])));
	$tab .= $val[$idLang];
	$tab .='</textarea></div>';
	$tab .= "</div>";
	$texts[] = $tab;

	if($this->getTranslationService()->getLanguage()==$idLang) $selectedTabIndex = $ind;
	$ind++;
}

$this->put("<ul>".implode("", $lis)."</ul>");
$this->put(implode("", $texts));

$this->put('</div>');

if($_POST["action"] =="check"){
	$setCook = " cookie_form_".$id."_tabs = parseInt($.cookie('cookie_form_".$id."_tabs')); ";
} else {
	$setCook = " cookie_form_".$id."_tabs = $selectedTabIndex; ";
}
$this->getExecutionService()->addJsCode("
$setCook
$('#".$id."_tabs').tabs({ cookie: { path: '/', expires: 30 }, active: cookie_form_".$id."_tabs, show:function(e,ui){ $.cookie('cookie_form_".$id."_tabs', ui.index); } });
");





