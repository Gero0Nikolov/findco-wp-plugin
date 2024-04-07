<?php

namespace FindCoRating;

class FindCoRating {

    public $config;
    public $controllers;

    function __construct() {
        
        $apiKey = '2e1b3d9d2402d61590ea379edb1203e7';

        // Setup
        $this->config = [
            'resourceVersion' => (
                strpos($_SERVER['HTTP_HOST'], 'localhost') !== false ||
                // strpos($_SERVER['HTTP_HOST'], '.kinsta.cloud') !== false ||
                strpos($_SERVER['HTTP_HOST'], 'loc.') !== false ?
                date('YmdHis') :
                '1.0.0'
            ),
            'base' => [
                'path' => dirname(__FILE__),
                'url' => plugin_dir_url(__FILE__),
            ],
            'autoload' => [
                'api' => [
                    'ApiController',
                ],
                'db' => [
                    'DbController',
                ],
                'wp-base' => [
                    'WpBaseController',
                ],
            ],
            'moduleConfig' => [
                'api' => [
                    'key' => $apiKey,
                    'defaultPrefix' => '/wp-json',
                    'namespace' => '/find-co/v1/',
                    'newLine' => PHP_EOL,
                    'tab' => '  ',
                    'parametersBasedOnRequestType' => [
                        'get' => 'query',
                        'post' => 'path',
                    ],
                    'user' => [
                        'logged' => false,
                        'id' => null,
                    ],
                ],
                'db' => [
                    'tablePrefix' => 'fc_',
                ],
                'wp-base' => [],
            ],
            'resources' => [
                'scripts' => plugin_dir_url(__FILE__) .'resources/dist/scripts/',
                'styles' => plugin_dir_url(__FILE__) .'resources/dist/styles/',
                'assets' => [
                    'img' => plugin_dir_url(__FILE__) .'resources/assets/img/',
                ],
            ],
        ];

        // Controllers Container
        $this->controllers = [];
       
        // Autoload
        add_action('init', [$this, 'autoload'], 3);

        // Load Public Resources
        add_action('wp_enqueue_scripts', [$this, 'loadPublicResources']);
    }

    function __destruct() {}

    /**
     * Autoloads the controllers of the modules specified in the configuration.
     *
     * @return void
     */
    function autoload() {

        if (empty($this->config['autoload'])) { return false; }
        
        foreach ($this->config['autoload'] as $moduleName => $moduleControllers) {
            if (empty($moduleControllers)) { continue; }

            $modulePath = $this->config['base']['path'] .'/'. $moduleName .'/';

            foreach ($moduleControllers as $controllerName) {
                $controllerPath = $modulePath . $controllerName .'.php';

                if (
                    !file_exists($controllerPath) ||
                    !empty($this->controllers[$controllerName])
                ) { continue; }
                
                require_once $controllerPath;
                
                $namespace = [];
                $namespaceArr = explode('-', strtolower($moduleName));
                foreach ($namespaceArr as $namespacePart) {
                    $namespace[] = ucfirst($namespacePart);
                }
                $namespaceName = implode('', $namespace);
                
                $controller = '\\'. $namespaceName .'\\FindCoRating\\'. $controllerName;

                $moduleConfig = (
                    !empty($this->config['moduleConfig'][$moduleName]) ?
                    $this->config['moduleConfig'][$moduleName] :
                    []
                );

                if (
                    $moduleName === 'api' ||
                    $moduleName === 'gpt'
                ) {
                    $moduleConfig['user']['logged'] = \is_user_logged_in();
                    $moduleConfig['user']['id'] = \get_current_user_id();
                }

                $this->controllers[$controllerName] = new $controller($moduleConfig);
            }
        }
    }

    /**
     * Autoloads the controllers of the modules specified in the configuration.
     *
     * @return void
     */
    function loadPublicResources() {
        $jsBasePath = plugins_url('/resources/dist/scripts/', __FILE__);
        $jsResources = [
            'base' => $jsBasePath,
            'autoload' => $jsBasePath .'autoload/',
            'dependencies' => $jsBasePath .'dependencies/',
        ];

        $stylesPath = plugins_url('/resources/dist/styles/', __FILE__);
        
        wp_enqueue_script('fcr-core-script', $jsResources['base'] .'core.js', ['jquery'], $this->config['resourceVersion'], true);
        wp_localize_script('fcr-core-script', 'fcrPublicConfig', [
            'scripts' => [
                'dir' => $jsResources['autoload'],
                'dependenciesDir' => $jsResources['dependencies'],
                'version' => $this->config['resourceVersion'],
                'apiUrl' => (
                    get_site_url() .
                    '/wp-json/find-co/v1/'
                ),
                'apiKey' => $this->config['moduleConfig']['api']['key'],
            ],
        ]);

        // Load Global Style
        $loadGlobalStyle = true;

        // Load Global Style if needed
        if ($loadGlobalStyle) {
            wp_enqueue_style('fcr-global-style', $stylesPath .'global.css', [], $this->config['resourceVersion'], 'all');
        }
    }

    /**
     * Retrieves a specific module from the controllers array.
     *
     * @param string $moduleName The name of the module to retrieve.
     * @return mixed The module if it exists, otherwise an empty array.
     */
    public function getModule($moduleName){
        return (
            !empty($this->controllers[$moduleName]) ?
            $this->controllers[$moduleName] :
            []
        );
    }
}

$FindCoRating = new FindCoRating;