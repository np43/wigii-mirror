Attribute VB_Name = "LogTable_Factory"
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
Public Sub LTFact_Initialize(s As Worksheet, Optional textOnly As Boolean = False)
    With s
        .Cells.Delete
        Set FreeRow = .Cells(1, 1)
    End With
    UnboundedTableOpen = False
    If textOnly Then
        ' Don't auto format those cells
        s.Range("A:Z").NumberFormat = "@"
    End If
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
                table.Range.rows.EntireRow.Delete
                table.SetRange Nothing, False
            End If
        End If
    End If
End Sub


'------------------------------------------------------------------
'- LTFACT_CREATEFROMREGULARXML
'------------------------------------------------------------------
'- Purpose : Creates a log table from a "regular" XML document, meaning
'-           an XML document that contains children all of which
'-           contain the same tags in the same order. Row headings
'-           are discovered from the tags in the first element
'- Input   : the XML document
'- Output  : nothing in case of error or the table.
'------------------------------------------------------------------
Public Function LTFact_CreateFromRegXml(xDoc As MSXML2.DOMDocument) As LogTable
    Set LTFact_CreateFromRegXml = Nothing
    Dim dest As LogTable
    Dim numRows As Integer, numCols As Integer


    If xDoc Is Nothing Then
        Exit Function
    End If
   
    numRows = xDoc.ChildNodes(1).ChildNodes.length
    
    If numRows = 0 Then Exit Function
    
    numCols = xDoc.ChildNodes(1).ChildNodes(0).ChildNodes.length
    
    Set dest = LTFact_CreateBounded(numRows + 1, numCols, True)
    
    If dest Is Nothing Then Exit Function
    
    Dim rowDoc As MSXML2.IXMLDOMNode
    Dim headingDone As Boolean
    Dim element As MSXML2.IXMLDOMNode
    Dim currentColumn As Integer, currentRow As Integer
        
    headingDone = False
        
    For currentRow = 1 To numRows
        Set rowDoc = xDoc.ChildNodes(1).ChildNodes(currentRow - 1)
        
        For currentColumn = 1 To numCols
            Set element = rowDoc.ChildNodes(currentColumn - 1)
            If Not headingDone Then ' This is the first row - record headings
                dest.header.Cells(currentRow, currentColumn).value = element.nodeName
            End If
           
            dest.header.Cells(currentRow + 1, currentColumn).value = element.Text
        Next currentColumn
            
        headingDone = True
    Next currentRow
            
    Set LTFact_CreateFromRegXml = dest
End Function



