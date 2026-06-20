@php
    $user = auth()->user();
@endphp

<li class="nav-item">
    <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
        <i class="nav-icon fas fa-home"></i>
        <p>Beranda</p>
    </a>
</li>

@if ($user->hasRole('admin'))
    <li class="nav-item has-treeview {{ request()->routeIs('admin.*') ? 'menu-open' : '' }}">
        <a href="#" class="nav-link">
            <i class="nav-icon fas fa-cogs"></i>
            <p>Admin <i class="right fas fa-angle-left"></i></p>
        </a>
        <ul class="nav nav-treeview">
            <li class="nav-item">
                <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <i class="fas fa-users nav-icon"></i> <p>Pengguna</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.academic-years.index') }}" class="nav-link {{ request()->routeIs('admin.academic-years.*') ? 'active' : '' }}">
                    <i class="fas fa-calendar nav-icon"></i> <p>Tahun Pelajaran</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.classrooms.index') }}" class="nav-link {{ request()->routeIs('admin.classrooms.*') ? 'active' : '' }}">
                    <i class="fas fa-building nav-icon"></i> <p>Kelas</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.subjects.index') }}" class="nav-link {{ request()->routeIs('admin.subjects.*') ? 'active' : '' }}">
                    <i class="fas fa-book nav-icon"></i> <p>Mapel</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.schedules.index') }}" class="nav-link {{ request()->routeIs('admin.schedules.*') ? 'active' : '' }}">
                    <i class="fas fa-clock nav-icon"></i> <p>Jadwal</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.attendance-times.index') }}" class="nav-link {{ request()->routeIs('admin.attendance-times.*') ? 'active' : '' }}">
                    <i class="fas fa-stopwatch nav-icon"></i> <p>Waktu Presensi</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.school-profile.index') }}" class="nav-link {{ request()->routeIs('admin.school-profile.*') ? 'active' : '' }}">
                    <i class="fas fa-school nav-icon"></i> <p>Profil Sekolah</p>
                </a>
            </li>
        </ul>
    </li>
@endif

@if ($user->hasRole('teacher'))
    <li class="nav-item has-treeview {{ request()->routeIs('teacher.*') ? 'menu-open' : '' }}">
        <a href="#" class="nav-link">
            <i class="nav-icon fas fa-chalkboard-teacher"></i>
            <p>Guru <i class="right fas fa-angle-left"></i></p>
        </a>
        <ul class="nav nav-treeview">
            <li class="nav-item">
                <a href="{{ route('teacher.dashboard') }}" class="nav-link {{ request()->routeIs('teacher.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-home nav-icon"></i> <p>Dashboard</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('teacher.agendas.index') }}" class="nav-link {{ request()->routeIs('teacher.agendas.*') ? 'active' : '' }}">
                    <i class="fas fa-clipboard nav-icon"></i> <p>Jurnal Mengajar</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('teacher.competencies.index') }}" class="nav-link {{ request()->routeIs('teacher.competencies.*') ? 'active' : '' }}">
                    <i class="fas fa-bullseye nav-icon"></i> <p>Tujuan Pembelajaran</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('teacher.attendance.scan') }}" class="nav-link {{ request()->routeIs('teacher.attendance.scan*') ? 'active' : '' }}">
                    <i class="fas fa-qrcode nav-icon"></i> <p>Scan Presensi</p>
                </a>
            </li>
        </ul>
    </li>
@endif

@if ($user->hasRole('student'))
    <li class="nav-item">
        <a href="{{ route('student.dashboard') }}" class="nav-link {{ request()->routeIs('student.*') ? 'active' : '' }}">
            <i class="nav-icon fas fa-user-graduate"></i>
            <p>Siswa</p>
        </a>
    </li>
@endif

@if ($user->hasRole('homeroom-teacher'))
    <li class="nav-item has-treeview {{ request()->routeIs('homeroom.*') ? 'menu-open' : '' }}">
        <a href="#" class="nav-link">
            <i class="nav-icon fas fa-chalkboard"></i>
            <p>Wali Kelas <i class="right fas fa-angle-left"></i></p>
        </a>
        <ul class="nav nav-treeview">
            <li class="nav-item">
                <a href="{{ route('homeroom.dashboard') }}" class="nav-link {{ request()->routeIs('homeroom.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-home nav-icon"></i> <p>Dashboard</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('homeroom.projects.index') }}" class="nav-link {{ request()->routeIs('homeroom.projects.*') ? 'active' : '' }}">
                    <i class="fas fa-project-diagram nav-icon"></i> <p>Proyek Kurmer</p>
                </a>
            </li>
        </ul>
    </li>
@endif

@if ($user->hasRole('finance'))
    <li class="nav-item">
        <a href="{{ route('finance.dashboard') }}" class="nav-link {{ request()->routeIs('finance.*') ? 'active' : '' }}">
            <i class="nav-icon fas fa-money-bill-wave"></i>
            <p>Bendahara</p>
        </a>
    </li>
@endif

@if ($user->hasRole('counselor'))
    <li class="nav-item">
        <a href="{{ route('counselor.dashboard') }}" class="nav-link {{ request()->routeIs('counselor.*') ? 'active' : '' }}">
            <i class="nav-icon fas fa-user-md"></i>
            <p>Guru BK</p>
        </a>
    </li>
@endif

@if ($user->hasRole('picket-officer'))
    <li class="nav-item has-treeview {{ request()->routeIs('picket.*') ? 'menu-open' : '' }}">
        <a href="#" class="nav-link">
            <i class="nav-icon fas fa-clipboard-check"></i>
            <p>Piket <i class="right fas fa-angle-left"></i></p>
        </a>
        <ul class="nav nav-treeview">
            <li class="nav-item">
                <a href="{{ route('picket.dashboard') }}" class="nav-link {{ request()->routeIs('picket.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-home nav-icon"></i> <p>Dashboard</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('picket.absences.index') }}" class="nav-link {{ request()->routeIs('picket.absences.*') ? 'active' : '' }}">
                    <i class="fas fa-user-times nav-icon"></i> <p>Absensi</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('picket.permits.index') }}" class="nav-link {{ request()->routeIs('picket.permits.*') ? 'active' : '' }}">
                    <i class="fas fa-sign-out-alt nav-icon"></i> <p>Izin</p>
                </a>
            </li>
        </ul>
    </li>
@endif

@if ($user->hasRole('inventory'))
    <li class="nav-item">
        <a href="{{ route('inventory.dashboard') }}" class="nav-link {{ request()->routeIs('inventory.*') ? 'active' : '' }}">
            <i class="nav-icon fas fa-boxes"></i>
            <p>Sarpras</p>
        </a>
    </li>
@endif

@if ($user->hasRole('principal'))
    <li class="nav-item">
        <a href="{{ route('principal.dashboard') }}" class="nav-link {{ request()->routeIs('principal.*') ? 'active' : '' }}">
            <i class="nav-icon fas fa-user-tie"></i>
            <p>Kepala Sekolah</p>
        </a>
    </li>
@endif
