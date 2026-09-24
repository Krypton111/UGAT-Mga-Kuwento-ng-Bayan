CREATE DATABASE IF NOT EXISTS game_launcher;
USE game_launcher;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    level INT DEFAULT 1,
    coins INT DEFAULT 100,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users(username,email,password,level,coins)
VALUES
(
'player1',
'player1@email.com',
SHA2('12345',256),
5,
250
);