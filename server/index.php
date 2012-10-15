<?php

	require_once('flight/Flight.php');
	require_once('includes/rb.php');


	Flight::before('start', function(){
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


	Flight::route('/user', function(){
		if(Flight::request()->method == "POST")
		{
			$user	=	R::findOne(
							'users', 
							'name = ? AND password = ?', 
							array(
								Flight::request()->data['user']['name'], 
								kloencrypt(Flight::request()->data['user']['password'])));
			if($user)
			{
				setcookie(
					'kloenschnack_session', 
					rand(100, 999) . '-' . $user->id . '-' . time(), 
					time() + 60 * 60 * 24 * 14, 
					'/');
				header('Location: /');
			} else {
				Flight::redirect('/login');
			}
		} else {
			Flight::redirect('/login');
		}
	});


	Flight::route('/logout', function(){
	});

	
	Flight::route('/post/create', function(){
		$post = R::dispense('posts');
		$post->body = Flight::request()->data['body'];
		$post->created = time();
		$id = R::store($post);
	});
	
	Flight::route('/post', function(){
		$posts = R::getAll("SELECT * FROM (SELECT * FROM posts ORDER BY created DESC LIMIT 24) AS postsDesc ORDER BY created ASC");

		$files = R::getAll("SELECT * FROM (SELECT * FROM files ORDER BY created DESC LIMIT 24) AS filesDesc ORDER BY created ASC");

		$timeline_array = array();
		foreach($posts as $post)
		{
			$timeline_array[md5('post-' . $post['id'])]['id'] = $post["id"];
			$timeline_array[md5('post-' . $post['id'])]['body'] = $post["body"];
			$timeline_array[md5('post-' . $post['id'])]['created'] = $post["created"];
		}

		foreach($files as $file)
		{
			$timeline_array[md5('file-' . $file['id'])]['id'] = $file["id"];
			$link_to_file = '/assets/' . $file['alias'];
			$body = '<a href="' . $link_to_file . '" target="_blank">' . $file["name"] . '</a>';
			if(preg_match("/^image\//", $file['type']))
			{
				$body = '<a href="' . $link_to_file . '" target="_blank"><img class="preview" src="' . $link_to_file . '" /></a>' . $body;
			} else {
				$body = '<a href="' . $link_to_file . '" target="_blank"><img class="fileicon" src="' . get_file_icon($file['type']) . '" /></a>' . $body;
			}
			$body = '<span class="file">' . $body . '</span>';
			$timeline_array[md5('file-' . $file['id'])]['body'] = $body;
			$timeline_array[md5('file-' . $file['id'])]['created'] = $file["created"];
		}
		
		usort($timeline_array, 'sort_timeline');

		Flight::view()->set('data', json_encode($timeline_array));
		Flight::render('json.php');
	});
	

	Flight::route('/file/upload', function(){
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
				$id = R::store($file);
				$ext = pathinfo(Flight::request()->files['files']['name'][$key], PATHINFO_EXTENSION);
				$alias = md5($id) . '.' . $ext;
				move_uploaded_file($value, $assets_folder . '/' . $alias);
				$file->alias = $alias;
				R::store($file);
			}
		}
	});


	Flight::route('/', function(){ });

	
	Flight::start();
	
	
	function sort_timeline($a, $b)
	{
		if($a['created'] == $b['created'])
		{
			return 0;
		}
		return $a['created'] > $b['created'] ? +1 : -1;
	}
	
	
	function get_file_icon($type)
	{
		$icon_path = '/images/fileicons';
		
		if(preg_match("/\/pdf$/", $type))
			return $icon_path . '/pdf.png';
		
		return $icon_path . '/file.png';
	}
	
	
	function kloencrypt($in)
	{
		return crypt($in, '$2y$31$kloenschnacktrittcampf$');
	}