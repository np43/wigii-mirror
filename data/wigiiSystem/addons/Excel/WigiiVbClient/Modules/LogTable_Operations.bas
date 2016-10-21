Attribute VB_Name = "LogTable_Operations"
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

'---------------------------------------------------------------
'- LOGICAL TABLE OPERATIONS
'---------------------------------------------------------------
'- Author  : Camille WEBER
'- Update  : fri 2-11-2000
'- Version : 1.0
'---------------------------------------------------------------
'- This package exports methods for manipulating logical tables
'- SELECTION, INTERSECTION, UNION, CONCATENATION, COMPLEMENTARY,
'- DOUBLES DELETION, CARTESIAN PRODUCT and NATURAL JOINT.
'---------------------------------------------------------------

'--------------------------------------------------------------------------
'- SELECTION
'--------------------------------------------------------------------------
'- Purpose : Selects in a table all the data satisfying boolean expressions
'-           The result is a table positioned at the given range
'- Input   : the table,
'-           the constraints (a row of boolean expressions),
'-           the constraints parameters :
'-           first row : the columns index where to fetch the data
'-           second row : the parameters values
'-           the destination range (upper left corner) !It musn't be in table!
'- Output  : the result table (with no header), Nothing if the result is empty
'--------------------------------------------------------------------------
Public Function LT_Selection(T As LogTable, _
                             constraints As Range, parameters As Range, _
                             destination As Range) As LogTable
    Dim i As Integer, j As Integer, R As Integer, c As Integer
    Dim data As Range
    Dim res As Boolean
    
    If T.IsEmpty Then
        Set LT_Selection = Nothing
        Exit Function
    End If
    
    R = 0 'Row counter
    c = T.colCount
    Set data = T.data
    For i = 1 To T.rowCount
        'Assigns the parameters with the current row
        For j = 1 To parameters.Columns.count
            parameters.Cells(2, j).Value2 = data.Cells(i, parameters.Cells(1, j).Value2).Value2
        Next j
        'and looks if it returns true for all constraints
        res = True: j = 1
        Do While res And j <= constraints.Columns.count
            res = res And constraints.Cells(1, j).Value2
            j = j + 1
        Loop
        If res Then
            'Copies the row in the destination range
            With destination.Offset(R, 0)
                .Worksheet.Range(.Cells(1, 1), .Cells(1, c)).Value2 = data.rows(i).Value2
            End With
            R = R + 1
        End If
    Next i
    'Builds the result table
    If R > 0 Then
        Set LT_Selection = New LogTable
        LT_Selection.SetRange destination.Worksheet.Range(destination, destination.Offset(R - 1, c - 1)), False
    Else
        Set LT_Selection = Nothing
    End If
End Function

'--------------------------------------------------------------------------
'- INTERSECTION
'--------------------------------------------------------------------------
'- Purpose : Intersects a table whith a mask
'-           The result is a table positioned at the given range
'- Input   : the table, the mask table, the column to apply the intersection,
'-           the destination range (upper left corner) !It musn't be in table!,
'-           flag saying if the table or the mask are sorted (true),
'-           the sort order (xlAscending, xlDescending)
'- Output  : the result table (with no header), Nothing if the result is empty
'-           ! the sheet containing the result must be unprotected !
'- Time    : bigger table sorted -> table * log(table), else -> table^2
'--------------------------------------------------------------------------
Public Function LT_Intersection(T As LogTable, mask As LogTable, col As Integer, _
                                destination As Range, _
                                Optional tSorted As Boolean = True, Optional tSortOrder As Integer = xlAscending, _
                                Optional mSorted As Boolean = True, Optional mSortorder As Integer = xlAscending) As LogTable
    Dim i As Integer, R As Integer, c As Integer
    Dim m As Range, d As Range, res As Range
    
    If T.IsEmpty Or mask.IsEmpty Then
        Set LT_Intersection = Nothing
        Exit Function
    End If
    
    Set m = mask.data
    Set d = T.data
    R = 0 'Row counter
    c = T.colCount
    
    If tSorted Then
        For i = 1 To mask.rowCount
            'Looks if the current mask value is in table
            Set res = T.FindDichotomy(m.Cells(i, col).Value2, col, tSortOrder)
            If Not res Is Nothing Then
                'Copies the row in the destination range
                With destination.Offset(R, 0)
                    .Worksheet.Range(.Cells(1, 1), .Cells(1, c)).Value2 = Intersect(res.EntireRow, d).Value2
                End With
                R = R + 1
            End If
        Next i
    Else
        If mSorted Then
            For i = 1 To T.rowCount
                'Looks if the current table value is in mask
                Set res = mask.FindDichotomy(d.Cells(i, col).Value2, col, mSortorder)
                If Not res Is Nothing Then
                    'Copies the row in the destination range
                    With destination.Offset(R, 0)
                        .Worksheet.Range(.Cells(1, 1), .Cells(1, c)).Value2 = d.rows(i).Value2
                    End With
                    R = R + 1
                End If
            Next i
        Else
            For i = 1 To T.rowCount
                'Looks if the current table value is in mask
                Set res = mask.FindLinear(d.Cells(i, col).Value2, col)
                If Not res Is Nothing Then
                    'Copies the row in the destination range
                    With destination.Offset(R, 0)
                        .Worksheet.Range(.Cells(1, 1), .Cells(1, c)).Value2 = d.rows(i).Value2
                    End With
                    R = R + 1
                End If
            Next i
        End If
    End If
    
    'Builds the result table
    If R > 0 Then
        Set LT_Intersection = New LogTable
        LT_Intersection.SetRange destination.Worksheet.Range(destination, destination.Offset(R - 1, c - 1)), False
        If tSorted And Not (mSorted And tSortOrder = mSortorder) Then
            LT_Intersection.Sort col, tSortOrder
        End If
    Else
        Set LT_Intersection = Nothing
    End If
End Function

'--------------------------------------------------------------------------
'- UNION
'--------------------------------------------------------------------------
'- Purpose : Builds the union from a table and from complementary data
'-           The result is a table positioned at the given range,
'-           with only once the data existing in the two tables
'- Input   : the table, the complements, the column to apply the union,
'-           the destination range (upper left corner) !It musn't be in table!,
'-           flag saying if the table or the complement are sorted (true),
'-           the sort order (xlAscending, xlDescending)
'- Output  : the result table (with no header), Nothing if the result is empty
'-           ! the sheet containing the result must be unprotected !
'- Time    : table and complement sorted -> complement + table
'-           table sorted -> complement * log(table)
'-           complement sorted -> complement * table
'--------------------------------------------------------------------------
Public Function LT_Union(T As LogTable, complement As LogTable, col As Integer, _
                         destination As Range, _
                         Optional tSorted As Boolean = True, Optional tSortOrder As Integer = xlAscending, _
                         Optional cSorted As Boolean = True, Optional cSortorder As Integer = xlAscending) As LogTable
    Dim compl As LogTable
    Dim R As Integer, c As Integer
    
    R = 0 'Row counter
    c = T.colCount
    If c = 0 Then
        Set LT_Union = Nothing
        Exit Function
    End If
    'Copies T to destination
    If Not T.IsEmpty Then
        R = T.rowCount
        With destination
            .Worksheet.Range(.Cells(1, 1), .Cells(R, c)).Value2 = T.data.Value2
        End With
    End If
    'Appends the complements which are not in T
    Set compl = LT_Complementary(T, complement, col, destination.Offset(R, 0), tSorted, tSortOrder, cSorted, cSortorder)
    If Not compl Is Nothing Then
        R = R + compl.rowCount
    End If
    'Builds the result table
    If R > 0 Then
        Set LT_Union = New LogTable
        LT_Union.SetRange destination.Worksheet.Range(destination, destination.Offset(R - 1, c - 1)), False
        If tSorted Then
            LT_Union.Sort col, tSortOrder
        End If
    Else
        Set LT_Union = Nothing
    End If
End Function

'--------------------------------------------------------------------------
'- CONCATENATION
'--------------------------------------------------------------------------
'- Purpose : Appends two tables
'-           The result is a table positioned at the given range
'- Input   : the two tables, the destination range (upper left corner)
'-           flag saying if the first table is sorted
'-           the sort order and the sorted column
'-           !It musn't be in one of the two tables!
'- Output  : the result table (with no header), Nothing if the result is empty
'--------------------------------------------------------------------------
Public Function LT_Concatenation(t1 As LogTable, t2 As LogTable, _
                                 destination As Range, _
                                 Optional t1Sorted As Boolean = True, Optional t1SortOrder As Integer = xlAscending, Optional col As Integer = 1) As LogTable
                              
    Dim r1 As Integer, r2 As Integer, c As Integer
    r1 = 0: r2 = 0 'Row counter
    c = t1.colCount
    If c = 0 Then
        Set LT_Concatenation = Nothing
        Exit Function
    End If
    'Copies T1 to destination
    If Not t1.IsEmpty Then
        r1 = t1.rowCount
        With destination
            .Worksheet.Range(.Cells(1, 1), .Cells(r1, c)).Value2 = t1.data.Value2
        End With
    End If
    'Appends T2 to T1
    If Not t2.IsEmpty Then
        r2 = t2.rowCount
        With destination.Offset(r1, 0)
            .Worksheet.Range(.Cells(1, 1), .Cells(r2, c)).Value2 = t2.data.Value2
        End With
    End If
    'Builds the result table
    If (r1 + r2) > 0 Then
        Set LT_Concatenation = New LogTable
        LT_Concatenation.SetRange destination.Worksheet.Range(destination, destination.Offset((r1 + r2) - 1, c - 1)), False
        If t1Sorted Then
            LT_Concatenation.Sort col, t1SortOrder
        End If
    Else
        Set LT_Concatenation = Nothing
    End If
End Function

'--------------------------------------------------------------------------
'- COMPLEMENTARY
'--------------------------------------------------------------------------
'- Purpose : Builds the complementary from a table in a given universe
'-           The result is a table positioned at the given range
'- Input   : the table, the universe, the column to apply the complementary,
'-           the destination range (upper left corner) !It musn't be in table!,
'-           flag saying if the table or the universe are sorted (true),
'-           the sort order (xlAscending, xlDescending)
'- Output  : the result table (with no header), Nothing if the result is empty
'-           ! the sheet containing the result must be unprotected !
'-           the result table respects the universe order
'- Time    : table and universe sorted -> universe + table
'-           table sorted -> universe * log(table)
'-           universe sorted -> universe * table
'--------------------------------------------------------------------------
Public Function LT_Complementary(T As LogTable, universe As LogTable, col As Integer, _
                                 destination As Range, _
                                 Optional tSorted As Boolean = True, Optional tSortOrder As Integer = xlAscending, _
                                 Optional uSorted As Boolean = True, Optional uSortorder As Integer = xlAscending) As LogTable
    Dim i As Integer, j As Integer, aj As Integer, bj As Integer
    Dim R As Integer, c As Integer
    Dim u As Range, d As Range, res As Range
    
    R = 0 'Row counter
    c = T.colCount
    If (universe.IsEmpty) Or (c = 0) Then
        Set LT_Complementary = Nothing
        Exit Function
    End If
    
    Set u = universe.data
    Set d = T.data
    'if T is empty, return universe
    If T.IsEmpty Then
        R = universe.rowCount
        With destination
            .Worksheet.Range(.Cells(1, 1), .Cells(R, c)).Value2 = u.Value2
        End With
    'if T is not empty, return complementary of T in universe
    Else
        If tSorted Then
            If uSorted Then
                If tSortOrder = uSortorder Then
                    aj = 0: bj = 1
                Else
                    aj = T.rowCount + 1: bj = -1
                End If
                i = 1: j = 1
                Do While i <= universe.rowCount And j <= T.rowCount
                    Do While (smaller(u.Cells(i, col), d.Cells(aj + bj * j, col)) And uSortorder = xlAscending) Or _
                             (greater(u.Cells(i, col), d.Cells(aj + bj * j, col)) And uSortorder = xlDescending)
                        
                        'Copies the row in the destination range
                        With destination.Offset(R, 0)
                            .Worksheet.Range(.Cells(1, 1), .Cells(1, c)).Value2 = u.rows(i).Value2
                        End With
                        R = R + 1
                        i = i + 1
                        If i > universe.rowCount Then
                            Exit Do
                        End If
                    Loop
                    If i > universe.rowCount Then
                        Exit Do
                    End If
                    Do While equal(u.Cells(i, col), d.Cells(aj + bj * j, col))
                        i = i + 1
                        If i > universe.rowCount Then
                            Exit Do
                        End If
                    Loop
                    If i > universe.rowCount Then
                        Exit Do
                    End If
                    Do While (greater(u.Cells(i, col), d.Cells(aj + bj * j, col)) And uSortorder = xlAscending) Or _
                             (smaller(u.Cells(i, col), d.Cells(aj + bj * j, col)) And uSortorder = xlDescending)
                    
                        j = j + 1
                        If j > T.rowCount Then
                            Exit Do
                        End If
                    Loop
                Loop
                Do While i <= universe.rowCount
                    'Copies the row in the destination range
                    With destination.Offset(R, 0)
                        .Worksheet.Range(.Cells(1, 1), .Cells(1, c)).Value2 = u.rows(i).Value2
                    End With
                    R = R + 1
                    i = i + 1
                Loop
            Else
                For i = 1 To universe.rowCount
                    'Looks if the current value in universe is in table
                    Set res = T.FindDichotomy(u.Cells(i, col).Value2, col, tSortOrder)
                    If res Is Nothing Then
                        'Copies the row in the destination range
                        With destination.Offset(R, 0)
                            .Worksheet.Range(.Cells(1, 1), .Cells(1, c)).Value2 = u.rows(i).Value2
                        End With
                        R = R + 1
                    End If
                Next i
            End If
        Else
            For i = 1 To universe.rowCount
                'Looks if the current value in universe is in table
                Set res = T.FindLinear(u.Cells(i, col).Value2, col)
                If res Is Nothing Then
                    'Copies the row in the destination range
                    With destination.Offset(R, 0)
                        .Worksheet.Range(.Cells(1, 1), .Cells(1, c)).Value2 = u.rows(i).Value2
                    End With
                    R = R + 1
                End If
            Next i
        End If
    End If
    'Builds the result table
    If R > 0 Then
        Set LT_Complementary = New LogTable
        LT_Complementary.SetRange destination.Worksheet.Range(destination, destination.Offset(R - 1, c - 1)), False
    Else
        Set LT_Complementary = Nothing
    End If
End Function

'--------------------------------------------------------------------------
'- CARTESIAN PRODUCT
'--------------------------------------------------------------------------
'- Purpose : Returns the cartesian product from two tables
'-           The result is a table positioned at the given range
'- Input   : the two tables,
'-           the destination range (upper left corner) !It mustn't be in table!
'- Output  : the result table (with no header), Nothing if the result is empty
'-           result.ColCount = T1.ColCount + T2.ColCount
'--------------------------------------------------------------------------
Public Function LT_CartProduct(t1 As LogTable, t2 As LogTable, destination As Range) As LogTable
    Dim r1 As Integer, c1 As Integer
    Dim r2 As Integer, c2 As Integer
    Dim i As Integer, j As Integer, k As Integer
    Dim data1 As Range, data2 As Range
    
    If t1.IsEmpty Or t2.IsEmpty Then
        Set LT_CartProduct = Nothing
        Exit Function
    End If
    
    'Cartesian product
    r1 = t1.rowCount: c1 = t1.colCount
    r2 = t2.rowCount: c2 = t2.colCount
    Set data1 = t1.data
    Set data2 = t2.data
    k = 0
    For i = 1 To r1
        For j = 1 To r2
            With destination.Offset(k, 0)
                'Copies T1[i]
                .Worksheet.Range(.Cells(1, 1), .Cells(1, c1)).Value2 = data1.rows(i).Value2
                'Copies T2[j]
                .Worksheet.Range(.Cells(1, c1 + 1), .Cells(1, c1 + c2)).Value2 = data2.rows(j).Value2
            End With
            k = k + 1
        Next j
    Next i
    
    'Builds the result
    Set LT_CartProduct = New LogTable
    LT_CartProduct.SetRange destination.Worksheet.Range(destination, destination.Offset(k - 1, c1 + c2 - 1)), False
End Function

'--------------------------------------------------------------------------
'- NATURAL JOINT
'--------------------------------------------------------------------------
'- Purpose : Returns the natural joint from two tables
'-           The result is a table positioned at the given range
'- Input   : the two tables, the columns to join,
'-           the destination range (upper left corner) !It mustn't be in table!
'-           flag saying if the tables are sorted (true),
'-           the sort order (xlAscending, xlDescending)
'- Output  : the result table (with no header), Nothing if the result is empty
'-           result.ColCount = T1.ColCount + T2.ColCount
'- Time    : both tables sorted -> T1 + T2, else T1 * T2
'--------------------------------------------------------------------------
Public Function LT_NatJoint(t1 As LogTable, col1 As Integer, _
                            t2 As LogTable, col2 As Integer, _
                            destination As Range, _
                            Optional t1Sorted As Boolean = True, Optional t1SortOrder As Integer = xlAscending, _
                            Optional t2Sorted As Boolean = True, Optional t2Sortorder As Integer = xlAscending) As LogTable
    Dim r1 As Integer, c1 As Integer
    Dim r2 As Integer, c2 As Integer
    Dim i As Integer, j As Integer, k As Integer, l As Integer
    Dim aj As Integer, bj As Integer
    Dim data1 As Range, data2 As Range
    Dim v As Range
    
    If t1.IsEmpty Or t2.IsEmpty Then
        Set LT_NatJoint = Nothing
        Exit Function
    End If
    
    'Natural joint
    r1 = t1.rowCount: c1 = t1.colCount
    r2 = t2.rowCount: c2 = t2.colCount
    Set data1 = t1.data
    Set data2 = t2.data
    k = 0
    
    If t1Sorted And t2Sorted Then
        If t1SortOrder <> t2Sortorder Then
            aj = t2.rowCount + 1: bj = -1
        Else
            aj = 0: bj = 1
        End If
        i = 1: j = 1
        Do While i <= t1.rowCount And j <= t2.rowCount
            Do While (smaller(data1.Cells(i, col1), data2.Cells(aj + bj * j, col2)) And t1SortOrder = xlAscending) Or _
                     (greater(data1.Cells(i, col1), data2.Cells(aj + bj * j, col2)) And t1SortOrder = xlDescending)
                i = i + 1
                If i > t1.rowCount Then
                    Exit Do
                End If
            Loop
            If i > t1.rowCount Then
                Exit Do
            End If
            Do
                l = 0
                Do While equal(data1.Cells(i, col1), data2.Cells(aj + bj * j, col2))
                    With destination.Offset(k, 0)
                        'Copies T1[i]
                        .Worksheet.Range(.Cells(1, 1), .Cells(1, c1)).Value2 = data1.rows(i).Value2
                        'Copies T2[j]
                        .Worksheet.Range(.Cells(1, c1 + 1), .Cells(1, c1 + c2)).Value2 = data2.rows(aj + bj * j).Value2
                    End With
                    k = k + 1: l = l + 1: i = i + 1
                    If i > t1.rowCount Then
                        Exit Do
                    End If
                Loop
                j = j + 1: i = i - l
                If j > t2.rowCount Then
                    Exit Do
                End If
                If Not equal(data1.Cells(i, col1), data2.Cells(aj + bj * j, col2)) Then
                    i = i + l
                    Exit Do
                End If
            Loop
            If i > t1.rowCount Or j > t2.rowCount Then
                Exit Do
            End If
            Do While (greater(data1.Cells(i, col1), data2.Cells(aj + bj * j, col2)) And t1SortOrder = xlAscending) Or _
                     (smaller(data1.Cells(i, col1), data2.Cells(aj + bj * j, col2)) And t1SortOrder = xlDescending)
                j = j + 1
                If j > t2.rowCount Then
                    Exit Do
                End If
            Loop
        Loop
    Else
        For i = 1 To r1
            Set v = data1.Cells(i, col1)
            For j = 1 To r2
                If equal(v, data2.Cells(j, col2)) Then
                    With destination.Offset(k, 0)
                        'Copies T1[i]
                        .Worksheet.Range(.Cells(1, 1), .Cells(1, c1)).Value2 = data1.rows(i).Value2
                        'Copies T2[j]
                        .Worksheet.Range(.Cells(1, c1 + 1), .Cells(1, c1 + c2)).Value2 = data2.rows(j).Value2
                    End With
                    k = k + 1
                End If
            Next j
        Next i
    End If
    
    'Builds the result
    If k > 0 Then
        Set LT_NatJoint = New LogTable
        LT_NatJoint.SetRange destination.Worksheet.Range(destination, destination.Offset(k - 1, c1 + c2 - 1)), False
    Else
        Set LT_NatJoint = Nothing
    End If
End Function

'--------------------------------------------------------------------------
'- DOUBLES DELETION
'--------------------------------------------------------------------------
'- Purpose : Returns a new table without any doubles (in a column)
'-           The result is a table positioned at the given range
'- Input   : a table, the column to apply the deletion,
'-           a flag saying if the table is sorted (true)
'-           the destination range (upper left corner) !It mustn't be in table!
'- Output  : the result table (with no header), Nothing if the result is empty
'-           ! if the table was not sorted, it will be sorted
'-           ! with order xlAscending.
'-           ! the result is sorted like the table
'--------------------------------------------------------------------------
Public Function LT_DoublesDeletion(T As LogTable, col As Integer, _
                                   destination As Range, _
                                   Optional sorted = True) As LogTable
    Dim R As Integer, c As Integer
    Dim i As Integer, k As Integer
    Dim v As Range
    Dim data As Range
    
    If T.IsEmpty Then
        Set LT_DoublesDeletion = Nothing
        Exit Function
    End If
        
    If Not sorted Then
        T.Sort col
    End If
    Set data = T.data
    R = T.rowCount: c = T.colCount
    k = 0: i = 1
    Do While i <= R
        Set v = data.Cells(i, col)
        
        'Copies T[i]
        With destination.Offset(k, 0)
            .Worksheet.Range(.Cells(1, 1), .Cells(1, c)).Value2 = data.rows(i).Value2
        End With
        k = k + 1
        
        'Skips the doubles
        Do While equal(data.Cells(i, col), v)
            i = i + 1
            If i > R Then
                Exit Do
            End If
        Loop
    Loop
    
    'Builds the result
    Set LT_DoublesDeletion = New LogTable
    LT_DoublesDeletion.SetRange destination.Worksheet.Range(destination, destination.Offset(k - 1, c - 1)), False
End Function

'- Utilities

Private Function smaller(v1 As Range, v2 As Range) As Boolean
   Dim NAv1 As Boolean, NAv2 As Boolean
   
   NAv1 = Application.WorksheetFunction.IsNA(v1)
   NAv2 = Application.WorksheetFunction.IsNA(v2)
   If NAv1 And NAv2 Then
      smaller = False
   ElseIf NAv1 Or NAv2 Then
      smaller = NAv2
   Else
      smaller = (v1.Value2 < v2.Value2)
   End If
End Function

Private Function greater(v1 As Range, v2 As Range) As Boolean
   Dim NAv1 As Boolean, NAv2 As Boolean
   
   NAv1 = Application.WorksheetFunction.IsNA(v1)
   NAv2 = Application.WorksheetFunction.IsNA(v2)
   If NAv1 And NAv2 Then
      greater = False
   ElseIf NAv1 Or NAv2 Then
      greater = NAv1
   Else
      greater = (v1.Value2 > v2.Value2)
   End If
End Function

Private Function equal(v1 As Range, v2 As Range) As Boolean
   Dim NAv1 As Boolean, NAv2 As Boolean
   
   NAv1 = Application.WorksheetFunction.IsNA(v1)
   NAv2 = Application.WorksheetFunction.IsNA(v2)
   If NAv1 And NAv2 Then
      equal = True
   ElseIf NAv1 Or NAv2 Then
      equal = False
   Else
      equal = (v1.Value2 = v2.Value2)
   End If
End Function

