<?php
/**
 * Video::validate()のテスト
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('NetCommonsValidateTest', 'NetCommons.TestSuite');
App::uses('VideoFixture', 'Videos.Test/Fixture');
App::uses('Video', 'Videos.Model');
App::uses('VideoTestUtil', 'Videos.Test/Case');

/**
 * Video::validate()のテスト
 *
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @package NetCommons\Videos\Test\Case\Model\Video
 */
class VideoValidateTest extends NetCommonsValidateTest {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array(
		'plugin.videos.video',
		'plugin.videos.video_setting',
		'plugin.videos.block_setting_for_video',
		'plugin.videos.video_frame_setting',
		'plugin.workflow.workflow_comment',
		'plugin.site_manager.site_setting',
	);

/**
 * Plugin name
 *
 * @var string
 */
	public $plugin = 'videos';

/**
 * Model name
 *
 * @var string
 */
	protected $_modelName = 'Video';

/**
 * Method name
 *
 * @var string
 */
	protected $_methodName = 'validates';

/**
 * ValidationErrorのDataProvider
 *
 * ### 戻り値
 *  - data 登録データ
 *  - field フィールド名
 *  - value セットする値
 *  - message エラーメッセージ
 *  - overwrite 上書きするデータ(省略可)
 *
 * @return array テストデータ
 */
	public function dataProviderValidationError() {
		$data['Video'] = (new VideoFixture())->records[0];
		$overwrite['Video'][Video::THUMBNAIL_FIELD]['size'] = 0;

		//debug($data);

		$result['登録:language_id'] = array('data' => $data, 'field' => 'language_id', 'value' => null,
			'message' => __d('net_commons', 'Invalid request.'),
			'overwrite' => $overwrite, 'options' => array('add'));

		$result['登録:block_id'] = array('data' => $data, 'field' => 'block_id', 'value' => null,
			'message' => __d('net_commons', 'Invalid request.'),
			'overwrite' => array(), 'options' => array('add'));

		$result['登録:title'] = array('data' => $data, 'field' => 'title', 'value' => null,
			'message' => sprintf(__d('net_commons', 'Please input %s.'), __d('videos', 'title')),
			'overwrite' => array(), 'options' => array('add'));

		$result['登録:category_id'] = array('data' => $data, 'field' => 'category_id', 'value' => 'x',
			'message' => __d('net_commons', 'Invalid request.'),
			'overwrite' => array(), 'options' => array('add'));

		$result['編集:language_id'] = array('data' => $data, 'field' => 'language_id', 'value' => null,
			'message' => __d('net_commons', 'Invalid request.'),
			'overwrite' => $overwrite, 'options' => array('edit'));

		return $result;
	}

/**
 * Validatesのテスト
 *
 * @param array $data 登録データ
 * @param string $field フィールド名
 * @param string $value セットする値
 * @param string $message エラーメッセージ
 * @param array $overwrite 上書きするデータ
 * @param array $options validateのオプション
 * @dataProvider dataProviderValidationError
 * @return void
 */
	public function testValidationError($data, $field, $value, $message, $overwrite = array(), $options = array()) {
		$model = $this->_modelName;

		if (is_null($value)) {
			unset($data[$model][$field]);
		} else {
			$data[$model][$field] = $value;
		}
		$data = Hash::merge($data, $overwrite);

		// undefind indexが出るため非活性
		$this->$model->Behaviors->disable('Files.Attachment');

		//validate処理実行
		$this->$model->set($data);
		//$result = $this->$model->validates();
		$result = $this->$model->validates($options);
		$this->assertFalse($result);

		if ($message) {
			$this->assertEquals($this->$model->validationErrors[$field][0], $message);
		}
	}

/**
 * ValidationUploadErrorのDataProvider
 *
 * ### 戻り値
 *  - data 登録データ
 *  - field フィールド名
 *  - value セットする値
 *  - message エラーメッセージ
 *  - overwrite 上書きするデータ(省略可)
 *
 * @return array テストデータ
 */
	public function dataProviderValidationUploadError() {
		$data['Video'] = (new VideoFixture())->records[0];

		$testUtil = new VideoTestUtil();
		$data['Video'][Video::VIDEO_FILE_FIELD] = $testUtil->getFileData('Videos', 'video1.mp4', 'video/mp4');
		$data['Video'][Video::THUMBNAIL_FIELD] = $testUtil->getFileData('Videos', 'thumbnail1.jpg', 'image/jpeg');

		//		$data['Video'][Video::VIDEO_FILE_FIELD] = array(
		//			'name' => 'video1.mp4',
		//			'type' => 'video/mp4',
		//			'tmp_name' => '',
		//			'error' => 0,
		//			'size' => 4544587,
		//		);
		//debug($data);

		// --- 登録, Ffmpeg=ON
		$overwrite = array();
		$overwrite['Video'][Video::VIDEO_FILE_FIELD]['error'] = 1;
		$result['登録, Ffmpeg=ON:video_file - ファイルなし'] = array('data' => $data, 'field' => Video::VIDEO_FILE_FIELD,
			//'message' => __d('files', 'Please specify the file'),
			'message' => __d('files', 'Failed uploading file.'),
			'overwrite' => $overwrite, 'options' => array('add'), 'isFfmpegEnable' => true);

		$overwrite = array();
		$overwrite['Video'][Video::VIDEO_FILE_FIELD]['name'] = 'thumbnail1.xxx';
		$result['登録, Ffmpeg=ON:video_file - 拡張子違い'] = array('data' => $data, 'field' => Video::VIDEO_FILE_FIELD,
			'message' => __d('files', 'It is upload disabled file format'),
			'overwrite' => $overwrite, 'options' => array('add'), 'isFfmpegEnable' => true);

		$data['Video'][Video::VIDEO_FILE_FIELD] = $testUtil->getFileData('Videos', 'thumbnail1.mp4', 'video/mp4');
		$result['登録, Ffmpeg=ON:video_file - 拡張子偽装によるmimetype違い'] = array('data' => $data, 'field' => Video::VIDEO_FILE_FIELD,
			'message' => __d('files', 'It is upload disabled file format'),
			'overwrite' => array(), 'options' => array('add'), 'isFfmpegEnable' => true);

		// video_fileを正常なデータにセットし直す
		$data['Video'][Video::VIDEO_FILE_FIELD] = $testUtil->getFileData('Videos', 'video1.mp4', 'video/mp4');

		// --- 登録, Ffmpeg=OFF
		$overwrite = array();
		$overwrite['Video'][Video::THUMBNAIL_FIELD]['error'] = 1;
		$result['登録, Ffmpeg=OFF:thumbnail - ファイルなし'] = array('data' => $data, 'field' => Video::THUMBNAIL_FIELD,
			//'message' => __d('files', 'Please specify the file'),
			'message' => __d('files', 'Failed uploading file.'),
			'overwrite' => $overwrite, 'options' => array('add'), 'isFfmpegEnable' => false);

		$overwrite = array();
		$overwrite['Video'][Video::THUMBNAIL_FIELD]['name'] = 'video1.xxx';
		$result['登録, Ffmpeg=OFF:thumbnail - 拡張子違い'] = array('data' => $data, 'field' => Video::THUMBNAIL_FIELD,
			'message' => __d('files', 'It is upload disabled file format'),
			'overwrite' => $overwrite, 'options' => array('add'), 'isFfmpegEnable' => false);

		$data['Video'][Video::THUMBNAIL_FIELD] = $testUtil->getFileData('Videos', 'video1.jpg', 'image/jpeg');
		$result['登録, Ffmpeg=OFF:thumbnail - 拡張子偽装によるmimetype違い'] = array('data' => $data, 'field' => Video::THUMBNAIL_FIELD,
			'message' => __d('files', 'It is upload disabled file format'),
			'overwrite' => array(), 'options' => array('add'), 'isFfmpegEnable' => false);

		// thumbnailを正常なデータにセットし直す
		$data['Video'][Video::THUMBNAIL_FIELD] = $testUtil->getFileData('Videos', 'thumbnail1.jpg', 'image/jpeg');

		// --- 編集
		$overwrite = array();
		$overwrite['Video'][Video::THUMBNAIL_FIELD]['name'] = 'video1.xxx';
		$result['編集:thumbnail - 拡張子違い'] = array('data' => $data, 'field' => Video::THUMBNAIL_FIELD,
			'message' => __d('files', 'It is upload disabled file format'),
			'overwrite' => $overwrite, 'options' => array('edit'), 'isFfmpegEnable' => true);

		$data['Video'][Video::THUMBNAIL_FIELD] = $testUtil->getFileData('Videos', 'video1.jpg', 'image/jpeg');
		$result['編集:thumbnail - 拡張子偽装によるmimetype違い'] = array('data' => $data, 'field' => Video::THUMBNAIL_FIELD,
			'message' => __d('files', 'It is upload disabled file format'),
			'overwrite' => array(), 'options' => array('edit'), 'isFfmpegEnable' => true);

		return $result;
	}

/**
 * Validates uploadのテスト
 *
 * @param array $data 登録データ
 * @param string $field フィールド名
 * @param string $message エラーメッセージ
 * @param array $overwrite 上書きするデータ
 * @param array $options validateのオプション
 * @param bool|int $isFfmpegEnable FFMPEG有効フラグ
 * @dataProvider dataProviderValidationUploadError
 * @return void
 */
	public function testValidationUploadError($data, $field, $message, $overwrite = array(), $options = array(), $isFfmpegEnable = 1) {
		//public function testValidationUploadError($data, $field, $value, $message, $overwrite = array(), $options = array()) {
		$model = $this->_modelName;
		$this->$model->Behaviors->unload('Workflow.Workflow');

		//		if (is_null($value)) {
		//			unset($data[$model][$field]);
		//		} else {
		//			$data[$model][$field] = $value;
		//		}
		$data = Hash::merge($data, $overwrite);

		$this->$model->setIsFfmpegEnable($isFfmpegEnable);

		//validate処理実行
		$this->$model->set($data);
		//$result = $this->$model->validates();
		$result = $this->$model->validates($options);
		$this->assertFalse($result);

		//var_dump($this->$model->validationErrors);

		if ($message) {
			$this->assertEquals($this->$model->validationErrors[$field][0], $message);
		}
	}

}
