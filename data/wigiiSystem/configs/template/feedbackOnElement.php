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
 * Created on 27 nov. 09
 * by LWR
 * template for feedbacks
 */
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title><? echo (defined("SITE_TITLE"))? SITE_TITLE : "";?></title>
<base href="<? echo SITE_ROOT; ?>" >
<meta name="copyright" content="wigii" >
<style type="text/css">
body { font-family:Arial; }
p { margin:0px; }
</style>
</head>
<body>
<?
if(!isset($configS)) $configS = $this->getWigiiExecutor()->getConfigurationContext();
$companyColor = $configS->getParameter($p, null, "companyColor");
$rCompanyColor = $configS->getParameter($p, null, "companyReverseColor");
$companyLogo = $configS->getParameter($p, null, "companyLogo");
$companyLogoMargins = $configS->getParameter($p, null, "companyLogoMargin");
if(!$companyColor) $companyColor = "#5E71FE";
if(!$rCompanyColor) $rCompanyColor = "#fff";
if(!$companyLogo) $companyLogo = "images/gui/wigii_logo_24.jpg";
if(!$companyLogoMargins) $companyLogoMargins = "2px 5px 2px 15px";

//include banner
?><div style="background-color:<?=$companyColor;?>;color:<?=$rCompanyColor;?>;vertical-align:middle;padding:4px;" ><?
	?><?=$configS->getParameter($p, null, "siteTitle");?><?
?></div><?


$this->displayFeedbackTitle($p, $exec);

$this->displayFeedbackText($p, $exec);

$this->displayFeedbackRecord($p, $exec);

$this->displayButtonViewElement($p, $exec);

?></body>
</html><?



