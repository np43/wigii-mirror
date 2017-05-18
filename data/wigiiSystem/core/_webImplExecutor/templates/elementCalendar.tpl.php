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
 * Created by LWR, on 7 October 2011
 */
//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."start elementCalendar.tpl.php"] = microtime(true);
$this->executionSink()->publishStartOperation("TEMPLATE elementCalendar.tpl.php");

if(!isset($groupAS)) $groupAS = ServiceProvider::getGroupAdminService();
if(!isset($sessAS)) $sessAS = ServiceProvider::getSessionAdminService();
if(!isset($elS)) $elS = ServiceProvider::getElementService();
if(!isset($transS)) $transS = ServiceProvider::getTranslationService();
if(!isset($exec)) $exec = ServiceProvider::getExecutionService();
if(!isset($configS)) $configS= $this->getConfigurationContext();

$lc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
//hide the importElement and addNewELement icon if the group is not writable:
$crtGroupIsWritable = false;
$crtGroupP = null;
if($lc->getGroupPList()->count()==1){
	$crtGroupP = reset($lc->getGroupPList()->getListIterator());
	if($crtGroupP != null && $crtGroupP->getRights()!=null && $crtGroupP->getRights()->canShareElement()){
		$crtGroupIsWritable = true;
	}
}
$exec->addJsCode("calendarIsEditable = ".strtolower(put($crtGroupIsWritable)).";");

$crtWigiiNamespace = $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl();
$crtModule = $exec->getCrtModule()->getModuleUrl();

//Hide searchbox when parameters is equal to
if($configS->getParameter($p, $exec->getCrtModule(), "hide_searchBar") == "1"){
	$exec->addJsCode("$('#searchBar > .firstBox, #searchBar > .toolbarBox').hide();");
} else {
	$exec->addJsCode("$('#searchBar > .firstBox, #searchBar > .toolbarBox').show();");
}

//add toolbar


//we need to fetch the group details
if($crtGroupP) $crtGroupP = $groupAS->getGroup($p, $crtGroupP->getId());

/**
 * URL portal zone
 * if a portal is set on the group, then display the content of the website
 */
$url = null;
if($configS->getParameter($p, $exec->getCrtModule(), "Group_enablePortal") == "1"){
	if($crtGroupP){
		if($crtGroupP->getDbEntity()->getDetail()!=null){ //if detail = null, then do nothing

			$portalRec = $this->createActivityRecordForForm($p, Activity::createInstance("groupPortal"), $exec->getCrtModule());
			$portalRec->getWigiiBag()->importFromSerializedArray($crtGroupP->getDbEntity()->getDetail()->getPortal(), $portalRec->getActivity());
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
		} //end if detail != null
	}
}

if(!$url){ //display list only if no url

?><div class="toolBar"><?

	//reload if search bar is reloaded
	$lastConfigKey = $sessAS->getData($this, "elementListLastConfigKey");
	$currentConfigKey = $this->getCurrentConfigContextKey($p, $exec);
	$sessAS->storeData($this, "elementListLastConfigKey", $currentConfigKey);

	//no sortBy or groupBy for this view
	$exec->addJsCode("$('#searchBar .toolbarBox .sortBy, #searchBar .toolbarBox .groupBy').hide();");

	//switchView
	$moduleTemplates = $lc->getAvailableTemplates($p, $exec->getCrtModule(), $configS);
    if(count($moduleTemplates)>1){
    	$first = true;
    	foreach($lc->getAvailableTemplates($p, $exec->getCrtModule(), $configS) as $moduleView=>$moduleTemplate){
			if($lc->getCrtView() == $moduleView) continue;
			if($first){
				$exec->addJsCode("$('#searchBar .toolbarBox .switchView').removeClass('disabledR').html('".$transS->h($p, $moduleView."View")."').unbind('click').click(function(){ update('NoAnswer/$crtWigiiNamespace/$crtModule/switchView/$moduleView'); }).show();");
				$first = false;
			} else {
				$exec->addJsCode("$('#searchBar .toolbarBox .switchView:first').clone().html('".$transS->h($p, $moduleView."View")."').unbind('click').click(function(){ update('NoAnswer/$crtWigiiNamespace/$crtModule/switchView/$moduleView'); }).insertAfter($('#searchBar .toolbarBox .switchView:first'));");
			}
	    }
    } else {
		$exec->addJsCode("$('#searchBar .toolbarBox .switchView').addClass('disabledR').unbind('click').hide();");
    }

	//Export menu, does this view include export activities?
	$this->includeExportMenu($p, $exec, $transS, $configS);

	//Emailing
	if($this->canCrtModuleEmailing($exec->getCrtModule())){
		$exec->addJsCode("if($('#searchBar .toolbarBox .emailing').length==0){ $('#searchBar .toolbarBox').append('<div class=\"emailing L H\">".$transS->h($p, "emailingButton")."</div>').find('.emailing').click(function(){ update('emailingDialog/$crtWigiiNamespace/$crtModule/Emailing'); }); }");
	}

	//Show in Outlook
	$outlookButtonEnabled = false;
	if($crtGroupP){
		$xmlRec = $this->createActivityRecordForForm($p, Activity :: createInstance("groupXmlPublish"), $exec->getCrtModule());
		$xmlRec->getWigiiBag()->importFromSerializedArray($crtGroupP->getDbEntity()->getDetail()->getXmlPublish(), $xmlRec->getActivity());
		if($xmlRec->getFieldValue("enableGroupXmlPublish")==true){
			$outlookButtonEnabled = true;
			$outlookUrl = str_replace("//", '\/\/', str_replace(" ", "%20", SITE_ROOT.$crtGroupP->getDbEntity()->getWigiiNamespace()->getWigiiNamespaceUrl()."/".$crtGroupP->getDbEntity()->getModule()->getModuleUrl()."/getXmlFeed/".$crtGroupP->getDbEntity()->getId()."/".$xmlRec->getFieldValue("xmlPublishCode")."/ics"));
			$intro = $transS->h($p, "howToLinkInOutlook1");
			$intro = "<b>".implode("</p><p>", explode('\n', str_replace('"', '&quot;', $intro)))."</b>";
			$expl = $transS->h($p, "howToLinkInOutlook2");
			$expl = "</p><p>".implode("</p><p>", explode('\n', str_replace('"', '&quot;', $expl)))."";
			$outlookHelp = "";
		} else {
			$outlookButtonEnabled = false;
			$outlookHelp = $transS->h($p, "toEnableOutlookButtonHelp");
		}
		$exec->addJsCode("" .
				"if($('#searchBar .toolbarBox .outlook').length!=0){ $('#searchBar .toolbarBox .outlook').remove(); }" .
				"$('#searchBar .toolbarBox').append('<div class=\"outlook L H ui-corner-all".($outlookButtonEnabled ? "" : " disabledR ")."\" title=\"$outlookHelp\" style=\"font-weight:bold;background-color:#fff;padding-bottom:5px;\"><span class=\"showInOutlookText\">".$transS->h($p, "showInOutlookButton")."<span></div>')" .
				";");
		if($outlookButtonEnabled){
			$exec->addJsCode("" .
				"if($('#searchBar .toolbarBox .outlook').length!=0){ $('#searchBar .toolbarBox .outlook').unbind('click mouseenter'); } " .
				"$('#searchBar .toolbarBox .outlook').append('" .
					"<div class=\"cm SBB\" id=\"outlookMenu\" style=\"display:none;font-weight:normal;\" >" .
						"<div class=\"exit SBB\">x</div>" .
						"<p style=\"margin-top:10px;\">$intro</p>" .
						"<p><input type=\"text\" style=\"float:none;margin:0px;padding:2px;width: 100%; max-width:400px;\" value=\"$outlookUrl\" /></p>" .
						"<p style=\"margin-bottom:10px;\">$expl</p>" .
					"</div>" .
					"');" .
				//New function to show the outlookMenu without use the generic function
				//This function show the element when your click on and if you click again, the element is hidden. You can use the cross too to hidden the element.
				"$('#searchBar .toolbarBox .outlook .showInOutlookText').click(function() {
					if($('#outlookMenu').css('display') == 'none') {
						$('#outlookMenu').fadeIn();
					} else {
						$('#outlookMenu').fadeOut();
					}
					$('#outlookMenu input').select();
				});".
				"$('#searchBar .toolbarBox .SBB .exit').click(function() {
					$('#outlookMenu').fadeOut();
				});".
				//"setListenersToMenu('searchBar .toolbarBox .outlook', 'outlookMenu', null, null);" .
				" ");
		}


	}

	//add element
	if($crtGroupIsWritable){
		$exec->addJsCode("$('#searchBar .toolbarBox .addNewElement').addClass('Green').removeClass('disabledBg').unbind('click').click(function(){ ".$exec->getUpdateJsCode($p->getRealUserId(), "'+crtRoleId+'", "'+crtWigiiNamespaceUrl+'", "'+crtModuleName+'", 'elementDialog', 'addElement', "element/add/".$crtGroupP->getId())." }).find('font').addClass('H');");
	} else {
		$exec->addJsCode("$('#searchBar .toolbarBox .addNewElement').removeClass('Green').addClass('disabledBg').unbind('click').find('font').removeClass('H');");
	}
	
	//refreshes module help icon if config changed
	if($lastConfigKey!=$currentConfigKey) $this->refreshModuleHelpAnchor($p,$exec);
	$this->bindJsServicesOnModuleView($p,$exec);

?></div></div><?
?><div class="clear"></div><?
/** INDICATORS */
?><div id="indicators"><?
	$this->includeTemplateIndicators($p, $exec);
?></div><?
?><div class="clear"></div><?


//define starting view / date
$crtView = $lc->getCrtViewParams("crtView");
if($crtView==null) $crtView = "month";
$crtDate = $lc->getCrtViewParams("crtDate");
if($crtDate==null) $crtDate = "crtYear=".date("Y")."; crtMonth=".(date("n")-1)."; crtDay=".date("j").";";
//eput($crtView." ".$crtDate);

$exec->addJsCode("$crtDate setListenersToCalendar('".($crtGroupP ? ($crtGroupP->getDbEntity()->getGroupParentId() ? $crtGroupP->getDbEntity()->getGroupParentId() : 0 ) : null)."', '".($crtGroupP ? $transS->h($p, "groupUp") : null)."', '$crtView', crtYear, crtMonth, crtDay);");

?><div class="dataZone calendar"><?
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
	?></div><?
	//echo $table;
?></div><?

//height is done in resize_elementList()

//no preview or context menu for calendar view

/**
 * Cover page zone
 */
if($configS->getParameter($p, $exec->getCrtModule(), "Group_enablePortal") == "1"){
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
		} else if($crtGroupP->getDbEntity()->getDetail()!=null){ //if detail = null, then do nothing
			$htmlContentRec = $this->createActivityRecordForForm($p, Activity::createInstance("groupHtmlContent"), $exec->getCrtModule());
			$htmlContentRec->getWigiiBag()->importFromSerializedArray($crtGroupP->getDbEntity()->getDetail()->getHtmlContent(), $htmlContentRec->getActivity());
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
					"hrefWithSiteroot2js('moduleView>div.portal', 'elmentDialog');" .
					"");
		} else {
			//remove and hide any previous cover page settings
			$exec->addJsCode("removeCoverPageItems();");
		} //end if detail != null
	}
}

} //display list only if no url

$this->executionSink()->publishEndOperation("TEMPLATE elementCalendar.tpl.php");

//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."end elementCalendar.tpl.php"] = microtime(true);
