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
var previews_aging_millis = 1000;
var previews_age_to_shrink = 300000;
var refresh_viewed_millis = 10000;


$(function(){
	post_template_source = $('#post-template').html();
	post_template = Handlebars.compile(post_template_source);
	event_template_source = $('#event-template').html();
	event_template = Handlebars.compile(event_template_source);
	list_entry_template_source = $('#list-entry-template').html();
	list_entry_template = Handlebars.compile(list_entry_template_source);

	posts_queue = new PostsQueue;

	if($('.timeline').length > 0)
	{
		refresh_timeline();
		refresh_viewed();
		window.setInterval(refresh_timeline, refresh_timeline_millis);
		window.setInterval(refresh_users_list, refresh_users_list_millis);
		window.setInterval(age_preview_images, previews_aging_millis);
		window.setInterval(refresh_viewed, refresh_viewed_millis);
	}

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

		var visible_entries_guids = [];
		$('.timeline').find('.timeline-entry').each(function (i, e) {
			visible_entries_guids[visible_entries_guids.length] = $(e).data('guid');
		});
		$.post('/post/view', {'guids': visible_entries_guids}, function () {});
	});

	$('.stati a').click(function(){
		$.post('/user/status/update', {'status' : $(this).attr('data-status')});
	});

	if(typeof hits != 'undefined' && hits.length > 0)
	{
		add_posts_to_timeline(hits, '#hits');
	}

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
		add_post_to_timeline(new_post, '.timeline');
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
			add_post_to_timeline(post, '.timeline');
		});
		refresh_previews();
		refresh_users_list();
		refresh_links_list();
		refresh_files_list();
	}, 'json');
}


function refresh_viewed() {
	auth();

	var visible_entries_guids = [];
	$('.timeline').find('.timeline-entry').each(function (i, e) {
		visible_entries_guids[visible_entries_guids.length] = $(e).data('guid');
	});

	$.post('/post/viewed', {'guids': visible_entries_guids}, function (data) {
		$.each(data, function(guid, users) {
			if(users.users.length > 0) {
				if($('.timeline-entry[data-guid=' + guid + '] .viewed-by').length == 0 || $('.timeline-entry[data-guid=' + guid + '] .viewed-by').data('changed') != users.changed_hash) {
					$('.timeline-entry[data-guid=' + guid + '] .viewed-by').remove();
					$('.timeline-entry[data-guid=' + guid + ']').append('<div class="viewed-by" data-changed="' + users.changed_hash + '"><span class="them">' + users.users.join(', ') + '</span><i class="icon-eye-open"></i></div>');
				}
			}
		});
	});
}


function age_preview_images()
{
	var now = Date.now();
	$('.preview').each(function(i, e){
		if(!$(e).parents('.file').hasClass("aged"))
		{
			var created_ts = $(e).parents('.timeline-entry').attr('data-created');
			if(typeof created_ts != "undefined")
			{
				var created_millis = created_ts * 1000;

				if((now - created_millis) > previews_age_to_shrink)
				{
					$(e).parents('.file').addClass("aged");
				}
			}
		}
	});
}


function add_posts_to_timeline(posts, timeline_identifier)
{
	var output = "";
	for(post_key in posts)
	{
		var post = posts[post_key];
		var new_post = new Post;
		new_post.setBody(hashtagify(urlify(post.body)));
		new_post.setCreated(post.created);
		new_post.setAuthor(post.author);
		new_post.setMultiline(new_post.getBody().search(/\r\n|\r|\n/) != -1);
		new_post.setAtMe(post.at_me);
		new_post.setGuid(post.guid);

		if(post.type == 'event') {
			output += event_template(new_post.toJson());
		} else {
			output += post_template(new_post.toJson());
		}
	}

	if(output.length > 0)
	{
		$(timeline_identifier).append(output);
		$('.post').emoticonize({ 'animate': false });
	}
}


function add_post_to_timeline(post, timeline_identifier) {
	if(typeof posts_in_timeline[post.guid] == "undefined")
	{
		var new_post = new Post;
		new_post.setBody(hashtagify(urlify(post.body)));
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
			$(timeline_identifier).append(output);
			$('.post').emoticonize({ 'animate': false });
			scroll_to_bottom();
			if(!window_in_focus)
			{
				unread_posts++;
				$(document).find('title').text('(' + unread_posts + ') ' + default_document_title);
				if(post.at_me) {
					notify(post.author, post.body, 1, false, "foo");
				}
				if(typeof window.fluid != "undefined")
				{
					window.fluid.dockBadge = unread_posts;
				}
			}
		}
	}
}


function notify(title, description, priority, sticky, identifier)
{
	if(typeof Audio != "undefined") {
		var blip = new Audio("/sounds/blip.wav");
		if(typeof blip.play != "undefined") {
			blip.play();
		}
	}

	if(typeof window.fluid != "undefined")
	{
		window.fluid.showGrowlNotification({
			title: title,
			description: description,
			priority: priority,
			sticky: sticky,
			identifier: identifier
		});
	}


/*	if (!("Notification" in window)) {
		// check for Fluid/Growl integration, otherwise do nothing
		if(typeof window.fluid != "undefined")
		{
			window.fluid.showGrowlNotification({
				title: title,
				description: description,
				priority: priority,
				sticky: sticky,
				identifier: identifier
			});
		}
	} else if (Notification.permission === "granted") {
		// Let's check if the user is okay to get some notification
		// If it's okay let's create a notification
		var notification = new Notification(title, {
			body: description
		});
		notification.show();
	} else if (Notification.permission !== 'denied') {
		// Otherwise, we need to ask the user for permission
		// Note, Chrome does not implement the permission static property
		// So we have to check for NOT 'denied' instead of 'default'
		Notification.requestPermission(function (permission) {
			// Whatever the user answers, we make sure we store the information
			if(!('permission' in Notification)) {
				Notification.permission = permission;
			}

			// If the user is okay, let's create a notification
			if (permission === "granted") {
				var notification = new Notification(title, {
					body: description
				});
				notification.show();
			}
		});
	}*/
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
	 * via http://stackoverflow.com/questions/8188645/javascript-regex-to-match-a-url-in-a-field-of-text
	 */
	var urlRegex = /(http|ftp|https):\/\/[\w-]+(\.[\w-]+)+([\w.,@?^=%&amp;:\/~+#-]*[\w@?^=%&amp;\/~+#-])?/g;
    return text.replace(urlRegex, function(url) {
        return '<a class="urlified" href="' + url + '" target="_blank">' + url + '</a>';
    });
}


function hashtagify(text)
{
	/*
	 * via http://stackoverflow.com/questions/1500260/detect-urls-in-text-with-javascript
	 */
//    var hashtagRegex = /(^|\s)+(#([a-zA-Z0-9_äöüÄÖÜß]+|\b))/g; // selektiert im Moment den Whitespace mit, muss noch besser werden!
    var hashtagRegex = /(^|[^\S])(#([a-zA-Z0-9_äöüÄÖÜß]+|\b))([^\S]|$)/g; // selektiert im Moment den Whitespace mit, muss noch besser werden!
//	console.log(text.match(hashtagRegex));
    return text.replace(hashtagRegex, function(hashtag) {
        return ' <a class="hashtagified" href="/archive?search=' + encodeURIComponent(trim11(hashtag)) + '">' + trim11(hashtag) + '</a> ';
    });
	return text;
}


function trim11 (str) {
    str = str.replace(/^\s+/, '');
    for (var i = str.length - 1; i >= 0; i--) {
        if (/\S/.test(str.charAt(i))) {
            str = str.substring(0, i + 1);
            break;
        }
    }
    return str;
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