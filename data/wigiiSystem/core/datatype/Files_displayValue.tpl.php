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

/**
 * Created on 3 déc. 09 by LWR
 * Modified by Medair (AMA,CWE) in 2016.09 to integrate Box
 */
$exec = $this->getExecutionService();
$idAnswer = $exec->getIdAnswer();
if(!$idAnswer) $idAnswer = "mainDiv";
$boxServiceFormExecutor = TechnicalServiceProvider::getBoxServiceFormExecutor();

if(!isset($elS)) $elS = ServiceProvider::getElementService();
if(!isset($transS)) $transS = ServiceProvider::getTranslationService();

$fieldXml = $field->getXml();
$elementId = $this->getRecord()->getId();
if(!$this->isForNotification()){
	$fieldId = $this->getDetailRenderer()->getDetailId()."__".$fieldName;
	// Activates File info refresh from Box on first call
	$boxServiceFormExecutor->setSyncBoxFileInfoIntoWigii($fieldId); 
}
$readonly = $this->getRecord()->getWigiiBag()->isReadonly($fieldName);
$disabled = $this->getRecord()->getWigiiBag()->isDisabled($fieldName);

/******************
 * Preview only!!!!
 ******************/
if(false && $fieldXml["displayPreviewOnly"]=="1" && !$this->isForNotification()){
	//no value
/******************
 * Normal view
 ******************/
} else if($fieldXml["htmlArea"]=="1" &&
	($fieldXml["displayContentInDetail"]=="1" && !$this->isForNotification())
//	|| ($this->isForNotification() && $fieldXml["displayContentInNotification"]=="1")
	){
	$size = $this->formatValueFromRecord($fieldName, "size", $this->getRecord());

	$this->put('<div class="clear"></div>');

	//filename, date and download
	$date = $this->formatValueFromRecord($fieldName, "date", $this->getRecord());
	$path = $this->formatValueToPreventInjection($this->getRecord()->getFieldValue($fieldName, "path"));
	$name = $this->formatValueFromRecord($fieldName, "name", $this->getRecord());
	$type = $this->formatValueFromRecord($fieldName, "type", $this->getRecord());

	//the ssrc is used for the media unzip, pdf, or html type
	if(is_a($this->getRecord(), "ActivityRecord")){
		$ssrc = $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/download/".$this->getRecord()->getActivity()->getActivityName()."/".$field->getFieldName()."/".$this->getRecord()->getAttachedRecord()->getId();
	} else if($this->isForExternalAccess()) {
		$ssrc = $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/downloadFromExternalAccess/".$exec->getCrtParameters(0)."/download/".$elementId."/".$field->getFieldName();
	} else {
		$ssrc = $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/download/".$elementId."/".$field->getFieldName();
	}
	$src = SITE_ROOT.$ssrc;

	//file details
	$this->put('<div class="fdet" style="'.($this->isForNotification() ? 'margin-top:5px;' : '').'width:'.$parentWidth.'px;" >');

	if($fieldXml["displayLabel"]=="1") $this->put('<font class="grayFont" >'.$this->t($fieldName, $field->getXml()).':</font><br />');
	$this->put('<img class="prev" style="vertical-align:top;" src="'.SITE_ROOT_forFileUrl."images/preview/prev.18.html.png".'" />&nbsp;&nbsp;');
	if(!$this->isForNotification()){
		$this->put('<a class="H fileDownload" href="#" target="_self" onclick="longDownload('."'$src','$fieldId'".')">');
		$this->put($name.$type.'('.$size.')');
		$this->put('</a>');
		//$exec->addJsCode("setListenerToDownloadFile('$fieldId', '".$field->getFieldName()."', '$src');");
		//read button is not usefull if displayInContent is enable
	} else {
		//link button
		$elS->displayElementId($this->getP(), $this->getRecord(), $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), null, true, $name.$type.' ('.$size.')');
	}
	$this->put("&nbsp;&nbsp;");
	$this->put('<font class="grayFont" >('.$date.')</font>');

	$this->put('</div><div class="clear"></div><br />');

	if(!$this->isForNotification() && !$this->isForPrint()){
		$this->put('<div style="width:'.($parentWidth).'px;overflow-y:auto;overflow-x:auto;" >');
	}

	//content
	$this->put($this->formatValueFromRecord($fieldName, "textContent", $this->getRecord()));
	if(!$this->isForNotification() && !$this->isForPrint()){
		$this->put("</div>");
	}

	$this->put('<div class="clear"></div><br />');

//} else if((($this->isForPrint() || $this->isForNotification() || ($fieldXml["displayContentInNotification"]=="1" && $fieldXml["displayContentInDetail"]=="1")) && $fieldXml["htmlArea"]=="1")){
//	$this->put($this->formatValueFromRecord($fieldName, "textContent", $this->getRecord()));
} else {
	$size = $this->formatValueFromRecord($fieldName, "size", $this->getRecord());
	$date = $this->formatValueFromRecord($fieldName, "date", $this->getRecord());
	$path = $this->getRecord()->getFieldValue($fieldName, "path");
	$name = $this->formatValueFromRecord($fieldName, "name", $this->getRecord());
	$type = $this->formatValueFromRecord($fieldName, "type", $this->getRecord());

	
	// if File is linked to Box, tags field with box service
	$boxElement = (string)$fieldXml["enableBoxIntegration"];
	$boxGeneral = (string)$this->getConfigService()->getParameter($this->getP(), $exec->getCrtModule(), "enableBoxIntegration");
	if(!$this->isForNotification() && ($boxElement == "1" || $boxGeneral == "1" && $boxElement !== "0") && strstr($path, "box://")){
		$formId = $this->getDetailRenderer()->getDetailId()."__$fieldName";
		$exec->addJsCode("domElementAddWigiiService('#$formId', 'box')");
	}
	
	
	//the ssrc is used for the media unzip, pdf, or html type
	if(is_a($this->getRecord(), "ActivityRecord")){
		$ssrc = $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/download/".$this->getRecord()->getActivity()->getActivityName()."/".$field->getFieldName()."/".$this->getRecord()->getAttachedRecord()->getId();
	} else if($this->isForExternalAccess()) {
		$ssrc = $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/downloadFromExternalAccess/".$exec->getCrtParameters(0)."/download/".$elementId."/".$field->getFieldName();
	} else {
		$ssrc = $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/download/".$elementId."/".$field->getFieldName();
	}
	$src = SITE_ROOT.$ssrc;

	//file details

	//if parentWidth is small, then display the file details as short as possible
	$isSmall = ($parentWidth && $parentWidth < 300) || $fieldXml["displayPreviewOnly"]=="1";


	//filename and date
	if(!$this->isForNotification()) $this->put('<div class="fdet '.($isSmall ? 'small' : '').'" style="'.($fieldXml["displayPreviewOnly"]=="1" ? 'font-weight:normal;' : '').'width:'.$parentWidth.'px;" >');
	if($fieldXml["displayLabel"]=="1") $this->put('<font class="grayFont" >'.$this->t($fieldName, $field->getXml()).':</font><br />');
	if(!$this->isForNotification()) $this->put('<a class="H fileDownload" style="white-space:normal;" href="#" target="_self">');
	$this->put($name.$type);
//	if($isSmall) $this->put('<span style="display:inline;" class="ui-icon ui-icon-arrowthickstop-1-s">&nbsp;&nbsp;</span>');
	if(!$this->isForNotification()) $this->put('</a>');
	if($fieldXml["displayPreviewOnly"]!="1"){
		$this->put(' ');
		$this->put('<font class="grayFont">(');
		if(($this->isForNotification() || $isSmall) && $size>0) $this->put($size.', ');
		$this->put($date);
		$this->put(')</font>');
	}

	// Adds icon pointing to Box folder containing the file	
	if(!$this->isForNotification() && ($boxElement == "1" || $boxGeneral == "1" && $boxElement !== "0") && strstr($path, "box://") && $boxServiceFormExecutor->isBoxEnabled()
			 && $this->getConfigService()->getParameter($this->getP(), $exec->getCrtModule(), "hideBoxLink") != '1'){
		$boxFileId = str_replace("box://", "", $path);
		try {
			$folderId = $boxServiceFormExecutor->getFolderId($this->getP(), $boxFileId);
			// Medair (LMA) 09.12.2016 Adds an hyperlink which opens the file into Box with user account
			$this->put(' <a href="#" style="font-size: 8pt;" class="linkBox" onclick="window.open(\''.$boxServiceFormExecutor->getBoxWebInterfaceUrl().'/files/0/f/'.$folderId[0].'/1/f_'.$boxFileId.'\',\'_blank\');">'. $this->t("boxFileOpen"). '</a>');
		}
		catch(Exception $boxExc) {/* doesn't display icon in case of exception */}
	}
	if(!$this->isForNotification() && !$isSmall) $this->put('</div>');
	if(!$this->isForNotification() && !$isSmall) $this->put('<div class="clear"></div>');

	//add the download button
	if(!$this->isForNotification()){
		//download button
		//$this->put('<a href="'.$src.'" target="_self" style="text-decoration:none;"><div class="generalButton G ui-corner-all " style="margin-top:4px;"><img style="border:none; float:left;" src="'.SITE_ROOT_forFileUrl.'images/icones/sanscons/download.png" /><span style="float:left; margin-left:5px; ">'.$this->t("download").'</span></div></a>');
		$this->put(' <a class="fdet fileDownload" href="#" target="_self">');
		if($isSmall) $this->put('<img class="H" title="'.$this->h("detailDownloadButton").' ('.$size.')'.'" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/16x16/actions/down.png" />');
		else $this->put('<div class="H G SBIB"><img src="'.SITE_ROOT_forFileUrl.'images/icones/22x22/Icon-download-22.png" /><span>'.$this->t("detailDownloadButton").' <font>('.$size.')</font></span></div>');
		$this->put('</a>');
		$exec->addJsCode("setListenerToDownloadFile('$fieldId', '".$field->getFieldName()."', '$src');");
	}

	/**
	 * Add other specific buttons based on the file type
	 */
	if(!$this->isForPrint() && !$this->isForNotification() && !is_a($this->getRecord(), "ActivityRecord")){
		// CWE 20.09.2016: if file is located into Box, then always proposes a preview (from Box)
		if(strstr($path, "box://")) {
			if($isSmall){
				$this->put('<img class="htmlPreview H" title="'.$this->t("detailViewButton").'" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/16x16/actions/system-search.png" />');
			} else {
				$this->put('<div class="mediaPreview htmlPreview H G SBIB" ><img class="box Icon" style="cursor: pointer" src="'.SITE_ROOT_forFileUrl.'images/gui/box_icon_16x16.jpg" /><span>'.$this->t("detailViewButton").'</span></div>');
			}
			$exec->addJsCode("setListenerToPreviewFile('$fieldId', '".$field->getFieldName()."', '$ssrc', '".time()."');");
		}
		else {
			$mediaId = $fieldName.'_file_media';
			switch ($type){
				case ".aif":
				case ".aiff":
				case ".mid":
				case ".midi":
				case ".ogg":
				case ".au":
				case ".aac":
				case ".rm":
				case ".ra":
					break;
				case ".mp3":
				case ".wav":
				case ".wma":
					//the player is after the download button
					break;
				case ".wmv":
				case ".avi":
				case ".mpg":
				case ".mpeg":
				case ".mp4":
				case ".h264":
				case ".3g2":
				case ".mov":
				case ".ram":
				case ".swf":
					//no player for those format
					break;
				case ".flv":
				case ".h264":
				case ".h.264":
				case ".H.264":
					//the player is after the download button
					break;
				case ".zip":
					if(!doesZipHaveAnIndexFile(FILES_PATH.$path)) {
						break;
					}
					if($isSmall){
						$this->put('<img class="htmlPreview  H" title="'.$this->t("detailViewButton").'" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/16x16/actions/system-search.png" />');
					} else {
						$this->put('<div class="mediaPreview htmlPreview H G SBIB" ><img src="'.SITE_ROOT_forFileUrl.'images/icones/22x22/Icon-view-22.png" /><span>'.$this->t("detailViewButton").'</span></div>');
					}
					$exec->addJsCode("setListenerToUnzipForViewing('$fieldId', '".$field->getFieldName()."', '$ssrc', '".time()."');");
					break;
				case ".doc":
				case ".docx":
				case ".dot":
				case ".dotx":
				case ".xls":
				case ".xlsx":
				case ".xlsm":
				case ".xlt":
				case ".xltx":
				case ".xltm":
				case ".pps":
				case ".ppsx":
				case ".ppsm":
				case ".ppt":
				case ".pptx":
				case ".pptm":
				case ".msg":
				case ".odt":
				case ".rtf":
	//			case ".svg":
	//			case ".sql":
				case ".db":
				case ".dbf":
					if($this->getConfigService()->getParameter($this->getP(), $exec->getCrtModule(), "useGoogleServiceToPreviewOfficeDocuments")=="1"){
						//http://docs.google.com/gview?url=http://domain.com/path/docFile.doc&embedded=true
						if($isSmall){
							$this->put('<img class="htmlPreview H" title="'.$this->t("detailViewButton").'" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/16x16/actions/system-search.png" />');
						} else {
							$this->put('<div class="mediaPreview htmlPreview H G SBIB" ><img src="'.SITE_ROOT_forFileUrl.'images/icones/22x22/Icon-view-22.png" /><span>'.$this->t("detailViewButton").'</span></div>');
						}
						$exec->addJsCode("setListenerToUnzipForViewing('$fieldId', '".$field->getFieldName()."', '$ssrc', '".time()."');");
					}
					break;
				case ".pdf":
				case ".html":
				case ".htm":
				case ".svg":
				case ".txt":
				case ".csv":
				case ".sql":
				case ".config":
				case ".xml":
				case ".bat":
				case ".dat":
				case ".data":
					if($isSmall){
						$this->put('<img class="htmlPreview H" title="'.$this->t("detailViewButton").'" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/16x16/actions/system-search.png" />');
					} else {
						$this->put('<div class="mediaPreview htmlPreview H G SBIB" ><img src="'.SITE_ROOT_forFileUrl.'images/icones/22x22/Icon-view-22.png" /><span>'.$this->t("detailViewButton").'</span></div>');
					}
					$exec->addJsCode("setListenerToPreviewFile('$fieldId', '".$field->getFieldName()."', '$ssrc', '".time()."');");
	
	//				if($fieldXml["forceDisplayContent"]=="1"){
	//					$exec->addJsCode('previewFile(\''.$src.'\', \''.time().'\');');
	//				}
					if($fieldXml["forceDisplayContent"]=="1"){
						$exec->addJsCode("$('#".$this->getDetailRenderer()->getDetailId()."_".$fieldName." .read').click()");
					}
					//$this->put('<div class="print" onclick="var w = window.open(\''.$src.'/integrated\'); setTimeout(function(){ w.print(); }, 3000);" ><img src="'.SITE_ROOT_forFileUrl.'images/icones/tango/22x22/actions/print.png" /><span>'.$this->t("").'</span></div>');
	
	//				$this->put('<div class="lockAndModify'.$field->getFieldName().' generalButton G ui-corner-all" style="margin-top:4px; margin-right:5px;" onclick="update(\'elementDialog/'.$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/element/lockAndModify/".$elementId."/".$field->getFieldName().'\');" ><img style="margin-left:5px;vertical-align:middle;" src="'.SITE_ROOT_forFileUrl.'images/icones/sanscons/edit.png" /><span class="" style="margin-left:5px;margin-right:5px;">'.$this->t("lockAndModify").'</span></div>');
	//				//if no modify rights then mask the lockAndModify button
	//				$exec->addJsCode("
	//if($('#elementDetail_toolbar .onlyWriteRights').length==0){
	//	$('#elementDialog .elementDetail .field .value div.lockAndModify".$field->getFieldName()." ').hide();
	//}
	//");
					break;
				case ".jpg":
				case ".jpeg":
				case ".gif":
				case ".png":
				case ".bmp":
					//create a tempPublicUrl to preview the content
					if($isSmall){
						$this->put('<img class="imgPreview H" title="'.$this->t("detailViewButton").'" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/16x16/actions/system-search.png" />');
					} else {
						$this->put('<div class="mediaPreview imgPreview H G SBIB" ><img src="'.SITE_ROOT_forFileUrl.'images/icones/22x22/Icon-view-22.png" /><span>'.$this->t("detailViewButton").'</span></div>');
					}
					//$this->put('<div class="print" style="margin-top:4px; margin-right:5px;" onclick="var w = window.open(\''.$src.'/integrated\'); setTimeout(function(){ w.print(); }, 3000);" ><img src="'.SITE_ROOT_forFileUrl.'images/icones/tango/22x22/actions/print.png" /><span>'.$this->t("").'</span></div>');
					$exec->addJsCode("setListenerToPreviewFile('$fieldId', '".$field->getFieldName()."', '$ssrc', '".time()."');");
					break;
			}
		}

		//add the check-in/out button
		if(!$this->isForExternalAccess() && $fieldXml["displayPreviewOnly"]!="1" && $this->getRecord() instanceof Element && !$readonly && !$disabled){
			if(!$this->getRecord()->isState_blocked()){
				// 1) the element is not locked -> check-out button
				$isCheckout = false;
				if(!$this->getRecord()->isState_locked()){
					$isCheckout = true;
					if($isSmall) $this->put('<img class="H checkOutIn" title="'.$this->h("detailCheckoutButton").' ('.$size.')'.'" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/16x16/actions/down-grey.png" />');
					else $this->put('<div class="checkOutIn H G SBIB"><img src="'.SITE_ROOT_forFileUrl.'images/icones/22x22/Icon-lock-22.png" /><span>'.$this->t("detailCheckoutButton").'</span></div>');
				}
				// 2) the element is locked by you -> check-in button
				if($this->getRecord()->isState_locked() && $this->getRecord()->getSys_user() == $exec->getExecPrincipal()->getRealUserId()){
					$isCheckout = false;
					if($isSmall) $this->put('<img class="H checkOutIn" title="'.$this->h("detailCheckinButton").' ('.$size.')'.'" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/16x16/actions/up-grey.png" />');
					else $this->put('<div class="checkOutIn H G SBIB" ><img src="'.SITE_ROOT_forFileUrl.'images/icones/22x22/Icon-unlock-22.png" /><span>'.$this->t("detailCheckinButton").'</span></div>');
				}
				$exec->addJsCode("setListenersToCheckInOutFiles('".$exec->getIdAnswer()."', $elementId, '$fieldId', '".$field->getFieldName()."', '".($isCheckout ? "download/checkoutFile" : "element/checkinFile")."', '".($isCheckout ? $this->t("checkoutTitleText") : $this->t("checkinTitleText"))."', '".$this->t("ok")."', '".$this->t("cancel")."');");
			}
		}
	}

	if($isSmall) $this->put('</div>');

	if($fieldXml["displayContentInDetail"]=="1"){
		//display content
		if($type == ".jpg"  || $type == ".jpeg"  || $type == ".gif"  || $type == ".png"  || $type == ".bmp"){
			$this->put('</div></div><div style="width:100%;" class="field" ><img style="vertical-align: bottom; max-width:100%;" class="field" src="'.$ssrc.'" /></div><div><div>');
		} else if($type == ".svg" || $type == ".txt"){
			if($this->getRecord()->getFieldValue($fieldName, "size") < 1024*1024){
				$this->put('</div></div><div style="width:100%;overflow:hidden;" class="field" >'.file_get_contents(FILES_PATH.$path).'</div><div><div>');
			}
		}
	}

	//for video the player is after the download button
	if(!$this->isForPrint() && !$this->isForNotification() && !is_a($this->getRecord(), "ActivityRecord")){
		$mediaId = $fieldName.'_file_media';
		switch ($type){
			case ".mp3":
			case ".wav":
			case ".wma":
				//flowplayer
				$this->put('<a href="'.$src.'/test'.$type.'" style="float:left;margin-top:5px;width:'.$parentWidth.'px;height:26px" id="'.$mediaId.'"></a>');
				$exec->addJsCode("displayAudioPlayer('$mediaId');");
				break;
			case ".flv":
			case ".h264":
			case ".h.264":
			case ".H.264":
				//VIDEO files
				$width = $this->getDetailRenderer()->getTotalWidth();
//				//flowplayer
				$this->put('</div>'); //end value div, to begin a full width player
				$this->put('<div style="float:left;padding-bottom:5px;padding-top:-5px;">'); //open a new div in field
				$this->put('<a class="SBB" href="'.$src.'/test'.$type.'" style="float:left;width:'.$width.'px;height:300px;" id="'.$mediaId.'"></a>');
				$exec->addJsCode("displayVideoPlayer('$mediaId');");
				break;
		}
	}

}



