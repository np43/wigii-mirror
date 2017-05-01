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
 * Created on 12 april 2010 by LWR
 * Updated by Medair (CWE) on 28.04.2017 - Added parsing checks on Numbers and Floats
 */
class ImportElementInFormExecutor extends FormExecutor implements ElementDataTypeSubfieldVisitor {

	private $crtGroupP; //current selected groupP
	protected function setCrtGroupP($var){ $this->crtGroupP = $var; }
	protected function getCrtGroupP(){ return $this->crtGroupP; }

	private $rootPrincipal;
	protected function setRootPrincipal($var){$this->rootPrincipal = $var; }
	protected function getRootPrincipal(){return $this->rootPrincipal; }

	public static function createInstance($wigiiExecutor, $record, $formId, $submitUrl, $rootPrincipal, $crtSelectedGroupP){
		$fe = new self();
		$fe->setWigiiExecutor($wigiiExecutor);
		$fe->setRecord($record);
		$fe->setFormId($formId);
		$fe->setSubmitUrl($submitUrl);
		$fe->setRootPrincipal($rootPrincipal);
		$fe->setCrtGroupP($crtSelectedGroupP);
		return $fe;
	}

	protected function setState($state){
		switch($state){
			case "start":
				break;
			case "newCheck": //this will provide a again check as after a start
				$this->addStateError(); //this state cannot allow to end the loop
				break;
			case "check":
				break;
			default: throw FormExecutorException("invalid state: ".$state, FormExecutorException::INVALID_STATE);
		}
		$this->state = $state;
	}
	protected function goToNextState(){

		switch($this->getState()){
			case "start": $this->setState("check"); break;
			case "newCheck": $this->setState("check"); break;
			case "check": $this->setState("check"); break;
				break;
			default: throw FormExecutorException("invalid state: ".$this->getState(), FormExecutorException::INVALID_STATE);
		}
		return $this->getState();
	}

	protected function parseHeaders($sep, $string){
		$string = stripslashes($string);
		//remove any newlines
		$string = str_replace(array("\n", "\r"), '', $string);
		$r = preg_split("/\\".$sep."\s*(?=(?:[^\"]*\"[^\"]*\")*(?![^\"]*\"))/", $string);
		$r2 = array();
		$pos = 1; //the header array stores foreach key the position of it. begins to 1 to prevent == null when position is first
		foreach($r as $item){
			$item2 = preg_replace("/^\"/", '', $item);
			if($item2 != $item){
				$item2 = preg_replace("/\"$/", '', $item2);
			}
			//replace double " with one
			$item2 = str_replace('""', '"', $item2);
			$r2[$item2] = $pos++;
		}
		return $r2;
	}
	protected function parseDataRow($sep, $string){
		//do the split with the separator followed by any spacer
		$string = str_replace(array("\n", "\r"), '', $string);
		$r = preg_split("/\\".$sep."\s*(?=(?:[^\"]*\"[^\"]*\")*(?![^\"]*\"))/", $string);
		$r2 = array();
		foreach($r as $item){
			//take of first "
			$item2 = preg_replace("/^\"/", '', $item);
			//take of last if had the first "
			if($item2 != $item){
				$item2 = preg_replace("/\"$/", '', $item2);
			}
			//replace double " with one
			$item2 = str_replace('""', '"', $item2);
			//trim value
			$item2 = trim($item2);
			$r2[] = $item2;
		}
		return $r2;
	}

	private $headers;
	private $keptHeaders;
	private $importFieldSelectorList;//field selector defined from the import file but with the sys_ fields automatically added for the real import
	private $importFieldSelectorListFromFile; //field selector defined in the import file
	private $elementPAList;
	private $originalFieldList;
	private $languageInstalled;
	private function getItemKey($fieldName, $subFieldName, $lang){
		return $fieldName.($subFieldName != "value" ? " ".$subFieldName : "").($lang!=null ? " ".$lang : "");
	}
	public function actOnSubfield($field, $dataType, $subFieldName, $subFieldType, $sqlType, $userSelectedSubField, $lang=null){
		$fieldName = $field->getFieldName();
		if($lang!=null){
			$this->languageInstalled[$lang] = $lang;
		}
		$key = $this->getItemKey($fieldName, $subFieldName, $lang);
		if($this->headers == null || $this->headers[$key]!=null){
			$this->importFieldSelectorList->addFieldSelector($fieldName, $subFieldName);
			$this->importFieldSelectorListFromFile->addFieldSelector($fieldName, $subFieldName);
			if($dataType->getDataTypeName()=="Emails"){
				//always add all the other subfields in the case of Emails
				if($this->headers[$this->getItemKey($fieldName, "value", $lang)] == null)
					$this->importFieldSelectorList->addFieldSelector($fieldName, "value");
				$this->importFieldSelectorListFromFile->addFieldSelector($fieldName, "value");
				if($this->headers[$this->getItemKey($fieldName, "proofStatus", $lang)] == null)
					$this->importFieldSelectorList->addFieldSelector($fieldName, "proofStatus");
				$this->importFieldSelectorListFromFile->addFieldSelector($fieldName, "proofStatus");
				if($this->headers[$this->getItemKey($fieldName, "proofKey", $lang)] == null)
					$this->importFieldSelectorList->addFieldSelector($fieldName, "proofKey");
				$this->importFieldSelectorListFromFile->addFieldSelector($fieldName, "proofKey");
				if($this->headers[$this->getItemKey($fieldName, "proof", $lang)] == null)
					$this->importFieldSelectorList->addFieldSelector($fieldName, "proof");
				$this->importFieldSelectorListFromFile->addFieldSelector($fieldName, "proof");
				if($this->headers[$this->getItemKey($fieldName, "externalConfigGroup", $lang)] == null)
					$this->importFieldSelectorList->addFieldSelector($fieldName, "externalConfigGroup");
				$this->importFieldSelectorListFromFile->addFieldSelector($fieldName, "externalConfigGroup");
				if($this->headers[$this->getItemKey($fieldName, "externalAccessLevel", $lang)] == null)
					$this->importFieldSelectorList->addFieldSelector($fieldName, "externalAccessLevel");
				$this->importFieldSelectorListFromFile->addFieldSelector($fieldName, "externalAccessLevel");
				if($this->headers[$this->getItemKey($fieldName, "externalCode", $lang)] == null)
					$this->importFieldSelectorList->addFieldSelector($fieldName, "externalCode");
				$this->importFieldSelectorListFromFile->addFieldSelector($fieldName, "externalCode");
				if($this->headers[$this->getItemKey($fieldName, "externalAccessEndDate", $lang)] == null)
					$this->importFieldSelectorList->addFieldSelector($fieldName, "externalAccessEndDate");
				$this->importFieldSelectorListFromFile->addFieldSelector($fieldName, "externalAccessEndDate");
			}
			//mark the header as took
			$this->keptHeaders[$key] = $key;
		}
	}
	private $hasElementIdAttribute = false;
	private $hasElementModulenameAttribute = false;
	protected function computeFieldSelectorList($p, $exec){
		$configS = $this->getWigiiExecutor()->getConfigurationContext();
		$elS = ServiceProvider::getElementService();
		$rec = $this->getRecord();
		$this->hasElementIdAttribute = false;
		$this->hasElementModulenameAttribute = false;
		if($this->headers!=null){
			$this->headers = $this->parseHeaders($rec->getFieldValue("csvSeparator"), $this->headers);
			//check for id or modulename field: take them off
			$this->hasElementIdAttribute = array_search("__element id", array_keys($this->headers));
			$this->hasElementModulenameAttribute = array_search("__element modulename", array_keys($this->headers));
			if($this->hasElementIdAttribute !== false){
				unset($this->headers["__element id"]);
			}
			if($this->hasElementModulenameAttribute !== false){
				unset($this->headers["__element modulename"]);
			}
		} else {
			$this->headers = array();
		}
		$this->keptHeaders = array();
		$this->originalFieldList = FormFieldList::createInstance(FormBag::createInstance());
		$this->importFieldSelectorList = FieldSelectorListArrayWebImpl::createInstance(false);
		$this->importFieldSelectorListFromFile = FieldSelectorListArrayWebImpl::createInstance(false, false);
		$configS->getFields($p, $exec->getCrtModule(), null, $this->originalFieldList);
		foreach($this->originalFieldList->getListIterator() as $field){
			$fieldName = $field->getFieldName();
			if($field->getDataType()!=null){
				//perform operation on each subField:
				$elS->visitDataTypeSubfields($field, $field->getDataType(), $this);
			}
		}
		//add element attribute fields
		$elFsl = $elS->getFieldSelectorListForElementAttributForImport();
		foreach($elFsl->getListIterator() as $fsl){
			$key = $this->getItemKey($fsl->getFieldName(), $fsl->getSubFieldName(), null);
			$this->keptHeaders[$key] = $key;
			if($this->headers == null || $this->headers[$key]!=null){
				$this->keptHeaders[$key] = $key;
				$this->importFieldSelectorList->addFieldSelectorInstance($fsl);
				$this->importFieldSelectorListFromFile->addFieldSelectorInstance($fsl);
			}
		}
//		$this->importFieldSelectorList->mergeFieldSelectorList($elFsl);
	}
	protected function doSpecificCheck($p, $exec){
		$transS = ServiceProvider::getTranslationService();
		$elS = ServiceProvider::getElementService();

		$rec = $this->getRecord();
		//set group
		$groupId = $rec->getFieldValue("inGroupId");
		if($this->getCrtGroupP()->getId() != $groupId){
			$this->setCrtGroupP(ServiceProvider::getGroupAdminService()->getGroup($p, $groupId));
		}
		//set separator
		$sep = $rec->getFieldValue("csvSeparator");

		//lookup if a file is uploaded. If yes read the data and put in $datas
		$csvFile = $rec->getFieldValue("csvFile", "path");
		if($csvFile){
			$strCSV = fopen($this->getTemporaryUploadedFilePath().$csvFile, 'r');
			$content = stream_get_contents($strCSV);
			fclose($strCSV);
			$encode = mb_detect_encoding($content, array('ASCII', 'UTF-8', 'ISO-8859-1', 'Windows-1252', 'Windows-1251', 'ISO-8859-15', 'ISO-8859-6'), true);
			if($encode == 'UTF-8'){
//				eput("is ".$encode."");
				//remove any BOM content
				$content = utf8_decode($content);
				$content = utf8_encode($content);
				if(substr($content,0,1)=="?"){
					$content = substr($content,1);
				}
			} else {
//				eput("convert ".$encode." in UTF-8");
				$content = mb_convert_encoding($content, 'UTF-8',$encode);
			}

			$rec->setFieldValue($content, "importInRawData");
			$rec->setFieldValue(null, "csvFile", "name");
			$rec->setFieldValue(null, "csvFile", "path");
			$rec->setFieldValue(null, "csvFile", "type");
			$rec->setFieldValue(null, "csvFile", "size");
			$rec->setFieldValue(null, "csvFile", "mime");
			$rec->setFieldValue(null, "csvFile", "date");
			$rec->setFieldValue(null, "csvFile", "user");
			$rec->setFieldValue(null, "csvFile", "username");
			$rec->setFieldValue(null, "csvFile", "content");
			$rec->setFieldValue(null, "csvFile", "textContent");
			$rec->setFieldValue(null, "csvFile", "thumbnail");
			@unlink($this->getTemporaryUploadedFilePath().$csvFile);
		}
		//treat row datas:
		$datas = $rec->getFieldValue("importInRawData");
		$datas = stripslashes($datas);
		//$datas = preg_split('/"\s*\\'.$sep.'*\s*$\n^\s*"/', $datas);
		$datas = explode("\n", $datas);

		$this->headers = $datas[0];
		//create and check the fieldlist from headers values
		$this->computeFieldSelectorList($p, $exec);

		//eput($this->importFieldSelectorList->getListIterator());
		$remainingHeaders = array_diff_key($this->headers, $this->keptHeaders);
		if($remainingHeaders != null){
			$error = $transS->h($p, "importInInvalidHeaders").":<br />".implode(" | ", array_keys($remainingHeaders)).'<br />';
			$this->addErrorToField($error, "importInRawData");
		}

		//foreach line:
		$wigiiBag = WigiiBagBaseImpl::createInstance();
		$pRights = PrincipalRights::createInstance();
		$pRights->setCanWriteElement(true);
		$errorInLine = array();
		//this is commented, because I prefer keeping the maximum of ressource CPU to compute the import
		//without adding this big part of computing for email notification of the import
//		//include in this elementPAList a fieldSelector with the values to display as a list
//		//this fieldSelector will be only used in the notification process
//		$fsl = FieldSelectorListForActivity::createInstance(false);
//		$this->getWigiiExecutor()->getConfigurationContext()->getFields($p, $exec->getCrtModule(), Activity::createInstance("listView"), $fsl);
//		$fsl->setSelectedLanguages(array($transS->getLanguage()=>$transS->getLanguage()));
//		$this->elementPAList = ElementPAdvancedListArrayImpl::createInstance(null, $fsl);
		$this->elementPAList = ElementPAdvancedListArrayImpl::createInstance();
		
		foreach($datas as $lineNb=>$dataRow){
			
			if($lineNb == 0) continue; //first line is header
			if($dataRow == null) continue; //ignore empty lines
			
			$dataRow = preg_replace('/^[^\\'.$sep.']*###\s*/', '', $dataRow);
			$datas[$lineNb] = $dataRow; //remove the information added
			$dataRow = $this->parseDataRow($sep, $dataRow);
			
			//take off the element id or modulename if defined
			if($this->hasElementIdAttribute !== false){
				unset($dataRow[$this->hasElementIdAttribute]);
			}
			if($this->hasElementModulenameAttribute !== false){
				unset($dataRow[$this->hasElementModulenameAttribute]);
			}
			
			
			//create an ElementP
			$el = Element::createInstance($exec->getCrtModule(), $this->originalFieldList, $wigiiBag);
			$elP = ElementP::createInstance($el);
			$elP->setRights($pRights);
			$el->setId($lineNb); //important that the first id is null --> new ids will always be different (id starts at 1), this is to prevent a problem in the case importing into a blank DB
			//fills the elementP + check datas
			$nb = 0;
			
			foreach($this->importFieldSelectorListFromFile->getListIterator() as $key=>$fs){

				if(!$fs->isElementAttributeSelector()){
					$field = $el->getFieldList()->getField($fs->getFieldName());
					$dataType = $field->getDataType();
					if($dataType == null) continue;
					$dataTypeName = $dataType->getDataTypeName();
				}

				if(!$fs->isElementAttributeSelector() && $dataType->getXml()->{$fs->getSubFieldName()}["multiLanguage"]=="1"){
					$pos = $this->headers[($fs->getSubFieldName()=="value" ? $fs->getFieldName()." ".reset($this->languageInstalled) : $fs->getFieldName()." ".$fs->getSubFieldName()." "." ".reset($this->languageInstalled))];
					$multilanguage = true;
				} else {
					$multilanguage = false;
					$pos = $this->headers[($fs->getSubFieldName()=="value" ? $fs->getFieldName() : $fs->getFieldName()." ".$fs->getSubFieldName())];
				}

				//if($lineNb == 1) eput($fs);
				//if($lineNb == 1) eput("nb:".$nb." ");
				//if($lineNb == 1) eput("pos:".$pos."\n");
				try{
					if($fs->isElementAttributeSelector()){
//						eput("set ".put($dataRow[$nb])." for ".$fs->getFieldName()." ".$fs->getSubFieldName()." | ");
//						eput($el->getAttribute($fs));
//						eput(" => ");
						$el->setAttribute($dataRow[$pos-1], $fs);
						$nb++;
//						eput($el->getAttribute($fs));
//						eput("\n");
                    // verify language fields:
					} else if($multilanguage){
							$temp = array_slice($dataRow, $pos-1, count($this->languageInstalled));
							if(count($temp) == count($this->languageInstalled)){ //if not the case, that means the format is incorrect, this
								$temp = array_combine($this->languageInstalled, $temp);
								$el->setFieldValue(str_replace('\n', "\n", $temp), $fs->getFieldName(), $fs->getSubFieldName());
							}
							$nb = $nb + count($this->languageInstalled);
					//verify MultipleAttributes management
					} else if($dataTypeName=="MultipleAttributs" && ($fs->getSubFieldName() == "value" || $fs->getSubFieldName() == "")){
						$temp = explode(", ", $dataRow[$pos-1]);
						$nb++;
						$temp = array_combine($temp, $temp);
						$el->setFieldValue($temp, $fs->getFieldName(), $fs->getSubFieldName());
					//calculate special email subfields
					} else if ($dataTypeName == "Emails") {
					   // Medair (CWE) 01.05.2017: treats all subfields when reading first email column
					   if(!$fs->getSubFieldName() || $fs->getSubFieldName() == "value") {
						$newValue = str_replace('\n', "\n", $dataRow[$pos -1]);
						
						// value
						$el->setFieldValue($newValue, $fs->getFieldName(), "value");
						$nb++;
						
						// proofKey
						$subPos = $this->headers[$fs->getFieldName() . " proofKey"];
						if($subPos) {
							// takes proofKey value in CSV if exists
							if($dataRow[$subPos -1]!=null) $el->setFieldValue(str_replace('\n', "\n", $dataRow[$subPos -1]), $fs->getFieldName(), "proofKey");
							// if empty but column present, then forces recalculation
							else $el->setFieldValue($elS->getEmailValidationCode($p, $newValue), $fs->getFieldName(), "proofKey");
							$nb++;
						}
						else $el->setFieldValue($elS->getEmailValidationCode($p, $newValue), $fs->getFieldName(), "proofKey");
						// proofStatus
						$subPos = $this->headers[$fs->getFieldName() . " proofStatus"]; 
						if($subPos) {
							// takes proofStatus from CSV file if present (can be forced to null or 0 if CSV has subfield present but empty)
							$el->setFieldValue(str_replace('\n', "\n", $dataRow[$subPos -1]), $fs->getFieldName(), "proofStatus");
							$nb++;
						}
						else $el->setFieldValue(0, $fs->getFieldName(), "proofStatus");
						// proof
						$subPos = $this->headers[$fs->getFieldName() . " proof"];
						if($subPos) {
							// takes proof from CSV file if present (can be forced to null if CSV has subfield present but empty)
							$el->setFieldValue(str_replace('\n', "\n", $dataRow[$subPos -1]), $fs->getFieldName(), "proof");
							$nb++;
						}
						else $el->setFieldValue(null, $fs->getFieldName(), "proof");
						// externalConfigGroup
						$subPos = $this->headers[$fs->getFieldName() . " externalConfigGroup"];
						if($subPos) {
							$el->setFieldValue(str_replace('\n', "\n", $dataRow[$subPos -1]), $fs->getFieldName(), "externalConfigGroup");
							$nb++;
						}
						else $el->setFieldValue(null, $fs->getFieldName(), "externalConfigGroup");
						// externalAccessLevel
						$subPos = $this->headers[$fs->getFieldName() . " externalAccessLevel"];
						if($subPos) {
							$el->setFieldValue(str_replace('\n', "\n", $dataRow[$subPos -1]), $fs->getFieldName(), "externalAccessLevel");
							$nb++;
						}
						else $el->setFieldValue(0, $fs->getFieldName(), "externalAccessLevel");
						// externalAccessEndDate
						$subPos = $this->headers[$fs->getFieldName() . " externalAccessEndDate"];
						if($subPos) {
							$el->setFieldValue(str_replace('\n', "\n", $dataRow[$subPos -1]), $fs->getFieldName(), "externalAccessEndDate");
							$nb++;
						}
						else $el->setFieldValue(null, $fs->getFieldName(), "externalAccessEndDate");
						// externalCode
						$subPos = $this->headers[$fs->getFieldName() . " externalCode"];
						if($subPos) {
							// takes externalCode value in CSV if exists
							if($dataRow[$subPos -1]!=null) $el->setFieldValue(str_replace('\n', "\n", $dataRow[$subPos -1]), $fs->getFieldName(), "externalCode");
							// if empty but column present, then forces recalculation
							else $el->setFieldValue($elS->getEmailExternalCode($p, $el->getId(), $fs->getFieldName(), $newValue), $fs->getFieldName(), "externalCode");
							$nb++;
						}
						else $el->setFieldValue($elS->getEmailExternalCode($p, $el->getId(), $fs->getFieldName(), $newValue), $fs->getFieldName(), "externalCode");	
					   }
				    // If the datatype is Numerics or Floats we remove all characters which are not numbers
			        } else if($dataTypeName === 'Numerics' || $dataTypeName === 'Floats') {
				        $el->setFieldValue(preg_replace("/[^-0-9.]/","", $dataRow[$pos -1]), $fs->getFieldName(), $fs->getSubFieldName());
				        if($fs->getSubFieldName()!="value" && $this->headers[$fs->getFieldName() . " ".$fs->getSubFieldName()] || $fs->getSubFieldName()=="value" && ($this->headers[$fs->getFieldName()] || $this->headers[$fs->getFieldName()." value"])) $nb++;
				    //normal
				    } else {							
						$el->setFieldValue(str_replace('\n', "\n", $dataRow[$pos-1]), $fs->getFieldName(), $fs->getSubFieldName());
						$nb++;
					}									
				} catch (ServiceException $e){
					$errorInLine[$lineNb] .= " !Exception ".$e->getCode()." ".$e->getMessage();
				}
			}

			//	- check the number of fields
			if(count($dataRow) != $nb){
				$errorInLine[$lineNb] .= " !NB found ".count($dataRow)." : $nb ";
			}
			//if no sys_creationUser and no sys_creationDate then add them
			if($el->getSys_creationUser()==null){
				$el->setSys_creationUser($p->getRealUserId());
				$el->setSys_creationUsername($p->getRealUsername());
			}
			if($el->getSys_creationDate()==null){
				$el->setSys_creationDate(time());
			}
			//add it to the list
			$this->elementPAList->addElementP($elP);
			$datas[$lineNb] = $lineNb.$errorInLine[$lineNb]." ### ".$datas[$lineNb];
		}
		if($errorInLine!=null){
			$error = $transS->h($p, "importErrorOnLines").": ".implode(", ", array_keys($errorInLine))."<br />".$transS->h($p, "importErrorOnLinesDetails");
			$this->addErrorToField($error, "importInRawData");
		}
		if($this->elementPAList->count()==0){
			$this->addErrorToField($transS->h($p, "noDataToImport"), "importInRawData");
		}
		$rec->setFieldValue(addslashes(implode("\n", $datas)), "importInRawData");

	}

	protected function actOnCheckedRecord($p, $exec) {
		//import the List
		$elS = ServiceProvider::getElementService();
		$transS = ServiceProvider::getTranslationService();

		$elS->insertMultipleElement($this->getRootPrincipal(), $p, $this->getCrtGroupP(), $this->elementPAList, $this->importFieldSelectorList);
		//fill any empty email codes
		$elS->fillEmptyEmailValidationAndExternalCode($p);

		//calculate a fieldSelector as columns to do the notification
		$this->getWigiiExecutor()->throwEvent()->insertMultipleElement(PWithModuleWithElementPListWithGroupList::createInstance($p, $exec->getCrtModule(), $this->elementPAList, GroupListArrayImpl::createInstance()->addGroup($this->getCrtGroupP()->getDbEntity())));

		//important to clear the cancel stack
		$exec->addJsCode("actOnCloseDialog('".$exec->getIdAnswer()."');");

		$this->getWigiiExecutor()->openAsMessage($exec->getIdAnswer(), 350, $transS->t($p, "operationDoneSuccessfully"), $this->elementPAList->count()." ".$transS->t($p, "elementsImported"), null, "Ok", null, null, "done");

		$exec->invalidCache($p, 'moduleView');
		$exec->addRequests("moduleView/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/display/moduleView/");
	}

	protected function doRenderForm($p, $exec){
		$transS = ServiceProvider::getTranslationService();
		$defaultSeparator = (string)$this->getWigiiExecutor()->getConfigurationContext()->getParameter($p, null, "csvDefaultSeparator");
		if($defaultSeparator == null) $defaultSeparator = ",";

		//add group data
		if($this->getCrtGroupP()!=null && $this->getCrtGroupP()->getRights()->canShareElement()){
			$this->getRecord()->setFieldValue($this->getCrtGroupP()->getId(), "inGroupId");
		}
		if($this->getState() == "start"){
			$this->computeFieldSelectorList($p, $exec);
			$this->getRecord()->setFieldValue($defaultSeparator, "csvSeparator");
			$this->getRecord()->setFieldValue(implode($defaultSeparator, $this->keptHeaders), "importInRawData");
			$this->getRecord()->getWigiiBag()->setHidden(true, "importInRawData");
			$this->getRecord()->getWigiiBag()->setHidden(false, "csvFile");
		} else {
			$this->getRecord()->getWigiiBag()->setHidden(true, "csvFile");
			$this->setTotalWidth(700);
		}


		$this->getTrm()->setState($this->getState());
		$this->getTrm()->setFormRenderer($this->getFormRenderer());

		$this->getTrm()->openForm($this->getFormId(), $this->getSubmitUrl(), $this->getTotalWidth(), $this->isDialog());

		$this->getTrm()->displayRemainingForms("importInRawData");

		if($this->getState() != "start"){
			echo '<br /><font style="font-style:italic;" >';
			if($this->languageInstalled != null) echo $transS->t($p, "importInMultilanguageExplanation")." : ".implode(", ", $transS->getVisibleLanguage())."<br /><br />";
			echo $transS->t($p, "importInFilesExplanation")."<br /><br />";
			echo $transS->t($p, "importInDataValidationExplanation")."<br /><br />";
			echo $transS->t($p, "importAvailableHeaders").':<br /><textarea class="noElastic" readonly="readonly" style="height:50px;width:'.$this->getTotalWidth().'px;overflow-y:scroll;overflow-x:hidden;">'.implode($defaultSeparator." ", $this->keptHeaders)."</textarea>";
			echo '</font>';
		}

		$this->getTrm()->displayRemainingForms();

		$this->getTrm()->getCaptchaHTML($this);

		$this->getTrm()->closeForm($this->getFormId(), $this->goToNextState(), $this->getSubmitLabel(), $this->isDialog());

		$this->getWigiiExecutor()->openAsDialogForm(
			$exec->getIdAnswer(), $this->getTotalWidth()+$this->getCorrectionWidth(),
			'$("form", this).submit();', $transS->t($p, "importElementIn"),
			$transS->t($p, "ok"), $transS->t($p, "cancel"), null);

		$this->getTrm()->addJsCodeAfterFormIsShown($this->getFormId());
	}
}



