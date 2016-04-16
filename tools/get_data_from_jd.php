<?php
/**
 * 从京东获取省市区三级数据
 * 执行 php get_data_from_jd.php 获取京东地区数据
 * Created by PhpStorm.
 * User: upliu
 * Date: 4/17/16
 * Time: 1:02 AM
 */

$app = new App();
$app->run();

class App
{
    protected $_rows;
    
    /** @var Cache */
    protected $_cache;

    protected $_jsFile;
    
    public function __construct()
    {
        $this->_cache = new Cache();
        $this->_jsFile = dirname(dirname(__FILE__)) . '/dists/AreaData.js';
    }

    public function run()
    {
        // 京东任一商品详情页获取省的数据
        // var area = [];$(jQuery('.area-list')[1]).find('li a').each(function(){var i=$(this);area.push({id:i.data('value'),name:i.text()});});console.log(JSON.stringify(area));
        $province = json_decode('[{"id":1,"name":"北京"},{"id":2,"name":"上海"},{"id":3,"name":"天津"},{"id":4,"name":"重庆"},{"id":5,"name":"河北"},{"id":6,"name":"山西"},{"id":7,"name":"河南"},{"id":8,"name":"辽宁"},{"id":9,"name":"吉林"},{"id":10,"name":"黑龙江"},{"id":11,"name":"内蒙古"},{"id":12,"name":"江苏"},{"id":13,"name":"山东"},{"id":14,"name":"安徽"},{"id":15,"name":"浙江"},{"id":16,"name":"福建"},{"id":17,"name":"湖北"},{"id":18,"name":"湖南"},{"id":19,"name":"广东"},{"id":20,"name":"广西"},{"id":21,"name":"江西"},{"id":22,"name":"四川"},{"id":23,"name":"海南"},{"id":24,"name":"贵州"},{"id":25,"name":"云南"},{"id":26,"name":"西藏"},{"id":27,"name":"陕西"},{"id":28,"name":"甘肃"},{"id":29,"name":"青海"},{"id":30,"name":"宁夏"},{"id":31,"name":"新疆"},{"id":32,"name":"台湾"},{"id":42,"name":"香港"},{"id":43,"name":"澳门"},{"id":84,"name":"钓鱼岛"}]', true);
        foreach ($province as $p) {
            $this->_rows[] = array(
                'id' => $p['id'],
                'pid' => 0,
                'name' => $p['name'],
            );
            $this->getChild($p['id'], 1);
        }

        $this->generateJsFile();

        print("Done with ".count($this->_rows)." rows\n");
    }

    protected function generateJsFile()
    {
        $rows_group_by_pid = array();
        foreach ($this->_rows as $row) {
            $rows_group_by_pid[$row['pid']][] = $row;
        }

        $address = array();
        foreach ($rows_group_by_pid as $pid => $sons) {
            $address['name'.$pid] = get_column($sons, 'name');
            $address['id'.$pid] = get_column($sons, 'id');
        }

        $js = 'var AreaData = ' . json_encode($address) . ';';
        file_put_contents($this->_jsFile, $js);
    }

    protected function getChild($pid, $depth)
    {
        // 只获取到区这一级
        if ($depth >= 3) {
            return false;
        }
        $url = 'http://d.jd.com/area/get?fid='.$pid;
        if (($area = $this->_cache->get($url)) === false) {
            $usleep = 500000;
            while (true) {
                print("get $url\n");
                $area = json_decode(file_get_contents($url, false, stream_context_create(array(
                    'http' => array(
                        'timeout' => 1,
                    )
                ))), true);
                if (is_array($area)) {
                    break;   
                } else {
                    usleep($usleep);
                    $usleep *= 2;
                }
            }
            $this->_cache->set($url, $area);
        } else {
            print("get $url from cache\n");
        }
        foreach ($area as $a) {
            $this->_rows[] = array(
                'id' => $a['id'],
                'name' => $a['name'],
                'pid' => $pid,
            );
            $this->getChild($a['id'], $depth+1);
        }
    }
}

function get_column($array, $key)
{
    $result = array();
    foreach ($array as $item) {
        $result[] = $item[$key];
    }
    
    return $result;
}

class Cache
{
    public $storageDir;
    
    public function __construct()
    {
        $this->storageDir = dirname(__FILE__) . '/cache';
        if (!file_exists($this->storageDir)) {
            mkdir($this->storageDir);
        }
    }

    public function get($key)
    {
        if (!file_exists($file = $this->storageDir.'/'.md5($key))) {
            return false;
        }
        return json_decode(file_get_contents($file), true);
    }
    
    public function set($key, $data)
    {
        file_put_contents($this->storageDir.'/'.md5($key), json_encode($data));
    }
}