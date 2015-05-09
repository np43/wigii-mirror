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

/*
 * Created on 24 juil. 09
 * by LWR
 */

?>
<div id="clickMe" style="cursor:pointer; border:#000 solid 1px; background-color:#ccc; color:#000; font-weight:bold; width:200px; text-align:center; padding:5px;">Generate an execution log!</div>
<script type="text/javascript" >
$('#clickMe').click(function(){
	update("NoAnswer/E-library/Filemanager/createTestLog");
});
</script>
<?
?>
<div id="clickMe1" style="cursor:pointer; border:#000 solid 1px; background-color:#ccc; color:#000; font-weight:bold; width:200px; text-align:center; padding:5px;">Generate an exception!</div>
<script type="text/javascript" >
$('#clickMe1').click(function(){
	update("NoAnswer/E-library/Filemanager/getExceptionTest");
});
</script>
<?
?>
<div id="clickMe2" style="cursor:pointer; border:#000 solid 1px; background-color:#ccc; color:#000; font-weight:bold; width:200px; text-align:center; padding:5px;">Change my text</div>
<script type="text/javascript" >
$('#clickMe2').click(function(){
	update("clickMe2/E-library/Filemanager/getTime");
});
</script>
<?




