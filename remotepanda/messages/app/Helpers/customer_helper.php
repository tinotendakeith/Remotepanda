<?php

use App\Entities\Customer;
use App\Libraries\TwilioMessenger;

function normaliseNumber(string $number): string
{
    // remove invalid characters
    $number = preg_replace("[^\d\+]", "", $number);

    preg_match("/(\+?)([1-9]{0,3})(\d{9})$/", $number, $matches);

    $plus = $matches[1] ?? "";
    $country = $matches[2] ?? "";
    $number = $matches[3] ?? "";

    if ($plus === '') {
        $plus = "+";
    }

    if ($country === '') {
        $country = "263";
    }

    if ($number === '') {
        return "";
    }

    return $plus . $country . $number;
}

function customer_id_offset(int $id): int
{
    return USER_ID_OFFSET + $id;
}

/**
 * @throws Exception
 */
function send_customer_message(Customer $customer, string $message)
{

    helper(["inflector", "option", "content"]);

    $customerId = customer_id_offset($customer->id);

    $error = "";

    $translations = [
        "{{title}}" => $customer->title,
        "{{name}}" => ucwords($customer->name),
        "{{ordinal}}" => ordinalize($customer->currentAge),
        "{{age}}" => $customer->currentAge,
        "{{company}}" => get_option("site-name")->value
    ];

    $messenger = new TwilioMessenger();
    try {
        $messenger->setTo($customer->mobileNumber);
        $messenger->setMethod($customer->{"method"});
        $messenger->setMessage($message, $translations);
        $messenger->send();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }

    $history = insert_content(TYPE_HISTORY, $messenger->getMessage(), $customerId);
    insert_content_meta($history, META_KEY_NUMBER, $messenger->getTo());
    insert_content_meta($history, META_KEY_METHOD, $messenger->getMethod());
    insert_content_meta($history, "error", $error);
}