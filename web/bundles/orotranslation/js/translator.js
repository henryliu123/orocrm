/* jshint devel:true */
/* global define */
define(['underscore', 'translator', 'module', 'json'],
function(_, Translator, module) {
    "use strict";

    var dict = {},
        debug = false,
        add = Translator.add,
        get = Translator.get,
        fromJSON = Translator.fromJSON,
        config = module.config();

    Translator.placeHolderPrefix = '{{ ';
    Translator.placeHolderSuffix = ' }}';

    /**
     * Adds a translation to Translator object and stores
     * translation id in protected dictionary
     *
     * @param {string} id
     */
    Translator.add = function (id) {
        dict[id] = 1;
        add.apply(Translator, arguments);
    };

    /**
     * Fetches translation by its id,
     * but before checks if the id was registered in dictionary
     *
     * @param {string} id
     * @returns {string}
     */
    Translator.get = function (id) {
        var string = get.apply(Translator, arguments);
        var hasTranslation = checkTranslation(id);

        if (!config.debugTranslator) {
            return string;
        }

        if (hasTranslation) {
            if (string.indexOf(']JS') == -1) {
                return '[' + string + ']JS';
            } else {
                return string;
            }
        } else {
            if (string.indexOf('---!!!JS') == -1) {
                return '!!!---' + string + '---!!!JS';
            } else {
                return string;
            }
        }
    };

    /**
     * Parses JSON data in store translations inside,
     * also turns on debug mode if in data was such directive
     *
     * @param {Object} data
     * @returns {Object} Translator
     */
    Translator.fromJSON = function (data) {
        if (typeof data === "string") {
            data = JSON.parse(data);
        }
        debug = data.debug || false;
        return fromJSON.call(Translator, data);
    };

    /**
     * Checks if translation for passed id exist, if it's debug mode
     * and there's no translation - output error message in console
     *
     * @param {string} id
     */
    function checkTranslation(id) {
        if (!debug) {
            return true;
        }
        var domains = Translator.defaultDomains,
            checker = function (domain) {
                return dict.hasOwnProperty(domain ? domain + ':' + id : id);
            };
        domains = _.union([undefined], _.isArray(domains) ? domains : [domains]);
        if (!_.some(domains, checker)) {
            console.error('Untranslated: %s', id);
            return false;
        }
        return true;
    }

    _.mixin({
        /**
         * Shortcut for Translator.get() method call,
         * Due to it's underscore mixin, it can be used inside templates
         * @returns {string}
         */
        __: _.bind(Translator.get, Translator)
    });

    /**
     * Shortcut for Translator.get() method call
     *
     * @export orotranslation/js/translator
     * @returns {string}
     */
    return _.__;
});
