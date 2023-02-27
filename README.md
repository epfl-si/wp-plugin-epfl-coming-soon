# EPFL Coming Soon

A WordPress plugin that allows to display a coming soon / maintenance page.

## Get the latest version

Download the latest release at https://github.com/epfl-si/wp-plugin-epfl-coming-soon/releases/latest.

## Features

  * Simple **ON / OFF** mode
  * Edit coming soon page in the wordpress **WYSIWYG** editor (TinyMCE)
  * Edit the page's title
  * Option to **redirect on the theme** `maintenance.php` page if present (has to be self-contained for now)
  * Option to send a **[HTTP status code 503](https://httpstatuses.com/503)** along with the page
  * Auto-activated if the `.maintenance` file is present (see https://developer.wordpress.org/cli/commands/maintenance-mode/), i.e. `wp cli maintenance-mode activate` : **wp cli compatible**
  * Shows its status in the **rest API**, `wp-json/epfl-coming-soon/v1/status`

## Screenshots

### Backend

![EPFL coming soon screenshot - backend](./screenshot_admin.png)

### Frontend

![EPFL coming soon screenshot - frontend](./screenshot_frontend.png)

## Development

The use of [wp-dev]() environment is highly recommended to have an environment 
similar to what we have in "prod" and avoid any conflicts between plugins.

Please use the [Makefile](./Makefile) to run WordPress-Core coding standards' 
linter.

Any commits should be accompanied with a new version following the 
[SemVer](https://semver.org/) system.

### New release

- [ ] Be sure to bump the version, both in comment and constant.
- [ ] Run the linter with `make phpcbf` and `make phpcs`; fix all the mentionned lines.
- [ ] Check that translations are done (`make pot`).
- [ ] Use `make zip` to create the plugin package without unused dependencies.
- [ ] Tag the version (`git tag -fa v1.1.1 -m v1.1.1`).
- [ ] Push everything and [create a new release](https://github.com/epfl-si/wp-plugin-epfl-coming-soon/releases/new) with comments.

## Issues

Feel free to share your thoughts, issues, remarks and comments here: <https://github.com/epfl-si/wp-plugin-epfl-coming-soon/issues/new/choose>.
