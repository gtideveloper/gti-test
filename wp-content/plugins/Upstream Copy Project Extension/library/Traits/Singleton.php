<?php

namespace UpStream\Plugins\CopyProject\Traits;

// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

/**
 * Trait that abstracts the Singleton design pattern.
 *
 * @package     UpStream\Plugins\CopyProject
 * @subpackage  Traits
 * @author      UpStream <https://upstreamplugin.com>
 * @copyright   Copyright (c) 2018 UpStream Project Management
 * @license     GPL-3
 * @since       1.0.0
 */
trait Singleton
{
    /**
     * @since   1.0.0
     * @access  private
     * @static
     *
     * @var     \ReflectionClass $instance The singleton class's instance.
     */
    private static $instance = null;

    /**
     * Initializes the singleton if it's not loaded yet.
     *
     * @since   1.0.0
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
     * Retrieve the singleton instance.
     * If the singleton it's not loaded, it will be initialized first.
     *
     * @return  \ReflectionClass
     * @since   1.0.0
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
     * Prevent the class instance being serialized.
     *
     * @throws  \Exception
     * @since   1.0.0
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
     * @since   1.0.0
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
     * @since   1.0.0
     * @final
     */
    final public function __clone()
    {
        // Do nothing.
    }
}
