<?php
/**
 * Created by PhpStorm.
 * User: ganodermaking
 * Date: 2017/11/8
 * Time: 下午3:03
 */

namespace BaodSync\Data;

use BaodSync\Lib\Db;

class DepositB
{
    /**
     * 获取定存宝B产品信息
     * @param integer $productId
     * @return array
     */
    public function getDepositBInfo($productId)
    {
        $sql = "SELECT * FROM `edai_deposit_platform` WHERE `id`=?";
        return Db::getInstance()->fetchAssoc($sql, [$productId]);
    }
}
