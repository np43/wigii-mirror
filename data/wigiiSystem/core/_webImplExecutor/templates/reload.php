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
 * LWR
 */

header("Content-Type: text/html; charset=UTF-8");
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<HTML>
<HEAD>
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
version = parseFloat(jQuery.browser.version.split(".").slice(0,2).join("."));
if(jQuery.browser.msie) browserName = "msie";
else if(jQuery.browser.mozilla) browserName = "mozilla";
else if(jQuery.browser.safari) browserName = "safari";
else browserName = "other";
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
Wigii is a web based system allowing management of any kind of data (contact, document, calendar, and any custom types). Find out project page on: <a href="http://code.google.com/p/wigii/">http://code.google.com/p/wigii/</a>.
</BODY>
</HTML><?

exit;

