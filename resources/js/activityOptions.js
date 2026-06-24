console.log("Activity Options Script Loaded");

function isActivityOptionsPage() {
    return Boolean(
        document.getElementById("activity_title")
        && document.getElementById("updateUrl")
    );
}

/* =========================================================
   UTIL
========================================================= */
function disableAndClear(inputs, disabled) {
    inputs.forEach(i => {
        if (!i) return;

        if (disabled) {
            i.value = "";
            i.setAttribute("disabled", "disabled");
        } else {
            i.removeAttribute("disabled");
        }
    });

    resizeExpandableSections();
}

/* =========================================================
   VISIBILITY DROPDOWN
========================================================= */
document.addEventListener("DOMContentLoaded", () => {
    if (!isActivityOptionsPage()) return;

    const header = document.getElementById("visibilityHeader");
    const content = document.getElementById("visibilityContent");
    const arrow = document.getElementById("visibilityArrow");

    if (!header) return;

    let open = false;

    content.style.maxHeight = "0px";
    content.style.overflow = "hidden";
    arrow.classList.remove("rotate-180");

    header.addEventListener("click", () => {
        open = !open;

        if (open) {
            content.style.maxHeight = content.scrollHeight + "px";
            content.style.overflow = "visible";
            arrow.classList.add("rotate-180");
        } else {
            content.style.maxHeight = "0px";
            content.style.overflow = "hidden";
            
            arrow.classList.remove("rotate-180");
        }
    });
});

/* =========================================================
   FILE SETTINGS TOGGLE
========================================================= */
document.addEventListener("DOMContentLoaded", () => {
    if (!isActivityOptionsPage()) return;

    const allowFiles = document.getElementById("allow_files");
    const fileSettings = document.getElementById("fileSettings");

    const maxFiles = document.getElementById("max_files");
    const maxSize  = document.getElementById("max_size");

    if (!allowFiles) return;

    function toggleFileSettings() {
        if (allowFiles.checked) {
            fileSettings.classList.remove("hidden");
            disableAndClear([maxFiles, maxSize], false);
        } else {
            fileSettings.classList.add("hidden");
            disableAndClear([maxFiles, maxSize], true);
        }
    }

    toggleFileSettings();
    allowFiles.addEventListener("change", toggleFileSettings);
});

/* =========================================================
   TEXT SETTINGS TOGGLE
========================================================= */
document.addEventListener("DOMContentLoaded", () => {
    if (!isActivityOptionsPage()) return;

    const allowText = document.getElementById("allow_text");
    const textSettings = document.getElementById("textSettings");

    const minWords = document.getElementById("min_words");
    const maxWords = document.getElementById("max_words");

    if (!allowText) return;

    function toggleTextSettings() {
        if (allowText.checked) {
            textSettings.classList.remove("hidden");
            disableAndClear([minWords, maxWords], false);
        } else {
            textSettings.classList.add("hidden");
            disableAndClear([minWords, maxWords], true);
        }
    }

    toggleTextSettings();
    allowText.addEventListener("change", toggleTextSettings);
});

/* =========================================================
   SUBMISSION DROPDOWN
========================================================= */
document.addEventListener("DOMContentLoaded", () => {
    if (!isActivityOptionsPage()) return;

    const subHeader = document.getElementById("submissionHeader");
    const subContent = document.getElementById("submissionContent");
    const subArrow  = document.getElementById("submissionArrow");

    if (!subHeader) return;

    let subOpen = false;

    subContent.style.maxHeight = "0px";
    subContent.style.overflow = "hidden";
    subArrow.classList.remove("rotate-180");

    function resizeSubmission() {
        if (subOpen) {
            subContent.style.maxHeight = subContent.scrollHeight + "px";
            subContent.style.overflow = "visible";
        }
    }

    subHeader.addEventListener("click", () => {
        subOpen = !subOpen;

        if (subOpen) {
            subContent.style.maxHeight = subContent.scrollHeight + "px";
            subContent.style.overflow = "visible";
            subArrow.classList.add("rotate-180");
        } else {
            subContent.style.maxHeight = "0px";
            subContent.style.overflow = "hidden";
            subArrow.classList.remove("rotate-180");
        }
    });

    const allowFiles = document.getElementById("allow_files");
    const allowText  = document.getElementById("allow_text");

    if (allowFiles) allowFiles.addEventListener("change", resizeSubmission);
    if (allowText)  allowText.addEventListener("change", resizeSubmission);
});

/* =========================================================
   RESTRICTIONS DROPDOWN
========================================================= */
document.addEventListener("DOMContentLoaded", () => {
    if (!isActivityOptionsPage()) return;

    const resHeader = document.getElementById("restrictionHeader");
    const resContent = document.getElementById("restrictionContent");
    const resArrow  = document.getElementById("restrictionArrow");

    if (!resHeader) return;

    let resOpen = false;

    resContent.style.maxHeight = "0px";
    resContent.style.overflow = "hidden";
    resArrow.classList.remove("rotate-180");

    resHeader.addEventListener("click", () => {
        resOpen = !resOpen;

        if (resOpen) {
            resContent.style.maxHeight = resContent.scrollHeight + "px";
            resContent.style.overflow = "visible";
            resArrow.classList.add("rotate-180");
        } else {
            resContent.style.maxHeight = "0px";
            resContent.style.overflow = "hidden";
            resArrow.classList.remove("rotate-180");
        }
    });
});

/* =========================================================
   ACTIVITY TAGS
========================================================= */
document.addEventListener("DOMContentLoaded", () => {
    if (!isActivityOptionsPage()) return;

    const tagsHeader = document.getElementById("activityTagsHeader");
    const tagsContent = document.getElementById("activityTagsContent");
    const tagsArrow = document.getElementById("activityTagsArrow");

    if (!tagsHeader || !tagsContent || !tagsArrow) return;

    let tagsOpen = false;

    const ensureTagsAnimator = () => {
        if (tagsContent.dataset.sectionAnimatorReady === "1") return;
        tagsContent.dataset.sectionAnimatorReady = "1";

        tagsContent.addEventListener("transitionend", (event) => {
            if (event.propertyName !== "max-height") return;
            if (!tagsOpen) return;

            tagsContent.style.maxHeight = "none";
            tagsContent.style.overflow = "visible";
        });
    };

    const resizeTagsSection = () => {
        if (!tagsOpen) return;
        if (tagsContent.style.maxHeight !== "none") {
            tagsContent.style.maxHeight = tagsContent.scrollHeight + "px";
        }
    };

    const setTagsState = () => {
        ensureTagsAnimator();
        if (tagsOpen) {
            tagsContent.style.paddingBottom = "";
            tagsContent.style.overflow = "hidden";
            tagsContent.style.maxHeight = tagsContent.scrollHeight + "px";
        } else {
            if (tagsContent.style.maxHeight === "none") {
                tagsContent.style.maxHeight = tagsContent.scrollHeight + "px";
                void tagsContent.offsetHeight;
            }
            tagsContent.style.maxHeight = "0px";
            tagsContent.style.overflow = "hidden";
            tagsContent.style.paddingBottom = "0px";
        }
        tagsArrow.classList.toggle("rotate-180", !tagsOpen);
    };

    setTagsState();

    tagsHeader.addEventListener("click", () => {
        tagsOpen = !tagsOpen;
        setTagsState();
    });

    const escapeHtml = (value) => {
        return String(value ?? "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#39;");
    };

const tagInputsContainer = document.getElementById('activityTagInputs');
        const tagPills = document.getElementById('activityTagPills');
        const noTagsMessage = document.getElementById('activityNoTagsMessage');
        const openTagsModalBtn = document.getElementById('activityOpenTagsModal');
        const clearTagsBtn = document.getElementById('activityClearTagsBtn');
        const tagsModal = document.getElementById('activityTagsModal');
        const closeTagsModalBtn = document.getElementById('activityCloseTagsModal');
        const confirmTagsModalBtn = document.getElementById('activityConfirmTagsModal');
        const tagSearchInput = document.getElementById('activityTagSearchInput');
        const tagListContainer = document.getElementById('activityTagListContainer');
        const tagListEmpty = document.getElementById('activityTagListEmpty');
        const tagPagination = document.getElementById('activityTagPagination');
        const tagPrevPageBtn = document.getElementById('activityTagPrevPage');
        const tagNextPageBtn = document.getElementById('activityTagNextPage');
        const tagPageInfo = document.getElementById('activityTagPageInfo');
        const openCategoryPickerBtn = document.getElementById('activityOpenCategoryPicker');
        const currentCategoryLabel = document.getElementById('activityCurrentCategoryLabel');
        const backToTagsBtn = document.getElementById('activityBackToTags');
        const tagCategoryPicker = document.getElementById('activityTagCategoryPicker');
        const categoryPagination = document.getElementById('activityCategoryPagination');
        const categoryPrevPageBtn = document.getElementById('activityCategoryPrevPage');
        const categoryNextPageBtn = document.getElementById('activityCategoryNextPage');
        const categoryPageInfo = document.getElementById('activityCategoryPageInfo');
        const categoryListEmpty = document.getElementById('activityCategoryListEmpty');
        const metricModeRelevancyBtn = document.getElementById('activityModeRelevancyBtn');
        const metricModeCoverageBtn = document.getElementById('activityModeCoverageBtn');
        const metricModeSlider = document.getElementById('activityMetricModeSlider');
        const tagRelevancyContainer = document.getElementById('activityTagRelevancyContainer');
        const tagMetricsEditor = document.getElementById('activityTagMetricsEditor');
        const relevancyPagination = document.getElementById('activityRelevancyPagination');
        const relevancyPrevPageBtn = document.getElementById('activityRelevancyPrevPage');
        const relevancyNextPageBtn = document.getElementById('activityRelevancyNextPage');
        const relevancyPageInfo = document.getElementById('activityRelevancyPageInfo');
        const derivedTagNote = document.getElementById('activityTagDerivedNote');

        const tagOptions = Array.from(document.querySelectorAll('.activity-tag-option'));
        const tagCheckboxes = Array.from(document.querySelectorAll('.activity-tag-checkbox'));
        const categoryOptions = Array.from(document.querySelectorAll('.activity-tag-category-option'));

        const tagsPerPage = 6;
        const categoriesPerPage = 6;
        const relevancyCardsPerPage = 3;
        let currentTagPage = 1;
        let currentCategoryPage = 1;
        let currentRelevancyPage = 1;
        let filteredTagOptions = [...tagOptions];
        let filteredCategoryOptions = [...categoryOptions];
        let currentCategory = '';
        let isCategoryMode = false;
        let isRelevancyMode = false;
        let currentMetricMode = 'relevance';
        let focusedMetricTagId = null;
        let isApplyingTagAction = false;
        let derivedTagIds = new Set();
        const minTagLoadingMs = 250;
        const categoryActiveClasses = ['bg-[#F3EEFF]', 'dark:bg-[#34255F]', 'border-[#5F2DED]', 'text-[#5F2DED]', 'dark:text-white'];
        const categoryInactiveClasses = ['bg-transparent', 'border-transparent', 'text-[#5E5F60]', 'dark:text-white'];
        const relevanceLevels = ['very_high', 'high', 'medium', 'low', 'very_low'];
        const relevanceScale = ['very_low', 'low', 'medium', 'high', 'very_high'];
        const relevanceLabels = {
            very_high: 'Very High',
            high: 'High',
            medium: 'Medium',
            low: 'Low',
            very_low: 'Very Low',
        };
        const relevanceRank = {
            very_high: 0,
            high: 1,
            medium: 2,
            low: 3,
            very_low: 4,
        };
        const relevancePillToneClasses = {
            very_high: 'tag-pill-tone-very-high',
            high: 'tag-pill-tone-high',
            medium: 'tag-pill-tone-medium',
            low: 'tag-pill-tone-low',
            very_low: 'tag-pill-tone-very-low',
        };
        const defaultRelevance = 'medium';
        const tagMetrics = {};
    window.activityTagMetrics = tagMetrics;
        const selectedTagIds = new Set(
            Array.from(tagInputsContainer?.querySelectorAll('input[name="tag_ids[]"]') || [])
                .map((input) => String(input.value))
        );

        const getCheckedTagIds = () => {
            return tagCheckboxes
                .filter((checkbox) => checkbox.checked)
                .map((checkbox) => String(checkbox.value));
        };

        const getMetricTargetTagIds = () => {
            if (focusedMetricTagId && selectedTagIds.has(focusedMetricTagId)) {
                return [focusedMetricTagId];
            }
            return getCheckedTagIds();
        };

        const getTagNameById = (tagId) => {
            const option = tagOptions.find((item) => item.dataset.tagId === String(tagId));
            return option?.dataset.tagName || 'Concept';
        };

        const normalizeRelevance = (value) => {
            const normalized = String(value || '').trim().toLowerCase();
            if (relevanceLevels.includes(normalized)) {
                return normalized;
            }
            return relevanceLevels.includes(defaultRelevance) ? defaultRelevance : 'medium';
        };

        const normalizeCoverage = (value) => {
            if (value === null || value === undefined || value === '') {
                return '5';
            }
            const parsed = Number(value);
            if (!Number.isFinite(parsed)) {
                return '5';
            }
            const bounded = Math.min(100, Math.max(5, parsed));
            const snapped = Math.round(bounded / 5) * 5;
            return String(snapped);
        };

        tagOptions.forEach((option) => {
            const tagId = String(option.dataset.tagId || '').trim();
            if (!tagId) return;
            tagMetrics[tagId] = {
                relevance: normalizeRelevance(option.dataset.tagRelevance),
                coverage: normalizeCoverage(option.dataset.tagCoverage),
            };
        });

        const syncTagInputs = () => {
            if (!tagInputsContainer) return;
            tagInputsContainer.innerHTML = '';
            Array.from(selectedTagIds).forEach((tagId) => {
                if (!tagMetrics[tagId]) {
                    tagMetrics[tagId] = {
                        relevance: normalizeRelevance(defaultRelevance),
                        coverage: '5',
                    };
                }

                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'tag_ids[]';
                input.value = tagId;
                tagInputsContainer.appendChild(input);

                const relevanceInput = document.createElement('input');
                relevanceInput.type = 'hidden';
                relevanceInput.name = `tag_relevance[${tagId}]`;
                relevanceInput.value = tagMetrics[tagId].relevance;
                tagInputsContainer.appendChild(relevanceInput);

                const coverageInput = document.createElement('input');
                coverageInput.type = 'hidden';
                coverageInput.name = `tag_coverage[${tagId}]`;
                coverageInput.value = tagMetrics[tagId].coverage;
                tagInputsContainer.appendChild(coverageInput);
            });
        };

        const updateDerivedTagNote = () => {
            if (!derivedTagNote) return;
            if (derivedTagIds.size > 0) {
                derivedTagNote.classList.remove('hidden');
            } else {
                derivedTagNote.classList.add('hidden');
            }
        };

        const renderTagPills = () => {
            if (!tagPills) return;
            tagPills.innerHTML = '';
            const tagEntries = Array.from(selectedTagIds)
                .map((tagId) => {
                    const metric = tagMetrics[tagId] || {
                        relevance: normalizeRelevance(defaultRelevance),
                        coverage: '5',
                    };
                    const relevance = normalizeRelevance(metric.relevance);
                    const coverage = Number.parseInt(normalizeCoverage(metric.coverage), 10);
                    const normalizedCoverage = Number.isFinite(coverage) ? coverage : 5;
                    return {
                        id: tagId,
                        name: getTagNameById(tagId),
                        relevance,
                        coverage: normalizedCoverage,
                        relevanceRank: relevanceRank[relevance] ?? relevanceRank.medium,
                    };
                })
                .sort((a, b) => {
                    if (a.relevanceRank !== b.relevanceRank) {
                        return a.relevanceRank - b.relevanceRank;
                    }
                    if (a.coverage !== b.coverage) {
                        return b.coverage - a.coverage;
                    }
                    return a.name.localeCompare(b.name);
                });

            if (tagEntries.length === 0) {
                noTagsMessage?.classList.remove('hidden');
                updateDerivedTagNote();
            window.activityCommittedTagIds = Array.from(selectedTagIds).map((id) => Number.parseInt(id, 10)).filter((value) => Number.isInteger(value));
                return;
            }

            noTagsMessage?.classList.add('hidden');
            tagEntries.forEach((tag) => {
                const pill = document.createElement('button');
                pill.type = 'button';
                const toneClass = relevancePillToneClasses[tag.relevance] || relevancePillToneClasses.medium;
                pill.className = `tag-pill inline-flex items-center gap-1.5 px-3 py-1 rounded-full border transition ${toneClass}`;
                pill.dataset.tagId = String(tag.id);
                pill.title = 'Edit relevance';
                pill.innerHTML = `
                    <span class="text-sm">${escapeHtml(tag.name)}</span>
                    <span class="ml-1 text-[10px] leading-none opacity-80">${tag.coverage}%</span>
                    <span class="tag-pill-remove text-xs leading-none opacity-70 hover:opacity-100 hover:text-red-500">x</span>
                `;
                tagPills.appendChild(pill);
            });
            updateDerivedTagNote();
            window.activityCommittedTagIds = Array.from(selectedTagIds).map((id) => Number.parseInt(id, 10)).filter((value) => Number.isInteger(value));
        };

        const clearTagSelection = () => {
            selectedTagIds.clear();
            focusedMetricTagId = null;
            tagCheckboxes.forEach((checkbox) => {
                checkbox.checked = false;
            });
            syncTagInputs();
            renderTagPills();
        };

        const setTagSelections = (tagIds) => {
            selectedTagIds.clear();
            tagIds.forEach((tagId) => {
                if (tagId !== null && tagId !== '') {
                    selectedTagIds.add(String(tagId));
                }
            });
            if (focusedMetricTagId && !selectedTagIds.has(focusedMetricTagId)) {
                focusedMetricTagId = null;
            }
            tagCheckboxes.forEach((checkbox) => {
                checkbox.checked = selectedTagIds.has(String(checkbox.value));
            });
            syncTagInputs();
            renderTagPills();
        };

        if (selectedTagIds.size > 0) {
            syncTagInputs();
            renderTagPills();
        }

        const toggleTagsModal = (show) => {
            if (!tagsModal) return;
            tagsModal.classList.toggle('hidden', !show);
            tagsModal.classList.toggle('flex', show);
            document.body.classList.toggle('modal-open', show);
        };

        const updateSearchPlaceholder = () => {
            if (!tagSearchInput) return;
            if (isRelevancyMode) {
                tagSearchInput.placeholder = 'Search selected tags...';
                return;
            }
            tagSearchInput.placeholder = isCategoryMode ? 'Search categories...' : 'Search tags...';
        };

        const setMetricModeSwitchState = () => {
            const isCoverageMode = currentMetricMode === 'coverage';
            if (metricModeSlider) {
                metricModeSlider.style.opacity = isRelevancyMode ? '1' : '0';
                metricModeSlider.style.transform = isCoverageMode ? 'translateX(100%)' : 'translateX(0)';
            }

            const setButtonState = (button, isActive) => {
                if (!button) return;
                button.classList.toggle('text-white', isRelevancyMode && isActive);
                button.classList.toggle('text-[#5E5F60]', !(isRelevancyMode && isActive));
                button.classList.toggle('dark:text-gray-300', !(isRelevancyMode && isActive));
            };

            setButtonState(metricModeRelevancyBtn, !isCoverageMode);
            setButtonState(metricModeCoverageBtn, isCoverageMode);
        };

        const getRelevancyTotalPages = (selectedIds) => {
            if (!Array.isArray(selectedIds) || selectedIds.length === 0) {
                return 0;
            }
            return Math.ceil(selectedIds.length / relevancyCardsPerPage);
        };

        const getFilteredRelevancyTagIds = (selectedIds) => {
            if (!Array.isArray(selectedIds)) {
                return [];
            }
            const term = (tagSearchInput?.value || '').trim().toLowerCase();
            if (!term) {
                return selectedIds;
            }

            return selectedIds.filter((id) => {
                const tagId = String(id);
                const option = tagOptions.find((item) => item.dataset.tagId === tagId);
                const name = String(option?.dataset.tagName || '').toLowerCase();
                const category = String(option?.dataset.tagCategory || '').toLowerCase();
                return name.includes(term) || category.includes(term);
            });
        };

        const syncRelevancyPaginationState = (totalPages) => {
            if (!relevancyPagination || !relevancyPrevPageBtn || !relevancyNextPageBtn || !relevancyPageInfo) {
                return;
            }

            const hasPages = totalPages > 0;
            const clampedPages = hasPages ? totalPages : 1;
            const page = Math.min(clampedPages, Math.max(1, currentRelevancyPage));
            currentRelevancyPage = page;

            relevancyPageInfo.textContent = `Page ${page} of ${clampedPages}`;
            relevancyPrevPageBtn.disabled = page <= 1;
            relevancyNextPageBtn.disabled = page >= clampedPages;
            relevancyPagination.classList.toggle('hidden', !isRelevancyMode || totalPages <= 1);
        };

        const setCategoryButtonState = (button, isActive) => {
            if (!button) return;
            if (isActive) {
                categoryInactiveClasses.forEach((cls) => button.classList.remove(cls));
                categoryActiveClasses.forEach((cls) => button.classList.add(cls));
                return;
            }
            categoryActiveClasses.forEach((cls) => button.classList.remove(cls));
            categoryInactiveClasses.forEach((cls) => button.classList.add(cls));
        };

        const refreshCategoryButtonStates = () => {
            const selectedCategory = String(currentCategory || '').trim().toLowerCase();
            categoryOptions.forEach((button) => {
                const buttonCategory = String(button.dataset.tagCategory || '').trim().toLowerCase();
                setCategoryButtonState(button, buttonCategory === selectedCategory);
            });
        };

        const setTagActionLoading = (button, fallbackText) => {
            if (!button) return;
            if (window.AdminModalLoading && typeof window.AdminModalLoading.setLoading === 'function') {
                window.AdminModalLoading.setLoading(button, {
                    text: button.dataset.loadingText || fallbackText,
                });
                return;
            }
            button.disabled = true;
            button.classList.add('opacity-75', 'cursor-wait');
        };

        const clearTagActionLoading = (button) => {
            if (!button) return;
            if (window.AdminModalLoading && typeof window.AdminModalLoading.clearLoading === 'function') {
                window.AdminModalLoading.clearLoading(button);
                return;
            }
            button.disabled = false;
            button.classList.remove('opacity-75', 'cursor-wait');
        };

        const finishTagAction = (triggerBtn, startedAt) => {
            const elapsed = Date.now() - startedAt;
            const remaining = Math.max(0, minTagLoadingMs - elapsed);

            window.setTimeout(() => {
                clearTagActionLoading(triggerBtn);
                if (confirmTagsModalBtn && triggerBtn !== confirmTagsModalBtn) {
                    confirmTagsModalBtn.disabled = false;
                }
                if (clearTagsBtn && triggerBtn !== clearTagsBtn) {
                    clearTagsBtn.disabled = false;
                }
                isApplyingTagAction = false;
            }, remaining);
        };

        const toggleCategoryPicker = (show) => {
            if (isRelevancyMode) {
                return;
            }
            isCategoryMode = show;
            tagCategoryPicker?.classList.toggle('hidden', !show);
            tagListContainer?.classList.toggle('hidden', show);
            tagRelevancyContainer?.classList.add('hidden');
            backToTagsBtn?.classList.toggle('hidden', !(show || isRelevancyMode));
            if (tagPagination) {
                tagPagination.classList.toggle('hidden', show);
            }
            if (categoryPagination) {
                categoryPagination.classList.toggle('hidden', !show);
            }
            categoryListEmpty?.classList.add('hidden');
            updateSearchPlaceholder();
            if (show) refreshCategoryButtonStates();
        };

        const toggleRelevancyMode = (show, mode = currentMetricMode) => {
            currentMetricMode = mode === 'coverage' ? 'coverage' : 'relevance';
            isRelevancyMode = show;

            if (show) {
                currentRelevancyPage = 1;
                isCategoryMode = false;
                tagCategoryPicker?.classList.add('hidden');
                tagListContainer?.classList.add('hidden');
                tagRelevancyContainer?.classList.remove('hidden');
                backToTagsBtn?.classList.remove('hidden');
                tagPagination?.classList.add('hidden');
                categoryPagination?.classList.add('hidden');
                categoryListEmpty?.classList.add('hidden');
                tagListEmpty?.classList.add('hidden');
                renderTagMetricsEditor(getMetricTargetTagIds());
            } else {
                tagRelevancyContainer?.classList.add('hidden');
                syncRelevancyPaginationState(0);
                toggleCategoryPicker(false);
                filterTags();
            }

            updateSearchPlaceholder();
            setMetricModeSwitchState();
    window.activityCommittedTagIds = Array.from(selectedTagIds).map((id) => Number.parseInt(id, 10)).filter((value) => Number.isInteger(value));
        };

        const getTagTotalPages = () => {
            if (filteredTagOptions.length === 0) return 0;
            return Math.ceil(filteredTagOptions.length / tagsPerPage);
        };

        const getCategoryTotalPages = () => {
            if (filteredCategoryOptions.length === 0) return 0;
            return Math.ceil(filteredCategoryOptions.length / categoriesPerPage);
        };

        const applyTagPagination = () => {
            if (tagOptions.length === 0) {
                tagListEmpty?.classList.add('hidden');
                if (tagPagination) tagPagination.classList.add('hidden');
                return;
            }

            const totalPages = getTagTotalPages();
            if (currentTagPage > totalPages) {
                currentTagPage = totalPages || 1;
            }

            const start = (currentTagPage - 1) * tagsPerPage;
            const end = start + tagsPerPage;
            const visibleOptions = new Set(filteredTagOptions.slice(start, end));

            tagOptions.forEach((option) => {
                option.classList.toggle('hidden', !visibleOptions.has(option));
            });

            if (tagPageInfo) {
                tagPageInfo.textContent = totalPages > 0
                    ? `Page ${currentTagPage} of ${totalPages}`
                    : 'Page 0 of 0';
            }
            if (tagPagination) {
                tagPagination.classList.toggle('hidden', totalPages <= 1);
            }
            if (tagPrevPageBtn) {
                tagPrevPageBtn.disabled = currentTagPage <= 1;
            }
            if (tagNextPageBtn) {
                tagNextPageBtn.disabled = currentTagPage >= totalPages;
            }
            if (tagListEmpty) {
                tagListEmpty.classList.toggle('hidden', filteredTagOptions.length > 0);
            }
        };

        const applyCategoryPagination = () => {
            if (categoryOptions.length === 0) {
                categoryPagination?.classList.add('hidden');
                categoryListEmpty?.classList.add('hidden');
                return;
            }

            const totalPages = getCategoryTotalPages();
            if (currentCategoryPage > totalPages) {
                currentCategoryPage = totalPages || 1;
            }

            const start = (currentCategoryPage - 1) * categoriesPerPage;
            const end = start + categoriesPerPage;
            const visibleOptions = new Set(filteredCategoryOptions.slice(start, end));

            categoryOptions.forEach((option) => {
                option.classList.toggle('hidden', !visibleOptions.has(option));
            });

            if (categoryPageInfo) {
                categoryPageInfo.textContent = totalPages > 0
                    ? `Page ${currentCategoryPage} of ${totalPages}`
                    : 'Page 0 of 0';
            }
            if (categoryPagination) {
                categoryPagination.classList.toggle('hidden', totalPages <= 1);
            }
            if (categoryPrevPageBtn) {
                categoryPrevPageBtn.disabled = currentCategoryPage <= 1;
            }
            if (categoryNextPageBtn) {
                categoryNextPageBtn.disabled = currentCategoryPage >= totalPages;
            }
            if (categoryListEmpty) {
                categoryListEmpty.classList.toggle('hidden', filteredCategoryOptions.length > 0);
            }
            refreshCategoryButtonStates();
        };

        const filterTags = () => {
            const query = (tagSearchInput?.value || '').toLowerCase().trim();
            const categoryValue = currentCategory.toLowerCase();
            filteredTagOptions = tagOptions.filter((option) => {
                const name = (option.dataset.tagName || '').toLowerCase();
                const category = (option.dataset.tagCategory || '').toLowerCase();
                const matchesCategory = !categoryValue || category === categoryValue;
                const matchesQuery = !query || name.includes(query) || category.includes(query);
                return matchesCategory && matchesQuery;
            });

            currentTagPage = 1;
            applyTagPagination();
        };

        const filterCategories = () => {
            const query = (tagSearchInput?.value || '').toLowerCase().trim();
            filteredCategoryOptions = categoryOptions.filter((option) => {
                const label = (option.dataset.tagCategory || 'All categories').toLowerCase();
                return !query || label.includes(query);
            });
            currentCategoryPage = 1;
            applyCategoryPagination();
        };

        const renderTagMetricsEditor = (selectedIds) => {
            if (!tagMetricsEditor) return;

            if (!selectedIds.length) {
                tagMetricsEditor.innerHTML = '<p class="text-xs text-gray-500 dark:text-gray-300">Select at least one tag to configure relevance and coverage.</p>';
                syncRelevancyPaginationState(0);
                return;
            }

            const filteredSelectedIds = getFilteredRelevancyTagIds(selectedIds);
            if (!filteredSelectedIds.length) {
                tagMetricsEditor.innerHTML = '<p class="text-xs text-gray-500 dark:text-gray-300">No selected tags match your search.</p>';
                syncRelevancyPaginationState(0);
                return;
            }

            const totalPages = getRelevancyTotalPages(filteredSelectedIds);
            if (currentRelevancyPage > totalPages) {
                currentRelevancyPage = totalPages || 1;
            }

            const start = (currentRelevancyPage - 1) * relevancyCardsPerPage;
            const end = start + relevancyCardsPerPage;
            const visibleSelectedIds = filteredSelectedIds.slice(start, end);

            const rows = visibleSelectedIds.map((id) => {
                const tagId = String(id);
                const option = tagOptions.find((item) => item.dataset.tagId === tagId);
                const tagName = option?.dataset.tagName || 'Tag';
                const metric = tagMetrics[tagId] || {
                    relevance: normalizeRelevance(defaultRelevance),
                    coverage: '5',
                };
                const currentRelevance = normalizeRelevance(metric.relevance);
                const relevanceIndex = Math.max(0, relevanceScale.indexOf(currentRelevance));
                const currentCoverage = normalizeCoverage(metric.coverage);
                const isCoverageMode = currentMetricMode === 'coverage';

                return `
                    <div class="border border-[#94A3B8] dark:border-[#475569] rounded-none p-3 transition-colors hover:bg-[#EFF6FF] dark:hover:bg-[#12243D] focus-within:bg-[#DBEAFE] dark:focus-within:bg-[#1E3A5F]">
                        <p class="text-sm text-[#373737] dark:text-white font-medium">${escapeHtml(tagName)}</p>
                        <div class="mt-2 ${isCoverageMode ? 'hidden' : ''}" data-metric-relevance-panel="${tagId}">
                            <div class="flex items-center gap-3">
                                <div class="flex-1" style="padding-right: 0.5in;">
                                    <input type="range"
                                        min="0" max="4" step="1"
                                        value="${relevanceIndex}"
                                        data-metric-relevance-slider="${tagId}"
                                        class="tag-gradient-slider w-full">
                                </div>
                                <span data-metric-relevance-current="${tagId}" style="width: 100px; flex: 0 0 100px;" class="shrink-0 whitespace-nowrap text-right text-sm font-semibold font-poppins text-[#334155] dark:text-white">
                                    ${escapeHtml(relevanceLabels[currentRelevance] || 'Medium')}
                                </span>
                            </div>
                        </div>
                        <div class="mt-2 ${isCoverageMode ? '' : 'hidden'}" data-metric-coverage-panel="${tagId}">
                            <div class="flex items-center gap-3">
                                <div class="flex-1" style="padding-right: 0.5in;">
                                    <input type="range"
                                        min="5" max="100" step="5"
                                        value="${currentCoverage}"
                                        data-metric-coverage-slider="${tagId}"
                                        class="tag-gradient-slider w-full">
                                </div>
                                <span data-metric-coverage-current="${tagId}" style="width: 100px; flex: 0 0 100px;" class="shrink-0 whitespace-nowrap text-right text-sm font-semibold font-poppins text-[#334155] dark:text-white">
                                    ${currentCoverage}%
                                </span>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');

            tagMetricsEditor.innerHTML = rows;

            tagMetricsEditor.querySelectorAll('[data-metric-relevance-slider]').forEach((element) => {
                element.addEventListener('input', (event) => {
                    const target = event.target;
                    if (!(target instanceof HTMLInputElement)) return;
                    const tagId = String(target.getAttribute('data-metric-relevance-slider') || '');
                    if (!tagId) return;
                    const rawIndex = Number.parseInt(target.value, 10);
                    const clampedIndex = Number.isFinite(rawIndex)
                        ? Math.min(4, Math.max(0, rawIndex))
                        : 0;
                    target.value = String(clampedIndex);
                    const relevanceValue = normalizeRelevance(relevanceScale[clampedIndex] || defaultRelevance);

                    if (!tagMetrics[tagId]) {
                        tagMetrics[tagId] = { relevance: normalizeRelevance(defaultRelevance), coverage: '5' };
                    }
                    tagMetrics[tagId].relevance = relevanceValue;
                    const relevanceLabel = tagMetricsEditor.querySelector(`[data-metric-relevance-current="${tagId}"]`);
                    if (relevanceLabel) {
                        relevanceLabel.textContent = relevanceLabels[relevanceValue] || 'Medium';
                    }
                    syncTagInputs();
                    renderTagPills();
                });
            });

            tagMetricsEditor.querySelectorAll('[data-metric-coverage-slider]').forEach((element) => {
                element.addEventListener('input', (event) => {
                    const target = event.target;
                    if (!(target instanceof HTMLInputElement)) return;
                    const tagId = String(target.getAttribute('data-metric-coverage-slider') || '');
                    if (!tagId) return;
                    if (!tagMetrics[tagId]) {
                        tagMetrics[tagId] = { relevance: normalizeRelevance(defaultRelevance), coverage: '5' };
                    }
                    const normalizedCoverage = normalizeCoverage(target.value);
                    target.value = normalizedCoverage;
                    tagMetrics[tagId].coverage = normalizedCoverage;
                    const coverageLabel = tagMetricsEditor.querySelector(`[data-metric-coverage-current="${tagId}"]`);
                    if (coverageLabel) {
                        coverageLabel.textContent = `${normalizedCoverage}%`;
                    }
                    syncTagInputs();
                    renderTagPills();
                });
            });

            syncRelevancyPaginationState(totalPages);
        };

        const updateTagErrorState = () => {
            const tagError = document.getElementById('reviewerTagError');
            const count = document.querySelectorAll(
                '#activityTagInputs input[name="tag_ids[]"]'
            ).length;

            if (count > 0) {
                tagError?.classList.add('hidden');
            }
        };

        const runTagAction = (triggerBtn, fallbackText, action) => {
            if (isApplyingTagAction) return;
            isApplyingTagAction = true;
            const actionStartAt = Date.now();

            setTagActionLoading(triggerBtn, fallbackText);
            if (confirmTagsModalBtn && triggerBtn !== confirmTagsModalBtn) {
                confirmTagsModalBtn.disabled = true;
            }
            if (clearTagsBtn && triggerBtn !== clearTagsBtn) {
                clearTagsBtn.disabled = true;
            }

            requestAnimationFrame(() => {
                try {
                    action();
                } finally {
                    finishTagAction(triggerBtn, actionStartAt);
                }
            });
        };

        openTagsModalBtn?.addEventListener('click', () => {
            focusedMetricTagId = null;
            if (tagSearchInput) {
                tagSearchInput.value = '';
            }
            toggleRelevancyMode(false);
            toggleCategoryPicker(false);
            filterTags();
            tagCheckboxes.forEach((checkbox) => {
                checkbox.checked = selectedTagIds.has(String(checkbox.value));
            });
            toggleTagsModal(true);
        });

        closeTagsModalBtn?.addEventListener('click', () => {
            focusedMetricTagId = null;
            toggleRelevancyMode(false);
            toggleCategoryPicker(false);
            toggleTagsModal(false);
        });

        confirmTagsModalBtn?.addEventListener('click', () => {
            runTagAction(confirmTagsModalBtn, 'Saving...', () => {
                const selected = tagCheckboxes
                    .filter((checkbox) => checkbox.checked)
                    .map((checkbox) => checkbox.value);
                setTagSelections(selected);
                updateTagErrorState();
                renderTagMetricsEditor(Array.from(selectedTagIds));
                focusedMetricTagId = null;
                toggleCategoryPicker(false);
                toggleRelevancyMode(false);
                toggleTagsModal(false);
            });
        });

        tagSearchInput?.addEventListener('input', () => {
            if (isRelevancyMode) {
                currentRelevancyPage = 1;
                renderTagMetricsEditor(getMetricTargetTagIds());
                return;
            }
            if (isCategoryMode) {
                filterCategories();
            } else {
                filterTags();
            }
        });

        tagPrevPageBtn?.addEventListener('click', () => {
            if (currentTagPage <= 1) return;
            currentTagPage -= 1;
            applyTagPagination();
        });

        tagNextPageBtn?.addEventListener('click', () => {
            const totalPages = getTagTotalPages();
            if (currentTagPage >= totalPages) return;
            currentTagPage += 1;
            applyTagPagination();
        });

        categoryPrevPageBtn?.addEventListener('click', () => {
            if (currentCategoryPage <= 1) return;
            currentCategoryPage -= 1;
            applyCategoryPagination();
        });

        categoryNextPageBtn?.addEventListener('click', () => {
            const totalPages = getCategoryTotalPages();
            if (currentCategoryPage >= totalPages) return;
            currentCategoryPage += 1;
            applyCategoryPagination();
        });

        metricModeRelevancyBtn?.addEventListener('click', () => {
            toggleRelevancyMode(true, 'relevance');
        });

        metricModeCoverageBtn?.addEventListener('click', () => {
            toggleRelevancyMode(true, 'coverage');
        });

        relevancyPrevPageBtn?.addEventListener('click', () => {
            if (currentRelevancyPage <= 1) return;
            currentRelevancyPage -= 1;
            renderTagMetricsEditor(getMetricTargetTagIds());
        });

        relevancyNextPageBtn?.addEventListener('click', () => {
            const totalPages = getRelevancyTotalPages(getFilteredRelevancyTagIds(getMetricTargetTagIds()));
            if (currentRelevancyPage >= totalPages) return;
            currentRelevancyPage += 1;
            renderTagMetricsEditor(getMetricTargetTagIds());
        });

        openCategoryPickerBtn?.addEventListener('click', () => {
            if (isRelevancyMode) {
                toggleRelevancyMode(false);
            }
            currentCategoryPage = 1;
            toggleCategoryPicker(true);
            filterCategories();
        });

        backToTagsBtn?.addEventListener('click', () => {
            if (isRelevancyMode) {
                focusedMetricTagId = null;
                toggleRelevancyMode(false);
                return;
            }
            toggleCategoryPicker(false);
            filterTags();
        });

        categoryOptions.forEach((button) => {
            button.addEventListener('click', () => {
                currentCategory = button.dataset.tagCategory || '';
                if (currentCategoryLabel) {
                    currentCategoryLabel.textContent = currentCategory || 'All categories';
                }
                refreshCategoryButtonStates();
                toggleCategoryPicker(false);
                filterTags();
            });
        });

        tagPills?.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof Element)) return;
            const pill = target.closest('.tag-pill');
            if (!pill) return;
            const tagId = String(pill.dataset.tagId || '');
            if (!tagId) return;

            const removeBtn = target.closest('.tag-pill-remove');
            if (removeBtn) {
                selectedTagIds.delete(tagId);
                if (focusedMetricTagId === tagId) {
                    focusedMetricTagId = null;
                }
                tagCheckboxes.forEach((checkbox) => {
                    checkbox.checked = selectedTagIds.has(String(checkbox.value));
                });
                syncTagInputs();
                renderTagPills();
                return;
            }

            if (!selectedTagIds.has(tagId)) {
                return;
            }

            focusedMetricTagId = tagId;
            currentMetricMode = 'relevance';
            currentRelevancyPage = 1;
            if (tagSearchInput) {
                tagSearchInput.value = '';
            }
            toggleCategoryPicker(false);
            filterTags();
            tagCheckboxes.forEach((checkbox) => {
                checkbox.checked = selectedTagIds.has(String(checkbox.value));
            });
            toggleTagsModal(true);
            toggleRelevancyMode(true, 'relevance');
        });

        tagCheckboxes.forEach((checkbox) => {
            checkbox.addEventListener('change', () => {
                if (!isRelevancyMode) return;
                currentRelevancyPage = 1;
                renderTagMetricsEditor(getMetricTargetTagIds());
            });
        });

        clearTagsBtn?.addEventListener('click', () => {
            runTagAction(clearTagsBtn, 'Clearing...', () => {
                clearTagSelection();
            });
        });
    window.addEventListener('resize', resizeTagsSection);
});

/* =========================================================
   AUTO RESIZE EXPANDABLE SECTIONS
========================================================= */
function resizeExpandableSections() {
    const sections = [
        document.getElementById("visibilityContent"),
        document.getElementById("submissionContent"),
        document.getElementById("restrictionContent")
    ];

    sections.forEach(sec => {
        if (!sec) return;
        if (sec.style.maxHeight !== "0px") {
            sec.style.maxHeight = sec.scrollHeight + "px";
            sec.style.overflow = "visible";
        }
    });
}

/* =========================================================
   ERROR HANDLING
========================================================= */
function clearErrors() {
    document.querySelectorAll(".error").forEach(e => e.remove());
    resizeExpandableSections();
}

function showError(input, message) {
    let box = input.parentElement.querySelector(".error");

    if (!box) {
        box = document.createElement("p");
        box.classList.add("error", "text-red-500", "text-sm", "mt-1");
        input.parentElement.appendChild(box);
    }

    box.innerHTML = message;
    resizeExpandableSections();
}

/* =========================================================
   VISIBILITY REQUIRED DATE/TIME + AUTO CLOSE LOGIC
========================================================= */
document.addEventListener("DOMContentLoaded", () => {
    if (!isActivityOptionsPage()) return;

    const visibleToggle = document.getElementById("is_visible");

    const openDate = document.getElementById("open_date");
    const openTime = document.getElementById("open_time");
    const dueDate  = document.getElementById("due_date");
    const dueTime  = document.getElementById("due_time");

    const hiddenOpenStatus = document.getElementById("is_open_status");

    if (!visibleToggle) return;

    function handleVisibleToggle() {
        disableAndClear(
            [openDate, openTime, dueDate, dueTime],
            !visibleToggle.checked
        );

        // AUTO FORCE is_open = 0 IF NOT VISIBLE
        if (!visibleToggle.checked && hiddenOpenStatus) {
            hiddenOpenStatus.value = 0;
        }
    }

    handleVisibleToggle();
    visibleToggle.addEventListener("change", handleVisibleToggle);
});

/* =========================================================
   VALIDATION
========================================================= */
function validateBeforeSave() {
    if (!isActivityOptionsPage()) return true;

    clearErrors();
    let valid = true;

    const visible = document.getElementById("is_visible")?.checked ?? false;
    const allowFiles = document.getElementById("allow_files")?.checked ?? false;

    const openDate = document.getElementById("open_date");
    const openTime = document.getElementById("open_time");
    const dueDate = document.getElementById("due_date");
    const dueTime = document.getElementById("due_time");

    const maxFiles = document.getElementById("max_files");
    const maxSize = document.getElementById("max_size");

    const totalScore = document.getElementById("total_score");
    const maxAttempt = document.getElementById("max_attempt");

    if (visible) {
        if (!openDate?.value) { if (openDate) showError(openDate, "Open date is required."); valid = false; }
        if (!openTime?.value) { if (openTime) showError(openTime, "Open time is required."); valid = false; }
        if (!dueDate?.value) { if (dueDate) showError(dueDate, "Due date is required."); valid = false; }
        if (!dueTime?.value) { if (dueTime) showError(dueTime, "Due time is required."); valid = false; }
    }

    if (allowFiles) {
        const maxFilesValue = Number(maxFiles?.value ?? 0);
        const maxSizeValue = Number(maxSize?.value ?? 0);

        if (!maxFiles?.value || maxFilesValue < 1) {
            if (maxFiles) showError(maxFiles, "Maximum number of files must be 1 or more.");
            valid = false;
        }

        if (!maxSize?.value || maxSizeValue <= 0 || maxSizeValue > 50) {
            if (maxSize) showError(maxSize, "Maximum submission size must be between 1MB and 50MB.");
            valid = false;
        }
    }

    const totalScoreValue = Number(totalScore?.value ?? 0);
    if (!totalScore?.value || totalScoreValue < 1) {
        if (totalScore) showError(totalScore, "Total score must be 1 or more.");
        valid = false;
    }

    const maxAttemptValue = Number(maxAttempt?.value ?? 0);
    if (!maxAttempt?.value || maxAttemptValue < 1) {
        if (maxAttempt) showError(maxAttempt, "Maximum attempts must be 1 or more.");
        valid = false;
    }

    // DUE DATE MUST NOT BE EARLIER THAN OPEN DATE
    if (visible && openDate?.value && openTime?.value && dueDate?.value && dueTime?.value) {
        const openDT = new Date(`${openDate.value}T${openTime.value}`);
        const dueDT  = new Date(`${dueDate.value}T${dueTime.value}`);

        if (dueDT < openDT) {
            if (dueDate) showError(dueDate, "Due date cannot be earlier than open date.");
            if (dueTime) showError(dueTime, "Due time cannot be earlier than open time.");
            valid = false;
        }
    }

    return valid;
}

if (isActivityOptionsPage()) {
    document.querySelectorAll("input").forEach(i => {
        i.addEventListener("input", () => {
            const box = i.parentElement?.querySelector(".error");
            if (box) box.remove();
            resizeExpandableSections();
        });
    });
}

/* =========================================================
   SAVE MODAL
========================================================= */
const confirmModal = document.getElementById("confirmSaveModal");
const cancelSaveBtn = document.getElementById("cancelSaveBtn");
const confirmSaveBtn = document.getElementById("confirmSaveBtn");
const openSaveModalBtn = document.getElementById("openSaveModal");
const openSaveModalBtnDefaultText = openSaveModalBtn?.textContent?.trim() || "Save";

const activitySaveContextReady = Boolean(
    isActivityOptionsPage()
    && confirmModal
    && cancelSaveBtn
    && confirmSaveBtn
    && openSaveModalBtn
);

if (activitySaveContextReady) {
    openSaveModalBtn.addEventListener("click", function () {
        if (!validateBeforeSave()) return;

        confirmModal.classList.remove("hidden");
        confirmModal.classList.add("flex");
    });

    cancelSaveBtn.addEventListener("click", () => {
        confirmModal.classList.add("hidden");
    });

    /* =========================================================
       FINAL SAVE REQUEST (WITH LOADING)
    ========================================================= */
    confirmSaveBtn.addEventListener("click", function () {

        // loading state
        const spinner = document.getElementById("saveLoadingSpinner");
        const btnText = document.getElementById("saveBtnText");
        if (!spinner || !btnText) return;

        confirmSaveBtn.disabled = true;
        spinner.classList.remove("hidden");
        btnText.textContent = "Saving...";
        cancelSaveBtn.disabled = true;

        if (openSaveModalBtn) {
            openSaveModalBtn.disabled = true;
            openSaveModalBtn.classList.add("opacity-75", "cursor-wait");
            openSaveModalBtn.textContent = "Saving...";
        }

        confirmModal.classList.add("hidden");

        const url = document.getElementById("updateUrl")?.value;
        if (!url) {
            console.error("Missing activity update URL.");
            alert("Something went wrong while saving.");
            confirmSaveBtn.disabled = false;
            spinner.classList.add("hidden");
            btnText.textContent = "Save";
            cancelSaveBtn.disabled = false;
            if (openSaveModalBtn) {
                openSaveModalBtn.disabled = false;
                openSaveModalBtn.classList.remove("opacity-75", "cursor-wait");
                openSaveModalBtn.textContent = openSaveModalBtnDefaultText;
            }
            return;
        }

        const formData = new FormData();

        formData.append("title", document.getElementById("activity_title")?.value ?? "");
        formData.append("description", document.getElementById("activity_description")?.value ?? "");

        formData.append("open_date", document.getElementById("open_date")?.value ?? "");
        formData.append("open_time", document.getElementById("open_time")?.value ?? "");
        formData.append("due_date", document.getElementById("due_date")?.value ?? "");
        formData.append("due_time", document.getElementById("due_time")?.value ?? "");

        const visible = document.getElementById("is_visible")?.checked ? 1 : 0;
        formData.append("visible", visible);

        const openHidden = document.getElementById("is_open_status");
        formData.append("is_open", visible ? (openHidden?.value ?? 0) : 0);

        formData.append("allow_files", document.getElementById("allow_files")?.checked ? 1 : 0);
        formData.append("max_files", document.getElementById("max_files")?.value ?? "");
        formData.append("max_size", document.getElementById("max_size")?.value ?? "");

        formData.append("allow_text", document.getElementById("allow_text")?.checked ? 1 : 0);
        formData.append("min_words", document.getElementById("min_words")?.value ?? "");
        formData.append("max_words", document.getElementById("max_words")?.value ?? "");

        formData.append("total_score", document.getElementById("total_score")?.value ?? "");
        formData.append("accepting", document.getElementById("accepting")?.value ?? "");
        formData.append("max_attempt", document.getElementById("max_attempt")?.value ?? "");

        const selectedTagIds = Array.isArray(window.activityCommittedTagIds) ? window.activityCommittedTagIds : [];
        selectedTagIds.forEach((tagId) => {
            const tagKey = String(tagId);
            formData.append("tag_ids[]", tagKey);
            const metrics = (window.activityTagMetrics && window.activityTagMetrics[tagKey]) || {};
            const rawRelevance = String(metrics.relevance || "medium").toLowerCase();
            const relevance = ["very_high", "high", "medium", "low", "very_low"].includes(rawRelevance)
                ? rawRelevance
                : "medium";
            const coverageValue = Number(metrics.coverage);
            const coverage = Number.isFinite(coverageValue)
                ? String(Math.round(Math.min(100, Math.max(5, coverageValue)) / 5) * 5)
                : "";
            formData.append(`tag_relevance[${tagKey}]`, relevance);
            formData.append(`tag_coverage[${tagKey}]`, coverage);
        });

        const stagedUploadFiles = Array.isArray(window.uploadedFiles) ? window.uploadedFiles : [];
        if (stagedUploadFiles.length > 0) {
            stagedUploadFiles.forEach((file) => {
                formData.append("attachments[]", file);
            });
        } else {
            const fileInput = document.getElementById("fileInput");
            const selectedFiles = Array.from(fileInput?.files ?? []);
            selectedFiles.forEach((file) => {
                formData.append("attachments[]", file);
            });
        }

        fetch(url, {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data?.success) {
                window.location.reload();
            } else {
                throw new Error("Save failed");
            }
        })
        .catch(error => {
            console.error(error);
            alert("Something went wrong while saving.");

            // reset loading
            confirmSaveBtn.disabled = false;
            spinner.classList.add("hidden");
            btnText.textContent = "Save";
            cancelSaveBtn.disabled = false;

            if (openSaveModalBtn) {
                openSaveModalBtn.disabled = false;
                openSaveModalBtn.classList.remove("opacity-75", "cursor-wait");
                openSaveModalBtn.textContent = openSaveModalBtnDefaultText;
            }
        });
    });
}

/* =========================================================
   DELETE ACTIVITY
========================================================= */
const openDeleteModalBtn = document.getElementById("openDeleteModal");
const deleteModal = document.getElementById("deleteModal");
const cancelDeleteBtn = document.getElementById("cancelDelete");
const confirmDeleteBtn = document.getElementById("confirmDeleteBtn");
const deleteActivityForm = document.getElementById("deleteActivityForm");

if (
    isActivityOptionsPage()
    && openDeleteModalBtn
    && deleteModal
    && cancelDeleteBtn
    && confirmDeleteBtn
    && deleteActivityForm
) {
    openDeleteModalBtn.addEventListener("click", () => {
        deleteModal.classList.remove("hidden");
    });

    cancelDeleteBtn.addEventListener("click", () => {
        deleteModal.classList.add("hidden");
    });

    confirmDeleteBtn.addEventListener("click", () => {
        deleteActivityForm.submit();
    });
}

document.addEventListener("DOMContentLoaded", () => {
    if (!isActivityOptionsPage()) return;

    const modal = document.getElementById("removeFileModal");
    const form = document.getElementById("removeFileForm");
    const cancelBtn = document.getElementById("cancelRemoveFile");
    const confirmBtn = document.getElementById("confirmRemoveFile");
    const spinner = document.getElementById("removeLoadingSpinner");
    const btnText = document.getElementById("removeBtnText");
    if (!modal || !form || !cancelBtn || !confirmBtn || !spinner || !btnText) return;

    document.querySelectorAll(".open-remove-modal").forEach(button => {
        button.addEventListener("click", () => {
            form.action = button.dataset.action;
            modal.classList.remove("hidden");
            modal.classList.add("flex");
        });
    });

    cancelBtn.addEventListener("click", () => {
        modal.classList.add("hidden");
        modal.classList.remove("flex");
        resetLoading();
    });

    form.addEventListener("submit", () => {
        confirmBtn.disabled = true;
        spinner.classList.remove("hidden");
        btnText.textContent = "Removing...";
    });

    function resetLoading() {
        confirmBtn.disabled = false;
        spinner.classList.add("hidden");
        btnText.textContent = "Remove";
    }
});


