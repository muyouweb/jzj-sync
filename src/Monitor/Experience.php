<?php
/**
 * Created by PhpStorm.
 * User: ganodermaking
 * Date: 2017/11/18
 * Time: 下午8:09
 */

namespace BaodSync\Monitor;

class Experience extends Base
{
    /**
     * 优惠券
     */
    const EXPERIENCE_TABLE = 'edai_experience_money';

    /**
     * @var array 涉及数据表
     */
    public static $tables = [
        self::EXPERIENCE_TABLE,
    ];

    /**
     * 验证数据变化条件(有数据变化，并且和存管版表数据不一致)
     * @param array $message
     * @return bool
     */
    protected static function verifyChangeFlagExperience($message)
    {
        $before = $message['values'][0]['before'];
        $after = $message['values'][0]['after'];
        $change = array_diff_assoc($after, $before);

        // 优惠券特殊判断条件
        $experience = new \BaodSync\Data\Experience();
        $experienceInfo = $experience->getExperienceInfo($after['id']);
        $flag = false;
        foreach ($change as $k => $v) {
            if (isset($experienceInfo[$k]) && $experienceInfo[$k] != $after[$k]) {
                $flag = true;
                break;
            }
        }

        return $flag;
    }

    /**
     * 分类处理
     * @param array $message
     * @return array|string
     */
    public static function handle($message)
    {
        switch ($message['table']) {
            case self::EXPERIENCE_TABLE:
                if ($message['type'] == self::UPDATE) {
                    $flag = self::verifyChangeFlagExperience($message);
                    if ($flag) {
                        return self::update($message['values'][0]['before'], $message['values'][0]['after']);
                    }
                }
                break;
        }
        return false;
    }

    /**
     * @param array $before
     * @param array $after
     * @return int
     */
    public static function update($before, $after)
    {
        $experience = new \BaodSync\Data\Experience();
        $change = array_diff_assoc($after, $before);
        return $experience->update($after['id'], $change);
    }
}
