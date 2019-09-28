<?php
/**
 * VideoBlocksController::add(),edit(),delete()
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('BlocksControllerEditTest', 'Blocks.TestSuite');
App::uses('VideoTestUtil', 'Videos.Test/Case');

/**
 * VideoBlocksController::add(),edit(),delete()
 *
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @package NetCommons\Videos\Test\Case\Controller\VideoBlocksController
 */
class VideoBlocksControllerEditTest extends BlocksControllerEditTest {

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
		'plugin.categories.category',
		'plugin.categories.category_order',
		'plugin.categories.categories_language',
		'plugin.workflow.workflow_comment',
		'plugin.mails.mail_setting',
		'plugin.mails.mail_setting_fixed_phrase',
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
	protected $_controller = 'video_blocks';

/**
 * Edit controller name
 *
 * @var string
 */
	protected $_editController = 'video_blocks';

/**
 * テストDataの取得
 *
 * @param bool $isEdit 編集かどうか
 * @return array
 */
	private function __data($isEdit) {
		$frameId = '6';
		$frameKey = 'frame_3';
		if ($isEdit) {
			$blockId = '2';
			$blockKey = 'block_2';
			$displayOrder = 'new';
			$displayNumber = 5;
			$blockName = 'Channel name';
		} else {
			$blockId = 1;
			$blockKey = 'block_1';
			$displayOrder = 'new';
			$displayNumber = 5;
			$blockName = null;
		}

		$data = array(
			'Frame' => array(
				'id' => $frameId,
				'key' => $frameKey
			),
			'Block' => array(
				'id' => $blockId,
				'key' => $blockKey,
				'room_id' => '2',
				'plugin_key' => $this->plugin,
				'public_type' => '1',
				'from' => null,
				'to' => null,
			),
			'BlocksLanguage' => array(
				'language_id' => '2',
				'name' => $blockName,
			),
			'VideoFrameSetting' => array(
				'id' => $frameId,
				'frame_key' => $frameKey,
				'display_order' => $displayOrder,
				'display_number' => $displayNumber,
			),
		);
		$data['VideoSetting'] = array(
			'block_key' => $blockKey,
			'total_size' => '0',
		);
		$data['VideoSetting'] = Hash::merge($data['VideoSetting'], array(
			'use_like' => '1',
			'use_unlike' => '1',
			'use_comment' => '1',
			'use_workflow' => '1',
			'auto_play' => '1',
			'use_comment_approval' => '1',
		));
		//debug($data);

		return $data;
	}

/**
 * add()アクションDataProvider
 *
 * ### 戻り値
 *  - method: リクエストメソッド（get or post or put）
 *  - data: 登録データ
 *  - validationError: バリデーションエラー
 *
 * @return array
 * @see BlocksControllerEditTest::testAdd()
 */
	public function dataProviderAdd() {
		$data = $this->__data(false);

		//テストデータ
		$results = array();
		$results[0] = array('method' => 'get');
		$results[1] = array('method' => 'put');
		$data['VideoSetting']['name'] = 'Channel name';
		$results[2] = array('method' => 'post', 'data' => $data, 'validationError' => false);
		unset($data['VideoSetting']['name']);
		$results[3] = array('method' => 'post', 'data' => $data,
			'validationError' => array(
				'field' => 'BlocksLanguage.name',
				'value' => '',
				'message' => sprintf(__d('net_commons', 'Please input %s.'), __d('videos', 'Channel name')),
			)
		);

		return $results;
	}

/**
 * edit()アクションDataProvider
 *
 * ### 戻り値
 *  - method: リクエストメソッド（get or post or put）
 *  - data: 登録データ
 *  - validationError: バリデーションエラー
 *
 * @return array
 * @see BlocksControllerEditTest::testEdit()
 */
	public function dataProviderEdit() {
		$data = $this->__data(true);

		//テストデータ
		$results = array();
		$results[0] = array('method' => 'get');
		$results[1] = array('method' => 'post');
		$results[2] = array('method' => 'put', 'data' => $data, 'validationError' => false);
		$results[3] = array('method' => 'put', 'data' => $data,
			'validationError' => array(
				'field' => 'BlocksLanguage.name',
				'value' => '',
				'message' => sprintf(__d('net_commons', 'Please input %s.'), __d('videos', 'Channel name')),
			)
		);

		return $results;
	}

/**
 * delete()アクションDataProvider
 *
 * ### 戻り値
 *  - data 削除データ
 *
 * @return array
 */
	public function dataProviderDelete() {
		$data = array(
			'Block' => array(
				//'id' => '4',
				'id' => '2',
			),
			'VideoSetting' => array(
				//'block_key' => 'block_2',
				'block_key' => 'block_1',
			),
		);
		//Current::$current['Block']['key'] = 'block_2';

		//テストデータ
		$results = array();
		$results[0] = array('data' => $data);

		return $results;
	}

/**
 * delete()のテスト
 *
 * @param array $data 削除データ
 * @dataProvider dataProviderDelete
 * @return void
 */
	public function testDelete($data) {
		// テスト実ファイル配置
		$this->_testFilePath = UPLOADS_ROOT . 'files/upload_file/test/11/';
		(new VideoTestUtil())->readyTestFile('Videos', 'video1.mp4', $this->_testFilePath);

		parent::testDelete($data);

		// テスト実ファイル削除
		(new VideoTestUtil())->deleteTestFile($this->_testFilePath);
	}

/**
 * deleteアクションのDELETE例外テスト json
 *
 * @return void
 */
	public function testDeleteAjaxFail() {
		//ログイン
		TestAuthGeneral::login($this);

		$this->_mockForReturnFalse('Videos.VideoSetting', 'deleteVideoSetting');

		$frameId = '6';
		$blockId = '2';

		//アクション実行
		$url = NetCommonsUrl::actionUrl(array(
			'plugin' => $this->plugin,
			'controller' => $this->_controller,
			'action' => 'delete',
			'frame_id' => $frameId,
			'block_id' => $blockId
		));
		$data = array(
			'Block' => array(
				'id' => $blockId,
				'key' => 'block_2',
			),
		);
		$params = array(
			'method' => 'delete',
			'return' => 'view',
			'data' => $data,
		);
		//$this->testAction($url, $params);
		$return = 'json';
		$result = $this->_testNcAction($url, $params, 'BadRequestException', $return);

		// チェック
		// 不正なリクエスト
		$this->assertEquals(400, $result['code']);

		//ログアウト
		TestAuthGeneral::logout($this);
	}
}
