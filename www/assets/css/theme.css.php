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

//needs specific headers to tell this is a css file
header("Content-type: text/css");

$wigiiLogoColor = "5E71FE";
$wigiiLogoColorDark = "4A58C7"; //3E4552

$white = "fff";
$black = "000";

$footerBar = "5c6c87"; //"5c6c87";
$dialogBorder = "5c6c87"; //"5c6c87";
$footerBarR = $white;
$goButton = "93a4c1";
$goButtonR = $white;
$dialogBoxTitle = "E0E2E5"; //"bec8da";
$dialogBoxTitleR = $black;
$searchBar = "EDEFF2"; //"e0e6f0";
$searchBarR = $black;
$searchBarBorder = "E0E2E5"; //"d0d2da";
$searchBarInputBorder = "93a4c1"; //"d0d2da";
$toolBar = "f1f3f7"; //"ecf3fe";
$toolBarR = "555";
$odd = "f1f3f7";
$link = "0050e8";
$red = "fe0000";
$green = "95C33B";

$disabled = $odd; //"B7C1D1"; //"6F757F";
$disabledR = $searchBarBorder;

$listHeader = $searchBar;
$listHeaderR = $searchBarR;

$fieldBorder = $toolBar;

$selected = "aac9ff";
$selectedR = $black;
$multipleSelected = "FFE694"; //"ff9f94";
$multipleSelectedR = $black;
$lightYellow = "FFFF94";
$lightYellowR = $black;
$hover = $toolBar;

$tag = $selected;
$tagR = $seletedR;

$calendarEvent = $footerBar;
$calendarEventHighlight = $selected;
$calendarEventR = $white;
$calendarEventHighlightR = $black;


?>
/**********************************
* THEME
**********************************/

.publicFormBorder {
	background: none repeat scroll 0 0 #EEE;
	border: 2px solid #BBB;
	color: #555555;
}
button.publicFormBorder:hover {
	background-color:#EEE;
	color: #000000;
}
.F, .F a {
  background-color:#<?=$footerBar;?>;
  color:#<?=$footerBarR;?>;
}
.G {
  background-color:#<?=$goButton;?>;
  color:#<?=$goButtonR;?>;
}
.D {
  background-color:#<?=$dialogBoxTitle;?>;
  color:#<?=$dialogBoxTitleR;?>;
}
.BD {
  border-color:#<?=$dialogBoxTitle;?>;
  border-style:solid;
  border-width:1px;
}
.S {
  background-color:#<?=$selected;?>;
}
#moduleView .blog div.dataBlog div.el.S {
    background-color:#<?=$odd;?>;
    box-shadow: 2px 2px 5px #888888;
}
.M {
  background-color:#<?=$multipleSelected;?>;
}
.L, a.L {
  color:#<?=$link;?>;
}
.R {
  color:#<?=$red;?>;
}
.Gft {
  color:#<?=$green;?>;
}
.Green {
  background-color:#<?=$green;?>;
  color:#fff;
}
.Red {
  background-color:#<?=$red;?>;
  color:#fff;
}
.White {
  background-color:#<?=$white;?>;
}
.SB {
  background-color:#<?=$searchBar;?>;
  color:#<?=$searchBarR;?>;
}
.BSB {
  border-color:#<?=$searchBar;?>;
  border-style:solid;
  border-width:1px;
}
.SBB {
  border-color:#<?=$searchBarBorder;?>;
  border-style:solid;
  border-width:1px;
}
.SBIB {
  border-color:#<?=$searchBarInputBorder; //$footerBar;
  ?>;
  border-style:solid;
  border-width:1px;
}
.DB {
  border-color:#<?=$dialogBorder; //$footerBar;
  ?>;
  border-style:solid;
  border-width:1px;
}
.lH {
  background-color:#<?=$listHeader;?>;
  color:#<?=$listHeaderR;?>;
}
.T {
  background-color:#<?=$toolBar;?>;
  color:#<?=$toolBarR;?>;
}
.T a{
    background-color:#<?=$toolBar;?>;
    color:#<?=$toolBarR;?>;
}
.Tcolor {
  background-color:#<?=$toolBar;?>;
  color:#<?=$toolBarR;?>;
}

div.field.updatedRecently {
	/* background-color:#FFFFE7; */
	border-right:5px solid #FFFF00; */
}
div.field.button .value div a:first-child {
  background-color:#<?=$goButton;?>;
  color:#<?=$goButtonR;?>;
  font-weight: bold;
  text-align: center;
  padding: 4px 10px;
  font-size: 18px;
  text-decoration: none;
}
div.field.button .value div a:last-child {
  padding-top: 8px;
  float:left;
}

div.field.HR, div.field.HR2 {
	min-height:0px;
	height:10px;
}
div.field.HR div.value {
	border-top:1px solid #<?=$searchBarInputBorder;?>;
}
div.field.HR2 div.value {
	border-top:1px solid #<?=$searchBar;?>;
}
div.field.narrow div.value{
	min-height:0px;
	margin:0px;
	padding:2px 0px;
}
/* make the ability to display multiple fields on the same line if their width defined in a blogView is smaller than the item size. */
.noClear, div.field.noClear, #moduleView .blog div.dataBlog div.el div.field.noClear{
	clear: none;
}
div.field.jointV div.value{
	min-height:0px;
	margin:0px;
	padding:0px 0px;
}
div.field.jointV div.label{ 
	padding-top:0px; padding-bottom:0px;
	margin:0px;
}
div.field.jointV1 div.value{
	min-height:0px;
	margin:0px;
	padding:1px 0px;
}
div.field.jointV1 div.label{ 
	padding-top:1px; padding-bottom:1px;
	margin:0px;
}
div.field.jointV2 div.value{
	min-height:0px;
	margin:0px;
	padding:2px 0px;
}
div.field.jointV2 div.label{ 
	padding-top:2px; padding-bottom:2px;
	margin:0px;
}
div.field.jointV3 div.value{
	min-height:0px;
	margin:0px;
	padding:3px 0px;
}
div.field.jointV3 div.label{ 
	padding-top:3px; padding-bottom:3px;
	margin:0px;
}
div.field.jointV4 div.value{
	min-height:0px;
	margin:0px;
	padding:4px 0px;
}
div.field.jointV4 div.label{ 
	padding-top:4px; padding-bottom:4px;
	margin:0px;
}
div.field.jointH {
	margin:0px;
	padding:0px 0px;
}
/* displayOnRightSide replacement */
div.fieldGroupRight{
	margin-left:0px;
	margin-right:0px;
}
div.fieldGroupRight>div.SBIB {
	background-color:#<?=$toolBar;?>;
	border:none;
	padding:4px 0px 4px 5px;
	box-sizing:content-box;
}
div.fieldGroupRight div.SBIB * {
	box-sizing:border-box;
}
div.fieldGroupRight>div.SBIB>div.field{
	border-bottom:5px solid #fff;
}
/*div.fieldGroupRight>div.SBIB>div.field>div.value>div {
	max-height:300px;
	overflow-y:auto;
}*/
div.fieldGroupRight>div.SBIB>div.field:last-child{
	border-bottom:none;
}

.O {
  background-color:#<?=$odd;?>;
}
.fB {
  border:1px solid #<?=$fieldBorder;?>;
}

.tag { background-color:#<?=$tag;?>; color:#<?=$tagR;?>; }


.fieldBorder						{
  border:1px dotted #<?=$fieldBorder;?>;
  border-bottom-style:none;
  border-left-style:none;
  border-right-style:none;
}

/* since 4.105 fieldBorders are removed. it is clearer */
.fieldBorder {
	border:none;
}

.fieldError {
  padding-bottom:5px;
  color:#<?=$red;?>;
  font-style:italic;
}

div.elementDetail fieldset.isPlayingRole {
  border-width:2px;
  padding:5px;
  border-color:#<?=$selected;?>;
  border-style:solid;
  margin-top:6px;
}
div.elementDetail fieldset.isPlayingRole legend{
  background-color:#<?=$selected;?>;
  color:#<?=$selectedR;?>;
  font-weight:bold;
  margin-top:-6px;
  padding:2px;
  padding-right:5px;
  padding-left:5px;
}

#cronJobs, #wigiiVersionLabel, #pUsername {
  color:#<?=$goButton;?>;
}
#wigiiVersionLabel a {
  color:#<?=$goButton;?>;
  text-decoration:none;
}
/*****************************
* OVER Section
*****************************/
.H:hover, a.H:hover,
.over
{
  text-decoration:underline;
  cursor:pointer;
}
a.H {
  text-decoration:none;
}

.disabled:hover,
.disabled>.H:hover,
{
  text-decoration:none;
  cursor:default;
}

#ContainerHome #homePageWigiiNamespaceMenu ul.sub .H:hover {
  background-color:		#<?=$odd;?>;
}

/*
#userMenu .home:hover, #userMenu .back:hover, #userMenu .closeAdmin:hover {
  background:none;
}

#userMenu .home a,
#userMenu .back a,
#userMenu .closeAdmin a {
  background:		#<?=$odd;?>;
  border-color:	#<?=$searchBarBorder;?>;
}
#userMenu .home:hover a,
#userMenu .back:hover a,
#userMenu .closeAdmin a {
  background:		#<?=$searchBar;?>;
  border-color:	#<?=$searchBarBorder;?>;
}
*/

#userMenu .closeAdmin a {
  background:		#<?=$odd;?>;
  border-color:	#<?=$searchBarBorder;?>;
}
#userMenu .closeAdmin a {
  background:		#<?=$searchBar;?>;
  border-color:	#<?=$searchBarBorder;?>;
}

/* Home underline on hover bug fix for IE7, IE8, Google Chrome. Still problem in google Chrome */
#ContainerHome .H:hover {
  text-decoration:none;
}
#ContainerHome #homePageModuleMenu li:hover a {
  text-decoration:underline;
}
#ContainerHome #homePageWigiiNamespaceMenu>li:hover>a {
  text-decoration:underline;
}
#ContainerHome #homePageWigiiNamespaceMenu>li:hover>ul>li.sub>a {
  text-decoration:none;
}
#ContainerHome #homePageWigiiNamespaceMenu>li:hover>ul>li.sub:hover a {
  text-decoration:underline;
}

.groupPanel li>div>a.H:hover{
  text-decoration:underline;
}
.groupPanel li>div.over, .groupPanel li>div:hover, div.cm>div:hover {
  background-color:#<?=$hover;?>;
  text-decoration:none;
}
.groupPanel li>div.highlight {
  background-color:#<?=$lightYellow;?>;
  text-decoration:none;
}
.groupPanel li>div.selected {
  background-color:#<?=$selected;?>;
  text-decoration:none;
}
.groupPanel li>div span.menu:hover{
  border:1px solid #<?=$footerBar;?>;
  margin-left:9px;
  margin-top:-4px;
  margin-right:2px;
}
#moduleView .list .dataList div.menu:hover{
  border:1px solid #<?=$footerBar;?>;
  padding:5px 0 5px 2px;
}

#workZone {
  float:left;
  width:100%;
}

/*
#moduleView .list div.dataList table tr:nth-child(odd) {background: #<?=$odd;?>}
#moduleView .list div.dataList table tr:nth-child(even) {background: #FFF}
*/
#moduleView .list div.dataList table tr:hover {background: #<?=$odd;?>}
#moduleView .list div.dataList table tr.navigation:hover {background: transparent;}

#moduleView .list div.dataList table.M tr.M,
#moduleView .blog div.dataBlog div.el.M { background-color:#<?=$multipleSelected;?>; }
#moduleView .list div.dataList table.M {
  background-color:transparent;
}
#moduleView .list div.dataList table tr.S {
  background-color:#<?=$selected;?>;
  text-decoration:none;
}

#moduleView .list div.dataList table tr.SBIB td div{
	border-top-color:#<?=$searchBarInputBorder; //$footerBar
	?>;
	border-top-style:solid;
	border-top-width:1px;
	border-bottom-color:#<?=$searchBarInputBorder; //$footerBar
	?>;
	border-bottom-style:solid;
	border-bottom-width:1px;
	padding-top:5px;
	padding-bottom:5px;
}

/*****************************
* SELECT Section
*****************************/
.Matrix div.highlight
{
  background-color:#<?=$selected;?>;
  color:#<?=$selectedR;?>;
}
.Matrix div.lightRed
{
  background-color:#<?=$multipleSelected;?>;
  color:#<?=$multipleSelectedR;?>;
}
.Matrix div.lightYellow
{
  background-color:#<?=$lightYellow;?>;
  color:#<?=$lightYellowR;?>;
}

/*****************************
* Disable Section
* here we add each entry which needs a disable
* the color is as the gray
*****************************/
.disabled,
input:disabled, textarea:disabled, option:disabled, optgroup:disabled, select:disabled:disabled
{
  color:#000; /* $disabled; */
  background-color:#<?=$disabled;?>; /* $disabled; */
  cursor:default;
}
.disabledR
{
  color:#<?=$disabledR;?>;
}
.disabledBg
{
  background-color:#<?=$disabled;?>; /* $disabled; */
  color:#<?=$disabledR;?>;
}
.groupPanel li.disabled {
  background-color:#fff;
}
.groupPanel li>div.disabled, .groupPanel li>div.disabled>a.H {
  color:#<?=$goButton;?>;
}
.grayFont {
  color:#<?=$goButton;?>;
}
.blackFont {
  color:#000;
}

/******************************
* Superfish menu
******************************/
.sf-menu a { /* visited pseudo selector so IE6 applies text colour*/
  border: solid 1px #<?=$searchBarBorder;?>;
}
.sf-menu a, .sf-menu a:visited  {
  color:inherit;
}
.sf-menu li {
	background-color:inherit;
}
.sf-menu ul {
  border: solid 1px #<?=$searchBarBorder;?>;
}
.sf-menu li li a {
  color:inherit;
  border-bottom:solid 1px #<?=$searchBarBorder;?>;
}
.sf-menu li.selected,
.sf-menu li.selected li.selected,
.sf-menu li.selected li.selected li.selected {
  background:		#<?=$selected;?>;
}

.sf-menu a:active, .sf-menu a:focus,
.sf-menu a:hover {
  text-decoration:underline;
  background:		#<?=$selected;?>;
  outline:		0;
  color:#000;
}
.sf-menu li.menuHover a, .sf-menu li:hover a {
  color:#000;
}
.sf-menu li:hover {
  background:		#<?=$selected;?>;
}

/* adds for nav bar */
.sf-menu>li {
  border-bottom:1px solid #<?=$searchBarBorder;?>;
}
.sf-navbar li.selected,
.sf-navbar li.selected li.selected,
.sf-navbar li.selected li.selected li.selected {
  background:		#<?=$odd;?>;
}
.sf-navbar a:active, .sf-navbar a:focus,
.sf-navbar li.sfHover,.sf-navbar li:hover,
.sf-navbar a:hover,
#userMenu .home>a:hover, #userMenu .home>a:focus, #userMenu .home>a:active,
#userMenu .back>a:hover, #userMenu .back>a:focus, #userMenu .back>a:active
 {
  background:		#<?=$odd;?>;
}
.sf-navbar li.selected a:active, .sf-navbar li.selected a:focus,
.sf-navbar li.selected li.sfHover,.sf-navbar li.selected li:hover,
.sf-navbar li.selected a:hover {
  background:		transparent;
}
.sf-navbar {
  border-bottom:1px solid #<?=$searchBarBorder;?>;
}
.sf-navbar li ul {
  background:#<?=$white;?>;
}
.sf-navbar li.selected ul {
  background:#<?=$goButton;?>;
}
.sf-navbar li.selected.D {
  background:#<?=$dialogBoxTitle;?>;
}
.sf-navbar li.selected {
  background:#<?=$searchBar;?>;
  border:1px solid #<?=$searchBarBorder;?>;
  border-bottom-color:#<?=$searchBar;?>;
}
.sf-navbar li.selected.with-ul {
  background:#<?=$goButton;?>;
  border-bottom-color:#<?=$goButton;?>;
}
.sf-navbar li.selected li.selected {
  background:#<?=$searchBar;?>;
  border:1px solid #<?=$white;?>;
  border-bottom-color:#<?=$searchBar;?>;
}
.sf-navbar  li:hover ul {
  border:1px solid #<?=$searchBarBorder;?>;
}
.sf-navbar  li.selected:hover ul {
  border:none;
}
.sf-navbar a, .sf-navbar li a, .sf-navbar li li a {
  color:inherit;
  border:none;
}
.sf-navbar>li.with-ul:hover>ul>li>a {
  border-right:1px solid #<?=$searchBarBorder;?>;
}
.sf-navbar>li.selected:hover>ul>li>a {
  border-right:none;
}

/******************************
* FullCalendar plugin
******************************/
.fc-content .highlight, .fc-content .highlight a {
  background-color:#<?=$calendarEventHighlight;?>;
  border-color:#<?=$calendarEventHighlight;?>;
  color:#<?=$calendarEventHighlightR;?>;
}
.fc-content .highlight{
  background-color:#<?=$calendarEventHighlight;?>;
  border-color:#<?=$calendarEventHighlight;?>;
  color:#<?=$calendarEventHighlightR;?>;
  font-weight:bold;
  border:2px solid black;
  padding:3px;
  /*font-size:14px;*/
}
.fc-content .highlight .fc-event-skin {
  background-color:transparent;
  border-color:transparent;
}
.fc-event-skin {
  background-color:#<?=$calendarEvent;?>;
  border-color:#<?=$calendarEvent;?>;
  color:#<?=$calendarEventR;?>;
}

/* show the group with displayAsTag */
#elementDialog .displayAsTag {
    width: 100%;
    height: 2px;
    background-color: #f9ebb2;
    margin-top: -17px;
    margin-bottom: 15px;
}

/******************************
* JQUERY-UI
******************************/
.ui-widget { border-color:#<?=$dialogBorder;?>; background: #<?=$white;?>; color: #<?=$black;?>; }
.ui-widget .ui-widget-header { background:#<?=$dialogBoxTitle;?>; color:#<?=$dialogBoxTitleR;?> }
.ui-dialog .ui-dialog-buttonpane { background:#<?=$footerBar; ?>; }
.ui-tabs .ui-tabs-nav { border-bottom-color:#<?=$searchBarBorder;?>; background-color:#fff; }

/******************************
* CKEditor
******************************/
.cke_inner .cke_bottom { padding:0px 3px; }
.cke_inner .cke_path { margin:0px; }
.cke_inner .cke_path_empty { padding:0px; }
.cke_inner .cke_path_item { padding: 1px 4px; }
.cke_inner .cke_resizer { margin-top:3px; margin-right:0px; }
.cke_inner .cke_toolbox_collapser.cke_toolbox_collapser_min { margin: 0px 2px; }
.cke_inner .cke_bottom { position:static; }
.cke_inner .cke_editable ol { font-family: Arial; font-size: 13px; }