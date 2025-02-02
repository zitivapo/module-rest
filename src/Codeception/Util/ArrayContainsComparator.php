<?php

declare(strict_types=1);

namespace Codeception\Util;

use function array_intersect;
use function array_keys;
use function count;
use function is_array;
use function is_numeric;
use function min;
use function range;

class ArrayContainsComparator
{
    protected array $haystack;

    public function __construct(array $haystack)
    {
        $this->haystack = $haystack;
    }

    public function getHaystack(): array
    {
        return $this->haystack;
    }

    public function containsArray(array $needle): bool
    {
        return $needle == $this->arrayIntersectRecursive($needle, $this->haystack);
    }

    /**
     * @author nleippe@integr8ted.com
     * @author tiger.seo@gmail.com
     * @link   https://www.php.net/manual/en/function.array-intersect-assoc.php#39822
     */
    private function arrayIntersectRecursive(mixed $arr1, mixed $arr2): bool|array|null
    {
        if (!is_array($arr1) || !is_array($arr2)) {
            return false;
        }
        // if it is not an associative array we do not compare keys
        if ($this->arrayIsSequential($arr1) && $this->arrayIsSequential($arr2)) {
            return $this->sequentialArrayIntersect($arr1, $arr2);
        }
        return $this->associativeArrayIntersect($arr1, $arr2);
    }

    /**
     * This array has sequential keys?
     */
    private function arrayIsSequential(array $array): bool
    {
        return array_keys($array) === range(0, count($array) - 1);
    }

    private function sequentialArrayIntersect(array $arr1, array $arr2): array
    {
        $ret = [];

        // Do not match the same item of $arr2 against multiple items of $arr1
        $matchedKeys = [];
        foreach ($arr1 as $key1 => $value1) {
            foreach ($arr2 as $key2 => $value2) {
                if (isset($matchedKeys[$key2])) {
                    continue;
                }

                $return = $this->arrayIntersectRecursive($value1, $value2);
                if ($return !== false && $return == $value1) {
                    $ret[$key1] = $return;
                    $matchedKeys[$key2] = true;
                    break;
                }

                if ($this->isEqualValue($value1, $value2)) {
                    $ret[$key1] = $value1;
                    $matchedKeys[$key2] = true;
                    break;
                }
            }
        }

        return $ret;
    }

    private function associativeArrayIntersect(array $arr1, array $arr2): bool|array|null
    {
        $commonKeys = array_intersect(array_keys($arr1), array_keys($arr2));

        $ret = [];
        foreach ($commonKeys as $key) {
            $return = $this->arrayIntersectRecursive($arr1[$key], $arr2[$key]);
            if ($return !== false) {
                $ret[$key] = $return;
                continue;
            }
            if ($this->isEqualValue($arr1[$key], $arr2[$key])) {
                $ret[$key] = $arr1[$key];
            }
        }

        if (empty($commonKeys)) {
            foreach ($arr2 as $arr) {
                $return = $this->arrayIntersectRecursive($arr1, $arr);
                if ($return && $return == $arr1) {
                    return $return;
                }
            }
        }

        if (count($ret) < min(count($arr1), count($arr2))) {
            return null;
        }

        return $ret;
    }

    private function isEqualValue($val1, $val2): bool
    {
        if (is_numeric($val1)) {
            $val1 = (string)$val1;
        }

        if (is_numeric($val2)) {
            $val2 = (string)$val2;
        }

        return $val1 === $val2;
    }
}
