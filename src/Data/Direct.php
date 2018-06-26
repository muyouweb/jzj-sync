<?php
/**
 * Created by PhpStorm.
 * User: ganodermaking
 * Date: 2017/11/7
 * Time: 上午11:35
 */

namespace BaodSync\Data;

use BaodSync\Lib\Db;

class Direct
{
    /**
     * 获取直投产品信息
     * @param integer $productId
     * @return array
     */
    public function getDirectInfo($productId)
    {
        $sql = "SELECT * FROM `edai_borrowinfo_platform` WHERE `edid`=?";
        return Db::getInstance()->fetchAssoc($sql, [$productId]);
    }
}
