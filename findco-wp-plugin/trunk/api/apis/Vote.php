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
            'postIdMissing' => [
                'label' => 'Thread Slug is missing.',
                'status' => 404,
            ],
            'voteTypeMissing' => [
                'label' => 'Vote Type is missing.',
                'status' => 404,
            ],
            'voteFailed' => [
                'label' => 'Vote failed.',
                'status' => 400,
            ],
        ];
    }
    
    /**
     * Applies a vote to a specific post.
     *
     * This function retrieves the post ID and vote type from the request. If either is missing, it returns an error.
     * It then retrieves the Vote controller from the DbController module and applies the vote.
     * If the vote fails, it returns an error. Otherwise, it retrieves the vote results and returns a success response.
     *
     * @param \WP_REST_Request $request The request object.
     * @return mixed A WP_Error object if an error occurred, otherwise a WP_REST_Response object.
     */
    function voteApply($request) {
        $postId = $request->get_param('postId');
        $voteType = $request->get_param('voteType');

        if (empty($postId)) {
            return self::handleError('postIdMissing', [], []);
        }

        if (empty($voteType) && $voteType !== '0') {
            return self::handleError('voteTypeMissing', [], []);
        }

        global $FindCoRating;

        $dbModule = $FindCoRating->getModule('DbController');
        $voteTable = $dbModule->getController('Vote');

        $vote = $voteTable->vote($postId, $voteType);

        if (!$vote) {
            return self::handleError('voteFailed', [], []);
        }

        $voteResults = $voteTable->getVoteResults($postId);

        $text = [
            'title' => 'Thank you for your feedback.',
            'voteUpText' => $voteResults['positive'] .'%',
            'voteDownText' => $voteResults['negative'] .'%',
        ];

        return self::handleSuccess([
            'voteState' => true,
            'text' => $text,
            'voteType' => $voteType,
        ]);
    }
}