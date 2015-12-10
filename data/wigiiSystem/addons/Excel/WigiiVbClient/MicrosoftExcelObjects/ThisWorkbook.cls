VERSION 1.0 CLASS
BEGIN
  MultiUse = -1  'True
END
Attribute VB_Name = "ThisWorkbook"
Attribute VB_GlobalNameSpace = False
Attribute VB_Creatable = False
Attribute VB_PredeclaredId = True
Attribute VB_Exposed = True
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

'File manager exported functions
Public Function FM_getTD() As Range
  Set FM_getTD = FileManager.FM_getTD
End Function

'Table manager exported functions
Public Function TM_Initialize(Optional mode As Integer = 1) As Integer
  TM_Initialize = TableManager.TM_Initialize(mode)
End Function
Public Function TM_getPhyTable(TID As Integer, output As Object) As Integer
  TM_getPhyTable = TableManager.TM_getPhyTable(TID, output)
End Function
Public Function TM_getTD() As Range
  TM_getTD = TableManager.TM_getTD()
End Function

'- Initializes slave mode
Public Sub openSlave()
   Dim events As Boolean
   
   events = Application.EnableEvents
   Application.EnableEvents = False
   
   LTFact_Initialize Feuil1
   SM_Initialize Feuil12.Range("modeTD"), Feuil12.Range("accessControlTD")
   SM_ChangeMode2 1, "slave"
   'FM_Initialize Only in distributed mode
   TM_Initialize
   
   Application.EnableEvents = events
End Sub

'- Initializes sytem mode
Public Sub openSystem()
   If changeMode Then
      Feuil12.Activate
      Feuil12.Range("A1").Select
   End If
End Sub

'- Changes the mode
Public Function changeMode() As Boolean
   Dim error As Integer
   
   LoginDialog.Initialize 0
   Do While Not LoginDialog.CancelClicked
      error = SM_ChangeMode1(LoginDialog.LoginName, LoginDialog.LoginPassword)
      If error = 0 Then
         Exit Do
      End If
      Beep
      MsgBox "Nom d'utilisateur ou mot de passe incorrects !", vbCritical, "Erreur login"
      LoginDialog.Initialize 0
   Loop
   changeMode = Not LoginDialog.CancelClicked
End Function

'- Boot
Public Sub boot()
   openSlave
   'SM_ChangeMode2 0, "jpinfo2002"
   'Feuil12.Activate
   SM_ChangeMode2 2, ""
   Feuil4.Activate
End Sub

'- First use : define user name
Public Sub defineUserName()
   Dim userName As String
   
   'Available only in user mode and for first use
   If (SM_Mode <> 2) Or Feuil12.Range("UserNameDefined").Value2 Then
      Exit Sub
   End If
   
   userName = InputBox("Entrez un nom d'utilisateur : ", "Saisie nom", "user")
   If userName = "" Then
      Exit Sub
   End If
   Do While SM_ChangeModeName(userName) < 0
      Beep
      MsgBox "Nom d'utilisateur invalide !", vbExclamation, "Erreur nom"
      userName = InputBox("Entrez un nom d'utilisateur : ", "Saisie nom", "user")
      If userName = "" Then
         Exit Sub
      End If
   Loop
   Feuil12.Range("UserNameDefined").Value2 = True
End Sub

'- Events

Private Sub Workbook_Open()
   boot
   defineUserName
End Sub

