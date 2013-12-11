var post_template_source = null;
var post_template = null;

var posts_queue = null;
var posts_in_timeline = new Object();

var window_in_focus = true;
var unread_posts = 0;

/* config parameters */
var refresh_timeline_millis = 2500;
var process_queue_millis = 1000;
var refresh_users_list_millis = 10000;


$(function(){
	post_template_source = $('#post-template').html();
	post_template = Handlebars.compile(post_template_source);
	event_template_source = $('#event-template').html();
	event_template = Handlebars.compile(event_template_source);
	list_entry_template_source = $('#list-entry-template').html();
	list_entry_template = Handlebars.compile(list_entry_template_source);
	
	posts_queue = new PostsQueue;

	refresh_timeline();
	window.setInterval(refresh_timeline, refresh_timeline_millis);
	window.setInterval(refresh_users_list, refresh_users_list_millis);

	$('.no-touch .talkbox textarea').keyup(function(event){
		if(event.keyCode == 13 && event.shiftKey === false)
		{
			do_post();
		}
	});

	$('.talkbox [type="submit"]').click(function(event){
		do_post();
		return false;
	});
	
	$(document).on('click', '.users ul li', function(){
		start_at_message($(this).text());
	});
	
	$('.talkbox textarea').focus();

	$('#fileupload').fileupload({
		debug: true,
		onProgress: function(id, fileName, loaded, total){
		}
	});
	
	$(window).blur(function(){
		window_in_focus = false;
	});

	$(window).focus(function(){
		window_in_focus = true;
		unread_posts = 0;
		if(typeof window.fluid != "undefined") {
			window.fluid.dockBadge = '';
		}
		$(document).find('title').text(default_document_title);
	});
	
	$('.stati a').click(function(){
		$.post('/user/status/update', {'status' : $(this).attr('data-status')});
	});

});


$.fn.selectRange = function(start, end) {
    if(!end) end = start; 
    return this.each(function() {
        if (this.setSelectionRange) {
            this.focus();
            this.setSelectionRange(start, end);
        } else if (this.createTextRange) {
            var range = this.createTextRange();
            range.collapse(true);
            range.moveEnd('character', end);
            range.moveStart('character', start);
            range.select();
        }
    });
};


function start_at_message(at) {
	if($('.talkbox textarea').val().search(/^@.+:/) == -1) {
		$('.talkbox textarea').val('@' + at + ': ' + $('.talkbox textarea').val());
	}
	$('.talkbox textarea').selectRange($('.talkbox textarea').val().length);
	$('.talkbox textarea').focus();
}


function do_post()
{
	auth();
	var new_post = new Post;
	if(trim($('.talkbox textarea').val()).length > 0)
	{
		new_post.body = nl2br(htmlentities(trim($('.talkbox textarea').val())));
		new_post.created = parseInt((new Date().getTime()) / 1000);
		new_post.guid = MD5(user_id + '-' + new_post.created);
		add_post_to_timeline(new_post);
		posts_queue.pushPostToQueue(new_post);
		if(posts_queue.isProcessing() == false)
		{
			posts_queue.setProcessing(true);
			window.setInterval(posts_queue.process, process_queue_millis);
		}
	}
	$('.talkbox textarea').val('');
}


function refresh_timeline()
{
	auth();
	$.get('/post', function(data){
		_.each(data, function(post){
			add_post_to_timeline(post);
		});
		$('.post').emoticonize({ 'animate': false });
		refresh_previews();
		refresh_users_list();
		refresh_links_list();
		refresh_files_list();
	}, 'json');
}


function add_post_to_timeline(post) {
	if(typeof posts_in_timeline[post.guid] == "undefined")
	{
		var new_post = new Post;
		new_post.setBody(urlify(post.body));
		new_post.setCreated(post.created);
		new_post.setAuthor(post.author);
		new_post.setMultiline(new_post.getBody().search(/\r\n|\r|\n/) != -1);
		new_post.setAtMe(post.at_me);
		new_post.setGuid(post.guid);
		posts_in_timeline[new_post.getGuid()] = new_post;

		var output = "";
		if(post.type == 'event') {
			output = event_template(new_post.toJson());
		} else {
			output = post_template(new_post.toJson());
		}

		if(output.length > 0)
		{
			$('.timeline').append(output);
			scroll_to_bottom();
			if(!window_in_focus)
			{
				unread_posts++;
				$(document).find('title').text('(' + unread_posts + ') ' + default_document_title);
				if(typeof window.fluid != "undefined")
				{
					window.fluid.dockBadge = unread_posts;
					if(post.at_me) {
						window.fluid.showGrowlNotification({
						    title: post.author, 
						    description: post.body, 
						    priority: 1, 
						    sticky: false,
						    identifier: "foo"
						});
					}
				}
			}
		}
	}	
}


function refresh_previews()
{
	$('.timeline a').not('.has-preview').each(function(i, e){
		if($(e).attr('href').search(/youtube\.com/) !== -1 && $(e).find('img').length == 0) {
			var url_query_split = $(e).attr('href').split('?');
			if(typeof url_query_split[1] !== "undefined")
			{
				var url_params_split = url_query_split[1].split("&");
				for(var i in url_params_split)
				{
					var param_split = url_params_split[i].split("=");
					if(typeof param_split[0] !== 'undefined' && param_split[0] == "v" && typeof param_split[1] !== "undefined")
					{
						$(e).after('<br><br><a href="' + $(e).attr('href') + '"><img src="http://img.youtube.com/vi/' + param_split[1] + '/mqdefault.jpg" /></a>');
						$(e).addClass('has-preview');
					}
				}
			}
		}
	});
}


function refresh_users_list()
{
	auth();
	$.get('/user/active', function(data){
		$('.users ul').empty();
		_.each(data, function(user){
			var output = list_entry_template(user);
			if(output.length > 0)
			{
				$('.users ul').append(output);
			}
		});
	}, 'json');
}


function refresh_links_list()
{
	auth();
	$('.drawer.links ul').empty();
	$('.post a.urlified').slice(-5).each(function(i, e){
		var link = {'name' : '<a href="' + $(e).html() + '" target="_blank">' + $(e).html() + '</a>'};
		var output = list_entry_template(link);
		if(output.length > 0)
		{
			$('.drawer.links ul').append(output);
		}
		if($('.drawer.links ul li').length > 0 && $('.drawer.users:visible').length > 0) {
			$('.drawer.links').show();
		} else {
			$('.drawer.links').hide();
		}
	});
}


function refresh_files_list()
{
	auth();
	$('.drawer.files ul').empty();
	$('.post a.file-namelink').slice(-5).each(function(i, e){
		var link = {'name' : '<a href="' + $(e).attr('href') + '" target="_blank">' + $(e).html() + '</a>'};
		var output = list_entry_template(link);
		if(output.length > 0)
		{
			$('.drawer.files ul').append(output);
		}
		if($('.drawer.files ul li').length > 0 && $('.drawer.users:visible').length > 0) {
			$('.drawer.files').show();
		} else {
			$('.drawer.files').hide();
		}
	});
}


function scroll_to_bottom()
{
	$('body,html').scrollTop($(document).height());
}


function auth()
{
	$.get('/auth', {key : $.cookies.get('kloenschnack_session')}, function(data){
		if(!data || typeof data == "undefined" || !data.authorized)
		{
			window.location.replace("/logout");
		}
	});
}


function urlify(text)
{
	/*
	 * via http://stackoverflow.com/questions/1500260/detect-urls-in-text-with-javascript
	 */
    var urlRegex = /(https?:\/\/[^\s]+)/g; // viel zu ungenau, aber zum Testen reicht es allemal!
    return text.replace(urlRegex, function(url) {
        return '<a class="urlified" href="' + url + '" target="_blank">' + url + '</a>';
    });
}


/**
 * Handlebars templates
 */
 
Handlebars.registerHelper('humanTime', function(timestamp){
	return 'am ' +  date("d.m.Y", timestamp) + ' um ' + date("H:i", timestamp) + ' Uhr';
});

Handlebars.registerHelper('humanTimeShorter', function(timestamp){
	return date("d.m.Y", timestamp) + ' um ' + date("H:i", timestamp) + ' Uhr';
});