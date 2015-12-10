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
 
 
 
To use the WigiiVbClient.xslm as a base to develop a functional VB client which connects to Wigii, you should :
- Open the file "WigiiVbClient.xlsm"
- Run the macro "ThisWorkbook.openSystem", username "system", password "wigii". You should see the worksheets "System","LTFact" and "About"
- Open the VisualBasic project (password is "wigii").
- Setup the external references as defined in the image "WigiiVbClient-system-references.png"
- Re-use and adapt the source code.