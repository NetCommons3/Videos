<?php
/**
 * 動画表示系 Controller
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('VideosAppController', 'Videos.Controller');
App::uses('ZipDownloader', 'Files.Utility');
App::uses('Video', 'Videos.Model');

/**
 * Videos Controller
 *
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @package NetCommons\Videos\Controller
 */
class VideosController extends VideosAppController {

/**
 * 関連動画 もっと見る start limit
 *
 * @var int
 */
	const START_LIMIT_RELATED_VIDEO = 3;

/**
 * 関連動画 もっと見る max limit
 *
 * @var int
 */
	const MAX_LIMIT_RELATED_VIDEO = 10;

/**
 * （編集長以上が使える）ダウンロードリンクを使用する
 * 使用しない場合はfalseに書き換える。
 *
 * @var bool
 */
	public $useDownloadLink = true;

/**
 * use model
 *
 * @var array
 */
	public $uses = array(
		'Categories.Category',
		'ContentComments.ContentComment',	// コンテンツコメント
		'Videos.Video',
		'Videos.VideoSetting',
		'Videos.VideoFrameSetting',
	);

/**
 * use helpers
 *
 * @var array
 * @see NetCommonsAppController::$helpers
 */
	public $helpers = array(
		'Categories.Category',
		'ContentComments.ContentComment' => array(
			'viewVarsKey' => array(
				'contentKey' => 'video.Video.key',
				'contentTitleForMail' => 'video.Video.title',
				'useComment' => 'videoSetting.use_comment',
				'useCommentApproval' => 'videoSetting.use_comment_approval',
			),
		),
		'Likes.Like',
		'NetCommons.DisplayNumber',
		'NetCommons.TitleIcon',
		'Videos.Video',
		'Workflow.Workflow',
		'Users.DisplayUser',
	);

/**
 * use components
 *
 * @var array
 * @link http://book.cakephp.org/2.0/ja/controllers/components.html#configuring-components
 * @link http://book.cakephp.org/2.0/ja/core-libraries/collections.html#id6 デフォルトのプライオリティ 10
 * @see NetCommonsAppController::$components
 * @see ContentCommentsComponent::beforeRender()
 *
 */
	public $components = array(
		'ContentComments.ContentComments' => array(
			'viewVarsKey' => array(
				'contentKey' => 'video.Video.key',
				'useComment' => 'videoSetting.use_comment',
			),
			'allow' => array('view'),
		),
		'Cookie',
		'Paginator',									// ページャ
		'Files.Download',
	);

/**
 * beforeFilter
 *
 * @return void
 * @see NetCommonsAppController::beforeFilter()
 */
	public function beforeFilter() {
		parent::beforeFilter();

		// ブロック未選択は、何も表示しない
		if (! Current::read('Block.id')) {
			$this->setAction('emptyRender');
			return false;
		}

		// ゲストアクセスOKのアクションを設定
		$this->Auth->allow('tag', 'embed');

		// FFMPEG有効フラグ
		/* @see VideoBehavior::isFfmpegEnable() */
		$this->set('isFfmpegEnable', $this->Video->isFfmpegEnable());
		// （編集長以上が使える）ダウンロードリンクを使用する
		$this->set('useDownloadLink', $this->useDownloadLink);
	}

/**
 * 一覧表示
 *
 * @return CakeResponse
 */
	public function index() {
		$conditions = array();
		$this->set('listTitle', Current::read('BlocksLanguage.name'));
		$this->set('filterDropDownLabel', __d('videos', 'All videos'));

		$categoryId = Hash::get($this->request->params['named'], 'category_id', 0);
		// URLから直入力対策
		$categoryId = (int)$categoryId;
		if ($categoryId) {
			$category = $this->Category->find('first', array(
				'recursive' => 0,
				'conditions' => array('Category.id' => $categoryId),
			));
			// 存在するカテゴリのみ
			if ($category) {
				$this->set(
					'listTitle',
					__d('categories', 'Category') . ':' . $category['CategoriesLanguage']['name']
				);
				$this->set('filterDropDownLabel', $category['CategoriesLanguage']['name']);
				$conditions['Video.category_id'] = $categoryId;
			}
		}

		// 一覧取得
		$results = $this->__list($conditions);
		$this->set($results);
	}

/**
 * tag別一覧
 *
 * @return CakeResponse
 */
	public function tag() {
		$this->view = 'index';
		$conditions = array();
		$this->set('listTitle', Current::read('BlocksLanguage.name'));
		$this->set('filterDropDownLabel', __d('videos', 'All videos'));

		// indexとのちがいはtagIdでの絞り込みだけ
		$tagId = Hash::get($this->request->params['named'], 'id', 0);
		// URLから直入力対策
		$tagId = (int)$tagId;

		if ($tagId) {
			// タグ名をタイトルに
			$tag = $this->Video->getTagByTagId($tagId);
			// 存在するタグのみ
			if ($tag) {
				$this->set('listTitle', __d('tags', 'tag') . ':' . $tag['Tag']['name']);
				// これを有効にするにはentry_tag_linkもJOINして検索か。
				$conditions['Tag.id'] = $tagId;
			}
		}

		// 一覧取得
		$results = $this->__list($conditions);
		$this->set($results);
	}

/**
 * 詳細表示
 *
 * @return CakeResponse
 */
	public function view() {
		//動画の取得
		$videoKey = $this->params['key'];
		/** @see WorkflowBehavior::getWorkflowContents() */
		$video = $this->Video->getWorkflowContents('first', array(
			'recursive' => 1,
			'conditions' => array(
				'Video.key' => $videoKey
			)
		));
		if (empty($video)) {
			return $this->throwBadRequest();
		}
		$this->set('video', $video);

		//新着データを既読にする
		$this->Video->saveTopicUserStatus($video);

		//関連動画の取得
		$relatedVideos = $this->Video->getWorkflowContents('all', array(
			'recursive' => 1,
			'conditions' => array(
				'Video.block_id' => Current::read('Frame.block_id'),
				'Video.created_user' => $video['Video']['created_user'],
				'NOT' => array(
					'Video.id' => $video['Video']['id'],
				),
			),
			'order' => 'Video.id DESC',
			'limit' => self::MAX_LIMIT_RELATED_VIDEO
		));
		$this->set('relatedVideos', $relatedVideos);

		// 利用系(コメント利用、高く評価を利用等)の設定取得
		$videoSetting = $this->VideoSetting->getVideoSetting(); //データあり
		$this->set('videoSetting', $videoSetting['VideoSetting']);

		// クッキー対応
		$cookie = $this->Cookie->read('video_history');
		$cookieArray = explode(':', $cookie);

		if (! in_array($video['Video']['id'], $cookieArray, true)) {
			//再生回数 + 1 で更新
			$playNumber = $this->Video->countUp($video);
			$this->viewVars['video']['Video']['play_number'] = $playNumber;

			// cookie value = コンテンツid & 区切り文字
			$cookie = $cookie . $video['Video']['id'] . ':';

			// CookieComponentは3501byte～3550byte位をwriteしても、cookieに書き込んでくれなくなる。
			// このcookieは、動画の視聴回数の抑制に使っていて、そこまで重要ではない＆1時間で消えるので、
			// 3500byteを超えたら、cookieの内容をクリアする。
			if (strlen($cookie) > 3500) {
				$cookie = $video['Video']['id'] . ':';
			}
			// アクセス情報を記録
			$this->Cookie->write('video_history', $cookie, false, '1 hour');
		}
	}

/**
 * 埋め込み動画表示
 *
 * @return CakeResponse
 */
	public function embed() {
		$this->view();
		$this->Components->unload('Pages.PageLayout');
		$this->layout = 'Videos.embed';
	}

/**
 * 動画のzipダウンロード
 *
 * @return CakeResponse
 * @throws NotFoundException 表示できない記事へのアクセス
 * @throws ForbiddenException アクセス権なし
 * @see DownloadComponent::doDownload()
 */
	public function download() {
		// ダウンロードリンク使わないなら、400
		if (!$this->useDownloadLink) {
			return $this->setAction('throwBadRequest');
		}
		// ブロック編集許可（編集長以上）持っていないなら403
		if (!Current::permission('block_editable')) {
			throw new ForbiddenException();
		}

		// ここから元コンテンツを取得する処理
		//$this->_prepare();
		$key = $this->params['key'];
		$conditions = $this->Video->getConditions();

		$conditions['Video.key'] = $key;
		$query = array(
			'conditions' => $conditions,
		);
		$video = $this->Video->find('first', $query);
		// ここまで元コンテンツを取得する処理

		// ダウンロード実行
		if (!$video) {
			// 表示できない記事へのアクセスなら404
			throw new NotFoundException(__d('videos', 'Invalid video entry'));
		}

		// 圧縮用パスワードキーを求める
		if (! empty($this->request->data['AuthorizationKey']['authorization_key'])) {
			$zipPassword = $this->request->data['AuthorizationKey']['authorization_key'];
		} else {
			$this->_setFlashMessageAndRedirect($key,
				__d('authorization_keys', 'please input compression password'));
			return;
		}

		// ダウンロードファイル名はタイトルにする
		$fileName = $video['Video']['title'];
		$zipFileName = $fileName . '.zip';
		$videoFileName = $fileName . '.mp4';

		$realFilePath = APP . WEBROOT_DIR . DS .
			$video['UploadFile'][Video::VIDEO_FILE_FIELD]['path'] .
			$video['UploadFile'][Video::VIDEO_FILE_FIELD]['id'] . DS .
			$video['UploadFile'][Video::VIDEO_FILE_FIELD]['real_file_name'];

		$zip = new ZipDownloader();
		$zip->addFile($realFilePath, $videoFileName);
		$zip->setPassword($zipPassword);
		$zip->close();

		return $zip->download($zipFileName);
	}

/**
 * _setFlashMessageAndRedirect
 *
 * @param string $contentKey コンテンツキー
 * @param string $message flash error message
 *
 * @return void
 */
	protected function _setFlashMessageAndRedirect($contentKey, $message) {
		$this->NetCommons->setFlashNotification($message,
			array('interval' => NetCommonsComponent::ALERT_VALIDATE_ERROR_INTERVAL));
		$url = NetCommonsUrl::actionUrl(array(
			'controller' => 'videos',
			'action' => 'view',
			'block_id' => Current::read('Block.id'),
			//'frame_id' => Current::read('Frame.id'),
			'key' => $contentKey
		), true);
		// 暫定対応：どうゆう訳だか、ここだと?frame_idが上記でセットされないので直書き
		$url .= '?frame_id=' . Current::read('Frame.id');
		$this->redirect($url);
	}

/**
 * 一覧取得
 *
 * @param array $extraConditions 追加conditions
 * @return array 動画一覧及び、設定
 * @throws Exception Paginatorによる例外
 */
	private function __list($extraConditions = array()) {
		$query['recursive'] = 0;

		/* @see WorkflowBehavior::getWorkflowConditions() */
		$query['conditions'] = $this->Video->getWorkflowConditions([
			'Video.block_id' => Current::read('Block.id'),
		]);

		$query['conditions'] = Hash::merge($query['conditions'], $extraConditions);

		// 表示系(並び順、表示件数)の設定取得
		$videoFrameSetting = $this->VideoFrameSetting->getVideoFrameSetting(true);

		//ソート
		$order = $this->__order($videoFrameSetting);
		$query['order'] = $order;
		$results['displayOrderPaginator'] = key($order) . '.' . $order[key($order)];

		//表示件数
		$limit = Hash::get($this->request->params['named'], 'limit');
		if ($limit) {
			$query['limit'] = (int)$limit;
		} else {
			$query['limit'] = $videoFrameSetting['VideoFrameSetting']['display_number'];
		}

		$this->Paginator->settings = $query;
		try {
			$videos = $this->Paginator->paginate('Video');
		} catch (Exception $ex) {
			CakeLog::error($ex);
			throw $ex;
		}
		$results['videos'] = $videos;

		// 利用系(コメント利用、高く評価を利用等)の設定取得
		$videoSetting = $this->VideoSetting->getVideoSetting();
		$results['videoSetting'] = $videoSetting['VideoSetting'];

		// カテゴリ
		$categories = $this->Category->getCategories(Current::read('Block.id'),
			Current::read('Room.id'));
		$results['categories'] = $categories;

		return $results;
	}

/**
 * ソート条件 取得
 *
 * @param array $videoFrameSetting videoFrameSetting
 * @return array ソート条件
 */
	private function __order($videoFrameSetting) {
		$sort = Hash::get($this->request->params['named'], 'sort');
		$direction = Hash::get($this->request->params['named'], 'direction');

		//ソート
		if (isset($sort) && isset($direction)) {
			$order = array($sort => $direction);
		} else {
			$displayOrder = $videoFrameSetting['VideoFrameSetting']['display_order'];
			if ($displayOrder == VideoFrameSetting::DISPLAY_ORDER_NEW) {
				$order = array('Video.created' => 'desc');
			} elseif ($displayOrder == VideoFrameSetting::DISPLAY_ORDER_TITLE) {
				$order = array('Video.title' => 'asc');
			} elseif ($displayOrder == VideoFrameSetting::DISPLAY_ORDER_PLAY) {
				$order = array('Video.play_number' => 'desc');
			} elseif ($displayOrder == VideoFrameSetting::DISPLAY_ORDER_LIKE) {
				// Like.weight = like_count - unlike_count
				$order = array('Like.weight' => 'desc');
			}
		}

		return $order;
	}
}
