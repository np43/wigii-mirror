<?php
/*
 * Created on 27 nov. 09
 * by LWR
 * template for feedbacks
 */
// version G99
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

</style><?
?></head>
<body>
<?

//include banner
$configS = $this->getConfigService();
$companyColor = $configS->getParameter($p, null, "companyColor");
$rCompanyColor = $configS->getParameter($p, null, "companyReverseColor");
$companyLogo = $configS->getParameter($p, null, "companyLogo");
$companyLogoMargins = $configS->getParameter($p, null, "companyLogoMargin");
if(!$companyColor) $companyColor = "#5E71FE";
if(!$rCompanyColor) $rCompanyColor = "#fff";
if(!$companyLogo) $companyLogo = "images/gui/wigii_logo_24.jpg";
if(!$companyLogoMargins) $companyLogoMargins = "2px 5px 2px 15px";

?><div style="background-color:<?=$companyColor;?>;color:<?=$rCompanyColor;?>;vertical-align:middle;" ><?
	?><img src="<?=SITE_ROOT_forFileUrl.$companyLogo;?>" style="border-color:<?=$companyColor;?>;border-width:<?=$companyLogoMargins;?>;" /><?
?></div><?

//include notification message
//$elementPList is filled only on multiple element operations
$this->displayNotificationMessage($p, $eventName, $entityName, $module, $rec, $gObj, $elementPList);

?></body></html><?




