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
// +----------------------------------------------------------------------
// | Workerman设置 仅对 php think worker:gateway 指令有效
// +----------------------------------------------------------------------
return [
    // 扩展自身需要的配置
    'protocol'              => 'tcp', // 协议 支持 tcp udp unix http websocket text
    'host'                  => '0.0.0.0', // 监听地址
    'port'                  => 8996, // 监听端口
    'socket'                => '', // 完整监听地址
    'context'               => [], // socket 上下文选项
    'register_deploy'       => true, // 是否需要部署register
    'businessWorker_deploy' => true, // 是否需要部署businessWorker
    'gateway_deploy'        => true, // 是否需要部署gateway

    // Register配置
    'registerAddress'       => '127.0.0.1:1236',    // 服务注册地址

    // Gateway配置
    'name'                  => 'iotyun_tcp',  // gateway名称，status方便查看
    'count'                 => 1,   // gateway进程数
    'lanIp'                 => '127.0.0.1', // 本机ip，分布式部署时使用内网ip
    'startPort'             => 2900,    // 内部通讯起始端口，假如$gateway->count=4，起始端口为4000
    // 则一般会使用4000 4001 4002 4003 4个端口作为内部通讯端口 
    'daemonize'             => false,
    'pingInterval'          => 30,  // 心跳间隔
    'pingNotResponseLimit'  => 0,   //pingNotResponseLimit = 0代表服务端允许客户端不发送心跳，服务端不会因为客户端长时间没发送数据而断开连接。如果pingNotResponseLimit = 1，则代表客户端必须定时发送数据给服务端，否则pingNotResponseLimit*pingInterval=55秒内没有任何数据发来则关闭对应连接，并触发onClose。
    'pingData'              => '{"type":"ping"}',   // 心跳数据

    // BusinsessWorker配置
    'businessWorker'        => [
        'name'         => 'BusinessWorker', //可以设置BusinessWorker进程的名称，方便status命令中查看统计
        'count'        => 1,    //可以设置BusinessWorker进程的数量，以便充分利用多cpu资源
        'eventHandler' => '\think\worker\Events',   //
    ],

];
