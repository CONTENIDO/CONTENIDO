<!-- content_fb_embeddedpost/template/get.tpl -->

<script type="text/javascript">
    $(function () {
        $("#cms_linkeditor_200").click(function () {
            $(".aToolTip").hide();
        });
    });
</script>

{if 0 lt $label|count_characters}
    <label class="con_content_type_label">{$label|escape}</label>
{/if}

{$content}

<!-- /content_fb_embeddedpost/template/get.tpl -->
