<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Tax totals modification block. Can be used just as subblock of \Magento\Sales\Block\Order\Totals
 */
namespace Urjakart\PinCodeValidator\Block\Adminhtml\Sales\Order\Invoice;



class CodFee extends \Magento\Framework\View\Element\Template
{
    protected $_config;
    protected $_order;
    protected $_source;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Tax\Model\Config $taxConfig,
        array $data = []
    ) {
        $this->_config = $taxConfig;
        parent::__construct($context, $data);
    }

    public function displayFullSummary()
    {
        return true;
    }

    public function getSource()
    {
        return $this->_source;
    }
    public function getStore()
    {
        return $this->_order->getStore();
    }
    public function getOrder()
    {
        return $this->_order;
    }
    public function getLabelProperties()
    {
        return $this->getParentBlock()->getLabelProperties();
    }

    public function getValueProperties()
    {
        return $this->getParentBlock()->getValueProperties();
    }

    public function initTotals()
    {
        $parent = $this->getParentBlock();
        $this->_order = $parent->getOrder();
        $this->_source = $parent->getSource();
        $codFee = $this->_order->getCodFee();

        if ((int)$codFee > 0) {
            $fee = new \Magento\Framework\DataObject(
                [
                    'code' => 'cod_fee',
                    'strong' => false,
                    'value' => $codFee,
                    'base_value' => $codFee,
                    'label' => __('COD Fee'),
                ]
            );
            $parent->addTotal($fee, 'cod_fee');
        }

        return $this;
    }
}