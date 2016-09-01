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
 * Class ElementFileAdminService
 * Created by DJC 26.06.2016
 *
 * Service of helper functions to handle Files related to Files field types in an Element
 */
class ElementFileAdminService {

	/**
	 * Creates the file name which will be stored into wigii
	 * (a copy of the algorithm found into method FormExecutor->manageUploadedFileForm)
	 * @param Principal $principal the authenticated user
	 * @param String $fieldName the name of the field where the report will be stored
	 * @param String $fileName the logical file name as set by the user
	 * @param String $type is the file extension with the dot
	 * (optional, if not set, then filename has no extension. It can be used to generate temp folder names)
	 * @return String The safely generated name
	 */
	public function createFileName(Principal $principal, $fieldName, $fileName, $type = '') {
		if ($type != '' && strpos($type, '.') !== 0) $type = '.' . $type;

		$returnValue = $principal->getWigiiNamespace()->getWigiiNamespaceName() .
			"_" . udate('Uu') . /* for files generated in batch mode, needs milliseconds precision */
			ipToStr($_SERVER["REMOTE_ADDR"]) .
			$principal->getUsername() .
			$fieldName .
			substr($fileName, 0, 5) .
			$type;

		$returnValue = preg_replace('/[^a-zA-Z0-9\.\-\_]/', "", $returnValue);
		return $returnValue;
	}

	/**
	 * Move a posted file from PHP $_FILES to the temporary uploaded location and update the element with
	 * the metadata so that it can be persisted when desired.
	 * @param Principal $principal the authenticated user
	 * @param Element $element
	 * @param String $fieldName
	 * @param String $formFieldName
	 * @return bool true if succeeds, else will throw an exception
	 * @throws ElementFileAdminServiceException
	 */
	public function stagePostedFile(Principal $principal, Element $element, $fieldName, $formFieldName = null) {

		//see if the file has been posted toi the application
		if (!isset($_FILES[$formFieldName])) throw new ElementFileAdminServiceException('The file uploaded from ' .
			$formFieldName . ' is not found ' , ElementFileAdminServiceException::FILE_NOT_FOUND);
		$file = $_FILES[$formFieldName];

		$fileNameInfo = pathinfo($file['name']);

		//create temporary file in the element
		$newName = $this->createFileName($principal, $fieldName, $fileNameInfo['filename'], $fileNameInfo['extension']);
		if (empty($newName)) throw new ElementFileAdminServiceException(
			'Unknown error generating the file name to store on disk',
			ElementFileAdminServiceException::UNKNOWN_ERROR);

		//check if we version the file
		

		$tempFileLocation = TEMPORARYUPLOADEDFILE_path . $newName;

		if (!$this->move_uploaded_file($file['tmp_name'], $tempFileLocation))
			throw new ElementFileAdminServiceException('Could not move the uploaded file.'. print_r($_FILES, true),
				                                        ElementFileAdminServiceException::UNKNOWN_ERROR);
		$this->make_file_safe($tempFileLocation);
		$this->setFileData($principal, $element, $fieldName, $fileNameInfo['filename'], $newName, true);

		return true;
	}

	/**
	 * Mutates Element with the field data
	 * @param Principal $principal the authenticated user
	 * @param Element $element the element to mutate
	 * @param string $fieldName The name of the field to update in the element
	 * @param string $originalName Original File name for display etc
	 * @param string $filePath The location path for the new file
	 * @param boolean $staging weather of not the file is in the staging area so we can find it
	 * @throws ElementFileAdminServiceException
	 * @throws RecordException
	 */
	public function setFileData(Principal $principal, Element $element, $fieldName, $originalName,
									   $filePath, $staging = false) {

		$fullPath = ($staging) ? TEMPORARYUPLOADEDFILE_path . $filePath : FILES_PATH . $filePath;
		if (!file_exists($fullPath)) throw new ElementFileAdminServiceException('No file found at ' . $fullPath,
			ElementFileAdminServiceException::FILE_NOT_FOUND);
		$size = filesize($fullPath);
		$pathParts = pathinfo($filePath);
		$type = $pathParts['extension'];
		if (empty($type)) throw new ElementFileAdminServiceException('Cannot guess the file type',
			ElementFileAdminServiceException::UNKNOWN_FILE_TYPE);
		$mime = typeMime('.' . $type);

		$originalName = pathinfo($originalName);
		$originalName = $originalName['filename'];

		$element->setFieldValue($filePath, $fieldName, 'path');
		$element->setFieldValue($originalName, $fieldName, 'name');
		$element->setFieldValue($size, $fieldName, 'size');
		$element->setFieldValue('.' . $type, $fieldName, 'type');
		$element->setFieldValue($mime, $fieldName, 'mime');
		$element->setFieldValue(date("Y-m-d H:i:s"), $fieldName, 'date');
		$element->setFieldValue($principal->getRealUserId(), $fieldName, 'user');
		$element->setFieldValue($principal->getRealUsername(), $fieldName, 'username');

	}

	/**
	 * Tests to see if we should version the files given a field
	 * @param Field $field to test
	 * @return bool
	 */
	public function isVersionedFileField(Field $field){
		$fieldXml = $field->getXml();
		return $fieldXml["keepHistory"]>0;
    }

	/**
	 * @param Record $record
	 * @param $fieldName
	 * @return string
	 */
	public function createVersionedFileName(Record $record, $fieldName){
		$dateString =  str_replace(array(" ", ":"), array("_", "-"),$record->getFieldValue($fieldName, "date"));
		$combinedName = $dateString.'_'.$record->getFieldValue($fieldName, "username").
			"_".$record->getFieldValue($fieldName, "name").$record->getFieldValue($fieldName, "type");
		return $combinedName;
	}


	/**
	 * Makes sure the uploaded file has the correct permissions so that it cannot be run on the server.
	 * @param string $filename
	 * @throws ElementFileAdminServiceException
	 */
	protected function make_file_safe($filename) {
		if (!file_exists($filename)) throw new ElementFileAdminServiceException(
			'Cannot change permissions on a non existent file: ' . $filename,
			ElementFileAdminServiceException::FILE_NOT_FOUND);
		if (!chmod($filename, 0664)) {
			unlink($filename); // We don't want the file if we get to this point as it is dangerous
			throw new ElementFileAdminServiceException (
				'Unable to change file permissions on ' . $filename,
				ElementFileAdminServiceException::FILE_PERMISSIONS_ERROR);
		}
	}

	/**
	 * Wrap up the native function so that we can unit test the file uploads
	 * @see move_uploaded_file()
	 * @param string $filename
	 * @param string $destination
	 * @return bool
	 */
	protected function move_uploaded_file($filename, $destination) {
		return move_uploaded_file($filename, $destination);
	}
}