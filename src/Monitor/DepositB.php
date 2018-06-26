<?php
/**
 * Created by PhpStorm.
 * User: ganodermaking
 * Date: 2017/11/5
 * Time: 下午10:32
 */

namespace BaodSync\Monitor;

use BaodSync\Lib\Amqp;
use BaodSync\Data\User;
use BaodSync\Lib\MongoDb;

class DepositB extends Base
{
    /**
     * 定存宝B购买(插入)
     */
    const DEPOSITB_BUY_TABLE = 'edai_deposit_invest_platform';
    /**
     * 定存宝B回款(插入)
     */
    const DEPOSITB_REFUND_TABLE = 'edai_deposit_refund_platform';

    /**
     * @var array 涉及数据表
     */
    public static $tables = [
        self::DEPOSITB_BUY_TABLE,
        self::DEPOSITB_REFUND_TABLE
    ];

    /**
     * @var array 更新数据变化条件
     */
    public static $fields = ['status', 'update_time'];

    /**
     * 分类处理
     * @param array $message
     * @return array|string
     */
    public static function handle($message)
    {
        switch ($message['table']) {
            case self::DEPOSITB_BUY_TABLE:
                if ($message['type'] == self::WRITE) {
                    return self::buy($message['values'][0]);
                }
                break;
            case self::DEPOSITB_REFUND_TABLE:
                if ($message['type'] == self::UPDATE) {
                    $flag = self::verifyChangeFlag($message, self::$fields);
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
     * @param array $values
     * @return array
     */
    public static function buy($values)
    {
        $user = new User();
        $userInfo = $user->getUserInfo($values['uid']);

        $depositB = new \BaodSync\Data\DepositB();
        $depositBInfo = $depositB->getDepositBInfo($values['pid']);
        $num = $values['invest_money'] / $depositBInfo['price'];

        $message = [
            'userNumber' => $userInfo['unumbers'],
            'productId' => $values['pid'],
            'num' => $num,
            'couponId' => $values['coupons'],
            'investId' => $values['id'],
        ];

        // 仅适用开发环境，通过查询mongo，从而判断是否需要广播至当前MQ
        if (self::getEnv() == 'dev') {
            $condition = [
                'eventKey' => 'RABBIT_WORK_DEPOSITB_BUY',
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

        return Amqp::getInstance()->send('RABBIT_WORK_DEPOSITB_BUY_PLATFORM', $message);
    }

    /**
     * 回款
     * @param array $after
     * @return array
     */
    public static function refund($after)
    {
        $message = [
            'id' => $after['id'],
            'invest_id' => $after['invest_id'],
            'money' => $after['money'],
            'interest' => $after['interest'],
            'sortorder' => $after['sortorder'],
            'status' => $after['status'],
            'update_time' => $after['update_time'],
            'deal_time' => $after['deal_time'],
            'num' => $after['num'],
            'total' => $after['total'],
        ];

        // 仅适用开发环境，通过查询mongo，从而判断是否需要广播至当前MQ
        if (self::getEnv() == 'dev') {
            $condition = [
                'eventKey' => 'RABBIT_WORK_DEPOSIT_B_REFUND',
                'status' => 1,
                'data.id' => $message['id'],
                'data.invest_id' => $message['invest_id'],
            ];
            $result = MongoDb::getMongoInstance()->findOne($condition);
            if (self::verify($result['_id']) == false) {
                return 1234;
            }
        }

        return Amqp::getInstance()->send('RABBIT_WORK_DEPOSIT_B_REFUND_PLATFORM', $message);
    }
}
