<?php
/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_PartnerWebapi
 * @author    Bakeway
 */

namespace Bakeway\PartnerWebapi\Api;

interface GlobalSearchInterface
{
    /**
     * Get Global Autocomplete List.
     *
     * @api
     * @param string|null $searchterm
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getGlobalSearchAutoCompleteList($searchterm = null);
}