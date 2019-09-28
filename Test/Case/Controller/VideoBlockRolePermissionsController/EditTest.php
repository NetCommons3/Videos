<?php
/**
 * VideoBlockRolePermissionsController::edit()のテスト
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('BlockRolePermissionsControllerEditTest', 'Blocks.TestSuite');

/**
 * VideoBlockRolePermissionsController::edit()のテスト
 *
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @package NetCommons\Videos\Test\Case\Controller\VideoBlockRolePermissionsController
 */
class VideoBlockRolePermissionsControllerEditTest extends BlockRolePermissionsControllerEditTest {

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
	protected $_controller = 'video_block_role_permissions';

/**
 * 権限設定で使うFieldsの取得
 *
 * @return array
 */
	private function __approvalFields() {
		$data = array(
			'VideoSetting' => array(
				'use_workflow',
				'use_comment_approval',
				'approval_type',
			)
		);

		return $data;
	}

/**
 * テストDataの取得
 *
 * @return array
 */
	private function __data() {
		$blockId = '2';
		$blockKey = 'block_2';
		$blockName = 'Channel name';
		$data = array(
			'Block' => array(
				'id' => $blockId,
				'key' => $blockKey,
				'language_id' => '2',
				'room_id' => '2',
				'plugin_key' => $this->plugin,
				'public_type' => '1',
				'from' => null,
				'to' => null,
				'name' => $blockName,
			)
		);
		$data['VideoSetting'] = $data['Block'];
		$data['VideoSetting'] = Hash::merge($data['VideoSetting'], array(
			'use_workflow' => '1',
			'use_comment_approval' => '1',
			'approval_type' => '1',
		));
		//debug($data);

		return $data;
	}

/**
 * edit()アクションDataProvider
 *
 * ### 戻り値
 *  - approvalFields コンテンツ承認の利用有無のフィールド
 *  - exception Exception
 *  - return testActionの実行後の結果
 *
 * @return array
 * @see BlockRolePermissionsControllerEditTest::testEditGet()
 */
	public function dataProviderEditGet() {
		return array(
			'editアクションのGETテスト:表示' => array(
				'approvalFields' => $this->__approvalFields(),
			),
		);
	}

/**
 * edit()アクションDataProvider
 *
 * ### 戻り値
 *  - data POSTデータ
 *  - exception Exception
 *  - return testActionの実行後の結果
 *
 * @return array
 */
	public function dataProviderEditPost() {
		return array(
			'editアクションのGETテスト:表示' => array(
				'data' => $this->__data(),
			),
		);
	}

/**
 * editアクションのPOST validateエラーテスト
 *
 * @return void
 */
	public function testEditPostValidateError() {
		//ログイン
		TestAuthGeneral::login($this);

		$data = $this->__data();
		$data['VideoSetting']['use_workflow'] = 'xxx';

		$frameId = '6';
		$blockId = '2';
		$blockKey = 'block_2';
		$roomId = '2';
		$permissions = $this->_getPermissionData(true, Hash::check($data, '{s}.use_comment_approval'));

		$RolesRoomFixture = new RolesRoomFixture();
		$rolesRooms = Hash::extract($RolesRoomFixture->records, '{n}[room_id=' . $roomId . ']');

		$default['Block'] = array('id' => $blockId, 'key' => $blockKey);
		foreach ($permissions as $permission => $roles) {
			foreach ($roles as $role) {
				$rolesRoom = Hash::extract($rolesRooms, '{n}[role_key=' . $role . ']');
				$default['BlockRolePermission'][$permission][$role] = array(
					'roles_room_id' => $rolesRoom[0]['id'],
					'block_key' => $blockKey,
					'permission' => $permission,
					'value' => '1',
				);
			}
		}

		//テスト実施
		$url = array(
			'action' => 'edit',
			'frame_id' => $frameId,
			'block_id' => $blockId
		);
		$this->_testPostAction('post', Hash::merge($default, $data), $url);
		//debug($this->controller->validationErrors);

		//ログアウト
		TestAuthGeneral::logout($this);

		// チェック
		$this->assertEquals($this->controller->validationErrors['use_workflow'][0], __d('net_commons', 'Invalid request.'));
	}

}
