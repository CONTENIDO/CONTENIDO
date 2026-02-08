<?php

/**
 * This file contains tests for the class cString.
 *
 * @package    Testing
 * @subpackage Util
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Class to test asset util.
 *
 * @package    Testing
 * @subpackage Util
 */
class cStringTest extends cTestingTestCase
{

    #[DataProvider('replaceDiacriticsProvider')]
    public function testReplaceDiacritics(string $input, string $expected)
    {
        $this->assertEquals($expected, cString::replaceDiacritics($input));
    }

    #[DataProvider('cleanURLCharactersProvider')]
    public function testCleanURLCharacters(string $input, bool $replace, string $expected)
    {
        $this->assertEquals($expected, cString::cleanURLCharacters($input, $replace));
    }

    #[DataProvider('normalizeLineEndingsProvider')]
    public function testNormalizeLineEndings(string $input, string $lineEnding, string $expected): void
    {
        $this->assertEquals($expected, cString::normalizeLineEndings($input, $lineEnding));
    }
    public static function replaceDiacriticsProvider(): array
    {
        return [
            // Set of characters in upper and lower case
            'german_umlauts' => [
                'Ä / ä, Ö / ö, Ü / ü, ß',
                'Ae / ae, Oe / oe, Ue / ue, ss'
            ],
            'common_latin_special_chars' => [
                'À / à, Á / á, Â / â, È / è, É / é, Ê / ê, Ì / ì, Í / í, Î / î, Ò / ò, Ó / ó, Ô / ô, Ù / ù, Ú / ú, Û / û, Ç / ç, Ñ / ñ, ¿, ¡',
                'A / a, A / a, A / a, E / e, E / e, E / e, I / i, I / i, I / i, O / o, O / o, O / o, U / u, U / u, U / u, C / c, N / n, ?, !',
            ],
            'tr_chars' => [
                'Ç / ç, Ğ / ğ, I / ı, İ / i, Ş / ş, Â / â, Û / û, Î / î',
                'C / c, G / g, I / i, I / i, S / s, A / a, U / u, I / i',
            ],

            // Text in different languages
            'german_text' => [
                'Frühstück mit Käse, Öl und süßem Gebäck ist typisch deutsch. Die Straße führt über die Hügel zur nächsten Bäckerei.',
                'Fruehstueck mit Kaese, Oel und suessem Gebaeck ist typisch deutsch. Die Strasse fuehrt ueber die Huegel zur naechsten Baeckerei.'
            ],
            'french_text' => [
                "L’élève a oublié son cahier à l’école. C’était un été très chaud à Marseille.",
                "L'eleve a oublie son cahier a l'ecole. C'etait un ete tres chaud a Marseille.",
            ],
            'spanish_text' => [
                'Mi cumpleaños es el próximo miércoles, ¡qué emoción! ¿Dónde está el baño? Necesito descansar.',
                'Mi cumpleanos es el proximo miercoles, !que emocion! ?Donde esta el bano? Necesito descansar.',
            ],
            'italian_text' => [
                "L’università di Bologna è molto antica. È stato un viaggio indimenticabile in Toscana.",
                "L'universita di Bologna e molto antica. E stato un viaggio indimenticabile in Toscana.",
            ],
            'polish_text' => [
                'Wczoraj kupiłem świeże jabłka, gruszki i śliwki na targu. Łukasz zajął pierwsze miejsce w biegu na 100 metrów. Czy możesz mi powiedzieć, gdzie znajduje się najbliższy przystanek?',
                'Wczoraj kupilem swieze jablka, gruszki i sliwki na targu. Lukasz zajal pierwsze miejsce w biegu na 100 metrow. Czy mozesz mi powiedziec, gdzie znajduje sie najblizszy przystanek?',
            ],
            'czech_text' => [
                'Včera jsme šli do krásného parku plného květin. Řeka teče klidně mezi zelenými kopci. Můj bratr studuje na univerzitě v Brně.',
                'Vcera jsme sli do krasneho parku plneho kvetin. Reka tece klidne mezi zelenymi kopci. Muj bratr studuje na univerzite v Brne.',
            ],
            'romanian_text' => [
                'Mă duc la piață să cumpăr legume proaspete. România are peisaje minunate și o istorie bogată. Fereastra este deschisă, iar vântul adie ușor.',
                'Ma duc la piata sa cumpar legume proaspete. Romania are peisaje minunate si o istorie bogata. Fereastra este deschisa, iar vantul adie usor.',
            ],
            'turkish_text' => [
                "Çalışkan öğrenciler, sınavdan önceki gün kütüphaneye gittiler. İstanbul’da yağmur yağarken, Ankara’da güneşli bir hava vardı.",
                "Caliskan oegrenciler, sinavdan oenceki guen kuetuephaneye gittiler. Istanbul'da yagmur yagarken, Ankara'da guenesli bir hava vardi.",
            ],
        ];
    }

    public static function cleanURLCharactersProvider(): array
    {
        return [
            // Standard cases
            'basic_sentence' => ['Hello world, this is a test!', false, 'Hello-world-this-is-a-test'],
            'with_special_chars' => ['A string with / slashes & ampersands * asterisks.', false, 'A-string-with-slashes-ampersands-asterisks.'],
            'with_diacritics' => ['C\'est la vie, où est le café?', false, 'Cest-la-vie-ou-est-le-cafe'],
            'with_numbers' => ['String with 123 numbers.', false, 'String-with-123-numbers.'],
            'with_underscores_and_dots' => ['File_Name.php', false, 'File_Name.php'],
            'already_clean' => ['a-clean-slug-123', false, 'a-clean-slug-123'],
            'empty_string' => ['', false, ''],

            // Edge cases
            'leading_trailing_spaces' => ['   Leading and trailing spaces   ', false, 'Leading-and-trailing-spaces'],
            'multiple_separators' => ['this is---a test // with...many!!! separators', false, 'this-is-a-test-with...many-separators'],
            'only_invalid_chars' => ['@#$%^&*()', false, ''],
            'all_caps' => ['ALL CAPS STRING', false, 'ALL-CAPS-STRING'],

            // Cases with $replace = true
            'basic_sentence_replace' => ['Hello world, this is a test!', true, 'Hello-world-this-is-a-test'],
            'with_special_chars_replace' => ['A string with / slashes & ampersands * asterisks.', true, 'A-string-with-slashes-ampersands-asterisks.'],
            'only_invalid_chars_replace' => ['@#$%^&*()', true, ''],
        ];
    }

    public static function normalizeLineEndingsProvider(): array
    {
        return [
            // Test cases for default line ending "\n"
            'default_case_with_crlf' => ["Hello\r\nWorld", "\n", "Hello\nWorld"],
            'default_case_with_cr' => ["Hello\rWorld", "\n", "Hello\nWorld"],
            'default_case_with_lf' => ["Hello\nWorld", "\n", "Hello\nWorld"],
            'default_case_with_mixed_endings' => ["Hello\r\nWorld\rThis\nIs\r\nWorking", "\n", "Hello\nWorld\nThis\nIs\nWorking"],
            'default_case_empty_string' => ["", "\n", ""],
            'default_case_no_endings' => ["This is a test", "\n", "This is a test"],

            // Test cases for target line ending "\r\n"
            'crlf_target_with_crlf' => ["Hello\r\nWorld", "\r\n", "Hello\r\nWorld"],
            'crlf_target_with_cr' => ["Hello\rWorld", "\r\n", "Hello\r\nWorld"],
            'crlf_target_with_lf' => ["Hello\nWorld", "\r\n", "Hello\r\nWorld"],
            'crlf_target_with_mixed_endings' => ["Hello\r\nWorld\rThis\nIs\r\nWorking", "\r\n", "Hello\r\nWorld\r\nThis\r\nIs\r\nWorking"],

            // Test cases for target line ending "\r"
            'cr_target_with_crlf' => ["Hello\r\nWorld", "\r", "Hello\rWorld"],
            'cr_target_with_cr' => ["Hello\rWorld", "\r", "Hello\rWorld"],
            'cr_target_with_lf' => ["Hello\nWorld", "\r", "Hello\rWorld"],
            'cr_target_with_mixed_endings' => ["Hello\r\nWorld\rThis\nIs\r\nWorking", "\r", "Hello\rWorld\rThis\rIs\rWorking"],

            // Test cases for an invalid line ending, should default to "\n"
            'invalid_line_ending' => ["Hello\r\nWorld", "  ", "Hello\nWorld"],
            'empty_line_ending' => ["Hello\r\nWorld", "", "Hello\nWorld"],
        ];
    }
}
