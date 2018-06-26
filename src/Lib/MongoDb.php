<?php
/**
 * Created by PhpStorm.
 * User: ganodermaking
 * Date: 2018/6/21
 * Time: 下午2:37
 */

namespace BaodSync\Lib;

use Dotenv\Dotenv;

class MongoDb
{
    const PARAM_INT_ARRAY = \Doctrine\DBAL\Connection::PARAM_INT_ARRAY;
    const PARAM_STR_ARRAY = \Doctrine\DBAL\Connection::PARAM_STR_ARRAY;
    /**
     * @var \MongoDB|null 连接对象 (private防止实例化)
     */
    public $db = null;
    /**
     * 构造函数连接数据库（private防止实例化）
     */
    private function __construct()
    {
        $dotenv = new Dotenv("/mnt/home/webroot/webconfig", "sync_dev.env");
        $dotenv->load();

        $host = getenv('MONGO_HOST');
        $port = getenv('MONGO_PORT');
        $dbname = getenv('MONGO_DBNAME');
        $connectTimeoutMS = getenv('MONGO_CONNECT_TIMEOUT_MS');
        $options = array('connect' => true, 'connectTimeoutMS' => $connectTimeoutMS, 'db' => $dbname);
        $isAuth = getenv('MONGO_AUTH');
        if ($isAuth == 0) {
            $this->db = new \MongoClient("mongodb://$host:$port", $options);
        } else {
            $username = getenv('MONGO_USERNAME');
            $password = getenv('MONGO_PASSWORD');
            $this->db = new \MongoClient("mongodb://$username:$password@$host:$port", $options);
        }
    }
    /**
     * 防止克隆
     *
     * @param  string  $table  表名
     * @param  array   $data   数据
     * @return mixed
     */
    public function insert($table, $data)
    {
        $dbName = getenv('MONGO_DBNAME');
        $socketTimeoutMS = getenv('MONGO_SOCKET_TIMEOUT_MS');
        $collection = $this->db->selectCollection($dbName, $table);
        $options = array('w' => true, 'fsync' => false, 'socketTimeoutMS' => $socketTimeoutMS);
        return $collection->insert($data, $options);
    }
    /**
     * 只能通过静态方法获得该类的对象（单例模式）
     */
    public static function getInstance()
    {
        static $obj = null;
        if ($obj == null) {
            $obj = new MongoDb();
        }
        return $obj;
    }
    /**
     * 只能通过静态方法获得该类的对象（单例模式）
     */
    public static function getMongoInstance()
    {
        $dbName = getenv('MONGO_DBNAME');
        $collectionName = getenv('MONGO_DEFAULT_COLLECTION');
        $collection = MongoDb::getInstance()->db->selectCollection($dbName, $collectionName);
        return $collection;
    }
    /**
     * 防止克隆
     */
    private function __clone()
    {

    }
}