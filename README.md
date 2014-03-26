
![Ditto.php](./docs/images/dittophp.png)

*Ditto.php* is a php package to mimic a site by a given URL (mimics everything, all pages, images, css, js, etc.).

It also allows you to perform replacements on the pages such as repalce text strings or image paths etc.

# TODO
- [ ] remove config files all together, and go for everything in a main index.php file
- [ ] include as vendor lib rather than as a project
- [ ] but offer a blank project version that requires the main lib via composer as a boilerplate (so 2 github repo's)
- [ ] Refactor to a main Ditto.php class inside of /src/
- [ ] Add simple way to replace text strings
- [ ] Add simple way to replace regex strings
- [ ] Add helper to replace images etc.
- [ ] Add methods for php callbacks before and after urls are loaded
- [ ] Full http methods support (GET, POST, PUT, DELETE)
- [ ] Add tests for replace methods
- [ ] Clean up

## Install
```bash
composer create-project stilliard/Ditto.php-starter-project . dev-master
```
This sets up an index.php file with an example config inside and a .htacess file to route all requests to it.
It also has a composer.json and auto requires in the stilliard/Ditto.php main lib
