<?php
/**
 * Created by PhpStorm.
 * User: ganodermaking
 * Date: 2017/11/5
 * Time: 下午10:33
 */

namespace BaodSync\Monitor;

use BaodSync\Lib\Amqp;
use BaodSync\Data\User;
use BaodSync\Lib\MongoDb;

class Transfer extends Base
{
    /**
     * 债权转让购买(插入)
     */
    const TRANSFER_BUY_TABLE = 'edai_transfer_user_platform';
    /**
     *  债权转让复审(插入)
     */
    const TRANSFER_REVIEW_TABLE = 'edai_transfer_platform';
    /**
     * 债权转让回款(插入)
     */
    const TRANSFER_REFUND_TABLE = 'edai_transfer_user_log_platform';

    /**
     * @var array 涉及数据表
     */
    public static $tables = [
        self::TRANSFER_BUY_TABLE,
        self::TRANSFER_REVIEW_TABLE,
        self::TRANSFER_REFUND_TABLE
    ];

    /**
     * @var array 更新数据变化条件
     */
    public static $fullFields = ['has_borrow_num', 'status', 'timestamp_full'];

    /**
     * 分类处理
     * @param array $message
     * @return array|string
     */
    public static function handle($message)
    {
        switch ($message['table']) {
            case self::TRANSFER_BUY_TABLE:
                if ($message['type'] == self::WRITE) {
                    return self::buy($message['values'][0]);
                }
                break;
            case self::TRANSFER_REVIEW_TABLE:
                if ($message['type'] == self::WRITE) {
                    return self::review($message['values'][0]);
                } elseif ($message['type'] == self::UPDATE) {
                    $flag = self::verifyChangeFlag($message, self::$fullFields);
                    if ($flag) {
                        return self::full($message['values'][0]['after']);
                    }
                }
                break;
            case self::TRANSFER_REFUND_TABLE:
                if ($message['type'] == self::WRITE) {
                    return self::refund($message['values'][0]);
                }
                break;
        }
        return false;
    }

    /**
     * 购买
     * @param array $values
     * @return array
     */
    public static function buy($values)
    {
        $user = new User();
        $userInfo = $user->getUserInfo($values['uid']);

        $message = [
            'userNumber' => $userInfo['unumbers'],
            'productId' => $values['tid'],
            'investId' => $values['id'],
        ];

        // 仅适用开发环境，通过查询mongo，从而判断是否需要广播至当前MQ
        if (self::getEnv() == 'dev') {
            $condition = [
                'eventKey' => 'RABBIT_WORK_TRANSFER_BUY',
                'status' => 1,
                'data.productId' => $message['productId'],
            ];
            $result = MongoDb::getMongoInstance()->findOne($condition);
            if (self::verify($result['_id']) == false) {
                return 1234;
            }
        }

        return Amqp::getInstance()->send('RABBIT_WORK_TRANSFER_BUY_PLATFORM', $message);
    }

    /**
     * 复审
     * @param array $values
     * @return array
     */
    public static function review($values)
    {
        $message = [
            'productId' => $values['id'],
        ];

        // 仅适用开发环境，通过查询mongo，从而判断是否需要广播至当前MQ
        if (self::getEnv() == 'dev') {
            $condition = [
                'eventKey' => 'RABBIT_WORK_TRANSFER_REVIEW',
                'status' => 1,
                'data.productId' => $message['productId'],
            ];
            $result = MongoDb::getMongoInstance()->findOne($condition);
            if (self::verify($result['_id']) == false) {
                return 1234;
            }
        }

        return Amqp::getInstance()->send('RABBIT_WORK_TRANSFER_REVIEW_PLATFORM', $message);
    }

    /**
     * 回款
     * @param array $values
     * @return array
     */
    public static function refund($values)
    {
        $user = new User();
        $userInfo = $user->getUserInfo($values['uid']);

        $message = [
            'userNumber' => $userInfo['unumbers'],
            'productId' => $values['tid'],
        ];

        // 仅适用开发环境，通过查询mongo，从而判断是否需要广播至当前MQ
        if (self::getEnv() == 'dev') {
            $condition = [
                'eventKey' => 'RABBIT_WORK_TRANSFER_REFUND',
                'status' => 1,
                'data.userNumber' => $message['userNumber'],
                'data.productId' => $message['productId'],
            ];
            $result = MongoDb::getMongoInstance()->findOne($condition);
            if (self::verify($result['_id']) == false) {
                return 1234;
            }
        }

        return Amqp::getInstance()->send('RABBIT_WORK_TRANSFER_REFUND_PLATFORM', $message);
    }

    /**
     * 满标
     * @param array $values
     * @return array
     */
    public static function full($values)
    {
        $message = [
            'productId' => $values['id'],
        ];

        // 仅适用开发环境，通过查询mongo，从而判断是否需要广播至当前MQ
        if (self::getEnv() == 'dev') {
            $condition = [
                'eventKey' => 'RABBIT_WORK_TRANSFER_FULL',
                'status' => 1,
                'data.productId' => $message['productId'],
            ];
            $result = MongoDb::getMongoInstance()->findOne($condition);
            if (self::verify($result['_id']) == false) {
                return 1234;
            }
        }

        return Amqp::getInstance()->send('RABBIT_WORK_TRANSFER_FULL_PLATFORM', $message);
    }
}
