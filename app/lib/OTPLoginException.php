<?php


class OTPLoginException extends Exception
{
    public $guid;
    public $temp_id;

    public function __construct($message = "", $guid, $temp_id)
    {
        $this->temp_id = $temp_id;
        $this->guid = $guid;

        parent::__construct($message);
    }
}