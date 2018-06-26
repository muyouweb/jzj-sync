<?php
/**
 * Created by PhpStorm.
 * User: ganodermaking
 * Date: 2017/11/5
 * Time: 下午10:30
 */

namespace BaodSync\Monitor;

use BaodSync\Lib\Amqp;
use BaodSync\Lib\MongoDb;

class Direct extends Base
{
    /**
     * 直投购买(插入)
     */
    const DIRECT_BUY_TABLE = 'edai_borrow_investor_platform';
    /**
     * 直投复审、回款(更新)
     */
    const DIRECT_REVIEW_REFUND_TABLE = 'edai_borrowinfo_platform';

    /**
     * @var array 涉及数据表
     */
    public static $tables = [
        self::DIRECT_BUY_TABLE,
        self::DIRECT_REVIEW_REFUND_TABLE,
    ];

    /**
     * @var array 更新数据变化条件
     */
    public static $buyFields = ['investor_capital', 'investor_interest', 'add_time'];

    /**
     * @var array 更新数据变化条件
     */
    public static $reviewFields = ['borrow_status', 'second_verify_time'];
    /**
     * @var array 更新数据变化条件
     */
    public static $refundFields = ['has_pay'];

    /**
     * 分类处理
     * @param array $message
     * @return array|string
     */
    public static function handle($message)
    {
        switch ($message['table']) {
            case self::DIRECT_BUY_TABLE:
                if ($message['type'] == self::WRITE) {
                    return self::buy($message['values'][0]);
                } elseif ($message['type'] == self::UPDATE) {
                    $flag = self::verifyChangeFlag($message, self::$buyFields);
                    if ($flag) {
                        return self::buyUpdate($message['values'][0]['before'], $message['values'][0]['after']);
                    }
                }
                break;
            case self::DIRECT_REVIEW_REFUND_TABLE:
                if ($message['type'] == self::UPDATE) {
                    $flag = self::verifyChangeFlag($message, self::$reviewFields);
                    $flag_refund = self::verifyChangeFlag($message, self::$refundFields);
                    if ($flag) {
                        return self::review($message['values'][0]['after']);
                    } elseif ($flag_refund) {
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
        $direct = new \BaodSync\Data\Direct();
        $directInfo = $direct->getDirectInfo($values['borrow_id']);
        $num = $values['investor_capital'] / $directInfo['borrow_min'];

        $message = [
            'userNumber' => $values['investor_uid'],
            'productId' => $values['borrow_id'],
            'num' => $num,
            'couponId' => $values['increase'],
        ];

        // 仅适用开发环境，通过查询mongo，从而判断是否需要广播至当前MQ
        if (self::getEnv() == 'dev') {
            $condition = [
                'eventKey' => 'RABBIT_WORK_DIRECT_BUY',
                'status' => 1,
                'data.userNumber' => $message['userNumber'],
                'data.productId' => $message['productId'],
                'data.num' => $message['num'],
                'data.couponId' => $message['couponId'],
            ];
            $result = MongoDb::getMongoInstance()->findOne($condition);
            if (self::verify($result['_id']) == false) {
                return 1234;
            }
        }

        return Amqp::getInstance()->send('RABBIT_WORK_DIRECT_BUY_PLATFORM', $message);
    }

    /**
     * 购买
     * @param array $before
     * @param array $after
     * @return array
     */
    public static function buyUpdate($before, $after)
    {
        $direct = new \BaodSync\Data\Direct();
        $directInfo = $direct->getDirectInfo($after['borrow_id']);
        $num = ($after['investor_capital'] - $before['investor_capital']) / $directInfo['borrow_min'];

        // 获取优惠券，优惠券ID字段变化，说明使用优惠券
        $couponId = '';
        if ($after['increase'] != $before['increase']) {
            $couponId = $after['increase'];
        }

        $message = [
            'userNumber' => $after['investor_uid'],
            'productId' => $after['borrow_id'],
            'num' => $num,
            'couponId' => $couponId,
        ];

        // 仅适用开发环境，通过查询mongo，从而判断是否需要广播至当前MQ
        if (self::getEnv() == 'dev') {
            $condition = [
                'eventKey' => 'RABBIT_WORK_DIRECT_BUY',
                'status' => 1,
                'data.userNumber' => $message['userNumber'],
                'data.productId' => $message['productId'],
                'data.num' => $message['num'],
                'data.couponId' => $message['couponId'],
            ];
            $result = MongoDb::getMongoInstance()->findOne($condition);
            if (self::verify($result['_id']) == false) {
                return 1234;
            }
        }

        return Amqp::getInstance()->send('RABBIT_WORK_DIRECT_BUY_PLATFORM', $message);
    }

    /**
     * 复审
     * @param array $after
     * @return array
     */
    public static function review($after)
    {
        $message = [
            'productId' => $after['edid'],
        ];

        // 仅适用开发环境，通过查询mongo，从而判断是否需要广播至当前MQ
        if (self::getEnv() == 'dev') {
            $condition = [
                'eventKey' => 'RABBIT_WORK_DIRECT_REVIEW',
                'status' => 1,
                'data.borrow_id' => $message['productId'],
            ];
            $result = MongoDb::getMongoInstance()->findOne($condition);
            if (self::verify($result['_id']) == false) {
                return 1234;
            }
        }

        return Amqp::getInstance()->send('RABBIT_WORK_DIRECT_REVIEW_PLATFORM', $message);
    }

    /**
     * 回款
     * @param array $after
     * @return array
     */
    public static function refund($after)
    {
        $message = [
            'borrow_id' => $after['edid'],
            'productId' => $after['edid'],
            'back_month' => $after['has_pay'],
        ];

        // 仅适用开发环境，通过查询mongo，从而判断是否需要广播至当前MQ
        if (self::getEnv() == 'dev') {
            $condition = [
                'eventKey' => 'RABBIT_WORK_DIRECT_REFUND',
                'status' => 1,
                'data.borrow_id' => $message['borrow_id'],
                'data.productId' => $message['productId'],
                'data.back_month' => $message['back_month'],
            ];
            $result = MongoDb::getMongoInstance()->findOne($condition);
            if (self::verify($result['_id']) == false) {
                return 1234;
            }
        }

        return Amqp::getInstance()->send('RABBIT_WORK_DIRECT_REFUND_PLATFORM', $message);
    }
}
