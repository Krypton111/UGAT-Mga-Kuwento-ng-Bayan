<?php
// includes/score_functions.php

function saveScore($userId, $username, $gameName, $score, $level = 1, $timePlayed = 0) {
    global $conn;
    
    error_log("saveScore called - User: $username, Game: $gameName, Score: $score");
    
    // Insert score
    $stmt = $conn->prepare("INSERT INTO scores (user_id, username, game_name, score, level, time_played) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("issiii", $userId, $username, $gameName, $score, $level, $timePlayed);
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        return false;
    }
    error_log("Score inserted successfully");
    
    // Update or insert user game stats (only keep highest score)
    $stmt2 = $conn->prepare("INSERT INTO user_game_stats (user_id, game_name, high_score, total_plays, last_played) 
                             VALUES (?, ?, ?, 1, NOW()) 
                             ON DUPLICATE KEY UPDATE 
                             high_score = GREATEST(high_score, VALUES(high_score)),
                             total_plays = total_plays + 1,
                             last_played = NOW()");
    if (!$stmt2) {
        error_log("Prepare failed for stats: " . $conn->error);
        return false;
    }
    
    $stmt2->bind_param("isi", $userId, $gameName, $score);
    if (!$stmt2->execute()) {
        error_log("Execute failed for stats: " . $stmt2->error);
        return false;
    }
    error_log("Stats updated successfully");
    
    return true;
}

function getHighScore($userId, $gameName) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT high_score FROM user_game_stats WHERE user_id = ? AND game_name = ?");
    $stmt->bind_param("is", $userId, $gameName);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['high_score'];
    }
    return 0;
}

function getLeaderboard($gameName, $limit = 10) {
    global $conn;
    
    // Get the highest score per user for this game
    $stmt = $conn->prepare("SELECT ugs.user_id, u.username, ugs.high_score as score, 
                                   (SELECT level FROM scores WHERE user_id = ugs.user_id AND game_name = ? AND score = ugs.high_score LIMIT 1) as level,
                                   ugs.last_played as created_at
                            FROM user_game_stats ugs
                            JOIN users u ON ugs.user_id = u.id
                            WHERE ugs.game_name = ?
                            ORDER BY ugs.high_score DESC
                            LIMIT ?");
    $stmt->bind_param("ssi", $gameName, $gameName, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $leaderboard = [];
    while ($row = $result->fetch_assoc()) {
        $leaderboard[] = $row;
    }
    return $leaderboard;
}

function getGlobalLeaderboard($limit = 10) {
    global $conn;
    
    // Get the highest score per user across all games
    $result = $conn->query("SELECT ugs.user_id, u.username, ugs.game_name, ugs.high_score as score,
                                   (SELECT level FROM scores WHERE user_id = ugs.user_id AND game_name = ugs.game_name AND score = ugs.high_score LIMIT 1) as level,
                                   ugs.last_played as created_at
                            FROM user_game_stats ugs
                            JOIN users u ON ugs.user_id = u.id
                            ORDER BY ugs.high_score DESC
                            LIMIT $limit");
    
    $leaderboard = [];
    while ($row = $result->fetch_assoc()) {
        $leaderboard[] = $row;
    }
    return $leaderboard;
}

function getUserGameStats($userId) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT game_name, high_score, total_plays, last_played 
                            FROM user_game_stats 
                            WHERE user_id = ? 
                            ORDER BY high_score DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $stats = [];
    while ($row = $result->fetch_assoc()) {
        $stats[] = $row;
    }
    return $stats;
}

function getUserRecentScores($userId, $limit = 5) {
    global $conn;
    
    // Get the highest score per game for the user
    $stmt = $conn->prepare("SELECT ugs.game_name, ugs.high_score as score, 
                                   (SELECT level FROM scores WHERE user_id = ? AND game_name = ugs.game_name AND score = ugs.high_score LIMIT 1) as level,
                                   ugs.last_played as created_at
                            FROM user_game_stats ugs
                            WHERE ugs.user_id = ?
                            ORDER BY ugs.last_played DESC
                            LIMIT ?");
    $stmt->bind_param("iii", $userId, $userId, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $scores = [];
    while ($row = $result->fetch_assoc()) {
        $scores[] = $row;
    }
    return $scores;
}

function getUserAllScores($userId, $gameName) {
    global $conn;
    
    // Get all scores for a specific game (for history)
    $stmt = $conn->prepare("SELECT score, level, created_at FROM scores WHERE user_id = ? AND game_name = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->bind_param("is", $userId, $gameName);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $scores = [];
    while ($row = $result->fetch_assoc()) {
        $scores[] = $row;
    }
    return $scores;
}
?>