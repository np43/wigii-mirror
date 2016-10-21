Attribute VB_Name = "SecurityManager"
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
' Modified by Medair Sept 2016 to extend functionality to add, remove, and replace sheets to ThisWorkbook, set the tab colour,
' set the visibility. Also added generic function to test if a sheet exists. These functions need the ability to unprotect the workbook
' and this is the only place in the framework where it is permitted to unlock the workbook.

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
   SM_ModeName = modeTable.LogTable.data.Cells(modePlusOne, 2).Value2
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
      modeTable.LogTable.data.Cells(modePlusOne, 2).Value2 = newName
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
   SM_ModePassword = modeTable.LogTable.data.Cells(modePlusOne, 3).Value2
End Property
Public Property Let SM_ModePassword(Password As String)
   If modePlusOne - 1 <> SM_NOMODE Then
      modeTable.LogTable.data.Cells(modePlusOne, 3).Value2 = Password
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
   SM_ModeDefinition = modeTable.LogTable.data.Cells(modePlusOne, 4).Value2
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
   If modeTable.LogTable.data.Cells(newMode + 1, 3).Value2 <> Password Then
      SM_ChangeMode2 = -2 'invalid password
      Exit Function
   End If
   
   unprotectWorkbook
   n = accessControlTable.rowCount
   With accessControlTable.LogTable.data
      For i = 1 To n
         
         Set s = ThisWorkbook.Worksheets(.Cells(i, 1).Value2)
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
   Dim R As Range
   
   If Not isSheetProtected(s) Then
      Exit Sub
   End If
   Set R = accessControlTable.LogTable.FindDichotomy(s.name, 1)
   If Not R Is Nothing Then
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
   Dim R As Range
   
   If isSheetProtected(s) Then
   '   Exit Sub
   End If
   Set R = accessControlTable.LogTable.FindDichotomy(s.name, 1)
   If Not R Is Nothing And modePlusOne - 1 > 0 Then
      If R.Offset(0, modePlusOne - 1).Value2 Then
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
'-------------------------------------------------------------
'- SM_ADDSHEET
'-------------------------------------------------------------
'- Purpose : Programatically add a sheet to the workbook
'- Input   : The sheet name
'- Output  : The worksheet added, or the named sheet if it already exists
'-------------------------------------------------------------
Public Function SM_AddSheet(sheetName As String, Optional tabColour As Variant) As Worksheet
    Dim newSheet As Worksheet
        
    If SheetExists(sheetName) Then
       Set SM_AddSheet = ThisWorkbook.Sheets(sheetName)
       Exit Function
    End If
    
    Call unprotectWorkbook
    Set newSheet = ThisWorkbook.Sheets.Add(after:=ThisWorkbook.Sheets(ThisWorkbook.Sheets.count))
    newSheet.name = sheetName
    newSheet.Activate
    
    If Not IsMissing(tabColour) Then
        newSheet.Tab.Color = tabColour
    End If
    
    Call SM_ProtectSheet(newSheet) ' if it is a managed sheet we protect it
    Set SM_AddSheet = newSheet
    Call protectWorkbook
    
End Function

'-------------------------------------------------------------
'- SM_IMPORTSHEET
'-------------------------------------------------------------
'- Purpose : Programatically import a sheet to the workbook,
'            This overwrites a sheet if it already exists
'- Input   : The sheet name
'- Output  : The worksheet added
'-------------------------------------------------------------
Public Function SM_ImportSheet(sheet As Worksheet) As Worksheet
    Dim newSheet As Worksheet
    
    If Not SM_isManagedSheet(sheet.name) Then
        If SheetExists(sheet.name) Then
            Call SM_RemoveSheet(ThisWorkbook.Sheets(sheet.name))
        End If
    End If
    
    Call unprotectWorkbook
    sheet.Copy after:=ThisWorkbook.Sheets(ThisWorkbook.Sheets.count)
    Set newSheet = ThisWorkbook.Sheets(sheet.name)
    Call SM_ProtectSheet(newSheet)
    Call protectWorkbook
    
    Set SM_ImportSheet = newSheet

End Function
'-------------------------------------------------------------
'- SM_IMPORTSHEETS
'-------------------------------------------------------------
'- Purpose : Programatically import a set of sheets to thisWorkbook
'- Input   : The sheet names as an array of strings, the workbook to import from
'- Output  : The worksheet added, or the named sheet if it already exists
'-------------------------------------------------------------
Public Sub SM_ImportSheets(sheetNames() As String, fromWorkbook As Workbook)
    Dim newSheet As Worksheet
    Dim sheetName As Variant

    For Each sheetName In sheetNames()
        'overwrite if it is not managed
        If Not SM_isManagedSheet(CStr(sheetName)) Then
            If SheetExists(CStr(sheetName)) Then
                Call SM_RemoveSheet(ThisWorkbook.Sheets(CStr(sheetName)))
            End If
        End If
    Next
    
    Call unprotectWorkbook
    fromWorkbook.Sheets(sheetNames()).Copy after:=ThisWorkbook.Sheets(ThisWorkbook.Sheets.count)
   
    For Each sheetName In sheetNames()
        Call SM_ProtectSheet(ThisWorkbook.Sheets(CStr(sheetName))) ' if it is a managed sheet we protect it
    Next
    
    Call protectWorkbook
    
End Sub

'-------------------------------------------------------------
'- SM_REMOVESHEET
'-------------------------------------------------------------
'- Purpose : Programatically remove a sheet from the workbook
'- Input   : The worksheet
'-------------------------------------------------------------
Public Sub SM_RemoveSheet(sheet As Worksheet)
    Call unprotectWorkbook
    Application.DisplayAlerts = False
    sheet.Delete
    Application.DisplayAlerts = True
    Call protectWorkbook
End Sub

'-------------------------------------------------------------
'- SM_REMOVESHEETBYNAME
'-------------------------------------------------------------
'- Purpose : Programatically remove a sheet from the workbook
'- Input   : The worksheet name
'-------------------------------------------------------------
Public Sub SM_RemoveSheetByName(sheetName As String)
    If SheetExists(sheetName) Then
     Call unprotectWorkbook
        Application.DisplayAlerts = False
        ThisWorkbook.Sheets(sheetName).Delete
        Application.DisplayAlerts = True
        Call protectWorkbook
    End If
End Sub

'-------------------------------------------------------------
'- SM_REMOVEUNKNOWNSHEETS
'-------------------------------------------------------------
'- Purpose : Programatically remove a sheets that are not in the security table
'-------------------------------------------------------------
Public Sub SM_RemoveUnknownSheets()
   Dim sheet As Worksheet
   Dim sheetName As String
   Dim foundsheet As Range
   
   For Each sheet In ThisWorkbook.Sheets
       sheetName = sheet.name
       Set foundsheet = accessControlTable.LogTable.data.Find(sheetName, Lookat:=xlWhole)
       If foundsheet Is Nothing Then
           SM_RemoveSheet sheet
       End If
   Next
End Sub
'-------------------------------------------------------------
'- SM_ISMANAGEDSHEET
'-------------------------------------------------------------
'- Purpose : Returns if the sheet is known to the security manager
'-------------------------------------------------------------
Public Function SM_isManagedSheet(sheetName As String) As Boolean
    Dim foundsheet As Range
    Set foundsheet = accessControlTable.LogTable.data.Find(sheetName, Lookat:=xlWhole)
    If foundsheet Is Nothing Then
        SM_isManagedSheet = False
    Else
        SM_isManagedSheet = True
    End If
    
End Function

'-------------------------------------------------------------
'- SM_SHOWSHEET
'-------------------------------------------------------------
'- Purpose : Programatically show a sheet. This also makes the sheet protected
'- Input   : The worksheet to show
'-------------------------------------------------------------
Public Sub SM_ShowSheet(sheet As Worksheet)
    Call unprotectWorkbook
    With sheet
        If .Visible = xlSheetHidden Then
            .Visible = xlSheetVisible
        End If
        .Activate
    End With
    Call protectWorkbook
End Sub

'-------------------------------------------------------------
'- SM_HIDEHEET
'-------------------------------------------------------------
'- Purpose : Programatically hide a sheet. This also makes the sheet protected if managed
'- Input   : The worksheet to show
'-------------------------------------------------------------
Public Sub SM_HideSheet(sheet As Worksheet)
    Call unprotectWorkbook
    With sheet
        If .Visible = xlSheetVisible Then
            .Visible = xlSheetHidden
        End If
    End With
    Call protectWorkbook
End Sub

'- Private methods

Private Property Get SM_ProtectionPassword() As String
   'NOPROTECTION mode password :
   SM_ProtectionPassword = modeTable.LogTable.data.Cells(1, 3).Value2
End Property

Private Sub protectSheet(s As Worksheet)
   s.Protect SM_ProtectionPassword, True, True, True, False
End Sub
Private Sub unprotectSheet(s As Worksheet)
   If Not tcv_devmode Then On Error GoTo errorHandler
   s.Unprotect SM_ProtectionPassword
   Exit Sub
errorHandler:
   With Err
      .Raise .Number, Description:=.Description & vbNewLine & "[error on sheet " & s.name & "]"
   End With
End Sub
Public Function isSheetProtected(s As Worksheet) As Boolean
   isSheetProtected = s.ProtectDrawingObjects Or _
                      s.ProtectContents Or _
                      s.ProtectScenarios Or _
                     (Not s.ProtectionMode)
End Function

Public Sub SM_setTabColor(sheet As Worksheet, newcolor As Integer)
     unprotectWorkbook
     sheet.Tab.Color = newcolor
    protectWorkbook
End Sub


Private Sub protectWorkbook()
   If bookIsProtected(ThisWorkbook) Then Exit Sub
   ThisWorkbook.Protect SM_ProtectionPassword, True, False
End Sub
Private Sub unprotectWorkbook()
   If Not bookIsProtected(ThisWorkbook) Then Exit Sub
   If Not tcv_devmode Then On Error GoTo errorHandler
   ThisWorkbook.Unprotect SM_ProtectionPassword
   Exit Sub
errorHandler:
   With Err
      .Raise .Number, Description:=.Description & vbNewLine & "[error on workbook " & ThisWorkbook.name & "]"
   End With
End Sub

Public Function bookIsProtected(book As Workbook) As Boolean
   Dim protected As Boolean
   If book.ProtectWindows Then protected = True
   If book.ProtectStructure Then protected = True
   
   bookIsProtected = protected
   
End Function

'-------------------------------------------------------------------
' Sheet Exists
'--------------------------------------------------------------------
' General function to check for a sheet given a sheet name
'
Public Function SheetExists(shtName As String, Optional wb As Workbook) As Boolean
    Dim sht As Worksheet

     If wb Is Nothing Then Set wb = ThisWorkbook
     On Error Resume Next
     Set sht = wb.Sheets(shtName)
     On Error GoTo 0
     SheetExists = Not sht Is Nothing
 End Function
