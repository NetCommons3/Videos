<?php
/**
 * Video::getConditions()のテスト
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('WorkflowGetTest', 'Workflow.TestSuite');

/**
 * Video::getConditions()のテスト
 *
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @package NetCommons\Videos\Test\Case\Model\Video
 */
class VideoGetConditionsTest extends WorkflowGetTest {

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
	protected $_methodName = 'getConditions';

/**
 * getConditions()のテスト
 *
 * @return void
 */
	public function testGetConditions() {
		$model = $this->_modelName;
		$methodName = $this->_methodName;

		//データ生成
		Current::writePermission('2', 'content_readable', true);

		//テスト実施
		$result = $this->$model->$methodName();

		//チェック
		//debug($result);
		$this->assertArrayHasKey('Video.block_id', $result);
	}

/**
 * getConditions()のパーミッションなしテスト
 *
 * @return void
 */
	public function testGetConditionsNoPermission() {
		$model = $this->_modelName;
		$methodName = $this->_methodName;

		//テスト実施
		$result = $this->$model->$methodName();

		//チェック
		//debug($result);
		$this->assertEquals(0, $result['Video.id']);
	}
}
