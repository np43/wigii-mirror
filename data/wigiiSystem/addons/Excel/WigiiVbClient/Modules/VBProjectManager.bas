Attribute VB_Name = "VBProjectManager"
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

Option Explicit
'----------------------------------------------------------------------------
'- VB PROJECT MANAGER
'----------------------------------------------------------------------------
'- Author  : Camille WEBER
'- Update  : 11.12.2009
'- Version : 1.0
'----------------------------------------------------------------------------
'- VB project management functions
'----------------------------------------------------------------------------

'Exports all vba project artefacts.
Public Sub VBPM_exportArtefacts()
   Dim folderPath As String
   Dim fd As FileDialog
   Set fd = Application.FileDialog(msoFileDialogFolderPicker)
   With fd
      .title = "Save vb project in"
      .AllowMultiSelect = False
      'If ok then
      If .Show = -1 Then
         folderPath = .SelectedItems(1)
      'If cancel, exits method
      Else
         Exit Sub
      End If
   End With
   Set fd = Nothing
   VBPM_exportArtefactsToFolder folderPath
   MsgBox "Done"
End Sub
Public Sub VBPM_exportArtefactsToFolder(folderPath As String)
   Dim vbCompo As Variant
   Dim fso As Scripting.FileSystemObject
   Set fso = New Scripting.FileSystemObject
   
   folderPath = folderPath & "\" & ThisWorkbook.VBProject.name
   
   For Each vbCompo In ThisWorkbook.VBProject.VBComponents
      Select Case vbCompo.Type
      'Excel sheets
      Case 100
         VBPM_exportVbComponent vbCompo, ".cls", folderPath & "\MicrosoftExcelObjects", fso
      'Module
      Case 1
         VBPM_exportVbComponent vbCompo, ".bas", folderPath & "\Modules", fso
      'Class
      Case 2
         VBPM_exportVbComponent vbCompo, ".cls", folderPath & "\ClassModules", fso
      'Forms
      Case 3
         VBPM_exportVbComponent vbCompo, ".frm", folderPath & "\Forms", fso
      End Select
   Next vbCompo
End Sub
Private Sub VBPM_exportVbComponent(vbCompo As Variant, fileExtension As String, folderPath As String, Optional fso As Scripting.FileSystemObject = Nothing)
   If fso Is Nothing Then
      Set fso = New Scripting.FileSystemObject
   End If
   Dim filename As String
   filename = folderPath & "\" & vbCompo.name & fileExtension
   If fso.FileExists(filename) Then
      fso.DeleteFile filename
   End If
   VBPM_createFolder folderPath, fso
   vbCompo.export filename
   Set fso = Nothing
End Sub
Private Sub VBPM_createFolder(folderPath As String, Optional fso As Scripting.FileSystemObject = Nothing)
   If fso Is Nothing Then
      Set fso = New Scripting.FileSystemObject
   End If
   Dim folders() As String
   folders = Split(folderPath, "\")
   Dim i As Integer, n As Integer
   n = UBound(folders)
   'checks drive exists
   Dim f As String
   f = folders(0)
   If Not fso.DriveExists(f) Then
      Err.Raise 1, "VBPM", "Drive " & f & " does not exists"
   End If
   
   'creates folders
   For i = 1 To n
      f = f & "\" & folders(i)
      If Not fso.FolderExists(f) Then
         fso.CreateFolder f
      End If
   Next i
   Set fso = Nothing
End Sub
