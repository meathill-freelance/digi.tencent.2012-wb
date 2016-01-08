<?php
/**
 * 此为PHP-SDK 2.0 的一个使用Demo,用于流程和接口调用演示
 * 请根据自身需求和环境进相应的安全和兼容处理，勿直接用于生产环境
 */
error_reporting(0);
require_once './Config.php';
require_once './Tencent.php';

OAuth::init($client_id, $client_secret);
Tencent::$debug = $debug;

//打开session
session_start();
header('Content-Type: text/html; charset=utf-8');

if ($_SESSION['t_access_token'] || ($_SESSION['t_openid'] && $_SESSION['t_openkey'])) { //用户已授权
  
  // 获取昵称
  $info = Tencent::api('user/info', array(), 'GET');
  $info = json_decode($info);
  $info = $info->data;
  $nick = $info->nick ? $info->nick : $info->name;
  
  $select = $_REQUEST['select'];
  $url = "http://digi2012.sinaapp.com/weibo.php?select=$select&username=$nick";
  
  /**
   * 发表图片微博
   * 如果图片地址为网络上的一个可用链接
   * 则使用add_pic_url接口
   * */
  $params = array(
    'content' => '2012我的消费电子心愿单出炉啦！快来秀秀你的选择吧，分享就有机会赢#腾讯数码扑克牌#实体版哦！http://digi.tech.qq.com/zt2012/poker/index.htm',
    'pic_url' => $url,
  );
  $r = Tencent::api('t/add_pic_url', $params, 'POST');
  header('Location: http://k.t.qq.com/k/%25E8%2585%25BE%25E8%25AE%25AF%25E6%2595%25B0%25E7%25A0%2581%25E6%2589%2591%25E5%2585%258B%25E7%2589%258C');
} else {//未授权
  $callback = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?select=' . $_REQUEST['select'];//回调url
  if ($_GET['code']) {//已获得code
    $code = $_GET['code'];
    $openid = $_GET['openid'];
    $openkey = $_GET['openkey'];
    //获取授权token
    $url = OAuth::getAccessToken($code, $callback);
    $r = Http::request($url);
    parse_str($r, $out);
    //存储授权数据
    if ($out['access_token']) {
      $_SESSION['t_access_token'] = $out['access_token'];
      $_SESSION['t_refresh_token'] = $out['refresh_token'];
      $_SESSION['t_expire_in'] = $out['expires_in'];
      $_SESSION['t_code'] = $code;
      $_SESSION['t_openid'] = $openid;
      $_SESSION['t_openkey'] = $openkey;

      //验证授权
      $r = OAuth::checkOAuthValid();
      if ($r) {
        header('Location: ' . $callback);//刷新页面
      } else {
        exit('<h3>授权失败,请重试</h3>');
      }
    } else {
      exit($r);
    }
  } else {//获取授权code
    if ($_GET['openid'] && $_GET['openkey']){//应用频道
      $_SESSION['t_openid'] = $_GET['openid'];
      $_SESSION['t_openkey'] = $_GET['openkey'];
      //验证授权
      $r = OAuth::checkOAuthValid();
      if ($r) {
        header('Location: ' . $callback);//刷新页面
      } else {
        exit('<h3>授权失败,请重试</h3>');
      }
    } else{
      $url = OAuth::getAuthorizeURL($callback);
      header('Location: ' . $url);
    }
  }
}
