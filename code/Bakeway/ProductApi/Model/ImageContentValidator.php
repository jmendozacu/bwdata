<?php

namespace Bakeway\ProductApi\Model;

use Magento\Framework\Api\Data\ImageContentInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Phrase;

class ImageContentValidator extends \Magento\Framework\Api\ImageContentValidator {
    /**
     * Check if gallery entry content is valid
     *
     * @param ImageContentInterface $imageContent
     * @return bool
     * @throws InputException
     */
    public function isValid(ImageContentInterface $imageContent)
    {
        $fileContent = @base64_decode($imageContent->getBase64EncodedData(), true);
        if (empty($fileContent)) {
            throw new InputException(new Phrase('The image content must be valid base64 encoded data.'));
        }
        $imageProperties = @getimagesizefromstring($fileContent);
        if (empty($imageProperties)) {
            throw new InputException(new Phrase('The image content must be valid base64 encoded data.'));
        }
        $sourceMimeType = $imageProperties['mime'];
        if ($sourceMimeType != $imageContent->getType() || !$this->isMimeTypeValid($sourceMimeType)) {
            throw new InputException(new Phrase('Image not uploaded or Image type not supported.'));
        }
        if (!$this->isNameValid($imageContent->getName())) {
            throw new InputException(new Phrase('Provided image name contains forbidden characters.'));
        }
        return true;
    }
}
