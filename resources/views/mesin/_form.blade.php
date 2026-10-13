{{--
    Form mesin (dipakai create & edit)
    Variabel: $action, $method ('POST'|'PUT'), $cabangs, $mesin (null saat tambah), $submitLabel
--}}
<form method="POST" action="{{ $action }}">
    @csrf
    @if (($method ?? 'POST') === 'PUT') @method('PUT') @endif

    <div class="form-body">

        <div class="field">
            <label for="idmesin">Kode Mesin</label>
            <div class="input-icon">
                <i class="bi bi-upc"></i>
                <input type="text" id="idmesin" name="idmesin"
                    class="form-control @error('idmesin') is-invalid @enderror"
                    value="{{ old('idmesin', $mesin->idmesin ?? '') }}" placeholder="Contoh: MSN-001" required>
            </div>
            @error('idmesin') <div class="field-error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="id_cabang_gedung">Cabang Gedung</label>
            <div class="input-icon">
                <i class="bi bi-geo-alt"></i>
                <select id="id_cabang_gedung" name="id_cabang_gedung"
                    class="form-select @error('id_cabang_gedung') is-invalid @enderror" required>
                    <option value="">-- Pilih Cabang --</option>
                    @foreach ($cabangs ?? $cabang as $c)
                        <option value="{{ $c->id }}"
                            {{ (string) old('id_cabang_gedung', $mesin->id_cabang_gedung ?? '') === (string) $c->id ? 'selected' : '' }}>
                            {{ $c->lokasi }}
                        </option>
                    @endforeach
                </select>
            </div>
            @error('id_cabang_gedung') <div class="field-error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="keterangan">Keterangan</label>
            <div class="input-icon">
                <i class="bi bi-card-text"></i>
                <input type="text" id="keterangan" name="keterangan"
                    class="form-control @error('keterangan') is-invalid @enderror"
                    value="{{ old('keterangan', $mesin->keterangan ?? '') }}" placeholder="Contoh: Mesin lobby lantai 1">
            </div>
            @error('keterangan') <div class="field-error">{{ $message }}</div> @enderror
        </div>

    </div>

    <div class="form-actions">
        <a href="{{ route('mesin.index') }}" class="btn-x btn-cancel"><i class="bi bi-x-lg"></i> Batal</a>
        <button type="submit" class="btn-x btn-submit"><i class="bi bi-check2-circle"></i> {{ $submitLabel }}</button>
    </div>
</form>