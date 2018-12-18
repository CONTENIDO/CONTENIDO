function deleteShortUrl(idshorturl) {
    $('form[name="edit_form"] input[name="action"]').val('url_shortener_delete');
    $('form[name="edit_form"] input[name="idshorturl"]').val(idshorturl);
    $('form[name="edit_form"]').submit();
    return false;
}

function editShortUrl(idshorturl, shorturl) {
    var rowSelector = '#shorturl-' + idshorturl;
    $(rowSelector + ' .edit-link').hide();
    $(rowSelector + ' .save-link').show();
    var input = $('<input>');
    input.attr('class', 'newshorturl');
    input.val(shorturl);
    $(rowSelector + ' .shorturl').replaceWith(input);
}

function saveShortUrl(idshorturl) {
    $('form[name="edit_form"] input[name="action"]').val('url_shortener_edit');
    $('form[name="edit_form"] input[name="idshorturl"]').val(idshorturl);
    var newshorturl = $('#shorturl-' + idshorturl + ' .newshorturl').val();
    $('form[name="edit_form"] input[name="newshorturl"]').val(newshorturl);
    $('form[name="edit_form"]').submit();
    return false;
}