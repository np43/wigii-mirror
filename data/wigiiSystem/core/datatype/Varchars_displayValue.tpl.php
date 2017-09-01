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
 * Created on 3 déc. 09
 * by LWR
 */

$val = $this->formatValueFromRecord($fieldName, null, $this->getRecord());

$crtLanguage = $this->getTranslationService()->getLanguage();

/*
 * Notification case:
 * display values without need of JS
 */
if($this->isForNotification()){
	$tab = array();
	foreach ($visibleLanguage as $idLang=>$languageLabel){
		$tab[] = $languageLabel." : ".$val[$idLang];
	}
	$this->put(implode("<br>", $tab));

/*
 * Normal case, create some tabs per language
 */
} else {

	$id = "elementDetail_".$fieldName;

	$this->put('<div id="'.$id.'_tabs" class="multilanguage" style="');

	if($parentWidth != null){
		$this->put(' width: 100%; max-width:'.$parentWidth.'px; float:left; ');
		if($fieldXml["isInLine"]=="1") $this->put(' clear:left; ');
	}
	$this->put('">');

	$texts = array();
	$lis = array();
	$ind=0;
	foreach ($visibleLanguage as $idLang=>$languageLabel){
		$lis[] = "<li><a style='font-size:x-small;' href='#".$id."_value_".$idLang."'>".$languageLabel."</a></li>";
		$tab = "";
		$tab .= "<div id='".$id."_value_".$idLang."' >";
		if(!$val[$idLang]){
			$tempVal = '<font class="grayFont">'.$this->t("emptyContentMultipleLanguage").'</font>';
		} else {
			$tempVal = $val[$idLang];
		}
		$tab .= "<div class='value' style='padding-top:5px;width: 100%; max-width:".($parentWidth-12)."px;' >".$tempVal."</div>";
		$tab .= "</div>";
		$texts[] = $tab;
		
		if($this->getTranslationService()->getLanguage()==$idLang) $selectedTabIndex = $ind;
		$ind++;
	}

	$this->put("<ul>".implode("", $lis)."</ul>");
	$this->put(implode("", $texts));

	$this->put('</div>');

	$this->getExecutionService()->addJsCode("$('#".$id."_tabs').tabs({ active: $selectedTabIndex});");

}


