<?php

namespace PromoCode\StoreHours;

class HourSet implements \Countable, \Iterator
{
    private $hours_set = [];

    private $index = 0;

    public function add(\DateTime $start, \DateTime $end)
    {
        $hours_set = $this->hours_set;

        $hours_set[] = compact('start', 'end');

        usort(
            $hours_set,
            function ($hour_range1, $hour_range2) {
                if ($hour_range1['start'] > $hour_range2['start']) {
                    return 1;
                }

                return -1;
            }
        );

        $this->hours_set = $hours_set;
    }

    public function count()
    {
        return count($this->hours_set);
    }

    public function current()
    {
        return $this->hours_set[$this->index];
    }

    public function next()
    {
        $this->index++;
    }

    public function key()
    {
        return $this->index;
    }

    public function valid()
    {
        return isset($this->hours_set[$this->index]);
    }

    public function rewind()
    {
        $this->index = 0;
    }
}
