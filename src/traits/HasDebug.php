<?php declare(strict_types=1);

namespace alanrogers\tools\traits;

use Craft;

trait HasDebug
{
    public static string $dump_path = '@storage/logs/%s';
    public static string $dump_filename = 'debug.json';

    public static int $dump_json_flags = JSON_PRETTY_PRINT;

    /**
     * @param ...$values
     * @return void
     */
    public static function dumpAsJson(...$values): void
    {
        $path = Craft::getAlias(sprintf(self::$dump_path, self::$dump_filename));

        foreach ($values as $value) {
            // write each value straight to file - they could be big and this way uses less memory than collecting all
            // the json and then writing it
            file_put_contents($path, json_encode($value, self::$dump_json_flags) . PHP_EOL, FILE_APPEND);
        }
    }

    public static function clearJsonDumpFile(): void
    {
        $path = Craft::getAlias(sprintf(self::$dump_path, self::$dump_filename));
        file_put_contents($path, '');
    }
}