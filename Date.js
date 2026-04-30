import dayjs from 'dayjs';

/**
 * @param {dayjs.Dayjs} startDate - The starting reference date
 * @param {number} daysToAdd - Number of business days to add
 * @param {string[]} holidays - Array of ISO date strings (e.g., ['2026-12-25'])
 */
const addBusinessDays = (startDate, daysToAdd, holidays = []) => {
  let date = dayjs(startDate);
  let addedDays = 0;

  while (addedDays < daysToAdd) {
    date = date.add(1, 'day');
    
    const isWeekend = date.day() === 0 || date.day() === 6; // 0 = Sunday, 6 = Saturday
    const isHoliday = holidays.some(h => dayjs(h).isSame(date, 'day'));

    if (!isWeekend && !isHoliday) {
      addedDays++;
    }
  }
  return date;
};

import React, { useState } from 'react';
import { LocalizationProvider } from '@mui/x-date-pickers/LocalizationProvider';
import { AdapterDayjs } from '@mui/x-date-pickers/AdapterDayjs';
import { DatePicker } from '@mui/x-date-pickers/DatePicker';
import dayjs from 'dayjs';

// Example: New Year's Day and a local holiday
const PUBLIC_HOLIDAYS = ['2026-01-01', '2026-05-01']; 

export default function BusinessDatePicker() {
  // Calculate +10 business days from today
  const initialDate = addBusinessDays(dayjs(), 10, PUBLIC_HOLIDAYS);
  
  const [value, setValue] = useState(initialDate);

  return (
    <LocalizationProvider dateAdapter={AdapterDayjs}>
      <DatePicker
        label="Project Deadline (+10 Business Days)"
        value={value}
        onChange={(newValue) => setValue(newValue)}
        // Optional: Disable weekends/holidays in the picker UI too
        shouldDisableDate={(date) => {
          const isWeekend = date.day() === 0 || date.day() === 6;
          const isHoliday = PUBLIC_HOLIDAYS.some(h => dayjs(h).isSame(date, 'day'));
          return isWeekend || isHoliday;
        }}
      />
    </LocalizationProvider>
  );
}
