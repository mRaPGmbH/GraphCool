<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Utils;

use RuntimeException;

class Country
{
    /** @var array[] */
    protected static array $namesByCountry;

    /** @var array[] */
    protected static array $mistypedNamesByCountry;

    public static function parseLenient(string $value): ?string
    {
        $result = static::parse($value);
        if ($result !== null) {
            return $result;
        }
        foreach (self::mistypedNamesByCountry() as $code => $names) {
            if (in_array(mb_strtolower($value), $names, true)) {
                return $code;
            }
        }
        return null;
    }

    public static function parse(?string $value): ?string
    {
        if ($value === null || strlen($value) <= 1) {
            return null;
        }
        if (strlen($value) === 2) {
            if ($value === 'XK' || $value === 'xk') {
                return 'XK';
            }
            $tmp = static::alpha2to3();
            if (array_key_exists($value, $tmp)) {
                return $value;
            }
            $upperValue = strtoupper($value);
            if (array_key_exists($upperValue, $tmp)) {
                return $upperValue;
            }
            return null;
        }
        if (strlen($value) === 3) {
            $tmp = array_flip(static::alpha2to3());
            if (array_key_exists($value, $tmp)) {
                return $tmp[$value];
            }
            $upperValue = strtoupper($value);
            if (array_key_exists($upperValue, $tmp)) {
                return $tmp[$upperValue];
            }
            return null;
        }
        foreach (self::namesByCountry() as $code => $names) {
            if (in_array(mb_strtolower($value), $names, true)) {
                return $code;
            }
        }
        return null;
    }

    /**
     * @return string[]
     */
    protected static function alpha2to3(): array
    {
        return [
            'AF' => 'AFG',
            'AL' => 'ALB',
            'DZ' => 'DZA',
            'AS' => 'ASM',
            'AD' => 'AND',
            'AO' => 'AGO',
            'AI' => 'AIA',
            'AQ' => 'ATA',
            'AG' => 'ATG',
            'AR' => 'ARG',
            'AM' => 'ARM',
            'AW' => 'ABW',
            'AU' => 'AUS',
            'AT' => 'AUT',
            'AZ' => 'AZE',
            'BS' => 'BHS',
            'BH' => 'BHR',
            'BD' => 'BGD',
            'BB' => 'BRB',
            'BY' => 'BLR',
            'BE' => 'BEL',
            'BZ' => 'BLZ',
            'BJ' => 'BEN',
            'BM' => 'BMU',
            'BT' => 'BTN',
            'BO' => 'BOL',
            'BQ' => 'BES',
            'BA' => 'BIH',
            'BW' => 'BWA',
            'BV' => 'BVT',
            'BR' => 'BRA',
            'IO' => 'IOT',
            'BN' => 'BRN',
            'BG' => 'BGR',
            'BF' => 'BFA',
            'BI' => 'BDI',
            'CV' => 'CPV',
            'KH' => 'KHM',
            'CM' => 'CMR',
            'CA' => 'CAN',
            'KY' => 'CYM',
            'CF' => 'CAF',
            'TD' => 'TCD',
            'CL' => 'CHL',
            'CN' => 'CHN',
            'CX' => 'CXR',
            'CC' => 'CCK',
            'CO' => 'COL',
            'KM' => 'COM',
            'CD' => 'COD',
            'CG' => 'COG',
            'CK' => 'COK',
            'CR' => 'CRI',
            'HR' => 'HRV',
            'CU' => 'CUB',
            'CW' => 'CUW',
            'CY' => 'CYP',
            'CZ' => 'CZE',
            'CI' => 'CIV',
            'DK' => 'DNK',
            'DJ' => 'DJI',
            'DM' => 'DMA',
            'DO' => 'DOM',
            'EC' => 'ECU',
            'EG' => 'EGY',
            'SV' => 'SLV',
            'GQ' => 'GNQ',
            'ER' => 'ERI',
            'EE' => 'EST',
            'SZ' => 'SWZ',
            'ET' => 'ETH',
            'FK' => 'FLK',
            'FO' => 'FRO',
            'FJ' => 'FJI',
            'FI' => 'FIN',
            'FR' => 'FRA',
            'GF' => 'GUF',
            'PF' => 'PYF',
            'TF' => 'ATF',
            'GA' => 'GAB',
            'GM' => 'GMB',
            'GE' => 'GEO',
            'DE' => 'DEU',
            'GH' => 'GHA',
            'GI' => 'GIB',
            'GR' => 'GRC',
            'GL' => 'GRL',
            'GD' => 'GRD',
            'GP' => 'GLP',
            'GU' => 'GUM',
            'GT' => 'GTM',
            'GG' => 'GGY',
            'GN' => 'GIN',
            'GW' => 'GNB',
            'GY' => 'GUY',
            'HT' => 'HTI',
            'HM' => 'HMD',
            'VA' => 'VAT',
            'HN' => 'HND',
            'HK' => 'HKG',
            'HU' => 'HUN',
            'IS' => 'ISL',
            'IN' => 'IND',
            'ID' => 'IDN',
            'IR' => 'IRN',
            'IQ' => 'IRQ',
            'IE' => 'IRL',
            'IM' => 'IMN',
            'IL' => 'ISR',
            'IT' => 'ITA',
            'JM' => 'JAM',
            'JP' => 'JPN',
            'JE' => 'JEY',
            'JO' => 'JOR',
            'KZ' => 'KAZ',
            'KE' => 'KEN',
            'KI' => 'KIR',
            'KP' => 'PRK',
            'KR' => 'KOR',
            'KW' => 'KWT',
            'KG' => 'KGZ',
            'LA' => 'LAO',
            'LV' => 'LVA',
            'LB' => 'LBN',
            'LS' => 'LSO',
            'LR' => 'LBR',
            'LY' => 'LBY',
            'LI' => 'LIE',
            'LT' => 'LTU',
            'LU' => 'LUX',
            'MO' => 'MAC',
            'MG' => 'MDG',
            'MW' => 'MWI',
            'MY' => 'MYS',
            'MV' => 'MDV',
            'ML' => 'MLI',
            'MT' => 'MLT',
            'MH' => 'MHL',
            'MQ' => 'MTQ',
            'MR' => 'MRT',
            'MU' => 'MUS',
            'YT' => 'MYT',
            'MX' => 'MEX',
            'FM' => 'FSM',
            'MD' => 'MDA',
            'MC' => 'MCO',
            'MN' => 'MNG',
            'ME' => 'MNE',
            'MS' => 'MSR',
            'MA' => 'MAR',
            'MZ' => 'MOZ',
            'MM' => 'MMR',
            'NA' => 'NAM',
            'NR' => 'NRU',
            'NP' => 'NPL',
            'NL' => 'NLD',
            'NC' => 'NCL',
            'NZ' => 'NZL',
            'NI' => 'NIC',
            'NE' => 'NER',
            'NG' => 'NGA',
            'NU' => 'NIU',
            'NF' => 'NFK',
            'MP' => 'MNP',
            'NO' => 'NOR',
            'OM' => 'OMN',
            'PK' => 'PAK',
            'PW' => 'PLW',
            'PS' => 'PSE',
            'PA' => 'PAN',
            'PG' => 'PNG',
            'PY' => 'PRY',
            'PE' => 'PER',
            'PH' => 'PHL',
            'PN' => 'PCN',
            'PL' => 'POL',
            'PT' => 'PRT',
            'PR' => 'PRI',
            'QA' => 'QAT',
            'MK' => 'MKD',
            'RO' => 'ROU',
            'RU' => 'RUS',
            'RW' => 'RWA',
            'RE' => 'REU',
            'BL' => 'BLM',
            'SH' => 'SHN',
            'KN' => 'KNA',
            'LC' => 'LCA',
            'MF' => 'MAF',
            'PM' => 'SPM',
            'VC' => 'VCT',
            'WS' => 'WSM',
            'SM' => 'SMR',
            'ST' => 'STP',
            'SA' => 'SAU',
            'SN' => 'SEN',
            'RS' => 'SRB',
            'SC' => 'SYC',
            'SL' => 'SLE',
            'SG' => 'SGP',
            'SX' => 'SXM',
            'SK' => 'SVK',
            'SI' => 'SVN',
            'SB' => 'SLB',
            'SO' => 'SOM',
            'ZA' => 'ZAF',
            'GS' => 'SGS',
            'SS' => 'SSD',
            'ES' => 'ESP',
            'LK' => 'LKA',
            'SD' => 'SDN',
            'SR' => 'SUR',
            'SJ' => 'SJM',
            'SE' => 'SWE',
            'CH' => 'CHE',
            'SY' => 'SYR',
            'TW' => 'TWN',
            'TJ' => 'TJK',
            'TZ' => 'TZA',
            'TH' => 'THA',
            'TL' => 'TLS',
            'TG' => 'TGO',
            'TK' => 'TKL',
            'TO' => 'TON',
            'TT' => 'TTO',
            'TN' => 'TUN',
            'TR' => 'TUR',
            'TM' => 'TKM',
            'TC' => 'TCA',
            'TV' => 'TUV',
            'UG' => 'UGA',
            'UA' => 'UKR',
            'AE' => 'ARE',
            'GB' => 'GBR',
            'UM' => 'UMI',
            'US' => 'USA',
            'UY' => 'URY',
            'UZ' => 'UZB',
            'VU' => 'VUT',
            'VE' => 'VEN',
            'VN' => 'VNM',
            'VG' => 'VGB',
            'VI' => 'VIR',
            'WF' => 'WLF',
            'EH' => 'ESH',
            'YE' => 'YEM',
            'ZM' => 'ZMB',
            'ZW' => 'ZWE',
            'AX' => 'ALA',
        ];
    }

    /**
     * @return array[]
     */
    protected static function namesByCountry(): array
    {
        if (!isset(static::$namesByCountry)) {
            $contents = file_get_contents(__DIR__ . '/country-data.cache');
            if ($contents === false) {
                // @codeCoverageIgnoreStart
                throw new RuntimeException('Could not get data from country-data.cache');
                // @codeCoverageIgnoreEnd
            }
            static::$namesByCountry = unserialize($contents, ['allowed_classes' => []]);
        }
        return static::$namesByCountry;
    }

    /**
     * @return array[]
     */
    protected static function mistypedNamesByCountry(): array
    {
        if (!isset(static::$mistypedNamesByCountry)) {
            $contents = file_get_contents(__DIR__ . '/country-data-mistyped.cache');
            if ($contents === false) {
                // @codeCoverageIgnoreStart
                throw new RuntimeException('Could not get data from country-data-mistyped.cache');
                // @codeCoverageIgnoreEnd
            }
            static::$mistypedNamesByCountry = unserialize($contents, ['allowed_classes' => []]);
        }
        return static::$mistypedNamesByCountry;
    }

    public static function convertToAlpha3(string $alpha2): ?string
    {
        return static::alpha2to3()[$alpha2] ?? null;
    }

}