<?php
/**
 * Video Behavior
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('Video', 'Videos.Model');
App::uses('TemporaryFolder', 'Files.Utility');

/**
 * Summary for Video Behavior
 */
class VideoBehavior extends ModelBehavior {

/**
 * setup
 *
 * @param Model $model モデル
 * @param array $settings 設定値
 * @return void
 * @link http://book.cakephp.org/2.0/ja/models/behaviors.html#ModelBehavior::setup
 */
	public function setup(Model $model, $settings = array()) {
		$this->settings[$model->alias] = $settings;

		$model->UploadFile = ClassRegistry::init('Files.UploadFile', true);
	}

/**
 * 動画変換とデータ保存
 *
 * @param Model $model モデル
 * @param array $video Video
 * @return bool true on success, false on error
 * @throws InternalErrorException
 */
	public function saveConvertVideo(Model $model, $video) {
		// 元動画 取得
		$noConvert = $model->UploadFile->getFile('videos', $model->id, Video::VIDEO_FILE_FIELD);

		// --- 動画変換
		$this->__convertVideo($model, $video, $noConvert);

		// 変換後動画 取得
		$convert = $model->UploadFile->getFile('videos', $model->id, Video::VIDEO_FILE_FIELD);

		// --- サムネイル自動作成
		$this->__generateThumbnail($model, $video, $convert);

		// --- 再生時間を取得
		$videoTimeSec = $this->__getVideoTime($model, $convert);

		// コールバックoff
		$validate = array(
			'validate' => false,
			'callbacks' => false,
		);

		// 動画時間のみ更新
		if (! $model->saveField('video_time', $videoTimeSec, $validate)) {
			throw new InternalErrorException('Failed ' . __METHOD__);
		}

		return true;
	}

/**
 * 動画変換
 *
 * @param Model $model モデル
 * @param array $video Video
 * @param array $noConvert File
 * @return void
 * @throws InternalErrorException
 */
	private function __convertVideo(Model $model, $video, $noConvert) {
		// --- 動画変換
		$noConvertPath = $model->UploadFile->uploadBasePath . $noConvert['UploadFile']['path'] .
						$noConvert['UploadFile']['id'] . DS;
		$realFileName = $noConvert['UploadFile']["real_file_name"];

		// サムネイル名は動画名で末尾jpgにしたものをセット
		$videoName = explode('.', $realFileName)[0];
		$tmpFolder = new TemporaryFolder();
		$convertedFilePath = $tmpFolder->path . DS . $videoName . '.mp4';

		// 動画変換
		// 動画変換実施(元動画 > H.264)  コマンドインジェクション対策
		// 例）ffmpeg -y -i /var/www/html/movies/original/MOV_test_movie.MOV -acodec libmp3lame -ab 128k -ar 44100 -ac 2 -vcodec libx264 -r 30 -b 500k MOV_test_movie.mp4
		// 例）/usr/bin/ffmpeg -y -i '/var/www/app/app/webroot/files/upload_file/real_file_name/1/21/bd14317ad1b299f9074b532116c89da8.MOV' -acodec libmp3lame -ab 128k -ar 44100 -ac 2 -vcodec libx264 -r 30 -b 500k '/var/www/app/app/webroot/files/upload_file/real_file_name/1/21/bd14317ad1b299f9074b532116c89da8.mp4' 2>&1
		// http://tech.ckme.co.jp/ffmpeg.shtml
		// http://www.xucker.jpn.org/product/ffmpeg_commands.html
		$strCmd = Video::FFMPEG_PATH . ' -y -i ' . escapeshellarg($noConvertPath . $realFileName) .
			' ' . Video::FFMPEG_OPTION . ' ' . escapeshellarg($convertedFilePath) . ' 2>&1';
		exec($strCmd, $arr, $ret);

		// 変換エラー時
		if ($ret != 0) {
			$this->log('[' . __METHOD__ . '] ' . __FILE__ . ' (line ' . __LINE__ .
				') ffmpeg H.264 convert error');
			$this->log([$strCmd, $arr, $ret], LOG_DEBUG);

			$model->UploadFile->removeFile($model->id, $noConvert['UploadFile']['id']);	//元動画 削除

			throw new InternalErrorException('Failed ' . __METHOD__);
		}

		//変換動画のファイル保存
		/** @see AttachmentBehavior::attachFile() */
		$model->attachFile($video, Video::VIDEO_FILE_FIELD, $convertedFilePath);
	}

/**
 * 再生時間を取得
 *
 * @param Model $model モデル
 * @param array $convert 動画変換後ファイルデータ
 * @return mixed int on success, false on error
 */
	private function __getVideoTime(Model $model, $convert) {
		// 元動画
		$noConvertPath = $model->UploadFile->uploadBasePath . $convert['UploadFile']['path'] .
						$convert['UploadFile']['id'] . DS;
		$realFileName = $convert['UploadFile']["real_file_name"];
		$videoName = explode('.', $realFileName)[0];

		// 変換後の動画情報を取得 コマンドインジェクション対策
		// ffmpeg -i の $retInfo はファイルがあってもなくても1(失敗)なので、エラー時処理は省く
		$strCmd = Video::FFMPEG_PATH . " -i " . escapeshellarg($noConvertPath . $videoName .
				'.mp4') . " 2>&1";
		exec($strCmd, $arrInfo);

		//動画情報から時間を取得
		$videoTimeSec = 0;
		foreach ($arrInfo as $line) {
			//時間を取得(フォーマット：Duration: 00:00:00.0)
			preg_match("/Duration: [0-9]{2}:[0-9]{2}:[0-9]{2}\.\d+/s", $line, $matches);

			//時間を取得出来た場合
			if (count($matches) > 0) {
				//「:」で文字列分割
				$resultLine = explode(':', $matches[0]);

				//動画の時間を計算
				$videoTimeSec = intval(trim($resultLine[1])) * 3600 +
								intval($resultLine[2]) * 60 + $resultLine[3];
				break;
			}
		}

		return $videoTimeSec;
	}

/**
 * サムネイル自動作成
 *
 * @param Model $model モデル
 * @param array $video Video
 * @param array $convert 動画変換後ファイルデータ
 * @return void
 */
	private function __generateThumbnail(Model $model, $video, $convert) {
		// 編集時サムネイルありの場合、自動作成しない
		$thumbnailSize = Hash::get($video, $model->alias . '.' . Video::THUMBNAIL_FIELD . '.size');
		if (!empty($thumbnailSize)) {
			return;
		}

		// 元動画
		$convertPath = $model->UploadFile->uploadBasePath . $convert['UploadFile']['path'] .
						$convert['UploadFile']['id'] . DS;
		$realFileName = $convert['UploadFile']["real_file_name"];
		$videoName = explode('.', $realFileName)[0];
		$tmpFolder = new TemporaryFolder();
		$convertedFilePath = $tmpFolder->path . DS . $videoName . '.jpg';

		// --- サムネイル自動作成
		// 例) ffmpeg -i /var/www/html/movies/play/20130901_072755.mp4 -ss 1 -vframes 1 -f image2 /var/www/html/movies/play/20130901_072755.jpg
		// サムネイルは変換後のmp4 から生成する。mts からサムネイルを生成した場合、灰色画像になりうまく生成できなかった。ファイル形式によりサムネイル生成に制限がある可能性があるため。
		// コマンドインジェクション対策
		$strCmd = Video::FFMPEG_PATH . ' -i ' .
			escapeshellarg($convertPath . $videoName . '.mp4') .
			' ' . Video::FFMPEG_THUMBNAIL_OPTION . ' ' .
			escapeshellarg($convertedFilePath);
		exec($strCmd, $arrImage, $retImage);

		// 変換エラー時
		if ($retImage != 0) {
			$this->log('[' . __METHOD__ . '] ' . __FILE__ . ' (line ' . __LINE__ .
				') ffmpeg thumbnail generate error', LOG_DEBUG);
			$this->log([$strCmd, $arrImage, $retImage], LOG_DEBUG);
			// return はしない
		} else {
			// サムネイルのファイル保存
			/** @see AttachmentBehavior::attachFile() */
			$model->attachFile($video, Video::THUMBNAIL_FIELD, $convertedFilePath);
		}
	}
}