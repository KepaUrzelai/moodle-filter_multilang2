<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    filter
 * @subpackage multilang2
 * @copyright  Gaetan Frenoy <gaetan@frenoy.net>
 * @copyright  2004 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @copyright  2015 onwards Iñaki Arenaza & Mondragon Unibertsitatea
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/*
 *  Given multilinguage text, return relevant text according to
 *  current language:
 *
 *    - look for multilang blocks in the text.
 *    - if there exists texts in the currently active language, print them.
 *    - else, if there exists texts in the current parent language, print them.
 *    - else, don't print any text inside the lang block (this is a change from previous filter versions behaviour!!!!)
 *
 *  Please note that English texts are not used as default anymore!
 * 
 *  This version is based on original multilang filter by Gaetan Frenoy, Eloy and skodak.
 * 
 *  Following new syntax is not compatible with old one:
 *    {mlang XX}one lang{mlang}Some common text for any language.{mlang YY}another language{mlang}
 */
class filter_multilang2 extends moodle_text_filter {

    protected $search;
    protected $callback;

    public function filter($text, array $options = array()) {
        global $CFG;
        
        if (empty($text) or is_numeric($text)) {
            return $text;
        }

        $this->search = '/{mlang\s+([a-z0-9_-]+)\s*}(.*?){\s*mlang\s*}/is';
        $this->callback = function ($langblock) { return filter_multilang2::replace_callback($langblock); };
        
        $result = preg_replace_callback($this->search, $this->callback, $text);

        if (is_null($result)) {
            return $text; // Error during regex processing, keep original text.
        } else {
            return $result;
        }
    }

    static protected function replace_callback($langblock) {
        global $CFG;
        static $parentcache;

        if (!isset($parentcache)) {
            $parentcache = array();
        }
        $mylang = current_language();
        if (!array_key_exists($mylang, $parentcache)) {
            $parentlang = get_parent_language($mylang);
            $parentcache[$mylang] = $parentlang;
        } else {
            $parentlang = $parentcache[$mylang];

        }

        $blocklang = trim(core_text::strtolower($langblock[1]));
        $blocktext = $langblock[2];
        if (($mylang === $blocklang) || ($parentlang === $blocklang)) {
            return $blocktext;
        }
        return '';
    }
}
