# Contributors welcome!

Thank you very much for your interest in this project! There are plenty of ways you can support us. :-)

## Code of Conduct

We like you to read and follow our [code of conduct](CODE_OF_CONDUCT.md) before contributing. Thank you.

## Use it

The best and (probably) easiest way to help is to use this library in your own projects. It would be very nice to share your thoughts with us. We love to hear from you.

If you have questions how to use it properly read the [documentation](README.md) carefully.

## Report bugs

If you find something strange please report it to [our issue tracker][issues].

## Make a wish

Of course, there are some features in the pipeline. However, if you have good ideas how to improve this application please let us know! Write a feature request [in our issue tracker][issues].

## Setup a development environment

If you like to contribute source code, documentation snippets, self-explaining examples or other useful bits, fork this repository, setup the environment and make a pull request.

~~~ {.bash}
git clone https://github.com/bheisig/i-doit-cli.git
~~~

If you have a GitHub account create a fork first and then clone the repository.

After that, change to your cloned repository and setup the environment with Composer:

~~~ {.bash}
cd i-doit-cli/
composer install
~~~

Now it is the time to do your stuff. Do not forget to commit your changes. When you are done consider to make a pull requests.

Notice, that any of your contributions merged into this repository will be [licensed under the AGPLv3](LICENSE).

## Requirements

Developers must meet these requirements:

-   See requirements mentioned in the [documentation](README.md)
-   [Composer](https://getcomposer.org/)
-   [Git](https://git-scm.com/)

## Run it!

To run `idoitcli` you do not need to build a binary first. Just run:

~~~ {.bash}
bin/idoitcli
~~~

## Add your own command

Each command has its own PHP class located under `src/Command`. There is a dummy class you can use as a skeleton. Copy it:

~~~ {.bash}
cd src/Command/
cp Dummy.php MyCommand.php
~~~

Note, that the file name must be in camel-case and it ends with `.php`.

Edit this file with your favorite editor. The class name must be the same as the file name (without the file extension). The entry point is the public method `execute()`.

Next step is to register your new command. This is done inside `bin/idoitcli.php`. Add something like this:

~~~ {.php}
$app
    ->addCommand(
        'my-command',
        __NAMESPACE__ . '\\Command\\MyCommand',
        'Dummy command to print "Hello, World!"'
    );
~~~

Now you are able to execute your command:

~~~ {.bash}
bin/idoitcli my-command
~~~

Further steps:

-   Add options to your command with `$app->addOption()`
-   Overwrite public method `showUsage()` to print usage with option `--help`

## Release new version

…to the public. You need commit rights for this repository.

1.  Bump version: `composer config extra.version <VERSION>`
3.  Keep [`CHANGELOG.md`](CHANGELOG.md) up-to-date
4.  Commit changes: `git commit -a -m "Bump version to $(composer config extra.version)"`
5.  Perform some tests, for example `composer ci`
6.  Build binary file: `composer build`
7.  Create distribution tarball: `composer dist`
8.  Build PHIVE files: `composer phive`
9.  Create Git tag: `git tag -s -a -m "Release version $(composer config extra.version)" $(composer config extra.version)`
10. Push changes: `git push --follow-tags`
11. Create new release on GitHub based on the last tag
12. Upload these files and add them to the release:
    -   Distribution tarball: `idoitcli-<VERSION>.tar.gz`
    -   Binaray file: `idoitcli`
    -   PHIVE files: `idoitcli.phar`, `idoitcli.phar.asc`
13. Cleanup project directory: `composer clean`

If any step produces an error please think twice before releasing. ;-)

## Create OS-related packages

At the moment, only Debian GNU/Linux is supported:

1. Make sure you have [`fpm`](https://github.com/jordansissel/fpm) installed
2. Build binary file: `composer build`
3. Create .deb package file: `composer deb`

This results in a file `idoitcli_<VERSION>_all.deb`.

## Composer scripts

This project comes with some useful composer scripts:

| Command                       | Description                                               |
| ----------------------------- | --------------------------------------------------------- |
| `composer ci`                 | Perform continuous integration tasks                      |
| `composer clean`              | Cleanup project directory                                 |
| `composer build`              | Create a binary                                           |
| `composer deb`                | Create a Debian GNU/Linux package                         |
| `composer dist`               | Create a distribution tarball                             |
| `composer find-forbidden`     | Find forbidden words in source code                       |
| `composer gitstats`           | Create Git statistics                                     |
| `composer gource`             | Visualize Git history                                     |
| `composer is-built`           | Test whether binary is already built                      |
| `composer lint`               | Perform all lint checks                                   |
| `composer lint-php`           | Check syntax of PHP files                                 |
| `composer lint-json`          | Check syntax of JSON files                                |
| `composer lint-xml`           | Check syntax of XML files                                 |
| `composer lint-yaml`          | Check syntax of YAML files                                |
| `composer phive`              | Build PHIVE files                                         |
| `composer phpcompatibility`   | Run PHP compatibility checks                              |
| `composer phpcpd`             | Detect copy/paste in source code                          |
| `composer phpcs`              | Detect violations of defined coding standards             |
| `composer phploc`             | Print source code statistics                              |
| `composer phpmd`              | Detect mess in source code                                |
| `composer phpmnd`             | Detect magic numbers in source code                       |
| `composer phpstan`            | Analyze source code                                       |
| `composer security-checker`   | Look for dependencies with known security vulnerabilities |
| `composer system-check`       | Run some system checks                                    |
| `composer test`               | Perform some tests with the built binary                  |

For example, execute `composer ci`.

## Donate

Last but not least, if you think this project is useful for your daily work, consider a donation. What about a beer?

## Further reading

-   [How to Contribute to Open Source](https://opensource.guide/how-to-contribute/)

[issues]: https://github.com/bheisig/i-doit-cli/issues
