<?php

use Config\Services;

helper(["form"]);

$request = Services::request();

$attr = [
    'class' => 'form-control py-3 bg-light',
    'onchange' => 'this.form.submit()',
];

$options = [
    "" => "Filter By Method",
    "message" => "Message",
    "whatsapp" => "Whatsapp"
];

echo form_open(current_url(true)->__toString(), ['method' => 'GET']);
echo form_dropdown('method', $options, [$request->getGet('method') ?? ''], $attr);
echo form_close();