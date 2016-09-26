<?php

namespace MichielBakker\Steganography;

/**
 * Steganography class supporting messages in grayscale images.
 */
class SteganographyImage extends Image
{
    public function __construct($file)
    {
        parent::__construct($file);
    }

    /**
     * Tests wether the given image is a valid image to use as clean image (to embed
     * a hidden message in).
     *
     * @return bool
     */
    public function isValidCleanImage()
    {
        $imageDimensions = $this->getImageDimensions();

        for ($y = 0; $y < $imageDimensions['height']; $y++) {
            for ($x = 0; $x < $imageDimensions['width']; $x++) {
                $colors = $this->getImageColorAt($x, $y);

                if ($colors['R'] !== $colors['G'] || $colors['G'] !== $colors['B']) {
                    return false;
                }
            }
        }

        return true;
    }

    public function isValidMessageImage()
    {
        $imageDimensions = $this->getImageDimensions();
        $messageFound    = false;

        for ($y = 0; $y < $imageDimensions['height']; $y++) {
            for ($x = 0; $x < $imageDimensions['width']; $x++) {
                $colors = $this->getImageColorAt($x, $y);

                $diffRG = abs($colors['R'] - $colors['G']);
                $diffGB = abs($colors['G'] - $colors['B']);
                $diffRB = abs($colors['R'] - $colors['B']);

                if ($diffRG > 1 || $diffGB > 1 || $diffRB > 1) {
                    return false;
                }

                if ($diffRG === 1 || $diffGB === 1 || $diffRB === 1) {
                    $messageFound = true;
                }
            }
        }

        return $messageFound;
    }

    public function embedMessage($message)
    {
        if (!is_scalar($message)) {
            throw new \InvalidArgumentException('Only scalar values can be embedded into the image.');
        }

        if (!is_string($message)) {
            $message = strval($message);
        }

        $codes = $this->messageToCodes($message);

        $imageDimensions = $this->getImageDimensions();
//        $pixelCount      = $imageDimensions['width'] * $imageDimensions['height'];
//        if (count($pixelModifications) > $pixelCount) {
//            throw new \RuntimeException('The given message is too long for the given image.');
//        }

        $index = 0;
        for ($y = 0; $y < $imageDimensions['height']; $y++) {
            for ($x = 0; $x < $imageDimensions['width']; $x++) {
                if ($codes[$index] > 0) {
                    $this->modifyPixelAt($x, $y, true);

                    $codes[$index]--;

                    continue;

                } else {
                    $index++;

                    if (!isset($codes[$index])) {
                        break 2;
                    }
                }
            }
        }
    }

    /**
     * Returns color information of the pixel at the given coordinates.
     *
     * @param int $x
     * @param int $y
     *
     * @return array
     */
    private function getImageColorAt($x, $y)
    {
        $colorIndex = imagecolorat($this->imageResource, $x, $y);

        $colors = array(
            'R' => ($colorIndex >> 16) & 0xFF,
            'G' => ($colorIndex >> 8) & 0xFF,
            'B' => $colorIndex & 0xFF,
        );

        if ($this->getImageType() == IMAGETYPE_PNG) {
            $colors['alpha'] = ($colorIndex & 0x7F000000) >> 24;
        }

        return $colors;
    }

    /**
     * Modifies the blue color channel of the given pixel in the image. When the pixel is
     * perfect white (255,255,255), then the blue channel is lowered by one (254). Otherwise
     * the channel is raised by one.
     *
     * @param int  $x
     * @param int  $y
     * @param bool $modify
     *
     * @return bool FALSE if the modification was attempted but failed, TRUE otherwise.
     */
    private function modifyPixelAt($x, $y, $modify)
    {
        if (!$modify) {
            return true;
        }

        $pixelColor = $this->getImageColorAt($x, $y);

        if ($pixelColor['R'] === 255) {
            $pixelColor['B']--;
        } else {
            $pixelColor['B']++;
        }

        $newColor = imagecolorallocate($this->imageResource, $pixelColor['R'], $pixelColor['G'], $pixelColor['B']);

        return imagesetpixel($this->imageResource, $x, $y, $newColor);
    }

    /**
     * Extracts the message from the image.
     *
     * @return string
     */
    public function extractMessage()
    {
        if (!$this->isValidMessageImage()) {
            return '';
        }

        $chars                   = array();
        $index                   = 0;
        $previousHasModification = true;
        $imageDimensions         = $this->getImageDimensions();
        $unmodifiedCount         = 0;

        for ($y = 0; $y < $imageDimensions['height']; $y++) {
            for ($x = 0; $x < $imageDimensions['width']; $x++) {
                $hasModification = $this->getModificationAt($x, $y);

                // If we have a modified pixel, add 1 to the current character.
                if ($hasModification) {
                    $unmodifiedCount = 0;

                    if (!isset($chars[$index])) {
                        $chars[$index] = 0;
                    }

                    $chars[$index]++;

                    continue;
                }

                if ($unmodifiedCount > 2) {
                    break 2;
                }

                $unmodifiedCount++;
                $index++;
            }
        }

        $message = $this->codesToMessage($chars);

        return $message;
    }

    /**
     * @param int $x
     * @param int $y
     *
     * @return bool
     */
    private function getModificationAt($x, $y)
    {
        $pixelColor = $this->getImageColorAt($x, $y);

        return $pixelColor['R'] !== $pixelColor['G'] || $pixelColor['G'] !== $pixelColor['B'];
    }

    /**
     * Converts a message into a code array.
     *
     * @param string $message
     *
     * @return array
     */
    private function messageToCodes($message)
    {
        $base64 = base64_encode($message);
        $codes  = array();

        for ($i = 0; $i < strlen($base64); $i++) {
            $codes[] = ord($base64{$i});
        }

        return $codes;
    }

    /**
     * Converts a code array into a message.
     *
     * @param array $codes
     *
     * @return string
     */
    private function codesToMessage(array $codes)
    {
        $base64 = '';
        foreach ($codes as $code) {
            $base64 .= chr($code);
        }

        $message = base64_decode($base64);

        return $message;
    }
}
