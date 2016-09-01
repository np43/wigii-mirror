<?php

class testWigiiBPL extends PHPUnit_Framework_TestCase {

	protected $wibiiBPL;
	protected $principal;
	protected $element;
	protected $formField;

	protected function setUp() {
		$this->wibiiBPL = new WigiiBPL();
		$this->principal = new MockPrincipal('testUser', 'testNameSpace');

		$fieldList = new FieldListArrayImpl();

		$this->formField = new Field();

		$this->fieldName = 'myFile';
		$this->formField->setFieldName($this->fieldName);
		$this->formField->setDataType(new Files());

		$fieldList->addField($this->formField);

		$wigiiBag = WigiiBagBaseImpl::createInstance();

		$this->element = MockElement::createInstance('test', $fieldList, $wigiiBag, null, '');
	}

	/**
	 * Make sure we are bootstrapped ok
	 */
	public function testClassExists() {
		$this->assertInstanceOf('WigiiBPL', $this->wibiiBPL);
	}

	public function testElementPersistFileFieldFromPostExists() {
		$this->assertTrue(method_exists($this->wibiiBPL, 'elementPersistFileFieldFromPost'));
	}

	/**
	 * @expectedException WigiiBPLException
	 * @expectedExceptionCode 4700
	 */
	public function testNoParametersOnElementPersistFileFieldFromPostThrowsError() {
		$this->wibiiBPL->elementPersistFileFieldFromPost($this->principal, null, wigiiBPLParam());
	}

	/**
	 * @expectedException ElementFileAdminServiceException
	 * @expectedExceptionCode 4900
	 */
	public function testNoPostFileOnElementPersistFileFieldFromPostThrowsError() {
		$params = wigiiBPLParam('element', $this->element,
			'fieldName', 'myFile',
			'formFieldName', 'myUploadedFile');
		$this->wibiiBPL->elementPersistFileFieldFromPost($this->principal, $this, $params);
	}

}