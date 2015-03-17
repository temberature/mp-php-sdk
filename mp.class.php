<?php
/**
 *    微信公众平台第三方授权PHP-SDK, 官方API部分
 *  @author  Tong <temberature@gmail.com>
 *  @link https://github.com/temberature/mp-php-sdk
 *  @version 0.1
 *  usage:
 *   $options = array(
 *   'token' => '1smzsbkxxni4dGhfz0vZ88UGFGv7Kczo', //填写你设定的key
 *   'encodingaeskey' => 'WXdTheUxyPKOpI6zu52rVhgCuIbCizpAG8OLRAgjunv', //填写加密用的EncodingAESKey
 *   'component_appid' => 'wx9c999e25cabb2a30', //填写高级调用功能的app id
 *   'component_appsecret' => '0c79e1fa963cd80cc0be99b20a18faeb', //填写高级调用功能的密钥
 *   'authorizer_appid' => 'wx58aa023b4bb6da74',
 *   'component_verify_ticket' => $component_verify_ticket,
 *   );
 *   $mpObj = new mp($options);
 *   $mpObj->valid();
 *   $type = $mpObj->getRev()->getRevType();
 *   switch($type) {
 *           case mp::MSGTYPE_TEXT:
 *               $mpObj->text("hello, I'm mp")->reply();
 *               exit;
 *               break;
 *           case mp::MSGTYPE_EVENT:
 *               ....
 *               break;
 *           case mp::MSGTYPE_IMAGE:
 *               ...
 *               break;
 *           default:
 *               $mpObj->text("help info")->reply();
 *   }
 *
 *   //获取菜单操作:
 *   $menu = $mpObj->getMenu();
 *   //设置菜单
 *   $newmenu =  array(
 *           "button"=>
 *               array(
 *                   array('type'=>'click','name'=>'最新消息','key'=>'MENU_KEY_NEWS'),
 *                   array('type'=>'view','name'=>'我要搜索','url'=>'http://www.baidu.com'),
 *                   )
 *          );
 *   $result = $mpObj->createMenu($newmenu);
 */
class Mp extends Wechat
{
    const API_URL_PREFIX = 'https://api.weixin.qq.com/cgi-bin';
    const COMPONENT_AUTH_URL = '/component/api_component_token';
    const PREAUTHCODE_CREATE_URL = '/component/api_create_preauthcode?';
    const AUTH_URL = '/component/api_query_auth?';
    const AUTH_REFRESH_URL = '/component/api_authorizer_token?';
    const AUTH_APP_INFO_URL = '/component/api_get_authorizer_info?';
    const AUTH_APP_OPTION_URL = '/component/api_get_authorizer_option?';
    const AUTH_APP_OPTION_SET_URL = '/component/api_set_authorizer_option?';

    private $token;
    private $encodingAesKey;
    private $encrypt_type;
    private $component_appid;
    private $component_appsecret;
    private $component_verify_ticket;
    private $component_access_token;
    private $pre_auth_code;

    private $unauthorized_appid;
    public $authorizer_appid;
    public $authorizer_refresh_token;
    public $authorizer_access_token;

    public function __construct($options)
    {
        $this->token = isset($options['token']) ? $options['token'] : '';
        $this->encodingAesKey = isset($options['encodingaeskey']) ? $options['encodingaeskey'] : '';
        $this->component_appid = isset($options['component_appid']) ? $options['component_appid'] : '';
        $this->component_appsecret = isset($options['component_appsecret']) ? $options['component_appsecret'] : '';
        $this->appid = isset($options['authorizer_appid']) ? $options['authorizer_appid'] : '';
        $this->component_verify_ticket = isset($options['component_verify_ticket']) ? $options['component_verify_ticket'] : '';
        $this->authorizer_refresh_token = isset($options['authorizer_refresh_token']) ? $options['authorizer_refresh_token'] : '';
    }

    /**
     * For weixin server validation
     */
    private function checkSignature($str = '')
    {
        $signature = isset($_GET["signature"]) ? $_GET["signature"] : '';
        $signature = isset($_GET["msg_signature"]) ? $_GET["msg_signature"] : $signature; //如果存在加密验证则用加密验证段
        $timestamp = isset($_GET["timestamp"]) ? $_GET["timestamp"] : '';
        $nonce = isset($_GET["nonce"]) ? $_GET["nonce"] : '';

        $token = $this->token;
        $tmpArr = array($token, $timestamp, $nonce, $str);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);

        if ($tmpStr == $signature) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * xml格式加密，仅请求为加密方式时再用
     */
    private function generate($encrypt, $signature, $timestamp, $nonce)
    {
        //格式化加密信息
        $format = "<xml>
<Encrypt><![CDATA[%s]]></Encrypt>
<MsgSignature><![CDATA[%s]]></MsgSignature>
<TimeStamp>%s</TimeStamp>
<Nonce><![CDATA[%s]]></Nonce>
</xml>";
        return sprintf($format, $encrypt, $signature, $timestamp, $nonce);
    }
/**
 * 过滤文字回复\r\n换行符
 * @param string $text
 * @return string|mixed
 */
    private function _auto_text_filter($text)
    {
        if (!$this->_text_filter) {
            return $text;
        }

        return str_replace("\r\n", "\n", $text);
    }
/**
 * For weixin server validation
 * 重写Wechat类的方法，替换appid为component_appid；
 * @param bool $return 是否返回
 */
    public function valid($return = false)
    {
        $encryptStr = "";
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $postStr = file_get_contents("php://input");
            $array = (array) simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $this->encrypt_type = isset($_GET["encrypt_type"]) ? $_GET["encrypt_type"] : '';
            if ($this->encrypt_type == 'aes') {
                //aes加密

                $this->log($postStr);
                $encryptStr = $array['Encrypt'];
                $pc = new Prpcrypt($this->encodingAesKey);
                $array = $pc->decrypt($encryptStr, $this->component_appid);
                if (!isset($array[0]) || ($array[0] != 0)) {
                    if (!$return) {
                        die('decrypt error!');
                    } else {
                        return "false1";
                    }
                }
                $this->postxml = $array[1];
                return "123" . $this->postxml . "111";
                if (!$this->component_appid) {
                    $this->component_appid = $array[2];
                }
//为了没有appid的订阅号。
            } else {
                $this->postxml = $postStr;
            }
        } elseif (isset($_GET["echostr"])) {
            $echoStr = $_GET["echostr"];
            if ($return) {
                if ($this->checkSignature()) {
                    return $echoStr;
                } else {
                    return "false2";
                }

            } else {
                if ($this->checkSignature()) {
                    die($echoStr);
                } else {
                    die('no access');
                }

            }
        }

        if (!$this->checkSignature($encryptStr)) {
            if ($return) {
                return "false3";
            } else {
                die('no access');
            }

        }
        return "true123";
    }

    /**
     * 获取微信服务器发来的component_verify_ticket
     * Example: $obj->getRev()->getRevComponentVerifyTicket();
     */
    public function getRevComponentVerifyTicket()
    {
        if (isset($this->_receive['ComponentVerifyTicket'])) {
            $this->component_verify_ticket = $this->_receive['ComponentVerifyTicket'];
            return $this->component_verify_ticket;
        } else {
            return false;
        }
    }
    /**
     * 获取微信服务器发来的unauthorized_appid
     * Example: $obj->getRev()->getUnauthAppid();
     */
    public function getUnauthAppid()
    {
        if (isset($this->_receive['AuthorizerAppid'])) {
            $this->unauthorized_appid = $this->_receive['AuthorizerAppid'];
            return $this->unauthorized_appid;
        } else {
            return false;
        }
    }
/**
 *
 * 回复微信服务器, 此函数支持链式操作
 * Example: $this->text('msg tips')->reply();
 * @param string $msg 要发送的信息, 默认取$this->_msg
 * @param bool $return 是否返回信息而不抛出到浏览器 默认:否
 */
    public function reply($msg = array(), $return = false)
    {
        if (empty($msg)) {
            if (empty($this->_msg)) //防止不先设置回复内容，直接调用reply方法导致异常
            {
                return false;
            }

            $msg = $this->_msg;
        }
        $xmldata = $this->xml_encode($msg);
        $this->log($xmldata);
        if ($this->encrypt_type == 'aes') {
            //如果来源消息为加密方式
            $pc = new Prpcrypt($this->encodingAesKey);
            $array = $pc->encrypt($xmldata, $this->component_appid);
            $ret = $array[0];
            if ($ret != 0) {
                $this->log('encrypt err!');
                return false;
            }
            $timestamp = time();
            $nonce = rand(77, 999) * rand(605, 888) * rand(11, 99);
            $encrypt = $array[1];
            $tmpArr = array($this->token, $timestamp, $nonce, $encrypt); //比普通公众平台多了一个加密的密文
            sort($tmpArr, SORT_STRING);
            $signature = implode($tmpArr);
            $signature = sha1($signature);
            $xmldata = $this->generate($encrypt, $signature, $timestamp, $nonce);
            $this->log($xmldata);
        }
        if ($return) {
            return $xmldata;
        } else {
            echo $xmldata;
        }

    }
    /**
     * GET 请求
     * @param string $url
     */
    private function http_get($url)
    {
        $oCurl = curl_init();
        if (stripos($url, "https://") !== false) {
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);
        if (intval($aStatus["http_code"]) == 200) {
            return $sContent;
        } else {
            return false;
        }
    }
/**
 * POST 请求
 * @param string $url
 * @param array $param
 * @param boolean $post_file 是否文件上传
 * @return string content
 */
    private function http_post($url, $param, $post_file = false)
    {
        $oCurl = curl_init();
        if (stripos($url, "https://") !== false) {
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        if (is_string($param) || $post_file) {
            $strPOST = $param;
        } else {
            $aPOST = array();
            foreach ($param as $key => $val) {
                $aPOST[] = $key . "=" . urlencode($val);
            }
            $strPOST = join("&", $aPOST);
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($oCurl, CURLOPT_POST, true);
        curl_setopt($oCurl, CURLOPT_POSTFIELDS, $strPOST);
        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);
        if (intval($aStatus["http_code"]) == 200) {
            return $sContent;
        } else {
            return false;
        }
    }

/**
 * 获取component_access_token
 * @param string $component_appid 如在类初始化时已提供，则可为空
 * @param string $component_appsecret 如在类初始化时已提供，则可为空
 */
    public function checkComponentAuth($component_appid = '', $component_appsecret = '')
    {
        if (!$component_appid || !$component_appsecret) {
            $component_appid = $this->component_appid;
            $component_appsecret = $this->component_appsecret;
        }

        // $authname = 'wechat_access_token' . $appid;
        // if ($rs = $this->getCache($authname)) {
        //     $this->access_token = $rs;
        //     return $rs;
        // }
        $data = array(
            "component_appid" => $component_appid,
            "component_appsecret" => $component_appsecret,
            "component_verify_ticket" => $this->component_verify_ticket,
        );

        $result = $this->http_post(self::API_URL_PREFIX . self::COMPONENT_AUTH_URL, self::json_encode($data));

        if ($result) {
            $json = json_decode($result, true);
            if (!$json || isset($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }
            $this->component_access_token = $json['component_access_token'];
            $expire = $json['expires_in'] ? intval($json['expires_in']) - 100 : 3600;
            // $this->setCache($authname, $this->component_access_token, $expire);
            return $this->component_access_token;
        }
        return false;
    }

/**
 * 获取预授权码
 * @param string $component_appid
 * @return string
 */
    public function getPreAuthCode($component_appid = '')
    {
        if (!$component_appid) {
            $component_appid = $this->component_appid;
        }
        if (!$this->component_access_token && !$this->checkComponentAuth()) {
            return false;
        }

        $data = array(
            "component_appid" => $component_appid,
        );

        $result = $this->http_post(self::API_URL_PREFIX . self::PREAUTHCODE_CREATE_URL . 'component_access_token=' . $this->component_access_token, self::json_encode($data));
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }
            $this->pre_auth_code = $json['pre_auth_code'];

            return $this->pre_auth_code;
        }
        return false;
    }

/**
 * 通过授权码auth_code获取authorizer_access_token
 * @return array {authorizer_appid,authorizer_access_token,expires_in,authorizer_refresh_token,func_info[]}
 */
    public function getAuthRefreshToken($component_appid = '')
    {
        $auth_code = isset($_GET['auth_code']) ? $_GET['auth_code'] : '';
        echo "string";
        if (!$auth_code) {
            return false;
        }

        if (!$this->component_access_token && !$this->checkComponentAuth()) {
            return false;
        }

        if (!$component_appid || !$authorization_code) {
            $component_appid = $this->component_appid;
        }

        $data = array(
            "component_appid" => $component_appid,
            "authorization_code" => $auth_code,
        );
        $result = $this->http_post(self::API_URL_PREFIX . self::AUTH_URL . 'component_access_token=' . $this->component_access_token, self::json_encode($data));

        if ($result) {
            $json = json_decode($result, true);
            if (!$json || isset($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }
            $this->authorizer_appid = $json['authorization_info']['authorizer_appid'];
            $this->authorizer_access_token = $json['authorization_info']['authorizer_access_token'];
            $this->authorizer_refresh_token = $json['authorization_info']['authorizer_refresh_token'];
            // $this->setCache($authname, $this->access_token, $expire);
            return $json['authorization_info'];
        }
        return false;
    }

    /**
     * 刷新authorizer_access_token
     * Example: $obj->setAuthRefreshToken($authorizer_refresh_token)->getAuthAccessToken();
     * @param string $component_appid 如在类初始化时已提供，则可为空
     * @param string $authorizer_appid 如在类初始化时已提供，则可为空
     * @return string
     */
    public function getAuthAccessToken($component_appid = '', $authorizer_appid = '')
    {
        if (!$component_appid || !$authorizer_appid) {
            $component_appid = $this->component_appid;
            $authorizer_appid = $this->authorizer_appid;

        }
        $data = array(
            "component_appid" => $component_appid,
            "authorizer_appid" => $authorizer_appid,
            "authorizer_refresh_token" => $this->authorizer_refresh_token,
        );
        $result = $this->http_post(self::API_URL_PREFIX . self::AUTH_REFRESH_URL . 'component_access_token=' . $this->component_access_token, self::json_encode($data));
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }
            $this->authorizer_access_token = $json['authorizer_access_token'];
            $expire = $json['expires_in'] ? intval($json['expires_in']) - 100 : 3600;
            // $this->setCache($authname, $this->access_token, $expire);
            $this->authorizer_refresh_token = $json['authorizer_refresh_token'];

            return $this->authorizer_access_token;
        }
        return false;

    }
/**
 * 获取access_token
 * 重写Wechat类的方法
 */
    public function checkAuth()
    {
        $this->access_token = $this->getAuthAccessToken();
        return $this->access_token;
    }
/**
 * 获取授权应用的信息
 * @param string $component_appid 如在类初始化时已提供，则可为空
 * @param string $authorizer_appid 如在类初始化时已提供，则可为空
 * @return array
 */
    public function getAuthAppInfo($component_appid = '', $authorizer_appid = '')
    {
        if (!$component_appid || !$authorizer_appid) {
            $component_appid = $this->component_appid;
            $authorizer_appid = $this->authorizer_appid;

        }
        $data = array(
            "component_appid" => $component_appid,
            "authorizer_appid" => $authorizer_appid,

        );
        $result = $this->http_post(self::API_URL_PREFIX . self::AUTH_APP_INFO_URL . 'component_access_token=' . $this->component_access_token, self::json_encode($data));
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }
            return $json["authorizer_info"];

        }
        return false;
    }
    /**
     * 获取授权应用的选项设置
     * @param string $option_name
     * @param string $component_appid 如在类初始化时已提供，则可为空
     * @param string $authorizer_appid 如在类初始化时已提供，则可为空
     * @return string
     */
    public function getAuthAppOption($option_name, $component_appid = '', $authorizer_appid = '')
    {
        if (!$component_appid || !$authorizer_appid) {
            $component_appid = $this->component_appid;
            $authorizer_appid = $this->authorizer_appid;

        }
        if (!$this->component_access_token && !$this->checkComponentAuth()) {
            return "false0";
        }
        $data = array(
            "component_appid" => $component_appid,
            "authorizer_appid" => $authorizer_appid,
            "option_name" => $option_name,
        );
        $url = self::API_URL_PREFIX . self::AUTH_APP_OPTION_URL . 'component_access_token=' . $this->component_access_token;
        $result = $this->http_post($url, self::json_encode($data));
        var_dump($url);
        var_dump($result);
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return "false1";
            }
            $option_value = $json["option_value"];
            return $option_value;

        }
        return "false2";
    }
/**
 * 设置授权应用的选项
 * @param string $option_name
 * @param string $option_value
 * @param string $component_appid 如在类初始化时已提供，则可为空
 * @param string $authorizer_appid 如在类初始化时已提供，则可为空
 * @return string
 */
    public function setAuthAppOption($option_name, $option_value, $component_appid = '', $authorizer_appid = '')
    {
        if (!$component_appid || !$authorizer_appid) {
            $component_appid = $this->component_appid;
            $authorizer_appid = $this->authorizer_appid;

        }
        if (!$this->component_access_token && !$this->checkComponentAuth()) {
            return false;
        }
        $data = array(
            "component_appid" => $component_appid,
            "authorizer_appid" => $authorizer_appid,
            "option_name" => $option_name,
            "option_value" => $option_value,
        );
        $result = $this->http_post(self::API_URL_PREFIX . self::AUTH_APP_OPTION_SET_URL . 'component_access_token=' . $this->component_access_token, self::json_encode($data));
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }
            return true;
        }
        return false;
    }}
