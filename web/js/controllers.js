var app = angular.module('DerefApp.controllers', []);

app.controller('derefController', function ($scope, derefService) {
    $scope.derefUrl = '';
    $scope.derefResponse = [];

    $scope.submitForm = function() {

        //Remove and hide the dynamic parts of the page before each submit
        $('div#error-alert').remove();
        $('.results-box').hide();

        // ensure provided URLs are HTTP:// or HTTPS://
        if ($scope.derefUrl) {
            var derefUrlComponents = $scope.derefUrl.split('://');

            if (derefUrlComponents.length == 1) {
                $scope.derefUrl = 'http://' + $scope.derefUrl;
            } else if (derefUrlComponents[0] != 'http' && derefUrlComponents[0] != 'https') {
                $scope.formError('Please specify a valid HTTP or HTTPS URL.');
                return false;
            }
        } else {
            $scope.formError('Please specify a URL.');
            return false;
        }

        derefService.derefUrl($scope.derefUrl).success(function (response) {
            $('.results-box').show();
            $scope.derefResponse = response;
        }).error(function (error, errorCode) {
            $scope.formError(error.error, errorCode);
        });
    };

    $scope.formError = function(message, code) {
        code = code ? code : '';
        $('input#derefUrl').before('<div class="alert alert-danger alert-dismissible fade in" role="alert" id="error-alert">'
            + '<button type="button" class="close" data-dismiss="alert">'
            + '<span aria-hidden="true">×</span>'
            + '<span class="sr-only">Close</span>'
            + '</button>'
            + '<h4>' + code + ' Error!</h4>'
            + '<p>' + message + '</p>'
            + '</div>'
        );
    };
});
