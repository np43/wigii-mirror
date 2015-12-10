Attribute VB_Name = "ExecutionSink"
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

Option Explicit
'----------------------------------------------------------------------------
'- EXECUTION SINK
'----------------------------------------------------------------------------
'- Author  : Camille WEBER
'- Update  : mon 2010-06-07
'- Version : 1.0
'----------------------------------------------------------------------------
'Manages an execution sink. This implementation currently uses a log file.
'----------------------------------------------------------------------------
Private ES_logFso As Scripting.FileSystemObject
Private ES_logFileName As String
Private ES_logFileFolder As String
Private ES_appendLogFile As Boolean
Private ES_outputToLogFile As Boolean
Private ES_moduleFilter As Dictionary

'Execution Sink log levels
Public Enum ES_LogLevels
   ESLL_Trace = 1
   ESLL_Info = 2
End Enum

'INITIALIZATION

'Initializes the Execution Sink
Public Sub ES_initialize()
   ES_outputToLogFile = False
   Set ES_moduleFilter = New Dictionary
End Sub

'Initializes the Execution Sink and sets output in a log file.
'Defaults to a log file with the same name of current workbook and in the same folder
'Default creates a new log file, no append.
Public Sub ES_initializeAndSetOutputToLogFile(Optional logFileName As String = "", Optional folderPath As String = "", Optional append As Boolean = False)
   ES_initialize
   ES_setOutputToLogFile logFileName, folderPath, append
End Sub


'DEPENDENCY INJECTION

'Adds a module filter
Public Sub ES_addModuleFilter(module As String, Optional method As String = "*", Optional logLevel As ES_LogLevels = ESLL_Trace)
   Dim k As String
   k = module & "." & method
   If ES_moduleFilter.Exists(k) Then
      ES_moduleFilter(k) = logLevel
   Else
      ES_moduleFilter.Add k, logLevel
   End If
End Sub

'Sets the output of the Execution Sink to a log file
'Defaults to a log file with the same name of current workbook and in the same folder
'Default creates a new log file, no append.
Public Sub ES_setOutputToLogFile(Optional logFileName As String = "", Optional folderPath As String = "", Optional append As Boolean = False)
   If logFileName <> "" Then
      ES_logFileName = logFileName
   Else
      ES_logFileName = ThisWorkbook.Name & ".log"
   End If
   If folderPath <> "" Then
      ES_logFileFolder = folderPath
   Else
      ES_logFileFolder = ThisWorkbook.Path
   End If
   ES_appendLogFile = append
   ES_outputToLogFile = True
   Set ES_logFso = Nothing
End Sub


'SERVICE IMPLEMENTATION

'Publishes the start of an operation
Public Sub ES_begin(module As String, method As String)
   If Not ES_isLogActive(module, method, ESLL_Trace) Then Exit Sub
   Dim msg As String
   msg = "BEGIN " & module & "." & method
   ES_logToFile msg
End Sub
'Publishes the end of an operation
Public Sub ES_end(module As String, method As String)
   If Not ES_isLogActive(module, method, ESLL_Trace) Then Exit Sub
   Dim msg As String
   msg = "END " & module & "." & method
   ES_logToFile msg
End Sub
'Publishes the end of an operation in case of error
Public Sub ES_endOnError(module As String, method As String, errNumber As Long, errDescription As String)
   If Not ES_isLogActive(module, method, ESLL_Trace) Then Exit Sub
   Dim msg As String
   msg = "END " & module & "." & method & " ON ERROR " & errNumber & ": " & errDescription
   ES_logToFile msg
End Sub
'Logs a message; for production trace only.
Public Sub ES_log(message As String, Optional module As String = "", Optional method As String = "")
   If Not ES_isLogActive(module, method, ESLL_Info) Then Exit Sub
   Dim msg As String
   If module <> "" Then
      msg = module
      If method <> "" Then
         msg = msg & "." & method
      End If
      msg = msg & ": "
   Else
      msg = ""
   End If
   msg = msg & message
   ES_logToFile msg
End Sub

'Returns true if the log is active for this module, method and level, else false
Private Function ES_isLogActive(module As String, method As String, level As ES_LogLevels) As Boolean
   Dim filterLevel As ES_LogLevels
   Dim filterDefined As Boolean
   
   filterDefined = False
   
   'global filter
   If ES_moduleFilter.Exists("*.*") Then
      filterLevel = ES_moduleFilter("*.*")
      filterDefined = True
   End If
   'module filter
   If ES_moduleFilter.Exists(module & ".*") Then
      filterLevel = ES_moduleFilter(module & ".*")
      filterDefined = True
   End If
   'method filter
   If ES_moduleFilter.Exists(module & "." & method) Then
      filterLevel = ES_moduleFilter(module & "." & method)
      filterDefined = True
   End If
   
   'checks log level
   If filterDefined Then
      ES_isLogActive = (filterLevel <= level)
   Else
      ES_isLogActive = False
   End If
End Function

'LOG FILE IMPLEMENTATION
Private Sub ES_logToFile(message As String)
   If Not ES_outputToLogFile Then Exit Sub
   Dim logFile As String
   logFile = ES_logFileFolder & "\" & ES_logFileName
   Dim out As Scripting.TextStream
   If ES_logFso Is Nothing Then
      Set ES_logFso = New Scripting.FileSystemObject
      Set out = ES_logFso.OpenTextFile(logFile, IIf(ES_appendLogFile, ForAppending, ForWriting), True)
   Else
      Set out = ES_logFso.OpenTextFile(logFile, ForAppending, True)
   End If
   out.WriteLine Format(Now(), "yyyy-MM-dd hh:mm:ss") & " " & message
   out.Close
   Set out = Nothing
End Sub

'UNIT TESTING

Private Sub ES_test()
   On Error GoTo errorHandler
   'ES_initializeAndSetOutputToLogFile append:=True
   ES_initializeAndSetOutputToLogFile append:=False
   ES_addModuleFilter "*", "*", ESLL_Trace
   'ES_addModuleFilter "ExecutionSink", "*", ESLL_Info
   ES_begin "ExecutionSink", "test"
   ES_log "a log message", "ExecutionSink", "test"
   ES_end "ExecutionSink", "test"
   Err.Raise 1000, "A vb error"
   Exit Sub
errorHandler:
   ES_endOnError "ExecutionSink", "test", Err.Number, Err.description
End Sub
