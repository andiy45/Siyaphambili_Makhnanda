/* Dropdown Logic */
(function () {

    const dropdowns = document.querySelectorAll(".nav-dropdown");
    const body = document.body;


    function closeAll(except) {
        dropdowns.forEach(function (dropdown) {
            if (dropdown !== except) {
                dropdown.classList.remove("is-open");
                const button = dropdown.querySelector(".dropdown-link");

                if (button) {
                    button.setAttribute("aria-expanded", "false");
                }
            }
        });

        if (!document.querySelector(".nav-dropdown.is-open") && !document.querySelector(".nav-dropdown:hover")) {
            body.classList.remove("menu-open");
        }
    }


    dropdowns.forEach(function (dropdown) {
        const trigger = dropdown.querySelector(".dropdown-link");

        if (!trigger) {
            return;
        }


        /* Click dropdown */
        trigger.addEventListener("click", function (event) {
            event.preventDefault();
            event.stopPropagation();
            const isOpen =  dropdown.classList.contains("is-open");
            closeAll(dropdown);
            dropdown.classList.toggle( "is-open", !isOpen );
            trigger.setAttribute(  "aria-expanded", String(!isOpen) );
            body.classList.toggle("menu-open",!isOpen);
        });

        /* Hover */
        dropdown.addEventListener(
            "mouseenter",
            function () {
                body.classList.add("menu-open");
            }
        );

        dropdown.addEventListener(
            "mouseleave",
            function () {
                setTimeout(function () {
                    if (
                        !document.querySelector(
                            ".nav-dropdown:hover"
                        ) &&
                        !document.querySelector(
                            ".nav-dropdown.is-open"
                        )
                    ) {
                        body.classList.remove(
                            "menu-open"
                        );
                    }
                }, 60);
            }
        );
    });

    /* Click outside dropdown */
    document.addEventListener("click",
        function (event) {
            if (!event.target.closest(".nav-dropdown")) {
                closeAll();
            }
        }
    );

    /* Escape key */
    document.addEventListener( "keydown",
        function (event) {
            if (event.key === "Escape") {
                closeAll();
                const mainNav = document.getElementById( "mainNav");

                if (mainNav) {
                    mainNav.classList.remove( "is-open" );
                }
            }
        }
    );

    /* Mobile Menu */
    const toggle = document.getElementById("menuToggle");
    const mainNav = document.getElementById("mainNav");
    if (toggle && mainNav) {
        toggle.addEventListener( "click",
            function (event) {
                event.stopPropagation();

                const open = mainNav.classList.toggle( "is-open");
                toggle.setAttribute(  "aria-expanded", String(open));
            }
        );
    }

    /* Close mobile menu */
    document.addEventListener("click",
        function (event) {
            if (!event.target.closest( ".floating-nav")) {
                if (mainNav) { 
                    mainNav.classList.remove("is-open");
                }

                if (toggle) {
                    toggle.setAttribute( "aria-expanded", "false");
                }
            }
        }
    );
})();

/* Slideshow*/
let slideIndex = 0;
const slides = document.querySelectorAll(".slide");
const dots = document.querySelectorAll(".dot");

function showSlide(index) {

    /* If there are no slides, stop */
    if (slides.length === 0) {
        return;
    }

    /* Go back to first slide */
    if (index >= slides.length) {
        slideIndex = 0;
    }

    /* Go to last slide */
    if (index < 0) {
        slideIndex = slides.length - 1;
    }

    /* Remove active class */
    slides.forEach(function (slide) {
        slide.classList.remove("active");
    });

    dots.forEach(function (dot) {
        dot.classList.remove("active-dot");
    });

    /* Show current slide */
    slides[slideIndex].classList.add("active");

    /* Activate current dot */
    if (dots.length > slideIndex) {
        dots[slideIndex].classList.add("active-dot");
    }
}

/* Previous / next slide */
function changeSlide(direction) {
    slideIndex += direction;
    showSlide(slideIndex);
}

/* Select specific slide */
function currentSlide(index) {
    slideIndex = index;
    showSlide(slideIndex);
}

/* Automatic slideshow */
if (slides.length > 0) {
    showSlide(slideIndex);
    setInterval(function () {
        slideIndex++;
        showSlide(slideIndex);
    }, 5000);
}

/*Calendar */

let currentMonth =  new Date().getMonth();
let currentYear = new Date().getFullYear();
const monthNames = [
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December"
];


function generateCalendar() {
    const calendarMonth = document.getElementById("calendarMonth" );
    const calendarDates = document.getElementById("calendarDates" );

    /* Make sure calendar exists */
    if (!calendarMonth || !calendarDates) {
        console.log("Calendar elements not found.");
        return;
    }

    /* Display month and year */
    calendarMonth.textContent = monthNames[currentMonth] + " " + currentYear;

    /* Clear previous dates */
    calendarDates.innerHTML = "";

    /* Find first day */
    const firstDay = new Date(currentYear,currentMonth,1).getDay();

    /* Find number of days */
    const daysInMonth = new Date(currentYear,currentMonth + 1,0).getDate();

    /* empty days*/
    for (let i = 0;i < firstDay;i++) {
        const emptyDate = document.createElement("span");

        calendarDates.appendChild(emptyDate);
    }

    /* Creating dates */

    for (let day = 1; day <= daysInMonth; day++) {
        const date = document.createElement("span");
        date.textContent = day;

        /* Today's date */
        const today = new Date();

        /* Highlight today */
        if (day === today.getDate() && currentMonth === today.getMonth() && currentYear === today.getFullYear()) {
            date.classList.add("today");
        }
        calendarDates.appendChild(date);
    }
}

/* Previous Months */

function previousMonth() {
    currentMonth--;
    if (currentMonth < 0) {
        currentMonth = 11;
        currentYear--;
    }
    generateCalendar();
}

/* Next Month*/
function nextMonth() {
    currentMonth++
    if (currentMonth > 11) {
        currentMonth = 0;
        currentYear++;
    }
    generateCalendar();
}

/* START CALENDAR */
document.addEventListener("DOMContentLoaded", function () {
     generateCalendar();
    }
); 



/*Weather*/
/* =========================================================
   WEATHER
========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    loadWeather();

});


/* =========================================================
   LOAD WEATHER FROM weather.php
========================================================= */

async function loadWeather() {

    try {

        console.log("Loading weather...");

        const response = await fetch("weather.php?time=" + Date.now());

        if (!response.ok) {

            throw new Error(
                "Weather request failed: " + response.status
            );

        }


        const data = await response.json();


        console.log("Weather data received:", data);


        if (data.error) {

            throw new Error(data.message);

        }


        /* Display the weather */

        displayCurrentWeather(data);

        displayForecast(data);


    } catch (error) {

        console.error("Weather error:", error);

        showWeatherError();

    }

}


/* =========================================================
   DISPLAY CURRENT WEATHER
========================================================= */

function displayCurrentWeather(data) {

    const current = data.current;

    const daily = data.daily;


    /* Current temperature */

    const temperature =
        document.getElementById("current-temperature");

    if (temperature) {

        temperature.textContent =
            Math.round(current.temperature_2m);

    }


    /* Maximum temperature */

    const maxTemperature =
        document.getElementById("max-temperature");

    if (maxTemperature) {

        maxTemperature.textContent =
            Math.round(daily.temperature_2m_max[0]) + "°C";

    }


    /* Minimum temperature */

    const minTemperature =
        document.getElementById("min-temperature");

    if (minTemperature) {

        minTemperature.textContent =
            Math.round(daily.temperature_2m_min[0]) + "°C";

    }


    /* Rain probability */

    const rain =
        document.getElementById("rain-probability");

    if (rain) {

        rain.textContent =
            daily.precipitation_probability_max[0] + "%";

    }


    /* Wind */

    const wind =
        document.getElementById("wind-speed");

    if (wind) {

        wind.textContent =
            Math.round(current.wind_speed_10m) + " km/h";

    }


    /* Weather description */

    const description =
        document.getElementById("weather-description");

    if (description) {

        description.textContent =
            getWeatherDescription(current.weather_code);

    }


    /* Weather icon */

    const icon =
        document.getElementById("weather-icon");

    if (icon) {

        icon.textContent =
            getWeatherIcon(current.weather_code);

    }


    /* Date */

    const weatherDate =
        document.getElementById("weather-date");

    if (weatherDate) {

        const date = new Date();

        weatherDate.textContent =
            date.toLocaleDateString("en-ZA", {

                weekday: "long",

                day: "numeric",

                month: "long",

                year: "numeric"

            });

    }


    /* Hide loading message */

    const loading =
        document.getElementById("weather-loading");

    if (loading) {

        loading.style.display = "none";

    }

}


/* =========================================================
   WEATHER DESCRIPTION
========================================================= */

function getWeatherDescription(code) {

    if (code === 0)
        return "Clear sky";

    if (code === 1)
        return "Mainly clear";

    if (code === 2)
        return "Partly cloudy";

    if (code === 3)
        return "Overcast";

    if (code === 45 || code === 48)
        return "Foggy";

    if (code >= 51 && code <= 57)
        return "Drizzle";

    if (code >= 61 && code <= 67)
        return "Rain";

    if (code >= 71 && code <= 77)
        return "Snow";

    if (code >= 80 && code <= 82)
        return "Rain showers";

    if (code >= 85 && code <= 86)
        return "Snow showers";

    if (code >= 95 && code <= 99)
        return "Thunderstorm";

    return "Weather unavailable";

}


/* =========================================================
   WEATHER ICON
========================================================= */

function getWeatherIcon(code) {

    if (code === 0)
        return "☀️";

    if (code === 1)
        return "🌤️";

    if (code === 2)
        return "⛅";

    if (code === 3)
        return "☁️";

    if (code === 45 || code === 48)
        return "🌫️";

    if (code >= 51 && code <= 57)
        return "🌦️";

    if (code >= 61 && code <= 67)
        return "🌧️";

    if (code >= 71 && code <= 77)
        return "❄️";

    if (code >= 80 && code <= 82)
        return "🌦️";

    if (code >= 85 && code <= 86)
        return "🌨️";

    if (code >= 95 && code <= 99)
        return "⛈️";

    return "🌤️";

}


/* =========================================================
   7-DAY FORECAST
========================================================= */

function displayForecast(data) {

    const container =
        document.getElementById("forecast-container");


    if (!container) {

        console.error(
            "forecast-container was not found."
        );

        return;

    }


    container.innerHTML = "";


    for (
        let i = 0;
        i < data.daily.time.length;
        i++
    ) {


        const date =
            new Date(data.daily.time[i]);


        const day =
            date.toLocaleDateString(
                "en-ZA",
                {
                    weekday: "short"
                }
            );


        const maxTemperature =
            Math.round(
                data.daily.temperature_2m_max[i]
            );


        const minTemperature =
            Math.round(
                data.daily.temperature_2m_min[i]
            );


        const rain =
            data.daily
                .precipitation_probability_max[i];


        const icon =
            getWeatherIcon(
                data.daily.weather_code[i]
            );


        const card =
            document.createElement("div");


        card.className =
            "forecast-card";


        card.innerHTML = `

            <div class="forecast-day">
                ${day}
            </div>

            <div class="forecast-icon">
                ${icon}
            </div>

            <div class="forecast-temperature">
                ${maxTemperature}° / ${minTemperature}°
            </div>

            <div class="forecast-rain">
                💧 ${rain}%
            </div>

        `;


        container.appendChild(card);

    }

}


/* =========================================================
   WEATHER ERROR
========================================================= */

function showWeatherError() {

    const loading =
        document.getElementById("weather-loading");


    if (loading) {

        loading.style.display = "block";

        loading.textContent =
            "Weather information is currently unavailable.";

    }

}

/*Dam Level*/
document.addEventListener("DOMContentLoaded", function () {

    /* Current dam level */
    const damLevel = 72;

    /* Find HTML elements */
    const damPercentage =
        document.getElementById("damPercentage");

    const damProgress =
        document.getElementById("damProgress");

    const damStatus =
        document.getElementById("damStatus");


    /* Check that elements exist */
    if (!damPercentage || !damProgress || !damStatus) {
        console.log("Dam level elements not found.");
        return;
    }


    /* Display percentage */
    damPercentage.textContent =
        damLevel + "%";


    /* Set progress bar width */
    damProgress.style.width =
        damLevel + "%";


    /* Determine dam status */

    if (damLevel >= 70) {

        damStatus.textContent =
            "Stable";

    } else if (damLevel >= 40) {

        damStatus.textContent =
            "Moderate";

    } else {

        damStatus.textContent =
            "Low";
    }

});


function searchNotice() {

    const searchValue = document.getElementById("search").value.trim();

    if (searchValue === "") {
        alert("Please enter something to search.");
        return false;
    }

    alert("Searching for: " + searchValue);

    return true;
}

