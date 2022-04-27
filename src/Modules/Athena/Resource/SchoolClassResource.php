<?php

namespace App\Modules\Athena\Resource;

use App\Classes\Exception\ErrorException;

class SchoolClassResource
{
    private static $classes = [
        [
            'credit' => 2500,
            'minSize' => 1,
            'maxSize' => 1,
            'minExp' => 100,
            'maxExp' => 210,
            'point' => 5,
            'title' => 'Engager un officier nul', ],
        [
            'credit' => 2500,
            'minSize' => 1,
            'maxSize' => 1,
            'minExp' => 100,
            'maxExp' => 210,
            'point' => 5,
            'title' => 'Engager un officier nul', ],
        [
            'credit' => 2500,
            'minSize' => 1,
            'maxSize' => 1,
            'minExp' => 100,
            'maxExp' => 210,
            'point' => 5,
            'title' => 'Engager un officier nul', ],
        ];

    public static function getInfo($i, $info)
    {
        if (in_array($info, ['credit', 'minSize', 'maxSize', 'minExp', 'maxExp', 'point', 'title'])) {
            if ($i < self::size()) {
                return self::$classes[$i][$info];
            } else {
                return false;
            }
        } else {
            throw new ErrorException('info inconnue dans getInfo de SchoolClassResource');
        }
    }

    public static function size()
    {
        return count(self::$classes);
    }
}
