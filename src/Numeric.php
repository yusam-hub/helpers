<?php

namespace YusamHub\Helper;
class Numeric
{
    public static function is_between($value, $from, $to): bool
    {
        return ($value >= $from) && ($value <= $to);
    }

    public static function clamp($current,  $min, $max)
    {
        if ($min > $max) {
            throw new \InvalidArgumentException("Minimum ($min) is not less than maximum ($max).");
        }
        return max($min, min($max, $current));
    }
}