@extends('layouts.dashboard')

@section('styles')
<style>
[x-cloak]{display:none!important}
.decade-folder-tab{clip-path:polygon(0 100%,0 34%,16% 0,100% 0,100% 100%)}
.workspace-folder-tab{clip-path:polygon(0 100%,0 28%,12% 0,100% 0,100% 100%)}
</style>
@endsection

@section('content')
@php
    $hasNoSchoolYears = $schoolYears->isEmpty();
    $selectedDecade = (int) old(
        'selected_decade',
        $selectedSchoolYear
            ? ((int) floor($selectedSchoolYear->start_year / 10) * 10)
            : ((int) floor(($candidateStartYear ?: now()->year) / 10) * 10)
    );
    $modalStartsInDecadeMode = $errors->schoolYearDecadeStore->any();
    $modalStartsInYearMode = $errors->schoolYearStore->any();
    $promotionRows = $promotionPreview ? $promotionPreview['rows']->values()->all() : [];
    $promotionSummary = $promotionPreview['summary'] ?? [
        'total_students' => 0,
        'promoted' => 0,
        'retained' => 0,
        'graduated' => 0,
        'transferred_out' => 0,
        'blocking' => 0,
    ];
    $promotionActionState = collect($promotionRows)->mapWithKeys(fn (array $row) => [(string) $row['student_id'] => $row['action']])->all();
    $promotionTargetSectionState = collect($promotionRows)->mapWithKeys(fn (array $row) => [(string) $row['student_id'] => $row['target_section_id']])->all();
    $selectedSourceSectionId = ! empty($promotionForm['source_section_id'])
        ? (int) $promotionForm['source_section_id']
        : ($sectionOverviewPayload->first()['id'] ?? null);
@endphp

<div x-data="{
    schoolYears:@js($schoolYearPayload),sectionOverview:@js($sectionOverviewPayload),targetSections:@js($targetSectionPayload),promotionRows:@js($promotionRows),
    previewPromotionUrl:@js(route('admin.school-year.promotions.preview')),executePromotionUrl:@js(route('admin.school-year.promotions.execute')),
    managePanelOpen:{{ ($errors->schoolYearStore->any() || $errors->schoolYearDecadeStore->any() || $hasNoSchoolYears) ? 'true' : 'false' }},
    createPanelOpen:{{ ($modalStartsInDecadeMode || $modalStartsInYearMode || $hasNoSchoolYears) ? 'true' : 'false' }},createMode:'{{ $modalStartsInYearMode ? 'year' : ($modalStartsInDecadeMode ? 'decade' : ($hasNoSchoolYears ? 'decade' : '')) }}',
    isCreatingYear:false,isSubmittingPromotion:false,searchTerm:'',selectedDecade:{{ $selectedDecade }},candidateStartYear:{{ (int) $candidateStartYear }},candidateEndYear:{{ (int) $candidateEndYear }},
    targetDecade:{{ (int) old('target_decade', $hasNoSchoolYears ? $selectedDecade : $selectedDecade + 10) }},expandedYearId:null,selectedSectionId:@js($selectedSourceSectionId),previewSectionId:@js($promotionPreview ? $selectedSourceSectionId : null),
    sectionWorkspaceTab:'students',sectionSelectorOpen:false,sectionModalSearchTerm:'',sectionModalGradeFilter:'',expandedSectionModalId:null,expandedSubjectKey:null,
    selectedStudentIds:[],bulkOutcomeOpen:false,targetSectionModalOpen:false,targetSectionMode:'bulk',targetSectionStudentId:null,confirmPromotionOpen:false,hasPromotionPreview:{{ $promotionPreview ? 'true' : 'false' }},
    actionState:@js($promotionActionState),targetSectionState:@js($promotionTargetSectionState),selectedSchoolYearId:{{ $selectedSchoolYear?->id ?? 'null' }},targetSchoolYearId:{{ $nextSchoolYear?->id ?? 'null' }},targetSchoolYearLabel:@js($nextSchoolYear?->label),
    init(){if(!this.availableDecades().includes(this.selectedDecade)){this.selectedDecade=this.availableDecades()[0]??this.currentDecade()}const ids=this.sectionOverview.map((section)=>Number(section.id));if(this.selectedSectionId!==null&&!ids.includes(Number(this.selectedSectionId))){this.selectedSectionId=null}if(this.selectedSectionId===null&&ids.length>0){this.selectedSectionId=ids[0]}this.sectionModalGradeFilter='';},
    currentDecade(){return Math.floor(new Date().getFullYear()/10)*10},availableDecades(){return [...new Set(this.schoolYears.map((year)=>Math.floor(Number(year.start)/10)*10))].sort((a,b)=>a-b)},hasManagedDecades(){return this.availableDecades().length>0},
    decadeLabel(decade){return `${decade}s`},filteredSchoolYears(){const search=this.searchTerm.trim().toLowerCase();return this.schoolYears.filter((year)=>{const matchesDecade=this.selectedDecade===null||Math.floor(Number(year.start)/10)*10===this.selectedDecade;const matchesSearch=search===''||year.label.toLowerCase().includes(search);return matchesDecade&&matchesSearch})},
    nextDecade(decade){return Number(decade)+10},hasDecade(decade){return this.schoolYears.some((year)=>Math.floor(Number(year.start)/10)*10===Number(decade))},canSeedNextDecade(decade){return !this.hasDecade(this.nextDecade(decade))},
    latestSchoolYear(){if(this.schoolYears.length===0){return null}const sorted=[...this.schoolYears].sort((a,b)=>a.start===b.start?a.end-b.end:a.start-b.start);return sorted[sorted.length-1]},seedCandidateYears(){const latest=this.latestSchoolYear();if(latest){this.candidateStartYear=Number(latest.end);this.candidateEndYear=Number(latest.end)+1;return}const currentYear=new Date().getFullYear();this.candidateStartYear=currentYear;this.candidateEndYear=currentYear+1},
    openCreatePanel(decade=null){const numeric=Number(decade);const seedCurrent=!this.hasManagedDecades()&&!Number.isFinite(numeric);const base=seedCurrent?this.currentDecade():(Number.isFinite(numeric)?numeric:this.currentDecade());this.selectedDecade=base;this.targetDecade=seedCurrent?base:this.nextDecade(base);this.expandedYearId=null;this.isCreatingYear=false;this.createMode='decade';this.createPanelOpen=true},
    switchCreateMode(mode){this.createMode=mode;this.isCreatingYear=false;if(mode==='year'){this.seedCandidateYears()}},decadeYears(decade=this.targetDecade){const numeric=Number(decade);if(!Number.isFinite(numeric)){return []}return Array.from({length:10},(_,index)=>({key:`${numeric}-${index}`,label:`${numeric+index} - ${numeric+index+1}`}))},
    parsedYear(value){if(value===null||value===undefined||value===''){return null}const parsed=Number(value);return Number.isInteger(parsed)?parsed:null},
    yearIntegrityMessages(){const messages=[];const start=this.parsedYear(this.candidateStartYear);const end=this.parsedYear(this.candidateEndYear);const latest=this.latestSchoolYear();if(start===null){messages.push('Enter a valid starting year.')}else if(start<2000||start>2100){messages.push('The starting year must be between 2000 and 2100.')}if(end===null){messages.push('Enter a valid ending year.')}else if(end<2001||end>2101){messages.push('The ending year must be between 2001 and 2101.')}if(start!==null&&end!==null&&end!==start+1){messages.push('The ending year must be exactly one year after the starting year.')}if(start!==null&&end!==null&&this.schoolYears.some((year)=>Number(year.start)===start&&Number(year.end)===end)){messages.push('This school year already exists.')}if(latest&&start!==null&&start!==Number(latest.end)){messages.push(`Additions must continue directly after ${latest.label}.`)}return [...new Set(messages)]},
    toggleExpandedYear(yearId){this.expandedYearId=this.expandedYearId===yearId?null:yearId},closeCreatePanel(){this.createPanelOpen=false;this.createMode='';this.isCreatingYear=false},
    selectedSection(){return this.sectionOverview.find((section)=>Number(section.id)===Number(this.selectedSectionId))??null},sectionGradeOptions(){return [...new Set(this.sectionOverview.map((section)=>Number(section.grade_level)))].sort((a,b)=>a-b)},
    filteredSectionsForSelector(){const search=this.sectionModalSearchTerm.trim().toLowerCase();const gradeFilter=this.sectionModalGradeFilter===''?'':Number(this.sectionModalGradeFilter);return this.sectionOverview.filter((section)=>{const matchesGrade=gradeFilter===''||Number(section.grade_level)===gradeFilter;const matchesSearch=search===''||section.label.toLowerCase().includes(search)||section.section_name.toLowerCase().includes(search);return matchesGrade&&matchesSearch})},
    setSectionModalGradeFilter(grade=''){this.sectionModalGradeFilter=grade===''?'':String(grade);this.sectionModalSearchTerm='';this.expandedSectionModalId=null},
    openSectionSelector(){if(this.sectionOverview.length===0){return}this.sectionSelectorOpen=true;this.expandedSectionModalId=null;this.sectionModalSearchTerm='';this.sectionModalGradeFilter='';},
    toggleSectionDetails(sectionId){this.expandedSectionModalId=this.expandedSectionModalId===sectionId?null:sectionId},selectSection(sectionId){const numericSectionId=Number(sectionId);this.selectedSectionId=numericSectionId;this.previewSectionId=numericSectionId;this.sectionSelectorOpen=false;this.sectionWorkspaceTab='students';this.selectedStudentIds=[];this.bulkOutcomeOpen=false;this.expandedSubjectKey=null;this.$nextTick(()=>{if(this.$refs.sourceSectionField){this.$refs.sourceSectionField.value=String(numericSectionId)}this.submitPreview()})},
    openStudentsTab(){this.sectionWorkspaceTab='students';if(!this.hasPromotionPreview||Number(this.previewSectionId)!==Number(this.selectedSectionId)){this.submitPreview()}},openSubjectsTab(){this.sectionWorkspaceTab='subjects'},toggleSubject(subjectId){const key=`${this.selectedSectionId??'none'}-${subjectId}`;this.expandedSubjectKey=this.expandedSubjectKey===key?null:key},
    canPreviewPromotions(){return Boolean(this.selectedSectionId)&&Boolean(this.selectedSchoolYearId)&&Boolean(this.targetSchoolYearId)},
    submitPreview(){if(!this.canPreviewPromotions()||this.isSubmittingPromotion){return}this.isSubmittingPromotion=true;this.bulkOutcomeOpen=false;this.$refs.promotionForm.action=this.previewPromotionUrl;this.$refs.promotionForm.submit()},
    submitExecution(){if(this.isSubmittingPromotion){return}this.isSubmittingPromotion=true;this.confirmPromotionOpen=false;this.$refs.promotionForm.action=this.executePromotionUrl;this.$refs.promotionForm.submit()},
    findPromotionRow(studentId){return this.promotionRows.find((row)=>Number(row.student_id)===Number(studentId))??null},
    currentAction(row){return this.actionState[row.student_id]??row.action},
    targetGradeForRow(row){const action=this.currentAction(row);if(action==='promoted'){return Math.min(Number(row.current_grade)+1,12)}if(action==='retained'){return Number(row.current_grade)}return null},
    rowRequiresTarget(row){return this.targetGradeForRow(row)!==null},
    currentTargetSectionId(row){const stateValue=this.targetSectionState[row.student_id];return stateValue===undefined||stateValue===null||stateValue===''?row.target_section_id:Number(stateValue)},
    targetSectionOptionById(sectionId){return this.targetSections.find((section)=>Number(section.id)===Number(sectionId))??null},
    targetSectionLabelForRow(row){if(!this.rowRequiresTarget(row)){return 'Not required'}const targetSection=this.targetSectionOptionById(this.currentTargetSectionId(row));return targetSection?targetSection.label:'No target section'},
    rowBlockingInfo(row){if(!row){return {blocking:false,message:''}}if(row.blocking_reason_code==='existing_target_enrollment'){return {blocking:true,message:row.status_note}}const action=this.currentAction(row);if(action==='graduated'&&Number(row.current_grade)<12){return {blocking:true,message:'Only Grade 12 students can be marked as graduated.'}}const targetGrade=this.targetGradeForRow(row);if(targetGrade!==null){const targetSection=this.targetSectionOptionById(this.currentTargetSectionId(row));if(!targetSection){const targetYearLabel=this.targetSchoolYearLabel??'the next school year';return {blocking:true,message:`Missing matching target section in ${targetYearLabel}.`}}if(Number(targetSection.grade_level)!==Number(targetGrade)){return {blocking:true,message:'The selected target section does not match the required grade level.'}}}return {blocking:false,message:''}},
    rowHasBlocking(row){return this.rowBlockingInfo(row).blocking},
    rowBlockingMessage(row){return this.rowBlockingInfo(row).message},
    outcomeSummary(){const summary={promoted:0,retained:0,graduated:0,transferred_out:0};this.promotionRows.forEach((row)=>{const action=this.currentAction(row);if(summary[action]!==undefined){summary[action]++}});return summary},
    allPromotionRowsSelected(){return this.promotionRows.length>0&&this.selectedStudentIds.length===this.promotionRows.length},
    toggleSelectAll(checked){this.selectedStudentIds=checked?this.promotionRows.map((row)=>Number(row.student_id)):[]},
    toggleStudentSelection(studentId,checked){const numericId=Number(studentId);if(checked){if(!this.selectedStudentIds.includes(numericId)){this.selectedStudentIds.push(numericId)}return}this.selectedStudentIds=this.selectedStudentIds.filter((id)=>Number(id)!==numericId)},
    rowCanGraduate(studentId){const row=this.findPromotionRow(studentId);return row?Number(row.current_grade)>=12:false},
    selectedRows(){return this.promotionRows.filter((row)=>this.selectedStudentIds.includes(Number(row.student_id)))},
    selectedRowsCanGraduate(){const rows=this.selectedRows();return rows.length>0&&rows.every((row)=>Number(row.current_grade)>=12)},
    setRowOutcome(studentId,action){if(action==='graduated'&&!this.rowCanGraduate(studentId)){return}this.actionState[studentId]=action;if(['graduated','transferred_out'].includes(action)){this.targetSectionState[studentId]=''}},
    toggleBulkOutcomeDrawer(){if(this.selectedStudentIds.length===0){return}this.bulkOutcomeOpen=!this.bulkOutcomeOpen},
    applyBulkOutcome(action){if(action==='graduated'&&!this.selectedRowsCanGraduate()){return}this.selectedStudentIds.forEach((studentId)=>{this.actionState[studentId]=action;if(['graduated','transferred_out'].includes(action)){this.targetSectionState[studentId]=''}});this.bulkOutcomeOpen=false},
    applicableTargetSectionsForStudent(studentId){const row=this.findPromotionRow(studentId);if(!row){return []}const targetGrade=this.targetGradeForRow(row);if(targetGrade===null){return []}return this.targetSections.filter((section)=>Number(section.grade_level)===Number(targetGrade))},
    applicableTargetSectionsForSelection(){const rows=this.selectedRows();if(rows.length===0){return []}const grades=[...new Set(rows.map((row)=>this.targetGradeForRow(row)).filter((value)=>value!==null))];if(grades.length!==1){return []}return this.targetSections.filter((section)=>Number(section.grade_level)===Number(grades[0]))},
    openBulkTargetSectionModal(){if(this.selectedStudentIds.length===0){return}this.targetSectionMode='bulk';this.targetSectionStudentId=null;this.targetSectionModalOpen=true},
    openRowTargetSectionModal(studentId){const row=this.findPromotionRow(studentId);if(!row||!this.rowRequiresTarget(row)){return}this.targetSectionMode='single';this.targetSectionStudentId=Number(studentId);this.targetSectionModalOpen=true},
    visibleTargetSections(){return this.targetSectionMode==='single'?this.applicableTargetSectionsForStudent(this.targetSectionStudentId):this.applicableTargetSectionsForSelection()},
    applyTargetSection(sectionId){const numericSectionId=Number(sectionId);if(this.targetSectionMode==='single'&&this.targetSectionStudentId!==null){this.targetSectionState[this.targetSectionStudentId]=numericSectionId}else{this.selectedStudentIds.forEach((studentId)=>{this.targetSectionState[studentId]=numericSectionId})}this.targetSectionModalOpen=false},
    targetSectionEmptyStateMessage(){if(!this.targetSchoolYearId){return 'Add the next school year first.'}if(this.targetSections.length===0){return 'No sections exist yet in the next school year.'}if(this.targetSectionMode==='single'){const row=this.findPromotionRow(this.targetSectionStudentId);if(!row){return 'Select a student first.'}const targetGrade=this.targetGradeForRow(row);if(targetGrade===null){return 'A target section is not required for this outcome.'}return `No successor section exists yet for Grade ${targetGrade}.`}const rows=this.selectedRows();if(rows.length===0){return 'Select at least one student first.'}const grades=[...new Set(rows.map((row)=>this.targetGradeForRow(row)).filter((value)=>value!==null))];if(grades.length!==1){return 'Select students with the same target grade to assign a shared target section.'}return `No successor section exists yet for Grade ${grades[0]}.`},
    canOpenConfirmModal(){return this.promotionRows.length>0}
}" class="relative flex-1">
    <div class="flex flex-col gap-6 rounded-2xl bg-whiteColor p-4 shadow-lg transition-all duration-300 dark:bg-darkblack dark:ring-2 dark:ring-blue-500/20 dark:ring-offset-0 sm:p-10">
        <div id="page-header" class="flex flex-col gap-2">
            <h1 class="text-2xl font-bold text-[#5E5F60] dark:text-white sm:text-3xl">School Year Management</h1>
            <p class="text-sm text-[#ABB7C2] sm:text-base">Control the active school year, preserve enrollment history, and run yearly promotion batches.</p>
        </div>

        <section class="rounded-[28px] border border-[#DCE4EC] bg-white px-6 py-7 shadow-sm dark:border-white/10 dark:bg-[#121A29] sm:px-8 sm:py-8">
            <div class="flex flex-col gap-6 xl:flex-row xl:items-start xl:justify-between">
                <div class="space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[#8B97A7] dark:text-[#7F92A8]">School Year</p>
                    <div class="flex flex-wrap items-center gap-3">
                        @if ($previousSchoolYear)
                            <a href="{{ route('admin.school-year', ['selected_school_year' => $previousSchoolYear->id]) }}" class="inline-flex h-10 w-10 items-center justify-center text-[#5E5F60] transition hover:text-primaryColor dark:text-white" aria-label="Previous school year"><i class="icofont-simple-left text-lg"></i></a>
                        @else
                            <span class="inline-flex h-10 w-10 items-center justify-center text-[#B6C1CB] dark:text-[#58677B]"><i class="icofont-simple-left text-lg"></i></span>
                        @endif

                        <button type="button" @click="managePanelOpen = true" class="inline-flex min-h-14 items-center gap-3 rounded-2xl border border-[#D7E1EA] bg-[#F9FBFD] px-5 py-3 text-left text-sm font-semibold text-[#394554] transition hover:border-primaryColor dark:border-white/10 dark:bg-darkblack dark:text-white">
                            <span>{{ $selectedSchoolYear?->label ?? 'No school year selected' }}</span>
                            <i class="icofont-simple-down text-xs text-[#8B97A7] dark:text-[#7F92A8]"></i>
                        </button>

                        @if ($nextSchoolYear)
                            <a href="{{ route('admin.school-year', ['selected_school_year' => $nextSchoolYear->id]) }}" class="inline-flex h-10 w-10 items-center justify-center text-[#5E5F60] transition hover:text-primaryColor dark:text-white" aria-label="Next school year"><i class="icofont-simple-right text-lg"></i></a>
                        @else
                            <span class="inline-flex h-10 w-10 items-center justify-center text-[#B6C1CB] dark:text-[#58677B]"><i class="icofont-simple-right text-lg"></i></span>
                        @endif
                    </div>
                </div>

                @if ($selectedSchoolYear)
                    <div class="flex flex-wrap items-center gap-3 xl:justify-end">
                        @if ($selectedSchoolYear->is_active)
                            <span class="inline-flex h-11 items-center justify-center rounded-xl border border-[#1F8A46] bg-[#EAF4EA] px-4 text-sm font-semibold text-[#1F8A46] dark:border-[#2E8B57] dark:bg-[#12331E] dark:text-[#82D7A0]">Current Active Year</span>
                        @else
                            <form method="POST" action="{{ route('admin.school-year.activate', $selectedSchoolYear) }}">@csrf
                                <button type="submit" class="inline-flex h-11 items-center justify-center rounded-xl bg-primaryColor px-4 text-sm font-semibold text-white transition hover:brightness-110">Activate School Year</button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>

            @if ($hasNoSchoolYears)
                <div class="mt-8 rounded-[24px] border border-dashed border-[#C9D7E4] bg-[#F9FBFD] px-6 py-7 dark:border-white/10 dark:bg-[#101B2C] sm:px-8 sm:py-8">
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                        <div class="max-w-2xl space-y-2">
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[#8B97A7] dark:text-[#7F92A8]">Empty State</p>
                            <h2 class="text-xl font-bold text-[#394554] dark:text-white">No school years have been created yet.</h2>
                            <p class="text-sm text-[#617184] dark:text-[#96A6BB]">Create the first decade or add an individual school year before working with sections, promotions, and active-year flows.</p>
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row">
                            <button
                                type="button"
                                @click="managePanelOpen = true; createPanelOpen = true; createMode = 'decade'; selectedDecade = currentDecade(); targetDecade = currentDecade();"
                                class="inline-flex h-11 items-center justify-center rounded-xl bg-primaryColor px-4 text-sm font-semibold text-white transition hover:brightness-110"
                            >
                                Add Current Decade
                            </button>
                            <button
                                type="button"
                                @click="managePanelOpen = true; createPanelOpen = true; createMode = 'year'; seedCandidateYears();"
                                class="inline-flex h-11 items-center justify-center rounded-xl border border-[#D7E1EA] px-4 text-sm font-semibold text-[#394554] transition hover:border-primaryColor hover:text-primaryColor dark:border-white/10 dark:text-white"
                            >
                                Add Individual School Year
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            <div class="mt-8 border-t border-[#E6EDF4] pt-8 dark:border-white/10">
                <div class="space-y-6">
                    <div class="space-y-4">
                        <div class="space-y-2">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#8B97A7] dark:text-[#7F92A8]">Student Promotion</p>
                            <button type="button" @click="openSectionSelector()" class="inline-flex items-center gap-3 text-left text-xl font-bold text-[#394554] transition hover:text-primaryColor dark:text-white dark:hover:text-[#8EB6FF]">
                                <span x-text="selectedSection() ? selectedSection().label : 'No section available'"></span>
                                <i class="icofont-simple-down text-sm text-[#8B97A7] dark:text-[#7F92A8]"></i>
                            </button>
                        </div>

                        <div class="flex items-end gap-2 overflow-x-auto [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden">
                            <button type="button" @click="openStudentsTab()" class="workspace-folder-tab relative -mb-px inline-flex min-w-[126px] items-center justify-center border px-4 py-2 text-sm font-semibold transition" :class="sectionWorkspaceTab === 'students' ? 'z-10 border-b-white border-[#D7E1EA] bg-white text-primaryColor dark:border-white/10 dark:border-b-[#121A29] dark:bg-[#121A29]' : 'border-transparent bg-[#EEF2F6] text-[#708096] hover:bg-white hover:text-primaryColor dark:bg-[#172234] dark:text-[#94A3B8]'">Students</button>
                            <button type="button" @click="openSubjectsTab()" class="workspace-folder-tab relative -mb-px inline-flex min-w-[126px] items-center justify-center border px-4 py-2 text-sm font-semibold transition" :class="sectionWorkspaceTab === 'subjects' ? 'z-10 border-b-white border-[#D7E1EA] bg-white text-primaryColor dark:border-white/10 dark:border-b-[#121A29] dark:bg-[#121A29]' : 'border-transparent bg-[#EEF2F6] text-[#708096] hover:bg-white hover:text-primaryColor dark:bg-[#172234] dark:text-[#94A3B8]'">Subjects</button>
                        </div>
                    </div>

                    <div class="border-t border-[#E6EDF4] pt-6 dark:border-white/10">
                        <div x-show="sectionWorkspaceTab === 'students'" x-cloak class="space-y-5">
                            @if (! $selectedSchoolYear)
                                <div class="rounded-2xl border border-dashed border-[#D7E1EA] px-4 py-6 text-center text-sm text-[#8B97A7] dark:border-white/10 dark:text-[#7F92A8]">Select a school year first to manage promotions.</div>
                            @elseif ($sectionOverviewPayload->isEmpty())
                                <div class="rounded-2xl border border-dashed border-[#D7E1EA] px-4 py-6 text-center text-sm text-[#8B97A7] dark:border-white/10 dark:text-[#7F92A8]">No sections are currently assigned to this school year.</div>
                            @elseif (! $nextSchoolYear)
                                <div class="rounded-2xl border border-[#F3D7A3] bg-[#FFF7E8] px-4 py-4 text-sm text-[#8A5A00] dark:border-[#5B4721] dark:bg-[#2B2112] dark:text-[#F2C889]">Add the next school year before promoting students from {{ $selectedSchoolYear->label }}.</div>
                            @endif

                            @if ($promotionExecutionError)
                                <div class="rounded-2xl border border-[#F2CACA] bg-[#FDECEC] px-4 py-3 text-sm text-[#B23A3A] dark:border-[#5B2222] dark:bg-[#2A1616] dark:text-[#F1A5A5]">{{ $promotionExecutionError }}</div>
                            @endif

                            @error('source_section_id', 'promotionPreview')
                                <div class="rounded-2xl border border-[#F2CACA] bg-[#FDECEC] px-4 py-3 text-sm text-[#B23A3A] dark:border-[#5B2222] dark:bg-[#2A1616] dark:text-[#F1A5A5]">{{ $message }}</div>
                            @enderror

                            @error('target_school_year_id', 'promotionPreview')
                                <div class="rounded-2xl border border-[#F2CACA] bg-[#FDECEC] px-4 py-3 text-sm text-[#B23A3A] dark:border-[#5B2222] dark:bg-[#2A1616] dark:text-[#F1A5A5]">{{ $message }}</div>
                            @enderror

                            <form method="POST" action="{{ route('admin.school-year.promotions.preview') }}" x-ref="promotionForm" class="space-y-5">
                                @csrf
                                <input type="hidden" name="selected_school_year_id" value="{{ $selectedSchoolYear?->id ?? '' }}">
                                <input type="hidden" name="source_school_year_id" value="{{ $selectedSchoolYear?->id ?? '' }}">
                                <input type="hidden" name="target_school_year_id" value="{{ $nextSchoolYear?->id ?? '' }}">
                                <input type="hidden" name="activate_target_school_year" value="0">
                                <input type="hidden" name="source_section_id" x-ref="sourceSectionField" :value="selectedSectionId ?? ''">

                                @foreach ($promotionRows as $row)
                                    <input type="hidden" name="actions[{{ $row['student_id'] }}]" :value="actionState['{{ $row['student_id'] }}'] ?? '{{ $row['action'] }}'">
                                    <input type="hidden" name="target_section_ids[{{ $row['student_id'] }}]" :value="targetSectionState['{{ $row['student_id'] }}'] ?? '{{ $row['target_section_id'] }}'">
                                @endforeach

                                @if ($promotionPreview)
                                    <div class="overflow-hidden rounded-2xl border border-[#E6EDF4] dark:border-white/10">
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full divide-y divide-[#E6EDF4] dark:divide-white/10">
                                                <thead class="bg-[#F9FBFD] dark:bg-[#10192A]">
                                                    <tr>
                                                        <th class="px-4 py-3 text-left text-sm font-semibold text-[#394554] dark:text-white">
                                                            <div class="flex items-center gap-3">
                                                                <input type="checkbox" class="h-4 w-4 rounded border-[#C4D0DB] text-primaryColor focus:ring-primaryColor" :checked="allPromotionRowsSelected()" @change="toggleSelectAll($event.target.checked)">
                                                                <span>Student ({{ $promotionSummary['total_students'] }})</span>
                                                            </div>
                                                        </th>
                                                        <th class="px-4 py-3 text-left text-sm font-semibold text-[#394554] dark:text-white">
                                                            <div class="flex items-center gap-2">
                                                                <span>Outcome</span>
                                                                <span class="text-xs font-medium text-[#8B97A7] dark:text-[#7F92A8]"><span title="Promoted" x-text="outcomeSummary().promoted"></span>/<span title="Retained" x-text="outcomeSummary().retained"></span>/<span title="Graduated Out" x-text="outcomeSummary().graduated + outcomeSummary().transferred_out"></span></span>
                                                            </div>
                                                        </th>
                                                        <th class="px-4 py-3 text-left text-sm font-semibold text-[#394554] dark:text-white">Target Section</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-[#E6EDF4] bg-white dark:divide-white/10 dark:bg-[#121A29]">
                                                    @forelse ($promotionRows as $row)
                                                        <tr>
                                                            <td class="px-4 py-4 align-top">
                                                                <div class="flex items-start gap-3">
                                                                    <input type="checkbox" class="mt-1 h-4 w-4 rounded border-[#C4D0DB] text-primaryColor focus:ring-primaryColor" :checked="selectedStudentIds.includes({{ $row['student_id'] }})" @change="toggleStudentSelection({{ $row['student_id'] }}, $event.target.checked)">
                                                                    <div class="min-w-0">
                                                                        <div class="flex items-center gap-2">
                                                                            <p class="truncate text-sm font-semibold text-[#394554] dark:text-white">{{ $row['student_name'] }}</p>
                                                                            <span x-show="rowHasBlocking(findPromotionRow({{ $row['student_id'] }}))" x-cloak class="inline-flex text-[#D64545] dark:text-[#F29B9B]" :title="rowBlockingMessage(findPromotionRow({{ $row['student_id'] }}))">
                                                                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M10 3.5 17 16H3L10 3.5Z" stroke-linejoin="round"/><path d="M10 7.5v4.25" stroke-linecap="round"/><circle cx="10" cy="14.2" r=".7" fill="currentColor" stroke="none"/></svg>
                                                                                </span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td class="px-4 py-4 align-top">
                                                                <div class="flex flex-wrap gap-2">
                                                                    <button type="button" @click="setRowOutcome({{ $row['student_id'] }}, 'promoted')" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border transition" :class="actionState['{{ $row['student_id'] }}'] === 'promoted' ? 'border-primaryColor bg-[#EEF4FF] text-primaryColor dark:border-[#4B7BFF] dark:bg-[#15253D] dark:text-[#8EB6FF]' : 'border-[#D7E1EA] text-[#394554] hover:border-primaryColor hover:text-primaryColor dark:border-white/10 dark:text-white'" title="Promoted"><svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M10 16V4" stroke-linecap="round"/><path d="m5.5 8.5 4.5-4.5 4.5 4.5" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                                                                    <button type="button" @click="setRowOutcome({{ $row['student_id'] }}, 'retained')" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border transition" :class="actionState['{{ $row['student_id'] }}'] === 'retained' ? 'border-primaryColor bg-[#EEF4FF] text-primaryColor dark:border-[#4B7BFF] dark:bg-[#15253D] dark:text-[#8EB6FF]' : 'border-[#D7E1EA] text-[#394554] hover:border-primaryColor hover:text-primaryColor dark:border-white/10 dark:text-white'" title="Retained"><svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 10a6 6 0 1 0 2.1-4.58" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 4.5v3.5h3.5" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                                                                    @if ((int) $row['current_grade'] >= 12)
                                                                        <button type="button" @click="setRowOutcome({{ $row['student_id'] }}, 'graduated')" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border transition" :class="actionState['{{ $row['student_id'] }}'] === 'graduated' ? 'border-primaryColor bg-[#EEF4FF] text-primaryColor dark:border-[#4B7BFF] dark:bg-[#15253D] dark:text-[#8EB6FF]' : 'border-[#D7E1EA] text-[#394554] hover:border-primaryColor hover:text-primaryColor dark:border-white/10 dark:text-white'" title="Graduated"><svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 8.5 10 5l7 3.5L10 12 3 8.5Z" stroke-linejoin="round"/><path d="M6.5 10.25V13.5L10 15l3.5-1.5v-3.25" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                                                                    @endif
                                                                    <button type="button" @click="setRowOutcome({{ $row['student_id'] }}, 'transferred_out')" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border transition" :class="actionState['{{ $row['student_id'] }}'] === 'transferred_out' ? 'border-primaryColor bg-[#EEF4FF] text-primaryColor dark:border-[#4B7BFF] dark:bg-[#15253D] dark:text-[#8EB6FF]' : 'border-[#D7E1EA] text-[#394554] hover:border-primaryColor hover:text-primaryColor dark:border-white/10 dark:text-white'" title="Transferred Out"><svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M8 5h7v10H8" stroke-linecap="round" stroke-linejoin="round"/><path d="M11 10H3" stroke-linecap="round"/><path d="m6.5 6.5-3.5 3.5 3.5 3.5" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                                                                </div>
                                                            </td>
                                                            <td class="px-4 py-4 align-top">
                                                                <button type="button" @click="openRowTargetSectionModal({{ $row['student_id'] }})" class="inline-flex min-h-[44px] w-full items-center justify-between gap-3 rounded-xl border border-[#D7E1EA] bg-[#F9FBFD] px-4 py-3 text-left text-sm font-medium text-[#394554] transition hover:border-primaryColor hover:text-primaryColor disabled:cursor-not-allowed disabled:opacity-50 dark:border-white/10 dark:bg-[#0F172A] dark:text-white" :disabled="!rowRequiresTarget(findPromotionRow({{ $row['student_id'] }}))">
                                                                    <span x-text="targetSectionLabelForRow(findPromotionRow({{ $row['student_id'] }}))"></span>
                                                                    <i class="icofont-simple-right text-xs text-[#8B97A7] dark:text-[#7F92A8]"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr><td colspan="3" class="px-4 py-8 text-center text-sm text-[#8B97A7] dark:text-[#7F92A8]">No students are currently assigned to this section for promotion.</td></tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <div class="border-t border-[#E6EDF4] pt-5 dark:border-white/10">
                                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                            <div class="flex flex-wrap items-center gap-3">
                                                <button type="button" @click="openBulkTargetSectionModal()" class="inline-flex h-11 items-center justify-center rounded-xl border border-[#D7E1EA] bg-white px-4 text-sm font-semibold text-[#394554] transition hover:border-primaryColor hover:text-primaryColor disabled:cursor-not-allowed disabled:opacity-50 dark:border-white/10 dark:bg-[#0F172A] dark:text-white" :disabled="selectedStudentIds.length === 0 || !targetSchoolYearId">Target Section</button>

                                                <div class="relative">
                                                    <button type="button" @click="toggleBulkOutcomeDrawer()" class="inline-flex h-11 items-center justify-center rounded-xl border border-[#D7E1EA] bg-white px-4 text-sm font-semibold text-[#394554] transition hover:border-primaryColor hover:text-primaryColor disabled:cursor-not-allowed disabled:opacity-50 dark:border-white/10 dark:bg-[#0F172A] dark:text-white" :disabled="selectedStudentIds.length === 0">Outcome</button>
                                                    <div x-show="bulkOutcomeOpen" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-x-2" x-transition:enter-end="opacity-100 translate-x-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-x-0" x-transition:leave-end="opacity-0 -translate-x-2" class="absolute left-full top-0 z-10 ml-2 flex items-center gap-2 rounded-2xl border border-[#D7E1EA] bg-white px-3 py-2 shadow-lg dark:border-white/10 dark:bg-[#0F172A]">
                                                        <button type="button" @click="applyBulkOutcome('promoted')" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-[#D7E1EA] text-[#394554] transition hover:border-primaryColor hover:text-primaryColor dark:border-white/10 dark:text-white" title="Promoted"><svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M10 16V4" stroke-linecap="round"/><path d="m5.5 8.5 4.5-4.5 4.5 4.5" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                                                        <button type="button" @click="applyBulkOutcome('retained')" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-[#D7E1EA] text-[#394554] transition hover:border-primaryColor hover:text-primaryColor dark:border-white/10 dark:text-white" title="Retained"><svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 10a6 6 0 1 0 2.1-4.58" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 4.5v3.5h3.5" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                                                        <button type="button" x-show="selectedRowsCanGraduate()" x-cloak @click="applyBulkOutcome('graduated')" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-[#D7E1EA] text-[#394554] transition hover:border-primaryColor hover:text-primaryColor dark:border-white/10 dark:text-white" title="Graduated"><svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 8.5 10 5l7 3.5L10 12 3 8.5Z" stroke-linejoin="round"/><path d="M6.5 10.25V13.5L10 15l3.5-1.5v-3.25" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                                                        <button type="button" @click="applyBulkOutcome('transferred_out')" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-[#D7E1EA] text-[#394554] transition hover:border-primaryColor hover:text-primaryColor dark:border-white/10 dark:text-white" title="Transferred Out"><svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M8 5h7v10H8" stroke-linecap="round" stroke-linejoin="round"/><path d="M11 10H3" stroke-linecap="round"/><path d="m6.5 6.5-3.5 3.5 3.5 3.5" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                                                    </div>
                                                </div>
                                            </div>

                                            <button type="button" @click="confirmPromotionOpen = true" class="inline-flex h-11 items-center justify-center rounded-xl bg-primaryColor px-5 text-sm font-semibold text-white transition hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-50" :disabled="!canOpenConfirmModal()">Confirm</button>
                                        </div>
                                    </div>
                                @endif
                            </form>
                        </div>

                        <div x-show="sectionWorkspaceTab === 'subjects'" x-cloak class="space-y-3">
                            <div x-show="!selectedSection()" x-cloak class="rounded-2xl border border-dashed border-[#D7E1EA] px-4 py-6 text-center text-sm text-[#8B97A7] dark:border-white/10 dark:text-[#7F92A8]">Select a section to inspect its subject lineup.</div>
                            <template x-if="selectedSection()">
                                <div class="space-y-3">
                                    <template x-if="selectedSection().subjects.length > 0">
                                        <div class="space-y-3">
                                            <template x-for="subject in selectedSection().subjects" :key="subject.id">
                                                <div class="overflow-hidden rounded-2xl border border-[#E6EDF4] bg-[#FBFCFE] dark:border-white/10 dark:bg-[#0F172A]">
                                                    <button type="button" @click="toggleSubject(subject.id)" class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left">
                                                        <p class="text-sm font-semibold text-[#394554] dark:text-white" x-text="subject.name"></p>
                                                        <i class="icofont-simple-right text-sm text-[#8B97A7] transition-transform duration-200 dark:text-[#7F92A8]" :class="expandedSubjectKey === `${selectedSectionId ?? 'none'}-${subject.id}` ? 'rotate-90 text-primaryColor' : ''"></i>
                                                    </button>
                                                    <div x-show="expandedSubjectKey === `${selectedSectionId ?? 'none'}-${subject.id}`" x-cloak class="border-t border-[#E6EDF4] px-4 py-3 dark:border-white/10">
                                                        <p class="text-sm text-[#617184] dark:text-[#96A6BB]">Teacher: <span class="font-medium text-[#394554] dark:text-white" x-text="subject.teacher_summary"></span></p>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="selectedSection().subjects.length === 0">
                                        <div class="rounded-2xl border border-dashed border-[#D7E1EA] px-4 py-6 text-center text-sm text-[#8B97A7] dark:border-white/10 dark:text-[#7F92A8]">No subjects are assigned to this section yet.</div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div x-cloak x-show="sectionSelectorOpen" class="fixed inset-0 z-[1000] flex items-center justify-center p-4" aria-hidden="false">
        <div x-show="sectionSelectorOpen" x-transition.opacity class="absolute inset-0 bg-[#0F172A]/45 backdrop-blur-[2px]" @click="sectionSelectorOpen = false"></div>
        <aside x-show="sectionSelectorOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-y-3 opacity-0" x-transition:enter-end="translate-y-0 opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-y-0 opacity-100" x-transition:leave-end="translate-y-3 opacity-0" class="relative z-10 flex h-[min(78vh,680px)] min-h-0 w-full max-w-[520px] flex-col rounded-[22px] border border-[#E6EDF4] bg-white p-5 shadow-2xl dark:border-white/10 dark:bg-[#0F172A]">
            <div class="flex items-start justify-between gap-4 border-b border-[#E6EDF4] pb-4 dark:border-white/10">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[#8B97A7] dark:text-[#7F92A8]">Section Workspace</p>
                    <h3 class="mt-2 text-xl font-bold text-[#394554] dark:text-white">Select Section</h3>
                </div>
                <div class="flex items-center gap-2">
                    <div class="relative hidden sm:block">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-[#8B97A7] dark:text-[#7F92A8]"><i class="icofont-search-1 text-xs"></i></span>
                        <input type="text" x-model="sectionModalSearchTerm" placeholder="Search section" class="h-9 w-[160px] rounded-lg border border-[#D7E1EA] bg-[#F9FBFD] pl-8 pr-3 text-xs text-[#394554] outline-none transition focus:border-primaryColor dark:border-white/10 dark:bg-[#121A29] dark:text-white">
                    </div>
                    <button type="button" @click="sectionSelectorOpen = false" class="px-2 text-xl leading-none text-gray-500 transition hover:text-gray-700 dark:text-gray-300 dark:hover:text-white" aria-label="Close section selector">&times;</button>
                </div>
            </div>

            <div class="mt-4 sm:hidden">
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-[#8B97A7] dark:text-[#7F92A8]"><i class="icofont-search-1 text-xs"></i></span>
                    <input type="text" x-model="sectionModalSearchTerm" placeholder="Search section" class="h-9 w-full rounded-lg border border-[#D7E1EA] bg-[#F9FBFD] pl-8 pr-3 text-xs text-[#394554] outline-none transition focus:border-primaryColor dark:border-white/10 dark:bg-[#121A29] dark:text-white">
                </div>
            </div>

            <div class="mt-4 min-h-0 flex-1 overflow-hidden rounded-2xl border border-[#E6EDF4] bg-[#F9FBFD] p-2 dark:border-white/10 dark:bg-[#121A29]">
                <div class="flex h-full min-h-0 flex-col">
                    <div class="flex items-end gap-2 overflow-x-auto px-1 pb-0 pt-1 [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden">
                        <button type="button" @click="setSectionModalGradeFilter('')" class="decade-folder-tab relative -mb-px inline-flex min-w-[82px] items-center justify-center border px-3 py-2 text-xs font-semibold transition" :class="sectionModalGradeFilter === '' ? 'z-10 border-b-[#F9FBFD] border-[#D7E1EA] bg-white text-primaryColor dark:border-white/10 dark:border-b-[#121A29] dark:bg-[#0F172A]' : 'border-transparent bg-[#EEF2F6] text-[#708096] hover:bg-white hover:text-primaryColor dark:bg-[#172234] dark:text-[#94A3B8]'">All</button>
                        <template x-for="grade in sectionGradeOptions()" :key="grade">
                            <button type="button" @click="setSectionModalGradeFilter(grade)" class="decade-folder-tab relative -mb-px inline-flex min-w-[82px] items-center justify-center border px-3 py-2 text-xs font-semibold transition" :class="sectionModalGradeFilter === String(grade) ? 'z-10 border-b-[#F9FBFD] border-[#D7E1EA] bg-white text-primaryColor dark:border-white/10 dark:border-b-[#121A29] dark:bg-[#0F172A]' : 'border-transparent bg-[#EEF2F6] text-[#708096] hover:bg-white hover:text-primaryColor dark:bg-[#172234] dark:text-[#94A3B8]'"><span x-text="`Grade ${grade}`"></span></button>
                        </template>
                    </div>

                    <div class="-mt-px min-h-0 flex-1 overflow-hidden rounded-2xl rounded-tl-none border border-[#E6EDF4] bg-[#F9FBFD] p-2 dark:border-white/10 dark:bg-[#121A29]">
                        <div class="mx-auto h-full max-h-[430px] min-h-0 w-full space-y-2 overflow-y-auto pr-1">
                            <template x-for="section in filteredSectionsForSelector()" :key="section.id">
                                <div class="space-y-2">
                                    <div class="flex min-h-[56px] items-center justify-between rounded-xl border bg-white px-4 py-2.5 transition dark:bg-[#0F172A]" :class="selectedSectionId === section.id ? 'border-primaryColor bg-[#EEF4FF] dark:border-[#4B7BFF] dark:bg-[#15253D]' : 'border-transparent hover:border-primaryColor hover:bg-[#F8FBFF] dark:hover:bg-[#13203A]'">
                                        <button type="button" @click="selectSection(section.id)" class="flex-1 text-left"><p class="text-sm font-semibold text-[#394554] dark:text-white" x-text="section.label"></p></button>
                                        <button type="button" @click.stop="toggleSectionDetails(section.id)" class="ml-4 inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#8B97A7] transition hover:text-primaryColor dark:text-[#7F92A8]" aria-label="Expand section details"><i class="icofont-simple-right text-sm transition-transform duration-200" :class="expandedSectionModalId === section.id ? 'rotate-90 text-primaryColor' : ''"></i></button>
                                    </div>
                                    <div x-show="expandedSectionModalId === section.id" x-cloak class="rounded-xl border border-[#E6EDF4] bg-white px-4 py-3 text-sm text-[#617184] dark:border-white/10 dark:bg-[#0F172A] dark:text-[#96A6BB]">
                                        <p><span class="font-medium text-[#394554] dark:text-white" x-text="section.students_count"></span> students <span class="mx-2 text-[#B6C1CB] dark:text-[#58677B]">/</span> <span class="font-medium text-[#394554] dark:text-white" x-text="section.subjects_count"></span> subjects</p>
                                    </div>
                                </div>
                            </template>
                            <div x-show="filteredSectionsForSelector().length === 0" class="rounded-xl border border-dashed border-[#D7E1EA] px-4 py-6 text-center text-sm text-[#8B97A7] dark:border-white/10 dark:text-[#7F92A8]">No sections matched this filter.</div>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </div>

    <div x-cloak x-show="targetSectionModalOpen" class="fixed inset-0 z-[1000] flex items-center justify-center p-4" aria-hidden="false">
        <div x-show="targetSectionModalOpen" x-transition.opacity class="absolute inset-0 bg-[#0F172A]/45 backdrop-blur-[2px]" @click="targetSectionModalOpen = false"></div>
        <aside x-show="targetSectionModalOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-y-3 opacity-0" x-transition:enter-end="translate-y-0 opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-y-0 opacity-100" x-transition:leave-end="translate-y-3 opacity-0" class="relative z-10 flex h-[min(72vh,560px)] min-h-0 w-full max-w-[460px] flex-col rounded-[22px] border border-[#E6EDF4] bg-white p-5 shadow-2xl dark:border-white/10 dark:bg-[#0F172A]">
            <div class="flex items-start justify-between gap-4 border-b border-[#E6EDF4] pb-4 dark:border-white/10">
                <div><p class="text-xs font-semibold uppercase tracking-[0.24em] text-[#8B97A7] dark:text-[#7F92A8]">Student Promotion</p><h3 class="mt-2 text-xl font-bold text-[#394554] dark:text-white">Target Section</h3></div>
                <button type="button" @click="targetSectionModalOpen = false" class="px-2 text-xl leading-none text-gray-500 transition hover:text-gray-700 dark:text-gray-300 dark:hover:text-white" aria-label="Close target section selector">&times;</button>
            </div>
            <div class="mt-4 min-h-0 flex-1 overflow-hidden rounded-2xl border border-[#E6EDF4] bg-[#F9FBFD] p-2 dark:border-white/10 dark:bg-[#121A29]">
                <div class="h-full min-h-0 space-y-2 overflow-y-auto pr-1">
                    <template x-for="section in visibleTargetSections()" :key="section.id">
                        <button type="button" @click="applyTargetSection(section.id)" class="flex min-h-[56px] w-full items-center justify-between rounded-xl border border-transparent bg-white px-4 py-2.5 text-left transition hover:border-primaryColor hover:bg-[#F8FBFF] dark:bg-[#0F172A] dark:hover:bg-[#13203A]">
                            <div><p class="text-sm font-semibold text-[#394554] dark:text-white" x-text="section.label"></p><p class="mt-1 text-xs text-[#8B97A7] dark:text-[#7F92A8]" x-text="`${section.students_count} students / ${section.subjects_count} subjects`"></p></div>
                            <i class="icofont-simple-right text-xs text-[#8B97A7] dark:text-[#7F92A8]"></i>
                        </button>
                    </template>
                    <div x-show="visibleTargetSections().length === 0" class="rounded-xl border border-dashed border-[#D7E1EA] px-4 py-6 text-center text-sm text-[#8B97A7] dark:border-white/10 dark:text-[#7F92A8]" x-text="targetSectionEmptyStateMessage()"></div>
                </div>
            </div>
        </aside>
    </div>

    <div x-cloak x-show="confirmPromotionOpen" class="fixed inset-0 z-[1000] flex items-center justify-center p-4" aria-hidden="false">
        <div x-show="confirmPromotionOpen" x-transition.opacity class="absolute inset-0 bg-[#0F172A]/45 backdrop-blur-[2px]" @click="confirmPromotionOpen = false"></div>
        <aside x-show="confirmPromotionOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-y-3 opacity-0" x-transition:enter-end="translate-y-0 opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-y-0 opacity-100" x-transition:leave-end="translate-y-3 opacity-0" class="relative z-10 w-full max-w-[460px] rounded-[22px] border border-[#E6EDF4] bg-white p-5 shadow-2xl dark:border-white/10 dark:bg-[#0F172A]">
            <div class="flex items-start justify-between gap-4 border-b border-[#E6EDF4] pb-4 dark:border-white/10">
                <div><p class="text-xs font-semibold uppercase tracking-[0.24em] text-[#8B97A7] dark:text-[#7F92A8]">Student Promotion</p><h3 class="mt-2 text-xl font-bold text-[#394554] dark:text-white">Confirm Promotion</h3></div>
                <button type="button" @click="confirmPromotionOpen = false" class="px-2 text-xl leading-none text-gray-500 transition hover:text-gray-700 dark:text-gray-300 dark:hover:text-white" aria-label="Close confirmation modal">&times;</button>
            </div>
            <div class="mt-4 space-y-4">
                <p class="text-sm text-[#617184] dark:text-[#96A6BB]"><span class="font-semibold text-[#394554] dark:text-white">{{ $promotionSummary['total_students'] }}</span> student(s) from {{ $selectedSchoolYear?->label ?? 'the selected school year' }} to {{ $nextSchoolYear?->label ?? 'the next school year' }}.</p>
                <div class="grid gap-3 sm:grid-cols-3">
                    <div class="rounded-2xl border border-[#E6EDF4] px-4 py-3 dark:border-white/10"><p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#8B97A7] dark:text-[#7F92A8]">Promoted</p><p class="mt-2 text-xl font-bold text-[#394554] dark:text-white" x-text="outcomeSummary().promoted"></p></div>
                    <div class="rounded-2xl border border-[#E6EDF4] px-4 py-3 dark:border-white/10"><p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#8B97A7] dark:text-[#7F92A8]">Retained</p><p class="mt-2 text-xl font-bold text-[#394554] dark:text-white" x-text="outcomeSummary().retained"></p></div>
                    <div class="rounded-2xl border border-[#E6EDF4] px-4 py-3 dark:border-white/10"><p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#8B97A7] dark:text-[#7F92A8]">Graduated / Out</p><p class="mt-2 text-xl font-bold text-[#394554] dark:text-white" x-text="outcomeSummary().graduated + outcomeSummary().transferred_out"></p></div>
                </div>
                <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                    <button type="button" @click="confirmPromotionOpen = false" class="inline-flex h-11 items-center justify-center rounded-xl border border-[#D7E1EA] px-4 text-sm font-semibold text-[#394554] transition hover:border-primaryColor hover:text-primaryColor dark:border-white/10 dark:text-white">Cancel</button>
                    <button type="button" @click="submitExecution()" class="inline-flex h-11 items-center justify-center rounded-xl bg-primaryColor px-4 text-sm font-semibold text-white transition hover:brightness-110">Confirm</button>
                </div>
            </div>
        </aside>
    </div>

    <div x-cloak x-show="managePanelOpen" class="fixed inset-0 z-[1000] flex items-center justify-center p-4" aria-hidden="false">
        <div x-show="managePanelOpen" x-transition.opacity class="absolute inset-0 bg-[#0F172A]/45 backdrop-blur-[2px]" @click="managePanelOpen = false; closeCreatePanel();"></div>
        <aside x-show="managePanelOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-y-3 opacity-0" x-transition:enter-end="translate-y-0 opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-y-0 opacity-100" x-transition:leave-end="translate-y-3 opacity-0" class="relative z-10 flex h-[min(72vh,560px)] min-h-0 w-full max-w-[500px] flex-col rounded-[22px] border border-[#E6EDF4] bg-white p-5 shadow-2xl dark:border-white/10 dark:bg-[#0F172A]">
            <div class="flex items-start justify-between gap-4 border-b border-[#E6EDF4] pb-4 dark:border-white/10">
                <div>
                    <div class="flex items-center gap-2">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[#8B97A7] dark:text-[#7F92A8]">Manage School Years</p>
                        <span class="inline-flex h-6 w-6 items-center justify-center text-[#2864FF] dark:text-[#8EB6FF]"><i class="icofont-ui-folder text-sm"></i></span>
                    </div>
                    <h3 class="mt-2 text-xl font-bold text-[#394554] dark:text-white">Select School Year</h3>
                </div>
                <div class="flex items-center gap-2">
                    <div x-show="hasManagedDecades()" x-cloak class="relative hidden sm:block">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-[#8B97A7] dark:text-[#7F92A8]"><i class="icofont-search-1 text-xs"></i></span>
                        <input type="text" x-model="searchTerm" placeholder="Search year" class="h-9 w-[150px] rounded-lg border border-[#D7E1EA] bg-[#F9FBFD] pl-8 pr-3 text-xs text-[#394554] outline-none transition focus:border-primaryColor dark:border-white/10 dark:bg-[#121A29] dark:text-white">
                    </div>
                    <button type="button" @click="managePanelOpen = false; closeCreatePanel();" class="px-2 text-xl leading-none text-gray-500 transition hover:text-gray-700 dark:text-gray-300 dark:hover:text-white" aria-label="Close school year management panel">&times;</button>
                </div>
            </div>

            <div x-show="hasManagedDecades()" x-cloak class="mt-4 sm:hidden">
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-[#8B97A7] dark:text-[#7F92A8]"><i class="icofont-search-1 text-xs"></i></span>
                    <input type="text" x-model="searchTerm" placeholder="Search year" class="h-9 w-full rounded-lg border border-[#D7E1EA] bg-[#F9FBFD] pl-8 pr-3 text-xs text-[#394554] outline-none transition focus:border-primaryColor dark:border-white/10 dark:bg-[#121A29] dark:text-white">
                </div>
            </div>

            <div class="mt-4 min-h-0 flex-1 overflow-hidden rounded-2xl border border-[#E6EDF4] bg-[#F9FBFD] p-2 dark:border-white/10 dark:bg-[#121A29]">
                <div class="flex h-full min-h-0 flex-col">
                    <div x-show="hasManagedDecades()" x-cloak class="flex items-end justify-between gap-2 px-1 pb-0 pt-1">
                        <div class="flex items-end gap-2 overflow-x-auto [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden">
                            <template x-for="decade in availableDecades()" :key="decade">
                                <div class="flex items-center gap-1">
                                    <button type="button" @click="selectedDecade = decade; expandedYearId = null; if (createPanelOpen) { closeCreatePanel(); }" class="decade-folder-tab relative -mb-px inline-flex min-w-[88px] items-center justify-center border px-3 py-2 text-xs font-semibold transition" :class="selectedDecade === decade ? 'z-10 border-b-[#F9FBFD] border-[#D7E1EA] bg-white text-primaryColor dark:border-white/10 dark:border-b-[#121A29] dark:bg-[#0F172A]' : 'border-transparent bg-[#EEF2F6] text-[#708096] hover:bg-white hover:text-primaryColor dark:bg-[#172234] dark:text-[#94A3B8]'">
                                        <span x-text="decadeLabel(decade)"></span>
                                    </button>
                                    <button type="button" x-show="canSeedNextDecade(decade)" x-cloak @click="openCreatePanel(decade)" class="inline-flex h-8 w-6 shrink-0 items-center justify-center self-center text-primaryColor transition hover:text-[#1F53D5] dark:hover:text-[#8EB6FF]" :title="`Add ${decadeLabel(nextDecade(decade))}`" :aria-label="`Add ${decadeLabel(nextDecade(decade))}`"><i class="icofont-ui-add text-sm"></i></button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="min-h-0 flex-1 overflow-hidden rounded-2xl border border-[#E6EDF4] bg-[#F9FBFD] p-2 dark:border-white/10 dark:bg-[#121A29]" :class="hasManagedDecades() ? '-mt-px rounded-tl-none' : ''">
                        <div x-show="!createPanelOpen" x-cloak class="mx-auto h-full max-h-[320px] min-h-0 w-full space-y-2 overflow-y-auto pr-1">
                            <template x-for="year in filteredSchoolYears()" :key="year.id">
                                <div class="space-y-2">
                                    <div class="flex min-h-[56px] items-center justify-between rounded-xl border bg-white px-4 py-2.5 transition dark:bg-[#0F172A]" :class="year.is_active ? 'border-[#1F8A46] bg-[#F3FBF5] dark:border-[#2E8B57] dark:bg-[#10291A]' : 'border-transparent hover:border-primaryColor hover:bg-[#F8FBFF] dark:hover:bg-[#13203A]'">
                                        <button type="button" @click="window.location.href = year.url" class="flex-1 text-left"><p class="text-sm font-semibold text-[#394554] dark:text-white" x-text="year.label"></p></button>
                                        <div class="ml-4 flex items-center gap-2">
                                            <span x-show="year.is_active" x-cloak class="rounded-full bg-[#EAF4EA] px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-[#1F8A46] dark:bg-[#12331E] dark:text-[#82D7A0]">Active</span>
                                            <button type="button" @click.stop="toggleExpandedYear(year.id)" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#8B97A7] transition hover:text-primaryColor dark:text-[#7F92A8]" aria-label="Expand school year actions"><i class="icofont-simple-right text-sm transition-transform duration-200" :class="expandedYearId === year.id ? 'rotate-90 text-primaryColor' : ''"></i></button>
                                        </div>
                                    </div>
                                    <div x-show="expandedYearId === year.id" x-cloak class="rounded-xl border border-[#E6EDF4] bg-white px-4 py-3 dark:border-white/10 dark:bg-[#0F172A]">
                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                            <div class="space-y-1 text-left">
                                                <div class="flex items-center gap-2"><i class="icofont-eye-alt text-sm text-[#8B97A7] dark:text-[#7F92A8]"></i><p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#8B97A7] dark:text-[#7F92A8]">Teacher Audit</p></div>
                                                <p class="text-sm text-[#617184] dark:text-[#96A6BB]">Allow teachers to review this school year in read-only audit mode.</p>
                                            </div>
                                            <form method="POST" :action="year.toggle_audit_url">@csrf
                                                <button type="submit" class="inline-flex h-8 w-14 items-center rounded-full p-1 transition" :class="year.teacher_audit_enabled ? 'bg-[#2864FF] dark:bg-[#4B7BFF]' : 'bg-[#D7E1EA] hover:bg-[#C8D4E0] dark:bg-[#233045] dark:hover:bg-[#2C3B52]'" :aria-pressed="year.teacher_audit_enabled ? 'true' : 'false'" aria-label="Toggle teacher audit mode">
                                                    <span class="inline-flex h-6 w-6 rounded-full bg-white shadow-sm transition-transform duration-200" :class="year.teacher_audit_enabled ? 'translate-x-6' : 'translate-x-0'"></span>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <div x-show="filteredSchoolYears().length === 0" class="rounded-xl border border-dashed border-[#D7E1EA] px-4 py-6 text-center text-sm text-[#8B97A7] dark:border-white/10 dark:text-[#7F92A8]">No school years found for this decade.</div>
                        </div>

                        <div x-show="!hasManagedDecades() && !createPanelOpen" x-cloak class="flex h-full items-center justify-center">
                            <button type="button" @click="openCreatePanel()" class="inline-flex h-10 w-10 items-center justify-center text-primaryColor transition hover:text-[#1F53D5] dark:hover:text-[#8EB6FF]" aria-label="Add current decade" title="Add current decade"><i class="icofont-ui-add text-2xl"></i></button>
                        </div>

                        <div x-show="createPanelOpen" x-cloak class="h-full min-h-0 overflow-y-auto pr-1">
                            <div class="rounded-2xl border border-[#D7E1EA] bg-white p-4 dark:border-white/10 dark:bg-[#0F172A]">
                                <div class="flex items-start justify-between gap-3 border-b border-[#E6EDF4] pb-4 dark:border-white/10">
                                    <div><p class="text-sm font-semibold text-[#394554] dark:text-white"><span x-text="createMode === 'year' ? 'Add Individual School Year' : 'Add Decade'"></span></p></div>
                                    <button type="button" @click="closeCreatePanel()" class="px-2 text-xl leading-none text-gray-500 transition hover:text-gray-700 dark:text-gray-300 dark:hover:text-white" aria-label="Close add school year form">&times;</button>
                                </div>

                                <div class="mt-4 grid grid-cols-2 gap-2">
                                    <button type="button" @click="switchCreateMode('decade')" class="inline-flex h-10 items-center justify-center rounded-xl border px-4 text-sm font-semibold transition" :class="createMode === 'decade' ? 'border-primaryColor bg-[#EEF4FF] text-primaryColor dark:border-[#4B7BFF] dark:bg-[#15253D] dark:text-[#8EB6FF]' : 'border-[#D7E1EA] text-[#394554] hover:border-primaryColor hover:text-primaryColor dark:border-white/10 dark:text-white'">Decade</button>
                                    <button type="button" @click="switchCreateMode('year')" class="inline-flex h-10 items-center justify-center rounded-xl border px-4 text-sm font-semibold transition" :class="createMode === 'year' ? 'border-primaryColor bg-[#EEF4FF] text-primaryColor dark:border-[#4B7BFF] dark:bg-[#15253D] dark:text-[#8EB6FF]' : 'border-[#D7E1EA] text-[#394554] hover:border-primaryColor hover:text-primaryColor dark:border-white/10 dark:text-white'">Individual</button>
                                </div>

                                <form x-show="createMode === 'decade'" x-cloak method="POST" action="{{ route('admin.school-year.decade.store') }}" class="mt-4 space-y-4" @submit="isCreatingYear = true">
                                    @csrf
                                    <input type="hidden" name="selected_decade" :value="selectedDecade">
                                    <input type="hidden" name="target_decade" :value="targetDecade">
                                    @error('target_decade', 'schoolYearDecadeStore')
                                        <div class="rounded-2xl border border-[#F2CACA] bg-[#FDECEC] px-4 py-3 text-sm text-[#B23A3A] dark:border-[#5B2222] dark:bg-[#2A1616] dark:text-[#F1A5A5]">{{ $message }}</div>
                                    @enderror
                                    <div class="rounded-2xl border border-[#E6EDF4] bg-[#F9FBFD] px-4 py-4 dark:border-white/10 dark:bg-[#121A29]">
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#8B97A7] dark:text-[#7F92A8]">Decade To Add</p>
                                        <p class="mt-2 text-base font-semibold text-[#394554] dark:text-white" x-text="decadeLabel(targetDecade)"></p>
                                        <div class="mt-4 max-h-[308px] overflow-y-auto pr-1"><div class="grid gap-2 sm:grid-cols-2"><template x-for="year in decadeYears()" :key="year.key"><div class="rounded-xl border border-[#E6EDF4] bg-white px-3 py-2 text-sm font-medium text-[#394554] dark:border-white/10 dark:bg-[#0F172A] dark:text-white" x-text="year.label"></div></template></div></div>
                                    </div>
                                    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                                        <button type="submit" class="inline-flex h-11 items-center justify-center rounded-xl bg-primaryColor px-4 text-sm font-semibold text-white transition hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-70" :disabled="isCreatingYear">
                                            <span x-show="!isCreatingYear" x-cloak>Confirm</span>
                                            <span x-show="isCreatingYear" x-cloak class="inline-flex items-center gap-2"><span class="inline-flex h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>Saving...</span>
                                        </button>
                                    </div>
                                </form>

                                <form x-show="createMode === 'year'" x-cloak method="POST" action="{{ route('admin.school-year.store') }}" class="mt-4 space-y-4" @submit="isCreatingYear = true">
                                    @csrf
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div class="space-y-2">
                                            <label for="school-year-start" class="text-sm font-semibold text-[#394554] dark:text-white">Start Year</label>
                                            <input id="school-year-start" type="number" name="start_year" min="2000" max="2100" readonly aria-readonly="true" x-model="candidateStartYear" class="h-11 w-full cursor-not-allowed rounded-xl border border-[#D7E1EA] bg-[#EEF2F6] px-4 text-sm text-[#394554] outline-none transition dark:border-white/10 dark:bg-[#182334] dark:text-white">
                                        </div>
                                        <div class="space-y-2">
                                            <label for="school-year-end" class="text-sm font-semibold text-[#394554] dark:text-white">End Year</label>
                                            <input id="school-year-end" type="number" name="end_year" min="2001" max="2101" readonly aria-readonly="true" x-model="candidateEndYear" class="h-11 w-full cursor-not-allowed rounded-xl border border-[#D7E1EA] bg-[#EEF2F6] px-4 text-sm text-[#394554] outline-none transition dark:border-white/10 dark:bg-[#182334] dark:text-white">
                                        </div>
                                    </div>

                                    <div x-show="yearIntegrityMessages().length > 0" x-cloak class="rounded-2xl border border-[#F2CACA] bg-[#FDECEC] px-4 py-3 text-sm text-[#B23A3A] dark:border-[#5B2222] dark:bg-[#2A1616] dark:text-[#F1A5A5]">
                                        <ul class="space-y-1"><template x-for="message in yearIntegrityMessages()" :key="message"><li x-text="message"></li></template></ul>
                                    </div>

                                    @if ($errors->schoolYearStore->any())
                                        <div class="rounded-2xl border border-[#F2CACA] bg-[#FDECEC] px-4 py-3 text-sm text-[#B23A3A] dark:border-[#5B2222] dark:bg-[#2A1616] dark:text-[#F1A5A5]"><ul class="space-y-1">@foreach ($errors->schoolYearStore->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>
                                    @endif

                                    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                                        <button type="submit" class="inline-flex h-11 items-center justify-center rounded-xl bg-primaryColor px-4 text-sm font-semibold text-white transition hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-70" :disabled="isCreatingYear || yearIntegrityMessages().length > 0">
                                            <span x-show="!isCreatingYear" x-cloak>Confirm</span>
                                            <span x-show="isCreatingYear" x-cloak class="inline-flex items-center gap-2"><span class="inline-flex h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>Saving...</span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>
@endsection
