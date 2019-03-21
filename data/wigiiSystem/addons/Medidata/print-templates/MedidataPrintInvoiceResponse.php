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
 *  @copyright  Copyright (c) 2019  Wigii.org
 *  @author     <http://www.wigii.org/system>      Wigii.org
 *  @link       <http://www.wigii-system.net>      <https://github.com/wigii/wigii>   Source Code
 *  @license    <http://www.gnu.org/licenses/>     GNU General Public License
 */

/**
 * Wigii interface with Medidata (https://www.medidata.ch/)
 * Template to print a Medidata XML invoice response
 * Created by CWE on 08.03.2019
 */
$transS = ServiceProvider::getTranslationService();
$config = $this->getWigiiExecutor()->getConfigurationContext();
$trm = $this->getTrm();

$companyColor = $config->getParameter($p, null, "companyColor");
$rCompanyColor = $config->getParameter($p, null, "companyReverseColor");
$companyLogo = $config->getParameter($p, null, "companyLogo");

$medidataXml = $options->getValue('medidataXml');
$medidataFL = $options->getValue('medidataFL');
$messageTitle = $options->getValue('messageTitle');
$responseType = $options->getValue('responseType');
$invoiceErrors = $options->getValue('invoiceErrors');
$invoiceNotifications = $options->getValue('invoiceNotifications');
$reimbursementXml = $options->getValue('reimbursementXml');

?><!DOCTYPE html>
<html>
<head>
<title><?=$messageTitle?></title>
<meta charset="UTF-8"/>
<link rel="stylesheet" href="<?=SITE_ROOT_forFileUrl;?>assets/css/bootstrap/bootstrap.min.css" type="text/css" media="all" />
<link rel="stylesheet" href="<?=SITE_ROOT_forFileUrl;?>assets/css/bootstrap/bootstrap-submenu.min.css" type="text/css" media="all" />
<link rel="stylesheet" href="<?=SITE_ROOT_forFileUrl;?>assets/css/wigii_<?=ASSET_REVISION_NUMBER;?>.css" type="text/css" media="all" />
<link rel="stylesheet" href="<?=SITE_ROOT_forFileUrl;?>assets/css/theme.css.php" type="text/css" media="all" />
<?php if(file_exists(CLIENT_WEB_PATH.CLIENT_NAME.".css")){?>
<link rel="stylesheet" href="<?=SITE_ROOT_forFileUrl.CLIENT_WEB_URL.CLIENT_NAME;?>.css?v=<?=ASSET_REVISION_NUMBER;?>" type="text/css" media="all" />
<?php } if(file_exists(CLIENT_CONFIG_PATH.CLIENT_NAME.".css")){ ?>
<style>
    <?php include CLIENT_CONFIG_PATH.CLIENT_NAME.".css";?>
</style>
<?php } ?>
<style>
body {
	margin:20px;
	margin-left:40px;
}
@page {
	size: A4;
	margin:2em;
	margin-left:3em;
}
@media print {
    /* to avoid page break within p or div */
	div:not(.orderBody), p {
		page-break-inside: avoid;
	}
}

</style>
</head>
<body>
<h2 style="margin-bottom:20px;"><?=$messageTitle?></h2>
<div class="field" style="width:50%;">
	<div class="label" style="width:25%;">Contact direct à l'assurance</div>
	<div class="value" style="width:75%;"><?=$medidataFL->printInsuranceDirectContact($medidataXml);?></div>
</div>
<div class="field" style="width:50%;">
	<div class="label" style="width:25%;">Assurance</div>
	<div class="value" style="width:75%;"><?=$medidataFL->printInsuranceContact($medidataXml);?></div>
</div>
<div class="field noFieldset" style="width:50%;"><div class="value fieldGroup" style="width:100%;">
	<div class="field" style="width:100%;">
    	<div class="label" style="width:25%;">Concerne</div>
    	<div class="value" style="width:75%;"><?=$medidataFL->printPatientContact($medidataXml);?></div>
    </div>
    <div class="field" style="width:100%;">
    	<div class="label" style="width:25%;">Date de naissance</div>
    	<div class="value" style="width:75%;"><?=$trm->doFormatForDate($options->getValue('patientBirthDate'));?></div>
    </div>    
    <div class="field" style="width:100%;">
    	<div class="label" style="width:25%;">No AVS</div>
    	<div class="value" style="width:75%;"><?=$trm->evalfx(fx('txtFormatSwissSsn',$options->getValue('patientSSN')));?></div>
    </div>
</div></div>
<div class="field noFieldset" style="width:50%;"><div class="value fieldGroup" style="width:100%;">
	<div class="field" style="width:100%;">
    	<div class="label" style="width:25%;">Facture</div>
    	<div class="value" style="width:75%;"><?=($responseType=='reminder'?'Rappel no '.$options->getValue('reminderLevel').' sur facture '.$options->getValue('customerOrderNumber'):$options->getValue('customerOrderNumber'));?></div>
    </div>
    <div class="field" style="width:100%;">
    	<div class="label" style="width:25%;">Du</div>
    	<div class="value" style="width:75%;"><?=$trm->doFormatForDate(($responseType=='reminder'?$options->getValue('reminderDate'):$options->getValue('invoiceDate')));?></div>
    </div>
</div></div>
<div class="field" style="width:100%;">
	<div class="label" style="width:100%;">Explication</div>
	<div class="value" style="width:100%;"><?=$options->getValue('messageExplanation');?></div>
</div>
<?php if($invoiceNotifications) { ?>
<div class="field" style="width:100%;">
	<div class="label" style="width:25%;">Notifications de l'assurance</div>
    <div class="value fieldGroup SBIB ui-corner-all" style="width:100%;">
 <?php foreach($invoiceNotifications as $invoiceNotification) { ?>
 	<div class="field" style="width:20%;">
    	<div class="value" style="width:100%;"><?=$invoiceNotification["code"];?></div>
    </div>
    <div class="field" style="width:80%;">
    	<div class="value" style="width:100%;"><?=$invoiceNotification["text"];?></div>
    </div>
 <?php } ?>   	
    </div>
</div>
<?php } ?>
<?php if($invoiceErrors) { ?>
<div class="field" style="width:100%;">
	<div class="label" style="width:25%;">Erreurs rapportées</div>
    <div class="value fieldGroup SBIB ui-corner-all" style="width:100%;">
 <?php foreach($invoiceErrors as $invoiceError) { ?>
 	<div class="field" style="width:20%;">
    	<div class="value" style="width:100%;"><?=$invoiceError["code"];?></div>
    </div>
    <div class="field" style="width:80%;">
    	<div class="value" style="width:100%;">
    		<?=$invoiceError["text"];?>
    		<br/>
    		<i><?=$trm->evalfx(fx('implode',' ',
    		    fx('postpend',fx('prepend','Facture, ligne no ',$invoiceError["record_id"]),':'),
    		    fx('postpend',fx('prepend','la valeur &apos;',$invoiceError["error_value"]),'&apos; est erronée.'),
    		    fx('postpend',fx('prepend','La valeur correcte est &apos;',$invoiceError["valid_value"]),'&apos;')
    		 ));?></i>    		
    	</div>
    </div>
 <?php } ?>   	
    </div>
</div>
<?php } ?>
<?php if(isset($reimbursementXml)) { ?>
<div class="field" style="width:100%;">
	<div class="label" style="width:25%;">Demande de remboursement de l&apos;assurance</div>
    <div class="value fieldGroup SBIB ui-corner-all" style="width:100%;">
     	<div class="field noFieldset" style="width:50%;">
            <div class="value fieldGroup" style="width:100%;">
             	<div class="field" style="width:100%;">
             		<div class="label" style="width:25%;">Montant</div>
                	<div class="value" style="width:75%;"><?=$medidataFL->printReimbursementAmount($reimbursementXml);?></div>
                </div>
                <div class="field" style="width:100%;">
                	<div class="label" style="width:25%;">Pour le</div>
                	<div class="value" style="width:75%;"><?=$trm->doFormatForDate($options->getValue('reimbursementDueDate'));?></div>
                </div>
            </div>
        </div>
        <div class="field" style="width:50%;">
        	<div class="label" style="width:25%;">A verser à</div>
        	<div class="value" style="width:75%;"><?=$medidataFL->printReimbursementPaiementDetails($reimbursementXml);?></div>
        </div>
    </div>
</div>
<?php } ?>
<?php if($options->getValue('nbAttachements')>0) { ?>
<div class="field" style="width:50%;">
	<div class="label" style="width:100%;">Documents attachés</div>
    <div class="value" style="width:100%;"><?=$medidataFL->printAttachementList($options->getValue('messageAttachements'));?></div>
</div>
<?php } ?>
</body>
</html><?php 