<?php
/**
 * Created by PhpStorm.
 * User: ganodermaking
 * Date: 2018/2/24
 * Time: 下午2:16
 */

namespace BaodSync\Task;

use BaodSync\Lib\Db;
use BaodSync\Lib\Redis;

class Clear
{
    /**
     * 运行开始
     */
    public static function run()
    {
        $db = Db::getInstance();
        $redis = Redis::getInstance();

        $eventListTable = getenv('SYNC_EVENT_LIST_TABLE');
        $lastPos = getenv('SYNC_LAST_POS');

        $pos = $redis->get($lastPos);
        $sql = 'DELETE FROM `' . $eventListTable . '` WHERE `id` <= ? AND `status` = 1';
        return $db->executeUpdate($sql, [$pos]);
    }
}

include __DIR__ . '/../../vendor/autoload.php';
Clear::run();