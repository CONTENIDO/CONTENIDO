<!-- form_assistant/templates/template.ajax_option_row.tpl -->

<div class="option-outer">

    <div class="option-inner-label">
        <label for="option_label_{$i}">{$trans.label} #{$i}</label>
        <input type="text" name="option_labels[]" id="option_label_{$i}" class="input_text" value="{$option.label|escape}" />
    </div>

    <div class="option-inner-value">
        <label for="option_value_{$i}">{$trans.value} #{$i}</label>
        <input type="text" name="option_values[]" id="option_value_{$i}" class="input_text" value="{$option.value|escape}" />
    </div>

    <div class="del-option">
        <a href="#"><img src="images/delete.gif" /></a>
    </div>

</div>

<!-- /form_assistant/templates/template.ajax_option_row.tpl -->
