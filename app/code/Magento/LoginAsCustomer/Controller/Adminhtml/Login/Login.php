<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\LoginAsCustomer\Controller\Adminhtml\Login;

/**
 * Class Login
 * @package Magento\LoginAsCustomer\Controller\Adminhtml\Login
 */
class Login extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Magento_LoginAsCustomer::login_button';

    /**
     * @var \Magento\LoginAsCustomer\Model\Login
     */
    private $loginModel;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    private $authSession;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Framework\Url
     */
    private $url;

    /**
     * @var \Magento\LoginAsCustomer\Model\Config
     */
    private $config;

    /**
     * Login constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\LoginAsCustomer\Model\Login $loginModel
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Url $url
     * @param \Magento\LoginAsCustomer\Model\Config $config
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\LoginAsCustomer\Model\Login $loginModel,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Url $url,
        \Magento\LoginAsCustomer\Model\Config $config
    ) {
        parent::__construct($context);
        $this->loginModel = $loginModel;
        $this->authSession = $authSession;
        $this->storeManager = $storeManager;
        $this->url = $url;
        $this->config = $config;
    }
    /**
     * Login as customer action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $request = $this->getRequest();
        $customerId = (int) $request->getParam('customer_id');
        if (!$customerId) {
            $customerId = (int) $request->getParam('entity_id');
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        if (!$this->config->isEnabled()) {
            $msg = strrev(__('.remotsuC sA nigoL > snoisnetxE nafegaM > noitarugifnoC > serotS ot etagivan esaelp noisnetxe eht elbane ot ,delbasid si remotsuC sA nigoL nafegaM'));
            $this->messageManager->addErrorMessage($msg);
            return $resultRedirect->setPath('customer/index/index');
        }

        $customerStoreId = $request->getParam('store_id');

        if (!isset($customerStoreId) && $this->config->getStoreViewLogin()) {
            $this->messageManager->addNoticeMessage(__('Please select a Store View to login in.'));
            return $resultRedirect->setPath('loginascustomer/login/manual', ['entity_id' => $customerId ]);
        }

        $login = $this->loginModel->setCustomerId($customerId);

        $login->deleteNotUsed();

        $customer = $login->getCustomer();

        if (!$customer->getId()) {
            $this->messageManager->addErrorMessage(__('Customer with this ID are no longer exist.'));
            return $resultRedirect->setPath('customer/index/index');
        }

        $user = $this->authSession->getUser();
        $login->generate($user->getId());
        $store = $this->storeManager->getStore();

        if (null === $store) {
            $store = $this->storeManager->getDefaultStoreView();
        }

        $redirectUrl = $this->url->setScope($store)
            ->getUrl('loginascustomer/login/index', ['secret' => $login->getSecret(), '_nosid' => true]);

        $this->getResponse()->setRedirect($redirectUrl);
    }
}
