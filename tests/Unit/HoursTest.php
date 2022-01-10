<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PromoCode\StoreHours\Hours;

class HoursTest extends TestCase
{
    public function test_default_date_and_time_formats()
    {
        [$hours, $start, $end, $second_start, $second_end] = $this->addSampleHours(new Hours());

        $formatted = $hours->getFormattedHours();

        $expected_time_range = $start->format('g:i') . ' - ' . $end->format('g:ia');

        $joined_time_range = $expected_time_range . ', ' .
            $second_start->format('g:i') . ' - ' . $second_end->format('g:ia');

        $this->assertFormatsMatchForCombiningDays(
            $formatted,
            $expected_time_range,
            $joined_time_range
        );
    }

    public function test_changed_time_formats()
    {
        /** @var Hours $hours */
        [$hours, $start, $end, $second_start, $second_end] =
            $this->addSampleHours(new Hours());

        $hours->setTimeFormat('H:i:s')
            ->setBetweenTimeRangeTemplate(' to ')
            ->setJoinTimeRangesTemplate(' and ');

        $formatted = $hours->getFormattedHours();

        $expected_time_range = $start->format('H:i:s') . ' to ' . $end->format('H:i:s');

        $joined_time_range = $expected_time_range . ' and ' .
            $second_start->format('H:i:s') . ' to ' . $second_end->format('H:i:s');

        $this->assertFormatsMatchForCombiningDays(
            $formatted,
            $expected_time_range,
            $joined_time_range
        );
    }

    public function test_uncombined_days_formatting()
    {
        /** @var Hours $hours */
        [$hours] =
            $this->addSampleHours(new Hours());

        $hours->setCombineConsecutiveDaysWithSameHours(false);

        $formatted = $hours->getFormattedHours();

        $this->assertCount(7, $formatted);

        $this->assertEquals('7:30 - 11:30am', $formatted['Monday']);

        $this->assertEquals('7:30 - 11:30am', $formatted['Tuesday']);

        $this->assertEquals('Closed', $formatted['Thursday']);

        $this->assertEquals(
            '7:30 - 11:30am, 12:30 - 3:30pm',
            $formatted['Friday']
        );

        $hours->setSingleDayTemplate('D');

        $formatted = $hours->getFormattedHours();

        $this->assertCount(7, $formatted);

        $this->assertEquals('7:30 - 11:30am', $formatted['Mon']);

        $this->assertEquals('7:30 - 11:30am', $formatted['Tue']);

        $this->assertEquals('Closed', $formatted['Thu']);

        $this->assertEquals(
            '7:30 - 11:30am, 12:30 - 3:30pm',
            $formatted['Fri']
        );
    }

    private function assertFormatsMatchForCombiningDays(
        array $formatted,
        string $expected_time_range,
        string $joined_time_range
    ): void
    {
        $first_date_range = key($formatted);

        $this->assertEquals('Mon - Wed', $first_date_range);

        $this->assertEquals($expected_time_range, $formatted[$first_date_range]);

        $this->assertEquals('Closed', $formatted['Thu']);

        $this->assertEquals('Closed', $formatted['Sat - Sun']);

        $this->assertEquals($joined_time_range, $formatted['Fri']);
    }

    private function addSampleHours(Hours $hours): array
    {
        $start = new \DateTime('7:30am');

        $end = new \DateTime('11:30am');

        $second_start = new \DateTime('12:30pm');

        $second_end = new \DateTime('3:30pm');

        $hours->addHoursForDay($start, $end, 1);

        $hours->addHoursForDay($start, $end, 2);

        $hours->addHoursForDay($start, $end, 3);

        $hours->addHoursForDay($start, $end, 5);

        $hours->addHoursForDay($second_start, $second_end, 5);

        return [$hours, $start, $end, $second_start, $second_end];
    }
}
