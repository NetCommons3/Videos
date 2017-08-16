<?php
/**
 * VideoFilesController::file()のテスト
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('NetCommonsControllerTestCase', 'NetCommons.TestSuite');

/**
 * VideoFilesController::file()のテスト
 *
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @package NetCommons\Videos\Test\Case\Controller\VideoFilesController
 */
class VideoFilesControllerFileTest extends NetCommonsControllerTestCase {

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
	protected $_controller = 'video_files';

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
			'action' => 'file',
			'frame_id' => $frameId,
			'block_id' => $blockId,
			'key' => 'content_key_1',
			'thumbnail'
		);

		return $data;
	}

/**
 * file()アクションのGetリクエストテスト
 *
 * @return void
 */
	public function testFileGet() {
		$this->generate(
			'Videos.VideoFiles', [
				'components' => [
					'Download'
				]
			]
		);

		$this->controller->Components->Download
			->expects($this->once())
			->method('doDownload')
			->will($this->returnCallback(function () {
				$fileName = 'thumbnail1.jpg';
				$filePath = APP . 'Plugin' . DS . 'Videos' . DS . 'Test' . DS . 'Fixture' . DS . $fileName;
				$options = array('name' => $fileName);
				$this->controller->response->file(
					$filePath,
					$options
				);
				return $this->controller->response;
			}));

		$urlOptions = $this->__data();

		//テスト実施
		$this->_testGetAction($urlOptions, array('method' => 'assertEmpty'), null, 'view');
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
