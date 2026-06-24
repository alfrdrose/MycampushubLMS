@props(['subject', 'gradings'])

<div class="flex-1 flex flex-col p-4 sm:p-10 h-auto bg-whiteColor shadow-lg dark:bg-darkblack rounded-2xl transition-all duration-300 dark:ring-2 dark:ring-blue-500/20 dark:ring-offset-0">

    <!-- Page Header -->
    <div class="flex flex-row items-center">
        <div class="w-1/2 flex justify-start gap-2">
            <a href="{{ route('admin.subjects') }}"
                class="inline-flex items-center text-primaryColor hover:opacity-90 transition dark:text-blue-400">
                <i class="icofont-arrow-left text-3xl"></i>
            </a>

            <h1 class="text-2xl sm:text-2xl font-bold text-[#5E5F60] dark:text-white">
                {{ $subject->subject_name }}
            </h1>
        </div>

        <div class="w-1/2 flex justify-end">
            <p class="text-sm sm:text-base text-primaryColor">
                Grade {{ $subject->grade_level }}
            </p>
        </div>
    </div>

    <hr class="border-gray-300 dark:border-gray-600 my-4">


    {{-- ================= EXISTING GRADING DISPLAY ================= --}}
    @if(!$gradings->isEmpty())

        <div id="existingGradingBox" class="mt-4">

            <div class="overflow-x-auto border border-gray-300 dark:border-gray-700">
                <table class="min-w-full text-sm">
                    <thead class="bg-[#03A9F4] text-white">
                        <tr>
                            <th class="py-3 px-4 text-center">Classwork Type</th>
                            <th class="py-3 px-4 text-center">Percentage</th>
                        </tr>
                    </thead>

                    <tbody class="bg-white dark:bg-darkblack text-center text-[#373737] dark:text-white">

                        @php $total = 0; @endphp

                        @foreach ($gradings as $g)
                            @php $total += $g->percentage; @endphp
                            <tr class="border-b dark:border-gray-700">
                                <td class="py-2 px-4">{{ $g->classwork_type }}</td>
                                <td class="py-2 px-4">{{ $g->percentage }}%</td>
                            </tr>
                        @endforeach

                        <tr class="font-bold">
                            <td class="py-2 px-4 text-primaryColor">TOTAL</td>
                            <td class="py-2 px-4 {{ $total == 100 ? 'text-primaryColor' : 'text-red-500' }}">
                                {{ $total }}%
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="w-full flex justify-end mt-4 gap-2">
                <button id="clearGradingBtn"
                    class="py-2 border-red-500 border px-4 text-sm rounded-lg text-red-500 hover:bg-red-500 hover:text-white transition flex items-center gap-2">
                    <i class="icofont-ui-delete"></i>
                    Clear Grading
                </button>
                <button id="editConfigBtn"
                    class="py-2 border-primaryColor border px-4 text-sm rounded-lg text-primaryColor hover:bg-primaryColor hover:text-white transition flex items-center gap-2">
                    <i class="icofont-edit"></i>
                    Edit Configuration
                </button>
            </div>


        </div>

    @endif



    {{-- ================= EMPTY STATE ================= --}}
    @if ($gradings->isEmpty())
        <div id="noGradingBox"
            class="w-full p-4 mt-4 text-center text-gray-500 dark:text-gray-300 border border-dashed border-gray-300 dark:border-gray-600 rounded-lg">
            Grading has not been configured for this subject.
        </div>

        <div class="w-full flex justify-end mt-4">
            <button id="openConfigBtn"
                class="py-2 border-primaryColor border px-4 rounded-lg text-primaryColor hover:bg-primaryColor hover:text-white transition flex items-center gap-2">
                <i class="icofont-settings"></i>
                Configure
            </button>
        </div>
    @endif



    {{-- ================= CONFIG TABLE ================= --}}
    <div id="gradingConfig" class="hidden mt-6">
        
        <div class="overflow-x-auto border border-gray-300 dark:border-gray-700">
            <table class="min-w-full text-sm">
                <thead class="bg-[#03A9F4] text-white">
                    <tr>
                        <th class="py-3 px-4 text-center">Classwork Type</th>
                        <th class="py-3 px-4 text-center">Percentage</th>
                        <th class="py-3 px-4 text-center">Action</th>
                    </tr>
                </thead>
                <tbody id="gradingTableBody" class="bg-white dark:bg-darkblack">
                </tbody>
            </table>
        </div>

        <div class="flex justify-between items-center mt-4">
            <button id="addRowBtn"
                class="px-4 py-2 rounded-lg border border-primaryColor text-primaryColor hover:bg-primaryColor hover:text-white transition">
                + Add Classwork
            </button>

            <div class="text-center">
                <p class="text-gray-600 dark:text-gray-200">
                    Total:
                    <span id="totalPercent" class="font-bold text-primaryColor">0%</span>
                </p>
                <p class="text-gray-600 dark:text-gray-200">
                    Remaining:
                    <span id="remainingPercent" class="font-bold">100%</span>
                </p>
            </div>
        </div>

        <div class="flex justify-end mt-6 gap-3">
            <button id="cancelBtn"
                class="px-6 py-2 rounded-lg border border-gray-400 text-gray-600 hover:bg-gray-200 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 transition">
                Cancel
            </button>

            <button id="saveBtn"
                disabled
                class="px-6 py-2 rounded-lg bg-gray-400 text-white cursor-not-allowed transition">
                Save
            </button>
        </div>

    </div>

</div>



<!-- CONFIRM SAVE MODAL -->
<div id="confirmModal"
     class="hidden fixed inset-0 flex items-center justify-center bg-black/50 z-50">

    <div class="bg-white dark:bg-darkblack p-6 rounded-xl w-[90%] sm:w-[400px]">

        <h2 class="text-lg font-bold text-[#5E5F60] dark:text-white mb-2">
            Confirm Save
        </h2>

        <p class="text-gray-600 dark:text-gray-300 mb-4">
            Are you sure you want to save this grading setup?
        </p>

        <div class="flex justify-end gap-3">
            <button id="modalCancel"
                    class="px-4 py-2 rounded-lg border border-gray-400 text-gray-700 hover:bg-gray-200 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                Cancel
            </button>

            <button id="modalConfirm"
                    class="px-4 py-2 rounded-lg bg-primaryColor text-white hover:opacity-90">
                Confirm
            </button>
        </div>
    </div>
</div>

<!-- CLEAR GRADE MODAL -->
<div id="clearModal"
     class="hidden fixed inset-0 flex items-center justify-center bg-black/50 z-50">

    <div class="bg-white dark:bg-darkblack p-6 rounded-xl w-[90%] sm:w-[400px]">

        <h2 class="text-lg font-bold text-[#5E5F60] dark:text-white mb-2">
            Clear Grading?
        </h2>

        <p class="text-gray-600 dark:text-gray-300 mb-6">
            This will remove all grading components for this subject. 
            Are you sure?
        </p>

        <div class="flex justify-end gap-3">
            <button id="clearCancel"
                class="px-4 py-2 rounded-lg border border-gray-400 text-gray-700 hover:bg-gray-200 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                Cancel
            </button>

            <button id="clearConfirm"
                class="px-4 py-2 rounded-lg bg-red-500 text-white hover:bg-red-400">
                Yes, Clear
            </button>
        </div>

    </div>
</div>




<script>
document.addEventListener("DOMContentLoaded", () => {

    const openBtn = document.getElementById("openConfigBtn");
    const editBtn = document.getElementById("editConfigBtn");
    const existingBox = document.getElementById("existingGradingBox");

    const configBox = document.getElementById("gradingConfig");
    const noGradingBox = document.getElementById("noGradingBox");
    const cancelBtn = document.getElementById("cancelBtn");

    const tableBody = document.getElementById("gradingTableBody");
    const addRowBtn = document.getElementById("addRowBtn");

    const totalPercent = document.getElementById("totalPercent");
    const remainingPercent = document.getElementById("remainingPercent");
    const saveBtn = document.getElementById("saveBtn");

    const confirmModal = document.getElementById("confirmModal");
    const modalCancel = document.getElementById("modalCancel");
    const modalConfirm = document.getElementById("modalConfirm");

    const clearBtn = document.getElementById("clearGradingBtn");
    const clearModal = document.getElementById("clearModal");
    const clearCancel = document.getElementById("clearCancel");
    const clearConfirm = document.getElementById("clearConfirm");

    let rowId = 0;

    const allTypes = [
        "Written Works",
        "Performance Task",
        "Quarterly Assessment",
        "Attendance"
    ];

    @if(!$gradings->isEmpty())
        const existingData = @json($gradings);
    @endif


    function openConfig() {
        if (noGradingBox) noGradingBox.classList.add("hidden");
        if (existingBox) existingBox.classList.add("hidden");

        configBox.classList.remove("hidden");

        if (openBtn) openBtn.classList.add("hidden");
        if (editBtn) editBtn.classList.add("hidden");

        tableBody.innerHTML = "";

        @if(!$gradings->isEmpty())
            existingData.forEach(item => addRow(item.classwork_type, item.percentage));
        @endif
    }

    if (openBtn) openBtn.addEventListener("click", openConfig);
    if (editBtn) editBtn.addEventListener("click", openConfig);

    addRowBtn.addEventListener("click", () => addRow());


    function getSelectedTypes() {
        return Array.from(document.querySelectorAll(".typeSelect"))
            .map(select => select.value)
            .filter(v => v !== "");
    }


    function updateSelectOptions() {
        const selected = getSelectedTypes();

        document.querySelectorAll(".typeSelect").forEach(select => {
            const currentValue = select.value;

            select.innerHTML = `<option value="">Select Type</option>`;

            allTypes.forEach(type => {
                if (!selected.includes(type) || type === currentValue) {
                    const option = document.createElement("option");
                    option.value = type;
                    option.textContent = type;
                    if (type === currentValue) {
                        option.selected = true;
                    }
                    select.appendChild(option);
                }
            });
        });
    }


    function addRow(type = "", percent = 0) {
        rowId++;

        const row = document.createElement("tr");
        row.classList.add("border-b", "dark:border-gray-700");

        row.innerHTML = `
            <td class="py-2 px-4 text-center">
                <select class="typeSelect w-full px-3 py-2 text-center rounded border dark:bg-darkblack dark:text-white"></select>
            </td>

            <td class="py-2 px-4 text-center">
                <input type="number"
                    value="${percent}"
                    min="0"
                    max="100"
                    class="percentInput w-24 px-3 py-2 text-center rounded border dark:bg-darkblack dark:text-white" />
            </td>

            <td class="py-2 px-4 text-center">
                <button class="removeBtn text-red-500 hover:text-red-400">
                    <i class="icofont-ui-delete text-xl"></i>
                </button>
            </td>
        `;

        tableBody.appendChild(row);

        const select = row.querySelector(".typeSelect");

        // Build full option list first
        select.innerHTML = `<option value="">Select Type</option>`;
        allTypes.forEach(t => {
            const option = document.createElement("option");
            option.value = t;
            option.textContent = t;
            select.appendChild(option);
        });

        // NOW restore selected value (this is the important fix)
        if (type) {
            select.value = type;
        }

        row.querySelector(".percentInput").addEventListener("input", updateTotals);

        select.addEventListener("change", () => {
            updateSelectOptions();
                updateTotals();
            });

            row.querySelector(".removeBtn").addEventListener("click", () => {
                row.remove();
                updateSelectOptions();
                updateTotals();
            });

            updateSelectOptions();
            updateTotals();
    }


    function hasZeroPercent() {
        let zero = false;

        document.querySelectorAll(".percentInput").forEach(input => {
            if (parseFloat(input.value) === 0) {
                zero = true;
            }
        });

        return zero;
    }


    function inputsFilled() {
        let ok = true;

        document.querySelectorAll("#gradingTableBody tr").forEach(row => {
            const type = row.querySelector(".typeSelect");
            const percent = row.querySelector(".percentInput");

            if (!type.value.trim()) ok = false;
            if (percent.value === "" || percent.value === null) ok = false;
        });

        return ok;
    }


    function updateTotals() {
        let total = 0;

        document.querySelectorAll(".percentInput").forEach(input => {
            total += parseFloat(input.value) || 0;
        });

        totalPercent.textContent = total + "%";
        remainingPercent.textContent = (100 - total) + "%";

        if (total > 100) {
            totalPercent.classList.remove("text-primaryColor");
            totalPercent.classList.add("text-red-500");
            remainingPercent.classList.add("text-red-500");
        } else {
            totalPercent.classList.add("text-primaryColor");
            totalPercent.classList.remove("text-red-500");
            remainingPercent.classList.remove("text-red-500");
        }

        const filled = inputsFilled();
        const zero = hasZeroPercent();

        if (total === 100 && filled && !zero) {
            saveBtn.disabled = false;
            saveBtn.classList.remove("bg-gray-400", "cursor-not-allowed");
            saveBtn.classList.add("bg-primaryColor");
        } else {
            saveBtn.disabled = true;
            saveBtn.classList.add("bg-gray-400", "cursor-not-allowed");
            saveBtn.classList.remove("bg-primaryColor");
        }
    }


    function resetTotals() {
        totalPercent.textContent = "0%";
        remainingPercent.textContent = "100%";
        totalPercent.classList.add("text-primaryColor");
        totalPercent.classList.remove("text-red-500");
        remainingPercent.classList.remove("text-red-500");
        saveBtn.disabled = true;
        saveBtn.classList.add("bg-gray-400", "cursor-not-allowed");
        saveBtn.classList.remove("bg-primaryColor");
    }


    cancelBtn.addEventListener("click", () => {
        configBox.classList.add("hidden");

        if (existingBox) existingBox.classList.remove("hidden");
        if (noGradingBox) noGradingBox.classList.remove("hidden");

        if (openBtn) openBtn.classList.remove("hidden");
        if (editBtn) editBtn.classList.remove("hidden");

        resetTotals();
    });


    saveBtn.addEventListener("click", () => {
        confirmModal.classList.remove("hidden");
    });

    modalCancel.addEventListener("click", () => {
        confirmModal.classList.add("hidden");
    });

    modalConfirm.addEventListener("click", () => {

        window.AdminModalLoading?.setLoading(modalConfirm, { text: "Saving..." });

        const rows = [];

        document.querySelectorAll("#gradingTableBody tr").forEach(row => {
            rows.push({
                classwork_type: row.querySelector(".typeSelect").value,
                percentage: parseFloat(row.querySelector(".percentInput").value)
            });
        });

        fetch("{{ route('admin.subject.grading.store', $subject->id) }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name=\"csrf-token\"]').content
            },
            body: JSON.stringify({ items: rows })
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                window.AdminModalLoading?.clearLoading(modalConfirm);
                alert(data.message);
                return;
            }
            location.reload();
        });

        confirmModal.classList.add("hidden");
    });


    if (clearBtn) {
        clearBtn.addEventListener("click", () => {
            clearModal.classList.remove("hidden");
        });
    }

    clearCancel.addEventListener("click", () => {
        clearModal.classList.add("hidden");
    });

    clearConfirm.addEventListener("click", () => {
        window.AdminModalLoading?.setLoading(clearConfirm, { text: "Clearing..." });

        fetch("{{ route('admin.subject.grading.clear', $subject->id) }}", {
            method: "DELETE",
            headers: {
                "X-CSRF-TOKEN": document.querySelector('meta[name=\"csrf-token\"]').content
            }
        })
        .then(res => res.json())
        .then(data => {
            if(!data.success){
                window.AdminModalLoading?.clearLoading(clearConfirm);
                alert("Failed to clear grading.");
                return;
            }
            location.reload();
        });

        clearModal.classList.add("hidden");
    });

});
</script>
