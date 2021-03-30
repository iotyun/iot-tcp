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
namespace iotyun\tcp;

use think\Service as BaseService;

class Service extends BaseService
{
    public function register()
    {
        $this->commands([
            'IotyunTcp' => 'iotyun\\tcp\\command\\tcp',
        ]);
    }
}
