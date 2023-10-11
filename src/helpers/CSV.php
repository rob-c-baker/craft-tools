<?php

namespace alanrogers\tools\helpers;

use Exception;

class CSV implements HelperInterface
{
    /**
     * Makes an array of data from the VSH file referenced by `$csv_path`.
     * Assumes that the first row are headers and those values are used as keys for values in each row.
     * @param string $csv_path
     * @return array|false
     * @throws Exception
     */
    public function makeDataArray(string $csv_path) : array|false
    {
        $csv = fopen($csv_path, 'r');
        if ($csv === false) {
            throw new Exception(sprintf('Could not open file "%s" as CSV', basename($csv_path)));
        }
        $headers = [];
        $data = [];

        // header
        $row = fgetcsv($csv);
        foreach ($row as $name) {
            $headers[] = trim($name);
        }

        while ($row = fgetcsv($csv)) {
            $r = [];
            foreach ($headers as $idx => $header) {
                $r[$header] = trim($row[$idx]);
            }
            $data[] = $r;
        }

        return $data;
    }
}