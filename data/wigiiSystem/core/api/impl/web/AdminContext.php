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
 * Created on 15 janv. 10
 * by LWR
 */

interface AdminContext {

	const UserFilterText = "__userFilterText";
	const UserFilterType = "__userFilterType";
	const User2FilterText = "__user2FilterText";
	const User2FilterType = "__user2FilterType";
	const GroupFilterText = "__groupFilterText";
	const HorizontalPagingText = "__horizontalPagingText";
	const VerticalPagingText = "__verticalPagingText";

	public function setWorkingModule($module);
	public function getWorkingModule();
	public function isWorkingModule($module);

	public function setSubScreen($subScreenName);
	public function getSubScreen();

	public function setUserListFilter($listFilter);
	public function setUser2ListFilter($listFilter);
	public function setGroupListFilter($listFilter);

	public function getGroupFilterPost($name=null);
	public function getUserFilterPost($name=null);
	public function getUser2FilterPost($name=null);

	public function setDesiredHPage($nb);
	public function getDesiredHPage();

	public function setDesiredVPage($nb);
	public function getDesiredVPage();

	public function setRootPrincipal($rootPrincipal);

}


