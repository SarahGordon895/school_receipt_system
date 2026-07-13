<?php

if (! function_exists('format_tzs')) {
    function format_tzs(int|float|null $amount): string
    {
        return number_format((int) round((float) ($amount ?? 0)));
    }
}
