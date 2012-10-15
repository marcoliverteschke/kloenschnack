CREATE TABLE users(
	id INT auto_increment PRIMARY KEY,
	name VARCHAR(32) NOT NULL DEFAULT '',
	realname VARCHAR(64) NOT NULL DEFAULT '',
	password VARCHAR(128) NOT NULL DEFAULT ''
);

INSERT INTO users VALUES(DEFAULT, 'marcoliverteschke', 'Marc-Oliver Teschke', '$2d4lP//wXarg');

ALTER TABLE users ADD COLUMN last_login INT NOT NULL DEFAULT 0;
ALTER TABLE users ADD COLUMN last_activity INT NOT NULL DEFAULT 0;
