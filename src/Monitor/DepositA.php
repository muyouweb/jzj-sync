<?php
/**
 * Created by PhpStorm.
 * User: ganodermaking
 * Date: 2017/11/6
 * Time: 下午2:45
 */

namespace BaodSync\Monitor;

use BaodSync\Lib\Amqp;
use BaodSync\Lib\MongoDb;

class DepositA extends Base
{
    /**
     * 定存宝A回款(更新)
     */
    const DEPOSITA_REFUND_TABLE = 'edai_licaitoubiaoku_new_platform';

    /**
     * @var array 涉及数据表
     */
    public static $tables = [
        self::DEPOSITA_REFUND_TABLE,
    ];

    /**
     * @var array 更新数据变化条件
     */
    public static $fields = ['ltstatus'];

    /**
     * 分类处理
     * @param array $message
     * @return array|string
     */
    public static function handle($message)
    {
        switch ($message['table']) {
            case self::DEPOSITA_REFUND_TABLE:
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
     * 回款
     * @param array $after
     * @return array
     */
    public static function refund($after)
    {
        $message = [
            'productId' => $after['ltid'],
            'productType' => "A",
            'back_month' => 1
        ];

        // 仅适用开发环境，通过查询mongo，从而判断是否需要广播至当前MQ
        if (self::getEnv() == 'dev') {
            $condition = [
                'eventKey' => 'RABBIT_WORK_DEPOSITB_BUY',
                'status' => 1,
                'data.productId' => $message['productId'],
                'data.productType' => $message['productType'],
                'data.back_month' => $message['back_month'],
            ];
            $result = MongoDb::getMongoInstance()->findOne($condition);
            if (self::verify($result['_id']) == false) {
                return 1234;
            }
        }

        return Amqp::getInstance()->send('RABBIT_WORK_DEPOSIT_A_REFUND_PLATFORM', $message);
    }
}
