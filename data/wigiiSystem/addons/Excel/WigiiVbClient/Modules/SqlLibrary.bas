Attribute VB_Name = "SqlLibrary"
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
'- SQL LIBRARY
'----------------------------------------------------------------------------
'- Author  : Camille WEBER
'- Update  : 24.11.2009
'- Version : 1.0
'----------------------------------------------------------------------------
'- Manages a cache of SQL queries read from sql text files.
'----------------------------------------------------------------------------
Private sqlCache As Dictionary

Public Function SqlLib_getSqlQuery(sSqlFileName As String, Optional cacheIt As Boolean = True) As String
   If cacheIt Then
      Dim sqlC As Dictionary
      Set sqlC = getSqlCache()
      If Not sqlC.Exists(sSqlFileName) Then
         sqlC.Add sSqlFileName, readSqlFile(sSqlFileName)
      End If
      SqlLib_getSqlQuery = sqlC(sSqlFileName)
   Else
      SqlLib_getSqlQuery = readSqlFile(sSqlFileName)
   End If
End Function

Private Function getSqlCache() As Dictionary
   If sqlCache Is Nothing Then
      Set sqlCache = New Dictionary
   End If
   Set getSqlCache = sqlCache
End Function

Private Function readSqlFile(sSqlFileName As String) As String
    On Error GoTo errorHandler
    Dim fso As Scripting.FileSystemObject
    Set fso = New Scripting.FileSystemObject
    Dim sqlFile As Scripting.TextStream
                        
    Set sqlFile = fso.OpenTextFile(sSqlFileName, ForReading, False, TristateUseDefault)
    readSqlFile = sqlFile.ReadAll
    sqlFile.Close
    Exit Function
errorHandler:
   If Not sqlFile Is Nothing Then
      sqlFile.Close
   End If
   Err.Raise Err.Number
End Function
