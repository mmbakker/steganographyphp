<?php

namespace MichielBakker\Steganography;

class Image
{
    /**
     * Location of the file.
     *
     * @var string
     */
    private $imageFile;

    /**
     * The type of the image (GIF, JPEG, PNG, etc). This property
     * contains one of PHP's native IMAGETYPE_* constants.
     *
     * @see http://php.net/manual/en/function.exif-imagetype.php#refsect1-function.exif-imagetype-constants
     *
     * @var int
     */
    private $imageType;

    /**
     * Image resource.
     *
     * @var resource
     */
    protected $imageResource;

    /**
     * The size of the file in bytes.
     *
     * @var int
     */
    private $fileSize;

    public function __construct($file)
    {
        $this->imageFile = $this->copyToTemp($file);
        $this->imageType = exif_imagetype($this->imageFile);

        $this->initResource();
    }

    public function __destruct()
    {
        if (is_resource($this->imageResource)) {
            imagedestroy($this->imageResource);
        }
    }

    public function getImageType()
    {
        return $this->imageType;
    }

    public function getImageFile()
    {
        return $this->imageFile;
    }

    /**
     * Returns the dimensions of the image.
     *
     * @return array
     */
    public function getImageDimensions()
    {
        $imageSize = getimagesize($this->getImageFile());
        if (empty($imageSize)) {
            throw new \RuntimeException('Failed to determine the image size.');
        }

        return array(
            'width'  => $imageSize[0],
            'height' => $imageSize[1],
        );
    }

    public function saveAs($file = null, $quality = 100)
    {
        if (empty($file)) {
            $file = $this->getImageFile();
        }

        if (file_exists($file)) {
            if (!is_writable($file)) {
                throw new \RuntimeException(sprintf('The file "%s" is not writable.', $file));
            }
        } else {
            $path = realpath($file);
            if (empty($path)) {
                $path = '.';
            }

            if (!is_writable($path)) {
                throw new \RuntimeException(sprintf('The directory "%s" for file "%s" is not writable.', $path, $file));
            }
        }

//        switch ($this->getImageType()) {
//            case IMAGETYPE_GIF:
//                imagegif($this->imageResource, $file);
//                break;
//
//            case IMAGETYPE_JPEG:
//                if (!is_int($quality)) {
//                    $quality = 100;
//                }
//
//                imagejpeg($this->imageResource, $file, $quality);
//                break;
//
//            case IMAGETYPE_PNG:
                if (!is_int($quality)) {
                    $quality = 100;
                }
                $quality = $quality / 100 * 9;

                imagealphablending($this->imageResource, false);
                imagesavealpha($this->imageResource, true);

                imagepng($this->imageResource, $file, $quality);
//                break;
//        }
    }

    /**
     * Copies a file to a local temporary file on the server.
     *
     * @param string $file The location (path or URL) of a file.
     *
     * @return string
     */
    private function copyToTemp($file)
    {
        if (!is_readable($file)) {
            throw new \RuntimeException(sprintf('The file at "%s" is not readable.', $file));
        }

        $sourceHandle = fopen($file, 'r');

        $targetFile   = tempnam(sys_get_temp_dir(), 'mbst_');
        $targetHandle = fopen($targetFile, 'w');

        $this->fileSize = stream_copy_to_stream($sourceHandle, $targetHandle);

        fclose($sourceHandle);
        fclose($targetHandle);

        return $targetFile;
    }

    /**
     * Opens the resource to the image file.
     */
    private function initResource()
    {
        switch ($this->getImageType()) {
            case IMAGETYPE_GIF:
                $resource = imagecreatefromgif($this->getImageFile());
                break;

            case IMAGETYPE_JPEG:
                $resource = imagecreatefromjpeg($this->getImageFile());
                break;

            case IMAGETYPE_PNG:
                $resource = imagecreatefrompng($this->getImageFile());
                break;

            default:
                throw new \InvalidArgumentException('The image file is of an unsupported format.');
        }

        if (!is_resource($resource)) {
            throw new \RuntimeException('Failed to create a resource of the given image.');
        }

        $this->imageResource = $resource;
    }
}
