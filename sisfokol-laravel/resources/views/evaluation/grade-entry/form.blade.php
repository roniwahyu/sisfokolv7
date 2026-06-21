@extends('layouts.app')

@section('title', 'Lembar Nilai ' . $classroom->name . ' — SISFOKOL')
@section('page-title', '📝 Lembar Nilai ' . $classroom->name)

@section('content')
<div class="max-w-7xl mx-auto space-y-6" x-data="gradeBook()">
    
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-indigo-400 font-semibold tracking-wide uppercase">
                <span>TA {{ $academicYear->name }}</span>
                <span>•</span>
                <span>Semester {{ $semester->nama == 1 ? '1 (Ganjil)' : '2 (Genap)' }}</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-100 mt-1">{{ $subject->name }} — {{ $classroom->name }}</h1>
            <p class="text-sm text-slate-500">Lembar kerja pengisian nilai formatif, sumatif, dan kalkulasi rapor.</p>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('evaluation.grade-entry.index') }}" 
               class="px-4 py-2.5 rounded-2xl bg-slate-800 border border-slate-700 hover:bg-slate-700 text-slate-200 text-sm font-semibold transition">
                <i class="fas fa-arrow-left mr-1"></i> Kembali
            </a>
            <button @click="openModal = true"
                    class="px-5 py-2.5 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold transition flex items-center gap-2 shadow-lg shadow-indigo-600/20">
                <i class="fas fa-plus"></i> Tambah Penilaian
            </button>
        </div>
    </div>

    {{-- Grid / Table --}}
    <div class="rounded-3xl bg-slate-900/80 border border-slate-800 p-6 backdrop-blur-sm shadow-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-800 text-slate-400 text-xs font-semibold uppercase tracking-wider">
                        <th class="py-4 px-4 min-w-[200px] sticky left-0 bg-slate-900 z-10">Siswa</th>
                        
                        {{-- Formative Assessment Columns --}}
                        @foreach($formativeAssessments as $fa)
                            <th class="py-4 px-4 min-w-[120px] text-center border-l border-slate-800/60">
                                <div class="space-y-1">
                                    <span class="text-indigo-400 block text-[10px] tracking-wider uppercase font-bold">Formatif</span>
                                    <span class="text-slate-200 font-medium block truncate max-w-[120px]" title="{{ $fa->name }}">{{ $fa->name }}</span>
                                    <span class="text-[9px] text-slate-500 block">{{ $fa->assessment_date->format('d/m') }}</span>
                                    <button @click="saveScores({{ $fa->id }}, 'formative')" 
                                            class="mt-1 px-2 py-0.5 rounded bg-indigo-950 hover:bg-indigo-900 border border-indigo-800 text-indigo-300 text-[9px] font-semibold transition">
                                        <i class="fas fa-save mr-1"></i> Simpan
                                    </button>
                                </div>
                            </th>
                        @endforeach

                        <th class="py-4 px-4 min-w-[100px] text-center border-l border-indigo-900/60 bg-indigo-950/20 text-indigo-400">Rata Formatif</th>

                        {{-- Summative Assessment Columns --}}
                        @foreach($summativeAssessments as $sa)
                            <th class="py-4 px-4 min-w-[120px] text-center border-l border-slate-800/60">
                                <div class="space-y-1">
                                    <span class="text-amber-400 block text-[10px] tracking-wider uppercase font-bold">Sumatif</span>
                                    <span class="text-slate-200 font-medium block truncate max-w-[120px]" title="{{ $sa->name }}">{{ $sa->name }}</span>
                                    <span class="text-[9px] text-slate-500 block">{{ $sa->assessment_date->format('d/m') }}</span>
                                    <button @click="saveScores({{ $sa->id }}, 'summative')" 
                                            class="mt-1 px-2 py-0.5 rounded bg-amber-950 hover:bg-amber-900 border border-amber-800 text-amber-300 text-[9px] font-semibold transition">
                                        <i class="fas fa-save mr-1"></i> Simpan
                                    </button>
                                </div>
                            </th>
                        @endforeach

                        <th class="py-4 px-4 min-w-[100px] text-center border-l border-amber-900/60 bg-amber-950/20 text-amber-400">Rata Sumatif</th>
                        <th class="py-4 px-4 min-w-[100px] text-center border-l border-emerald-900/60 bg-emerald-950/20 text-emerald-400 font-bold">Nilai Rapor</th>
                        <th class="py-4 px-4 min-w-[80px] text-center bg-emerald-950/10 text-emerald-400 font-bold">Predikat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    <template x-for="(student, index) in students" :key="student.id">
                        <tr class="hover:bg-slate-800/20 transition">
                            <td class="py-3.5 px-4 sticky left-0 bg-slate-900/90 backdrop-blur-sm z-10">
                                <p class="font-medium text-slate-200" x-text="student.name"></p>
                                <p class="text-[10px] text-slate-500" x-text="'NIS ' + student.nis"></p>
                            </td>

                            {{-- Formative Inputs --}}
                            @foreach($formativeAssessments as $fa)
                                <td class="py-3.5 px-4 border-l border-slate-800/40 text-center">
                                    <input type="number" min="0" max="100" 
                                           x-model.number="student.formative_scores[{{ $fa->id }}]"
                                           class="w-16 px-2 py-1 text-center bg-slate-800 border border-slate-700 rounded-lg text-slate-200 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                                </td>
                            @endforeach

                            {{-- Formative Average --}}
                            <td class="py-3.5 px-4 border-l border-indigo-900/40 bg-indigo-950/10 text-center font-semibold text-indigo-300 text-sm"
                                x-text="calcFormativeAvg(student)"></td>

                            {{-- Summative Inputs --}}
                            @foreach($summativeAssessments as $sa)
                                <td class="py-3.5 px-4 border-l border-slate-800/40 text-center">
                                    <input type="number" min="0" max="100" 
                                           x-model.number="student.summative_scores[{{ $sa->id }}]"
                                           class="w-16 px-2 py-1 text-center bg-slate-800 border border-slate-700 rounded-lg text-slate-200 text-sm focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition">
                                </td>
                            @endforeach

                            {{-- Summative Average --}}
                            <td class="py-3.5 px-4 border-l border-amber-900/40 bg-amber-950/10 text-center font-semibold text-amber-300 text-sm"
                                x-text="calcSummativeAvg(student)"></td>

                            {{-- Final Score --}}
                            <td class="py-3.5 px-4 border-l border-emerald-900/40 bg-emerald-950/10 text-center font-bold text-emerald-400 text-sm"
                                x-text="calcFinalScore(student)"></td>

                            {{-- Predicate --}}
                            <td class="py-3.5 px-4 text-center font-bold text-sm"
                                :class="getPredicateColor(calcPredicate(calcFinalScore(student)))"
                                x-text="calcPredicate(calcFinalScore(student))"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Add Assessment Modal --}}
    <div x-show="openModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4 z-50" x-cloak>
        <div @click.away="openModal = false" class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-md shadow-2xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="text-base font-bold text-slate-100">Tambah Penilaian Baru</h3>
                <button @click="openModal = false" class="text-slate-400 hover:text-slate-200">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="space-y-4">
                {{-- Type --}}
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-400">Jenis Penilaian</label>
                    <select x-model="modalData.type" class="w-full px-3 py-2 rounded-xl bg-slate-800 border border-slate-700 text-slate-100 text-sm focus:outline-none focus:border-indigo-500">
                        <option value="formative">Formatif (Tugas/Harian)</option>
                        <option value="summative">Sumatif (UTS/UAS)</option>
                    </select>
                </div>
                {{-- Name --}}
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-400">Nama Penilaian</label>
                    <input type="text" x-model="modalData.name" placeholder="Contoh: Tugas 1, UH Trigonometri, UTS" 
                           class="w-full px-3 py-2 rounded-xl bg-slate-800 border border-slate-700 text-slate-100 text-sm focus:outline-none focus:border-indigo-500">
                </div>
                {{-- Date --}}
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-400">Tanggal Asesmen</label>
                    <input type="date" x-model="modalData.date" 
                           class="w-full px-3 py-2 rounded-xl bg-slate-800 border border-slate-700 text-slate-100 text-sm focus:outline-none focus:border-indigo-500">
                </div>
            </div>
            <div class="flex items-center justify-end gap-3 pt-3">
                <button @click="openModal = false" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-semibold transition">Batal</button>
                <button @click="createAssessment()" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold transition">Buat</button>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
    function gradeBook() {
        return {
            students: @json($gridData),
            openModal: false,
            modalData: {
                type: 'formative',
                name: '',
                date: new Date().toISOString().split('T')[0]
            },
            weightFormative: {{ app(\App\Support\TenantContext::class)->weight_formative ?? 0.40 }},
            weightSummative: {{ app(\App\Support\TenantContext::class)->weight_summative ?? 0.60 }},
            
            calcFormativeAvg(student) {
                let scores = Object.values(student.formative_scores).filter(s => s !== '' && s !== null);
                if (scores.length === 0) return 0;
                let sum = scores.reduce((a, b) => a + b, 0);
                return Math.round((sum / scores.length) * 100) / 100;
            },

            calcSummativeAvg(student) {
                let scores = Object.values(student.summative_scores).filter(s => s !== '' && s !== null);
                if (scores.length === 0) return 0;
                let sum = scores.reduce((a, b) => a + b, 0);
                return Math.round((sum / scores.length) * 100) / 100;
            },

            calcFinalScore(student) {
                let formAvg = this.calcFormativeAvg(student);
                let sumAvg = this.calcSummativeAvg(student);
                let final = (formAvg * this.weightFormative) + (sumAvg * this.weightSummative);
                return Math.round(final * 100) / 100;
            },

            calcPredicate(score) {
                if (score >= 90) return 'A';
                if (score >= 80) return 'B';
                if (score >= 70) return 'C';
                return 'D';
            },

            getPredicateColor(pred) {
                return {
                    'text-emerald-400': pred === 'A',
                    'text-blue-400': pred === 'B',
                    'text-amber-400': pred === 'C',
                    'text-rose-400': pred === 'D'
                };
            },

            createAssessment() {
                if (!this.modalData.name) {
                    alert('Nama penilaian harus diisi');
                    return;
                }
                
                fetch('{{ route("evaluation.assessments.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        classroom_id: {{ $classroom->id }},
                        subject_id: {{ $subject->id }},
                        type: this.modalData.type,
                        name: this.modalData.name,
                        assessment_date: this.modalData.date
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        location.reload(); // Refresh to draw new column
                    } else {
                        alert('Gagal membuat penilaian');
                    }
                })
                .catch(err => console.error(err));
            },

            saveScores(assessmentId, type) {
                let scores = this.students.map(s => {
                    return {
                        student_id: s.id,
                        score: type === 'formative' ? s.formative_scores[assessmentId] : s.summative_scores[assessmentId]
                    };
                });

                fetch('{{ route("evaluation.grade-entry.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        classroom_id: {{ $classroom->id }},
                        subject_id: {{ $subject->id }},
                        type: type,
                        assessment_id: assessmentId,
                        scores: scores
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Alert success
                        const toast = document.createElement('div');
                        toast.className = 'fixed bottom-4 right-4 bg-slate-900 border border-emerald-800 text-emerald-400 px-5 py-3 rounded-2xl shadow-xl flex items-center gap-2 z-50 text-sm font-semibold transition transform duration-300';
                        toast.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message;
                        document.body.appendChild(toast);
                        setTimeout(() => {
                            toast.classList.add('opacity-0');
                            setTimeout(() => toast.remove(), 300);
                        }, 3000);
                    } else {
                        alert('Gagal menyimpan nilai');
                    }
                })
                .catch(err => console.error(err));
            }
        };
    }
</script>
@endpush
