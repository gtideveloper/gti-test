<?php

namespace UpStream\Plugins\EmailNotifications;

// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

/**
 * Class responsible for load all package classes under the PSR-4 pattern.
 *
 * @package     UpStream\Plugins\EmailNotifications
 * @author      UpStream <https://upstreamplugin.com>
 * @copyright   Copyright (c) 2018 UpStream Project Management
 * @license     GPL-3
 * @since       1.0.0
 */
class Psr4Autoloader
{
    /**
     * Associative array where the key is a namespace prefix and the value
     * is an array of base directories for classes in that namespace.
     *
     * @since   1.0.0
     * @access  protected
     * @static
     *
     * @var     array
     */
    protected static $prefixes = [];

    /**
     * Associative array of prefixes for loading specialized camelCase classes
     * where Uppercase letters in the class name indicate directory structure
     *
     * @since   1.0.0
     * @access  protected
     * @static
     *
     * @var     array
     */
    protected static $camelPrefixes = [];

    /**
     * @since   1.0.0
     * @access  protected
     * @static
     *
     * @var     Psr4Autoloader
     */
    protected static $instance = null;

    /**
     * Register a PSR-4 namespace.
     *
     * @since   1.0.0
     * @static
     *
     * @param   string $prefix    The namespace prefix.
     * @param   string $baseDir   A base directory for class files in the namespace.
     * @param   bool   $prepend   If true, prepend the base directory to the stack instead of
     *                            appending it; this causes it to be searched first rather than last.
     */
    public static function register($prefix = null, $baseDir = null, $prepend = false)
    {
        if ($prefix === null || $baseDir === null) {
            // Recognize old-style instantiations for backward compatibility.
            return;
        }

        if (count(self::$prefixes) == 0) {
            // Register function on first call.
            static::registerLoader('loadClass');
        }

        // Normalize namespace prefix.
        $prefix = trim($prefix, '\\') . '\\';

        // Normalize the base directory with a trailing separator.
        $baseDir = rtrim($baseDir, '\\/') . '/';

        // Initialise the namespace prefix array.
        if (empty(self::$prefixes[$prefix])) {
            self::$prefixes[$prefix] = [];
        }

        // Retain the base directory for the namespace prefix.
        if ($prepend) {
            array_unshift(self::$prefixes[$prefix], $baseDir);
        } else {
            array_push(self::$prefixes[$prefix], $baseDir);
        }
    }

    /**
     * Register a new loader.
     *
     * @since   1.0.0
     * @access  protected
     * @static
     *
     * @param   string $method The method name which will be called.
     */
    protected static function registerLoader($method)
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        spl_autoload_register([static::$instance, $method]);
    }

    /**
     * Register a base directory for classes organized using camelCase.
     * Class names beginning with the prefix will be automatically loaded
     * if there is a matching file in the directory tree starting with $baseDir.
     * File names and directory names are all expected to be lower case.
     *
     * @since   1.0.0
     * @static
     *
     * @throws  \Exception when $baseDir doesn't seem to exist.
     *
     * @param   string $prefix  The class name prefix.
     * @param   string $baseDir The file name base directory.
     */
    public static function registerCamelBase($prefix, $baseDir)
    {
        if ( ! is_dir($baseDir)) {
            throw new \Exception("Cannot register '{$prefix}'. The requested base directory does not exist!'");
        }

        if (count(self::$camelPrefixes) == 0) {
            // Register function on first call.
            static::registerLoader('loadCamelClass');
        }

        if (empty(self::$camelPrefixes[$prefix])) {
            self::$camelPrefixes[$prefix] = $baseDir;
        }
    }

    /**
     * Loads the class file for a given class name.
     *
     * @since   1.0.0
     * @access  protected
     *
     * @param   string $class The fully-qualified class name.
     *
     * @return  mixed           The mapped file name on success, or boolean false on failure.
     */
    protected function loadClass($class)
    {
        $prefixes  = explode('\\', $class);
        $className = '';
        while ($prefixes) {
            $className = array_pop($prefixes) . $className;
            $prefix    = join('\\', $prefixes) . '\\';

            if ($filePath = $this->loadMappedFile($prefix, $className)) {
                return $filePath;
            }
            $className = '\\' . $className;
        }

        // never found a mapped file
        return false;
    }

    /**
     * Load the mapped file for a namespace prefix and class.
     *
     * @since   1.0.0
     * @access  protected
     *
     * @param   string $prefix    The namespace prefix.
     * @param   string $className The relative class name.
     *
     * @return  mixed               False if no mapped file can be loaded | path that was loaded.
     */
    protected function loadMappedFile($prefix, $className)
    {
        // Are there any base directories for this namespace prefix?
        if (isset(self::$prefixes[$prefix]) === false) {
            return false;
        }

        // Look through base directories for this namespace prefix.
        foreach (self::$prefixes[$prefix] as $baseDir) {
            $path = $baseDir . str_replace('\\', '/', $className) . '.php';

            if (is_file($path)) {
                require_once $path;

                return $path;
            }
        }

        // Never found it.
        return false;
    }

    /**
     * Autoload a class using the camelCase structure.
     * In case the file/class doesn't exist, false will be returned.
     *
     * @since   1.0.0
     * @access  protected
     *
     * @param   string $class The class name to be loaded.
     *
     * @return  bool/string         The class's path being loaded or false if the load fails.
     */
    protected function loadCamelClass($class)
    {
        if ( ! class_exists($class)) {
            foreach (self::$camelPrefixes as $prefix => $baseDir) {
                if (strpos($class, $prefix) === 0) {
                    $parts = preg_split('/(?<=[a-z])(?=[A-Z])/x', substr($class, strlen($prefix)));

                    $file     = strtolower(join('/', $parts));
                    $filePath = $baseDir . '/' . $file . '.php';

                    if (is_file($filePath)) {
                        require_once $filePath;

                        return $filePath;
                    }
                }
            }
        }

        // No file found.
        return false;
    }
}
