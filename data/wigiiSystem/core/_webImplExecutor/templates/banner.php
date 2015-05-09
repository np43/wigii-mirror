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


//since 30/08/2013 the standard design is without any banner...

return;



/*
 * Created on 29 June 2011
 * by LWR
 */
if(!isset($configS)) $configS = $this->getConfigurationContext();
$companyColor = $configS->getParameter($p, null, "companyColor");
$rCompanyColor = $configS->getParameter($p, null, "companyReverseColor");
?><div id="companyBanner" style="" ><? //4E5666- 3E4552 - 2D323B
	?><div style="<?=($companyColor ? "background-color:".$companyColor.";color:".$rCompanyColor.";" : "background-color:#3E4552;");?>border-bottom:0px solid #93a4c1; overflow:hidden;width:100%;height:30px;float:left;" ><?
		?><img src="<?=SITE_ROOT_forFileUrl;?>images/gui/wigii_logo_24.jpg" style="margin-left:10px;margin-right:10px;margin-top:2px;border:1px solid #d0d2da; "/><?
		?><span style="color:#f1f3f7;font-weight:bold;font-size:24px;vertical-align:top;"><?=$configS->getParameter($p, null, "siteTitle");?></span><?
if(false){
		?><span style="color:#fff;font-weight:normal;font-size:12px;vertical-align:top;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?="HOST ".SITE_ROOT;?></span><?
		?><span style="color:#fff;font-weight:normal;font-size:12px;vertical-align:top;">&nbsp;&nbsp;&nbsp;<?="Client ".CLIENT_NAME;?></span><?
		?><span style="color:#fff;font-weight:normal;font-size:12px;vertical-align:top;">&nbsp;&nbsp;&nbsp;<?="DB ".DB_NAME;?></span><?
		if(defined("REDIRECT_ALL_EMAILS_TO")) { ?><span style="color:#fff;font-weight:normal;font-size:12px;vertical-align:top;"><?="&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;EMAIL redirection setup to ".REDIRECT_ALL_EMAILS_TO;?></span><?
		} else { ?><span style="color:#fff;font-weight:normal;font-size:12px;vertical-align:top;"><?="&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;NO email redirection setup!!";?></span><? }
}
	?></div><?
?></div><div class="clear"></div><?


