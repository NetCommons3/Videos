<?php
/**
 * 埋め込み動画表示 template
 *
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */
?>

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
));