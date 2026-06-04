<?php

use Config\Services;

helper(["form"]);

$request = Services::request();

$attr = [
    'class' => 'form-control py-3 bg-light',
    'onchange' => 'this.form.submit()',
];

$options = [
    "" => "Filter By Subscription",
    "yes" => "Subscribed",
    "no" => "UnSubscribed"
];

echo form_open(current_url(true)->__toString(), ['method' => 'GET']);
echo form_dropdown('subscription', $options, [$request->getGet('subscription') ?? ''], $attr);
echo form_close();