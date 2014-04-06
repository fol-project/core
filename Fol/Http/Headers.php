<?php
/**
 * Fol\Http\Headers
 *
 * Manage http headers
 */
namespace Fol\Http;

use Fol\ContainerTrait;

class Headers implements \ArrayAccess
{
    use ContainerTrait;

    /**
     * list of standard formats -> mime-types
     */
    public static $formats = [
        'atom' => ['application/atom+xml'],
        'css' => ['text/css'],
        'html' => ['text/html', 'application/xhtml+xml'],
        'gif' => ['image/gif'],
        'jpg' => ['image/jpeg', 'image/jpg'],
        'js'  => ['text/javascript', 'application/javascript', 'application/x-javascript'],
        'json' => ['text/json', 'application/json', 'application/x-json'],
        'png' => ['image/png',  'image/x-png'],
        'pdf' => ['application/pdf', 'application/x-download'],
        'rdf' => ['application/rdf+xml'],
        'rss' => ['application/rss+xml'],
        'txt' => ['text/plain'],
        'xml' => ['text/xml', 'application/xml', 'application/x-xml'],
        'zip' => ['application/zip', 'application/x-zip', 'application/x-zip-compressed']
    ];


    /**
     * List of standard http status codes
     */
    public static $status = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
    ];


    /**
     * List of standards http languages
     */
    public static $languages = [
        'aa' => 'Afar',
        'ab' => 'Abkhazian',
        'af' => 'Afrikaans',
        'am' => 'Amharic',
        'ar' => 'Arabic',
        'as' => 'Assamese',
        'ay' => 'Aymara',
        'az' => 'Azerbaijani',
        'ba' => 'Bashkir',
        'be' => 'Byelorussian',
        'bg' => 'Bulgarian',
        'bh' => 'Bihari',
        'bi' => 'Bislama',
        'bn' => 'Bengali',
        'bo' => 'Tibetan',
        'br' => 'Breton',
        'ca' => 'Catalan',
        'co' => 'Corsican',
        'cs' => 'Czech',
        'cy' => 'Welsh',
        'da' => 'Danish',
        'de' => 'German',
        'dz' => 'Bhutani',
        'el' => 'Greek',
        'en' => 'English',
        'eo' => 'Esperanto',
        'es' => 'Spanish',
        'et' => 'Estonian',
        'eu' => 'Basque',
        'fa' => 'Persian',
        'fi' => 'Finnish',
        'fj' => 'Fiji',
        'fo' => 'Faeroese',
        'fr' => 'French',
        'fy' => 'Frisian',
        'ga' => 'Irish',
        'gd' => 'Gaelic',
        'gl' => 'Galician',
        'gn' => 'Guarani',
        'gu' => 'Gujarati',
        'ha' => 'Hausa',
        'hi' => 'Hindi',
        'hr' => 'Croatian',
        'hu' => 'Hungarian',
        'hy' => 'Armenian',
        'ia' => 'Interlingua',
        'ie' => 'Interlingue',
        'ik' => 'Inupiak',
        'in' => 'Indonesian',
        'is' => 'Icelandic',
        'it' => 'Italian',
        'iw' => 'Hebrew',
        'ja' => 'Japanese',
        'ji' => 'Yiddish',
        'jw' => 'Javanese',
        'ka' => 'Georgian',
        'kk' => 'Kazakh',
        'kl' => 'Greenlandic',
        'km' => 'Cambodian',
        'kn' => 'Kannada',
        'ko' => 'Korean',
        'ks' => 'Kashmiri',
        'ku' => 'Kurdish',
        'ky' => 'Kirghiz',
        'la' => 'Latin',
        'ln' => 'Lingala',
        'lo' => 'Laothian',
        'lt' => 'Lithuanian',
        'lv' => 'Latvian',
        'mg' => 'Malagasy',
        'mi' => 'Maori',
        'mk' => 'Macedonian',
        'ml' => 'Malayalam',
        'mn' => 'Mongolian',
        'mo' => 'Moldavian',
        'mr' => 'Marathi',
        'ms' => 'Malay',
        'mt' => 'Maltese',
        'my' => 'Burmese',
        'na' => 'Nauru',
        'ne' => 'Nepali',
        'nl' => 'Dutch',
        'no' => 'Norwegian',
        'oc' => 'Occitan',
        'om' => 'Oromo',
        'or' => 'Oriya',
        'pa' => 'Punjabi',
        'pl' => 'Polish',
        'ps' => 'Pashto',
        'pt' => 'Portuguese',
        'qu' => 'Quechua',
        'rm' => 'Rhaeto-Romance',
        'rn' => 'Kirundi',
        'ro' => 'Romanian',
        'ru' => 'Russian',
        'rw' => 'Kinyarwanda',
        'sa' => 'Sanskrit',
        'sd' => 'Sindhi',
        'sg' => 'Sangro',
        'sh' => 'Serbo-Croatian',
        'si' => 'Singhalese',
        'sk' => 'Slovak',
        'sl' => 'Slovenian',
        'sm' => 'Samoan',
        'sn' => 'Shona',
        'so' => 'Somali',
        'sq' => 'Albanian',
        'sr' => 'Serbian',
        'ss' => 'Siswati',
        'st' => 'Sesotho',
        'su' => 'Sudanese',
        'sv' => 'Swedish',
        'sw' => 'Swahili',
        'ta' => 'Tamil',
        'te' => 'Tegulu',
        'tg' => 'Tajik',
        'th' => 'Thai',
        'ti' => 'Tigtinya',
        'tk' => 'Turkmen',
        'tl' => 'Tagalog',
        'tn' => 'Setswana',
        'to' => 'Tonga',
        'tr' => 'Turkish',
        'ts' => 'Tsonga',
        'tt' => 'Tatar',
        'tw' => 'Twi',
        'uk' => 'Ukrainian',
        'ur' => 'Urdu',
        'uz' => 'Uzbek',
        'vi' => 'Vietnamese',
        'vo' => 'Volapuk',
        'wo' => 'Wolof',
        'xh' => 'Xhosa',
        'yo' => 'Yoruba',
        'zh' => 'Chinese',
        'zu' => 'Zulu'
    ];


    /**
     * Detects http header from a $_SERVER array
     *
     * @return array The headers found
     */
    public static function getFromGlobals()
    {
        $headers = [];

        foreach ($_SERVER as $name => $value) {
            if (strpos($name, 'HTTP_') === 0) {
                $headers[str_replace('_', '-', substr($name, 5))] = $value;
                continue;
            }

            if (in_array($name, array('CONTENT_LENGTH', 'CONTENT_MD5', 'CONTENT_TYPE'))) {
                $headers[str_replace('_', '-', $name)] = $value;
            }
        }

        if (isset($_SERVER['PHP_AUTH_USER'])) {
            $pass = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';
            $headers['AUTHORIZATION'] = 'Basic '.base64_encode($_SERVER['PHP_AUTH_USER'].':'.$pass);
        }

        return $headers;
    }


    /**
     * Gets the status text related with a status code.
     *
     * Headers::getStatusText(404) Returns "Not Found"
     *
     * @param integer $code The Http code
     *
     * @return string The status text or false
     */
    public static function getStatusText($code)
    {
        return isset(self::$status[$code]) ? self::$status[$code] : false;
    }



    /**
     * Gets the format related with a mimetype. Search in self::$formats array.
     *
     * Headers::getFormat('text/css') Returns "css"
     *
     * @param string $mimetype The mimetype to search
     *
     * @return string The extension of the mimetype or false
     */
    public static function getFormat($mimetype)
    {
        foreach (self::$formats as $format => $mimetypes) {
            if (in_array($mimetype, $mimetypes)) {
                return $format;
            }
        }

        return false;
    }


    /**
     * Gets the mimetype related with a format. This is the opposite of getFormat()
     *
     * Headers::getMimetype('css') Returns "text/css"
     *
     * @param string $format The format to search
     *
     * @return string The mimetype code or false
     */
    public static function getMimetype($format)
    {
        return isset(self::$formats[$format][0]) ? self::$formats[$format][0] : false;
    }


    /**
     * Gets the language
     *
     * Headers::getLanguageCode('gl-es') Returns "gl"
     *
     * @param string $language The raw language code
     * @param boolean $returnName Set true to return "Galician" instead "gl" (for example)
     *
     * @return string The simplified language code or false
     */
    public static function getLanguage($language, $returnName = false)
    {
        $language = strtolower(substr($language, 0, 2));

        if (!isset(self::$languages[$language])) {
            return false;
        }

        return $returnName ? self::$languages[$language] : $language;
    }



    /**
     * Normalize the name of the parameters.
     * self::normalize('CONTENT type') Returns "Content-Type"
     *
     * @param string $string The text to normalize
     *
     * @return string The normalized text
     */
    public static function normalize($string)
    {
        return str_replace(' ', '-', ucwords(strtolower(str_replace('-', ' ', $string))));
    }


    /**
     * Sends the headers if don't have been send before
     *
     * @return boolean True if headers has been sent and false if headers had been sent before
     */
    public function send()
    {
        if (headers_sent()) {
            return false;
        }

        foreach ($this->items as $name => $value) {
            if (is_array($value)) {
                foreach ($value as $value) {
                    header($name.': '.$value, false);
                }
            } else {
                header($name.': '.$value, false);
            }
        }

        return true;
    }


    /**
     * Stores new headers. You can define an array to store more than one at the same time
     *
     * @param string  $name    The header name
     * @param string  $value   The header value
     * @param boolean $replace True to replace a previous header with the same name
     */
    public function set($name, $value = true, $replace = true)
    {
        if (is_array($name)) {
            $replace = $value;

            foreach ($name as $name => $value) {
                $this->set($name, $value, $replace);
            }

            return;
        }

        $name = self::normalize($name);

        if ($replace || !isset($this->items[$name])) {
            $this->items[$name] = $value;
        } else {
            $this->items[$name] = array_merge((array) $this->items[$name], (array) $value);
        }
    }


    /**
     * Gets one or all parameters
     *
     * @param string  $name  The header name
     * @param boolean $first Set true to return just the value of the first header with this name. False to return an array with all values.
     *
     * @return string The header value or an array with all values
     */
    public function get($name = null, $first = true)
    {
        if (func_num_args() === 0) {
            return $this->items;
        }

        $name = self::normalize($name);

        if (!isset($this->items[$name])) {
            return null;
        }

        if (is_array($this->items[$name]) && $first) {
            return $this->items[$name][0];
        }

        return $this->items[$name];
    }


    /**
     * Gets the value of an header parsed.
     *
     * $header->get('Accept') Returns: text/html,application/xhtml+xml,application/xml;q=0.9,* /*;q=0.8
     * $header->getParsed('Accept')
     * Array (
     *     [text/html] => Array()
     *     [application/xhtml+xml] => Array()
     *     [application/xml] => Array([q] => 0.9)
     *     [* /*] => Array([q] => 0.8)
     * )
     *
     * @param string $name The header name
     *
     * @return array The parsed value
     */
    public function getParsed($name)
    {
        return self::toArray($this->get($name));
    }


    /**
     * It's the opposite of getParsed: saves a header defining the value as array
     *
     * @param string $name  The header name
     * @param array  $value The parsed value
     */
    public function setParsed($name, array $value)
    {
        $this->set($name, self::toString($value));
    }


    /**
     * Gets one parameter as a getDateTime object
     * Useful for datetime values (Expires, Last-Modification, etc)
     *
     * @param string $name    The header name
     * @param string $default The default value if the header does not exists
     *
     * @return Datetime The value in a datetime object or false
     */
    public function getDateTime($name, $default = 'now')
    {
        if ($this->has($name)) {
            return \DateTime::createFromFormat(DATE_RFC2822, $this->get($name));
        }

        if ($default instanceof \Datetime) {
            return $default;
        }

        return new \Datetime($default, new \DateTimeZone('UTC'));
    }


    /**
     * Define a header using a Datetime object and returns it
     *
     * @param string          $name     The header name
     * @param Datetime|string $datetime The datetime object. You can define also an string so the Datetime object will be created
     *
     * @return Datetime The datetime object
     */
    public function setDateTime($name, $datetime = null)
    {
        if (!($datetime instanceof \Datetime)) {
            $datetime = new \DateTime($datetime);
        }

        $datetime->setTimezone(new \DateTimeZone('UTC'));
        $this->set($name, $datetime->format('D, d M Y H:i:s').' GMT');

        return $datetime;
    }


    /**
     * Deletes one or all headers
     *
     * $headers->delete('content-type') Deletes one header
     * $headers->delete() Deletes all headers
     *
     * @param $name The header name
     */
    public function delete($name = null)
    {
        if (func_num_args() === 0) {
            $this->items = array();
        } else {
            $name = self::normalize($name);

            unset($this->items[$name]);
        }
    }


    /**
     * Checks if a header exists
     *
     * @param string $name The header name
     *
     * @return boolean True if the header exists, false if not
     */
    public function has($name)
    {
        return array_key_exists(self::normalize($name), $this->items);
    }


    /**
     * Private function to parse and return http values
     *
     * @param string $value The string to parse
     *
     * @return array The parsed value
     */
    private static function toArray($value)
    {
        if (!$value) {
            return [];
        }

        $results = [];

        foreach (explode(',', $value) as $values) {
            $items = [];

            foreach (explode(';', $values) as $value) {
                if (strpos($value, '=') === false) {
                    $items[trim($value)] = true;
                } else {
                    list($name, $value) = explode('=', $value, 2);
                    $items[trim($name)] = trim($value);
                }
            }

            $name = key($items);

            if (($items[$name] === true) && (count($items) > 1)) {
                array_shift($items);
                $results[$name] = $items;
            } else {
                $results[$name] = $items[$name];
            }
        }

        return $results;
    }


    /**
     * Private function to convert a parsed http value to string
     *
     * @param array $values The parsed value
     *
     * @return string The value in string format
     */
    private static function toString(array $values)
    {
        if (!$values) {
            return '';
        }

        $results = array();

        foreach ($values as $name => $value) {
            if (!is_array($value)) {
                $results[] = ($value === true) ? $name : "$name=$value";
                continue;
            }

            $sub_values = array($name);

            foreach ($value as $value_name => $value_value) {
                $sub_values[] = ($value_value === true) ? $value_name : "$value_name=$value_value";
            }

            $results[] = implode(';', $sub_values);
        }

        return implode(',', $results);
    }
}
