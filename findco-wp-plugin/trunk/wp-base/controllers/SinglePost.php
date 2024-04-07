<?php

namespace WpBase\FindCoRating;

class SinglePost extends WpBaseController {

    public static $config;
    
    function __construct($config) {
    
        // Set Base SinglePost Config
        self::$config = $config;

        // Append the FE Voting Box
        add_filter('the_content', [$this, 'appendVotingBox']);
    }

    function appendVotingBox($content) {
        if (!is_single()) { return $content; }

        global $FindCoRating;

        if (empty($FindCoRating)) { return $content; }

        $dbModule = $FindCoRating->getModule('DbController');

        $voteTable = $dbModule->getController('Vote');

        $postId = get_the_ID();

        $userVoted = $voteTable->userVoted($postId);

        $voteResults = [
            'positive' => 0,
            'negative' => 0,
        ];

        if ($userVoted) {
            $voteResults = $voteTable->getVoteResults($postId);
        }

        $votingBoxHtml = '
        <div class="findco-voting-box">

            <div class="findco-voting-box-title">[TITLE]</div>
            <button
                id="findco-vote-up"
                class="findco-vote-button findco-vote-up [VOTEUPSELECTEDCLASS]"
                data-type="1"
                data-post-id="[POSTID]"
            >[VOTEUPTEXT]</button>
            <button
                id="findco-vote-down"
                class="findco-vote-button findco-vote-down"
                data-type="0"
                data-post-id="[POSTID]"
            >[VOTEDOWNTEXT]</button>
        </div>
        ';

        $tags = [
            'title' => (
                !$userVoted ?
                'Was this article helpful?' :
                'Thank you for your feedback.'
            ),
            'voteUpSelectedClass' => (
                !$userVoted ?
                '' :
                'findco-vote-selected'
            ),
            'voteUpText' => (
                !$userVoted ?
                'Yes' :
                $voteResults['positive']
            ),
            'voteDownSelectedClass' => (
                !$userVoted ?
                '' :
                'findco-vote-selected'
            ),
            'voteDownText' => (
                !$userVoted ?
                'No' :
                $voteResults['negative']
            ),
            'postId' => $postId,
        ];

        $content .= $this->convertTagsToHtml($tags, $votingBoxHtml);

        return $content;
    }
}

