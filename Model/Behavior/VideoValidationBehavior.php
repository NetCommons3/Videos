<?php
/**
 * Video Validation Behavior
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('Video', 'Videos.Model');

/**
 * Summary for Video Validation Behavior
 */
class VideoValidationBehavior extends ModelBehavior {

/**
 * セッティングの種類(setSettingで利用)
 *
 * @var string ffmpeg 有効フラグ
 */
	const IS_FFMPEG_ENABLE = 'isFfmpegEnable';

/**
 * setup
 *
 * @param Model $model モデル
 * @param array $settings 設定値
 * @return void
 * @link http://book.cakephp.org/2.0/ja/models/behaviors.html#ModelBehavior::setup
 */
	public function setup(Model $model, $settings = array()) {
		$this->settings[$model->alias] = $settings;
	}

/**
 * セッティング セット
 *
 * @param Model $model モデル
 * @param string $settingKey セッティングのキー
 * @param string|array $settingValue セッティングの値
 * @return void
 * @see VideoValidationBehavior::IS_FFMPEG_ENABLE
 */
	public function setVideoValidationSettings(Model $model, $settingKey, $settingValue) {
		$this->settings[$model->alias][$settingKey] = $settingValue;
	}

/**
 * beforeValidate is called before a model is validated, you can use this callback to
 * add behavior validation rules into a models validate array. Returning false
 * will allow you to make the validation fail.
 *
 * @param Model $model モデル
 * @param array $options Options passed from Model::save().
 * @return mixed False or null will abort the operation. Any other result will continue.
 * @link http://book.cakephp.org/2.0/ja/models/callback-methods.html#beforevalidate
 * @see Model::save()
 */
	public function beforeValidate(Model $model, $options = array()) {
		parent::beforeValidate($model, $options);

		if (in_array('edit', $options, true)) {
			// 編集時の動画 任意 対応
			if (isset($model->data[$model->alias][Video::VIDEO_FILE_FIELD]['size']) &&
				$model->data[$model->alias][Video::VIDEO_FILE_FIELD]['size'] === 0) {

				unset($model->data[$model->alias][Video::VIDEO_FILE_FIELD]);
			}

			// 編集時のサムネイル 任意 対応
			if (isset($model->data[$model->alias][Video::THUMBNAIL_FIELD]['size']) &&
				$model->data[$model->alias][Video::THUMBNAIL_FIELD]['size'] === 0) {

				unset($model->data[$model->alias][Video::THUMBNAIL_FIELD]);
			}
		}

		return true;
	}

/**
 * ルール定義
 *
 * @param Model $model モデル
 * @param array $options Options passed from Model::save().
 * @return array
 */
	public function rules(Model $model, $options = array()) {
		$rules = $this->__initValidate($model);

		$isFfmpegEnable = Hash::get($this->settings, $model->alias . '.' . self::IS_FFMPEG_ENABLE);
		if ($isFfmpegEnable) {
			// ffmpeg=ON
			$extension = Video::VIDEO_EXTENSION;
			$mimeType = Video::VIDEO_MIME_TYPE;

		} else {
			// ffmpeg=OFF
			$extension = 'mp4';
			$mimeType = 'video/mp4';
		}

		if (in_array('add', $options, true)) {
			// --- 登録時
			if (!$isFfmpegEnable) {
				$rules = Hash::merge($rules, array(
					// 必須
					Video::THUMBNAIL_FIELD => array(
						// http://book.cakephp.org/2.0/ja/models/data-validation.html#Validation::uploadError
						'upload-file' => array(
							'rule' => array('uploadError'),
							'message' => array(__d('files', 'Please specify the file'))
						),
						// http://book.cakephp.org/2.0/ja/models/data-validation.html#Validation::extension
						'extension' => array(
							'rule' => array('extension', explode(',', Video::THUMBNAIL_EXTENSION)),
							'message' => array(__d('files', 'It is upload disabled file format'))
						),
						// http://book.cakephp.org/2.0/ja/models/data-validation.html#Validation::mimeType
						'mimetype' => array(
							'rule' => array('mimeType', explode(',', Video::THUMBNAIL_MIME_TYPE)),
							'message' => array(__d('files', 'It is upload disabled file format'))
						),
					),
				));
			}

			// 必須
			$rules = Hash::merge($rules, array(
				Video::VIDEO_FILE_FIELD => array(
					'upload-file' => array(
						'rule' => array('uploadError'),
						'message' => array(__d('files', 'Please specify the file'))
					),
					'extension' => array(
						'rule' => array('extension', explode(',', $extension)),
						'message' => array(__d('files', 'It is upload disabled file format'))
					),
					'mimetype' => array(
						'rule' => array('mimeType', explode(',', $mimeType)),
						'message' => array(__d('files', 'It is upload disabled file format'))
					),
				),
			));

		} elseif (in_array('edit', $options, true)) {
			// --- 編集時
			$rules = Hash::merge($rules, array(
				// 任意
				Video::VIDEO_FILE_FIELD => array(
					'extension' => array(
						'rule' => array('extension', explode(',', $extension)),
						'message' => array(__d('files', 'It is upload disabled file format'))
					),
					'mimetype' => array(
						'rule' => array('mimeType', explode(',', $mimeType)),
						'message' => array(__d('files', 'It is upload disabled file format'))
					),
				),
				// 任意
				Video::THUMBNAIL_FIELD => array(
					'extension' => array(
						'rule' => array('extension', explode(',', Video::THUMBNAIL_EXTENSION)),
						'message' => array(__d('files', 'It is upload disabled file format'))
					),
					'mimetype' => array(
						'rule' => array('mimeType', explode(',', Video::THUMBNAIL_MIME_TYPE)),
						'message' => array(__d('files', 'It is upload disabled file format'))
					),
				),
			));
		}

		return $rules;
	}

/**
 * 初期ルール
 *
 * @param Model $model モデル
 * @return array 初期ルール
 */
	private function __initValidate(Model $model) {
		return Hash::merge($model->validate, array(
			'language_id' => array(
				'numeric' => array(
					'rule' => array('numeric'),
					'message' => __d('net_commons', 'Invalid request.'),
					'required' => true,
				),
			),
			'block_id' => array(
				'numeric' => array(
					'rule' => array('numeric'),
					'message' => __d('net_commons', 'Invalid request.'),
					'required' => true,
				),
			),
			'title' => array(
				'notBlank' => array(
					'rule' => array('notBlank'),
					'message' => sprintf(__d('net_commons', 'Please input %s.'), __d('videos', 'title')),
					'required' => true,
				),
			),
			'category_id' => array(
				'numeric' => array(
					'rule' => array('numeric'),
					'message' => __d('net_commons', 'Invalid request.'),
					'allowEmpty' => true,
				),
			),
		));
	}

}