<?php

    #[AllowDynamicProperties]
    class WP_Locale
    {
        public $weekday = [];

        public $weekday_initial = [];

        public $weekday_abbrev = [];

        public $month = [];

        public $month_genitive = [];

        public $month_abbrev = [];

        public $meridiem = [];

        public $text_direction = 'ltr';

        public $number_format = [];

        public $list_item_separator;

        public $word_count_type;

        public function __construct()
        {
            $this->init();
            $this->register_globals();
        }

        public function init()
        {
            // The weekdays.
            $this->weekday[0] = /* translators: Weekday. */
                __('Sunday');
            $this->weekday[1] = /* translators: Weekday. */
                __('Monday');
            $this->weekday[2] = /* translators: Weekday. */
                __('Tuesday');
            $this->weekday[3] = /* translators: Weekday. */
                __('Wednesday');
            $this->weekday[4] = /* translators: Weekday. */
                __('Thursday');
            $this->weekday[5] = /* translators: Weekday. */
                __('Friday');
            $this->weekday[6] = /* translators: Weekday. */
                __('Saturday');

            // The first letter of each day.
            $this->weekday_initial[$this->weekday[0]] = /* translators: One-letter abbreviation of the weekday. */
                _x('S', 'Sunday initial');
            $this->weekday_initial[$this->weekday[1]] = /* translators: One-letter abbreviation of the weekday. */
                _x('M', 'Monday initial');
            $this->weekday_initial[$this->weekday[2]] = /* translators: One-letter abbreviation of the weekday. */
                _x('T', 'Tuesday initial');
            $this->weekday_initial[$this->weekday[3]] = /* translators: One-letter abbreviation of the weekday. */
                _x('W', 'Wednesday initial');
            $this->weekday_initial[$this->weekday[4]] = /* translators: One-letter abbreviation of the weekday. */
                _x('T', 'Thursday initial');
            $this->weekday_initial[$this->weekday[5]] = /* translators: One-letter abbreviation of the weekday. */
                _x('F', 'Friday initial');
            $this->weekday_initial[$this->weekday[6]] = /* translators: One-letter abbreviation of the weekday. */
                _x('S', 'Saturday initial');

            // Abbreviations for each day.
            $this->weekday_abbrev[$this->weekday[0]] = /* translators: Three-letter abbreviation of the weekday. */
                __('Sun');
            $this->weekday_abbrev[$this->weekday[1]] = /* translators: Three-letter abbreviation of the weekday. */
                __('Mon');
            $this->weekday_abbrev[$this->weekday[2]] = /* translators: Three-letter abbreviation of the weekday. */
                __('Tue');
            $this->weekday_abbrev[$this->weekday[3]] = /* translators: Three-letter abbreviation of the weekday. */
                __('Wed');
            $this->weekday_abbrev[$this->weekday[4]] = /* translators: Three-letter abbreviation of the weekday. */
                __('Thu');
            $this->weekday_abbrev[$this->weekday[5]] = /* translators: Three-letter abbreviation of the weekday. */
                __('Fri');
            $this->weekday_abbrev[$this->weekday[6]] = /* translators: Three-letter abbreviation of the weekday. */
                __('Sat');

            // The months.
            $this->month['01'] = /* translators: Month name. */
                __('January');
            $this->month['02'] = /* translators: Month name. */
                __('February');
            $this->month['03'] = /* translators: Month name. */
                __('March');
            $this->month['04'] = /* translators: Month name. */
                __('April');
            $this->month['05'] = /* translators: Month name. */
                __('May');
            $this->month['06'] = /* translators: Month name. */
                __('June');
            $this->month['07'] = /* translators: Month name. */
                __('July');
            $this->month['08'] = /* translators: Month name. */
                __('August');
            $this->month['09'] = /* translators: Month name. */
                __('September');
            $this->month['10'] = /* translators: Month name. */
                __('October');
            $this->month['11'] = /* translators: Month name. */
                __('November');
            $this->month['12'] = /* translators: Month name. */
                __('December');

            // The months, genitive.
            $this->month_genitive['01'] = /* translators: Month name, genitive. */
                _x('January', 'genitive');
            $this->month_genitive['02'] = /* translators: Month name, genitive. */
                _x('February', 'genitive');
            $this->month_genitive['03'] = /* translators: Month name, genitive. */
                _x('March', 'genitive');
            $this->month_genitive['04'] = /* translators: Month name, genitive. */
                _x('April', 'genitive');
            $this->month_genitive['05'] = /* translators: Month name, genitive. */
                _x('May', 'genitive');
            $this->month_genitive['06'] = /* translators: Month name, genitive. */
                _x('June', 'genitive');
            $this->month_genitive['07'] = /* translators: Month name, genitive. */
                _x('July', 'genitive');
            $this->month_genitive['08'] = /* translators: Month name, genitive. */
                _x('August', 'genitive');
            $this->month_genitive['09'] = /* translators: Month name, genitive. */
                _x('September', 'genitive');
            $this->month_genitive['10'] = /* translators: Month name, genitive. */
                _x('October', 'genitive');
            $this->month_genitive['11'] = /* translators: Month name, genitive. */
                _x('November', 'genitive');
            $this->month_genitive['12'] = /* translators: Month name, genitive. */
                _x('December', 'genitive');

            // Abbreviations for each month.
            $this->month_abbrev[$this->month['01']] = /* translators: Three-letter abbreviation of the month. */
                _x('Jan', 'January abbreviation');
            $this->month_abbrev[$this->month['02']] = /* translators: Three-letter abbreviation of the month. */
                _x('Feb', 'February abbreviation');
            $this->month_abbrev[$this->month['03']] = /* translators: Three-letter abbreviation of the month. */
                _x('Mar', 'March abbreviation');
            $this->month_abbrev[$this->month['04']] = /* translators: Three-letter abbreviation of the month. */
                _x('Apr', 'April abbreviation');
            $this->month_abbrev[$this->month['05']] = /* translators: Three-letter abbreviation of the month. */
                _x('May', 'May abbreviation');
            $this->month_abbrev[$this->month['06']] = /* translators: Three-letter abbreviation of the month. */
                _x('Jun', 'June abbreviation');
            $this->month_abbrev[$this->month['07']] = /* translators: Three-letter abbreviation of the month. */
                _x('Jul', 'July abbreviation');
            $this->month_abbrev[$this->month['08']] = /* translators: Three-letter abbreviation of the month. */
                _x('Aug', 'August abbreviation');
            $this->month_abbrev[$this->month['09']] = /* translators: Three-letter abbreviation of the month. */
                _x('Sep', 'September abbreviation');
            $this->month_abbrev[$this->month['10']] = /* translators: Three-letter abbreviation of the month. */
                _x('Oct', 'October abbreviation');
            $this->month_abbrev[$this->month['11']] = /* translators: Three-letter abbreviation of the month. */
                _x('Nov', 'November abbreviation');
            $this->month_abbrev[$this->month['12']] = /* translators: Three-letter abbreviation of the month. */
                _x('Dec', 'December abbreviation');

            // The meridiems.
            $this->meridiem['am'] = __('am');
            $this->meridiem['pm'] = __('pm');
            $this->meridiem['AM'] = __('AM');
            $this->meridiem['PM'] = __('PM');

            /*
             * Numbers formatting.
             * See https://www.php.net/number_format
             */

            /* translators: $thousands_sep argument for https://www.php.net/number_format, default is ',' */
            $thousands_sep = __('number_format_thousands_sep');

            // Replace space with a non-breaking space to avoid wrapping.
            $thousands_sep = str_replace(' ', '&nbsp;', $thousands_sep);

            $this->number_format['thousands_sep'] = ('number_format_thousands_sep' === $thousands_sep) ? ',' : $thousands_sep;

            /* translators: $dec_point argument for https://www.php.net/number_format, default is '.' */
            $decimal_point = __('number_format_decimal_point');

            $this->number_format['decimal_point'] = ('number_format_decimal_point' === $decimal_point) ? '.' : $decimal_point;

            /* translators: Used between list items, there is a space after the comma. */
            $this->list_item_separator = __(', ');

            // Set text direction.
            if(isset($GLOBALS['text_direction']))
            {
                $this->text_direction = $GLOBALS['text_direction'];
                /* translators: 'rtl' or 'ltr'. This sets the text direction for WordPress. */
            }
            elseif('rtl' === _x('ltr', 'text direction'))
            {
                $this->text_direction = 'rtl';
            }

            // Set the word count type.
            $this->word_count_type = $this->get_word_count_type();
        }

        public function get_word_count_type()
        {
            /*
             * translators: If your word count is based on single characters (e.g. East Asian characters),
             * enter 'characters_excluding_spaces' or 'characters_including_spaces'. Otherwise, enter 'words'.
             * Do not translate into your own language.
             */
            $word_count_type = is_null($this->word_count_type) ? _x('words', 'Word count type. Do not translate!') : $this->word_count_type;

            // Check for valid types.
            if('characters_excluding_spaces' !== $word_count_type && 'characters_including_spaces' !== $word_count_type)
            {
                // Defaults to 'words'.
                $word_count_type = 'words';
            }

            return $word_count_type;
        }

        public function register_globals()
        {
            $GLOBALS['weekday'] = $this->weekday;
            $GLOBALS['weekday_initial'] = $this->weekday_initial;
            $GLOBALS['weekday_abbrev'] = $this->weekday_abbrev;
            $GLOBALS['month'] = $this->month;
            $GLOBALS['month_abbrev'] = $this->month_abbrev;
        }

        public function get_weekday($weekday_number)
        {
            return $this->weekday[$weekday_number];
        }

        public function get_weekday_initial($weekday_name)
        {
            return $this->weekday_initial[$weekday_name];
        }

        public function get_weekday_abbrev($weekday_name)
        {
            return $this->weekday_abbrev[$weekday_name];
        }

        public function get_month($month_number)
        {
            return $this->month[zeroise($month_number, 2)];
        }

        public function get_month_abbrev($month_name)
        {
            return $this->month_abbrev[$month_name];
        }

        public function get_meridiem($meridiem)
        {
            return $this->meridiem[$meridiem];
        }

        public function is_rtl()
        {
            return 'rtl' === $this->text_direction;
        }

        public function _strings_for_pot()
        {
            /* translators: Localized date format, see https://www.php.net/manual/datetime.format.php */
            __('F j, Y');
            /* translators: Localized time format, see https://www.php.net/manual/datetime.format.php */
            __('g:i a');
            /* translators: Localized date and time format, see https://www.php.net/manual/datetime.format.php */
            __('F j, Y g:i a');
        }

        public function get_list_item_separator()
        {
            return $this->list_item_separator;
        }
    }
