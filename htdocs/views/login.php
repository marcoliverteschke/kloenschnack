<?php

	require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/lessc.0.4.0.inc.php');
	
	$less = new lessc;
	$less->checkedCompile($_SERVER['DOCUMENT_ROOT'] . '/stylesheets/kloenschnack.less', $_SERVER['DOCUMENT_ROOT'] . '/stylesheets/kloenschnack.css');

?><!doctype html>
<html class="no-js" lang="en">
<head>
	<meta charset="utf-8">
	<title>kloenschnack — really simple team messaging</title>
	<meta name="description" content="kloenschnack — really simple team messaging">
	<meta name="author" content="Marc-Oliver Teschke">
	<meta name = "viewport" content = "initial-scale=1.0">
	<link rel="apple-touch-icon" href="/images/apple-touch-icon-precomposed.png">
	<link rel="shortcut icon" href="/images/favicon.ico" />
	<link rel="stylesheet" href="/min/?b=stylesheets&f=normalize.css,font-awesome.css,kloenschnack.css">
	<!--[if lt IE 9]>
		<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
	<![endif]-->
</head>
<body>
	<section class="container">
		<h1>kloenschnack – login</h1>
		<form action="/user" method="POST">
			<input name="user[name]" placeholder="benutzername" type="text" />
			<input name="user[password]" placeholder="passwort" type="password" />
			<input type="submit" value="Anmelden" />
		</form>
	</section>
</body>
</html>