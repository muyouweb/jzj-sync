<?php
/**
 * Created by PhpStorm.
 * User: ganodermaking
 * Date: 2017/11/6
 * Time: 上午9:15
 */

namespace BaodSync\Monitor;

use Dotenv\Dotenv;

class Base
{
    const WRITE = 'write';
    const UPDATE = 'update';

    /**
     * 验证数据变化条件
     * @param array $message
     * @param array $fields
     * @return bool
     */
    protected static function verifyChangeFlag($message, $fields)
    {
        $before = $message['values'][0]['before'];
        $after = $message['values'][0]['after'];
        $change = array_diff_assoc($after, $before);

        $flag = true;
        foreach ($fields as $v) {
            if (!in_array($v, array_keys($change))) {
                $flag = false;
                break;
            }
        }

        return $flag;
    }

    /**
     * @param string $msgId
     * @return bool
     */
    protected static function verify($msgId)
    {
        $orderNoArr = explode("-", $msgId);
        $serverIp = $orderNoArr[1];
        $serverIp = str_replace("_", ".", $serverIp);

        if ($serverIp == getenv('SERVER_IP')) {
            return true;
        }
        return false;
    }

    /**
     * @return array|false|string
     */
    protected static function getEnv()
    {
        $dotenv = new Dotenv("/mnt/home/webroot/webconfig", "sync_dev.env");
        $dotenv->load();

        return getenv('APP_SERVER');
    }
}
