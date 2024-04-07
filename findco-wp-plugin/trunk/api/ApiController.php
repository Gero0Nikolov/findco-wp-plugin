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
                    self::apiKeyCheck($apiKey)
                ) { return false; }

                return true;                
            }
        ]);
    }

    function getApi($apiName) {
        return (
            !empty($this->apis[$apiName]) ?
            $this->apis[$apiName] :
            null
        );
    }

    static function handleError($code, $extraInfo, $response = []) {
        // Store Errors only when the Response is empty since it's responsible for the Fields worker
        if (empty($response)) {
          error_log($code, 0);
          error_log(json_encode($extraInfo), 0);
        }
    
        // Prepare the WP Error
        $errorData = !empty(self::$errorHandlers[$code]) ? self::$errorHandlers[$code] : self::$errorHandlers['default'];
    
        // Build the Error Object
        $error = new \WP_Error($code, $errorData['label'], [
          'extra' => $extraInfo,
          'response' => $response,
          'status' => $errorData['status'],
        ]);
    
        // Return the Error
        return $error;
    }

    static function handleSuccess($response = []) {
        return new \WP_REST_Response($response, 200);
    }

    static function apiKeyCheck($key) {
        return $key === self::$config['key'];
    }

    static function getTimestamp() {
        self::$currentTimestamp = date('Y-m-d H:i:s');
        return self::$currentTimestamp;
    }
}

