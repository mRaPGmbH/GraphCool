<?php


namespace Mrap\GraphCool\Tests\Utils;


use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Enums\CountryCode;
use Mrap\GraphCool\Utils\Country;

class CountryTest extends TestCase
{
    public function testParse(): void
    {
        $array = [
            'Österreich' => 'AT',
            'Austria' => 'AT',
            'Deutschland' => 'DE',
            'Germany' => 'DE',
            'Schweiz' => 'CH',
            'Switzerland' => 'CH',
            'Frankreich' => 'FR',
            'France' => 'FR',
            'Polen' => 'PL',
            'Poland' => 'PL'
        ];
        foreach ($array as $value => $expected) {
            self::assertSame($expected, Country::parse($value));
        }
    }

    public function testParseLenient(): void
    {
        $array = [
            'Österreich' => 'AT',
            'Oesterreich' => 'AT',
            'österreich' => 'AT',
            'Östereich' => 'AT',
            'österr' => 'AT',
            'Öster' => 'AT',
            'Österr.' => 'AT',
            'Östrereeich' => 'AT',
            'Ostereich' => 'AT',
            'Österreich / Steiermark' => 'AT',
            'Österreichq' => 'AT',
            'AT (Österreich)' => 'AT',
            'österreiich' => 'AT',
            'Österrich' => 'AT',
            'Österreoch' => 'AT',
            'östr' => 'AT',
            'Öaterreich' => 'AT',
            'Austira' => 'AT',
            'Oostenrijk' => 'AT',
            'Österrecih' => 'AT',
            'Österrreich' => 'AT',
            'Österre' => 'AT',
            'Öterreich' => 'AT',
            'Ösaterreich' => 'AT',
            'östreich easd' => 'AT',
            'Öster reich' => 'AT',
            'Autria' => 'AT',
            'Österreiche' => 'AT',
            'sterreich' => 'AT',
            'Östrerreich' => 'AT',
            ':sterreich' => 'AT',
            'Ausztria' => 'AT',
            'Ausrtia' => 'AT',
            'Osterreich' => 'AT',
            'Östeerreich' => 'AT',
            'Austrai' => 'AT',
            'Österreidh' => 'AT',
            'Östtreich' => 'AT',
            'Öster.' => 'AT',
            'Östrreich.' => 'AT',
            'Östreeich.' => 'AT',
            'Östterreich.' => 'AT',
            'Austrtia.' => 'AT',
            'Öeterreich.' => 'AT',
            'Österreocj' => 'AT',
            'Österreuch' => 'AT',
            'ÖSTERICH' => 'AT',
            'Östrr.' => 'AT',
            'Östr.' => 'AT',
            'Östrreich' => 'AT',
            'Oestereich' => 'AT',
            'Osterrecih' => 'AT',
            'öst.' => 'AT',
            'Österrech' => 'AT',
            'Österrz' => 'AT',
            'Österreic' => 'AT',
            'Österreicht' => 'AT',
            'Österr3i' => 'AT',
            'Österriech' => 'AT',
            'sterr.' => 'AT',
            'Österr.l' => 'AT',
            'Öster,' => 'AT',
            'Öösterreich' => 'AT',
            'östraicha' => 'AT',
            'Österreichische' => 'AT',
            'Österreich /' => 'AT',
            'Ostrich' => 'AT',
            'Austrtia' => 'AT',
            'Öeterreich' => 'AT',
            'Östere.' => 'AT',
            'Östereichr' => 'AT',
            'ÖsterrÖsterr' => 'AT',
            'Австрия' => 'AT',
            'Österreich/AUSTRIA' => 'AT',
            'Austaria' => 'AT',
            'Öszerreich' => 'AT',
            'Österreich.,' => 'AT',
            'Österreicch' => 'AT',
            'Österreih' => 'AT',
            'Östterreich' => 'AT',
            '™sterreich' => 'AT',
            'Ésterreich' => 'AT',
            'Österreich+' => 'AT',
            'Österreichen' => 'AT',
            '…sterreich' => 'AT',
            'Österreichisch' => 'AT',
            'Österreich#' => 'AT',
            'Östserreich' => 'AT',
            'Össtereich' => 'AT',
            'Öserreich' => 'AT',
            'Österr,' => 'AT',
            'Austrialand' => 'AT',
            'Öaterr.' => 'AT',
            'Österreichs' => 'AT',
            'Österreich.' => 'AT',
            'Östertreich' => 'AT',
            'Esterreich' => 'AT',
            'Österreivh' => 'AT',
            'Österreich,AUT' => 'AT',
            'šsterreich' => 'AT',
            'Österteich' => 'AT',
            'Austia' => 'AT',
            'Ausria' => 'AT',
            'Österreicz' => 'AT',
            'Ösrerreich' => 'AT',
            'österrêich' => 'AT',
            'Österreicvh' => 'AT',
            'Österreucg' => 'AT',
            'Ôsterreich' => 'AT',
            'Österrerich' => 'AT',
            'Ösetterich' => 'AT',
            'Österreicher' => 'AT',
            'Östewrreich' => 'AT',
            'Ösetrr' => 'AT',
            'Österrr.' => 'AT',
            ';sterreich' => 'AT',
            'Österraich' => 'AT',
            'Östzerreich' => 'AT',
            'Ödterreich' => 'AT',
            'Össterreich' => 'AT',
            'Rakousko' => 'AT',
            'Austria/Österreich' => 'AT',
            'Östeerrich' => 'AT',
            'Ästerreich' => 'AT',
            'Österreo' => 'AT',
            'Örterreich' => 'AT',
            'Öterreichh' => 'AT',
            'ÖsterrÖsterreich' => 'AT',
            'ÖsterreichÖster' => 'AT',
            'ÖsÖsterreich' => 'AT',
            'Österreick' => 'AT',
            'Öesterreich' => 'AT',
            'Österreoich' => 'AT',
            '™sterrreich' => 'AT',
            'Österreichische.' => 'AT',
            'austiria' => 'AT',
            'Österreicj' => 'AT',
            'ÖsterÖsterreich' => 'AT',
            'Östrereich' => 'AT',
            'sterreic' => 'AT',
            'ÖLsterreich' => 'AT',
            'Österreich (Austria)' => 'AT',
            'ÖstÖsterreich' => 'AT',
            'Österreich,' => 'AT',
            'Österreich / Austria' => 'AT',
            'Öerreich' => 'AT',
            'Östrreiche' => 'AT',
            'Ösetrreich' => 'AT',
            'AUT AUSTRIA' => 'AT',
            'Ostereeich' => 'AT',
            'Österech' => 'AT',
            'Österreichj' => 'AT',
            'östreich' => 'AT',
            'Osterrreich' => 'AT',
            'Austrian' => 'AT',
            'Öszterreich' => 'AT',
            'Österrewich' => 'AT',
            'Öasterreich' => 'AT',
            'Austria (at)' => 'AT',
            'Österreich (AT)' => 'AT',
            'Österreich' => 'AT',
            'Österreeich' => 'AT',
            'Ōsterreich' => 'AT',
            'Ősterreich' => 'AT',
            'Austrija' => 'AT',
            'Österrecich' => 'AT',
            'Österreich sterr' => 'AT',
            'Österereich' => 'AT',
            'Östwrreich' => 'AT',
            'Östererich' => 'AT',
            'AT - Österreich' => 'AT',
            'Öserrec' => 'AT',
            'Õsterreich' => 'AT',
            'Austraia' => 'AT',
            'Аустрија' => 'AT',
            'Öst/' => 'AT',
            'Öterriech' => 'AT',
            'Östtereich' => 'AT',
            'Österreich der' => 'AT',
            'Österreich680' => 'AT',
            'Ösrterreich' => 'AT',
            'Ösdt' => 'AT',
            '\'Österreich' => 'AT',
            'Astria' => 'AT',
            'Austria (Österreich)' => 'AT',
            'AUSTRIA-ÖSTERREICH' => 'AT',
            'Avstrija' => 'AT',
            'Ösgterreich' => 'AT',
            'östereich' => 'AT',
            'östterreich' => 'AT',


            'Deutschland' => 'DE',
            'deutschland' => 'DE',
            'deutschaldn' => 'DE',

            'Belgien' => 'BE',
            'aus Belgien' => 'BE',
            'Niederlande' => 'NL',
            'Philippinen' => 'PH',
            'Tschechien' => 'CZ',
            'Italien' => 'IT',
            'Rumänien' => 'RO',
            'Kroatien' => 'HR',
            'Serbien' => 'RS',
            'Schweden' => 'SE',
            'Czech Republic' => 'CZ',
            'Frankreich' => 'FR',
            'Slowakai' => 'SK',
            'Slowakei' => 'SK',
            'Schweiz' => 'CH',
            'Ungarn' => 'HU',
            'Albanien' => 'AL',
            'Tschechische Republik' => 'CZ',
            'Polen' => 'PL',
            'Russland' => 'RU',
            'Techien' => 'CZ',
            'Nederland' => 'NL',
            'Niederland' => 'NL',
            'Slovakei' => 'SK',
            'Türkei' => 'TR',
            'Norwegen' => 'NO',
            'usbekistan' => 'UZ',
            'Irland' => 'IE',
            'Großbritannien' => 'GB',
            'Slowenien' => 'SI',
            'Rumänia' => 'RO',
            'Brasilien' => 'BR',
            'Belgiën' => 'BE',
            'Italia' => 'IT',
            'Holland' => 'NL',
            'Dänemark' => 'DK',
            'England' => 'GB',
            'Litauen' => 'LT',
            'Ukreine' => 'UA',
            'Australien' => 'AU',
            'Spanien' => 'ES',
            'Finnland' => 'FI',
            'Bulgarien' => 'BG',
            'FRanzreich' => 'FR',
            'Großbritanien' => 'GB',
            'Grossbritannien' => 'GB',
            'Russische Förderation' => 'RU',
            'Tschechische Repuplik' => 'CZ',
            'Großbritanien und Nordirland' => 'GB',
            'Dominikana' => 'DO',
            'Bosna Hercegovian' => 'BA',
            'Lettland' => 'LV',
            'Tschechische Rep.' => 'CZ',
            'Argentinien' => 'AR',
            'Südafrika' => 'ZA',
            'Slovensko' => 'SK',
            'Vereinigte Staaten von Amerika' => 'US',
            'Moldavien' => 'MD',
            'Luxenburg' => 'LU',
            'Lichtenstein' => 'LI',
            'GB Great Britain' => 'GB',
            'België' => 'BE',
            'amerika' => 'US',
            'Jordanien' => 'JO',
            'Turkei' => 'TR',
            'Dubai UAE' => 'AE',
            'Grönland' => 'GL',
            'Deutschlang' => 'DE',
            'Tunesien' => 'TN',
            'Itlaien' => 'IT',
            'Deuschland' => 'DE',
            'Slovenien' => 'SI',
            'Slovakai' => 'SK',
            'Fürstentum Liechtenstein' => 'LI',
            'The Netherlands' => 'NL',
            'Weißrussland' => 'BY',
            'Aserbaidschan' => 'AZ',
            'Deutschand' => 'DE',
            'Slovenija' => 'SI',
            'Bosnien Herzegowina' => 'BA',
            'Kanada' => 'CA',
            'Czechische Rebublik' => 'CZ',
            'Irak' => 'IQ',
            'Filipini' => 'PH',
            'Südkorea' => 'KR',
            'Vereinigte Arabische Emirate' => 'AE',
            'Luxemburg' => 'LU',
            'Jamaika' => 'JM',
            'Vereinigte Staaten' => 'US',
            'Czechrepublic' => 'CZ',
            'Saudi-Arabien' => 'SA',
            'Bosnien' => 'BA',
            'Deutchland' => 'DE',
            'VAE Dubai' => 'AE',
            'Singapur' => 'SG',
            'Indien' => 'IN',
            'Republica Dominikana' => 'DO',
            'Indonesien' => 'ID',
            'Detschland' => 'DE',
            'Tschechoslowakei' => 'CZ',
            'Griechenland' => 'GR',
            'Estland' => 'EE',
            'Grossbritanien' => 'GB',
            'Syrien' => 'SY',
            'Nigerien' => 'NG',
            'Slowkai' => 'SK',
            'Bosna i Hercegovina' => 'BA',
            'Süd-Korea' => 'KR',
            'Armenien' => 'AM',
            'V.Arabien' => 'AE',
            'Slovak Repuplic' => 'SK',
            'Lovak Repuplic' => 'SK',
            'Deutscland' => 'DE',
            'Mazedonien' => 'MK',
            'Bosnien und Herzegovina' => 'BA',
            'VAE' => 'AE',
            'Neusseland' => 'NZ',
            'türkiye' => 'TR',
            'Schweitz' => 'CH',
            'Deutschlan' => 'DE',
            'Ägypten' => 'EG',
            'Romanien' => 'RO',
            'Rumenien' => 'RO',
            'Czeck Rep.' => 'CZ',
            'Kananda' => 'CA',
            'Mexiko' => 'MX',
            'Czech Republick' => 'CZ',
            'Cote d\'voir' => 'CI',
            'Neuseeland' => 'NZ',
            'Czechoslovakia' => 'CZ',
            'Romania / Rumänien' => 'RO',
            'Estonien' => 'EE',
            'Froßbritannien' => 'GB',
            'Deutshcland' => 'DE',
            'Polska' => 'PL',
            'Belgique' => 'BE',
            'Dominikanische Republik' => 'DO',
            'Testonien' => 'EE',
            'Belgie' => 'BE',
            'Slovakien' => 'SK',
            'Grichenland' => 'GR',
            'Tschechisch' => 'CZ',
            'United States (US)' => 'US',
            'Italie' => 'IT',
            'Tschechslowakei' => 'CZ',
            'Chech Republic' => 'CZ',
            'Eestland' => 'EE',
            'Tscheschische Republik' => 'CZ',
            'Denemarken' => 'DK',
            'Danemark' => 'DK',
            '´Deutschland' => 'DE',
            'Belge' => 'BE',
            'Argentien' => 'AR',
            'Slowakia' => 'SK',
            'Romänien' => 'RO',
            'Weissrussland' => 'BY',
            'Lithauen' => 'LT',
            'Philipinen' => 'PH',
            'Ungaria' => 'HU',
            'Danmark' => 'DK',
            'Saudi Arabien' => 'SA',
            'Czech rep.' => 'CZ',
            'Sxhweiz' => 'CH',
            'Hungaria' => 'HU',
            'Niedelände' => 'NL',
            'Sloweisch' => 'SI',
            'Ontario, Canada' => 'CA',
            'Serbija' => 'RS',
            'Ceska Republika' => 'CZ',
            'Srbija' => 'RS',
            '^Deutschland' => 'DE',
            'Afrika' => 'ZA', // south africa oder central african republic?
            'Libanon' => 'LB',
            'Zypern' => 'CY',
            'Rumänian' => 'RO',
            'New York' => 'US',
            'Romänia' => 'RO',
            'Bosnien Herzogovina' => 'BA',
            'Tschische Republik' => 'CZ',
            'Georgien' => 'GE',
            'Qld, Australia' => 'AU',
            'Tschechoslowakai' => 'CZ',
            'Mazodonien' => 'MD',
            'Hongkong' => 'HK',
            'Turky' => 'TR',
            'Russische Föderation' => 'RU',
            'Czeck Republic' => 'CZ',
            'Macedonia (FYROM)' => 'MK',
            'Katar' => 'QA',
            'Bayern' => 'DE',
            'Deutschlandutschland' => 'DE',
            'Dchweiz' => 'CH',
            'Taiwan (ROC)' => 'TW',
            'Vietnam' => 'VN',
            'Slovenja' => 'SI',
            'Pole' => 'PL',
            'Deustschland' => 'DE',
            'Serbije' => 'RS',
            'fiji island' => 'FJ',
            'Kirgisistan' => 'KG',
            'Bolivien' => 'BO',
            'Schottland' => 'GB',
            'Norge' => 'NO',
            'Polnisch' => 'PL',
            'die Schweiz' => 'CH',
            'Tschechen' => 'CZ',
            'Bosnien-Hercegovina' => 'BA',
            'Deutzschland' => 'DE',
            'Äquatorialguinea' => 'GQ',
            'Unites States' => 'US',
            'Schweit' => 'CH',
            'Česká republika' => 'CZ',
            'Slovenska Republika' => 'SI',
            'Isreal' => 'IL',
            'Norwegien' => 'NO',
            'Belgiums' => 'BE',
            'Ukraina' => 'UA',
            'Detuschland' => 'DE',
            'Côté d\'ivoire/ Afrika' => 'CI',
            'česko' => 'CZ',
            'Česko' => 'CZ',
            'Bosnien und Herzegowina' => 'BA',
            '\'Deutschland' => 'DE',
            'ČR' => 'CZ',
            'Egyptien' => 'EG',
            'Kosovo' => 'XK',
            'U.S.A.' => 'US',
            'Romäna' => 'RO',
            'Brazilien' => 'BR',
            'Italien / Italy' => 'IT',
            'Deutsshcland' => 'DE',
            'Tscheckische Republik' => 'CZ',
            'South Afrika' => 'ZA',
            'U.A.E.' => 'AE',
            'Nordirland' => 'IE',
            'Neatherland' => 'NL',
            'Deutschalnd' => 'DE',
            'HONGKONG (SAR)' => 'HK',
            'Rußland' => 'RU',
            'Deutschlsnd' => 'DE',
            'Thailand (T)' => 'TH',
            'Udssr' => 'RU',
            'Niederlade' => 'NL',
            'Scotland' => 'GB',
            'Tokyo' => 'JP',
            'Irrland' => 'IE',
            'Dändemark' => 'DK',
            'Belgine' => 'BE',
            'Unganr' => 'HU',
            'Tschechisch Republika' => 'CZ',
            'Lithuana' => 'LT',
            'Deu' => 'DE',
            'Polish' => 'PL',
            'Russland-Russia' => 'RU',
            'Suisse' => 'CH',
            'Mazedonia' => 'MK',
            'Swizerland' => 'CH',
            'F. Liechtenstein' => 'LI',
            'Cuenca Ecuador' => 'EC',
            'Slo.' => 'SI',
            'Groß Britannien' => 'GB',
            'Duitsland' => 'DE',
            'Beutschland' => 'DE',
            'Sout Africa' => 'ZA',
            'Vereinigte Arabische Emirate (UAE)' => 'AE',
            'Dänemärk' => 'DK',
            'Vereinigtes Königreich' => 'GB',
            'Great-Britain' => 'GB',
            'Isral' => 'IL',
            'Tschechische Pepublik' => 'CZ',
            'Kroation' => 'HR',
            'NSW Australien' => 'AU',
            'Lietuva' => 'LT',
            'Deuttschland' => 'DE',
            'Großbritannien (GB)' => 'GB',
            'ITALI' => 'IT',
            'Tsch' => 'CZ',
            'South Korea' => 'KR',
            'Dutschland' => 'DE',
            'Russische F”deration' => 'RU',
            'Vereinigtes K”nigreich' => 'GB',
            'Rum„nien' => 'RO',
            'Trkei' => 'TR',
            'Mosambik' => 'MZ',
            'Italiy' => 'IT',
            'Lithvania' => 'LT',
            'Croatien' => 'HR',
            'Bosnien Herzegovina' => 'BA',
            'China (RC)' => 'CN',
            'Deutsch' => 'DE',
            'Slowakien' => 'SK',
            'Marokko' => 'MA',
            'Volksrepublik China' => 'CN',
            'Tschechische Rebulik' => 'CZ',
            'Kasachstan' => 'KZ',
            'Algerien' => 'DZ',
            'Elfenbeinküste' => 'CI',
            'United States of Amerika' => 'US',
            'Moldawien' => 'MD',
            'Niedelande' => 'NL',
            'Deutshland' => 'DE',
            'Südtirol' => 'IT',
            'Deuschand' => 'DE',
            'Nordrhein-Westfalen Germany' => 'DE',
            'Deutschlamd' => 'DE',
            'Tschechischen' => 'CZ',
            'Süd Korea' => 'KR',
            'Espana' => 'ES',
            'ehemalige jugoslawische Republik Mazedonien' => 'MK',
            'BRASIL' => 'BR',
            'Denemark' => 'DK',
            'Republic of Ireland' => 'IE',
            'Columbien' => 'CO',
            'Austtralien' => 'AU',
            'Framkreich' => 'FR',
            'Great Britian' => 'GB',
            'Niederlanden' => 'NL',
            'Kambodscha' => 'KH',
            'Niederlanda' => 'NL',
            'Tschechoslowakische Republik' => 'CZ',
            'DSeutschland' => 'DE',
            'Deutsc' => 'DE',
            'Kolumbien' => 'CO',
            'Francie' => 'FR',
            'Südtirol-Italien' => 'IT',
            'Deutsxchland' => 'DE',
            'Deutschlnad' => 'DE',
            'Deutscchland' => 'DE',
            'DEUTSCHHLAND' => 'DE',
            'Kratien' => 'HR',
            'Slonakia' => 'SK',
            'United Arabic Emirates' => 'AE',
            'Palästina' => 'PS',
            'Cech Repuplic' => 'CZ',
            'Italien.' => 'IT',
            'Sverige' => 'SE',
            'Polsko' => 'PL',
            'Itálie' => 'IT',
            'Deutschland (D)' => 'DE',
            'Tschische Rep.' => 'CZ',
            'Italen' => 'IT',
            'Slowakische Republik' => 'SK',
            'Česka Republika' => 'CZ',
            'Kroatia' => 'HR',
            'Undarn' => 'HU',
            'Èeská republika' => 'CZ',
            'Rmänien' => 'RO',
            'Schweriz' => 'CH',
            'Tschechei' => 'CZ',
            'Grßbritannien' => 'GB',
            'Hungarian' => 'HU',
            'Ungan' => 'HU',
            'Grozbritannie' => 'GB',
            'Bělorusko' => 'BY',
            'Rumänine' => 'RO',
            'Nederlands' => 'NL',
            'Belguen' => 'BE',
            'Western Australia' => 'AU',
            '-schweiz' => 'CH',
            'Espania' => 'ES',
            'Švédsko' => 'SE',
            'Dánsko' => 'DK',
            'Německo' => 'DE',
            'Ukaine' => 'UA',
            'Begien' => 'BE',
            'Tschechische Republick' => 'CZ',
            'Rumanien' => 'RO',
            'Montengro' => 'ME',
            'Jordanien / Jordan' => 'JO',
            'Izrel' => 'IL',
            'Litva' => 'LT',
            'Čína' => 'CN',
            'Rusko' => 'RU',
            'Maďarsko' => 'HU',
            'Abadan, Iran' => 'IR',
            'Zanjan, Iran' => 'IR',
            'Saudiarabien' => 'SA',
            'CZECH Repuplik' => 'CZ',
            'Dubai' => 'AE',
            'Slivakia' => 'SK',
            'Niedeerlande' => 'NL',
            'Ruminien' => 'RO',
            'Niiederlande' => 'NL',
            'Swiss' => 'CH',
            'Slovak Republik' => 'SK',
            'UK Vereinigtes Königreich' => 'GB',
            'Eesti' => 'EE',
            'Hruatska' => 'HR',
            'Španělsko' => 'ES',
            'Jižní Korea' => 'KR',
            'Ceska' => 'CZ',
            'Deuts' => 'DE',
            'Republic of Korea' => 'KR',
            'Magyarország' => 'HU',
            'Roumanie' => 'RO',
            'Deutechland' => 'DE',
            'Niederlamde' => 'NL',
            'New zeeland' => 'NZ',
            'Deutschlad' => 'DE',
            'Hungari' => 'HU',
            'Argentinia' => 'AR',
            'Groß Britanien' => 'GB',
            'Grozbritanien' => 'GB',
            'Italian' => 'IT',
            'Slowanie' => 'SI',
            'Jordnaien' => 'JO',
            'united  states' => 'US',
            'Fürstentum Lichtenstein' => 'LI',
            'Republic of Belarus' => 'BY',
            'Unganrn' => 'HU',
            'Alicante/Spanien' => 'ES',
            'Südtirol/Italien' => 'IT',
            'Hungarry' => 'HU',
            'BOSNE ǀ HERCEGOVINE' => 'BA',
            'Mazadonien' => 'MK',
            'Brasilia' => 'BR',
            'Deutschlan d' => 'DE',
            'Slovenijen' => 'SI',
            'Deuutschland' => 'DE',
            'HR-Croatia' => 'HR',
            'Ungarm' => 'HU',
            'Slovinsko' => 'SI',
            'Italien/Südtirol' => 'IT',
            'Bosnien Herzigovina' => 'BA',
            'Slovak Republic' => 'SK',
            'Tschechhen' => 'CZ',
            'Polská republika' => 'PL',
            'deutschladn' => 'DE',
            'Tschechische Republik (CZ)' => 'CZ',
            'Greek' => 'GR',
            'Deutschlaand' => 'DE',
            'USA / TN' => 'US',
            'Luxemborg' => 'LU',
            'Slowenia' => 'SI',
            'Frankeich' => 'FR',
            'Spaien' => 'ES',
            'ungran' => 'HU',
            'Deuzschland' => 'DE',
            'Libyen' => 'LY',
            'Deutschlabd' => 'DE',
            'Deutschkand' => 'DE',
            'SLOVKAI' => 'SK',
            'Romanei' => 'RO',
            'Duetschland' => 'DE',
            'Schwiss' => 'CH',
            'Ungarn - Hungary' => 'HU',
            'Deut' => 'DE',
            'Uruquay' => 'UY',
            'Czeck' => 'CZ',
            'sweiz' => 'CH',
            'Deutrschland' => 'DE',
            'Schweizer' => 'CH',
            'Vereinigtes Königreich Großbritannien' => 'GB',
            'Republic of Moldova' => 'MD',
            'Amerkia' => 'US',
            'Deutschlannd' => 'DE',
            'Australien (AUS)' => 'AU',
            'Rusland' => 'RU',
            'Niederalande' => 'NL',
            'Cechoslovaka' => 'CZ',
            'Tscheische Rebublik' => 'CZ',
            'Malediven' => 'MV',
            'Niederlande Antillen' => 'NL',
            'Unites States Of America' => 'US',
            'Bénin' => 'BJ',
            'FEANCE' => 'FR',
            'turquie' => 'TR',
            'marrakech' => 'MA',
            'marrakesh' => 'MA',
            'Niderlande' => 'NL',
            'Gemany' => 'DE',
            'Deitschland' => 'DE',
            'Türkey' => 'TR',
            'USA / NC' => 'US',
            'Deotschland' => 'DE',
            'Dänemark (DK)' => 'DK',
            'Rumunia' => 'RO',
            'Kroatian' => 'HR',
            'Czechy' => 'CZ',
            'Szwajcaria' => 'CH',
            'Deutschlnd' => 'DE',
            'Francja' => 'FR',
            'ایران' => 'IR',
            'Deutsland' => 'DE',
            'Rusia' => 'RU',
            'Francia' => 'FR',
            'Belgia' => 'BE',
            'USA / OR' => 'US',
            'Czech-Rep.' => 'CZ',
            'Brazylia' => 'BR',
            'Römanien' => 'RO',
            'Holandia' => 'NL',
            'Deutschlaned' => 'DE',
            'Niederlande (NL)' => 'NL',
            'Bosnien-Herzegowina' => 'BA',
            'P.R. of China' => 'CN',
            'Tschechische Republic' => 'CZ',
            'Great Britain, Northern Ireland' => 'GB',
            'DE - Deutschland' => 'DE',
            'Srbia' => 'RS',
            'Isle of Man/UK' => 'GB',
            'Macedonien' => 'MK',
            'slovenská republika' => 'SK',
            'Polen (PL)' => 'PL',
            'Deeutschland' => 'DE',
            'USA - United States of America' => 'US',
            'Deutaschland' => 'DE',
            'România' => 'RO',
            'NSW Australia' => 'AU',
            'Rumania' => 'RO',
            'LU - Luxemburg' => 'LU',
            'Vereinigten Arabischen Emirate' => 'AE',
            'Bangladesch' => 'BD',
            'Deutschland/ Bayern' => 'DE',
            'Russische Federation' => 'RU',
            'Doha, Qatar' => 'QA',
            'De Uta chla nd' => 'DE',
            'Ungern' => 'HU',
            'Japonsko' => 'JP',
            'Schweiz/Deutschland' => 'CH',
            'Unitet Kingdom' => 'GB',
            'Rep. Dominicana' => 'DO',
            'Franchreich' => 'FR',
            'Francis' => 'FR',
            'Unrgarn' => 'HU',
            'Dominikan. Republik' => 'DO',
            'Italien Südtirol' => 'IT',
            'Tadschikistan' => 'TJ',
            'Lituanien' => 'LT',
            'Deutschlande' => 'DE',
            'UNGARNA' => 'HU',
            'Saud Arabia' => 'ZA',
            'Great Britain Wales' => 'GB',
            'REPUBLIC OF LITHUANIA' => 'LT',
            'France.' => 'FR',
            'Deutschl.' => 'DE',
            'Tchechien' => 'CZ',
            'Canadian' => 'CA',
            'Deutschland (DEU)' => 'DE',
            'Deutschland/Germany' => 'DE',
            'Tschehoslovakai' => 'CZ',
            'Tschechische Repubik' => 'CZ',
            'Slovwakei' => 'SK',
            'Die Niederlande' => 'NL',
            'Espagne' => 'ES',
            'Tschech' => 'CZ',
            'Teheran, Iran' => 'IR',
            'Deztschland' => 'DE',
            'Dehtschland' => 'DE',
            'Fürtentum Lichtenstein' => 'LI',
            'Ukraie' => 'UA',
            'Sdtirol (IT)' => 'IT',
            'Tunisie' => 'TN',
            'Algérie' => 'DZ',
            'Jordanie' => 'JO',
            'Zambie' => 'ZM',
            'Deutschlsand' => 'DE',
            'Scweiz' => 'CH',
            'Techische Replubik' => 'CZ',
            'Deutschland (Germany)' => 'DE',
            'Deutschland-NL' => 'DE',
            'Fran kreich' => 'FR',
            'Valencia' => 'ES',
            'Curacao' => 'CW',
            'Rossia' => 'RU',
            'Czechische Repubik' => 'CZ',
            'Izrael' => 'IL',
            'Deurschland' => 'DE',
            'Chorvatsko' => 'HR',
            'Estonsko' => 'EE',
            'Suomi' => 'FI',
            'BZ - Italien' => 'IT',
            'Ukrajina' => 'UA',
            'Velká Británie' => 'GB',
            'Deutschlanf' => 'DE',
            'Hrvatska' => 'HR',
            'Trinidad und Tobago' => 'TT',
            'D„nemark' => 'DK',
            'Quatar' => 'QA',
            'Korea, Republik' => 'KR',
            'Liechtenstein FL' => 'LI',
            'Deutschland Nord' => 'DE',
            'P.R.China' => 'CN',
            'Latvija' => 'LV',
            'Deutschald' => 'DE',
            'Hollandia' => 'NL',
            'Tschechische  Repubik' => 'CZ',
            'Deutschland Hessen' => 'DE',
            'Romenien' => 'RO',
            'Italien (ITA)' => 'IT',
            'Ungard' => 'HU',
            'Nederland.' => 'NL',
            'Bayern / Deutschland' => 'DE',
            'Bosnia Herzegovina' => 'BA',
            'españa' => 'ES',
            'Grßbritanien' => 'GB',
            'CH - Schweiz' => 'CH',
            'Cypern' => 'CY',
            'Rümänien' => 'RO',
            'Ialien' => 'IT',
            'NEMACKA' => 'DE',
            'ITALIJA' => 'IT',
            'Kanada BC' => 'CA',
            'Arad Rumänien' => 'RO',
            'Schweiiz' => 'CH',
            'Budapest' => 'HU',
            'IRLANDE' => 'IE',
            'Affania' => 'AF',
            'Deutscchaland' => 'DE',
            'Svizzera' => 'CH',
            'Isle of Man/ England' => 'GB',
            'Bosnoen und Herzegowina' => 'BA',
            'Deustchland' => 'DE',
            'TSCHECHIEN , CESKA REPUBLIKA' => 'CZ',
            'cechoslowakai' => 'CZ',
            'Rumeänien' => 'RO',
            'Irakl' => 'IQ',
            'Beligien' => 'BE',
            'Bosnien u. Herzegowina' => 'BA',
            'Süd Kora' => 'KR',
            'Ukraiene' => 'UA',
            'Suiza' => 'CH',
            'Moldanie' => 'MD',
            'Scgweiz' => 'CH',
            'Rumeniän' => 'RO',
            'Nieierlande' => 'NL',
            'Bosnien&Herzegowina' => 'BA',
            'Niedrlande' => 'NL',
            'Irland (IRL)' => 'IE',
            'Isle Of Wight/England' => 'GB',
            'Deutschland,DEU' => 'DE',
            'Mongolie' => 'MN',
            'Tschechien Republik' => 'CZ',
            'Czech Rep' => 'CZ',
            'Sinhgapur' => 'SG',
            'Niederlsnde' => 'NL',
            'Polend' => 'PL',
            'Slovakia / Slowakei' => 'SK',
            'Deudschland' => 'DE',
            'tschechische' => 'CZ',
            'Nizozemsko' => 'NL',
            'Teschechische Republik' => 'CZ',
            'Deutschland#' => 'DE',
            'Románia' => 'RO',
            'Belgien (B)' => 'BE',
            'Bulagrien' => 'BG',
            'Tschechischen Republik' => 'CZ',
            'Deurtschland' => 'DE',
            'Niderland' => 'NL',
            'Istrael' => 'IL',
            'Niede4rlande' => 'NL',
            'Malaysien' => 'MY',
            'IVA Italia' => 'IT',
            'Deutsschland' => 'DE',
            'Cameroun' => 'CM',
            'Bosnien i Hercegovina' => 'BA',
            'Honkong' => 'HK',
            'Israel (IL)' => 'IL',
            'Dubai, Vereinigte Arabische Emirate' => 'AE',
            'ESPANYA' => 'ES',
            'Tscheien' => 'CZ',
            'AFGANISTAN' => 'AF',
            'Deutschaland' => 'DE',
            'Ubgarn' => 'HU',
            'Romenia' => 'RO',
            'Litoauen' => 'LT',
            'Bosnien  und Herzegowina' => 'BA',
            'Nordmazedonien' => 'MK',
            'Tschehische Republic' => 'CZ',
            'Irish' => 'IE',
            'Teschechien' => 'CZ',
            'epaña' => 'ES',
            'Martinique (Franz.Verwaltung)' => 'MQ',
            'Cechien' => 'CZ',
            'Ukraine (UA)' => 'UA',
            'Makedonien' => 'MK',
            'Kindom of Bahrain' => 'BH',
            'България' => 'BG',
            'Ceská republika' => 'CZ',
            'Kenia' => 'KE',
            'Allemagne' => 'DE',
            'Schwitz' => 'CH',
            'Czech.Republik' => 'CZ',
            'Tschrchische Republik' => 'CZ',
            'chweden' => 'SE',
            'Tchech Republik' => 'CZ',
            'Australie' => 'AU',
            'BOSNIEN UMD HERCEGONIVE' => 'BA',
            'Pölen' => 'PL',
            'der Niederlände' => 'NL',
            'Argentinen' => 'AR',
            'Slovenska' => 'SL',
            'Slovekei' => 'SK',
            'Scheden' => 'SE',
            'Norwegian' => 'NO',
            'PR of China' => 'CN',
            'Russia' => 'RU',
            'Great Britain' => 'GB',
            'Taiwan' => 'TW',
            'United States' => 'US',
            'Czech. Rep.' => 'CZ',
            'Nemecko' => 'DE',
            'Czech' => 'CZ',
            'United Kingdom' => 'GB',
            'Ungar' => 'HU',
            'RUMÄNIEN' => 'RO',
            'Korea' => 'KR',
            'Iran' => 'IR',
            'česká republika' => 'CZ',
            'Island' => 'IS',
            'Netherland' => 'NL',
            'Hungar' => 'HU',
            'Hun' => 'HU',
            'Kingdom of Saudi Arabia' => 'SA',
            'Moldova' => 'MD',
            'America' => 'US',
            'Cesko' => 'CZ',
            'venezuela' => 'VE',
            'Brazillian' => 'BR',
            'Macedonia' => 'MK',
            'Cayman Island' => 'KY',
            'British' => 'GB',
            'México' => 'MX',
            'Venezuela' => 'VE',
            'Verenigtes Königreich' => 'GB',
            'Sénégal' => 'SN',
            'Franc' => 'FR',
            'Phiilipines' => 'PH',
            'česka republika' => 'CZ',
            'Slovaška' => 'SK',
            'Bosnia' => 'BA',
            'Serbin' => 'RS',
            'chweiz' => 'CH',
            'Romanian' => 'RO',
        ];

        foreach ($array as $value => $expected) {
            self::assertSame($expected, Country::parseLenient($value), $value);
        }
    }

    public function testLenientError(): void
    {
        self::assertNull(Country::parseLenient('not-a-country-name!'));
    }

    public function testTooShort(): void
    {
        $tooShort = ['A','B','c','d','E','f','G','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','X','y','Z','+','*'];
        foreach ($tooShort as $value) {
            self::assertNull(Country::parse($value));
        }
    }

    public function testAlpha2(): void
    {
        $codes = new CountryCode();
        foreach ($codes->getValues() as $value) {
            self::assertSame($value->value, Country::parse($value->value));
        }
        foreach ($codes->getValues() as $value) {
            self::assertSame($value->value, Country::parse(strtolower($value->value)));
        }
    }

    public function testAlpha2Error(): void
    {
        self::assertNull(Country::parse('QQ'));
    }

    public function testAlpha3(): void
    {
        $codes = new CountryCode();
        foreach ($codes->getValues() as $value) {
            if ($value->value === 'XK') {
                self::assertNull(Country::convertToAlpha3($value->value));
            } else {
                self::assertSame($value->value, Country::parse(Country::convertToAlpha3($value->value)));
            }
        }
    }

}