<?php
use Workerman\Worker;
use Workerman\Lib\Timer;


// worker
$worker = new Worker();
$worker->name = 'FileMonitor';
$worker->reloadable = false;
$last_mtime = time();

$worker->onWorkerStart = function()
{
    //global $monitor_dir;
    // watch files only in daemon mode
    // if(!Worker::$daemonize)
    // {
        // chek mtime of files per second 
        Timer::add(1, 'check_files_change');
    // }
};

// check files func
function check_files_change()
{
    global $last_mtime;
	$monitor_dir = realpath(__DIR__ . '/../data/');
    // recursive traversal directory
    $dir_iterator = new RecursiveDirectoryIterator($monitor_dir);
    $iterator = new RecursiveIteratorIterator($dir_iterator);
    foreach ($iterator as $file)
    {
		echo $file->getMTime();
        // only check php files
        if(pathinfo($file, PATHINFO_EXTENSION) != 'json')
        {
            continue;
        }
        // check mtime
        if($last_mtime < $file->getMTime())
        {
            echo $file." update and reload\n";
            // send SIGUSR1 signal to master process for reload
            posix_kill(posix_getppid(), SIGUSR1);
            $last_mtime = $file->getMTime();
            break;
        }
    }
}
