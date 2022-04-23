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
App::uses('NetCommonsSecurity', 'NetCommons.Utility');

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
		'Files.Download',
		//Currentで必要なため、定義する
		'Session',
		'Auth',
	);

/**
 * beforeFilter
 *
 * @return void
 */
	public function beforeFilter() {
		parent::beforeFilter();

		// NetCommonsAppController(VideosAppController) を継承しないため、ここでカレントデータセット
		$instance = Current::getInstance();
		$instance->initialize($this);
		// $componentsにAuthが設定されると、パブリックで公開された動画がログインしないと見れなくなるため、functionのfileを除外する
		// @see https://github.com/NetCommons3/NetCommons3/issues/1540
		$this->Auth->allow('file');
	}

/**
 * サムネイル、動画の表示
 *
 * @return CakeResponse
 * @throws NotFoundException 表示できない記事へのアクセス
 */
	public function file() {
		if (! (new NetCommonsSecurity())->enableBadIps()) {
			throw new NotFoundException();
		}

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
			//NC2からNC3で移行するとサムネイルが移行されないため、サムネイル画像がないときはNoImageを表示させる
			//@see https://github.com/NetCommons3/NetCommons3/issues/1617
			try {
				$response = $this->Download->doDownload($video['Video']['id']);
			} catch (Exception $ex) {
				if (!empty($this->request->params['pass']) &&
						$this->request->params['pass'][0] === Video::THUMBNAIL_FIELD) {
					//NoImageを表示する
					$noimagePath = CakePlugin::path('Videos') .
							'webroot' . DS . 'img' . DS . 'thumbnail_noimage.png';
					$this->response->file($noimagePath, ['name' => 'No Image']);
					$response = $this->response;
				} else {
					throw $ex;
				}
			}
			return $response;
		} else {
			// 表示できないなら404
			throw new NotFoundException();
		}
	}
}
