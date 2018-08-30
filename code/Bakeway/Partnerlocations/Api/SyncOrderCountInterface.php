<?php
/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_Partnerlocations
 * @author    Bakeway
 */

namespace Bakeway\Partnerlocations\Api;

/**
 * SyncOrderCount Interface
 */
interface SyncOrderCountInterface
{
    /**
     * SyncOrder Count
     *
     * @api
     * @return int $orderId
     */
    public function SyncOrderCount();
}
