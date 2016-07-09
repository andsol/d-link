<?php
/**
 * Created by IntelliJ IDEA.
 * User: Andrey
 * Date: 07-Jul-16
 * Time: 09:32 PM
 */

namespace Andsol;


use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class DLink {

    const URL_LOGIN = '/userRpm/LoginRpm.htm?Save=Save';
    const URL_RELEASE = '/userRpm/StatusRpm.htm?ReleaseIp=Release&wan=1';
    const URL_RENEW = '/userRpm/StatusRpm.htm?RenewIp=Renew&wan=1';
    const URL_STATUS = '/userRpm/StatusRpm.htm';
    const URL_LOGOUT = '/userRpm/StatusRpm.htm?Logout=Logout';
    const URL_INDEX = '/userRpm/Index.htm';

    /**
     * @var Client
     */
    private $client;
    private $routerIp;
    private $login;
    private $password;


    private $secureKey = null;

    private $options = [];

    public function __construct($routerIp, $login, $password, Client $client)
    {
        $this->client = $client;
        $this->routerIp = $routerIp;
        $this->login = $login;
        $this->password = $password;

        $this->options = [
            RequestOptions::HEADERS => [
                'Cookie' => 'Authorization=Basic ' . base64_encode($this->login . ':' . md5($this->password))
            ]
        ];
    }

    private function login(){

        $url = $this->routerIp . self::URL_LOGIN;

        $response = $this->client->request('GET', $url, $this->options);
        $body = (string) $response->getBody();
        $found = preg_match('/\/([^\/]*)\/userRpm/', $body, $matches);

        if($found && array_key_exists(1, $matches)){
            $this->secureKey = $matches[1];
            // Visit index page
            $this->index();
            return true;
        }
        return false;
    }


    public function release(){

        if(!$this->secureKey){
            $this->login();
        }

        $this->client->request('GET', 'http://' . $this->routerIp . '/' . $this->secureKey . self::URL_RELEASE, $this->options);
    }

    public function renew(){

        if(!$this->secureKey){
            $this->login();
        }
        $options = $this->options;
        $options[RequestOptions::HEADERS]['Referer'] = 'http://' . $this->routerIp . '/' . $this->secureKey . self::URL_STATUS;

        $response = $this->client->request('GET', 'http://' . $this->routerIp . '/' . $this->secureKey . self::URL_RENEW, $options);
        $body = (string) $response->getBody();
    }

    public function status(){

        if(!$this->secureKey){
            $this->login();
        }

        $url = 'http://' . $this->routerIp . '/' . $this->secureKey . self::URL_STATUS;

        $response = $this->client->request('GET', $url, $this->options);
        $body = (string) $response->getBody();

        $data = [];

        if (preg_match('/var statusPara = new Array\((.*?)\)/ms', $body, $matches)) {
            $data['statusPara'] = json_decode('[' . $matches[1] . ']');
        }

        if (preg_match('/var lanPara = new Array\((.*?)\)/ms', $body, $matches)) {
            $data['lanPara'] = json_decode('[' . $matches[1] . ']');
        }

        if (preg_match('/var statistList = new Array\((.*?)\)/ms', $body, $matches)) {
            $data['statistList'] = json_decode('[' . $matches[1] . ']');
        }

        if (preg_match('/var wanPara = new Array\((.*?)\)/ms', $body, $matches)) {
            $data['wanPara'] = json_decode('[' . $matches[1] . ']');
        }

        return $data;
    }

    public function index(){

        if(!$this->secureKey){
            $this->login();
        }

        $url = 'http://' . $this->routerIp . '/' . $this->secureKey . self::URL_STATUS;
        $this->options[RequestOptions::HEADERS]['Referer'] = 'http://' . $this->routerIp . '/' . $this->secureKey . self::URL_LOGIN;
        $response = $this->client->request('GET', $url, $this->options);
        $code = $response->getStatusCode();

        if($code !== 200){
            throw new \RuntimeException('Can not get index page');
        }
        $this->options[RequestOptions::HEADERS]['Referer'] = 'http://' . $this->routerIp . '/' . $this->secureKey . self::URL_INDEX;
    }
}