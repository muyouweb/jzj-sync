<?php
/**
 * Created by PhpStorm.
 * User: ganodermaking
 * Date: 2017/11/8
 * Time: 下午3:20
 */

namespace BaodSync\Data;

use BaodSync\Lib\Db;

class User
{
    /**
     * 获取用户信息
     * @param integer $uid
     * @return array
     */
    public function getUserInfo($uid)
    {
        $sql = "SELECT * FROM `edai_usersystab` WHERE `uid`=?";
        return Db::getInstance()->fetchAssoc($sql, [$uid]);
    }
}
