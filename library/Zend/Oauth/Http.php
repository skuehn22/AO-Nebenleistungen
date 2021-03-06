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
 * @package    Zend_Oauth
 * @copyright  Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */

/** Zend_Oauth_Http_Utility */
require_once 'Zend/Oauth/Http/Utility.php';

/** Zend_Uri_Http */
require_once 'Zend/Uri/Http.php';

/**
 * @category   Zend
 * @package    Zend_Oauth
 * @copyright  Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Oauth_Http
{
    /**
     * Array of all custom service parameters to be sent in the HTTP request
     * in addition to the usual OAuth parameters.
     *
     * @var array
     */
    protected $_parameters = array();

    /**
     * Reference to the Zend_Oauth_Consumer instance in use.
     *
     * @var string
     */
    protected $_consumer = null;

    /**
     * OAuth specifies three request methods, this holds the current preferred
     * one which by default uses the Authorization Header approach for passing
     * OAuth parameters, and a POST body for non-OAuth custom parameters.
     *
     * @var string
     */
    protected $_preferredRequestScheme = null;

    /**
     * Request Method for the HTTP Request.
     *
     * @var string
     */
    protected $_preferredRequestMethod = Zend_Oauth::POST;

    /**
     * Instance of the general Zend_Oauth_Http_Utility class.
     *
     * @var Zend_Oauth_Http_Utility
     */
    protected $_httpUtility = null;

    /**
     * Constructor
     *
     * @param  Zend_Oauth_Consumer $consumer
     * @param  null|array $parameters
     * @param  null|Zend_Oauth_Http_Utility $utility
     * @return void
     */
    public function __construct(
        Zend_Oauth_Consumer $consumer,
        array $parameters = null,
        Zend_Oauth_Http_Utility $utility = null
    ) {
        $this->_consumer = $consumer;
        $this->_preferredRequestScheme = $this->_consumer->getRequestScheme();
        if ($parameters !== null) {
            $this->setParameters($parameters);
        }
        if ($utility !== null) {
            $this->_httpUtility = $utility;
        } else {
            $this->_httpUtility = new Zend_Oauth_Http_Utility;
        }
    }

    /**
     * Set a preferred HTTP request method.
     *
     * @param  string $method
     * @return Zend_Oauth_Http
     */
    public function setMethod($method)
    {
        if (!in_array($method, array(Zend_Oauth::POST, Zend_Oauth::GET))) {
            require_once 'Zend/Oauth/Exception.php';
            throw new Zend_Oauth_Exception('invalid HTTP method: ' . $method);
        }
        $this->_preferredRequestMethod = $method;
        return $this;
    }

    /**
     * Preferred HTTP request method accessor.
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->_preferredRequestMethod;
    }

    /**
     * Mutator to set an array of custom parameters for the HTTP request.
     *
     * @param  array $customServiceParameters
     * @return Zend_Oauth_Http
     */
    public function setParameters(array $customServiceParameters)
    {
        $this->_parameters = $customServiceParameters;
        return $this;
    }

    /**
     * Accessor for an array of custom parameters.
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->_parameters;
    }

    /**
     * Return the Consumer instance in use.
     *
     * @return Zend_Oauth_Consumer
     */
    public function getConsumer()
    {
   �m�P 2����o��G`W6��a�ȇ�u�t�,�V�bI8}0iy�M8x���)#jDO��pCxq>�ț+�b��0���La�э��۲@Tέ�E-*������(���4�w�B�׉w��Js0.B.1�` �a���i�*2���s��������T�A����L�Y��0��ד]��f\��tcgJGg3(s,���@ϡt{�:Gi3�s�NK[r��ݜZ��jA|t荏5h��/ݳ��<�� 쀔ޮ++3����ZW�f�a\$Չ�W���8&%=�H$��_�e9���D�9��A�I�
�M	H	��oC�����׉��/TIKK<A��T+��6!֜��)=��js�U����^�clR��h.ה���ƽl�A�q�f�����k�O}D�,c�:s��s�2��}T��BJ����l�>��_ĕ�Cq���Gw#��о��^��5��K�j4#l��qP�ZT��C���|��tf�(�����T��i�|
k�Q���djF�Pz����!�Ҧ`�w��)k��dB�Wj`��3)E�2���F�OZ����-��c�v2V۰6J?�>��af�-�q�UO ��<�*q#[V��C�M�}R�jiRI\H��1�jx�'��Q�HYm�|��d��|L�k�:�͚ͤ:Mڠ�l1�rrKV�>�r_��OO��}�������ga�-=_�a?�':��^�"v2�� ff�ʑX��gXl�S��h�MQe��JƓ�Ǿ�u����>���{[��}�"_O��V'����9�[W��p�
ȭ���x�W�$24�7�~c��'�oR���{�G̄J\�W��o<��ΈU�_�PZ|�'�� ��	��b<���IG�ϧ~l�b�H��{����Ha��E�ܤ�v�KR��'Qw"o�{�z;Ȝ,|�d�t`w>rf6onsrJ)�M[+�����9��%��"V�9MS,��P�º:QPr����[�R�C ��Y������^w��)�p�TPGMq;ݸ���&5���}Ȓ!
K���U#�R5��h":Cޙ�+���e���_՜(P�fS�f֒��V���"wＺ���Z�j��a�Xi ��|�)�o��U��c��h���'���H|^
F~�ᱡ�~�[���	l�vN�-��wH��u�q�&�ퟜee�~�_g��V���4�o-7�F+���b�B��z�8g���w�/=~��q�-���+�	D㱈�-���H%�"�ɳU�B��:�q��)�HvZ��ZIU.#u3�6�l����-lb�?g�ON��"S�A�HyU��%�ȥ/e5\�
4A��a�*�o����/GN
�f@&�l�C�Ny�J�/P�+^��J:����`�{S���d��$I�^�d,��&�߲�Kd�%��&�N2��h�-.u��Ee�q
Wp���t�@��;H����D�?�lL��� ���\*��m'#xa͙�E�P��Z�g2�'��c�I�ҋe�c��4��"skD�ekuA,���ǂ��fu��s��@�����A�r~�i�jm\�|�m�-����@úQzYa喩3Qu�o^�aNDИmO"�0��g{��L@}/�Y�-T�	�K���J��'�8��O������A4P­���ˬ��I�BB�?Ph�&:�>���qO.�/�},"�rS�3R��؄������2ژ�ǶJ��'RL���gQ����T�8\�柰��-��?�_EV��󈉵 T� �o&}ǰ��&V'�E^î�ĕ��q����b���N�#$��֬B��8�X!_��H���E
��K�b���U��Eh r�3}H�ew(Khǎ�)�N4\A�]b�`�I!%������్\�W.����_���5�A%�>QI˱�L�x�<�����^�0�1�0�����6߮a8-��6�
��?Z&H`�{�\|�O^����Z�W�ǁ�g���C�Ηõ"��\�v��=	�n&����<⓿(�=��"^��Q�(S�l�k�C� Z�j�m����	�0��~���2nL��x�e��kn��R��G���mE�6Adg`�K�m�a%'5q�1��[������m�������=38��<����8�� ��,ίZ)U쟈��:LOk��Y8��;qI/e܌��{]����Pw�o�i�tB�����9��C�L�b#�\�/�v|����[����uN�h�4y%�ʎ]{��V��䬰��h��P��$�=���t�|���|r2>�H|���2];@}ǽ{�������r�z�;��]/�e�p�0L����#��ǡg����_��Μ�P����n;Hn!fP����'uHQ�b�Ō�AK=�
�5i"^"�ZNq�"Y�h�CF��d���5��cV�k�vܤ�;li|�}<���T���8ep��M�⠞ED�F^X�`����ȁ-�M��Sc�ޝ����W��礫bB�|�Ύ�	�\�� ��`���~�R0E���)��JI;-[ �8M'ёoQ�?�y{`�	A� ����
:I���cʄ��"(���<��P]Tܧ3���(wY*��CD�hnǧǎ(��$�Dćnvx����]����3Mwv�x^:Y�Z&$�������y��y2zb�Z�t��t`Z�xk������v���F�$��4��˙ʏ�}�\x���
��^�z,��]�9�\�Xx8���\e�+��%����)߳�l��y�-����]KH��V!���6/�M+5kq�o��j94�A~{|~_�oGcd��do<~�n&��ML�6o��_d��l[�`�:�����IÏcQ ��Kþ�j�c`�+{XH��ɜ)�H��
&���Ʋ!�MM��+�1鲎�wQ~���Fg��V�t6g�z<?�7�J�����;�h�-Ԭ6<���>�9�0��Uw�򱸫(VO`��/�S\�(�s���K5�Ɇ3u��|R��3H���%dύ
KHx��դv�T�{���mQ�	#��.�RP�K��f�NO^��2e��>Ae�<�����ڋT�� p���~j�`;�T�#l�Z��)��6>;��)�`�;��e���0ȋ81��8�j9~ru��B��tU{�7<�-����%�"p���zg#�-��ע��Tgn�3dC�Eh��iT�؅&p��� 8�j�s��
Z>~�pl�L�ؖ^�����i��;����m�M�q>�+��Ƭ�n�hn������8z�׊�H(/Aļ���x��_OZ��bX�|%��O�'L�����z"7"�=��ܡ���kO����#ۓ��2�s(�1P�����\�@Hyj5�ܲ�6BR���vA̳�����4bp���R���������ޜ�G~�$5(٨6t|���uF� �� �"��[�-ߨ����4�j��yE7fM5!3r)^L��rF��`�,��z8�Q��U��p>3�}(��t�X���ii�ptN�8�S�,�&!�	Dg����I�٤\n�@�]�������xӪ�o�����t��÷�,a$���*�Z��?�q���l�����whЌ~&�;�d�88�+h/N��`�nT��(�!���,�!���}�St�����bR������1��3n' (�����`D��~y����;�Xc��8�EU]נ ��C��Dh���ö��,�b!�?�)�7%-��_	�]@�� �;v�P�Р�S%-)��!!���T^l�Pe!�hXH����K)��)��hʧD�r��7������y��]�V�� �/����O�s�p��Cm-�(qu!�͡) ȑ!�N��d%��G�/�#z�(�HM�&�<�a�',t��y\6ǳ�dr��E��kD���M�)~"?��*L�\w�?9me!c��-by��/h������5�k��m/ׂ����t2�lIh�c��p��O]R�xo�W�S�Y���#�'v���(AWog��RõE�^�c�[� 9�PmS�]G.TѱL.'��;Ǚl*h1�c��@�]T�Hj��3r�bm�Ι�-��0@�I�	�@�Lv�U�=�ao]bP�}#̭8��� ��_���R����?�g��"e	�o������-T]5���i�Zһ�|O�i͇����b\�v�z\��iJh#Wo�{���5n���-l� ]⎱n(����޽U{þ��P���'�xpDS�W�V�����ƪm�.�u8q#�Y|�}Y,|x�� �U��Ó�Ҡ�F�?[�6z��1����{�2]�$D�y{.@8��,f���Xk>�"��F��������Kڼit��,�8$��xd��b�E�Ȍ��R��|˶�br�jdL���nD1ҵ��>�[&T�Z�������;�AL�ʁ5����|�����42�F{6��qW4�E�V�\����������}I���#����O�1�r����ܔE�#N�ǥ+��5 �bq���T�a>2֘�j��Yк����r�a�-�A�'��&��@C��c�p�-2��n�z0�w���<.w2F��:+��_2�S�	h=���׎[���#t�6�M/?��R�Ū�z�sm��wu�c��~���]I���+�qs�G��3�Sl2�ӣ���
�~:35�\N�U8�Q9ڿ9���.b���~2�×_�<{�Q\x����r0���dO�ꢌпnJ�0�˿k^"Y���Z!�Y͑���lJ�l�ej:= A�4�s�����?�a%��Z���?��[r���%u	�?�b��S��o;��ł%Bn\w��X�X���?�xZv�3���������;�sA�H��^� (	qR��Q�52Ŵ	Z�ʡ-2��2�(?+-	��u�5�y���ݖ�mp1~�}�C�/�2Q��W�:YT�ZI�>�:�I�y9������<B�S�f�z�j7g�F���B_����ĎE~���hϩ����vb[|�.vb�t���\V���HG�Up��*����8���k���5�>qw9>��(˂hb3>{4/ĉ��fg^�1W�s��'�m��h��pd��1��<|_��J�s ��q+���t��E̫��e3��M.�Í'�y�'�'ۯ}E��iC&鈅�%�A:�	8�n��}�V	+$;��:��Sa��0P:��Kp�"�F�M�[���`�ċs�e�=Я�4���"�o|�������� ��ւ��s�9�������
bU7lR�1�q@�9��HKs�m"U�3�~Vqd��M�9>��>9K(&���� W�G�&�,�!^��ɻW���K5IyF �2���f��r`r	���5���F�^��V�Q�O�RV�Y�ާ;>X�}���@ɼ���F�j���y���#�I��0�#���̖�:h�8��e�*yT�YE>RN ��q�|�yD��E�NuFZ��hkg������;,�������G>���}`�ݛ�|�$W���4{�@5)��&��l�|NJ�
����#4vhi�-E��7�{��6�!�0_hc�^_\�#��@L:�����V!��[�H��	�<�6
Q�\�]�X�T���u��tJ��(�{8�!�?q�-ADA#�Ţa�4���l0��M�,��rP����or�f����*�>�6(*c�Di4W�����]6���'6��E{�w}L�F��Gt���Ys𡰔
=�M'��d��N��D�R��H�7�%a�4�$�v�����c+��6�&�����B9�"�6�v�y�J���t0�j���w�M}�3A�8��zӔ����
g��f)>aI��EM��ü�J�7��-d>�;�D3T4W�@)�����i*UP¤־[��3fm�m�j2g�190��%-,a�/��_���}�2 U��+/�l�i���i&�X�����zW�Ub&��vlG�2��+d��a��z5-�-6I6$�N��X~,Nf��������?:n�Vm����f�c�3l1X3R1Y��a�t�	k���B捄��m��yv����/6�EX��y^���I{�e�g/KFF�k�O}��;`�������Y�ڇ��Tۗ����$���c���E~i�\� Ȩ	�j��w&i}��y��	��^�t�8ť�5��_�A"�򩷁��q��l$��Hx���R��0{�-΍�i�H�Q�-���?C�	t�����}4�$�������b���H��ܸ�D��d/�>�m@�x%����q�������������Lǃ�)#�NzQõ�Hy��yߠ�l'���ښ��!tR�����H��KiJ�k7J�,���`B�iVqQ���Iw]e�Ȳ��fۤV���Oi��`)�`��aM�H��$S����M��W��gLFP�(��Poӏ��o�k^b�Ĝpen	P����p��ﭨ���/�Rf�֔h��c�c@;9{,��������q����ںM;m���X�j��_��/<%6�:��LU�5�U�>���%�*?
{���?x���"��F�'��Ez�?�HR@��q�Cͨ�AR�[��x�q+�eE��} �+��*�dȀ���F���D��/�}Z�`�2�d�k��i# ���-�6�rQVW
�A��=C�H'FQE������_V�d�ƨh�Y+���*Ҡ����,0C!�mL�k�;$��3Զ��Z�(��H��H�kݬO�3HMS�2f��b��͚0��,�T��a{W|�����h$��%���V�{,ۀL�m�e�/5�g�U����!�����|e���&E�0�2{)�K��g��A�/h-	ff"N�E⼁�{Wdi���rh����~a�US�⋒��Hd�3tW�jL=@�1�7�RZ�i1c1���&Ō�4�O��PQe"�NQe�\@!�ʚY�X[#cy;��3�k5''CZ���z#�q_��	�e_^��s�c�T��B�.	���6�e�<9x�*����o�TU��-i*sf���zOm���ҁ�f��KP45���Y�qW��ĲTj�n[Z��E��==��
���/@�(U�#�NL��ن�G:1c3����8�y��^O��p0j9�eѹ��)��4ݿ����Ѫ��f��mRڻ��#�c���z30C�<g���v��=F�R���OǕS`������j ��U+챕�q����f�wn�3��τ�j���l����g�n�>~�}��YdQ`$!�dv�`4��Mf���B<��g�sˈ�I�t`~L!op�����8^�N�d���O�����U#F�X��><l�K@C���Ώ���W3��Ⱦ���w�����J�#�o ��ݎ?�fD�aA�6k�W��8$�\6;3.l^����d9�^��<�B��¿�VO��X/���X�5D���p���E-�e{ȍS�րq�K�\����q�o����K�_iֈ0�y9��t6&Ϩ���.j)ǖD4�1odQ�,�0��~޽֢�!̙yx�j�s��۩��� ���!�����;��p�^�N��m&�z��	s��66s�ncC�����N�4 ���*���g>�"�ӌܳ���J]��b���.�h&��[d��T�O�)5��[���i�,ߕC쵃���%��0q\��{��[8�!�[:9��8�/D�9�	�	V�� D}�)��=�O��ⓙCD*�8&Y���
����w���H;�i�{���\��|��U�2��[��m(�ɑR��Ki'�n���i���ul�m�tLV����Uꔨ<�K��m>���~��22{�F�e�o�:�Z�)�e����`Nx�dm@xf��7��p�7��v�v�ef�R�u�9�Te��5L*C���0,�1CV��S̸��dS���yB&|�g�q��͕g�b�	�2�g��]e��#��L3�<�� ��3�)x�K�fᐳd����D�;�rX<�g��ivx���<������>�e���Nee��>����_�^:�J�Ҏ���c+f��xa�y��<\g���!��/�KVQX$�C&�2|{�r3��C|ީ��PX��\F��ޑL\%��;�N	"N�V��G���|[/��A���hk`l�u9��T�&E/���ĕ�-��h|V�Xw��X����N嗈?"�α�질�F2�m5�B�~��<Z���x�IPv��J�vJ�<tHg��N	�w1�S ��y��|��<���.���9�E��������'X�ͱ+�a���)p7ɼ@$Pdе	�z�u0��:�<͡��ԏ9��$T��>���mY��$ν�|��}��@&��0��RG�aD0�Ǝ`o �KkQ�.��i3a�T�MD��ÊV��S�v��d*������:n�Fo��	�.���۳���� ъ�л>�1�o��"����);�Yʮhz��vE�D������<f�"}�V�9�y�`����\U���d&:�:c��2;J�M��k���X������'b����v�ZW�3)x��	%�K��u�}��1�I�\B��l(l@0�6.`�n��SS�K�����5�⸪C�,蹇���2 ~��y�y�Rp9��D�
�S��v��u�aCd�}�;c�DJ_�t{nzk��x�G�=�>�t���zN1a%�,�Q$�T`�<��~��xa �&?����|�i��H��v-��WB0h��xd�={�F�ޘ�Ea1�^�(h�ߩR�S)���E�C�|��jv~	�$7�4����t�_OVo�p���TE�b�(���~bE�Ⱥ�#5m��T>��@T�d�?�X<�ZfBW'8m���߂�2,��4܃Z#�h�Z�
ٷ����?�ȁ���kd���@���U98��1��=Gp/��D�A� �H�P�-q���H�!�y��>Z9W��� B��χ섋�����[��N�_�n�v�Z�c�D^�(].)1�Q��nX1zHN�{8��%������a=�&)-��`r您~�}%P)/�1G3h끨�*��<?�T��I���A�{(l�aaSY�q�`�x�۠׾^��ioX�o]�5��h�;�0"��ơ��3^��O��")��m+G7��X���)�Q%�|�X@��5S�yk岐�"Z����.�D���u�3����9���̏�h�� �V�ۆ#U>�?�	����/.���TEd5#��J��4��hf�Yn~7�?˪�a��	������9K��s�μ)�_h_�]�E���K2ؒ	;#�$�_�0H�40��w+�Q��k���ߙ�v�34�BYf�o'Cƙ|�A#��$����ß��C@b)���h8�M���d9^7�|\�8�/N�a��e =+�cR�<t�k�.�;�7�O0'q
��tUKr����ӟ^����O)�x}R[�-�k��'��̻��⍞��A���/Q}��ǫ:X�o��C�D,�߉�� L�w7=�+�HL-�zL�����	��MGUb�0uBiF�	56�B.������>;]T`19z�Oi�����/t������>�{;xx��q��Z���5���U|���&��dZ�uVΣ�y_CJO�E>o��?�?#Q]<tA/���z�=�G\�������:ȍY��.K�e)�)�Fi�njz<�mw��\r��6 �aM	7O�&Ҧ�&<�[x�{�N:����>�¤�j	f�ֳ�:�Iߝ�H6���	l�qB/��lN>i�iļ3/mn�
K0h�X�uP��'����ω>����v���Ȼ8��ٓ � �s4�C%`��^�uG�W�9�� �����C�nu����E�\�����Zu�N �V���2�2��'��]@�SX\Z{��l_{��p�����!���k>������e�{�^?�h�>٩�s�pK���Pu���VyTT��B�j�٣��ѝ�ID�&P*����ͥ�l o�\�#��&f��&��,B��d<�!�rK�6�S{�d���4���������%z�>���<%C��\�V!���m�nj�r��^7�����|���	K腷�ka ')maa�y�;6�7V�U�{�2���	|��Ǝ�6�����nf���k�QJ� �Dl{<tK�)�:^x�|���}�7���1~�0e^�� � fH���D��3m��N��	$���C�s{��.�`]p6���К��m��Y��v���/����l*�����&�i���K����'֬��Ze[L��&�Ym�z�&3N���=쁷����p��떒cw��&�����sQN�ƋR;�������A
k*.��l�/����/j���K�Y��2OK0f�g�w!�BH��V/��TJ�����㴺�R��f�G%T����MC6{�O�*���Z�#���[��o^e���r��7-v����QE�b����F��R؁���M����o���ĥ������[�Pnds&��Eta�ȳ�S\S1�9nK�5|��|O��GC}�P�m����7�������"�x���|�U��Z=�s���]7��ĳ�YP^i��)���.v�W�4k-8� ����DZ�ʨۤ�x��J�FK{>T����cI<V,d)7����$
r�O�D���p~�O0�&�_o5�a�Ř�֒���J�N�OC�Rx�׮��o�ݑ90{Er8� �ǘV|�����,��K��u"/�����EWe����s�sP��}Pm���1`t�x�Wu��K�D�X���Ӿ�UsM��-5�m�]�Pmu�Ta�R&6��j3�۸�ܼ&�.��$����Gx���W�#��'�މ��F���<�W��#2Q��A;��+m I�<z�p�|�{�CW#Q�b�/�
�1�22�(�Ć����4��`
��=[��'ۀ1���r�`�W���.��<�0vj�)ы�$6���m�՘,Q����?�	"�<�)x65oJ}%H����p�g0�[�0:��\�xlʼ�Yݝ�9Ӿ� �T3uj�iSp������6%?50�\D=��Z����^��'�[/���ݵ�����HG�7�!�b���~�]�F�����e�z�r�ڦ�/�hC���u8 3b������}yN"���8
>��RU=��p:���^\R�ݩ���RV��%$�-��#��@I���̧�bd�5H��iu��)M�Ĳco�Hl�>��^
^V�c�m+�LħQҨ��H�!������-�GZ����\����/�m��3i���춰2�.YeO��V�Gi+���-�7v���.�1����9�g�T�@��gQ�}�����zy�@�Z=��INUNI�	#�>O�)'DjΕ!�H��6e�(�zs���ZĤ�sN��G4ռ`��]�u4m��*�0���>n%�]���4��?��AK��X25֛[��2 �ځv�o�T�3u�"M�(b�?Di�P^�u+��"#׸LG/)X�.�N3�Ҍ�Le;HW/�-�i����i��P�0^�yf�K�o���z/u�����Kcګ���x��e���� h��H}eD�˗4�����VU$iW���������y��!�GI�L%
jy�X�"�E�؟Q��;�Ghl|�܇O`uf�J4f��"9W'��F�x�s��_�}�Tjh#Q��X��s���$(.�!像E2'��$�ҁ�z'Qu_�H��D��&(u
���@�7ɰ1^a,r�φ��Pg�#�`؉������$cQ�)2��m�dO�Kr�~�B`�_C<�^2in��p��| ����
�S~���f���߱{�KvM�2n�*(��F�u%uY
��t��lvf'�(�Oj�'.�<}t���찛=\�&�8����f
��k�F̔ ��u�q*5.�X�Mu�$-��"�+�-!r���X���M�D5�ߑNK!�HN�������)�6h��ɟ�������-`<D�A!����IP��h�ԔkHϽ�ɂDli�ۉ���B�ސ�dO������.Wz����D�'�01���^T��)nˮ{6�*&\���w4������NqwR���K�䯿J�"�3j��F<�
��#�,��$Y� q�|��0�E;��zHS��Ѣ���&v��TV^��߂]�E�f�KG�����>����~