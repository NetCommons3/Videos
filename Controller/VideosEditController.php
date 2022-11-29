<?php
/**
 * 動画編集系 Controller
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('VideosAppController', 'Videos.Controller');
App::uses('VideosAppModel', 'Videos.Model');
App::uses('Video', 'Videos.Model');

/**
 * 動画編集系 Controller
 *
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @package NetCommons\Videos\Controller
 */
class VideosEditController extends VideosAppController {

/**
 * use model
 *
 * @var array
 */
	public $uses = array(
		'Categories.Category',
		'Videos.Video',
	);

/**
 * use components
 *
 * @var array
 */
	public $components = array(
		'Files.FileUpload',
		'NetCommons.Permission' => array(
			//アクセスの権限
			'allow' => array(
				'add,edit,delete' => 'content_creatable',
			),
		),
	);

/**
 * use helpers
 *
 * @var array
 */
	public $helpers = array(
		'Categories.Category',
		'NetCommons.TitleIcon',
		'Workflow.Workflow',
	);

/**
 * beforeFilter
 *
 * @return bool
 * @see NetCommonsAppController::beforeFilter()
 */
	public function beforeFilter() {
		parent::beforeFilter();

		// bugfix: post_max_size を超えると $_POSTが空っぽになり(php標準の動き)、postした値とサーバ側で受け取った値がずれて予期しないトークンエラー対応
		// post & $_FILESが空 & add|editアクションのみ、セキュリティコンポーネントを除外
		//   補足1: 除外すると、FileUploadComponent::startup()で、「post_max_size を超えると $_POSTが空っぽ」でエラーメッセージを表示してくれる。
		//   補足2: 登録前にアップロード空チェックは、バリデーションで必須チェックしているため、empty($_FILES)を通しても問題なし
		//   補足3: post_max_sizeを超えなければ$_FILESの値は消えない & upload_max_filesizeを超えた場合は、バリデーションのuploadErrorでチェックしているため、問題なし
		//   補足4: $this->NetCommonsForm->unlockField(Video::VIDEO_FILE_FIELD);指定、$this->Security->csrfCheck = false;を指定しても「予期しないトークンエラー」となったため、$this->Security->unlockedActionsを使用
		$unlockedActions = ['add', 'edit'];
		if ($this->request->is('post') && empty($_FILES) && in_array($this->action, $unlockedActions)) {
			$this->Security->unlockedActions = $unlockedActions;
		}

		// ブロック未選択は、何も表示しない
		if (! Current::read('Block.id')) {
			$this->setAction('emptyRender');
			return false;
		}

		// FFMPEG有効フラグ
		/* @see VideoBehavior::isFfmpegEnable() */
		$this->set('isFfmpegEnable', $this->Video->isFfmpegEnable());
	}

/**
 * 登録
 *
 * @return CakeResponse
 */
	public function add() {
		$this->set('video', null);

		$categories = $this->Category->getCategories(Current::read('Block.id'),
			Current::read('Room.id'));
		$this->set('categories', $categories);

		if ($this->request->is('post')) {
			//登録処理
			$data = $this->data;
			$data['Video']['status'] = $this->Workflow->parseStatus();
			unset($data['Video']['id']);

			// 登録
			if ($this->Video->saveVideo($data)) {
				return $this->redirect(NetCommonsUrl::backToPageUrl());
			}

			$this->NetCommons->handleValidationError($this->Video->validationErrors);

		} else {
			//表示処理
			$this->request->data = Hash::merge($this->request->data,
				$this->Video->create()
			);
			$this->request->data['Frame'] = Current::read('Frame');
			$this->request->data['Block'] = Current::read('Block');
		}
	}

/**
 * 編集
 *
 * @return CakeResponse
 */
	public function edit() {
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

		if (! $this->Video->canEditWorkflowContent($video)) {
			return $this->throwBadRequest();
		}

		$categories = $this->Category->getCategories(Current::read('Block.id'),
			Current::read('Room.id'));
		$this->set('categories', $categories);

		/* @see WorkflowCommentBehavior::getCommentsByContentKey() */
		$comments = $this->Video->getCommentsByContentKey($videoKey);
		$this->set('comments', $comments);

		if ($this->request->is('put')) {

			$data = $this->data;
			$data['Video']['status'] = $this->Workflow->parseStatus();
			unset($data['Video']['id']);

			// 登録（ワークフロー対応のため、編集でも常にinsert）
			if ($video = $this->Video->saveVideo($data, 1)) {
				$url = NetCommonsUrl::actionUrl(array(
					'controller' => 'videos',
					'action' => 'view',
					'block_id' => Current::read('Block.id'),
					'frame_id' => Current::read('Frame.id'),
					'key' => $video['Video']['key']
				));
				return $this->redirect($url);
			}
			$this->NetCommons->handleValidationError($this->Video->validationErrors);

		} else {
			$this->request->data = $video;
			$this->request->data['Frame'] = Current::read('Frame');
			$this->request->data['Block'] = Current::read('Block');
		}
	}

/**
 * 削除
 *
 * @return CakeResponse
 */
	public function delete() {
		if (! $this->request->is('delete')) {
			return $this->throwBadRequest();
		}

		$video = $this->Video->getWorkflowContents('first', array(
			'recursive' => 1,
			'conditions' => array(
				$this->Video->alias . '.key' => $this->data['Video']['key']
			)
		));

		//削除権限チェック
		if (! $this->Video->canDeleteWorkflowContent($video)) {
			return $this->throwBadRequest();
		}

		// 削除
		if (!$this->Video->deleteVideo($this->data)) {
			return $this->throwBadRequest();
		}

		// 一覧へ
		$url = NetCommonsUrl::actionUrl(array(
			'controller' => 'videos',
			'action' => 'index',
			'block_id' => $this->data['Block']['id'],
			'frame_id' => $this->data['Frame']['id'],
		));
		$this->redirect($url);
	}
}
