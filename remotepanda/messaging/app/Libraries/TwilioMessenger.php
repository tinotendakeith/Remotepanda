<?php

namespace App\Libraries;

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
     * @param string $message
     */
    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @param string $to
     * @return TwilioMessenger
     */
    public function setTo(string $to): self
    {
        $this->to = $to;

        return $this;
    }

    public function setMethod(string $method): self
    {
        $this->method = $method;

        return $this;
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

}