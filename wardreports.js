document.addEventListener("DOMContentLoaded", function () {

    const searchInput = document.getElementById("reportSearch");
    const categoryFilter = document.getElementById("categoryFilter");
    const statusFilter = document.getElementById("statusFilter");
    const tableRows = document.querySelectorAll("#reportsTable tbody tr[data-category]");
    const modal = document.getElementById("reportModal");

    function filterReports() {

        const searchValue = searchInput.value.toLowerCase().trim();
        const categoryValue = categoryFilter.value.toLowerCase().trim();
        const statusValue = statusFilter.value.toLowerCase().trim();

        tableRows.forEach(function (row) {

            const rowText = row.textContent.toLowerCase();
            const rowCategory = (row.dataset.category || "").toLowerCase().trim();
            const rowStatus = (row.dataset.status || "").toLowerCase().trim();

            const matchesSearch = rowText.includes(searchValue);

            const matchesCategory =
                categoryValue === "" ||
                rowCategory === categoryValue;

            const matchesStatus =
                statusValue === "" ||
                rowStatus === statusValue ||
                (
                    statusValue === "in progress" &&
                    (
                        rowStatus === "in-progress" ||
                        rowStatus === "processing"
                    )
                );

            if (matchesSearch && matchesCategory && matchesStatus) {
                row.classList.remove("hidden-row");
            } else {
                row.classList.add("hidden-row");
            }

        });
    }

    if (searchInput) {
        searchInput.addEventListener("input", filterReports);
    }

    if (categoryFilter) {
        categoryFilter.addEventListener("change", filterReports);
    }

    if (statusFilter) {
        statusFilter.addEventListener("change", filterReports);
    }

    window.openReport = function (
        ticket,
        title,
        category,
        description,
        priority,
        address,
        status,
        date
    ) {

        document.getElementById("modalTicket").textContent = ticket || "";
        document.getElementById("modalTitle").textContent = title || "Report";
        document.getElementById("modalCategory").textContent = category || "Not specified";
        document.getElementById("modalDescription").textContent = description || "No description provided.";
        document.getElementById("modalPriority").textContent = priority || "Not specified";
        document.getElementById("modalAddress").textContent = address || "Not specified";
        document.getElementById("modalStatus").textContent = status || "Not specified";
        document.getElementById("modalDate").textContent = date || "Not specified";

        modal.classList.add("show");

        document.body.style.overflow = "hidden";
    };

    window.closeReport = function () {

        modal.classList.remove("show");

        document.body.style.overflow = "";

    };

    modal.addEventListener("click", function (event) {

        if (event.target === modal) {
            closeReport();
        }

    });

    document.addEventListener("keydown", function (event) {

        if (event.key === "Escape") {
            closeReport();
        }

    });

});