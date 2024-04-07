<?php

namespace WpBase\FindCoRating;

class WpBaseController {

    public static $config;
    private $controllers;

    function __construct($config) {

        // Set WP Base config
        self::$config = array_merge(
            $config,
            [
                'controllers' => [
                    'path' => dirname(__FILE__) .'/controllers/',
                    'restricted' => [
                        '.',
                        '..',
                        'index',
                    ],
                    'loadWhen' => [
                        'SinglePost' => function() {
                            return true;
                        },
                        'Post' => function() {
                            return is_admin();
                        }
                    ],
                ],
            ]
        );

        // Define Controllers Container
        $this->controllers = [];

        // Init Controllers
        $this->init();
    }

    /**
     * Initializes the WordPress Base Controller.
     *
     * This method is responsible for triggering the autoload process for WordPress controllers.
     *
     * @return void
     */
    function init() {

        // Autoload WP Controllers
        $this->autoload();
    }

    /**
     * Autoloads the controllers specified in the configuration.
     *
     * This method scans the directory specified in the configuration for controller scripts.
     * It then iterates over each script, checks if it should be loaded based on the configuration,
     * and if so, requires the script and instantiates the controller.
     *
     * @return void
     */
    function autoload() {
        if (
            empty(self::$config['controllers']) ||
            empty(self::$config['controllers']['path'])
        ) { return false; }

        $controllersDir = scandir(self::$config['controllers']['path']);

        if (
            empty($controllersDir) ||
            count($controllersDir) <= 2
        ) { return false; }
        
        foreach ($controllersDir as $controllerScript) {

            $controllerClassName = explode('.php', $controllerScript)[0];

            if (
                in_array($controllerClassName, self::$config['controllers']['restricted']) ||
                (
                    !empty(self::$config['controllers']['loadWhen'][$controllerClassName]) &&
                    empty(self::$config['controllers']['loadWhen'][$controllerClassName]())
                )
            ) { continue; }

            $controllerPath = self::$config['controllers']['path'] . $controllerScript;

            if (
                !file_exists($controllerPath) ||
                !empty($this->controllers[$controllerClassName])
            ) { continue; }

            require_once $controllerPath;
                
            $controllerClass = '\\WpBase\\FindCoRating\\'. $controllerClassName;

            $this->controllers[$controllerClassName] = new $controllerClass(self::$config);
        }
    }

    /**
     * Retrieves a specific controller from the controllers array.
     *
     * @param string $controllerName The name of the controller to retrieve.
     * @return mixed The controller if it exists, otherwise null.
     */
    function getController($controllerName) {
        return (
            !empty($this->controllers[$controllerName]) ?
            $this->controllers[$controllerName] :
            null
        );
    }

    /**
     * Converts tags in a HTML string to their corresponding values.
     *
     * @param array $tags An associative array of tags and their corresponding values.
     * @param string $html The HTML string in which to replace the tags.
     * @return string The HTML string with the tags replaced by their values.
     */
    function convertTagsToHtml($tags = [], $html) {
        if (empty($tags)) { return $html; }

        foreach ($tags as $tagKey => $tagValue) {
            $tag = strtoupper('['. $tagKey .']');
            $html = str_replace($tag, $tagValue, $html);
        }

        return $html;
    }
}