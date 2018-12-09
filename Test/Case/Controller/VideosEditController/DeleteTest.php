<?php
/**
 * VideosEditController::delete()のテスト
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('WorkflowControllerDeleteTest', 'Workflow.TestSuite');
App::uses('VideoTestUtil', 'Videos.Test/Case');

/**
 * VideosEditController::delete()のテスト
 *
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @package NetCommons\Videos\Test\Case\Controller\VideosEditController
 */
class VideosEditControllerDeleteTest extends WorkflowControllerDeleteTest {

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
	protected $_controller = 'videos_edit';

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
 * テストDataの取得
 *
 * @param string $contentKey キー
 * @return array
 */
	private function __data($contentKey = null) {
		$frameId = '6';
		$blockId = '2';
		$blockKey = 'block_1';
		if ($contentKey === 'content_key_2') {
			$contentId = '3';
		} elseif ($contentKey === 'content_key_4') {
			$contentId = '5';
		} else {
			$contentId = '2';
		}

		$data = array(
			'delete' => null,
			'Frame' => array(
				'id' => $frameId,
			),
			'Block' => array(
				'id' => $blockId,
				'key' => $blockKey,
				'language_id' => '2',
				'room_id' => '2',
				'plugin_key' => $this->plugin,
			),
			'Video' => array(
				'id' => $contentId,
				'key' => $contentKey,
			),
		);

		return $data;
	}

/**
 * deleteアクションのGETテスト用DataProvider
 *
 * ### 戻り値
 *  - role: ロール
 *  - urlOptions: URLオプション
 *  - assert: テストの期待値
 *  - exception: Exception
 *  - return: testActionの実行後の結果
 *
 * @return array
 */
	public function dataProviderDeleteGet() {
		$data = $this->__data();

		//テストデータ
		$results = array();
		// * ログインなし
		$results[0] = array('role' => null,
			'urlOptions' => array(
				'frame_id' => $data['Frame']['id'],
				'block_id' => $data['Block']['id'],
				'key' => 'content_key_1',
			),
			'assert' => null, 'exception' => 'ForbiddenException'
		);
		// * 作成権限のみ(自分自身)
		array_push($results, Hash::merge($results[0], array(
			'role' => Role::ROOM_ROLE_KEY_GENERAL_USER,
			'urlOptions' => array(
				'frame_id' => $data['Frame']['id'],
				'block_id' => $data['Block']['id'],
				'key' => 'content_key_2',
			),
			'assert' => null, 'exception' => 'BadRequestException'
		)));
		// * 編集権限、公開権限なし
		array_push($results, Hash::merge($results[0], array(
			'role' => Role::ROOM_ROLE_KEY_EDITOR,
			'assert' => null, 'exception' => 'BadRequestException'
		)));
		// * 公開権限あり
		array_push($results, Hash::merge($results[0], array(
			'role' => Role::ROOM_ROLE_KEY_ROOM_ADMINISTRATOR,
			'assert' => null, 'exception' => 'BadRequestException'
		)));
		// * 作成権限のみ(自分自身)-json
		array_push($results, Hash::merge($results[0], array(
			'role' => Role::ROOM_ROLE_KEY_GENERAL_USER,
			'urlOptions' => array(
				'frame_id' => $data['Frame']['id'],
				'block_id' => $data['Block']['id'],
				'key' => 'content_key_2',
			),
			'assert' => null, 'exception' => 'BadRequestException',
			'return' => 'json',
		)));

		return $results;
	}

/**
 * deleteアクションのPOSTテスト用DataProvider
 *
 * ### 戻り値
 *  - data: 登録データ
 *  - role: ロール
 *  - urlOptions: URLオプション
 *  - exception: Exception
 *  - return: testActionの実行後の結果
 *
 * @return array
 */
	public function dataProviderDeletePost() {
		$data = $this->__data();

		//テストデータ
		$results = array();
		// * ログインなし
		$contentKey = 'content_key_1';
		array_push($results, array(
			'data' => $this->__data($contentKey),
			'role' => null,
			'urlOptions' => array(
				'frame_id' => $data['Frame']['id'],
				'block_id' => $data['Block']['id'],
				'key' => $contentKey
			),
			'exception' => 'ForbiddenException'
		));
		// * 作成権限のみ
		// ** 他人の記事
		$contentKey = 'content_key_1';
		array_push($results, array(
			'data' => $this->__data($contentKey),
			'role' => Role::ROOM_ROLE_KEY_GENERAL_USER,
			'urlOptions' => array(
				'frame_id' => $data['Frame']['id'],
				'block_id' => $data['Block']['id'],
				'key' => $contentKey
			),
			'exception' => 'BadRequestException'
		));
		// ** 自分の記事＆一度も公開されていない
		$contentKey = 'content_key_2';
		array_push($results, array(
			'data' => $this->__data($contentKey),
			'role' => Role::ROOM_ROLE_KEY_GENERAL_USER,
			'urlOptions' => array(
				'frame_id' => $data['Frame']['id'],
				'block_id' => $data['Block']['id'],
				'key' => $contentKey
			),
		));
		// ** 自分の記事＆一度公開している
		$contentKey = 'content_key_4';
		array_push($results, array(
			'data' => $this->__data($contentKey),
			'role' => Role::ROOM_ROLE_KEY_GENERAL_USER,
			'urlOptions' => array(
				'frame_id' => $data['Frame']['id'],
				'block_id' => $data['Block']['id'],
				'key' => $contentKey
			),
			'exception' => 'BadRequestException'
		));
		// * 編集権限あり
		// ** 公開していない
		$contentKey = 'content_key_2';
		array_push($results, array(
			'data' => $this->__data($contentKey),
			'role' => Role::ROOM_ROLE_KEY_EDITOR,
			'urlOptions' => array(
				'frame_id' => $data['Frame']['id'],
				'block_id' => $data['Block']['id'],
				'key' => $contentKey
			),
		));
		// ** 公開している
		$contentKey = 'content_key_4';
		array_push($results, array(
			'data' => $this->__data($contentKey),
			'role' => Role::ROOM_ROLE_KEY_EDITOR,
			'urlOptions' => array(
				'frame_id' => $data['Frame']['id'],
				'block_id' => $data['Block']['id'],
				'key' => $contentKey
			),
			'exception' => 'BadRequestException'
		));
		// * 公開権限あり
		// ** フレームID指定なしテスト
		$contentKey = 'content_key_1';
		array_push($results, array(
			'data' => $this->__data($contentKey),
			'role' => Role::ROOM_ROLE_KEY_ROOM_ADMINISTRATOR,
			'urlOptions' => array(
				'frame_id' => null,
				'block_id' => $data['Block']['id'],
				'key' => $contentKey
			),
		));

		return $results;
	}

/**
 * deleteアクションのExceptionErrorテスト用DataProvider
 *
 * ### 戻り値
 *  - mockModel: Mockのモデル
 *  - mockMethod: Mockのメソッド
 *  - data: 登録データ
 *  - urlOptions: URLオプション
 *  - exception: Exception
 *  - return: testActionの実行後の結果
 *
 * @return array
 */
	public function dataProviderDeleteExceptionError() {
		$data = $this->__data();

		//テストデータ
		$results = array();
		$results[0] = array(
			'mockModel' => 'Videos.Video',
			'mockMethod' => 'deleteVideo',
			'data' => $data,
			'urlOptions' => array(
				'frame_id' => $data['Frame']['id'],
				'block_id' => $data['Block']['id'],
				'key' => 'content_key_1',
			),
			'exception' => 'BadRequestException',
			'return' => 'view'
		);
		// json
		$results[1] = array(
			'mockModel' => 'Videos.Video',
			'mockMethod' => 'deleteVideo',
			'data' => $data,
			'urlOptions' => array(
				'frame_id' => $data['Frame']['id'],
				'block_id' => $data['Block']['id'],
				'key' => 'content_key_1',
			),
			'exception' => 'BadRequestException',
			'return' => 'json'
		);

		return $results;
	}

}
