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

/*
 * Created on 21 July 2011
 * by LWR
 */
if(!isset($transS)) $transS = ServiceProvider::getTranslationService();
if(!isset($sessAS)) $sessAS = ServiceProvider::getSessionAdminService();
if(!isset($lc)) $lc = $lc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
?><div class="firstBox"><?
	?><input class="SBIB R" type="text" <?
	?>name="<?=ListContext::TextSearchField;?>" value="<?=$lc->getTextSearch();?>" /><?
	?><div class="searchButtons SBIB"><span id="filtersButton" class="grayFont H" <?
		?>onmouseover="showHelp(this, '<?=str_replace('"', '&quot;', str_replace("'", '&rsquo;', $transS->t($p, "advancedFiltersHelp")));?>', 30, 'fromLeft');" onmouseout="hideHelp();" <?
	?>><font style="font-size:12px;font-weight:normal;"><?=$transS->t($p, "advancedFiltersButtonText");?></font></span><span id="removeFiltersButton" class="H grayFont" <?
		?>onmouseover="showHelp(this, '<?=str_replace('"', '&quot;', str_replace("'", '&rsquo;', $transS->t($p, "removeFilters")));?>', 30, 'fromLeft');" onmouseout="hideHelp();" <?
	?>>X</span></div><?
	?><div id="goForSearch" class="H G SBIB" ><?=$transS->t($p, "searchBarGoButtonText");?></div><?
?></div><?

?><div class="toolbarBox"><?

	//when the searchBar is reloaded, then clear the last
	$sessAS->clearData($this, "elementListLastConfigKey");
	// module help button
	$this->includeModuleHelpAnchor($p,$exec);
	// list refresh button
	?><div class="refresh H"><?=$transS->t($p, "refresh");?></div><?
	//in some views, the sortBy and groupBy are not relevant. In this case there are hidden
	//groupBy
	?><div class="groupBy disabledR"><?=$transS->t($p, "groupBy");?>:&nbsp;<div class="SBB H disabledBg value"><?=$transS->t($p, "selectInList");?></div></div><?
	//sortBy
	?><div class="sortBy disabledR"><?=$transS->t($p, "sortBy");?>:<div class="H disabledBg"><div class="direction H ASC"></div><div class="SBB value H"><?=$transS->t($p, "selectInList");?></div></div></div><?

	//add element
	?><div class="addNewElement ui-corner-all disabledBg">+ <font><?=$transS->h($p, "addElementButton");?></font></div><?

	//switch view
	?><div class="switchView H L disabledR"><?=$transS->t($p, $moduleView."View");?></div><?

?></div><?

?><div class="clear"></div><?

$exec->addJsCode("setListenersToFilters(); setFiltersButton(".($lc->getSearchBar() ? 'true' : 'false').");");
// adds refresh js code
$exec->addJsCode('$("#searchBar div.toolbarBox div.refresh").click(function(){invalidCache("moduleView"); invalidCache("elementDialog"); update("moduleView/"+crtWigiiNamespaceUrl+"/"+crtModuleName+"/display/moduleView");});');
// bind Wigii HelpService to moduleHelp
$exec->addJsCode('$("#searchBar div.toolbarBox div.moduleHelp .wigiiHelp").wigii("bindHelpService");');


