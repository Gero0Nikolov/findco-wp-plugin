<?php

namespace Db\FindCoRating;

class Vote extends DbController {
    private static $config;

    private $table;

    function __construct($config) {

        // Set Defaults
        self::$config = array_merge(
            $config,
            [
            ]
        );

        // Set Table
        $this->table = [
            'name' => self::$config['tablePrefix'] .'Vote',
            'columns' => [
                'id' => 'INT NOT NULL AUTO_INCREMENT',
                'ip' => 'VARCHAR(255)',
                'postId' => 'INT',
                'type' => 'VARCHAR(1)',
            ],
            'indexedColumns' => [
                'ip',
                'postId',
                'type',
            ],
        ];

        $registerState = $this->registerTable($this->table);

        if (!$registerState) {
            die('Table not set. Investigate: '. $this->table['name']);
        }
    }

    /**
     * Retrieves the name of the table associated with this controller.
     *
     * @return string The name of the table.
     */
    function getTableName() {
        return $this->table['name'];
    }

    /**
     * Checks if a user has voted on a specific post.
     *
     * This function retrieves the user's IP address and checks the database to see if a vote from this IP exists for the specified post.
     * If a vote exists, it returns the type of the vote. If no vote exists or the post ID is not provided, it returns null.
     *
     * @param int $postId The ID of the post to check.
     * @return mixed The type of the vote if a vote exists, otherwise null.
     */
    function userVoted($postId) {
        $postId = intval($postId);
        
        if (empty($postId)) { return null; }

        $userIp = (
            isset($_SERVER['HTTP_X_FORWARDED_FOR']) ?
            $_SERVER['HTTP_X_FORWARDED_FOR'] :
            $_SERVER['REMOTE_ADDR']
        );

        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT * FROM ". $this->table['name'] ." WHERE ip = %s AND postId = %d LIMIT 1",
            $userIp,
            $postId
        );

        $results = $wpdb->get_results($query, ARRAY_A);

        if (empty($results)) { return null; }

        return $results[0]['type'];
    }

    /**
     * Retrieves the vote results for a specific post.
     *
     * This function retrieves all votes for the specified post from the database and calculates the percentage of positive and negative votes.
     * If no votes exist or the post ID is not provided, it returns an array with 'positive' and 'negative' keys both set to 0.
     *
     * @param int $postId The ID of the post to retrieve the vote results for.
     * @return array An associative array containing the percentage of positive and negative votes.
     */
    function getVoteResults($postId) {
        $result = [
            'positive' => 0,
            'negative' => 0,
        ];

        $postId = intval($postId);
        
        if (empty($postId)) { return false; }

        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT * FROM ". $this->table['name'] ." WHERE postId = %d",
            $postId
        );

        $results = $wpdb->get_results($query, ARRAY_A);

        if (empty($results)) { return $result; }

        $totalVotes = 0;
        $positiveVotes = 0;
        $negativeVotes = 0;

        foreach ($results as $voteIndex => $voteObject) {
            if ($voteObject['type'] === '1') {
                $positiveVotes += 1;
            }

            if ($voteObject['type'] === '0') {
                $negativeVotes += 1;
            }

            $totalVotes += 1;
        }

        if ($totalVotes > 0) {
            $positivePercentage = ($positiveVotes / $totalVotes) * 100;
            $negativePercentage = ($negativeVotes / $totalVotes) * 100;

            $result['positive'] = round($positivePercentage, 0);
            $result['negative'] = round($negativePercentage, 0);
        }

        return $result;
    }

    /**
     * Records a vote for a specific post.
     *
     * This function checks if the post ID and vote type are valid and if the user has not already voted on the post.
     * If all checks pass, it retrieves the user's IP address and inserts a new row into the votes table in the database.
     * The new row contains the user's IP, the post ID, and the vote type.
     *
     * @param int $postId The ID of the post to vote on.
     * @param string $voteType The type of the vote ('1' for positive, '0' for negative).
     * @return bool True if the vote was recorded successfully, otherwise false.
     */
    function vote($postId, $voteType) {
        $postId = intval($postId);
        $voteType = $voteType;
        
        if (
            empty($postId) || 
            (
                empty($voteType) &&
                $voteType !== '0'
            ) ||
            (
                $voteType !== '1' &&
                $voteType !== '0' 
            ) ||
            $this->userVoted($postId)
        ) { return false; }

        $userIp = (
            isset($_SERVER['HTTP_X_FORWARDED_FOR']) ?
            $_SERVER['HTTP_X_FORWARDED_FOR'] :
            $_SERVER['REMOTE_ADDR']
        );

        global $wpdb;

        $query = $wpdb->prepare(
            "INSERT INTO ". $this->table['name'] ." (ip, postId, type) VALUES (%s, %d, %d)",
            $userIp,
            $postId,
            $voteType
        );

        $insertState = $wpdb->query($query);

        return $insertState;
    }
}