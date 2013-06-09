# php organization packages

A simple two-step script to display all php packages used by all organization's projects.

## Autoload

This project needs [Composer](http://getcomposer.org).
The first step to use `org-packages` is to download composer:

```bash
$ curl -s http://getcomposer.org/installer | php
```

Then we have to install our dependencies using:

```bash
$ php composer.phar install
```

## Basic usage of `org-packages` client
### First step: retrieve packages in CLI mode

```sh
php bin/retrieveOrganizationProjects.php org:packages <organization_name>
```

If we want to get packages from a private organization, we can use an authentication
token. We need to have a valid authentication. If we have rights to access private
organization, we can generate a valid token with https://github.com/settings/applications
menu (Personal API Access Tokens)

### Second step : see results

We need to access www/index.php via web server to see packages table