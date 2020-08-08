<?php
/**
 * 動画詳細 template
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @author Kazunori Sakamoto <exkazuu@willbooster.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

echo $this->NetCommonsHtml->css('/videos/css/style.css');
echo $this->NetCommonsHtml->script(array(
	'/videos/js/videos.js',
	'/authorization_keys/js/authorization_keys.js',
));
?>

<?php /* 上部ボタン */ ?>
<header>
	<div class="clearfix">
		<div class="pull-left">
			<?php echo $this->LinkButton->toList(); ?>
		</div>
		<div class="pull-right text-right">
			<?php
			if ($this->Workflow->canEdit("Videos.Video", $video)) {
				echo $this->Button->editLink(__d('net_commons', 'Edit'),
					array(
						'controller' => 'videos_edit',
						'action' => 'edit',
						'key' => $video['Video']['key']
					)
				);
			}
			?>
		</div>
	</div>
</header>

<?php /* 動画 */ ?>
<div class="video-margin-row">
	<?php /* 動画プレイヤー */ ?>
	<?php echo $this->element('Videos/player', array(
		'fileMp4Url' => [
			'controller' => 'video_files',
			'action' => 'file',
			'key' => $video['Video']['key'],
			Video::VIDEO_FILE_FIELD,
		],
		'fileThumbnailUrl' => [
			'controller' => 'video_files',
			'action' => 'file',
			'key' => $video['Video']['key'],
			Video::THUMBNAIL_FIELD,
			//'big',
		],
		'isAutoPlay' => $videoSetting['auto_play'],
	)); ?>
</div>

<?php
	$initialValues = array();
	$cacheable = $this->CDNCache->isCacheable();
	$value = $cacheable ? null : $video['Video']['play_number'];
	$initialValues[$video['Video']['id']] = $value;
?>
<div class="video-margin-row" ng-controller="VideoView"
	 ng-init="init(<?php echo h(json_encode(Current::read('Frame.id'))) .
			', ' . h(json_encode($initialValues)); ?>)">
	<div class="panel panel-default video-detail">
		<div class="nc-content-list">
			<?php /* ステータス、タイトル */ ?>
			<h1>
				<?php echo $this->Workflow->label($video['Video']['status']); ?>
				<?php echo $this->TitleIcon->titleIcon($video['Video']['title_icon']); ?>
				<?php echo h($video['Video']['title']); ?>
			</h1>
		</div>
		<div>
			<?php /* 登録日 */ ?>
			<?php echo __d('videos', 'Registration Date') . '：' . $this->Date->dateFormat($video['Video']['created']); ?>
			&nbsp;
			<?php /* 投稿者 */ ?>
			<?php echo $this->DisplayUser->handleLink($video, ['avatar' => true]); ?>
			&nbsp;
			<?php /* カテゴリ */ ?>
			<?php if ($video['Video']['category_id']) : ?>
				<?php echo __d('categories', 'Category') ?>:
				<?php echo $this->NetCommonsHtml->link($video['CategoriesLanguage']['name'], array(
					'controller' => 'videos',
					'action' => 'index',
					'category_id' => $video['Video']['category_id']
				)); ?>
			<?php endif; ?>
		</div>
		<div class="video-description">
			<?php /* 説明 */ ?>
			<?php echo $video['Video']['description']; ?>
		</div>
		<div class="video-detail-links-row">
			<?php /* ブロック編集許可OK（編集長以上）ならダウンロードできる */ ?>
			<?php if (Current::permission('block_editable') && $useDownloadLink): ?>
				<span class="video-detail-links">
					<?php /* ダウンロード */ ?>
					<a href="" authorization-keys-popup-link frame-id="<?php echo Current::read('Frame.id'); ?>"
					   url="<?php echo NetCommonsUrl::blockUrl(array(
							'plugin' => 'videos',
							'controller' => 'videos',
							'action' => 'download',
							'key' => $video['Video']['key']
						), false); ?>"
						popup-title="<?php echo __d('authorization_keys', 'Compression password'); ?>"
						popup-label="<?php echo __d('authorization_keys', 'Compression password'); ?>"
						popup-placeholder="<?php echo __d('authorization_keys', 'please input compression password'); ?>">
						<?php echo __d('videos', 'Downloads'); ?>
					</a>
				</span>
			<?php endif; ?>

			<span class="video-detail-links">
				<?php /* 埋め込みコード */ ?>
				<a href="" ng-click="embed();"><?php echo __d('videos', 'Embed'); ?></a>
			</span>

			<?php /* 再生回数 */ ?>
			<span class="video-count-icons" ng-cloak>
				<span class="glyphicon glyphicon-play" aria-hidden="true"></span>
				{{playCounts[<?php echo h(json_encode($video['Video']['id'])); ?>]}}
			</span>

			<?php /* いいね */ ?>
			<?php echo $this->Like->buttons('Video', $videoSetting, $video); ?>
		</div>
		<div class="form-group video-embed">
			<?php /* 埋め込みコード(非表示) */ ?>
			<input type="text" class="form-control video-embed-text" value='<iframe width="400" height="300" src="<?php echo $this->NetCommonsHtml->url(
				[
					'action' => 'embed',
					'key' => $video['Video']['key'],
				],
				true
			); ?>" frameborder="0" scrolling="no" allowfullscreen></iframe>'>
		</div>
		<div>
			<?php /* Tags */ ?>
			<?php if (isset($video['Tag'])) : ?>
				<?php echo __d('tags', 'tag'); ?>:
				<?php foreach ($video['Tag'] as $tag): ?>
					<?php echo $this->NetCommonsHtml->link($tag['name'], array(
						'controller' => 'videos',
						'action' => 'tag',
						'id' => $tag['id'],
					)); ?>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</div>
</div>

<?php /* 関連動画 */ ?>
<div class="video-margin-row">
	<div id="nc-related-videos-<?php echo Current::read('Frame.id'); ?>" ng-controller="RelatedVideos">
		<?php
		$initialValues = array();
		foreach ($relatedVideos as $video) {
			$value = $cacheable ? null : $video['Video']['play_number'];
			$initialValues[$video['Video']['id']] = $value;
		}
		?>
		<?php $i = 0; ?>
		<div ng-controller="Video.index"
			 ng-init="init(<?php echo h(json_encode(Current::read('Frame.id'))) .
					', ' . h(json_encode($initialValues)); ?>)">
			<?php foreach ($relatedVideos as $relatedVideo) : ?>
				<?php /* related-videoはJSで必要 */ ?>
				<article class="related-video <?php echo $i >= VideosController::START_LIMIT_RELATED_VIDEO ? 'hidden' : '' ?>">
					<?php echo $this->element('Videos.Videos/list', array(
						"video" => $relatedVideo,
						"style" => 'panel panel-default',
						"videoSetting" => $videoSetting,
						"isFfmpegEnable" => $isFfmpegEnable,
					)); ?>
				</article>
				<?php $i++; ?>
			<?php endforeach; ?>
		</div>

		<?php /* もっと見る */ ?>
		<?php
		$hidden = '';
		if ($i <= VideosController::START_LIMIT_RELATED_VIDEO) {
			$hidden = 'hidden';
		}
		echo $this->NetCommonsForm->button(__d('net_commons', 'More'),
			[
				// related-video-moreはJSで必要
				'class' => 'btn btn-info btn-block related-video-more ' . $hidden,
				'ng-click' => 'more();',
			]
		); ?>
	</div>
</div>

<?php /* コンテンツコメント */ ?>
<?php echo $this->ContentComment->index($video);
