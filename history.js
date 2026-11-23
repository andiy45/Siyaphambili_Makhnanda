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



function searchNotice() {

    const searchValue = document.getElementById("search").value.trim();

    if (searchValue === "") {
        alert("Please enter something to search.");
        return false;
    }

    alert("Searching for: " + searchValue);

    return true;
}

