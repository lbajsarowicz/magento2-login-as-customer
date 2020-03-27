<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\LoginAsCustomer\Plugin;

use Magento\Customer\Model\Session;
use Magento\Framework\App\ActionInterface;
use Magento\LoginAsCustomer\Model\ResourceModel\CheckLoginAsCustomerAdminSessionIsActive;
use Magento\Security\Model\AdminSessionInfoFactory;

/**
 * Invalidate expired and not active login-as-customer sessions.
 */
class InvalidateExpiredSessionPlugin
{
    /**
     * @var Session
     */
    private $session;

    /**
     * @var CheckLoginAsCustomerAdminSessionIsActive
     */
    private $checkLoginAsCustomerSessionIsActive;

    /**
     * @var AdminSessionInfoFactory
     */
    private $adminSessionInfoFactory;

    /**
     * @param Session $session
     * @param AdminSessionInfoFactory $adminSessionInfoFactory
     * @param CheckLoginAsCustomerAdminSessionIsActive $checkLoginAsCustomerSessionIsActive
     */
    public function __construct(
        Session $session,
        AdminSessionInfoFactory $adminSessionInfoFactory,
        CheckLoginAsCustomerAdminSessionIsActive $checkLoginAsCustomerSessionIsActive
    ) {
        $this->session = $session;
        $this->adminSessionInfoFactory = $adminSessionInfoFactory;
        $this->checkLoginAsCustomerSessionIsActive = $checkLoginAsCustomerSessionIsActive;
    }

    /**
     * Invalidate expired and not active login-as-customer sessions.
     *
     * @param ActionInterface $subject
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function beforeExecute(ActionInterface $subject)
    {
        $adminSessionId = $this->session->getAdminUserSessionId();
        $customerId = $this->session->getCustomerId();
        if ($adminSessionId && $customerId) {
            if (!$this->checkAdminSessionActive($adminSessionId) ||
                !$this->checkLoginAsCustomerSessionIsActive->execute($customerId, $adminSessionId)
            ) {
                $this->session->destroy();
            }
        }
    }

    /**
     * Check logged-as-customer admin session is still active.
     *
     * @param string $adminSessionId
     * @return bool
     */
    private function checkAdminSessionActive(string $adminSessionId): bool
    {
        $adminSessionInfo = $this->adminSessionInfoFactory->create();
        $adminSessionInfo->load($adminSessionId, 'session_id');

        return $adminSessionInfo->getStatus() && !$adminSessionInfo->isSessionExpired();
    }
}
