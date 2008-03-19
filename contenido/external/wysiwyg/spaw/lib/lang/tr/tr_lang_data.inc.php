<?php
// =========================================================
// SPAW PHP WYSIWYG editor control
// =========================================================
// German language file
// =========================================================
// Developed: Alan Mendelevich, alan@solmetra.lt
// Turkish translation: Zeki Erkmen, erkmen@t-online.de
// Copyright: Solmetra (c)2003 All rights reserved.
// ---------------------------------------------------------
//                                www.solmetra.com
// =========================================================
// v.1.0, 2003-04-10
// =========================================================

// charset to be used in dialogs
$spaw_lang_charset = 'iso-8859-9';

// language text data array
// first dimension - block, second - exact phrase
// alternative text for toolbar buttons and title for dropdowns - 'title'

$spaw_lang_data = array(
  'cut' => array(
    'title' => 'Kes'
  ),
  'copy' => array(
    'title' => 'Kopyala'
  ),
  'paste' => array(
    'title' => 'Ekle'
  ),
  'undo' => array(
    'title' => 'Geri al'
  ),
  'redo' => array(
    'title' => 'Tekrarla'
  ),
  'hyperlink' => array(
    'title' => 'Link ekle'
  ),
  'image_insert' => array(
    'title' => 'Resim ekle',
    'select' => 'Seç',
    'cancel' => 'Ýptal',
    'library' => 'Kütüphane',
    'preview' => 'Ön izle',
    'images' => 'Resim',
    'upload' => 'Resim yükle',
    'upload_button' => 'Yükle',
    'error' => 'Fehler',
    'error_no_image' => 'Lütfen bir resim seçiniz',
    'error_uploading' => 'Dosya yükleme iþleminde bir hata oluþtu. Lütfen biraz sonra tekrar deneyiniz.',
    'error_wrong_type' => 'Yanlýþ resim tipi',
    'error_no_dir' => 'Kütüphane fiziksel olarak mevcut',
  ),
  'image_prop' => array(
    'title' => 'Resim ayarlarý',
    'ok' => '   OK   ',
    'cancel' => 'Ýptal',
    'source' => 'Kaynak',
    'alt' => 'Alternatif Metin',
    'align' => 'Konum',
    'left' => 'Sol',
    'right' => 'Sað',
    'top' => 'Yukarda',
    'middle' => 'Ortada',
    'bottom' => 'Alt kýsýmda',
    'absmiddle' => 'Merkezde',
    'texttop' => 'Metin üstü',
    'baseline' => 'Çizgi üzeri',
    'width' => 'Geniþlik',
    'height' => 'Yükseklik',
    'border' => 'Çerceve',
    'hspace' => 'Yatay boþluk',
    'vspace' => 'Dikey boþluk',
    'error' => 'Hata',
    'error_width_nan' => 'Geniþlik sayý deðil',
    'error_height_nan' => 'Yükseklik sayý deðil',
    'error_border_nan' => 'Çerceve sayý deðil',
    'error_hspace_nan' => 'Yatay boþluk sayý deðil',
    'error_vspace_nan' => 'Dikey boþluk sayý deðil',
  ),
  'hr' => array(
    'title' => 'Yatay çizgi'
  ),
  'table_create' => array(
    'title' => 'Tabela oluþtur'
  ),
  'table_prop' => array(
    'title' => 'Tabela özellikleri',
    'ok' => '   OK   ',
    'cancel' => 'Ýptal et',
    'rows' => 'Satýrlar',
    'columns' => 'Haneler',
    'width' => 'Geniþlik',
    'height' => 'Yükseklik',
    'border' => 'Çerceve',
    'pixels' => 'Pixel',
    'cellpadding' => 'Hücreyi dolumu',
    'cellspacing' => 'Hücre mesafesi',
    'bg_color' => 'Arka ekran rengi',
    'error' => 'Hata',
    'error_rows_nan' => 'Satýr rakam deðil',
    'error_columns_nan' => 'Hane rakam deðil',
    'error_width_nan' => 'Geniþlik rakam deðil',
    'error_height_nan' => 'Yükseklik rakam deðil',
    'error_border_nan' => 'Çerceve rakam deðil',
    'error_cellpadding_nan' => 'Hücre dolumu rakam deðil',
    'error_cellspacing_nan' => 'Hücre mesafesi rakam deðil',
  ),
  'table_cell_prop' => array(
    'title' => 'Hücre özelliði',
    'horizontal_align' => 'Yatay konumu',
    'vertical_align' => 'Dikey konumu',
    'width' => 'Geniþlik',
    'height' => 'Yükseklik',
    'css_class' => 'CSS sýnýfý',
    'no_wrap' => 'Paketsiz',
    'bg_color' => 'Arka ekran rengi',
    'ok' => '   OK   ',
    'cancel' => 'Ýptal et',
    'left' => 'Sol',
    'center' => 'Merkezi',
    'right' => 'Sað',
    'top' => 'Üst kýsým',
    'middle' => 'Orta',
    'bottom' => 'Alt kýsým',
    'baseline' => 'Çizgi üstü',
    'error' => 'Hata',
    'error_width_nan' => 'Geniþlik rakam deðil',
    'error_height_nan' => 'Yükseklik rakam deðil',
    
  ),
  'table_row_insert' => array(
    'title' => 'Satýr ekle'
  ),
  'table_column_insert' => array(
    'title' => 'Hane ekle'
  ),
  'table_row_delete' => array(
    'title' => 'Satýr sil'
  ),
  'table_column_delete' => array(
    'title' => 'Hane sil'
  ),
  'table_cell_merge_right' => array(
    'title' => 'Hücreyi sað taraf ile birleþtir.'
  ),
  'table_cell_merge_down' => array(
    'title' => 'Hücereyi alt taraf ile birleþtir.'
  ),
  'table_cell_split_horizontal' => array(
    'title' => 'Hücreyi yatay olarak böl'
  ),
  'table_cell_split_vertical' => array(
    'title' => 'Hücreyi dikey olarak böl'
  ),
  'style' => array(
    'title' => 'Düzenleme'
  ),
  'font' => array(
    'title' => 'Yazý'
  ),
  'fontsize' => array(
    'title' => 'Büyüklüðü'
  ),
  'paragraph' => array(
    'title' => 'Paraðraf'
  ),
  'bold' => array(
    'title' => 'Kalýn'
  ),
  'italic' => array(
    'title' => 'Yatay ince'
  ),
  'underline' => array(
    'title' => 'Alt çizgili'
  ),
  'ordered_list' => array(
    'title' => 'Numarasal'
  ),
  'bulleted_list' => array(
    'title' => 'Listesel'
  ),
  'indent' => array(
    'title' => 'Dýþa çek'
  ),
  'unindent' => array(
    'title' => 'Ýçe çek'
  ),
  'left' => array(
    'title' => 'Sol'
  ),
  'center' => array(
    'title' => 'Merkez'
  ),
  'right' => array(
    'title' => 'Sað'
  ),
  'fore_color' => array(
    'title' => 'Yazý rengi'
  ),
  'bg_color' => array(
    'title' => 'Arka ekran rengi'
  ),
  'design_tab' => array(
    'title' => 'Design Modüsüne geç'
  ),
  'html_tab' => array(
    'title' => 'HTML Modüsüne geç'
  ),
  'colorpicker' => array(
    'title' => 'Renk seçimi',
    'ok' => '   OK   ',
    'cancel' => 'Ýptal et',
  ),
  'cleanup' => array(
    'title' => 'HTML temizleyeçi',
    'confirm' => 'Bu seçenek HTML formatlarýný (Style) Ýçeriðinizden siler. Bu komutu seçmekle ya tüm ya da bazý Style blocklarý metin içerisinden silinir ',
    'ok' => '   OK   ',
    'cancel' => 'Ýptal',
  ),
  'toggle_borders' => array(
    'title' => 'Toggle borders',
  ),
  'hyperlink' => array(
    'title' => 'Link ekle',
    'url' => 'URL',
    'name' => 'Adý',
    'target' => 'Hedef',
    'title_attr' => 'Baþlýk',
    'ok' => '   OK   ',
    'cancel' => 'Ýptal',
  ),
  'table_row_prop' => array(
    'title' => 'Satýr özellikleri',
    'horizontal_align' => 'Yatay konum',
    'vertical_align' => 'Dikey konum',
    'css_class' => 'CSS Klasý',
    'no_wrap' => 'Paketsiz',
    'bg_color' => 'Arka ekran rengi',
    'ok' => '   OK   ',
    'cancel' => 'Ýptal',
    'left' => 'Sol',
    'center' => 'Merkez',
    'right' => 'Sað',
    'top' => 'Üst',
    'middle' => 'Orta',
    'bottom' => 'Alt',
    'baseline' => 'Çizgi üstü',
  ),
  'symbols' => array(
    'title' => 'Özel karekterler',
    'ok' => '   OK   ',
    'cancel' => 'Ýptal',
  ),
  'templates' => array(
    'title' => 'Kalýplar',
  ),
  'page_prop' => array(
    'title' => 'Sayfa özelliði',
    'title_tag' => 'Baþlýk',
    'charset' => 'Metin Karekteri',
    'background' => 'Arka plan resmi',
    'bgcolor' => 'Arka plan rengi',
    'text' => 'Yazý rengi',
    'link' => 'Link rengi',
    'vlink' => 'Uðranýlmýþ link rengi',
    'alink' => 'Actif link rengi',
    'leftmargin' => 'Sol kenar',
    'topmargin' => 'Üst kenar',
    'css_class' => 'CSS Klasý',
    'ok' => '   OK   ',
    'cancel' => 'Ýptal',
  ),
  'preview' => array(
    'title' => 'Ön gösterim',
  ),
  'image_popup' => array(
    'title' => 'Resim popup',
  ),
  'zoom' => array(
    'title' => 'Büyülteç',
  ),
);
?>

