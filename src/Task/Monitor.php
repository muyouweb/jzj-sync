<?php
/**
 * Created by PhpStorm.
 * User: ganodermaking
 * Date: 2017/11/17
 * Time: 下午1:56
 */

namespace BaodSync\Task;

use BaodSync\Lib\Amqp;
use BaodSync\Lib\Db;
use BaodSync\Lib\Redis;
use BaodSync\Monitor\Init;

class Monitor
{
    /**
     * @var \Doctrine\DBAL\Connection|null
     */
    public static $db;
    /**
     * @var \Redis
     */
    public static $redis;
    /**
     * @var \AMQPChannel
     */
    public static $amqp;
    /**
     * @var int
     */
    public static $sleepTime;
    /**
     * @var string
     */
    public static $eventListTable;
    /**
     * @var string
     */
    public static $elementElementSeparator;
    /**
     * @var string
     */
    public static $keyValueSeparator;
    /**
     * @var string
     */
    public static $beforeAfterSeparator;
    /**
     * @var string
     */
    public static $lastPos;

    /**
     * 初始化
     */
    public static function init()
    {
        self::$db = Db::getInstance();
        self::$redis = Redis::getInstance();
        self::$amqp = Amqp::getInstance();
        self::$sleepTime = getenv('SYNC_SLEEP_TIME');
        self::$eventListTable = getenv('SYNC_EVENT_LIST_TABLE');
        self::$elementElementSeparator = getenv('SYNC_ELEMENT_ELEMENT_SEPARATOR');
        self::$keyValueSeparator = getenv('SYNC_KEY_VALUE_SEPARATOR');
        self::$beforeAfterSeparator = getenv('SYNC_BEFORE_AFTER_SEPARATOR');

        self::$lastPos = getenv('SYNC_LAST_POS');
    }

    /**
     * 运行开始
     */
    public static function run()
    {
        // 初始化
        self::init();

        $timeOld = self::floatMicrotime();
        while (true) {
            // mysql保持心跳
            $sql = "select 1";
            try {
                self::$db->query($sql);
            } catch (\Exception $e) {
                self::$db->close();
                self::$db->connect();
                self::$db->query($sql);
            }

            // redis保持心跳
            try {
                self::$redis->ping();
            } catch (\Exception $e) {
                self::$redis = new Redis();
            }

            // amqp保持心跳
            try {
                self::$amqp->_connection->isConnected();
            } catch (\Exception $e) {
                self::$amqp = new Amqp();
            }

            try {
                $lastPos = self::getLastPos();
                $eventList = self::getEventList($lastPos);
                foreach ($eventList as $v) {
                    $message = [
                        'type' => $v['type'],
                        'table' => $v['table'],
                        'values' => self::getValues($v['type'], $v['values'])
                    ];

                    /**
                     * 1.优惠劵表同步
                     * 2.广播平台消息
                     */
                    $result = Init::handle($message);

                    if ($result == 1234) {
                        echo date("Y-m-d H:i:s", time()) . "\t此环境MQ不接收此消息\t" . $v['id'] . PHP_EOL;
                    } else {
                        if ($result === false) {
                            echo date("Y-m-d H:i:s", time()) . "\t" . $v['table'] . "\t" . $v['type'] . "\t" . $v['id'] . PHP_EOL;
                        }
                        self::updateEvent($v['id']);
                        self::updateLastPos($v['id']);
                    }
                }
            } catch (\Exception $e) {
                echo $e->getMessage();
            }

            $timeNew = self::floatMicrotime();
            $diff = $timeNew - $timeOld;
            if ($diff < self::$sleepTime) {
                usleep((self::$sleepTime - $diff) * 1000000);
            }
            $timeOld = self::floatMicrotime();
        }
    }

    /**
     * 返回当前 Unix 时间戳的微秒数
     * @return float
     */
    public static function floatMicrotime()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }

    /**
     * 获取事件列表
     * @param int $lastPos
     * @return array
     */
    public static function getEventList($lastPos)
    {
        $sql = 'SELECT `id`,`table_name`,`event_type`,`content` FROM `' . self::$eventListTable . '` WHERE `id` > ? AND `status` = 0';
        $result = self::$db->fetchAll($sql, [$lastPos]);

        $eventList = [];
        foreach ($result as $k => $v) {
            $eventList[$k]['id'] = $v['id'];
            $eventList[$k]['type'] = $v['event_type'];
            $eventList[$k]['table'] = $v['table_name'];
            $eventList[$k]['values'] = $v['content'];
        }

        return $eventList;
    }

    /**
     * 更新事件类型
     * @param int $id
     * @return int
     */
    public static function updateEvent($id)
    {
        $data = ['status' => 1];
        $identifier = ['id' => $id];
        return self::$db->update(self::$eventListTable, $data, $identifier);
    }

    /**
     * 组装数据
     * @param string $type
     * @param string $content
     * @return array|string
     */
    public static function getValues($type, $content)
    {
        switch ($type) {
            case 'write':
                $values = self::getWriteValues($content);
                break;
            case 'update':
                $values = self::getUpdateValues($content);
                break;
            default:
                $values = '难道还有其他事件？';
        }

        return $values;
    }

    /**
     * 组装写数据
     * @param string $content
     * @return array
     */
    public static function getWriteValues($content)
    {
        $values = [];
        $valueArr = explode(self::$elementElementSeparator, $content);
        foreach ($valueArr as $v) {
            if (strpos($v, self::$keyValueSeparator) != false) {
                $keyArr = explode(self::$keyValueSeparator, $v);
                $values[0][$keyArr[0]] = $keyArr[1];
            }
        }

        return $values;
    }

    /**
     * 组装更新数据
     * @param string $content
     * @return array
     */
    public static function getUpdateValues($content)
    {
        $values = [];
        $valueArr = explode(self::$elementElementSeparator, $content);
        foreach ($valueArr as $v) {
            if (strpos($v, self::$keyValueSeparator) != false) {
                $vArr = explode(self::$keyValueSeparator, $v);
                if (strpos($vArr[1], self::$beforeAfterSeparator) != false) {
                    $beforeAfter = explode(self::$beforeAfterSeparator, $vArr[1]);
                    $values[0]['before'][$vArr[0]] = $beforeAfter[0];
                    $values[0]['after'][$vArr[0]] = $beforeAfter[1];
                }
            }
        }

        return $values;
    }

    /**
     * 更新最后位置
     * @param int $pos
     */
    public static function updateLastPos($pos)
    {
        self::$redis->set(self::$lastPos, $pos);
    }

    /**
     * 获取最后位置
     * @return mixed
     */
    public static function getLastPos()
    {
        $pos = self::$redis->get(self::$lastPos);
        return $pos;
    }
}

include __DIR__ . '/../../vendor/autoload.php';
Monitor::run();
