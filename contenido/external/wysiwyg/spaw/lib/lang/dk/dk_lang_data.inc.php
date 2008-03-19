<?php 
// ================================================
// SPAW PHP WYSIWYG editor control
// ================================================
// Danish language file
// ================================================
// Developed: Alan Mendelevich, alan@solmetra.lt
// Translated: Morten Skyt Eriksen xgd_bitnissen@hotmail.com
// Copyright: Solmetra (c)2003 All rights reserved.
// ------------------------------------------------
//                                www.solmetra.com
// ================================================
// v.1.0, 2003-05-20
// ================================================

// charset to be used in dialogs
$spaw_lang_charset = 'iso-8859-1';

// language text data array
// first dimension - block, second - exact phrase
// alternative text for toolbar buttons and title for dropdowns - 'title'

$spaw_lang_data = array(
  'cut' => array(
    'title' => 'Klip'
  ),
  'copy' => array(
    'title' => 'Kopier'
  ),
  'paste' => array(
    'title' => 'S�t ind'
  ),
  'undo' => array(
    'title' => 'Fortryd'
  ),
  'redo' => array(
    'title' => 'Gentag'
  ),
  'hyperlink' => array(
    'title' => 'Hyperlink'
  ),
  'image_insert' => array(
    'title' => 'Inds�t billede',
    'select' => 'V�lg',
    'cancel' => 'Annuller',
    'library' => 'Bibliotek',
    'preview' => 'Eksempel',
    'images' => 'Billeder',
    'upload' => 'Upload billede',
    'upload_button' => 'Upload',
    'error' => 'Fejl',
    'error_no_image' => 'V�lg venligst et billede',
    'error_uploading' => 'En fejl skete under upload. Pr�v venligst igen senere',
    'error_wrong_type' => 'Forkert billede type.',
    'error_no_dir' => 'Bibliotek eksisterer ikke fysisk',
  ),
  'image_prop' => array(
    'title' => 'Billede indstillinger',
    'ok' => '   OK   ',
    'cancel' => 'Annuller',
    'source' => 'Kilde',
    'alt' => 'Alternativ tekst',
    'align' => 'Juster',
    'left' => 'venstre',
    'right' => 'h�jre',
    'top' => 'top',
    'middle' => 'midten',
    'bottom' => 'bunden',
    'absmiddle' => 'absolut midte',
    'texttop' => 'teksttop',
    'baseline' => 'bundlinie',
    'width' => 'Bredde',
    'height' => 'H�jde',
    'border' => 'Kant',
    'hspace' => 'Hor. mellemrum',
    'vspace' => 'Vert. mellemrum',
    'error' => 'Fejl',
    'error_width_nan' => 'Bredden er ikke et nummer',
    'error_height_nan' => 'H�jden er ikke et nummer',
    'error_border_nan' => 'Kanten er ikke et nummer',
    'error_hspace_nan' => 'Horisontalt mellemrum er ikke et nummer',
    'error_vspace_nan' => 'Vertikalt mellemrum er ikke et nummer',
  ),
  'hr' => array(
    'title' => 'Horisontal bar'
  ),
  'table_create' => array(
    'title' => 'Opret tabel'
  ),
  'table_prop' => array(
    'title' => 'Tabel indstillinger',
    'ok' => '   OK   ',
    'cancel' => 'Annuller',
    'rows' => 'R�kker',
    'columns' => 'Kollonner',
    'width' => 'Bredde',
    'height' => 'H�jde',
    'border' => 'Kant',
    'pixels' => 'pixels',
    'cellpadding' => 'Celle forskydning',
    'cellspacing' => 'Celle mellemrum',
    'bg_color' => 'Baggrundsfarve',
    'error' => 'Fejl',
    'error_rows_nan' => 'R�kken er ikke et nummer',
    'error_columns_nan' => 'Kollonnen er ikke et nummer',
    'error_width_nan' => 'Bredden er ikke et nummer',
    'error_height_nan' => 'H�jden er ikke et nummer',
    'error_border_nan' => 'Kanten er ikke et nummer',
    'error_cellpadding_nan' => 'Celle forskydning er ikke et nummer',
    'error_cellspacing_nan' => 'Celle mellemrum er ikke et nummer',
  ),
  'table_cell_prop' => array(
    'title' => 'Celle indstillinger',
    'horizontal_align' => 'Horisontal placering',
    'vertical_align' => 'Vertikal placering',
    'width' => 'Bredde',
    'height' => 'H�jde',
    'css_class' => 'CSS class',
    'no_wrap' => 'Ingen tekstombrydning',
    'bg_color' => 'Baggrundsfarve',
    'ok' => '   OK   ',
    'cancel' => 'Annuller',
    'left' => 'Venstre',
    'center' => 'Centrer',
    'right' => 'H�jre',
    'top' => 'Top',
    'middle' => 'Midten',
    'bottom' => 'Bunden',
    'baseline' => 'Bundlinie',
    'error' => 'Fejl',
    'error_width_nan' => 'Bredden er ikke et nummer',
    'error_height_nan' => 'H�jden er ikke et nummer',
  ),
  'table_row_insert' => array(
    'title' => 'Inds�t r�kke'
  ),
  'table_column_insert' => array(
    'title' => 'Inds�t kolonne'
  ),
  'table_row_delete' => array(
    'title' => 'Slet r�kke'
  ),
  'table_column_delete' => array(
    'title' => 'Slet kolonne'
  ),
  'table_cell_merge_right' => array(
    'title' => 'Flet til h�jre'
  ),
  'table_cell_merge_down' => array(
    'title' => 'Flet ned'
  ),
  'table_cell_split_horizontal' => array(
    'title' => 'Split celle horisontalt'
  ),
  'table_cell_split_vertical' => array(
    'title' => 'Split celle vertikalt'
  ),
  'style' => array(
    'title' => 'Stil'
  ),
  'font' => array(
    'title' => 'Skrift'
  ),
  'fontsize' => array(
    'title' => 'St�rrelse'
  ),
  'paragraph' => array(
    'title' => 'Paragraf'
  ),
  'bold' => array(
    'title' => 'Fed'
  ),
  'italic' => array(
    'title' => 'Kursiv'
  ),
  'underline' => array(
    'title' => 'Understreget'
  ),
  'ordered_list' => array(
    'title' => 'Organiseret liste'
  ),
  'bulleted_list' => array(
    'title' => 'Prik liste'
  ),
  'indent' => array(
    'title' => 'Indent'
  ),
  'unindent' => array(
    'title' => 'Unindent'
  ),
  'left' => array(
    'title' => 'Venstre'
  ),
  'center' => array(
    'title' => 'Centrer'
  ),
  'right' => array(
    'title' => 'H�jre'
  ),
  'fore_color' => array(
    'title' => 'Forgrundsfarve'
  ),
  'bg_color' => array(
    'title' => 'Baggrundsfarve'
  ),
  'design_tab' => array(
    'title' => 'Skift til WYSIWYG (design) mode'
  ),
  'html_tab' => array(
    'title' => 'Skift til HTML (kodnings) mode'
  ),
  'colorpicker' => array(
    'title' => 'Farve v�lger',
    'ok' => '   OK   ',
    'cancel' => 'Annuller',
  ),
  'cleanup' => array(
    'title' => 'HTML renser (fjerner stilen)',
    'confirm' => 'Dette vil fjerne alle stile, skrifte og ubruelige koder fra indholdet. Nogle af dine formateringer g�r m�ske tabt.',
    'ok' => '   OK   ',
    'cancel' => 'Annuller',
  ),
  'toggle_borders' => array(
    'title' => 'Kanter fra/til',
  ),
  'hyperlink' => array(
    'title' => 'Hyperlink',
    'url' => 'URL',
    'name' => 'Navn',
    'target' => 'Destination',
    'title_attr' => 'Titel',
    'ok' => '   OK   ',
    'cancel' => 'Annuller',
  ),
  'table_row_prop' => array(
    'title' => 'R�kke indstillinger',
    'horizontal_align' => 'Horisontal placering',
    'vertical_align' => 'Vertikal placering',
    'css_class' => 'CSS class',
    'no_wrap' => 'Ingen tekstombrydning',
    'bg_color' => 'Baggrundsfarve',
    'ok' => '   OK   ',
    'cancel' => 'Annuller',
    'left' => 'Venstre',
    'center' => 'Centrer',
    'right' => 'H�jre',
    'top' => 'Top',
    'middle' => 'Midten',
    'bottom' => 'Bunden',
    'baseline' => 'Bundlinie',
  ),
  'symbols' => array(
    'title' => 'Speciale tegn',
    'ok' => '   OK   ',
    'cancel' => 'Annuller',
  ),
  'templates' => array(
    'title' => 'Templates',
  ),
  'page_prop' => array(
    'title' => 'Side indstillinger',
    'title_tag' => 'Tittel',
    'charset' => 'Tegns�t',
    'background' => 'Baggrundsbillede',
    'bgcolor' => 'Baggrundsfarve',
    'text' => 'Tekst farve',
    'link' => 'Link farve',
    'vlink' => 'Bes�gt link farve',
    'alink' => 'Aktivt link farve',
    'leftmargin' => 'Venstre margen',
    'topmargin' => 'Top margen',
    'css_class' => 'CSS class',
    'ok' => '   OK   ',
    'cancel' => 'Annuller',
  ),
  'preview' => array(
    'title' => 'Eksempel',
  ),
  'image_popup' => array(
    'title' => 'Billede popup',
  ),
  'zoom' => array(
    'title' => 'Zoom',
  ),
);
?>

