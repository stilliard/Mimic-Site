
![Ditto.php](./docs/images/dittophp.png)

*Ditto.php* is a php package to mimic a site by a given URL (mimics everything, all pages, images, css, js, etc.).

It also allows you to perform replacements on the pages such as repalce text strings or image paths etc.

## Install
```bash
composer create-project stilliard/Ditto.php . dev-master
```
Now edit the config.json file for your setup

## config.js docs
TODO

### Todo
- Tidy up the main index fie / refactor it a bit
- Clean up the file/directory structure
- Refactor methods to Ditto.php class file
- Add support for REGEX replacements! :D
- Consider alternative config file type instead of xml. (im not keen on on having to use <![CDATA[ for all the HTML fragments)
