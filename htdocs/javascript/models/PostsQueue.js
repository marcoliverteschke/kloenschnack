function PostsQueue()
{
	this.posts = new Array();
	this.processing = false;
	this.communicatingWithServer = false;
	_this = this;
	
	this.setPosts = function(newPosts)
	{
		this.posts = newPosts;
	}
	
	this.getPosts = function()
	{
		return this.posts;
	}
	
	this.setProcessing = function(truefalse)
	{
		this.processing = truefalse;
	}
	
	this.isProcessing = function()
	{
		return this.processing;
	}

	this.setCommunicatingWithServer = function(truefalse)
	{
		this.communicatingWithServer = truefalse;
	}
	
	this.isCommunicatingWithServer = function()
	{
		return this.communicatingWithServer;
	}

	this.pushPostToQueue = function(newPost)
	{
		this.posts.push(newPost);
	}

	this.shiftFirstPostFromQueue = function()
	{
		this.posts.shift();
	}
	
	this.process = function()
	{
		if(_this.getPosts().length > 0 && _this.isCommunicatingWithServer() == false)
		{
			var send_to_server = _this.getPosts()[0];
			_this.setCommunicatingWithServer(true);
			$.post('/post/create', { body : send_to_server.body, created : send_to_server.created }, function(data){
				_this.shiftFirstPostFromQueue();
				_this.setCommunicatingWithServer(false);
			});
		}
	}
	
}