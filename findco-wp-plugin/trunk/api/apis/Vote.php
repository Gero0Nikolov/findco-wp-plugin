<?php

namespace Api\FindCoRating;

class Vote extends ApiController {
    public static $config;
    public static $errorHandlers;

    function __construct() {

        // Set Defaults
        self::$config = [
            'endpoint' => 'vote',
            'methods'  => \WP_REST_Server::CREATABLE,
            'callback' => 'voteApply',
            'args'     => [
                'apiKey' => [
                    'required' => true,
                    'type' => 'string',
                    'description' => 'API Key.'
                ],
                'voteType' => [
                    'required' => true,
                    'type' => 'string',
                    'description' => 'The vote type; Can be: 0 - Negative; 1 - Positive',
                ],
                'postId' => [
                    'required' => true,
                    'type' => 'integer',
                    'description' => 'The post ID to vote on.',
                ],
            ],
        ];

        // Define Error Handlers
        self::$errorHandlers = [
            'success' => [
                'label' => 'OK',
                'status' => 200,
                'schema' => [
                    'key' => 'voteSuccess',
                    'type' => 'object',
                    'properties' => [
                        'positiveVotes' => [
                            'type' => 'integer',
                            'description' => 'The total number of positive votes.',
                        ],
                        'negativeVotes' => [
                            'type' => 'integer',
                            'description' => 'The total number of negative votes.',
                        ],
                    ],
                ],
            ],
        ];
    }

    function voteApply($request) {
        // TODO: Connect the Vote API
        return self::handleSuccess([
            'voteState' => true,
        ]);
    }
}