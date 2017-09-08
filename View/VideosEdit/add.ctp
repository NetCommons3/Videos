<?php
/**
 * 動画登録 template
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

$this->NetCommonsHtml->script(array(
	'/tags/js/tags.js',
	'/videos/js/videos.js',
));

$video = NetCommonsAppController::camelizeKeyRecursive(array('video' => $this->data['Video']));
?>

<article ng-controller="VideoEdit" ng-init="initialize(<?php echo h(json_encode($video)); ?>)">
	<?php /* ブロック名表示 */ ?>
	<?php echo $this->NetCommonsHtml->blockTitle(Current::read('BlocksLanguage.name')); ?>

	<div class="panel panel-default">
		<?php /* ファイル送信は、FormHelperでform作成時、'type' => 'file' 必要。記述すると enctype="multipart/form-data" が追加される */ ?>
		<?php echo $this->NetCommonsForm->create('Video', array(
			'name' => 'form',
			'novalidate' => true,
			'type' => 'file',
		)); ?>
			<?php echo $this->NetCommonsForm->hidden('Video.block_id'); ?>
			<?php echo $this->NetCommonsForm->hidden('Video.language_id'); ?>

			<div class="panel-body">
				<?php
				echo $this->TitleIcon->inputWithTitleIcon(
					'Video.title',
					'Video.title_icon',
					array(
						'type' => 'text',
						'label' => __d('videos', 'Title'),
						'required' => 'required',
					)
				);

				if ($isFfmpegEnable) {
					$videoHelpBlockMessage = sprintf(__d('videos', 'support of %s.'), Video::VIDEO_EXTENSION);
				} else {
					$videoHelpBlockMessage = sprintf(__d('videos', 'support of %s.'), 'mp4');
				}
				?>
				<?php echo $this->NetCommonsForm->uploadFile(Video::VIDEO_FILE_FIELD, array(
					'label' => __d('videos', 'Video file'),
					'required' => true,
					'help' => h($videoHelpBlockMessage),
				)); ?>

				<?php /* ffmpeg=OFF */ ?>
				<?php if (!$isFfmpegEnable) : ?>
					<?php echo $this->NetCommonsForm->uploadFile(Video::THUMBNAIL_FIELD, array(
						'label' => __d('videos', 'Thumbnail'),
						'required' => true,
						'help' => sprintf(__d('videos', 'support of %s.'), Video::THUMBNAIL_EXTENSION),
					)); ?>
				<?php endif; ?>

				<?php echo $this->NetCommonsForm->wysiwyg('Video.description', array(
					'label' => __d('videos', 'Description'),
					'rows' => 5
				)); ?>

				<?php /** @see CategoryHelper::select() */ ?>
				<?php echo $this->Category->select('Video.category_id', array('empty' => true)); ?>

				<div class="form-group">
					<?php $this->NetCommonsForm->unlockField('Tag');
					echo $this->element('Tags.tag_form', array(
						'tagData' => isset($this->request->data['Tag']) ? $this->request->data['Tag'] : array(),
						'modelName' => 'Video',
					)); ?>
				</div>

				<hr />

				<?php echo $this->Workflow->inputComment('Video.status'); ?>
			</div>
			<?php echo $this->Workflow->buttons('Video.status'); ?>

		<?php echo $this->NetCommonsForm->end(); ?>
	</div>
	<?php echo $this->Workflow->comments(); ?>
</article>
