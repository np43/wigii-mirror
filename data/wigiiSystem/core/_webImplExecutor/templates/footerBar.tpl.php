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
 * Created on 15 oct. 09
 * by LWR
 */
//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."start footerBar.tpl.php"] = microtime(true);
$this->executionSink()->publishStartOperation("TEMPLATE footerBar.tpl.php");

if(!isset($exec)) $exec = ServiceProvider::getExecutionService();
if(!isset($transS)) $transS = ServiceProvider::getTranslationService();
if(!isset($configS)) $configS = $this->getConfigurationContext();

$wakeupTimer = 1000*60*3; //"3min";
$postponeTimer = 1000*60*15; //"15min";
?><div id="footerBar" class="F" ><?

	if(false){ //with full html explaning what this is
	?><span id="wigiiVersionLabel"  UNSELECTABLE="on" style=" -moz-user-select: none; -khtml-user-select: none; unselectable:on; user-select: none; " ondblclick="update('<?=EMPTY_ANSWER_ID_URL;?>/<?=WigiiNamespace::EMPTY_NAMESPACE_URL;?>/<?=Module::EMPTY_MODULE_URL;?>/toggleSysConsole', function(){ $('#systemConsole').toggle(); });" ><?
		echo '<a target="_blank" href="http://code.google.com/p/wigii">'.VERSION_LABEL.'</a>';
		//echo ", ".$exec->getBrowserName()." ".$exec->getBrowserVersion();
	?></span><?
	?><span id="cronJobs" style="margin-left:10px;"><?
		?><label class="label"><?=$transS->t($p, "cronJobs");?>&nbsp;(<span id="cronJobsStart" class="H" style="padding:2px 2px 2px 2px; "><?=$transS->t($p, "cronJobsStartButton");?></span>)<span id="cronJobsCursor"> - </span></label><label id="cronJobsNb" class="cronJobsNb" style="margin-right:5px;">0</label>(<label id="cronJobsStop" class="H" style="padding:2px 2px 2px 2px;"><?=$transS->t($p, "cronJobsStop");?><span></span></label>)<?
	} else {
	?><span id="wigiiVersionLabel" UNSELECTABLE="on" style=" -moz-user-select: none; -khtml-user-select: none; unselectable:on; user-select: none; " ondblclick="update('<?=EMPTY_ANSWER_ID_URL;?>/<?=WigiiNamespace::EMPTY_NAMESPACE_URL;?>/<?=Module::EMPTY_MODULE_URL;?>/toggleSysConsole', function(){ $('#systemConsole').toggle(); });" ><?
		echo '<a target="_blank" href="http://code.google.com/p/wigii">'.VERSION_LABEL.'</a> -D- ';
		//echo ", ".$exec->getBrowserName()." ".$exec->getBrowserVersion();
	?></span><?

	?><span UNSELECTABLE="on" style=" -moz-user-select: none; -khtml-user-select: none; unselectable:on; user-select: none; " ><?
		echo '<span id="wigiiMaintenanceMessage" style="color:#fff; background-color:#f00; font-weight:bold;font-size:14px">'.(defined('MAINTENANCE_Message') && MAINTENANCE_Message ? '&nbsp;&nbsp;'.MAINTENANCE_Message.'&nbsp;&nbsp;' : '').'</span>';
	?></span><?

	?><span id="cronJobs" style="margin-left:10px;"><?
		?><label class="label"><span id="cronJobsStart" class="H" style="padding:2px 2px 2px 2px; ">task</span><span id="cronJobsCursor"> - </span></label><label id="cronJobsNb" class="cronJobsNb" style="margin-right:5px;">0</label>(<label id="cronJobsStop" class="H" style="padding:2px 2px 2px 2px;">stop<span></span></label>)<?
	}

	if(true){
	if($wakeupTimer === "never"){
		$wakeupJsCode = "cronJobsWorkingFunction();";
	} else {
		$wakeupJsCode = "" .
			"$('#cronJobsNb').stopTime('cronJobsWakeup');" .
			"$('#cronJobsNb').everyTime($wakeupTimer, 'cronJobsWakeup', function(i){ " .
				"cronJobsWorkingFunction(); " .
			"});" .
			"cronJobsWorkingFunction();" .
			"";
	}
		$exec->addJsCode("" .
				"$wakeupJsCode" .
				"setListenersToCronJob(function(){ $wakeupJsCode }, $postponeTimer);" .
				"");
	}
	?></span><?

	?><span id="userFeedbackOnSystem" class=""><?
		?><img src="<?=SITE_ROOT_forFileUrl."images/icones/18px/feedbacks.png";?>" /> <a href="#" onclick="if(!$(this).hasClass('disabled') && crtModuleName) { <?=$exec->getUpdateJsCode($p->getRealUserId(), "'+crtRoleId+'", "'+crtWigiiNamespaceUrl+'", "'+crtModuleName+'", 'feedbackDialog', 'giveGeneralFeedback', 'giveFeedback');?> } else { self.location='#'; } return false; "><? echo $transS->t($p, "giveGeneralFeedback");?></a><?
	?></span><?
?></div><?

//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."end footerBar.tpl.php"] = microtime(true);
$this->executionSink()->publishEndOperation("TEMPLATE footerBar.tpl.php");