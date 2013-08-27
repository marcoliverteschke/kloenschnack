var post_template_source = null;
var post_template = null;

var posts_queue = null;
var posts_in_timeline = new Array();

var window_in_focus = true;
var unread_posts = 0;
var default_document_title = '';

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

	default_document_title = $(document).find('title').text();

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
		window.fluid.dockBadge = '';
		$(document).find('title').text(default_document_title);
	});

});


function do_post()
{
	auth();
	var new_post = new Post;
	if(trim($('.talkbox textarea').val()).length > 0)
	{
		new_post.body = nl2br(htmlentities(trim($('.talkbox textarea').val())));
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
			if(typeof posts_in_timeline[post.id] == "undefined")
			{
				var new_post = new Post;
				new_post.setId(post.id);
				new_post.setBody(urlify(post.body));
				new_post.setCreated(post.created);
				new_post.setAuthor(post.author);
				new_post.setMultiline(new_post.getBody().search(/\r\n|\r|\n/) != -1);
				posts_in_timeline[new_post.getId()] = new_post;

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
						}
					}
				}
			}
		});
		$('.post').emoticonize({ 'animate': false });
		refresh_users_list();
		refresh_links_list();
		refresh_files_list();
	}, 'json');
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
        return '<a class="urlified" href="' + url + '">' + url + '</a>';
    });
    // or alternatively
    // return text.replace(urlRegex, '<a href="$1">$1</a>')
}


/**
 * Handlebars templates
 */
 
Handlebars.registerHelper('humanTime', function(timestamp){
	return 'am ' +  date("d.m.Y", timestamp) + ' um ' + date("H:i", timestamp) + ' Uhr';
});