<?php

namespace SearchEngine\FileIndexer;

/**
 * PdfToText file indexer
 *
 * Note that this file indexer depends on spatie/pdf-to-text, which in turn requires the pdftotext CLI tool to be
 * installed on your OS. Check out the README at https://github.com/spatie/pdf-to-text for more details.
 *
 * @version 0.1.0
 * @author Teppo Koivula <teppo.koivula@gmail.com>
 * @license Mozilla Public License v2.0 https://mozilla.org/MPL/2.0/
 */
class FileIndexerPdfToText extends FileIndexer {

    /**
     * Get info about this file indexer
     *
     * @return array
     */
    public static function getFileIndexerInfo() {
        return [
            'label' => 'PdfToText',
            'icon' => 'file-pdf-o',
            'available' => class_exists('\Spatie\PdfToText\Pdf'),
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
                'name' => 'file_indexer_pdf_to_text_timeout',
                'type' => 'InputfieldInteger',
                'label' => \ProcessWire\__('Timeout in seconds'),
                'description' => \ProcessWire\__('In order to avoid timeouts while indexing individual files, set preferred timeout in seconds.'),
                'notes' => sprintf(
                    \ProcessWire\__('Default value is `%d` seconds.'),
                    60
                ),
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
        // pdftotext path and options are intentionally limited to config, which would normally require access to code.
        // Said values be potentially dangerous, so we're not going to allow freely modifying them via the config GUI.
        return \Spatie\PdfToText\Pdf::getText(
            $file->filename,
            $this->config->SearchEngineFileIndexer['file_indexer_pdf_to_text_path'] ?? null,
            $this->config->SearchEngineFileIndexer['file_indexer_pdf_to_text_options'] ?? [
                'nopgbrk',
            ],
            (int) $this->modules->getConfig('SearchEngineFileIndexer', 'file_indexer_pdf_to_text_timeout') ?: 60
        );
    }

}
