<?php
/**
 * VideoSetting::deleteVideoSetting()のテスト
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('NetCommonsDeleteTest', 'NetCommons.TestSuite');
App::uses('VideoSettingFixture', 'Videos.Test/Fixture');
App::uses('VideoTestUtil', 'Videos.Test/Case');

/**
 * VideoSetting::deleteVideoSetting()のテスト
 *
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @package NetCommons\Videos\Test\Case\Model\VideoSetting
 */
class VideoSettingDeleteVideoSettingTest extends NetCommonsDeleteTest {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array(
		'plugin.videos.video',
		'plugin.videos.video_frame_setting',
		'plugin.videos.video_setting',
		'plugin.videos.block_setting_for_video',
		'plugin.videos.like',
		'plugin.likes.likes_user',
		'plugin.tags.tag',
		'plugin.tags.tags_content',
		'plugin.content_comments.content_comment',
		'plugin.workflow.workflow_comment',
		'plugin.mails.mail_setting_fixed_phrase',
		'plugin.site_manager.site_setting',
		'plugin.categories.category',
		'plugin.categories.category_order',
		'plugin.categories.categories_language',
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
	protected $_modelName = 'VideoSetting';

/**
 * Method name
 *
 * @var string
 */
	protected $_methodName = 'deleteVideoSetting';

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();

		// テスト実ファイル配置
		$this->_testFilePath = APP . WEBROOT_DIR . DS . 'files/upload_file/test/11/';
		(new VideoTestUtil())->readyTestFile('Videos', 'video1.mp4', $this->_testFilePath);
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		// テスト実ファイル削除
		(new VideoTestUtil())->deleteTestFile($this->_testFilePath);

		parent::tearDown();
	}

/**
 * Delete用DataProvider
 *
 * ### 戻り値
 *  - data: 削除データ
 *  - associationModels: 削除確認の関連モデル array(model => conditions)
 *
 * @return array テストデータ
 */
	public function dataProviderDelete() {
		$data['VideoSetting'] = (new VideoSettingFixture())->records[0];
		$association = array();
		//Current::$current['Block']['key'] = 'block_1';

		$results = array();
		$results[0] = array($data, $association);

		return $results;
	}

/**
 * ExceptionError用DataProvider
 *
 * ### 戻り値
 *  - data 登録データ
 *  - mockModel Mockのモデル
 *  - mockMethod Mockのメソッド
 *
 * @return array テストデータ
 */
	public function dataProviderDeleteOnExceptionError() {
		$data = $this->dataProviderDelete()[0][0];

		return array(
			array($data, 'Tags.TagsContent', 'deleteAll'),
			array($data, 'Likes.LikesUser', 'deleteAll'),
			array($data, 'Files.UploadFile', 'deleteAll'),
		);
	}

}
