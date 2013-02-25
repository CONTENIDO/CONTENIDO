<!-- content_unsolicited_application_form/template/post.tpl -->

{if 0 lt $reply.headline|trim|strlen}
    <h1>{$reply.headline}</h1>
{/if}

{if 0 lt $reply.text|trim|strlen}
    <p>{$reply.text}</p>
{/if}

<!-- /content_unsolicited_application_form/template/post.tpl -->
