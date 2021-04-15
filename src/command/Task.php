<?php

namespace iotyun\tcp\command;

use Workerman\Crontab\Crontab;
use iotyun\tcp\crontab\JobLogic;

class Task
{
	public static function taskLoad()
    {
		$jobs = JobLogic::readJobs();
		foreach ($jobs as $job)
		{
			$cron = $job['cron']['i'] . ' ' . $job['cron']['h'] . ' ' . $job['cron']['d'] . ' ' . $job['cron']['m'] . ' ' . $job['cron']['w'] . ' ' . $job['cron']['y'];
			new Crontab($cron, function(){
				echo date('Y-m-d H:i:s')."\n";
			});
		}
    }
	
}
