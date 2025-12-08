// This file handles the Calendar component, allowing for date selection using Bootstrap.
// It initializes a simple calendar interface and handles date selection.

document.addEventListener('DOMContentLoaded', function () {
    const calendarContainer = document.getElementById('calendar');
    
    // Create a simple calendar layout
    const calendarHTML = `
        <div class="calendar">
            <div class="calendar-header">
                <button class="btn btn-primary" id="prevMonth">Previous</button>
                <h2 id="monthYear"></h2>
                <button class="btn btn-primary" id="nextMonth">Next</button>
            </div>
            <div class="calendar-days">
                <div class="day">Sun</div>
                <div class="day">Mon</div>
                <div class="day">Tue</div>
                <div class="day">Wed</div>
                <div class="day">Thu</div>
                <div class="day">Fri</div>
                <div class="day">Sat</div>
            </div>
            <div class="calendar-grid" id="calendarGrid"></div>
        </div>
    `;
    
    calendarContainer.innerHTML = calendarHTML;

    const monthYearDisplay = document.getElementById('monthYear');
    const calendarGrid = document.getElementById('calendarGrid');
    let currentDate = new Date();

    function renderCalendar() {
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();
        monthYearDisplay.textContent = `${currentDate.toLocaleString('default', { month: 'long' })} ${year}`;
        
        // Clear previous days
        calendarGrid.innerHTML = '';

        // Get the first day of the month
        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        // Fill in the days
        for (let i = 0; i < firstDay; i++) {
            calendarGrid.innerHTML += '<div class="day empty"></div>';
        }
        for (let day = 1; day <= daysInMonth; day++) {
            calendarGrid.innerHTML += `<div class="day">${day}</div>`;
        }
    }

    document.getElementById('prevMonth').addEventListener('click', function () {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar();
    });

    document.getElementById('nextMonth').addEventListener('click', function () {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar();
    });

    renderCalendar();
});