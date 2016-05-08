var app = angular.module('hexcrafting-app', ['ngAnimate']);

// Adds a style to an html element - used for progress bar
app.directive('parseStyle', function($interpolate) {
    return function(scope, elem) {
        var exp = $interpolate(elem.html()),
            watchFunc = function () { return exp(scope); };

        scope.$watch(watchFunc, function (html) {
            elem.html(html);
        });
    };
});

// Reverses the order of a list of items - used for champion sorting
app.filter('reverse', function() {
  return function(items) {
    return items.slice().reverse();
  };
});

// Main Controller
app.controller('hextroller', ['$http', '$sce', '$timeout', '$filter', function ($http, $sce, $timeout, $filter) {

    /* Initialize Variables */
    hex = this;
    this.isFirstLoad = true;
    this.summoner = '';
    this.isSearchLoading = false;
    this.isError = false;
    this.summonerData = {};
    this.summonerData.summoner = {};
    this.searchErrorMessage = '';
    var mytimeout;
    var timeoutTime = 20;
    this.regions = [{name: 'NA', code: 'na'},
                   {name: 'BR', code: 'br'},
                   {name: 'EUNE', code: 'eune'},
                   {name: 'UEW', code: 'euw'},
                   {name: 'LAN', code: 'lan'},
                   {name: 'LAS', code: 'las'},
                   {name: 'OCE', code: 'oce'},
                   {name: 'TR', code: 'tr'}];
    this.currentRegion = 'na';
    this.positions = ['All','Top','Middle','ADC','Support','Jungle'];
    this.currentPosition = 'All';
    
    /* Public Functions */
    
    // Sets the region for searching
    this.setRegion = function(code) {
        hex.currentRegion = code;
    };
    
    // Checks the region that is currently selected - used for active class
    this.isRegion = function(region) {
        return (region == hex.currentRegion);
    };
    
    // Sets the position for filtering
    this.setPosition = function(position) {
        hex.currentPosition = position;
    };
    
    // Checks the postion that is currently selected - used for active class
    this.isPosition = function(position) {
        return (position == hex.currentPosition);
    };
    
    // Searches (either big button or nav bar button)
    this.search = function () {
        if (hex.summoner != '' && !hex.isSearchLoading) {
            angular.element('#searchModal').modal('hide');
            hex.isFirstLoad = false;
            hex.summonerData.summoner.name = 'Loading...'
            hex.searchErrorMessage = '';
            hex.isSearchLoading = true;
            hex.isError = false;
            
            // Call our own server-side API, show loading icon while we do it
            $http.get('/api/v1/user/' + hex.currentRegion + '/' + hex.summoner).success(function (data) {
                hex.isSearchLoading = false;
                if (data.hasOwnProperty('error')) {
                    hex.summonerData.summoner.name = 'Error...'
                    hex.searchErrorMessage = data.error;
                    hex.isError = true;
                    console.log('Error: ' + data.error);
                    return;
                }
                hex.summonerData = data;
            });
        }
    };

}]);