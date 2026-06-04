<?php

use Config\Services;

helper(["form"]);

$request = Services::request();

$attr = [
   'class'       => 'form-control',
   'placeholder' => 'Search now',
   'onchange'    => 'this.form.submit()',
];

echo form_open(current_url(true)->__toString(), ['method' => 'get'] ) ;
echo form_input('search', $request->getGet('search') ?? '', $attr, 'search') ;
echo form_close();
