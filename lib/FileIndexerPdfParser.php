<?php

namespace SearchEngine\FileIndexer;

/**
 * PdfParser file indexer
 *
 * @version 0.1.0
 * @author Teppo Koivula <teppo.koivula@gmail.com>
 * @license Mozilla Public License v2.0 https://mozilla.org/MPL/2.0/
 */
class FileIndexerPdfParser extends FileIndexer {

    /**
     * Get info about this file indexer
     *
     * @return array
     */
    public static function getFileIndexerInfo() {
        return [
            'label' => 'PDF parser',
            'icon' => 'file-pdf-o',
            'available' => class_exists('\Smalot\PdfParser\Parser'),
            'extensions' => [
                'pdf',
            ],
        ];
    }

    /**
     * Get config inputfields for this file indexer
     *
     * @param \ProcessWire\InputfieldFieldset $fieldset
     */
    public static function getFileIndexerConfigInputfields(\ProcessWire\InputfieldFieldset $fieldset) {
        $fieldset->add([
            [
                'name' => 'file_indexer_pdf_parser_decode_memory_limit',
                'type' => 'InputfieldInteger',
                'label' => \ProcessWire\__('Memory limit for decoding operations'),
                'description' => \ProcessWire\__('In order to avoid memory running out while indexing individual files, you can set a lower memory limit for decoding operations.'),
                'notes' => \ProcessWire\__('Provide value in bytes or leave empty to disable limit. `1048576` = 1 MiB, `5242880` = 5 MiB, `31457280` = 30 MiB etc.'),
            ]
        ]);
    }

    /**
     * Return file content as text
     *
     * @param \ProcessWire\Pagefile $file
     * @return string|null
     */
    public function getText(\ProcessWire\Pagefile $file): ?string {
        $config = new \Smalot\PdfParser\Config();
        $decode_memory_limit = $this->modules->getConfig('SearchEngineFileIndexer', 'file_indexer_pdf_parser_decode_memory_limit');
        if (!empty($decode_memory_limit)) {
            $config->setDecodeMemoryLimit((int) $decode_memory_limit);
        }
        $config->setRetainImageContent(false);
        $parser = new \Smalot\PdfParser\Parser([], $config);
        $pdf = $parser->parseFile($file->filename);
        // Call PHP garbage collector after parsing file to alleviate memory leak issue (see
        // https://github.com/smalot/pdfparser/issues/104 for more details).
        gc_collect_cycles();
        return $pdf->getText();
    }

}
