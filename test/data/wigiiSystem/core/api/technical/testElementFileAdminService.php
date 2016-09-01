<?php

//We need to mock move_uploaded_file to copy
class OverrideElementFileAdminService extends ElementFileAdminService {
	protected function move_uploaded_file($filename, $destination) {
		return copy($filename, $destination);
	}
}

class testElementFileAdminService extends PHPUnit_Framework_TestCase {

	protected $principal;
	protected $userName;
	protected $nameSpaceName;
	protected $element;
	protected $formField;
	protected $versionedFormField;
	/**
	 * @var FieldListArrayImpl $fieldList
	 */
	protected $fieldList;
	protected $fileFieldName;
	protected $versionedFileFieldName;
	protected $formFieldName;
	protected $originalFileName;
	/**
	 * @var ElementFileAdminService $testClass
	 */
	protected $testClass;

	protected function setUp() {
		$this->userName = "tester";
		$this->nameSpaceName = "myTestNameSpace";
		$this->principal = new MockPrincipal($this->userName, $this->nameSpaceName);

		$this->fieldList = new FieldListArrayImpl();
		$this->fileFieldName = 'myFile';
		$this->formField = $this->createUnVersionedFormField($this->fileFieldName);
		$this->fieldList->addField($this->formField);

		$this->versionedFileFieldName = 'myVersionedFile';
		$this->versionedFormField = $this->createUnVersionedFormField($this->versionedFileFieldName);
		$this->fieldList->addField($this->versionedFormField);

		$wigiiBag = WigiiBagBaseImpl::createInstance();

		$this->element = MockElement::createInstance('test', $this->fieldList, $wigiiBag, null, ''); // used to spy

		$this->formFieldName = 'myUploadFileFormFieldName';
		$this->originalFileName = 'uploaded.xml';

		$this->testClass = new OverrideElementFileAdminService();
	}

	protected function tearDown() {
		$_FILES = array();
		$files = glob(TEMPORARYUPLOADEDFILE_path . '*'); // get all file names
		foreach ($files as $file) { // iterate files
			if (is_file($file))
				unlink($file); // delete file
		}
		clearstatcache();
	}

	public function testElementFileAdminServiceExists() {
		$class = new ElementFileAdminService();
		$this->assertInstanceOf('ElementFileAdminService', $class);
	}

	/**
	 * @param $fieldName
	 * @param $filename
	 * @param $type
	 * @param $expectedMatch
	 * @dataProvider fileNameTestingProvider
	 */
	public function testCreateFileNameReturnsExpectedValue($fieldName, $filename, $type, $expectedMatch) {
		$generatedFileName = $this->testClass->createFileName($this->principal, $fieldName, $filename, $type);
		$this->assertRegExp('/' . $this->nameSpaceName . '_\d{10,16}' . $this->userName . $expectedMatch . '/', $generatedFileName);
	}

	/**
	 * @expectedException ElementFileAdminServiceException
	 * @expectedExceptionCode 4900
	 */
	public function testStagePostedFilethrowsErrorIfFileisMissing() {
		$this->testClass->stagePostedFile($this->principal, $this->element, $this->fileFieldName, $this->formFieldName);
	}


	public function testStagePostedFilePutsTheTemporaryFilePathInTheElement() {

		$filename = 'testfile.txt';

		$this->setupFileDataForTest($filename);
		$this->testClass->stagePostedFile($this->principal, $this->element, $this->fileFieldName, $this->formFieldName);
		$this->assertRegExp('/' . $this->nameSpaceName . '_\d{10,16}' . $this->userName . 'myFileuploa\.xml/', $this->element->getFieldValue($this->fileFieldName, 'path'));
	}

	public function testStagePostedFilePutsTheTemporaryFilePathInTheRightLocation() {

		$filename = 'testfile.txt';
		$this->setupFileDataForTest($filename);
		$this->testClass->stagePostedFile($this->principal, $this->element, $this->fileFieldName, $this->formFieldName);
		$location = $this->element->getFieldValue($this->fileFieldName, 'path');

		$this->assertFileExists(TEMPORARYUPLOADEDFILE_path . $location);
	}

	public function testUploadedFileIsNotExicutable() {
		$filename = 'testfile.txt';
		$this->setupFileDataForTest($filename);
		$this->testClass->stagePostedFile($this->principal, $this->element, $this->fileFieldName, $this->formFieldName);
		$location = TEMPORARYUPLOADEDFILE_path . $this->element->getFieldValue($this->fileFieldName, 'path');

		// be safe with expecting 0666 all can read write but no execution of the file
		$filePermission = substr(sprintf('%o', fileperms($location)), -4);
		$this->assertEquals("0664", $filePermission);
		$this->assertFalse(is_executable($location));
	}

	public function testStagePostedFileReturnsTrueOnSuccess() {
		$filename = 'testfile.txt';
		$this->setupFileDataForTest($filename);
		$this->assertTrue($this->testClass->stagePostedFile($this->principal, $this->element, $this->fileFieldName, $this->formFieldName));
	}

	/**
	 * @expectedException ElementFileAdminServiceException
	 * @expectedExceptionCode 4902
	 */
	public function testFileWithNoExtensionThrowsCorrectError(){
		$this->testClass->setFileData($this->principal,$this->element, $this->fileFieldName,'original' ,'testdummy');
	}

	public function testSetFileDataUpdatesAllDataAsExpected(){

		$this->testClass->setFileData($this->principal,$this->element, $this->fileFieldName,'original.xml' ,'successfullyUploaded.xml');
		
		$size = filesize(FILES_PATH.'successfullyUploaded.xml');
		$dateMatcher = '/^(((\d{4})(-)(0[13578]|10|12)(-)(0[1-9]|[12][0-9]|3[01]))|((\d{4})(-)(0[469]|1‌​1)(-)([0][1-9]|[12][0-9]|30))|((\d{4})(-)(02)(-)(0[1-9]|1[0-9]|2[0-8]))|(([02468]‌​[048]00)(-)(02)(-)(29))|(([13579][26]00)(-)(02)(-)(29))|(([0-9][0-9][0][48])(-)(0‌​2)(-)(29))|(([0-9][0-9][2468][048])(-)(02)(-)(29))|(([0-9][0-9][13579][26])(-)(02‌​)(-)(29)))(\s([0-1][0-9]|2[0-4]):([0-5][0-9]):([0-5][0-9]))$/';

		$this->assertEquals('successfullyUploaded.xml', $this->element->getFieldValue($this->fileFieldName, 'path'));
		$this->assertEquals('original', $this->element->getFieldValue($this->fileFieldName, 'name'));
		$this->assertEquals($size, $this->element->getFieldValue($this->fileFieldName, 'size'));
		$this->assertEquals('.xml', $this->element->getFieldValue($this->fileFieldName, 'type'));
		$this->assertEquals('text/xml', $this->element->getFieldValue($this->fileFieldName, 'mime'));
		$this->assertRegExp($dateMatcher, $this->element->getFieldValue($this->fileFieldName, 'date'));
		$this->assertEquals('tester_id', $this->element->getFieldValue($this->fileFieldName, 'user'));
		$this->assertEquals('tester', $this->element->getFieldValue($this->fileFieldName, 'username'));
	}

	public function testIsVersionedFileField(){
		$versionedField = $this->createVersionedFormField('myFieldIsGood');
		$this->assertTrue($this->testClass->isVersionedFileField($versionedField));

		$unVersionedField = $this->createUnVersionedFormField('noVersionHere');
		$this->assertFalse($this->testClass->isVersionedFileField($unVersionedField));
	}

	public function testGenerateVersionedFilenameMatchesExpectedValues(){
		$this->testClass->setFileData($this->principal,$this->element, $this->versionedFileFieldName,'original.xml' ,'successfullyUploaded.xml');
        $versionName = $this->testClass->createVersionedFileName($this->element, $this->versionedFileFieldName);
		$this->assertRegExp('/\d{4}-\d{2}-\d{2}_\d{1,2}-\d{2}-\d{2}_' . $this->userName . '_original\.xml/', $versionName);
	}
	
	/**
	 * data provider to run through name generation examples for creating file names
	 * @return array
	 */
	public function fileNameTestingProvider() {
		return array(
			array('A', 'B', 'C', 'AB.C'),
			array('myField', 'oh/is\this>?>S}{}P}APFO!"$(%_"%(_^**^&_&*$ (^+_("!_*%"£%^&"$*^&$_&*_^*!£$^&^&!£_&*_!£%&(+"¬OK', '.txt', 'myFieldohis\.txt'),
			array('emoji', '😂😇', 'txt', 'emoji\.txt'),
			array('arabic', 'لمفي', 'txt', 'arabic\.txt'),
			array('accented', 'ÂÃÄÀÁÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýþÿ', 'bob', 'accented\.bob'),
			array('myFieldSuperLongFieldNamesAsItShouldBe', 'myawesomelongerthanIthoughtitshouldbebutIshouldtestitanywayfilename', 'fuzz', 'myFieldSuperLongFieldNamesAsItShouldBemyawe\.fuzz')
		);
	}

	protected function setupFileDataForTest($filename) {

		$_FILES[$this->formFieldName] = array('name' => $this->originalFileName,
			'error' => UPLOAD_ERR_OK,
			'size' => filesize(FIXTURES . $filename),
			'tmp_name' => FIXTURES . $filename
		);

	}

	protected function createVersionedFormField($fieldName, $keepNumber = 2){
		$formField = new Field();
		$formField->setFieldName($fieldName);
		$formField->setDataType(new Files());
		$formField->setXml(simplexml_load_string('<'.$fieldName.' type="Files" keepHistory="'.$keepNumber.'"></'.$fieldName.'>'));
		return $formField;
	}

	protected function createUnVersionedFormField($fieldName){
		$formField = new Field();
		$formField->setFieldName($fieldName);
		$formField->setDataType(new Files());
		$formField->setXml(simplexml_load_string('<'.$fieldName.' type="Files"></'.$fieldName.'>'));
		return $formField;
	}

}