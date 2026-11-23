function createCalendar() {

    const calendar = document.getElementById("calendar");
    const today = new Date();
    const year = today.getFullYear();
    const month = today.getMonth();
    const monthName = today.toLocaleString("default", {
        month: "long"});

    // First day of the month
    const firstDay = new Date(year, month, 1).getDay();

    // Number of days in the month
    const daysInMonth = new Date(
        year,month + 1, 0).getDate();


    let html = "";

    html += '<h4>${monthName} ${year}</h4>';

    html += `
        <div class="calendar-days">
            <span>Sun</span>
            <span>Mon</span>
            <span>Tue</span>
            <span>Wed</span>
            <span>Thu</span>
            <span>Fri</span>
            <span>Sat</span>
        </div>
    `;

    html += `<div class="calendar-dates">`;


    // Empty spaces before the first day
    for (let i = 0; i < firstDay; i++) {

        html += `<span></span>`;

    }


    // Create the days
    for (let day = 1; day <= daysInMonth; day++) {

        if (day === today.getDate()) {

            html += `<span class="today">${day}</span>`;

        } else {

            html += `<span>${day}</span>`;

        }

    }


    html += `</div>`;

    calendar.innerHTML = html;
}


createCalendar();