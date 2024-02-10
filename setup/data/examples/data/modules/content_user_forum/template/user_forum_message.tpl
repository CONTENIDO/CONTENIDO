<p>
    {if !empty($MESSAGES)}
        {foreach from=$MESSAGES item=msgItem}
            {$msgItem|escape}
            <br/>
        {/foreach}
    {/if}
</p>
