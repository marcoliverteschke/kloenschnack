<?php

	require_once('flight/Flight.php');
	require_once('includes/rb.php');
	require_once('includes/lessc.0.4.0.inc.php');

	Flight::before('start', function(){
		$less = new lessc;
		$less->checkedCompile('stylesheets/kloenschnack.less', 'stylesheets/kloenschnack.css');
		if($_SERVER['SERVER_NAME'] == 'kloenschnack.leopard.planwerk6.local')
		{
			R::setup('mysql:host=localhost; dbname=kloenschnack','kloenschnack','eireeM2ohTe2aejoh7ooGhah');
		} else {
			R::setup('mysql:host=localhost; dbname=kloenschnack_dev','root','root');
		}
	});

	Flight::before('render', function(){
		Flight::view()->set('page_title', 'kloenschnack — really simple team messaging');
		$current_user = current_user();
		Flight::view()->set('user_id', $current_user->id);
	});

	Flight::route('/login', function(){
		Flight::render('login.php');
	});

	Flight::route('/auth', function(){
		$authd = false;
		$key_fragments = explode('-', Flight::request()->query['key']);
		if(count($key_fragments) == 3)
		{
			$user	=	R::findOne(
							'users',
							'id = ? AND last_login = ?',
							array(
								$key_fragments[1],
								$key_fragments[2]
							)
						);
			if($user)
			{
				$authd = true;
			}
		}
		Flight::view()->set('data', json_encode(array('authorized' => $authd)));
		Flight::render('json.php');
	});

	Flight::route('/user', function(){
		if(Flight::request()->method == "POST")
		{
//			error_log(kloencrypt(Flight::request()->data['user']['password']));
			$user	=	R::findOne(
							'users',
							'name = ? AND password = ?',
							array(
								Flight::request()->data['user']['name'],
								kloencrypt(Flight::request()->data['user']['password'])
							)
						);
			// $user	=	R::findOne(
			// 				'users',
			// 				'name = ?',
			// 				array(
			// 					Flight::request()->data['user']['name']
			// 				)
			// 			);
			if($user)
			{
				$now = time();
				setcookie(
					'kloenschnack_session',
					rand(100, 999) . '-' . $user->id . '-' . $now,
					$now + 60 * 60 * 24 * 14,
					'/');
				$user->last_login = $now;
				R::store($user);

				$login_event = R::dispense('events');
				$login_event->event = 'login';
				$login_event->message = 'hat sich angemeldet';
				$login_event->created = time();
				$login_event->user_id = $user->id;
				R::store($login_event);

				header('Location: /');
			} else {
				Flight::redirect('/login');
			}
		} else {
			Flight::redirect('/login');
		}
	});

	Flight::route('/user/active', function(){
		$users = R::find('users', ' last_activity > ? ORDER BY realname ASC', array(time() - 600));
		$users_array = array();
		foreach($users as $key => $user)
		{
			$users_array[$key]['name'] = $user->realname;
			$users_array[$key]['class'] = 'status-' . $user->current_status;
			switch($user->current_status) {
				case 'do_not_disturb':
					$users_array[$key]['title'] = 'Voll beschäftigt.';
				break;
				case 'on_the_phone':
					$users_array[$key]['title'] = 'An der Strippe.';
				break;
			}
		}
		Flight::view()->set('data', json_encode($users_array));
		Flight::render('json.php');
	});

	Flight::route('/user/status/update', function(){
		$current_user = current_user();
		if(!empty($current_user) && !empty(Flight::request()->data['status'])) {
			$current_user->current_status = Flight::request()->data['status'];
			R::store($current_user);
		}
	});

	Flight::route('/logout', function(){
		$current_user = current_user();
		if($current_user != null)
		{
			$current_user->last_activity = time() - 601;
			R::store($current_user);

			$logout_event = R::dispense('events');
			$logout_event->event = 'logout';
			$logout_event->message = 'hat sich abgemeldet';
			$logout_event->created = time();
			$logout_event->user_id = $current_user->id;
			R::store($logout_event);
		}

		setcookie(
			'kloenschnack_session',
			null,
			$now - 60 * 60 * 24 * 14,
			'/');
		unset($_COOKIE['kloenschnack_session']);
		Flight::redirect('/login');
	});

	Flight::route('/api/post/create', function(){

		$request = Flight::request();

		if($request->type == 'application/json') {
			$body = $request->body;
			if(strlen($body) > 0) {
				$body_object = json_decode($body);

				if(isset($body_object->user) && strlen($body_object->user) > 0) {
					$user	=	R::findOne(
						'users',
						'api_key = ?',
						array(
							kloencrypt($body_object->user),
						)
					);

					if(!empty($user)) {
						if(isset($body_object->message) && isset($body_object->message->body) && strlen($body_object->message->body) > 0) {
							$post = R::dispense('posts');
							$post->body = (string)$body_object->message->body;
							$post->created = time();
							$post->user_id = $user->id;
							$id = R::store($post);
						} else {
							echo 'Message body cannot be empty.';
						}
					} else {
						echo 'No corresponding user was found.';
					}
				} else {
					echo 'User API key cannot be empty.';
				}
			} else {
				echo 'Request body cannot be empty.';
			}
		} else {
			echo 'Request has to be in JSON format.';
		}
	});


	Flight::route('/user/type', function(){
		$current_user = current_user();
		$current_user->last_input = time();
		R::store($current_user);
	});


	Flight::route('/user/typing', function(){
		$current_user = current_user();
		$active_users = R::getAll("SELECT users.id, users.realname, UNIX_TIMESTAMP() - last_input AS typing FROM users ORDER BY last_input DESC;");
		$users_array = array();
		foreach($active_users as $key => $user)
		{
			if($user['typing'] <= 5 && $user['id'] != $current_user->id) {
				$users_array[$key]['name'] = $user['realname'];
			}
		}
		Flight::view()->set('data', json_encode($users_array));
		Flight::render('json.php');
	});


	Flight::route('/post/create', function(){
		update_activity_time();
		$post = R::dispense('posts');
		$post->body = Flight::request()->data['body'];
		$post->created = (int)Flight::request()->data['created'];
		$current_user = current_user();
		$post->user_id = $current_user->id;
		$all_users = R::getAll("SELECT users.id, users.realname FROM users ORDER BY users.realname ASC");
		if(preg_match("/^@alle:/i", $post->body)) {
			$post->at_user_id = -1;
		} else {
			foreach($all_users as $user) {
				if(preg_match("/^@$user[realname]:/i", $post->body)) {
					$post->at_user_id = $user['id'];
				}
			}
		}
		$id = R::store($post);
	});

	Flight::route('/post/view', function(){
		if(isset(Flight::request()->data['guids']) && is_array(Flight::request()->data['guids']) && count(Flight::request()->data['guids']) > 0) {
			$current_user = current_user();

			foreach(Flight::request()->data['guids'] as $guid) {
				if(preg_match("/^[a-z0-9]{32}$/", $guid)) {
					$exists_query = R::getRow(
						'SELECT count(*) AS got_that FROM posts_viewed WHERE guid = :guid AND user_id = :user_id',
						array(':guid' => $guid, ':user_id' => $current_user->id)
					);

					if(isset($exists_query['got_that']) && (int)$exists_query['got_that'] === 0) {
						R::exec(
							'INSERT INTO posts_viewed VALUES(:guid, :user_id)',
							array(':guid' => $guid, ':user_id' => $current_user->id)
						);
					}
				}
			}
		}
	});


	Flight::route('/post/viewed', function(){
		if(isset(Flight::request()->data['guids']) && is_array(Flight::request()->data['guids']) && count(Flight::request()->data['guids']) > 0) {
			$current_user = current_user();

			$views = array();
			foreach(Flight::request()->data['guids'] as $guid) {
				if(preg_match("/^[a-z0-9]{32}$/", $guid)) {
					$viewed_query = R::getAll(
						'SELECT u.realname FROM users u JOIN posts_viewed pv ON u.id = pv.user_id WHERE pv.guid = :guid ORDER BY realname ASC',
						array(
							':guid' => $guid
						)
					);
					$users_hash = '';
					if(is_array($viewed_query) && count($viewed_query) > 0) {
						foreach($viewed_query as $a_view) {
							if(isset($a_view['realname']) && strlen($a_view['realname']) > 0) {
								$views[$guid]['users'][] = $substring = substr($a_view['realname'], 0, strpos($a_view['realname'], ' '));;
								$users_hash .= $a_view['realname'];
							}
						}
					}
					$views[$guid]['changed_hash'] = md5($users_hash);
				}
			}
			Flight::view()->set('data', json_encode($views));
			Flight::render('json.php');
		}
	});


	Flight::route('/post', function(){
		update_activity_time();
		$current_user = current_user();

		$entries = R::getAll("SELECT postsDesc.id, postsDesc.body, '' as filename, '' as filetype, '' as filesize, postsDesc.created, '' as filealias, postsDesc.user_id, postsDesc.at_user_id, postsDesc.guid, users.realname, 'post' as type FROM postsunique AS postsDesc LEFT JOIN users ON postsDesc.user_id = users.id UNION SELECT filesDesc.id, '' as body, filesDesc.name as filename, filesDesc.type as filetype, filesDesc.size as filesize, filesDesc.created, filesDesc.alias as filealias, filesDesc.user_id, 0 as at_user_id, filesDesc.guid, users.realname, 'file' as type FROM filesunique AS filesDesc LEFT JOIN users ON filesDesc.user_id = users.id ORDER BY created DESC LIMIT 50");

		$timeline_array = array();

		foreach($entries as $entry) {
			if($entry['type'] == 'post') {

				$timeline_array[md5('post-' . $entry['id'])]['id'] = md5('post-' . $entry['id']);
				$timeline_array[md5('post-' . $entry['id'])]['body'] = $entry["body"];
				$timeline_array[md5('post-' . $entry['id'])]['created'] = $entry["created"];
				$timeline_array[md5('post-' . $entry['id'])]['author'] = abbreviate_name($entry["realname"]);
				$timeline_array[md5('post-' . $entry['id'])]['type'] = 'post';
				$timeline_array[md5('post-' . $entry['id'])]['at_me'] = ($entry['at_user_id'] == $current_user->id || (int)$entry['at_user_id'] === -1);
				$timeline_array[md5('post-' . $entry['id'])]['guid'] = $entry['guid'];

			} else if($entry['type'] == 'file') {

				$timeline_array[md5('file-' . $entry['id'])]['id'] = md5('file-' . $entry['id']);
				$link_to_file = '/assets/' . $entry['filealias'];
				$body = '<a class="file-namelink" href="' . $link_to_file . '" target="_blank">' . $entry["filename"] . '</a>';
				if(preg_match("/^image\//", $entry['filetype']))
				{
					$body = '<a href="' . $link_to_file . '" target="_blank"><img class="preview" src="' . $link_to_file . '" /></a>' . $body;
				} else {
					$body = '<a href="' . $link_to_file . '" target="_blank"><img class="fileicon" src="' . get_file_icon($entry['filetype'], $entry['filename']) . '" /></a>' . $body;
				}
				$body = '<span class="file">' . $body . '</span>';
				$timeline_array[md5('file-' . $entry['id'])]['body'] = $body;
				$timeline_array[md5('file-' . $entry['id'])]['created'] = $entry["created"];
				$timeline_array[md5('file-' . $entry['id'])]['author'] = abbreviate_name($entry["realname"]);
				$timeline_array[md5('file-' . $entry['id'])]['type'] = 'file';
				$timeline_array[md5('file-' . $entry['id'])]['guid'] = $entry['guid'];
			}
		}

		usort($timeline_array, 'sort_timeline');

		Flight::view()->set('data', json_encode($timeline_array));
		Flight::render('json.php');
	});

	Flight::route('/file/upload', function(){
		update_activity_time();
		$assets_folder = $_SERVER['DOCUMENT_ROOT'] . '/assets';
		if(!file_exists($assets_folder))
		{
			mkdir($assets_folder, 0755);
		}

		foreach(Flight::request()->files['files']['tmp_name'] as $key => $value)
		{
			if(Flight::request()->files['files']['error'][$key] == 0)
			{
				$file = R::dispense('files');
				$file->name = Flight::request()->files['files']['name'][$key];
				$file->type = Flight::request()->files['files']['type'][$key];
				$file->size = Flight::request()->files['files']['size'][$key];
				$file->created = time();
				$current_user = current_user();
				$file->user_id = $current_user->id;
				$id = R::store($file);
				$ext = pathinfo(Flight::request()->files['files']['name'][$key], PATHINFO_EXTENSION);
				$alias = md5($id) . '.' . $ext;
				move_uploaded_file($value, $assets_folder . '/' . $alias);
				$file->alias = $alias;
				R::store($file);
			}
		}
	});

	Flight::route('/archive', function(){
		$errors = array();
		$messages = array();
		$hits = array();
		$search = '';

		if(isset(Flight::request()->query['search']) && strlen(trim(Flight::request()->query['search'])) > 0)
		{
			$search = Flight::request()->query['search'];
			$hits = R::getAll(
				"SELECT postsDesc.id, postsDesc.body, postsDesc.created, postsDesc.user_id, postsDesc.at_user_id, postsDesc.guid, users.realname, 'post' as type FROM postsunique AS postsDesc LEFT JOIN users ON postsDesc.user_id = users.id WHERE postsDesc.body LIKE :search ORDER BY created DESC",
				array(
					':search' => sprintf('%%%s%%', $search)
				)
			);
			if(is_array($hits) && count($hits) > 0)
			{
				foreach($hits as $key => &$hit)
				{
					$hit['body'] = preg_replace(sprintf("/%s/i", $search), sprintf('<span class="match">%s</span>', $search), $hit['body']);
					$hit['author'] = abbreviate_name($hit["realname"]);
					$hit['at_me'] = (int)$hit['at_user_id'] == $current_user->id;
				}

				$hits = json_encode($hits);
			}
		}

		// get distinct days with activity
		// SELECT DATE(FROM_UNIXTIME(created)) AS date FROM posts UNION SELECT DATE(FROM_UNIXTIME(created)) as date FROM files ORDER BY date ASC;
		// get posts for last day or day from parameter
		// display posts
		$search = mb_convert_encoding($search, 'UTF-8', 'UTF-8');
		$search = htmlentities($search, ENT_QUOTES, 'UTF-8');
		Flight::render('archive.php', array('errors' => $errors, 'messages' => $messages, 'hits' => $hits, 'search' => $search), 'body_content');
		Flight::render('layout_logged_in.php');
	});

	Flight::route('/settings', function(){
		$errors = array();
		$messages = array();
		if(Flight::request()->method == "POST")
		{
			if(strlen(Flight::request()->data['user']['new_password']) < 8)
			{
				$errors[] = 'Das neue Passwort muss mindestens 8 Zeichen lang sein.';
			}
			if(Flight::request()->data['user']['new_password'] != Flight::request()->data['user']['new_password_confirm'])
			{
				$errors[] = 'Das neue Passwort und die Passwortbestätigung stimmen nicht überein.';
			}
			if(count($errors) == 0)
			{
				$current_user = current_user();
				$current_user->password = kloencrypt(Flight::request()->data['user']['new_password']);
				R::store($current_user);
				$messages[] = 'Passwort geändert.';
			}
		}
		Flight::render('settings.php', array('errors' => $errors, 'messages' => $messages), 'body_content');
		Flight::render('layout_logged_in.php');
	});

	Flight::route('/', function(){
		if(!isset($_COOKIE['kloenschnack_session']) || !preg_match("/^[0-9]{3}\-[0-9]+\-[0-9]+$/", $_COOKIE['kloenschnack_session']))
		{
			Flight::redirect('/login');
		}
		Flight::render('app.php', array(), 'body_content');
		Flight::render('layout_logged_in.php');
	});

	Flight::start();

	function sort_timeline($a, $b)
	{
		if($a['created'] == $b['created'])
		{
			return 0;
		}
		return $a['created'] > $b['created'] ? +1 : -1;
	}

	function get_file_icon($type, $name)
	{
		$icon_path = '/images/fileicons';

		if(preg_match("/text\/plain$/", $type))
			return $icon_path . '/txt.png';
		if(preg_match("/\/pdf$/", $type))
			return $icon_path . '/pdf.png';

		if(preg_match("/application\/octet-stream$/", $type))
		{
			$filename_fragments = explode('.', $name);
			if(preg_match("/^pdf$/", $filename_fragments[count($filename_fragments) - 1]))
				return $icon_path . '/pdf.png';
			if(preg_match("/^(md|markdown|mdown)$/", $filename_fragments[count($filename_fragments) - 1]))
				return $icon_path . '/txt.png';
		}

		return $icon_path . '/file.png';
	}

	function kloencrypt($in)
	{
		return crypt($in, '$2y$31$kloenschnacktrittcampf$');
	}

	function abbreviate_name($name)
	{
		return substr($name, 0, strrpos($name, " "));
	}

	function current_user()
	{
		$user = null;
		if(isset($_COOKIE['kloenschnack_session']))
		{
			$key_fragments = explode('-', $_COOKIE['kloenschnack_session']);
			if(count($key_fragments) == 3)
			{
				$user	=	R::findOne(
								'users',
								'id = ? AND last_login = ?',
								array(
									$key_fragments[1],
									$key_fragments[2]
								)
							);
			}
		}
		return $user;
	}

	function update_activity_time()
	{
		$current_user = current_user();
		if($current_user != null)
		{
			$current_user->last_activity = time();
			R::store($current_user);
		}
	}
