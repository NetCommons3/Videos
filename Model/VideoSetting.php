<?php
/**
 * VideoSetting Model
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('Video', 'Videos.Model');
App::uses('VideosAppModel', 'Videos.Model');
App::uses('BlockSettingBehavior', 'Blocks.Model/Behavior');

/**
 * VideoSetting Model
 *
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @package NetCommons\Videos\Model
 */
class VideoSetting extends VideosAppModel {

/**
 * Validation rules
 *
 * @var array
 */
	public $validate = array();

/**
 * use behaviors
 *
 * @var array
 * @see NetCommonsAppModel::$actAs
 * @see BlockBehavior
 * @see OriginalKeyBehavior
 * @see MailSettingBehavior::beforeDelete()
 * @see CategoryBehavior
 */
	public $actsAs = array(
		'Blocks.Block' => array(
			'name' => 'VideoSetting.name',
			// save, delete時にloadModels()
			// delete時にblock_id, block_keyで紐づいてるデータ削除
			'loadModels' => array(
				'BlockSetting' => 'Blocks.BlockSetting',
				'BlocksLanguage' => 'Blocks.BlocksLanguage',
				'Category' => 'Categories.Category',
				'CategoryOrder' => 'Categories.CategoryOrder',
				'ContentComment' => 'ContentComments.ContentComment',
				'Like' => 'Likes.Like',
				'Tag' => 'Tags.Tag',
				'Video' => 'Videos.Video',
				// メール関連
				'MailSetting' => 'Mails.MailSetting',
				'MailSettingFixedPhrase' => 'Mails.MailSettingFixedPhrase',
				'MailQueue' => 'Mails.MailQueue',
				'MailQueueUser' => 'Mails.MailQueueUser',
			)
		),
		'Blocks.BlockSetting' => array(
			BlockSettingBehavior::FIELD_USE_WORKFLOW,
			BlockSettingBehavior::FIELD_USE_LIKE,
			BlockSettingBehavior::FIELD_USE_UNLIKE,
			BlockSettingBehavior::FIELD_USE_COMMENT,
			BlockSettingBehavior::FIELD_USE_COMMENT_APPROVAL,
			'auto_play',
		),
		'Categories.Category',
		'NetCommons.OriginalKey',
		'Blocks.BlockRolePermission',
	);

/**
 * belongsTo associations
 *
 * @var array
 */
	public $belongsTo = array(
		'Block' => array(
			'className' => 'Blocks.Block',
			'foreignKey' => false,
			'conditions' => array(
				'Block.key = VideoSetting.block_key',
			),
			'fields' => '',
			'order' => ''
		),
	);

/**
 * beforeValidate
 *
 * @param array $options Options passed from Model::save().
 * @return bool True if validate operation should continue, false to abort
 * @link http://book.cakephp.org/2.0/ja/models/callback-methods.html#beforevalidate
 * @see Model::save()
 */
	public function beforeValidate($options = array()) {
		if (!parent::beforeValidate($options)) {
			return false;
		}

		if (isset($this->data['BlocksLanguage']['name'])) {
			$this->loadModels(array(
				'BlocksLanguage' => 'Blocks.BlocksLanguage',
			));
			$this->BlocksLanguage->validate = array(
				'name' => array(
					'notBlank' => array(
						'rule' => array('notBlank'),
						'message' => sprintf(__d('net_commons', 'Please input %s.'), __d('videos', 'Channel name')),
						'required' => true,
					),
				)
			);
			$this->BlocksLanguage->set($this->data['BlocksLanguage']);
			if (!$this->BlocksLanguage->validates()) {
				$this->validationErrors = Hash::merge(
					$this->validationErrors,
					$this->BlocksLanguage->validationErrors
				);
				return false;
			}
		}
	}

/**
 * VideoSettingデータ新規作成
 *
 * @return array
 */
	public function createVideoSetting() {
		$videoSetting = $this->createAll();
		$this->loadModels(array(
			'BlocksLanguage' => 'Blocks.BlocksLanguage',
		));
		$videoSetting = Hash::merge($videoSetting, $this->BlocksLanguage->createAll(array(
			'BlocksLanguage' => array(
				'name' => __d('videos', 'New channel %s', date('YmdHis')),
			),
		)));
		/** @see BlockSettingBehavior::createBlockSetting() */
		return Hash::merge($videoSetting, $this->createBlockSetting());
	}

/**
 * VideoSettingデータ取得
 *
 * @return array
 * @throws InternalErrorException
 */
	public function getVideoSetting() {
		$conditions = array(
			$this->alias . '.block_key' => Current::read('Block.key'),
		);

		$belongsTo = $this->Block->bindModelBlockLang();
		$this->bindModel($belongsTo, true);

		$videoSetting = $this->find('first', array(
			'recursive' => 0,
			'conditions' => $conditions,
			'order' => $this->alias . '.id DESC'
		));
		if (!$videoSetting) {
			throw new InternalErrorException('Not found ' . __METHOD__);
		}

		/** @see BlockSettingBehavior::getBlockSetting() */
		return Hash::merge($videoSetting, $this->getBlockSetting());
	}

/**
 * VideoSettingデータ保存
 *
 * @param array $data received post data
 * @return mixed On success Model::$data if its not empty or true, false on failure
 * @throws InternalErrorException
 */
	public function saveVideoSetting($data) {
		//トランザクションBegin
		$this->begin();

		// 値をセット
		$this->set($data);
		if (! $this->validates()) {
			return false;
		}

		try {
			if (! $this->save(null, false)) {
				throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
			}

			//トランザクションCommit
			$this->commit();

		} catch (Exception $ex) {
			//トランザクションRollback
			$this->rollback($ex);
		}
		return true;
	}

/**
 * 総容量のみ更新
 *
 * @param int $totalSize 総容量
 * @return bool
 * @throws InternalErrorException
 */
	public function saveTotalSize($totalSize) {
		$videoSetting = $this->getVideoSetting();
		$this->id = $videoSetting['VideoSetting']['id'];

		// コールバックoff
		$validate = array(
			'validate' => false,
			'callbacks' => false,
		);

		// 総容量のみ更新
		if (! $this->saveField('total_size', $totalSize, $validate)) {
			throw new InternalErrorException('Failed ' . __METHOD__);
		}

		return true;
	}

/**
 * VideoSettingデータ削除
 *
 * @param array $data received post data
 * @return mixed On success Model::$data if its not empty or true, false on failure
 * @throws InternalErrorException
 */
	public function deleteVideoSetting($data) {
		$this->loadModels(array(
			'Like' => 'Likes.Like',
			'LikesUser' => 'Likes.LikesUser',
			'Tag' => 'Tags.Tag',
			'TagsContent' => 'Tags.TagsContent',
			'UploadFile' => 'Files.UploadFile',
			'Video' => 'Videos.Video',
		));

		//トランザクションBegin
		$this->begin();

		$blockKey = $data[$this->alias]['block_key'];

		// 多言語コンテンツ削除対応
		// 各IDの配列
		$blockIds = $this->__getIds($this->Block, 'key', $blockKey);
		$likeIds = $this->__getIds($this->Like, 'block_key', $blockKey);
		$tagIds = $this->__getIds($this->Tag, 'block_id', $blockIds);

		// コンテンツキーの配列
		$contentKeys = $this->__getContentKeys($blockIds);

		// アップロードファイル
		$uploadFiles = $this->UploadFile->find('all', array(
			'recursive' => 1,
			'conditions' => array($this->UploadFile->alias . '.content_key' => $contentKeys),
			'callbacks' => false,
		));

		try {
			// VideoSetting削除
			$this->__deleteAll($this, 'block_key', $blockKey);

			// 動画とサムネイルのデータと物理ファイル削除
			foreach ($uploadFiles as $uploadFile) {
				foreach ($uploadFile['UploadFilesContent'] as $uploadFilesContent) {
					$this->UploadFile->removeFile($uploadFilesContent['content_id'],
						$uploadFilesContent['upload_file_id']);
				}
			}

			// アップロードファイル 削除
			$this->__deleteAll($this->UploadFile, 'content_key', $contentKeys, true);

			// タグコンテンツ 削除
			$this->__deleteAll($this->TagsContent, 'tag_id', $tagIds);

			// いいねユーザー 削除
			$this->__deleteAll($this->LikesUser, 'like_id', $likeIds);

			//Blockデータ削除
			/** @see BlockBehavior::deleteBlock() */
			$this->deleteBlock($blockKey);

			//トランザクションCommit
			$this->commit();

		} catch (Exception $ex) {
			//トランザクションRollback
			$this->rollback($ex);
		}

		return true;
	}

/**
 * コンテンツIDの配列
 *
 * @param Model $model モデル
 * @param string $filed フィールド名
 * @param string $value 値
 * @return array
 */
	private function __getIds(Model $model, $filed, $value) {
		$ids = $model->find('list', array(
			'recursive' => -1,
			'conditions' => array($model->alias . '.' . $filed => $value),
			'callbacks' => false,
		));
		$ids = array_keys($ids);
		return $ids;
	}

/**
 * コンテンツキーの配列
 *
 * @param array $blockIds ブロックID複数
 * @return array
 */
	private function __getContentKeys($blockIds) {
		$contentKeys = $this->Video->find('list', array(
			'fields' => array($this->Video->alias . '.key'),
			'recursive' => -1,
			'conditions' => array($this->Video->alias . '.block_id' => $blockIds),
			'callbacks' => false,
		));
		$contentKeys = array_values($contentKeys);
		return $contentKeys;
	}

/**
 * 全削除
 *
 * @param Model $model モデル
 * @param string $filed フィールド名
 * @param string $value 値
 * @param bool $callBackFlag callBack実行
 * @return void
 * @throws InternalErrorException
 */
	private function __deleteAll(Model $model, $filed, $value, $callBackFlag = false) {
		if (empty($value)) {
			return;
		}

		$conditions = array($model->alias . '.' . $filed => $value);
		if (! $model->deleteAll($conditions, false, $callBackFlag)) {
			throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
		}
	}
}
