<?php
/**
 *
 * Copyright © 2018 Urjakart, All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Urjakart\UkPullApi\Model;

use \Urjakart\UkPullApi\Api\Data\HeaderInterface;

class Header  implements HeaderInterface
{
    /**
     * Response code
     *
     * @var String
     */
    public $code;

    /**
     * Response status
     *
     * @var String
     */
    public $status;

    /**
     * Response message
     *
     * @var String
     */
    public $message;

    /**
     * Get code.
     *
     * @return string|null
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set code.
     *
     * @param string $code
     *
     * @return \Urjakart\UkPullApi\Api\Data\HeaderInterface
     */
    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }

    /**
     * Get status.
     *
     * @return string|null
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set status.
     *
     * @param string $status
     *
     * @return \Urjakart\UkPullApi\Api\Data\HeaderInterface
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Get message.
     *
     * @return string|null
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set message.
     *
     * @param string $message
     *
     * @return \Urjakart\UkPullApi\Api\Data\HeaderInterface
     */
    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }
}
