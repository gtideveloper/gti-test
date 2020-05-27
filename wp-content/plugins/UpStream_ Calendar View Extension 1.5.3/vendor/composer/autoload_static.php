<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit0271056cc6bcc9b54945c8a51a233320
{
    public static $files = array (
        'c92bf23a32412037ecdc51806b458c36' => __DIR__ . '/..' . '/alledia/edd-sl-plugin-updater/EDD_SL_Plugin_Updater.php',
    );

    public static $prefixLengthsPsr4 = array (
        'A' => 
        array (
            'Alledia\\Builder\\' => 16,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Alledia\\Builder\\' => 
        array (
            0 => __DIR__ . '/..' . '/alledia/wordpress-plugin-builder/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit0271056cc6bcc9b54945c8a51a233320::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit0271056cc6bcc9b54945c8a51a233320::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
