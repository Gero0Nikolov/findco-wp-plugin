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
                    ],
                ],
            ]
        );

        // Define Controllers Container
        $this->controllers = [];

        // Init Controllers
        $this->init();
    }

    function init() {

        // Autoload WP Controllers
        $this->autoload();
    }

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

    function getController($controllerName) {
        return (
            !empty($this->controllers[$controllerName]) ?
            $this->controllers[$controllerName] :
            null
        );
    }

    function convertTagsToHtml($tags = [], $html) {
        if (empty($tags)) { return $html; }

        foreach ($tags as $tagKey => $tagValue) {
            $tag = strtoupper('['. $tagKey .']');
            $html = str_replace($tag, $tagValue, $html);
        }

        return $html;
    }
}