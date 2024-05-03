/* eslint-disable */

const app = angular.module('translationApp', ['ngTable']);

/**
 * Shared object to display user messages.
 */
app.factory('sharedMessage', () => ({
    css: '',
    icon: '',
    content: '',

    set(css, icon, content) {
        this.css = css;
        this.icon = icon;
        this.content = content;
    },

    reset() {
        this.set('', '', '');
    },
}));

/**
 * Api manager service.
 */
app.factory('translationApiManager', ['$http', '$httpParamSerializer', function ($http, $httpParamSerializer) {
    return {
        token: null,

        setToken(token) {
            this.token = token;
        },

        getPage(params, tableData) {
            const parameters = {};

            if (Object.keys(params.sorting()).length) {
                const keys = Object.keys(params.sorting());
                parameters.sidx = keys[0];
                parameters.sord = params.sorting()[keys[0]];

                if (!angular.equals(tableData.currentSort, params.sorting())) {
                    params.page(1);
                    tableData.currentSort = params.sorting();
                }
            }

            if (Object.keys(params.filter()).length) {
                parameters._search = true;
                for (const key in params.filter()) {
                    parameters[key] = params.filter()[key];
                }

                if (!angular.equals(tableData.currentFilter, params.filter())) {
                    params.page(1);
                    tableData.currentFilter = params.filter();
                }
            }

            parameters.page = params.page();
            parameters.rows = params.count();

            const queryString = window.location.search;
            const urlParams = new URLSearchParams(queryString);
            parameters['channelCode'] = urlParams.get('channelCode');

            const url = (this.token != null) ? translationCfg.url.listByToken.replace('-token-', this.token) : translationCfg.url.list;

            return $http.get(url, { params: parameters });
        },

        invalidateCache() {
            return $http.get(translationCfg.url.invalidateCache, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                params: this.initializeParametersWithCsrf(),
            });
        },

        updateTranslation(translation) {
            const url = translationCfg.url.update.replace('-id-', translation._id);

            const parameters = this.initializeParametersWithCsrf();
            for (const name in translation) {
                parameters[name] = translation[name];
            }

            const queryString = window.location.search;
            const urlParams = new URLSearchParams(queryString);
            parameters['channelCode'] = urlParams.get('channelCode');

            // force content type to make SF create a Request with the PUT parameters
            return $http({
                url,
                data: $httpParamSerializer(parameters),
                method: 'PUT',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            });
        },

        deleteTranslationLocale(translation, locale) {
            const url = translationCfg.url.deleteLocale
                .replace('-id-', translation._id)
                .replace('-locale-', locale);

            return $http.delete(url, {
                params: this.initializeParametersWithCsrf(),
            });
        },

        deleteChannelTranslation(translation, locale) {
            const url = translationCfg.url.deleteChannelTranslation
                .replace('-id-', translation._id)
                .replace('-locale-', locale.substring(1)); // Remove "_" from locale

            return $http.delete(url, {
                params: this.initializeParametersWithCsrf(),
            });
        },

        deleteTranslation(translation) {
            return $http.delete(translationCfg.url.delete.replace('-id-', translation._id), {
                params: this.initializeParametersWithCsrf(),
            });
        },

        initializeParametersWithCsrf(parameters) {
            var parameters = {};

            if (translationCfg.csrfToken) {
                parameters._token = translationCfg.csrfToken;
            }

            return parameters;
        },
    };
}]);

/**
 * ngTable column definition and parameters builder service.
 */
app.factory('tableParamsManager', ['ngTableParams', 'translationApiManager', '$location', function (ngTableParams, translationApiManager, $location) {
    return {
        columns: [],
        tableParams: null,
        defaultOptions: angular.extend({
            page: 1, count: 20, filter: {}, sort: { _id: 'asc' },
        }, $location.search()),

        build(locales, labels) {
            this.columns = [
                {
                    title: 'ID', index: '_id', edit: false, delete: false, filter: false, sortable: true, visible: true,
                },
                {
                    title: labels.domain,
                    index: '_domain',
                    edit: false,
                    delete: false,
                    filter: { _domain: 'text' },
                    sortable: true,
                    visible: true,
                },
                {
                    title: labels.key,
                    index: '_key',
                    edit: false,
                    delete: true,
                    filter: { _key: 'text' },
                    sortable: true,
                    visible: true,
                },
            ];

            for (const key in locales) {
                const columnDef = {
                    title: locales[key].toUpperCase(),
                    index: locales[key],
                    edit: true,
                    delete: true,
                    filter: {},
                    sortable: false,
                    visible: true,
                    channelTranslation: false
                };
                columnDef.filter[locales[key]] = 'text';

                this.columns.push(columnDef);
            }

            for (const key in locales) {
                const columnDef = {
                    title: channel.name + ' - ' + locales[key].toUpperCase(),
                    index: '_' + locales[key],
                    edit: true,
                    delete: true,
                    filter: {},
                    sortable: false,
                    visible: true,
                    channelTranslation: true
                };
                columnDef.filter[locales[key]] = 'text';

                this.columns.push(columnDef);
            }

            // grid data
            const tableData = {
                total: 0,
                currentSort: {},
                currentFilter: {},
                getData($defer, params) {
                    $location.search(params.url());

                    translationApiManager
                        .getPage(params, this)
                        .success((responseData) => {
                            params.total(responseData.total);
                            $defer.resolve(responseData.translations);
                        });
                },
            };

            this.tableParams = new ngTableParams(this.defaultOptions, tableData);
        },

        reloadTableData() {
            this.tableParams.reload();
        },

        getColumnsDefinition() {
            return this.columns;
        },

        getTableParams() {
            return this.tableParams;
        },
    };
}]);

/**
 * Translation grid controller.
 */
app.controller('TranslationController', [
    '$scope', '$location', '$anchorScroll', 'sharedMessage', 'tableParamsManager', 'translationApiManager',
    function ($scope, $location, $anchorScroll, sharedMessage, tableParamsManager, translationApiManager) {
        $scope.locales = translationCfg.locales;
        $scope.editType = translationCfg.inputType;
        $scope.autoCacheClean = translationCfg.autoCacheClean;
        $scope.labels = translationCfg.label;
        $scope.hideColumnsSelector = false;
        $scope.areAllColumnsSelected = true;
        $scope.profilerTokens = translationCfg.profilerTokens;
        $scope.sharedMsg = sharedMessage;

        tableParamsManager.build($scope.locales, $scope.labels);

        $scope.columns = tableParamsManager.getColumnsDefinition();
        $scope.tableParams = tableParamsManager.getTableParams();

        // override default changePage function to scroll to top on change page
        $scope.tableParams.changePage = function (pageNumber) {
            $scope.tableParams.page(pageNumber);
            $location.hash('translation-grid');
            $anchorScroll();
        };

        // trigger the grid sorting
        $scope.sortGrid = function (column) {
            if (column.sortable) {
                $scope.tableParams.sorting(column.index, $scope.tableParams.isSortBy(column.index, 'asc') ? 'desc' : 'asc');
            }
        };

        // go to the top of the grid on page change
        $scope.changePage = function (pageNumber) {
            $scope.tableParams.page(pageNumber);
            $location.hash('translation-grid');
            $anchorScroll();
        };

        // toggle show/hide column with a similar name (if "en" is clicked all "en_XX" columns will be toggled too)
        $scope.toggleSimilar = function (currentCol) {
            if (translationCfg.toggleSimilar) {
                angular.forEach($scope.columns, (column) => {
                    if (column.index != currentCol.index && column.index.indexOf(`${currentCol.index}_`) == 0) {
                        column.visible = !currentCol.visible; // use the negation because it seems the model value has not been refreshed yet.
                    }
                });
            }
        };

        // invalidate translation cache
        $scope.invalidateCache = function () {
            translationApiManager
                .invalidateCache()
                .success((responseData) => {
                    sharedMessage.set('success', 'ok-circle', responseData.message);
                })
                .error(() => {
                    sharedMessage.set('danger', 'remove-circle', 'Error');
                });
        };

        // toggle all columns
        $scope.toggleAllColumns = function () {
            $scope.areAllColumnsSelected = !$scope.areAllColumnsSelected;
            angular.forEach($scope.columns, (column) => {
                column.visible = $scope.areAllColumnsSelected;
            });
        };
    },
]);

/**
 * Translations source controller.
 */
app.controller('DataSourceController', [
    '$scope', 'tableParamsManager', 'translationApiManager',
    function ($scope, tableParamsManager, translationApiManager) {
        $scope.selectedToken = null;
        $scope.defaultSourceClass = 'btn-info';
        $scope.tokenSourceClass = 'btn-default';
        $scope.showProfiles = false;

        // use the given profile token as translations source
        $scope.changeToken = function (selectedToken) {
            translationApiManager.setToken(selectedToken);

            if (selectedToken != '') {
                tableParamsManager.reloadTableData();
            }
        };

        $scope.resetSource = function () {
            $scope.selectedToken = null;
            $scope.defaultSourceClass = 'btn-info';
            $scope.tokenSourceClass = 'btn-default';
            $scope.showProfiles = false;

            translationApiManager.setToken($scope.selectedToken);
            tableParamsManager.reloadTableData();
        };

        $scope.useTokenAsSource = function () {
            $scope.defaultSourceClass = 'btn-default';
            $scope.tokenSourceClass = 'btn-info';
            $scope.showProfiles = true;

            if ($scope.profilerTokens.length) {
                $scope.selectedToken = $scope.profilerTokens[0].token;
                translationApiManager.setToken($scope.selectedToken);
                tableParamsManager.reloadTableData();
            } else {
                $scope.selectedToken = '';
            }
        };
    },
]);

/**
 * Directive to switch table row in edit mode.
 */
app.directive('editableRow', [
    'translationApiManager', 'tableParamsManager', 'sharedMessage',
    function (translationApiManager, tableParamsManager, sharedMessage) {
        return {
            restrict: 'A',
            scope: {
                translation: '=translation',
                columns: '=columns',
                editType: '=editType',
            },
            template: $('#editable-row-template').html(),
            link($scope, element, attrs) {
                $scope.mode = null;

                $scope.enableMode = function (mode) {
                    $scope.mode = mode;
                    sharedMessage.reset();
                };

                $scope.disableMode = function () {
                    $scope.mode = null;
                    sharedMessage.reset();
                };

                $scope.save = function (event, source) {
                    if ((source == 'input' || source == 'textarea') && event.which == 27) { // escape key
                        $scope.mode = null;
                    } else if (source == 'btn-save' || (source == 'input' && event.which == 13)) { // click btn OR return key
                        translationApiManager
                            .updateTranslation($scope.translation)
                            .success((data) => {
                                $scope.mode = null;
                                $scope.translation = data;
                                sharedMessage.set('success', 'ok-circle', translationCfg.label.updateSuccess.replace('%id%', data._key));
                            }).error(() => {
                                sharedMessage.set('danger', 'remove-circle', translationCfg.label.updateFail.replace('%id%', $scope.translation._key));
                            });
                    }
                };

                $scope.delete = function (column) {
                    if (!window.confirm('Confirm ?')) {
                        return;
                    }

                    if (column.index === '_key') {
                        translationApiManager
                            .deleteTranslation($scope.translation)
                            .success((data) => {
                                sharedMessage.set('success', 'ok-circle', translationCfg.label.deleteSuccess.replace('%id%', data._key));
                                $scope.mode = null;
                                tableParamsManager.reloadTableData();
                            }).error(() => {
                                sharedMessage.set('danger', 'remove-circle', translationCfg.label.deleteFail.replace('%id%', $scope.translation._key));
                            });
                    } else if (column.channelTranslation) {
                        translationApiManager
                            .deleteChannelTranslation($scope.translation, column.index)
                            .success((data) => {
                                sharedMessage.set('success', 'ok-circle', translationCfg.label.deleteSuccess.replace('%id%', data._key));
                                $scope.translation[column.index] = '';
                            }).error(() => {
                            sharedMessage.set('danger', 'remove-circle', translationCfg.label.deleteFail.replace('%id%', $scope.translation._key));
                        });
                    } else {
                        translationApiManager
                            .deleteTranslationLocale($scope.translation, column.index)
                            .success((data) => {
                                sharedMessage.set('success', 'ok-circle', translationCfg.label.deleteSuccess.replace('%id%', data._key));
                                $scope.translation[column.index] = '';
                                $scope.translation['_' + column.index] = '';
                            }).error(() => {
                            sharedMessage.set('danger', 'remove-circle', translationCfg.label.deleteFail.replace('%id%', $scope.translation._key));
                        });
                    }
                };
            },
        };
    },
]);
