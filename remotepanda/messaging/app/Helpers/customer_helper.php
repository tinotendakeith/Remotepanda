<?php

function normaliseNumber(string $number): string{
    preg_match("/(\+?)([1-9]{0,3})(\d{9})$/", $number, $matches);

    $plus = $matches[1];
    $country = $matches[2];
    $number = $matches[3];

    if ($plus === ''){
        $plus = "+";
    }

    if ($country === ''){
        $country = "263";
    }

    if ($number === ''){
        return "";
    }

    return $plus . $country . $number;
}