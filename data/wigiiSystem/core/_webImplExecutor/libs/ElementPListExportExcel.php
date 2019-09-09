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

/**
 * Created on 6 oct. 09 by LWR
 * Modified by Medair in 2016 for maintenance purposes (see SVN log for details)
 */

class ElementPListExportExcel extends ElementPListWebImplWithWigiiExecutor implements ElementDataTypeSubfieldVisitor, TreeInDepthVisitor {

	private $factory;
	public function setFactory($factory) {$this->factory = $factory; }
	protected function getFactory(){
		if(!isset($this->factory)){
		    // Medair (CWE) 08.10.2018: generate xlsx format
			//$this->factory = "Excel5";
		    $this->factory = "Excel2007";
		}
		return $this->factory;
	}

	private $lcTemp;
	protected function getLCTemp(){ return $this->lcTemp; }
	public function setLCTemp($lcTemp){ $this->lcTemp = $lcTemp; }


	public static function getContentTypeHeader(){
	    // Medair (CWE) 08.10.2018: generate xlsx format
		//return 'application/vnd.ms-excel';
		return 'application/excel';
	}

	public static function getFileExtensions(){
	    // Medair (CWE) 08.10.2018: generate xlsx format
		//return 'xls';
        return 'xlsx';
	}
	
	protected function preventFormulasInCellValue($valToInsert){
		if($valToInsert && $valToInsert[0]=="=") $valToInsert = "'".$valToInsert;
		return $valToInsert;
	}
	
	private $groupPList;
	public function setGroupPList($gl){ $this->groupPList = $gl; }
	protected function getGroupPList(){
		return $this->groupPList;
	}

	private $grouping = null;
	public function setTitleGrouping(){ $this->grouping = "title"; }
	public function isTitleGrouping(){ return $this->grouping === "title"; }
	//this option is deprecated from 22 January 2012. Reason: not used and item within first group are not inclueded. --> this is not understandable by user...
//	public function setSheetGrouping(){ $this->grouping = "sheet"; }
//	public function isSheetGrouping(){ return $this->grouping === "sheet"; }
	public function setNoGrouping(){ $this->grouping = null; }
	public function isNoGrouping(){ return $this->grouping === null; }

	private $isLocalLink = null;
	public function setLocalLinks($isLocalLink){
		$this->isLocalLink = $isLocalLink;
	}
	public function isLocalLink(){ return $this->isLocalLink; }
	public static function createInstance($wigiiExecutor, $listContext) {
	    return self::createInstanceForP($wigiiExecutor, $listContext, null);
	}
	public static function createInstanceForP($wigiiExecutor, $listContext,$p){
		$elPl = new self();
		$elPl->setP($p);
		//update the list context with a fieldSelector matching the export activity:
		$transS = ServiceProvider::getTranslationService();
		$configS = $wigiiExecutor->getConfigurationContext();
		$p = $elPl->getP();
		$exec = ServiceProvider::getExecutionService();
		$fsl = FieldSelectorListForActivity::createInstance();
		$fsl->setSelectedLanguages(array($transS->getLanguage()=>$transS->getLanguage()));
		$fieldList = FormFieldList::createInstance(null);
		$configS->getFields($p, $exec->getCrtModule(), null, $fieldList);
		foreach($fieldList->getListIterator() as $field){
			if($field->getDataType()==null) continue;
			$fieldXml = $field->getXml();
			if((string)$fieldXml["excelExport"]=="none") continue;
			if(!$fsl->containsField($field->getFieldName())) $fsl->addFieldSelector($field->getFieldName());
			if($field->isCalculated() && $field->shouldCalculateOnFetch()){
				$field->getFuncExpDependencies($fsl);
			}
		}
		$listContext->setFieldSelectorList($fsl);
		$elPl->setListContext($listContext);
		$elPl->setWigiiExecutor($wigiiExecutor);
		return $elPl;
	}

	/**
	 * Adds an element to the list
	 * throws ListException::ALREADY_EXISTS if we try to put a second time the same element in the list
	 */
//	private $elementIds; //array which stores the elements ids already added
//	public function getElementIds(){ return $this->elementIds; }
	private $elementPs;
	public function getListIterator(){
		return $this->elementPs;
	}
	public function isEmpty(){
		return $this->elementPs==null;
	}
	public function count(){
		if($this->elementPs == null) return null;
		return count($this->elementPs);
	}


	public function actOnSubfield($field, $dataType, $subFieldName, $subFieldType, $sqlType, $userSelectedSubField, $lang=null){
//		echo alert($field->getFieldName()." ".$subFieldName."\n");
		$transS = ServiceProvider::getTranslationService();
		$elS = ServiceProvider::getElementService();
		$fieldSelectorList = $this->getListContext()->getFieldSelectorList();
		//if a field selector is defined, then check that this field and subfield is in
		if($fieldSelectorList != null){
			if(	!$fieldSelectorList->containsFieldSelector($field->getFieldName(), $subFieldName) &&
				!$fieldSelectorList->containsField($field->getFieldName())) return;
		}
		//bypassing none usefull information:
		$exl = $this->getExcelObj();
		$l = $this->getCrtLineForPage($exl->getActiveSheetIndex());
		$c = $this->getCrtColForPage($exl->getActiveSheetIndex());
		$style = $exl->getActiveSheet()->getStyle(num2letter($c).$l);
		$cell = $exl->getActiveSheet()->getCell(num2letter($c).$l);

		$noFormat = false; //if define at true in this switch, then the value will not be formated with the TRM
		$collapseCol = false; //if defined, this force the column to be collpased independantly of the excelExport function (used for some sub fields)
		switch($dataType->getDataTypeName()){
			case "Files":
				switch($subFieldName){
					//do not export the SysInfo subfields
					case "sys_user":
					case "sys_username":
					case "sys_date":
					case "sys_creationUser":
					case "sys_creationUsername":
					case "sys_creationDate":
					case "type":
					case "size":
					case "mime":
					case "path":
					case "date":
					case "user":
					case "username":
					case "content":
					case "textContent":
					case "thumbnail": return;
					case "name":
						if($this->first){
							$this->addCell($this->getTrm()->t($field->getFieldName(), $field->getXml()), $this->firstCell);
						} else {
							$name = $this->getTrm()->formatValueFromRecord($field->getFieldName(), "name", $this->crtElement);
							$ext = $this->getTrm()->formatValueFromRecord($field->getFieldName(), "type", $this->crtElement);
							$size = $this->getTrm()->formatValueFromRecord($field->getFieldName(), "size", $this->crtElement);
							$date = $this->getTrm()->formatValueFromRecord($field->getFieldName(), "date", $this->crtElement);
							$cell->getHyperlink()->setUrl($this->crtElement->getId()."-".$field->getFieldName()."-".stripAccents($name).$ext);
							$style->applyFromArray($this->urlStyle);
							$this->addCell(($name ? $name.$ext." (".$size.", ".$date.")" : ""), $this->firstCell);
						}
				}
				break;
			case "TimeRanges":
				switch($subFieldName){
					//do not export the SysInfo subfields
					case "sys_user":
					case "sys_username":
					case "sys_date":
					case "sys_creationUser":
					case "sys_creationUsername":
					case "sys_creationDate":
					case "isAllDay":
					case "endTime":
					case "endDate": return;
					case "begTime":
						if($this->first){
							$this->addCell($this->getTrm()->t($field->getFieldName(), $field->getXml()), $this->firstCell);
						} else {
							$isAllDay = $this->crtElement->getFieldValue($field->getFieldName(), "isAllDay");
							$begDate = $this->crtElement->getFieldValue($field->getFieldName(), "begDate");
							$begTime = $this->crtElement->getFieldValue($field->getFieldName(), "begTime");
							$endDate = $this->crtElement->getFieldValue($field->getFieldName(), "endDate");
							$endTime = $this->crtElement->getFieldValue($field->getFieldName(), "endTime");
							$value = "";
							if($isAllDay){
								$begTime = null;
								$endTime = null;
							}
							if(!empty($begDate)){
								// si la date de début est vide, tous les champs sont considérés comme vides
								$value .= Dates::formatDisplay($begDate, "dd mmm yyyy",null);
								if($begDate == $endDate || empty($endDate)) null;
								if(!$isAllDay && !empty($begTime)){
									$value .= " - ";
									$value .= Times::formatDisplay($begTime, "hh:mm");
								}
							}
							if(!empty($endDate)){
								if ((!empty($endDate) && $begDate != $endDate) || (!$isAllDay && !empty($endTime)))
								$value .= ' > ';
								if(!$isAllDay && !empty($endDate) && $begDate != $endDate) $value .= " ";
								if ($begDate != $endDate)
								$value .= Dates::formatDisplay($endDate, "dd mmm yyyy",null);
								if(!$isAllDay && !empty($endTime)){
									if (!empty($endDate) && $begDate != $endDate) $value .= " - ";
									$value .= Times::formatDisplay($endTime, "hh:mm");
								}
							}
							$this->addCell($value, $this->firstCell);
						}
				}
				break;
			case "Urls":
				switch($subFieldName){
					//do not export the SysInfo subfields
					case "sys_user":
					case "sys_username":
					case "sys_date":
					case "sys_creationUser":
					case "sys_creationUsername":
					case "sys_creationDate":
					case "target":
					case "name": return;
					case "url":
						if($this->first){
							$this->addCell($this->getTrm()->t($field->getFieldName(), $field->getXml()), $this->firstCell);
						} else {
							$name = $this->crtElement->getFieldValue($field->getFieldName(), "name");
							$url = $this->crtElement->getFieldValue($field->getFieldName(), "url");
							if($name == null) $name = $url;
							if($url){
								$cell->getHyperlink()->setUrl($url);
								$style->applyFromArray($this->urlStyle);
							}
							$this->addCell($name, $this->firstCell);
						}
				}
				break;
			case "Addresses":
				//do not export the SysInfo subfields
				switch($subFieldName){
					case "sys_user":
					case "sys_username":
					case "sys_date":
					case "sys_creationUser":
					case "sys_creationUsername":
					case "sys_creationDate":
						return;
				}
				switch($subFieldName){
					case "street":
					case "zip_code":
					case "state":
					case "country":
					case "city":
						if($this->first){
							$this->addCell($this->getTrm()->t($field->getFieldName(), $field->getXml())." ".$this->getTrm()->t($dataType->getDataTypeName()."_".$subFieldName), $this->firstCell);
						} else {
//							$street = $this->crtElement->getFieldValue($field->getFieldName(), "street");
//							$zip_code = $this->crtElement->getFieldValue($field->getFieldName(), "zip_code");
//							$city = $this->crtElement->getFieldValue($field->getFieldName(), "city");
//							$state = $this->crtElement->getFieldValue($field->getFieldName(), "state");
//							$country = $this->crtElement->getFieldValue($field->getFieldName(), "country");
//							$value = $street.($street ? " " : "").($zip_code || $city ? "\r\n" : "").($zip_code ? $zip_code." " : "").$city.($zip_code || $city ? " " : "").($state || $country ? "\r\n" : "").$state.($state && $country ? " / " : "").$country;
//							$this->addCell($value, $this->firstCell);
							if($subFieldName =="street"){
								$tempVal = $this->crtElement->getFieldValue($field->getFieldName(), $subFieldName);
								if($tempVal) $style->getAlignment()->setWrapText(true);
								$l = $this->getCrtLineForPage($exl->getActiveSheetIndex());
								$c = $this->incCrtColForPage($exl->getActiveSheetIndex());
								$exl->getActiveSheet()->setCellValue(num2letter($c).$l, $this->preventFormulasInCellValue($tempVal));
							} else {
								$this->addCell($this->crtElement->getFieldValue($field->getFieldName(), $subFieldName), $this->firstCell);
							}
						}
				}
				break;
			case "Booleans":
				//do not export the SysInfo subfields
				switch($subFieldName){
					case "sys_user":
					case "sys_username":
					case "sys_date":
					case "sys_creationUser":
					case "sys_creationUsername":
					case "sys_creationDate":
						return;
				}
				if($this->first){
					$this->addCell($this->getTrm()->t($field->getFieldName(), $field->getXml()), $this->firstCell);
				} else {
					$value = $this->crtElement->getFieldValue($field->getFieldName(), $subFieldName);
					$this->addCell(($value ? "X" : ""), $this->firstCell);
				}
				break;
			case "Emails":
				switch($subFieldName){
					case "sys_user":
					case "sys_username":
					case "sys_date":
					case "sys_creationUser":
					case "sys_creationUsername":
					case "sys_creationDate":
					case "proofKey":
					case "proof":
					case "externalConfigGroup":
					case "externalCode": return;
					case "externalAccessLevel":
					case "externalAccessEndDate":
					case "proofStatus":
						$collapseCol = true;
						return; //since 19 march 2012 we are no more exporting in excel the external access. this is too complex for normal user. To find this info use the export csv raw
				}
				if($this->first){
					$label = $field->getFieldName();
					$label = $this->getTrm()->t($field->getFieldName(), $field->getXml());
					if($subFieldName != "value") $label = $this->getTrm()->t($dataType->getDataTypeName()."_".$subFieldName);
					if($lang != null) $label .= " (".(ServiceProvider::getTranslationService()->getVisibleLanguage($lang)).")";
					$this->addCell($label, $this->firstCell);
				} else {
					$value = $this->crtElement->getFieldValue($field->getFieldName(), $subFieldName);
					if($subFieldName == "externalAccessEndDate"){
						$value = date("d.m.Y");
					}
					$this->addCell($value, $this->firstCell);
					if($subFieldName == "value" && $value){
						$cell->getHyperlink()->setUrl("mailto:".$value);
						$style->applyFromArray($this->urlStyle);
					}
				}
				break;
			default:
				//do not export the SysInfo subfields
				switch($subFieldName){
					case "sys_user":
					case "sys_username":
					case "sys_date":
					case "sys_creationUser":
					case "sys_creationUsername":
					case "sys_creationDate":
						return;
				}
				if($this->first){
					$label = $field->getFieldName();
					$label = $this->getTrm()->t($field->getFieldName(), $field->getXml());
					if($subFieldName != "value") $label .= " ".$this->getTrm()->t($dataType->getDataTypeName()."_".$subFieldName);
					if($lang != null) $label .= " (".(ServiceProvider::getTranslationService()->getVisibleLanguage($lang)).")";
					$this->addCell($label, $this->firstCell);
				} else {
					$value = $this->crtElement->getFieldValue($field->getFieldName(), $subFieldName);
					if($lang!=null) $value = $value[$lang];
					$this->addCell($this->getTrm()->formatValue($field->getFieldName(), $subFieldName, $value, $field), $this->firstCell);
					if($value && ($dataType->getDataTypeName()=="Blobs" || $dataType->getDataTypeName()=="Texts")){
						$style->getAlignment()->setWrapText(true);
						//if cell contains a lot of char, limit the height
						if(strlen($value)>64 || substr_count($value, "\n")>2){
							$exl->getActiveSheet()->getRowDimension($this->getCrtLineForPage($exl->getActiveSheetIndex()))->setRowHeight(38);
						}
					}
				}
		}
		if($this->first){
			$fieldXml = $field->getXml();
			//fput($field->getFieldName()." ".$subFieldName." ".$fieldXml["excelExport"].":");
			if(!$collapseCol && ((string)$fieldXml["excelExport"] == "" || (string)$fieldXml["excelExport"]=="auto")){
				$exl->getActiveSheet()->getColumnDimension(num2letter($c))->setAutoSize(true);
			} else if($collapseCol || (string)$fieldXml["excelExport"]=="0" || (string)$fieldXml["excelExport"]=="none"){ //none can happen if displaying calculated fields
				$exl->getActiveSheet()->getColumnDimension(num2letter($c))->setAutoSize(true);
				$exl->getActiveSheet()->getColumnDimension(num2letter($c))->setVisible(false);
				$exl->getActiveSheet()->getColumnDimension(num2letter($c))->setCollapsed(true);
				$exl->getActiveSheet()->getColumnDimension(num2letter($c))->setOutlineLevel(1);
			} else {
				$exl->getActiveSheet()->getColumnDimension(num2letter($c))->setWidth((int)$fieldXml["excelExport"]);
			}
		}
		if($this->firstCell) $this->firstCell = false;
	}
	private $first = true;
	private $firstCell = true;
	private $crtElement = null;
	private $elementAttributeFsl = null;
	private function getElementAttributeFsl(){
		if(!isset($this->elementAttributeFsl)){
			$this->elementAttributeFsl = ServiceProvider::getElementService()->getFieldSelectorListForElementAttributForImport();
		}
		return $this->elementAttributeFsl;
	}
	private $trm_ = null;
	private function getTrm(){
		if(!isset($this->trm_)){
			$this->trm_ = TemplateRecordManager::createInstance();
			$this->trm_->setWorkzoneViewDocked($this->isWorkzoneViewDocked());
		}
		return $this->trm_;
	}
	private function setElementToTrm($element){
		$trm_ = $this->getTrm();
		$trm_->reset($element);
	}
	public function addElementP($elementP){

		$elS = ServiceProvider::getElementService();
		$configS = $this->getWigiiExecutor()->getConfigurationContext();
		$p = $this->getP();
		$transS = ServiceProvider::getTranslationService();

		$element = $elementP->getElement();
		$this->crtElement = $element;
		$this->setElementToTrm($element);

		$elId = $element->getId();
//		if($this->elementPs[$elId]!=null) throw new ListException("Id: $elId already exist in the ElementListFrame", ListException::ALREADY_EXIST);
		$this->elementPs[$elId] = $elementP;

		$exl = $this->getExcelObj();

		//le premier élément que l'on ajoute, on créé les headers
		if($this->first){
			if($this->isNoGrouping()){
				$firstLine = 1;
			} else {
				$firstLine = 2;
			}
			$exl->getActiveSheet()->insertNewRowBefore($firstLine);
			$exl->getActiveSheet()->freezePane('A'.($firstLine+1));
			//setup page orientation / dimension
			$exl->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
			$exl->getActiveSheet()->getPageSetup()->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
			//setup paging scale
			$exl->getActiveSheet()->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1,$firstLine);
			$exl->getActiveSheet()->getPageSetup()->setFitToWidth(1);
			$exl->getActiveSheet()->getPageSetup()->setFitToHeight(0);
			//setup margin
			$exl->getActiveSheet()->getPageMargins()->setBottom(1/2.5);
			$exl->getActiveSheet()->getPageMargins()->setTop(1/2.5);
			$exl->getActiveSheet()->getPageMargins()->setLeft(1/2.5);
			$exl->getActiveSheet()->getPageMargins()->setRight(1/2.5);
			$exl->getActiveSheet()->getPageMargins()->setHeader(0.5/2.5);
			$exl->getActiveSheet()->getPageMargins()->setFooter(0.5/2.5);
			//setup headers & footers
			$exl->getActiveSheet()->getHeaderFooter()->setOddHeader('&L &C &R&D &T');
			$exl->getActiveSheet()->getHeaderFooter()->setOddFooter('&L' . $exl->getProperties()->getTitle() . '&C &RPage &P / &N');


			$exl->getActiveSheet()->getRowDimension($firstLine)->setRowHeight(16);
			$crtLineTemp = $this->getCrtLineForPage($exl->getActiveSheetIndex());
			$this->setCrtLineForPage($firstLine, $exl->getActiveSheetIndex());

			$this->firstCell = true;
			$this->beginElement(null);
			//add ID
			if($configS->getParameter($p, $element->getModule(), "ExcelExport_enableId")!="0"){
				$exl->getActiveSheet()->getColumnDimension(num2letter($this->getCrtColForPage($exl->getActiveSheetIndex())))->setWidth($this->idWidth);
				$this->addCell("ID", $this->firstCell);
				if($this->firstCell) $this->firstCell = false;
			}
			//add sys_creationDate
			if($configS->getParameter($p, $element->getModule(), "ExcelExport_enableSysCreationDate")=="1"){
				$exl->getActiveSheet()->getColumnDimension(num2letter($this->getCrtColForPage($exl->getActiveSheetIndex())))->setWidth($this->dateWidth);
				$this->addCell($transS->t($p, "sys_creationDate"), $this->firstCell);
				if($this->firstCell) $this->firstCell = false;
			}
			//add sys_date
			if($configS->getParameter($p, $element->getModule(), "ExcelExport_enableSysDate")!="0"){
				$exl->getActiveSheet()->getColumnDimension(num2letter($this->getCrtColForPage($exl->getActiveSheetIndex())))->setWidth($this->dateWidth);
				$this->addCell($transS->t($p, "sys_date"), $this->firstCell);
				if($this->firstCell) $this->firstCell = false;
			}
			//add important 1
			if($configS->getParameter($p, $element->getModule(), "Element_enableImportant1Status")!="0"){
				$exl->getActiveSheet()->getColumnDimension(num2letter($this->getCrtColForPage($exl->getActiveSheetIndex())))->setWidth($this->importantWidth);
				//$exl->getActiveSheet()->getStyle(num2letter($this->getCrtColForPage($exl->getActiveSheetIndex())).$this->getCrtLineForPage($exl->getActiveSheetIndex()))->applyFromArray($this->importantStyle);
				$this->addCell("!", $this->firstCell);
				if($this->firstCell) $this->firstCell = false;
			}
			//add important 2
			if($configS->getParameter($p, $element->getModule(), "Element_enableImportant2Status")!="0"){
				$exl->getActiveSheet()->getColumnDimension(num2letter($this->getCrtColForPage($exl->getActiveSheetIndex())))->setWidth($this->importantWidth);
				//$exl->getActiveSheet()->getStyle(num2letter($this->getCrtColForPage($exl->getActiveSheetIndex())).$this->getCrtLineForPage($exl->getActiveSheetIndex()))->applyFromArray($this->importantStyle);
				$this->addCell("!!", $this->firstCell);
				if($this->firstCell) $this->firstCell = false;
			}
			//add locked
			if($configS->getParameter($p, $element->getModule(), "Element_enableLockedStatus")!="0"){
				$exl->getActiveSheet()->getColumnDimension(num2letter($this->getCrtColForPage($exl->getActiveSheetIndex())))->setWidth($this->lockedWidth);
				//$exl->getActiveSheet()->getStyle(num2letter($this->getCrtColForPage($exl->getActiveSheetIndex())).$this->getCrtLineForPage($exl->getActiveSheetIndex()))->applyFromArray($this->lockedStyle);
				$this->addCell($transS->t($p, "locked"), $this->firstCell);
				if($this->firstCell) $this->firstCell = false;
			}
			foreach($element->getFieldList()->getListIterator() as $field){
				//we take only dataTypes in export. There is no sense exporting the freetext
				if($field->getDataType()==null) continue;
				$fieldXml = $field->getXml();
				if((string)$fieldXml["excelExport"]=="none") continue;
				//perform operation on each subField:
				$elS->visitDataTypeSubfields($field, $field->getDataType(), $this);
			}
			$this->setCrtWidthForPage($this->getCrtColForPage($exl->getActiveSheetIndex())-1, $exl->getActiveSheetIndex());
			$exl->getActiveSheet()->getStyle('A'.$firstLine.':'.num2letter($this->getCrtWidthForPage($exl->getActiveSheetIndex())).$firstLine)->applyFromArray($this->headerStyle);
			//apply stored title
//			fput($this->getTitlesRowsForPage($exl->getActiveSheetIndex()));
			foreach($this->getTitlesRowsForPage($exl->getActiveSheetIndex()) as $level=>$lines){
				if($lines){
					foreach($lines as $line){
						if($line>1)$line = $line + 1; //the header is added just after the line 1...
						$exl->getActiveSheet()->getStyle('A'.$line.':'.num2letter(max(1, $this->getCrtWidthForPage($exl->getActiveSheetIndex()))).$line)->applyFromArray($this->{"title".$level."Style"});
					}
				}
			}

			$this->endElement();
			$this->setCrtLineForPage($crtLineTemp+1, $exl->getActiveSheetIndex());
			$this->first = false;
		}


		$this->beginElement($elId);

		$this->firstCell = true;
		$exl = $this->getExcelObj();
		//add ID
		if($configS->getParameter($p, $element->getModule(), "ExcelExport_enableId")!="0"){
			$exl->getActiveSheet()->getCell(num2letter($this->getCrtColForPage($exl->getActiveSheetIndex())).$this->getCrtLineForPage($exl->getActiveSheetIndex()))->getHyperlink()->setUrl($elS->getUrlForElement($this->crtWigiiNamespace, $this->crtElement->getModule(), $this->crtElement));
			$exl->getActiveSheet()->getStyle(num2letter($this->getCrtColForPage($exl->getActiveSheetIndex())).$this->getCrtLineForPage($exl->getActiveSheetIndex()))->applyFromArray($this->urlStyle);
			$exl->getActiveSheet()->getColumnDimension(num2letter($this->getCrtColForPage($exl->getActiveSheetIndex())))->setWidth(max($this->idWidth+$this->getCrtDepthForPage($exl->getActiveSheetIndex()), $exl->getActiveSheet()->getColumnDimension(num2letter($this->getCrtColForPage($exl->getActiveSheetIndex())))->getWidth()));
			$this->addCell($this->crtElement->getId(), $this->firstCell);
			if($this->firstCell) $this->firstCell = false;
		}
		//add sys_creationDate
		if($configS->getParameter($p, $element->getModule(), "ExcelExport_enableSysCreationDate")=="1"){
			$this->addCell($this->getTrm()->doFormatForDate($this->crtElement->getSys_creationDate(), false, false, true), $this->firstCell);
			if($this->firstCell) $this->firstCell = false;
		}
		//add sys_date
		if($configS->getParameter($p, $element->getModule(), "ExcelExport_enableSysDate")!="0"){
			$this->addCell($this->getTrm()->doFormatForDate($this->crtElement->getSys_date(), false, false, true), $this->firstCell);
			if($this->firstCell) $this->firstCell = false;
		}
		//add important 1
		if($configS->getParameter($p, $element->getModule(), "Element_enableImportant1Status")!="0"){
			if($this->crtElement->isState_important1()) $exl->getActiveSheet()->getStyle(num2letter($this->getCrtColForPage($exl->getActiveSheetIndex())).$this->getCrtLineForPage($exl->getActiveSheetIndex()))->applyFromArray($this->importantStyle);
			$this->addCell(($this->crtElement->isState_important1() ? "!" : ""), $this->firstCell);
			if($this->firstCell) $this->firstCell = false;
		}
		//add important 2
		if($configS->getParameter($p, $element->getModule(), "Element_enableImportant2Status")!="0"){
			if($this->crtElement->isState_important2()) $exl->getActiveSheet()->getStyle(num2letter($this->getCrtColForPage($exl->getActiveSheetIndex())).$this->getCrtLineForPage($exl->getActiveSheetIndex()))->applyFromArray($this->importantStyle);
			$this->addCell(($this->crtElement->isState_important2() ? "!!" : ""), $this->firstCell);
			if($this->firstCell) $this->firstCell = false;
		}
		//add locked
		if($configS->getParameter($p, $element->getModule(), "Element_enableLockedStatus")!="0"){
			if($this->crtElement->isState_locked()) $exl->getActiveSheet()->getStyle(num2letter($this->getCrtColForPage($exl->getActiveSheetIndex())).$this->getCrtLineForPage($exl->getActiveSheetIndex()))->applyFromArray($this->lockedStyle);
			$this->addCell(($this->crtElement->isState_locked() ? "x" : ""), $this->firstCell);
			if($this->firstCell) $this->firstCell = false;
		}
		foreach($element->getFieldList()->getListIterator() as $field){
			//we take only dataTypes in export. There is no sense exporting the freetext
			if($field->getDataType()==null) continue;
			$fieldXml = $field->getXml();
			if((string)$fieldXml["excelExport"]=="none") continue;
			//perform operation on each subField:
			$elS->visitDataTypeSubfields($field, $field->getDataType(), $this);
		}

		$this->endElement();
	}

	protected function beginElement($elId){
		$this->setCrtColForPage(1, $this->getExcelObj()->getActiveSheetIndex());
		$this->getExcelObj()->getActiveSheet()->getStyle('A'.$this->getCrtLineForPage($this->getExcelObj()->getActiveSheetIndex()))->getAlignment()->setIndent(max(0, $this->getCrtDepthForPage($this->getExcelObj()->getActiveSheetIndex())-1));
	}

	protected function endElement(){
		$this->incCrtLineForPage($this->getExcelObj()->getActiveSheetIndex());
	}

	private $html2text;
	protected function addCell($value, $firstCell){
		$exl = $this->getExcelObj();
		$l = $this->getCrtLineForPage($exl->getActiveSheetIndex());
		$c = $this->incCrtColForPage($exl->getActiveSheetIndex());
		if($value){
//			$value = formatToString($value);
//			$value = str_replace('&nbsp;', ' ', str_replace('<br />', "\n", $value));
//			$rt = new PHPExcel_RichText();
//			$t = new PHPExcel_RichText_TextElement();
//			$t->setText($value);
//			$rt->addText($t);

//			$exl->getActiveSheet()->setCellValue(num2letter($c).$l, trim(strtr($value, array_flip(get_html_translation_table(HTML_ENTITIES, ENT_QUOTES)))));
			if(!isset($this->html2text)) $this->html2text = new Html2text();
			$this->html2text->setHtml($value);
			$exl->getActiveSheet()->setCellValue(num2letter($c).$l, $this->preventFormulasInCellValue(trim(str_replace(array("	", "\n\n", " ", "   ", "  ", " \n"), array("", "\n", "", " ", " ", "\n"), htmlspecialchars_decode($this->html2text->get_text(), ENT_QUOTES)))));
// 			$this->html2text->clear();
		}
	}

	private $objPHPExcel;
	protected function getExcelObj(){
		if(!isset($this->objPHPExcel)){
			$this->objPHPExcel = new PHPExcel();
			$this->objPHPExcel->getProperties()->setCreator(VERSION_LABEL)
				->setLastModifiedBy(VERSION_LABEL)
				->setTitle("Wigii export")
				->setSubject("")
				->setDescription("")
				->setKeywords("")
				->setCategory("");
			$this->objPHPExcel->getDefaultStyle()->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
			$this->setPageIndex(-1);
			$this->objPHPExcel->removeSheetByIndex();
		}
		return $this->objPHPExcel;
	}

	public function actOnBeforeAddElementP(){
		if($this->isNoGrouping()){
			//create the first page
			$exl = $this->getExcelObj();
			if($this->getPageIndex() === -1){
				$exl->createSheet();
				$this->first = true;
				$this->setPageIndex($this->getPageIndex()+1);
				$exl->setActiveSheetIndex($this->getPageIndex());
				$this->setCrtLineForPage(1, $exl->getActiveSheetIndex());
			}
		}
	}

	public function actOnFinishAddElementP($numberOfObjects){
		if($this->isNoGrouping()){
			$exl = $this->getExcelObj();
			//no more usefull since 13/05/2013, that was causing error in links for any other rows than the first one.... remove firt empty line (normaly defined for the title)
			//$exl->getActiveSheet()->removeRow(1);
			//$exl->getActiveSheet()->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1,1);
		}
	}



	/**
	 * ******************************
	 * ******************************
	 * TreeInDepth implementation
	 * ******************************
	 * ******************************
	 */
	private $crtWigiiNamespace = null;
	public function setCrtWigiiNamespace($wigiiNamespace){ $this->crtWigiiNamespace = $wigiiNamespace; }
	protected function getCrtWigiiNamespace(){ return $this->crtWigiiNamespace; }

	private $depthOffset = 0; //contains the offset of the first selected group id
	private $groupFound = false; //boolean indicating if we are in a group or subgroup of the groupList or not

	private $pageIndex = 1;
	protected function getPageIndex(){ return $this->pageIndex; }
	protected function setPageIndex($i){ $this->pageIndex = $i; }

	private $crtLines;
	protected function getCrtLineForPage($pageNb){
		if(!isset($this->crtLines)) $this->crtLines = array();
		if(!isset($this->crtLines[$pageNb])) $this->crtLines[$pageNb] = 1;
		return $this->crtLines[$pageNb];
	}
	protected function incCrtLineForPage($pageNb){
		if(!isset($this->crtLines)) $this->crtLines = array();
		if(!isset($this->crtLines[$pageNb])) $this->crtLines[$pageNb] = 1;
		$this->crtLines[$pageNb]++;
		return $this->crtLines[$pageNb]-1;
	}
	protected function setCrtLineForPage($line, $pageNb){
		$this->crtLines[$pageNb] = $line;
	}
	private $crtCols;
	protected function getCrtColForPage($pageNb){
		if(!isset($this->crtCols)) $this->crtCols = array();
		if(!isset($this->crtCols[$pageNb])) $this->crtCols[$pageNb] = 1;
		return $this->crtCols[$pageNb];
	}
	protected function incCrtColForPage($pageNb){
		if(!isset($this->crtCols)) $this->crtCols = array();
		if(!isset($this->crtCols[$pageNb])) $this->crtCols[$pageNb] = 1;
		$this->crtCols[$pageNb]++;
		return $this->crtCols[$pageNb]-1;
	}
	protected function setCrtColForPage($col, $pageNb){
		$this->crtCols[$pageNb] = $col;
	}
	private $crtDepths;
	protected function getCrtDepthForPage($pageNb){
		if(!isset($this->crtDepths)) $this->crtDepths = array();
		if(!isset($this->crtDepths[$pageNb])) $this->crtDepths[$pageNb] = 0;
		return $this->crtDepths[$pageNb];
	}
	protected function setCrtDepthForPage($d, $pageNb){
		$this->crtDepths[$pageNb] = $d;
	}
	private $crtWidths;
	protected function getCrtWidthForPage($pageNb){
		if(!isset($this->crtWidths)) $this->crtWidths = array();
		if(!isset($this->crtWidths[$pageNb])) $this->crtWidths[$pageNb] = 0;
		return $this->crtWidths[$pageNb];
	}
	protected function setCrtWidthForPage($w, $pageNb){
		$this->crtWidths[$pageNb] = $w;
	}

	private $titles;
	protected function addTitleForPage($level, $line, $pageNb){
		if(!isset($this->titles)) $this->titles = array();
		if(!isset($this->titles[$pageNb])) $this->titles[$pageNb] = array();
		if(!isset($this->titles[$pageNb][$level])) $this->titles[$pageNb][$level] = array();
		$this->titles[$pageNb][$level][]=$line;
	}
	protected function getTitlesRowsForPage($pageNb){
		if(!isset($this->titles)) return array();
		if(!isset($this->titles[$pageNb])) return array();
		return $this->titles[$pageNb];
	}
	/**
	 * Does something with the current tree node
	 * Indicates him the current depth in the tree, root is 0.
	 * Indicates him the number of direct children.
	 * If visitor returns false then visits stops at this level, else it continues deeper
	 */
	private $parentStackName = array();
	private $headerStyle = array(
			'font' => array(
				'bold' => true,
				'color' => array('argb' => 'FF000000'),
				'size' => 12
			),
			'borders' => array(
				'top' => array('style' => 'none')
			),
			'alignment' => array(
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
				'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
			),
			'fill' => array(
	 			'type' => 'none',
	 		)
		);
	private $title1Style = array(
			'font' => array(
				'bold' => true,
				'color' => array('argb' => 'FFFFFFFF'),
				'size' => 16
			),
			'alignment' => array(
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
				'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
			),
			'borders' => array(
				'top' => array('style' => PHPExcel_Style_Border::BORDER_THIN)
			),
			'fill' => array(
	 			'type' => PHPExcel_Style_Fill::FILL_SOLID,
	 			'color' => array('argb' => 'FFA0A0A0')
	 		)
		);
	private $title2Style = array(
			'font' => array(
				'bold' => true,
				'color' => array('argb' => 'FF000000'),
				'size' => 12
			),
			'alignment' => array(
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
				'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
			),
			'fill' => array(
	 			'type' => PHPExcel_Style_Fill::FILL_SOLID,
	 			'color' => array('argb' => 'FFD0D0D0')
	 		)
		);
	private $title3Style = array(
			'font' => array(
				'bold' => true,
				'color' => array('argb' => 'FF000000'),
				'size' => 12
			),
			'alignment' => array(
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
				'vertical' => PHPExcel_Style_Alignment::VERTICAL_BOTTOM
			)
		);
	private $urlStyle = array(
			'font' => array(
				'color' => array('argb' => 'FF0000FF'),
				'underline' => 'single'
			)
		);

	private $importantStyle = array(
			'font' => array(
				'bold' => true,
				'color' => array('argb' => 'FFFFFFFF')
			),
			'fill' => array(
				'type'=>'solid',
				'color'=>array('argb' => 'FFFF6600')
			),
			'alignment'=>array('horizontal' => 'center')
		);
	private $lockedStyle = array(
			'font' => array(
				'bold' => true,
				'color' => array('argb' => 'FFFFFFFF')
			),
			'fill' => array(
				'type'=>'solid',
				'color'=>array('argb' => 'FFFF0000')
			),
			'alignment'=>array('horizontal' => 'center')
		);

	protected function formatForWorksheetName($string){
		// Some of the printable ASCII characters are invalid:  * : / \ ? [ ]
		if (str_replace(array('*', ':', '/', '\\', '?', '[', ']'), '', $string) !== $string) {

		}

		// Maximum 31 characters allowed for sheet title
		if (mb_strlen($string, 'UTF-8') > 31) {
			$string = substr($string, 0, 31);
		}
		return $string;
	}
	private $idWidth = 8;
	private $dateWidth = 18;
	private $importantWidth = 3;
	private $lockedWidth = 7;
	/**
	 * @param $object : GroupP
	 * @param $depth : depth of group tree, 0 is first level
	 * @param $numberOfChildren : int, number of sub groups
	 */
	public function actOnTreeNode($object, $depth, $numberOfChildren){
		$configS = $this->getWigiiExecutor()->getConfigurationContext(); //ServiceProvider::getConfigService();
		$transS = ServiceProvider::getTranslationService();
		$p = $this->getP();
		$ite = $this->getGroupPList()->getGroupIds();
//		fput($object->getDbEntity()->getGroupname()." depth:".$depth." depth offset:".$this->depthOffset);
		if($ite[$object->getId()] != null){
			$this->depthOffset = $depth;
			$this->groupFound = true;
		} else if($depth <= $this->depthOffset){ //} || $configS->getParameter($p, $object->getDbEntity()->getModule(), "Group_IncludeChildrenGroupsOnSelect")=="0"){
			$this->groupFound = false;
			$this->depthOffset = 0;
		}
		if($depth == 0) $this->parentStackName = array();
		else $this->parentStackName = array_slice($this->parentStackName, 0, $depth);
		$this->parentStackName[$depth] = $object->getDbEntity()->getGroupname();
		if(!$this->groupFound) return true;
		$depth -= $this->depthOffset;

//		fput("\n");
//		fput($depth);
//		fput($this->groupFound);

		$exl = $this->getExcelObj();
		if($this->isTitleGrouping()){
			if($this->getPageIndex() === -1){
				$exl->createSheet();
				$this->first = true;
				$this->setPageIndex($this->getPageIndex()+1);
				$exl->setActiveSheetIndex($this->getPageIndex());
				if($this->getGroupPList()->count()>1 && $depth==0){
					$s = $exl->getActiveSheet();
					$s->getStyle('A1:D1')->applyFromArray($this->title1Style);
					$s->setCellValue('A'.$this->incCrtLineForPage($exl->getActiveSheetIndex()), ($p->getWigiiNamespace()->getWigiiNamespaceName() ? $p->getWigiiNamespace()->getWigiiNamespaceName()." / " : "").$transS->t($p, $object->getDbEntity()->getModule()->getModuleUrl()));
					$this->addTitleForPage(1, 1, $exl->getActiveSheetIndex());
				}
			}
			$depth += 1;
		} else {
			throw new ServiceException("ActOnTreeNode in ElementPList Excel export is not configured right: no grouping.", ServiceException::INVALID_ARGUMENT);
		}

		if($this->getGroupPList()->count() == 1){
			$depth -= 1; //if groupList is 1 then reduce the depth of one to make the title of first level of subgroup as main
		}
		$s = $exl->getActiveSheet();
//		eput(" create title");
		//create a title
//		fput("depth: ".$depth." active sheet: ".$exl->getActiveSheetIndex()." line: ".$this->getCrtLineForPage($exl->getActiveSheetIndex())." for ".implode("/", $this->parentStackName));
		if($depth<=0){
			$s->getRowDimension(1)->setRowHeight(40);
//			fput("set title1 style on line 1 on sheet ".$exl->getActiveSheetIndex());
			$s->getStyle('A1:D1')->applyFromArray($this->title1Style);
			$s->setCellValue('A'.$this->incCrtLineForPage($exl->getActiveSheetIndex()), $this->preventFormulasInCellValue(implode("/", $this->parentStackName)));
			$this->addTitleForPage(1, 1, $exl->getActiveSheetIndex()); //to be able to set the style on the right nb of column when the first element is found
		} else if($depth==1) {
			//add a new line
			$this->incCrtLineForPage($exl->getActiveSheetIndex());
			$l = $this->incCrtLineForPage($exl->getActiveSheetIndex());
//			fput("set title2 style on line ".$l." on sheet ".$exl->getActiveSheetIndex());
			$s->setCellValue('A'.$l, $this->preventFormulasInCellValue($object->getDbEntity()->getGroupname()));
			$s->getStyle('A'.$l.':'.num2letter(max(4, (int)$this->getCrtWidthForPage($exl->getActiveSheetIndex()))).$l)->applyFromArray($this->title2Style);
			$this->addTitleForPage(2, $l, $exl->getActiveSheetIndex()); //to be able to set the style on the right nb of column when the first element is found
		} else {
			$l = $this->incCrtLineForPage($exl->getActiveSheetIndex());
//			fput("set title3 style on line ".$l." on sheet ".$exl->getActiveSheetIndex());
			$s->getRowDimension($l)->setRowHeight(25);
			$s->setCellValue('A'.$l, $this->preventFormulasInCellValue($object->getDbEntity()->getGroupname()));
			$s->getStyle('A'.$l.':'.num2letter(max(4, (int)$this->getCrtWidthForPage($exl->getActiveSheetIndex()))).$l)->applyFromArray($this->title3Style);
			$s->getStyle('A'.$l)->getAlignment()->setIndent($depth-1);
			$this->addTitleForPage(3, $l, $exl->getActiveSheetIndex()); //to be able to set the style on the right nb of column when the first element is found
		}
		$this->setCrtDepthForPage($depth, $exl->getActiveSheetIndex());
		//select all the elements in the group (without children)
		$this->crtWigiiNamespace = $object->getDbEntity()->getWigiiNamespace();
		$this->actOnBeforeAddElementP();

		//Change the context to the current group:
		$lcTemp = $this->getLCTemp();
		$tempGroupList = GroupListAdvancedImpl::createInstance()->addGroupP($object);
		$doesGroupListIncludeChildren = $this->getListContext()->doesGroupListIncludeChildren();
		$lcTemp->setGroupPList($tempGroupList, $doesGroupListIncludeChildren);
		$lcTemp->resetFetchCriteria($p, $this->getWigiiExecutor());
		$lcTemp->matchFetchCriteria($this->getListContext());
		if($this->getListContext()->isMultipleSelection()){
			//add the multipleSelection criterias in the LogExp
			$lcTemp->addLogExpOnMultipleSelection($this->getListContext()->getMultipleSelection());
		}

		$lcTemp->setConfigGroupList($this->getListContext()->getConfigGroupList());

		try{
			$nbRow = ServiceProvider::getElementService()->getAllElementsInGroup($p, $object->getDbEntity(), $this, false, $lcTemp);
		} catch (ElementServiceException $elx){
			if($elx->getCode() == ElementServiceException::INVALID_ARGUMENT){
				//if it fails, reset the search criteria, this means we are in a too different configuration
				$lcTemp->resetFetchCriteria($p, $this->getWigiiExecutor());
				$nbRow = ServiceProvider::getElementService()->getAllElementsInGroup($p, $object->getDbEntity(), $this, false, $lcTemp);
			} else throw $elx;
		}

		$this->actOnFinishAddElementP($nbRow);

		return true;
	}

	/**
	 * Does something on the current node after having visited the children nodes
	 * Indicates him the current depth in the tree, root is 0.
	 * Indicates him if the all the children have been visited.
	 * If visitor returns false then visits backtracks to parent,
	 * else continues by visiting peers on same level
	 */
	public function actAfterTreeNode($object, $depth, $visitedAllChildren){
		//add elements which are only in parent

		return true;
	}

	public function saveFile(){
		// Set active sheet index to the first sheet, so Excel opens this as the first sheet
		$this->getExcelObj()->setActiveSheetIndex(0);
		// Save Excel 2007 file
		$objWriter = PHPExcel_IOFactory::createWriter($this->getExcelObj(), $this->getFactory());
//		$objWriter->save(str_replace('.php', '.xlsx', __FILE__));
		$objWriter->save('php://output');
	}

}


