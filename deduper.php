<?php

require __DIR__ . '/vendor/autoload.php';

ini_set('memory_limit', '2G');

use Monolog\Logger;
use PHPExiftool\Reader;
use PHPExiftool\Driver\Value\ValueInterface;
use Symfony\Component\Finder\Finder;


/////////////////////////////////////////////
$inputDirs    = [
    '/volume1/photo'
];
$excludeDirs  = ['@eaDir', '.DS_Store'];

$finder = new Finder();
$finder->files()->in($inputDirs);
foreach ($excludeDirs as $excludeDir) {
    $finder->exclude($excludeDir);
}

$fs = new \Symfony\Component\Filesystem\Filesystem();

$logger = new Logger('exiftool');
$logger->pushHandler(new \Monolog\Handler\NullHandler(Logger::ERROR));

/** @var \Symfony\Component\Finder\SplFileInfo $file */
foreach ($finder as $file) {
    if(
        $file->getPathname() !== strtolower($file->getPathname()) &&
        file_exists(strtolower($file->getPathname()))
    ) {

        echo 'Removing: '.$file->getPathname();
        $fs->remove($file->getPathname());
    }
}