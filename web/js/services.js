angular.module('DerefApp.services', []).
    factory('derefService', function($http) {

        var derefAPI = {};

        derefAPI.derefUrl = function($url) {
            return $http({
                method: 'POST',
                url: '/deref',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                data: 'url=' + encodeURI($url)
            });
        };

        return derefAPI;
    });