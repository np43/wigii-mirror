Attribute VB_Name = "TableManager"
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

' Modified by Medair Sept 2016. Added option Explict to solve 64-bit + VBA7 compatability issues
Option Explicit

'----------------------------------------------------------------------------
'- TABLE MANAGER
'----------------------------------------------------------------------------
'- Author  : Camille WEBER
'- Update  : tue 2000-12-26
'- Version : 1.1
'----------------------------------------------------------------------------
'- This package manages the data-base tables. We can get a PhyTable
'- given its TID.
'- Before using this package TM_initialize with the correct mode :
'- local mode (authorizes access only to local tables) or distributed mode
'- (authorizes access to other tables according to the global index and file index).
'-
'- When installing the table manager in a workbook, you have to define 3
'- constants : (see source code below)
'- LOCALINDEX_TD  : the local index table descriptor upper left cell
'- GLOBALINDEX_TD : the global index table descriptor upper left cell
'- TM_HOSTSHEET   : the name from the sheet hosting the local index TD,
'-                  and the global index TD.
'- You have to export 3 functions in ThisWorkbook object (copy and paste) :
'------
'Table manager exported functions
'Public Function TM_Initialize(Optional mode As Integer = 1) As Integer
'  TM_Initialize = TableManager.TM_Initialize(mode)
'End Function
'Public Function TM_getPhyTable(TID As Integer, output As PhyTable) As Integer
'  TM_getPhyTable = TableManager.TM_getPhyTable(TID, output)
'End Function
'Public Function TM_getTD() As Range
'  TM_getTD = TableManager.TM_getTD()
'End Function
'------
'-
'- The table manager uses 2 tables :
'- the local index which contains all the local TD,
'- the global index which contains all the TID and their associated FID
'-
'- CAUTIONS :
'- ! The TID have to be in ascending order and adjacent : i,i+1,i+2,...
'- ! but they can start with any integer.
'- ! The local index or the global index can be empty
'----------------------------------------------------------------------------

'- Table manager's host sheet
Private Const TM_HOSTSHEET As String = "System"

'- Local physical tables
Private Const LOCALINDEX_TD As String = "LocalIndexTD"
Private localIndex As PhyTable
Private TIDlocalBase As Integer
Private localPhyTables() As PhyTable

'- Global index
Private Const GLOBALINDEX_TD As String = "GlobalIndexTD"
Private globalIndex As PhyTable
Private TIDglobalBase As Integer

'- Table manager mode
Private Const NOT_INITIALIZED_MODE As Integer = 0
Private Const LOCAL_MODE As Integer = 1
Private Const DISTRIBUTED_MODE As Integer = 2
Private TM_mode As Integer


'- Initialization

'-------------------------------------------------------------
'- TM_INITIALIZE
'-------------------------------------------------------------
'- Purpose : Initializes the table manager
'- Input   : TM mode :
'-           1 : local mode
'-           2 : distributed mode
'- Output  : -
'-------------------------------------------------------------
Public Function TM_Initialize(Optional mode As Integer = LOCAL_MODE) As Integer
   Dim i As Integer, n As Integer
   Dim ref As Workbook
   Dim Error As Integer
   Dim globalIndex2 As PhyTable
   Dim TD As Range
   
   If mode <= 1 Then
      TM_mode = LOCAL_MODE
   Else
      TM_mode = DISTRIBUTED_MODE
   End If
   
   'Loads the local index
   Set localIndex = New PhyTable
   localIndex.Load ThisWorkbook.Worksheets(TM_HOSTSHEET).Range(LOCALINDEX_TD)
   n = localIndex.rowCount
   
   'Loads the global index
   Set globalIndex = New PhyTable
   globalIndex.Load ThisWorkbook.Worksheets(TM_HOSTSHEET).Range(GLOBALINDEX_TD)
   
   'Loads the local tables
   If n > 0 Then
      With localIndex.LogTable.data
         TIDlocalBase = .Cells(1, 1).Value2
         ReDim localPhyTables(n - 1) As PhyTable
         
         For i = 0 To n - 1
            Set localPhyTables(i) = New PhyTable
            localPhyTables(i).Load .Cells(i + 1, 1)
         Next i
      End With
   End If
   
   'Updates the global index
   If TM_mode = DISTRIBUTED_MODE Then
      
      'Opens the global index reference
      FM_getWorkbook FileManager.FIDglobalBase, ref
      
      If Not ref Is ThisWorkbook Then
         
        'Gives control to the global index reference file
        'and asks for the global index
        Set TD = ref.TM_getTD
        Set globalIndex2 = New PhyTable
        globalIndex2.Load TD
         
        'Updates the global index
        With globalIndex.LogTable.Range
          .ClearContents
          globalIndex.ChangeLogTable globalIndex2.LogTable.CopyTo(.Cells(1, 1))
        End With
      End If
   End If
   
   'Initializes the global index
   With globalIndex.LogTable
      If Not .IsEmpty Then
         TIDglobalBase = .data.Cells(1, 1).Value2
      Else
         TIDglobalBase = 0
      End If
   End With
End Function


'- Public methods

'-------------------------------------------------------------
'- TM_GETPHYTABLE
'-------------------------------------------------------------
'- Purpose : Returns a reference on a PhyTable given its TID
'- Input   : the TID
'- Output  : the PhyTable, an error number :
'-           0 : no error
'-          -1 : TM is not initialized
'-          -2 : invalid TID
'-          Only in distributed mode :
'-          -3 : invalid FID associated to TID
'-          -4 : file path error for FID associated to TID
'-------------------------------------------------------------
Public Function TM_getPhyTable(TID As Integer, Output As PhyTable) As Integer
   Dim FID As Integer
   Dim wb As Workbook
   Dim Error As Integer
   
   If TM_mode = NOT_INITIALIZED_MODE Then
      Set Output = Nothing
      TM_getPhyTable = -1
      Exit Function
   End If
      
   If TM_isTIDlocal(TID) Then
      Set Output = localPhyTables(TID - TIDlocalBase)
      TM_getPhyTable = 0
   ElseIf (TM_mode = DISTRIBUTED_MODE) And TM_isTIDglobal(TID) Then
      'Opens the associated file
      FID = globalIndex.LogTable.data.Cells(TID - TIDglobalBase + 1, 2).Value2
      Error = FM_getWorkbook(FID, wb)
      If (Error = -1) Or (Error = -2) Then
         Set Output = Nothing
         TM_getPhyTable = Error - 2
         Exit Function
      End If
      
      'Gives control to the file and asks for the table
      TM_getPhyTable = wb.TM_getPhyTable(TID, Output)
      If TM_getPhyTable = -1 Then
        wb.TM_Initialize DISTRIBUTED_MODE
        TM_getPhyTable = wb.TM_getPhyTable(TID, Output)
      End If
   Else
      Set Output = Nothing
      TM_getPhyTable = -2
   End If
End Function

'- Private methods

Private Function TM_isTIDlocal(TID As Integer) As Boolean
   TM_isTIDlocal = (TIDlocalBase <= TID) And (TID < TIDlocalBase + localIndex.rowCount) And Not localIndex.LogTable.IsEmpty
End Function

Private Function TM_isTIDglobal(TID As Integer) As Boolean
   TM_isTIDglobal = (TIDglobalBase <= TID) And (TID < TIDglobalBase + globalIndex.rowCount) And Not globalIndex.LogTable.IsEmpty
End Function

'- ! DO NOT USE DIRECTLY THIS FUNCTION, IT IS PUBLIC FOR  !
'- ! COMMUNICATION PURPOSE ONLY                           !
'- Input : -
'- Output : the global index TD
Public Function TM_getTD() As Range
  Set TM_getTD = ThisWorkbook.Worksheets(TM_HOSTSHEET).Range(GLOBALINDEX_TD)
End Function


