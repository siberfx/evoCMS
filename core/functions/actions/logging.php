<?php

if (!function_exists('array_unique_multi')) {
    /**
     * @param array $array
     * @param string $checkKey
     *
     * @return array
     */
    function array_unique_multi(array $array, string $checkKey): array
    {
        // Use the builtin if we're not a multidimensional array
        if (!is_array(current($array)) || empty($checkKey)) {
            return array_unique($array);
        }

        $ret = [];
        $checkValues = []; // contains the unique key Values
        foreach ($array as $key => $current) {
            if (in_array($current[$checkKey], $checkValues)) {
                continue;
            } // duplicate

            $checkValues[] = $current[$checkKey];
            $ret[$key] = $current;
        }

        return $ret;
    }
}

if (!function_exists('record_sort')) {
    /**
     * @param array $array
     * @param string $key
     *
     * @return array
     */
    function record_sort(array $array, string $key): array
    {
        $hash = [];
        foreach ($array as $k => $v) {
            $hash[$k] = $v[$key];
        }

        natsort($hash);

        $records = [];
        foreach ($hash as $k => $row) {
            $records[$k] = $array[$k];
        }

        return $records;
    }
}
