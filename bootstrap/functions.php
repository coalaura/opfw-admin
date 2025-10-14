<?php
function splitAlphaNum($string)
{
    $array = preg_split('/[^a-zA-Z0-9]/', $string, -1, PREG_SPLIT_DELIM_CAPTURE);

    return first($array);
}

function first($array)
{
    if (is_string($array)) {
        $array = json_decode($array, true);
    } else if ($array instanceof stdClass) {
        $array = (array) $array;
    }

    if (!$array || !is_array($array) || empty($array)) {
        return null;
    }

    if (!array_is_list($array)) {
        $keys = array_keys($array);

        if (empty($keys)) {
            return null;
        }

        return $array[$keys[0]];
    }

    return $array[0];
}

function joaat(string $str): int {
    $str = strtolower($str);
    $hash = 0;

    for ($i = 0; $i < strlen($str); $i++) {
        $hash += ord($str[$i]);
        $hash += ($hash << 10);
        $hash &= 0xFFFFFFFF;
        $hash ^= ($hash >> 6);
    }

    $hash += ($hash << 3);
    $hash &= 0xFFFFFFFF;
    $hash ^= ($hash >> 11);
    $hash += ($hash << 15);
    $hash &= 0xFFFFFFFF;

    // Convert to signed int32
    if ($hash > 0x7FFFFFFF) {
        $hash -= 0x100000000;
    }

    return $hash;
}

if (!function_exists('mb_str_pad')) {
    function mb_str_pad($input, $pad_length, $pad_string = ' ', $pad_type = STR_PAD_RIGHT, $encoding = 'UTF-8')
    {
        $input_length      = mb_strlen($input, $encoding);
        $pad_string_length = mb_strlen($pad_string, $encoding);

        if ($pad_length <= 0 || ($pad_length - $input_length) <= 0) {
            return $input;
        }

        $num_pad_chars = $pad_length - $input_length;

        switch ($pad_type) {
            case STR_PAD_RIGHT:
                $left_pad  = 0;
                $right_pad = $num_pad_chars;
                break;

            case STR_PAD_LEFT:
                $left_pad  = $num_pad_chars;
                $right_pad = 0;
                break;

            case STR_PAD_BOTH:
                $left_pad  = floor($num_pad_chars / 2);
                $right_pad = $num_pad_chars - $left_pad;
                break;
        }

        $result = '';
        for ($i = 0; $i < $left_pad; ++$i) {
            $result .= mb_substr($pad_string, $i % $pad_string_length, 1, $encoding);
        }
        $result .= $input;
        for ($i = 0; $i < $right_pad; ++$i) {
            $result .= mb_substr($pad_string, $i % $pad_string_length, 1, $encoding);
        }

        return $result;
    }
}

function fileVersion($file)
{
    $time = filemtime(__DIR__ . "/../" . $file);

    return substr(md5($time), 0, 8);
}

function extraHeader()
{
    $header = env('EXTRA_HEADER', '');

    if (empty($header)) {
        return '';
    }

    return $header;
}

function backWith(string $type, string $message)
{
    session_put('flash_' . $type, $message);

    return back();
}

function redirectWith(string $path, string $type, string $message)
{
    session_put('flash_' . $type, $message);

    return redirect($path);
}

function put_contents(string $filename, mixed $content, int $flags = 0): int | false
{
    $success = file_put_contents($filename, $content, $flags);

    if (!$success) {
        return false;
    }

    // Ensure correct file permissions
    chmod($filename, 0755);
    chown($filename, 'www-data');

    return $success;
}

function op_week_identifier(): int
{
    $weekZero = 1609113600;

    $difference = time() - $weekZero;

    return floor($difference / 604800);
}
