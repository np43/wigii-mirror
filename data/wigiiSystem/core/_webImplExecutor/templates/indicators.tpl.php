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
 * Created on 25 February 2011
 * by LWR
 */
if(!isset($transS)) $transS = ServiceProvider::getTranslationService();
if(!isset($p)) $p = $authS->getMainPrincipal();
if(!isset($exec)) $exec = ServiceProvider::getExecutionService();
if(!isset($configS)) $configS = $this->getConfigurationContext();


$indicatorsAreShown= $p->getValueInRoleContext("indicators_areShown");
$indicatorList = $this->getIndicatorList($p, $exec); //$p->getValueInRoleContext("indicators_list");

?><div class="closeIndicators" style="display:<?=($indicatorList != null && !$indicatorList->isEmpty() && $indicatorsAreShown ? "inherit" : "none");?>;">( <font class="L H"><?=$transS->t($p, "closeIndicators");?></font> )</div><?
?><div class="showIndicators" style="display:<?=($indicatorList != null && !$indicatorList->isEmpty() && !$indicatorsAreShown ? "inherit" : "none");?>;">( <font class="L H"><?=$transS->t($p, "showIndicators");?> <?=$indicatorList->count();?></font>)</div><?

if($indicatorsAreShown && $indicatorList!=null && !$indicatorList->isEmpty()){
	foreach($indicatorList->getListIterator() as $indicatorId=>$indicator){
		?><div id="<?=$indicatorId;?>" class="indicator"><?
			echo $indicator->getLabel().": ";
			echo '<span class="value">'.$indicator->getValue().'</span>';
		?></div><?
	}
	$exec->addJsCode("update('JSCode/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/evaluateIndicators');");
}

$exec->addJsCode("setListenersToIndicator();");


