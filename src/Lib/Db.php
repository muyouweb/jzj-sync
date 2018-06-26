<?php
/**
 * Created by PhpStorm.
 * User: ganodermaking
 * Date: 2017/11/4
 * Time: 上午9:04
 */

namespace BaodSync\Lib;

class Db extends Base
{
    /**
     * @var \Doctrine\DBAL\Connection|null
     */
    public $conn = null;

    /**
     * 构造函数
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct();

        $host = getenv('DB_HOST');
        $port = getenv('DB_PORT');
        $user = getenv('DB_USER');
        $password = getenv('DB_PASSWORD');
        $db = getenv('DB_DATABASES_ONLY');

        $config = new \Doctrine\DBAL\Configuration();
        $dbConfig = [
            'host' => $host,
            'port' => $port,
            'user' => $user,
            'password' => $password,
            'dbname' => $db,
            'driver' => 'pdo_mysql',
        ];
        $this->conn = \Doctrine\DBAL\DriverManager::getConnection($dbConfig, $config);
        if (!$this->conn) {
            throw new \Exception('Mysql连接失败，请检查配置');
        }
        $this->conn->query("set names utf8");
    }


    /**
     * @return \Doctrine\DBAL\Connection|null
     */
    public static function getInstance()
    {
        static $obj = null;
        if ($obj == null) {
            $obj_tmp = new Db();
            $obj = $obj_tmp->conn;
        }
        return $obj;
    }
}
