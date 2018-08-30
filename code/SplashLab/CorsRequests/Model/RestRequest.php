<?php

/**
 * @copyright  Copyright 2017 SplashLab
 */

namespace SplashLab\CorsRequests\Model;

class RestRequest extends \Magento\Framework\Webapi\Rest\Request
{
    /**
     * Retrieve accept types understandable by requester in a form of array sorted by quality in descending order.
     *
     * @return string[]
     */
    public function getAcceptTypes()
    {
        $qualityToTypes = [];
        $orderedTypes = [];

        foreach (preg_split('/,\s*/', $this->getHeader('Accept')) as $definition) {
            $typeWithQ = explode(';', $definition);
            $mimeType = trim(array_shift($typeWithQ));

            // check MIME type validity
            if (!preg_match('~^([0-9a-z*+\-]+)(?:/([0-9a-z*+\-\.]+))?$~i', $mimeType)) {
                continue;
            }
            $quality = '1.0';
            // default value for quality

            if ($typeWithQ) {
                $qAndValue = explode('=', $typeWithQ[0]);

                if (2 == count($qAndValue)) {
                    $quality = $qAndValue[1];
                }
            }
            $qualityToTypes[$quality][$mimeType] = true;
        }
        krsort($qualityToTypes);

        foreach ($qualityToTypes as $typeList) {
            $orderedTypes += $typeList;
        }
        $requestUri = $this->getRequestUri();

        $headerValue = $this->getHeader('Content-Type');
        $requestMethod = $this->getHttpMethod();
        if (strtolower($requestMethod) == "get") {
            if (strpos($requestUri, "rest/V1/partners") !== false ||
                strpos($requestUri, "rest/V1/category") !== false
            ) {
                if (!$headerValue) {
                    return array_keys(["application/json" => 1]);
                }
                if (strpos($this->getContentType(), "xml") !== false) {
                    return array_keys(["application/json" => 1]);
                }
            }
        }
        return empty($orderedTypes) ? [self::DEFAULT_ACCEPT] : array_keys($orderedTypes);
    }
}