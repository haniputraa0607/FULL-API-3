<?php

namespace App\Lib;

use LaravelFCM\Message\PayloadNotificationBuilder;
use App\Lib\CustomPayloadNotification;

class CustomPayloadNotificationBuilder extends PayloadNotificationBuilder
{
    protected $image;

    public function setImage($image)
    {
        $this->image = $image;
        return $this;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function build()
    {
        return new CustomPayloadNotification($this);
    }

}
