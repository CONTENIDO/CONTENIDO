{foreach from=$values|array_keys item=key}
{$key}            {if $value.$key|is_array}{foreach from=$value.$key item=item}{$item} {/foreach}{else}{$value.$key}{/if}
{/foreach}
