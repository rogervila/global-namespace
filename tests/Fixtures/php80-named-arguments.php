<?php

/*
 * Requires PHP 8.0+ (named arguments). Tests load this file with
 * require_once only after checking the PHP version, because older
 * versions cannot parse it.
 */

namespace Tests\Fixtures\Php80;

use PHP\IgnoreAlways;
use PHP\IgnoreMissing;
use PHP\PHP;
use Tests\Fixtures\RecordingPHP;

function pad_with_php(): mixed
{
    return PHP::str_pad(string: '7', length: 3, pad_string: '0', pad_type: STR_PAD_LEFT);
}

function encode_with_positional_and_named_arguments(): mixed
{
    return PHP::json_encode(['path' => '/home'], flags: JSON_UNESCAPED_SLASHES);
}

function call_with_unknown_named_argument(): mixed
{
    return PHP::strlen(text: 'abc');
}

function pad_with_ignore_missing(): mixed
{
    return IgnoreMissing::str_pad(string: '7', length: 3, pad_string: '0', pad_type: STR_PAD_LEFT);
}

function call_missing_function_with_ignore_missing(): mixed
{
    return IgnoreMissing::global_namespace_missing_function(value: 1);
}

function pad_with_ignore_always(): mixed
{
    return IgnoreAlways::str_pad(string: '7', length: 3, pad_string: '0', pad_type: STR_PAD_LEFT);
}

function record_named_arguments(): mixed
{
    return RecordingPHP::str_pad('7', length: 3, pad_string: '0');
}
