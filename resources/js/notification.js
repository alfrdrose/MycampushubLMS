import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css";
import "flatpickr/dist/themes/dark.css";

document.addEventListener("DOMContentLoaded", () => {
    const input = document.getElementById("send_time");
    if (!input) return;

    const picker = flatpickr(input, {
        enableTime: true,
        noCalendar: true,
        dateFormat: "H:i",
        altInput: true,
        altFormat: "h:i K",
        minuteIncrement: 1,
        theme: "dark",

        onChange: () => {
            input.dispatchEvent(new Event("input", { bubbles: true }));
        },
        onValueUpdate: () => {
            input.dispatchEvent(new Event("input", { bubbles: true }));
        }
    });

    const syncDisabled = () => {
        const disabled = input.disabled;
        if (picker.altInput) picker.altInput.disabled = disabled;
        picker.set("clickOpens", !disabled);
        if (disabled) picker.close();
    };

    new MutationObserver(syncDisabled).observe(input, {
        attributes: true,
        attributeFilter: ["disabled"]
    });

    syncDisabled();
});
