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

/**
 * Host entrance when no specific url, main door for robots.
 * Created on 23 juil. 09 LWR
 * Modified by CWE on 08.04.2016 to link forward to http://www.wigii-system.net
 * Modified by CWE on 15.01.2019 to prevent cross origin iframes
 */
header("X-Frame-Options: SAMEORIGIN");
header("Content-Type: text/html; charset=UTF-8");
?><!DOCTYPE html>
<HTML>
<HEAD>
<script type="text/javascript" >
<? //Definition of JS constante needed in the wigii_XXX.js file ?>
DIALOG_okLabel = 'Ok';
DIALOG_noLabel = 'No';
DIALOG_cancelLabel = 'Cancel';
DIALOG_closeLabel = 'Close';
</script>
<script type="text/javascript" src="<?=SITE_ROOT_forFileUrl;?>assets/js/wigii_<?=ASSET_REVISION_NUMBER;?>.js"></script>
<script language="JavaScript" type="text/JavaScript">
<?
if(HTTPS_ON and $_SERVER['HTTPS'] != "on"){
	//reload on correct protocol
?>
self.location = "<?=(HTTPS_ON ? "https" : "http" );?>://<?=$_SERVER['HTTP_HOST']?><?=$_SERVER['REQUEST_URI']?>"+window.location.hash;
</script>
</HEAD>
<BODY>
reload
</BODY>
</HTML><?
	exit;
}
?>
/*
version = parseFloat(jQuery.browser.version.split(".").slice(0,2).join("."));
if(jQuery.browser.msie) browserName = "msie";
else if(jQuery.browser.mozilla) browserName = "mozilla";
else if(jQuery.browser.safari) browserName = "safari";
else browserName = "other";
*/
if (navigator.userAgent.search("MSIE") >= 0) {
	browserName = "msie";
	version = parseFloat(navigator.appVersion.split(".").slice(0,2).join("."));
}
else if (navigator.userAgent.search("Firefox") >= 0) {
	browserName = "mozilla";
	version = parseFloat(navigator.appVersion.split(".").slice(0,2).join("."));
}
else if (navigator.userAgent.search("Safari") >= 0 && navigator.userAgent.search("Chrome") < 0) {
	browserName = "safari";
	version = parseFloat(navigator.appVersion.split(".").slice(0,2).join("."));
}
else if (navigator.userAgent.search("Chrome") >= 0){
	browserName = "chrome";
	version = parseFloat(navigator.appVersion.split(".").slice(0,2).join("."));
}
else{
	browserName = "other";
	version = parseFloat(navigator.appVersion.split(".").slice(0,2).join("."));
}
<?
//wigii_anchor cookie reload process and check
?>
if(window.location.hash){
	$.cookie('wigii_anchor', '#'+window.location.href.split('#')[1],  { path: '/' });
} else {
	$.cookie('wigii_anchor', '#',  { path: '/' });
}
self.location = "<?=(HTTPS_ON ? "https" : "http" );?>://<?=$_SERVER['HTTP_HOST']?><?=$_SERVER['REQUEST_URI']?>"+window.location.hash;
self.location.reload();
</script>
</HEAD>
<BODY>
Wigii® is a web based software allowing management of any kind of data (contact, document, calendar, and any custom types). Find out documentation on <a href="http://www.wigii-system.net">http://www.wigii-system.net</a>.
</BODY>
</HTML><?

exit;

