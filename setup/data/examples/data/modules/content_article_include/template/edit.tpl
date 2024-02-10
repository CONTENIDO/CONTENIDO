<div class="content_article_include">
    <link rel="stylesheet" type="text/css"
          href="{$backendUrl|escape}styles/content_types/cms_abstract_tabbed.css">
    <div class="content_article_include_holder cms_abstract ui-draggable"
         id="article_include_container_{$id|escape}" style="position: absolute; display: none;">
        <form name="content_article_include_form_{$id|escape}"
              id="content_article_include_form_{$id|escape}" action="" method="POST">
            <div class="content_article_include_close close" style="cursor: pointer;">
                <img id="article_include_close_{$id|escape}" alt=""
                     src="{$backendUrl|escape}/images/but_cancel.gif">
            </div>
            <p class="head" style="cursor: move;">{$articleIncludeSettingsLabel|escape}</p>
            <div class="clearfix"></div>
            <div class="config_container">
                <label>{$articleIncludeChooseCategoryLabel|escape}</label>{$categorySelect}
                <br/>
                <label>{$articleIncludeChooseArticleLabel|escape}</label>{$articleSelect}
            </div>
            <div class="toolbar">
                <img src="{$backendUrl|escape}images/but_ok.gif"
                     class="con_img_button save_settings" alt=""
                     id="article_include_save_settings_{$id|escape}" style="cursor: pointer;">
                <img src="{$backendUrl|escape}images/but_cancel.gif"
                     class="con_img_button cancel_settings" alt=""
                     id="article_include_cancel_{$id|escape}" style="cursor: pointer;">
            </div>
        </form>
    </div>
    <div class="popup_opener">
        <img src="{$backendUrl|escape}images/article_include.png" alt=""
             class="con_img_button cms_abstract_img cms_teaser_img"
             id="article_include_{$id|escape}" style="cursor: pointer;"/>
    </div>
</div>
<script type="text/javascript">
    (function ($) {
        $('.content_article_include .popup_opener img#article_include_{$id|escape}').click(function () {
            var holder = $(this).parent().parent().find('#article_include_container_{$id|escape}');
            holder.css("top", $(this).offset().top + holder.height() + (holder.height() / 2) + 'px');
            holder.css("left", $(this).offset().left + (holder.width() / 2) + 'px');
            holder.show();
        });

        $(".content_article_include .content_article_include_close img#article_include_close_{$id|escape:'javascript'}").click(function () {
            $('#article_include_container_{$id|escape}').hide();
        });

        $(".content_article_include .content_article_include_holder .toolbar img#article_include_cancel_{$id|escape:'javascript'}").click(function () {
            $("#article_include_container_{$id|escape:'javascript'}").hide();
        });

        $(".content_article_include #categoryselect_{$id|escape:'javascript'}").on('change', function () {

            var optionVal = $("option:selected", this).val();
            var ajaxUrl = $(this).find('input[name=ajaxUrl]').val();

            $.ajax({
                type: "POST",
                url: '{$ajaxUrl|escape}',
                data: {
                    idcat: optionVal,
                    ajax: 'artsel',
                    name: "articleselect_ajax_{$id|escape:'javascript'}"
                },
                success: function (responsedata) {
                    if ($(".content_article_include #articleselect_{$id|escape:'javascript'}").length > 0) {
                        $(".content_article_include #articleselect_{$id|escape:'javascript'}").replaceWith(responsedata);
                    } else {
                        $(".content_article_include #articleselect_ajax_{$id|escape:'javascript'}").replaceWith(responsedata);
                    }
                }
            });
        });

        $(".content_article_include .content_article_include_holder .toolbar #article_include_save_settings_{$id|escape:'javascript'}").click(function () {
            $(".content_article_include_holder #content_article_include_form_{$id|escape:'javascript'}").submit();
        });

    })(jQuery);

</script>