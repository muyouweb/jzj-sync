<?php
/**
 * Created by PhpStorm.
 * User: ganodermaking
 * Date: 2017/11/18
 * Time: 下午8:27
 */

namespace BaodSync\Data;

use BaodSync\Lib\Db;

class Experience
{
    /**
     * 获取优惠券信息
     * @param int $id 主键id
     * @return array
     */
    public function getExperienceInfo($id)
    {
        $sql = "SELECT * FROM `edai_experience_money` WHERE `id`=?";
        return Db::getInstance()->fetchAssoc($sql, [$id]);
    }

    /**
     * 更新优惠券信息
     * @param int $id
     * @param array $data
     * @return int
     */
    public function update($id, $data)
    {
        $identifier = [
            'id' => $id
        ];
        return Db::getInstance()->update('edai_experience_money', $data, $identifier);
    }
}
