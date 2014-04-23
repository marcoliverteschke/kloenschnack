<section class="tabbar hanging-tabs">
	<section class="container">
		<ul class="clearfix">
			<li><a href="/settings">Einstellungen</a></li>
			<li><a href="/logout">abmelden</a></li>
		</ul>
	</section>
</section>
<section class="drawers">
	<section class="drawer users">
		<ul>
		</ul>
	</section>
	<section class="drawer stati">
		<ul>
			<li><a href="javascript:void(0);" data-status="available" title="Verfügbar">&#xf00c;</a></li>
			<li><a href="javascript:void(0);" data-status="do_not_disturb" title="Nicht stören">&#xf056;</a></li>
			<li><a href="javascript:void(0);" data-status="on_the_phone" title="Am Telefon">&#xf095;</a></li>
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
<script>var default_document_title = '<?php print $page_title ?>';</script>
<script>var user_id = '<?php print $user_id ?>';</script>
<script src="/javascript/jquery.js"></script>
<script src="/min/?b=javascript&f=handlebars.js,underscore.js,modernizr.js,jquery.cookies.2.2.0.min.js,jquery.cssemoticons.min.js,md5.js"></script>
<script src="/min/?b=javascript/phpjs&f=date.js,get_html_translation_table.js,html_entity_decode.js,htmlentities.js,nl2br.js,trim.js"></script>
<script src="/min/?b=javascript/models&f=Post.js,PostsQueue.js"></script>
<script src="/min/?b=javascript/fileupload&f=vendor/jquery.ui.widget.js,jquery.iframe-transport.js,jquery.fileupload.js"></script>

<script src="/min/?b=javascript&f=kloenschnack.js"></script>
<script id="post-template" type="text/x-handlebars-template">
	<article class="timeline-entry" data-created="{{created}}">
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
