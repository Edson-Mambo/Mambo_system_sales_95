<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInite87558d66c1f21693a0ddb5066b78fdf
{
    private static $loader;

    public static function loadClassLoader($class)
    {
        if ('Composer\Autoload\ClassLoader' === $class) {
            require __DIR__ . '/ClassLoader.php';
        }
    }

    /**
     * @return \Composer\Autoload\ClassLoader
     */
    public static function getLoader()
    {
        if (null !== self::$loader) {
            return self::$loader;
        }

        require __DIR__ . '/platform_check.php';

        spl_autoload_register(array('ComposerAutoloaderInite87558d66c1f21693a0ddb5066b78fdf', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader(\dirname(__DIR__));
        spl_autoload_unregister(array('ComposerAutoloaderInite87558d66c1f21693a0ddb5066b78fdf', 'loadClassLoader'));

        require __DIR__ . '/autoload_static.php';
        call_user_func(\Composer\Autoload\ComposerStaticInite87558d66c1f21693a0ddb5066b78fdf::getInitializer($loader));

        $loader->register(true);

        return $loader;
    }
}
