<?php
namespace Urjakart\Mixpanel\Controller\Standard;

class CartDetailData extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $_cart;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Checkout\Model\Cart $cart
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->_cart = $cart;
        parent::__construct($context);
    }

    public function execute() {

        $params = [
            'skulist' => [],
            'qtys' => 0,
            'cartTotal' => 0,
            'sku' => '',
            'qty' => 0,
            'price' => 0
        ];

        $option = $this->getRequest()->getParam('super_attribute');
        $productId = $this->getRequest()->getParam('product');
        if ($option) {
            $option = array_values($option);
            $option = !empty($option[0]) ? $option[0] : null;
        }
        $params['qty'] = $this->getRequest()->getParam('qty');
        $items = $this->_cart->getItems();
        foreach($items as $item) {
            if (!$item->getParentItemId()) {
                array_push($params['skulist'], $item->getSku());
                $params['qtys'] += $item->getQty();
                if ($option) {
                    $opt = $item->getOptions();
                    $opt = $opt[0] instanceof \Magento\Quote\Model\Quote\Item\Option ? $opt[0] : null;
                    if ($opt) {
                        $optData = @unserialize($opt->getValue());
                    }
                    $optData = !empty($optData['super_attribute']) ? $optData['super_attribute'] : [];
                    $optData = array_values($optData);
                    $optData = !empty($optData[0]) ? $optData[0] : null;
                    if ($optData == $option) {
                        $params['sku'] = $item->getSku();
                    }
                } else {
                    if ($item->getProduct()->getId() == $productId) {
                        $params['sku'] = $item->getSku();
                        $params['name'] = $item->getName();
                        $params['price'] = $item->getProduct()->getPriceModel()->getFinalPrice($params['qty'], $item->getProduct()) * (int)$params['qty'];
                    }
                }
            } else {
                if ($option) {
                    $opt = $item->getOptions();
                    $opt = $opt[0] instanceof \Magento\Quote\Model\Quote\Item\Option ? $opt[0] : null;
                    if ($opt) {
                        $optData = @unserialize($opt->getValue());
                    }
                    $optData = !empty($optData['super_attribute']) ? $optData['super_attribute'] : [];
                    $optData = array_values($optData);
                    $optData = !empty($optData[0]) ? $optData[0] : null;
                    if ($optData == $option) {
                        $params['name'] = $item->getName();
                        $params['price'] = $item->getProduct()->getPriceModel()->getFinalPrice($params['qty'], $item->getProduct()) * (int)$params['qty'];
                    }
                }
            }
        }
        $params['cartTotal'] += (int)$this->_cart->getQuote()->getGrandTotal();

        return $this->resultJsonFactory->create()->setData($params);
    }
}
