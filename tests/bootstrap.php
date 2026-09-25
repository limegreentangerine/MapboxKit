<?php

declare(strict_types=1);

defined('C5_EXECUTE') or define('C5_EXECUTE', true);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../vendor/concrete5/core/bootstrap/helpers.php';

// ConcreteCMS registers these global aliases at runtime; the package code imports them with `use Core;` / `use Package;`.
if (!class_exists('Core', false)) {
    class_alias(Concrete\Core\Support\Facade\Application::class, 'Core');
}

if (!class_exists('Package', false)) {
    class_alias(Concrete\Core\Package\Package::class, 'Package');
}
