<?php

require __DIR__ . '/vendor/autoload.php';

ini_set('memory_limit', '2G');

use Monolog\Logger;
use PHPExiftool\Reader;
use PHPExiftool\Driver\Value\ValueInterface;
use Symfony\Component\Finder\Finder;


/////////////////////////////////////////////
//$inputDirs    = [
//    '/volume1/Family/preview'
//];
//$outputDir    = '/volume1/photo';
//$duplicateDir = '/volume1/Family/duplicates';
/////////////////////////////////////////////


/////////////////////////////////////////////
$inputDirs    = [
    '/Users/armen/Desktop/input'
];
$outputDir    = '/Users/armen/Desktop/output';
$duplicateDir = '/Users/armen/Desktop/duplicates';
/////////////////////////////////////////////



$finder = new Finder();
$finder->files()->in($inputDirs);

$fs = new \Symfony\Component\Filesystem\Filesystem();

$logger = new Logger('exiftool');
$logger->pushHandler(new \Monolog\Handler\NullHandler(Logger::ERROR));

foreach ($finder as $file) {

    $reader = Reader::create($logger);

    $metaDatas = $reader->files($file->getPathName())->first();


    $pathinfo        = pathinfo($metaDatas->getFile());
    $filename        = $pathinfo['basename'];
    $filenamePartial = $pathinfo['filename'];
    $ext             = $pathinfo['extension'];


    $createdDate = [];
    foreach ($metaDatas as $metadata) {
        $value = $metadata->getValue()->asString();
        $tag   = (string)$metadata->getTag();

        if (in_array($tag, [
            'Composite:SubSecCreateDate',
            'XMP-photoshop:DateCreated',
            'ExifIFD:CreateDate',
            'QuickTime:CreateDate',
            'ExifIFD:DateTimeOriginal'
        ])
        ) {
            if (is_object(\DateTime::createFromFormat('Y:m:d H:i:s.u', $value))) {
                $date                = \DateTime::createFromFormat('Y:m:d H:i:s.u', $value);
                $createdDate['high'] = $date->format('Y-m-d His') . substr($date->format('u'), 0, 3);
            } else if (is_object(\DateTime::createFromFormat('Y:m:d H:i:s', $value))) {
                $date                  = \DateTime::createFromFormat('Y:m:d H:i:s', $value);
                $createdDate['normal'] = $date->format('Y-m-d His') . '000';
            }
        }

        if (in_array($tag, [
            'System:FileModifyDate'
        ])
        ) {
            $date = \DateTime::createFromFormat('Y:m:d H:i:sP', $value);
            $date->setTimezone($date->getTimezone());
            $createdDate['low'] = $date->setTimezone($date->getTimezone())->format('Y-m-d His') . '000';
        }
    }

    $createdDateValue = '';
    if (isset($createdDate['high'])) {
        $createdDateValue = $createdDate['high'];
    } elseif (isset($createdDate['normal'])) {
        $createdDateValue = $createdDate['normal'];
    } elseif (isset($createdDate['low'])) {
        $createdDateValue = $createdDate['low'];
    }

    if ($createdDateValue) {

        $date           = \DateTime::createFromFormat('Y-m-d His', substr($createdDateValue, 0, -3));
        $outputFilePath = $outputDir . '/' . $date->format('Y/Y-m') . '/' . $createdDateValue . '.' . $ext;
        if (file_exists($outputFilePath)) {
            $duplicateFilePath = $duplicateDir . '/' . $date->format('Y/Y-m') . '/' . $createdDateValue . '-' . substr(md5(uniqid(rand(), true)), 0, 6) . '.' . $ext;
            $fs->copy($metaDatas->getFile(), $duplicateFilePath);
            $fs->remove($metaDatas->getFile());
            echo 'Duplicate: ' . $filename . ' => ' . pathinfo($duplicateFilePath, PATHINFO_BASENAME) . PHP_EOL;
        } else {
            $fs->copy($metaDatas->getFile(), $outputFilePath);
            $fs->remove($metaDatas->getFile());
            echo 'Rename: ' . $filename . ' => ' . pathinfo($outputFilePath, PATHINFO_BASENAME) . PHP_EOL;
        }

    } else {
        echo 'Not Found ' . $filename . PHP_EOL;
        foreach ($metaDatas as $metadata) {
            if ((string)$metadata->getTag() != 'Composite:ThumbnailImage') {
                var_dump((string)$metadata->getTag() . '::' . $metadata->getValue()->asString());
            }
        }
    }

    if (0 && $filename == '2013-12-18 175950107701.jpg') {
        var_dump($createdDate);

        foreach ($metaDatas as $metadata) {
            if ((string)$metadata->getTag() != 'Composite:ThumbnailImage') {
                var_dump((string)$metadata->getTag() . '::' . $metadata->getValue()->asString());
            }
        }
        exit;
    }
}