<?php
/**
 * Video::countUp()のテスト
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('NetCommonsModelTestCase', 'NetCommons.TestSuite');
App::uses('VideoFixture', 'Videos.Test/Fixture');

/**
 * Video::countUp()のテスト
 *
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @package NetCommons\Videos\Test\Case\Model\Video
 */
class VideoCountUpTest extends NetCommonsModelTestCase {

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
	protected $_methodName = 'countUp';

/**
 * countUp()のテスト
 *
 * @return void
 */
	public function testCountUp() {
		$model = $this->_modelName;
		$methodName = $this->_methodName;

		//データ生成
		$data['Video'] = (new VideoFixture)->records[0];

		//テスト実施
		$result = $this->$model->$methodName($data);

		//チェック
		//debug($result);
		$this->assertEquals(2, $result);
	}

/**
 * countUpのExceptionErrorテスト
 *
 * @param array $data 登録データ
 * @param string $mockModel Mockのモデル
 * @param string $mockMethod Mockのメソッド
 * @dataProvider dataProviderSaveOnExceptionError
 * @return void
 */
	public function testSaveOnExceptionError($data, $mockModel, $mockMethod) {
		$model = $this->_modelName;
		$method = $this->_methodName;

		$this->_mockForReturnFalse($model, $mockModel, $mockMethod);

		$this->setExpectedException('InternalErrorException');
		$this->$model->$method($data);
	}

/**
 * countUpのExceptionError用DataProvider
 *
 * ### 戻り値
 *  - data 登録データ
 *  - mockModel Mockのモデル
 *  - mockMethod Mockのメソッド
 *
 * @return array テストデータ
 */
	public function dataProviderSaveOnExceptionError() {
		$data['Video'] = (new VideoFixture)->records[0];

		return array(
			array($data, 'Videos.Video', 'save'),
		);
	}
}
