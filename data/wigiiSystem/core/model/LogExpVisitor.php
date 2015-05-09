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
 * Logical Expression visitor
 * Created by CWE on 1 sept. 09
 */
interface LogExpVisitor
{
	public function actOnAndExp($andLogExp);
	public function actOnOrExp($orLogExp);
	public function actOnNotExp($notLogExp);
	public function actOnSmaller($obj, $val);
	public function actOnSmallerEq($obj, $val);
	public function actOnGreater($obj, $val);
	public function actOnGreaterEq($obj, $val);
	public function actOnEqual($obj, $val);
	public function actOnNotEqual($obj, $val);
	public function actOnIn($obj, $vals);
	public function actOnNotIn($obj, $vals);
	public function actOnLike($obj, $val);
	public function actOnMatchAgainst($obj, $val);
	public function actOnNotLike($obj, $val);
	public function actOnInGroup($inGroupLogExp);
	public function actOnNotInGroup($notInGroupLogExp);
}