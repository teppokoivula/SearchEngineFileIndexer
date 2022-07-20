<?php namespace ProcessWire;

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

/**
 * SearchEngine File Indexer add-on
 *
 * This module adds (experimental) file indexing support for the SearchEngine module.
 *
 * Please note that in order to parse files, we need to install some third party PHP dependencies:
 *
 * - smalot/pdfparser and spatie/pdf-to-text for parsing pdf files
 * - phpoffice/phpspreadsheet for parsing common spreadsheet files
 * - phpoffice/phpword for parsing office files (doc(x), rtf, odf)
 *
 * Dependencies are automatically installed along with this module when you install it via Composer, but if you install
 * the module via file upload or using the modules manager in admin, you need to run composer install in the module
 * directory after installing.
 *
 * @license Mozilla Public License v2.0 http://mozilla.org/MPL/2.0/
 */
class SearchEngineFileIndexer extends WireData implements Module, ConfigurableModule {

    /**
     * Available file indexers
     *
     * @var array
     */
    protected $file_indexers = [
        'FileIndexerPhpSpreadsheet',
        'FileIndexerPhpWord',
        'FileIndexerPdfParser',
        'FileIndexerPdfToText',
        'FileIndexerPlainText',
    ];

    /**
     * Module info
     *
     * @return array
     */
    public static function getModuleInfo() {
        return [
            'title' => 'SearchEngine File Indexer',
            'summary' => 'SearchEngine add-on for indexing files (experimental)',
            'icon' => 'file-text-o',
            'version' => '0.0.1',
            'requires' => 'PHP>=7.4, ProcessWire>=3.0.164, SearchEngine>=0.33.0',
            'autoload' => true,
        ];
    }

    /**
     * Init method
     */
    public function init() {

        // Init class autoloader
        $this->wire('classLoader')->addNamespace(
            'SearchEngine\FileIndexer',
            $this->wire('config')->paths->SearchEngineFileIndexer . 'lib/'
        );

        if ($this->enabled_file_indexers) {
            $this->addHookAfter('Indexer::getPagefileIndexValue', $this, 'pagefileIndexValueHook');
        }
    }

    /**
     * If Pagefile is of a suitable document type, attempt to index it
     *
     * @param HookEvent $event
     */
    protected function pagefileIndexValueHook(HookEvent $event) {
        $indexed_file = $this->indexFile($event->arguments[0]);
        if ($indexed_file) {
            $event->return = implode(' ... ', array_filter([
                $event->return,
                $indexed_file,
            ]));
        }
    }

    /**
     * If Pagefile is of a suitable document type, attempt to index it
     *
     * @param string|Pagefile $file
     * @return string|null
     */
    protected function indexFile($file): ?string {

        // Validate file
        $file_info = $this->getFileInfo($file);
        if (!$this->isValidFile($file_info)) {
            return null;
        }

        // Attempt to get file indexer
        $file_indexer = $this->getFileIndexer($file_info);
        if ($file_indexer === null) {
            return null;
        }

        // Attempt to read file data using file indexer
        try {
            $text = $file_indexer->getText($file);
        } catch (\Exception $e) {
            $this->log->error(sprintf(
                'SearchEngineFileIndexer::%s error for file at %s: %s',
                $file_indexer['method'],
                $file_info['filename'],
                $e->getMessage()
            ));
            return null;
        }

        // Prepare and return file data
        return $this->prepareText($text, $file_info);
    }

    /**
     * Add new file indexer
     *
     * @param string $file_indexer
     *
     * @throws WireException if file indexer class cannot be located
     */
    public function addFileIndexer(string $file_indexer) {
        if (!in_array($file_indexer, $this->file_indexers)) {
            $file_indexer_class = '\SearchEngine\FileIndexer\\' . $file_indexer;
            if (!class_exists('\SearchEngine\FileIndexer\\' . $file_indexer)) {
                throw new WireException(sprintf(
                    'File indexer not found: %s (%s)',
                    $file_indexer,
                    $file_indexer_class
                ));
            }
            $this->file_indexers[] = $file_indexer;
        }
    }

    /**
     * Attempt to fetch a file indexer for a file
     *
     * @param array $file_info
     * @return \SearchEngine\FileIndexer|null
     */
    protected function ___getFileIndexer(array $file_info): ?\SearchEngine\FileIndexer\FileIndexer {
        foreach ($this->enabled_file_indexers as $file_indexer) {
            $file_indexer_class = '\SearchEngine\FileIndexer\\' . $file_indexer;
            $file_indexer_info = $file_indexer_class::getFileIndexerInfo();
            if ($file_indexer_info['available'] && in_array(strtolower($file_info['ext']), $file_indexer_info['extensions'])) {
                return $this->wire(new $file_indexer_class());
            }
        }
        return null;
    }

    /**
     * Validate file
     *
     * @param array $file_info
     * @return bool
     */
    protected function ___isValidFile(array $file_info): bool {
        $max_file_size = $this->getValuesByType('max_file_size', $file_info['ext']);
        return !$max_file_size || $file_info['size'] <= $max_file_size;
    }

    /**
     * Prepare text for storage
     *
     * @param string $text
     * @param array $file_info
     * @return string $text
     */
    protected function ___prepareText(string $text, array $file_info): string {
        $max_text_length = $this->getValuesByType('max_text_length', $file_info['ext']);
        if ($max_text_length && mb_strlen($text) > $max_text_length) {
            $text = mb_substr($text, 0, $max_text_length);
        }
        return $text;
    }

    /**
     * Get info about provided file
     *
     * @param string|Pagefile $file
     * @return array
     *
     * @throws WireException if file argument is of wrong type
     */
    protected function ___getFileInfo($file): array {
        $is_pagefile = $file instanceof Pagefile;
        if (!$is_pagefile && !is_string($file)) {
            throw new WireException(sprintf(
                'Invalid file argument, expected string|Pagefile and got %s',
                gettype($file)
            ));
        }
        return [
            'filename' => $is_pagefile ? $file->filename : $file,
            'pagefile' => $is_pagefile ? $file : null,
            'size' => $is_pagefile ? $file->filesize : filesize($file),
            'ext' => $is_pagefile ? $file->ext : pathinfo($file, \PATHINFO_EXTENSION),
        ];
    }

    /**
     * Get file type specific values from a textarea
     *
     * @param string $key
     * @param string|null $return_type
     * @return null|array|int
     */
    protected function getValuesByType(string $key, ?string $return_type = null) {
        if (empty($this->get($key))) {
            return null;
        }
        $values = [];
        $rows = preg_split("/\r\n|\n|\r/", $this->get($key));
        if ($rows) {
            foreach ($rows as $row) {
                $row = trim($row);
                if (empty($row)) continue;
                $row_parts = array_filter(explode(' ', $row));
                if (!is_numeric($row_parts[0])) {
                    $this->error(sprintf(
                        $this->_('Possible configuration issue with %s, first value of a row isn\'t numeric: %s'),
                        $key,
                        $row
                    ));
                    continue;
                }
                $value = (int) array_shift($row_parts);
                if (empty($row_parts)) {
                    $values['*'] = $value;
                } else {
                    foreach ($row_parts as $type) {
                        $values[strtolower($type)] = $value;
                    }
                }
            }
        }
        if (!empty($values)) {
            return $return_type === null ? $values : $values[$return_type] ?? $values['*'] ?? null;
        }
        return null;
    }

    /**
     * Module config inputfields
     *
     * @param InputfieldWrapper $inputfields
     */
    public function getModuleConfigInputfields(InputfieldWrapper $inputfields) {

        /** @var InputfieldAsmSelect */
        $enabled_file_indexers = $this->modules->get('InputfieldAsmSelect');
        $enabled_file_indexers->name = 'enabled_file_indexers';
        $enabled_file_indexers->label = $this->_('Enabled indexing methods');
        $inputfields->add($enabled_file_indexers);
        $num_available_file_indexers = 0;
        foreach ($this->file_indexers as $file_indexer) {
            $file_indexer_class = '\SearchEngine\FileIndexer\\' . $file_indexer;
            $file_indexer_info = $file_indexer_class::getFileIndexerInfo();
            $enabled_file_indexers->addOption(
                $file_indexer,
                $file_indexer_info['label'] . ' (' . implode(', ', $file_indexer_info['extensions']) . ')',
                ['disabled' => !$file_indexer_info['available']]
            );
            if ($file_indexer_info['available']) {
                ++$num_available_file_indexers;
            }
            /** @var InputfieldFieldset */
            $file_indexer_settings = new InputfieldFieldset;
            $file_indexer_settings->label = $file_indexer_info['label'];
            $file_indexer_settings->icon = $file_indexer_info['icon'] ?? 'cog';
            $file_indexer_settings->showIf('enabled_file_indexers=' . $file_indexer);
            $file_indexer_class::getFileIndexerConfigInputfields($file_indexer_settings);
            if ($file_indexer_settings->count()) {
                foreach ($file_indexer_settings as $file_indexer_setting) {
                    $file_indexer_setting->value = $this->get($file_indexer_setting->name);
                }
                $inputfields->add($file_indexer_settings);
            }
        }
        $enabled_file_indexers->value = $this->enabled_file_indexers ?? null;
        if ($num_available_file_indexers) {
            $enabled_file_indexers->notes = $this->_('Note that if you enable more than one indexer for specific file extension, only the first one will be used.');
        } else {
            $enabled_file_indexers->notes = sprintf(
                $this->_('There are currently no indexing methods available. Please make sure that you have installed this module via Composer or executed `composer install` in the module\'s directory (`%s`).'),
                $this->config->paths->SearchEngineFileIndexer
            );
        }

        /** @var InputfieldTextarea */
        $max_file_size = $this->modules->get('InputfieldTextarea');
        $max_file_size->name = 'max_file_size';
        $max_file_size->label = $this->_('Maximum file size to process');
        $max_file_size->description = $this->_('In order to avoid memory or execution time running out while indexing individual files, you can define maximum file size to process. You can use more than one value if you want to specify limit per file type.');
        $max_file_size->notes = $this->_('Place each value on a separate line, with optional extension (or extensions) after the limit value. Last line without extensions specified will be used as the default value:')
            . "\n\n```1048576 pdf\n3145728 doc docx odf rtf\n5242880```\n\n"
            . $this->_('Provide memory limit in bytes. Leave empty or provide value `0` to disable limit. `1048576` = 1 MiB, `5242880` = 5 MiB, `31457280` = 30 MiB, etc.');
        $max_file_size->value = $this->max_file_size;
        $inputfields->add($max_file_size);

        /** @var InputfieldTextarea */
        $max_text_length = $this->modules->get('InputfieldTextarea');
        $max_text_length->name = 'max_text_length';
        $max_text_length->label = $this->_('Maximum length of text to index for each file');
        $max_text_length->description = $this->_('In order to avoid database storage restrictions for index field, you can define maximum length of processed text to store in index. You can use more than one value if you want to specify limit per file type.');
        $max_text_length->notes = $this->_('Place each value on a separate line, with optional extension (or extensions) after the limit value. Last line without extensions specified will be used as the default value:')
            . "\n\n```1048576 pdf\n3145728 doc docx odf rtf\n5242880```\n\n"
            . $this->_('Provide length limit as number of characters.');
        $max_text_length->value = $this->max_text_length;
        $inputfields->add($max_text_length);
    }

}
