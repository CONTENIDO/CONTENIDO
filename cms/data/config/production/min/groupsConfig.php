<?php

/**
 * Groups configuration for default Minify implementation
 * @package Minify
 */

/**
 * You may wish to use the Minify URI Builder app to suggest
 * changes. https://yourdomain/min/builder/
 *
 * See https://github.com/mrclay/minify/blob/master/docs/CustomServer.wiki.md for other ideas
 **/

/**
 * Example groups for CONTENIDO client frontend to join some files together.
 * NOTE:
 * The beginning '//cms' of the entries points to the 'cms' folder within your document root.
 */
return [
    'css' => [
        '//cms/css/reset.css',
        '//cms/css/main.css',
        '//cms/css/media.css',
        '//cms/css/contenido_backend.css',
    ],
    'js_libs_jquery' => [
        '//cms/js/jquery-1.8.2.min.js',
        '//cms/js/jquery-ui-1.9.1.custom.min.js',
        '//cms/js/jquery.touchSwipe.min.js',
        '//cms/js/jquery.validate.js',
    ],
    'js_libs' => [
            '//cms/js/velocity.min.js',
            '//cms/js/velocity.ui.min.js',
            '//cms/js/js/respond.min.js',
    ],
    'js' => [
        '//cms/js/main.js',
        '//cms/js/media.js',
    ],
];
