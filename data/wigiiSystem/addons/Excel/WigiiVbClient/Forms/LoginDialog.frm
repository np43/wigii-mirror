VERSION 5.00
Begin {C62A69F0-16DC-11CE-9E98-00AA00574A4F} LoginDialog 
   Caption         =   "UserForm1"
   ClientHeight    =   1830
   ClientLeft      =   45
   ClientTop       =   330
   ClientWidth     =   5265
   OleObjectBlob   =   "LoginDialog.frx":0000
   StartUpPosition =   1  'CenterOwner
End
Attribute VB_Name = "LoginDialog"
Attribute VB_GlobalNameSpace = False
Attribute VB_Creatable = False
Attribute VB_PredeclaredId = True
Attribute VB_Exposed = False
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

'----------------------------------------------------------------------------
'- LOGIN DIALOG
'----------------------------------------------------------------------------
'- Author  : Camille WEBER
'- Update  : fri 13-10-2000
'- Version : 1.0
'----------------------------------------------------------------------------
'- This dialog is used for the login process or to change the password
'----------------------------------------------------------------------------

'- Dialog mode : LOGIN or CHANGEPASSWORD
Private Const LD_LOGIN As Integer = 0
Private Const LD_CHANGEPASSWORD As Integer = 1
Private LD_mode As Integer
'- OK button clicked
Private dialogValided As Boolean

'-------------------------------------------------------------
'- INITIALIZE
'-------------------------------------------------------------
'- Purpose : Initializes the dialog
'- Input   : the mode (0 : LOGIN, 1 : CHANGEPASSWORD)
'-           the default value for the 2 textboxes,
'-           if the textboxes are enabled
'- Output  : -
'-------------------------------------------------------------
Public Sub Initialize(mode As Integer, _
                      Optional default1 As String = "", Optional enable1 As Boolean = True, _
                      Optional default2 As String = "", Optional enable2 As Boolean = True)
                                  
   dialogValided = False
   LD_mode = mode
   If LD_mode = LD_LOGIN Then
      LoginDialog.Caption = "Login"
      Label1.Caption = "User Name :"
      Label2.Caption = "Password :"
      With TextBox1
         .Enabled = enable1
         .PasswordChar = ""
         .value = default1
         If enable1 Then
            .SetFocus
         End If
      End With
      With TextBox2
         .Enabled = enable2
         .PasswordChar = "*"
         .value = ""
         If enable2 And Not enable1 Then
            .SetFocus
         End If
      End With
      OKButton.Default = True
      Me.Show
   ElseIf LD_mode = LD_CHANGEPASSWORD Then
      LoginDialog.Caption = "New Password"
      Label1.Caption = "Password :"
      Label2.Caption = "Confirm :"
      With TextBox1
         .Enabled = enable1
         .PasswordChar = "*"
         .value = ""
         If enable1 Then
            .SetFocus
         End If
      End With
      With TextBox2
         .Enabled = enable2
         .PasswordChar = "*"
         .value = ""
         If enable2 And Not enable1 Then
            .SetFocus
         End If
      End With
      OKButton.Default = True
      Me.Show
   Else
      dialogValided = False
      TextBox1.value = ""
      TextBox2.value = ""
   End If
End Sub

'-------------------------------------------------------------
'- CANCELCLICKED
'-------------------------------------------------------------
'- Purpose : Receipt saying if the cancel button or 'x' have
'-           been pressed
'- Input   : -
'- Output  : true or false
'-------------------------------------------------------------
Public Property Get CancelClicked() As Boolean
   CancelClicked = Not dialogValided
End Property

'-------------------------------------------------------------
'- LOGIN NAME / PASSWORD
'-------------------------------------------------------------
'- Purpose : Returns the login name or password
'- Input   : -
'- Output  : if mode = LOGIN -> the name and the password
'-------------------------------------------------------------
Public Property Get LoginName() As String
   If LD_mode = LD_LOGIN Then
      LoginName = TextBox1.value
   Else
      LoginName = ""
   End If
End Property
Public Property Get LoginPassword() As String
   If LD_mode = LD_LOGIN Then
      LoginPassword = TextBox2.value
   Else
      LoginPassword = ""
   End If
End Property

'-------------------------------------------------------------
'- NEW PASSWORD1 / PASSWORD2
'-------------------------------------------------------------
'- Purpose : Returns the 2 passwords
'- Input   : -
'- Output  : if mode = CHANGEPASSWORD -> the 2 passwords
'-------------------------------------------------------------
Public Property Get NewPassword1() As String
   If LD_mode = LD_CHANGEPASSWORD Then
      NewPassword1 = TextBox1.value
   Else
      NewPassword1 = ""
   End If
End Property
Public Property Get NewPassword2() As String
   If LD_mode = LD_CHANGEPASSWORD Then
      NewPassword2 = TextBox2.value
   Else
      NewPassword2 = ""
   End If
End Property

'- Events

Private Sub CancelButton_Click()
   dialogValided = False
   TextBox1.value = ""
   TextBox2.value = ""
   Me.Hide
End Sub

Private Sub OKButton_Click()
   dialogValided = True
   Me.Hide
End Sub

Private Sub UserForm_Terminate()
   dialogValided = False
   TextBox1.value = ""
   TextBox2.value = ""
   Me.Hide
End Sub
