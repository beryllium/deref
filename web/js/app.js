angular.module('DerefApp', [
    'DerefApp.controllers',
    'DerefApp.services'
]);

function htmlentities(text) {
    return $('<div />').text(text).html()
}