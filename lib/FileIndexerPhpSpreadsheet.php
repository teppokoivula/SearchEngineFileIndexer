<?php

namespace SearchEngine\FileIndexer;

/**
 * PHPSpreadsheet file indexer
 *
 * @version 0.1.0
 * @author Teppo Koivula <teppo.koivula@gmail.com>
 * @license Mozilla Public License v2.0 https://mozilla.org/MPL/2.0/
 */
class FileIndexerPhpSpreadsheet extends FileIndexer {

    /**
     * Get info about this file indexer
     *
     * @return array
     */
    public static function getFileIndexerInfo() {
        return [
            'label' => 'PHPSpreadsheet',
            'icon' => 'file-excel-o',
            'available' => class_exists('\PhpOffice\PhpSpreadsheet\IOFactory'),
            'extensions' => [
                'xls',
                'xlsx',
                'ods',
                'csv',
            ],
        ];
    }

    /**
     * Return file content as text
     *
     * @param \ProcessWire\Pagefile $file
     * @return string|null
     */
    public function getText(\ProcessWire\Pagefile $file): ?string {
        /** @var \PhpOffice\PhpSpreadsheet\Reader\IReader $reader */
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file->filename);
        $reader->setReadDataOnly(true);
        /** @var \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet */
        $spreadsheet = $reader->load($file->filename);
        $text = '';
        foreach ($spreadsheet->getAllSheets() as $worksheet) {
            /** @var \PhpOffice\PhpSpreadsheet\Worksheet $worksheet */
            $worksheet = $spreadsheet->getSheet($spreadsheet->getFirstSheetIndex());
            $text .= implode(' ... ', array_map(function($row) {
                return implode(", ", $row);
            }, $worksheet->toArray()));
        }
        return $text;
    }
}
