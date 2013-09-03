# kloenschnack

## Simple Team Messaging

### Libraries & Tools

#### App

* FontAwesome
* LESSPHP
* jquery.fileupload.js
* many PHP.js scripts
* handlebars.js
* jquery.cookies.js
* jquery.js
* modernizr.js
* underscore.js
* Minify
* LESSElements

#### Server

* FlightPHP
* RedBean

### Currently in the works

Posts can be marked to be directed at a specific user-id. When said user's timeline is refreshed, the post appears highlighted.
Display works. Step 2: detection of messages beginning with @username:, marking them with the corresponding user-id. Step 3: client-side detection "it seems you are composing an @-message, here's a list of users." to reduce the risk of false entries.
Plus, usernames in the list of currently online users should be clickable for instant @-replies.

### ToDo

* implement archive page
* implement search function
* add progress bar to uploader
* test file upload with a variety of files
* implement user management functions
* allow users to log-in from multiple locations?

### ToDone

* API (for CVSBot)
* conducted CSS facelift for tabs/drawers, input bar
* added emoticons http://os.alfajango.com/css-emoticons/
* updated LessPHP, Handlebars, jQuery, Underscore, Less Elements, RedBean
* implement list of logged-in users
* implement logout functionality
* integrate link-detection
* events (login, logout)
* list 5 most recent files
* list 5 most recent links
