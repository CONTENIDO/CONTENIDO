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
    'title' => 'Sæt ind'
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
    'title' => 'Indsæt billede',
    'select' => 'Vælg',
    'cancel' => 'Annuller',
    'library' => 'Bibliotek',
    'preview' => 'Eksempel',
    'images' => 'Billeder',
    'upload' => 'Upload billede',
    'upload_button' => 'Upload',
    'error' => 'Fejl',
    'error_no_image' => 'Vælg venligst et billede',
    'error_uploading' => 'En fejl skete under upload. Prøv venligst igen senere',
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
    'right' => 'højre',
    'top' => 'top',
    'middle' => 'midten',
    'bottom' => 'bunden',
    'absmiddle' => 'absolut midte',
    'texttop' => 'teksttop',
    'baseline' => 'bundlinie',
    'width' => 'Bredde',
    'height' => 'Højde',
    'border' => 'Kant',
    'hspace' => 'Hor. mellemrum',
    'vspace' => 'Vert. mellemrum',
    'error' => 'Fejl',
    'error_width_nan' => 'Bredden er ikke et nummer',
    'error_height_nan' => 'Højden er ikke et nummer',
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
    'rows' => 'Rækker',
    'columns' => 'Kollonner',
    'width' => 'Bredde',
    'height' => 'Højde',
    'border' => 'Kant',
    'pixels' => 'pixels',
    'cellpadding' => 'Celle forskydning',
    'cellspacing' => 'Celle mellemrum',
    'bg_color' => 'Baggrundsfarve',
    'error' => 'Fejl',
    'error_rows_nan' => 'Rækken er ikke et nummer',
    'error_columns_nan' => 'Kollonnen er ikke et nummer',
    'error_width_nan' => 'Bredden er ikke et nummer',
    'error_height_nan' => 'Højden er ikke et nummer',
    'error_border_nan' => 'Kanten er ikke et nummer',
    'error_cellpadding_nan' => 'Celle forskydning er ikke et nummer',
    'error_cellspacing_nan' => 'Celle mellemrum er ikke et nummer',
  ),
  'table_cell_prop' => array(
    'title' => 'Celle indstillinger',
    'horizontal_align' => 'Horisontal placering',
    'vertical_align' => 'Vertikal placering',
    'width' => 'Bredde',
    'height' => 'Højde',
    'css_class' => 'CSS class',
    'no_wrap' => 'Ingen tekstombrydning',
    'bg_color' => 'Baggrundsfarve',
    'ok' => '   OK   ',
    'cancel' => 'Annuller',
    'left' => 'Venstre',
    'center' => 'Centrer',
    'right' => 'Højre',
    'top' => 'Top',
    'middle' => 'Midten',
    'bottom' => 'Bunden',
    'baseline' => 'Bundlinie',
    'error' => 'Fejl',
    'error_width_nan' => 'Bredden er ikke et nummer',
    'error_height_nan' => 'Højden er ikke et nummer',
  ),
  'table_row_insert' => array(
    'title' => 'Indsæt række'
  ),
  'table_column_insert' => array(
    'title' => 'Indsæt kolonne'
  ),
  'table_row_delete' => array(
    'title' => 'Slet række'
  ),
  'table_column_delete' => array(
    'title' => 'Slet kolonne'
  ),
  'table_cell_merge_right' => array(
    'title' => 'Flet til højre'
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
    'title' => 'Størrelse'
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
    'title' => 'Højre'
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
    'title' => 'Farve vælger',
    'ok' => '   OK   ',
    'cancel' => 'Annuller',
  ),
  'cleanup' => array(
    'title' => 'HTML renser (fjerner stilen)',
    'confirm' => 'Dette vil fjerne alle stile, skrifte og ubruelige koder fra indholdet. Nogle af dine formateringer går måske tabt.',
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
    'title' => 'Række indstillinger',
    'horizontal_align' => 'Horisontal placering',
    'vertical_align' => 'Vertikal placering',
    'css_class' => 'CSS class',
    'no_wrap' => 'Ingen tekstombrydning',
    'bg_color' => 'Baggrundsfarve',
    'ok' => '   OK   ',
    'cancel' => 'Annuller',
    'left' => 'Venstre',
    'center' => 'Centrer',
    'right' => 'Højre',
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
    'charset' => 'Tegnsæt',
    'background' => 'Baggrundsbillede',
    'bgcolor' => 'Baggrundsfarve',
    'text' => 'Tekst farve',
    'link' => 'Link farve',
    'vlink' => 'Besøgt link farve',
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

