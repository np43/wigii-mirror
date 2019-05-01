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
/*
 * Development environment banner
 * Created by LWR on 29 June 2011
 * Modified by CWE on 30.04.2019
 */
?><div id="companyBanner" style="" ><?
        ?><div style="background-color:DarkOrange;overflow:hidden;width:100%;float:left;font-weight:bold;color:#ddd;" ><?
                ?>&nbsp;&nbsp;&nbsp;DEV SYSTEM!!<?
                ?>&nbsp;&nbsp;&nbsp;<?="HOST ".SITE_ROOT;?><?
                ?>&nbsp;&nbsp;&nbsp;<?="Client ".CLIENT_NAME;?><?
                ?>&nbsp;&nbsp;&nbsp;<?="DB ".DB_NAME;?><?
                if(defined("REDIRECT_ALL_EMAILS_TO")) { ?><?="&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;EMAIL redirection setup to ".REDIRECT_ALL_EMAILS_TO;?><?
                } else { ?><?="&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;NO email redirection setup!!";?><? }
        ?></div><?
?></div><div class="clear"></div><?
return;