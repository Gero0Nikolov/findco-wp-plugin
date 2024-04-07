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

        if ($userVoted !== null) {
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
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM164.1 325.5C182 346.2 212.6 368 256 368s74-21.8 91.9-42.5c5.8-6.7 15.9-7.4 22.6-1.6s7.4 15.9 1.6 22.6C349.8 372.1 311.1 400 256 400s-93.8-27.9-116.1-53.5c-5.8-6.7-5.1-16.8 1.6-22.6s16.8-5.1 22.6 1.6zM144.4 208a32 32 0 1 1 64 0 32 32 0 1 1 -64 0zm192-32a32 32 0 1 1 0 64 32 32 0 1 1 0-64z"/></svg>
                <span class="text">[VOTEUPTEXT]</span>
            </button>
            <button
                id="findco-vote-down"
                class="findco-vote-button findco-vote-down [VOTEDOWNSELECTEDCLASS]"
                data-type="0"
                data-post-id="[POSTID]"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M0 256a256 256 0 1 0 512 0A256 256 0 1 0 0 256zm240 80c0-8.8 7.2-16 16-16c45 0 85.6 20.5 115.7 53.1c6 6.5 5.6 16.6-.9 22.6s-16.6 5.6-22.6-.9c-25-27.1-57.4-42.9-92.3-42.9c-8.8 0-16-7.2-16-16zm-80 80c-26.5 0-48-21-48-47c0-20 28.6-60.4 41.6-77.7c3.2-4.4 9.6-4.4 12.8 0C179.6 308.6 208 349 208 369c0 26-21.5 47-48 47zM367.6 208a32 32 0 1 1 -64 0 32 32 0 1 1 64 0zm-192-32a32 32 0 1 1 0 64 32 32 0 1 1 0-64z"/></svg>
                <span class="text">[VOTEDOWNTEXT]</span>
            </button>
        </div>
        ';

        $tags = [
            'title' => (
                $userVoted === null ?
                'Was this article helpful?' :
                'Thank you for your feedback.'
            ),
            'voteUpSelectedClass' => (
                $userVoted === '1' ?
                'findco-vote-selected' :
                ''
            ),
            'voteUpText' => (
                $userVoted === null ?
                'Yes' :
                $voteResults['positive'] .'%'
            ),
            'voteDownSelectedClass' => (
                $userVoted === '0' ?
                'findco-vote-selected' :
                ''
            ),
            'voteDownText' => (
                $userVoted === null ?
                'No' :
                $voteResults['negative'] .'%'
            ),
            'postId' => $postId,
        ];

        $content .= $this->convertTagsToHtml($tags, $votingBoxHtml);

        return $content;
    }
}

