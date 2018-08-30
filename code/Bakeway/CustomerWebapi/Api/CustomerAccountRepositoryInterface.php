<?php
/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_CustomerWebapi
 * @author    Bakeway
 */

namespace Bakeway\CustomerWebapi\Api;

interface CustomerAccountRepositoryInterface
{
    /**
     * @api
     * @param mixed $data
     * @return mixed
     */
    public function socialLogin($data);

    /**
     * @api
     * @param string $token
     * @return bool
     * @throws LocalizedException
     */
    public function logout($token);
}