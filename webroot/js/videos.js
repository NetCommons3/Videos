/**
 * @fileoverview Videos Javascript
 * @author mutaguchi@opensource-workshop.jp (Mitsuru Mutaguchi)
 * @author exkazuu@willbooster.com (Kazunori Sakamoto)
 */


(function() {
  /**
   * 動画一覧 Javascript
   *
   * @param {string} Controller name
   * @param {function($scope, $http, NC3_URL)} Controller
   */
  NetCommonsApp.controller('Video.index',
      ['$scope', '$http', 'NC3_URL',
        function($scope, $http, NC3_URL) {
          $scope.init = function(frameId, videoIds) {
            showPlayCounts($scope, $http, NC3_URL, frameId, videoIds, false);
          };
        }]
  );


  /**
   * 動画詳細 Javascript
   *
   * @param {string} Controller name
   * @param {function($scope, $http, NC3_URL)} Controller
   */
  NetCommonsApp.controller('VideoView',
      ['$scope', '$http', 'NC3_URL',
        function($scope, $http, NC3_URL) {
          $scope.init = function(frameId, initialValues) {
            showPlayCounts($scope, $http, NC3_URL, frameId, initialValues, true);
          };

          /**
           * 埋め込みコード
           *
           * @return {void}
           */
          $scope.embed = function() {
            // jquery 表示・非表示
            $('div.video-embed').toggle('normal');
            // 表示後埋め込みコード選択
            $('input.video-embed-text').select();
          };
        }]);

  function showPlayCounts($scope, $http, NC3_URL, frameId, initialValues, increment) {
    var videoIds = initialValues && Object.keys(initialValues);
    if (!videoIds || !videoIds.length) return;

    $scope.playCounts = initialValues;
    if (initialValues[videoIds[0]] !== null) return;
    for (var i = 0; i < videoIds.length; i++) {
      initialValues[videoIds[i]] = '-';
    }

    var params = '?frame_id=' + frameId + '&video_ids=' + videoIds.join(',');
    if (increment) {
      params += '&increment=1';
    }
    $http.get(NC3_URL + '/videos/videos/get_play_counts.json' + params)
        .then(
        function(response) {
          var counts = response.data.counts;
          for (var i = 0; i < counts.length; i++) {
            $scope.playCounts[counts[i].Video.id] = counts[i].Video.play_number;
          }
        },
        function() {
        });
  }
})();


/**
 * 関連動画 Javascript
 *
 * @param {string} Controller name
 * @param {function($scope)} Controller
 */
NetCommonsApp.controller('RelatedVideos',
    ['$scope', function($scope) {

      /**
       * もっと見る
       *
       * @return {void}
       */
      $scope.more = function() {
        $('article.related-video:hidden').removeClass('hidden');
        $('button.related-video-more').hide(0);
      };
    }]);


/**
 * 動画編集 Javascript
 *
 * @param {string} Controller name
 * @param {function($scope, NetCommonsWysiwyg)} Controller
 */
NetCommonsApp.controller('VideoEdit',
    ['$scope', 'NetCommonsWysiwyg', function($scope, NetCommonsWysiwyg) {

       /**
        * tinymce
        *
        * @type {object}
        */
       $scope.tinymce = NetCommonsWysiwyg.new();

       /**
        * Initialize
        *
        * @return {void}
        */
       $scope.initialize = function(data) {
         $scope.video = angular.copy(data.video);
       };
    }]);
