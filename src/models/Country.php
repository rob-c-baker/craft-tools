<?php

namespace alanrogers\tools\models;

use craft\base\Model;

class Country extends Model
{
    /**
     * The name of the country in English
     * @var string
     */
    public string $name = '';

    /**
     * The 2 char ISO standard country code (capital letters)
     * @var string
     */
    public string $iso2 = '';

    /**
     * The 3 char ISO standard country code (capital letters)
     * @var string
     */
    public string $iso3 = '';

    /**
     * The 3 char ISO standard currency code (capital letters)
     * @var string
     */
    public string $currency_code = '';

    /**
     * The currency symbol
     * @var string
     */
    public string $currency_symbol = '';

    /**
     * The name of the continent in English
     * @var string
     */
    public string $continent = '';

    /**
     * The 2 char ISO standard continent code (capital letters)
     * @var string
     */
    public string $continent_code = '';

    public function rules(): array
    {
        $rules = parent::rules();

        // All fields required:
        $rules[] = [ [ 'name', 'iso2', 'iso3', 'currency_code', 'currency_symbol', 'continent', 'continent_code' ], 'required' ];

        // 2 chars
        $rules[] = [ [ 'iso2' ], 'string', 'length' => 2 ];

        // 3 chars
        $rules[] = [ [ 'iso3', 'currency_code' ], 'string', 'length' => 3 ];

        // Uppercase
        $rules[] = [ [ 'iso2', 'iso3', 'currency_code', 'continent_code' ], 'filter', 'filter' => 'strtoupper' ];

        return $rules;
    }
}