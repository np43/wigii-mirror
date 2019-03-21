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
 * Template to print a Medidata XML invoice request, for customer copy or for the insurance
 * Created by CWE on 19.03.2019
 */
$transS = ServiceProvider::getTranslationService();
$config = $this->getWigiiExecutor()->getConfigurationContext();
$trm = $this->getTrm();

$companyColor = $config->getParameter($p, null, "companyColor");
$rCompanyColor = $config->getParameter($p, null, "companyReverseColor");
$companyLogo = $config->getParameter($p, null, "companyLogo");

$medidataXml = $options->getValue('medidataXml');
$medidataFL = $options->getValue('medidataFL');
$xmlVal = function($xml,$xpath,$attrName=null,$dataType="string") use($medidataXml,$medidataFL,$trm) {
    $returnValue = $medidataFL->getXmlValue((isset($xml)?$xml:$medidataXml),'invoice',$xpath,$attrName);
    switch($dataType) {
        case "string": $returnValue=(string)$returnValue; break;
        // converts to Wigii date format
        case "date": $returnValue = date('Y-m-d H:m:i',strtotime((string)$returnValue));break;
        case "ssn": $returnValue = $trm->evalfx(fx('txtFormatSwissSsn',$returnValue)); break;
        case "numeric":
            if(empty($returnValue)) $returnValue = 0;
            $returnValue = $trm->doFormatForNumeric($returnValue);
            break;
        case "xml": break;
        default: $returnValue=(string)$returnValue; break;
    }
    return $returnValue;
};
$tiersXml = $options->getValue('invoiceTiersXml');
$treatmentXml = $xmlVal($medidataXml,'/request/payload/body/treatment',null,'xml');

?><!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8"/>
<title><?=$options->getValue('invoiceFileName');?></title>
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
	margin:10mm;	
}
@media print {
    /* to avoid page break within p or div */
	div:not(.orderBody), p {
		page-break-inside: avoid;
	}
}
div.field.patient {
    width:100%;    
}
div.field.patient div.label {
    width:25%;    
}
div.field.patient div.value {
    width:75%;    
}
div.invoiceServices {
	float:none;	
}
/* Invoice services table */
table.invoiceServices {
	width:100%;
	margin-top:1em;
	margin-bottom:2em;	
}
table.invoiceServices td:nth-child(5),table.invoiceServices td:nth-child(7),table.invoiceServices td:last-child,
table.invoiceServices th:nth-child(5),table.invoiceServices th:nth-child(7),table.invoiceServices th:last-child {
	text-align:right;
}
table.invoiceServices td, table.invoiceServices th {
	padding:0.5em 1em 0.5em 0em;
}
table.invoiceServices td:last-child, table.invoiceServices th:last-child {
	padding-right:0em;
}
table.invoiceServices td.designation {
	font-weight:bold;
}
/* VAT summary table */
table.vatSummary {
	width:100%;
}
table.vatSummary td:nth-child(2),table.vatSummary td:nth-child(3),table.vatSummary td:last-child,
table.vatSummary th:nth-child(2),table.vatSummary th:nth-child(3),table.vatSummary th:last-child {
	text-align:right;
}
table.vatSummary td, table.vatSummary th {
	padding:0.5em 1em 0.5em 0em;
}
table.vatSummary td:last-child, table.vatSummary th:last-child {
	padding-right:0em;
}
</style>
</head>
<body>
<h2 style="margin-bottom:20px;"><?=$options->getValue('invoiceTitle');?></h2>
<div class="field noFieldset" style="width:100%;border:1px solid;"><div class="value fieldGroup" style="width:100%;padding:0">
	<div class="field noFieldset" style="width:100%;">
    	<div class="label" style="width:100%;font-weight:bold;">Document</div>
        <div class="value fieldGroup" style="width:100%;position:relative;margin-left:90px;margin-top:-35px;">
        	<div class="field noFieldset" style="width:50%;"><div class="value fieldGroup" style="width:100%;">
            	<div class="field patient">
                	<div class="label">Identification</div>
                	<div class="value"><?=$xmlVal($medidataXml,'/request/payload/invoice','request_timestamp').'&nbsp;&nbsp;'.date('m.d.Y H:i:s',$xmlVal($medidataXml,'/request/payload/invoice','request_timestamp'))?></div>
                </div>
            </div></div>
            <div class="field noFieldset" style="width:50%;"><div class="value fieldGroup" style="width:100%;">
            	<div class="field patient">
                	<div class="value">Page:&nbsp;1</div>
                </div>
            </div></div>
        </div>
    </div>
    <div class="field noFieldset" style="width:100%;">
    	<div class="label" style="width:100%;font-weight:bold;">Auteur<br/>facture</div>
        <div class="value fieldGroup" style="width:100%;position:relative;margin-left:90px;margin-top:-40px;">
        	<div class="field noFieldset" style="width:50%;"><div class="value fieldGroup" style="width:100%;">
            	<div class="field patient">
                	<div class="label">No GLN(B)</div>
                	<div class="value"><?=$xmlVal($tiersXml,'./biller','ean_party')?></div>
                </div>
                <div class="field patient">
                	<div class="label">No RCC(B)</div>
                	<div class="value"><?=$xmlVal($tiersXml,'./biller','zsr')?></div>
                </div>
            </div></div>
            <div class="field noFieldset" style="width:50%;"><div class="value fieldGroup" style="width:100%;">
            	<div class="field patient">
                	<div class="value"><?=$xmlVal($tiersXml,'./biller/company/companyname')?></div>
                </div>
                <div class="field patient">
                	<div class="value"><?=$xmlVal($tiersXml,'./biller/company/postal/street').'&nbsp;&nbsp;&nbsp;'.$xmlVal($tiersXml,'./biller/company/postal/zip').' '.$xmlVal($tiersXml,'./biller/company/postal/city')?></div>
                </div>
            </div></div>
        </div>
    </div>
    <div class="field noFieldset" style="width:100%;">
    	<div class="label" style="width:100%;font-weight:bold;">Four. de<br/>prestations</div>
        <div class="value fieldGroup" style="width:100%;position:relative;margin-left:90px;margin-top:-40px;">
        	<div class="field noFieldset" style="width:50%;"><div class="value fieldGroup" style="width:100%;">
            	<div class="field patient">
                	<div class="label">No GLN(P)</div>
                	<div class="value"><?=$xmlVal($tiersXml,'./provider','ean_party')?></div>
                </div>
                <div class="field patient">
                	<div class="label"><?=($options->getValue('invoiceLawType')=='LAI'?'No NIF(P)':'No RCC(P)')?></div>
                	<div class="value"><?=($options->getValue('invoiceLawType')=='LAI'?$xmlVal($options->getValue('invoiceCaseXml'),'./','nif'):$xmlVal($tiersXml,'./provider','zsr'))?></div>
                </div>
            </div></div>
            <div class="field noFieldset" style="width:50%;"><div class="value fieldGroup" style="width:100%;">
            	<div class="field patient">
                	<div class="value"><?=$xmlVal($tiersXml,'./provider/company/companyname')?></div>
                </div>
                <div class="field patient">
                	<div class="value"><?=$xmlVal($tiersXml,'./provider/company/postal/street').'&nbsp;&nbsp;&nbsp;'.$xmlVal($tiersXml,'./provider/company/postal/zip').' '.$xmlVal($tiersXml,'./provider/company/postal/city')?></div>
                </div>
            </div></div>
        </div>
    </div>
</div></div>
<div class="field noFieldset" style="width:100%;border:1px solid;">
	<div class="label" style="width:100%;font-weight:bold;">Patient/client</div>
    <div class="value fieldGroup" style="width:100%;position:relative;margin-left:90px;margin-top:-35px;">
    	<div class="field noFieldset" style="width:50%;"><div class="value fieldGroup" style="width:100%;">
        	<div class="field patient">
            	<div class="label">Nom</div>
            	<div class="value"><?=$xmlVal($tiersXml,'./patient/person/familyname')?></div>
            </div>
            <div class="field patient">
            	<div class="label">Prénom</div>
            	<div class="value"><?=$xmlVal($tiersXml,'./patient/person/givenname')?></div>
            </div>
            <div class="field patient">
            	<div class="label">Rue</div>
            	<div class="value"><?=$xmlVal($tiersXml,'./patient/person/postal/street')?></div>
            </div>
            <div class="field patient">
            	<div class="label">NPA</div>
            	<div class="value"><?=$xmlVal($tiersXml,'./patient/person/postal/zip')?></div>
            </div>
            <div class="field patient">
            	<div class="label">Localité</div>
            	<div class="value"><?=$xmlVal($tiersXml,'./patient/person/postal/city')?></div>
            </div>
            <div class="field patient">
            	<div class="label">Date de naissance</div>
            	<div class="value"><?=$trm->doFormatForDate($xmlVal($tiersXml,'./patient','birthdate','date'))?></div>
            </div>
            <div class="field patient">
            	<div class="label">Sexe</div>
            	<div class="value"><?=($xmlVal($tiersXml,'./patient','gender')=='male'?'M':'F')?></div>
            </div>
            <div class="field patient">
            	<div class="label">Date cas</div>
            	<div class="value"><?=$trm->doFormatForDate($xmlVal($options->getValue('invoiceCaseXml'),'./','case_date','date'))?></div>
            </div>
            <div class="field patient">
            	<div class="label">No cas</div>
            	<div class="value"><?=$xmlVal($options->getValue('invoiceCaseXml'),'./','case_id')?></div>
            </div>
            <div class="field patient">
            	<div class="label">No AVS</div>
            	<div class="value"><?=$xmlVal($tiersXml,'./patient','ssn','ssn')?></div>
            </div>
            <div class="field patient">
            	<div class="label">No Cada</div>
            	<div class="value">&nbsp;</div>
            </div>
            <div class="field patient">
            	<div class="label">No assuré</div>
            	<div class="value"><?=$xmlVal($tiersXml,'./patient','ssn','ssn')?></div>
            </div>
            <div class="field patient">
            	<div class="label">Canton</div>
            	<div class="value"><?=$xmlVal($treatmentXml,'./','canton')?></div>
            </div>
            <div class="field patient">
            	<div class="label">Copie de facture</div>
            	<div class="value"><?=(intval($xmlVal($medidataXml,'/request/payload','copy'))>0?"Oui":"Non")?></div>
            </div>
            <div class="field patient">
            	<div class="label">Type de remb.</div>
            	<div class="value"><?=($options->getValue('invoiceTiersType')=='tiers_payant'?'TP':'TG')?></div>
            </div>
            <div class="field patient">
            	<div class="label">Loi</div>
            	<div class="value"><?=$options->getValue('invoiceLawType')?></div>
            </div>
            <div class="field patient">
            	<div class="label">No contrat</div>
            	<div class="value">&nbsp;</div>
            </div>
            <div class="field patient">
            	<div class="label">Traitement</div>
            	<div class="value"><?=$trm->doFormatForDate($xmlVal($treatmentXml,'./','date_begin','date')).' - '.$trm->doFormatForDate($xmlVal($treatmentXml,'./','date_end','date'))?></div>
            </div>
            <div class="field patient">
            	<div class="label">No/Nom entreprise</div>
            	<div class="value">&nbsp;</div>
            </div>
            <div class="field patient">
            	<div class="label">Rôle/Localité</div>
            	<div class="value"><?=$medidataFL->printProviderRole($medidataXml);?></div>
            </div>
        </div></div>
        <div class="field noFieldset" style="width:50%;"><div class="value fieldGroup" style="width:100%;">
        	<div class="field patient">
            	<div class="label"><?=($xmlVal($tiersXml,'./debitor','ean_party')!=null?"No GLN":"&nbsp;")?></div>
            	<div class="value"><?=$xmlVal($tiersXml,'./debitor','ean_party')?></div>
            </div>
            <div class="field patient" style="margin-bottom:200px;">
            	<div class="value" style="width:100%;padding:40px 20px;"><?=$medidataFL->printDebitorAddress($medidataXml);?></div>
            </div>
            <div class="field patient">
            	<div class="label">Date/No GaPrCh</div>
            	<div class="value">&nbsp;</div>
            </div>
            <div class="field patient">
            	<div class="label">Date/No facture</div>
            	<div class="value"><?=$trm->doFormatForDate($xmlVal($medidataXml,'/request/payload/invoice','request_date','date')).' / '.$xmlVal($medidataXml,'/request/payload/invoice','request_id')?></div>
            </div>
            <div class="field patient">
            	<div class="label">Date/No rappel</div>
            	<div class="value"><?=($options->getValue('invoiceType')=='reminder'?$trm->doFormatForDate($xmlVal($medidataXml,'/request/payload/reminder','request_date','date')).' / '.$xmlVal($medidataXml,'/request/payload/reminder','request_id'):'&nbsp;')?></div>
            </div>
            <div class="field patient">
            	<div class="label">Motif traitement</div>
            	<div class="value"><?=$medidataFL->printTreatmentReason($medidataXml);?></div>
            </div>
        </div></div>    	
    </div>
</div>
<div class="field noFieldset" style="width:100%;border:1px solid;">
	<div class="label" style="width:100%;font-weight:bold;">Mandataire</div>
    <div class="value fieldGroup" style="width:50%;position:relative;margin-left:90px;margin-top:-30px;">
    	<div class="field patient">
        	<div class="label">No GLN/No RCC</div>
        	<div class="value">/</div>
        </div>
    </div>
</div>
<div class="field noFieldset" style="width:100%;border:1px solid;">
	<div class="label" style="width:100%;font-weight:bold;">Diagnostic</div>
    <div class="value fieldGroup" style="width:50%;position:relative;margin-left:90px;margin-top:-30px;">
    	<div class="field patient">
        	<div class="label">&nbsp;</div>
        	<div class="value">&nbsp;</div>
        </div>
    </div>
</div>
<div class="field noFieldset" style="width:100%;border:1px solid;">
	<div class="label" style="width:100%;font-weight:bold;">Liste GLN</div>
    <div class="value fieldGroup" style="width:50%;position:relative;margin-left:90px;margin-top:-30px;">
    	<div class="field patient">
        	<div class="label">&nbsp;</div>
        	<div class="value"><?=($options->getValue('nbServices')>0?$medidataFL->printGLNList($options->getValue('invoiceServices')):'&nbsp;')?></div>
        </div>
    </div>
</div>
<div class="field noFieldset" style="width:100%;border:1px solid;">
	<div class="label" style="width:100%;font-weight:bold;">Commentaire</div>
    <div class="value fieldGroup" style="width:50%;position:relative;margin-left:90px;margin-top:-30px;">
       	<div class="field patient">
           	<div class="label">&nbsp;</div>
           	<div class="value">&nbsp;</div>
        </div>
    </div>
</div>
<?php if($options->getValue('nbServices')>0) { ?>
<div class="invoiceServices"><table class="invoiceServices">
<thead>
	<tr><th>Date</th><th>Tarif</th><th>Code tarifaire</th><th>Quan.</th><th>Prix</th><th>VPt</th><th>TVA</th><th>Montant</th></tr>
</thead>
<tbody>
<?php foreach($options->getValue('invoiceServices') as $invoiceService) { ?>
	<tr>
    	<td><?=$trm->doFormatForDate($xmlVal($invoiceService,'./','date_begin','date'))?></td>
    	<td><?=$invoiceService["tariff_type"]?></td>
    	<td><?=$invoiceService["code"]?></td>
    	<td><?=$invoiceService["quantity"]?></td>
    	<td><?=$invoiceService["unit"]?></td>
    	<td><?=$invoiceService["unit_factor"]?></td>
    	<td><?=$invoiceService["vat_rate"]?></td>
    	<td><?=$invoiceService["amount"]?></td>    	
	</tr>
	<tr>
    	<td>&nbsp;</td>
    	<td>&nbsp;</td>
    	<td class="designation" colspan="5"><?=$invoiceService["name"]?></td>
    	<td>&nbsp;</td>    	
	</tr>
 <?php } ?>
</tbody>
</table></div>
<?php } ?>
<div class="field noFieldset" style="width:25%;"><div class="value fieldGroup" style="width:100%;">
<table class="vatSummary">
<thead>
	<tr><th>Code</th><th>Taux</th><th>Montant</th><th>TVA</th></tr>
</thead>
<tbody>
<?php $n=0; foreach($tiersXml->xpath('./invoice:balance/invoice:vat/invoice:vat_rate') as $vatDetail) {?>
	<tr>
    	<td><?=$n?></td>
    	<td><?=$vatDetail["vat_rate"]?></td>
    	<td><?=$vatDetail["amount"]?></td>
    	<td><?=$vatDetail["vat"]?></td>
	</tr>
 <?php $n++; } ?>
</tbody>
</table>
</div></div>
<div class="field noFieldset" style="width:25%;"><div class="value fieldGroup" style="width:100%;">
	<div class="field patient">
    	<div class="label">No TVA:</div>
    	<div class="value"><?=$trm->evalfx(fx('txtFormatSwissVATNumber',$xmlVal($tiersXml,'./balance/vat','vat_number')))?></div>
    </div>
    <div class="field patient">
    	<div class="label">Monnaie:</div>
    	<div class="value"><?=$xmlVal($tiersXml,'./balance','currency')?></div>
    </div>
    <div class="field patient">
    	<div class="label">IBAN:</div>
    	<div class="value"><?=$trm->evalfx(fx('txtFormatIBAN',$xmlVal($medidataXml,'/request/payload/body/esrQR','iban')))?></div>
    </div>
    <div class="field patient">
    	<div class="label">No référence:</div>
    	<div class="value"><?=$trm->evalfx(fx('txtFormatSwissBvr',$xmlVal($tiersXml,'/request/payload/body/esrQR','reference_number'),true))?></div>
    </div>
    <div class="field patient">
    	<div class="value" style="width:100%"><?=$medidataFL->printPaiementConditions($medidataXml)?></div>
    </div>    
</div></div>
<div class="field noFieldset" style="width:25%;"><div class="value fieldGroup" style="width:100%;">
	<div class="field patient">
    	<div class="label">Acompte:</div>
    	<div class="value"><?=$xmlVal($tiersXml,'./balance','amount_prepaid','numeric')?></div>
    </div>    
</div></div>
<div class="field noFieldset" style="width:25%;"><div class="value fieldGroup" style="width:100%;">
	<div class="field patient">
    	<div class="label">Montant total:</div>
    	<div class="value"><?=$xmlVal($tiersXml,'./balance','amount','numeric')?></div>
    </div>
    <div class="field patient">
    	<div class="label">dont pr. obl.:</div>
    	<div class="value"><?=$xmlVal($tiersXml,'./balance','amount_obligations','numeric')?></div>
    </div>
    <div class="field patient">
    	<div class="label">&nbsp;</div>
    	<div class="value">&nbsp;</div>
    </div>
    <div class="field patient">
    	<div class="label">Montant dû:</div>
    	<div class="value"><?=$xmlVal($tiersXml,'./balance','amount_due','numeric')?></div>
    </div>
</div></div>
</body>
</html><?php 