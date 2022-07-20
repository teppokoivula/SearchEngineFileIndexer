<?php

namespace SearchEngine\FileIndexer;

/**
 * Plaintext file indexer
 *
 * @version 0.1.0
 * @author Teppo Koivula <teppo.koivula@gmail.com>
 * @license Mozilla Public License v2.0 https://mozilla.org/MPL/2.0/
 */
class FileIndexerPlainText extends FileIndexer {

    /**
     * Get info about this file indexer
     *
     * @return array
     */
    public static function getFileIndexerInfo() {
        return [
            'label' => 'PlainText',
            'icon' => 'file-text-o',
            'available' => true,
            'extensions' => [
                'txt',
            ],
        ];
    }

}
