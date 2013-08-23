CREATE TABLE users(
	id INT auto_increment PRIMARY KEY,
	name VARCHAR(32) NOT NULL DEFAULT '',
	realname VARCHAR(64) NOT NULL DEFAULT '',
	password VARCHAR(128) NOT NULL DEFAULT ''
);

INSERT INTO users VALUES(DEFAULT, 'marcoliverteschke', 'Marc-Oliver Teschke', '$2d4lP//wXarg');

ALTER TABLE users ADD COLUMN last_login INT NOT NULL DEFAULT 0;
ALTER TABLE users ADD COLUMN last_activity INT NOT NULL DEFAULT 0;

INSERT INTO users VALUES(DEFAULT, 'sebastian', 'Sebastian Meister', '$2rcByx51ejoM', 0, 0);


INSERT INTO users VALUES(DEFAULT, 'jens', 'Jens Eversmann', '$2rcByx51ejoM', 0, 0);
INSERT INTO users VALUES(DEFAULT, 'cathrin', 'Cathrin Lange', '$2rcByx51ejoM', 0, 0);
INSERT INTO users VALUES(DEFAULT, 'martina', 'Martina Seiler', '$2rcByx51ejoM', 0, 0);
