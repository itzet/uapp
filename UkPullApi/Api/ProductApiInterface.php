<?php
/**
 *
 * Copyright © 2018 Urjakart, All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Urjakart\UkPullApi\Api;

/**
 * @api
 */
interface ProductApiInterface
{
    /**
     * Returns list of product data with header detail.
     *
     * @param int $fromDate from date timestamp.
     * @param int $toDate till date timestamp.
     * @param int $offset product per page.
     * @param int $page page number.
     * @return \Urjakart\UkPullApi\Api\Data\FormatInterface
     */
    public function getProductData($fromDate, $toDate, $offset, $page);
}
