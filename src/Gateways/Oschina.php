<?php
/**
 * Oschina
 * api接口文档
*/
namespace tinymeng\OAuth2\Gateways;
use tinymeng\OAuth2\Connector\Gateway;
use tinymeng\OAuth2\Helper\ConstCode;

/**
 * Class Gitlab
 * @package tinymeng\OAuth2\Gateways
 * @Author: TinyMeng <666@majiameng.com>
 * @Created: 2023/07/09
 */
class Oschina extends Gateway
{
    const API_BASE            = 'https://openapi.baidu.com/';
    protected $AuthorizeURL   = 'https://openapi.baidu.com/oauth/2.0/authorize';
    protected $AccessTokenURL = 'https://openapi.baidu.com/';
    protected $UserInfoURL = 'https://openapi.baidu.com/';

    /**
     * Description:  得到跳转地址
     * @author: JiaMeng <666@majiameng.com>
     * Updater:
     * @return string
     */
    public function getRedirectUrl()
    {
        //存储state
        $this->saveState();
        //登录参数
        $params = [
            'response_type' => $this->config['response_type'],
            'client_id'     => $this->config['app_id'],
            'redirect_uri'  => $this->config['callback'],
            'state'         => $this->config['state'],
            'scope'         => $this->config['scope'],
        ];
        return $this->AuthorizeURL . '?' . http_build_query($params);
    }

    /**
     * Description:  获取格式化后的用户信息
     * @return array
     * @throws \Exception
     * @author: JiaMeng <666@majiameng.com>
     * Updater:
     */
    public function userInfo()
    {
        $result = $this->getUserInfo();
        $userInfo = [
            'open_id' => isset($result['uid']) ? $result['uid'] : '',
            'union_id'=> isset($result['aid']) ? $result['aid'] : '',
            'channel' => ConstCode::TYPE_OSCHINA,
            'nickname'=> $result['login_name'],
            'gender'  => ConstCode::GENDER,
            'avatar'  => '',
            'birthday'=> '',
            'access_token'=> $this->token['access_token'] ?? '',
            'native'=> $result,
        ];
        return $userInfo;
    }

    /**
     * Description:  获取原始接口返回的用户信息
     * @return array
     * @throws \Exception
     * @author: JiaMeng <666@majiameng.com>
     * Updater:
     */
    public function getUserInfo()
    {
        /** 获取用户信息 */
        $this->openid();

        $headers = ['Authorization: Bearer '.$this->token['access_token']];
        $data = $this->get($this->UserInfoURL, [],$headers);
        return json_decode($data, true);
    }

    /**
     * Description:  获取当前授权用户的openid标识
     * @author: JiaMeng <666@majiameng.com>
     * Updater:
     * @return mixed
     * @throws \Exception
     */
    public function openid()
    {
        $this->getToken();
    }


    /**
     * Description:  获取AccessToken
     * @author: JiaMeng <666@majiameng.com>
     * Updater:
     */
    protected function getToken(){
        if (empty($this->token)) {
            /** 验证state参数 */
            $this->CheckState();

            /** 获取参数 */
            $params = $this->accessTokenParams();

            /** 获取access_token */
            $this->AccessTokenURL = $this->AccessTokenURL . '?' . http_build_query($params);
            $token =  $this->post($this->AccessTokenURL);
            /** 解析token值(子类实现此方法) */
            $this->token = $this->parseToken($token);
        }
    }

    /**
     * Description:  解析access_token方法请求后的返回值
     * @author: JiaMeng <666@majiameng.com>
     * Updater:
     * @param $token
     * @return mixed
     * @throws \Exception
     */
    protected function parseToken($token)
    {
        $data = json_decode($token, true);
        if (isset($data['access_token'])) {
            return $data;
        } else {
            throw new \Exception("获取OSChina ACCESS_TOKEN出错：{$data['error']}");
        }
    }

}
