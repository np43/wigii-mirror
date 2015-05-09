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

/**
 * A wigii Record is the parent of an element or an activity. 
 * A record is a set of fields with values.
 * Created by LWR 15 sept 09
 */
abstract class Record extends DbEntityInstance
{
	const GroupEmailNotificationString = "##GroupEmailNotification##";

	private $idBeforeInsert;
	public function setId($id)
	{
		// if still new, then stores id before insert
		if($this->isNew($id))
		{
			$this->idBeforeInsert = $this->getId();
		}
		return parent::setId($id);
	}
	private $fieldList;
	public function getFieldList(){ return $this->fieldList; }
	public function setFieldList($fieldList) { $this->fieldList = $fieldList; }
	private $wigiiBag;
	public function getWigiiBag() { return $this->wigiiBag; }
	public function setWigiiBag($wigiiBag) { $this->wigiiBag = $wigiiBag; }

	/**
	 * An attached rec can be set for resolving redirect attribute of some
	 * fields
	 */
	protected $attachedRecord;
	public function getAttachedRecord(){ return $this->attachedRecord; }
	public function attachRecord($rec){ $this->attachedRecord = $rec; }

	public static function createInstance($fieldList = null, $wigiiBag = null){
		$r = new Record();
		if(isset($fieldList)) $r->setFieldList($fieldList);
		if(isset($wigiiBag)) $r->setWigiiBag($wigiiBag);
		return $r;
	}

	/**
	 * Returns the value of a field given its name
	 * subFieldName: the dataType subfield name from which to retrieve the value. If null takes the predefined "value" subfield.
	 */
	public function getFieldValue($fieldName, $subFieldName = null) {
		$field = $this->getFieldList()->getField($fieldName);
		if(isset($field)) return $field->getValue($this->getId(), $this->getWigiiBag(), $subFieldName);
		else return null;
	}
	/**
	 * Returns the value of a field stored under a specific record id, given its name
	 * This method is useful if record id changed, but wigii bag content is stored under previous id.
	 */
	public function getFieldValueUnderSpecificId($elementId, $fieldName, $subFieldName = null)
	{
		$field = $this->getFieldList()->getField($fieldName);
		if(isset($field)) return $field->getValue($elementId, $this->getWigiiBag(), $subFieldName);
		else return null;
	}

	/**
	 * Returns the value of a field given its name as it was before the database insertion
	 */
	public function getFieldValueBeforeInsert($fieldName, $subFieldName = null)
	{
		return $this->getFieldValueUnderSpecificId($this->idBeforeInsert, $fieldName, $subFieldName);
	}

	/**
	 * Sets the value of a field given its name
	 * subFieldName: the dataType subfield name for which to store the value. If null takes the predefined "value" subfield.
	 */
	public function setFieldValue($value, $fieldName, $subFieldName = null) {
		$field = $this->getFieldList()->getField($fieldName);
		if(isset($field)) $field->setValue($value, $this->getId(), $this->getWigiiBag(), $subFieldName);
		else throw new RecordException("field '$fieldName' is not a valid field name in the record", RecordException::INVALID_ARGUMENT);
	}

	public abstract function getModule();
	public abstract function getActivity();

	/**
	 * Returns an array of redirect value attribut for the specific value.
	 * This works only for Attributes or MultipleAttributes or Booleans
	 * with an attribut node with a redirect attribut seted.
	 * @param forValue value of the field:
	 * 	- if Attributs -> string
	 *  - if MultipleAttributs -> array of string
	 *  - if Booleans -> bool (done only if true)
	 */
	public function getRedirectedFieldValues($p, $fieldName, $forValue)
	{
		$f = $this->getFieldList()->getField($fieldName);
		$fxml = $f->getXml();
		$dt = $f->getDataType()->getDataTypeName();
		if($dt == "Attributs" || $dt == "MultipleAttributs"){
			if(is_array($forValue)){
				$forValue = implode("\" or text()=\"", $forValue);
			}
			$redirects = $fxml->xpath("attribute[@redirect and (text()=\"$forValue\")]");
			if($redirects){
				$attachedRec = $this->getAttachedRecord();
				if($attachedRec == null) throw new RecordException("", RecordException::NO_ATTACHED_RECORD);

				if($dt == "MultipleAttributs"){
					$returnValue = array();
					foreach($redirects as $redirect){
						$returnValue[] = $attachedRec->getFieldValue((string)$redirect["redirect"]); //(string)$redirect["redirect"];
					}
				} else {
					$returnValue = $attachedRec->getFieldValue((string)$redirects[0]["redirect"]);
				}
				return $returnValue;
			}
		}

		if($dt == "Booleans" && $fxml["redirect"] != null){
			$attachedRec = $this->getAttachedRecord();
			if($attachedRec == null) throw new RecordException("", RecordException::NO_ATTACHED_RECORD);

			if($forValue){
				$attachedRec->getFieldValue((string)$fxml["redirect"]);
			}
		}
		return false;
	}

	/**
	 * Fill the valueList with the idGroups found in an attribute (even if this attribute is not the one selected)
	 * The redirection for Attributes, MultipleAttributes, Booleans is solved if defined.
	 * This works only for Attributes, MultipleAttributes, Booleans.
	 * No redirection can be defined in this process.
	 * @param $p Principal
	 * @param $valueList : ValueList
	 */
	public function getAllLinkedIdGroupInRecord($p, $valueList){
		$wg = $this->getWigiiBag();
		foreach($this->getFieldList()->getListIterator() as $fieldName=>$f){
			//check only dataTypes
			if($f->getDataType() != null){
				$fxml = $f->getXml();
				$dt = $f->getDataType()->getDataTypeName();
				$gids = array();
				if($dt == "Attributs" || $dt == "MultipleAttributs"){
					$gids= $fxml->xpath("attribute[@idGroup and @idGroup!=\"\"]/attribute::idGroup");
					if($gids)  $gids = implode(";",$gids);
				} else if($dt == "Booleans"){
					if($fxml["idGroup"] != null){
						$gids = (string)$fxml["idGroup"];
					}
				}
				if($gids) $valueList->addValue($gids);
			}
		}
	}
	/**
	 * Fill the valueList with the idGroups found in an attribute.
	 * The redirection for Attributes, MultipleAttributes, Booleans is solved if defined.
	 * This works only for Attributes, MultipleAttributes, Booleans.
	 * No redirection can be defined in this process.
	 * @param $p Principal
	 * @param $valueList : ValueList
	 * @param $isMultiple: Boolean, if true the check will be done accordingly to wigiiBag->isMultipleChecked
	 */
	public function getLinkedIdGroupInRecord($p, $valueList, $isMultiple=false){
		$wg = $this->getWigiiBag();
		foreach($this->getFieldList()->getListIterator() as $fieldName=>$f){
			//check only dataTypes
			if($f->getDataType() != null){
				//if isMultiple, check only the multiple checked field
				if(!$isMultiple || $isMultiple && $wg->isMultipleChecked($fieldName)){
					$fxml = $f->getXml();
					$dt = $f->getDataType()->getDataTypeName();
					$gids = array();
					if($dt == "Attributs" || $dt == "MultipleAttributs"){
						$forValue = $this->getFieldValue($fieldName);
						if($forValue == null) continue; //empty value cause a simpleXml path warning
						if(is_array($forValue)){
							$forValue = implode("\" or text()=\"", $forValue); //$tempRes;
						}
						$gids= $fxml->xpath("attribute[@idGroup and (text()=\"$forValue\") and @idGroup!=\"\"]/attribute::idGroup");
						if($gids)  $gids = implode(";",$gids);
					} else if($dt == "Booleans"){
						$forValue = $this->getFieldValue($fieldName);
						if($forValue){
							if($fxml["idGroup"] != null){
								$gids = (string)$fxml["idGroup"];
							}
						}
					}
					if($gids) $valueList->addValue($gids);
				}
			}
		}
	}
	/**
	 * Fill the valueList with the idGroups found in any attribute that are not selected
	 * in the multiple edit record. Used in: EditMultipleElementFormExecutor.php
	 * @param $p Principal
	 * @param $valueList : ValueList
	 */
	public function getNoneLinkedIdGroupInRecord($p, $valueList, $isMultiple=false){
		$wg = $this->getWigiiBag();
		foreach($this->getFieldList()->getListIterator() as $fieldName=>$f){
			if($f->getDataType() != null){
				if(!$isMultiple || $isMultiple && $wg->isMultipleChecked($fieldName) && !$wg->isMultipleAddOnlyChecked($fieldName)){
					$fxml = $f->getXml();
					$dt = $f->getDataType()->getDataTypeName();
					$gids = array();
					if($dt == "Attributs" || $dt == "MultipleAttributs"){
						$forValue = $this->getFieldValue($fieldName);
						if($forValue != null){
							if(is_array($forValue)){
								$forValue = implode("\" or text()!=\"", $forValue); //$tempRes;
							}
							$gids= $fxml->xpath("attribute[@idGroup and (text()!=\"$forValue\") and @idGroup!=\"\"]/attribute::idGroup");
						} else {
							//empty value cause a simpleXml path warning
							$gids= $fxml->xpath("attribute[@idGroup and @idGroup!=\"\"]/attribute::idGroup");
						}
						if($gids)  $gids = implode(";",$gids);
					} else if($dt == "Booleans"){
						$forValue = $this->getFieldValue($fieldName);
						if(!$forValue){
							if($fxml["idGroup"] != null){
								$gids = (string)$fxml["idGroup"];
							}
						}
					}
					if($gids) $valueList->addValue($gids);
				}
			}
		}
	}

	/**
	 * Fill the valueList with the emails found in an attribute email.
	 * The redirection for Attributes, MultipleAttributes, Booleans is solved if defined.
	 * This works only for Attributes, MultipleAttributes, Booleans.
	 * This method is recursive for the redirection, but allows a redirection on an Emails field.
	 * Only in the case of a redirection an Emails field value is returned. If a redirection is set and then
	 * no attachedRecord is setted to this level, then an exception is thrown
	 * with an attribut node with a redirect attribut seted.
	 * @param $p Principal
	 * @param $valueList : ValueList
	 * @return bool, true if GroupEmailNotification string request was in the email found. But the GroupEmailNotification emails are not added. this needs to be done externally
	 */
	public function getLinkedEmailInRecord($p, $valueList){
		$hasGroupEmailNotification = false;
		foreach($this->getFieldList()->getListIterator() as $fieldName=>$field){
			if($field->getDataType() != null){
				$value = $this->getLinkedEmailInField($p, $fieldName, $this->getFieldValue($fieldName));
				//we always do the check of GroupEmailNotification to replace it as ""
				if(!is_array($value)) $value = array($value);
				foreach($value as $val){
					if(strstr($val, "##GroupEmailNotification##")) {
						$val = str_replace("##GroupEmailNotification##", "", $val);
						$hasGroupEmailNotification = true;
					}
					if($val) $valueList->addValue($val);
				}
			}
		}
		return $hasGroupEmailNotification;
	}

	/**
	 * Returns a string with the emails define in an attribute email.
	 * The redirection for Attributes, MultipleAttributes, Booleans is solved if defined.
	 * This works only for Attributes, MultipleAttributes, Booleans.
	 * This method is recursive for the redirection, but allows a redirection on an Emails field.
	 * Only in the case of a redirection an Emails field value is returned. If a redirection is set and then
	 * no attachedRecord is setted to this level, then an exception is thrown
	 * with an attribut node with a redirect attribut seted.
	 * @param forValue value of the field:
	 * 	- if Attributs -> string
	 *  - if MultipleAttributs -> array of string
	 *  - if Booleans -> bool (done only if true)
	 */
	public function getLinkedEmailInField($p, $fieldName, $forValue)
	{
		return $this->doGetLinkedEmailInField($p, $fieldName, $forValue, false);
	}

	protected function doGetLinkedEmailInField($p, $fieldName, $forValue, $fromRedirect){
		$f = $this->getFieldList()->getField($fieldName);
		$fxml = $f->getXml();
		$dt = $f->getDataType()->getDataTypeName();

		if($dt == "Emails" && ($fromRedirect || $fxml["enableForNotification"]=="1")){
			return $forValue;
		} else if($dt == "Attributs" || $dt == "MultipleAttributs"){
			if($forValue == null) return null; //empty value cause a simpleXml path warning
			$emails = "";
			if(is_array($forValue)){
//				$tempRes = "";
//				$tempFirst = true;
//				foreach($forValue as $tempVal){
//					if($tempFirst) $tempFirst = false;
//					else $tempRes .= "' or text()='";
//					$tempRes .= str_replace("'", "\\'", $tempVal);
//				}
				$forValue = implode("\" or text()=\"", $forValue); //$tempRes;
			} else {
//				$forValue = str_replace("'", "\\'", $forValue);
			}
			$emails = $fxml->xpath("attribute[@email and (text()=\"$forValue\")]/attribute::email");
			if($emails) $emails = implode(";",$emails); //(string)$emails[0];

			$redirects = $fxml->xpath("attribute[@redirect and (text()=\"$forValue\")]");

			if($redirects){
				$attachedRec = $this->getAttachedRecord();
				if($attachedRec == null) throw new RecordException("", RecordException::NO_ATTACHED_RECORD);

				if($dt == "MultipleAttributs"){
					foreach($redirects as $redirect){
						$emails .= ";".$attachedRec->doGetLinkedEmailInField($p, (string)$redirect["redirect"], $attachedRec->getFieldValue((string)$redirect["redirect"]), true);
					}
				} else {
					$emails .= ";".$attachedRec->doGetLinkedEmailInField($p, (string)$redirects[0]["redirect"], $attachedRec->getFieldValue((string)$redirects[0]["redirect"]), true);
				}
			}
			return $emails;

		} else if($dt == "Booleans" && $forValue){
			$emails = "";
			if($fxml["email"] != null){
				$emails = (string)$fxml["email"];
			}
			if($fxml["redirect"] != null){
				$attachedRec = $this->getAttachedRecord();
				if($attachedRec == null) throw new RecordException("", RecordException::NO_ATTACHED_RECORD);

				if($forValue){
					$emails .= ";".$attachedRec->doGetLinkedEmailInField($p, (string)$fxml["redirect"], $attachedRec->getFieldValue((string)$fxml["redirect"]), true);
					//$attachedRec->getFieldValue((string)$fxml["redirect"]);
				}
			}
			return $emails;
		}
		return false;
	}

	/**
	 * returns a translated label of the field with in () the result
	 * of the redirection if it exist.
	 * if no value is found returns false!
	 * @param $attribute SimpleXml node on wihch the redirect attribut is set
	 * this method can be used either on an attribut of a select, either on any
	 * other field.
	 */
	public function getRedirectedFieldLabel($p, $fieldName, $attribute){
		$transS = ServiceProvider::getTranslationService();
		$redirect = (string) $attribute["redirect"];
		if($redirect == null) return $transS->t($p, (string)$attribute, $attribute);

		$attachedRec = $this->getAttachedRecord();
		if($attachedRec == null) throw new RecordException("", RecordException::NO_ATTACHED_RECORD);

		$returnValue = $transS->t($p, (string)$attribute, $attribute);

		$value = $attachedRec->getFieldValue($redirect);
		/*********************************
		 * if no value then return FALSE!!
		 ********************************/
		if($value == null) return false;

		$attachedRecField = $attachedRec->getFieldList()->getField($redirect);
		$fieldDatatypeName = $attachedRecField->getDataType()->getDatatypeName();
		//if the redirect points on Attributs or MultipleAttributs
		// then we need to translate the value found
		if($fieldDatatypeName == "Attributs" || $fieldDatatypeName == "MultipleAttributs"){
//			$tempValue = array();
//			foreach($attachedRecField->getXml()->attribute as $attr){
//				if((is_array($value) && array_search((string)$attr, $value) !== false) ||
//					$value == (string)$attr){
//					$tempValue[] = $transS->t($p, (string)$attr, $attr);
//				}
//			}
			if(is_array($value)){
				$value = implode("\" or text()=\"", $value);
			}
			$value = $attachedRecField->getXml()->xpath("attribute[(text()=\"".$value."\")]");
			$tempValue = array();
			foreach($value as $val){
				$tempValue[] = $transS->t($p, (string)$val, $val);
			}
			$value = implode(", ", $tempValue);

		} else if($fieldDatatypeName == "Booleans"){
			$value = $transS->t($p, $redirect, $attachedRecField->getXml());
		}

		$returnValue .= " (".$value.")";

		return $returnValue;
	}
}


