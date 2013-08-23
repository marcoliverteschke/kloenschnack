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
							'name = ?',  AND password = ?
							array(
								Flight::request()->data['user']['name'], 
								kloencrypt(Flight::request()->data['user']['password'])));
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
		}

		setcookie(
			'kloenschnack_session', 
			null, 
			$now - 60 * 60 * 24 * 14, 
			'/');
		unset($_COOKIE['kloenschnack_session']);
		Flight::redirect('/login');
	});

	Flight::route('/post/create', function(){
		update_activity_time();
		$post = R::dispense('posts');
		$post->body = Flight::request()->data['body'];
		$post->created = time();
		$current_user = current_user();
		$post->user_id = $current_user->id;
		$id = R::store($post);
	});

	Flight::route('/post', function(){
		update_activity_time();
		$posts = R::getAll("SELECT postsDesc.id, postsDesc.body, postsDesc.created, postsDesc.user_id, users.realname FROM (SELECT * FROM posts ORDER BY created DESC LIMIT 24) AS postsDesc LEFT JOIN users ON postsDesc.user_id = users.id ORDER BY created ASC");

		$files = R::getAll("SELECT filesDesc.id, filesDesc.name, filesDesc.type, filesDesc.size, filesDesc.created, filesDesc.alias, filesDesc.user_id, users.realname FROM (SELECT * FROM files ORDER BY created DESC LIMIT 24) AS filesDesc LEFT JOIN users ON filesDesc.user_id = users.id ORDER BY created ASC");

		$timeline_array = array();
		foreach($posts as $post)
		{
			$timeline_array[md5('post-' . $post['id'])]['id'] = md5('post-' . $post['id']);
			$timeline_array[md5('post-' . $post['id'])]['body'] = $post["body"];
			$timeline_array[md5('post-' . $post['id'])]['created'] = $post["created"];
			$timeline_array[md5('post-' . $post['id'])]['author'] = abbreviate_name($post["realname"]);
		}

		foreach($files as $file)
		{
			$timeline_array[md5('file-' . $file['id'])]['id'] = md5('file-' . $file['id']);
			$link_to_file = '/assets/' . $file['alias'];
			$body = '<a href="' . $link_to_file . '" target="_blank">' . $file["name"] . '</a>';
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
	