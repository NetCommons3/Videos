<?php
/**
 * サムネイル、動画の表示 Controller
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('Controller', 'Controller');
App::uses('Current', 'NetCommons.Utility');

/**
 * サムネイル、動画の表示 Controller
 * パフォーマンス改善のため、NetCommonsAppController(VideosAppController)を継承しない
 *
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @package NetCommons\Videos\Controller
 * @property Video $Video
 * @property DownloadComponent $Download
 */
class VideoFilesController extends Controller {

/**
 * use model
 *
 * @var array
 */
	public $uses = array(
		'Videos.Video'
	);

/**
 * use components
 *
 * @var array
 */
	public $components = array(
		'Files.Download'
	);

/**
 * beforeFilter
 *
 * @return void
 */
	public function beforeFilter() {
		parent::beforeFilter();

		// NetCommonsAppController(VideosAppController) を継承しないため、ここでカレントデータセット
		Current::initialize($this);
	}

/**
 * サムネイル、動画の表示
 *
 * @return CakeResponse
 * @throws NotFoundException 表示できない記事へのアクセス
 */
	public function file() {
		// ここから元コンテンツを取得する処理
		$key = $this->params['key'];
		$conditions = $this->Video->getConditions();

		$conditions['Video.key'] = $key;
		$query = array(
			'conditions' => $conditions,
		);
		$video = $this->Video->find('first', $query);
		// ここまで元コンテンツを取得する処理

		// ダウンロード実行
		if ($video) {
			return $this->Download->doDownload($video['Video']['id']);
		} else {
			// 表示できないなら404
			throw new NotFoundException(__d('videos', 'Invalid video entry'));
		}
	}
}
