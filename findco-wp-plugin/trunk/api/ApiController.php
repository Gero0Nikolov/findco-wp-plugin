<?php

namespace Api\FindCoRating;

class ApiController {
    private $apis;
    private $apisRestState;
    
    public static $config;
    public static $apiKey;
    public static $namespace;
    public static $errorHandlers;
    public static $currentTimestamp;

    function __construct($config) {

        // Define Client API key by Environment
        self::$apiKey = (
            !empty($config['key']) ?
            $config['key'] :
            false
        );

        // Define URI Base
        self::$namespace = (
            !empty($config['namespace']) ?
            $config['namespace'] :
            false
        );

        // Stop here in case the config is missing
        if ( 
            empty(self::$apiKey) ||
            empty(self::$namespace)
        ) { return false; }

        // Set Base APIs config
        self::$config = array_merge(
            $config,
            [
                'apisPath' => dirname(__FILE__) .'/apis/',
                'restrictedApis' => [
                    '.',
                    '..',
                    'index.php',
                ],
            ]
        );

        // Defined APIs container
        $this->apis = [];

        // Define APIs State
        $this->apisRestState = [];

        // Define Error Handlers
        self::$errorHandlers = [
            'default' => [
                'label' => 'Unknown Error',
                'status' => 500,
            ],
            'accessForbidden' => [
                'label' => 'Access Forbidden',
                'status' => 403,
            ],
        ];

        // Init APIs
        $this->init();
    }

    function init() {

        // Autoload API scripts
        $this->autoload();
    }

    /**
     * Autoloads the API scripts specified in the configuration.
     *
     * This method scans the directory specified in the configuration for API scripts.
     * It then iterates over each script, checks if it should be loaded based on the configuration,
     * and if so, requires the script, instantiates the API class, and generates its REST API.
     * It also merges the API class's error handlers with the global error handlers.
     *
     * @return bool False if the APIs directory is empty or only contains '.', '..', otherwise no explicit return.
     */
    function autoload() {
        if (empty(self::$config['apisPath'])) { return false; }

        $apisDir = scandir(self::$config['apisPath']);

        if (
            empty($apisDir) ||
            count($apisDir) <= 2
        ) { return false; }
        
        foreach ($apisDir as $apiScript) {
            if (in_array($apiScript, self::$config['restrictedApis'])) { continue; }

            $apiPath = self::$config['apisPath'] . $apiScript;
            $apiClassName = explode('.php', $apiScript)[0];

            if (
                !file_exists($apiPath) ||
                !empty($this->apis[$apiClassName])
            ) { continue; }

            require_once $apiPath;
                
            $apiClass = '\\Api\\FindCoRating\\'. $apiClassName;        

            $this->apis[$apiClassName] = new $apiClass();
            $this->apisRestState[$apiClassName] = $this->generateRestApi($this->apis[$apiClassName]);

            self::$errorHandlers = array_merge(
                $this->apis[$apiClassName]::$errorHandlers,
                self::$errorHandlers
            );
        }
    }

    /**
     * Generates a REST API for a specific class.
     *
     * This function checks if the class has a configuration. If it does, it trims the endpoint and namespace,
     * and registers a new REST route with the specified methods, callback, and arguments.
     * It also sets a permission callback that checks if the API key is valid.
     *
     * @param object $class The class to generate the REST API for.
     * @return mixed The result of the register_rest_route function if the class has a configuration, otherwise null.
     */
    function generateRestApi($class) {
        if (empty($class::$config)) { return null; }

        $endpoint = trim($class::$config['endpoint'], '/');
        $namespace = trim(self::$config['namespace'], '/');

        return register_rest_route($namespace, $endpoint, [
            'methods' => $class::$config['methods'],
            'callback' => [$class, $class::$config['callback']],
            'args' => !empty($class::$config['args']) ? $class::$config['args'] : [],
            'permission_callback' => function (\WP_REST_Request $request) {
                $apiKey = $request->get_param('apiKey');                

                if (
                    empty($apiKey) ||
                    !self::apiKeyCheck($apiKey)
                ) { return false; }

                return true;                
            }
        ]);
    }

    /**
     * Retrieves a specific API from the APIs array.
     *
     * @param string $apiName The name of the API to retrieve.
     * @return mixed The API if it exists in the APIs array, otherwise null.
     */
    function getApi($apiName) {
        return (
            !empty($this->apis[$apiName]) ?
            $this->apis[$apiName] :
            null
        );
    }

    /**
     * Handles errors for the API controller.
     *
     * This function logs the error code and extra information if the response is empty.
     * It then prepares the error data, builds a WP_Error object with the error data, and returns it.
     *
     * @param string $code The error code.
     * @param array $extraInfo Extra information about the error.
     * @param array $response The response to include in the error object.
     * @return \WP_Error The error object.
     */
    static function handleError($code, $extraInfo, $response = []) {
        if (empty($response)) {
          error_log($code, 0);
          error_log(json_encode($extraInfo), 0);
        }
    
        $errorData = !empty(self::$errorHandlers[$code]) ? self::$errorHandlers[$code] : self::$errorHandlers['default'];
    
        $error = new \WP_Error($code, $errorData['label'], [
          'extra' => $extraInfo,
          'response' => $response,
          'status' => $errorData['status'],
        ]);
    
        return $error;
    }

    /**
     * Handles successful API responses.
     *
     * This function builds a WP_REST_Response object with the provided response data and a 200 status code, and returns it.
     *
     * @param array $response The response data to include in the response object.
     * @return \WP_REST_Response The response object.
     */
    static function handleSuccess($response = []) {
        return new \WP_REST_Response($response, 200);
    }

    /**
     * Checks if the provided API key matches the one in the configuration.
     *
     * @param string $key The API key to check.
     * @return bool True if the provided key matches the one in the configuration, otherwise false.
     */
    static function apiKeyCheck($key) {
        return $key === self::$config['key'];
    }

    /**
     * Retrieves the current timestamp.
     *
     * This function generates the current timestamp in the 'Y-m-d H:i:s' format and stores it in the $currentTimestamp static property.
     * It then returns the current timestamp.
     *
     * @return string The current timestamp in the 'Y-m-d H:i:s' format.
     */
    static function getTimestamp() {
        self::$currentTimestamp = date('Y-m-d H:i:s');
        return self::$currentTimestamp;
    }
}

