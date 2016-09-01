<?php

/** Create a spy version to make sure correct functions are being called */

class MockElement extends Element implements SysInformation{

    public static function createInstance($module, $fieldList = null, $wigiiBag = null, $array=null, $colPrefix='')
    {
        $e = new MockElement();
        $e->setModule($module);
        if(isset($fieldList)) $e->setFieldList($fieldList);
        if(isset($wigiiBag)) $e->setWigiiBag($wigiiBag);
        if(is_array($array))
        {
            $e->fillFromArray($array, $colPrefix);
        }
        return $e;
    }
}