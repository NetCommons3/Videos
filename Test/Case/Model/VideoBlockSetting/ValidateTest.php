<?php
/**
 * VideoBlockSetting::validate()のテスト
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('NetCommonsValidateTest', 'NetCommons.TestSuite');
App::uses('VideoBlockSettingFixture', 'Videos.Test/Fixture');

/**
 * VideoBlockSetting::validate()のテスト
 *
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @package NetCommons\Videos\Test\Case\Model\VideoBlockSetting
 */
class VideoBlockSettingValidateTest extends NetCommonsValidateTest {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array(
		'plugin.videos.video_block_setting',
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
	protected $_modelName = 'VideoBlockSetting';

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
		$data['VideoBlockSetting'] = (new VideoBlockSettingFixture())->records[0];

		//debug($data);
		return array(
			array('data' => $data, 'field' => 'use_like', 'value' => 'dummy',
				'message' => __d('net_commons', 'Invalid request.')),
			array('data' => $data, 'field' => 'use_unlike', 'value' => 'dummy',
				'message' => __d('net_commons', 'Invalid request.')),
			array('data' => $data, 'field' => 'use_comment', 'value' => 'dummy',
				'message' => __d('net_commons', 'Invalid request.')),
			array('data' => $data, 'field' => 'use_workflow', 'value' => 'dummy',
				'message' => __d('net_commons', 'Invalid request.')),
			array('data' => $data, 'field' => 'auto_play', 'value' => 'dummy',
				'message' => __d('net_commons', 'Invalid request.')),
			array('data' => $data, 'field' => 'use_comment_approval', 'value' => 'dummy',
				'message' => __d('net_commons', 'Invalid request.')),
		);
	}

}