$(function () {
    $(".user_forum .like, .user_forum .dislike").click(function (e) {
        var dir = $(this).children("a").attr("href");
        // disable link after click : prevents bug to add more likes while
        // loading page.
        $(this).children("a").attr("href", "javascript:void(0)");
        window.location = dir;

    });

    $(".user_forum .list_table").each(function () {
        if ($(this).css("paddingLeft").replace("px", "") >= "200") {
            $(this).parent().find(".reply, .reply_quote").remove();
        }
    });
});
