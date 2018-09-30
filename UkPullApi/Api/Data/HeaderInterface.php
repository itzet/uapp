<?php
/**
 *
 * Copyright © 2018 Urjakart, All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Urjakart\UkPullApi\Api\Data;

/**
 * @api
 */
interface HeaderInterface
{

    /**#@+
    * Constants defined for keys of data array
    */
    const CODE = 'code';

    const STATUS = 'status';

    const MESSAGE = 'message';
    /**#@-*/

    /**
     * Get code.
     *
     * @return string|null
     */
    public function getCode();

    /**
     * Set code.
     *
     * @param string $code
     *
     * @return \Urjakart\UkPullApi\Api\Data\HeaderInterface
     */
    public function setCode($code);

    /**
     * Get status.
     *
     * @return string|null
     */
    public function getStatus();

    /**
     * Set status.
     *
     * @param string $status
     *
     * @return \Urjakart\UkPullApi\Api\Data\HeaderInterface
     */
    public function setStatus($status);

    /**
     * Get message.
     *
     * @return string|null
     */
    public function getMessage();

    /**
     * Set message.
     *
     * @param string $message
     *
     * @return \Urjakart\UkPullApi\Api\Data\HeaderInterface
     */
    public function setMessage($message);
}
