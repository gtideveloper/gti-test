<?php

namespace UpStream\Plugins\CustomFields;

// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

use UpStream\Plugins\CustomFields\Fields\Autoincrement as AutoincrementField;
use UpStream\Plugins\CustomFields\Fields\Category as CategoryField;
use UpStream\Plugins\CustomFields\Fields\Checkbox as CheckboxField;
use UpStream\Plugins\CustomFields\Fields\Colorpicker as ColorpickerField;
use UpStream\Plugins\CustomFields\Fields\Country as CountryField;
use UpStream\Plugins\CustomFields\Fields\Field;
use UpStream\Plugins\CustomFields\Fields\File as FileField;
use UpStream\Plugins\CustomFields\Fields\Radio as RadioField;
use UpStream\Plugins\CustomFields\Fields\Select as SelectField;
use UpStream\Plugins\CustomFields\Fields\Tag as TagField;
use UpStream\Plugins\CustomFields\Fields\Text as TextField;
use UpStream\Plugins\CustomFields\Fields\User as UserField;
use UpStream\Plugins\CustomFields\Traits\Singleton;

/**
 * Plugin main class file.
 *
 * @package     UpStream\Plugins\CustomFields
 * @author      UpStream <https://upstreamplugin.com>
 * @copyright   Copyright (c) 2018 UpStream Project Management
 * @license     GPL-3
 * @since       1.0.0
 * @final
 */
final class Model
{
    use Singleton;

    /**
     * Retrieve all supported field types.
     *
     * @return  array
     * @since   1.0.0
     * @static
     *
     */
    public static function getFieldTypes()
    {
        $fieldTypes = [
            'text'          => __('Text', 'upstream-custom-fields'),
            'select'        => __('Dropdown', 'upstream-custom-fields'),
            'file'          => __('File', 'upstream-custom-fields'),
            'radio'         => __('Radio Buttons', 'upstream-custom-fields'),
            'checkbox'      => __('Checkboxes', 'upstream-custom-fields'),
            'colorpicker'   => __('Color Picker', 'upstream-custom-fields'),
            'category'      => __('Category', 'upstream'),
            'tag'           => __('Tag', 'upstream'),
            'autoincrement' => __('Autoincrement', 'upstream'),
            'user'          => __('User', 'upstream-custom-fields'),
            'country'       => __('Country', 'upstream-custom-fields'),
        ];

        return $fieldTypes;
    }

    public static function filterMediaBasedOnUser($query)
    {
        $user = wp_get_current_user();

        $userRoles = (array)$user->roles;
        if (count(array_intersect($userRoles,
                ['administrator', 'upstream_manager'])) === 0 && in_array('upstream_client_user', $userRoles)) {
            $query['author'] = $user->ID;
        }

        return $query;
    }

    public static function fetchFilterableFieldsForType($itemType = null, $keyAsId = true)
    {
        $rowset = self::fetchRowset($itemType, $keyAsId);

        if (count($rowset) === 0) {
            return [];
        }

        $filterableFields = [];
        foreach ($rowset as $customField) {
            $isFilterable = $customField->isFilterable();
            if ($isFilterable) {
                if ($keyAsId) {
                    $filterableFields[$customField->id] = $customField;
                } else {
                    $filterableFields[$customField->name] = $customField;
                }
            }
        }

        return $filterableFields;
    }

    /**
     * Retrieve an array of custom fields objects based on parameters.
     *
     * @param string $itemType      Item type where custom fields are attached to.
     * @param bool   $keyAsId       If true, $rowset is returned using custom fields ids as keys.
     *                              If false, $rowset is returned using custom fields unique names as keys.
     *
     * @return  array   $rowset
     * @since   1.0.0
     * @static
     *
     */
    public static function fetchRowset($itemType = null, $keyAsId = true)
    {
        $rowset = [];

        if ( ! in_array($itemType, ['project', 'milestone', 'task', 'bug', 'file', 'client', 'colorpicker'])) {
            $itemType = null;
        }

        $queryArgs = [
            'post_type'      => UP_CUSTOM_FIELDS_POST_TYPE,
            'post_status'    => "publish",
            'posts_per_page' => -1,
        ];

        $queryArgs2 = [
            'post_type'      => UP_CUSTOM_FIELDS_POST_TYPE,
            'post_status'    => "publish",
            'posts_per_page' => -1,
        ];


        if ($itemType !== null) {
            $metaKey = UP_CUSTOM_FIELDS_META_PREFIX . 'usage';

            $queryArgs['meta_key']   = $metaKey;
            $queryArgs['orderby'] = ['order_clause' => 'ASC'];
            $queryArgs['meta_query'] = [
                [
                    'key'     => $metaKey,
                    'value'   => $itemType,
                    'compare' => 'LIKE',
                ],
                'order_clause' => [
                    'key' => UP_CUSTOM_FIELDS_META_PREFIX .'weight',
                    'type' => 'NUMERIC'
                ]
            ];

            $queryArgs2['meta_key']   = $metaKey;
            $queryArgs2['meta_query'] = [
                [
                    'key'     => $metaKey,
                    'value'   => $itemType,
                    'compare' => 'LIKE',
                ]
            ];


            unset($metaKey);
        }

        $posts = \Upstream_Cache::get_instance()->get('CustomFields_fetchRowset_'.$itemType.$keyAsId);
        if ($posts === false) {

            $posts = get_posts($queryArgs);
            $posts2 = get_posts($queryArgs2);

            if (count($posts2) > count($posts)) {
                $posts = $posts2;
            }

            \Upstream_Cache::get_instance()->set('CustomFields_fetchRowset_'.$itemType.$keyAsId, $posts);

        }

        if (count($posts) > 0) {
            foreach ($posts as $post) {
                $fieldType = Field::getMetaForId('type', $post->ID);
                if (empty($fieldType)) {
                    continue;
                }

                if (in_array($fieldType, ['multicheck', 'multicheck_inline'])) {
                    $fieldType = 'select';
                } elseif (in_array($fieldType, ['radio', 'radio_inline'])) {
                    $fieldType = 'radio';
                }

                if ($fieldType === 'text') {
                    $field = new TextField($post);
                } elseif ($fieldType === 'select') {
                    $field = new SelectField($post);
                } elseif ($fieldType === 'checkbox') {
                    $field = new CheckboxField($post);
                } elseif ($fieldType === 'radio') {
                    $field = new RadioField($post);
                } elseif ($fieldType === 'file') {
                    $field = new FileField($post);
                } elseif ($fieldType === 'colorpicker') {
                    $field = new ColorpickerField($post);
                } elseif ($fieldType === 'category') {
                    $field = new CategoryField($post);
                } elseif ($fieldType === 'autoincrement') {
                    $field = new AutoincrementField($post);
                } elseif ($fieldType === 'tag') {
                    $field = new TagField($post);
                } elseif ($fieldType === 'user') {
                    $field = new UserField($post);
                } elseif ($fieldType === 'country') {
                    $field = new CountryField($post);
                } else {
                    throw new \Exception('Invalid field type.');
                }

                if ($keyAsId) {
                    $rowset[$field->id] = $field;
                } else {
                    $rowset[$field->name] = $field;
                }
            }
        }

        return $rowset;
    }

    public static function fetchColumnFieldsForType($itemType = null, $keyAsId = true)
    {
        $rowset = self::fetchRowset($itemType, $keyAsId);

        if (count($rowset) === 0) {
            return [];
        }

        $columnsFields = [];
        foreach ($rowset as $customField) {
            $isColumn = $customField->isColumn();
            if ($isColumn) {
                if ($keyAsId) {
                    $columnsFields[$customField->id] = $customField;
                } else {
                    $columnsFields[$customField->name] = $customField;
                }
            }
        }

        return $columnsFields;
    }

    /**
     * @param array $filter
     *
     * @return array
     */
    public static function getCountries($filter = [])
    {
        $countries = [
            'AFG' => 'Afghanistan',
            'ALB' => 'Albania',
            'DZA' => 'Algeria',
            'ASM' => 'American Samoa',
            'AND' => 'Andorra',
            'AGO' => 'Angola',
            'AIA' => 'Anguilla',
            'ATA' => 'Antarctica',
            'ATG' => 'Antigua and Barbuda',
            'ARG' => 'Argentina',
            'ARM' => 'Armenia',
            'ABW' => 'Aruba',
            'AUS' => 'Australia',
            'AUT' => 'Austria',
            'AZE' => 'Azerbaijan',
            'BHS' => 'Bahamas',
            'BHR' => 'Bahrain',
            'BGD' => 'Bangladesh',
            'BRB' => 'Barbados',
            'BLR' => 'Belarus',
            'BEL' => 'Belgium',
            'BLZ' => 'Belize',
            'BEN' => 'Benin',
            'BMU' => 'Bermuda',
            'BTN' => 'Bhutan',
            'BOL' => 'Bolivia',
            'BIH' => 'Bosnia and Herzegovina',
            'BWA' => 'Botswana',
            'BRA' => 'Brazil',
            'IOT' => 'British Indian Ocean Territory',
            'VGB' => 'British Virgin Islands',
            'BRN' => 'Brunei',
            'BGR' => 'Bulgaria',
            'BFA' => 'Burkina Faso',
            'BDI' => 'Burundi',
            'KHM' => 'Cambodia',
            'CMR' => 'Cameroon',
            'CAN' => 'Canada',
            'CPV' => 'Cape Verde',
            'CYM' => 'Cayman Islands',
            'CAF' => 'Central African Republic',
            'TCD' => 'Chad',
            'CHL' => 'Chile',
            'CHN' => 'China',
            'CXR' => 'Christmas Island',
            'CCK' => 'Cocos Islands',
            'COL' => 'Colombia',
            'COM' => 'Comoros',
            'COK' => 'Cook Islands',
            'CRI' => 'Costa Rica',
            'HRV' => 'Croatia',
            'CUB' => 'Cuba',
            'CUW' => 'Curacao',
            'CYP' => 'Cyprus',
            'CZE' => 'Czech Republic',
            'COD' => 'Democratic Republic of the Congo',
            'DNK' => 'Denmark',
            'DJI' => 'Djibouti',
            'DMA' => 'Dominica',
            'DOM' => 'Dominican Republic',
            'TLS' => 'East Timor',
            'ECU' => 'Ecuador',
            'EGY' => 'Egypt',
            'SLV' => 'El Salvador',
            'GNQ' => 'Equatorial Guinea',
            'ERI' => 'Eritrea',
            'EST' => 'Estonia',
            'ETH' => 'Ethiopia',
            'FLK' => 'Falkland Islands',
            'FRO' => 'Faroe Islands',
            'FJI' => 'Fiji',
            'FIN' => 'Finland',
            'FRA' => 'France',
            'PYF' => 'French Polynesia',
            'GAB' => 'Gabon',
            'GMB' => 'Gambia',
            'GEO' => 'Georgia',
            'DEU' => 'Germany',
            'GHA' => 'Ghana',
            'GIB' => 'Gibraltar',
            'GRC' => 'Greece',
            'GRL' => 'Greenland',
            'GRD' => 'Grenada',
            'GUM' => 'Guam',
            'GTM' => 'Guatemala',
            'GGY' => 'Guernsey',
            'GIN' => 'Guinea',
            'GNB' => 'GuineaBissau',
            'GUY' => 'Guyana',
            'HTI' => 'Haiti',
            'HND' => 'Honduras',
            'HKG' => 'Hong Kong',
            'HUN' => 'Hungary',
            'ISL' => 'Iceland',
            'IND' => 'India',
            'IDN' => 'Indonesia',
            'IRN' => 'Iran',
            'IRQ' => 'Iraq',
            'IRL' => 'Ireland',
            'IMN' => 'Isle of Man',
            'ISR' => 'Israel',
            'ITA' => 'Italy',
            'CIV' => 'Ivory Coast',
            'JAM' => 'Jamaica',
            'JPN' => 'Japan',
            'JEY' => 'Jersey',
            'JOR' => 'Jordan',
            'KAZ' => 'Kazakhstan',
            'KEN' => 'Kenya',
            'KIR' => 'Kiribati',
            'XKX' => 'Kosovo',
            'KWT' => 'Kuwait',
            'KGZ' => 'Kyrgyzstan',
            'LAO' => 'Laos',
            'LVA' => 'Latvia',
            'LBN' => 'Lebanon',
            'LSO' => 'Lesotho',
            'LBR' => 'Liberia',
            'LBY' => 'Libya',
            'LIE' => 'Liechtenstein',
            'LTU' => 'Lithuania',
            'LUX' => 'Luxembourg',
            'MAC' => 'Macau',
            'MKD' => 'Macedonia',
            'MDG' => 'Madagascar',
            'MWI' => 'Malawi',
            'MYS' => 'Malaysia',
            'MDV' => 'Maldives',
            'MLI' => 'Mali',
            'MLT' => 'Malta',
            'MHL' => 'Marshall Islands',
            'MRT' => 'Mauritania',
            'MUS' => 'Mauritius',
            'MYT' => 'Mayotte',
            'MEX' => 'Mexico',
            'FSM' => 'Micronesia',
            'MDA' => 'Moldova',
            'MCO' => 'Monaco',
            'MNG' => 'Mongolia',
            'MNE' => 'Montenegro',
            'MSR' => 'Montserrat',
            'MAR' => 'Morocco',
            'MOZ' => 'Mozambique',
            'MMR' => 'Myanmar',
            'NAM' => 'Namibia',
            'NRU' => 'Nauru',
            'NPL' => 'Nepal',
            'NLD' => 'Netherlands',
            'ANT' => 'Netherlands Antilles',
            'NCL' => 'New Caledonia',
            'NZL' => 'New Zealand',
            'NIC' => 'Nicaragua',
            'NER' => 'Niger',
            'NGA' => 'Nigeria',
            'NIU' => 'Niue',
            'PRK' => 'North Korea',
            'MNP' => 'Northern Mariana Islands',
            'NOR' => 'Norway',
            'OMN' => 'Oman',
            'PAK' => 'Pakistan',
            'PLW' => 'Palau',
            'PSE' => 'Palestine',
            'PAN' => 'Panama',
            'PNG' => 'Papua New Guinea',
            'PRY' => 'Paraguay',
            'PER' => 'Peru',
            'PHL' => 'Philippines',
            'PCN' => 'Pitcairn',
            'POL' => 'Poland',
            'PRT' => 'Portugal',
            'PRI' => 'Puerto Rico, ',
            'QAT' => 'Qatar',
            'COG' => 'Republic of the Congo',
            'REU' => 'Reunion',
            'ROU' => 'Romania',
            'RUS' => 'Russia',
            'RWA' => 'Rwanda',
            'BLM' => 'Saint Barthelemy',
            'SHN' => 'Saint Helena',
            'KNA' => 'Saint Kitts and Nevis',
            'LCA' => 'Saint Lucia',
            'MAF' => 'Saint Martin',
            'SPM' => 'Saint Pierre and Miquelon',
            'VCT' => 'Saint Vincent and the Grenadines',
            'WSM' => 'Samoa',
            'SMR' => 'San Marino',
            'STP' => 'Sao Tome and Principe',
            'SAU' => 'Saudi Arabia',
            'SEN' => 'Senegal',
            'SRB' => 'Serbia',
            'SYC' => 'Seychelles',
            'SLE' => 'Sierra Leone',
            'SGP' => 'Singapore',
            'SXM' => 'Sint Maarten',
            'SVK' => 'Slovakia',
            'SVN' => 'Slovenia',
            'SLB' => 'Solomon Islands',
            'SOM' => 'Somalia',
            'ZAF' => 'South Africa',
            'KOR' => 'South Korea',
            'SSD' => 'South Sudan',
            'ESP' => 'Spain',
            'LKA' => 'Sri Lanka',
            'SDN' => 'Sudan',
            'SUR' => 'Suriname',
            'SJM' => 'Svalbard and Jan Mayen',
            'SWZ' => 'Swaziland',
            'SWE' => 'Sweden',
            'CHE' => 'Switzerland',
            'SYR' => 'Syria',
            'TWN' => 'Taiwan',
            'TJK' => 'Tajikistan',
            'TZA' => 'Tanzania',
            'THA' => 'Thailand',
            'TGO' => 'Togo',
            'TKL' => 'Tokelau',
            'TON' => 'Tonga',
            'TTO' => 'Trinidad and Tobago',
            'TUN' => 'Tunisia',
            'TUR' => 'Turkey',
            'TKM' => 'Turkmenistan',
            'TCA' => 'Turks and Caicos Islands',
            'TUV' => 'Tuvalu',
            'VIR' => 'U.S. Virgin Islands',
            'UGA' => 'Uganda',
            'UKR' => 'Ukraine',
            'ARE' => 'United Arab Emirates',
            'GBR' => 'United Kingdom',
            'USA' => 'United States',
            'URY' => 'Uruguay',
            'UZB' => 'Uzbekistan',
            'VUT' => 'Vanuatu',
            'VAT' => 'Vatican',
            'VEN' => 'Venezuela',
            'VNM' => 'Vietnam',
            'WLF' => 'Wallis and Futuna',
            'ESH' => 'Western Sahara',
            'YEM' => 'Yemen',
            'ZMB' => 'Zambia',
            'ZWE' => 'Zimbabwe',
        ];

        if ( ! empty($filter)) {
            $newCountriesList = [];

            foreach ($countries as $code => $country) {
                if (in_array($code, $filter)) {
                    $newCountriesList[$code] = $country;
                }
            }

            $countries = $newCountriesList;
        }

        return $countries;
    }
}
