' Excel macro to generate FREEtext file sfrom hebrew excel file '

Sub Macro1()

 Dim objStream As Object
 Dim filename
 
 
 Dim i As Integer
 For i = 2 To 9095

    filename = "C:\Users\salevy\Documents\xmls\FREETEXT_HEB_MRI_" & i & ".xml"
    
    'Create the stream
    Set objStream = CreateObject("ADODB.Stream")
 
    'Initialize the stream
    objStream.Open
 
    'Reset the position and indicate the charactor encoding
    objStream.Position = 0
    objStream.Charset = "UTF-8"
 
    
 
    'Write to the steam
    objStream.WriteText "<soap:Envelope xmlns:soap='http://www.w3.org/2003/05/soap-envelope'><soap:Body xmlns:m='http://192.168.163.129/medical_warehouse/warehouse.php'><m:ingest_file><m:clientId>Sam_Test</m:clientId><m:md5>81dc9bdb52d04dc20036dbd8313ed055</m:md5><m:format>FREETEXT</m:format><m:payload>" & Cells(i, 1).Value & "</m:payload><m:batchId>MRIsHEB</m:batchId></m:ingest_file></soap:Body></soap:Envelope>"
 
    'Save the stream to a file
    objStream.SaveToFile filename
    
    
Next i
End Sub


C:\Users\salevy\My Documents\xmls>for %f in (.\*) do @curl -X POST -d @%f "http://192.168.163.130/medical_warehouse/warehouse.php"