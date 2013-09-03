function Post ()
{
	this.id = null;
	this.body = "";
	this.created = 0;
	this.multiline = false;
	this.author = "";
	this.type = "post";
	this.at_me = false;
	
	
	this.setId = function(newId)
	{
		this.id = newId;
	};
	
	this.getId = function()
	{
		return this.id;
	};
	
	this.setBody = function(newBody)
	{
		this.body = newBody;
	}
	
	this.getBody = function()
	{
		return this.body;
	};

	this.setCreated = function(newCreated)
	{
		this.created = newCreated;
	}
	
	this.getCreated = function()
	{
		return this.created;
	};

	this.setMultiline = function(truefalse)
	{
		this.multiline = truefalse;
	}
	
	this.isMultiline = function()
	{
		return this.multiline;
	};
	
	this.setAuthor = function(newAuthor)
	{
		this.author = newAuthor;
	}
	
	this.getAuthor = function()
	{
		return this.author;
	};

	this.setType = function(newType)
	{
		this.type = newType;
	}
	
	this.getType = function()
	{
		return this.type;
	};

	this.setAtMe = function(truefalse)
	{
		this.at_me = truefalse;
	}
	
	this.isAtMe = function()
	{
		return this.at_me;
	};
	


	this.toJson = function()
	{
		var returnValue = {};
		returnValue.id = this.getId();
		returnValue.body = this.getBody();
		returnValue.created = this.getCreated();
		returnValue.multiline = this.isMultiline();
		returnValue.at_me = this.isAtMe();
		returnValue.author = this.getAuthor();
		returnValue.type = this.getType();
		return returnValue;
	}
}