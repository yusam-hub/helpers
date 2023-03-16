<?php

use YusamHub\Helper\DotArray;

if (! function_exists('helper_dot_array')) {

    /**
     * @param DotArray|array|mixed $value
     * @return DotArray
     */
    function helper_dot_array($value): DotArray
    {
        return new DotArray($value);
    }
}