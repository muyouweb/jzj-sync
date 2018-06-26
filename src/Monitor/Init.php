<?php
/**
 * Created by PhpStorm.
 * User: ganodermaking
 * Date: 2017/11/6
 * Time: 下午2:30
 */

namespace BaodSync\Monitor;

class Init
{
    public static function handle($message)
    {
        $result = false;
        if (in_array($message['table'], Direct::$tables)) {
            $result = Direct::handle($message);
        } elseif (in_array($message['table'], DepositA::$tables)) {
            $result = DepositA::handle($message);
        } elseif (in_array($message['table'], DepositB::$tables)) {
            $result = DepositB::handle($message);
        } elseif (in_array($message['table'], DepositE::$tables)) {
            $result = DepositE::handle($message);
        } elseif (in_array($message['table'], Transfer::$tables)) {
            $result = Transfer::handle($message);
        } elseif (in_array($message['table'], Experience::$tables)) { // 优惠劵表同步
            $result = Experience::handle($message);
        }

        return $result;
    }
}
