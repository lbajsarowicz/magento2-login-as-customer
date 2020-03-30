<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\LoginAsCustomer\Model;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Integration\Model\Oauth\TokenFactory as TokenModelFactory;
use Magento\Integration\Model\ResourceModel\Oauth\Token\CollectionFactory as TokenCollectionFactory;
use Magento\LoginAsCustomer\Api\LoginAsCustomerTokenServiceInterface;
use Magento\LoginAsCustomer\Model\ResourceModel\RevokeLoginAsCustomerTokens;

/**
 * @inheritdoc
 */
class LoginAsCustomerTokenService implements LoginAsCustomerTokenServiceInterface
{
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var RevokeLoginAsCustomerTokens
     */
    private $revokeLoginAsCustomerTokens;

    /**
     * @var TokenModelFactory
     */
    private $tokenModelFactory;

    /**
     * @var TokenCollectionFactory
     */
    private $tokenModelCollectionFactory;

    /**
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param RevokeLoginAsCustomerTokens $revokeLoginAsCustomerTokens
     * @param TokenCollectionFactory $tokenModelCollectionFactory
     * @param TokenModelFactory $tokenModelFactory
     * @param UserContextInterface $userContext
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        RevokeLoginAsCustomerTokens $revokeLoginAsCustomerTokens,
        TokenCollectionFactory $tokenModelCollectionFactory,
        TokenModelFactory $tokenModelFactory,
        UserContextInterface $userContext
    ) {
        $this->customerRepository = $customerRepository;
        $this->revokeLoginAsCustomerTokens = $revokeLoginAsCustomerTokens;
        $this->tokenModelCollectionFactory = $tokenModelCollectionFactory;
        $this->tokenModelFactory = $tokenModelFactory;
        $this->userContext = $userContext;
    }

    /**
     * @inheritdoc
     */
    public function createCustomerAccessToken(string $username): string
    {
        $customer = $this->customerRepository->get($username);
        $customerId = (int)$customer->getId();
        $this->revokeCustomerAccessTokens($customerId);
        $integrationId = (int)$this->userContext->getUserId();
        $oauthToken = $this->tokenModelFactory->create();
        if ($this->userContext->getUserType() === UserContextInterface::USER_TYPE_ADMIN) {
            $oauthToken->setAdminId($integrationId);
        } elseif ($this->userContext->getUserType() === UserContextInterface::USER_TYPE_INTEGRATION) {
            $oauthToken->setConsumerId($integrationId);
        } else {
            throw new LocalizedException(__('Neither integration, nor admin token detected.'));
        }
        $oauthToken->createCustomerToken($customerId);

        return $oauthToken->getToken();
    }

    /**
     * @inheritdoc
     */
    public function revokeCustomerAccessToken(int $userId): void
    {
        $this->revokeCustomerAccessTokens($userId);
    }

    /**
     * Revokes all existing LoginAsCustomer tokens by customer id and admin id or consumer id.
     *
     * @param int $customerId
     * @throws LocalizedException
     */
    private function revokeCustomerAccessTokens(int $customerId): void
    {
        if ($this->userContext->getUserType() === UserContextInterface::USER_TYPE_ADMIN) {
            $integrationTypeId = 'admin_id';
        } elseif ($this->userContext->getUserType() === UserContextInterface::USER_TYPE_INTEGRATION) {
            $integrationTypeId = 'consumer_id';
        } else {
            throw new LocalizedException(__('Neither integration, nor admin token detected.'));
        }

        $integrationId = (int)$this->userContext->getUserId();
        $this->revokeLoginAsCustomerTokens->execute($customerId, $integrationId, $integrationTypeId);
    }
}
