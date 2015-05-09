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

/***
 * Created by LWR, on 7 October 2011
 */
//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."start elementCalendar.tpl.php"] = microtime(true);
$this->executionSink()->publishStartOperation("TEMPLATE elementCalendar.tpl.php");

if(!isset($groupAS)) $groupAS = ServiceProvider::getGroupAdminService();
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

//add toolbar


//we need to fetch the group details
if($crtGroupP) $crtGroupP = $groupAS->getGroup($p, $crtGroupP->getId());

/**
 * URL portal zone
 * 	if an portal is setted on the group, then display the content of the website
 */
$url = null;
if($configS->getParameter($p, $exec->getCrtModule(), "Group_enablePortal") == "1"){
	if($crtGroupP){
		if($crtGroupP->getDbEntity()->getDetail()!=null){ //if detail = null, then do nothing

			$portalRec = $this->createActivityRecordForForm($p, Activity::createInstance("groupPortal"), $exec->getCrtModule());
			$portalRec->getWigiiBag()->importFromSerializedArray($crtGroupP->getDbEntity()->getDetail()->getPortal(), $portalRec->getActivity());
			$url = $portalRec->getFieldValue("url", "url");
			if($url != null){
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
				"$('#searchBar .toolbarBox').append('<div class=\"outlook L H ui-corner-all".($outlookButtonEnabled ? "" : " disabledR ")."\" title=\"$outlookHelp\" style=\"font-weight:bold;background-color:#fff;padding-bottom:5px;\">".$transS->h($p, "showInOutlookButton")."</div>')" .
				";");
		if($outlookButtonEnabled){
			$exec->addJsCode("" .
				"if($('#searchBar .toolbarBox .outlook').length!=0){ $('#searchBar .toolbarBox .outlook').unbind('click mouseenter'); } " .
				"$('#searchBar .toolbarBox .outlook').append('" .
					"<div class=\"cm SBB\" id=\"outlookMenu\" style=\"display:none;font-weight:normal;\" >" .
						"<div class=\"exit SBB\">x</div>" .
						"<p style=\"margin-top:10px;\">$intro</p>" .
						"<p><input type=\"text\" style=\"float:none;margin:0px;padding:2px;width:400px;\" value=\"$outlookUrl\" /></p>" .
						"<p style=\"margin-bottom:10px;\">$expl</p>" .
					"</div>" .
					"');" .
				"setListenersToMenu('searchBar .toolbarBox .outlook', 'outlookMenu', null, null);" .
				" ");
		}


	}

	//add element
	if($crtGroupIsWritable){
		$exec->addJsCode("$('#searchBar .toolbarBox .addNewElement').addClass('Green').removeClass('disabledBg').unbind('click').click(function(){ ".$exec->getUpdateJsCode($p->getRealUserId(), "'+crtRoleId+'", "'+crtWigiiNamespaceUrl+'", "'+crtModuleName+'", 'elementDialog', 'addElement', "element/add/".$crtGroupP->getId())." }).find('font').addClass('H');");
	} else {
		$exec->addJsCode("$('#searchBar .toolbarBox .addNewElement').removeClass('Green').addClass('disabledBg').unbind('click').find('font').removeClass('H');");
	}

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
	//no context menu for calendar view

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
		if($crtGroupP->getDbEntity()->getDetail()!=null){ //if detail = null, then do nothing
			$htmlContentRec = $this->createActivityRecordForForm($p, Activity::createInstance("groupHtmlContent"), $exec->getCrtModule());
			$htmlContentRec->getWigiiBag()->importFromSerializedArray($crtGroupP->getDbEntity()->getDetail()->getHtmlContent(), $htmlContentRec->getActivity());
			$trmHtmlContent = $this->createTRM($htmlContentRec);
			$htmlContent = $trmHtmlContent->doFormatForHtmlText($htmlContentRec->getFieldValue("text"));

			if($htmlContent != null){
				?><div class="portal" style="overflow:hidden; display:none; padding-left:10px; padding-right:10px;"><?
					echo $htmlContent;
				//calcul de la hauteur plus redimensionnement
				?></div><?
				$exec->addJsCode("" .
					"coverPage_toggleList_titleList = '".$transS->h($p, "viewElementsInPortal")."';" .
					"coverPage_toggleList_titleWebsite = '".$transS->h($p, "viewPortalContent")."';" .
					"coverPage_toggleList();" .
					"if($('#searchBar .firstBox #removeFiltersButton.R').length==1) coverPage_toggleList();" .
					"hrefWithSiteroot2js('moduleView>div.portal', 'elmentDialog');" .
					"");
			}
		} else {
			//remove and hide any previous cover page settings
			$exec->addJsCode("removeCoverPageItems();");
		} //end if detail != null
	}
}

} //display list only if no url

$this->executionSink()->publishEndOperation("TEMPLATE elementCalendar.tpl.php");

//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."end elementCalendar.tpl.php"] = microtime(true);
