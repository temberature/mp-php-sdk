# mp-php-sdk
微信公众平台第三方授权php开发包, weixin mp developer SDK.

依赖https://github.com/dodgepudding/wechat-php-sdk
需要对wechat类属性做少量修改
protected $appid;
protected $access_token;
protected $postxml;
protected $_msg;
protected $_funcflag = false;
protected $_receive;
protected $_text_filter = true;
目前调用接口接收消息、回复消息、自定义菜单管理已实现，
网页授权、jsapi待完成。

#使用详解
https://open.weixin.qq.com/cgi-bin/frame?t=home/wx_plugin_tmpl&lang=zh_CN&token=c5052940fea95a1f27e0cb4ba709901be5b309de

https://open.weixin.qq.com/cgi-bin/showdocument?action=dir_list&t=resource/res_list&verify=1&id=open1419318292&lang=zh_CN

#官方api类库
##第三方特殊的功能
（其他功能见wechat.class.php)
获取第三方平台access_token 
获取预授权码  
使用授权码换取公众号的授权信息 
获取（刷新）授权公众号的令牌  
获取授权方信息 
获取授权方的选项设置信息    
设置授权方的选项信息  
推送component_verify_ticket协议 
推送取消授权通知    
##初始化动作
$options = array(
    'token' => '1smzsbkxxhfz0vZ88UGFGv7Kczo', //填写第三方的key
    'encodingaeskey' => 'WXdTheUxyPKOpI6zuhgCuIbCizpAG8OLRAgjunv', //填写第三方加密用的EncodingAESKey
    'component_appid' => 'wx9c25cabb2a30', //填写第三方的app id
    'component_appsecret' => '0c79e1f80cc0be99b20a18faeb', //填写第三方的密钥
    'authorizer_appid' => 'wx58aa023bda74',//根据需要初始化
    'component_verify_ticket' => ”eSBg9VPmFI43OvUHCv5ofzMGvNCG3F0LuQ84i“,//根据需要初始化
);
##被动接口方法
getRevComponentVerifyTicket() 获取微信服务器发来的component_verify_ticket
getUnauthAppid() 获取微信服务器发来的unauthorized_appid
##主动接口方法
checkComponentAuth()  获取第三方平台access_token 
getPreAuthCode() 获取预授权码
getAuthRefreshToken() 通过授权码auth_code获取authorizer_access_token
getAuthAccessToken() 刷新authorizer_access_token
getAuthAppInfo() 获取授权应用的信息
getAuthAppOption() 获取授权应用的选项设置
setAuthAppOption() 设置授权应用的选项
##重写的4个方法
__construct()
    private $token;
    private $encodingAesKey;
    private $encrypt_type;
    private $component_appid;
    private $component_appsecret;
    private $component_verify_ticket;
    private $component_access_token;
    private $pre_auth_code;
    以上属性均是第三方平台的
valid() appid换成了component_appid
checkAuth() 替换获取access_token的方式
reply() appid换成了component_appid
##剩下为从wechat.class.php原样复制函数

#调用示例
$options = array(
    'token' => '1smzsbkxxhfz0vZ88UGFGv7Kczo', //填写第三方的key
    'encodingaeskey' => 'WXdTheUxyPKOpI6zuhgCuIbCizpAG8OLRAgjunv', //填写第三方加密用的EncodingAESKey
    'component_appid' => 'wx9c25cabb2a30', //填写第三方的app id
    'component_appsecret' => '0c79e1f80cc0be99b20a18faeb', //填写第三方的密钥
    'authorizer_appid' => 'wx58aa023bda74',//根据需要初始化
    'component_verify_ticket' => ”eSBg9VPmFI43OvUHCv5ofzMGvNCG3F0LuQ84i“,//根据需要初始化
);
$mpObj = new Mp($options);
$mpObj->valid();

$component_verify_ticket = $mpObj->getRev()->getRevComponentVerifyTicket();


$mpObj = new Mp($options);

$mpObj->valid();

$type = $mpObj->getRev()->getRevType();

switch ($type) {
    case Mp::MSGTYPE_TEXT:
        $mpObj->text("hello, I'm wechat")->reply();
        # code...
        break;

    default:
        # code...
        break;
}
