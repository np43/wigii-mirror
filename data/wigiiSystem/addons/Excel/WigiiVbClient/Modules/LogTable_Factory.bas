Attribute VB_Name = "LogTable_Factory"
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

'----------------------------------------------------------------------------
'- LOGICAL TABLE FACTORY
'----------------------------------------------------------------------------
'- Author  : Camille WEBER
'- Update  : fri 2-11-2000
'- Version : 1.0
'----------------------------------------------------------------------------
'- This package manages a logical table factory.
'- If you need an empty table with a given size, use LTFact_CreateBounded;
'- If you need a range to put data, use LTFact_CreateUnbounded;
'- But don't forget to call LTFact_RegisterUnbounded to register your table
'- when it is full.
'- To destroy a table when you don't want it anymore, use LTFact_DestroyTable
'- To flush the whole factory, use LTFact_Initialize
'- Before using this package, don't forget to call LTFact_Initialize
'----------------------------------------------------------------------------

'- Pointer on the first free cell for a new table
Private FreeRow As Range
'- Flag to enable or not the creation of new tables
Private UnboundedTableOpen As Boolean

'- Public methods :

'-------------------------------------------------------------
'- LTFACT_INITIALIZE
'-------------------------------------------------------------
'- Purpose : Initializes the table factory : all the existing
'-           tables are destroyed
'- Input   : the sheet to host the factory
'- Output  : !! the range occupied by each table is destroyed !!
'-------------------------------------------------------------
Public Sub LTFact_Initialize(s As Worksheet)
    With s
        .Cells.Delete
        Set FreeRow = .Cells(1, 1)
    End With
    UnboundedTableOpen = False
End Sub

'-------------------------------------------------------------
'- LTFACT_CREATEBOUNDED
'-------------------------------------------------------------
'- Purpose : Creates a bounded empty table
'- Input   : the size, with a header or not
'- Output  : an empty table or
'-           Nothing if an actually unbounded table is opened or
'-           if nbrows and nbcols are negative or null
'-------------------------------------------------------------
Public Function LTFact_CreateBounded(nbrows As Integer, nbcols As Integer, _
                                     Optional withHeader As Boolean = False) As LogTable
    If (Not UnboundedTableOpen) And _
       (nbrows > 0) And (nbcols > 0) Then
       
        Set LTFact_CreateBounded = New LogTable
        LTFact_CreateBounded.SetRange FreeRow.Worksheet.Range(FreeRow, FreeRow.Offset(nbrows - 1, nbcols - 1)), _
                                      withHeader
        Set FreeRow = FreeRow.Offset(nbrows, 0)
    Else
        Set LTFact_CreateBounded = Nothing
    End If
End Function

'-------------------------------------------------------------
'- LTFACT_CREATEUNBOUNDED
'-------------------------------------------------------------
'- Purpose : Creates an unbounded table : returns a range
'-           (the upper-left cell) where you can write data.
'-           Don't forget to call method LTFact_RegisterUnbounded
'-           to register the table, otherwise you stop
'-           all creation of new tables
'- Input   : -
'- Output  : the upper left cell or
'-           Nothing if an actually unbounded table is opened
'-------------------------------------------------------------
Public Function LTFact_CreateUnbounded() As Range
    If Not UnboundedTableOpen Then
        Set LTFact_CreateUnbounded = FreeRow
        UnboundedTableOpen = True
    End If
End Function

'-------------------------------------------------------------
'- LTFACT_REGISTERUNBOUNDED
'-------------------------------------------------------------
'- Purpose : Registers the new table in factory,
'-           and permits the creation of new table
'- Input   : the size of the table
'- Output  : -
'-------------------------------------------------------------
Public Sub LTFact_RegisterUnbounded(nbrows As Integer)
    If UnboundedTableOpen And (nbrows >= 0) Then
        Set FreeRow = FreeRow.Offset(nbrows, 0)
        UnboundedTableOpen = False
    End If
End Sub

'------------------------------------------------------------------
'- LTFACT_DESTROYTABLE
'------------------------------------------------------------------
'- Purpose : Destroys a given table in factory
'- Input   : the table
'- Output  : nothing is done if the table doesn't exist in factory
'------------------------------------------------------------------
Public Sub LTFact_DestroyTable(table As LogTable)
    If Not table Is Nothing Then
        If Not table.Range Is Nothing Then
            If table.Range.Worksheet Is FreeRow.Worksheet Then
                table.Range.Rows.EntireRow.Delete
                table.SetRange Nothing, False
            End If
        End If
    End If
End Sub

'------------------------------------------------------------------
'- LTFACT_CREATEFROMSQL
'------------------------------------------------------------------
'- Purpose : Creates a log table as a result of an SQL query
'- Input   : the connection string, the sql query
'-           if disconnectFromQueryTable then does not keep underlying querytable
'- Output  : nothing in case of error or the table.
'------------------------------------------------------------------
Public Function LTFact_CreateFromSql(connectionString As String, sql As String, Optional disconnectFromQueryTable As Boolean = True) As LogTable
   Set LTFact_CreateFromSql = Nothing
   'opens unbounded table
   Dim dest As Range
   Set dest = LTFact_CreateUnbounded()
   If dest Is Nothing Then Exit Function
   'fetches query
   ES_log sql, "LogTable_Factory", "LTFact_CreateFromSql"
   Dim qt As QueryTable
   Set qt = dest.Worksheet.QueryTables.Add(connectionString, dest, sql)
   'registers unbounded and creates log table
   If Not qt Is Nothing Then
      On Error Resume Next
      Dim ok As Boolean
      ok = qt.Refresh(False)
      On Error GoTo 0
      'checks that table is not empty
      Dim thirdRowEmpty As Boolean
      thirdRowEmpty = False
      If ok Then
         With qt.ResultRange
            If .Rows.Count = 3 Then
               'only two rows of data and all empty ?
               Dim n As Integer, i As Integer
               Dim foundSomething As Boolean
               foundSomething = False
               thirdRowEmpty = True
               n = .Columns.Count
               For i = 1 To n
                  If CStr(.Cells(2, i).Value2) <> "" Then
                     foundSomething = True
                  End If
                  If CStr(.Cells(3, i).Value2) <> "" Then
                     foundSomething = True
                     thirdRowEmpty = False
                     Exit For
                  End If
               Next i
               ok = foundSomething
            End If
         End With
      End If
      If ok Then
         Set LTFact_CreateFromSql = New LogTable
         LTFact_CreateFromSql.SetQueryTable qt
         If disconnectFromQueryTable And thirdRowEmpty Then
            LTFact_CreateFromSql.Resize -1, 0
         End If
         LTFact_RegisterUnbounded LTFact_CreateFromSql.Range.Rows.Count
         'Disconnects query table
         If disconnectFromQueryTable And Not qt Is Nothing And Not thirdRowEmpty Then
            LTFact_CreateFromSql.SetQueryTable Nothing
            qt.Delete
         End If
      Else
         LTFact_RegisterUnbounded 0
         If Not qt Is Nothing Then
            qt.Delete
         End If
      End If
   Else
      LTFact_RegisterUnbounded 0
   End If
End Function

