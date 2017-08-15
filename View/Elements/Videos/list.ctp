<?php
/**
 * 動画リスト Element
 *   - $video: 動画1件
 *   - $style: css
 *   - $videoSetting: 動画セッティング
 *   - $isFfmpegEnable: Ffmpegを使うか
 *
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

echo $this->NetCommonsHtml->css('/videos/css/style.css');
echo $this->NetCommonsHtml->script('/videos/js/videos.js');

if (!isset($style)) {
	$style = '';
}
?>

<div class="video-thumbnail-row <?php echo $style; ?>">
	<div class="media">
		<div class="media-left">
			<div>
				<?php /* サムネイル */ ?>
				<div>
					<a href="<?php echo $this->NetCommonsHtml->url(array('action' => 'view', 'key' => $video['Video']['key'])); ?>">
						<?php echo $this->NetCommonsHtml->image(
							[
								'controller' => 'video_files',
								'action' => 'file',
								'key' => $video['Video']['key'],
								Video::THUMBNAIL_FIELD,
							],
							[
								'alt' => $video['Video']['title'],
								'class' => 'img-rounded video-thumbnail-image',
							]
						); ?>
					</a>
				</div>

				<?php /* 再生時間 */ ?>
				<?php echo $this->Video->playTime($video['Video']['video_time'], $isFfmpegEnable); ?>
			</div>
		</div>
		<div class="media-body">
			<?php /* ステータス */ ?>
			<div>
				<?php echo $this->Workflow->label($video['Video']['status']); ?>
			</div>

			<?php /* タイトル */ ?>
			<div>
				<a href="<?php echo $this->NetCommonsHtml->url(array('action' => 'view', 'key' => $video['Video']['key'])); ?>">
					<h2>
						<?php echo $this->TitleIcon->titleIcon($video['Video']['title_icon']); ?>
						<?php echo h($video['Video']['title']); ?>
					</h2>
				</a>
			</div>

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
			<br />

			<?php /* 再生回数 */ ?>
			<span class="video-count-icons">
				<span class="glyphicon glyphicon-play" aria-hidden="true"></span> <?php echo $video['Video']['play_number'] ?>
			</span>

			<?php /* コメント数 */ ?>
			<?php echo $this->ContentComment->count($video, array('class' => 'video-count-icons')); ?>

			<?php /* いいね数 */ ?>
			<?php echo $this->Like->display($videoSetting, $video); ?>
		</div>
	</div>
</div>
