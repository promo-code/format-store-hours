<?php

namespace PromoCode\StoreHours;

class Hours
{
    public const MONDAY = 1;
    public const TUESDAY = 2;
    public const WEDNESDAY = 3;
    public const THURSDAY = 4;
    public const FRIDAY = 5;
    public const SATURDAY = 6;
    public const SUNDAY = 7;

    public const ABBREVIATED_DAYS = [
        'mon' => 1,
        'tue' => 2,
        'wed' => 3,
        'thu' => 4,
        'fri' => 5,
        'sat' => 6,
        'sun' => 7
    ];

    public const NUMBER_TO_DAY = [
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
        7 => 'Sunday',
    ];

    private $config = [
        'combine_consecutive_days_with_same_hours' => true,
        'start_week_on' => 1,
        'templates' => [
            'single_day' => 'l',
            'between_day_range' => ' - ',
            'day_range_start' => 'D',
            'day_range_end' => 'D',
            'between_time_range' => ' - ',
            'time_range_start' => 'g:i',
            'time_range_start_no_minutes' => 'g',
            'time_range_end' => 'g:ia',
            'time_range_end_no_minutes' => 'ga',
            'join_time_ranges' => ', '
        ]
    ];

    private $hours = [];

    public function addHoursForDay(
        \DateTime $start,
        \DateTime $end,
        $day
    ): self
    {
        if (is_string($day)) {
            $day = self::getDayNumberFromString($day);
        }

        if (!is_int($day)) {
            throw new \InvalidArgumentException(
                'You must supply a string or int representation of a day'
            );
        }

        if (empty($this->hours[$day])) {
            $this->hours[$day] = new HourSet();
        }

        $this->hours[$day]->add(
            $this->handleDateTime($start, $day),
            $this->handleDateTime($end, $day)
        );

        return $this;
    }

    public function getFormattedHours(): array
    {
        $uncombined_days = $this->getFormattedHoursWithoutCombiningDays();

        if (!$this->config['combine_consecutive_days_with_same_hours']) {
            return $uncombined_days;
        }

        $current_day = key($uncombined_days);
        $current_days_hours = current($uncombined_days);

        $index = 1;

        $groups = [
            $index => [
                'hours' => $current_days_hours,
                'days' => [$current_day]
            ]
        ];

        while ($next_days_hours = next($uncombined_days)) {
            $next_day = key($uncombined_days);

            if ($current_days_hours === $next_days_hours) {
                $groups[$index]['days'][] = $next_day;
            }
            else {
                $index++;
                $groups[$index] = [
                    'hours' => $next_days_hours,
                    'days' => [$next_day]
                ];

                $current_days_hours = $next_days_hours;
            }
        }

        $between_days = $this->config['templates']['between_day_range'];

        $final_hours = [];
        foreach ($groups as $days) {
            $start_format = $this->config['templates']['day_range_start'];
            $end_format = $this->config['templates']['day_range_end'];

            $day_label = (new \DateTime(current($days['days'])))
                ->format($start_format);

            if (count($days['days']) > 1) {
                $last_day = new \DateTime(end($days['days']));
                $day_label .= $between_days . $last_day->format($end_format);
            }

            $final_hours[$day_label] = $days['hours'];
        }

        return $final_hours;
    }

    public function getFormattedHoursWithoutCombiningDays(): array
    {
        // This will be the returned array with populated hours.
        $final_hours = [];

        $single_day_label_format = $this->config['templates']['single_day'];

        $first_range_start = $this->config['start_week_on'];

        // Start filling in $final_hours starting with whatever is the
        // starting day up to Sunday.
        // The "Closed" string will get overwritten later if there are actual
        // hours for that particular day.
        foreach (range($first_range_start, 7) as $day_index) {
            $day_label = (new \DateTime(Hours::NUMBER_TO_DAY[$day_index]))
                ->format($single_day_label_format);

            $final_hours[$day_label] = 'Closed';
        }

        // If we did not start on Monday, then we need to fill in the gaps
        // from Monday up to the starting day
        if (1 !== $first_range_start) {
            $second_range_end = $first_range_start - 1;

            foreach (range(1, $second_range_end) as $day_index) {
                $day_label = (new \DateTime(Hours::NUMBER_TO_DAY[$day_index]))
                    ->format($single_day_label_format);

                $final_hours[$day_label] = 'Closed';
            }
        }

        foreach ($this->hours as $day_index => $hours) {
            $day_label = $this->getDateTimeFromDay($day_index)
                ->format($single_day_label_format);

            // Formats the open and close using the templates:
            // E.g. 9:30 - 5:30pm
            $formatted_hours = $this->convertHoursSetsToFormattedString($hours);

            // Overwrites the "Closed" string for the particular day.
            $final_hours[$day_label] = $formatted_hours;
        }

        return $final_hours;
    }

    /**
     * @param \PromoCode\StoreHours\HourSet $hours The opening and closing hours for a particular day
     * @return string The hours joined together based on the template variables
     */
    public function convertHoursSetsToFormattedString(HourSet $hours): string
    {
        $final_hours = [];

        $between_times = $this->config['templates']['between_time_range'];

        foreach ($hours as $hour_set) {
            $start = $this->convertHourToFormattedString($hour_set['start']);

            $end = $this->convertHourToFormattedString(
                $hour_set['end'],
                'end'
            );

            $final_hours[] = "{$start}{$between_times}{$end}";
        }

        return implode(
            $this->config['templates']['join_time_ranges'],
            $final_hours
        );
    }

    public function convertHourToFormattedString(
        \DateTime $time,
        string $start_or_end = 'start'
    ): string {
        $key = 'time_range_' . $start_or_end;
        $format = $this->config['templates'][$key];

        if (0 === (int) $time->format('i')) {
            $format = $this->config['templates'][$key . '_no_minutes'];
        }

        return $time->format($format);
    }

    /**
     * @param string|int $day
     * @return \DateTime
     */
    public function getDateTimeFromDay($day): \DateTime
    {
        if (is_int($day)) {
            $day = Hours::NUMBER_TO_DAY[$day];
        }

        return new \DateTime($day);
    }

    public function setCombineConsecutiveDaysWithSameHours(
        bool $setting_value = true
    ): self {
        $this->config['combine_consecutive_days_with_same_hours'] = $setting_value;

        return $this;
    }

    public function setStartingDayOfWeek(int $setting_value = 1): self
    {
        $this->config['start_week_on'] = $setting_value;

        return $this;
    }

    public function setSingleDayTemplate(string $setting_value = 'l'): self
    {
        $this->config['templates']['single_day'] = $setting_value;

        return $this;
    }

    public function setBetweenDayRangeTemplate(
        string $setting_value = '-'
    ): self
    {
        $this->config['templates']['between_day_range'] = $setting_value;

        return $this;
    }

    public function setDayRangeStartTemplate(string $setting_value = 'D'): self
    {
        $this->config['templates']['day_range_start'] = $setting_value;

        return $this;
    }

    public function setDayRangeEndTemplate(string $setting_value = 'D'): self
    {
        $this->config['templates']['day_range_end'] = $setting_value;

        return $this;
    }

    public function setBetweenTimeRangeTemplate(
        string $setting_value = '-'
    ): self
    {
        $this->config['templates']['between_time_range'] = $setting_value;

        return $this;
    }


    public function setTimeFormat(
        string $setting_value = 'g:i'
    ): self
    {
        foreach ([
                     'time_range_start',
                     'time_range_end',
                     'time_range_start_no_minutes',
                     'time_range_end_no_minutes',
                 ] as $config_key) {
            $this->config['templates'][$config_key] = $setting_value;
        }

        return $this;
    }

    public function setTimeRangeStartTemplate(
        string $setting_value = 'g:i'
    ): self
    {
        $this->config['templates']['time_range_start'] = $setting_value;

        return $this;
    }

    public function setTimeRangeEndTemplate(
        string $setting_value = 'g:ia'
    ): self
    {
        $this->config['templates']['time_range_end'] = $setting_value;

        return $this;
    }

    public function setTimeRangeStartWithoutMinutesTemplate(
        string $setting_value = 'g'
    ): self
    {
        $this->config['templates']['time_range_start_no_minutes'] =
            $setting_value;

        return $this;
    }

    public function setTimeRangeEndWithoutMinutesTemplate(
        string $setting_value = 'ga'
    ): self
    {
        $this->config['templates']['time_range_end_no_minutes'] = $setting_value;

        return $this;
    }

    public function setJoinTimeRangesTemplate(
        string $setting_value = ', '
    ): self
    {
        $this->config['templates']['join_time_ranges'] = $setting_value;

        return $this;
    }

    private function handleDateTime(
        \DateTime $date_time,
        int $day_of_the_week
    ): \DateTime
    {
        // Setting a date for comparison consistency
        $week_of_jan_11_2010 = $date_time->setDate(2010, 1, 11);

        while ((int) $week_of_jan_11_2010->format('N') !== $day_of_the_week) {
            $week_of_jan_11_2010 = $week_of_jan_11_2010->add(
                new \DateInterval('P1D')
            );
        }

        return $week_of_jan_11_2010;
    }

    public static function getDayNumberFromString(string $day_of_week): int
    {
        foreach (self::ABBREVIATED_DAYS as $abbreviation => $day_number) {
            if (false !== stripos($day_of_week, $abbreviation)) {
                return $day_number;
            }
        }

        throw new \InvalidArgumentException(
            'Could not detect the day of the week from ' . $day_of_week
        );
    }

    public static function instance(): self
    {
        return new self();
    }
}
