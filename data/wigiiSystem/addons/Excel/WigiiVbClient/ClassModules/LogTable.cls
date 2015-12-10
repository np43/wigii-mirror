VERSION 1.0 CLASS
BEGIN
  MultiUse = -1  'True
END
Attribute VB_Name = "LogTable"
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

'-------------------------------------------------------------
'- LOGICAL TABLE
'-------------------------------------------------------------
'- Author  : Camille WEBER
'- Update  : fri 02-11-2000
'- Version : 1.0
'-------------------------------------------------------------
'- This class exports the logical table object
'- This object represents the atomic logical data structure
'- in the whole project.
'- A logical table has an associated range for data,
'- and an optional range for a header
'-
'- Use the method SetRange to initialize a new table with
'- a given range
'-------------------------------------------------------------

'- Instance variables :
Private trange As Range
Private theader As Boolean

'- Public properties :

'-------------------------------------------------------------
'- RANGE
'-------------------------------------------------------------
'- Purpose : Returns the whole range occupied by the table
'-           (with the header)
'- Input   : -
'- Output  : the range or Nothing if the table is empty
'-------------------------------------------------------------
Public Property Get Range() As Range
    Set Range = trange
End Property

'-------------------------------------------------------------
'- HEADER
'-------------------------------------------------------------
'- Purpose : Returns the range occupied by the header
'- Input   : -
'- Output  : the range or Nothing if there is no header
'-------------------------------------------------------------
Public Property Get Header() As Range
    If theader And Not (trange Is Nothing) Then
        Set Header = trange.Rows(1)
    Else
        Set Header = Nothing
    End If
End Property

'-------------------------------------------------------------
'- DATA
'-------------------------------------------------------------
'- Purpose : Returns the range occupied by the data in table
'-           (without the header)
'- Input   : -
'- Output  : the range or Nothing if the table is empty
'-------------------------------------------------------------
Public Property Get Data() As Range
    If Not Me.IsEmpty Then
        If theader Then
            With trange
                Set Data = .Worksheet.Range(.Cells(2, 1), .Cells(Me.rowCount + 1, Me.colCount))
            End With
        Else
            Set Data = trange
        End If
    Else
        Set Data = Nothing
    End If
End Property

'-------------------------------------------------------------
'- COLCOUNT
'-------------------------------------------------------------
'- Purpose : Returns the number of columns in table
'- Input   : -
'- Output  : 0 if there is no table
'-------------------------------------------------------------
Public Property Get colCount() As Integer
    If Not (trange Is Nothing) Then
        colCount = trange.Columns.Count
    Else
        colCount = 0
    End If
End Property

'-------------------------------------------------------------
'- ROWCOUNT
'-------------------------------------------------------------
'- Purpose : Returns the number of rows of data in table
'- Input   : -
'- Output  : 0 if the table is empty
'-------------------------------------------------------------
Public Property Get rowCount() As Integer
    If Not (trange Is Nothing) Then
        If theader Then
            rowCount = trange.Rows.Count - 1
        Else
            rowCount = trange.Rows.Count
        End If
    Else
        rowCount = 0
    End If
End Property

'-------------------------------------------------------------
'- ISEMPTY
'-------------------------------------------------------------
'- Purpose : Looks if the table is empty (no data)
'- Input   : -
'- Output  : true or false
'-------------------------------------------------------------
Public Function IsEmpty() As Boolean
    IsEmpty = (Me.colCount = 0) Or (Me.rowCount = 0)
End Function

'-------------------------------------------------------------
'- HASHEADER
'-------------------------------------------------------------
'- Purpose : Looks if the table has a header
'- Input   : -
'- Output  : true or false
'-------------------------------------------------------------
Public Property Get HasHeader() As Boolean
    HasHeader = theader
End Property

'- Public methods :

'------------------------------------------------------------------
'- SETRANGE
'------------------------------------------------------------------
'- Purpose : Associates a range to the table
'-           The current range is replaced
'- Input   : the new range, use first row for header (true) or not
'- Output  : -
'------------------------------------------------------------------
Public Sub SetRange(r As Range, withHeader As Boolean)
    Set trange = r
    If withHeader And Not (r Is Nothing) Then
        theader = True
    Else
        theader = False
    End If
End Sub

'-------------------------------------------------------------
'- RESIZE
'-------------------------------------------------------------
'- Purpose : Resizes the table (relative to the current size)
'-           Use SetRange to totally replace the table
'- Input   : the number of rows/columns to add or remove
'- Output  : -
'-------------------------------------------------------------
Public Sub Resize(nbrows As Integer, nbcols As Integer)
    Dim r As Integer, c As Integer
    
    If trange Is Nothing Then
        Exit Sub
    End If
    r = nbrows + Me.rowCount
    c = nbcols + Me.colCount
    
    If theader Then
        If (r >= 0) And (c > 0) Then
            With trange
                Set trange = .Worksheet.Range(.Cells(1, 1), .Cells(1 + r, c))
            End With
        End If
    Else
        If (r > 0) And (c > 0) Then
            With trange
                Set trange = .Worksheet.Range(.Cells(1, 1), .Cells(r, c))
            End With
        End If
    End If
End Sub

'-------------------------------------------------------------
'- COPYTO
'-------------------------------------------------------------
'- Purpose : Copies the whole table (with header) to
'-           the given range. Only the values are copied
'- Input   : the destination range (upper left corner)
'-           ! the range must not be in table !
'- Output  : the new table (the current one is not changed)
'-------------------------------------------------------------
Public Function CopyTo(r As Range, Optional withHeader As Boolean = True) As LogTable
    Dim result As New LogTable
        
    If Not withHeader And Me.HasHeader And Not r Is Nothing Then
        If Me.IsEmpty Then
            Set CopyTo = Nothing
        Else
            With r
                result.SetRange .Worksheet.Range(.Cells(1, 1), .Cells(Me.rowCount, Me.colCount)), False
            End With
            result.Range.Value2 = Me.Data.Value2
            Set CopyTo = result
        End If
    ElseIf Not r Is Nothing And Not trange Is Nothing Then
        With r
            result.SetRange .Worksheet.Range(.Cells(1, 1), .Cells(trange.Rows.Count, trange.Columns.Count)), theader
        End With
        result.Range.Value2 = trange.Value2
        Set CopyTo = result
    Else
        Set CopyTo = Nothing
    End If
End Function

'-------------------------------------------------------------
'- SPECIFICCOPY
'-------------------------------------------------------------
'- Purpose : Copies the specified columns from the table,
'-           in the given order
'- Input   : the destination range (upper left corner)
'-           ! the range must not be in table !
'-           the column map (array with the column numbers),
'-           the number of columns to copy
'- Output  : the new table
'-------------------------------------------------------------
Public Function SpecificCopy(r As Range, colmap() As Integer, nbcol As Integer) As LogTable
    Dim result As New LogTable
    Dim i As Integer, n As Integer
    
    If (Not r Is Nothing) And _
       (Not trange Is Nothing) And _
       (nbcol > 0) Then
    
        'Calculates the number of columns to copy
        n = trange.Columns.Count
        If nbcol < n Then
            n = nbcol
        End If
        'Constructs the result table
        With r
            result.SetRange .Worksheet.Range(.Cells(1, 1), .Cells(trange.Rows.Count, n)), theader
        End With
        'Copies the data
        For i = 1 To n
            result.Range.Columns(i).Value2 = trange.Columns(colmap(i)).Value2
        Next i
        Set SpecificCopy = result
    Else
        Set SpecificCopy = Nothing
    End If
End Function

'---------------------------------------------------------------------
'- SORT
'---------------------------------------------------------------------
'- Purpose : Sorts the data in table according to the given column
'- Input   : the column, the sort order (xlAscending, xlDescending)
'- Output  : ! the sheet containing the table must be unprotected !
'---------------------------------------------------------------------
Public Sub Sort(col As Integer, Optional order As Integer = xlAscending)
    If (Me.IsEmpty) Or _
       (col <= 0 Or col > Me.colCount) Then
        Exit Sub
    End If
    With Me.Data
        .Sort .Cells(1, col), order
    End With
End Sub

'----------------------------------------------------------------------
'- FINDLINEAR
'----------------------------------------------------------------------
'- Purpose : Looks for the given value in the given column
'-           Use this method for none sorted table (complexity : O(n))
'- Input   : the value, the column, return the first value found (true)
'-           or return the last value found (false)
'- Output  : the range containing the value or Nothing if not found
'----------------------------------------------------------------------
Public Function FindLinear(val As Variant, col As Integer, _
                           Optional first As Boolean = True) As Range
    Dim d As Range, NAVal As Boolean, NACell As Boolean
    Dim a, b, s As Integer
    
    If (Me.IsEmpty) Or _
       (col <= 0 Or col > Me.colCount) Then
       
        Set FindLinear = Nothing
        Exit Function
    End If
    Set d = Me.Data
    NAVal = Application.WorksheetFunction.IsNA(val)
    
    'Goes forward through the table
    If first Then
        a = 1: b = Me.rowCount 'Walk
        s = 1                  'Step
    'Goes backward through the table
    Else
        a = Me.rowCount: b = 1 'Walk
        s = -1                 'Step
    End If
    'Linear walk :
    For i = a To b Step s
        NACell = Application.WorksheetFunction.IsNA(d.Cells(i, col))
        If NACell And NAVal Then
           'value found
            Set FindLinear = d.Cells(i, col)
            Exit Function
        ElseIf Not NACell And Not NAVal Then
            If d.Cells(i, col).Value2 = val Then
               'value found
               Set FindLinear = d.Cells(i, col)
               Exit Function
            End If
        End If
    Next i
    'Not found
    Set FindLinear = Nothing
End Function

'----------------------------------------------------------------------
'- FINDDICHOTOMY
'----------------------------------------------------------------------
'- Purpose : Looks for the given value in the given column
'-           Use this method for sorted table (complexity : O(log n))
'- Input   : the value, the column, the sortorder (xlAscending, xlDescending),
'-           has to return the nearest position if not found (true),
'-           else Nothing (false)
'- Output  : the range containing the value or Nothing if not found
'----------------------------------------------------------------------
Public Function FindDichotomy(val As Variant, col As Integer, _
                              Optional sortorder As Integer = xlAscending, _
                              Optional nearestpos As Boolean = False) As Range
    Dim d As Range, NAVal As Boolean, NACell As Boolean
    Dim i As Integer, j As Integer, k As Integer
    
    
    If (Me.IsEmpty) Or _
       (col <= 0 Or col > Me.colCount) Then
       
        Set FindDichotomy = Nothing
        Exit Function
    End If
    Set d = Me.Data
    NAVal = Application.WorksheetFunction.IsNA(val)
    i = 1: j = Me.rowCount
    
    'Dichotomy walk :
    Do While i < j
        k = (i + j + 1) \ 2
        NACell = Application.WorksheetFunction.IsNA(d.Cells(k, col))
        If NACell And NAVal Then
           'value found
            Set FindDichotomy = d.Cells(k, col)
            Exit Function
        ElseIf NACell Or NAVal Then
            If (sortorder = xlAscending And NACell) Or _
               (sortorder = xlDescending And NAVal) Then
                j = k - 1
            Else
                i = k
            End If
        ElseIf d.Cells(k, col).Value2 = val Then
            'value found
            Set FindDichotomy = d.Cells(k, col)
            Exit Function
        ElseIf (d.Cells(k, col).Value2 > val And sortorder = xlAscending) Or _
               (d.Cells(k, col).Value2 < val And sortorder = xlDescending) Then
            j = k - 1
        Else
            i = k
        End If
    Loop
    'value found or nearest place found
    NACell = Application.WorksheetFunction.IsNA(d.Cells(i, col))
    'Found
    If NACell And NAVal Then
        Set FindDichotomy = d.Cells(i, col)
    'Not found
    ElseIf Not nearestpos And (NACell Or NAVal) Then
        Set FindDichotomy = Nothing
    'Found
    ElseIf nearestpos And (NACell Or NAVal) Then
        Set FindDichotomy = d.Cells(i, col)
    'Found
    ElseIf nearestpos Or d.Cells(i, col).Value2 = val Then
        Set FindDichotomy = d.Cells(i, col)
    'Not found
    Else
        Set FindDichotomy = Nothing
    End If
End Function

'----------------------------------------------------------------------
'- EXPORT AS CSV
'----------------------------------------------------------------------
'- Purpose : Exports a table in CSV format
'- Input   : -
'- Output  : true if the table was exported, else false
'----------------------------------------------------------------------
Public Function exportAsCSV() As Boolean
   Dim events As Boolean
   Dim wb As Workbook, nbSheets As Long
   Dim response As Boolean
   
   nbSheets = Application.SheetsInNewWorkbook
   Application.SheetsInNewWorkbook = 1
   events = Application.EnableEvents
   Application.EnableEvents = False
   
   Set wb = Workbooks.Add
   Me.CopyTo wb.Worksheets(1).Cells(1, 1)
   response = Application.Dialogs(xlDialogSaveAs).Show("", xlCSV)
   wb.Close False
   ThisWorkbook.Activate
   
   Application.SheetsInNewWorkbook = nbSheets
   Application.EnableEvents = events
   exportAsCSV = response
End Function

