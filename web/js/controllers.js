var app = angular.module('DerefApp.controllers', []);

app.controller('derefController', function ($scope, derefService) {
    $scope.derefUrl = '';
    $scope.derefResponse = [];

    $scope.submitForm = function() {

        //Remove and hide the dynamic parts of the page before each submit
        $('div#error-alert').remove();
        $('.results-box').hide();

        // If we have a URL to check, send it to the checking route
        // Otherwise, mention that the URL field is required
        // (this is useful on Safari and IE9, which don't support HTML5 form input validation)
        if ($scope.derefUrl) {
            derefService.derefUrl($scope.derefUrl).success(function (response) {
                $('.results-box').show();
                $scope.derefResponse = response;
            }).error(function (error, errorCode) {
                $('input#derefUrl').before('<div class="alert alert-danger alert-dismissible fade in" role="alert" id="error-alert">'
                    + '<button type="button" class="close" data-dismiss="alert">'
                    + '<span aria-hidden="true">×</span>'
                    + '<span class="sr-only">Close</span>'
                    + '</button>'
                    + '<h4>' + errorCode + ' Error!</h4>'
                    + '<p><small>' + error.error + '</small></p>'
                    + '</div>'
                );
            });
        } else {
            $('input#derefUrl').before('<div class="alert alert-danger alert-dismissible fade in" role="alert" id="error-alert">'
                + '<button type="button" class="close" data-dismiss="alert">'
                + '<span aria-hidden="true">×</span>'
                + '<span class="sr-only">Close</span>'
                + '</button>'
                + '<h4>Error!</h4>'
                + '<p>Please specify a URL.</p>'
                + '</div>'
            );
        }
    };
});
