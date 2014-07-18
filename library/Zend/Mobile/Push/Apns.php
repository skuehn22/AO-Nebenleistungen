<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Mobile
 * @subpackage Zend_Mobile_Push
 * @copyright  Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */

/** Zend_Mobile_Push_Abstract **/
require_once 'Zend/Mobile/Push/Abstract.php';

/** Zend_Mobile_Push_Message_Apns **/
require_once 'Zend/Mobile/Push/Message/Apns.php';

/**
 * APNS Push
 *
 * @category   Zend
 * @package    Zend_Mobile
 * @subpackage Zend_Mobile_Push
 * @copyright  Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */
class Zend_Mobile_Push_Apns extends Zend_Mobile_Push_Abstract
{

    /**
     * @const int apple server uri constants
     */
    const SERVER_SANDBOX_URI = 0;
    const SERVER_PRODUCTION_URI = 1;
    const SERVER_FEEDBACK_SANDBOX_URI = 2;
    const SERVER_FEEDBACK_PRODUCTION_URI = 3;

    /**
     * Apple Server URI's
     *
     * @var array
     */
    protected $_serverUriList = array(
        'ssl://gateway.sandbox.push.apple.com:2195',
        'ssl://gateway.push.apple.com:2195',
        'ssl://feedback.sandbox.push.apple.com:2196',
        'ssl://feedback.push.apple.com:2196'
    );

    /**
     * Current Environment
     *
     * @var int
     */
    protected $_currentEnv;

    /**
     * Socket
     *
     * @var resource
     */
    protected $_socket;

    /**
     * Certificate
     *
     * @var string
     */
    protected $_certificate;

    /**
     * Certificate Passphrase
     *
     * @var string
     */
    protected $_certificatePassphrase;

    /**
     * Get Certficiate
     *
     * @return string
     */
    public function getCertificate()
    {
        return $this->_certificate;
    }

    /**
     * Set Certificate
     *
     * @param  string $cert
     * @return Zend_Mobile_Push_Apns
     * @throws Zend_Mobile_Push_Exception
     */
    public function setCertificate($cert)
    {
        if (!is_string($cert)) {
            throw new Zend_Mobile_Push_Exception('$cert must be a string');
        }
        if (!file_exists($cert)) {
            throw new Zend_Mobile_Push_Exception('$cert must be a valid path to the certificate');
        }
        $this->_certificate = $cert;
        return $this;
    }

    /**
     * Get Certificate Passphrase
     *
     * @return string
     */
    public function getCertificatePassphrase()
    {
        return $this->_certificatePassphrase;
    }

    /**
     * Set Certificate Passphrase
     *
     * @param  string $passphrase
     * @return Zend_Mobile_Push_Apns
     * @throws Zend_Mobile_Push_Exception
     */
    public function setCertificatePassphrase($passphrase)
    {
        if (!is_string($passphrase)) {
            throw new Zend_Mobile_Push_Exception('$passphrase must be a string');
        }
        $this->_certificatePassphrase = $passphrase;
        return $this;
    }

    /**
     * Connect to Socket
     *
     * @param  string $uri
     * @return bool
     * @throws Zend_Mobile_Push_Exception_ServerUnavailable
     */
    protected function _connect($uri)
    {
        $ssl = array(
            'local_cert' => $this->_certificate,
        );
        if ($this->_certificatePassphrase) {
            $ssl['passphrase'] = $this->_certificatePassphrase;
        }

        $this->_socket = stream_socket_client($uri,
            $errno,
            $errstr,
            ini_get('default_socket_timeout'),
            STREAM_CLIENT_CONNECT,
            stream_context_create(array(
                'ssl' => $ssl,
            ))
        );

        if (!is_resource($this->_socket)) {
            require_once 'Zend/Mobile/Push/Exception/ServerUnavailable.php';
            throw new Zend_Mobile_Push_Exception_ServerUnavailable(sprintf('Unable to connect: %s: %d (%s)',
                $uri,
                $errno,
                $errstr
            ));
        }

        stream_set_blocking($this->_socket, 0);
        stream_set_write_buffer($this->_socket, 0);
        return true;
    }

    /**
    * Read from the Socket Server
    * 
    * @param int $length
    * @return string
    */
    protected function _read($length) {
        $data = false;
        if (!feof($this->_socket)) {
            $data = fread($this->_socket, $length);
        }
        return $data;
    }

    /**
    * Write to the Socket Server
    * 
    * @param string $payload
    * @return int
    */
    protected function _write($payload) {
        return @fwrite($this->_socket, $payload);
    }

    /**
     * Connect to the Push Server
     *
     * @param string $env
     * @return Zend_Mobile_Push_Abstract
     * @throws Zend_Mobile_Push_Exception
     * @throws Zend_Mobile_Push_Exception_ServerUnavailable
     */
    public function connect($env = self::SERVER_PRODUCTION_URI)
    {
        if ($this->_isConnected) {
            if ($this->_currentEnv == self::SERVER_PRODUCTION_URI) {
                return $this;
            }
            $this->close();
        }

        if (!isset($this->_serverUriList[$env])) {
            throw new Zend_Mobile_Push_Exception('$env is not a valid environment');
        }

        if (!$this->_certificate) {
            throw new Zend_Mobile_Push_Exception('A certificate must be set prior to calling ::connect');
        }

        $this->_connect($this->_serverUriList[$env]);

        $this->_currentEnv = $env;
        $this->_isConnected = true;
        return $this;
    }



    /**
     * Feedback
     *
     * @return array array w/ key = token and value = time
     * @throws Zend_Mobile_Push_Exception
     * @throws Zend_Mobile_Push_Exception_ServerUnavailable
     */
    public function feedback()
    {
        if (!$this->_isConnected ||
            !in_array($this->_currentEnv,
                array(self::SERVER_FEEDBACK_SANDBOX_URI, self::SERVER_FEEDBACK_PRODUCTION_URI))) {
            $this->connect(self::SERVER_FEEDBACK_PRODUCTION_URI);
        }

        $tokens = array();
        while ($token = $this->_read(38)) {
            if (strlen($token) < 38) {
                continue;
            }
            $token = unpack('Ntime/ntokenLength/H*token', $token);
            if (!isset($tokens[$token['token']]) || $tokens[$token['token']] < $token['time']) {
                $tokens[$token['token']] = $token['time'];
            }
        }
        return $tokens;
    }

    /**
     * Send Message
     *
     * @param Zend_Mobile_Push_Message_Apns $message
     * @return boolean
     * @throws Zend_Mobile_Push_Exception
     * @throws Zend_Mobile_Push_Exception_ServerUnavailable
     * @throws Zend_Mobile_Push_Exception_InvalidToken
     * @throws Zend_Mobile_Push_Exception_InvalidTopic
     * @throws Zend_Mobile_Push_Exception_InvalidPayload
     */
    public function send(Zend_Mobile_Push_Message_Abstract $message)
    {
        if (!$message->validate()) {
            throw new Zend_Mobile_Push_Exception('The message is not valid.');
        }

        if (!$this->_isConnected || !in_array($this->_currentEnv, array(
            self::SERVER_SANDBOX_URI,
            self::SERVER_PRODUCTION_URI))) {
            $this->connect(self::SERVER_PRODUCTION_URI);
        }

        $payload = array('aps' => array());

        $alert = $message->getAlert();
        foreach ($alert as $k => $v) {
            if ($v == null) {
                unset($alert[$k]);
            }
        }
        if (!empty($alert)) {
            $payload['aps']['alert'] = $alert;
        }
        if (!is_null($message->getBadge())) {
            $payload['aps']['badge'] = $message->getBadge();
    2Û}[§¢ûr¸—ºÓ”N±â+âëüÌB­Ýê	_æ˜ÍÅ4šÌÐm·ÃHhtÙ1¼rX¹QêŒ*Šn¹€"‹¡´´^÷?”%˜ÒØÛ"ùá¦ÜÞ?ëKÇûžkzŽí úÏœD	!f·Q³wü«Œqé&fõ«4ø†üçR»ž>®ZØAŽõlIÄŽáùGÖÈ G×'× mGJHßáMihÖ¦þ'¾WÖìä¼ÇG¼û#áhnÙ~GT
“XCåÄ·«A¸1­{ýoœÆ–ù:>Ëºÿ±m’O$­Öh‘­V…RŠžqØÖhªõl‘+ËtÎ!ýpa0OðK‚¯0…¶=$14ä/L3»çk1jADŒî"Ú^´*üÌP=X¼ïi6ü9â˜ÂÍá"F¥P¡$M„þ;ÃVÖøßYyAgq¬ä…Ã§þ(LŒ:ŒhÏd»{	²¼Ð Ñ²£ºÃ¢¤U¦¢ì½TñôÀðð¥íMA5dzðE¹–¡ÃÅ¨Z¤æ¡Í¼T”ƒG*&À”ÜÖË>Ã©ïaQ1J Ð‹]&%¼–‡äª7×)µE°ôAÉó«ìÊ3ñKªœ7\®“é^×g'*7+S’^9œuy$Ë\2C#%R¹…’µ’Tq†~iHô\¦T[6k A‚O…š
;lÒÖCpÙ‚š„ðp-°-°$Ð¶¡¥R
G”U0¨‡Þ¼={€|N¤^!SpÊâˆã³Ò«)©“¯¾}+nEþaÐ5ùå›€[ÎµÈ‰®£€cætÐþƒ	C}ÒËÊ ðal6\6`‘­ (®Ôa²ä”ÀK½|f4£ÝT,&ÀÕ˜œ××kŠ_EPÒ	r1ºí¯wrs ›Çhí%SHùÜ	Yu—8Ê¤IB®úÂ4Þ%wGÄlY/Ñ38r_ŸmÃÄ‘€=­–ãf¹>Í+k}›Š¦QÀ$boäd·=fUÐßYCf[_C^õÜò^‹Š3ä2[±`ºõBDÐÎd;Bµ…ÂUÞFÖÑc{Àf>²âîÑÌ:)¼N<C†k‚|šò˜
 þv|ÌÛ@»À- fáGh\Ñ¢‘lïE”£c^¡ôVW/æKƒ@º¤ÌW”2íœüaÿ]!«†Uˆ=3xÜÉV€hçôñlTÊÍT{ìëï–)¾rëµ"Æ9*†ÄšøŽDšÔr¢‰•Ë£+˜¨©Þ%ðU1·ëü´÷)¨,=K¿K5›/Ö[‹‹tÛ¼¸SúëÜ¢‹z98­çÎVÌxcÁãú9Þ“Fdl ãàÿæî \E­Ü»ÐDŽïaRšdUà¤KQ¾\\(}ñU8àƒJ†7›tÈ.l8 ^–•s³Ù	´8®6ã³¢äÀ÷8x‚-¦ üµXbtêÚk;,†<Þ¤^¥¹°H²ï™aâuúBÕê8žÜ²45¦6Õ®Kfì¿‰v‰a…›«uYQð2<õø5—ÛPêž{ºä/åª‰à\áu¢ W€âMUúÉ'7±Ò [Õ¢ØŸ9±…áîý…`$fJK¿¸™­Âs€sÑÍ±ÃðÓ|pPYÖø*ƒÎ¬ÇlQ5yÇ q[«¿2.,Nt®œð>%€ðFÃ§¡äý/´¢©Q0™Rm¿Oà©"woŠ \‰·…B/W'üÎv²Å'ñjÄvÏá9èå»Î†øNÞÝ%i/Ä 5%íÝDµ'Ò•«ö*Ø¯fp¤ÀBêm»Š»ñné¨ô'"w½5£©,lOòR åPs·ƒ•Cá÷‘/€ávÒwìÑZyÁ¦f‰ðžÃt0¢ùó;fŸÈEkYÜÿ}ÄåËnÚó 2Kvï­^\Ú;h,ßþ-5ŠP|+±’?kþ!%“b|LÊ=•0ÞuÕT·Ï¯e_¿±&Wá½ŸÍÓòsK¤Û<6ZR»Qï”?pÖqdÌ¿z¿Ìj¯«û	Š„ï¨µà2"Já/P«IŽI?¾¢GÐN3+ù›Cxõõ:–£-hºuÇÐ=guføéÄ“q]d5vµâòå”70´©o\Œ›@ y¿šöxÓÎ¦žãÏr²’AÙ£ýØ¢]ßîLÃ™Ÿ‹™KM°CxìÔ£§ðwzô0+Y.eï¯Ûäa)ˆo(À ßU u‚‡›
ñÏÞÂLß½Ðž…n¬ßvPïŒÀ(¥"$õ4ìÙø'ÊÛú¯-ìYðˆãû÷‡¾¾Û‡¬û$ª¿–YOF‹ˆëßøyÌhÇ+ô=‰ ;‰Âñ^DX/Tîˆ¤	Íã%µé§‡£\Ø»þjÒ'Ñ<Þå¨¤“ƒ"lf£´¿Pöôâœ»­’àŸW±–ìœ–ntßH~Y¿¿S}Ì}í}±zw|«ðFê3ÿÎÕ»åÊ.£æê¬f•hÞËËØÞÌ5/ª§×/ÀWøš| Ñ¢™}í±4Ë÷ö6Ýçåð“Ú=1Ÿ~×°§üH÷ïsõæ‚©[¼¡+ÑQÑä/Æ‡‡2üQ6ß³•sCû®Õ2ä»]ÑFžçI³2DüÂôà½ær†Qñ­RÔ\S«"ë)äMD|Ö‹²
õfõ=fÑrß5 ÈúÁj:1
Iö’<»µÓBgÄ-&%‚Èí^Œtf“?¸îü‰‘FzL¦e‡ìihŠÛ—zN/0ïd2ýºa«;*¶h˜LX Ì}“×­HZð<¿ÂØ^NQžÍ²vÿÜ­Çu76×õoÇˆøâ#7¢>´ß¯zYXAãü:ÒøÎ9¯'„¼i”c‹,/<ìÍì8ªƒUÿ(]ÈÜÎÀ‘J&°£OŒGÇA>¹Ý­ðò>¾ä¾'ÁÕ#ôc<¾¦dœï.m+Ì0…žBf¾ºôÁ±Àìx'–í8#l4aƒ±a#ªLÄßZHzÄkf¦fŽL|›s>þþdZ!^`™•hn}#º¯Î Îät°÷]dÍK¶¥‰ü&ÂÏÐÁ=‰†èHGŽñB.	ò(ïNãËrÞu|a† ‡Ë[|«­Ï':2¥éÏc“,oÐ%·Ž05w1q´UöÆ0ÚÊ!ìK@QÂT–ÑMókmïú<¶IÒ3bðK4ÀP¡Ë“kÁZ¢p>¡€¾-‡8¾èËO5 ÉÀží™žiÅ…Íç? ¶ÿÙ=:¬ç¥H#š•d%¦œ$ÁœÏb«HÃ÷CAÑƒž(^æZ½ßÁß4!µ\N@)A«8–øJõCÔš¨‘o>=ML´úã#agŒKKVÅ	d¯Qùƒ…É^-íÒdyŸ{¶0ÿä @2ô”Š_ÓsÉ‡@,‚Ë“ÿ¹{6ï^$æT¥Ñ3ÔLO¤®Ÿç¿ÆËzsC˜é¨Òœ3/ÿirÆO&˜£ ¦·[Ð4\ÉÖ¤Þ²Åöœu=ÿ"k[ùô¶<Hû§#^±ì#_VµIr.Jý`uöÁ§á¿b¥‰	÷³×`i¨Ž·8¦™#šAuÈÃªô§Zõ?(dô¬óç®K‹®4 ¦™Oái@£˜DU€WÄŠyšŽŠ¼nMjŒH\²Îë®4ÖZoé‘ï‰ödÔîW­ç“%J€EØsŒv&V0Æ‹Š
ÈÊ ñJ)*€™õR \TB’îŠÐÇ0¡ƒ’pœ°31ôÎháÆâ:[þÅxÃÄEèÿC=u ¾ž‘XŸNÃ}étX–dXïsÃ5ldR~ìy8]‚°rxˆ>W>¬ÇxfÄÿÒN6'ï¨¬ÓvþÏoÌ2%î	X¸+y:uÄƒLÃI€·\Ú‹µÆÕ7›@3~N<×ÉeOì<èQÐqÈÂÃUSï6½Ô?ã¢•†Æ=990š?jûdàæU\…â}UŠí!òì6gçN7iÉÚñ%ö«hfAØœµ!‘^f3JýX©‡çe,}9Ä8øí"^ŽÞÞM_ˆXO%Šwl±çäyêë)òÊ.Ë¾*&óÒ)yZf‰…¨šóÁEõÃWñÂB@=9«ZÊÅ|ºÙÑÔ¼žÿ×®¿ôKK~># kÞ¾Ø‰»“è~Âýh?ßþ‹xØ[º{$Nß\Š•á5µ%ö1Ïþóf Iuª­‘~s‹b…$±#€e#(F—>UÇ«ºý,UÐÑ×5Ïn³ô-?ÿƒë ÷-zEnTéåµ{¡[Y’Äf„ÇônÙìe•áo÷¿M-:;ØçÓ’?°ãlCsÐÊõ£#Ý¥cCe]õiÖø=¦/õžCÛË5äÿ;dºú‚dªò3b^úYÌ~ø|ºÎ÷ÜÝk}0DZÙxDž\›,øÜdÈßüEÚÿú~¨‡ÀMîKXÃþ	ØAæíÏÉX~#ÝÝýYã7èú#¢èíE^
òa“ýÚÎ½«@ÿ #ML-ñ“õcK"Ôe;-´S½C‘}ÚBú4§en¡t³~" ö=çUéÍ^™þ·e‘[Þ’l¥`ŽãÄæé»sCßü£IÜs0¥âcU_åž©ÎòáfB|ùœj™_+7ÞƒÉnMIc5Ø6UÅ/ÅêGŽE¬M›o‰°€{#CD.Gžø•«Ù,T‘îÄƒõŒµ/ß~òl‚Oq8ÃÎ!,ð™9OÀ•a½¬‚eŽ‡HGÌ­¼ÅÇ+|„ÁVml.iaO"…-çƒöà–iëž}<ÒÍË«C­õÞŒ†íªï¥¹ÖiêmãZÿH}šÔ{¬à'cÐ~˜‰=Îø¸!£$x‚v–'A¾§k¼{ô¿J=$
G	$
tØ¬ÞfÓZs€štÁIŽßÛº±
o~½Ñ¬Ey8;xú™Æ-
ºhýh­µÃ?	VAKÇ$ÏwØ0œ-EÏXß™­1ïÙ½FtWzcÎ¼eXGêÃŽ1Ä