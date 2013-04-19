uol-private-blog
================

This plugin restricts access to a blog on a multisite network so you have to be logged in to view pages. You can also configure a set of IP addresses or ranges to match client IP addresses if you want to restrict access by IP. As it is trivial to spoof an IP address, this plugin should not be used as in this way to make it private, rather to discourage search engines from indexing it.

The plugin should be Network activated on a Multisite Wordpress installation.

### Network Settings

A list of allowed IP addresses can be added to the Privacy Settings page on the Network Dashboard (under Settings). Full IP addresses or partial IP addresses are allowed here, and are added to a text box, one per line. If the setting is enabled on the blog, client IP addresses are pattern-matched against the stored list, and if one of the allowed IP addresses or IP address fragments matches the start of the client IP address, the site is accessible by the client (without login).

### Blog settings

Each blog on the network can be made private using the Privacy Settings page in the Settings menu of the blog Dashboard. This page contains two checkboxes, one to activate the privacy setting for the blog to only allow logged-in users to view the site, and another to allow network users to view the site (if this setting is configured at the Network level). If IP address filtering is not used, the second checkbox is hidden.
