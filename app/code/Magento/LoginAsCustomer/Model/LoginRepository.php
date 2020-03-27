<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\LoginAsCustomer\Model;

/**
 * Login repository class.
 */
class LoginRepository
{
    /**
     * @var ResourceModel\Login
     */
    private $loginResourceModel;

    /**
     * @param ResourceModel\Login $loginResourceModel
     */
    public function __construct(
        ResourceModel\Login $loginResourceModel
    ) {
        $this->loginResourceModel = $loginResourceModel;
    }

    /**
     * Save Login.
     *
     * @param Login $login
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function save(Login $login): void
    {
        $this->loginResourceModel->save($login);
    }
}
