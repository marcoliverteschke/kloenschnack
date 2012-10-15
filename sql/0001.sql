CREATE TABLE posts(
	id INT auto_increment PRIMARY KEY,
	body TEXT NOT NULL DEFAULT '',
	created INT NOT NULL DEFAULT 0
);