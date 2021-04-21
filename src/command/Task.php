<?php

namespace iotyun\tcp\command;

set_time_limit(0);

use Workerman\Crontab\Crontab;
use iotyun\tcp\crontab\JobLogic;
//use think\facade\Log;
use iotyun\iotprotocol\Utils\Types;
use \GatewayWorker\Lib\Gateway;
use iotyun\iotprotocol\Utils\Crc16;

class Task
{
	public static function taskLoad()
    {
		$jobs = JobLogic::readJobs();
		if (is_array($jobs))
		{
			foreach ($jobs as $job)
			{
				$cron = $job['cron']['i'] . ' ' . $job['cron']['h'] . ' ' . $job['cron']['d'] . ' ' . $job['cron']['m'] . ' ' . $job['cron']['w'] . ' ' . $job['cron']['y'];
				new Crontab($cron, function()use($job){
					//Log::channel('iottcp')->info(date('Y-m-d H:i:s')."\n");
					echo date('Y-m-d H:i:s') . "\n";
					$uid = bin2hex(Types::toInt16($job['appid'])) . bin2hex(Types::toInt16($job['productid'])) . bin2hex(Types::toInt32($job['driverid'], 1));
					if(Gateway::isUidOnline($uid))
					{
						$client_id = Gateway::getClientIdByUid($uid);
						foreach ($job['addr'] as $addr)
						{
							$message = bin2hex(Types::toByte($addr));
							$message .= bin2hex(Types::toByte($job['function_code']));
							$message .= bin2hex(Types::toInt16($job['register']));
							$message .= bin2hex(Types::toInt16($job['register_length']));
							echo "\n";
							$message .= unpack("H*", Crc16::crc166($message))[1];
							echo $message;
							echo "\n";
							foreach ($client_id as $client)
							{
								Gateway::updateSession($client, array('function_code' => $job['function_code'], 'register' => $job['register'], 'register_length' => $job['register_length']));
								Gateway::sendToClient($client, hex2bin($message));
								for ($i=0; $i<30; $i++)
								{
									$get_session = Gateway::getSession($client);
									var_dump($get_session);
									if (empty($get_session["function_code"]) && empty($get_session["register"]) && empty($get_session["register_length"]))
									{
										break;
									}
									usleep(100000);
								}
								Gateway::updateSession($client, array('function_code' => '', 'register' => '', 'register_length' => ''));
							}
						}
					}
					echo "\n";
				});
			}
		}
    }
	
}
