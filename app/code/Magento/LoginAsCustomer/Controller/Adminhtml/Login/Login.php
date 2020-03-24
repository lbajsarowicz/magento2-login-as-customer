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
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

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
        \Magento\LoginAsCustomer\Model\Config $config,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
    ) {
        parent::__construct($context);
        $this->loginModel = $loginModel;
        $this->authSession = $authSession;
        $this->storeManager = $storeManager;
        $this->url = $url;
        $this->config = $config;
        $this->customerRepository = $customerRepository;
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
            $msg = __(strrev('.remotsuC sA nigoL > snoisnetxE nafegaM > noitarugifnoC > serotS ot etagivan esaelp noisnetxe eht elbane ot ,delbasid si remotsuC sA nigoL nafegaM'));
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

        /* Check if customer's company is active */
        $tmpCustomer = $this->customerRepository->getById($customer->getId());
        if ($tmpCustomer->getExtensionAttributes() !== null) {
            $companyAttributes = null;
            if (method_exists($tmpCustomer->getExtensionAttributes(), 'getCompanyAttributes')) {
                $companyAttributes = $tmpCustomer->getExtensionAttributes()->getCompanyAttributes();
            }

            if ($companyAttributes !== null) {
                $companyId = $companyAttributes->getCompanyId();
                if ($companyId) {
                    try {
                        $company = $this->getCompanyRepository()->get($companyId);
                        if ($company->getStatus() != 1) {
                            $this->messageManager->addErrorMessage(__('You cannot login as customer. Customer\'s company is not active.'));
                            return $resultRedirect->setPath('customer/index/index');
                        }
                    } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {}
                }
            }
        }
        /* End check */

        $user = $this->authSession->getUser();
        $login->generate($user->getId());

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
     * Retrieve Company Repository
     * @return \Magento\Company\Api\CompanyRepositoryInterface
     */
    protected function getCompanyRepository()
    {
        return $this->_objectManager->get(\Magento\Company\Api\CompanyRepositoryInterface::class);
    }
}
