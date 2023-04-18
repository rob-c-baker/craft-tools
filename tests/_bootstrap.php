<?php

use craft\test\TestSetup;

ini_set('date.timezone', 'UTC');

// Use the current installation of Craft
const CRAFT_TESTS_PATH = __DIR__;
const CRAFT_STORAGE_PATH = __DIR__ . '/_craft/storage';
const CRAFT_TEMPLATES_PATH = __DIR__ . '/_craft/templates';
const CRAFT_CONFIG_PATH = __DIR__ . '/_craft/config';
const CRAFT_MIGRATIONS_PATH = __DIR__ . '/_craft/migrations';
const CRAFT_TRANSLATIONS_PATH = __DIR__ . '/_craft/translations';

define('CRAFT_VENDOR_PATH', dirname(__DIR__).'/vendor');

require __DIR__ .'/../vendor/autoload.php';

// Load dotenv?
if (class_exists(Dotenv\Dotenv::class)) {
    // By default, this will allow .env file values to override environment variables
    // with matching names. Use `createUnsafeImmutable` to disable this.
    Dotenv\Dotenv::createUnsafeMutable(CRAFT_TESTS_PATH)->load();
}

TestSetup::configureCraft();
