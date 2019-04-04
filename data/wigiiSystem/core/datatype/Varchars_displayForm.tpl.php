<?php
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
$inputId = $formId.'_'.$fieldName;

//do not purify when displaying in form, but keep htmlspecialchars
$val = $this->formatValueToPreventInjection($this->getRecord()->getFieldValue($fieldName));

$id = $formId."_".$fieldName;
$height = 150;

$this->put('<div id="'.$id.'_tabs" class="multilanguage" style="');

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
	$lis[] = "<li><a style='font-size:x-small;' href='#".$id."_value_".$idLang."'>".$languageLabel."</a></li>";
	$tab = "";
	$tab .= "<div id='".$id."_value_".$idLang."' >";
	$tab .= "<div class='value' style='width:100%;'><input type='text' id='".$id."_value_".$idLang."_text' name='".$fieldName."_value[".$idLang."]' ";
	if($disabled) $tab .=' disabled ';
	if($readonly) $tab .= ' disabled class="removeDisableOnSubmit" ';
	$tab .=' style="width: 100%; max-width:'.($parentWidth).'px; ';
	if($readonly || $disabled) $tab .='color:#a9a9a9; background-color:#E3E3E3;';
	$tab .='" value="';
	$tab .= $val[$idLang];
	$tab .='" /></div>';
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






