<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/SplClassLoader.php';

$autoloader = new SplClassLoader('MichielBakker\\Steganography', sprintf('%s/src', __DIR__));
$autoloader->register();

use MichielBakker\Steganography\SteganographyImage;

$images = array(
    'sample-image.jpg',
    'sample-image-invalid.jpg',
);

/**
 * Image validity detection.
 */
echo '********************************', PHP_EOL;
echo '*** IMAGE VALIDITY DETECTION ***', PHP_EOL, PHP_EOL;

foreach ($images as $image) {
    $si = new SteganographyImage($image);

    printf('File.: %s%s', $image, PHP_EOL);
    printf('Temp.: %s%s', $si->getImageFile(), PHP_EOL);
    printf('Valid: %s%s', $si->isValidCleanImage() ? 'YES' : 'NO', PHP_EOL);

    echo PHP_EOL;
}

/**
 * Message detection.
 */
echo '*******************************', PHP_EOL;
echo '*** IMAGE MESSAGE DETECTION ***', PHP_EOL, PHP_EOL;

foreach ($images as $image) {
    $si = new SteganographyImage($image);

    printf('File............: %s%s', $image, PHP_EOL);
    printf('Temp............: %s%s', $si->getImageFile(), PHP_EOL);
    printf('Contains message: %s%s', $si->isValidMessageImage() ? 'YES' : 'NO', PHP_EOL);

    echo PHP_EOL;
}

/**
 * Message insertion.
 */
echo '*******************************', PHP_EOL;
echo '*** IMAGE MESSAGE INSERTION ***', PHP_EOL, PHP_EOL;

foreach ($images as $image) {
    $si = new SteganographyImage($image);
    if (!$si->isValidCleanImage()) {
        continue;
    }

    $message = 'This is a test message to determine if both the basic message embedding and extraction it works.';

    printf('File............: %s%s', $image, PHP_EOL);
    printf('Temp............: %s%s', $si->getImageFile(), PHP_EOL);
    printf('Contains message: %s%s', $si->isValidMessageImage() ? 'YES' : 'NO', PHP_EOL);
    printf('Embed message...: %s%s', $message, PHP_EOL);

    $si->embedMessage($message);

    printf('Contains message: %s%s', $si->isValidMessageImage() ? 'YES' : 'NO', PHP_EOL);

    $si->saveAs(__DIR__ . '/output/image-message-insertion.png');

    printf('Result..........: %s%s', '/output/image-message-insertion.png', PHP_EOL);

    echo PHP_EOL;
}

/**
 * Message insertion.
 */
echo '********************************', PHP_EOL;
echo '*** IMAGE MESSAGE EXTRACTION ***', PHP_EOL, PHP_EOL;

$image = __DIR__ . '/output/image-message-insertion.png';

$si = new SteganographyImage($image);

printf('File.............: %s%s', $image, PHP_EOL);
printf('Temp.............: %s%s', $si->getImageFile(), PHP_EOL);
printf('Contains message.: %s%s', $si->isValidMessageImage() ? 'YES' : 'NO', PHP_EOL);

$message = $si->extractMessage();

printf('Extracted message: %s%s', $message, PHP_EOL);

echo PHP_EOL;
