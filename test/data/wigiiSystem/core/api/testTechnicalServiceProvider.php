<?php
class testTechnicalServiceProvider extends PHPUnit_Framework_TestCase{

    
    public function testGetElementFileAdminServiceReturnsInstanceOfElementFileAdminService(){
        $this->assertInstanceOf( 'ElementFileAdminService',TechnicalServiceProvider::getElementFileAdminService());
    }


}


