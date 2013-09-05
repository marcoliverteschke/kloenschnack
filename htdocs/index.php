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
			$user	=	R::findOne(
							'users', 
							'name = ?'/* AND password = ?'*/,
							array(
								Flight::request()->data['user']['name']/*, 
								kloencrypt(Flight::request()->data['user']['password'])*/));
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
		foreach($users as $user)
		{
			$users_array[]['name'] = $user->realname;
		}
		Flight::view()->set('data', json_encode($users_array));
		Flight::render('json.php');
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

	Flight::route('/post/create', function(){
		update_activity_time();
		$post = R::dispense('posts');
		$post->body = Flight::request()->data['body'];
		$post->created = time();
		$current_user = current_user();
		$post->user_id = $current_user->id;
		$all_users = R::getAll("SELECT users.id, users.realname FROM users ORDER BY users.realname ASC");
		foreach($all_users as $user) {
			if(preg_match("/^@$user[realname]:/", $post->body)) {
				$post->at_user_id = $user['id'];
			}
		}
		$id = R::store($post);
	});

	Flight::route('/post', function(){
		update_activity_time();
		$current_user = current_user();

//		$posts = R::getAll("SELECT postsDesc.id, postsDesc.body, postsDesc.created, postsDesc.user_id, postsDesc.at_user_id, users.realname FROM (SELECT * FROM posts ORDER BY created DESC LIMIT 24) AS postsDesc LEFT JOIN users ON postsDesc.user_id = users.id ORDER BY created ASC");

//		$files = R::getAll("SELECT filesDesc.id, filesDesc.name, filesDesc.type, filesDesc.size, filesDesc.created, filesDesc.alias, filesDesc.user_id, users.realname FROM (SELECT * FROM files ORDER BY created DESC LIMIT 24) AS filesDesc LEFT JOIN users ON filesDesc.user_id = users.id ORDER BY created ASC");

//		$events = R::getAll("SELECT eventsDesc.id, eventsDesc.event, eventsDesc.message, eventsDesc.created, eventsDesc.user_id, users.realname FROM (SELECT * FROM events ORDER BY created DESC LIMIT 24) AS eventsDesc LEFT JOIN users ON eventsDesc.user_id = users.id ORDER BY created ASC");

		$entries = R::getAll("SELECT postsDesc.id, postsDesc.body, '' as filename, '' as filetype, '' as filesize, postsDesc.created, '' as filealias, postsDesc.user_id, postsDesc.at_user_id, users.realname, 'post' as type FROM posts AS postsDesc LEFT JOIN users ON postsDesc.user_id = users.id UNION SELECT filesDesc.id, '' as body, filesDesc.name as filename, filesDesc.type as filetype, filesDesc.size as filesize, filesDesc.created, filesDesc.alias as filealias, filesDesc.user_id, 0 as at_user_id, users.realname, 'file' as type FROM files AS filesDesc LEFT JOIN users ON filesDesc.user_id = users.id ORDER BY created DESC LIMIT 50");

		$timeline_array = array();
		
		foreach($entries as $entry) {
			if($entry['type'] == 'post') {

				$timeline_array[md5('post-' . $entry['id'])]['id'] = md5('post-' . $entry['id']);
				$timeline_array[md5('post-' . $entry['id'])]['body'] = $entry["body"];
				$timeline_array[md5('post-' . $entry['id'])]['created'] = $entry["created"];
				$timeline_array[md5('post-' . $entry['id'])]['author'] = abbreviate_name($entry["realname"]);
				$timeline_array[md5('post-' . $entry['id'])]['type'] = 'post';
				$timeline_array[md5('post-' . $entry['id'])]['at_me'] = ($entry['at_user_id'] == $current_user->id);

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
			}
		}
		
/*		foreach($posts as $post)
		{
			$timeline_array[md5('post-' . $post['id'])]['id'] = md5('post-' . $post['id']);
			$timeline_array[md5('post-' . $post['id'])]['body'] = $post["body"];
			$timeline_array[md5('post-' . $post['id'])]['created'] = $post["created"];
			$timeline_array[md5('post-' . $post['id'])]['author'] = abbreviate_name($post["realname"]);
			$timeline_array[md5('post-' . $post['id'])]['type'] = 'post';
			$timeline_array[md5('post-' . $post['id'])]['at_me'] = ($post['at_user_id'] == $current_user->id);
		}*/

/*		foreach($events as $event)
		{
			$timeline_array[md5('event-' . $event['id'])]['id'] = md5('event-' . $event['id']);
			$timeline_array[md5('event-' . $event['id'])]['body'] = $event["message"];
			$timeline_array[md5('event-' . $event['id'])]['created'] = $event["created"];
			$timeline_array[md5('event-' . $event['id'])]['author'] = abbreviate_name($event["realname"]);
			$timeline_array[md5('event-' . $event['id'])]['type'] = 'event';
		}*/

/*		foreach($files as $file)
		{
			$timeline_array[md5('file-' . $file['id'])]['id'] = md5('file-' . $file['id']);
			$link_to_file = '/assets/' . $file['alias'];
			$body = '<a class="file-namelink" href="' . $link_to_file . '" target="_blank">' . $file["name"] . '</a>';
			if(preg_match("/^image\//", $file['type']))
			{
				$body = '<a href="' . $link_to_file . '" target="_blank"><img class="preview" src="' . $link_to_file . '" /></a>' . $body;
			} else {
				$body = '<a href="' . $link_to_file . '" target="_blank"><img class="fileicon" src="' . get_file_icon($file['type'], $file['name']) . '" /></a>' . $body;
			}
			$body = '<span class="file">' . $body . '</span>';
			$timeline_array[md5('file-' . $file['id'])]['body'] = $body;
			$timeline_array[md5('file-' . $file['id'])]['created'] = $file["created"];
			$timeline_array[md5('file-' . $file['id'])]['author'] = abbreviate_name($file["realname"]);
			$timeline_array[md5('file-' . $file['id'])]['type'] = 'file';
		}*/
		
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
		// get distinct days with activity
		// SELECT DATE(FROM_UNIXTIME(created)) AS date FROM posts UNION SELECT DATE(FROM_UNIXTIME(created)) as date FROM files ORDER BY date ASC;
		// get posts for last day or day from parameter
		// display posts
	});

	Flight::route('/', function(){
		if(!isset($_COOKIE['kloenschnack_session']) || !preg_match("/^[0-9]{3}\-[0-9]+\-[0-9]+$/", $_COOKIE['kloenschnack_session']))
		{
			Flight::redirect('/login');
		}
		Flight::render('app.php');
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
	