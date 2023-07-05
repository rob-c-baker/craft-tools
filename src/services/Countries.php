<?php

namespace alanrogers\tools\services;

use alanrogers\tools\models\Country;
use Exception;

class Countries
{
    private static array $countries = [
        'AF' => ['name' => 'Afghanistan', 'currency_symbol' => '؋', 'currency_code' => 'AFN', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'AFG'],
        'AX' => ['name' => 'Aland Islands', 'currency_symbol' => '€', 'currency_code' => 'EUR', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'ALA'],
        'AL' => ['name' => 'Albania', 'currency_symbol' => 'Lek', 'currency_code' => 'ALL', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'ALB'],
        'DZ' => ['name' => 'Algeria', 'currency_symbol' => 'دج', 'currency_code' => 'DZD', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'DZA'],
        'AS' => ['name' => 'American Samoa', 'currency_symbol' => '$', 'currency_code' => 'USD', 'continent' => 'Oceania', 'continent_code' => 'OC', 'iso3' => 'ASM'],
        'AD' => ['name' => 'Andorra', 'currency_symbol' => '€', 'currency_code' => 'EUR', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'AND'],
        'AO' => ['name' => 'Angola', 'currency_symbol' => 'Kz', 'currency_code' => 'AOA', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'AGO'],
        'AI' => ['name' => 'Anguilla', 'currency_symbol' => '$', 'currency_code' => 'XCD', 'continent' => 'North America', 'continent_code' => 'NA', 'iso3' => 'AIA'],
        'AQ' => ['name' => 'Antarctica', 'currency_symbol' => '$', 'currency_code' => 'AAD', 'continent' => 'Antarctica', 'continent_code' => 'AN', 'iso3' => 'ATA'],
        'AG' => ['name' => 'Antigua and Barbuda', 'currency_symbol' => '$', 'currency_code' => 'XCD', 'continent' => 'North America', 'continent_code' => 'NA', 'iso3' => 'ATG'],
        'AR' => ['name' => 'Argentina', 'currency_symbol' => '$', 'currency_code' => 'ARS', 'continent' => 'South America', 'continent_code' => 'SA', 'iso3' => 'ARG'],
        'AM' => ['name' => 'Armenia', 'currency_symbol' => '֏', 'currency_code' => 'AMD', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'ARM'],
        'AW' => ['name' => 'Aruba', 'currency_symbol' => 'ƒ', 'currency_code' => 'AWG', 'continent' => 'North America', 'continent_code' => 'NA', 'iso3' => 'ABW'],
        'AU' => ['name' => 'Australia', 'currency_symbol' => '$', 'currency_code' => 'AUD', 'continent' => 'Oceania', 'continent_code' => 'OC', 'iso3' => 'AUS'],
        'AT' => ['name' => 'Austria', 'currency_symbol' => '€', 'currency_code' => 'EUR', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'AUT'],
        'AZ' => ['name' => 'Azerbaijan', 'currency_symbol' => 'm', 'currency_code' => 'AZN', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'AZE'],
        'BS' => ['name' => 'Bahamas', 'currency_symbol' => 'B$', 'currency_code' => 'BSD', 'continent' => 'North America', 'continent_code' => 'NA', 'iso3' => 'BHS'],
        'BH' => ['name' => 'Bahrain', 'currency_symbol' => '.د.ب', 'currency_code' => 'BHD', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'BHR'],
        'BD' => ['name' => 'Bangladesh', 'currency_symbol' => '৳', 'currency_code' => 'BDT', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'BGD'],
        'BB' => ['name' => 'Barbados', 'currency_symbol' => 'Bds$', 'currency_code' => 'BBD', 'continent' => 'North America', 'continent_code' => 'NA', 'iso3' => 'BRB'],
        'BY' => ['name' => 'Belarus', 'currency_symbol' => 'Br', 'currency_code' => 'BYN', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'BLR'],
        'BE' => ['name' => 'Belgium', 'currency_symbol' => '€', 'currency_code' => 'EUR', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'BEL'],
        'BZ' => ['name' => 'Belize', 'currency_symbol' => '$', 'currency_code' => 'BZD', 'continent' => 'North America', 'continent_code' => 'NA', 'iso3' => 'BLZ'],
        'BJ' => ['name' => 'Benin', 'currency_symbol' => 'CFA', 'currency_code' => 'XOF', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'BEN'],
        'BM' => ['name' => 'Bermuda', 'currency_symbol' => '$', 'currency_code' => 'BMD', 'continent' => 'North America', 'continent_code' => 'NA', 'iso3' => 'BMU'],
        'BT' => ['name' => 'Bhutan', 'currency_symbol' => 'Nu.', 'currency_code' => 'BTN', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'BTN'],
        'BO' => ['name' => 'Bolivia', 'currency_symbol' => 'Bs.', 'currency_code' => 'BOB', 'continent' => 'South America', 'continent_code' => 'SA', 'iso3' => 'BOL'],
        'BQ' => ['name' => 'Bonaire, Sint Eustatius and Saba', 'currency_symbol' => '$', 'currency_code' => 'USD', 'continent' => 'North America', 'continent_code' => 'NA', 'iso3' => 'BES'],
        'BA' => ['name' => 'Bosnia and Herzegovina', 'currency_symbol' => 'KM', 'currency_code' => 'BAM', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'BIH'],
        'BW' => ['name' => 'Botswana', 'currency_symbol' => 'P', 'currency_code' => 'BWP', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'BWA'],
        'BV' => ['name' => 'Bouvet Island', 'currency_symbol' => 'kr', 'currency_code' => 'NOK', 'continent' => 'Antarctica', 'continent_code' => 'AN', 'iso3' => 'BVT'],
        'BR' => ['name' => 'Brazil', 'currency_symbol' => 'R$', 'currency_code' => 'BRL', 'continent' => 'South America', 'continent_code' => 'SA', 'iso3' => 'BRA'],
        'IO' => ['name' => 'British Indian Ocean Territory', 'currency_symbol' => '$', 'currency_code' => 'USD', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'IOT'],
        'BN' => ['name' => 'Brunei Darussalam', 'currency_symbol' => 'B$', 'currency_code' => 'BND', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'BRN'],
        'BG' => ['name' => 'Bulgaria', 'currency_symbol' => 'Лв.', 'currency_code' => 'BGN', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'BGR'],
        'BF' => ['name' => 'Burkina Faso', 'currency_symbol' => 'CFA', 'currency_code' => 'XOF', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'BFA'],
        'BI' => ['name' => 'Burundi', 'currency_symbol' => 'FBu', 'currency_code' => 'BIF', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'BDI'],
        'KH' => ['name' => 'Cambodia', 'currency_symbol' => 'KHR', 'currency_code' => 'KHR', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'KHM'],
        'CM' => ['name' => 'Cameroon', 'currency_symbol' => 'FCFA', 'currency_code' => 'XAF', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'CMR'],
        'CA' => ['name' => 'Canada', 'currency_symbol' => '$', 'currency_code' => 'CAD', 'continent' => 'North America', 'continent_code' => 'NA', 'iso3' => 'CAN'],
        'CV' => ['name' => 'Cape Verde', 'currency_symbol' => '$', 'currency_code' => 'CVE', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'CPV'],
        'KY' => ['name' => 'Cayman Islands', 'currency_symbol' => '$', 'currency_code' => 'KYD', 'continent' => 'North America', 'continent_code' => 'NA', 'iso3' => 'CYM'],
        'CF' => ['name' => 'Central African Republic', 'currency_symbol' => 'FCFA', 'currency_code' => 'XAF', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'CAF'],
        'TD' => ['name' => 'Chad', 'currency_symbol' => 'FCFA', 'currency_code' => 'XAF', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'TCD'],
        'CL' => ['name' => 'Chile', 'currency_symbol' => '$', 'currency_code' => 'CLP', 'continent' => 'South America', 'continent_code' => 'SA', 'iso3' => 'CHL'],
        'CN' => ['name' => 'China', 'currency_symbol' => '¥', 'currency_code' => 'CNY', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'CHN'],
        'CX' => ['name' => 'Christmas Island', 'currency_symbol' => '$', 'currency_code' => 'AUD', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'CXR'],
        'CC' => ['name' => 'Cocos (Keeling] Islands', 'currency_symbol' => '$', 'currency_code' => 'AUD', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'CCK'],
        'CO' => ['name' => 'Colombia', 'currency_symbol' => '$', 'currency_code' => 'COP', 'continent' => 'South America', 'continent_code' => 'SA', 'iso3' => 'COL'],
        'KM' => ['name' => 'Comoros', 'currency_symbol' => 'CF', 'currency_code' => 'KMF', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'COM'],
        'CG' => ['name' => 'Congo', 'currency_symbol' => 'FC', 'currency_code' => 'XAF', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'COG'],
        'CD' => ['name' => 'Congo, Democratic Republic of the Congo', 'currency_symbol' => 'FC', 'currency_code' => 'CDF', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'COD'],
        'CK' => ['name' => 'Cook Islands', 'currency_symbol' => '$', 'currency_code' => 'NZD', 'continent' => 'Oceania', 'continent_code' => 'OC', 'iso3' => 'COK'],
        'CR' => ['name' => 'Costa Rica', 'currency_symbol' => '₡', 'currency_code' => 'CRC', 'continent' => 'North America', 'continent_code' => 'NA', 'iso3' => 'CRI'],
        'CI' => ['name' => 'Cote D\'Ivoire', 'currency_symbol' => 'CFA', 'currency_code' => 'XOF', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'CIV'],
        'HR' => ['name' => 'Croatia', 'currency_symbol' => 'kn', 'currency_code' => 'HRK', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'HRV'],
        'CU' => ['name' => 'Cuba', 'currency_symbol' => '$', 'currency_code' => 'CUP', 'continent' => 'North America', 'continent_code' => 'NA', 'iso3' => 'CUB'],
        'CW' => ['name' => 'Curacao', 'currency_symbol' => 'ƒ', 'currency_code' => 'ANG', 'continent' => 'North America', 'continent_code' => 'NA', 'iso3' => 'CUW'],
        'CY' => ['name' => 'Cyprus', 'currency_symbol' => '€', 'currency_code' => 'EUR', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'CYP'],
        'CZ' => ['name' => 'Czech Republic', 'currency_symbol' => 'Kč', 'currency_code' => 'CZK', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'CZE'],
        'DK' => ['name' => 'Denmark', 'currency_symbol' => 'Kr.', 'currency_code' => 'DKK', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'DNK'],
        'DJ' => ['name' => 'Djibouti', 'currency_symbol' => 'Fdj', 'currency_code' => 'DJF', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'DJI'],
        'DM' => ['name' => 'Dominica', 'currency_symbol' => '$', 'currency_code' => 'XCD', 'continent' => 'North America', 'continent_code' => 'NA', 'iso3' => 'DMA'],
        'DO' => ['name' => 'Dominican Republic', 'currency_symbol' => '$', 'currency_code' => 'DOP', 'continent' => 'North America', 'continent_code' => 'NA', 'iso3' => 'DOM'],
        'EC' => ['name' => 'Ecuador', 'currency_symbol' => '$', 'currency_code' => 'USD', 'continent' => 'South America', 'continent_code' => 'SA', 'iso3' => 'ECU'],
        'EG' => ['name' => 'Egypt', 'currency_symbol' => 'ج.م', 'currency_code' => 'EGP', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'EGY'],
        'SV' => ['name' => 'El Salvador', 'currency_symbol' => '$', 'currency_code' => 'USD', 'continent' => 'North America', 'continent_code' => 'NA', 'iso3' => 'SLV'],
        'GQ' => ['name' => 'Equatorial Guinea', 'currency_symbol' => 'FCFA', 'currency_code' => 'XAF', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'GNQ'],
        'ER' => ['name' => 'Eritrea', 'currency_symbol' => 'Nfk', 'currency_code' => 'ERN', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'ERI'],
        'EE' => ['name' => 'Estonia', 'currency_symbol' => '€', 'currency_code' => 'EUR', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'EST'],
        'ET' => ['name' => 'Ethiopia', 'currency_symbol' => 'Nkf', 'currency_code' => 'ETB', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'ETH'],
        'FK' => ['name' => 'Falkland Islands (Malvinas]', 'currency_symbol' => '£', 'currency_code' => 'FKP', 'continent' => 'South America', 'continent_code' => 'SA', 'iso3' => 'FLK'],
        'FO' => ['name' => 'Faroe Islands', 'currency_symbol' => 'Kr.', 'currency_code' => 'DKK', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'FRO'],
        'FJ' => ['name' => 'Fiji', 'currency_symbol' => 'FJ$', 'currency_code' => 'FJD', 'continent' => 'Oceania', 'continent_code' => 'OC', 'iso3' => 'FJI'],
        'FI' => ['name' => 'Finland', 'currency_symbol' => '€', 'currency_code' => 'EUR', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'FIN'],
        'FR' => ['name' => 'France', 'currency_symbol' => '€', 'currency_code' => 'EUR', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'FRA'],
        'GF' => ['name' => 'French Guiana', 'currency_symbol' => '€', 'currency_code' => 'EUR', 'continent' => 'South America', 'continent_code' => 'SA', 'iso3' => 'GUF'],
        'PF' => ['name' => 'French Polynesia', 'currency_symbol' => '₣', 'currency_code' => 'XPF', 'continent' => 'Oceania', 'continent_code' => 'OC', 'iso3' => 'PYF'],
        'TF' => ['name' => 'French Southern Territories', 'currency_symbol' => '€', 'currency_code' => 'EUR', 'continent' => 'Antarctica', 'continent_code' => 'AN', 'iso3' => 'ATF'],
        'GA' => ['name' => 'Gabon', 'currency_symbol' => 'FCFA', 'currency_code' => 'XAF', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'GAB'],
        'GM' => ['name' => 'Gambia', 'currency_symbol' => 'D', 'currency_code' => 'GMD', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'GMB'],
        'GE' => ['name' => 'Georgia', 'currency_symbol' => 'ლ', 'currency_code' => 'GEL', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'GEO'],
        'DE' => ['name' => 'Germany', 'currency_symbol' => '€', 'currency_code' => 'EUR', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'DEU'],
        'GH' => ['name' => 'Ghana', 'currency_symbol' => 'GH₵', 'currency_code' => 'GHS', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'GHA'],
        'GI' => ['name' => 'Gibraltar', 'currency_symbol' => '£', 'currency_code' => 'GIP', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'GIB'],
        'GR' => ['name' => 'Greece', 'currency_symbol' => '€', 'currency_code' => 'EUR', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'GRC'],
        'GL' => ['name' => 'Greenland', 'currency_symbol' => 'Kr.', 'currency_code' => 'DKK', 'continent' => 'North America', 'continent_code' => 'NA', 'iso3' => 'GRL'],
        'GD' => ['name' => 'Grenada', 'currency_symbol' => '$', 'currency_code' => 'XCD', 'continent' => 'North America', 'continent_code' => 'NA', 'iso3' => 'GRD'],
        'GP' => ['name' => 'Guadeloupe', 'currency_symbol' => '€', 'currency_code' => 'EUR', 'continent' => 'North America', 'continent_code' => 'NA', 'iso3' => 'GLP'],
        'GU' => ['name' => 'Guam', 'currency_symbol' => '$', 'currency_code' => 'USD', 'continent' => 'Oceania', 'continent_code' => 'OC', 'iso3' => 'GUM'],
        'GT' => ['name' => 'Guatemala', 'currency_symbol' => 'Q', 'currency_code' => 'GTQ', 'continent' => 'North America', 'continent_code' => 'NA', 'iso3' => 'GTM'],
        'GG' => ['name' => 'Guernsey', 'currency_symbol' => '£', 'currency_code' => 'GBP', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'GGY'],
        'GN' => ['name' => 'Guinea', 'currency_symbol' => 'FG', 'currency_code' => 'GNF', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'GIN'],
        'GW' => ['name' => 'Guinea-Bissau', 'currency_symbol' => 'CFA', 'currency_code' => 'XOF', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'GNB'],
        'GY' => ['name' => 'Guyana', 'currency_symbol' => '$', 'currency_code' => 'GYD', 'continent' => 'South America', 'continent_code' => 'SA', 'iso3' => 'GUY'],
        'HT' => ['name' => 'Haiti', 'currency_symbol' => 'G', 'currency_code' => 'HTG', 'continent' => 'North America', 'continent_code' => 'NA', 'iso3' => 'HTI'],
        'HM' => ['name' => 'Heard Island and McDonald Islands', 'currency_symbol' => '$', 'currency_code' => 'AUD', 'continent' => 'Antarctica', 'continent_code' => 'AN', 'iso3' => 'HMD'],
        'VA' => ['name' => 'Holy See (Vatican City State]', 'currency_symbol' => '€', 'currency_code' => 'EUR', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'VAT'],
        'HN' => ['name' => 'Honduras', 'currency_symbol' => 'L', 'currency_code' => 'HNL', 'continent' => 'North America', 'continent_code' => 'NA', 'iso3' => 'HND'],
        'HK' => ['name' => 'Hong Kong', 'currency_symbol' => '$', 'currency_code' => 'HKD', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'HKG'],
        'HU' => ['name' => 'Hungary', 'currency_symbol' => 'Ft', 'currency_code' => 'HUF', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'HUN'],
        'IS' => ['name' => 'Iceland', 'currency_symbol' => 'kr', 'currency_code' => 'ISK', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'ISL'],
        'IN' => ['name' => 'India', 'currency_symbol' => '₹', 'currency_code' => 'INR', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'IND'],
        'ID' => ['name' => 'Indonesia', 'currency_symbol' => 'Rp', 'currency_code' => 'IDR', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'IDN'],
        'IR' => ['name' => 'Iran, Islamic Republic of', 'currency_symbol' => '﷼', 'currency_code' => 'IRR', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'IRN'],
        'IQ' => ['name' => 'Iraq', 'currency_symbol' => 'د.ع', 'currency_code' => 'IQD', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'IRQ'],
        'IE' => ['name' => 'Ireland', 'currency_symbol' => '€', 'currency_code' => 'EUR', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'IRL'],
        'IM' => ['name' => 'Isle of Man', 'currency_symbol' => '£', 'currency_code' => 'GBP', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'IMN'],
        'IL' => ['name' => 'Israel', 'currency_symbol' => '₪', 'currency_code' => 'ILS', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'ISR'],
        'IT' => ['name' => 'Italy', 'currency_symbol' => '€', 'currency_code' => 'EUR', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'ITA'],
        'JM' => ['name' => 'Jamaica', 'currency_symbol' => 'J$', 'currency_code' => 'JMD', 'continent' => 'North America', 'continent_code' => 'NA', 'iso3' => 'JAM'],
        'JP' => ['name' => 'Japan', 'currency_symbol' => '¥', 'currency_code' => 'JPY', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'JPN'],
        'JE' => ['name' => 'Jersey', 'currency_symbol' => '£', 'currency_code' => 'GBP', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'JEY'],
        'JO' => ['name' => 'Jordan', 'currency_symbol' => 'ا.د', 'currency_code' => 'JOD', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'JOR'],
        'KZ' => ['name' => 'Kazakhstan', 'currency_symbol' => 'лв', 'currency_code' => 'KZT', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'KAZ'],
        'KE' => ['name' => 'Kenya', 'currency_symbol' => 'KSh', 'currency_code' => 'KES', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'KEN'],
        'KI' => ['name' => 'Kiribati', 'currency_symbol' => '$', 'currency_code' => 'AUD', 'continent' => 'Oceania', 'continent_code' => 'OC', 'iso3' => 'KIR'],
        'KP' => ['name' => 'Korea, Democratic People\'s Republic of', 'currency_symbol' => '₩', 'currency_code' => 'KPW', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'PRK'],
        'KR' => ['name' => 'Korea, Republic of', 'currency_symbol' => '₩', 'currency_code' => 'KRW', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'KOR'],
        'XK' => ['name' => 'Kosovo', 'currency_symbol' => '€', 'currency_code' => 'EUR', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'XKX'],
        'KW' => ['name' => 'Kuwait', 'currency_symbol' => 'ك.د', 'currency_code' => 'KWD', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'KWT'],
        'KG' => ['name' => 'Kyrgyzstan', 'currency_symbol' => 'лв', 'currency_code' => 'KGS', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'KGZ'],
        'LA' => ['name' => 'Lao People\'s Democratic Republic', 'currency_symbol' => '₭', 'currency_code' => 'LAK', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'LAO'],
        'LV' => ['name' => 'Latvia', 'currency_symbol' => '€', 'currency_code' => 'EUR', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'LVA'],
        'LB' => ['name' => 'Lebanon', 'currency_symbol' => '£', 'currency_code' => 'LBP', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'LBN'],
        'LS' => ['name' => 'Lesotho', 'currency_symbol' => 'L', 'currency_code' => 'LSL', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'LSO'],
        'LR' => ['name' => 'Liberia', 'currency_symbol' => '$', 'currency_code' => 'LRD', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'LBR'],
        'LY' => ['name' => 'Libyan Arab Jamahiriya', 'currency_symbol' => 'د.ل', 'currency_code' => 'LYD', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'LBY'],
        'LI' => ['name' => 'Liechtenstein', 'currency_symbol' => 'CHf', 'currency_code' => 'CHF', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'LIE'],
        'LT' => ['name' => 'Lithuania', 'currency_symbol' => '€', 'currency_code' => 'EUR', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'LTU'],
        'LU' => ['name' => 'Luxembourg', 'currency_symbol' => '€', 'currency_code' => 'EUR', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'LUX'],
        'MO' => ['name' => 'Macao', 'currency_symbol' => '$', 'currency_code' => 'MOP', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'MAC'],
        'MK' => ['name' => 'Macedonia, the Former Yugoslav Republic of', 'currency_symbol' => 'ден', 'currency_code' => 'MKD', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'MKD'],
        'MG' => ['name' => 'Madagascar', 'currency_symbol' => 'Ar', 'currency_code' => 'MGA', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'MDG'],
        'MW' => ['name' => 'Malawi', 'currency_symbol' => 'MK', 'currency_code' => 'MWK', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'MWI'],
        'MY' => ['name' => 'Malaysia', 'currency_symbol' => 'RM', 'currency_code' => 'MYR', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'MYS'],
        'MV' => ['name' => 'Maldives', 'currency_symbol' => 'Rf', 'currency_code' => 'MVR', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'MDV'],
        'ML' => ['name' => 'Mali', 'currency_symbol' => 'CFA', 'currency_code' => 'XOF', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'MLI'],
        'MT' => ['name' => 'Malta', 'currency_symbol' => '€', 'currency_code' => 'EUR', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'MLT'],
        'MH' => ['name' => 'Marshall Islands', 'currency_symbol' => '$', 'currency_code' => 'USD', 'continent' => 'Oceania', 'continent_code' => 'OC', 'iso3' => 'MHL'],
        'MQ' => ['name' => 'Martinique', 'currency_symbol' => '€', 'currency_code' => 'EUR', 'continent' => 'North America', 'continent_code' => 'NA', 'iso3' => 'MTQ'],
        'MR' => ['name' => 'Mauritania', 'currency_symbol' => 'MRU', 'currency_code' => 'MRO', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'MRT'],
        'MU' => ['name' => 'Mauritius', 'currency_symbol' => '₨', 'currency_code' => 'MUR', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'MUS'],
        'YT' => ['name' => 'Mayotte', 'currency_symbol' => '€', 'currency_code' => 'EUR', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'MYT'],
        'MX' => ['name' => 'Mexico', 'currency_symbol' => '$', 'currency_code' => 'MXN', 'continent' => 'North America', 'continent_code' => 'NA', 'iso3' => 'MEX'],
        'FM' => ['name' => 'Micronesia, Federated States of', 'currency_symbol' => '$', 'currency_code' => 'USD', 'continent' => 'Oceania', 'continent_code' => 'OC', 'iso3' => 'FSM'],
        'MD' => ['name' => 'Moldova, Republic of', 'currency_symbol' => 'L', 'currency_code' => 'MDL', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'MDA'],
        'MC' => ['name' => 'Monaco', 'currency_symbol' => '€', 'currency_code' => 'EUR', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'MCO'],
        'MN' => ['name' => 'Mongolia', 'currency_symbol' => '₮', 'currency_code' => 'MNT', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'MNG'],
        'ME' => ['name' => 'Montenegro', 'currency_symbol' => '€', 'currency_code' => 'EUR', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'MNE'],
        'MS' => ['name' => 'Montserrat', 'currency_symbol' => '$', 'currency_code' => 'XCD', 'continent' => 'North America', 'continent_code' => 'NA', 'iso3' => 'MSR'],
        'MA' => ['name' => 'Morocco', 'currency_symbol' => 'DH', 'currency_code' => 'MAD', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'MAR'],
        'MZ' => ['name' => 'Mozambique', 'currency_symbol' => 'MT', 'currency_code' => 'MZN', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'MOZ'],
        'MM' => ['name' => 'Myanmar', 'currency_symbol' => 'K', 'currency_code' => 'MMK', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'MMR'],
        'NA' => ['name' => 'Namibia', 'currency_symbol' => '$', 'currency_code' => 'NAD', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'NAM'],
        'NR' => ['name' => 'Nauru', 'currency_symbol' => '$', 'currency_code' => 'AUD', 'continent' => 'Oceania', 'continent_code' => 'OC', 'iso3' => 'NRU'],
        'NP' => ['name' => 'Nepal', 'currency_symbol' => '₨', 'currency_code' => 'NPR', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'NPL'],
        'NL' => ['name' => 'Netherlands', 'currency_symbol' => '€', 'currency_code' => 'EUR', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'NLD'],
        'AN' => ['name' => 'Netherlands Antilles', 'currency_symbol' => 'NAf', 'currency_code' => 'ANG', 'continent' => 'North America', 'continent_code' => 'NA', 'iso3' => 'ANT'],
        'NC' => ['name' => 'New Caledonia', 'currency_symbol' => '₣', 'currency_code' => 'XPF', 'continent' => 'Oceania', 'continent_code' => 'OC', 'iso3' => 'NCL'],
        'NZ' => ['name' => 'New Zealand', 'currency_symbol' => '$', 'currency_code' => 'NZD', 'continent' => 'Oceania', 'continent_code' => 'OC', 'iso3' => 'NZL'],
        'NI' => ['name' => 'Nicaragua', 'currency_symbol' => 'C$', 'currency_code' => 'NIO', 'continent' => 'North America', 'continent_code' => 'NA', 'iso3' => 'NIC'],
        'NE' => ['name' => 'Niger', 'currency_symbol' => 'CFA', 'currency_code' => 'XOF', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'NER'],
        'NG' => ['name' => 'Nigeria', 'currency_symbol' => '₦', 'currency_code' => 'NGN', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'NGA'],
        'NU' => ['name' => 'Niue', 'currency_symbol' => '$', 'currency_code' => 'NZD', 'continent' => 'Oceania', 'continent_code' => 'OC', 'iso3' => 'NIU'],
        'NF' => ['name' => 'Norfolk Island', 'currency_symbol' => '$', 'currency_code' => 'AUD', 'continent' => 'Oceania', 'continent_code' => 'OC', 'iso3' => 'NFK'],
        'MP' => ['name' => 'Northern Mariana Islands', 'currency_symbol' => '$', 'currency_code' => 'USD', 'continent' => 'Oceania', 'continent_code' => 'OC', 'iso3' => 'MNP'],
        'NO' => ['name' => 'Norway', 'currency_symbol' => 'kr', 'currency_code' => 'NOK', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'NOR'],
        'OM' => ['name' => 'Oman', 'currency_symbol' => '.ع.ر', 'currency_code' => 'OMR', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'OMN'],
        'PK' => ['name' => 'Pakistan', 'currency_symbol' => '₨', 'currency_code' => 'PKR', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'PAK'],
        'PW' => ['name' => 'Palau', 'currency_symbol' => '$', 'currency_code' => 'USD', 'continent' => 'Oceania', 'continent_code' => 'OC', 'iso3' => 'PLW'],
        'PS' => ['name' => 'Palestinian Territory, Occupied', 'currency_symbol' => '₪', 'currency_code' => 'ILS', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'PSE'],
        'PA' => ['name' => 'Panama', 'currency_symbol' => 'B/.', 'currency_code' => 'PAB', 'continent' => 'North America', 'continent_code' => 'NA', 'iso3' => 'PAN'],
        'PG' => ['name' => 'Papua New Guinea', 'currency_symbol' => 'K', 'currency_code' => 'PGK', 'continent' => 'Oceania', 'continent_code' => 'OC', 'iso3' => 'PNG'],
        'PY' => ['name' => 'Paraguay', 'currency_symbol' => '₲', 'currency_code' => 'PYG', 'continent' => 'South America', 'continent_code' => 'SA', 'iso3' => 'PRY'],
        'PE' => ['name' => 'Peru', 'currency_symbol' => 'S/.', 'currency_code' => 'PEN', 'continent' => 'South America', 'continent_code' => 'SA', 'iso3' => 'PER'],
        'PH' => ['name' => 'Philippines', 'currency_symbol' => '₱', 'currency_code' => 'PHP', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'PHL'],
        'PN' => ['name' => 'Pitcairn', 'currency_symbol' => '$', 'currency_code' => 'NZD', 'continent' => 'Oceania', 'continent_code' => 'OC', 'iso3' => 'PCN'],
        'PL' => ['name' => 'Poland', 'currency_symbol' => 'zł', 'currency_code' => 'PLN', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'POL'],
        'PT' => ['name' => 'Portugal', 'currency_symbol' => '€', 'currency_code' => 'EUR', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'PRT'],
        'PR' => ['name' => 'Puerto Rico', 'currency_symbol' => '$', 'currency_code' => 'USD', 'continent' => 'North America', 'continent_code' => 'NA', 'iso3' => 'PRI'],
        'QA' => ['name' => 'Qatar', 'currency_symbol' => 'ق.ر', 'currency_code' => 'QAR', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'QAT'],
        'RE' => ['name' => 'Reunion', 'currency_symbol' => '€', 'currency_code' => 'EUR', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'REU'],
        'RO' => ['name' => 'Romania', 'currency_symbol' => 'lei', 'currency_code' => 'RON', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'ROM'],
        'RU' => ['name' => 'Russian Federation', 'currency_symbol' => '₽', 'currency_code' => 'RUB', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'RUS'],
        'RW' => ['name' => 'Rwanda', 'currency_symbol' => 'FRw', 'currency_code' => 'RWF', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'RWA'],
        'BL' => ['name' => 'Saint Barthelemy', 'currency_symbol' => '€', 'currency_code' => 'EUR', 'continent' => 'North America', 'continent_code' => 'NA', 'iso3' => 'BLM'],
        'SH' => ['name' => 'Saint Helena', 'currency_symbol' => '£', 'currency_code' => 'SHP', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'SHN'],
        'KN' => ['name' => 'Saint Kitts and Nevis', 'currency_symbol' => '$', 'currency_code' => 'XCD', 'continent' => 'North America', 'continent_code' => 'NA', 'iso3' => 'KNA'],
        'LC' => ['name' => 'Saint Lucia', 'currency_symbol' => '$', 'currency_code' => 'XCD', 'continent' => 'North America', 'continent_code' => 'NA', 'iso3' => 'LCA'],
        'MF' => ['name' => 'Saint Martin', 'currency_symbol' => '€', 'currency_code' => 'EUR', 'continent' => 'North America', 'continent_code' => 'NA', 'iso3' => 'MAF'],
        'PM' => ['name' => 'Saint Pierre and Miquelon', 'currency_symbol' => '€', 'currency_code' => 'EUR', 'continent' => 'North America', 'continent_code' => 'NA', 'iso3' => 'SPM'],
        'VC' => ['name' => 'Saint Vincent and the Grenadines', 'currency_symbol' => '$', 'currency_code' => 'XCD', 'continent' => 'North America', 'continent_code' => 'NA', 'iso3' => 'VCT'],
        'WS' => ['name' => 'Samoa', 'currency_symbol' => 'SAT', 'currency_code' => 'WST', 'continent' => 'Oceania', 'continent_code' => 'OC', 'iso3' => 'WSM'],
        'SM' => ['name' => 'San Marino', 'currency_symbol' => '€', 'currency_code' => 'EUR', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'SMR'],
        'ST' => ['name' => 'Sao Tome and Principe', 'currency_symbol' => 'Db', 'currency_code' => 'STD', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'STP'],
        'SA' => ['name' => 'Saudi Arabia', 'currency_symbol' => '﷼', 'currency_code' => 'SAR', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'SAU'],
        'SN' => ['name' => 'Senegal', 'currency_symbol' => 'CFA', 'currency_code' => 'XOF', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'SEN'],
        'RS' => ['name' => 'Serbia', 'currency_symbol' => 'din', 'currency_code' => 'RSD', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'SRB'],
        'CS' => ['name' => 'Serbia and Montenegro', 'currency_symbol' => 'din', 'currency_code' => 'RSD', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'SCG'],
        'SC' => ['name' => 'Seychelles', 'currency_symbol' => 'SRe', 'currency_code' => 'SCR', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'SYC'],
        'SL' => ['name' => 'Sierra Leone', 'currency_symbol' => 'Le', 'currency_code' => 'SLL', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'SLE'],
        'SG' => ['name' => 'Singapore', 'currency_symbol' => '$', 'currency_code' => 'SGD', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'SGP'],
        'SX' => ['name' => 'St Martin', 'currency_symbol' => 'ƒ', 'currency_code' => 'ANG', 'continent' => 'North America', 'continent_code' => 'NA', 'iso3' => 'SXM'],
        'SK' => ['name' => 'Slovakia', 'currency_symbol' => '€', 'currency_code' => 'EUR', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'SVK'],
        'SI' => ['name' => 'Slovenia', 'currency_symbol' => '€', 'currency_code' => 'EUR', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'SVN'],
        'SB' => ['name' => 'Solomon Islands', 'currency_symbol' => 'Si$', 'currency_code' => 'SBD', 'continent' => 'Oceania', 'continent_code' => 'OC', 'iso3' => 'SLB'],
        'SO' => ['name' => 'Somalia', 'currency_symbol' => 'Sh.so.', 'currency_code' => 'SOS', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'SOM'],
        'ZA' => ['name' => 'South Africa', 'currency_symbol' => 'R', 'currency_code' => 'ZAR', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'ZAF'],
        'GS' => ['name' => 'South Georgia and the South Sandwich Islands', 'currency_symbol' => '£', 'currency_code' => 'GBP', 'continent' => 'Antarctica', 'continent_code' => 'AN', 'iso3' => 'SGS'],
        'SS' => ['name' => 'South Sudan', 'currency_symbol' => '£', 'currency_code' => 'SSP', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'SSD'],
        'ES' => ['name' => 'Spain', 'currency_symbol' => '€', 'currency_code' => 'EUR', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'ESP'],
        'LK' => ['name' => 'Sri Lanka', 'currency_symbol' => 'Rs', 'currency_code' => 'LKR', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'LKA'],
        'SD' => ['name' => 'Sudan', 'currency_symbol' => '.س.ج', 'currency_code' => 'SDG', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'SDN'],
        'SR' => ['name' => 'Suriname', 'currency_symbol' => '$', 'currency_code' => 'SRD', 'continent' => 'South America', 'continent_code' => 'SA', 'iso3' => 'SUR'],
        'SJ' => ['name' => 'Svalbard and Jan Mayen', 'currency_symbol' => 'kr', 'currency_code' => 'NOK', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'SJM'],
        'SZ' => ['name' => 'Swaziland', 'currency_symbol' => 'E', 'currency_code' => 'SZL', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'SWZ'],
        'SE' => ['name' => 'Sweden', 'currency_symbol' => 'kr', 'currency_code' => 'SEK', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'SWE'],
        'CH' => ['name' => 'Switzerland', 'currency_symbol' => 'CHf', 'currency_code' => 'CHF', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'CHE'],
        'SY' => ['name' => 'Syrian Arab Republic', 'currency_symbol' => 'LS', 'currency_code' => 'SYP', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'SYR'],
        'TW' => ['name' => 'Taiwan, Province of China', 'currency_symbol' => '$', 'currency_code' => 'TWD', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'TWN'],
        'TJ' => ['name' => 'Tajikistan', 'currency_symbol' => 'SM', 'currency_code' => 'TJS', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'TJK'],
        'TZ' => ['name' => 'Tanzania, United Republic of', 'currency_symbol' => 'TSh', 'currency_code' => 'TZS', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'TZA'],
        'TH' => ['name' => 'Thailand', 'currency_symbol' => '฿', 'currency_code' => 'THB', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'THA'],
        'TL' => ['name' => 'Timor-Leste', 'currency_symbol' => '$', 'currency_code' => 'USD', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'TLS'],
        'TG' => ['name' => 'Togo', 'currency_symbol' => 'CFA', 'currency_code' => 'XOF', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'TGO'],
        'TK' => ['name' => 'Tokelau', 'currency_symbol' => '$', 'currency_code' => 'NZD', 'continent' => 'Oceania', 'continent_code' => 'OC', 'iso3' => 'TKL'],
        'TO' => ['name' => 'Tonga', 'currency_symbol' => '$', 'currency_code' => 'TOP', 'continent' => 'Oceania', 'continent_code' => 'OC', 'iso3' => 'TON'],
        'TT' => ['name' => 'Trinidad and Tobago', 'currency_symbol' => '$', 'currency_code' => 'TTD', 'continent' => 'North America', 'continent_code' => 'NA', 'iso3' => 'TTO'],
        'TN' => ['name' => 'Tunisia', 'currency_symbol' => 'ت.د', 'currency_code' => 'TND', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'TUN'],
        'TR' => ['name' => 'Turkey', 'currency_symbol' => '₺', 'currency_code' => 'TRY', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'TUR'],
        'TM' => ['name' => 'Turkmenistan', 'currency_symbol' => 'T', 'currency_code' => 'TMT', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'TKM'],
        'TC' => ['name' => 'Turks and Caicos Islands', 'currency_symbol' => '$', 'currency_code' => 'USD', 'continent' => 'North America', 'continent_code' => 'NA', 'iso3' => 'TCA'],
        'TV' => ['name' => 'Tuvalu', 'currency_symbol' => '$', 'currency_code' => 'AUD', 'continent' => 'Oceania', 'continent_code' => 'OC', 'iso3' => 'TUV'],
        'UG' => ['name' => 'Uganda', 'currency_symbol' => 'USh', 'currency_code' => 'UGX', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'UGA'],
        'UA' => ['name' => 'Ukraine', 'currency_symbol' => '₴', 'currency_code' => 'UAH', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'UKR'],
        'AE' => ['name' => 'United Arab Emirates', 'currency_symbol' => 'إ.د', 'currency_code' => 'AED', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'ARE'],
        'GB' => ['name' => 'United Kingdom', 'currency_symbol' => '£', 'currency_code' => 'GBP', 'continent' => 'Europe', 'continent_code' => 'EU', 'iso3' => 'GBR'],
        'US' => ['name' => 'United States', 'currency_symbol' => '$', 'currency_code' => 'USD', 'continent' => 'North America', 'continent_code' => 'NA', 'iso3' => 'USA'],
        'UM' => ['name' => 'United States Minor Outlying Islands', 'currency_symbol' => '$', 'currency_code' => 'USD', 'continent' => 'North America', 'continent_code' => 'NA', 'iso3' => 'UMI'],
        'UY' => ['name' => 'Uruguay', 'currency_symbol' => '$', 'currency_code' => 'UYU', 'continent' => 'South America', 'continent_code' => 'SA', 'iso3' => 'URY'],
        'UZ' => ['name' => 'Uzbekistan', 'currency_symbol' => 'лв', 'currency_code' => 'UZS', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'UZB'],
        'VU' => ['name' => 'Vanuatu', 'currency_symbol' => 'VT', 'currency_code' => 'VUV', 'continent' => 'Oceania', 'continent_code' => 'OC', 'iso3' => 'VUT'],
        'VE' => ['name' => 'Venezuela', 'currency_symbol' => 'Bs', 'currency_code' => 'VEF', 'continent' => 'South America', 'continent_code' => 'SA', 'iso3' => 'VEN'],
        'VN' => ['name' => 'Viet Nam', 'currency_symbol' => '₫', 'currency_code' => 'VND', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'VNM'],
        'VG' => ['name' => 'Virgin Islands, British', 'currency_symbol' => '$', 'currency_code' => 'USD', 'continent' => 'North America', 'continent_code' => 'NA', 'iso3' => 'VGB'],
        'VI' => ['name' => 'Virgin Islands, U.s.', 'currency_symbol' => '$', 'currency_code' => 'USD', 'continent' => 'North America', 'continent_code' => 'NA', 'iso3' => 'VIR'],
        'WF' => ['name' => 'Wallis and Futuna', 'currency_symbol' => '₣', 'currency_code' => 'XPF', 'continent' => 'Oceania', 'continent_code' => 'OC', 'iso3' => 'WLF'],
        'EH' => ['name' => 'Western Sahara', 'currency_symbol' => 'MAD', 'currency_code' => 'MAD', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'ESH'],
        'YE' => ['name' => 'Yemen', 'currency_symbol' => '﷼', 'currency_code' => 'YER', 'continent' => 'Asia', 'continent_code' => 'AS', 'iso3' => 'YEM'],
        'ZM' => ['name' => 'Zambia', 'currency_symbol' => 'ZK', 'currency_code' => 'ZMW', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'ZMB'],
        'ZW' => ['name' => 'Zimbabwe', 'currency_symbol' => '$', 'currency_code' => 'ZWL', 'continent' => 'Africa', 'continent_code' => 'AF', 'iso3' => 'ZWE']
    ];

    /**
     * @throws Exception
     */
    public function getCountryFromName(string $name): ?Country
    {
        foreach (self::$countries as $iso2 => $c) {
            if ($c['name'] === $name) {
                $country = new Country([ ...$c, 'iso2' => $iso2 ]);
                if (!$country->validate()) {
                    throw new Exception(sprintf('Invalid country defined: %s', $iso2));
                }
                return $country;
            }
        }
        return null;
    }

    /**
     * @throws Exception
     */
    public function getCountryFromISO2(string $iso2): ?Country
    {
        foreach (array_keys(self::$countries) as $key) {
            if ($key === $iso2) {
                $country = new Country([ ...self::$countries[$key], 'iso2' => $key ]);
                if (!$country->validate()) {
                    throw new Exception(sprintf('Invalid country defined: %s', $iso2));
                }
                return $country;
            }
        }
        return null;
    }

    /**
     * @throws Exception
     */
    public function getCountryFromISO3(string $iso3): ?Country
    {
        foreach (self::$countries as $iso2 => $c) {
            if ($c['iso3'] === $iso3) {
                $country = new Country([ ...$c, 'iso2' => $iso2 ]);
                if (!$country->validate()) {
                    throw new Exception(sprintf('Invalid country defined: %s', $iso2));
                }
                return $country;
            }
        }
        return null;
    }
}