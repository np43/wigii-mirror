/**
 *  This file is part of Wigii.
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

This folder contains some files that allows you to extract a Wigii database with the QlikView product in a quick and easy way. This stands as a Wigii - QlikView connector.
QlikView script extract from a Wigii database structure all the necessary data. The extraction is done in csv and in qvd

In order to use it you need to have a QlikView client installed (or a QlikView server).

Create a new QlikView document, hit CTRL+E to open the Script Editor, delete everything that Qlik puts by default. Then click on Insert->Scriptfiles and select the file "Wigii Base Script.txt", click Open.

At begining of the script there is a series of parameters you can change:
- General format settings
- ODBC connection name that you have setup on your computer or server (that must be done with the MSWindow ODBC Wizard)
- Database name
- Trashbin folder name filter
- Last update time to look when reloading

You can update the file Dico.xls to translate fields of type Attributs or MultipleAttributs or fieldnames.
You can update the file Filters.xls if you don't want that the script extract all the Namespaces and modules contained within your Wigii database. The tab sys_fields is there to define what are the fields where you want to have the sys info extracted (i.e. first_name sys_date, first_name sys_creationDate, etc...)



