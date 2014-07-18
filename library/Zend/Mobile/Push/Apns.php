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
    2�}[���r���ӔN��+����B���	_���4���m��H�ht�1�rX�Q�*�n��"����^�?�%����"����?�K���kz���ϜD	!f�Q�w����q�&f��4����R��>�Z�A��lIĎ��G�� G��'� mGJH��Mih֦�'�W����G��#�hn��~GT
�XC�ķ�A�1�{�o����:>����m�O$��h��V�R��q��h��l�+�t�!�pa0O�K��0��=$14�/L3��k1jAD��"�^��*��P=X��i6�9�����"F�P�$M��;�V���YyAgq���ç�(L�:�h�d�{	��� Ѳ��â�U����T�������MA5dz�E����ŨZ���ͼT��G*&������>é�a�Q1J�Ћ]&%����7�)�E��A����3�K��7\���^�g'*7+S�^9�uy�$�\2C#%R�����Tq��~iH�\�T[6k A�O��
;l��Cpق����p-�-�$ж��R
G�U0��޼={�|N��^!Sp���ҫ)�����}+nE�a�5�偛�[εȉ���c�t���	C}��� �al6\6`�� (��a�䐔�K�|f4��T,&�՘���k�_EP�	r1��wrs ��h�%SH��	Yu�8�ʤIB���4�%wG�lY/�38r_�m�đ�=���f�>�+k}���Q�$bo�d�=f�U�ߏYCf[_C^���^��3�2[�`��BD��d;B���U�F��c{�f>�����:)�N<C�k�|���
��v|��@��-�f�Gh\Ѣ�l�E��c^��VW�/�K�@���W�2��a�]!��U��=3x��V�h���lT��T{���)�r�"�9*�Ě��D���r���ˣ+����%�U1�����)�,=K�K5�/�[��tۼ�S�����z98���V�x�c���9ޓFdl ����� \E�ܻ�D��aR��dU���KQ�\\(}�U8��J�7�t�.l8 ^���s��	�8�6�����8x�-� ��Xbt��k;,�<ޤ^���H��a��u�B��8���45�6ծKf쿉v�a���uYQ�2<��5��P�{��/媉�\�u� �W��MU��'7�� [բ؟9������`$fJK�����s�s�ͱ���|pPY��*�ά�lQ5y� q[��2.,Nt���>%��Fç���/���Q0�Rm�O���"wo� �\���B/W'��v��'�j�v��9��Ά�N��%i/� 5%��D�'ҕ��*دfp��B�m����n��'"w�5��,lO�R �Ps���C���/��v�w��Zy��f���t0����;f��EkY��}���n���2Kv�^\�;h,��-5�P|+��?k�!%�b|L�=�0�u�T���e_���&WὟ���sK��<6ZR�Q�?p�qd̿z��j���	��卑�2"J�/P�I�I?��G�N3+��Cx���:��-h�u��=guf��ēq]d5v����70��o\��@�y�����x�Φ���r��A٣�آ]��LÙ���KM�Cx������wz�0+Y.e���a)�o(� �U u���
����L߽���n��vP��(�"$�4���'����-�Y��������ۇ���$���YOF�����y�h�+�=��;���^DX/�T���	��%�駇�\���j�'�<�娤��"lf���P���������W��윖nt�H~Y���S}�}�}�zw|��F�3������.���f�h������5/���/�W��| Ѣ�}�4���6������=1�~�׏���H��s���[��+�Q��/Ƈ�2��Q6߳�sC���2�]�F��I�2D�����r�Q�R�\S�"�)�MD|֋�
�f�=f�r�5 ���j:1
I��<���Bg�-&%���^�tf�?������FzL�e��ih�ۗzN/0�d2��a�;*�h�LX��}�׭HZ�<���^NQ�Ͳv�ܭ�u76��oǈ��#7�>�߯zYXA��:���9�'��i�c�,/<���8��U�(]�����J&��O�G�A>�ݭ��>��'��#�c<��d��.m+�0��Bf�������x'��8#l4a��a#�L��ZHz�kf��f�L|�s>��dZ!^`��hn}#��Π��t��]d�K����&����=���HG��B.	�(�N��r�u|a�����[|���':2���c�,o�%���05w1q�U��0��!�K@Q�T��M�km��<�I�3b�K4�P�˓k�Z�p>���-�8���O5� ���폙��iŅ��? ���=:��H#��d%��$���b�H��CAу�(^�Z����4!�\N@)A�8��J�CԚ��o>=ML���#ag�KKV�	d�Q����^-��dy�{�0�� �@2���_�sɇ@,�˓��{6�^$�T���3�LO�������zsC��Ҝ3/�ir�O&�����[�4\�֤޲���u=�"k[���<H��#^��#_V�Ir.J�`u����b��	��׍`i���8��#�Au�����Z�?(d����K��4 ��O�i@��DU�WĊy����nMj�H\���4�Zo���d��W��%J�E�s�v&V0Ƌ�
�ʠ�J)*���R�\TB�����0���p��31��h���:[��x��E��C=u ���X�N�}�tX�dX�s�5l�dR~�y8]��rx�>W>��xf��ҏ�N�6'館�v��o̐2%�	X�+y:uăL�I��\ڋ���7�@3~N<��eO�<�Q�q���US��6��?㢕��=990�?j�d��U\��}U��!��6g�N7i���%��hfA؜�!��^f3J�X���e,}9�8��"^���M_�XO%�wl���y��)��.��*&��)yZf������E��W�B@=9�Z��|���Լ��׮��KK~>#�k޾؉����~���h?����x�[�{�$N�\���5�%�1���f I�u���~s�b�$�#�e#(F�>Uǫ��,U���5��n��-?��� �-zEnT�嵏{�[Y��f���n��e��o��M-:;����?��lCs����#ݥcCe]�i��=��/��C��5��;d���d��3b^�Y�~�|�����k}0DZ�xD�\�,��d���E����~����M�KX��	�A����X~#���Y�7��#���E^
�a���ν�@� #ML-��cK"�e;-�S�C�}�B�4�en�t�~" �=�U��^���e�[��l�`�����sC���I�s0��cU_垩���fB|��j�_+7ރ�nMIc5�6U�/��G�E�M�o���{#CD.G�����,T��ă���/�~�l�Oq8��!,�9O��a����e���HG̭���+|��V�ml.iaO"�-����i�}<��˫C��ތ��凉�i�m�Z�H}���{��'c�~��=���!�$x�v�'A��k�{��J=$
G	$
tج�f�Zs��t�I��ې��
o~�ѬEy8;x���-�
�h�h���?	VAK�$�w�0�-E�Xߙ�1�ٽFtWzcμeXG�Î1�