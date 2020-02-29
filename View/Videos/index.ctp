<?php
/**
 * 動画一覧 template
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @author Kazunori Sakamoto <exkazuu@willbooster.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

echo $this->NetCommonsHtml->css('/videos/css/style.css');
echo $this->NetCommonsHtml->script('/videos/js/videos.js');
?>

<div class="nc-content-list">

	<?php /* ブロック名, タグ検索表示 */ ?>
	<?php echo $this->NetCommonsHtml->blockTitle($listTitle); ?>

	<header class="video-margin-row">

		<?php /* 上部ボタン */ ?>
		<div class="clearfix">
			<div class="pull-left">
				<?php /* 絞り込み(カテゴリ) */ ?>
				<span class="dropdown">
					<button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-expanded="true">
						<span class="pull-left nc-drop-down-ellipsis">
							<?php echo h($filterDropDownLabel) ?>
						</span>
						<span class="pull-right">
							<span class="caret"></span>
						</span>
					</button>
					<ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMenu1">
						<li role="presentation">
							<a role="menuitem" tabindex="-1" href="<?php echo $this->NetCommonsHtml->url(
								array(
									'action' => 'index',
									'frame_id' => Current::read('Frame.id'),
								)
							);?>">
								<?php echo __d('videos', 'All videos') ?>
							</a>
						</li>
						<li role="presentation" class="dropdown-header"><?php echo __d('categories', 'Category') ?></li>

						<?php /** @see CategoryHelper::dropDownToggle() */?>
						<?php echo $this->Category->dropDownToggle(array(
							'empty' => false,
							'displayMenu' => false,
							$this->NetCommonsHtml->url(array('action' => 'index')),
						)); ?>
					</ul>
				</span>

				<?php /* ソート順 */ ?>
				<span class="btn-group text-left">
					<?php $displayOrderOptions = array(
						'Video.created.desc' => array(
							'label' => __d('videos', 'Newest'),
							'sort' => 'Video.created',
							'direction' => 'desc'
						),
						'Video.title.asc' => array(
							'label' => __d('videos', 'By title'),
							'sort' => 'Video.title',
							'direction' => 'asc'
						),
						'Video.play_number.desc' => array(
							'label' => __d('videos', 'Viewed'),
							'sort' => 'Video.play_number',
							'direction' => 'desc'
						),
						'Like.weight.desc' => array(
							'label' => __d('videos', 'Reviews'),
							'sort' => 'Like.weight',
							'direction' => 'desc'
						),
					); ?>

					<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
						<?php echo $displayOrderOptions[$displayOrderPaginator]['label']; ?>
						<span class="caret"></span>
					</button>

					<ul class="dropdown-menu" role="menu">
						<?php foreach ($displayOrderOptions as $key => $sort) : ?>
							<li>
								<?php echo $this->Paginator->link($sort['label'], array('sort' => $sort['sort'], 'direction' => $sort['direction'])); ?>
							</li>
						<?php endforeach; ?>
					</ul>
				</span>

				<?php /* 表示件数 */ ?>
				<?php echo $this->DisplayNumber->dropDownToggle(); ?>
			</div>
			<div class="pull-right">
				<?php
				if (Current::permission('content_creatable')) {
					echo $this->Button->addLink(__d('videos', 'video'),
						array('controller' => 'videos_edit', 'action' => 'add', 'frame_id' => Current::read('Frame.id'))
					);
				}
				?>
			</div>
		</div>
	</header>

	<?php if ($this->Paginator->param('count') == 0) : ?>
		<div class="nc-not-found"><?php echo sprintf(__d('net_commons', '%s is not.'), __d('videos', 'Video')); ?></div>
	<?php else : ?>
		<?php /* 動画一覧 */ ?>
		<?php
			$initialValues = array();
			$cacheable = $this->CDNCache->isCacheable();
			foreach ($videos as $video) {
				$value = $cacheable ? null : $video['Video']['play_number'];
				$initialValues[$video['Video']['id']] = $value;
			}
		?>
		<div ng-controller="Video.index"
			ng-init="init(<?php echo h(json_encode(Current::read('Frame.id'))
				. ', ' . h(json_encode($initialValues)); ?>)">
			<?php foreach ($videos as $video) : ?>
				<article>
					<?php echo $this->element('Videos.Videos/list', array(
						'video' => $video,
						'videoSetting' => $videoSetting,
						'isFfmpegEnable' => $isFfmpegEnable,
					)); ?>
				</article>
			<?php endforeach; ?>
		</div>

		<?php /* ページャ */ ?>
		<?php echo $this->element('NetCommons.paginator'); ?>
	<?php endif; ?>
</div>
