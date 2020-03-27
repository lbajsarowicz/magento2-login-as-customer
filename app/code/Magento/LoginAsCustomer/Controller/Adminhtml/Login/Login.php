<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\LoginAsCustomer\Controller\Adminhtml\Login;

use Magento\LoginAsCustomer\Model\Login as LoginModel;
use Magento\LoginAsCustomer\Model\ResourceModel\DeactivateLoginAsCustomerSession;
use Magento\LoginAsCustomer\Model\ResourceModel\DeleteNotUsedLoggedAsCustomerSessions;

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
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    private $dateTime;

    /**
     * @var \Magento\Framework\Math\Random
     */
    private $random;

    /**
     * @var \Magento\LoginAsCustomer\Model\LoginFactory
     */
    private $loginFactory;

    /**
     * @var \Magento\LoginAsCustomer\Model\LoginRepository
     */
    private $loginRepository;

    /**
     * @var DeactivateLoginAsCustomerSession
     */
    private $deactivateLoginAsCustomerSession;

    /**
     * @var DeleteNotUsedLoggedAsCustomerSessions
     */
    private $deleteNotUsedLoggedAsCustomerSessions;

    /**
     * Login constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Url $url
     * @param \Magento\LoginAsCustomer\Model\Config $config
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\Math\Random $random
     * @param \Magento\LoginAsCustomer\Model\LoginFactory $loginFactory
     * @param \Magento\LoginAsCustomer\Model\LoginRepository $loginRepository
     * @param DeactivateLoginAsCustomerSession $deactivateLoginAsCustomerSession
     * @param DeleteNotUsedLoggedAsCustomerSessions $deleteNotUsedLoggedAsCustomerSessions
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Url $url,
        \Magento\LoginAsCustomer\Model\Config $config,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Math\Random $random,
        \Magento\LoginAsCustomer\Model\LoginFactory $loginFactory,
        \Magento\LoginAsCustomer\Model\LoginRepository $loginRepository,
        DeactivateLoginAsCustomerSession $deactivateLoginAsCustomerSession,
        DeleteNotUsedLoggedAsCustomerSessions $deleteNotUsedLoggedAsCustomerSessions
    ) {
        parent::__construct($context);
        $this->authSession = $authSession;
        $this->storeManager = $storeManager;
        $this->url = $url;
        $this->config = $config;
        $this->dateTime = $dateTime;
        $this->random = $random;
        $this->loginFactory = $loginFactory;
        $this->loginRepository = $loginRepository;
        $this->deactivateLoginAsCustomerSession = $deactivateLoginAsCustomerSession;
        $this->deleteNotUsedLoggedAsCustomerSessions = $deleteNotUsedLoggedAsCustomerSessions;
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
            // TODO: remove strrev
            $msg = strrev(__('.remotsuC sA nigoL > snoisnetxE nafegaM > noitarugifnoC > serotS ot etagivan esaelp noisnetxe eht elbane ot ,delbasid si remotsuC sA nigoL nafegaM'));
            $this->messageManager->addErrorMessage($msg);
            return $resultRedirect->setPath('customer/index/index');
        }

        $customerStoreId = $request->getParam('store_id');

        if (!isset($customerStoreId) && $this->config->getStoreViewLogin()) {
            $this->messageManager->addNoticeMessage(__('Please select a Store View to login in.'));
            return $resultRedirect->setPath('loginascustomer/login/manual', ['entity_id' => $customerId ]);
        }

        $this->deleteNotUsedLoggedAsCustomerSessions->execute();
        $login = $this->generateLoginModel($customerId);

        $customer = $login->getCustomer();

        if (!$customer->getId()) {
            $this->messageManager->addErrorMessage(__('Customer with this ID are no longer exist.'));
            return $resultRedirect->setPath('customer/index/index');
        }

        if (!$customerStoreId) {
            $customerStoreId = $this->getCustomerStoreId($customer);
        }

        if ($customerStoreId) {
            $store = $this->storeManager->getStore($customerStoreId);
        } else {
            $store = $this->storeManager->getDefaultStoreView();
        }

        $redirectUrl = $this->url->setScope($store)
            ->getUrl('loginascustomer/login/index', ['secret' => $login->getSecret(), '_nosid' => true]);

        $this->getResponse()->setRedirect($redirectUrl);
    }

    /**
     * We're not using the $customer->getStoreId() method due to a bug where it returns the store for the customer's website
     * @param $customer
     * @return int
     */
    public function getCustomerStoreId(\Magento\Customer\Model\Customer $customer): int
    {
        return (int)$customer->getData('store_id');
    }


    /**
     * Generate Login model.
     *
     * @param int $customerId
     * @return LoginModel
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function generateLoginModel(int $customerId): LoginModel
    {
        $user = $this->authSession->getUser();
        $this->deactivateLoginAsCustomerSession->execute((int)$user->getId());

        $login = $this->loginFactory->create();
        $login->setData(
            [
                'customer_id' => $customerId,
                'admin_id' => $user->getId(),
                'admin_session_id' => $this->authSession->getSessionId(),
                'secret' => $this->random->getRandomString(64),
                'used' => 0,
                'created_at' => $this->dateTime->gmtTimestamp(),
                'is_active' => 1
            ]
        );

        $this->loginRepository->save($login);

        return $login;
    }
}
