<?php
/**
 * Copyright © Urjakart. All rights reserved.
 */
namespace Urjakart\Checkout\Block;

use Magento\Checkout\Helper\Data;
use Magento\Framework\App\ObjectManager;

class Billingposition extends \Magento\Framework\View\Element\Template
{

    /**
     * @var Data
     */
    private $checkoutDataHelper;

    /**
     * @var Data
     */
    private $url;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        $objectManager = ObjectManager::getInstance();
        $this->url = $objectManager->get('Magento\Framework\Url');
        parent::__construct($context, $data);
    }

    /**
     * Get checkout data helper instance
     *
     * @return Data
     * @deprecated
     */
    private function getCheckoutDataHelper()
    {
        if (!$this->checkoutDataHelper) {
            $this->checkoutDataHelper =
                ObjectManager::getInstance()->get(Data::class);
        }

        return $this->checkoutDataHelper;
    }

    /**
     * Get config data for billing address position
     *
     * @return boolean
     */
    public function isBillingInPaymentMethod() {

        $data = [
            'isBillingInPaymentMethod' => $this->getCheckoutDataHelper()->isDisplayBillingOnPaymentMethodAvailable()
        ];

        return \Zend_Json::encode($data);
    }
}