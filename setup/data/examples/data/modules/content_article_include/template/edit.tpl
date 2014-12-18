<div class="content_article_include">
<link rel="stylesheet" type="text/css" href="{$backendUrl}styles/content_types/cms_abstract_tabbed.css">
    <div class="content_article_include_holder cms_abstract ui-draggable" id="article_include_container_{$id}" style="position: absolute; display: none;">
        <form name="content_article_include_form_{$id}" id="content_article_include_form_{$id}" action="" method="POST">
            <div class="content_article_include_close close" style="cursor: pointer;">
                <img id="article_include_close_{$id}" src="{$backendUrl}/images/but_cancel.gif">
            </div>
            <p class="head" style="cursor: move;">{$articleIncludeSettingsLabel}</p>
            <div class="clearfix"></div>
            <div class="config_container">
                <label>{$articleIncludeChooseCategoryLabel}</label>{$categorySelect}
                <br />
                <label>{$articleIncludeChooseArticleLabel}</label>{$articleSelect}
            </div>
            <div class="toolbar">
                <img src="{$backendUrl}images/but_ok.gif" class="save_settings" id="article_include_save_settings_{$id}" style="cursor: pointer;">
                <img src="{$backendUrl}images/but_cancel.gif" class="cancel_settings" id="article_include_cancel_{$id}" style="cursor: pointer;">
            </div>
        </form>
    </div>
    <div class="popup_opener">
        <img src="{$backendUrl}images/article_include.png" class="cms_abstract_img cms_teaser_img" id="article_include_{$id}" style="cursor: pointer;" />
    </div>
</div>
<script type="text/javascript">
(function($) {
    $('.content_article_include .popup_opener img#article_include_{$id}').click(function() {
        var holder = $(this).parent().parent().find('#article_include_container_{$id}');
        holder.css("top", $(this).offset().top + holder.height() + (holder.height()/2) + 'px');
        holder.css("left", $(this).offset().left + (holder.width()/2) + 'px');
        holder.show();
    });

    $('.content_article_include .content_article_include_close img#article_include_close_{$id}').click(function() {
        $('#article_include_container_{$id}').hide();
    });

    $('.content_article_include .content_article_include_holder .toolbar img#article_include_cancel_{$id}').click(function() {
        $('#article_include_container_{$id}').hide();
    });

    $('.content_article_include #categoryselect_{$id}').on('change', function() {

        var optionVal = $("option:selected", this).val();
        var ajaxUrl = $(this).find('input[name=ajaxUrl]').val();

        $.ajax({
            type: "POST",
            url: '{$ajaxUrl}',
            data: { idcat: optionVal, ajax: 'artsel', name: 'articleselect_ajax_{$id}'},
            success: function(responsedata){
                if($('.content_article_include #articleselect_{$id}').length > 0) {
                    $('.content_article_include #articleselect_{$id}').replaceWith(responsedata);
                } else {
                    $('.content_article_include #articleselect_ajax_{$id}').replaceWith(responsedata);
                }
            }
        });
    });

    $('.content_article_include .content_article_include_holder .toolbar #article_include_save_settings_{$id}').click(function() {
        $('.content_article_include_holder #content_article_include_form_{$id}').submit();
    });

 })(jQuery);

</script>