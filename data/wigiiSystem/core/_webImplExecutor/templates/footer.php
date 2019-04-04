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

/*
 * Created on 23 juil. 09
 * by LWR
 */
//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."start footer.php"] = microtime(true);
$this->executionSink()->publishStartOperation("TEMPLATE footer.php");

?>
</div>
<?

// Wigii light client
if($exec->getCrtAction()!="c") {
?>
<script type="text/javascript" >
if(reqOnHash != null){
	self.location = "<?=(HTTPS_ON ? "https" : "http" );?>://<?=$_SERVER['HTTP_HOST']?><?=$_SERVER['REQUEST_URI']?>"+crtHash;
	update(reqOnHash);
}
</script>
<? } ?>
</body>
</html><?

//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."end footer.php"] = microtime(true);
$this->executionSink()->publishEndOperation("TEMPLATE footer.php");