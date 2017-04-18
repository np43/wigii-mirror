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

/***
 * Created by LWR, on 21 July 2011
 */

//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."start elementBlog.tpl.php"] = microtime(true);
$this->executionSink()->publishStartOperation("TEMPLATE elementBlog.tpl.php");

if(!isset($groupAS)) $groupAS = ServiceProvider::getGroupAdminService();
if(!isset($sessAS)) $sessAS = ServiceProvider::getSessionAdminService();
if(!isset($elS)) $elS = ServiceProvider::getElementService();
if(!isset($transS)) $transS = ServiceProvider::getTranslationService();
if(!isset($exec)) $exec = ServiceProvider::getExecutionService();
if(!isset($configS)) $configS= $this->getConfigurationContext();

$lc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList"); //keep the same context than the list, ordering, filtering, group by is shared
//hide the importElement and addNewELement icon if the group is not writable:
$crtGroupIsWritable = false;
$crtGroupP = null;
if($lc->getGroupPList()->count()==1){
	$crtGroupP = reset($lc->getGroupPList()->getListIterator());
	if($crtGroupP != null && $crtGroupP->getRights()!=null && $crtGroupP->getRights()->canShareElement()){
		$crtGroupIsWritable = true;
	}
}

$crtWigiiNamespace = $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl();
$crtModule = $exec->getCrtModule()->getModuleUrl();

$lc->setDesiredPageNumber(1);
$pageSize = (int)$configS->getParameter($p, $exec->getCrtModule(), "listPageSize");
if($pageSize == null) $pageSize = 250; //default value
$lc->setPageSize($pageSize);
//$lc->resetPagination();


//Hide searchbox when parameters is equal to 
if($configS->getParameter($p, $exec->getCrtModule(), "hide_searchBar") == "1"){
	$exec->addJsCode("$('#searchBar > .firstBox, #searchBar > .toolbarBox').hide();");
} else {
	$exec->addJsCode("$('#searchBar > .firstBox, #searchBar > .toolbarBox').show();");
}

//add toolbar

/**
 * URL portal zone
 * if a portal is set on the group, then display the content of the website
 */
$url = null;
if($configS->getParameter($p, $exec->getCrtModule(), "Group_enablePortal") == "1"){
	if($crtGroupP){
		//we need to fetch the group details
		$crtGroup = $groupAS->getGroup($p, $crtGroupP->getId());
		if($crtGroup->getGroup()->getDetail()!=null){ //if detail = null, then do nothing

			$portalRec = $this->createActivityRecordForForm($p, Activity::createInstance("groupPortal"), $exec->getCrtModule());
			$portalRec->getWigiiBag()->importFromSerializedArray($crtGroup->getGroup()->getDetail()->getPortal(), $portalRec->getActivity());
			$url = $portalRec->getFieldValue("url", "url");
			// evaluates any given FuncExp 
			$url = $this->evaluateConfigParameter($p,$exec,$url);
			if(!empty($url)){
				$cooKieName = $portalRec->getFieldValue("groupPortalCookieName");
				if($portalRec->getFieldValue("groupPortalCookieIncludeRoles")){
					$roleList = $p->getRoleListener()->getRolesPerWigiiNamespaceModule($exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl(), $exec->getCrtModule()->getModuleUrl());
					if($roleList) $roleList = implode(";", $roleList);
				} else $roleList = null;
				if($cooKieName){
					$result = getExternalCookieConnectionValue($portalRec->getFieldValue("groupPortalCookieSeparator"), $p->getRealUsername(), $portalRec->getFieldValue("groupPortalCookieCustomParameter"), $portalRec->getFieldValue("groupPortalCookieExpiration"), $roleList, $portalRec->getFieldValue("groupPortalCookieEncrypt"), $portalRec->getFieldValue("groupPortalCookieRotationKey"));
					$exec->addJsCode("$.cookie('".$cooKieName."', '".$result."', { path: '/', domain: '".$portalRec->getFieldValue("groupPortalCookieDomain")."', secure: ".strtolower(put(HTTPS_ON))." }); ");
				}
				?><div class="portal" style="overflow:hidden; "><?
					if(!preg_match('#^(((ht|f)tp(s?))\://)#i', $url)) $url = "http://".$url;
					?><a class="media {type:'html'}" href="<?=$url;?>" ></a><?
				?></div><?
				$exec->addJsCode("$('#moduleView .portal a.media').media();");
			}
		//end if detail != null
		}
	}
}

if(!$url){ //display list only if no url

?><div class="toolBar"><?
	//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."start searchBar toolbar"] = microtime(true);
	$this->executionSink()->log("start searchBar toolbar");
	//reload if search bar is reloaded
	$lastConfigKey = $sessAS->getData($this, "elementListLastConfigKey");
	$currentConfigKey = $this->getCurrentConfigContextKey($p, $exec);
	$sessAS->storeData($this, "elementListLastConfigKey", $currentConfigKey);

	//sortByCaching in session
	$fieldSelectorList = $sessAS->getData($this, $currentConfigKey."_sortByFieldselectorList");
	$sortByOptions = $sessAS->getData($this, $currentConfigKey."_sortByOptions");
	if($fieldSelectorList==null){
		//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."start sortBy"] = microtime(true);
		$this->debugLogger()->write("start sortBy");
		//sortBy
		//look if a groupBy activity is defined. Otherwise just list all available fields
		$sortByActivity = Activity :: createInstance("sortBy");
		$sortByOptions = array();
		if($configS->ma($p, $exec->getCrtModule(), $sortByActivity)){
			//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."gather activity config"] = microtime(true);
			$this->debugLogger()->write("gather activity config");
			$fieldSelectorList = FieldSelectorListForActivity :: createInstance(false, false, true); //no issue if double time the same
			$fieldSelectorList->setSelectedLanguages(array ($transS->getLanguage() => $transS->getLanguage()));
			$configS->getFields($p, $exec->getCrtModule(), $sortByActivity, $fieldSelectorList);
			$fieldList = FormFieldList :: createInstance(null);
			$configS->getFields($p, $exec->getCrtModule(), null, $fieldList);
			//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."config gathered"] = microtime(true);
			$this->debugLogger()->write("config gathered");
			$trmTemp = $this->createTRM(null, false, false, false, true, false, false); //we want to be able to buffer the result
			//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."build sortByOptionArray for activity"] = microtime(true);
			$this->debugLogger()->write("build sortByOptionArray for activity");
			foreach($fieldSelectorList->getListIterator() as $key=>$fieldSelector){
				if($fieldSelector->isElementAttributeSelector()) $fieldXml = null;
				else $fieldXml = $fieldList->getField($fieldSelector->getFieldName())->getXml();
				$xmlHeader = $fieldSelectorList->getXml($key);
				$trmTemp->displayHeaderLabel($fieldSelector, $fieldXml, $xmlHeader, true);
				$label = $trmTemp->getHtmlAndClean();
				$sortByOptions[$key] = $label;
			}
			//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."build sortByOptionArray for activity"] = microtime(true);
			$this->debugLogger()->write("build sortByOptionArray for activity");
		} else {
			//create the fieldSelectorList for all fields and for element info
			//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."gather all config"] = microtime(true);
			$this->debugLogger()->write("gather all config");
			$elementFieldsXml = $configS->mf($p, $exec->getCrtModule());
			//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."config gathered"] = microtime(true);
			$fieldSelectorList = FieldSelectorListArrayWebImpl::createInstance(false, false);
			//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."build sortByOptionArray for fields"] = microtime(true);
			foreach($elementFieldsXml->children() as $elementFieldXml){
				if($lc->byPassDataTypeForSortByFilter($elementFieldXml["type"])) continue;
				$subFieldNames = $lc->defineDataTypeSubFieldForSortByFilter($elementFieldXml["type"]);
				foreach($subFieldNames as $subFieldName){
					$key = $fieldSelectorList->addFieldSelector($elementFieldXml->getName(), $subFieldName);
					$label = $transS->t($p, $elementFieldXml->getName(), $elementFieldXml);
					if($subFieldName != "" && $subFieldName != "value") $label .= " ".$transS->t($p, $elementFieldXml["type"]."_".$subFieldName);
					$sortByOptions[$key] = $label;
				}
			}
			//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."build sortByOptionArray for subFields"] = microtime(true);
			//add the element infos
			$subFieldNames = $lc->defineElementSubFieldForSortByFilter();
			foreach($subFieldNames as $subFieldName){
				$fieldSelector = FieldSelector::createElementAttributeSelector($subFieldName);
				$key = $fieldSelectorList->addFieldSelectorInstance($fieldSelector);
				$label = $transS->t($p, $subFieldName);
				$sortByOptions[$key] = $label;
			}
			//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."sortByOptionArray built"] = microtime(true);
		}
		$sessAS->storeData($this, $currentConfigKey."_sortByFieldselectorList", $fieldSelectorList);
		$sessAS->storeData($this, $currentConfigKey."_sortByOptions", $sortByOptions);
		//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."sortBy stored in session"] = microtime(true);
		$this->debugLogger()->write("sortBy stored in session");
	} else {
//		fput("found ".$currentConfigKey."_sortBy"." in session!");
	}
	$lc->setSortByFieldSelectorList($fieldSelectorList);
	$exec->addJsCode("" .
			"$('#searchBar .toolbarBox .sortBy').removeClass('disabledR');" .
			"$('#searchBar .toolbarBox .sortBy .SBB').removeClass('SBB').addClass('SBIB');" .
			"$('#searchBar .toolbarBox .sortBy div.disabledBg').removeClass('disabledBg');");

	//groupBy
	$fieldSelectorList = $sessAS->getData($this, $currentConfigKey."_groupByfieldselectorList");
	$groupByOptions = $sessAS->getData($this, $currentConfigKey."_groupByOptions");
	if($fieldSelectorList==null){
		//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."start groupBy"] = microtime(true);
		//look if a groupBy activity is defined. Otherwise just list all available fields
		$groupByActivity = Activity :: createInstance("groupBy");
		$groupByOptions = array();
		if($configS->ma($p, $exec->getCrtModule(), $groupByActivity)){
			//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."gather activity config"] = microtime(true);
			$fieldSelectorList = FieldSelectorListForActivity :: createInstance(false, false, true); //no issue if double time the same
			$fieldSelectorList->setSelectedLanguages(array ($transS->getLanguage() => $transS->getLanguage()));
			$configS->getFields($p, $exec->getCrtModule(), $groupByActivity, $fieldSelectorList);
			//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."config gathered"] = microtime(true);
			$fieldList = FormFieldList :: createInstance(null);
			$configS->getFields($p, $exec->getCrtModule(), null, $fieldList);
			$trmTemp = $this->createTRM(null, false, false, false, true, false, false); //we want to be able to buffer the result
			//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."build groupByOptionArray for fields"] = microtime(true);
			foreach($fieldSelectorList->getListIterator() as $key=>$fieldSelector){
				if($fieldSelector->isElementAttributeSelector()) $fieldXml = null;
				else $fieldXml = $fieldList->getField($fieldSelector->getFieldName())->getXml();
				$xmlHeader = $fieldSelectorList->getXml($key);
				$trmTemp->displayHeaderLabel($fieldSelector, $fieldXml, $xmlHeader, true);
				$label = $trmTemp->getHtmlAndClean();
				$groupByOptions[$key] = $label;
			}
			//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."build sortByOptionArray for activity"] = microtime(true);
		} else {
			//create the fieldSelectorList for all fields and for element info
			//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."gather all config"] = microtime(true);
			$elementFieldsXml = $configS->mf($p, $exec->getCrtModule());
			//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."config gathered"] = microtime(true);
			$fieldSelectorList = FieldSelectorListArrayWebImpl::createInstance(false, false);
			//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."build groupByOptionArray for fields"] = microtime(true);
			foreach($elementFieldsXml->children() as $elementFieldXml){
				if($lc->byPassDataTypeForGroupByFilter($elementFieldXml["type"])) continue;
				if($elementFieldXml["groupable"]=="0") continue;
				$subFieldNames = $lc->defineDataTypeSubFieldForGroupByFilter($elementFieldXml["type"]);
				foreach($subFieldNames as $subFieldName){
					$key = $fieldSelectorList->addFieldSelector($elementFieldXml->getName(), $subFieldName);
					$label = $transS->t($p, $elementFieldXml->getName(), $elementFieldXml);
					if($subFieldName != "" && $subFieldName != "value") $label .= " ".$transS->t($p, $elementFieldXml["type"]."_".$subFieldName);
					$groupByOptions[$key] = $label;
				}
			}
			//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."build groupByOptionArray for subFields"] = microtime(true);
			//add the element infos
			$subFieldNames = $lc->defineElementSubFieldForGroupByFilter();
			foreach($subFieldNames as $subFieldName){
				$fieldSelector = FieldSelector::createElementAttributeSelector($subFieldName);
				$key = $fieldSelectorList->addFieldSelectorInstance($fieldSelector);
				$label = $transS->t($p, $subFieldName);
				$groupByOptions[$key] = $label;
			}
			//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."groupByOptionArray built"] = microtime(true);
		}
		$sessAS->storeData($this, $currentConfigKey."_groupByfieldselectorList", $fieldSelectorList);
		$sessAS->storeData($this, $currentConfigKey."_groupByOptions", $groupByOptions);
		//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."groupBy stored in session"] = microtime(true);
		$this->debugLogger()->write("groupBy stored in session");
	} else {
//		fput("found ".$currentConfigKey."_groupBy"." in session!");
	}
	$lc->setGroupByFieldSelectorList($fieldSelectorList);
	$exec->addJsCode("" .
			"$('#searchBar .toolbarBox .groupBy').removeClass('disabledR');" .
			"$('#searchBar .toolbarBox .groupBy .SBB').removeClass('SBB').addClass('SBIB');" .
			"$('#searchBar .toolbarBox .groupBy div.disabledBg').removeClass('disabledBg');");

	//add element
	//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."start searchBar toolbar addElement"] = microtime(true);
	$this->debugLogger()->write("start searchBar toolbar addElement");
	if($crtGroupIsWritable){
		$exec->addJsCode("" .
				"$('#searchBar .toolbarBox .addNewElement')" .
				".addClass('Green')" .
				".removeClass('disabledBg')" .
				".unbind('click').click(function(){ ".$exec->getUpdateJsCode($p->getRealUserId(), "'+crtRoleId+'", "'+crtWigiiNamespaceUrl+'", "'+crtModuleName+'", 'elementDialog', 'addElement', "element/add/".$crtGroupP->getId())." })" .
				".find('font').addClass('H');");
	} else {
		$exec->addJsCode("" .
				"$('#searchBar .toolbarBox .addNewElement')" .
				".removeClass('Green')" .
				".addClass('disabledBg')" .
				".unbind('click')" .
				".find('font').removeClass('H');");
	}

	//switchView
	//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."start searchBar toolbar switchView"] = microtime(true);
	$moduleTemplates = $lc->getAvailableTemplates($p, $exec->getCrtModule(), $configS);
    if(count($moduleTemplates)>1){
    	$first = true;
    	foreach($lc->getAvailableTemplates($p, $exec->getCrtModule(), $configS) as $moduleView=>$moduleTemplate){
			if($lc->getCrtView() == $moduleView) continue;
			if($first){
				$exec->addJsCode("" .
						"$('#searchBar .toolbarBox .switchView')" .
						".removeClass('disabledR')" .
						".html('".$transS->h($p, $moduleView."View")."')" .
						".unbind('click').click(function(){ update('NoAnswer/$crtWigiiNamespace/$crtModule/switchView/$moduleView'); })" .
						".show();");
				$first = false;
			} else {
				$exec->addJsCode("" .
						"$('#searchBar .toolbarBox .switchView:first')" .
						".clone().html('".$transS->h($p, $moduleView."View")."')" .
						".unbind('click').click(function(){ update('NoAnswer/$crtWigiiNamespace/$crtModule/switchView/$moduleView'); })" .
						".insertAfter($('#searchBar .toolbarBox .switchView:first'));");
			}
	    }
    } else {
		$exec->addJsCode("" .
				"$('#searchBar .toolbarBox .switchView')" .
				".addClass('disabledR')" .
				".unbind('click')" .
				".hide();");
    }

//    //Export menu, does this view include export activities?
//	$this->includeExportMenu($p, $exec, $transS, $configS);
//
//	//Emailing
//	if($this->canCrtModuleEmailing($exec->getCrtModule())){
//		$exec->addJsCode("if($('#searchBar .toolbarBox .emailing').length==0){ $('#searchBar .toolbarBox').append('<div class=\"emailing L H\">".$transS->h($p, "emailingButton")."</div>').find('.emailing').click(function(){ update('emailingDialog/$crtWigiiNamespace/$crtModule/Emailing'); }); }");
//	}

    // Run menu
    $this->includeRunMenu($p, $exec, $transS, $configS);

	//duplicates filter is on
	if($lc->isGroupByOnlyDuplicates()){
		?><div class="removeDuplicatesFilter ui-corner-all  R White" <?
			?> onclick="update('moduleView/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/changeGroupByKey/reset');" <?
			if(false) { ?> onclick="$('#elementListGroupBy').val('reset').change();" <? }
			?>><font style="float:left;"><?=$transS->h($p, "removeDuplicatesFilter");?></font> <div class="H" style="font-size:11px;float:right;padding:3px;margin-top:-5px;margin-right:-5px;margin-left:2px;cursor:pointer;">x</div></div><?
		$exec->addJsCode("$('#moduleView>div.toolBar').addClass('Red');");
	}

?></div><?
?><div class="clear"></div><?
/** INDICATORS */
?><div id="indicators"><?
	//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."start indicator"] = microtime(true);
	$this->debugLogger()->write("start indicator");
	$this->includeTemplateIndicators($p, $exec);
?></div><?
?><div class="clear"></div><?

?><div class="dataZone blog"><?
	/**
	 * element List context menu
	 */
	?><div class="cm SBB"><?
		?><div id="cm_exit" class="exit SBB">x</div><?
		?><div id="cm_open" class="H fB"><?=$transS->t($p, "openElement");?></div><?
		?><div id="cm_addElementInList" class="H fB"><?=$transS->t($p, "addElement");?></div><?
		?><div id="cm_edit" class="write H fB"><?=$transS->t($p, "editElement");?>...</div><?
		?><div id="cm_delete" class="write H fB"><?=$transS->t($p, "deleteElement");?>...</div><?
		?><div id="cm_copy" class="write H fB"><?=$transS->t($p, "copyElement");?>...</div><?
		?><div id="cm_organize" class="write H fB"><?=$transS->t($p, "organizeElement");?>...</div><?
		?><div id="cm_lock" class="write H fB"><?=$transS->t($p, "state_lock");?></div><?
		?><div id="cm_block" class="write H fB unchecked"><?=$transS->t($p, "state_block");?></div><?
		?><div id="cm_state_important1" class="write H fB unchecked"><?=$transS->t($p, "state_make_important1");?></div><?
		?><div id="cm_state_important2" class="write H fB unchecked"><?=$transS->t($p, "state_make_important2");?></div><?
		?><div id="cm_finalize" class="write H fB unchecked"><?=$transS->t($p, "state_finalize");?></div><?
		?><div id="cm_approve" class="write H fB unchecked"><?=$transS->t($p, "state_approve");?></div><?
		?><div id="cm_dismiss" class="write H fB unchecked"><?=$transS->t($p, "state_dismiss");?></div><?
		?><div id="cm_state_archived" class="write H fB unchecked"><?=$transS->t($p, "state_mark_archived");?></div><?
		?><div id="cm_state_deprecated" class="write H fB unchecked"><?=$transS->t($p, "state_mark_deprecated");?></div><?
		?><div id="cm_hide" class="write H fB unchecked"><?=$transS->t($p, "state_hide");?></div><?
	?></div><?

	//reset the groupBy if config change
	list($total, $nbRow) = $this->getAllElementsInblogView($p, $exec, $lc, false, false, 1, $lastConfigKey!=$currentConfigKey);

	//groupBy and sortBy are defined in getAllElementsInblogView if there where empty
	//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."start define groupBy value"] = microtime(true);
	$this->debugLogger()->write("start define groupBy value");
	if($lc->getGroupBy()==null){
		$exec->addJsCode("$('#searchBar .toolbarBox .groupBy .value').html('<a href=\"#null\">".str_replace("'", "\\'", $transS->t($p, "selectInList"))."</a>');");
	} else if($groupByOptions[$lc->getGroupBy()]){
		$exec->addJsCode("$('#searchBar .toolbarBox .groupBy .value').html('<a href=\"#".$lc->getGroupBy()."\">".str_replace("'", "\\'", $groupByOptions[$lc->getGroupBy()])."</a>');");
	}
	//load the menu only if the config changed
	if(($lastConfigKey!=$currentConfigKey) && $groupByOptions){
		//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."start loading groupBy menu"] = microtime(true);
		$this->debugLogger()->write("loading groupBy menu");
		$options = "";
		//add option to reset the groupBy to nothing
		$options .= '<div><a href="#null">'.str_replace("'", "\\'", $transS->t($p, "selectInList")).'</a></div>';
		foreach($groupByOptions as $key=>$label){
			$options .= '<div><a href="#'.$key.'">'.str_replace("'", "\\'", $label).'</a></div>';
		}
		$exec->addJsCode("" .
			"if($('#searchBar .toolbarBox .groupBy .value').length!=0){ $('#searchBar .toolbarBox .groupBy .value').unbind('click mouseenter'); } " .
			"if($('#searchBar .toolbarBox .groupBy #groupByMenu').length){ $('#searchBar .toolbarBox .groupBy #groupByMenu').remove(); }" .
			"$('#searchBar .toolbarBox .groupBy .value').after('" .
				"<div class=\"cm SBIB\" id=\"groupByMenu\" style=\"display:none;font-weight:normal;\" >" .
					"<div class=\"exit SBB\">x</div>" .
					"$options" .
				"</div>" .
				"');" .
			"setListenersToMenu('searchBar .toolbarBox .groupBy .value', 'groupByMenu', 'moduleView', 'changeGroupByKey', null, true);" .
			" ");
	}
	if($sortByOptions[$lc->getSortedBy()]){
		//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."start define sortBy value"] = microtime(true);
		$this->debugLogger()->write("define sortByValue");
		$exec->addJsCode("" .
				"$('#searchBar .toolbarBox .sortBy .direction').removeClass('".($lc->isAscending() ? "DESC" : "ASC")."').addClass('".($lc->isAscending() ? "ASC" : "DESC")."');" .
				"$('#searchBar .toolbarBox .sortBy .value').html('<a href=\"#".$lc->getSortedBy()."\">".str_replace("'", "\\'", $sortByOptions[$lc->getSortedBy()])."</a>');");
	}
	//load the menu only if the config changed
	if(($lastConfigKey!=$currentConfigKey) && $sortByOptions){
		//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."start loading sortBy menu"] = microtime(true);
		$this->debugLogger()->write("loading sortBy menu");
		$options = "";
		foreach($sortByOptions as $key=>$label){
			$options .= '<div><a href="#'.$key.'">'.str_replace("'", "\\'", $label).'</a></div>';
		}
		$exec->addJsCode("" .
			"if($('#searchBar .toolbarBox .sortBy .value').length!=0){ $('#searchBar .toolbarBox .sortBy .value').unbind('click mouseenter'); } " .
			"if($('#searchBar .toolbarBox .sortBy #sortByMenu').length){ $('#searchBar .toolbarBox .sortBy #sortByMenu').remove(); }" .
			"$('#searchBar .toolbarBox .sortBy .value').after('" .
				"<div class=\"cm SBIB\" id=\"sortByMenu\" style=\"display:none;font-weight:normal;\" >" .
					"<div class=\"exit SBB\">x</div>" .
					"$options" .
				"</div>" .
				"');" .
			//the sort by direction will take the sortby direction by defaul depending of the header and the type
			//this is defined in:
			"setListenersToMenu('searchBar .toolbarBox .sortBy .value', 'sortByMenu', 'moduleView', 'changeSortByKey', null, true);" .
			//add listener to direction
			"$('#searchBar .toolbarBox .sortBy .direction').click(function(){ " .
				"update('moduleView/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/changeSortByKey/'+$('a:first', $(this).next()).attr('href').replace('#', '')+'/'+$(this).hasClass('DESC'));" .
			" });" .
			" ");
	}
	//refreshes module help icon if config changed
	if($lastConfigKey!=$currentConfigKey) $this->refreshModuleHelpAnchor($p,$exec);
	$this->bindJsServicesOnModuleView($p,$exec);
	
?></div><?

//the resize_elementList is called in the selectGroup in modulView.tpl.php
//$exec->addJsCode("resize_elementList();");

/**
 * Cover page zone
 */
if($configS->getParameter($p, $exec->getCrtModule(), "Group_enablePortal") == "1"){
	//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."start cover page"] = microtime(true);
	$this->debugLogger()->write("start cover page");
	if($crtGroupP){
		//we need to fetch the group details
		//already done in the portal part
		$groupPortalAction = $configS->getParameter($p, $exec->getCrtModule(), "Group_portalAction");
		if(!empty($groupPortalAction)) {
			// evaluates any FuncExp given as a groupPortalAction
			$groupPortalAction = $this->evaluateConfigParameter($p,$exec,$groupPortalAction);
			?><div id="groupPortalAction" class="portal" style="overflow:hidden; display:none; padding-left:10px; padding-right:10px;"><?
				if($configS->getParameter($p, $exec->getCrtModule(), "Group_portalActionRefreshOnMultipleChange") != "1"){					
					$groupPortalAction =  'groupPortalAction/'.$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/".$groupPortalAction."/".$crtGroupP->getId();
					$exec->addJsCode("update('".$groupPortalAction."');");					
				}
			//calcul de la hauteur plus redimensionnement
			?></div><?
		} else if($crtGroup->getGroup()->getDetail()!=null){ //if detail = null, then do nothing

			$htmlContentRec = $this->createActivityRecordForForm($p, Activity::createInstance("groupHtmlContent"), $exec->getCrtModule());
			$htmlContentRec->getWigiiBag()->importFromSerializedArray($crtGroup->getGroup()->getDetail()->getHtmlContent(), $htmlContentRec->getActivity());
			$trmHtmlContent = $this->createTRM($htmlContentRec);
			$htmlContent = $trmHtmlContent->doFormatForHtmlText($htmlContentRec->getFieldValue("text"));

			if($htmlContent != null){
				?><div class="portal" style="overflow:hidden; display:none; padding-left:10px; padding-right:10px;"><?
					echo $htmlContent;
				//calcul de la hauteur plus redimensionnement
				?></div><?
			}
		}
		if($htmlContent != null || !empty($groupPortalAction)){
			$exec->addJsCode("" .
				"coverPage_toggleList_titleList = '".$transS->h($p, "viewElementsInPortal")."';" .
				"coverPage_toggleList_titleWebsite = '".$transS->h($p, "viewPortalContent")."';" .
				"coverPage_toggleList();" .
				"if($('#searchBar .firstBox #removeFiltersButton.R').length==1) coverPage_toggleList();" .
				"hrefWithSiteroot2js('moduleView>div.portal', 'elementDialog');" .
				"");
		} else {
			//remove and hide any previous cover page settings
			$exec->addJsCode("removeCoverPageItems();");
		} //end if detail != null
	}
}

} //display list only if no url

$this->executionSink()->publishEndOperation("TEMPLATE elementBlog.tpl.php");

//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."end elementBlog.tpl.php"] = microtime(true);