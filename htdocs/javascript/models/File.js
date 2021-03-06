function File ()
{
	this.id = null;
	this.name = "";
	this.type = "";
	this.size = 0;
	this.hash = "";
	this.created = 0;

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
	
	this.toJson = function()
	{
		var returnValue = {};
		returnValue.id = this.getId();
		returnValue.body = this.getBody();
		returnValue.created = this.getCreated();
		returnValue.multiline = this.isMultiline();
		return returnValue;
	}
}