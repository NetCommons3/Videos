<?php
/**
 * Video::deleteVideo()のテスト
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('WorkflowDeleteTest', 'Workflow.TestSuite');
App::uses('VideoFixture', 'Videos.Test/Fixture');
App::uses('VideoTestUtil', 'Videos.Test/Case');

/**
 * Video::deleteVideo()のテスト
 *
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @package NetCommons\Videos\Test\Case\Model\Video
 */
class VideoDeleteVideoTest extends WorkflowDeleteTest {

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
		'plugin.likes.like',
		'plugin.likes.likes_user',
		'plugin.tags.tag',
		'plugin.tags.tags_content',
		'plugin.workflow.workflow_comment',
		'plugin.files.upload_file',
		'plugin.files.upload_files_content',
		'plugin.content_comments.content_comment',
		'plugin.site_manager.site_setting',
		'plugin.categories.category',
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
	protected $_methodName = 'deleteVideo';

/**
 * testFilePath
 *
 * @var string
 */
	protected $_testFilePath = null;

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();

		// テスト実ファイル配置
		$this->_testFilePath = UPLOADS_ROOT . 'files/upload_file/test/11/';
		(new VideoTestUtil())->readyTestFile('Videos', 'video1.mp4', $this->_testFilePath);

		// 総容量取得用のblock_key
		Current::write('Block.key', 'block_1');
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
 * @see NetCommonsDeleteTest::testDelete()
 */
	public function dataProviderDelete() {
		$data['Video'] = (new VideoFixture())->records[0];
		$association = array();

		$results = array();
		$results[0] = array($data, $association);

		return $results;
	}

/**
 * Deleteのテスト
 *
 * @param array|string $data 削除データ
 * @param array $associationModels 削除確認の関連モデル array(model => conditions)
 * @dataProvider dataProviderDelete
 * @return void
 */
	public function testDelete($data, $associationModels = null) {
		parent::testDelete($data, $associationModels);
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
			array($data, 'Videos.Video', 'deleteAll'),
			array($data, 'Likes.Like', 'deleteAll'),
			array($data, 'Tags.TagsContent', 'deleteAll'),
			array($data, 'Files.UploadFile', 'deleteAll'),
		);
	}

}
