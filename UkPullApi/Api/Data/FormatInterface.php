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
interface FormatInterface
{

    /**#@+
    * Constants defined for keys of data array
    */
    const HEADER = 'header';

    const BODY = 'body';
    /**#@-*/

    /**
     * Get header.
     *
     * @return \Urjakart\UkPullApi\Api\Data\HeaderInterface|null
     */
    public function getHeader();

    /**
     * Set header.
     *
     * @param \Urjakart\UkPullApi\Api\Data\HeaderInterface $header
     *
     * @return \Urjakart\UkPullApi\Api\Data\FormatInterface
     */
    public function setHeader($header);

    /**
     * Get body.
     *
     * @return string|null
     */
    public function getBody();

    /**
     * Set body.
     *
     * @param string $body
     *
     * @return \Urjakart\UkPullApi\Api\Data\FormatInterface
     */
    public function setBody($body);
}
