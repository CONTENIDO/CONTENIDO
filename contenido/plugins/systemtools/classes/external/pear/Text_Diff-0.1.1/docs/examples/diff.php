<?php
/**
 * Text_Diff example script.
 *
 * Take two files from the command line args and produce a unified
 * diff of them.
 *
 * @package Text_Diff
 */

include_once '/var/www/html/Projekte_Persoenlich/willi.man/Text_Diff-0.1.1/Diff.php';
include_once '/var/www/html/Projekte_Persoenlich/willi.man/Text_Diff-0.1.1/Diff/Renderer.php';
include_once '/var/www/html/Projekte_Persoenlich/willi.man/Text_Diff-0.1.1/Diff/MyRenderer.php';
include_once '/var/www/html/Projekte_Persoenlich/willi.man/Text_Diff-0.1.1/Diff/Renderer/unified.php';
include_once '/var/www/html/Projekte_Persoenlich/willi.man/Text_Diff-0.1.1/Diff/Renderer/inline.php';


/* Make sure we have enough arguments. */
/*
if (count($argv) < 3) {
    echo "Usage: diff.php <file1> <file2>\n\n";
    exit;
}
*/

/* Make sure both files exist. */#
/*
if (!is_readable($argv[1])) {
    echo "$argv[1] not found or not readable.\n\n";
}
if (!is_readable($argv[2])) {
    echo "$argv[2] not found or not readable.\n\n";
}
*/

/* Load the lines of each file. */
$lines1 = file('Navigation_main_new.output.php');
$lines2 = file('Navigation_main.output.php');

/* Create the Diff object. */
$diff = &new Text_Diff($lines1, $lines2);

print "<pre>"; print_r($diff); print "</pre>";

/* Output the diff in unified format. */
#$renderer = &new Text_Diff_Renderer_unified();
#echo $renderer->render($diff);

$renderer = &new My_Text_Diff_Renderer();
echo $renderer->render($diff);

?>
