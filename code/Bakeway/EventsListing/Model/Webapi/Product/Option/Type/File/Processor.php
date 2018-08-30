<?php

namespace Bakeway\EventsListing\Model\Webapi\Product\Option\Type\File;

use Magento\Catalog\Model\Webapi\Product\Option\Type\File\Processor as CoreProcessor;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Api\Data\ImageContentInterface;
use Thai\S3\Model\MediaStorage\File\Storage\S3 as ThaiBucketStorage;
use Magento\Framework\Api\ImageProcessor;
use Magento\Framework\Filesystem;

class Processor extends CoreProcessor
{
    /**
     * @var ThaiBucketStorage
     */
    protected $thaiBucketStorage;

    /** @var Filesystem */
    protected $filesystem;

    /** @var ImageProcessor  */
    protected $imageProcessor;
    
    /**
     * Processor constructor.
     * @param ThaiBucketStorage $thaiBucketStorage
     * @param Filesystem $filesystem
     * @param ImageProcessor $imageProcessor
     */
    public function __construct(
        ThaiBucketStorage $thaiBucketStorage,
        Filesystem $filesystem,
        ImageProcessor $imageProcessor
    ) {
        parent::__construct($filesystem, $imageProcessor);
        $this->thaiBucketStorage = $thaiBucketStorage;
    }

    /**
     * @param ImageContentInterface $imageContent
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function processFileContent(ImageContentInterface $imageContent)
    {
        $filePath = $this->saveFile($imageContent);

        $fileAbsolutePath = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath($filePath);
        $fileHash = md5($this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->readFile($filePath));
        $imageSize = getimagesize($fileAbsolutePath);
        $result = [
            'type' => $imageContent->getType(),
            'title' => $imageContent->getName(),
            'fullpath' => $fileAbsolutePath,
            'quote_path' => $filePath,
            'order_path' => $filePath,
            'size' => filesize($fileAbsolutePath),
            'width' => $imageSize ? $imageSize[0] : 0,
            'height' => $imageSize ? $imageSize[1] : 0,
            'secret_key' => substr($fileHash, 0, 20),
        ];
        /** saving image to s3 bucket **/
        $this->thaiBucketStorage->saveFile($filePath);
        return $result;
    }
}