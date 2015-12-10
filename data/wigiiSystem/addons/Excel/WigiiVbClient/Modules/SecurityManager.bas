Attribute VB_Name = "SecurityManager"
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
'- SECURITY MANAGER
'----------------------------------------------------------------------------
'- Author  : Camille WEBER
'- Update  : wed 2-23-2000
'- Version : 1.0
'----------------------------------------------------------------------------
'- This package manages the workbook security :
'- We can define different modes and if a sheet is accessible for
'- a mode.
'- The mode 0 is the NOPROTECTION mode : the workbook is unprotected,
'- all sheets are unprotected and visible.
'- For the other modes : the workbook is protected and the sheets are
'- protected according to the access control table.
'- A sheet is accessible => the sheet is visible and protected
'- A sheet is not accessible => the sheet is not visible and not protected
'- The security manager has two tables :
'- the mode table which memorizes information about modes :
'- the number, the name, the password and the definition
'- the access control table which memorizes for each mode and sheet
'- if the given sheet is accessible.
'-
'- Before using the security manager don't forget to call SM_Initialize
'- with the mode table descriptor and the access control table descriptor.
'- ! In the mode table, the numbers must be in ascending order and adjacent
'- ! 0, 1, 2, 3, ...
'- ! In the access control table, the sheets must be in ascending order
'- ! The sheets which are not registered into the security manager are under the
'- ! programmer's responsability
'- ! All the informations contained in the mode table and access control table
'- ! are defined statically by the programmer.
'----------------------------------------------------------------------------

'- (currentMode + 1); if modePlusOne = 0 then currentMode = NOMODE
Private modePlusOne As Integer
'- the mode table
Private modeTable As PhyTable
'- the access control table
Private accessControlTable As PhyTable

'- Mode constant : NO MODE
Public Const SM_NOMODE As Integer = -1

'- Initialization

'-------------------------------------------------------------
'- SM_INITIALIZE
'-------------------------------------------------------------
'- Purpose : Initializes the security manager
'- Input   : the mode table descriptor,
'-           the access control table descriptor
'- Output  : the current mode is NOMODE
'-------------------------------------------------------------
Public Sub SM_Initialize(modeTD As Range, _
                         accessControlTD As Range)
   modePlusOne = SM_NOMODE + 1
   Set modeTable = New PhyTable
   Set accessControlTable = New PhyTable
   modeTable.Load modeTD
   accessControlTable.Load accessControlTD
End Sub

'- Public properties

'-------------------------------------------------------------
'- SM_MODE
'-------------------------------------------------------------
'- Purpose : Returns the current mode
'- Input   : -
'- Output  : the current mode
'-------------------------------------------------------------
Public Property Get SM_Mode() As Integer
   SM_Mode = modePlusOne - 1
End Property

'-------------------------------------------------------------
'- SM_MODENAME
'-------------------------------------------------------------
'- Purpose : Gets or sets the current mode name
'- Input   : -
'- Output  : the name / error number :
'-           0 : no error
'-          -1 : name already used
'-------------------------------------------------------------
Public Property Get SM_ModeName() As String
   SM_ModeName = modeTable.LogTable.Data.Cells(modePlusOne, 2).Value2
End Property
Public Function SM_ChangeModeName(newName As String) As Integer
   Dim res As Range
   Set res = modeTable.LogTable.FindLinear(newName, 2, True)
   If Not res Is Nothing Then
      If res.Offset(0, -1).Value2 = modePlusOne - 1 Then
         SM_ChangeModeName = 0
      Else
         SM_ChangeModeName = -1
      End If
   Else
      modeTable.LogTable.Data.Cells(modePlusOne, 2).Value2 = newName
      SM_ChangeModeName = 0
   End If
End Function

'-------------------------------------------------------------
'- SM_MODEPASSWORD
'-------------------------------------------------------------
'- Purpose : Gets or sets the current mode password
'- Input   : the new password
'- Output  : the password
'-           ! you can't change the password from NOMODE !
'-------------------------------------------------------------
Public Property Get SM_ModePassword() As String
   SM_ModePassword = modeTable.LogTable.Data.Cells(modePlusOne, 3).Value2
End Property
Public Property Let SM_ModePassword(Password As String)
   If modePlusOne - 1 <> SM_NOMODE Then
      modeTable.LogTable.Data.Cells(modePlusOne, 3).Value2 = Password
   End If
End Property

'-------------------------------------------------------------
'- SM_MODEDEFINITION
'-------------------------------------------------------------
'- Purpose : Returns the current mode definition
'- Input   : -
'- Output  : the definition or "" if NOMODE
'-------------------------------------------------------------
Public Property Get SM_ModeDefinition() As String
   SM_ModeDefinition = modeTable.LogTable.Data.Cells(modePlusOne, 4).Value2
End Property

'- Public methods

'-------------------------------------------------------------
'- SM_CHANGEMODE
'-------------------------------------------------------------
'- Purpose : Changes the current mode
'- Input   : the new mode, the new mode password
'- Output  : the error number :
'-           0 : no error
'-          -1 : invalid mode
'-          -2 : invalid password
'-      ! nothing is done if the new mode = the current mode !
'-------------------------------------------------------------
Public Function SM_ChangeMode1(newMode As String, Password As String) As Integer
   Dim res As Range
   
   Set res = modeTable.LogTable.FindLinear(newMode, 2, False)
   If res Is Nothing Then
      SM_ChangeMode1 = -1
   Else
      SM_ChangeMode1 = SM_ChangeMode2(res.Offset(0, -1).Value2, Password)
   End If
End Function
Public Function SM_ChangeMode2(newMode As Integer, Password As String) As Integer
   Dim s As Worksheet
   Dim i As Integer, n As Integer
   
   If newMode = modePlusOne - 1 Then
      SM_ChangeMode2 = 0 'no error (same mode)
      Exit Function
   End If
   If newMode < 0 Or newMode >= modeTable.rowCount Then
      SM_ChangeMode2 = -1 'invalid mode
      Exit Function
   End If
   If modeTable.LogTable.Data.Cells(newMode + 1, 3).Value2 <> Password Then
      SM_ChangeMode2 = -2 'invalid password
      Exit Function
   End If
   
   unprotectWorkbook
   n = accessControlTable.rowCount
   With accessControlTable.LogTable.Data
      For i = 1 To n
         Set s = Worksheets(.Cells(i, 1).Value2)
         unprotectSheet s
         If newMode > 0 Then
            'sheet accessible => protection and visible
            If .Cells(i, newMode + 1).Value2 Then
               protectSheet s
               s.Visible = True
            'sheet not accessible => no protection and not visible
            Else
               s.Visible = False
            End If
         'NOPROTECTION mode => no protection and visible
         Else
            s.Visible = True
         End If
      Next i
   End With
   
   'mode <> NOPROTECTION mode => protect workbook
   If newMode > 0 Then
      protectWorkbook
   End If
   
   'changes mode
   modePlusOne = newMode + 1
   SM_ChangeMode2 = 0
End Function

'-------------------------------------------------------------
'- SM_UNPROTECTSHEET
'-------------------------------------------------------------
'- Purpose : Unprotects a sheet wich was protected by the SM
'- Input   : the sheet
'- Output  : nothing is done if the sheet is not protected,
'-           or if the sheet is not registered in the SM
'-------------------------------------------------------------
Public Sub SM_UnprotectSheet(s As Worksheet)
   Dim r As Range
   
   If Not isSheetProtected(s) Then
      Exit Sub
   End If
   Set r = accessControlTable.LogTable.FindDichotomy(s.Name, 1)
   If Not r Is Nothing Then
      unprotectSheet s
   End If
End Sub

'-------------------------------------------------------------
'- SM_PROTECTSHEET
'-------------------------------------------------------------
'- Purpose : Protects a sheet which was previously unprotected
'- Input   : the sheet
'- Output  : nothing is done if the sheet is protected,
'-           or if the sheet is not accessible in this mode,
'-           or if the sheet is not registered in the SM
'-------------------------------------------------------------
Public Sub SM_ProtectSheet(s As Worksheet)
   Dim r As Range
   
   If isSheetProtected(s) Then
      Exit Sub
   End If
   Set r = accessControlTable.LogTable.FindDichotomy(s.Name, 1)
   If Not r Is Nothing And modePlusOne - 1 > 0 Then
      If r.Offset(0, modePlusOne - 1).Value2 Then
         protectSheet s
      End If
   End If
End Sub

'-------------------------------------------------------------
'- SM_ISSHEETPROTECTED
'-------------------------------------------------------------
'- Purpose : Looks if a sheet is protected
'- Input   : the sheet
'- Output  : true or false
'-------------------------------------------------------------
Public Function SM_IsSheetProtected(s As Worksheet) As Boolean
   SM_IsSheetProtected = isSheetProtected(s)
End Function

'- Private methods

Private Property Get SM_ProtectionPassword() As String
   'NOPROTECTION mode password :
   SM_ProtectionPassword = modeTable.LogTable.Data.Cells(1, 3).Value2
End Property

Private Sub protectSheet(s As Worksheet)
   s.protect SM_ProtectionPassword, True, True, True, False
End Sub
Private Sub unprotectSheet(s As Worksheet)
   On Error GoTo errorHandler
   s.unprotect SM_ProtectionPassword
   Exit Sub
errorHandler:
   With Err
      .Raise .Number, description:=.description & vbNewLine & "[error on sheet " & s.Name & "]"
   End With
End Sub
Private Function isSheetProtected(s As Worksheet) As Boolean
   isSheetProtected = s.ProtectDrawingObjects And _
                      s.ProtectContents And _
                      s.ProtectScenarios And _
                     (Not s.ProtectionMode)
End Function

Private Sub protectWorkbook()
   ThisWorkbook.protect SM_ProtectionPassword, True, False
End Sub
Private Sub unprotectWorkbook()
   On Error GoTo errorHandler
   ThisWorkbook.unprotect SM_ProtectionPassword
   Exit Sub
errorHandler:
   With Err
      .Raise .Number, description:=.description & vbNewLine & "[error on workbook " & ThisWorkbook.Name & "]"
   End With
End Sub
