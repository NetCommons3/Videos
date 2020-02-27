<?php
/**
 * Video Model
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @author Kazunori Sakamoto <exkazuu@willbooster.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('VideosAppModel', 'Videos.Model');
App::uses('VideoValidationBehavior', 'Videos.Model/Behavior');

/**
 * Video Model
 *
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @author Kazunori Sakamoto <exkazuu@willbooster.com>
 * @package NetCommons\Videos\Model
 */
class Video extends VideosAppModel {

/**
 * @var string file field name 動画ファイル
 */
	const VIDEO_FILE_FIELD = 'video_file';

/**
 * @var string file field name サムネイル
 */
	const THUMBNAIL_FIELD = 'thumbnail';

/**
 * @var string 動画 拡張子
 */
	const VIDEO_EXTENSION = 'mpeg,mpg,mpe,avi,mov,wmv,asf,flv,mp4';

/**
 * @var string 動画 MIMEタイプ
 */
	// @codingStandardsIgnoreStart
	// [.]よる文字連結で複数行にすると syntax error になるため、phpcs(1行100文字制限)を除外
	const VIDEO_MIME_TYPE = 'video/mpeg,video/x-mpeg,video/avi,video/msvideo,video/x-msvideo,video/quicktime,video/x-ms-wmv,video/x-ms-asf,video/x-flv,video/mp4';
	// @codingStandardsIgnoreEnd

/**
 * @var string サムネイル 拡張子
 */
	const THUMBNAIL_EXTENSION = 'jpg,png,gif';

/**
 * @var string サムネイル MIMEタイプ
 */
	const THUMBNAIL_MIME_TYPE = 'image/jpeg,image/png,image/gif';

/**
 * use behaviors
 *
 * @var array
 * @see MailQueueBehavior
 */
	public $actsAs = array(
		'ContentComments.ContentComment',
		'Likes.Like',					// いいね
		'NetCommons.OriginalKey',		// 自動でkeyセット
		'Tags.Tag',
		'Videos.Video',					// 動画変換
		'Videos.VideoValidation',		// Validation rules
		'Workflow.Workflow',			// 自動でis_active, is_latestセット
		'Workflow.WorkflowComment',
		// 自動でメールキューの登録, 削除。ワークフロー利用時はWorkflow.Workflowより下に記述する
		'Mails.MailQueue' => array(
			'embedTags' => array(
				'X-SUBJECT' => 'Video.title',
				'X-BODY' => 'Video.description',
			),
			'embedTagsWysiwyg' => array(),
		),
		'Mails.MailQueueDelete',
		'Files.Attachment' => [
			Video::VIDEO_FILE_FIELD,
			Video::THUMBNAIL_FIELD,
		],
		'Topics.Topics' => array(
			'fields' => array(
				'title' => 'Video.title',
				'summary' => 'Video.description',
				'path' => '/:plugin_key/:plugin_key/view/:block_id/:content_key',
			),
		),
		'Wysiwyg.Wysiwyg' => array(
			'fields' => array('description'),
		),
		//多言語
		'M17n.M17n' => array(
			'commonFields' => array('category_id', 'title_icon'),
			'associations' => array(
				'UploadFilesContent' => array(
					'class' => 'Files.UploadFilesContent',
					'foreignKey' => 'content_id',
					'isM17n' => true
				),
				'TagsContent' => array(
					'class' => 'Tags.TagsContent',
					'foreignKey' => 'content_id',
					'fieldForIdentifyPlugin' => array('field' => 'model', 'value' => 'Video'),
					'isM17n' => true
				),
			),
		),
	);

/**
 * belongsTo associations
 *
 * @var array
 */
	public $belongsTo = array(
		'Category' => array(
			'className' => 'Categories.Category',
			'foreignKey' => 'category_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		),
		'Block' => array(
			'className' => 'Blocks.Block',
			'foreignKey' => 'block_id',
			'conditions' => '',
			'fields' => '',
			'order' => '',
			'counterCache' => array(
				'content_count' => array(
					'Video.is_origin' => true,
					'Video.is_latest' => true
				),
			),
		),
	);

/**
 * Validation rules
 *
 * @var array
 */
	public $validate = array();

/**
 * Called before each find operation. Return false if you want to halt the find
 * call, otherwise return the (modified) query data.
 *
 * @param array $query Data used to execute this query, i.e. conditions, order, etc.
 * @return mixed true if the operation should continue, false if it should abort; or, modified
 *  $query to continue with new $query
 * @link http://book.cakephp.org/2.0/en/models/callback-methods.html#beforefind
 */
	public function beforeFind($query) {
		if (Hash::get($query, 'recursive') > -1 && ! $this->id) {
			$belongsTo = $this->Category->bindModelCategoryLang('Video.category_id');
			$this->bindModel($belongsTo, true);
		}
		return true;
	}

/**
 * Called during validation operations, before validation. Please note that custom
 * validation rules can be defined in $validate.
 *
 * @param array $options Options passed from Model::save().
 * @return bool True if validate operation should continue, false to abort
 * @link http://book.cakephp.org/2.0/ja/models/callback-methods.html#beforevalidate
 * @see Model::save()
 */
	public function beforeValidate($options = array()) {
		/* @see VideoValidationBehavior::setVideoValidationSettings() */
		/* @see VideoBehavior::isFfmpegEnable() */
		$this->setVideoValidationSettings(VideoValidationBehavior::IS_FFMPEG_ENABLE,
			$this->isFfmpegEnable());

		/* @see VideoValidationBehavior::rules() */
		$this->validate = $this->rules($options);

		return parent::beforeValidate($options);
	}

/**
 * 総容量取得
 *
 * @return int
 */
	public function getTotalSize() {
		$this->loadModels(array(
			'UploadFile' => 'Files.UploadFile',
		));

		$video = $this->find('first', array(
			'recursive' => -1,
			'joins' => array (
				array (
					'type' => 'LEFT',
					'table' => $this->UploadFile->table,
					'alias' => 'UploadFile',
					'conditions' => 'UploadFile.content_key = Video.key',
				),
			),
			'fields' => array(
				'SUM(UploadFile.size) AS total_size',
			),
			'conditions' => array(
				'Video.block_id' => Current::read('Block.id'),
				'Video.is_latest' => true,
			),
		));

		return (int)$video[0]['total_size'];
	}

/**
 * UserIdと権限から参照可能なEntryを取得するCondition配列を返す
 *
 * @return array condition
 */
	public function getConditions() {
		// contentReadable falseなら何も見えない
		if (! Current::permission('content_readable')) {
			$conditions = array('Video.id' => 0); // ありえない条件でヒット0にしてる
			return $conditions;
		}

		// デフォルト絞り込み条件
		$conditions = array(
			'Video.block_id' => Current::read('Block.id')
		);

		$conditions = $this->getWorkflowConditions($conditions);

		return $conditions;
	}

/**
 * 再生回数 + 1 で更新
 *
 * @param array $data received post data
 * @return mixed On success Model::$data if its not empty or true, false on failure
 * @throws InternalErrorException
 */
	public function countUp($data) {
		$this->id = $data['Video']['id'];
		$playNumber = $data['Video']['play_number'];
		//再生回数 + 1
		$playNumber++;

		//トランザクションBegin
		$this->begin();

		try {
			// コールバックoff
			$validate = array(
				'validate' => false,
				'callbacks' => false,
			);

			// 再生回数のみ更新
			if (! $this->saveField('play_number', $playNumber, $validate)) {
				throw new InternalErrorException('Failed ' . __METHOD__);
			}

			//トランザクションCommit
			$this->invalidateCDN = false;
			$this->commit();
			$this->invalidateCDN = true;

		} catch (Exception $ex) {
			//トランザクションRollback
			$this->rollback($ex);
		}

		return $playNumber;
	}

/**
 * Videoデータ保存
 *
 * @param array $data received post data
 * @param int $isEdit 編集か
 * @return mixed On success Model::$data if its not empty or true, false on failure
 * @throws InternalErrorException
 */
	public function saveVideo($data, $isEdit = 0) {
		$this->loadModels(array(
			'VideoSetting' => 'Videos.VideoSetting',
		));

		//トランザクションBegin
		$this->begin();

		if ($isEdit) {
			$options = array('edit');
		} else {
			$options = array('add');
		}

		$data['Video']['category_id'] = Hash::get($data, 'Video.category_id', '0');

		//バリデーション
		$this->set($data);
		/* @see beforeValidate() */
		if (! $this->validates($options)) {
			return false;
		}

		try {
			if (! $video = $this->save(null, false)) {
				throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
			}

			// Ffmpeg=ON and 動画あり
			/* @see VideoBehavior::isFfmpegEnable() */
			if ($this->isFfmpegEnable() &&
				isset($data[$this->alias][Video::VIDEO_FILE_FIELD]['size']) &&
				$data[$this->alias][Video::VIDEO_FILE_FIELD]['size'] !== 0) {

				// 動画変換とデータ保存
				/* @see VideoBehavior::saveConvertVideo() */
				$this->saveConvertVideo($video);
			}

			// 総容量更新
			$totalSize = $this->getTotalSize();
			$this->VideoSetting->saveTotalSize($totalSize);

			//トランザクションCommit
			$this->commit();

		} catch (Exception $ex) {
			//トランザクションRollback
			$this->rollback($ex);
		}

		return $video;
	}

/**
 * Videoデータ削除
 *
 * @param array $data received post data
 * @return mixed On success Model::$data if its not empty or true, false on failure
 * @throws InternalErrorException
 */
	public function deleteVideo($data) {
		$this->loadModels(array(
			'Like' => 'Likes.Like',
			'TagsContent' => 'Tags.TagsContent',
			'UploadFile' => 'Files.UploadFile',
			'VideoSetting' => 'Videos.VideoSetting',
		));

		//トランザクションBegin
		$this->begin();

		// アップロードファイル
		$uploadFiles = $this->UploadFile->find('all', array(
			'recursive' => 1,
			'conditions' => array($this->UploadFile->alias . '.content_key' => $data['Video']['key']),
			'callbacks' => false,
		));

		try {
			// 動画削除($callbacks = true)
			$this->contentKey = $data['Video']['key'];
			if (! $this->deleteAll(array($this->alias . '.key' => $data['Video']['key']), false, true)) {
				throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
			}

			// 動画とサムネイルのデータと物理ファイル削除
			foreach ($uploadFiles as $uploadFile) {
				foreach ($uploadFile['UploadFilesContent'] as $uploadFilesContent) {
					$this->UploadFile->removeFile($uploadFilesContent['content_id'],
						$uploadFilesContent['upload_file_id']);
				}
			}

			// アップロードファイル 削除
			$conditions = array($this->UploadFile->alias . '.content_key' => $data['Video']['key']);
			if (! $this->UploadFile->deleteAll($conditions, false)) {
				throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
			}

			// タグコンテンツ 削除
			$conditions = array($this->TagsContent->alias . '.content_id' => $data['Video']['id']);
			if (! $this->TagsContent->deleteAll($conditions, false)) {
				throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
			}

			// いいね 削除
			$conditions = array($this->Like->alias . '.content_key' => $data['Video']['key']);
			if (! $this->Like->deleteAll($conditions, false)) {
				throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
			}

			// 総容量更新
			$totalSize = $this->getTotalSize();
			$this->VideoSetting->saveTotalSize($totalSize);

			//トランザクションCommit
			$this->commit();

		} catch (Exception $ex) {
			//トランザクションRollback
			$this->rollback($ex);
		}

		return true;
	}
}
