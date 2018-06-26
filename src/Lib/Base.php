<?php
/**
 * Created by PhpStorm.
 * User: ganodermaking
 * Date: 2017/10/25
 * Time: 下午1:59
 */

namespace BaodSync\Lib;

use Dotenv\Dotenv;

class Base
{
    const PATH = '/mnt/home/webroot/webconfig';
    const FILE = 'sync.env';

    /**
     * 构造函数
     */
    public function __construct()
    {
        $dotenv = new Dotenv(self::PATH, self::FILE);
        $dotenv->load();
    }
}

