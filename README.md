SearchEngine File Indexer add-on
--------------------------------

This module adds (experimental) file indexing support for the SearchEngine module.

**WARNING** this module is currently considered experimental.

There's a good chance that installing it will cause errors on your site. Please backup your data before installing the module and/or enabling it. If you run into any problems, please open a GitHub issue at https://github.com/teppokoivula/SearchEngineFileIndexer/issues/.

Potential gotchas:

- Files are indexed after page or field has been saved, while SearchEngine is creating the index. Indexing pages with large number of files or large files can take a long time, resulting in timeouts.
- Files can contain a lot of data. Due to this, database level size limit for the index field could be reached.

## Usage

0) Install and configure [SearchEngine](https://github.com/teppokoivula/SearchEngine), version 0.34.0 or later.
1) Install SearchEngineFileIndexer, preferably via Composer (`composer require teppokoivula/search-engine-file-indexer`)
2) If you installed SearchEngineFileIndexer via modules manager or file upload, run `composer install` in the directory of the module
3) Configure SearchEngineFileIndexer

## Installing

This module can be installed by downloading or cloning the SearchEngineFileIndexer directory into the /site/modules/ directory of your site, but the recommended method installign it using Composer: `composer require teppokoivula/search-engine-file-indexer`. Composer installation takes care of dependencies automatically, which makes following steps easier.

## License

This project is licensed under the Mozilla Public License Version 2.0. For licensing of any third party dependencies that this module interfaces with, see their respective README or LICENSE files.
