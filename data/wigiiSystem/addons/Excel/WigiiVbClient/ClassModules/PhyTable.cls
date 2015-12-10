VERSION 1.0 CLASS
BEGIN
  MultiUse = -1  'True
END
Attribute VB_Name = "PhyTable"
Attribute VB_GlobalNameSpace = False
Attribute VB_Creatable = True
Attribute VB_PredeclaredId = False
Attribute VB_Exposed = False
'-
'This file is part of Wigii.
'
'Wigii is free software: you can redistribute it and\/or modify
'it under the terms of the GNU General Public License as published by
'the Free Software Foundation, either version 3 of the License, or
'(at your option) any later version.
'
'Wigii is distributed in the hope that it will be useful,
'but WITHOUT ANY WARRANTY; without even the implied warranty of
'MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
'GNU General Public License for more details.
'
'You should have received a copy of the GNU General Public License
'along with Wigii.  If not, see <http:\//www.gnu.org/licenses/>.
'
'@copyright  Copyright (c) 2000-2015 Wigii    https://github.com/wigii/wigii    http://www.wigii.org/system
'@license    http://www.gnu.org/licenses/     GNU General Public License
'-

'---------------------------------------------------------------
'- PHYSICAL TABLE
'---------------------------------------------------------------
'- Author  : Camille WEBER
'- Update  : fri 02-11-2000
'- Version : 1.0
'---------------------------------------------------------------
'- This class exports the physical table object
'- This object stores permanently data in the DB;
'- it is located on an Excel Sheet;
'- it has a unique table identifier TID (0..n)
'- and a logical table associated.
'-
'- Before using the DB don't forget to call LTFact_Initialize
'- and load all physical tables
'---------------------------------------------------------------

'- Instance variables :
Private descriptor As Range
Private table As LogTable

'- Public properties :

'-------------------------------------------------------------
'- TID
'-------------------------------------------------------------
'- Purpose : Returns the table identifier
'- Input   : -
'- Output  : the TID or -1 if the table is not loaded
'-------------------------------------------------------------
Public Property Get TID() As Integer
   If Not descriptor Is Nothing Then
      TID = phyTID
   Else
      TID = -1
   End If
End Property

'-------------------------------------------------------------
'- NAME
'-------------------------------------------------------------
'- Purpose : Returns the table name
'- Input   : -
'- Output  : the name or "" if the table is not loaded
'-------------------------------------------------------------
Public Property Get Name() As String
   If Not descriptor Is Nothing Then
      Name = phyName
   Else
      Name = ""
   End If
End Property

'-------------------------------------------------------------
'- DEFINITION
'-------------------------------------------------------------
'- Purpose : Returns the table definition
'- Input   : -
'- Output  : the description or "" if the table is not loaded
'-------------------------------------------------------------
Public Property Get Definition() As String
   If Not descriptor Is Nothing Then
      Definition = phyDefintion
   Else
      Definition = ""
   End If
End Property

'-------------------------------------------------------------
'- HAS HEADER
'-------------------------------------------------------------
'- Purpose : Looks if the table has a header
'- Input   : -
'- Output  : true or false
'-------------------------------------------------------------
Public Property Get HasHeader() As Boolean
   If Not descriptor Is Nothing Then
      HasHeader = phyHasHeader
   Else
      HasHeader = False
   End If
End Property

'-------------------------------------------------------------
'- ROW COUNT
'-------------------------------------------------------------
'- Purpose : Returns the number of rows in the table
'- Input   : -
'- Output  : 0..n
'-------------------------------------------------------------
Public Property Get rowCount() As Integer
   If Not descriptor Is Nothing Then
      rowCount = phyRowCount
   Else
      rowCount = 0
   End If
End Property

'-------------------------------------------------------------
'- COLUMN COUNT
'-------------------------------------------------------------
'- Purpose : Returns the number of columns in the table
'- Input   : -
'- Output  : 0..n
'-------------------------------------------------------------
Public Property Get colCount() As Integer
   If Not descriptor Is Nothing Then
      colCount = phyColCount
   Else
      colCount = 0
   End If
End Property

'-------------------------------------------------------------
'- LOGICAL TABLE
'-------------------------------------------------------------
'- Purpose : Returns the logical table associated
'- Input   : -
'- Output  : the table or Nothing if the table is not loaded
'-           ! do not call SetRange or Resize on this table,
'-           ! but use instead ChangeLogTable
'-------------------------------------------------------------
Public Property Get LogTable() As LogTable
   If Not descriptor Is Nothing Then
      Set LogTable = table
   Else
      Set LogTable = Nothing
   End If
End Property

'- Public methods :

'-------------------------------------------------------------
'- LOAD
'-------------------------------------------------------------
'- Purpose : Loads a table from an Excel sheet
'- Input   : the table descriptor (upper left cell)
'- Output  : -
'-------------------------------------------------------------
Public Sub Load(descr As Range)
   Dim d As Range
   Dim r As Integer, c As Integer
   
   Set descriptor = descr
   Set d = phyFirstCell
   r = phyRowCount: c = phyColCount
   Set table = New LogTable
   
   'Binds the logical table to the physical range
   If phyHasHeader And (c > 0) Then
      With phySheet
         table.SetRange .Range(d, d.Offset(r, c - 1)), True
      End With
   ElseIf (r > 0) And (c > 0) Then
      With phySheet
         table.SetRange .Range(d, d.Offset(r - 1, c - 1)), False
      End With
   Else
      table.SetRange Nothing, False
   End If
End Sub

'---------------------------------------------------------------
'- INSERT DELETE
'---------------------------------------------------------------
'- Purpose : Replaces data in the table with new data
'- Input   : the old data, the new data
'-           the column to apply the comparison between the new
'-           data and the existing data,
'-           a flag saying if the old data or the table are sorted (true),
'-           the sort order (xlAscending, xlDescending)
'- Output  : if oldD is nothing then newD is inserted
'-           if newD is nothing then oldD is deleted
'-           else replaces the oldD in table with newD
'-           the error number :
'-           0 : no error
'-          -1 : the table is not loaded
'-          -2 : impossible to create temporary tables
'---------------------------------------------------------------
Public Function insertDelete(oldD As LogTable, newD As LogTable, col As Integer, _
                             Optional tSorted As Boolean = True, Optional tSortOrder As Integer = xlAscending, _
                             Optional oldSorted As Boolean = True, Optional oldSortOrder As Integer = xlAscending) As Integer
   
   Dim tmp1 As LogTable, tmp2 As LogTable
   Dim tmprange As Range
   Dim r As Integer, c As Integer
   
   If descriptor Is Nothing Or table Is Nothing Then
      insertDelete = -1 'Table not loaded
      Exit Function
   End If
   
   Set tmprange = LTFact_CreateUnbounded
   If tmprange Is Nothing Then
      ChangeData = -2 'Impossible to create temporary tables
      Exit Function
   End If
   Set tmp2 = New LogTable
   
   'Builds the complementary of the old data in tmp1
   If oldD Is Nothing Then
      tmp2.SetRange table.Data, False
      Set tmp1 = tmp2.CopyTo(tmprange)
   Else
      Set tmp1 = LT_Complementary(oldD, table, col, tmprange, oldSorted, oldSortOrder, tSorted, tSortOrder)
   End If
      
   'Appends the new data to tmp1
   If Not newD Is Nothing Then
      tmp2.SetRange newD.Data, False
      If Not tmp1 Is Nothing Then
         Set tmprange = tmprange.Offset(tmp1.rowCount, 0)
         Set tmp2 = tmp2.CopyTo(tmprange)
         If Not tmp2 Is Nothing Then
            tmp1.Resize tmp2.rowCount, 0
         End If
      Else
         Set tmp1 = tmp2.CopyTo(tmprange)
      End If
   End If
   
   If Not tmp1 Is Nothing Then
      LTFact_RegisterUnbounded tmp1.rowCount
   Else
      LTFact_RegisterUnbounded 0
   End If
      
   'Deletes the old table
   If Not table.IsEmpty Then
      table.Data.ClearContents
   End If
   
   'Copies the new data on the physical table
   Set tmprange = phyFirstCell
   If Not tmp1 Is Nothing Then
      If table.HasHeader Then
         Set tmp2 = tmp1.CopyTo(tmprange.Offset(1, 0))
      Else
         Set tmp2 = tmp1.CopyTo(tmprange)
      End If
      r = tmp2.rowCount: c = phyColCount
   Else
      Set tmp2 = Nothing
      r = 0: c = phyColCount
   End If
   
   'Builds the new physical table
   If phyHasHeader And (c > 0) Then
      With phySheet
         table.SetRange .Range(tmprange, tmprange.Offset(r, c - 1)), True
      End With
   ElseIf (r > 0) And (c > 0) Then
      With phySheet
         table.SetRange .Range(tmprange, tmprange.Offset(r - 1, c - 1)), False
      End With
   Else
      table.SetRange Nothing, False
   End If
   phyRowCount = r
   
   If tSorted Then
      table.Sort col, tSortOrder
   End If
   
   'Destroys temporary tables and exits the method
   LTFact_DestroyTable tmp1
   insertDelete = 0 'No error
End Function

'---------------------------------------------------------------
'- INSERT UPDATE
'---------------------------------------------------------------
'- Purpose : Updates or inserts new data in table
'- Input   : the data to update or insert
'-           the column ! identifying ! the data,
'-           a flag saying if the data or the table are sorted (true),
'-           the sort order (xlAscending, xlDescending)
'- Output  : the error number :
'-           0 : no error
'-          -1 : the table is not loaded
'---------------------------------------------------------------
Public Function insertUpdate(uData As LogTable, col As Integer, _
                             Optional tSorted As Boolean = True, Optional tSortOrder As Integer = xlAscending, _
                             Optional dSorted As Boolean = True, Optional dSortOrder As Integer = xlAscending)
   Dim i As Integer, j As Integer, aj As Integer, bj As Integer
   Dim t As Range, d As Range, res As Range, dest As Range
   Dim r As Integer, c As Integer
   
   If descriptor Is Nothing Or table Is Nothing Then
      insertUpdate = -1 'Table not loaded
      Exit Function
   End If
   
   r = phyRowCount: c = phyColCount
   Set t = table.Data
   Set d = uData.Data
   Set dest = phyFirstCell
   If tSorted Then
      If dSorted Then
         If dSortOrder = tSortOrder Then
            aj = 0: bj = 1
         Else
            aj = uData.rowCount + 1: bj = -1
         End If
         i = 1: j = 1
         Do While i <= table.rowCount And j <= uData.rowCount
            Do While (t.Cells(i, col).Value2 < d.Cells(aj + bj * j, col).Value2 And tSortOrder = xlAscending) Or _
                     (t.Cells(i, col).Value2 > d.Cells(aj + bj * j, col).Value2 And tSortOrder = xlDescending)
                                       
               i = i + 1
               If i > table.rowCount Then
                  Exit Do
               End If
            Loop
            If i > table.rowCount Then
               Exit Do
            End If
            If t.Cells(i, col).Value2 = d.Cells(aj + bj * j, col).Value2 Then
               'Updates the data
               t.Rows(i).Value2 = d.Rows(aj + bj * j).Value2
               j = j + 1
            End If
            If j > uData.rowCount Then
               Exit Do
            End If
            Do While (t.Cells(i, col).Value2 > d.Cells(aj + bj * j, col).Value2 And tSortOrder = xlAscending) Or _
                     (t.Cells(i, col).Value2 < d.Cells(aj + bj * j, col).Value2 And tSortOrder = xlDescending)
               
               'Inserts the data
               getNextFreeRow(r, c).Value2 = d.Rows(aj + bj * j).Value2
               r = r + 1: j = j + 1
               If j > uData.rowCount Then
                  Exit Do
               End If
            Loop
         Loop
         Do While j <= uData.rowCount
            'Inserts the data
            getNextFreeRow(r, c).Value2 = d.Rows(aj + bj * j).Value2
            r = r + 1: j = j + 1
         Loop
      Else
         For i = 1 To uData.rowCount
            Set res = table.FindDichotomy(d.Cells(i, col).Value2, col, tSortOrder)
            If res Is Nothing Then
               'Inserts the data
               getNextFreeRow(r, c).Value2 = d.Rows(i).Value2
            Else
               'Updates the data
               Intersect(res.EntireRow, t).Value2 = d.Rows(i).Value2
            End If
         Next i
      End If
   Else
      For i = 1 To uData.rowCount
         Set res = table.FindLinear(d.Cells(i, col).Value2, col)
         If res Is Nothing Then
            'Inserts the data
            getNextFreeRow(r, c).Value2 = d.Rows(i).Value2
            r = r + 1
         Else
            'Updates the data
            Intersect(res.EntireRow, t).Value2 = d.Rows(i).Value2
         End If
      Next i
   End If
   
   'Builds the new physical table
   If phyHasHeader And (c > 0) Then
      With phySheet
         table.SetRange .Range(dest, dest.Offset(r, c - 1)), True
      End With
   ElseIf (r > 0) And (c > 0) Then
      With phySheet
         table.SetRange .Range(dest, dest.Offset(r - 1, c - 1)), False
      End With
   Else
      table.SetRange Nothing, False
   End If
   phyRowCount = r
   
   If tSorted Then
      table.Sort col, tSortOrder
   End If
   
   insertUpdate = 0 'No error
End Function

'-----------------------------------------------------------------
'- CHANGE LOGICAL TABLE
'-----------------------------------------------------------------
'- Purpose : Replaces the associated logical table with a new one
'- Input   : the new logical table
'- Output  : ! the old logical table is not destroyed !
'-           the error number :
'-           0 : no error
'-          -1 : table not loaded
'-          -2 : new table doesn't exist
'-----------------------------------------------------------------
Public Function ChangeLogTable(newLogTable As LogTable) As Integer
   If descriptor Is Nothing Then
      ChangeLogTable = -1 'table not loaded
      Exit Function
   End If
   If newLogTable.Range Is Nothing Then
      ChangeLogTable = -2 'new table doesn't exist
      Exit Function
   End If
   
   'Changes the logical table
   Set table = newLogTable
   'Updates the table descriptor
   phyHasHeader = table.HasHeader
   phyRowCount = table.rowCount
   phyColCount = table.colCount
   Set phySheet = table.Range.Worksheet
   Set phyFirstCell = table.Range.Cells(1, 1)
   
   ChangeLogTable = 0 'no error
End Function

'- Private methods :

Private Property Get phyTID() As Integer
   phyTID = descriptor.Value2
End Property

Private Property Get phyName() As String
   phyName = descriptor.Offset(0, 1).Value2
End Property

Private Property Get phyDefinition() As String
   phyDefinition = descriptor.Offset(0, 2).Value2
End Property

Private Property Get phyHasHeader() As Boolean
   phyHasHeader = descriptor.Offset(0, 3).Value2
End Property

Private Property Let phyHasHeader(ByVal h As Boolean)
   descriptor.Offset(0, 3).Value2 = h
End Property

Private Property Get phyRowCount() As Integer
   phyRowCount = descriptor.Offset(0, 4).Value2
End Property
Private Property Let phyRowCount(ByVal r As Integer)
   descriptor.Offset(0, 4).Value2 = r
End Property

Private Property Get phyColCount() As Integer
   phyColCount = descriptor.Offset(0, 5).Value2
End Property
Private Property Let phyColCount(ByVal c As Integer)
   descriptor.Offset(0, 5).Value2 = c
End Property

Private Property Get phySheet() As Worksheet
   Set phySheet = Worksheets(descriptor.Offset(0, 6).Value2)
End Property
Private Property Set phySheet(s As Worksheet)
   descriptor.Offset(0, 6).Value2 = s.Name
End Property

Private Property Get phyFirstCell() As Range
   Set phyFirstCell = phySheet.Cells(descriptor.Offset(0, 7).Value2, _
                                     descriptor.Offset(0, 8).Value2)
End Property
Private Property Set phyFirstCell(r As Range)
   descriptor.Offset(0, 6).Value2 = r.Worksheet.Name
   descriptor.Offset(0, 7).Value2 = r.Row
   descriptor.Offset(0, 8).Value2 = r.Column
End Property

Private Function getNextFreeRow(r As Integer, c As Integer) As Range
   If phyHasHeader Then
      With phyFirstCell
         Set getNextFreeRow = .Worksheet.Range(.Offset(1 + r, 0), .Offset(1 + r, c - 1))
      End With
   Else
      With phyFirstCell
         Set getNextFreeRow = .Worksheet.Range(.Offset(r, 0), .Offset(r, c - 1))
      End With
   End If
End Function
