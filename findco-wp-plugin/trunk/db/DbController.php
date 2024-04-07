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

    /**
     * Initializes the Database Controller.
     *
     * This method is responsible for triggering the autoload process for API scripts and running the install process.
     *
     * @return void
     */
    function init() {

        // Autoload API scripts
        $this->autoload();

        // Run Install
        $allInstallState = $this->install();
    }

    /**
     * Autoloads the controllers specified in the configuration.
     *
     * This method scans the directory specified in the configuration for controller scripts.
     * It then iterates over each script, checks if it should be loaded based on the configuration,
     * and if so, requires the script and instantiates the controller.
     *
     * @return bool False if the controllers directory is empty or only contains '.', '..', otherwise no explicit return.
     */
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

    /**
     * Installs the controllers specified in the configuration.
     *
     * This method iterates over each controller in the controllers array, checks if it should be installed based on the configuration,
     * and if so, calls the install method of the controller.
     * It also handles prohibited controllers separately, checking if they exist in the controllers array and if so, calls their install method.
     *
     * @return bool False if the controllers array is empty, otherwise true.
     */
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

    /**
     * Registers a new table in the database.
     *
     * This function checks if the table already exists, and if not, it creates a new table with the specified name and columns.
     * It also creates indices for the specified columns.
     *
     * @param array $table An associative array containing the name of the table, the columns, and the indexed columns.
     * @return bool False if the table details are incomplete or if the table already exists, otherwise true.
     */
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
}

