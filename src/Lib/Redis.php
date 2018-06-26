<?php
/**
 * Created by PhpStorm.
 * User: ganodermaking
 * Date: 2017/10/10
 * Time: 下午4:03
 */

namespace BaodSync\Lib;

/**
 * Created by PhpStorm.
 * User: ganodermaking
 * Date: 2017/10/20
 * Time: 下午3:10
 */
class Redis extends Base
{
    /**
     * @var null|\Redis
     */
    private $_redis = null;

    /**
     * 构造函数
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct();

        $host = getenv('REDIS_HOST');
        $port = getenv('REDIS_PORT');
        $password = getenv('REDIS_PASSWORD');
        $db = getenv('REDIS_DB');

        $this->_redis = new \Redis();
        $connect = $this->_redis->connect($host, $port);
        if (!$connect) {
            throw new \Exception('Redis连接失败，请检查配置');
        }
        if ($password) {
            $this->_redis->auth($password);
        }
        $this->_redis->select($db);
    }

    /**
     * 防止克隆
     */
    private function __clone()
    {

    }

    /**
     * 只能通过静态方法获得该类的对象(单例模式)
     * @return null|\Redis
     */
    public static function getInstance()
    {
        static $obj = null;
        if ($obj == null) {
            $obj_tmp = new Redis();
            $obj = $obj_tmp->_redis;
        }
        return $obj;
    }
}
