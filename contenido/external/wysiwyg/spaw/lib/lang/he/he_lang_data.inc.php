<?php 
// ================================================
// SPAW PHP WYSIWYG editor control
// ================================================
// Hebrew language file
// ================================================
// Developed: Alan Mendelevich, alan@solmetra.lt
// Translation to Hebrew: Yaron Gonen (lord_gino@yahoo.com)
// Copyright: Solmetra (c)2003 All rights reserved.
// ------------------------------------------------
//                                www.solmetra.com
// ================================================
// v.1.0, 2003-03-20
// ================================================

// charset to be used in dialogs
$spaw_lang_charset = 'windows-1255';

// text direction for the language
$spaw_lang_direction = 'rtl';

// language text data array
// first dimension - block, second - exact phrase
// alternative text for toolbar buttons and title for dropdowns - 'title'

$spaw_lang_data = array(
  'cut' => array(
    'title' => 'גזור'
  ),
  'copy' => array(
    'title' => 'העתק'
  ),
  'paste' => array(
    'title' => 'הדבק'
  ),
  'undo' => array(
    'title' => 'בטל'
  ),
  'redo' => array(
    'title' => 'בצע שוב'
  ),
  'hyperlink' => array(
    'title' => 'היפר קישור'
  ),
  'image_insert' => array(
    'title' => 'הכנס תמונה',
    'select' => '  בחר  ',
    'cancel' => '  בטל  ',
    'library' => 'ספריה',
    'preview' => 'תצוגה מקדימה',
    'images' => 'תמונות',
    'upload' => 'העלה תמונה',
    'upload_button' => 'העלה',
    'error' => 'שגיאה',
    'error_no_image' => 'בחר תמונה',
    'error_uploading' => 'ארעה שגיאה בעת העלאת התמונה. אנא נסה שוב מאוחר יותר.',
    'error_wrong_type' => 'סוג קובץ תמונה שגוי',
    'error_no_dir' => 'הספריה אינה קיימת',
  ),
  'image_prop' => array(
    'title' => 'אפשרויות תמונה',
    'ok' => '  אוקי  ',
    'cancel' => '  בטל  ',
    'source' => 'מקור',
    'alt' => 'טקסט אלטרנטיבי',
    'align' => 'הצמדה',
    'left' => 'שמאל',
    'right' => 'ימין',
    'top' => 'למעלה',
    'middle' => 'אמצע',
    'bottom' => 'למטה',
    'absmiddle' => 'מרכז',
    'texttop' => 'texttop',
    'baseline' => 'baseline',
    'width' => 'רוחב',
    'height' => 'גובה',
    'border' => 'קו גבול',
    'hspace' => 'מרווח אפקי',
    'vspace' => 'מרווח אנכי',
    'error' => 'שגיאה',
    'error_width_nan' => 'הרוחב אינו מספר',
    'error_height_nan' => 'הגובה אינו מספר',
    'error_border_nan' => 'הגבול אינו מספר',
    'error_hspace_nan' => 'מרווח אפקי אינו מספר',
    'error_vspace_nan' => 'מרווח אנכי אינו מספר',
  ),
  'hr' => array(
    'title' => 'קו אפקי'
  ),
  'table_create' => array(
    'title' => 'צור טבלה'
  ),
  'table_prop' => array(
    'title' => 'אפשרויות טבלה',
    'ok' => '  אוקי  ',
    'cancel' => '  בטל  ',
    'rows' => 'שורות',
    'columns' => 'עמודות',
    'width' => 'רוחב',
    'height' => 'גובה',
    'border' => 'גבול',
    'pixels' => 'פיקסלים',
    'cellpadding' => 'דיפון תא',
    'cellspacing' => 'ריווח תא',
    'bg_color' => 'צבע רקע',
    'error' => 'שגיאה',
    'error_rows_nan' => 'השורות אינן מספר',
    'error_columns_nan' => 'העמודות אינן מספר',
    'error_width_nan' => 'הרוחב אינן מספר',
    'error_height_nan' => 'הגובה אינו מספר',
    'error_border_nan' => 'הגבול אינו מספר',
    'error_cellpadding_nan' => 'דיפון התא אינו מספר',
    'error_cellspacing_nan' => 'ריווח התא אינו מספר',
  ),
  'table_cell_prop' => array(
    'title' => 'אפשרויות תא',
    'horizontal_align' => 'הצמדה אפקית',
    'vertical_align' => 'הצמדה אנכית',
    'width' => 'רוחב',
    'height' => 'גובה',
    'css_class' => 'CSS class',
    'no_wrap' => 'ללא שבירת שורות',
    'bg_color' => 'צבע רקע',
    'ok' => '  אוקי  ',
    'cancel' => '  בטל  ',
    'left' => 'שמאל',
    'center' => 'מרכז',
    'right' => 'ימין',
    'top' => 'למעלה',
    'middle' => 'אמצע',
    'bottom' => 'למטה',
    'baseline' => 'קו התחלה',
    'error' => 'שגיאה',
    'error_width_nan' => 'הרוחב אינו מספר',
    'error_height_nan' => 'גובה אינו מספר',
    
  ),
  'table_row_insert' => array(
    'title' => 'הכנס רשומה'
  ),
  'table_column_insert' => array(
    'title' => 'הכנס עמודה'
  ),
  'table_row_delete' => array(
    'title' => 'מחק רשומה'
  ),
  'table_column_delete' => array(
    'title' => 'מחק עמודה'
  ),
  'table_cell_merge_right' => array(
    'title' => 'מזג תאים ימינה'
  ),
  'table_cell_merge_down' => array(
    'title' => 'מזג תאים למטה'
  ),
  'table_cell_split_horizontal' => array(
    'title' => 'פצל תא אפקית'
  ),
  'table_cell_split_vertical' => array(
    'title' => 'פצל תא אנכית'
  ),
  'style' => array(
    'title' => 'סגנון'
  ),
  'font' => array(
    'title' => 'גופן'
  ),
  'fontsize' => array(
    'title' => 'גודל'
  ),
  'paragraph' => array(
    'title' => 'פיסקה'
  ),
  'bold' => array(
    'title' => 'מודגש'
  ),
  'italic' => array(
    'title' => 'נטוי'
  ),
  'underline' => array(
    'title' => 'קו תחתי'
  ),
  'ordered_list' => array(
    'title' => 'רשימה ממוספרת'
  ),
  'bulleted_list' => array(
    'title' => 'רשימה'
  ),
  'indent' => array(
    'title' => 'הכנס פנימה'
  ),
  'unindent' => array(
    'title' => 'הוצא'
  ),
  'left' => array(
    'title' => 'שמאל'
  ),
  'center' => array(
    'title' => 'מרכז'
  ),
  'right' => array(
    'title' => 'ימין'
  ),
  'fore_color' => array(
    'title' => 'צבע קדמי'
  ),
  'bg_color' => array(
    'title' => 'צבע רקע'
  ),
  'design_tab' => array(
    'title' => 'עיצוב המסמך'
  ),
  'html_tab' => array(
    'title' => 'ערוך קוד Html'
  ),
  'colorpicker' => array(
    'title' => 'בחר צבע',
    'ok' => '  אוקי  ',
    'cancel' => '  בטל  ',
  ),
  // <<<<<<<<< NEW >>>>>>>>>
  'cleanup' => array(
    'title' => 'ניקוי Html (הסר סגנונות)',
    'confirm' => 'ביצוע פעולה זו יסיר את כל הסגנונות, גופנים וכל התאגים הלא שימושיים ממסמך זה. חלק או כל העיצובים יאבדו.',
    'ok' => '  אוקי  ',
    'cancel' => '  בטל  ',
  ),
  'toggle_borders' => array(
    'title' => 'חיזוק גבולות',
  ),
  'hyperlink' => array(
    'title' => 'היפר קישור',
    'url' => 'URL',
    'name' => 'שם',
    'target' => 'מטרה',
    'title_attr' => 'כותרת',
    'ok' => '  אוקי  ',
    'cancel' => '  בטל  ',
  ),
  'table_row_prop' => array(
    'title' => 'תכונות שורה',
    'horizontal_align' => 'הצמדה אופקית',
    'vertical_align' => 'הצמדה אנכית',
    'css_class' => 'CSS class',
    'no_wrap' => 'ללא שבירת שורות',
    'bg_color' => 'צבע רקע',
    'ok' => '  אוקי  ',
    'cancel' => '  בטל  ',
    'left' => 'שמאל',
    'center' => 'מרכז',
    'right' => 'ימין',
    'top' => 'למעלה',
    'middle' => 'אמצע',
    'bottom' => 'למטה',
    'baseline' => 'קו התחלה',
  ),
  'symbols' => array(
    'title' => 'תווים מיוחדים',
    'ok' => '  אוקי  ',
    'cancel' => '  בטל  ',
  ),
  'templates' => array(
    'title' => 'תבניות',
  ),
  'page_prop' => array(
    'title' => 'תכונות דף',
    'title_tag' => 'כותרת',
    'charset' => 'Charset',
    'background' => 'תמונת רקע',
    'bgcolor' => 'צבע רקע',
    'text' => 'צבע טקסט',
    'link' => 'צבע קישור',
    'vlink' => 'צבע קישור שהיו בו כבר',
    'alink' => 'צבע קישור פעיל',
    'leftmargin' => 'שוליים שמאליים',
    'topmargin' => 'שוליים עליונים',
    'css_class' => 'CSS class',
    'ok' => '  אוקי  ',
    'cancel' => '  בטל  ',
  ),
  'preview' => array(
    'title' => 'תצוגה מקדימה',
  ),
  'image_popup' => array(
    'title' => 'תמונה קופצת',
  ),
  'zoom' => array(
    'title' => 'זום',
  ),
);
?>

