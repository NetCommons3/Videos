<?php
/**
 * 埋め込み動画 Layout
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */
?>
<?php //$cakeDescription = __d('cake_dev', 'CakePHP: the rapid development php framework'); ?>
<!DOCTYPE html>
<html lang="<?php echo Configure::read('Config.language') ?>">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="viewport" content="width=device-width">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="robots" content="noindex,nofollow" />

		<title><?php echo (isset($pageTitle) ? h($pageTitle) : ''); ?></title>

		<?php
			echo $this->html->meta('icon', '/net_commons/favicon.ico');
			echo $this->fetch('meta');

			echo $this->element('NetCommons.common_css');
			echo $this->fetch('css');
			echo $this->element('NetCommons.common_theme_css');

			echo $this->element('NetCommons.common_js');
			echo $this->fetch('script');
		?>
	</head>

	<body style="margin: 0px; padding: 0px;">
		<?php //echo $this->Session->flash(); ?>

		<?php echo $this->fetch('content'); ?>

	</body>
</html>
