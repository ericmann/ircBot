ircBot
======

An open-source IRC bot written in PHP. Handles plugins, database support and cron-based tasks.

Todo
----

* Add cron-based support for plugins that need to register cron jobs with the ircBot. This is actually already integrated into the current private version of the ircBot and is only a simple rewrite/review away from being integrated into this version of the bot.
* Test the usage of database-driven plugins to ensure that things are working correctly with this. Providing a sample plugin with examples on how the database connections should work would probably be in the best interest of everyone.
* Incorporate some basic bot commands including !restart, !kill, !join <channel>, !part <channel>, etc.

Contributing
------------

1. Fork it.
2. Create a branch (`git checkout -b my_branch`)
3. Commit your changes (`git commit -am "Added some sweet stuff"`)
4. Push to the branch (`git push origin my_branch`)
5. Open a [Pull Request][1]
6. Enjoy a refreshing Diet Coke and wait

[1]: http://github.com/github/markup/pulls