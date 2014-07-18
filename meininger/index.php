<?php
$url = "http://178.23.123.23:8080/rq_readreservation/";

$options = "skey=RES876HSR&sterm1=457.1.421/HSR";

//open connection
$curlHandler = curl_init($url);
curl_setopt($curlHandler, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curlHandler,CURLOPT_POSTFIELDS, $options);

//execute XML
$responseXml = curl_exec($curlHandler);

//close connection
curl_close($curlHandler);

echo $responseXml;