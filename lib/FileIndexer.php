<?php

namespace SearchEngine\FileIndexer;

/**
 * Abstract base implementation for file indexer classes
 *
 * @version 0.1.0
 * @author Teppo Koivula <teppo.koivula@gmail.com>
 * @license Mozilla Public License v2.0 https://mozilla.org/MPL/2.0/
 */
abstract class FileIndexer extends \ProcessWire\Wire {

    /**
     * Get info about this file indexer
     *
     * @return array
     */
    public static function getFileIndexerInfo() {
        return [
            'label' => 'File indexer',
            'icon' => 'file-o',
            'extensions' => [
                // 'txt',
            ],
        ];
    }

    /**
     * Get config inputfields for this file indexer
     *
     * @param InputfieldFieldset $fieldset
     */
    public static function getFileIndexerConfigInputfields(\ProcessWire\InputfieldFieldset $fieldset) {
        // $fieldset->add([
        //     [
        //         'name' => 'file_indexer_name_setting_name',
        //         'type' => 'InputfieldInteger',
        //         'label' => \ProcessWire\__('Field label'),
        //     ]
        // ]);
    }

    /**
     * Return file content as text
     *
     * @param \ProcessWire\Pagefile $file
     * @return string|null
     */
    public function getText(\ProcessWire\Pagefile $file): ?string {
        // $setting_name = $this->modules->getConfig('SearchEngineFileIndexer', 'file_indexer_name_setting_name')
        $text = file_get_contents($file->filename);
        return $text === false ? null : $text;
    }

}
