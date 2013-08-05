{foreach from=$values|array_keys item=key}
{$key}            {if $values.$key|is_array}{foreach from=$values.$key item=item}{$item} {/foreach}{else}{$values.$key}{/if}

{/foreach}
