CREATE TABLE events(
	id INT auto_increment PRIMARY KEY,
	event VARCHAR(32) NOT NULL DEFAULT '',
	message TEXT NOT NULL DEFAULT '',
	created INT NOT NULL DEFAULT 0,
	user_id INT NOT NULL REFERENCES users(id)
);