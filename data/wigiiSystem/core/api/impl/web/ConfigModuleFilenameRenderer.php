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
 * Created on 16 avr. 10
 * by LWR
 */

class ConfigModuleFilenameRenderer {
	
	public static function createInstance(){
		$r = new self();
		$r->reset();
		return $r;
	}
	
	private $first;
	public function reset(){
		$this->first = true;
	}
	
	//regroup per extension
	private $result;
	public function actOnFilename($path){
		$info = pathinfo($path);
		ob_start();
		?><div id="file<?=str_replace(".","", $info['basename']);?>" class="H configFile" style="<?=$style;?>" onmouseover=" showHelp(this, '<?=prepareTextForInfoBuble($help, true);?>', 25, 'fromLeft', 0, 200, 0)" onmouseout="hideHelp();" ><?
		echo $info['basename'];
		?></div><?
		$this->result[$info['extension']] .= ob_get_clean();
	}
	
	public function endRendering($p, $exec){
		ksort($this->result);
		foreach($this->result as $ext=>$html){
			?><div class="SBB grayFont" style="border-top:none;border-right:none;border-left:none;" ><?
			echo ".".$ext;
			?></div><?
			echo $html;
			
		}
		$exec->addJsCode("
$('#adminModuleEditor_list div.configFile').dblclick(function(e){
	$('#adminModuleEditor_detail div.commands>div.moduleEditorEdit').click();
});
$('#adminModuleEditor_list div.configFile').click(function(e){
	$('#adminModuleEditor_list *').removeClass('S');
	$(this).addClass('S');
	$('#adminModuleEditor_detail div.commands>div.moduleEditorEdit').removeClass('disabled'); ".
($p->isWigiiNamespaceCreator() ? "
	$('#adminModuleEditor_detail div.commands>div.moduleEditorDelete').removeClass('disabled');
" : "
")."
	adminModuleEditor_crtSelectedModuleConfig = $(this).text();
});

if(adminModuleEditor_crtSelectedModuleConfig!=null){
	$('#file'+adminModuleEditor_crtSelectedModuleConfig.replace(/\./g,'')).addClass('S');
}

if($('#adminModuleEditor_list .S').length == 0){
	$('#adminModuleEditor_detail div.commands>div.moduleEditorEdit, #adminModuleEditor_detail div.commands>div.moduleEditorDelete').addClass('disabled');
}
");
	
	}
}


