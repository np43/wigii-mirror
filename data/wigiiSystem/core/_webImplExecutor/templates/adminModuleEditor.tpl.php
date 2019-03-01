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
 *  @copyright  Copyright (c) 2016  Wigii.org
 *  @author     <http://www.wigii.org/system>      Wigii.org 
 *  @link       <http://www.wigii-system.net>      <https://github.com/wigii/wigii>   Source Code
 *  @license    <http://www.gnu.org/licenses/>     GNU General Public License
 */

/*
 * Created on 18 janv. 10
 * by LWR
 */


if(!isset($transS)) $transS = ServiceProvider::getTranslationService();
if(!isset($ac)) $ac = $this->getAdminContext($p);
if(!isset($configS)) $configS = $this->getConfigurationContext();

?><div id="adminModuleEditor_detail" ><?
	//display the toolbar:
	$crtWigiiNamespace = $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl();
	$crtModule = $exec->getCrtModule()->getModuleUrl();
	$workingModule = $ac->getWorkingModule()->getModuleUrl();

	$exec->addJsCode(" adminModuleEditorDetailOnResize(); ");

	?><div id="elementDetail_toolbar" class="T" style="" ><?
		?><div class="commands" ><?
			$activities = array();
			$activities[] = "moduleEditorNew";
			$activities[] = "moduleEditorEdit";
			$activities[] = "moduleEditorDelete";
			$activities[] = "moduleEditorZip";
			//$activities[] = "moduleEditorCalc"; // deprecated since 03.02.2016, use multiple modify or batch instead.
			$activities[] = "moduleEditorRemoveEmailNotification";
			$activities[] = "moduleEditorNewNamespace";
			$activities[] = "moduleEditorRemoveNamespace";
			foreach($activities as $act){
				$func = null;
				switch($act){
					case "moduleEditorNew":
						$img = '<img src="'.SITE_ROOT_forFileUrl.'/images/icones/26px/new.png"/> ';
						break;
					case "moduleEditorEdit":
						$img = '<img src="'.SITE_ROOT_forFileUrl.'/images/icones/26px/edit.png"/> ';
						break;
					case "moduleEditorDelete":
						$img = '<img src="'.SITE_ROOT_forFileUrl.'/images/icones/26px/cancel.png"/> ';
						break;
					case "moduleEditorZip":
						$img = '<img src="'.SITE_ROOT_forFileUrl.'/images/icones/26px/zip.png"/> ';
						$func = "download('$crtWigiiNamespace/$crtModule/download/$act/$workingModule');";
						continue 2;
					case "moduleEditorCalc":
						$img = '<img src="'.SITE_ROOT_forFileUrl.'/images/icones/tango/22x22/actions/document-properties.png" style="margin-top:4px;margin-right:2px;"/> ';
						break;
					case "moduleEditorNewNamespace":
						$img = '<img src="'.SITE_ROOT_forFileUrl.'/images/icones/tango/22x22/actions/list-add.png" style="margin-top:4px;margin-right:2px;"/> ';
						break;
					case "moduleEditorRemoveNamespace":
						$img = '<img src="'.SITE_ROOT_forFileUrl.'/images/icones/tango/22x22/actions/list-remove.png" style="margin-top:4px;margin-right:2px;"/> ';
						break;
					case "moduleEditorRemoveEmailNotification":
						$img = '<img src="'.SITE_ROOT_forFileUrl.'/images/icones/26px/alert.png" /> ';
						break;
					default: $img = null;
				}
				?><div class="<?=$act;?>" onmouseover="showHelp(this, '<?=$transS->h($p, $act."_help");?>', 30, 'fromLeft', 0, 200, 0);" onmouseout="hideHelp();" onclick="if(!$(this).hasClass('disabled')) <? if($func) echo $func; else { ?>update('elementDialog/<?=$crtWigiiNamespace;?>/<?=$crtModule;?>/<?=$act;?>/<?=$workingModule;?>/'+adminModuleEditor_crtSelectedModuleConfig);<? } ?>" ><?=$img;?><div><?=$transS->t($p, $act);?></div></div><?
			}
		?></div><?
		?><div class="clear" style="padding:0px;"></div><?
	?></div><div class="clear"></div><?

	//remove none namespace creator options
	if(!$p->isWigiiNamespaceCreator()){
		$exec->addJsCode("$('#adminModuleEditor_detail div.commands>div:not(.moduleEditorEdit):not(.moduleEditorCalc):not(.moduleEditorRemoveEmailNotification)').addClass('disabled').hide();");
	}

?></div><?

?><div id="adminModuleEditor_list" class="BSB"><?

if($p->isWigiiNamespaceCreator()){
	//list all files in ClientConfigFolder
	$renderer = ConfigModuleFilenameRenderer::createInstance();
	$dir = scandir(CLIENT_CONFIG_PATH, 0);
	if($dir){
		//the two first entry are . and ..
		$dir = array_slice($dir, 2);
		foreach($dir as $file){
			$renderer->actOnFilename(utf8_encode($file));
		}
		$renderer->endRendering($p, $exec);
	}
} else {
	$renderer = ConfigModuleFilenameRenderer::createInstance();
	$configS->listModuleConfigFilesForWigiiNamespace($p, $renderer);
	$renderer->endRendering($p, $exec);
}

?></div><?

$exec->addJsCode('adminModuleEditorListOnResize()');
