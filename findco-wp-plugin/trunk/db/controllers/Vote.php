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

    function getTableName() {
        return $this->table['name'];
    }

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