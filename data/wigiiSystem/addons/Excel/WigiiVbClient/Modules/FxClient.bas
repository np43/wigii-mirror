Attribute VB_Name = "FxClient"
'-
' Created by Medair
'-
'----------------------------------------------------------------------------
'- FX CLIENT
'----------------------------------------------------------------------------
'- Author  : DJ Clack
'- Update  : 31.06.2016
'- Version : 1.0
'----------------------------------------------------------------------------
'- The fx client is used to make FX api calls to wigii
'- This is mainly a wrapper around WinHttpRequest with the ability
'- to pass in just the fx operation
'- The client will return an FxResponse class that holds information about the response
'- including if there was an error.
'- There is an additional use of Microsoft.XMLHTTP to post a binary file to Wigii. This allows
'- applications to upload a copy of a workbook

Option Explicit
Const STR_BOUNDARY  As String = "3fbd04f5-b1ed-4060-99b9-fca7ff59c113" 'uid for multipart form data boundary
#If VBA7 Then ' 64 bits
Private Const TIMEOUT As LongPtr = 300000      'Timeout for request
#Else  ' 32 bits
Private Const TIMEOUT As Long = 300000      'Timeout for request
#End If
Private Const MAXRETRIES As Integer = 3 'Request retries
Private HostURL As String               'The base url for the fx endpoint
Private loggedIn As FxResponse          'Are we already logged in? Prevent making aditional login calls


'- Public methods :

'-------------------------------------------------------------
'- Fx_Init
'-------------------------------------------------------------
'- Purpose : Initializes the fx calls
'- Input   : The base host to make the fx calls to
'- Output  : void
'-------------------------------------------------------------
Public Sub Fx_Init(Host As String)

    If Host <> "" Then
       HostURL = Host
    End If
    Set loggedIn = Nothing
End Sub

'-------------------------------------------------------------
'- FX_Call
'-------------------------------------------------------------
'- Purpose : Call an fx operation on Wigii
'- Input   : String operation  - The fx operation to call
'          : String postData   - Optional post data to send
'            (non binary)
'- Output  : FxResponse object that contains the response or
'            error from the Wiggii service
'-------------------------------------------------------------
Public Function FX_Call(operation As String, Optional postData As String) As FxResponse
    Dim url As String
    Dim response As FxResponse
        
    url = FX_UrlFromOpreration(operation)
''
'    Debug.Print operation
'    Debug.Print url
'    Debug.Print ""
'
    
    Set FX_Call = FX_Request(url, postData)
        
End Function

'-------------------------------------------------------------
'- FX_UrlFromOpreration
'-------------------------------------------------------------
'- Purpose : Create a full Url from a given fx operation
'- Input   : String operation - The fx command to send
'- Output  : String Url       - The full URL to the Wigii
'                               endpoint
'-------------------------------------------------------------
Public Function FX_UrlFromOpreration(operation As String) As String
    FX_UrlFromOpreration = HostURL & Base64URLEncodeString(operation)
End Function

'-------------------------------------------------------------
'- FX_PostStringBoundary
'-------------------------------------------------------------
'- Purpose : Expose the Boundary seperator for creating post
'            data
'- Output  : String  - The boundary string
'-------------------------------------------------------------
Public Function FX_PostStringBoundary() As String
    FX_PostStringBoundary = STR_BOUNDARY
End Function

'-------------------------------------------------------------
'- FX_DataCall
'-------------------------------------------------------------
'- Purpose : Create a logTable as the response from simple xml
'            data output as a result of a wigii fxcall
'- Input   : String  operation          - The fx command to send
'          : String  ErrorMessagePrefix - Prefix of an error response
'          : Boolean showErrors         - show or hide the
'                                         error messages from
'                                         the fxCall
'- Output  : LogTable of data
'-------------------------------------------------------------
Public Function FX_DataCall(operation As String, Optional ErrorMessagePrefix As String = "", Optional showErrors As Boolean = True) As LogTable
 Dim response As FxResponse
       
    If Not TCV_FXLogin() Then
        Exit Function
    End If
    
    Set response = FX_Call(operation)
        
    If response.HasError Then
        If showErrors Then MsgBox ErrorMessagePrefix & ": " & response.ErrorMessage, vbCritical, "Portfolio Communiation Failure"
        End
    End If

    Set FX_DataCall = LogTable_Factory.LTFact_CreateFromRegXml(response.ResponseXml)

End Function

'-------------------------------------------------------------
'- FX_PostFile
'-------------------------------------------------------------
'- Purpose : Post a file as binary to an fx endpoint
'- Input   : String  operation - The fx command to send
'          : String  sFileName - The file location of the file
'                                to send
'- Output  : FxResponse Response object with the result of the call
'-------------------------------------------------------------
Public Function FX_PostFile(operation As String, sFileName As String) As FxResponse
    Dim url As String
    
    url = FX_UrlFromOpreration(operation)
    
    Set FX_PostFile = FX_PostBinaryFile(url, sFileName)

End Function

'-------------------------------------------------------------
'- FX_Login
'-------------------------------------------------------------
'- Purpose : Shortcut to handle the login of a user
'- Input   : String userName
'          : String Password
'- Output  : fxResponse The response object
'-------------------------------------------------------------
Public Function FX_Login(userName As String, userPassword As String) As FxResponse
    Dim fxoperation As String
    If Not loggedIn Is Nothing Then
        If Not loggedIn.HasError Then
            Set FX_Login = loggedIn
            Exit Function
        End If
    End If
     
    fxoperation = "ctlSeq(sysLogin(""" & userName & """,""" & userPassword & """))"
    Set loggedIn = FX_Call(fxoperation)
    Set FX_Login = loggedIn
End Function

'--- Private methods

'-------------------------------------------------------------
'- FX_Request
'-------------------------------------------------------------
'- Purpose : Make the raw request to the service and populate
'            the FxResponse object
'- Input   : string operationUrl - The full url to make the
'                                  request to
'- Output  : fxResponse The response object
'-------------------------------------------------------------
Private Function FX_Request(operationUrl As String, Optional postData As String) As FxResponse
    Dim status As String
    Dim retries As Integer
    Dim responseBody As String
    Dim response As FxResponse
    Dim httpMethod As String
    
    If IsMissing(postData) Then
        httpMethod = "GET"
    Else
        httpMethod = "POST"
    End If
    
    Set response = New FxResponse
        
    Do While status <> "200" And retries < MAXRETRIES
        On Error GoTo 0
        With CreateObject("Microsoft.XMLHTTP")
            .Open httpMethod, operationUrl, False
            .SetRequestHeader "Content-Type", "text/plain;Charset=UTF-8;"
            .SetRequestHeader "Cache-Control", "no-cache, no-store"
            If postData <> "" Then
            .SetRequestHeader "Content-Length", Len(postData)
            .SetRequestHeader "Content-Type", "text/xml"
            End If
            '.SetTimeouts TIMEOUT, TIMEOUT, TIMEOUT, TIMEOUT
            If Not IsMissing(postData) Then
            .Send (postData)
            Else
            .Send
            End If

            responseBody = .ResponseText
            status = .status
        End With
        If status = "200" Then
            response.responseBody = responseBody
            Set FX_Request = response
            Exit Function
        ElseIf status = "500" Then
            response.ErrorMessage = responseBody
            Set FX_Request = response
            Exit Function
        Else
            Debug.Print status & " for http request"
        End If
            retries = retries + 1
    Loop
    
    response.ErrorMessage = "Unable to contact server. Please try again later"
    Set FX_Request = response
End Function

'-------------------------------------------------------------
'- FX_PostBinaryFile
'-------------------------------------------------------------
'- Purpose : Send binary file to an fx endpoint
'- Input   : string sUrl - The full url to make the request to
'          : string sFileName - The full path to the file to upload
'- Output  : FxResponse - the response object
'-------------------------------------------------------------
Private Function FX_PostBinaryFile(sUrl As String, sFileName As String) As FxResponse
    Const STR_BOUNDARY  As String = "3fbd04f5-b1ed-4060-99b9-fca7ff59c113"
    Dim nFile           As Integer
    Dim baBuffer()      As Byte
    Dim sPostData       As String
    Dim status          As String
    Dim responseBody    As String
    Dim response        As FxResponse
       
    Set response = New FxResponse
 
    '--- read file
    nFile = FreeFile
    Open sFileName For Binary Access Read As nFile
    If LOF(nFile) > 0 Then
        ReDim baBuffer(0 To LOF(nFile) - 1) As Byte
        Get nFile, , baBuffer
        sPostData = VBA.StrConv(baBuffer, vbUnicode)
    End If
      
    Close nFile
    
    '--- prepare body
   
    sPostData = "--" & STR_BOUNDARY & vbCrLf & _
        "Content-Disposition: form-data; name=""uploadfile""; filename=""" & VBA.Mid$(sFileName, InStrRev(sFileName, "\") + 1) & """" & vbCrLf & _
        "Content-Type: application/octet-stream" & vbCrLf & vbCrLf & _
        sPostData & vbCrLf & _
        "--" & STR_BOUNDARY & "--"

    '--- post
    With CreateObject("Microsoft.XMLHTTP")
        .Open "POST", sUrl, False
        .SetRequestHeader "Content-Type", "multipart/form-data; boundary=" & STR_BOUNDARY
        .Send FX_pvToByteArray(sPostData)
        responseBody = .ResponseText

    End With
    
    '--- handle response

        response.responseBody = responseBody
        Set FX_PostBinaryFile = response
        
    
End Function

Private Function FX_pvToByteArray(sText As String) As Byte()
    FX_pvToByteArray = VBA.StrConv(sText, vbFromUnicode)
End Function
