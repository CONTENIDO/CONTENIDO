/* global Con: true, jQuery: true */

/**
 * JavaScript functions for the PIM plugin.
 *
 * @module     plugin
 * @submodule  pim
 * @requires   jQuery, Con
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

(function(Con, $) {

    var NAME = 'plugin-pim',
        DIRECTION_UP = 1,
        DIRECTION_DOWN = 2;

    /**
     * @class Pim
     * @constructor
     * @extends Con.Plugin
     * @param {String}  page Page name (html body id)
     * @param {Object}  options
     * <pre>
     * options.installedPlugins  (Number)  Number of installed plugins
     * options.textError  (String)  Error title
     * options.textPluginAlreadyAtBottom  (String)  Plugin already exists message
     * options.textPluginAlreadyAtTop  (String)  Plugin already at to message
     * options.textUninstallPluginConfirmation  (String)  Uninstall plugin confirmation message
     * options.textRemovePluginConfirmation  (String)  Remove plugin confirmation message
     * options.textUninstallSqlSelectedInfo  (String)  Selected uninstall SQL info
     * </pre>
     */
    var Pim = function(page, options) {

        /**
         * Page name
         * @property {String} _page
         * @private
         */
        var _page = page,

            /**
             * Page id
             * @property {String}
             * @private
             */
            _pageId = '#' + _page,

            /**
             * Translation strings
             * @property {Object} _options
             * @private
             */
            _options = $.extend({
                installedPlugins: 0,
                textError: 'Error',
                textPluginAlreadyAtBottom: 'This plugin is already at the bottom!',
                textPluginAlreadyAtTop: 'This plugin is already at the top!',
                textUninstallPluginConfirmation: "Are you sure to uninstall this plugin? Files are not deleted in filesystem."
            }, options),
            _ajax = false,
            $_sortButtonsUp,
            $_sortButtonsDown;

        initialize();
        registerEventHandler();

        function initialize() {
            updateSortButtons();
        }

        function registerEventHandler() {
            $(_pageId + ' [data-action]').live('click', function() {
                var $element = $(this),
                    action = $element.data('action');

                if (action === 'toggle_not_installed') {
                    actionToggleAll($element, 'not_installed');
                } else if (action === 'toggle_installed') {
                    actionToggleAll($element, 'installed');
                } else if ($.inArray(action, ['display_all', 'display_active', 'display_inactive']) > -1) {
                    actionFilterInstalled($element, action);
                } else if (action === 'toggle_element') {
                    actionToggleElement($element.data('element-id'));
                } else if (action === 'toggle_table_element') {
                    var $mainTable = $element.closest('.plugin_overview'),
                        tableId = $element.data('element-id')
                        ? $element.data('element-id')
                        : 'plugin_info_' + $mainTable.data('plugin-id');
                    actionToggleTableElement($mainTable, tableId);
                } else if (action === 'sort_plugin_up') {
                    actionUpdatePluginOrder($element, DIRECTION_UP);
                } else if (action === 'sort_plugin_down') {
                    actionUpdatePluginOrder($element, DIRECTION_DOWN);
                } else if (action === 'uninstall_plugin') {
                    actionUninstallPlugin($element);
                } else if (action === 'remove_plugin') {
                    actionRemovePlugin($element);
                }
            });
        }

        function actionUpdatePluginOrder($element, direction) {
            if (_ajax) {
                _ajax.abort();
            }

            var pluginId = parseInt($element.closest('.plugin_overview').data('plugin-id'), 10),
                pluginRow = $(_pageId + ' #plugin_installed_row_' + pluginId),
                pluginTable = pluginRow.parent().parent(),
                oldOrder = parseInt(pluginRow.data('plugin-execorder')),
                newOrder;

            if (direction === DIRECTION_DOWN) {
                newOrder = oldOrder + 1;
                if (newOrder > _options.installedPlugins) {
                    Con.showNotification(_options.textError, _options.textPluginAlreadyAtBottom);
                    return;
                }
            } else {
                newOrder = oldOrder - 1;
                if (newOrder === 0) {
                    Con.showNotification(_options.textError, _options.textPluginAlreadyAtTop);
                    return;
                }
            }
            // console.log({pluginId: pluginId, pluginRow: pluginRow, pluginTable: pluginTable, oldOrder: oldOrder, newOrder: newOrder});

            _ajax = $.ajax({
                url: 'ajaxmain.php',
                type: 'POST',
                data: {
                    ajax: 'updatepluginorder',
                    neworder: newOrder,
                    idplugin: pluginId
                },
                success: function(data, status, jqxhr) {
                    if (data.length === 0) {
                        window.console.warn(NAME + ': Update plugin order fail!');
                        return false;
                    } else if (data === 'ok') {
                        pluginRow.data('plugin-execorder', newOrder);

                        if (direction === DIRECTION_DOWN) {
                            pluginTable.next().children().children('tr').first().data('plugin-execorder', oldOrder);
                            pluginTable.next().after(pluginTable);
                        } else {
                            pluginTable.prev().children().children('tr').first().data('plugin-execorder', oldOrder);
                            pluginTable.prev().before(pluginTable);
                        }

                        updateSortButtons();
                    }
                }
            });
        }

        function actionToggleTableElement($mainTable, tableId) {
            var $table = $mainTable.find('#' + tableId);
            if ($table.hasClass('display')) {
                collapseTableBody(tableId);
            } else {
                expandTableBody(tableId);
            }
        }

        function actionToggleAll($element, pluginGroup) {
            var status = $element.data('status');
            $(_pageId + ' .' + pluginGroup + ' .plugin_overview').each(function(pos, element) {
                if (status === 1) {
                    collapseTableBody('plugin_info_' + $(element).data('plugin-id'));
                } else {
                    expandTableBody('plugin_info_' + $(element).data('plugin-id'));
                }
            });
            $element.data('status', status === 1 ? 0 : 1);
        }

        function actionFilterInstalled($element, action) {
            var $table = $(_pageId + ' .installed');
            $element.closest('.title_action_bar').find('a').removeClass('active');
            $element.addClass('active');
            if (action === 'display_all') {
                $table.removeClass('filtered')
                    .find('.plugin_overview').show();
            } else if (action === 'display_active') {
                $table.removeClass('filtered');
                $table.find('.plugin_overview[data-plugin-active-status="1"]').show();
                $table.find('.plugin_overview[data-plugin-active-status="0"]').hide();
            } else if (action === 'display_inactive') {
                $table.addClass('filtered');
                $table.find('.plugin_overview[data-plugin-active-status="0"]').show();
                $table.find('.plugin_overview[data-plugin-active-status="1"]').hide();
            }
        }

        function actionToggleElement(elementId) {
            $(_pageId + ' #' + elementId).toggleClass('display');
        }

        function actionUninstallPlugin($element) {
            var href = $element.data('href'),
                $uninstallSqlChk = $element.closest('td').find('.uninstall_sql'),
                confirmationMessage, newHref;

            confirmationMessage = _options.textUninstallPluginConfirmation

            if ($uninstallSqlChk.prop('checked')) {
                newHref = href.replace('uninstallsql=0', 'uninstallsql=1');
                confirmationMessage += '<br><br>' + _options.textUninstallSqlSelectedInfo;
            } else {
                newHref = href.replace('uninstallsql=1', 'uninstallsql=0');
            }

            Con.showConfirmation(confirmationMessage, function() {
                window.location.href = newHref;
            });
        }

        function actionRemovePlugin($element) {
            Con.showConfirmation(_options.textRemovePluginConfirmation, function() {
                window.location.href = $element.data('href');
            });
        }

        function updateSortButtons() {
            $_sortButtonsUp = $(_pageId + ' .sort_plugin_up');
            $_sortButtonsDown = $(_pageId + ' .sort_plugin_down');
            $_sortButtonsUp.css('visibility', 'visible');
            $_sortButtonsUp.first().css('visibility', 'hidden');
            $_sortButtonsDown.css('visibility', 'visible');
            $_sortButtonsDown.last().css('visibility', 'hidden');
        }

        function expandTableBody(tableId) {
            var $table = $(_pageId + ' #' + tableId),
                $img = $(_pageId + ' #' + tableId + '_img');
            if (!$table.hasClass('display')) {
                $table.addClass('display');
                $img.attr('src', 'images/close_all.gif');
            }
        }

        function collapseTableBody(tableId) {
            var $table = $(_pageId + ' #' + tableId),
                $img = $(_pageId + ' #' + tableId + '_img');
            if ($table.hasClass('display')) {
                $table.removeClass('display');
                $img.attr('src', 'images/open_all.gif');
            }
        }

        return {
            // Return empty public interface, there is nothing for public
        };

    }

    // Assign to Con.Plugin namespace
    Con.Plugin.Pim = Pim;

})(Con, Con.$);
