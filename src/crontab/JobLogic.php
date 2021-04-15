<?php
namespace iotyun\tcp\crontab;

class JobLogic
{
    public $file_path = '/data/jobs.json';
    public $file_path_copy = './common/data/jobs_copy.json';
    public static $status = array(
        'on'=>array('k'=>'on', 'v'=>'正常', 'c'=>'text-green'),
        'off'=>array('k'=>'off', 'v'=>'关闭', 'c'=>'text-gray'),
    );
    public static $type = array(
        'http_get'=>array('k'=>'http_get', 'v'=>'HTTP GET 请求', 'c'=>'text-blue'),
        //'http_post'=>array('k'=>'http_post', 'v'=>'HTTP POST 请求', 'c'=>'text-primary'),
    );

    /**
     * 任务状态 select
     *
     * @param string  $choose 默认选中
     * @param string  $name 名称、ID
     * @param string  $onchange 事件
     * @param boolean $disabled
     *
     * @return string
     */
    public static function getStatusSelect($choose='', $name='status', $onchange='', $disabled=false)
    {
        return self::getSelect5(self::$status, $choose, $name, $onchange, $disabled);
    }

    /**
     * 任务类型 select
     *
     * @param string  $choose 默认选中
     * @param string  $name 名称、ID
     * @param string  $onchange 事件
     * @param boolean $disabled
     *
     * @return string
     */
    public static function getTypeSelect($choose='', $name='type', $onchange='', $disabled=false)
    {
        return self::getSelect5(self::$type, $choose, $name, $onchange, $disabled);
    }

    /**
     * 获取 jobs.json 数据
     *
     * return array
     */
    public static function readJobs()
    {
        $jobs_array = [];
        if(is_file(__DIR__ . '/data/jobs.json')){
            $jobs_array = json_decode(file_get_contents(__DIR__ . '/data/jobs.json'), true);
            if(empty($jobs_array))
			{ 
				$jobs_array = [];
			}
        }
        return $jobs_array;
		//return __DIR__;
    }

    /**
     * 设置 jobs.json 数据
     *
     * @param array $jobs
     *
     * @return array
     */
    public function writeJobs($jobs = array())
    {
        // json_encode 的第二个参数：
        //JSON_UNESCAPED_UNICODE = 256 //中文不转为unicode
        //JSON_UNESCAPED_SLASHES = 64 //不转义反斜杠
        //JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES = 320
        $content = json_encode($jobs, 320);

        //保存原来的
        file_put_contents($this->file_path_copy, file_get_contents($this->file_path));

        //写入新的
        $fp = fopen($this->file_path, 'w');
        if(flock($fp, LOCK_EX)){
            fwrite($fp, $content);
            flock($fp, LOCK_UN);
        }
        fclose($fp);

        return $this->rs(0, 'ok');
    }

    /**
     * 工具::组装返回信息
     *
     * @param int    $c
     * @param string $m
     * @param array  $d
     *
     * @return array
     */
    public function rs($c=0, $m='', $d=[])
    {
        $rt = [];
        $rt['c'] = intval($c);
        $rt['m'] = $m;
        $rt['d'] = $d;
        return $rt;
    }

    /**
     * 由字符串拆分得到对应数组
     *
     * @param string  $string = 1,2,3,66,
     * @param string  $delimit 分隔符
     * @param boolean $unique  唯一性
     * @param boolean $gtZero  大于零
     *
     * @return array(1,2,3,66)
     */
    public function getIntArrayFromString($string='', $delimit=',', $unique=true, $gtZero=true)
    {
        $string = trim($string); if($string == ''){ return array(); }
        $delimit = trim($delimit); if($delimit == ''){ return array(); }
        $tmp = explode($delimit, $string);
        $rs = array();
        foreach($tmp as $t){
            $t = intval($t);
            if($gtZero){
                if($t > 0){
                    $rs[] = $t;
                }
            }else{
                $rs[] = $t;
            }
        }
        if($unique){
            $rs = array_unique($rs);
        }
        return $rs;
    }

    /**
     * 组装select
     *      适用格式：
     *      $dts = array('a'=>array('k'=>'a', 'v'=>'xxx'), 'b'=>array('k'=>'b', 'v'=>'yyy'))
     *
     * @param array   $dts
     * @param string  $choose 默认选中
     * @param string  $name 名称、ID
     * @param string  $onchange 事件
     * @param boolean $disabled
     * @param mixed   $fst
     *
     * @return string
     */
    public static function getSelect5($dts=[], $choose='', $name='dft', $onchange='', $disabled=false, $fst='---')
    {
        $dis = $disabled ? 'disabled="disabled"' : '';
        $html = '<select class="form-control" autocomplete="off" name="'.$name.'" id="'.$name.'" onchange="'.$onchange.'" '.$dis.'>';
        if(false !== $fst)
        {
            $html .= '<option value="-1">'.$fst.'</option>';
        }
        if( ! empty($dts))
        {
            foreach($dts as $k=>$v)
            {
                $sel = ($k === $choose) ? 'selected="selected"' : '';
                $html .= '<option value="'.$k.'" '.$sel.'>'.$v['v'].'</option>';
            }
        }
        $html .= '</select>';
        return $html;
    }
}
