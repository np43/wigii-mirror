<?php
/*
 * Created on 18 may 2010
 * by LWR
 * template for medair emailings
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

//this template is the same than Emailing-NoSignature, this is kept for backward compatibility

$this->displayEmailingMessage($p, $exec);

?>
</body>
</html><?


