<?php

namespace UpStream\Plugins\FrontendEdit\Traits;

// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

/**
 * Trait that abstracts the Singleton design pattern.
 *
 * @package     UpStream\Plugins\FrontendEdit
 * @subpackage  Traits
 * @author      UpStream <https://upstreamplugin.com>
 * @copyright   Copyright (c) 2018 UpStream Project Management
 * @license     GPL-3
 * @since       1.3.0
 */
trait Singleton
{
    /**
     * @var     \ReflectionClass $instance The singleton class's instance.
     *
     * @since   1.3.0
     * @access  private
     * @static
     */
    private static $instance = null;

    /**
     * Retrieve the singleton instance.
     * If the singleton it's not loaded, it will be initialized first.
     *
     * @return  \ReflectionClass
     * @since   1.3.0
     * @static
     *
     */
    public static function getInstance()
    {
        // Ensure the singleton is loaded.
        self::instantiate();

        return self::$instance;
    }

    /**
     * Initializes the singleton if it's not loaded yet.
     *
     * @since   1.3.0
     * @static
     * @final
     *
     * @uses    \ReflectionClass
     */
    final public static function instantiate()
    {
        if (empty(self::$instance)) {
            $reflection     = new \ReflectionClass(__CLASS__);
            self::$instance = $reflection->newInstanceArgs(func_get_args());
        }
    }

    /**
     * Prevent the class instance being serialized.
     *
     * @throws  \Exception
     * @since   1.3.0
     * @final
     *
     */
    final public function __sleep()
    {
        throw new \Exception("You cannot serialize a singleton.");
    }

    /**
     * Prevent the class instance being unserialized.
     *
     * @throws  \Exception
     * @since   1.3.0
     * @final
     *
     */
    final public function __wakeup()
    {
        throw new \Exception("You cannot unserialize a singleton.");
    }

    /**
     * Prevent the class instance being cloned.
     *
     * @since   1.3.0
     * @final
     */
    final public function __clone()
    {
        // Do nothing.
    }
}
