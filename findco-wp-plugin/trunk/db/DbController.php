<?php

namespace Db\FindCoRating;

class DbController {
    private $controllers;
    private $apisRestState;
    
    private static $config;
    
    function __construct($config) {

        // Set Base DB config
        self::$config = array_merge(
            $config,
            [
                'controllersPath' => dirname(__FILE__) .'/controllers/',
                'restrictedControllers' => [
                    '.',
                    '..',
                    'index.php',
                ],
            ]
        );

        // Init DB
        $this->init();
    }

    function init() {

        // Autoload API scripts
        $this->autoload();

        // Run Install
        $allInstallState = $this->install();
    }

    function autoload() {
        if (empty(self::$config['controllersPath'])) { return false; }

        $controllersDir = scandir(self::$config['controllersPath']);

        if (
            empty($controllersDir) ||
            count($controllersDir) <= 2
        ) { return false; }
        
        foreach ($controllersDir as $controllerScript) {
            if (in_array($controllerScript, self::$config['restrictedControllers'])) { continue; }

            $controllerPath = self::$config['controllersPath'] . $controllerScript;
            $controllerClassName = explode('.php', $controllerScript)[0];

            if (
                !file_exists($controllerPath) ||
                !empty($this->controllers[$controllerClassName])
            ) { continue; }

            require_once $controllerPath;
                
            $controllerClass = '\\Db\\FindCoRating\\'. $controllerClassName;        

            $this->controllers[$controllerClassName] = new $controllerClass(self::$config);
        }
    }

    function install() {

        if (empty($this->controllers)) { return false; }

        $prohibitedControllers = [
        ];

        foreach ($this->controllers as $controllerName => $controller) {
            if (
                empty($controller) ||
                in_array($controllerName, $prohibitedControllers)
            ) { continue; }

            $installState = $controller->install();
        }

        if (!empty($prohibitedControllers)) {

            foreach ($prohibitedControllers as $controllerIndex => $controllerName) {

                if (!isset($this->controllers[$controllerName])) { continue; }

                $installState = $this->controllers[$controllerName]->install($this->controllers);
            }
        }

        return true;
    }

    function registerTable($table) {

        if (
            empty($table) ||
            empty($table['name']) ||
            empty($table['columns'])
        ) { return false; }

        $tableName = $table['name'];
        $tableColumns = $table['columns'];
        $tableIndexedColumns = !empty($table['indexedColumns']) ? $table['indexedColumns'] : [];

        global $wpdb;
        $charsetCollate = $wpdb->get_charset_collate();

        if ($wpdb->get_var('SHOW TABLES LIKE "'. $tableName .'"') === $tableName) { return true; }

        $columns = [];

        foreach ($tableColumns as $columnKey => $columnType) {
            $columns[] = $columnKey .' '. $columnType;
        }

        $sql = '
        CREATE TABLE [TABLENAME] (
            [COLUMNS],
            PRIMARY KEY(id)
        ) [CHARSETCOLLATE];';

        $sqlTags = [
            'tableName' => $tableName,
            'columns' => implode(', '.PHP_EOL, $columns),
            'charsetCollate' => $charsetCollate,
        ];

        foreach ($sqlTags as $tagKey => $tagValue) {
            $tag = strtoupper('['. $tagKey .']');
            $sql = str_replace($tag, $tagValue, $sql);
        }

        require_once(ABSPATH .'wp-admin/includes/upgrade.php');
        $result = dbDelta($sql);

        foreach ($tableIndexedColumns as $index) {
            $indexingSql = 'CREATE INDEX '. $index .' ON '. $tableName .' ('. $index .');';
            $indexStatus = $wpdb->query($indexingSql);
        }

        return true;
    }

    function getController($controllerName) {
        return (
            !empty($this->controllers[$controllerName]) ?
            $this->controllers[$controllerName] :
            null
        );
    }
}

