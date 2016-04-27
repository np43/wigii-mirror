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
 * Created on 28 sept 2012
 * by LWR
 */
class UpdateElementInFormExecutor extends FormExecutor implements ElementDataTypeSubfieldVisitor {

	private $rootPrincipal;
	protected function setRootPrincipal($var) {
		$this->rootPrincipal = $var;
	}
	protected function getRootPrincipal() {
		return $this->rootPrincipal;
	}

	private $writeGroupPList;
	protected function setWriteGroupPList($var) {
		$this->writeGroupPList = $var;
	}
	protected function getWriteGroupPList() {
		return $this->writeGroupPList;
	}

	public static function createInstance($wigiiExecutor, $record, $formId, $submitUrl, $rootPrincipal, $writeGroupPList) {
		$fe = new self();
		$fe->setWigiiExecutor($wigiiExecutor);
		$fe->setRecord($record);
		$fe->setFormId($formId);
		$fe->setSubmitUrl($submitUrl);
		$fe->setRootPrincipal($rootPrincipal);
		$fe->setWriteGroupPList($writeGroupPList);
		return $fe;
	}

	protected function parseHeaders($sep, $string) {
		$string = stripslashes($string);
		//remove any newlines
		$string = str_replace(array("\n", "\r"), '', $string);
		$r = preg_split("/\\" . $sep . "\s*(?=(?:[^\"]*\"[^\"]*\")*(?![^\"]*\"))/", $string);
		$r2 = array ();
		$pos = 1; //the header array stores foreach key the position of it. begins to 1 to prevent == null when position is first
		foreach ($r as $item) {
			//remove first quote if exist
			$item2 = preg_replace('/^"/', '', $item);
			if ($item2 != $item) {
				//then remove last quote
				$item2 = preg_replace("/\"$/", '', $item2);
			}
			//replace double " with one
			$item2 = str_replace('""', '"', $item2);
			$r2[$item2] = $pos++;
		}
		return $r2;
	}
	protected function parseDataRow($sep, $string) {
		//do the split with the separator followed by any spacer
		$string = str_replace(array("\n", "\r"), '', $string);
		$r = preg_split("/\\" . $sep . "\s*(?=(?:[^\"]*\"[^\"]*\")*(?![^\"]*\"))/", $string);
		$r2 = array ();
		foreach ($r as $item) {
			//take of first "
//			eput($item);
			$item2 = preg_replace("/^\"/", '', $item);
			//take of last if had the first "
			if ($item2 != $item) {
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

	//private variables
	private $headers; //headers defined from the imported data
	private $keptHeaders;
	private $importFieldSelectorList; //field selector list defined from the imported data + with the sysInfo fields
	private $importFieldSelectorListFromFile; //field selector list defined from the imported data
	private $elementPAList; //element list defined from the imported data
	private $originalFieldList; //fieldList defined for the current configuration (namespace + folder)
	private $computedFieldList; //fieldList defined from the imported data
	private $languageInstalled; //number of languages included in a multilanguage column
	private $hasElementIdAttribute = false; //does the data list include a column with id?
	private $hasElementModulenameAttribute = false; //does the data list include a column with the modulename?
	private $datas = array (); //array containing each line of the updatedDataRow
	private $parseDatas = array (); //array containing each parsed line of the updatedDataRow
	private $fieldKeyCheck = null; //array containing each key values with the associated line number
	private $fieldKey = null; //key field defined to match existing elements
	private $groupPList = null; //grouplist in which the update occurs (either one, either roots)

	private function getItemKey($fieldName, $subFieldName, $lang) {
		return $fieldName . ($subFieldName != "value" ? " " . $subFieldName : "") . ($lang != null ? " " . $lang : "");
	}
	private $actOnSubFieldStatus = null; //this flag is to determine what task to do on ActOnSubField, as it is used for different purposes
	private $crtUpdatedElement = null; //if actOnSubFieldStatus==actOnCheckedRecord contains the element with the updated data
	private $crtOriginalElement = null; //if actOnSubFieldStatus==actOnCheckedRecord contains the element with the original data
	public function actOnSubfield($field, $dataType, $subFieldName, $subFieldType, $sqlType, $userSelectedSubField, $lang = null) {
		//compute fieldSelectorList satus
		if ($this->actOnSubFieldStatus == "computeFieldSelectorList") {
			$fieldName = $field->getFieldName();
			if ($lang != null) {
				$this->languageInstalled[$lang] = $lang;
			}
			$key = $this->getItemKey($fieldName, $subFieldName, $lang);
			if ($this->headers == null || $this->headers[$key] != null) {
				$this->importFieldSelectorList->addFieldSelector($fieldName, ($subFieldName ? $subFieldName : "value"));
				$this->importFieldSelectorListFromFile->addFieldSelector($fieldName, ($subFieldName ? $subFieldName : "value"));
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
				if(!isset($this->computedFieldList)) $this->computedFieldList = FieldListArrayImpl::createInstance(false, true);
				$this->computedFieldList->addField($field);
				//mark the header as took
				$this->keptHeaders[$key] = $key;
			}
			//update the originalElement with the updated values
		} else {
			if ($this->actOnSubFieldStatus == "actOnCheckedRecord") {
				$this->crtOriginalElement->setFieldValue($this->crtUpdatedElement->getFieldValue($field->getFieldName(), $subFieldName), $field->getFieldName(), $subFieldName);
				//$this->crtOriginalElement->getWigiiBag()->applyOperator(SUPDOP_SET, $this->crtUpdatedElement->getFieldValue($field->getFieldName(), $subFieldName), $dataType->getDataTypeName(), $field->getFieldName(), $subFieldName, $lang, $field);
			}
		}
	}

	protected function computeFieldSelectorList($p, $exec) {
		$configS = $this->getWigiiExecutor()->getConfigurationContext();
		$elS = ServiceProvider :: getElementService();
		$rec = $this->getRecord();
		$this->hasElementIdAttribute = false;
		$this->hasElementModulenameAttribute = false;
		if ($this->headers != null) {
			$this->headers = $this->parseHeaders($rec->getFieldValue("csvSeparator"), $this->headers);
			//check for modulename field: take them off
			$this->hasElementIdAttribute = array_search("__element id", array_keys($this->headers));
			$this->hasElementModulenameAttribute = array_search("__element modulename", array_keys($this->headers));
			if ($this->hasElementModulenameAttribute !== false) {
				unset ($this->headers["__element modulename"]);
			}
		} else {
			$this->headers = array ();
		}
		$this->keptHeaders = array ();
		$this->originalFieldList = FormFieldList :: createInstance(FormBag :: createInstance());
		$this->importFieldSelectorList = FieldSelectorListArrayWebImpl :: createInstance(false);
		$this->importFieldSelectorListFromFile = FieldSelectorListArrayWebImpl :: createInstance(false, false);
		$configS->getFields($p, $exec->getCrtModule(), null, $this->originalFieldList);
		$this->actOnSubFieldStatus = "computeFieldSelectorList";
		foreach ($this->originalFieldList->getListIterator() as $field) {
			$fieldName = $field->getFieldName();
			if ($field->getDataType() != null) {
				//perform operation on each subField:
				$elS->visitDataTypeSubfields($field, $field->getDataType(), $this);
			}
		}
		//add element attribute fields
		$elFsl = $elS->getFieldSelectorListForElementAttributForUpdate();
		foreach ($elFsl->getListIterator() as $fsl) {
			$key = $this->getItemKey($fsl->getFieldName(), $fsl->getSubFieldName(), null);
			$this->keptHeaders[$key] = $key;
			if ($this->headers == null || $this->headers[$key] != null) {
				$this->keptHeaders[$key] = $key;
				$this->importFieldSelectorList->addFieldSelectorInstance($fsl);
				$this->importFieldSelectorListFromFile->addFieldSelectorInstance($fsl);
			}
		}
		//		$this->importFieldSelectorList->mergeFieldSelectorList($elFsl);
	}

	protected function doSpecificCheck($p, $exec) {
		$transS = ServiceProvider :: getTranslationService();
		$groupAS = ServiceProvider :: getGroupAdminService();
		$elS = ServiceProvider :: getElementService();
		$configS = $this->getWigiiExecutor()->getConfigurationContext();
		
		$rec = $this->getRecord();
		//set group
		$groupId = $rec->getFieldValue("updateInGroupId");
		if ($groupId) {
			$groupP = $groupAS->getGroup($p, $groupId);
			if ($groupP->getRights() && $groupP->getRights()->canWriteElement()) {
				$this->groupPList = GroupListArrayImpl :: createInstance()->addGroup($groupP->getDbEntity());
			} else {
				$this->addErrorToField($transS->h($p, "updateInGroupIdMustHaveWriteRights"), "updateInGroupId");
			}
		} else {
			$trashBinGroup = (string)$configS->getParameter($p, $exec->getCrtModule(), "trashBinGroup");			
			//take write root groups, except trashbin
			$this->groupPList = GroupListArrayImpl :: createInstance();
			foreach ($this->getWriteGroupPList()->getListIterator() as $groupP) {
				if($groupP->getId() != $trashBinGroup) $this->groupPList->addGroup($groupP->getDbEntity());
			}
		}

		//set separator
		$sep = $rec->getFieldValue("csvSeparator");

		//set key field
		$this->fieldKey = $rec->getFieldValue("fieldKey");

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

			$rec->setFieldValue($content, "updateInRawData");
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
		$this->datas = $rec->getFieldValue("updateInRawData");
		$this->datas = stripslashes($this->datas);
		//$this->datas = preg_split('/"\s*\\'.$sep.'*\s*$\n^\s*"/', $this->datas);
		$this->datas = explode("\n", $this->datas);

		$this->headers = $this->datas[0];
		//create and check the fieldlist from headers values
		$this->computeFieldSelectorList($p, $exec);

		//check key field exist in headers:
		if ($this->headers[$this->fieldKey] == null) {
			$this->addErrorToField($transS->h($p, "fieldKeyMustBeInHeader"), "fieldKey");
			return;
		}

		//if __element id is defined in headers fieldKey MUST be __element id.
		//this is to prevent duplicate update by ignoring element id
		if ($this->headers["__element id"] && $this->fieldKey != "__element id") {
			$this->addErrorToField($transS->h($p, "ifElementIdInHeaderFieldKeyMustBeElementId"), "fieldKey");
		}

		//eput($this->importFieldSelectorList->getListIterator());
		$remainingHeaders = array_diff_key($this->headers, $this->keptHeaders);
		if ($remainingHeaders != null) {
			$error = $transS->h($p, "importInInvalidHeaders").":<br />".implode(" | ", array_keys($remainingHeaders)).'<br />';
			$this->addErrorToField($error, "updateInRawData");
		}

		//foreach line:
		$wigiiBag = WigiiBagBaseImpl :: createInstance();
		$pRights = PrincipalRights :: createInstance();
		$pRights->setCanWriteElement(true);
		$errorInLine = array ();

		$this->elementPAList = ElementPAdvancedListArrayImpl :: createInstance();

		//fieldKey check unique
		$this->fieldKeyCheck = array ();
		$fieldKeyPos = $this->headers[$this->fieldKey] - 1;
//				eput($this->fieldKey);
//				eput($fieldKeyPos);
//				eput($this->headers);
		foreach ($this->datas as $lineNb => $dataRow) {
			if ($lineNb == 0)
				continue; //first line is header
			if ($dataRow == null)
				continue; //ignore empty lines
			$dataRow = preg_replace('/^[^\\' . $sep . ']*###\s*/', '', $dataRow);
			$this->datas[$lineNb] = trim($dataRow); //remove the information added

			$dataRow = $this->parseDataRow($sep, $dataRow);
			$this->parseDatas[$lineNb] = $dataRow;
			if ($this->hasElementModulenameAttribute !== false) {
				unset ($dataRow[$this->hasElementModulenameAttribute]);
			}
			//check if fieldKey value already exist in the list
			//			eput($dataRow);
			//			eput($this->fieldKeyCheck);
			if ($this->fieldKeyCheck[$dataRow[$fieldKeyPos]] != null) {
				$errorInLine[$lineNb] .= " !duplicate of line " . $this->fieldKeyCheck[$dataRow[$fieldKeyPos]] . " ";
			} else {
				$this->fieldKeyCheck[$dataRow[$fieldKeyPos]] = $lineNb;
			}
			//eput($dataRow);
			//create an ElementP
			$el = Element :: createInstance($exec->getCrtModule(), $this->computedFieldList, $wigiiBag);
			$elP = ElementP :: createInstance($el);
			$elP->setRights($pRights);
			if ($this->fieldKey == "__element id") {
				$el->setId($dataRow[$fieldKeyPos]); //important that the first id is null --> new ids will always be different (id starts at 1), this is to prevent a problem in the case importing into a blank DB
			} else {
				//if the check field is not on element id, then the ids will in any case be replaced.
				$el->setId($lineNb);
			}
			//fills the elementP + check datas
			$nb = 0;
			foreach ($this->importFieldSelectorListFromFile->getListIterator() as $key => $fs) {
				if (!$fs->isElementAttributeSelector()) {
					$field = $el->getFieldList()->getField($fs->getFieldName());
					$dataType = $field->getDataType();
					if ($dataType == null)
						continue;
				}

				if (!$fs->isElementAttributeSelector() && $dataType->getXml()->{$fs->getSubFieldName()}["multiLanguage"] == "1") {
					$pos = $this->headers[($fs->getSubFieldName() == "value" ? $fs->getFieldName() . " " . reset($this->languageInstalled) : $fs->getFieldName() . " " . $fs->getSubFieldName() . " " . " " . reset($this->languageInstalled))];
					$multilanguage = true;
				} else {
					$multilanguage = false;
					$pos = $this->headers[($fs->getSubFieldName() == "value" ? $fs->getFieldName() : $fs->getFieldName() . " " . $fs->getSubFieldName())];
				}

				//				if($lineNb == 1) eput($fs);
				//				if($lineNb == 1) eput("nb:".$nb." ");
				//				if($lineNb == 1) eput("pos:".$pos."\n");
				try {
					if ($fs->isElementAttributeSelector()) {
						//						eput("set ".put($dataRow[$nb])." for ".$fs->getFieldName()." ".$fs->getSubFieldName()." | ");
						//						eput($el->getAttribute($fs));
						//						eput(" => ");
						$el->setAttribute($dataRow[$pos -1], $fs);
						$nb++;
						//						eput($el->getAttribute($fs));
						//						eput("\n");
					} else {
						//verify language fields:
						if ($multilanguage) {
							$temp = array_slice($dataRow, $pos -1, count($this->languageInstalled));
							$temp = array_combine($this->languageInstalled, $temp);
							$el->setFieldValue(str_replace('\n', "\n", $temp), $fs->getFieldName(), $fs->getSubFieldName());
							$nb = $nb +count($this->languageInstalled);
							//verify MultipleAttributes management
						} else
							if ($dataType->getDataTypeName() == "MultipleAttributs" && ($fs->getSubFieldName() == "value" || $fs->getSubFieldName() == "")) {
								$temp = explode(", ", $dataRow[$pos -1]);
								$nb++;
								$temp = array_combine($temp, $temp);
								$el->setFieldValue($temp, $fs->getFieldName(), $fs->getSubFieldName());
								//calculate special email subfields: takes what is in the CSV or proposes a default value if not present.
							} else
								if ($dataType->getDataTypeName() == "Emails") {									
									if(!$fs->getSubFieldName() || $fs->getSubFieldName() == "value") {
										$newValue = str_replace('\n', "\n", $dataRow[$pos -1]);
										
										// value
										$el->setFieldValue($newValue, $fs->getFieldName(), "value");
										$nb++;
										
										// proofKey
										$subPos = $this->headers[$fs->getFieldName() . " proofKey"];
										if($subPos) {
											$el->setFieldValue(str_replace('\n', "\n", $dataRow[$subPos -1]), $fs->getFieldName(), "proofKey");
											$nb++;
										}
										// new code is calculated in actOnCheckedRecord because one record in CSV can update several elements in case of email key
										else $el->setFieldValue(null, $fs->getFieldName(), "proofKey");
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
											$el->setFieldValue(str_replace('\n', "\n", $dataRow[$subPos -1]), $fs->getFieldName(), "externalCode");
											$nb++;
										}
										// new code is calculated in actOnCheckedRecord because one record in CSV can update several elements in case of email key
										else $el->setFieldValue(null, $fs->getFieldName(), "externalCode");
									}									
								} else {
									//normal
									$el->setFieldValue(str_replace('\n', "\n", $dataRow[$pos -1]), $fs->getFieldName(), $fs->getSubFieldName());
									if($fs->getSubFieldName()!="value" && $this->headers[$fs->getFieldName() . " ".$fs->getSubFieldName()] || $fs->getSubFieldName()=="value" && ($this->headers[$fs->getFieldName()] || $this->headers[$fs->getFieldName()." value"])) $nb++;
								}
					}
				} catch (ServiceException $e) {
					$errorInLine[$lineNb] .= " !Exception " . $e->getCode() . " " . $e->getMessage();
				}
			}

			//	- check the number of fields
			if (count($dataRow) != $nb) {
				$errorInLine[$lineNb] .= " !NB found " . count($dataRow) . " : $nb ";
			}
			//if no sys_creationUser and no sys_creationDate then add them
			if ($el->getSys_creationUser() == null) {
				$el->setSys_creationUser($p->getRealUserId());
				$el->setSys_creationUsername($p->getRealUsername());
			}
			if ($el->getSys_creationDate() == null) {
				$el->setSys_creationDate(time());
			}
			//add it to the list
			$this->elementPAList->addElementP($elP);
			$this->datas[$lineNb] = $lineNb . $errorInLine[$lineNb] . " ### " . $this->datas[$lineNb];
		}
		if ($errorInLine != null) {
			$error = $transS->h($p, "importErrorOnLines").": ".implode(", ", array_keys($errorInLine))."<br />".$transS->h($p, "importErrorOnLinesDetails");
			$this->addErrorToField($error, "updateInRawData");
		}
		if($this->elementPAList->count()==0){
			$this->addErrorToField($transS->h($p, "noDataToUpdate"), "updateInRawData");
		}
		$rec->setFieldValue(addslashes(implode("\n", $this->datas)), "updateInRawData");

	}

	protected function actOnCheckedRecord($p, $exec) {
		//import the List
		$elS = ServiceProvider :: getElementService();
		$transS = ServiceProvider :: getTranslationService();

		$rec = $this->getRecord();

		//lookup existing element list
		$originalElementPAList = ElementPAdvancedListArrayImpl :: createInstance(null, $this->importFieldSelectorList);		
		$filteredElementPAList = ElementPAdvancedListArrayImpl :: createInstance(null, $this->importFieldSelectorList);
		$fieldSelectorLogExpParser = TechnicalServiceProvider :: getFieldSelectorLogExpParser();
		$lf = ListFilter :: createInstance();
		$lf->setFieldSelectorList($this->importFieldSelectorList);
		//make the field selector based on fieldKey values
		$fieldKeyValues = array_keys($this->fieldKeyCheck);
		//$stringLogExp = str_replace(" ", ".", $this->fieldKey).' LIKE "'.implode('" OR '.str_replace(" ", ".", $this->fieldKey).' LIKE "', $fieldKeyValues).'"';
		$stringLogExp = str_replace(" ", ".", $this->fieldKey) . ' IN ("' . implode('", "', $fieldKeyValues) . '")';
		$lf->setFieldSelectorLogExp($fieldSelectorLogExpParser->createLogExpFromString($stringLogExp));
		$nbRows = $elS->getAllElementsInGroups($p, $this->groupPList, $originalElementPAList, true, $lf);

		//loop through existing and make updates
		list ($fieldKey, $subFieldKey) = explode(" ", $this->fieldKey);
		if (!$subFieldKey) $subFieldKey = "value";
		$elementPListIterator = $this->elementPAList->getListIterator();
		//noneUsedLines is an array containing each imported lines with it id and as value "true"
		//the true indicates the line is not used. In the following loop, this flag will be set to false when the line is used
		$noneUsedLines = array_combine(array_keys($elementPListIterator), array_fill(0, count($elementPListIterator), true));
		$nbBlockedElements = 0;
		//		eput($fieldKey." ".$subFieldKey);
		//		eput($this->fieldKeyCheck);
		//		eput($elementPListIterator);
		//		eput($this->elementPAList);
		$this->actOnSubFieldStatus = "actOnCheckedRecord";
		foreach ($originalElementPAList->getListIterator() as $elementP) {
			if ($fieldKey == "__element" && $subFieldKey == "id") {
				$fieldKeyValue = $elementP->getId();
				$updatedElementP = $elementPListIterator[$fieldKeyValue];
			} else {
				$fieldKeyValue = $elementP->getDbEntity()->getFieldValue($fieldKey, $subFieldKey);
				$updatedElementP = $elementPListIterator[$this->fieldKeyCheck[$fieldKeyValue]];
			}
			//mark line as read
			//updatedElementP id is either the line nb either the real id, depending on the field key
			$noneUsedLines[$updatedElementP->getId()] = false;

			$this->crtUpdatedElement = $updatedElementP->getDbEntity();
			$this->crtOriginalElement = $elementP->getDbEntity();
			
			//ignores blocked elements
			if($this->crtOriginalElement->isState_blocked()) {
				$nbBlockedElements++;
			}
			else {
				//adds current element to filtered list
				$filteredElementPAList->addElementP($elementP);
				
				//visit subfields and update as appropriate
				if(isset($this->computedFieldList)) {
					foreach ($this->computedFieldList->getListIterator() as $field) {
						if ($field->getDataType() != null) {										
							//update special email subfields: updates subfield if declared in CSV or email changed. proofKey and externalCode are recalculated if empty.
							if($field->getDataType()->getDataTypeName() == "Emails") {						
								$newValue = $this->crtUpdatedElement->getFieldValue($field->getFieldName());
								$currentValue = $this->crtOriginalElement->getFieldValue($field->getFieldName());
								
								if($newValue == null) $updatedValue = null;
								elseif($currentValue == null) $updatedValue = $newValue;
								else $updatedValue = str_update($currentValue, $newValue, SUPDOP_SET, '/'.ValueListArrayMapper::Natural_Separators.'/', ", ");
								
								$emailChanged = ($updatedValue != $currentValue);										
								// value
								$this->crtOriginalElement->setFieldValue($updatedValue, $field->getFieldName(), "value");
														
								// proofKey						
								if($emailChanged || $this->headers[$field->getFieldName() . " proofKey"]) {
									$value = $this->crtUpdatedElement->getFieldValue($field->getFieldName(), "proofKey");
									// generates proofKey if empty
									if(empty($value)) $value = $elS->getEmailValidationCode($p, $updatedValue);
									$this->crtOriginalElement->setFieldValue($value, $field->getFieldName(), "proofKey");
								}
								// proofStatus (updates proof only if email is not emptied (keep possible delete status))
								if(($emailChanged && $updatedValue) || $this->headers[$field->getFieldName() . " proofStatus"]) {
									$this->crtOriginalElement->setFieldValue($this->crtUpdatedElement->getFieldValue($field->getFieldName(), "proofStatus"), $field->getFieldName(), "proofStatus");
								}
								// proof (updates proof only if email is not emptied (keep possible delete status))
								if(($emailChanged && $updatedValue) || $this->headers[$field->getFieldName() . " proof"]) {
									$this->crtOriginalElement->setFieldValue($this->crtUpdatedElement->getFieldValue($field->getFieldName(), "proof"), $field->getFieldName(), "proof");
								}
								// externalConfigGroup
								if($emailChanged || $this->headers[$field->getFieldName() . " externalConfigGroup"]) {
									$this->crtOriginalElement->setFieldValue($this->crtUpdatedElement->getFieldValue($field->getFieldName(), "externalConfigGroup"), $field->getFieldName(), "externalConfigGroup");
								}
								// externalAccessLevel
								if($emailChanged || $this->headers[$field->getFieldName() . " externalAccessLevel"]) {
									$this->crtOriginalElement->setFieldValue($this->crtUpdatedElement->getFieldValue($field->getFieldName(), "externalAccessLevel"), $field->getFieldName(), "externalAccessLevel");
								}
								// externalAccessEndDate
								if($emailChanged || $this->headers[$field->getFieldName() . " externalAccessEndDate"]) {
									$this->crtOriginalElement->setFieldValue($this->crtUpdatedElement->getFieldValue($field->getFieldName(), "externalAccessEndDate"), $field->getFieldName(), "externalAccessEndDate");
								}
								// externalCode
								if($emailChanged || $this->headers[$field->getFieldName() . " externalCode"]) {
									$value = $this->crtUpdatedElement->getFieldValue($field->getFieldName(), "externalCode");
									// generates externalCode if empty
									if(empty($value)) {
										$value = $elS->getEmailExternalCode($p, $this->crtOriginalElement->getId(), $field->getFieldName(), $updatedValue);
										// resets externalAccessLevel to 0 if not present in CSV and new externalCode is generated
										if(!($emailChanged || $this->headers[$field->getFieldName() . " externalAccessLevel"])) {
											$this->crtOriginalElement->setFieldValue(0, $field->getFieldName(), "externalAccessLevel");
										}
									}
									$this->crtOriginalElement->setFieldValue($value, $field->getFieldName(), "externalCode");
								}
							} else {
								//perform operation on each subField						
								$elS->visitDataTypeSubfields($field, $field->getDataType(), $this);
							}
						}
					}
				}
				//update any element attribute
				$importFieldSelectorList = $this->importFieldSelectorListFromFile->getListIterator();
				if($importFieldSelectorList["(__element(modulename))"]) $this->crtOriginalElement->setAttribute($this->crtUpdatedElement->getAttribute($importFieldSelectorList["(__element(modulename))"]), $importFieldSelectorList["(__element(modulename))"]);
				if($importFieldSelectorList["(__element(version))"]) $this->crtOriginalElement->setAttribute($this->crtUpdatedElement->getAttribute($importFieldSelectorList["(__element(version))"]), $importFieldSelectorList["(__element(version))"]);
				if($importFieldSelectorList["(__element(state_locked))"]) $this->crtOriginalElement->setAttribute($this->crtUpdatedElement->getAttribute($importFieldSelectorList["(__element(state_locked))"]), $importFieldSelectorList["(__element(state_locked))"]);
				if($importFieldSelectorList["(__element(state_lockedInfo))"]) $this->crtOriginalElement->setAttribute($this->crtUpdatedElement->getAttribute($importFieldSelectorList["(__element(state_lockedInfo))"]), $importFieldSelectorList["(__element(state_lockedInfo))"]);
				if($importFieldSelectorList["(__element(state_important1))"]) $this->crtOriginalElement->setAttribute($this->crtUpdatedElement->getAttribute($importFieldSelectorList["(__element(state_important1))"]), $importFieldSelectorList["(__element(state_important1))"]);
				if($importFieldSelectorList["(__element(state_important1Info))"]) $this->crtOriginalElement->setAttribute($this->crtUpdatedElement->getAttribute($importFieldSelectorList["(__element(state_important1Info))"]), $importFieldSelectorList["(__element(state_important1Info))"]);
				if($importFieldSelectorList["(__element(state_important2))"]) $this->crtOriginalElement->setAttribute($this->crtUpdatedElement->getAttribute($importFieldSelectorList["(__element(state_important2))"]), $importFieldSelectorList["(__element(state_important2))"]);
				if($importFieldSelectorList["(__element(state_important2Info))"]) $this->crtOriginalElement->setAttribute($this->crtUpdatedElement->getAttribute($importFieldSelectorList["(__element(state_important2Info))"]), $importFieldSelectorList["(__element(state_important2Info))"]);
				if($importFieldSelectorList["(__element(state_hidden))"]) $this->crtOriginalElement->setAttribute($this->crtUpdatedElement->getAttribute($importFieldSelectorList["(__element(state_hidden))"]), $importFieldSelectorList["(__element(state_hidden))"]);
				if($importFieldSelectorList["(__element(state_hiddenInfo))"]) $this->crtOriginalElement->setAttribute($this->crtUpdatedElement->getAttribute($importFieldSelectorList["(__element(state_hiddenInfo))"]), $importFieldSelectorList["(__element(state_hiddenInfo))"]);
				if($importFieldSelectorList["(__element(state_archived))"]) $this->crtOriginalElement->setAttribute($this->crtUpdatedElement->getAttribute($importFieldSelectorList["(__element(state_archived))"]), $importFieldSelectorList["(__element(state_archived))"]);
				if($importFieldSelectorList["(__element(state_archivedInfo))"]) $this->crtOriginalElement->setAttribute($this->crtUpdatedElement->getAttribute($importFieldSelectorList["(__element(state_archivedInfo))"]), $importFieldSelectorList["(__element(state_archivedInfo))"]);
				if($importFieldSelectorList["(__element(state_deprecated))"]) $this->crtOriginalElement->setAttribute($this->crtUpdatedElement->getAttribute($importFieldSelectorList["(__element(state_deprecated))"]), $importFieldSelectorList["(__element(state_deprecated))"]);
				if($importFieldSelectorList["(__element(state_deprecatedInfo))"]) $this->crtOriginalElement->setAttribute($this->crtUpdatedElement->getAttribute($importFieldSelectorList["(__element(state_deprecatedInfo))"]), $importFieldSelectorList["(__element(state_deprecatedInfo))"]);
				if($importFieldSelectorList["(__element(state_finalized))"]) $this->crtOriginalElement->setAttribute($this->crtUpdatedElement->getAttribute($importFieldSelectorList["(__element(state_finalized))"]), $importFieldSelectorList["(__element(state_finalized))"]);
				if($importFieldSelectorList["(__element(state_finalizedInfo))"]) $this->crtOriginalElement->setAttribute($this->crtUpdatedElement->getAttribute($importFieldSelectorList["(__element(state_finalizedInfo))"]), $importFieldSelectorList["(__element(state_finalizedInfo))"]);
				if($importFieldSelectorList["(__element(state_approved))"]) $this->crtOriginalElement->setAttribute($this->crtUpdatedElement->getAttribute($importFieldSelectorList["(__element(state_approved))"]), $importFieldSelectorList["(__element(state_approved))"]);
				if($importFieldSelectorList["(__element(state_approvedInfo))"]) $this->crtOriginalElement->setAttribute($this->crtUpdatedElement->getAttribute($importFieldSelectorList["(__element(state_approvedInfo))"]), $importFieldSelectorList["(__element(state_approvedInfo))"]);
				if($importFieldSelectorList["(__element(state_dismissed))"]) $this->crtOriginalElement->setAttribute($this->crtUpdatedElement->getAttribute($importFieldSelectorList["(__element(state_dismissed))"]), $importFieldSelectorList["(__element(state_dismissed))"]);
				if($importFieldSelectorList["(__element(state_dismissedInfo))"]) $this->crtOriginalElement->setAttribute($this->crtUpdatedElement->getAttribute($importFieldSelectorList["(__element(state_dismissedInfo))"]), $importFieldSelectorList["(__element(state_dismissedInfo))"]);
				if($importFieldSelectorList["(__element(state_blocked))"]) $this->crtOriginalElement->setAttribute($this->crtUpdatedElement->getAttribute($importFieldSelectorList["(__element(state_blocked))"]), $importFieldSelectorList["(__element(state_blocked))"]);
				if($importFieldSelectorList["(__element(state_blockedInfo))"]) $this->crtOriginalElement->setAttribute($this->crtUpdatedElement->getAttribute($importFieldSelectorList["(__element(state_blockedInfo))"]), $importFieldSelectorList["(__element(state_blockedInfo))"]);
				if($importFieldSelectorList["(__element(sys_creationDate))"]) $this->crtOriginalElement->setAttribute($this->crtUpdatedElement->getAttribute($importFieldSelectorList["(__element(sys_creationDate))"]), $importFieldSelectorList["(__element(sys_creationDate))"]);
				if($importFieldSelectorList["(__element(sys_creationUser))"]) $this->crtOriginalElement->setAttribute($this->crtUpdatedElement->getAttribute($importFieldSelectorList["(__element(sys_creationUser))"]), $importFieldSelectorList["(__element(sys_creationUser))"]);
				if($importFieldSelectorList["(__element(sys_creationUsername))"]) $this->crtOriginalElement->setAttribute($this->crtUpdatedElement->getAttribute($importFieldSelectorList["(__element(sys_creationUsername))"]), $importFieldSelectorList["(__element(sys_creationUsername))"]);
			}
		}

		//keep only lines which hasn't been marked as read false
		//		fput($noneUsedLines);
		$noneUsedLines = array_filter($noneUsedLines);

		//store changes
//		fput($filteredElementPAList);
//		fput($this->importFieldSelectorList);
		if(!$filteredElementPAList->isEmpty()){
			$elS->updateMultipleElement($this->getRootPrincipal(), $p, $filteredElementPAList, $this->importFieldSelectorList);
			//fill any empty email codes
			$elS->fillEmptyEmailValidationAndExternalCode($p);
			//correct any change of validation code in the update
			//deprecated since v.4.322 R1746 (27.08.2015): $elS->correctEmailValidationCode($p, $filteredElementPAList->getIds());

			//add the groups for notification
			$this->getWigiiExecutor()->throwEvent()->insertMultipleElement(PWithModuleWithElementPListWithGroupList :: createInstance($p, $exec->getCrtModule(), $filteredElementPAList, $this->groupPList));
		}

		//important to clear the cancel stack
		$exec->addJsCode("actOnCloseDialog('" . $exec->getIdAnswer() . "');");

		//any none-used lines are proposed
		//	- either to be imported
		//	- either if exist should be updated with selecting the root folder
		if ($noneUsedLines) {
			//			fput($noneUsedLines);
			//			fput($this->datas);
			$dataToImport = array ();
			//add headers
			$dataToImport[0] = $this->datas[0];
//			fput($noneUsedLines);
			if ($fieldKey == "__element" && $subFieldKey == "id") {
				//none used lines contains the element id as key
				foreach($noneUsedLines as $fieldKeyValue=>$nothing){
					$dataToImport[] = str_replace('\\n', '\\\\n', str_replace('&#39;', '\\&#39;', $this->datas[$this->fieldKeyCheck[$fieldKeyValue]]));
				}
			} else {
				//$noneUsedLines contains the line number as key if the field key is not the element id
				$dataToImport = array_merge($dataToImport, array_intersect_key($this->datas, $noneUsedLines));
			}

			//display the number of data updated
			$exec->addRequests("moduleView/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/display/moduleView/");

			$message = $filteredElementPAList->count()." ".$transS->t($p, "elementsUpdated").($nbBlockedElements>0?" (".$nbBlockedElements." ".$transS->t($p, "blockedElementsIgnored").")":"");
			$message .= "<br />";
			$message .= "<br />";
			$message .= "<span class='R'><b>".(count($noneUsedLines))."</b> ".$transS->t($p, "elementsHaventBeenFound")."</span><br />";
			$dataToImport = implode("\n", $dataToImport);
			$dataToImport = str_replace("\r", "", $dataToImport);
			$dataToImport = str_replace("\n", "\\n", $dataToImport);
			$dataToImport = str_replace('"', '&quot;', $dataToImport);
			$dataToImport = str_replace("'", "\\'", $dataToImport);
//			fput($dataToImport);
			$message .= '<button href="#" onclick="
url = SITE_ROOT +\'Update/\'+crtContextId+EXEC_requestSeparator+ \'importDialog/\'+crtWigiiNamespaceUrl+\'/\'+crtModuleName+\'/importElementIn\';
setVis(\'busyDiv\', true);
var myAjax = new jQuery.ajax({
	type: \'POST\',
	url: encodeURI(url),
	success : parseUpdateResult,
	cache:false,
	data: {
		csvSeparator_value: \''.$rec->getFieldValue("csvSeparator").'\',
		inGroupId_value: \''.$rec->getFieldValue("updateInGroupId").'\',
		importInRawData_value: \''.$dataToImport.'\',
		idForm: \'importElementIn_form\',
		action: \'newCheck\'
	},
	error: errorOnUpdate
});
$(this).removeAttr(\'onclick\');
">'.$transS->t($p, "clicHereToImportNoneExistingElements").'</button><br /><br />';
			$this->getWigiiExecutor()->openAsMessage($exec->getIdAnswer(), 350, ($filteredElementPAList->isEmpty() ? $transS->t($p, "operationUnsuccessfull") : $transS->t($p, "operationPartlyDone")), $message);
		} else {
			$this->getWigiiExecutor()->openAsMessage($exec->getIdAnswer(), 350, ($filteredElementPAList->isEmpty() ? $transS->t($p, "operationUnsuccessfull") : ($nbBlockedElements>0?$transS->t($p, "operationPartlyDone"):$transS->t($p, "operationDoneSuccessfully"))), 
					$filteredElementPAList->count()." ".$transS->t($p, "elementsUpdated").($nbBlockedElements>0?" (".$nbBlockedElements." ".$transS->t($p, "blockedElementsIgnored").")":""), null, "Ok", null, null, ($nbBlockedElements>0||$filteredElementPAList->isEmpty()?"warning":"done"));
		}

		$exec->invalidCache($p, 'elementDialog');
		$exec->invalidCache($p, 'moduleView');
		$exec->addRequests("moduleView/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/display/moduleView/");
	}

	protected function doRenderForm($p, $exec) {
		$transS = ServiceProvider :: getTranslationService();
		$defaultSeparator = (string) $this->getWigiiExecutor()->getConfigurationContext()->getParameter($p, null, "csvDefaultSeparator");
		if ($defaultSeparator == null)
			$defaultSeparator = ",";

		if($this->getState() == "start"){
			$this->computeFieldSelectorList($p, $exec);
			$this->getRecord()->setFieldValue($defaultSeparator, "csvSeparator");
			$this->getRecord()->setFieldValue(implode($defaultSeparator, $this->keptHeaders), "updateInRawData");
			$this->getRecord()->getWigiiBag()->setHidden(true, "updateInRawData");
			$this->getRecord()->getWigiiBag()->setHidden(false, "csvFile");
		} else {
			$this->getRecord()->getWigiiBag()->setHidden(true, "csvFile");
			$this->setTotalWidth(700);
		}


		$this->getTrm()->setState($this->getState());
		$this->getTrm()->setFormRenderer($this->getFormRenderer());

		$this->getTrm()->openForm($this->getFormId(), $this->getSubmitUrl(), $this->getTotalWidth(), $this->isDialog());

		$this->getTrm()->displayRemainingForms("updateInRawData");

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

		$this->getWigiiExecutor()->openAsDialogForm($exec->getIdAnswer(), $this->getTotalWidth() + $this->getCorrectionWidth(), '$("form", this).submit();', $transS->t($p, "updateElementIn"), $transS->t($p, "ok"), $transS->t($p, "cancel"), null);

		$this->getTrm()->addJsCodeAfterFormIsShown($this->getFormId());
	}
}