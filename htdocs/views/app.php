<!doctype html>
<html class="no-js" lang="en">
<head>
	<meta charset="utf-8">
	<title>kloenschnack — really simple team messaging</title>
	<meta name="description" content="kloenschnack — really simple team messaging">
	<meta name="author" content="Marc-Oliver Teschke">
	<meta name = "viewport" content = "initial-scale=1.0">
	<link rel="apple-touch-icon" href="/images/apple-touch-icon-precomposed.png">
	<link rel="shortcut icon" href="/images/favicon.ico" />
	<link rel="stylesheet" href="/min/?b=stylesheets&f=normalize.css,jquery.cssemoticons.css,font-awesome.css,kloenschnack.css">
	<!--[if lt IE 9]>
		<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
	<![endif]-->
</head>
<body>
	<section class="tabbar hanging-tabs">
		<section class="container">
			<ul class="clearfix">
				<li><a href="/logout">abmelden</a></li>
			</ul>
		</section>
	</section>
	<section class="drawers">
		<section class="drawer users">
			<ul>
			</ul>
		</section>
		<section class="drawer files">
			<ul>
			</ul>
		</section>
		<section class="drawer links">
			<ul>
			</ul>
		</section>
	</section>
	<section class="timeline container"></section>
	<section class="talkbox">
		<section class="container">
			<form action="" method="" class="">
				<textarea></textarea>
				<input class="clearfix" type="submit" value="&#x23CE; abschicken">
				<span class="button fileinput-button">
					<i class="icon-upload-alt"></i>
					<span>datei hochladen</span>
					<input id="fileupload" type="file" name="files[]" data-url="/file/upload/">
				</span>
			</form>
		</section>
	</section>
	<script src="/javascript/jquery.js"></script>
	<script src="/min/?b=javascript&f=handlebars.js,underscore.js,modernizr.js,jquery.cookies.2.2.0.min.js,jquery.cssemoticons.min.js"></script>
	<script src="/min/?b=javascript/phpjs&f=date.js,get_html_translation_table.js,html_entity_decode.js,htmlentities.js,nl2br.js,trim.js"></script>
	<script src="/min/?b=javascript/models&f=Post.js,PostsQueue.js"></script>
	<script src="/min/?b=javascript/fileupload&f=vendor/jquery.ui.widget.js,jquery.iframe-transport.js,jquery.fileupload.js"></script>
<!--	<script src="/javascript/jquery.js"></script>
	<script src="/javascript/handlebars.js"></script>
	<script src="/javascript/underscore.js"></script>
	<script src="/javascript/modernizr.js"></script>
	<script src="/javascript/jquery.cookies.2.2.0.min.js"></script>
	<script src="/javascript/phpjs/date.js"></script>
	<script src="/javascript/phpjs/get_html_translation_table.js"></script>
	<script src="/javascript/phpjs/html_entity_decode.js"></script>
	<script src="/javascript/phpjs/htmlentities.js"></script>
	<script src="/javascript/phpjs/nl2br.js"></script>
	<script src="/javascript/phpjs/trim.js"></script>
	<script src="/javascript/models/Post.js"></script>
	<script src="/javascript/models/PostsQueue.js"></script>
	<script src="/javascript/fileupload/vendor/jquery.ui.widget.js"></script>
	<script src="/javascript/fileupload/jquery.iframe-transport.js"></script>
	<script src="/javascript/fileupload/jquery.fileupload.js"></script>
	-->
	<script src="/min/?b=javascript&f=kloenschnack.js"></script>
	<script id="post-template" type="text/x-handlebars-template">
		<span class="person">{{author}} sagte {{humanTime created}} : </span>
		{{#if multiline}}<pre class="post">{{else}}<p class="post">{{/if}}{{{body}}}{{#if multiline}}</pre>{{else}}</p>{{/if}}
	</script>
	<script id="event-template" type="text/x-handlebars-template">
		<div class="event">{{author}} {{{body}}} {{humanTime created}}</div>
	</script>
	<script id="list-entry-template" type="text/x-handlebars-template">
		<li>{{{name}}}</li>
	</script>
</body>
</html>