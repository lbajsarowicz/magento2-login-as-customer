<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\LoginAsCustomer\Api;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Interface providing customer token generation for admin.
 *
 * @api
 */
interface LoginAsCustomerTokenServiceInterface
{
    /**
     * Create access token for admin given the customer name.
     *
     * Returns created token.
     *
     * @param string $username
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function createCustomerAccessToken(string $username): string;

    /**
     * Revoke token by customer id.
     *
     * Returns true if token successfully revoked.
     *
     * @param int $userId
     * @return void
     * @throws LocalizedException
     */
    public function revokeCustomerAccessToken(int $userId): void;
}
