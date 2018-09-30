<?php
namespace Urjakart\OrderComment\Controller\Adminhtml\Order;

use Magento\Framework\App\ObjectManager;

class AddComment extends \Magento\Sales\Controller\Adminhtml\Order\AddComment
{
    /**
     * Modify order comment action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $objectManager = ObjectManager::getInstance();
        $authSession = $objectManager->get('Magento\Backend\Model\Auth\Session');
        $data = $this->getRequest()->getPost('history');
        if ($authSession->getUser()) {
            $name = $authSession->getUser()->getName();
            $user = '<b> By ' . $name . '</b>';
            $data['comment'] = '| ' . $data['comment'] . $user;
        }
        $this->getRequest()->setPostValue('history', $data);

        return parent::execute();
    }
}
