<?php

namespace App\Libraries;

use Exception;
use Twilio\Exceptions\ConfigurationException;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

class TwilioMessenger
{
    protected string $method = METHOD_MESSAGE;
    protected string $sid = "";
    protected string $token = "";
    protected string $from = "";
    protected string $message = "";
    protected string $to = "";

    public function __construct()
    {
        $this->setSid(getenv(Client::ENV_ACCOUNT_SID));
        $this->setToken(getenv(Client::ENV_AUTH_TOKEN));
        $this->setFrom(getenv("TWILIO_FROM_NUMBER"));
    }

    /**
     * @param string $sid
     * @return TwilioMessenger
     */
    public function setSid(string $sid): self
    {
        $this->sid = $sid;

        return $this;
    }

    /**
     * @param string $token
     * @return TwilioMessenger
     */
    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @param string $from
     * @return TwilioMessenger
     */
    public function setFrom(string $from): self
    {
        $this->from = $from;

        return $this;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    public function setMethod(string $method): self
    {
        $this->method = $method;

        return $this;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage(string $message, array $translations = []): self
    {
        $this->message = strtr($message, $translations);

        return $this;
    }

    /**
     * @return string
     */
    public function getTo(): string
    {
        return $this->to;
    }

    /**
     * @param string $to
     * @return TwilioMessenger
     * @throws Exception
     */
    public function setTo(string $to): self
    {
        helper(["option"]);

        $option = get_option("test-number");

        if (!get_option("enabled")->value && ($option !== false && $option->value !== "")) {
            $number = $option->value;
        } else {
            $number = $to;
        }

        $number = normaliseNumber($number);

        if (empty($number)) {
            throw new Exception("Invalid target number.");
        }

        $this->to = $number;

        return $this;
    }

    /**
     * @throws ConfigurationException
     * @throws TwilioException
     */
    public function send()
    {

        if (empty($this->to)) {
            throw new ConfigurationException("The number to send message to is required");
        }

        $client = new Client($this->sid, $this->token);

        $prefix = "";
        if ($this->method === METHOD_WHATSAPP) {
            $prefix = "whatsapp:";
        }

        $client->messages->create(
            $prefix . $this->to,
            [
                'from' => $prefix . $this->from,
                'body' => $this->cleanMessage($this->message)
            ]
        );

    }

    protected function cleanMessage(string $message): string
    {

        $tags = [];
        if ($this->method === METHOD_WHATSAPP) {
            $tags = ["em", "strong", "s", "pre"];
        }

        $message = strip_tags($message, $tags);

        $message = preg_replace("/<em\s(?:.+?)>(.+?)<\/em>/is", "_$1_", $message);
        $message = preg_replace("/<strong\s(?:.+?)>(.+?)<\/strong>/is", "*$1*", $message);
        $message = preg_replace("/<s\s(?:.+?)>(.+?)<\/s>/is", "~$1~", $message);
        return preg_replace("/<pre\s(?:.+?)>(.+?)<\/pre>/is", "```$1```", $message);

    }

}