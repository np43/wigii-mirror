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
 * Created on 26 ao�t 09
 * by LWR
 */
if(!isset($authS)) $authS = ServiceProvider::getAuthenticationService();
if(!isset($transS)) $transS = ServiceProvider::getTranslationService();
if(!isset($p)) $p = $authS->getMainPrincipal();
if(!isset($configS)) $configS = $this->getConfigurationContext();

$companyColor = $configS->getParameter($p, null, "companyColor");
$rCompanyColor = $configS->getParameter($p, null, "companyReverseColor");
if(!$companyColor) $companyColor = "#3E4552";
if(!$rCompanyColor) $rCompanyColor = "#fff";

$companyLogo = $configS->getParameter($p, null, "companyLogo");
$companyLogoMargins = $configS->getParameter($p, null, "companyLogoMargin");
if($companyLogo){
	if(!$companyLogoMargins) $companyLogoMargins = "2px 5px 2px 15px";
	echo '<div class="" style="display:inline-block;margin:'.($companyLogoMargins).';background-color:'.$companyColor.';border-left:none;border-right:none;border-top:none;"><img src="'.SITE_ROOT_forFileUrl.$companyLogo.'"/></div>';
}


?><div id="loginForm" class="" style=""><?
//if we are currently updating, then we just add a request for this div
//else we need to execute the action manually because in that case we just
//want the html of this action directly here.
if($exec->getIsUpdating()){
	$exec->addRequests("loginForm/".WigiiNamespace::EMPTY_NAMESPACE_URL."/Admin/login");
} else {
	$exec->addRequests(WigiiNamespace::EMPTY_NAMESPACE_URL."/Admin/login");
	$exec->loadNextRequest($this);
	$this->executeAction($exec);
}
?></div><?

$xmlFeedPath = (string)$configS->getParameter($p, null, "tabsContentUrlForLoginPage");
$xmlFeed = $xmlFeedPath;
if($xmlFeed){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, SITE_ROOT.$xmlFeed);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$xmlFeed = curl_exec($ch);
	if($xmlFeed){
		$xmlFeed = @simplexml_load_string($xmlFeed);
	}
	curl_close($ch);

	if($xmlFeed){
		//lookup for any item with a field  type Files with a none empty path
		$tabs = array();
		foreach($xmlFeed->item as $item){
			$files = $item->xpath("fields/field[@type='Files']");
			if($files){
				foreach($files as $file){
					$name = (string)$file->name;
					if($name){
						$postFix = "";
						if((string)$file->type==".htm" || (string)$file->type==".html" ){
							$postFix = "/integrated";
						}
						if((string)$file->type==".zip" || (string)$file->type==".ZIP"){
							$postFix = "/unzipAndRead";
						}
						$path = (string)$file->path;
						if($path == null) $path = $xmlFeedPath."/download/".(string)$item->id."/".$file["name"];
						$tabs[] = '<li><a href="'.$path.$postFix.'">'.$name.'</a></li>';
						continue 2;
					}
				}
			}
		}
		if($tabs){
			?><div id="quickStart"><div id="quickStartTabs"><ul><?
				echo implode("", $tabs);
			?></ul></div></div><?
			$exec->addJsCode("$('#quickStartTabs').tabs();");
			if(count($tabs)==1){
				$exec->addJsCode("$('#quickStartTabs ul.ui-tabs-nav').hide();");
			}
		}
	}
}





