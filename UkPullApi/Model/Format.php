<?php
/**
 *
 * Copyright © 2018 Urjakart, All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Urjakart\UkPullApi\Model;

use \Urjakart\UkPullApi\Api\Data\FormatInterface;

class Format implements FormatInterface
{
    /**
     * Response header
     *
     * @var \Urjakart\UkPullApi\Api\Data\HeaderInterface|null
     */
    public $header;

    /**
     * Response body
     *
     * @var Array
     */
    public $body;

    /**
     * Get header.
     *
     * @return \Urjakart\UkPullApi\Api\Data\HeaderInterface|null
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * Set header.
     *
     * @param \Urjakart\UkPullApi\Api\Data\HeaderInterface $header
     *
     * @return \Urjakart\UkPullApi\Api\Data\FormatInterface
     */
    public function setHeader($header)
    {
        $this->header = $header;
        return $this;
    }

    /**
     * Get body.
     *
     * @return Array
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Set body.
     *
     * @param Array $body
     *
     * @return \Urjakart\UkPullApi\Api\Data\FormatInterface
     */
    public function setBody($body)
    {
        $this->body = $body;
        return $this;
    }
}
