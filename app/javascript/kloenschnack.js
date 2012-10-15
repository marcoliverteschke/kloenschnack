var post_template_source = null;
var post_template = null;

var posts_queue = null;
var posts_in_timeline = new Array();

/* config parameters */
var refresh_timeline_millis = 2000;
var process_queue_millis = 1000;

$(function(){

	post_template_source = $('#post-template').html();
	post_template = Handlebars.compile(post_template_source);
	
	posts_queue = new PostsQueue;

	refresh_timeline();
	window.setInterval(refresh_timeline, refresh_timeline_millis);

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

	$('#fileupload').fileupload({});

});


function do_post()
{
	var new_post = new Post;
	new_post.body = nl2br(htmlentities(trim($('.talkbox textarea').val())));
	posts_queue.pushPostToQueue(new_post);
	if(posts_queue.isProcessing() == false)
	{
		posts_queue.setProcessing(true);
		window.setInterval(posts_queue.process, process_queue_millis);
	}
	$('.talkbox textarea').val('');
}


function refresh_timeline()
{
	$.get('/server/post', function(data){
		_.each(data, function(post){
			if(typeof posts_in_timeline[post.id] == "undefined")
			{
				var new_post = new Post;
				new_post.setId(post.id);
				new_post.setBody(post.body);
				new_post.setCreated(post.created);
				new_post.setMultiline(new_post.getBody().search(/\r\n|\r|\n/) != -1);
				posts_in_timeline[new_post.getId()] = new_post;
				var output = post_template(new_post.toJson());
				if(output.length > 0)
				{
					$('.timeline').append(output);
					scroll_to_bottom();
				}
			}
		});
	}, 'json');
}


function scroll_to_bottom()
{
	$('body,html').scrollTop($(document).height());
}


/**
 * Handlebars templates
 */
 
Handlebars.registerHelper('humanTime', function(timestamp){
	return 'am ' +  date("d.m.Y", timestamp) + ' um ' + date("H:i", timestamp) + ' Uhr';
});