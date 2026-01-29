@extends('layouts.app')
@section('title','Streams')

@section('actions')
  <a href="{{ route('streams.create') }}" class="btn btn-primary">
    <i class="bi bi-plus-lg me-1"></i> Add Stream
  </a>
@endsection

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span class="fw-semibold">Streams</span>
    <a href="{{ route('streams.create') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-lg me-1"></i>Add</a>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Class</th>
            <th>Stream</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($streams as $s)
            <tr>
              <td>{{ $s->classRoom->name ?? '' }}</td>
              <td class="fw-semibold">{{ $s->name }}</td>
              <td class="text-end">
                <a href="{{ route('streams.edit',$s) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                <form action="{{ route('streams.destroy',$s) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this stream?')">
                  @csrf @method('DELETE')
                  <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="3" class="text-center text-muted py-4">No streams yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  @if(method_exists($streams,'links'))
    <div class="card-footer">{{ $streams->links() }}</div>
  @endif
</div>
@endsection
