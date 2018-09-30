<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Urjakart\PinCodeValidator\Block\Order\Invoice;

class EmailCodFee extends \Magento\Framework\View\Element\AbstractBlock
{
    public function initTotals()
    {
        $orderTotalsBlock = $this->getParentBlock();
        $invoice = $orderTotalsBlock->getInvoice();
        if ((int)$invoice->getCodFee() > 0) {
            $orderTotalsBlock->addTotal(new \Magento\Framework\DataObject([
                'code'       => 'cod_fee',
                'label'      => __('COD Fee'),
                'value'      => $invoice->getCodFee(),
                'base_value' => $invoice->getCodFee(),
            ]), 'shipping');
        }
    }
}