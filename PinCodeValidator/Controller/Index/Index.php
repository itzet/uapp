<?php
/**
 *
 * Copyright © Urjakart. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Urjakart\PinCodeValidator\Controller\Index;

class Index extends \Magento\Checkout\Controller\Index\Index
{
    /**
     * Checkout page
     * check cod available form backend
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $quote = $this->getOnepage()->getQuote();
        $codAttribute = [];
        $skus = '';
        foreach ($quote->getAllItems() as $item) {
            $productId = $item->getProductId();
            $product = $this->_objectManager->get('Magento\Catalog\Model\Product')->load($productId);
            $codAttribute[] = strtolower($product->getAttributeText('cod_available'));
            if (strtolower($product->getAttributeText('cod_available')) === 'no')
                $skus .= '`' . $product->getName() . '`, ';
        }
        $skus = rtrim($skus, ', ');
        if(in_array('no', $codAttribute)) {
            echo '<script>window.isBackendCodAvailable = "no";window.CodNoSku= "' . $skus . '"</script>';
        } else {
            echo '<script>window.isBackendCodAvailable = "yes"</script>';
        }

        return parent::execute();
    }
}
