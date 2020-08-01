<?php
/**
 * 動画編集 template
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
			<?php echo $this->NetCommonsForm->hidden('Video.id'); ?>
			<?php echo $this->NetCommonsForm->hidden('Video.key'); ?>
			<?php echo $this->NetCommonsForm->hidden('Video.block_id'); ?>
			<?php echo $this->NetCommonsForm->hidden('Video.language_id'); ?>
			<?php echo $this->NetCommonsForm->hidden('Video.video_time'); ?>
			<?php echo $this->NetCommonsForm->hidden('Video.play_number'); ?>
			<?php echo $this->NetCommonsForm->hidden('Video.status'); ?>
			<?php echo $this->NetCommonsForm->hidden('UploadFile.' . Video::VIDEO_FILE_FIELD . '.id'); ?>
			<?php echo $this->NetCommonsForm->hidden('UploadFile.' . Video::VIDEO_FILE_FIELD . '.field_name'); ?>
			<?php echo $this->NetCommonsForm->hidden('UploadFile.' . Video::THUMBNAIL_FIELD . '.id'); ?>
			<?php echo $this->NetCommonsForm->hidden('UploadFile.' . Video::THUMBNAIL_FIELD . '.field_name'); ?>

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

				$videoHelpBlockMessage = __d('videos', 'Please register if you want to change.');
				if ($isFfmpegEnable) {
					$videoHelpBlockMessage .= sprintf(__d('videos', 'support of %s.'), Video::VIDEO_EXTENSION);
				} else {
					$videoHelpBlockMessage .= sprintf(__d('videos', 'support of %s.'), 'mp4');
				}
				$videoHelpBlockMessage .= '<br>' . sprintf(__d('videos', 'Maximum upload size: %s'), ini_get('upload_max_filesize'));
				$thumbnailHelpBlockMessage = __d('videos', 'Please register if you want to change.');
				$thumbnailHelpBlockMessage .= sprintf(__d('videos', 'support of %s.'), Video::THUMBNAIL_EXTENSION);
				?>
				<?php /** @see FilesFormHelper::uploadFile() */?>
				<?php echo $this->NetCommonsForm->uploadFile(Video::VIDEO_FILE_FIELD, array(
					'label' => __d('videos', 'Video file'),
					'help' => $videoHelpBlockMessage,
					'remove' => false,
				)); ?>

				<?php echo $this->NetCommonsForm->uploadFile(Video::THUMBNAIL_FIELD, array(
					'label' => __d('videos', 'Thumbnail'),
					'help' => h($thumbnailHelpBlockMessage),
					'remove' => false,
				)); ?>

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
			<?php echo $this->Workflow->buttons('Video.status', NetCommonsUrl::actionUrl(array(
				'controller' => 'videos',
				'action' => 'view',
				'block_id' => Current::read('Block.id'),
				'frame_id' => Current::read('Frame.id'),
				'key' => $this->request->data('Video.key')
			))); ?>

		<?php echo $this->Form->end(); ?>

		<?php if ($this->Workflow->canDelete('Videos.Video', $this->request->data('Video'))) : ?>
			<div class="panel-footer">
				<div class="text-right">
					<?php /* 削除 */ ?>
					<?php echo $this->NetCommonsForm->create('Video', array(
						'name' => 'form',
						'url' => NetCommonsUrl::blockUrl(array(
							'controller' => 'videos_edit',
							'action' => 'delete',
						)),
						'type' => 'delete',
					)); ?>
						<?php echo $this->NetCommonsForm->hidden('Frame.id'); ?>
						<?php echo $this->NetCommonsForm->hidden('Block.id'); ?>
						<?php echo $this->NetCommonsForm->hidden('Block.key'); ?>

						<?php echo $this->NetCommonsForm->hidden('Video.id'); ?>
						<?php echo $this->NetCommonsForm->hidden('Video.key'); ?>
						<?php echo $this->Button->delete('',
							sprintf(__d('net_commons', 'Deleting the %s. Are you sure to proceed?'), __d('videos', 'video'))
						); ?>

					<?php echo $this->NetCommonsForm->end(); ?>
				</div>
			</div>
		<?php endif; ?>
	</div>
	<?php echo $this->Workflow->comments(); ?>
</article>
