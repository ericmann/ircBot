ircBot
======

An open-source IRC bot written in PHP. Handles plugins, database support and cron-based tasks.

Features
--------

* A full plugin system that offers an API for registering hooks within the bot. This hook system is basically the same as WordPress. Since the bot originated in my work environment, it was important that we had something for our other developers to easily contribute code to. Support for: `addAction()`, `doAction()`, `removeAction()`, `applyFilter()`, `addFilter()`, `removeFilter()`
* A cron-based task system that allows users to register/remove cron jobs with the bot. This is extremely useful for checking for news or status updates via RSS or APIs, etc. There are many things that we can do with this cron-based task system.
* A streamlined, completely custom IRC framework for the sole purpose of handling IRC bots. This framework has been written based off of the needs from my original IRC bot. Previously, we used SmartIRC and after rewriting this, we eliminated unnecessary lines of code.

Todo
----

* Test the usage of database-driven plugins to ensure that things are working correctly with this. Providing a sample plugin with examples on how the database connections should work would probably be in the best interest of everyone.
* Incorporate some basic bot commands including !restart, !kill, !join <channel>, !part <channel>, etc.
* We will need to thoroughly test the !restart command to ensure that it's killing old PHP processes and correctly performing any subversion pulls. Providing multiple OS restart scripts is probably in the best interest of everyone.
* Thorough commenting - have been in a rush to get it up and running.
* Plugin for WordPress Trac support.

How to Use
----------

* `ircBot::sendChannelMessage()` - sends a message to the specified channel. ircBot will make sure that it is currently in this channel before trying to send this message.
* `pluginManager::doAction()` - performs an action hook and passes the specified arguments to all registered hooks.
* `pluginManager::addAction()` - registers your callback to the action hooks
* `pluginManager::removeAction()` - removes a specified action
* `pluginManager::applyFilter()` - applies all filters to specified data
* `pluginManager::addFilter()` - registers your callback to the filter hooks
* `pluginManager::removeFilter()` - removes a specified filter
* `cronSystem::register()` - registers your cron job with the cron system
* `cronSystem::remove()` - removes the specified cron job if it exists.
* `Database::connect()` - connects to the MySQL server using the settings in your config file.
* `Database::disconnect()` - disconnects from the MySQL server if it was previously connected
* `Database::query()` - performs a manual query and returns the results.
* `Database::get()` - gets data from a table
* `Database::delete()` - deletes data from a table
* `Database::insert()` - inserts the specified data into the table
* `Database::update()` - updates data in the specified table with the new data passed
* `Database::where()` - handles adding a where clause to each MySQL query


Contributing
------------

1. Fork it.
2. Create a branch (`git checkout -b my_branch`)
3. Commit your changes (`git commit -am "Added some sweet stuff"`)
4. Push to the branch (`git push origin my_branch`)
5. Open a [Pull Request][1]
6. Enjoy a refreshing Mountain Dew and wait

[1]: https://github.com/carldanley/ircBot/pulls
