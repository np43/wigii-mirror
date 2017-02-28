VERSION 1.0 CLASS
BEGIN
  MultiUse = -1  'True
END
Attribute VB_Name = "FxResponse"
Attribute VB_GlobalNameSpace = False
Attribute VB_Creatable = False
Attribute VB_PredeclaredId = False
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
'-
' Created by Medair in June 2016
'-
'-------------------------------------------------------------
'- FX RESPONSE
'-------------------------------------------------------------
'- Author  : DJ Clack
'- Update  : 01.07.2016
'- Version : 1.0
'-------------------------------------------------------------
'- This class stores a response from an FX Call
'- Also handles automatic parsing of XML responses
'- and error state
'-------------------------------------------------------------
Option Explicit

Private pErrorMessage As String
Private pResponseBody As String
Private pResponseXml As MSXML2.DOMDocument
Private pResponseError As Boolean
Private pXmlError As Boolean ' Flag to see if we have tried to parse XML
Private pHasXML As Boolean ' Flag to see if there is XML

'-------------------------------------------------------------
'- ERROR MESSAGE
'-------------------------------------------------------------
'- Purpose : Returns the error message if there has been an error
'- Input   : -
'- Output  : the range or Nothing if the table is empty
'-------------------------------------------------------------
Public Property Get ErrorMessage() As String
    ErrorMessage = pErrorMessage
End Property

'-------------------------------------------------------------
'- RESPONSE BODY
'-------------------------------------------------------------
'- Purpose : Returns the raw response body as a string
'- Input   : -
'- Output  : the response body
'-------------------------------------------------------------
Public Property Get responseBody() As String
    responseBody = pResponseBody
End Property

'-------------------------------------------------------------
'- RESPONSE XML
'-------------------------------------------------------------
'- Purpose : Returns the rresponse as parsed XML
'- Input   : -
'- Output  : MSXML2.DOMDocument parsed XML
'-------------------------------------------------------------
Public Property Get ResponseXml() As MSXML2.DOMDocument
    Set ResponseXml = pResponseXml
End Property

'-------------------------------------------------------------
'- HAS ERROR
'-------------------------------------------------------------
'- Purpose : Returns if the response returned an error
'- Input   : -
'- Output  : Boolean
'-------------------------------------------------------------
Public Property Get HasError() As Boolean
   HasError = pResponseError
End Property

'-------------------------------------------------------------
'- HAS ERROR
'-------------------------------------------------------------
'- Purpose : Returns if the response returned an error
'- Input   : -
'- Output  : Boolean
'-------------------------------------------------------------
Public Property Get HasXML() As Boolean
   HasXML = pHasXML
End Property

'-------------------------------------------------------------
'- ERROR MESSAGE = ""
'-------------------------------------------------------------
'- Purpose : Sets up the error message. Will parse exception message
'            from a wigii exception
'- Input   : Error String
'- Output  : -
'-------------------------------------------------------------
Public Property Let ErrorMessage(ErrorMessage As String)
    Dim node As IXMLDOMNode
    pResponseError = True
    Call ParseXML(ErrorMessage)

    If pHasXML = True Then
      For Each node In pResponseXml.SelectNodes("//exception/message")
          ErrorMessage = node.Text
      Next
    End If
    
    pErrorMessage = ErrorMessage
End Property

'-------------------------------------------------------------
'- RESPONSE BODY = ""
'-------------------------------------------------------------
'- Purpose : Sets the response body and will parse if it is XML
'- Input   : The raw response string
'- Output  : -
'-------------------------------------------------------------
Public Property Let responseBody(responseBody As String)
    Call ParseXML(responseBody)
    pResponseBody = responseBody
End Property

' Try to parse the string as xml
Private Sub ParseXML(body As String)
    Dim xDoc As MSXML2.DOMDocument
    Set xDoc = New MSXML2.DOMDocument
    
    If xDoc.LoadXML(body) Then
        Set pResponseXml = xDoc
        pHasXML = True
    Else
        pXmlError = True
    End If
End Sub






