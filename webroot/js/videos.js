/**
 * @fileoverview Videos Javascript
 * @author mutaguchi@opensource-workshop.jp (Mitsuru Mutaguchi)
 */


/**
 * 動画詳細 Javascript
 *
 * @param {string} Controller name
 * @param {function($scope)} Controller
 */
NetCommonsApp.controller('VideoView',
    ['$scope', function($scope) {

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
