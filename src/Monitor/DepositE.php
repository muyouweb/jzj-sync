<?php
/**
 * Created by PhpStorm.
 * User: ganodermaking
 * Date: 2018/5/11
 * Time: 上午11:38
 */

namespace BaodSync\Monitor;

use BaodSync\Lib\Amqp;
use BaodSync\Lib\MongoDb;

class DepositE extends Base
{
    /**
     * 定存宝E购买(新增)
     */
    const DEPOSITE_BUY_TABLE = 'edai_deposit_e_user_platform';
    /**
     * 定存宝E回款(更新)
     */
    const DEPOSITE_REFUND_TABLE = 'edai_deposit_e_refund_platform';

    /**
     * @var array 涉及数据表
     */
    public static $tables = [
        self::DEPOSITE_BUY_TABLE,
    ];

    /**
     * 验证数据变化条件(有数据变化，并且和存管版表数据不一致)
     * @param array $before
     * @param array $after
     * @return bool
     */
    protected static function verifyChangeRefund($before, $after)
    {
        if ($before['status'] == 0 && $after['status'] == 9) {
            return true;
        }
        return false;
    }

    /**
     * 分类处理
     * @param array $message
     * @return array|string
     */
    public static function handle($message)
    {
        switch ($message['table']) {
            case self::DEPOSITE_BUY_TABLE:
                if ($message['type'] == self::WRITE) {
                    return self::buy($message['values'][0]);
                }
                break;
            case self::DEPOSITE_REFUND_TABLE:
                if ($message['type'] == self::UPDATE) {
                    $flag = self::verifyChangeRefund($message['values'][0]['before'], $message['values'][0]['after']);
                    if ($flag) {
                        return self::refund($message['values'][0]['after']);
                    }
                }
                break;
        }
        return false;
    }

    /**
     * 购买
     * @param array $after
     * @return array
     */
    public static function buy($after)
    {
        $message = [
            'userNumber' => $after['unumbers'],
            'investId' => $after['invest_id'],
            'productId' => $after['pid'],
            'num' => $after['invest_num'],
        ];

        // 仅适用开发环境，通过查询mongo，从而判断是否需要广播至当前MQ
        if (self::getEnv() == 'dev') {
            $condition = [
                'eventKey' => 'RABBIT_WORK_DEPOSIT_E_BUY',
                'status' => 1,
                'data.userNumber' => $message['userNumber'],
                'data.productId' => intval($message['productId']),
                'data.num' => intval($message['num']),
            ];
            $result = MongoDb::getMongoInstance()->findOne($condition);
            if (self::verify($result['_id']) == false) {
                return 1234;
            }
        }

        return Amqp::getInstance()->send('RABBIT_WORK_DEPOSIT_E_BUY_PLATFORM', $message);
    }

    /**
     * 回款
     * @param array $after
     * @return array
     */
    public static function refund($after)
    {
        $message = [
            'investId' => $after['invest_id'],
        ];
        return Amqp::getInstance()->send('RABBIT_WORK_DEPOSIT_E_REFUND_PLATFORM', $message);
    }
}