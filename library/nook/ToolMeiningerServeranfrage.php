<?php
/**
 * Fragt den Server von meininger nach Verfügbarkeiten ab.
 *
 * @author Stephan Krauss
 * @date 30.05.2014
 * @file ToolMeiningerServeranfrage.php
 * @project HOB
 * @package tool
 */
 
 class nook_ToolMeiningerServeranfrage
 {
     /** @var $convertDataObj PhpJsonXmlArrayStringInterchanger */
     protected $convertDataObj = null;

     protected $serverIp = null;
     protected $serverPort = null;
     protected $requestXml = null;
     protected $urlErweiterungen = array();

     protected $responseData = null;

     /**
      * @param PhpJsonXmlArrayStringInterchanger $convertDataObj
      * @return nook_ToolMeiningerServeranfrage
      */
     public function setConvertDataObj(nook_ToolPhpJsonXmlArrayStringInterchanger $convertDataObj)
     {
         $this->convertDataObj = $convertDataObj;

         return $this;
     }

     /**
      * @param $serverPort
      * @return nook_ToolMeiningerServeranfrage
      */
     public function setServerPort($serverPort)
     {
         $this->serverPort = $serverPort;

         return $this;
     }

     /**
      * @param $serverIp
      * @return nook_ToolMeiningerServeranfrage
      */
     public function setServerIp($serverIp)
     {
         $this->serverIp = $serverIp;

         return $this;
     }

     /**
      * @param $requestXml
      * @return nook_ToolMeiningerServeranfrage
      */
     public function setRequestXml($requestXml)
     {
         $this->requestXml = $requestXml;

         return $this;
     }

     /**
      * @param $urlErweiterungen
      * @return nook_ToolMeiningerServeranfrage
      */
     public function setUrlErweiterungen($urlErweiterungen)
     {
         $this->urlErweiterungen = $urlErweiterungen;

         return $this;
     }

     /**
      * Holt die Antwort vom meininger Server
      *
      * @return nook_ToolMeiningerServeranfrage
      * @throws Exception
      */
     public function steuerungHolenResponse()
     {
         try{
             if(is_null($this->serverIp))
                 throw new Exception('Server IP fehlt');

             if(is_null($this->serverPort))
                 throw new Exception('Server Port fehlt');

             if(is_null($this->requestXml))
                 throw new Exception('Anfrage XMl fehlt');

             if( count($this->urlErweiterungen) == 0 )
                 throw new Exception('URL Erweiterung fehlt');

             $this->responseData = $this->requestMeininger($this->requestXml, $this->serverIp, $this->serverPort, $this->urlErweiterungen);

             return $this;
         }
         catch(Exception $e){
             throw $e;
         }
     }

     /**
      * Holt den Response vom Server Meininger
      *
      * @param $requestXml
      * @param $serverIp
      * @param $serverPort
      * @return mixed
      */
     protected function requestMeininger($requestXml, $serverIp, $serverPort, $urlErweiterung)
     {
         $url = "http://".$serverIp.":".$serverPort."/".$urlErweiterung;

         //open connection
         $curlHandler = curl_init($url);
         curl_setopt($curlHandler, CURLOPT_CUSTOMREQUEST, "POST");
         curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, true);
         curl_setopt($curlHandler,CURLOPT_POSTFIELDS, $requestXml);

         //execute post
         $responseXml = curl_exec($curlHandler);

         //close connection
         curl_close($curlHandler);

         // XML to Array
         $responseArray = $this->convertDataObj->convertXmlToArray($responseXml);

         try{
             if($responseArray[0] == 'Key not found')
                 throw new Exception("Fehler Server Meininger. 'Key not found' ".$serverIp.":".$serverPort.", ".$responseArray[0]);

             if($responseArray['BODY']['H1'] == 'Not Found.')
                 throw new nook_Exception("Fehler Server Meininger. 'Not Found' ".$serverIp.":".$serverPort);
         }
         catch(Exception $e){
             $e->kundenId = nook_ToolKundendaten::findKundenId();
             nook_ExceptionRegistration::buildAndRegisterErrorInfos($e, 2);

             $responseArray = false;
         }

         return $responseArray;
     }

     /**
      * @return String
      */
     public function getResponseData()
     {
         return $this->responseData;
     }
 } 