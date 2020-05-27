<?php

namespace UpStream\Plugins\CustomFields\Fields;

// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

/**
 * @package     UpStream\Plugins\CustomFields
 * @subpackage  Fields
 * @author      UpStream <https://upstreamplugin.com>
 * @copyright   Copyright (c) 2018 UpStream Project Management
 * @license     GPL-3
 * @since       1.0.0
 */
class File extends Field
{
    /**
     * Current field type identifier.
     *
     * @since   1.0.0
     *
     * @var     string $type
     */
    public $type = 'file';

    /**
     * The upload button text. Supported only on admin.
     *
     * @since   1.0.0
     *
     * @var     string $buttonText
     */
    public $buttonText = "";

    /**
     * Defines whether the field can be filterable on frontend or not.
     *
     * @since   1.2.0
     *
     * @var     bool $filterable
     */
    public $filterable = false;

    /**
     * Class constructor.
     *
     * @since   1.0.0
     *
     * @param   mixed $post Custom Field ID or post object.
     */
    public function __construct($post)
    {
        parent::__construct($post);

        $buttonText = (string)$this->getMeta('args:btn_text');
        if (strlen($buttonText) > 0) {
            $this->buttonText = $buttonText;
        }

        $this->filterable = false;
    }

    public function isValid($type, $id, $value)
    {
        if (get_attached_file($value) === false) {
            return sprintf(__('File ID %s is invalid.', 'upstream'), $value);
        }

        return true;
    }

    public function loadFromString($type, $id, $str)
    {
        $value = $str;
        $res = 0;

        if (strlen((string)$value) > 0) {
            global $wpdb;

            $attachment = $wpdb->get_row(sprintf(
                'SELECT `ID` AS `id`, `post_title` AS `title`, `post_mime_type` AS `mime_type`
                                       FROM `%s`
                                      WHERE `guid` = "%s"',
                $wpdb->prefix . 'posts',
                $value
            ));

            if (!empty($attachment)) {
                $res = $attachment->id;
            }

            unset($attachment);
        }

        return $res;
    }

    public function storeToObject($type, $id, $inputValue)
    {
        $res = wp_get_attachment_url($inputValue);
        if ($res === false) {
            return '';
        }

        return $res;
    }

    /**
     * Retrieve an array of settings based on CMB2 patterns.
     *
     * @since   1.0.0
     *
     * @return  array
     */
    public function toCmb2()
    {
        $data = parent::toCmb2();

        if (strlen($this->buttonText) > 0) {
            $data['text'] = [
                'add_upload_file_text' => $this->buttonText,
            ];
        }

        $data = apply_filters('upstream.' . UP_CUSTOM_FIELDS_ALIAS . ':field_' . $this->type . '.cmb2_args', $data,
            $this);

        return $data;
    }

    /**
     * Checks if the field is filterable.
     *
     * @since   1.2.0
     *
     * @return  bool
     */
    public function isFilterable()
    {
        return false;
    }
}
