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
 * Created on 23 juil. 09
 * by LWR
 */
//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."start header.php"] = microtime(true);
$this->executionSink()->publishStartOperation("TEMPLATE header.php");

 if(!isset($configS)) $configS = $this->getConfigurationContext();
 if(!isset($exec)) $exec = $this->getExecutionService();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title><?
//depending on the request change title:
if($exec->getCrtAction()=="element"){
	if($exec->getCrtParameters(0)=="detail"){
		echo $exec->getCrtWigiiNamespace()->getWigiiNamespaceName().": ".$transS->t($p, "detailElement");
	} else if($exec->getCrtParameters(0)=="print"){
		echo $exec->getCrtWigiiNamespace()->getWigiiNamespaceName().": ".$transS->t($p, "printElement");
	}
} else {
	echo (isset($SITE_TITLE) ? $SITE_TITLE : "");
}
?></title>
<base href="<? echo SITE_ROOT; ?>" />
<meta name="Copyright" content="Wigii" />
<meta name="License" content="GNU GPL 3.0" />
<meta name="Generator" content="Wigii" />
<meta name="Description" content="Wigii is a web based system allowing management of any kind of data (contact, document, calendar, and any custom types). Find out project page on: http://code.google.com/p/wigii/" />
<?
if(defined("PREVENT_INDEXING") && PREVENT_INDEXING){
?>
<meta name="robots" content="none,noindex" />
<?
}
?>
<meta http-equiv="content-type" content="text/html;charset=utf-8" />
<?
if(file_exists(CLIENT_WEB_PATH."favicon.ico")){
?>
<link rel="shortcut icon" href="<?=SITE_ROOT_forFileUrl.CLIENT_WEB_URL;?>favicon.ico" type="image/x-icon" />
<?
} else if(file_exists(CLIENT_WEB_PATH."favicon.jpg")){
?>
<link rel="shortcut icon" href="<?=SITE_ROOT_forFileUrl.CLIENT_WEB_URL;?>favicon.jpg" type="image/x-icon" />
<?
} else if(file_exists(CLIENT_WEB_PATH."favicon.gif")){
?>
<link rel="shortcut icon" href="<?=SITE_ROOT_forFileUrl.CLIENT_WEB_URL;?>favicon.gif" type="image/x-icon" />
<?
} else if(file_exists(CLIENT_WEB_PATH."favicon.png")){
?>
<link rel="shortcut icon" href="<?=SITE_ROOT_forFileUrl.CLIENT_WEB_URL;?>favicon.png" type="image/x-icon" />
<?
} else if(file_exists("favicon.jpg")){
?>
<link rel="shortcut icon" href="<?=SITE_ROOT_forFileUrl;?>favicon.jpg" type="image/x-icon" />
<?
} else {
?>
<link rel="shortcut icon" href="<?=SITE_ROOT_forFileUrl;?>favicon.gif" type="image/x-icon" />
<?
}

//Google analytics for Wigii Project
?>
<script type="text/javascript">
var _gaq = _gaq || [];
_gaq.push(['_setAccount', 'UA-37057508-4']);
_gaq.push(['_trackPageview']);
(function() {
var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
})();
</script>
<script type="text/javascript" src="<?=SITE_ROOT_forFileUrl;?>assets/js/wigii_<?=ASSET_REVISION_NUMBER;?>.js"></script>
<script type="text/javascript" src="<?=SITE_ROOT_forFileUrl;?>swfobject.js"></script>
<script type="text/javascript" src="<?=SITE_ROOT_forFileUrl;?>assets/ckeditor4.4.3/ckeditor.js"></script>
<script type="text/javascript" src="<?=SITE_ROOT_forFileUrl;?>assets/ckeditor4.4.3/config.js"></script>
<script type="text/javascript" src="<?=SITE_ROOT_forFileUrl;?>assets/ckeditor4.4.3/adapters/jquery.js"></script>
<?
?>
<link rel="stylesheet" href="<?=SITE_ROOT_forFileUrl;?>assets/css/wigii_<?=ASSET_REVISION_NUMBER;?>.css" type="text/css" media="all" />
<link rel="stylesheet" href="<?=SITE_ROOT_forFileUrl;?>assets/css/theme.css.php" type="text/css" media="all" />
<?
if(file_exists(CLIENT_WEB_PATH.CLIENT_NAME.".css")){
?>
<link rel="stylesheet" href="<?=SITE_ROOT_forFileUrl.CLIENT_WEB_URL.CLIENT_NAME;?>.css" type="text/css" media="all" />
<?
}
//***
// IE 7 and less fixes
//***
?>
<!--[if lte IE 7]>
<style>
@import "assets/css/css_IE7fix.css";
</style>
<![endif]-->
<!--[if IE 8]>
<style>
@import "assets/css/css_IE8fix.css";
</style>
<![endif]-->
<!--[if gte IE 11]>
<style>
@import "assets/css/css_IE11fix.css";
</style>
<![endif]-->
</head>
<body>
<script type="text/javascript" ><?
//Definition of JS constante
?>
SITE_ROOT = '<?=SITE_ROOT;?>';
CLIENT_NAME = '<?=CLIENT_NAME;?>';
crtContextId = <?=$exec->getCrtContext();//prevent creating new context on setBrowser?>;
EXEC_answerRequestSeparator = '<?=ExecutionServiceImpl::answerRequestSeparator;?>';
EXEC_answerParamSeparator = '<?=ExecutionServiceImpl::answerParamSeparator;?>';
EXEC_requestSeparator = '<?=ExecutionServiceImpl::requestSeparator;?>';
EXEC_foundInJSCache = '<?=ExecutionServiceImpl::answerFoundInJSCache;?>';
DIALOG_okLabel = '<?=$transS->h($p, "ok");?>';
DIALOG_cancelLabel = '<?=$transS->h($p, "cancel");?>';
DIALOG_doYouWantToSaveChage = '<?=$transS->h($p, "doYouWantToSaveChange");?>';
DIALOG_finishCurrentAction = '<?=$transS->h($p, "pleaseFinishYourCurrentAction");?>';
DIALOG_selectAtLeastOneGroup = '<?=$transS->h($p, "pleaseSelectAtLeastOneGroup");?>';
DIALOG_doYouWantToMoveThisFolderUnderParent = '<?=$transS->h($p, "doYouWantToMoveThisFolderUnderParent");?>';
DIALOG_doYouWantToMoveOrKeepInBoth = '<?=$transS->h($p, "doYouWantToMoveOrKeepInBoth");?>';
DIALOG_move = '<?=$transS->h($p, "move");?>';
DIALOG_keepInBoth = '<?=$transS->h($p, "keepInBoth");?>';

<?
//Browser detection, at each refresh, the browser and window height is refreshed
?>
version = parseFloat(jQuery.browser.version.split(".").slice(0,2).join("."));
if(jQuery.browser.msie) browserName = "msie";
else if(jQuery.browser.mozilla) browserName = "mozilla";
else if(jQuery.browser.safari) browserName = "safari";
else browserName = "other";
<?
//translation of v3 url type to v4
?>
lookupForItem = window.location.href.match(/\/do\/\D*\/(\D*)\/(\D*)\/element\/detail\/([0-9]+)/);
lookupForGroup = window.location.href.match(/\/do\/\D*\/(\D*)\/(\D*)\/groupSelectorPanel\/selectGroupAsConfig\/([0-9]+)/);
if(lookupForItem){
	crtHash = "#"+lookupForItem[1]+"/"+lookupForItem[2]+"/item/"+lookupForItem[3];
	$.cookie('wigii_anchor', crtHash,  { path: '/' });
	window.location = "<?=SITE_ROOT;?>"+crtHash;
} else if(lookupForGroup){
	crtHash = "#"+lookupForGroup[1]+"/"+lookupForGroup[2]+"/folder/"+lookupForGroup[3];
	$.cookie('wigii_anchor', crtHash,  { path: '/' });
	window.location = "<?=SITE_ROOT;?>"+crtHash;
} else {
<?
//wigii_anchor cookie reload process and check
?>
$.cookie('wigii_anchor', '#',  { path: '/' });
crtHash = window.location.hash;
if(crtHash==''){ crtHash = '#'; }

var reqOnHash = crtHash.replace('#', '');
if(reqOnHash == 'Home' || reqOnHash == 'logout') {
	reqOnHash = [null, 'Home', null, null];
}
else {
	reqOnHash = reqOnHash.split('/');
	if(reqOnHash.length > 1 && reqOnHash[1] == 'Admin') reqOnHash = new Array();
	else if(reqOnHash.length > 0 && (reqOnHash[0] == null || reqOnHash[0] == '')) reqOnHash = new Array();
}
if(reqOnHash.length > 0) {
	reqOnHash = {
		wigiiNamespace:(reqOnHash.length > 0 ? (reqOnHash[0] != null ? reqOnHash[0].replace('%20', ' ') : '<?=WigiiNamespace::EMPTY_NAMESPACE_URL; ?>'): '<?=WigiiNamespace::EMPTY_NAMESPACE_URL; ?>'),
		module:(reqOnHash.length > 1 ? (reqOnHash[1] != null ? reqOnHash[1].replace('%20', ' ') : '<?=Module::EMPTY_MODULE_URL; ?>'): '<?=Module::EMPTY_MODULE_URL; ?>'),
		type:(reqOnHash.length > 2 ? (reqOnHash[2] != null ? reqOnHash[2].replace('%20', ' ') : null): null),
		id:(reqOnHash.length > 3 ? (reqOnHash[3] != null ? reqOnHash[3].replace('%20', ' ') : null): null),
		remainingParams:(reqOnHash.length > 4 ? reqOnHash.slice(4).join('/') : null)
	};
	if(reqOnHash.module == 'Home') {
		reqOnHash = 'NoAnswer/'+reqOnHash.wigiiNamespace+'/'+reqOnHash.module+'/start';
	}
	else if(reqOnHash.type=='find') {
		reqOnHash = 'NoAnswer/'+reqOnHash.wigiiNamespace+'/'+reqOnHash.module+'/find/'+reqOnHash.id+(reqOnHash.remainingParams != null ? '/'+reqOnHash.remainingParams : '');
	}
	else {
		reqOnHash = 'NoAnswer/'+reqOnHash.wigiiNamespace+'/'+reqOnHash.module+'/navigate'+(reqOnHash.type != null ? '/'+reqOnHash.type+'/'+reqOnHash.id : '');
	}
}
else {
<? if($exec->getCrtAction() == null || $exec->getCrtModule()->isHomeModule()) {?>
	reqOnHash = 'NoAnswer/NoWigiiNamespace/Home/start';
<? } else { ?>
	reqOnHash = null;
<? }?>
}
<? if(ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal()) { ?>
reqOnHash = null;
<? } ?>
$.cookie('wigii_anchor', crtHash,  { path: '/' });
if(reqOnHash != null){
	self.location = "<?=(HTTPS_ON ? "https" : "http" );?>://<?=$_SERVER['HTTP_HOST']?><?=$_SERVER['REQUEST_URI']?>"+crtHash;
	setTimeout(function(){update(reqOnHash);}, 500);
}
update('<?=EMPTY_ANSWER_ID_URL; ?>/<?=WigiiNamespace::EMPTY_NAMESPACE_URL; ?>/<?=Module::EMPTY_MODULE_URL; ?>/setBrowser/'+browserName+'/'+version);

}
<?
//add a listener on windows unload to put in the wigii_anchor the last content of #
//this allow to reload the page having directly the correct hash tag in the cookie
?>
$(window).unload(function(){
crtHash = window.location.hash;
if(crtHash==''){ crtHash = '#'; }
$.cookie('wigii_anchor', crtHash,  { path: '/' });
});
<?
//Incompatible browser are alerted only the first time
if($exec->getBrowserName()==null){
?>
if((jQuery.browser.msie && version < 7.0) ||(jQuery.browser.mozilla && version < 1.8)||(jQuery.browser.safari && version < 522.1)){
alert('Unsupported browser, please upgrade to a recent browser.\n\nThe system may not work properly.\n\nWe advise you to upgrade to the latest Firefox browser: http://www.mozilla.org/firefox/\n\nTo find out what is your navigator name and version please go on:\n<?=SITE_ROOT;?>incompatibleBrowser.php');}
<? }

// closeStandardsDialogs functions
?>
var elementDialogStack = 0;
function closeStandardsDialogs(){
$('.elementDialog').each(function(){
	if($(this).is(':ui-dialog')) { $(this).dialog("destroy"); }
	actOnCloseDialog($(this).attr('id'));
});
if( $('#organizeDialog').is(':ui-dialog')) { $('#organizeDialog').dialog("destroy"); }
actOnCloseDialog("organizeDialog");
emptyDialog("organizeDialog");
if( $('#multipleDialog').is(':ui-dialog')) { $('#multipleDialog').dialog("destroy"); }
actOnCloseDialog("multipleDialog");
emptyDialog("multipleDialog");
if( $('#filtersDialog').is(':ui-dialog')) { $('#filtersDialog').dialog("destroy"); }
actOnCloseDialog("filtersDialog");
emptyDialog("filtersDialog");
if( $('#elementPreview').is(':ui-dialog')) { $('#elementPreview').dialog("destroy"); }
actOnCloseDialog("elementPreview");
emptyDialog("elementPreview");
if( $('#confirmationDialog').is(':ui-dialog')) { $('#confirmationDialog').dialog("destroy"); }
actOnCloseDialog("confirmationDialog");
emptyDialog("confirmationDialog");
if( $('#feedbackDialog').is(':ui-dialog')) { $('#feedbackDialog').dialog("destroy"); }
actOnCloseDialog("feedbackDialog");
emptyDialog("feedbackDialog");
if( $('#emailingDialog').is(':ui-dialog')) { $('#emailingDialog').dialog("destroy"); }
actOnCloseDialog("emailingDialog");
emptyDialog("emailingDialog");
if( $('#exportDialog').is(':ui-dialog')) { $('#exportDialog').dialog("destroy"); }
actOnCloseDialog("exportDialog");
emptyDialog("exportDialog");
if( $('#importDialog').is(':ui-dialog')) { $('#importDialog').dialog("destroy"); }
actOnCloseDialog("importDialog");
emptyDialog("importDialog");
if( $('#changePasswordDialog').is(':ui-dialog')) { $('#changePasswordDialog').dialog("destroy"); }
actOnCloseDialog("changePasswordDialog");
emptyDialog("changePasswordDialog");
if( $('#downloadingDialog').is(':ui-dialog')) { $('#downloadingDialog').dialog("destroy"); }
actOnCloseDialog("downloadingDialog");
emptyDialog("downloadingDialog");
}
DIALOG_doYouWantToSave_organizeDialog = '<?=$transS->h($p, "doYouWantToSave_organizeDialog");?>';
DIALOG_doYouWantToSave_filtersDialog = '<?=$transS->h($p, "doYouWantToSave_filtersDialog");?>';
DIALOG_doYouWantToSave_confirmationDialog = '<?=$transS->h($p, "doYouWantToSave_confirmationDialog");?>';
DIALOG_doYouWantToSave_feedbackDialog = '<?=$transS->h($p, "doYouWantToSave_feedbackDialog");?>';
DIALOG_doYouWantToSave_emailingDialog = '<?=$transS->h($p, "doYouWantToSave_emailingDialog");?>';
DIALOG_doYouWantToSave_importDialog = '<?=$transS->h($p, "doYouWantToSave_importDialog");?>';
DIALOG_doYouWantToSave_exportDialog = '<?=$transS->h($p, "doYouWantToSave_exportDialog");?>';
DIALOG_doYouWantToSave_changePasswordDialog = '<?=$transS->h($p, "doYouWantToSave_changePasswordDialog");?>';
</script>
<?
if(file_exists(CLIENT_WEB_PATH.CLIENT_NAME.".js")){
?><script type="text/javascript" src="<?=SITE_ROOT_forFileUrl.CLIENT_WEB_URL.CLIENT_NAME;?>.js" ></script><?
}
if(file_exists(CLIENT_WEB_PATH."CKTemplates.js.php")){
?><script type="text/javascript" src="<?=SITE_ROOT_forFileUrl.CLIENT_WEB_URL;?>CKTemplates.js.php" ></script><?
} else if(file_exists(CLIENT_WEB_PATH."CKTemplates.js")){
?><script type="text/javascript" src="<?=SITE_ROOT_forFileUrl.CLIENT_WEB_URL;?>CKTemplates.js" ></script><?
}
flush();
$companyColor = $configS->getParameter($p, null, "companyColor");
$rCompanyColor = $configS->getParameter($p, null, "companyReverseColor");
if(!$companyColor) $rCompanyColor = "#3E4552";
if(!$rCompanyColor) $rCompanyColor = "#fff";
?>
<div id="busyDiv" class="ui-corner-all" style="background-color:<?=$companyColor;?>;color:<?=$rCompanyColor;?>;border:2px solid <?=$rCompanyColor;?>;font-size:small;padding:2px 13px;position:absolute; top:32px; left:48%; display:none; z-index:999999;"><?=$transS->t($p, "wigiiBusyLoading");?></div>
<div id="filteringBar" class="ui-corner-all SBIB" style="background-color:#fff;font-size:large;position:absolute; top:40%; left:40%; display:none; z-index:999999; padding:5px 10px;"><?=$transS->t($p, "wigiiFilteringLoading");?>&nbsp;&nbsp;&nbsp;<img src="<?=SITE_ROOT_forFileUrl;?>images/gui/busyBlue.gif" style="vertical-align:middle;"/></div>
<div id="loadingBar" class="ui-corner-all SBIB" style="background-color:#fff;font-size:large;position:absolute; top:40%; left:40%; display:none; z-index:999999; padding:5px 10px;"><?=$transS->t($p, "wigiiBusyLoading");?>&nbsp;&nbsp;&nbsp;<img src="<?=SITE_ROOT_forFileUrl;?>images/gui/busyBlue.gif" style="vertical-align:middle;"/></div>
<div id="formProgressBar" style="background-color:#fff;position:absolute; top:40%; left:40%; width:20%; display:none; z-index:999999;padding:10px;"></div>
<div id="help" style="display:none;"></div>
<div id="systemConsole" class="ui-corner-all" style="display:none;" ></div>
<div id="summaryDialog" class="summary cm SBB" style="display:none; top:100px; left:100px;" ><div class="exit SBB" onclick="$(this).parent().hide();">x</div><div class="handler F"></div><textarea class="content"></textarea></div>
<div id='elementDialog' class='elementDialog' style='display:none; top:0px; left:0px;'></div>
<div id='organizeDialog' style='display:none; top:0px; left:0px;'></div>
<div id='multipleDialog' style='display:none; top:0px; left:0px;'></div>
<div id='filtersDialog' style='display:none; top:0px; left:0px;'></div>
<? //elementPreview is used to display the content of a file without downloading it ?>
<div id='elementPreview' style='display:none; top:0px; left:0px;'></div>
<div id='confirmationDialog' style='display:none; top:0px; left:0px; padding-top:20px;'></div>
<div id='feedbackDialog' style='top:0px; left:0px;display:none;'></div>
<div id='emailingDialog' style='top:0px; left:0px;display:none;'></div>
<div id='exportDialog' style='top:0px; left:0px;display:none;'></div>
<div id='importDialog' style='top:0px; left:0px;display:none;'></div>
<div id='changePasswordDialog' style='top:0px; left:0px;display:none;'></div>
<div id='downloadingDialog' style='top:0px; left:0px;display:none;'></div>
<script type="text/javascript" >
$('#systemConsole').css('display','none').resizable({handles:'e,w',stop:function(event,ui){$(this).css('height','auto');}}).draggable({cursor:'crosshair',handle:'.header'});
</script>
<div id="updateRequests"></div>
<div id="mainDiv">
<?

//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."end header.php"] = microtime(true);
$this->executionSink()->publishEndOperation("TEMPLATE header.php");