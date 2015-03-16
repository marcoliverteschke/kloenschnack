<!doctype html>
<html class="no-js" lang="en">
<head>
	<meta charset="utf-8">
	<title><?php print $page_title ?></title>
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
	<?php print $body_content; ?>
	<script>var default_document_title = '<?php print $page_title ?>';</script>
	<script>var user_id = '<?php print $user_id ?>';</script>
	<script src="/javascript/jquery.js"></script>
	<script src="/min/?b=javascript&f=handlebars.js,underscore.js,modernizr.js,jquery.cookies.2.2.0.min.js,jquery.cssemoticons.min.js,md5.js"></script>
	<script src="/min/?b=javascript/phpjs&f=date.js,get_html_translation_table.js,html_entity_decode.js,htmlentities.js,nl2br.js,trim.js"></script>
	<script src="/min/?b=javascript/models&f=Post.js,PostsQueue.js"></script>
	<script src="/min/?b=javascript/fileupload&f=vendor/jquery.ui.widget.js,jquery.iframe-transport.js,jquery.fileupload.js"></script>

	<script src="/min/?b=javascript&f=kloenschnack.js"></script>
	<script id="post-template" type="text/x-handlebars-template">
		<article class="timeline-entry" data-created="{{created}}" data-guid="{{guid}}">
			<span class="person">{{#if author}}{{author}} sagte {{humanTime created}}{{else}}{{humanTimeShorter created}}{{/if}}: </span>
			{{#if multiline}}<pre class="post {{#if at_me}}at_me{{/if}}">{{else}}<p class="post {{#if at_me}}at_me{{/if}}">{{/if}}{{{body}}}{{#if multiline}}</pre>{{else}}</p>{{/if}}
		</article>
	</script>
	<script id="event-template" type="text/x-handlebars-template">
		<div class="event">{{author}} {{{body}}} {{humanTime created}}</div>
	</script>
	<script id="list-entry-template" type="text/x-handlebars-template">
		<li class="{{class}}" title="{{title}}">{{{name}}}</li>
	</script>
</body>
</html>