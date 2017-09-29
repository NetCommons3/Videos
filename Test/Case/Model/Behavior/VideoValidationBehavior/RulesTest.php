<?php
/**
 * VideoValidationBehavior::rules()のテスト
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('NetCommonsModelTestCase', 'NetCommons.TestSuite');
App::uses('VideoValidationBehavior', 'Videos.Model/Behavior');

/**
 * VideoValidationBehavior::rules()のテスト
 *
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @package NetCommons\Videos\Test\Case\Model\Behavior\VideoValidationBehavior
 */
class VideoValidationBehaviorRulesTest extends NetCommonsModelTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array();

/**
 * Plugin name
 *
 * @var string
 */
	public $plugin = 'videos';

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();

		//テストプラグインのロード
		NetCommonsCakeTestCase::loadTestPlugin($this, 'Videos', 'TestVideos');
		$this->TestModel = ClassRegistry::init('TestVideos.TestVideoValidationBehaviorModel');
	}

/**
 * rules()テストのDataProvider
 *
 * ### 戻り値
 *
 * @return array データ
 */
	public function dataProvider() {
		return array(
			'登録:Ffmpge無効' => array(
				'options' => array('add'), 'isFfmpegEnable' => 0, 'checkKey' => 'video_file',
			),
			'登録:Ffmpge有効' => array(
				'options' => array('add'), 'isFfmpegEnable' => 1, 'checkKey' => 'video_file',
			),
			'編集:Ffmpge無効' => array(
				'options' => array('edit'), 'isFfmpegEnable' => 0, 'checkKey' => 'thumbnail',
			),
			'編集:Ffmpge有効' => array(
				'options' => array('edit'), 'isFfmpegEnable' => 1, 'checkKey' => 'thumbnail',
			),
		);
	}

/**
 * rules()のテスト
 *
 * @param array $options validateのオプション
 * @param int $isFfmpegEnable FFMPGE有効フラグ
 * @param string $checkKey このキーで配列存在チェック
 * @dataProvider dataProvider
 * @return void
 */
	public function testRules($options = array(), $isFfmpegEnable = 0, $checkKey = '') {
		//テスト実施
		$this->TestModel->setVideoValidationSettings(VideoValidationBehavior::IS_FFMPEG_ENABLE, $isFfmpegEnable);
		$result = $this->TestModel->rules($options);

		//チェック
		//debug($result);
		$this->assertArrayHasKey($checkKey, $result);
	}

}
