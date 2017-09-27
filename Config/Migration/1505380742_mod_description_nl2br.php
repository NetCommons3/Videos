<?php
/**
 * 説明項目WYSIWYG対応のため、改行コードを`<br>`に更新
 *
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('NetCommonsMigration', 'NetCommons.Config/Migration');

/**
 * 説明項目WYSIWYG対応のため、改行コードを`<br>`に更新
 *
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @package NetCommons\Videos\Config\Migration
 */
class ModDescriptionNl2br extends NetCommonsMigration {

/**
 * Migration description
 *
 * @var string
 */
	public $description = 'mod_description_nl2br';

/**
 * Actions to be performed
 *
 * @var array $migration
 */
	public $migration = array(
		'up' => array(),
		'down' => array(),
	);

/**
 * Before migration callback
 *
 * @param string $direction Direction of migration process (up or down)
 * @return bool Should process continue
 */
	public function before($direction) {
		return true;
	}

/**
 * After migration callback
 *
 * @param string $direction Direction of migration process (up or down)
 * @return bool Should process continue
 * @throws InternalErrorException
 */
	public function after($direction) {
		$this->loadModels([
			'Video' => 'Videos.Video',
		]);

		$videos = $this->Video->find('all', array(
			'fields' => array('Video.id', 'Video.description'),
			'conditions' => array(
				"NOT" => array(
					'Video.description' => ''
				),
			),
			'recursive' => -1
		));
		if (empty($videos)) {
			return true;
		}

		foreach ($videos as &$video) {
			if ($direction === 'down') {
				$video['Video']['description'] = strip_tags($video['Video']['description']);
			} else {
				$video['Video']['description'] = nl2br($video['Video']['description']);
			}
		}

		//トランザクションBegin
		$this->Video->begin();

		try {
			if (! $this->Video->saveMany($videos, ['validate' => false, 'callbacks' => false])) {
				throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
			}

			//トランザクションCommit
			$this->Video->commit();

		} catch (Exception $ex) {
			//トランザクションRollback
			$this->Video->rollback($ex);
		}

		return true;
	}
}
