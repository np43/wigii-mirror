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
 * Created on 20 janv. 10
 * by LWR
 */

abstract class MatrixRenderer {

	private $id;
	protected function setId($id){ $this->id = $id; }
	public function getId(){ return $this->id; }
	/**
	 * the matrix will contains as rows GroupP
	 * as columns UserP
	 */
	private $matrix;
	public function setMatrix($matrix){ $this->matrix = $matrix; }
	public function getMatrix(){
		if(!isset($this->matrix)){
			$this->matrix = $this->createMatrixInstance();
		}
		return $this->matrix;
	}
	protected function createMatrixInstance(){
		return MatrixArrayImpl::createInstance();
	}

	public function createInstance($id){
		$r = new self();
		$r->setId($id);
		return $r;
	}

	abstract public function prepareRenderingColHeader($p, $exec, $wigiiExecutor);
	abstract public function endRenderingColHeader($p, $exec, $wigiiExecutor);
	//this method must define a dom node with an id as colId (used for the higlight)
	abstract public function renderColHeader($p, $exec, $wigiiExecutor, $colId);
	abstract public function prepareRenderingRowHeader($p, $exec, $wigiiExecutor);
	abstract public function endRenderingRowHeader($p, $exec, $wigiiExecutor);
	//this method must define a dom node with an id as rowId (used for the higlight)
	abstract public function renderRowHeader($p, $exec, $wigiiExecutor, $rowId);
	abstract public function prepareRenderingValues($p, $exec, $wigiiExecutor);
	abstract public function endRenderingValues($p, $exec, $wigiiExecutor);
	abstract public function renderValue($p, $exec, $wigiiExecutor, $rowId, $colId);

	public function render($p, $exec, $wigiiExecutor){
		$m = $this->getMatrix();
		if($m->countRows() == 0) throw new ListException("try to render an empty matrix", ListException::UNSUPPORTED_OPERATION);

		?><div id="<?=$this->getId();?>" class="Matrix"><?
		?><div class="MatrixCorner BSB"><? ?></div><?
		?><div class="MatrixColHeaders BSB"><?
			$this->prepareRenderingColHeader($p, $exec, $wigiiExecutor);
			?><table class="" ><tr><?
			foreach($m->getColIdsIterator() as $colId){
				?><td><?
					?><div class="SBB"><?
						$this->renderColHeader($p, $exec, $wigiiExecutor, $colId);
					?></div><?
				?></td><?
			}
			?></tr></table><?
			$this->endRenderingColHeader($p, $exec, $wigiiExecutor);
		?></div><div class="clear" /><?

		?><div class="MatrixRowHeaders BSB"><?
			$this->prepareRenderingRowHeader($p, $exec, $wigiiExecutor);
			foreach($m->getRowIdsIterator() as $rowId){
				$this->renderRowHeader($p, $exec, $wigiiExecutor, $rowId);
			}
			$this->endRenderingRowHeader($p, $exec, $wigiiExecutor);

		?></div><?

		?><div class="MatrixItems BSB"><?
			$this->prepareRenderingValues($p, $exec, $wigiiExecutor);
			?><table class="" ><?
				foreach($m->getRowIdsIterator() as $rowId){
					?><tr class="row<?=$rowId;?>" ><?
					foreach($m->getColIdsIterator() as $colId){
						?><td class="col<?=$colId;?>" ><?
							?><div class="SBB" ><?
								$this->renderValue($p, $exec, $wigiiExecutor, $rowId, $colId);
							?></div><?
						?></td><?
					}
					?></tr><?
				}
			?></table><?
			$this->endRenderingValues($p, $exec, $wigiiExecutor);
		?></div><?

		?><div class="ScrollLeftZone"></div><?
		?><div class="ScrollRightZone"></div><?
		?><div class="ScrollUpZone"></div><?
		?><div class="ScrollDownZone"></div><?
		?><div class="clear"></div><?

	?></div><?

	$exec->addJsCode("
matrixResize('".$this->getId()."');

$('#".$this->getId()." .ScrollRightZone')
.mouseenter(function(){ scrollRight('".$this->getId()."'); })
.mouseleave(function(){ stopScroll('".$this->getId()."'); })
.dblclick(function(){ goRightScroll('".$this->getId()."'); });
$('#".$this->getId()." .ScrollLeftZone')
.mouseenter(function(){ scrollLeft('".$this->getId()."'); })
.mouseleave(function(){ stopScroll('".$this->getId()."'); })
.dblclick(function(){ goLeftScroll('".$this->getId()."'); });
$('#".$this->getId()." .ScrollUpZone')
.mouseenter(function(){ scrollUp('".$this->getId()."'); })
.mouseleave(function(){ stopScroll('".$this->getId()."'); })
.dblclick(function(){ goUpScroll('".$this->getId()."'); });
$('#".$this->getId()." .ScrollDownZone')
.mouseenter(function(){ scrollDown('".$this->getId()."'); })
.mouseleave(function(){ stopScroll('".$this->getId()."'); })
.dblclick(function(){ goDownScroll('".$this->getId()."'); });

$('#".$this->getId()." .MatrixItems td>div').mouseenter(highlightFromMatrixItem);
$('#".$this->getId()."').mouseleave(unHighlight);

$(window).resize(function(){
	matrixResize('".$this->getId()."');
});
");
	}

}


