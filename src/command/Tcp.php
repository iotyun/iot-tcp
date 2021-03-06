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

namespace iotyun\tcp\command;

use GatewayWorker\BusinessWorker;
use GatewayWorker\Gateway;
use GatewayWorker\Register;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Config;
use Workerman\Worker;
use Workerman\Lib\Timer;

use \RecursiveDirectoryIterator;
use \RecursiveIteratorIterator;
//use Workerman\Crontab\Crontab;

/**
 * Worker 命令行类
 */
class Tcp extends Command
{
	
    public function configure()
    {
        $this->setName('worker:gateway')
            ->addArgument('action', Argument::OPTIONAL, "start|stop|restart|reload|status|connections", 'start')
            ->addOption('host', 'H', Option::VALUE_OPTIONAL, 'the host of workerman server.', null)
            ->addOption('port', 'p', Option::VALUE_OPTIONAL, 'the port of workerman server.', null)
            ->addOption('daemon', 'd', Option::VALUE_NONE, 'Run the workerman server in daemon mode.')
            ->setDescription('GatewayWorker Server for ThinkPHP');
    }

    public function execute(Input $input, Output $output)
    {
        $action = $input->getArgument('action');

        //if (DIRECTORY_SEPARATOR !== '\\') {
            if (!in_array($action, ['start', 'stop', 'reload', 'restart', 'status', 'connections'])) {
                $output->writeln("Invalid argument action:{$action}, Expected start|stop|restart|reload|status|connections .");
                exit(1);
            }

            global $argv;
            array_shift($argv);
            array_shift($argv);
            array_unshift($argv, 'think', $action);
        //} else {
        //    $output->writeln("GatewayWorker Not Support On Windows.");
        //    exit(1);
        //}

        if ('start' == $action) {
            $output->writeln('Starting GatewayWorker server...');
        }

        $option = Config::get('iotyun_tcp');

        if ($input->hasOption('host')) {
            $host = $input->getOption('host');
        } else {
            $host = !empty($option['host']) ? $option['host'] : '0.0.0.0';
        }

        if ($input->hasOption('port')) {
            $port = $input->getOption('port');
        } else {
            $port = !empty($option['port']) ? $option['port'] : '2347';
        }

        $this->start($host, (int) $port, $option);
    }

    /**
     * 启动
     * @access public
     * @param  string   $host 监听地址
     * @param  integer  $port 监听端口
     * @param  array    $option 参数
     * @return void
     */
    public function start(string $host, int $port, array $option = [])
    {
        $registerAddress = !empty($option['registerAddress']) ? $option['registerAddress'] : '127.0.0.1:1236';
        if (DIRECTORY_SEPARATOR !== '\\') {
			if (!empty($option['register_deploy'])) {
				// 分布式部署的时候其它服务器可以关闭register服务
				// 注意需要设置不同的lanIp
				$this->register($registerAddress);
			}

			// 启动businessWorker
			if (!empty($option['businessWorker_deploy'])) {
				$this->businessWorker($registerAddress, $option['businessWorker'] ?? []);
			}

			// 启动gateway
			if (!empty($option['gateway_deploy'])) {
				$this->gateway($registerAddress, $host, $port, $option);
			}
			
			// 启动Crontab
			if (!empty($option['crontab'])) {
				$this->taskCrontab($option);
				//$this->FileMonitor();
				require_once __DIR__ . '/../crontab/FileMonitor/start_json.php';
			}
			
			
        
            Worker::runAll();
        }
        else
        {
            if (!empty($option['register_deploy'])) {
                // 分布式部署的时候其它服务器可以关闭register服务
                // 注意需要设置不同的lanIp
                $this->register($registerAddress);
            }
            Worker::runAll();
            // 启动businessWorker
            if (!empty($option['businessWorker_deploy'])) {
                $this->businessWorker($registerAddress, $option['businessWorker'] ?? []);
            }
            Worker::runAll();
            // 启动gateway
            if (!empty($option['gateway_deploy'])) {
                $this->gateway($registerAddress, $host, $port, $option);
            }
            Worker::runAll();


        }

        
    }

    /**
     * 启动register
     * @access public
     * @param  string   $registerAddress
     * @return void
     */
    public function register(string $registerAddress)
    {
        // 初始化register
        new Register('text://' . $registerAddress);
    }

    /**
     * 启动businessWorker
     * @access public
     * @param  string   $registerAddress registerAddress
     * @param  array    $option 参数
     * @return void
     */
    public function businessWorker(string $registerAddress, array $option = [])
    {
        // 初始化 bussinessWorker 进程
        $worker = new BusinessWorker();

        $this->option($worker, $option);

        $worker->registerAddress = $registerAddress;
    }

    /**
     * 启动gateway
     * @access public
     * @param  string  $registerAddress registerAddress
     * @param  string  $host 服务地址
     * @param  integer $port 监听端口
     * @param  array   $option 参数
     * @return void
     */
    public function gateway(string $registerAddress, string $host, int $port, array $option = [])
    {
        // 初始化 gateway 进程
        if (!empty($option['socket'])) {
            $socket = $option['socket'];
            unset($option['socket']);
        } else {
            $protocol = !empty($option['protocol']) ? $option['protocol'] : 'websocket';
            $socket   = $protocol . '://' . $host . ':' . $port;
            unset($option['host'], $option['port'], $option['protocol']);
        }

        $gateway = new Gateway($socket, $option['context'] ?? []);

        // 以下设置参数都可以在配置文件中重新定义覆盖
        $gateway->name                 = 'iotyun_tcp';
        $gateway->count                = 4;
        $gateway->lanIp                = '127.0.0.1';
        $gateway->startPort            = 2900;
        //$gateway->pingInterval         = 30;
        //$gateway->pingNotResponseLimit = 0;
        //$gateway->pingData             = '{"type":"ping"}';
        //$gateway->registerAddress      = $registerAddress;

        // 全局静态属性设置
        foreach ($option as $name => $val) {
            if (in_array($name, ['stdoutFile', 'daemonize', 'pidFile', 'logFile'])) {
                Worker::${$name} = $val;
                unset($option[$name]);
            }
        }

        $this->option($gateway, $option);
    }

    /**
     * 设置参数
     * @access protected
     * @param  Worker $worker Worker对象
     * @param  array  $option 参数
     * @return void
     */
    protected function option(Worker $worker, array $option = [])
    {
        // 设置参数
        if (!empty($option)) {
            foreach ($option as $key => $val) {
                $worker->$key = $val;
            }
        }
    }
	
	/**
     * 设置Crontab
     * @access protected
     * @param  Worker $worker Worker对象
     * @param  array  $option 参数
     * @return void
     */
    protected function taskCrontab(array $option = [])
    {
        $worker = new Worker();
		// 设置时区，避免运行结果与预期不一致
		date_default_timezone_set('PRC');
		//$option = Config::get('iotyun_tcp');
		$worker->onWorkerStart = array('iotyun\tcp\command\Task', 'taskLoad');
		$worker->name = 'Crontab';
    }
	
	/**
     * 自动更新json文件
     * @access protected
     * @param  Worker $worker Worker对象
     * @param  array  $option 参数
     * @return void
     */
	
	private $last_mtime = 0;
    protected function FileMonitor()
    {
		$monitor_dir = realpath(__DIR__ . '/../crontab/data');
		$file = realpath(__DIR__ . '/../crontab/data/jobs.json');
        $this->_last_time = filemtime($file);
		
		$worker_file = new Worker();
		$worker_file->name = 'FileMonitor';
		$worker_file->reloadable = false;

		$worker_file->onWorkerStart = function()
        {
            Timer::add(1, array($this, 'monitor'));
        };
    }
	
	//监听器，kill进程
    public function monitor ()
    {
        
		//$file = realpath(__DIR__ . '/../crontab/data/jobs.json');
		//echo filemtime($file);
		$monitor_dir = realpath(__DIR__ . '/../crontab/data');
		$dir_iterator = new RecursiveDirectoryIterator($monitor_dir);
		$iterator = new RecursiveIteratorIterator($dir_iterator);
		foreach ($iterator as $file)
		{
			// only check php files
			if(pathinfo($file, PATHINFO_EXTENSION) != 'json')
			{
				continue;
			}
			echo $file->getMTime();
			// check mtime
			if($this->last_mtime < $file->getMTime())
			{
				echo $file." update and reload\n";
				// send SIGUSR1 signal to master process for reload
				posix_kill(posix_getppid(), SIGUSR1);
				$this->last_mtime = $file->getMTime();
				break;
			}
		}
		
		
		
		// check mtime
		// if ($this->_last_time < filemtime($file))
		// {
			// echo $file." update and reload\n";
			//send SIGUSR1 signal to master process for reload
			// posix_kill(posix_getppid(), SIGUSR1);
			// $last_mtime = $file->getMTime();
		// }
        
    }

}
