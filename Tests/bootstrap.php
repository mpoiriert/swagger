<?php

$autoload = require __DIR__ . '/../vendor/autoload.php';;
Doctrine\Common\Annotations\AnnotationRegistry::registerLoader(array($autoload, 'loadClass'));
