Attribute VB_Name = "FileManager"
'**
'*  This file is part of Wigii.
'*  Wigii is developed to inspire humanity. To Humankind we offer Gracefulness, Righteousness and Goodness.
'*
'*  Wigii is free software: you can redistribute it and/or modify it
'*  under the terms of the GNU General Public License as published by
'*  the Free Software Foundation, either version 3 of the License,
'*  or (at your option) any later version.
'*
'*  Wigii is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
'*  without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
'*  See the GNU General Public License for more details.
'*
'*  A copy of the GNU General Public License is available in the Readme folder of the source code.
'*  If not, see <http://www.gnu.org/licenses/>.
'*
'*  @copyright  Copyright (c) 2016  Wigii.org
'*  @author     <http://www.wigii.org/system>      Wigii.org
'*  @link       <http://www.wigii-system.net>      <https://github.com/wigii/wigii>   Source Code
'*  @license    <http://www.gnu.org/licenses/>     GNU General Public License
'*/

'----------------------------------------------------------------------------
'- FILE MANAGER
'----------------------------------------------------------------------------
'- Author  : Camille WEBER
'- Update  : tue 2000-12-26
'- Version : 1.0
'----------------------------------------------------------------------------
'- This package manages workbooks. We can get a workbook given its FID
'- Before using this package, don't forget to call FM_Initialize
'-
'- When installing the file manager in a workbook, you have to define 2
'- constants : (see source code below)
'- FILEINDEX_TD   : the file index table descriptor upper left cell
'- FM_HOSTSHEET   : the name from the sheet hosting the file index TD.
'- You have to export 1 function in ThisWorkbook object (copy and paste) :
'------
'File manager exported functions
'Public Function FM_getTD() As Range
'  Set FM_getTD = FileManager.FM_getTD
'End Function
'------
'-
'- The file manager uses 1 table :
'- the file index which contains all the file descriptors in data-base.
'-
'- CAUTIONS :
'- ! The FID have to be in ascending order and adjacent : i,i+1,i+2,...
'- ! but they can start with any integer.
'----------------------------------------------------------------------------



'- Table manager's host sheet
Private Const FM_HOSTSHEET As String = "System"

'- File index
Private Const FILEINDEX_TD As String = "FileIndexTD"
Private fileIndex As PhyTable
'- Global index FID
Public FIDglobalBase As Integer

'-------------------------------------------------------------
'- FM_INITIALIZE
'-------------------------------------------------------------
'- Purpose : Initializes the file manager
'- Input   : -
'- Output : error number
'-          0 : no error
'-         -1 : global index reference not found
'-------------------------------------------------------------
Public Function FM_Initialize() As Integer
  Dim ref As Workbook
  Dim Error As Integer
  Dim fileIndex2 As PhyTable
  Dim TD As Range
  
  'Loads the local file index
  Set fileIndex = New PhyTable
  fileIndex.Load ThisWorkbook.Worksheets(FM_HOSTSHEET).Range(FILEINDEX_TD)
        
  'Opens the global reference index
  Error = FM_getWorkbook(FIDglobalBase, ref)
  If Error = -2 Then
    FM_Initialize = -1
    Exit Function
  End If
      
  If Not ref Is ThisWorkbook Then
         
    'Gives control to the global index reference file
    'and asks for file index
    Set fileIndex2 = New PhyTable
    fileIndex2.Load ref.TM_getTD()
    
    'Updates the file index
    With fileIndex.LogTable.Range
      .ClearContents
      fileIndex.ChangeLogTable fileIndex2.LogTable.CopyTo(.Cells(1, 1))
    End With
  End If
  FM_Initialize = 0
End Function

'- Public methods

'-------------------------------------------------------------
'- FM_GETWORKBOOK
'-------------------------------------------------------------
'- Purpose : Returns a reference on a workbook given its FID
'- Input   : the FID
'- Output : error number :
'-          0 : no error
'-         -1 : invalid FID
'-         -2 : invalid file path
'-------------------------------------------------------------
Public Function FM_getWorkbook(FID As Integer, Output As Workbook) As Integer
   Dim r As Range
   Dim events As Boolean
   Dim errorNumber As Integer
   Dim lastActiveWb As Workbook
   
   On Error GoTo errorHandler
   errorNumber = 0
   
   If Not FM_isFIDvalid(FID) Then
      Set Output = Nothing
      FM_openWorkbook = -1
      Exit Function
   End If
      
   Set r = fileIndex.LogTable.data.Cells(FID - FIDglobalBase + 1, 1)
   
   'Looks if the workbook is already open
   errorNumber = 1
   Set Output = Workbooks(r.Offset(0, 1).Value2)
   errorNumber = 0
   FM_openWorkbook = 0
   Exit Function
   
   'Opens the workbook
openWorkbook:
   events = Application.EnableEvents
   Application.EnableEvents = False
   Set lastActiveWb = ActiveWorkbook
   errorNumber = 2
   Set Output = Workbooks.Open(r.Offset(0, 2).Value2 & "\" & r.Offset(0, 1).Value2, _
                               0, False, notify:=False)
   errorNumber = 0
   lastActiveWb.Activate
   Application.EnableEvents = events
   FM_openWorkbook = 0
   Exit Function
   
   'Invalid file path
invalidPath:
   Set Output = Nothing
   Application.EnableEvents = events
   FM_openWorkbook = -2
   Exit Function

errorHandler:
   Select Case errorNumber
   Case 1
      Resume openWorkbook
   Case 2
      Resume invalidPath
   Case Else
      Err.Raise Err.Number
   End Select
End Function

'- Private methods

Private Function FM_isFIDvalid(FID As Integer) As Boolean
   FM_isFIDvalid = (FIDglobalBase <= FID) And (FID < FIDglobalBase + fileIndex.rowCount)
End Function

'- ! DO NOT USE DIRECTLY THIS FUNCTION, IT IS PUBLIC FOR  !
'- ! COMMUNICATION PURPOSE ONLY                           !
'- Input : -
'- Output : file index TD
Public Function FM_getTD() As Range
  Set FM_getTD = ThisWorkbook.Worksheets(FM_HOSTSHEET).Range(FILEINDEX_TD)
End Function

