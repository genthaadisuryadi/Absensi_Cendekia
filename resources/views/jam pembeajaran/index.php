@extends('layouts.app')

@section('title', 'Jam Pembelajaran')

@section('content')

<div class="card">
    <div class="card-header flex justify-between items-center">
        <strong>Jam Pembelajaran</strong>
        <span class="text-xs text-slate-400"><i class="bi bi-arrow-repeat animate-spin"></i> Real-time Sync</span>
    </div>

    <div class="card-body">

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        {{-- Form Input Jam (Format 24 Jam) --}}
        <form action="{{ url('/jam-pembelajaran') }}" method="POST" class="row g-3 mb-4">
            @csrf

            <div class="col-md-3">
                <label class="form-label">Jam Masuk</label>
                <input type="time" name="jam_masuk" class="form-control" required>
            </div>

            <div class="col-md-3">
                <label class="form-label">Jam Pulang</label>
                <input type="time" name="jam_pulang" class="form-control" required>
            </div>

            <div class="col-md-3">
                <label class="form-label">Jam Mulai</label>
                <input type="time" name="jam_mulai" class="form-control" required>
            </div>

            <div class="col-md-3">
                <label class="form-label">Jam Selesai</label>
                <input type="time" name="jam_selesai" class="form-control" required>
            </div>

            <div class="col-12 mt-3">
                <button class="btn btn-primary">Simpan</button>
            </div>
        </form>

        {{-- Tabel Data dengan Auto-Reload Real-Time (Opsional via AJAX) --}}
        <div class="table-responsive">
            <table class="table table-bordered" id="tabelJamPembelajaran">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Jam Masuk</th>
                        <th>Jam Pulang</th>
                        <th>Jam Mulai</th>
                        <th>Jam Selesai</th>
                        <th width="120">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data as $row)
                    <tr>
                        <td>{{ $row->id }}</td>
                        <td>{{ $row->jam_masuk ? \Carbon\Carbon::parse($row->jam_masuk)->format('H:i') : '-' }}</td>
                        <td>{{ $row->jam_pulang ? \Carbon\Carbon::parse($row->jam_pulang)->format('H:i') : '-' }}</td>
                        <td>{{ $row->jam_mulai ? \Carbon\Carbon::parse($row->jam_mulai)->format('H:i') : '-' }}</td>
                        <td>{{ $row->jam_selesai ? \Carbon\Carbon::parse($row->jam_selesai)->format('H:i') : '-' }}</td>
                        <td>
                            <form action="{{ url('/jam-pembelajaran/'.$row->id) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger btn-sm"
                                    onclick="return confirm('Hapus data?')">
                                    Hapus
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    </div>
</div>

{{-- Script tambahan untuk memastikan data selalu sinkron seketika --}}
<script>
    // Memastikan setiap kali halaman dibuka, browser mengambil data terbaru (bukan dari cache memori)
    window.addEventListener( "pageshow", function ( event ) {
        if ( event.persisted ) {
            window.location.reload();
        }
    });
</script>

@endsection