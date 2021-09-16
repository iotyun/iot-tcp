<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace app\demo\controller;

use GatewayWorker\Lib\Gateway;
use Workerman\Worker;
use think\facade\Config;
use think\facade\Event;
use think\facade\Log;
use Workerman\Lib\Timer;
use iotyun\iotprotocol\Driver;
use app\demo\controller\Test;

/**
 * Worker 命令行服务类
 */
class Events
{
    //protected $config = Config::get('tcp_callback');
    /**
     * onWorkerStart 事件回调
     * 当businessWorker进程启动时触发。每个进程生命周期内都只会触发一次
     *
     * @access public
     * @param  \Workerman\Worker    $businessWorker
     * @return void
     */
    public static function onWorkerStart(Worker $businessWorker)
    {
        // $app = new Application;
        // $app->initialize();
    }

    /**
     * onConnect 事件回调
     * 当客户端连接上gateway进程时(TCP三次握手完毕时)触发
     *
     * @access public
     * @param  int       $client_id
     * @return void
     */
    public static function onConnect($client_id)
    {
		//这是当通讯服务器接收到设备连接时的回调信息，接收到这个信息，说明设备正常的连接到了通讯服务器。
        Log::channel('iottcp')->info('客户端连接' . $client_id);
		//下面这句不要动，这是开启终端登录验证服务，如果终端30秒内没有发送注册码或者注册码不正确，将自动断开连接
		$_SESSION['auth_timer_id'] = Timer::add(30, function($client_id){
            Gateway::closeClient($client_id);
        }, array($client_id), false);
    }

    /**
     * onWebSocketConnect 事件回调
     * 当客户端连接上gateway完成websocket握手时触发
     *
     * @param  integer  $client_id 断开连接的客户端client_id
     * @param  mixed    $data
     * @return void
     */
    public static function onWebSocketConnect($client_id, $data)
    {
        var_export($data);
    }

    /**
     * onMessage 事件回调
     * 当客户端发来数据(Gateway进程收到数据)后触发
     *
     * @access public
     * @param  int       $client_id
     * @param  mixed     $data
     * @return void
     */
    public static function onMessage($client_id, $data)
    {
        //这是当通讯服务器接收到设备发来的消息时的回调，这里是把设备发来的原始数据进行了记录。$data为二进制数据，如果直接存储会是乱码，使用bin2hex()函数将二进制数据转换为16进制字符串。
		Log::channel('iottcp')->info("client_id：" . $client_id . "data:" . bin2hex($data));	//原始信息记入日志
		
		if ($_SESSION['auth_timer_id'] != 0)
		{
			$driver_json = Driver::getRegisterInfo($data);	//解析注册信息
			Log::channel('iottcp')->info($driver_json);	//解析信息记入日志
			//$driver = json_decode($driver_json);
			
			if (Test::authentication($driver_json))
			{
				Log::channel('iottcp')->info("开始注册操作");	//解析信息记入日志
				Timer::del($_SESSION['auth_timer_id']);
				$_SESSION['auth_timer_id'] = 0;
				// $_SESSION['auth_driver_appid'] = $driver->appid;
				// $_SESSION['auth_driver_productid'] = $driver->productid;
				// $_SESSION['auth_driver_driverid'] = $driver->driverid;
				Gateway::bindUid($client_id, bin2hex($data));
				$uid = Gateway::getUidByClientId($client_id);
				Log::channel('iottcp')->info("Uid：" . $uid);	//解析信息记入日志
				Gateway::sendToClient($client_id, $data);
			}
			else
			{
				Gateway::closeClient($client_id);
			}
		}
		else if (bin2hex($data) == '70696E67')
		{
			Log::channel('iottcp')->info("心跳信息：" . bin2hex($data));	//原始信息记入日志
			Gateway::sendToClient($client_id, $data);
		}
		else
		{
			Log::channel('iottcp')->info("数据信息：" . bin2hex($data) . "client_id：" . $client_id);
			$message_json = Driver::getMessageInfo($client_id, $data);	//解析modbus协议信息
			Log::channel('iottcp')->info("上报信息：" . $message_json);
		}
		
    }

    /**
     * onClose 事件回调 当用户断开连接时触发的方法
     *
     * @param  integer $client_id 断开连接的客户端client_id
     * @return void
     */
    public static function onClose($client_id)
    {
        GateWay::sendToAll("client[$client_id] logout\n");
    }

    /**
     * onWorkerStop 事件回调
     * 当businessWorker进程退出时触发。每个进程生命周期内都只会触发一次。
     *
     * @param  \Workerman\Worker    $businessWorker
     * @return void
     */
    public static function onWorkerStop(Worker $businessWorker)
    {
        echo "WorkerStop\n";
    }
}
