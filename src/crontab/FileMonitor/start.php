<?php
use Workerman\Worker;
use Workerman\Lib\Timer;

// watch Applications catalogue
$file = realpath(__DIR__ . '/../crontab/data/jobs.json');
//echo $file;
// worker
$worker_file = new Worker();
$worker_file->name = 'FileMonitor';
$worker_file->reloadable = false;
$last_mtime = time();

$worker_file->onWorkerStart = function()
{
    global $file;

    Timer::add(1, 'check_files_change', array($file));

};

// check files func
function check_files_change($file)
{
    global $last_mtime;
	echo $file;
    // recursive traversal directory

        if($last_mtime < filemtime($file))
        {
            echo $file." update and reload\n";
            // send SIGUSR1 signal to master process for reload
            posix_kill(posix_getppid(), SIGUSR1);
            $last_mtime = $file->getMTime();

        }
    
}
