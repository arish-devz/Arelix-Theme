<?php

if (!function_exists('is_digit')) {
    
    function is_digit(mixed $value): bool
    {
        return !is_bool($value) && ctype_digit(strval($value));
    }
}

if (!function_exists('object_get_strict')) {
    
    function object_get_strict(object $object, ?string $key, $default = null): mixed
    {
        if (is_null($key) || trim($key) == '') {
            return $object;
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_object($object) || !property_exists($object, $segment)) {
                return value($default);
            }

            $object = $object->{$segment};
        }

        return $object;
    }
}
