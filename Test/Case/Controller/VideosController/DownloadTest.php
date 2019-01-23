<?php
/**
 * VideosController::download()のテスト
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('NetCommonsControllerTestCase', 'NetCommons.TestSuite');
App::uses('VideoTestUtil', 'Videos.Test/Case');

/**
 * VideosController::download()のテスト
 *
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @package NetCommons\Videos\Test\Case\Controller\VideosController
 */
class VideosControllerDownloadTest extends NetCommonsControllerTestCase {

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
		'plugin.content_comments.content_comment',
	);

/**
 * Plugin name
 *
 * @var string
 */
	public $plugin = 'videos';

/**
 * Controller name
 *
 * @var string
 */
	protected $_controller = 'videos';

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();

		//ログイン
		TestAuthGeneral::login($this);
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		//ログアウト
		TestAuthGeneral::logout($this);

		parent::tearDown();
	}

/**
 * テストDataの取得
 *
 * @return array
 */
	private function __data() {
		$frameId = '6';
		$blockId = '2';

		$data = array(
			'action' => 'download',
			'frame_id' => $frameId,
			'block_id' => $blockId,
			'key' => 'content_key_1',
			'video_file'
		);

		return $data;
	}

/**
 * download()アクションのGetリクエストテスト
 *
 * @return void
 */
	public function testDownloadGet() {
		$data = $this->__data();

		//テスト実行
		$this->_testGetAction($data, array('method' => 'assertEmpty'), null, 'view');
	}

/**
 * download()アクションのPostリクエストテスト
 *
 * @return void
 */
	public function testDownloadPost() {
		// テストZipDownloaderに差し替え
		App::uses('ZipDownloader', 'Videos.Test/test_app/Plugin/TestFiles/Utility');

		$urlOptions = $this->__data();
		$data = array(
			'AuthorizationKey' => array(
				'authorization_key' => 'pass',
			)
		);
		// テスト実ファイル配置
		$videoTestUtil = new VideoTestUtil();
		$testFilePath = UPLOADS_ROOT . 'files/upload_file/test/11/';
		$videoTestUtil->readyTestFile('Videos', 'video1.mp4', $testFilePath);

		//テスト実行
		// http://www.shigemk2.com/entry/20120105/1325694807
		// returnに定義できるのは、'vars','view','contents','result'
		$result = $this->_testPostAction('post', $data, $urlOptions, null, 'result');

		//debug($result);
		$this->assertEquals('Title 2.zip', $result);

		// テスト実ファイル削除
		$videoTestUtil->deleteTestFile($testFilePath);
	}

/**
 * download()アクションのGetリクエスト 圧縮用パスワードなしテスト
 *
 * @return void
 */
	public function testDownloadGetFlashMessageAndRedirect() {
		$data = $this->__data();

		//テスト実行
		$this->_testGetAction($data, array('method' => 'assertEmpty'), null, 'view');
	}

/**
 * file()アクションのGetリクエスト 動画データなし例外テスト
 *
 * @return void
 */
	public function testFileGetNotFoundException() {
		$data = $this->__data();
		$data = Hash::insert($data, 'key', 'content_key_999');

		//テスト実行
		$this->_testGetAction($data, null, 'NotFoundException', 'view');
	}
}
