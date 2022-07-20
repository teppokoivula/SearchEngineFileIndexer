<?php

namespace SearchEngine\FileIndexer;

/**
 * PhpWord file indexer
 *
 * @version 0.1.0
 * @author Teppo Koivula <teppo.koivula@gmail.com>
 * @license Mozilla Public License v2.0 https://mozilla.org/MPL/2.0/
 */
class FileIndexerPhpWord extends FileIndexer {

    /**
     * Get info about this file indexer
     *
     * @return array
     */
    public static function getFileIndexerInfo() {
        return [
            'label' => 'PHPWord',
            'icon' => 'file-word-o',
            'available' => class_exists('\PhpOffice\PhpWord\IOFactory'),
            'extensions' => [
                'doc',
                'docx',
                'odf',
                'rtf',
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
        /** @var \PhpOffice\PhpWord\PhpWord $phpword */
        $phpword = \PhpOffice\PhpWord\IOFactory::load($file->filename);
        $text = '';
        foreach ($phpword->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                $element_text = $this->getElementText($element);
                if (!empty($element_text)) {
                    $text .= (empty($text) ? '' : ' ') . $element_text;
                }
            }
        }
        return $text;
    }

    /**
     * Read text from PHPWord Element iteratively
     *
     * @param \PhpOffice\PhpWord\Element\AbstractElement $element
     * @return string
     */
    protected function getElementText(\PhpOffice\PhpWord\Element\AbstractElement $element): string {
        $text = '';
        if ($element instanceof \PhpOffice\PhpWord\Element\AbstractContainer) {
            foreach ($element->getElements() as $child_element) {
                $text .= $this->getElementText($child_element);
            }
        }
        if (method_exists($element, 'getText')) {
            $text .= $element->getText();
        }
        return $text;
    }
}
