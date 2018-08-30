define([
    './columnLogin',
    'jquery',
], function (Column, $) {
    'use strict';
    return Column.extend({
        defaults: {
            bodyTmpl: 'Webkul_Marketplace/grid/cells/htmlLogin',
            fieldClass: {
                'v-login': true
            }
        },
        getFieldHandler: function (row) {
            return false;
        }
    });
});
