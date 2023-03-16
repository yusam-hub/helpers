<?php

namespace YusamHub\Helper;
class DotArray
{
    /**
     * @var array
     */
    protected array $sourceArray = [];

    /**
     * @param DotArray|array|mixed $value
     */
    public function __construct($value = [])
    {
        $this->import($value);
    }

    /**
     * @param DotArray|array|mixed $value
     * @return void
     */
    public function import($value): void
    {
        if ($value instanceof self) {
            $this->sourceArray = $value->all();
        } elseif (is_array($value)) {
            $this->sourceArray = $value;
        } else {
            $this->sourceArray = (array) $value;
        }
    }

    /**
     * @return array
     */
    public function all(): array
    {
        return $this->sourceArray;
    }

    /**
     * @param string $dotKey
     * @return bool
     */
    public function has(string $dotKey): bool
    {
        $keys = explode(".", trim($dotKey, '.'));
        $source = $this->sourceArray;
        foreach($keys as $key) {
            if (isset($source[$key])) {
                $source = $source[$key];
            } else {
                return false;
            }
        }
        return true;
    }

    /**
     * @param string $dotKey
     * @param mixed $default
     * @return mixed
     */
    public function get(string $dotKey, $default = null)
    {
        if (empty($dotKey)) {
            return $this->sourceArray;
        }
        $keys = explode(".", trim($dotKey, '.'));
        $source = $this->sourceArray;
        foreach($keys as $key) {
            if (isset($source[$key])) {
                $source = $source[$key];
            } else {
                return $default;
            }
        }
        return $source;
    }


    /**
     * @param string $dotKey
     * @param null $value
     * @param bool $kSortAfterMerge
     * @return bool
     */
    public function set(string $dotKey, $value = null, bool $kSortAfterMerge = false): bool
    {
        if (empty($dotKey)) {
            return false;
        }
        $keys = explode(".", trim($dotKey, '.'));
        $source = [];
        $max = count($keys);
        for($i = $max-1; $i >= 0; $i--) {
            $key = $keys[$i];
            if ($i === $max-1) {
                $source[$key] = $value;
            } else {
                $v = $source;
                $source = [];
                $source[$key] = $v;
            }
        }
        if (!empty($source)) {
            $this->sourceArray = array_merge_recursive($this->sourceArray, $source);
            if ($kSortAfterMerge) {
                $this->recursiveKSort($this->sourceArray);
            }
            return true;
        }

        return false;
    }

    /**
     * @param $array
     * @return void
     */
    private function recursiveKSort(&$array) {
        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->recursiveKSort($value);
            }
        }
        ksort($array);
    }

    /**
     * @return array
     */
    public function getDotKeys(): array
    {
        $out = [];

        $this->fetch("", $this->sourceArray, $out);

        return $out;
    }

    /**
     * @param string $dotKey
     * @param $source
     * @param array $out
     * @return void
     */
    private function fetch(string $dotKey, $source, array &$out): void
    {
        $keys = [];
        if (is_array($source)) {
            $keys = array_keys($source);
        } elseif ($source instanceof DotArray) {
            $source = $source->all();
            $keys = array_keys($source);
        }
        foreach($keys as $k) {
            $newDotKey = empty($dotKey) ? $k : $dotKey. "." . $k;
            $out[] = $newDotKey;
            $this->fetch($newDotKey, $source[$k], $out);
        }
    }
};